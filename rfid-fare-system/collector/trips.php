<?php
// collector/trips.php
// Collector Trips Dashboard - Card Grid View

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if logged in as collector
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'collector') {
    header('Location: login.php');
    exit();
}

$collector_id = $_SESSION['user_id'];
$employee_number = $_SESSION['username'];

// Get collector name
$stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', 
                            IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                            last_name) as full_name 
                       FROM collectors WHERE id = ?");
$stmt->execute([$collector_id]);
$collector_name = $stmt->fetch()['full_name'];

// =============================================
// FUNCTION: Generate today's trips
// =============================================
function generateTodayTrips($pdo) {
    $today = date('Y-m-d');
    $generated = 0;
    
    // Get all active schedules with vessel capacity
    $stmt = $pdo->query("
        SELECT ts.*, 
               v.aircon_capacity, 
               v.non_aircon_capacity
        FROM trip_schedules ts
        JOIN destinations d ON ts.destination_id = d.id
        JOIN vessels v ON d.vessel_id = v.id
        WHERE ts.is_active = 1 AND d.is_active = 1
    ");
    $schedules = $stmt->fetchAll();
    
    foreach ($schedules as $schedule) {
        // Check if trip already exists for today
        $stmt = $pdo->prepare("SELECT id FROM trips WHERE schedule_id = ? AND trip_date = ?");
        $stmt->execute([$schedule['id'], $today]);
        if (!$stmt->fetch()) {
            // Create trip with capacity from vessel
            $stmt = $pdo->prepare("INSERT INTO trips 
                                   (schedule_id, trip_date, status, 
                                    aircon_capacity, non_aircon_capacity, 
                                    aircon_count, non_aircon_count) 
                                   VALUES (?, ?, 'upcoming', ?, ?, 0, 0)");
            $stmt->execute([
                $schedule['id'], 
                $today,
                $schedule['aircon_capacity'],
                $schedule['non_aircon_capacity']
            ]);
            $generated++;
        }
    }
    
    return $generated;
}

// =============================================
// Generate trips for today
// =============================================
$generated_count = generateTodayTrips($pdo);

// =============================================
// Get collector's assigned routes
// =============================================
$stmt = $pdo->prepare("
    SELECT destination_id 
    FROM collector_assignments 
    WHERE collector_id = ?
");
$stmt->execute([$collector_id]);
$assigned_routes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// =============================================
// Get today's trips for assigned routes
// =============================================
$trips = [];

if (count($assigned_routes) > 0) {
    $placeholders = implode(',', array_fill(0, count($assigned_routes), '?'));
    $params = array_merge($assigned_routes, [date('Y-m-d')]);
    
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            ts.departure_time,
            ts.arrival_time,
            d.origin,
            d.destination,
            d.aircon_fare,
            d.non_aircon_fare,
            v.id as vessel_id,
            v.name as vessel_name,
            v.aircon_capacity,
            v.non_aircon_capacity
        FROM trips t
        JOIN trip_schedules ts ON t.schedule_id = ts.id
        JOIN destinations d ON ts.destination_id = d.id
        JOIN vessels v ON d.vessel_id = v.id
        WHERE ts.destination_id IN ($placeholders)
        AND t.trip_date = ?
        ORDER BY ts.departure_time ASC
    ");
    $stmt->execute($params);
    $trips = $stmt->fetchAll();
}

// =============================================
// Handle Trip Actions (Start Boarding, Sail, Arrive, Cancel)
// =============================================
$action = $_GET['action'] ?? '';
$trip_id = intval($_GET['trip_id'] ?? 0);
$message = '';
$message_type = '';

if ($trip_id > 0 && !empty($action)) {
    try {
        // Get trip details
        $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
        $stmt->execute([$trip_id]);
        $trip = $stmt->fetch();
        
        if (!$trip) {
            throw new Exception('Trip not found');
        }
        
        // Check if trip belongs to collector's assigned route
        $stmt = $pdo->prepare("
            SELECT ts.destination_id 
            FROM trips t
            JOIN trip_schedules ts ON t.schedule_id = ts.id
            WHERE t.id = ?
        ");
        $stmt->execute([$trip_id]);
        $trip_destination = $stmt->fetch()['destination_id'];
        
        if (!in_array($trip_destination, $assigned_routes)) {
            throw new Exception('You are not assigned to this trip');
        }
        
        switch ($action) {
            case 'start':
                // Check if any other trip is ACCEPTING or SAILED for this collector
                $stmt = $pdo->prepare("
                    SELECT t.id, t.status 
                    FROM trips t
                    JOIN trip_schedules ts ON t.schedule_id = ts.id
                    JOIN collector_assignments ca ON ts.destination_id = ca.destination_id
                    WHERE ca.collector_id = ? 
                    AND t.status IN ('accepting', 'sailed') 
                    AND t.id != ?
                    LIMIT 1
                ");
                $stmt->execute([$collector_id, $trip_id]);
                $active_trip = $stmt->fetch();
                
                if ($active_trip) {
                    if ($active_trip['status'] == 'accepting') {
                        throw new Exception('Another trip is currently accepting passengers. Please sail and complete it first.');
                    } else {
                        throw new Exception('A trip is currently sailing. Please click ARRIVED to complete it first.');
                    }
                }
                
                $stmt = $pdo->prepare("UPDATE trips SET status = 'accepting' WHERE id = ?");
                $stmt->execute([$trip_id]);
                $message = 'Trip started! You can now accept passengers.';
                $message_type = 'success';
                break;
                
            case 'sail':
                $stmt = $pdo->prepare("UPDATE trips SET status = 'sailed', actual_departure = NOW() WHERE id = ?");
                $stmt->execute([$trip_id]);
                
                // =============================================
                // FINALIZE MANIFEST - Mark all passengers as boarded
                // =============================================
                // No need to update anything, manifest is already recorded.
                // The manifest is now "finalized" because the trip is sailed.
                
                $message = 'Trip sailed! Manifest is now finalized.';
                $message_type = 'success';
                break;
                
            case 'arrive':
                $stmt = $pdo->prepare("UPDATE trips SET status = 'arrived', actual_arrival = NOW() WHERE id = ?");
                $stmt->execute([$trip_id]);
                $message = 'Trip arrived at destination!';
                $message_type = 'success';
                break;
                
            case 'cancel':
                // =============================================
                // REFUND ALL PASSENGERS + DEDUCT POINTS
                // =============================================
                try {
                    $pdo->beginTransaction();
                    
                    // Get all transactions for this trip that are NOT yet refunded
                    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE trip_id = ? AND is_refunded = 0");
                    $stmt->execute([$trip_id]);
                    $transactions = $stmt->fetchAll();
                    
                    $refund_count = 0;
                    $total_refund = 0;
                    $total_points_deducted = 0;
                    
                    foreach ($transactions as $trans) {
                        // =============================================
                        // 1. Refund money to passenger
                        // =============================================
                        $stmt = $pdo->prepare("UPDATE passengers SET balance = balance + ? WHERE id = ?");
                        $stmt->execute([$trans['net_payment'], $trans['passenger_id']]);
                        
                        // =============================================
                        // 2. Deduct points from passenger
                        // =============================================
                        $points_to_deduct = $trans['loyalty_points_earned'] ?? 0;
                        if ($points_to_deduct > 0) {
                            $stmt = $pdo->prepare("UPDATE passengers SET loyalty_points = loyalty_points - ? WHERE id = ?");
                            $stmt->execute([$points_to_deduct, $trans['passenger_id']]);
                            $total_points_deducted += $points_to_deduct;
                        }
                        
                        // =============================================
                        // 3. Mark transaction as refunded
                        // =============================================
                        $stmt = $pdo->prepare("UPDATE transactions SET 
                                            is_refunded = 1, 
                                            refunded_at = NOW(), 
                                            points_deducted = ? 
                                            WHERE id = ?");
                        $stmt->execute([$points_to_deduct, $trans['id']]);
                        
                        // =============================================
                        // 4. Record refund in refunds table
                        // =============================================
                        $stmt = $pdo->prepare("INSERT INTO refunds 
                                            (transaction_id, passenger_id, trip_id, amount, points_deducted, reason, refunded_by) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $trans['id'],
                            $trans['passenger_id'],
                            $trip_id,
                            $trans['net_payment'],
                            $points_to_deduct,
                            'Trip cancelled',
                            $collector_id
                        ]);
                        
                        $refund_count++;
                        $total_refund += $trans['net_payment'];
                    }
                    
                    // =============================================
                    // 5. Update trip status to cancelled
                    // =============================================
                    $stmt = $pdo->prepare("UPDATE trips SET status = 'cancelled' WHERE id = ?");
                    $stmt->execute([$trip_id]);
                    
                    $pdo->commit();
                    
                    $message = 'Trip cancelled! ' . $refund_count . ' passengers refunded. Total refund: ₱' . number_format($total_refund, 2) . '. Points deducted: ' . $total_points_deducted;
                    $message_type = 'warning';
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw new Exception('Failed to cancel trip: ' . $e->getMessage());
                }
                break;
        }
        
        // Refresh trips data
        if (count($assigned_routes) > 0) {
            $placeholders = implode(',', array_fill(0, count($assigned_routes), '?'));
            $params = array_merge($assigned_routes, [date('Y-m-d')]);
            
            $stmt = $pdo->prepare("
                SELECT 
                    t.*,
                    ts.departure_time,
                    ts.arrival_time,
                    d.origin,
                    d.destination,
                    d.aircon_fare,
                    d.non_aircon_fare,
                    v.id as vessel_id,
                    v.name as vessel_name,
                    v.aircon_capacity,
                    v.non_aircon_capacity
                FROM trips t
                JOIN trip_schedules ts ON t.schedule_id = ts.id
                JOIN destinations d ON ts.destination_id = d.id
                JOIN vessels v ON d.vessel_id = v.id
                WHERE ts.destination_id IN ($placeholders)
                AND t.trip_date = ?
                ORDER BY ts.departure_time ASC
            ");
            $stmt->execute($params);
            $trips = $stmt->fetchAll();
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-ship mr-2"></i>My Trips
        </h1>
        <p class="text-sm text-gray-500">Welcome, <?php echo htmlspecialchars($collector_name); ?> (<?php echo htmlspecialchars($employee_number); ?>)</p>
    </div>
    <div class="text-sm text-gray-500">
        <?php echo date('l, F d, Y'); ?>
        <?php if ($generated_count > 0): ?>
            <span class="text-green-600 ml-2">(<?php echo $generated_count; ?> new trips generated)</span>
        <?php endif; ?>
    </div>
</div>

<?php if ($message): ?>
    <div class="mb-4 p-4 rounded-lg <?php 
        echo $message_type == 'success' ? 'bg-green-100 text-green-700 border border-green-400' : 
            ($message_type == 'warning' ? 'bg-yellow-100 text-yellow-700 border border-yellow-400' : 
            'bg-red-100 text-red-700 border border-red-400'); 
    ?>">
        <i class="fas <?php 
            echo $message_type == 'success' ? 'fa-check-circle' : 
                ($message_type == 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'); 
        ?> mr-2"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if (count($trips) > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($trips as $trip): 
            // Determine card style based on status
            $border_color = '';
            $status_badge = '';
            $action_buttons = '';
            
            // Calculate total capacity
            $total_capacity = $trip['aircon_capacity'] + $trip['non_aircon_capacity'];
            
            switch ($trip['status']) {
                case 'accepting':
                    $border_color = 'border-l-4 border-blue-500';
                    $status_badge = '<span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800 font-bold">ACCEPTING</span>';
                    $action_buttons = '
                        <a href="?action=sail&trip_id=' . $trip['id'] . '" 
                           onclick="return confirm(\'Start sailing?\')"
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">
                            <i class="fas fa-ship mr-1"></i> SAIL
                        </a>
                        <a href="?action=cancel&trip_id=' . $trip['id'] . '" 
                           onclick="return confirm(\'Cancel this trip? All passengers will be refunded.\')"
                           class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition">
                            <i class="fas fa-times mr-1"></i> CANCEL
                        </a>
                    ';
                    break;
                    
                case 'sailed':
                    $border_color = 'border-l-4 border-orange-500';
                    $status_badge = '<span class="px-2 py-1 rounded text-xs bg-orange-100 text-orange-800 font-bold">SAILED</span>';
                    $action_buttons = '
                        <a href="?action=arrive&trip_id=' . $trip['id'] . '" 
                           onclick="return confirm(\'Arrived at destination?\')"
                           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition">
                            <i class="fas fa-flag-checkered mr-1"></i> ARRIVED
                        </a>
                    ';
                    break;
                    
                case 'arrived':
                    $border_color = 'border-l-4 border-green-500';
                    $status_badge = '<span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800 font-bold">ARRIVED</span>';
                    $action_buttons = '<span class="text-gray-400 text-sm">Trip Complete</span>';
                    break;
                    
                case 'cancelled':
                    $border_color = 'border-l-4 border-red-500 opacity-60';
                    $status_badge = '<span class="px-2 py-1 rounded text-xs bg-red-100 text-red-800 font-bold">CANCELLED</span>';
                    $action_buttons = '<span class="text-gray-400 text-sm">Cancelled</span>';
                    break;
                    
                default: // upcoming
                    $border_color = 'border-l-4 border-gray-400';
                    $status_badge = '<span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-800 font-bold">UPCOMING</span>';
                    
                    // =============================================
                    // CHECK IF ANY TRIP IS ACCEPTING OR SAILED
                    // =============================================
                    $has_active = false;
                    $active_status = '';
                    foreach ($trips as $check) {
                        if ($check['status'] == 'accepting' || $check['status'] == 'sailed') {
                            $has_active = true;
                            $active_status = $check['status'];
                            break;
                        }
                    }
                    
                    if ($has_active) {
                        if ($active_status == 'accepting') {
                            $action_buttons = '<span class="text-gray-400 text-sm"><i class="fas fa-lock mr-1"></i> Another trip is accepting passengers</span>';
                        } else {
                            $action_buttons = '<span class="text-gray-400 text-sm"><i class="fas fa-ship mr-1"></i> A trip is currently sailing. Complete it first.</span>';
                        }
                    } else {
                        $action_buttons = '
                            <a href="?action=start&trip_id=' . $trip['id'] . '" 
                            onclick="return confirm(\'Start boarding passengers?\')"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition">
                                <i class="fas fa-play mr-1"></i> START BOARDING
                            </a>
                            <a href="?action=cancel&trip_id=' . $trip['id'] . '" 
                            onclick="return confirm(\'Cancel this trip? No passengers will be refunded.\')"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition">
                                <i class="fas fa-times mr-1"></i> CANCEL
                            </a>
                        ';
                    }
                    break;
            }
        ?>
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition <?php echo $border_color; ?> p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-bold text-lg"><?php echo htmlspecialchars($trip['origin'] . ' → ' . $trip['destination']); ?></h3>
                    <p class="text-sm text-gray-600">Vessel: <?php echo htmlspecialchars($trip['vessel_name']); ?></p>
                    <p class="text-sm text-gray-600">
                        Time: <?php echo date('h:i A', strtotime($trip['departure_time'])); ?> - <?php echo date('h:i A', strtotime($trip['arrival_time'])); ?>
                    </p>
                </div>
                <?php echo $status_badge; ?>
            </div>
            <div class="mt-3">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Aircon: <span class="font-bold <?php echo ($trip['aircon_count'] ?? 0) >= ($trip['aircon_capacity'] ?? 0) ? 'text-red-600' : 'text-green-600'; ?>">
                        <?php echo ($trip['aircon_count'] ?? 0); ?>/<?php echo ($trip['aircon_capacity'] ?? 0); ?>
                    </span></span>
                    <span>Non-Aircon: <span class="font-bold <?php echo ($trip['non_aircon_count'] ?? 0) >= ($trip['non_aircon_capacity'] ?? 0) ? 'text-red-600' : 'text-green-600'; ?>">
                        <?php echo ($trip['non_aircon_count'] ?? 0); ?>/<?php echo ($trip['non_aircon_capacity'] ?? 0); ?>
                    </span></span>
                    <span>Total: <span class="font-bold"><?php echo $trip['passenger_count']; ?>/<?php echo $total_capacity; ?></span></span>
                </div>
            </div>
            <div class="mt-2 flex justify-between items-center">
                <span class="text-sm text-gray-500">
                    <?php 
                        $total_remaining = $total_capacity - $trip['passenger_count'];
                        echo $total_remaining > 0 ? $total_remaining . ' seats remaining' : 'FULL';
                    ?>
                </span>
                <div class="flex gap-2">
                    <?php echo $action_buttons; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="fas fa-ship text-6xl text-gray-300 mb-4 block"></i>
        <h3 class="text-xl font-bold text-gray-600">No Trips Assigned</h3>
        <p class="text-gray-500 mt-2">You don't have any trips assigned for today.</p>
        <p class="text-gray-400 text-sm mt-1">Contact management to assign you to a route.</p>
    </div>
<?php endif; ?>

<?php include_once '../includes/footer.php'; ?>