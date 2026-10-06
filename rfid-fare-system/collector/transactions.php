<?php
// collector/transactions.php
// Transactions for Collector (Daily, Weekly, Monthly)

require_once 'session_config.php';
require_once '../config/database.php';

// Check if logged in as collector
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'collector') {
    header('Location: login.php');
    exit();
}

$collector_id = $_SESSION['user_id'];
$employee_number = $_SESSION['username'];

// Get collector name from database (always up to date)
$stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', 
                            IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                            last_name) as full_name 
                       FROM collectors WHERE id = ?");
$stmt->execute([$collector_id]);
$collector_name = $stmt->fetch()['full_name'];

// =============================================
// CSV EXPORT HANDLER — must be at the top
// =============================================
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Read filter params
    $search = $_GET['search'] ?? '';
    $trip_filter = $_GET['trip_id'] ?? '';
    $period = $_GET['period'] ?? 'today';
    $type_filter = $_GET['type'] ?? 'all';
    
    // Build date condition based on period
    $date_condition = "";
    switch ($period) {
        case 'today':
            $date_condition = "DATE(t.transaction_date) = CURDATE()";
            break;
        case 'week':
            $date_condition = "YEARWEEK(t.transaction_date, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $date_condition = "MONTH(t.transaction_date) = MONTH(CURDATE()) AND YEAR(t.transaction_date) = YEAR(CURDATE())";
            break;
        default:
            $date_condition = "DATE(t.transaction_date) = CURDATE()";
    }
    
    // Build query
    $query = "SELECT t.*, 
              CONCAT(p.first_name, ' ', 
                     IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                     p.last_name) as passenger_name, 
              p.rfid_uid,
              CONCAT(d.origin, ' → ', d.destination) as destination_name
              FROM transactions t
              JOIN passengers p ON t.passenger_id = p.id
              JOIN destinations d ON t.destination_id = d.id
              WHERE t.collector_id = ? AND " . $date_condition;
    
    $params = [$collector_id];
    
    // Search filter
    if (!empty($search)) {
        $query .= " AND (t.receipt_no LIKE ? OR CONCAT(p.first_name, ' ', IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), p.last_name) LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Trip filter
    if (!empty($trip_filter)) {
        $query .= " AND t.trip_id = ?";
        $params[] = $trip_filter;
    }
    
    // Type filter
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
    
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Column headers
    fputcsv($output, [
        'Receipt No',
        'Date & Time',
        'Passenger Name',
        'RFID UID',
        'Route',
        'Base Fare',
        'Discount Amount',
        'Net Payment',
        'Points Earned',
        'Status'
    ]);
    
    // Data rows
    foreach ($transactions as $t) {
        $status = ($t['is_refunded'] == 1) ? 'Cancelled' : 'Completed';
        
        fputcsv($output, [
            $t['receipt_no'],
            date('Y-m-d H:i:s', strtotime($t['transaction_date'])),
            $t['passenger_name'],
            $t['rfid_uid'],
            $t['destination_name'],
            $t['base_fare'],
            $t['discount_amount'],
            $t['net_payment'],
            $t['loyalty_points_earned'],
            $status
        ]);
    }
    
    fclose($output);
    exit();
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$trip_filter = $_GET['trip_id'] ?? '';   // ← CHANGED: was destination_id
$period = $_GET['period'] ?? 'today';     // today, week, month
$type_filter = $_GET['type'] ?? 'all';    // all | completed | cancelled

// Build date condition based on period
$date_condition = "";
switch ($period) {
    case 'today':
        $date_condition = "DATE(t.transaction_date) = CURDATE()";
        $period_label = "Today's Transactions";
        break;
    case 'week':
        $date_condition = "YEARWEEK(t.transaction_date, 1) = YEARWEEK(CURDATE(), 1)";
        $period_label = "This Week's Transactions";
        break;
    case 'month':
        $date_condition = "MONTH(t.transaction_date) = MONTH(CURDATE()) AND YEAR(t.transaction_date) = YEAR(CURDATE())";
        $period_label = "This Month's Transactions";
        break;
    default:
        $date_condition = "DATE(t.transaction_date) = CURDATE()";
        $period_label = "Today's Transactions";
}

// =============================================
// MAIN TABLE QUERY
// =============================================
$query = "SELECT t.*, 
          CONCAT(p.first_name, ' ', 
                 IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                 p.last_name) as passenger_name, 
          p.rfid_uid,
          CONCAT(d.origin, ' → ', d.destination) as destination_name
          FROM transactions t
          JOIN passengers p ON t.passenger_id = p.id
          JOIN destinations d ON t.destination_id = d.id
          WHERE t.collector_id = ? AND " . $date_condition;

$params = [$collector_id];

// Search filter
if (!empty($search)) {
    $query .= " AND (t.receipt_no LIKE ? OR CONCAT(p.first_name, ' ', IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), p.last_name) LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

// Trip filter (CHANGED: was destination filter)
if (!empty($trip_filter)) {
    $query .= " AND t.trip_id = ?";
    $params[] = $trip_filter;
}

// TYPE filter
if ($type_filter === 'completed') {
    $query .= " AND t.is_refunded = 0";
} elseif ($type_filter === 'cancelled') {
    $query .= " AND t.is_refunded = 1";
}
// if 'all', no filter

$query .= " ORDER BY t.transaction_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// =============================================
// SUMMARY DATA — split into Completed vs Cancelled
// =============================================
// Pulls ALL txns for this collector + period (ignores Type filter)
$summary_query = "SELECT t.*
                  FROM transactions t
                  WHERE t.collector_id = ? AND " . $date_condition;

$summary_params = [$collector_id];

// Trip filter on summary (CHANGED: was destination filter)
if (!empty($trip_filter)) {
    $summary_query .= " AND t.trip_id = ?";
    $summary_params[] = $trip_filter;
}

$summary_stmt = $pdo->prepare($summary_query);
$summary_stmt->execute($summary_params);
$all_transactions = $summary_stmt->fetchAll();

// Split
$completed = array_filter($all_transactions, function($t) { return $t['is_refunded'] == 0; });
$cancelled = array_filter($all_transactions, function($t) { return $t['is_refunded'] == 1; });

// COMPLETED totals
$total_passengers = count($completed);
$total_collection = array_sum(array_column($completed, 'net_payment'));
$total_points     = array_sum(array_column($completed, 'loyalty_points_earned'));
$total_discount   = array_sum(array_column($completed, 'discount_amount'));

// CANCELLED totals
$total_cancelled_count  = count($cancelled);
$total_cancelled_amount = array_sum(array_column($cancelled, 'net_payment'));

// =============================================
// Get TRIPS for filter dropdown — ONLY trips for assigned routes
// AND only within the current period
// (CHANGED: was destinations list)
// =============================================
$period_start = date('Y-m-d');
$period_end = date('Y-m-d');

switch ($period) {
    case 'today':
        $period_start = date('Y-m-d');
        $period_end = date('Y-m-d');
        break;
    case 'week':
        $period_start = date('Y-m-d', strtotime('monday this week'));
        $period_end = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $period_start = date('Y-m-01');
        $period_end = date('Y-m-t');
        break;
}

$stmt = $pdo->prepare("
    SELECT tr.id, tr.trip_date, ts.departure_time, 
           d.origin, d.destination, 
           v.name as vessel_name
    FROM trips tr
    JOIN trip_schedules ts ON tr.schedule_id = ts.id
    JOIN destinations d ON ts.destination_id = d.id
    JOIN collector_assignments ca ON ca.destination_id = d.id
    LEFT JOIN vessels v ON d.vessel_id = v.id
    WHERE ca.collector_id = ? 
    AND tr.trip_date BETWEEN ? AND ?
    ORDER BY tr.trip_date DESC, ts.departure_time ASC
");
$stmt->execute([$collector_id, $period_start, $period_end]);
$trips = $stmt->fetchAll();

// Get period display text
switch ($period) {
    case 'today':
        $period_display = date('F d, Y');
        break;
    case 'week':
        $start_of_week = date('F d', strtotime('monday this week'));
        $end_of_week = date('F d, Y', strtotime('sunday this week'));
        $period_display = $start_of_week . ' - ' . $end_of_week;
        break;
    case 'month':
        $period_display = date('F Y');
        break;
    default:
        $period_display = date('F d, Y');
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-receipt mr-2"></i>Transactions
    </h1>
    <div class="flex items-center gap-3">
        <div class="text-sm text-gray-500 text-right">
            <div><span class="font-semibold"><?php echo $period_display; ?></span></div>
            <div>Collector: <span class="font-semibold"><?php echo htmlspecialchars($collector_name); ?></span></div>
        </div>
        <a href="?export=csv&<?php echo htmlspecialchars($_SERVER['QUERY_STRING']); ?>" 
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition inline-block whitespace-nowrap">
            <i class="fas fa-file-excel mr-2"></i>Export to Excel
        </a>
    </div>
</div>

<!-- Period Filter Buttons (CHANGED: destination_id → trip_id) -->
<div class="flex flex-wrap gap-2 mb-6">
    <a href="?period=today&<?php echo http_build_query(['search' => $search, 'trip_id' => $trip_filter, 'type' => $type_filter]); ?>" 
       class="px-4 py-2 rounded-lg transition <?php echo $period === 'today' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
        <i class="fas fa-calendar-day mr-2"></i>Today
    </a>
    <a href="?period=week&<?php echo http_build_query(['search' => $search, 'trip_id' => $trip_filter, 'type' => $type_filter]); ?>" 
       class="px-4 py-2 rounded-lg transition <?php echo $period === 'week' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
        <i class="fas fa-calendar-week mr-2"></i>This Week
    </a>
    <a href="?period=month&<?php echo http_build_query(['search' => $search, 'trip_id' => $trip_filter, 'type' => $type_filter]); ?>" 
       class="px-4 py-2 rounded-lg transition <?php echo $period === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
        <i class="fas fa-calendar-alt mr-2"></i>This Month
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
                <p class="text-gray-500 text-sm">Net Collection</p>
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
                <p class="text-gray-500 text-sm">Points Issued</p>
                <p class="text-2xl font-bold"><?php echo number_format($total_points); ?></p>
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
                <p class="text-2xl font-bold">₱<?php echo number_format($total_discount, 2); ?></p>
            </div>
        </div>
    </div>

    <!-- Cancelled card -->
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

<!-- Search and Filter -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-4">
        <form method="GET" action="" class="flex flex-wrap gap-4">
            <input type="hidden" name="period" value="<?php echo $period; ?>">
            
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" placeholder="Search by Receipt No or Passenger Name..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            
            <!-- CHANGED: Trip dropdown (was Route dropdown) -->
            <div>
                <select name="trip_id" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <option value="">All Trips</option>
                    <?php foreach ($trips as $trip): ?>
                        <option value="<?php echo $trip['id']; ?>" <?php echo $trip_filter == $trip['id'] ? 'selected' : ''; ?>>
                            <?php 
                                echo htmlspecialchars($trip['origin'] . ' → ' . $trip['destination']);
                                echo ' (' . date('M d', strtotime($trip['trip_date']));
                                if (!empty($trip['vessel_name'])) {
                                    echo ' - ' . htmlspecialchars($trip['vessel_name']);
                                }
                                echo ', ' . date('h:i A', strtotime($trip['departure_time'])) . ')';
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <select name="type" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="completed" <?php echo $type_filter === 'completed' ? 'selected' : ''; ?>>Completed Only</option>
                    <option value="cancelled" <?php echo $type_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled Only</option>
                </select>
            </div>
            
            <div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <a href="transactions.php?period=<?php echo $period; ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition ml-2">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passenger</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Base Fare</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Net Payment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
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
                        <td class="px-6 py-4 text-sm"><?php echo date('M d, h:i A', strtotime($transaction['transaction_date'])); ?></td>
                        <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($transaction['passenger_name']); ?></td>
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
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-receipt text-4xl mb-2 block"></i>
                            No transactions found for this period
                            <p class="text-sm mt-2">Start collecting fares to see transactions here</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>