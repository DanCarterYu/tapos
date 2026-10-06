<?php
// collector/load_history.php
// Collector's Load Balance History

require_once 'session_config.php';
require_once '../config/database.php';

// Check if logged in as collector
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'collector') {
    header('Location: login.php');
    exit();
}

$collector_id = $_SESSION['user_id'];
$collector_name = $_SESSION['full_name'];
$employee_number = $_SESSION['username'];

// Get filter parameters
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'today';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build date condition
$date_condition = "";
$period_label = "";

switch ($date_filter) {
    case 'today':
        $date_condition = "DATE(bl.load_date) = CURDATE()";
        $period_label = "Today";
        break;
    case 'yesterday':
        $date_condition = "DATE(bl.load_date) = CURDATE() - INTERVAL 1 DAY";
        $period_label = "Yesterday";
        break;
    case 'this_week':
        $date_condition = "YEARWEEK(bl.load_date, 1) = YEARWEEK(CURDATE(), 1)";
        $period_label = "This Week";
        break;
    case 'this_month':
        $date_condition = "MONTH(bl.load_date) = MONTH(CURDATE()) AND YEAR(bl.load_date) = YEAR(CURDATE())";
        $period_label = "This Month";
        break;
    case 'last_month':
        $date_condition = "MONTH(bl.load_date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(bl.load_date) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
        $period_label = "Last Month";
        break;
    case 'custom':
        if (!empty($start_date) && !empty($end_date)) {
            $date_condition = "DATE(bl.load_date) BETWEEN ? AND ?";
            $period_label = date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
        } else {
            $date_condition = "DATE(bl.load_date) = CURDATE()";
            $period_label = "Today";
        }
        break;
    default:
        $date_condition = "DATE(bl.load_date) = CURDATE()";
        $period_label = "Today";
}

// Build query - ONLY collector loads (NOT management)
$query = "SELECT bl.*, 
          CONCAT(p.first_name, ' ', 
                 IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                 p.last_name) as passenger_name,
          p.rfid_uid
          FROM balance_loads bl
          JOIN passengers p ON bl.passenger_id = p.id
          WHERE bl.loaded_by = ? AND bl.loaded_by_role = 'collector' AND " . $date_condition;

$params = [$collector_id];

if ($date_filter === 'custom' && !empty($start_date) && !empty($end_date)) {
    $params[] = $start_date;
    $params[] = $end_date;
}

// Add search filter
if (!empty($search)) {
    $query .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.rfid_uid LIKE ? OR bl.reference_no LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY bl.load_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$loads = $stmt->fetchAll();

// Get summary
$total_loads = count($loads);
$total_amount = array_sum(array_column($loads, 'amount'));
$unique_passengers = count(array_unique(array_column($loads, 'passenger_id')));
$avg_load = $total_loads > 0 ? $total_amount / $total_loads : 0;

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-history mr-2 text-blue-500"></i>Load History
    </h1>
    <div class="text-sm text-gray-500">
        Collector: <span class="font-semibold"><?php echo htmlspecialchars($collector_name); ?></span>
        <span class="mx-2">|</span>
        Employee: <span class="font-semibold"><?php echo htmlspecialchars($employee_number); ?></span>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-list text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Loads</p>
                <p class="text-2xl font-bold"><?php echo $total_loads; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-coins text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Amount</p>
                <p class="text-2xl font-bold">₱<?php echo number_format($total_amount, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-purple-100 rounded-full p-3 mr-4">
                <i class="fas fa-users text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Unique Passengers</p>
                <p class="text-2xl font-bold"><?php echo $unique_passengers; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Average Load</p>
                <p class="text-2xl font-bold">₱<?php echo number_format($avg_load, 2); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-1">Date Range</label>
            <select name="date_filter" id="date_filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg" onchange="toggleCustomDate()">
                <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="yesterday" <?php echo $date_filter == 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                <option value="this_week" <?php echo $date_filter == 'this_week' ? 'selected' : ''; ?>>This Week</option>
                <option value="this_month" <?php echo $date_filter == 'this_month' ? 'selected' : ''; ?>>This Month</option>
                <option value="last_month" <?php echo $date_filter == 'last_month' ? 'selected' : ''; ?>>Last Month</option>
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
        
        <div class="md:col-span-3">
            <label class="block text-gray-700 text-sm font-bold mb-1">Search</label>
            <input type="text" name="search" placeholder="Search by passenger, RFID UID, or reference..." 
                   value="<?php echo htmlspecialchars($search); ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        
        <div class="flex items-end gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition w-full">
                <i class="fas fa-search mr-2"></i>Apply Filters
            </button>
            <a href="load_history.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition text-center w-full">
                <i class="fas fa-sync-alt mr-2"></i>Reset
            </a>
        </div>
    </form>
</div>

<!-- Load History Table -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-receipt mr-2 text-gray-600"></i>Load Records
            <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo $period_label; ?>)</span>
        </h2>
    </div>
    <div class="overflow-x-auto">
        <?php if (count($loads) > 0): ?>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passenger</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">RFID UID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference No.</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($loads as $load): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('M d, Y h:i A', strtotime($load['load_date'])); ?></td>
                    <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($load['passenger_name']); ?></td>
                    <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($load['rfid_uid']); ?></td>
                    <td class="px-6 py-4 text-sm font-bold text-green-600">₱<?php echo number_format($load['amount'], 2); ?></td>
                    <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($load['reference_no']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50 border-t">
                <tr>
                    <td colspan="3" class="px-6 py-3 text-right font-bold">Total:</td>
                    <td class="px-6 py-3 font-bold text-green-600">₱<?php echo number_format($total_amount, 2); ?></td>
                    <td class="px-6 py-3"><?php echo $total_loads; ?> load(s)</td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
        <div class="text-center text-gray-500 py-12">
            <i class="fas fa-history text-4xl mb-2 block"></i>
            <p>No load records found for this period</p>
            <p class="text-sm mt-2">Load balances for passengers will appear here</p>
        </div>
        <?php endif; ?>
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