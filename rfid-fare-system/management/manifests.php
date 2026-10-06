<?php
// management/manifests.php
// View Manifests for All Trips (Management)

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

// =============================================
// GET FILTER PARAMETERS
// =============================================
$date_filter = $_GET['date_filter'] ?? 'today';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$trip_id = $_GET['trip_id'] ?? '';

// =============================================
// BUILD QUERY
// =============================================
$query = "
    SELECT 
        t.id as trip_id,
        t.trip_date,
        t.status as trip_status,
        t.passenger_count,
        ts.departure_time,
        ts.arrival_time,
        d.origin,
        d.destination,
        v.name as vessel_name,
        COUNT(m.id) as manifest_count,
        GROUP_CONCAT(
            CONCAT(m.passenger_name, '|', m.age, '|', m.sex, '|', m.address)
            SEPARATOR '||'
        ) as passengers_data
    FROM trips t
    JOIN trip_schedules ts ON t.schedule_id = ts.id
    JOIN destinations d ON ts.destination_id = d.id
    JOIN vessels v ON d.vessel_id = v.id
    LEFT JOIN manifests m ON t.id = m.trip_id
    WHERE 1=1
";

$params = [];

// Date filter
if ($date_filter == 'today') {
    $query .= " AND t.trip_date = CURDATE()";
} elseif ($date_filter == 'yesterday') {
    $query .= " AND t.trip_date = CURDATE() - INTERVAL 1 DAY";
} elseif ($date_filter == 'this_week') {
    $query .= " AND YEARWEEK(t.trip_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($date_filter == 'this_month') {
    $query .= " AND MONTH(t.trip_date) = MONTH(CURDATE()) AND YEAR(t.trip_date) = YEAR(CURDATE())";
} elseif ($date_filter == 'custom' && !empty($start_date) && !empty($end_date)) {
    $query .= " AND t.trip_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
}

// Specific trip filter
if (!empty($trip_id)) {
    $query .= " AND t.id = ?";
    $params[] = $trip_id;
}

$query .= " GROUP BY t.id ORDER BY t.trip_date DESC, ts.departure_time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$trips = $stmt->fetchAll();

