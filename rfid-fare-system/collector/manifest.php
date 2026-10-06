<?php
// collector/manifest.php
// View Manifest for Current Active Trip

require_once 'session_config.php';
require_once '../config/database.php';

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
// GET TODAY'S TRIPS FOR THIS COLLECTOR
// =============================================
$stmt = $pdo->prepare("
    SELECT t.*, ts.destination_id, ts.departure_time, ts.arrival_time,
           d.origin, d.destination,
           v.id as vessel_id, v.name as vessel_name,
           COUNT(m.id) as manifest_count
    FROM trips t
    JOIN trip_schedules ts ON t.schedule_id = ts.id
    JOIN destinations d ON ts.destination_id = d.id
    JOIN vessels v ON d.vessel_id = v.id
    JOIN collector_assignments ca ON ts.destination_id = ca.destination_id
    LEFT JOIN manifests m ON t.id = m.trip_id
    WHERE ca.collector_id = ?
    AND t.trip_date = CURDATE()
    GROUP BY t.id
    ORDER BY ts.departure_time ASC
");
$stmt->execute([$collector_id]);
$trips = $stmt->fetchAll();

// =============================================
// GET SELECTED TRIP ID (if any)
// =============================================
$selected_trip_id = $_GET['trip_id'] ?? 0;

// If a trip is selected, get its manifest
$manifest_passengers = [];
$selected_trip = null;

if ($selected_trip_id > 0) {
    // Verify the selected trip belongs to this collector
    foreach ($trips as $trip) {
        if ($trip['id'] == $selected_trip_id) {
            $selected_trip = $trip;
            break;
        }
    }
    
    if ($selected_trip) {
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   CASE 
                       WHEN m.sex = 'M' THEN 'Male'
                       WHEN m.sex = 'F' THEN 'Female'
                       ELSE ''
                   END as sex_label
            FROM manifests m
            WHERE m.trip_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$selected_trip_id]);
        $manifest_passengers = $stmt->fetchAll();
    }
}

$total_passengers = count($manifest_passengers);

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-list mr-2"></i>Trip Manifests
        </h1>
        <p class="text-sm text-gray-500"><?php echo date('l, F d, Y'); ?></p>
    </div>
    <div class="text-sm text-gray-500">
        Collector: <span class="font-semibold"><?php echo htmlspecialchars($collector_name); ?></span>
        <span class="mx-2">|</span>
        Employee: <span class="font-semibold"><?php echo htmlspecialchars($employee_number); ?></span>
    </div>
</div>

