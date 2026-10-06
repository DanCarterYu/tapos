<?php
// management/load_history.php
// All Collectors Load History for Management

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$collector_filter = $_GET['loaded_by'] ?? '';
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

// Build query
$query = "SELECT bl.*, 
          CONCAT(p.first_name, ' ', 
                 IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                 p.last_name) as passenger_name,
          p.rfid_uid,
          bl.loaded_by_role,
          CASE 
              WHEN bl.loaded_by_role = 'management' THEN 'Management'
              ELSE c.employee_number
          END as loaded_by_display,
          c.employee_number as collector_employee,
          CONCAT(c.first_name, ' ', 
                 IF(c.middle_name IS NOT NULL AND c.middle_name != '', CONCAT(c.middle_name, ' '), ''), 
                 c.last_name) as collector_name
          FROM balance_loads bl
          JOIN passengers p ON bl.passenger_id = p.id
          LEFT JOIN collectors c ON bl.loaded_by = c.id
          WHERE " . $date_condition;

$params = [];

if ($date_filter === 'custom' && !empty($start_date) && !empty($end_date)) {
    $params[] = $start_date;
    $params[] = $end_date;
}

if (!empty($collector_filter)) {
    if ($collector_filter === 'management') {
        $query .= " AND bl.loaded_by_role = 'management'";
    } else {
        $query .= " AND bl.loaded_by = ? AND bl.loaded_by_role = 'collector'";
        $params[] = $collector_filter;
    }
}

if (!empty($search)) {
    $query .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR c.employee_number LIKE ?)";
    $search_param = "%$search%";
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
$total_collected = array_sum(array_column($loads, 'amount'));
$unique_passengers = count(array_unique(array_column($loads, 'passenger_id')));
$unique_collectors = count(array_unique(array_column($loads, 'collector_employee')));


// Get loaded by options (Collectors + Management)
$loaded_by_options = [];

// Add Management option
$loaded_by_options[] = [
    'id' => 'management',
    'display' => 'Management (System Administrator)',
    'type' => 'management'
];

// Add Collectors
$collectors = $pdo->query("SELECT id, employee_number, first_name, middle_name, last_name 
                          FROM collectors WHERE status = 'active' 
                          ORDER BY employee_number")->fetchAll();

foreach ($collectors as $col) {
    $name = trim($col['first_name'] . ' ' . ($col['middle_name'] ? $col['middle_name'] . ' ' : '') . $col['last_name']);
    $loaded_by_options[] = [
        'id' => $col['id'],
        'display' => $col['employee_number'] . ' - ' . $name,
        'type' => 'collector'
    ];
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-history mr-2 text-blue-500"></i>Load History
    </h1>
    <div class="text-sm text-gray-500">
        All Collectors | <span class="font-semibold"><?php echo $period_label; ?></span>
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
                <p class="text-gray-500 text-sm">Total Collected</p>
                <p class="text-2xl font-bold text-green-600">₱<?php echo number_format($total_collected, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-purple-100 rounded-full p-3 mr-4">
                <i class="fas fa-users text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Passengers</p>
                <p class="text-2xl font-bold"><?php echo $unique_passengers; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-user-tie text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Collectors</p>
                <p class="text-2xl font-bold"><?php echo $unique_collectors; ?></p>
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
        
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-1">Loaded By</label>
            <select name="loaded_by" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <option value="">All (Collectors + Management)</option>
                <?php foreach ($loaded_by_options as $option): ?>
                <option value="<?php echo $option['id']; ?>" <?php echo $collector_filter == $option['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($option['display']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="md:col-span-3">
            <label class="block text-gray-700 text-sm font-bold mb-1">Search</label>
            <input type="text" name="search" placeholder="Search passenger or collector..." 
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

<!-- Loads Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <?php if (count($loads) > 0): ?>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loaded By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passenger</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($loads as $load): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('M d, Y h:i A', strtotime($load['load_date'])); ?></td>
                    <td class="px-6 py-4 text-sm font-medium">
                        <?php if ($load['loaded_by_role'] == 'management'): ?>
                            <span class="text-blue-600 font-bold">Management</span>
                            <span class="text-xs text-gray-400 block">System Administrator</span>
                        <?php else: ?>
                            <span class="text-green-600 font-bold"><?php echo htmlspecialchars($load['collector_employee']); ?></span>
                            <span class="text-xs text-gray-400 block"><?php echo htmlspecialchars($load['collector_name']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($load['passenger_name']); ?></td>
                    <td class="px-6 py-4 text-sm font-bold text-green-600">₱<?php echo number_format($load['amount'], 2); ?></td>
                    <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($load['reference_no']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50 border-t">
                <tr>
                    <td colspan="3" class="px-6 py-3 text-right font-bold">Total:</td>
                    <td class="px-6 py-3 font-bold text-green-600">₱<?php echo number_format($total_collected, 2); ?></td>
                    <td class="px-6 py-3"><?php echo $total_loads; ?> load(s)</td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
        <div class="text-center text-gray-500 py-12">
            <i class="fas fa-history text-4xl mb-2 block"></i>
            <p>No load records found</p>
            <p class="text-sm mt-2">Collectors loading balances will appear here</p>
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