// =============================================
// GET TRIPS FOR DROPDOWN
// =============================================
$stmt = $pdo->query("
    SELECT t.id, 
           CONCAT(d.origin, ' → ', d.destination, ' (', DATE_FORMAT(t.trip_date, '%Y-%m-%d'), ')') as trip_label
    FROM trips t
    JOIN trip_schedules ts ON t.schedule_id = ts.id
    JOIN destinations d ON ts.destination_id = d.id
    ORDER BY t.trip_date DESC
    LIMIT 50
");
$trip_options = $stmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-list mr-2"></i>Passenger Manifests
    </h1>
</div>

<!-- Filter Form -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-1">Date Range</label>
            <select name="date_filter" id="date_filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg" onchange="toggleCustomDate()">
                <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="yesterday" <?php echo $date_filter == 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                <option value="this_week" <?php echo $date_filter == 'this_week' ? 'selected' : ''; ?>>This Week</option>
                <option value="this_month" <?php echo $date_filter == 'this_month' ? 'selected' : ''; ?>>This Month</option>
                <option value="custom" <?php echo $date_filter == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
            </select>
        </div>
        
        <div id="custom_date_range" class="md:col-span-2 <?php echo $date_filter != 'custom' ? 'hidden' : ''; ?>">
            <label class="block text-gray-700 text-sm font-bold mb-1">Custom Date Range</label>
            <div class="flex gap-2">
                <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                <span class="py-2">to</span>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
            </div>
        </div>
        
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-1">Trip</label>
            <select name="trip_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">All Trips</option>
                <?php foreach ($trip_options as $trip): ?>
                    <option value="<?php echo $trip['id']; ?>" <?php echo $trip_id == $trip['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($trip['trip_label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex items-end gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition w-full">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
            <a href="manifests.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition text-center w-full">
                <i class="fas fa-sync-alt mr-2"></i>Reset
            </a>
        </div>
    </form>
</div>

<!-- Manifests List -->
<?php if (count($trips) > 0): ?>
    <?php foreach ($trips as $trip): 
        // Parse passengers data
        $passengers = [];
        if (!empty($trip['passengers_data'])) {
            $raw_passengers = explode('||', $trip['passengers_data']);
            foreach ($raw_passengers as $raw) {
                $parts = explode('|', $raw);
                if (count($parts) >= 4) {
                    $passengers[] = [
                        'name' => $parts[0],
                        'age' => $parts[1],
                        'sex' => $parts[2],
                        'address' => $parts[3]
                    ];
                }
            }
        }
    ?>
    <div class="bg-white rounded-lg shadow mb-6">
        <!-- Trip Header -->
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center flex-wrap">
            <div>
                <h3 class="font-bold text-lg">
                    <?php echo htmlspecialchars($trip['origin'] . ' → ' . $trip['destination']); ?>
                </h3>
                <p class="text-sm text-gray-600">
                    <strong>Vessel:</strong> <?php echo htmlspecialchars($trip['vessel_name']); ?>
                    <span class="mx-2">|</span>
                    <strong>Date:</strong> <?php echo date('F d, Y', strtotime($trip['trip_date'])); ?>
                    <span class="mx-2">|</span>
                    <strong>Time:</strong> <?php echo date('h:i A', strtotime($trip['departure_time'])); ?> - <?php echo date('h:i A', strtotime($trip['arrival_time'])); ?>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm">
                    <strong>Passengers:</strong> <?php echo $trip['manifest_count']; ?>
                </span>
                <span class="px-2 py-1 rounded text-xs font-bold <?php 
                    echo $trip['trip_status'] == 'upcoming'  ? 'bg-gray-100 text-gray-800' : 
                        ($trip['trip_status'] == 'accepting' ? 'bg-blue-100 text-blue-800' : 
                        ($trip['trip_status'] == 'sailed'    ? 'bg-orange-100 text-orange-800' : 
                        ($trip['trip_status'] == 'arrived'   ? 'bg-green-100 text-green-800' : 
                        ($trip['trip_status'] == 'cancelled' ? 'bg-red-100 text-red-800' : 
                        'bg-yellow-100 text-yellow-800'))));
                ?>">
                    <?php echo strtoupper($trip['trip_status']); ?>
                </span>
            </div>
        </div>
        
        <!-- Passenger Table -->
        <div class="overflow-x-auto p-4">
            <?php if (count($passengers) > 0): ?>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Full Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sex</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php $count = 1; ?>
                        <?php foreach ($passengers as $p): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-500"><?php echo $count++; ?></td>
                            <td class="px-4 py-2 font-medium"><?php echo htmlspecialchars($p['name']); ?></td>
                            <td class="px-4 py-2"><?php echo $p['age'] ?? '-'; ?></td>
                            <td class="px-4 py-2"><?php echo $p['sex'] == 'M' ? 'Male' : ($p['sex'] == 'F' ? 'Female' : '-'); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($p['address'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center text-gray-500 py-4">No passengers on this manifest</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="fas fa-list text-6xl text-gray-300 mb-4 block"></i>
        <h3 class="text-xl font-bold text-gray-600">No Manifests Found</h3>
        <p class="text-gray-500 mt-2">No trips found for the selected filters.</p>
    </div>
<?php endif; ?>

<script>
    function toggleCustomDate() {
        var dateFilter = document.getElementById('date_filter');
        var customDateDiv = document.getElementById('custom_date_range');
        if (dateFilter.value === 'custom') {
            customDateDiv.classList.remove('hidden');
        } else {
            customDateDiv.classList.add('hidden');
        }
    }
    
    // Initialize on page load
    toggleCustomDate();
</script>

<?php include_once '../includes/footer.php'; ?>