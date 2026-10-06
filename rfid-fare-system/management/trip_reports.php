<?php
// management/trip_reports.php
// Trip Reports for Management - Trip Data Only

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
$status_filter = $_GET['status'] ?? '';
$trip_filter = $_GET['trip_id'] ?? '';

// Build date condition
$date_condition = "";
$params = [];

if ($date_filter == 'today') {
    $date_condition = "t.trip_date = CURDATE()";
    $period_label = "Today";
} elseif ($date_filter == 'yesterday') {
    $date_condition = "t.trip_date = CURDATE() - INTERVAL 1 DAY";
    $period_label = "Yesterday";
} elseif ($date_filter == 'this_week') {
    $date_condition = "YEARWEEK(t.trip_date, 1) = YEARWEEK(CURDATE(), 1)";
    $period_label = "This Week";
} elseif ($date_filter == 'this_month') {
    $date_condition = "MONTH(t.trip_date) = MONTH(CURDATE()) AND YEAR(t.trip_date) = YEAR(CURDATE())";
    $period_label = "This Month";
} elseif ($date_filter == 'custom' && !empty($start_date) && !empty($end_date)) {
    $date_condition = "t.trip_date BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
    $period_label = date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
} else {
    $date_condition = "t.trip_date = CURDATE()";
    $period_label = "Today";
}

$status_condition = "";
if (!empty($status_filter)) {
    $status_condition = "AND t.status = ?";
    $params[] = $status_filter;
}

$trip_condition = "";
if (!empty($trip_filter)) {
    $trip_condition = "AND t.id = ?";
    $params[] = $trip_filter;
}

// =============================================
// GET TRIP SUMMARY
// =============================================
$sql = "
    SELECT 
        COUNT(DISTINCT t.id) as total_trips,
        SUM(t.passenger_count) as total_passengers
    FROM trips t
    WHERE " . $date_condition . "
    " . $status_condition . "
    " . $trip_condition . "
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$summary = $stmt->fetch();

// =============================================
// GET TRIPS LIST
// =============================================
$sql = "
    SELECT 
        t.id,
        t.trip_date,
        t.status,
        t.passenger_count,
        ts.departure_time,
        ts.arrival_time,
        d.origin,
        d.destination,
        v.name as vessel_name,
        COUNT(m.id) as manifest_count
    FROM trips t
    JOIN trip_schedules ts ON t.schedule_id = ts.id
    JOIN destinations d ON ts.destination_id = d.id
    JOIN vessels v ON d.vessel_id = v.id
    LEFT JOIN manifests m ON t.id = m.trip_id
    WHERE " . $date_condition . "
    " . $status_condition . "
    " . $trip_condition . "
    GROUP BY t.id
    ORDER BY t.trip_date DESC, ts.departure_time ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll();

// =============================================
// GET TRIPS FOR DROPDOWN
// =============================================
$sql = "
    SELECT t.id, 
           CONCAT(d.origin, ' → ', d.destination, ' (', DATE_FORMAT(t.trip_date, '%Y-%m-%d'), ' ', TIME_FORMAT(ts.departure_time, '%h:%i %p'), ')') as trip_label
    FROM trips t
    JOIN trip_schedules ts ON t.schedule_id = ts.id
    JOIN destinations d ON ts.destination_id = d.id
    ORDER BY t.trip_date DESC, ts.departure_time DESC
    LIMIT 100
";
$trip_options = $pdo->query($sql)->fetchAll();

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-chart-bar mr-2"></i>Trip Reports
    </h1>
    <div class="text-sm text-gray-500">
        Period: <span class="font-semibold"><?php echo $period_label; ?></span>
    </div>
</div>

<!-- ============================================= -->
<!-- FILTERS -->
<!-- ============================================= -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
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
        
        <div id="custom_date_range" class="md:col-span-2 lg:col-span-2 <?php echo $date_filter != 'custom' ? 'hidden' : ''; ?>">
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
            <label class="block text-gray-700 text-sm font-bold mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">All Status</option>
                <option value="upcoming" <?php echo $status_filter == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                <option value="accepting" <?php echo $status_filter == 'accepting' ? 'selected' : ''; ?>>Accepting</option>
                <option value="sailed" <?php echo $status_filter == 'sailed' ? 'selected' : ''; ?>>Sailed</option>
                <option value="arrived" <?php echo $status_filter == 'arrived' ? 'selected' : ''; ?>>Arrived</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-1">Trip</label>
            <select name="trip_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">All Trips</option>
                <?php foreach ($trip_options as $trip): ?>
                    <option value="<?php echo $trip['id']; ?>" <?php echo $trip_filter == $trip['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($trip['trip_label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex items-end gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition w-full">
                <i class="fas fa-search mr-2"></i>Apply
            </button>
            <a href="trip_reports.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition text-center w-full">
                <i class="fas fa-sync-alt mr-2"></i>Reset
            </a>
        </div>
    </form>
</div>

<!-- ============================================= -->
<!-- SUMMARY CARDS -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-ship text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Trips</p>
                <p class="text-2xl font-bold"><?php echo $summary['total_trips'] ?? 0; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-users text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Passengers</p>
                <p class="text-2xl font-bold"><?php echo $summary['total_passengers'] ?? 0; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- TRIPS LIST -->
<!-- ============================================= -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-list mr-2 text-gray-600"></i>Trip Details
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trip</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vessel</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passengers</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($trips) > 0): ?>
                    <?php foreach ($trips as $trip): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            <?php echo htmlspecialchars($trip['origin'] . ' → ' . $trip['destination']); ?>
                            <div class="text-xs text-gray-400">
                                <?php echo date('h:i A', strtotime($trip['departure_time'])); ?> - <?php echo date('h:i A', strtotime($trip['arrival_time'])); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm"><?php echo date('M d, Y', strtotime($trip['trip_date'])); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($trip['vessel_name']); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs <?php 
                                echo $trip['status'] == 'accepting' ? 'bg-green-100 text-green-800' : 
                                    ($trip['status'] == 'sailed' ? 'bg-blue-100 text-blue-800' : 
                                    ($trip['status'] == 'arrived' ? 'bg-gray-100 text-gray-800' : 
                                    ($trip['status'] == 'cancelled' ? 'bg-red-100 text-red-800' : 
                                    'bg-yellow-100 text-yellow-800')));
                            ?>">
                                <?php echo strtoupper($trip['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm font-bold"><?php echo $trip['passenger_count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-chart-bar text-4xl mb-2 block text-gray-300"></i>
                            No trips found for the selected filters.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

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