<?php
// management/transactions.php
// Transactions Reports for Management

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

// =============================================
// CSV EXPORT HANDLER - MUST BE AT THE TOP
// =============================================
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Get the same filters as the main query
    $date_filter = $_GET['date_filter'] ?? 'today';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $destination_id = $_GET['destination_id'] ?? '';
    $collector_id = $_GET['collector_id'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build date conditions
    $date_condition = "";
    $date_params = [];
    
    switch ($date_filter) {
        case 'today':
            $date_condition = "DATE(t.transaction_date) = CURDATE()";
            break;
        case 'yesterday':
            $date_condition = "DATE(t.transaction_date) = CURDATE() - INTERVAL 1 DAY";
            break;
        case 'this_week':
            $date_condition = "YEARWEEK(t.transaction_date, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'this_month':
            $date_condition = "MONTH(t.transaction_date) = MONTH(CURDATE()) AND YEAR(t.transaction_date) = YEAR(CURDATE())";
            break;
        case 'last_month':
            $date_condition = "MONTH(t.transaction_date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(t.transaction_date) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
            break;
        case 'custom':
            if (!empty($start_date) && !empty($end_date)) {
                $date_condition = "DATE(t.transaction_date) BETWEEN ? AND ?";
                $date_params = [$start_date, $end_date];
            }
            break;
        default:
            $date_condition = "DATE(t.transaction_date) = CURDATE()";
    }
    
    // Build query for CSV
    $query = "SELECT t.*, 
              CONCAT(p.first_name, ' ', 
                     IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                     p.last_name) as passenger_name, 
              CONCAT(c.first_name, ' ', 
                     IF(c.middle_name IS NOT NULL AND c.middle_name != '', CONCAT(c.middle_name, ' '), ''), 
                     c.last_name) as collector_name,
              c.employee_number,
              CONCAT(d.origin, ' → ', d.destination) as destination_name
              FROM transactions t
              JOIN passengers p ON t.passenger_id = p.id
              JOIN collectors c ON t.collector_id = c.id
              JOIN destinations d ON t.destination_id = d.id
              WHERE 1=1";
    
    $params = $date_params;
    
    if (!empty($date_condition)) {
        $query .= " AND " . $date_condition;
    }
    
    if (!empty($destination_id)) {
        $query .= " AND t.destination_id = ?";
        $params[] = $destination_id;
    }
    
    if (!empty($collector_id)) {
        $query .= " AND t.collector_id = ?";
        $params[] = $collector_id;
    }
    
    if (!empty($search)) {
        $query .= " AND (t.receipt_no LIKE ? OR CONCAT(p.first_name, ' ', IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), p.last_name) LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Type filter (NEW)
    $type_filter = $_GET['type'] ?? 'all';
    if ($type_filter === 'completed') {
        $query .= " AND t.is_refunded = 0";
    } elseif ($type_filter === 'cancelled') {
        $query .= " AND t.is_refunded = 1";
    }
    
    $query .= " ORDER BY t.transaction_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d_His') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
        // Add headers
    fputcsv($output, [
        'Receipt No',
        'Date & Time',
        'Passenger Name',
        'Collector Name',
        'Employee Number',
        'Route',
        'Base Fare',
        'Discount Amount',
        'Net Payment',
        'Points Earned',
        'Status'
    ]);
    
    // Add data rows
    foreach ($transactions as $transaction) {
        $status = ($transaction['is_refunded'] == 1) ? 'Cancelled' : 'Completed';
        
        fputcsv($output, [
            $transaction['receipt_no'],
            date('Y-m-d H:i:s', strtotime($transaction['transaction_date'])),
            $transaction['passenger_name'],
            $transaction['collector_name'],
            $transaction['employee_number'],
            $transaction['destination_name'],
            $transaction['base_fare'],
            $transaction['discount_amount'],
            $transaction['net_payment'],
            $transaction['loyalty_points_earned'],
            $status
        ]);
    }
    
    fclose($output);
    exit();
}
// =============================================
// END CSV EXPORT HANDLER
// =============================================

// Get filter parameters
$date_filter = $_GET['date_filter'] ?? 'today';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$destination_id = $_GET['destination_id'] ?? '';
$collector_id = $_GET['collector_id'] ?? '';
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? 'all';   // ← NEW: all | completed | cancelled

// Build date conditions based on filter type
$date_condition = "";
$date_params = [];

switch ($date_filter) {
    case 'today':
        $date_condition = "DATE(t.transaction_date) = CURDATE()";
        break;
    case 'yesterday':
        $date_condition = "DATE(t.transaction_date) = CURDATE() - INTERVAL 1 DAY";
        break;
    case 'this_week':
        $date_condition = "YEARWEEK(t.transaction_date, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'this_month':
        $date_condition = "MONTH(t.transaction_date) = MONTH(CURDATE()) AND YEAR(t.transaction_date) = YEAR(CURDATE())";
        break;
    case 'last_month':
        $date_condition = "MONTH(t.transaction_date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(t.transaction_date) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
        break;
    case 'custom':
        if (!empty($start_date) && !empty($end_date)) {
            $date_condition = "DATE(t.transaction_date) BETWEEN ? AND ?";
            $date_params = [$start_date, $end_date];
        }
        break;
    default:
        $date_condition = "DATE(t.transaction_date) = CURDATE()";
}

// Build the main query
$query = "SELECT t.*, 
          CONCAT(p.first_name, ' ', 
                 IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                 p.last_name) as passenger_name, 
          p.rfid_uid,
          CONCAT(c.first_name, ' ', 
                 IF(c.middle_name IS NOT NULL AND c.middle_name != '', CONCAT(c.middle_name, ' '), ''), 
                 c.last_name) as collector_name,
          c.employee_number,
          CONCAT(d.origin, ' → ', d.destination) as destination_name
          FROM transactions t
          JOIN passengers p ON t.passenger_id = p.id
          JOIN collectors c ON t.collector_id = c.id
          JOIN destinations d ON t.destination_id = d.id
          WHERE 1=1";

$params = $date_params;

// Add date condition
if (!empty($date_condition)) {
    $query .= " AND " . $date_condition;
}

// Add destination filter
if (!empty($destination_id)) {
    $query .= " AND t.destination_id = ?";
    $params[] = $destination_id;
}

// Add collector filter
if (!empty($collector_id)) {
    $query .= " AND t.collector_id = ?";
    $params[] = $collector_id;
}

// Add TYPE filter (NEW)
if ($type_filter === 'completed') {
    $query .= " AND t.is_refunded = 0";
} elseif ($type_filter === 'cancelled') {
    $query .= " AND t.is_refunded = 1";
}
// if 'all', no filter — show everything

// Add search filter
if (!empty($search)) {
    $query .= " AND (t.receipt_no LIKE ? OR CONCAT(p.first_name, ' ', IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), p.last_name) LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY t.transaction_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// =============================================
// Get summary data — SPLIT into Completed vs Cancelled
// =============================================
// NOTE: These are computed from the SAME date/route/collector filters
// as the table, but they ALWAYS include both completed AND cancelled
// (they ignore the Type filter) — so management sees the full picture.

// Rebuild the base query WITHOUT the type filter, for the summary cards
$summary_query = "SELECT t.*, 
                  CONCAT(d.origin, ' → ', d.destination) as destination_name
                  FROM transactions t
                  JOIN passengers p ON t.passenger_id = p.id
                  JOIN collectors c ON t.collector_id = c.id
                  JOIN destinations d ON t.destination_id = d.id
                  WHERE 1=1";

$summary_params = $date_params;

if (!empty($date_condition)) {
    $summary_query .= " AND " . $date_condition;
}
if (!empty($destination_id)) {
    $summary_query .= " AND t.destination_id = ?";
    $summary_params[] = $destination_id;
}
if (!empty($collector_id)) {
    $summary_query .= " AND t.collector_id = ?";
    $summary_params[] = $collector_id;
}
if (!empty($search)) {
    $summary_query .= " AND (t.receipt_no LIKE ? OR CONCAT(p.first_name, ' ', IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), p.last_name) LIKE ?)";
    $search_param = "%$search%";
    $summary_params[] = $search_param;
    $summary_params[] = $search_param;
}

$summary_stmt = $pdo->prepare($summary_query);
$summary_stmt->execute($summary_params);
$all_transactions = $summary_stmt->fetchAll();

// Now split them
$completed = array_filter($all_transactions, function($t) { return $t['is_refunded'] == 0; });
$cancelled = array_filter($all_transactions, function($t) { return $t['is_refunded'] == 1; });

// COMPLETED totals
$total_passengers     = count($completed);
$total_collection     = array_sum(array_column($completed, 'net_payment'));
$total_points_earned  = array_sum(array_column($completed, 'loyalty_points_earned'));
$total_discount_given = array_sum(array_column($completed, 'discount_amount'));

// CANCELLED totals
$total_cancelled_count  = count($cancelled);
$total_cancelled_amount = array_sum(array_column($cancelled, 'net_payment'));

// Get destinations for filter dropdown
$stmt = $pdo->query("SELECT id, origin, destination FROM destinations WHERE is_active = 1 ORDER BY origin");
$destinations = $stmt->fetchAll();

// Get collectors for filter dropdown - FIXED
$stmt = $pdo->query("SELECT id, employee_number, 
                     CONCAT(first_name, ' ', 
                            IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                            last_name) as full_name 
                     FROM collectors WHERE status = 'active' ORDER BY first_name");
$collectors = $stmt->fetchAll();

// Get daily summary for chart (last 7 days)
$stmt = $pdo->query("SELECT 
                     DATE(transaction_date) as date, 
                     SUM(net_payment) as daily_total,
                     COUNT(*) as daily_count
                     FROM transactions 
                     WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                     AND is_refunded = 0
                     GROUP BY DATE(transaction_date)
                     ORDER BY date ASC");
$dailySummary = $stmt->fetchAll();

// Get route summary
$stmt = $pdo->query("SELECT 
                     CONCAT(d.origin, ' → ', d.destination) as route_name,
                     COUNT(t.id) as passenger_count,
                     SUM(t.net_payment) as total_collection
                     FROM transactions t
                     JOIN destinations d ON t.destination_id = d.id
                     WHERE t.is_refunded = 0
                     GROUP BY d.id
                     ORDER BY total_collection DESC");
$destinationSummary = $stmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-receipt mr-2"></i>Transactions Reports
    </h1>
    <a href="?export=csv&<?php echo htmlspecialchars($_SERVER['QUERY_STRING']); ?>" 
       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition inline-block">
        <i class="fas fa-file-excel mr-2"></i>Export to Excel
    </a>
</div>

<!-- Summary Cards -->

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Passengers</p>
                <p class="text-2xl font-bold"><?php echo number_format($total_passengers); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-coins text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Collection</p>
                <p class="text-2xl font-bold">₱<?php echo number_format($total_collection, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-gem text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Points Earned</p>
                <p class="text-2xl font-bold"><?php echo number_format($total_points_earned); ?></p>
            </div>
        </div>
    </div>
    
        <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-purple-100 rounded-full p-3 mr-4">
                <i class="fas fa-percent text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Discount Given</p>
                <p class="text-2xl font-bold">₱<?php echo number_format($total_discount_given, 2); ?></p>
            </div>
        </div>
    </div>

    <!-- NEW: Cancelled Transactions card -->
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
        <div class="flex items-center">
            <div class="bg-red-100 rounded-full p-3 mr-4">
                <i class="fas fa-ban text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Cancelled</p>
                <p class="text-2xl font-bold text-red-600">₱<?php echo number_format($total_cancelled_amount, 2); ?></p>
                <p class="text-xs text-gray-400"><?php echo $total_cancelled_count; ?> transaction(s)</p>
            </div>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-filter mr-2 text-gray-600"></i>Filter Transactions
        </h2>
    </div>
    <div class="p-6">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Date Range</label>
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
                <label class="block text-gray-700 text-sm font-bold mb-2">Custom Date Range</label>
                <div class="flex gap-2">
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                    <span class="py-2">to</span>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Route</label>
                <select name="destination_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Routes</option>
                    <?php foreach ($destinations as $dest): ?>
                        <option value="<?php echo $dest['id']; ?>" <?php echo $destination_id == $dest['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dest['origin'] . ' → ' . $dest['destination']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Collector</label>
                <select name="collector_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Collectors</option>
                    <?php foreach ($collectors as $col): ?>
                        <option value="<?php echo $col['id']; ?>" <?php echo $collector_id == $col['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($col['employee_number'] . ' - ' . $col['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- NEW: Type filter -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Type</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="completed" <?php echo $type_filter === 'completed' ? 'selected' : ''; ?>>Completed Only</option>
                    <option value="cancelled" <?php echo $type_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled Only</option>
                </select>
            </div>
            
            <div class="md:col-span-3">
                <label class="block text-gray-700 text-sm font-bold mb-2">Search</label>
                <input type="text" name="search" placeholder="Search by Receipt No or Passenger Name..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition w-full">
                    <i class="fas fa-search mr-2"></i>Apply Filters
                </button>
                <a href="transactions.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition text-center w-full">
                    <i class="fas fa-sync-alt mr-2"></i>Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passenger</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Collector</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Base Fare</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Net Payment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($transactions) > 0): ?>
                                        <?php foreach ($transactions as $transaction): 
                        $is_cancelled = ($transaction['is_refunded'] == 1);
                        $amount_class = $is_cancelled ? 'text-red-600 line-through' : 'text-green-600';
                        $points_class = $is_cancelled ? 'text-red-500' : 'text-yellow-600';
                        $points_prefix = $is_cancelled ? '-' : '+';
                        $badge_class = $is_cancelled ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
                        $badge_text = $is_cancelled ? 'Cancelled' : 'Completed';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($transaction['receipt_no']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($transaction['passenger_name']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($transaction['collector_name']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($transaction['destination_name']); ?></td>
                        <td class="px-6 py-4 text-sm">₱<?php echo number_format($transaction['base_fare'], 2); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <?php if ($transaction['discount_amount'] > 0): ?>
                                <span class="text-green-600 text-xs">
                                    -₱<?php echo number_format($transaction['discount_amount'], 2); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm font-bold <?php echo $amount_class; ?>">₱<?php echo number_format($transaction['net_payment'], 2); ?></td>
                        <td class="px-6 py-4 text-sm <?php echo $points_class; ?>"><?php echo $points_prefix . $transaction['loyalty_points_earned']; ?> pts</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs font-medium <?php echo $badge_class; ?>">
                                <?php echo $badge_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('M d, Y h:i A', strtotime($transaction['transaction_date'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-receipt text-4xl mb-2 block"></i>
                            No transactions found for the selected filters
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
        <!-- Summary Footer -->
    <?php if (count($transactions) > 0): ?>
        <div class="px-6 py-4 border-t bg-gray-50">
            <?php
                // Count completed vs cancelled in the CURRENT filtered list
                $shown_completed = 0;
                $shown_cancelled = 0;
                $shown_completed_amount = 0;
                $shown_cancelled_amount = 0;
                $shown_completed_points = 0;

                foreach ($transactions as $t) {
                    if ($t['is_refunded'] == 1) {
                        $shown_cancelled++;
                        $shown_cancelled_amount += $t['net_payment'];
                    } else {
                        $shown_completed++;
                        $shown_completed_amount += $t['net_payment'];
                        $shown_completed_points += $t['loyalty_points_earned'];
                    }
                }
            ?>
            <p class="text-sm text-gray-600">
                Showing <strong><?php echo count($transactions); ?></strong>
                transaction<?php echo count($transactions) == 1 ? '' : 's'; ?>
                (
                    <span class="text-green-600 font-medium"><?php echo $shown_completed; ?> completed</span>,
                    <span class="text-red-600 font-medium"><?php echo $shown_cancelled; ?> cancelled</span>
                )
                &nbsp;|&nbsp;
                Net Collection: <strong class="text-green-700">₱<?php echo number_format($shown_completed_amount, 2); ?></strong>
                &nbsp;|&nbsp;
                Points Issued: <strong class="text-yellow-700"><?php echo number_format($shown_completed_points); ?></strong>
                <?php if ($shown_cancelled > 0): ?>
                    &nbsp;|&nbsp;
                    Cancelled Amount: <strong class="text-red-700">₱<?php echo number_format($shown_cancelled_amount, 2); ?></strong>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- Summary Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
    <!-- Daily Summary (Last 7 Days) -->
    <!-- <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold">
                <i class="fas fa-chart-line mr-2 text-blue-500"></i>Last 7 Days Summary
            </h2>
        </div>
        <div class="p-4">
            <div class="space-y-3">
                <?php 
                $max_daily = !empty($dailySummary) ? max(array_column($dailySummary, 'daily_total')) : 1;
                foreach ($dailySummary as $day): 
                    $bar_width = ($day['daily_total'] / $max_daily) * 100;
                ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span><?php echo date('M d, Y', strtotime($day['date'])); ?></span>
                        <span class="font-bold">₱<?php echo number_format($day['daily_total'], 2); ?></span>
                        <span class="text-gray-500">(<?php echo $day['daily_count']; ?> passengers)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="bg-blue-500 h-4 rounded-full" style="width: <?php echo $bar_width; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($dailySummary)): ?>
                    <p class="text-center text-gray-500 py-4">No transactions in the last 7 days</p>
                <?php endif; ?>
            </div>
        </div>
    </div> -->
    
    <!-- Route Summary -->
    <!-- <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold">
                <i class="fas fa-map-marker-alt mr-2 text-green-500"></i>Collection by Route
            </h2>
        </div>
        <div class="p-4">
            <div class="space-y-3">
                <?php 
                $max_dest = !empty($destinationSummary) ? max(array_column($destinationSummary, 'total_collection')) : 1;
                foreach ($destinationSummary as $dest): 
                    $bar_width = ($dest['total_collection'] / $max_dest) * 100;
                ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span><?php echo htmlspecialchars($dest['route_name']); ?></span>
                        <span class="font-bold">₱<?php echo number_format($dest['total_collection'], 2); ?></span>
                        <span class="text-gray-500">(<?php echo $dest['passenger_count']; ?> passengers)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div class="bg-green-500 h-4 rounded-full" style="width: <?php echo $bar_width; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($destinationSummary)): ?>
                    <p class="text-center text-gray-500 py-4">No transactions yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div> -->
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