<?php if ($selected_trip): ?>
    <!-- ============================================= -->
    <!-- SELECTED TRIP MANIFEST VIEW -->
    <!-- ============================================= -->
    
    <div class="mb-4">
        <a href="manifest.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition inline-block">
            <i class="fas fa-arrow-left mr-2"></i>Back to Trips
        </a>
    </div>

    <!-- Trip Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-xl font-bold text-gray-800">
                    <?php echo htmlspecialchars($selected_trip['origin'] . ' → ' . $selected_trip['destination']); ?>
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    <strong>Vessel:</strong> <?php echo htmlspecialchars($selected_trip['vessel_name']); ?>
                    <span class="mx-2">|</span>
                    <strong>Time:</strong> <?php echo date('h:i A', strtotime($selected_trip['departure_time'])); ?> - <?php echo date('h:i A', strtotime($selected_trip['arrival_time'])); ?>
                </p>
            </div>
            <span class="px-3 py-1 rounded text-xs font-bold <?php 
                echo $selected_trip['status'] == 'upcoming'  ? 'bg-gray-100 text-gray-800' : 
                    ($selected_trip['status'] == 'accepting' ? 'bg-blue-100 text-blue-800' : 
                    ($selected_trip['status'] == 'sailed'    ? 'bg-orange-100 text-orange-800' : 
                    ($selected_trip['status'] == 'arrived'   ? 'bg-green-100 text-green-800' : 
                    ($selected_trip['status'] == 'cancelled' ? 'bg-red-100 text-red-800' : 
                    'bg-yellow-100 text-yellow-800'))));
            ?>">
                <?php echo strtoupper($selected_trip['status']); ?>
            </span>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="bg-blue-100 rounded-full p-3 mr-4">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Passengers</p>
                    <p class="text-2xl font-bold"><?php echo $total_passengers; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="bg-green-100 rounded-full p-3 mr-4">
                    <i class="fas fa-male text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Male</p>
                    <p class="text-2xl font-bold">
                        <?php 
                            $male_count = 0;
                            foreach ($manifest_passengers as $p) {
                                if ($p['sex'] == 'M') $male_count++;
                            }
                            echo $male_count;
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="bg-pink-100 rounded-full p-3 mr-4">
                    <i class="fas fa-female text-pink-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Female</p>
                    <p class="text-2xl font-bold">
                        <?php 
                            $female_count = 0;
                            foreach ($manifest_passengers as $p) {
                                if ($p['sex'] == 'F') $female_count++;
                            }
                            echo $female_count;
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Manifest Table -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-bold">
                <i class="fas fa-list mr-2 text-gray-600"></i>Passenger List
            </h2>
            <?php if ($selected_trip['status'] == 'accepting'): ?>
                <span class="text-sm text-green-600">
                    <i class="fas fa-plus-circle mr-1"></i> Passengers are being added...
                </span>
            <?php elseif ($selected_trip['status'] == 'sailed'): ?>
                <span class="text-sm text-blue-600">
                    <i class="fas fa-lock mr-1"></i> Manifest Finalized
                </span>
            <?php elseif ($selected_trip['status'] == 'arrived'): ?>
                <span class="text-sm text-gray-600">
                    <i class="fas fa-check-circle mr-1"></i> Trip Completed
                </span>
            <?php elseif ($selected_trip['status'] == 'cancelled'): ?>
                <span class="text-sm text-red-600">
                    <i class="fas fa-times-circle mr-1"></i> Trip Cancelled
                </span>
            <?php endif; ?>
        </div>
        <div class="overflow-x-auto">
            <?php if (count($manifest_passengers) > 0): ?>
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Full Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sex</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php $count = 1; ?>
                    <?php foreach ($manifest_passengers as $passenger): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo $count++; ?></td>
                        <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($passenger['passenger_name']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo $passenger['age'] ?? '-'; ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo $passenger['sex_label'] ?? '-'; ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($passenger['address'] ?? '-'); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs <?php 
                                echo $passenger['passenger_type'] == 'regular' ? 'bg-gray-100 text-gray-800' : 
                                    ($passenger['passenger_type'] == 'senior' ? 'bg-purple-100 text-purple-800' : 
                                    ($passenger['passenger_type'] == 'pwd' ? 'bg-blue-100 text-blue-800' : 
                                    ($passenger['passenger_type'] == 'child' ? 'bg-orange-100 text-orange-800' :
                                    ($passenger['passenger_type'] == 'infant' ? 'bg-pink-100 text-pink-800' :
                                    'bg-green-100 text-green-800'))));
                            ?>">
                                <?php echo ucfirst($passenger['passenger_type'] ?? 'Regular'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs <?php 
                                echo $passenger['section'] == 'aircon' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; 
                            ?>">
                                <?php echo ucfirst($passenger['section'] ?? 'Aircon'); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="text-center text-gray-500 py-12">
                <i class="fas fa-users text-4xl mb-2 block text-gray-300"></i>
                <p>No passengers on manifest yet</p>
                <p class="text-sm mt-2">Passengers will be added automatically after fare payment</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- ============================================= -->
    <!-- TRIP LIST VIEW (No trip selected) -->
    <!-- ============================================= -->
    
    <?php if (count($trips) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($trips as $trip): 
                // Determine card style based on status
                $border_color = '';
                $status_badge = '';
                
                switch ($trip['status']) {
                    case 'upcoming':
                        $border_color = 'border-l-4 border-gray-400';
                        $status_badge = '<span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-800 font-bold">UPCOMING</span>';
                        break;
                    case 'accepting':
                        $border_color = 'border-l-4 border-blue-500';
                        $status_badge = '<span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800 font-bold">ACCEPTING</span>';
                        break;
                    case 'sailed':
                        $border_color = 'border-l-4 border-orange-500';
                        $status_badge = '<span class="px-2 py-1 rounded text-xs bg-orange-100 text-orange-800 font-bold">SAILED</span>';
                        break;
                    case 'arrived':
                        $border_color = 'border-l-4 border-green-500';
                        $status_badge = '<span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800 font-bold">ARRIVED</span>';
                        break;
                    case 'cancelled':
                        $border_color = 'border-l-4 border-red-500 opacity-60';
                        $status_badge = '<span class="px-2 py-1 rounded text-xs bg-red-100 text-red-800 font-bold">CANCELLED</span>';
                        break;
                    default:
                        $border_color = 'border-l-4 border-gray-400';
                        $status_badge = '<span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-800 font-bold">UPCOMING</span>';
                }
            ?>
            <a href="manifest.php?trip_id=<?php echo $trip['id']; ?>" class="bg-white rounded-lg shadow hover:shadow-lg transition <?php echo $border_color; ?> p-6 block">
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
                <div class="mt-4 flex justify-between items-center">
                    <span class="text-sm text-gray-500">
                        <i class="fas fa-users mr-1"></i> Manifest: <?php echo $trip['manifest_count']; ?> passengers
                    </span>
                    <span class="text-blue-600 text-sm font-medium">
                        View <i class="fas fa-arrow-right ml-1"></i>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <i class="fas fa-list text-6xl text-gray-300 mb-4 block"></i>
            <h3 class="text-xl font-bold text-gray-600">No Trips Today</h3>
            <p class="text-gray-500 mt-2">You don't have any trips assigned for today.</p>
            <p class="text-gray-400 text-sm mt-1">Contact management to assign you to a route.</p>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php include_once '../includes/footer.php'; ?>