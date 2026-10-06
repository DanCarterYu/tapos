<?php
// collector/fare_collection.php
// Fare Collection Module with RFID Tap - SIMPLIFIED VERSION

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/receipt_template.php';

// Check if logged in as collector
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'collector') {
    header('Location: login.php');
    exit();
}

$collector_id = $_SESSION['user_id'];
$employee_number = $_SESSION['username'];

// Get collector name from database
$stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', 
                            IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                            last_name) as full_name 
                       FROM collectors WHERE id = ?");
$stmt->execute([$collector_id]);
$collector_name = $stmt->fetch()['full_name'];

// =============================================
// CHECK FOR ACTIVE TRIP
// =============================================
$stmt = $pdo->prepare("
    SELECT t.*, ts.destination_id, ts.departure_time, ts.arrival_time,
           d.origin, d.destination, d.aircon_fare, d.non_aircon_fare,
           v.id as vessel_id, v.name as vessel_name
    FROM trips t
    JOIN trip_schedules ts ON t.schedule_id = ts.id
    JOIN destinations d ON ts.destination_id = d.id
    JOIN vessels v ON d.vessel_id = v.id
    WHERE t.id IN (
        SELECT t2.id FROM trips t2
        JOIN trip_schedules ts2 ON t2.schedule_id = ts2.id
        JOIN collector_assignments ca ON ts2.destination_id = ca.destination_id
        WHERE ca.collector_id = ? AND t2.status = 'accepting'
    )
    LIMIT 1
");
$stmt->execute([$collector_id]);
$active_trip = $stmt->fetch();

// If no active trip, redirect to trips page
if (!$active_trip) {
    $_SESSION['error'] = 'Please start a trip first before collecting fares.';
    header('Location: trips.php');
    exit();
}

// Store active trip info
$active_trip_id = $active_trip['id'];
$active_destination_id = $active_trip['destination_id'];
$active_route = $active_trip['origin'] . ' → ' . $active_trip['destination'];
$active_aircon_fare = $active_trip['aircon_fare'];
$active_non_aircon_fare = $active_trip['non_aircon_fare'];
$active_vessel_name = $active_trip['vessel_name'];
$active_departure = date('h:i A', strtotime($active_trip['departure_time']));
$active_arrival = date('h:i A', strtotime($active_trip['arrival_time']));
$active_passenger_count = $active_trip['passenger_count'];

// =============================================
// GET SEAT CAPACITY AND COUNTS
// =============================================
$stmt = $pdo->prepare("
    SELECT aircon_capacity, non_aircon_capacity, 
           aircon_count, non_aircon_count 
    FROM trips WHERE id = ?
");
$stmt->execute([$active_trip_id]);
$seat_data = $stmt->fetch();

$active_aircon_capacity = $seat_data['aircon_capacity'] ?? 0;
$active_non_aircon_capacity = $seat_data['non_aircon_capacity'] ?? 0;
$active_aircon_count = $seat_data['aircon_count'] ?? 0;
$active_non_aircon_count = $seat_data['non_aircon_count'] ?? 0;

$active_aircon_remaining = $active_aircon_capacity - $active_aircon_count;
$active_non_aircon_remaining = $active_non_aircon_capacity - $active_non_aircon_count;

// =============================================
// GET VESSEL TYPE
// =============================================
$stmt = $pdo->prepare("
    SELECT v.type as vessel_type
    FROM trips t
    JOIN trip_schedules ts ON t.schedule_id = ts.id
    JOIN destinations d ON ts.destination_id = d.id
    JOIN vessels v ON d.vessel_id = v.id
    WHERE t.id = ?
");
$stmt->execute([$active_trip_id]);
$vessel_type = $stmt->fetch()['vessel_type'] ?? 'both';

// Get discount types for dropdown
$stmt = $pdo->query("SELECT * FROM discount_types WHERE is_active = 1 ORDER BY id");
$discount_types = $stmt->fetchAll();

// =============================================
// Process single fare payment via AJAX
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_fare') {
    header('Content-Type: application/json');
    
    $rfid_uid = trim($_POST['rfid_uid']);
    $passenger_type_id = intval($_POST['passenger_type_id']);
    $section = $_POST['section'] ?? 'aircon';
    
    if (empty($rfid_uid)) {
        echo json_encode(['success' => false, 'message' => 'Please tap RFID card']);
        exit();
    }
    
    // Get passenger info
    $stmt = $pdo->prepare("SELECT *, 
                        CONCAT(first_name, ' ', 
                                IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                                last_name) as full_name 
                        FROM passengers WHERE rfid_uid = ? AND status = 'active'");
    $stmt->execute([$rfid_uid]);
    $passenger = $stmt->fetch();

    if (!$passenger) {
        echo json_encode(['success' => false, 'message' => 'Invalid or unregistered RFID card']);
        exit();
    }

    // =============================================
    // CHECK SEAT AVAILABILITY
    // =============================================
    $stmt = $pdo->prepare("
        SELECT aircon_capacity, non_aircon_capacity, 
            aircon_count, non_aircon_count 
        FROM trips WHERE id = ?
    ");
    $stmt->execute([$active_trip_id]);
    $seat_data = $stmt->fetch();

    $aircon_remaining = ($seat_data['aircon_capacity'] ?? 0) - ($seat_data['aircon_count'] ?? 0);
    $non_aircon_remaining = ($seat_data['non_aircon_capacity'] ?? 0) - ($seat_data['non_aircon_count'] ?? 0);

    if ($section == 'aircon' && $aircon_remaining <= 0) {
        echo json_encode(['success' => false, 'message' => 'Aircon section is full. Please choose Non-Aircon.']);
        exit();
    }

    if ($section == 'non_aircon' && $non_aircon_remaining <= 0) {
        echo json_encode(['success' => false, 'message' => 'Non-Aircon section is full. Please choose Aircon.']);
        exit();
    }

    // Check if both sections are full
    if ($aircon_remaining <= 0 && $non_aircon_remaining <= 0) {
        echo json_encode(['success' => false, 'message' => 'Vessel is fully booked. No seats available.']);
        exit();
    }

    // =============================================
    // CHECK IF PASSENGER ALREADY PAID FOR THIS TRIP
    // =============================================
    $stmt = $pdo->prepare("SELECT id FROM transactions WHERE passenger_id = ? AND trip_id = ?");
    $stmt->execute([$passenger['id'], $active_trip_id]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false, 
            'message' => 'This passenger has already paid for this trip.'
        ]);
        exit();
    }
    
    // USE ACTIVE TRIP'S DESTINATION
    $stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ? AND is_active = 1");
    $stmt->execute([$active_destination_id]);
    $destination = $stmt->fetch();
    
    if (!$destination) {
        echo json_encode(['success' => false, 'message' => 'Active trip has an invalid destination']);
        exit();
    }
    
    // Use section fare
    $base_fare = ($section == 'aircon') ? $destination['aircon_fare'] : $destination['non_aircon_fare'];
    
    if ($base_fare <= 0) {
        echo json_encode(['success' => false, 'message' => 'No fare set for this section']);
        exit();
    }
    
    // Get discount percentage
    $stmt = $pdo->prepare("SELECT * FROM discount_types WHERE id = ?");
    $stmt->execute([$passenger_type_id]);
    $discount_type = $stmt->fetch();
    
    $discount_percentage = $discount_type['percentage'];
    $discount_amount = ($base_fare * $discount_percentage) / 100;
    $net_payment = $base_fare - $discount_amount;
    
    // Check balance
    if ($passenger['balance'] < $net_payment) {
        echo json_encode([
            'success' => false, 
            'message' => 'Insufficient balance. Current balance: ₱' . number_format($passenger['balance'], 2)
        ]);
        exit();
    }
    
    // =============================================
    // GET LOYALTY SETTINGS - SAFE VERSION
    // =============================================
    try {
        $stmt = $pdo->query("SELECT points_per_travel, is_enabled FROM loyalty_settings LIMIT 1");
        $loyalty_settings = $stmt->fetch();
        
        $points_per_travel = $loyalty_settings['points_per_travel'] ?? 1;
        $is_enabled = $loyalty_settings['is_enabled'] ?? 1;
    } catch (Exception $e) {
        // If query fails, use defaults
        $points_per_travel = 1;
        $is_enabled = 1;
    }

    // Calculate loyalty points based on settings
    if ($is_enabled) {
        $points_earned = $points_per_travel;
    } else {
        $points_earned = 0;
    }
    
    // Calculate new balance
    $new_balance = $passenger['balance'] - $net_payment;
    $new_points = $passenger['loyalty_points'] + $points_earned;
    
    // Generate receipt number
    $receipt_no = 'RC-' . date('YmdHis') . rand(100, 999);
    
    try {
        $pdo->beginTransaction();
        
        // Update passenger balance and points
        $stmt = $pdo->prepare("UPDATE passengers SET balance = ?, loyalty_points = ? WHERE id = ?");
        $stmt->execute([$new_balance, $new_points, $passenger['id']]);
        
        // Record transaction with active trip ID
        $stmt = $pdo->prepare("INSERT INTO transactions 
                               (receipt_no, passenger_id, collector_id, destination_id, trip_id,
                                base_fare, discount_percentage, discount_amount, net_payment, 
                                balance_before, balance_after, loyalty_points_earned) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $receipt_no,
            $passenger['id'],
            $collector_id,
            $active_destination_id,
            $active_trip_id,
            $base_fare,
            $discount_percentage,
            $discount_amount,
            $net_payment,
            $passenger['balance'],
            $new_balance,
            $points_earned
        ]);
        
        // Update passenger count on trip
        $stmt = $pdo->prepare("UPDATE trips SET passenger_count = passenger_count + 1 WHERE id = ?");
        $stmt->execute([$active_trip_id]);

        // =============================================
        // UPDATE SEAT COUNTS
        // =============================================
        if ($section == 'aircon') {
            $stmt = $pdo->prepare("UPDATE trips SET aircon_count = aircon_count + 1 WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE trips SET non_aircon_count = non_aircon_count + 1 WHERE id = ?");
        }
        $stmt->execute([$active_trip_id]);

        // =============================================
        // ADD PASSENGER TO MANIFEST
        // =============================================
        $age = null;
        if (!empty($passenger['date_of_birth'])) {
            $birthDate = new DateTime($passenger['date_of_birth']);
            $today = new DateTime('today');
            $age = $birthDate->diff($today)->y;
        }
        
        // Get the transaction ID by looking up its unique receipt_no
        $stmt = $pdo->prepare("SELECT id FROM transactions WHERE receipt_no = ?");
        $stmt->execute([$receipt_no]);
        $txn_row = $stmt->fetch();
        $transaction_id = $txn_row['id'] ?? 0;
        
        $stmt = $pdo->prepare("INSERT INTO manifests 
                            (transaction_id, trip_id, passenger_id, passenger_name, age, sex, address, passenger_type, section, fare_paid, discount_amount) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $transaction_id,
            $active_trip_id,
            $passenger['id'],
            $passenger['full_name'],
            $age,
            $passenger['sex'],
            $passenger['address'] ?? '',
            $discount_type['name'],
            $section,
            $net_payment,
            $discount_amount
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment successful!',
            'receipt_no' => $receipt_no,
            'transaction_id' => $transaction_id,
            'passenger_name' => $passenger['full_name'],
            'destination' => $destination['origin'] . ' → ' . $destination['destination'],
            'section' => $section,
            'base_fare' => $base_fare,
            'discount_type' => $discount_type['name'],
            'discount_percentage' => $discount_percentage,
            'discount_amount' => $discount_amount,
            'net_payment' => $net_payment,
            'old_balance' => $passenger['balance'],
            'new_balance' => $new_balance,
            'points_earned' => $points_earned
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
    exit();
}


include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-credit-card mr-2"></i>Fare Collection
        </h1>
        <?php if ($active_trip): ?>
            <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-ship mr-1"></i> 
                    <strong>Active Trip:</strong> <?php echo htmlspecialchars($active_route); ?>
                    <span class="mx-2">|</span>
                    <strong>Vessel:</strong> <?php echo htmlspecialchars($active_vessel_name); ?>
                    <span class="mx-2">|</span>
                    <strong>Time:</strong> <?php echo $active_departure; ?> - <?php echo $active_arrival; ?>
                </p>
                <div class="mt-2 flex flex-wrap gap-4 text-sm">
                    <span class="text-blue-700">
                        <strong>Aircon:</strong> 
                        <span class="<?php echo $active_aircon_remaining <= 0 ? 'text-red-600 font-bold' : 'text-green-600 font-bold'; ?>">
                            <?php echo $active_aircon_count; ?>/<?php echo $active_aircon_capacity; ?>
                        </span>
                        <?php echo $active_aircon_remaining <= 0 ? 'FULL' : '(' . $active_aircon_remaining . ' left)'; ?>
                    </span>
                    <span class="text-blue-700">
                        <strong>Non-Aircon:</strong> 
                        <span class="<?php echo $active_non_aircon_remaining <= 0 ? 'text-red-600 font-bold' : 'text-green-600 font-bold'; ?>">
                            <?php echo $active_non_aircon_count; ?>/<?php echo $active_non_aircon_capacity; ?>
                        </span>
                        <?php echo $active_non_aircon_remaining <= 0 ? 'FULL' : '(' . $active_non_aircon_remaining . ' left)'; ?>
                    </span>
                    <span class="text-blue-700">
                        <strong>Total:</strong> 
                        <span class="font-bold"><?php echo $active_passenger_count; ?>/<?php echo $active_aircon_capacity + $active_non_aircon_capacity; ?></span>
                        <?php 
                            $total_remaining = ($active_aircon_remaining + $active_non_aircon_remaining);
                            echo $total_remaining <= 0 ? 'FULL' : '(' . $total_remaining . ' seats left)'; 
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="text-sm text-gray-500">
        Collector: <span class="font-semibold"><?php echo htmlspecialchars($collector_name); ?></span>
        <span class="mx-2">|</span>
        Employee: <span class="font-semibold"><?php echo htmlspecialchars($employee_number); ?></span>
    </div>
</div>

<!-- Alert Message -->
<div id="alertMessage" class="hidden mb-4 p-4 rounded-lg"></div>

<!-- Fare Collection Form -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- LEFT COLUMN: Fare Collection -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold">
                <i class="fas fa-receipt mr-2 text-blue-500"></i>Process Fare Payment
            </h2>
        </div>
        
        <!-- ============================================= -->
        <!-- RFID TAP SECTION - MOVED OUTSIDE TABS -->
        <!-- ============================================= -->
        <div class="bg-blue-50 rounded-lg p-4 mb-4 text-center mx-4 mt-2">
            <div class="bg-blue-100 inline-block p-3 rounded-full mb-3">
                <i class="fas fa-id-card text-blue-600 text-2xl"></i>
            </div>
            <h3 class="font-bold text-gray-800 mb-2">Tap RFID Card</h3>
            <p class="text-sm text-gray-600 mb-3">Place the passenger's RFID card on the reader</p>
            
            <input type="text" id="rfidInput" 
                   placeholder="Card UID will appear here after tapping..."
                   class="w-full text-center font-mono text-md px-4 py-3 border-2 border-blue-300 rounded-lg bg-white"
                   readonly
                   style="cursor: pointer;">
            <input type="hidden" id="hiddenRfidUid" name="rfid_uid" value="">
            <p class="text-xs text-gray-500 mt-2">
                <i class="fas fa-info-circle"></i> Tap the RFID card on the reader to auto-fill
            </p>
        </div>
        
        <!-- ============================================= -->
        <!-- TABS FOR SINGLE / MULTIPLE PASSENGERS -->
        <!-- ============================================= -->
        <div class="border-b">
            <div class="flex">
                <button id="tabSingle" class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600 hover:text-blue-700 transition">
                    <i class="fas fa-user mr-2"></i>Single Passenger
                </button>
                <button id="tabMultiple" class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition">
                    <i class="fas fa-users mr-2"></i>Multiple Passengers
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <!-- ============================================= -->
            <!-- // SINGLE PASSENGER TAB -->
            <!-- ============================================= -->
            <div id="singleTab" class="tab-content">
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-sm text-gray-500">Route</p>
                        <p class="font-bold text-gray-800"><?php echo htmlspecialchars($active_route); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">
                            <i class="fas fa-chair mr-2 text-blue-500"></i>Section
                        </label>
                        <select id="sectionSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <?php if ($vessel_type == 'aircon' || $vessel_type == 'both'): ?>
                                <option value="aircon" <?php echo $active_aircon_remaining <= 0 ? 'disabled class="text-red-500"' : ''; ?>>
                                    Aircon <?php echo $active_aircon_remaining <= 0 ? '🔴 FULL' : '(' . $active_aircon_remaining . ' seats left)'; ?>
                                </option>
                            <?php endif; ?>
                            <?php if ($vessel_type == 'non_aircon' || $vessel_type == 'both'): ?>
                                <option value="non_aircon" <?php echo $active_non_aircon_remaining <= 0 ? 'disabled class="text-red-500"' : ''; ?>>
                                    Non-Aircon <?php echo $active_non_aircon_remaining <= 0 ? '🔴 FULL' : '(' . $active_non_aircon_remaining . ' seats left)'; ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">
                            <i class="fas fa-user-tag mr-2 text-blue-500"></i>Passenger Type
                        </label>
                        <select id="passenger_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <?php foreach ($discount_types as $type): ?>
                                <?php if ($type['name'] != 'infant'): ?>
                                    <option value="<?php echo $type['id']; ?>" data-discount="<?php echo $type['percentage']; ?>" 
                                        <?php echo ($type['name'] == 'regular') ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($type['name']); ?> (<?php echo $type['percentage']; ?>% off)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button id="processBtn" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg transition font-bold text-lg">
                        <i class="fas fa-check-circle mr-2"></i>Process Payment
                    </button>
                </div>
            </div>
            
            <!-- ============================================= -->
             <!-- MULTIPLE PASSENGERS TAB (SIMPLIFIED) -->
            <!-- ============================================= -->
            <div id="multipleTab" class="tab-content hidden">
                
                <!-- RFID Card Holder Section -->
                <div class="bg-blue-50 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">RFID Card Holder</p>
                            <p class="font-bold text-lg" id="groupCardHolderName">-</p>
                            <p class="text-sm text-gray-500" id="groupCardHolderDetails">-</p>
                        </div>
                        <div>
                            <input type="hidden" id="groupRfidUid" value="">
                        </div>
                    </div>
                    <!-- Card Holder Passenger Type & Section -->
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Passenger Type</label>
                            <select id="groupCardHolderType" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                <?php foreach ($discount_types as $type): ?>
                                    <?php if ($type['name'] != 'infant'): ?>
                                        <option value="<?php echo $type['id']; ?>" data-discount="<?php echo $type['percentage']; ?>" <?php echo ($type['name'] == 'regular') ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($type['name']); ?> (<?php echo $type['percentage']; ?>% off)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Section</label>
                            <select id="groupCardHolderSection" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                <?php if ($vessel_type == 'aircon' || $vessel_type == 'both'): ?>
                                    <option value="aircon" <?php echo $active_aircon_remaining <= 0 ? 'disabled class="text-red-500"' : ''; ?>>
                                        Aircon <?php echo $active_aircon_remaining <= 0 ? '🔴 FULL' : '(' . $active_aircon_remaining . ' left)'; ?>
                                    </option>
                                <?php endif; ?>
                                <?php if ($vessel_type == 'non_aircon' || $vessel_type == 'both'): ?>
                                    <option value="non_aircon" <?php echo $active_non_aircon_remaining <= 0 ? 'disabled class="text-red-500"' : ''; ?>>
                                        Non-Aircon <?php echo $active_non_aircon_remaining <= 0 ? '🔴 FULL' : '(' . $active_non_aircon_remaining . ' left)'; ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Add Passenger Form -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <h4 class="font-bold text-gray-700 mb-3">Add Passenger</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                            <input type="text" id="groupPassengerName" placeholder="FULL NAME" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500 uppercase"
                                   style="text-transform: uppercase;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Age *</label>
                            <input type="number" id="groupPassengerAge" placeholder="Age" min="1" max="120"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sex *</label>
                            <select id="groupPassengerSex" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                <option value="">Select</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address *</label>
                            <input type="text" id="groupPassengerAddress" placeholder="ADDRESS" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500 uppercase"
                                   style="text-transform: uppercase;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Passenger Type</label>
                            <select id="groupPassengerType" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                <?php foreach ($discount_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" data-discount="<?php echo $type['percentage']; ?>" <?php echo ($type['name'] == 'regular') ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($type['name']); ?> (<?php echo $type['percentage']; ?>% off)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Section</label>
                            <select id="groupPassengerSection" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                <?php if ($vessel_type == 'aircon' || $vessel_type == 'both'): ?>
                                    <option value="aircon" <?php echo $active_aircon_remaining <= 0 ? 'disabled class="text-red-500"' : ''; ?>>
                                        Aircon <?php echo $active_aircon_remaining <= 0 ? '🔴 FULL' : '(' . $active_aircon_remaining . ' seats left)'; ?>
                                    </option>
                                <?php endif; ?>
                                <?php if ($vessel_type == 'non_aircon' || $vessel_type == 'both'): ?>
                                    <option value="non_aircon" <?php echo $active_non_aircon_remaining <= 0 ? 'disabled class="text-red-500"' : ''; ?>>
                                        Non-Aircon <?php echo $active_non_aircon_remaining <= 0 ? '🔴 FULL' : '(' . $active_non_aircon_remaining . ' seats left)'; ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <button id="addGroupPassengerBtn" 
                            class="mt-3 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm">
                        <i class="fas fa-plus mr-1"></i> Add Passenger
                    </button>
                </div>
                
                <!-- Passenger List -->
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-bold text-gray-700">Passenger List</h4>
                        <span class="text-sm text-gray-500" id="groupPassengerCount">Total: 0</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">#</th>
                                    <th class="px-3 py-2 text-left">Name</th>
                                    <th class="px-3 py-2 text-left">Age</th>
                                    <th class="px-3 py-2 text-left">Sex</th>
                                    <th class="px-3 py-2 text-left">Type</th>
                                    <th class="px-3 py-2 text-left">Section</th>
                                    <th class="px-3 py-2 text-left">Fare</th>
                                    <th class="px-3 py-2 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="groupPassengerList">
                                <tr>
                                    <td colspan="8" class="text-center text-gray-400 py-4">No passengers added yet</td>
                                </tr>
                            </tbody>
                            <tfoot id="groupPassengerTotal" class="bg-gray-50 font-bold hidden">
                                <tr>
                                    <td colspan="6" class="px-3 py-2 text-right">Total Fare:</td>
                                    <td class="px-3 py-2 text-green-600" id="groupTotalFare">₱0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Process Payment Button -->
                <button id="processGroupBtn" 
                        class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg transition font-bold text-lg">
                    <i class="fas fa-check-circle mr-2"></i>Process Payment (₱<span id="groupTotalFareBtn">0.00</span>)
                </button>
                <button id="clearGroupBtn" 
                        class="w-full mt-2 bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 rounded-lg transition text-sm">
                    <i class="fas fa-trash mr-1"></i> Clear All
                </button>
            </div>
        </div>
    </div>
    
    <!-- RIGHT COLUMN: Fare Summary -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold">
                <i class="fas fa-calculator mr-2 text-green-500"></i>Fare Summary
            </h2>
        </div>
        <div class="p-6">
            <div id="fareSummary" class="text-center text-gray-500 py-8">
                <i class="fas fa-receipt text-5xl mb-3 block text-gray-300"></i>
                <p>Select section and passenger type</p>
                <p class="text-sm mt-2">Tap RFID card to process payment</p>
            </div>
        </div>
    </div>
</div>

<!-- Last Transaction Result -->
<div id="lastTransaction" class="hidden mt-6 bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b bg-green-50">
        <h2 class="text-lg font-bold text-green-700">
            <i class="fas fa-check-circle mr-2"></i>Transaction Successful!
        </h2>
    </div>
    <div class="p-6" id="transactionDetails"></div>
</div>

<script>
// =============================================
// RFID KEYBOARD CAPTURE
// =============================================
let rfidBuffer = '';
let rfidTimer = null;
let rfidInputField = document.getElementById('rfidInput');
let hiddenRfidField = document.getElementById('hiddenRfidUid');

// =============================================
// MULTIPLE PASSENGERS VARIABLES
// =============================================
let groupPassengers = [];
let groupCardHolder = null;
let groupTotalFare = 0;

document.addEventListener('DOMContentLoaded', function() {
    rfidInputField.focus();
    updateFareSummary();
    
    // =============================================
    // TAB SWITCHING
    // =============================================
    const tabSingle = document.getElementById('tabSingle');
    const tabMultiple = document.getElementById('tabMultiple');
    const singleTab = document.getElementById('singleTab');
    const multipleTab = document.getElementById('multipleTab');
    
    tabSingle.addEventListener('click', function() {
        singleTab.classList.remove('hidden');
        multipleTab.classList.add('hidden');
        tabSingle.className = 'tab-btn px-4 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600 hover:text-blue-700 transition';
        tabMultiple.className = 'tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition';
    });
    
    tabMultiple.addEventListener('click', function() {
        multipleTab.classList.remove('hidden');
        singleTab.classList.add('hidden');
        tabMultiple.className = 'tab-btn px-4 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600 hover:text-blue-700 transition';
        tabSingle.className = 'tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition';
    });
    
    // =============================================
    // RFID CAPTURE - UPDATE GROUP CARD HOLDER
    // =============================================
    rfidInputField.addEventListener('input', function() {
        hiddenRfidField.value = this.value;
        if (this.value.length > 0) {
            loadCardHolder(this.value);
        }
    });
});

// =============================================
// RFID KEYBOARD CAPTURE
// =============================================
document.addEventListener('keypress', function(e) {
    clearTimeout(rfidTimer);
    rfidBuffer += e.key;
    
    if (e.key === 'Enter') {
        e.preventDefault();
        let cardUid = rfidBuffer.replace(/Enter/g, '').trim();
        rfidBuffer = '';
        
        if (cardUid.length > 0) {
            rfidInputField.value = cardUid;
            hiddenRfidField.value = cardUid;
            loadCardHolder(cardUid);
            
            rfidInputField.classList.add('border-green-500', 'bg-green-50');
            setTimeout(function() {
                rfidInputField.classList.remove('border-green-500', 'bg-green-50');
            }, 1000);
        }
    }
    
    rfidTimer = setTimeout(function() {
        rfidBuffer = '';
    }, 100);
});

rfidInputField.addEventListener('click', function() {
    this.focus();
});

// =============================================
// LOAD CARD HOLDER
// =============================================
function loadCardHolder(rfidUid) {
    fetch('fare_collection_ajax.php?action=get_passenger&rfid_uid=' + rfidUid)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                groupCardHolder = data.passenger;
                document.getElementById('groupRfidUid').value = rfidUid;
                document.getElementById('groupCardHolderName').textContent = data.passenger.full_name;
                document.getElementById('groupCardHolderDetails').textContent = 
                    'Age: ' + (data.passenger.age || '-') + ' | Sex: ' + (data.passenger.sex || '-');
            } else {
                document.getElementById('groupCardHolderName').textContent = 'Card not found';
                document.getElementById('groupCardHolderDetails').textContent = 'Please register this RFID card first.';
                groupCardHolder = null;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// =============================================
// FARE SUMMARY
// =============================================
function updateFareSummary() {
    var section = document.getElementById('sectionSelect').value;
    var passengerTypeId = document.getElementById('passenger_type_id').value;
    var fareDiv = document.getElementById('fareSummary');
    
    if (!passengerTypeId) {
        fareDiv.innerHTML = '<i class="fas fa-receipt text-5xl mb-3 block text-gray-300"></i><p>Select section and passenger type</p><p class="text-sm mt-2">Tap RFID card to process payment</p>';
        return;
    }
    
    var baseFare = (section === 'aircon') ? <?php echo $active_aircon_fare; ?> : <?php echo $active_non_aircon_fare; ?>;
    
    var select = document.getElementById('passenger_type_id');
    var selectedType = select.options[select.selectedIndex];
    var typeName = selectedType.text.split(' (')[0];
    var discountPercent = parseFloat(selectedType.dataset.discount) || 0;
    
    var discountAmount = (baseFare * discountPercent) / 100;
    var netPayment = baseFare - discountAmount;
    
    fareDiv.innerHTML = 
        '<div class="space-y-3">' +
            '<div class="flex justify-between py-2 border-b"><span class="text-gray-600">Route:</span><span class="font-semibold"><?php echo htmlspecialchars($active_route); ?></span></div>' +
            '<div class="flex justify-between py-2 border-b"><span class="text-gray-600">Section:</span><span class="font-semibold">' + (section.charAt(0).toUpperCase() + section.slice(1)) + '</span></div>' +
            '<div class="flex justify-between py-2 border-b"><span class="text-gray-600">Passenger Type:</span><span class="font-semibold">' + typeName + ' (' + discountPercent + '% off)</span></div>' +
            '<div class="flex justify-between py-2 border-b"><span class="text-gray-600">Base Fare:</span><span class="font-semibold">₱' + baseFare.toFixed(2) + '</span></div>' +
            (discountPercent > 0 ? '<div class="flex justify-between py-2 border-b text-green-600"><span>Discount:</span><span>-₱' + discountAmount.toFixed(2) + '</span></div>' : '') +
            '<div class="flex justify-between py-3 bg-blue-50 rounded-lg px-3 mt-2"><span class="font-bold">Net Payment:</span><span class="font-bold text-green-600 text-xl">₱' + netPayment.toFixed(2) + '</span></div>' +
            '<p class="text-xs text-gray-500 text-center mt-3"><i class="fas fa-info-circle"></i> Tap RFID card and click "Process Payment"</p>' +
        '</div>';
}

document.getElementById('sectionSelect').addEventListener('change', updateFareSummary);
document.getElementById('passenger_type_id').addEventListener('change', updateFareSummary);

// =============================================
// ADD PASSENGER TO GROUP
// =============================================
document.getElementById('addGroupPassengerBtn').addEventListener('click', function() {
    var name = document.getElementById('groupPassengerName').value.trim().toUpperCase();
    var age = document.getElementById('groupPassengerAge').value.trim();
    var sex = document.getElementById('groupPassengerSex').value;
    var address = document.getElementById('groupPassengerAddress').value.trim().toUpperCase();
    var typeSelect = document.getElementById('groupPassengerType');
    var section = document.getElementById('groupPassengerSection').value;
    
    if (!name || !age || !sex || !address) {
        showAlert('Please fill in all required fields', 'error');
        return;
    }
    
    var typeId = typeSelect.value;
    var typeName = typeSelect.options[typeSelect.selectedIndex].text;
    var discountPercent = parseFloat(typeSelect.options[typeSelect.selectedIndex].dataset.discount) || 0;
    
    var baseFare = (section === 'aircon') ? <?php echo $active_aircon_fare; ?> : <?php echo $active_non_aircon_fare; ?>;
    var discountAmount = (baseFare * discountPercent) / 100;
    var fare = baseFare - discountAmount;
    
    var passenger = {
        name: name,
        age: parseInt(age),
        sex: sex,
        address: address,
        type_id: typeId,
        type_name: typeName,
        discount: discountPercent,
        section: section,
        fare: fare
    };
    
    groupPassengers.push(passenger);
    updateGroupList();
    clearGroupForm();
    showAlert('Passenger added!', 'success');
});

// =============================================
// UPDATE GROUP LIST
// =============================================
function updateGroupList() {
    var tbody = document.getElementById('groupPassengerList');
    var total = 0;
    var html = '';
    
    if (!tbody) {
        console.error('groupPassengerList not found');
        return;
    }
    
    if (groupPassengers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-gray-400 py-4">No passengers added yet</td></tr>';
        var totalElement = document.getElementById('groupPassengerTotal');
        if (totalElement) totalElement.classList.add('hidden');
        var countElement = document.getElementById('groupPassengerCount');
        if (countElement) countElement.textContent = 'Total: 0';
        var fareElement = document.getElementById('groupTotalFare');
        if (fareElement) fareElement.textContent = '₱0.00';
        var btnElement = document.getElementById('groupTotalFareBtn');
        if (btnElement) btnElement.textContent = '0.00';
        return;
    }
    
    groupPassengers.forEach(function(p, index) {
        total += p.fare;
        html += 
            '<tr>' +
                '<td class="px-3 py-2 text-gray-500">' + (index + 1) + '</td>' +
                '<td class="px-3 py-2 font-medium">' + p.name + '</td>' +
                '<td class="px-3 py-2">' + p.age + '</td>' +
                '<td class="px-3 py-2">' + (p.sex === 'M' ? 'Male' : 'Female') + '</td>' +
                '<td class="px-3 py-2"><span class="px-2 py-1 rounded text-xs bg-gray-100">' + p.type_name + '</span></td>' +
                '<td class="px-3 py-2"><span class="px-2 py-1 rounded text-xs ' + (p.section === 'aircon' ? 'bg-blue-100' : 'bg-gray-100') + '">' + p.section.charAt(0).toUpperCase() + p.section.slice(1) + '</span></td>' +
                '<td class="px-3 py-2 text-green-600 font-bold">₱' + p.fare.toFixed(2) + '</td>' +
                '<td class="px-3 py-2 text-center"><button onclick="removeGroupPassenger(' + index + ')" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button></td>' +
            '</tr>';
    });
    
    tbody.innerHTML = html;
    
    var totalElement = document.getElementById('groupPassengerTotal');
    if (totalElement) totalElement.classList.remove('hidden');
    
    var countElement = document.getElementById('groupPassengerCount');
    if (countElement) countElement.textContent = 'Total: ' + groupPassengers.length;
    
    var fareElement = document.getElementById('groupTotalFare');
    if (fareElement) fareElement.textContent = '₱' + total.toFixed(2);
    
    var btnElement = document.getElementById('groupTotalFareBtn');
    if (btnElement) btnElement.textContent = total.toFixed(2);
}

// =============================================
// REMOVE PASSENGER FROM GROUP
// =============================================
function removeGroupPassenger(index) {
    groupPassengers.splice(index, 1);
    updateGroupList();
}

// =============================================
// CLEAR GROUP FORM
// =============================================
function clearGroupForm() {
    document.getElementById('groupPassengerName').value = '';
    document.getElementById('groupPassengerAge').value = '';
    document.getElementById('groupPassengerSex').value = '';
    document.getElementById('groupPassengerAddress').value = '';
    document.getElementById('groupPassengerName').focus();
}

// =============================================
// CLEAR ALL PASSENGERS
// =============================================
document.getElementById('clearGroupBtn').addEventListener('click', function() {
    if (groupPassengers.length === 0) return;
    if (confirm('Clear all passengers?')) {
        groupPassengers = [];
        updateGroupList();
        showAlert('All passengers cleared', 'success');
    }
});

// =============================================
// PROCESS GROUP PAYMENT
// =============================================
document.getElementById('processGroupBtn').addEventListener('click', function() {
    var rfidUid = document.getElementById('groupRfidUid').value;
    var btn = document.getElementById('processGroupBtn');
    
    if (!rfidUid) {
        showAlert('Please tap RFID card first', 'error');
        return;
    }
    
    if (!groupCardHolder) {
        showAlert('Invalid RFID card holder', 'error');
        return;
    }
    
    // =============================================
    // AUTO-ADD CARD HOLDER TO PASSENGER LIST
    // =============================================
    var cardHolderSection = document.getElementById('groupCardHolderSection').value;
    var cardHolderType = document.getElementById('groupCardHolderType').value;
    
    var cardHolderBaseFare = (cardHolderSection === 'aircon') ? <?php echo $active_aircon_fare; ?> : <?php echo $active_non_aircon_fare; ?>;
    var cardHolderTypeSelect = document.getElementById('groupCardHolderType');
    var cardHolderDiscount = parseFloat(cardHolderTypeSelect.options[cardHolderTypeSelect.selectedIndex].dataset.discount) || 0;
    var cardHolderFare = cardHolderBaseFare - ((cardHolderBaseFare * cardHolderDiscount) / 100);
    
    var passengersToPay = [];
    
    // Always add card holder as Passenger #1
    passengersToPay.push({
        name: groupCardHolder.full_name,
        age: groupCardHolder.age || 0,
        sex: groupCardHolder.sex || '',
        address: groupCardHolder.address || '',
        type_name: cardHolderTypeSelect.options[cardHolderTypeSelect.selectedIndex].text.split(' (')[0],
        section: cardHolderSection,
        fare: cardHolderFare,
        is_card_holder: true
    });
    
    // Add all other passengers
    groupPassengers.forEach(function(p) {
        passengersToPay.push({
            name: p.name,
            age: p.age,
            sex: p.sex,
            address: p.address,
            type_name: p.type_name,
            section: p.section,
            fare: p.fare,
            is_card_holder: false
        });
    });
    
    // Recalculate total fare
    var newTotalFare = 0;
    passengersToPay.forEach(function(p) {
        newTotalFare += p.fare;
    });
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    
    var data = {
        rfid_uid: rfidUid,
        passengers: passengersToPay,
        total_fare: newTotalFare,
        card_holder_boarding: true,
        card_holder_type: cardHolderType,
        trip_id: <?php echo $active_trip_id; ?>
    };
    
    console.log('Sending data:', data);
    
    fetch('fare_collection_group.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(function(response) {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(function(data) {
        console.log('Response data:', data);
        if (data.success) {
            showAlert(data.message, 'success');
            groupPassengers = [];
            updateGroupList();
            
            // Clear RFID fields
            var rfidInput = document.getElementById('rfidInput');
            var hiddenRfid = document.getElementById('hiddenRfidUid');
            var groupRfid = document.getElementById('groupRfidUid');
            var cardHolderName = document.getElementById('groupCardHolderName');
            var cardHolderDetails = document.getElementById('groupCardHolderDetails');
            
            if (rfidInput) rfidInput.value = '';
            if (hiddenRfid) hiddenRfid.value = '';
            if (groupRfid) groupRfid.value = '';
            if (cardHolderName) cardHolderName.textContent = '-';
            if (cardHolderDetails) cardHolderDetails.textContent = '-';
            groupCardHolder = null;
            
            var lastTransaction = document.getElementById('lastTransaction');
            if (lastTransaction) {
                lastTransaction.classList.remove('hidden');
                var details = document.getElementById('transactionDetails');
                if (details) {
                    details.innerHTML =
                        '<div class="grid grid-cols-2 gap-4">' +
                            '<div><p class="text-gray-500 text-sm">Receipt Number:</p><p class="font-mono font-bold">' + (data.receipt_no || 'N/A') + '</p></div>' +
                            '<div><p class="text-gray-500 text-sm">Passengers:</p><p class="font-semibold">' + (data.passenger_count || 0) + '</p></div>' +
                            '<div><p class="text-gray-500 text-sm">Total Fare:</p><p class="text-xl font-bold text-green-600">₱' + parseFloat(data.total_fare || 0).toFixed(2) + '</p></div>' +
                            '<div><p class="text-gray-500 text-sm">Points Earned:</p><p class="text-yellow-600 font-bold">+' + (data.points_earned || 0) + ' points</p></div>' +
                            '<div><p class="text-gray-500 text-sm">Date & Time:</p><p>' + new Date().toLocaleString() + '</p></div>' +
                        '</div>' +
                        '<div class="mt-4 pt-4 border-t text-center"><button onclick="printReceipt(' + data.transaction_id + ')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"><i class="fas fa-print mr-2"></i>Print Receipt</button></div>';
                }
            }
            
            var fareSummary = document.getElementById('fareSummary');
            if (fareSummary) {
                fareSummary.innerHTML = '<i class="fas fa-receipt text-5xl mb-3 block text-gray-300"></i><p>Ready for next passenger</p><p class="text-sm mt-2">Tap next RFID card</p>';
            }
            
        } else {
            showAlert(data.message || 'Payment failed', 'error');
        }
    })
    .catch(function(error) {
        console.error('Fetch error:', error);
        showAlert('Error processing payment: ' + error.message, 'error');
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Process Payment (₱<span id="groupTotalFareBtn">0.00</span>)';
    });
});

// =============================================
// PROCESS SINGLE PAYMENT
// =============================================
async function processPayment() {
    var rfidUid = document.getElementById('hiddenRfidUid').value;
    var passengerTypeId = document.getElementById('passenger_type_id').value;
    var section = document.getElementById('sectionSelect').value;
    var btn = document.getElementById('processBtn');
    
    if (!rfidUid) {
        showAlert('Please tap the RFID card first', 'error');
        document.getElementById('rfidInput').focus();
        return;
    }
    
    if (!passengerTypeId) {
        showAlert('Please select passenger type', 'error');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    
    try {
        var formData = new URLSearchParams();
        formData.append('action', 'process_fare');
        formData.append('rfid_uid', rfidUid);
        formData.append('passenger_type_id', passengerTypeId);
        formData.append('section', section);
        
        var response = await fetch('fare_collection.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        
        var data = await response.json();
        
        if (data && data.success === true) {
            var netPayment = parseFloat(data.net_payment) || 0;
            var oldBalance = parseFloat(data.old_balance) || 0;
            var newBalance = parseFloat(data.new_balance) || 0;
            var discountAmount = parseFloat(data.discount_amount) || 0;
            
            showAlert(data.message, 'success');
            
            document.getElementById('lastTransaction').classList.remove('hidden');
            document.getElementById('transactionDetails').innerHTML =
                '<div class="grid grid-cols-2 gap-4">' +
                    '<div><p class="text-gray-500 text-sm">Receipt Number:</p><p class="font-mono font-bold">' + data.receipt_no + '</p></div>' +
                    '<div><p class="text-gray-500 text-sm">Passenger:</p><p class="font-semibold">' + data.passenger_name + '</p></div>' +
                    '<div><p class="text-gray-500 text-sm">Route:</p><p>' + data.destination + '</p></div>' +
                    '<div><p class="text-gray-500 text-sm">Section:</p><p class="font-semibold">' + data.section.charAt(0).toUpperCase() + data.section.slice(1) + '</p></div>' +
                    '<div><p class="text-gray-500 text-sm">Discount Applied:</p><p class="text-green-600">' + data.discount_type + ' (' + data.discount_percentage + '%) - ₱' + discountAmount.toFixed(2) + '</p></div>' +
                    '<div><p class="text-gray-500 text-sm">Net Payment:</p><p class="text-xl font-bold text-green-600">₱' + netPayment.toFixed(2) + '</p></div>' +
                    '<div><p class="text-gray-500 text-sm">Balance Before/After:</p><p>₱' + oldBalance.toFixed(2) + ' → ₱' + newBalance.toFixed(2) + '</p></div>' +
                    '<div><p class="text-gray-500 text-sm">Loyalty Points Earned:</p><p class="text-yellow-600 font-bold">+' + data.points_earned + ' points</p></div>' +
                    '<div><p class="text-gray-500 text-sm">Date & Time:</p><p>' + new Date().toLocaleString() + '</p></div>' +
                '</div>' +
                '<div class="mt-4 pt-4 border-t text-center"><button onclick="printReceipt(' + data.transaction_id + ')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"><i class="fas fa-print mr-2"></i>Print Receipt</button></div>';
            
            document.getElementById('rfidInput').value = '';
            document.getElementById('hiddenRfidUid').value = '';
            rfidBuffer = '';
            document.getElementById('rfidInput').focus();
            
            document.getElementById('fareSummary').innerHTML = '<i class="fas fa-receipt text-5xl mb-3 block text-gray-300"></i><p>Ready for next passenger</p><p class="text-sm mt-2">Tap next RFID card</p>';
            
        } else if (data && data.success === false) {
            showAlert(data.message, 'error');
            document.getElementById('rfidInput').value = '';
            document.getElementById('hiddenRfidUid').value = '';
            rfidBuffer = '';
            document.getElementById('rfidInput').focus();
        } else {
            console.error('Unexpected response:', data);
            showAlert('Unexpected response from server', 'error');
        }
    } catch (error) {
        console.error('Fetch error:', error);
        showAlert('Error: ' + error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Process Payment';
    }
}

document.getElementById('processBtn').addEventListener('click', processPayment);

// =============================================
// SHOW ALERT
// =============================================
function showAlert(message, type) {
    var alertDiv = document.getElementById('alertMessage');
    alertDiv.className = 'mb-4 p-4 rounded-lg ' + (type === 'error' ? 'bg-red-100 text-red-700 border border-red-400' : 'bg-green-100 text-green-700 border border-green-400');
    alertDiv.innerHTML = '<i class="fas fa-' + (type === 'error' ? 'exclamation-triangle' : 'check-circle') + ' mr-2"></i> ' + message;
    alertDiv.classList.remove('hidden');
    
    setTimeout(function() {
        alertDiv.classList.add('hidden');
    }, 5000);
}

// =============================================
// PRINT RECEIPT
// =============================================
function printReceipt(transactionId) {
    if (!transactionId) {
        alert('Transaction ID not available');
        return;
    }
    window.open('receipt_print.php?transaction_id=' + transactionId, '_blank', 'width=500,height=700');
}
</script>

<?php include_once '../includes/footer.php'; ?>