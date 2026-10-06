<?php
// passenger/receipts.php
// Passenger Receipts / Transaction History

require_once 'session_config.php';
require_once '../config/database.php';

// Check if logged in as passenger
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header('Location: login.php');
    exit();
}

$passenger_id = $_SESSION['user_id'];

// Get passenger name from database (always up to date)
$stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', 
                            IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                            last_name) as full_name 
                       FROM passengers WHERE id = ?");
$stmt->execute([$passenger_id]);
$passenger_name = $stmt->fetch()['full_name'];

// Get filter parameters
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date_filter'] ?? '';

// Get specific receipt if requested
$specific_receipt = $_GET['receipt'] ?? '';

// Build query with passenger name and route
$query = "SELECT t.*, 
          CONCAT(p.first_name, ' ', 
                 IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                 p.last_name) as passenger_name,
          CONCAT(d.origin, ' → ', d.destination) as destination_name 
          FROM transactions t
          JOIN passengers p ON t.passenger_id = p.id
          JOIN destinations d ON t.destination_id = d.id
          WHERE t.passenger_id = ?";
$params = [$passenger_id];

// Filter by specific receipt
if (!empty($specific_receipt)) {
    $query .= " AND t.receipt_no = ?";
    $params[] = $specific_receipt;
}

// Search filter
if (!empty($search)) {
    $query .= " AND (t.receipt_no LIKE ? OR d.destination LIKE ? OR d.origin LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Date filter
if (!empty($date_filter)) {
    $query .= " AND DATE(t.transaction_date) = ?";
    $params[] = $date_filter;
}

$query .= " ORDER BY t.transaction_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// =============================================
// SUMMARY — completed only
// =============================================
$total_transactions = 0;
$total_spent = 0;
$total_points = 0;

foreach ($transactions as $t) {
    if ($t['is_refunded'] == 0) {
        $total_transactions++;
        $total_spent += $t['net_payment'];
        $total_points += $t['loyalty_points_earned'];
    }
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-receipt mr-2"></i>My Receipts
    </h1>
    <div class="text-sm text-gray-500">
        <span class="font-semibold"><?php echo htmlspecialchars($passenger_name); ?></span>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-receipt text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Trips</p>
                <p class="text-2xl font-bold"><?php echo number_format($total_transactions); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-coins text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Spent</p>
                <p class="text-2xl font-bold">₱<?php echo number_format($total_spent, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-gem text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Points Earned</p>
                <p class="text-2xl font-bold"><?php echo number_format($total_points); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-4">
        <form method="GET" action="" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" placeholder="Search by Receipt No or Route..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <input type="date" name="date_filter" value="<?php echo htmlspecialchars($date_filter); ?>"
                       class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <a href="receipts.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition ml-2">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Base Fare</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Net Payment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
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
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($transaction['destination_name']); ?></td>
                        <td class="px-6 py-4 text-sm">₱<?php echo number_format($transaction['base_fare'], 2); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <?php if ($transaction['discount_amount'] > 0): ?>
                                <span class="text-green-600">-₱<?php echo number_format($transaction['discount_amount'], 2); ?></span>
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
                        <td class="px-6 py-4 text-sm">
                            <button onclick="printReceipt('<?php echo $transaction['receipt_no']; ?>')" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition">
                                <i class="fas fa-print mr-1"></i> Print
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-receipt text-4xl mb-2 block"></i>
                            No receipts found
                            <p class="text-sm mt-2">Start riding to see your receipts here</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// =============================================
// PRINT RECEIPT
// Opens the receipt print endpoint in a new window
// =============================================
function printReceipt(receiptNo) {
    if (!receiptNo) {
        alert('Receipt number not available');
        return;
    }
    window.open(
        'receipt_print.php?receipt_no=' + encodeURIComponent(receiptNo),
        '_blank',
        'width=500,height=700'
    );
}
</script>

<?php include_once '../includes/footer.php'; ?>