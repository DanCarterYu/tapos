<?php
// collector/dashboard.php
// Main Dashboard for Collector - Fare Collection Focus

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
// TODAY'S SUMMARY — completed only
// =============================================
$stmt = $pdo->prepare("SELECT 
                       COUNT(*) as total_passengers,
                       SUM(net_payment) as total_collection,
                       SUM(loyalty_points_earned) as total_points
                       FROM transactions 
                       WHERE collector_id = ? 
                       AND DATE(transaction_date) = CURDATE()
                       AND is_refunded = 0");
$stmt->execute([$collector_id]);
$todaySummary = $stmt->fetch();

$today_passengers = $todaySummary['total_passengers'] ?? 0;
$today_collection = $todaySummary['total_collection'] ?? 0;
$today_points = $todaySummary['total_points'] ?? 0;

// =============================================
// RECENT TRANSACTIONS — last 10 (includes cancelled, flagged)
// =============================================
$stmt = $pdo->prepare("SELECT t.*, 
                       CONCAT(p.first_name, ' ', 
                              IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                              p.last_name) as passenger_name, 
                       CONCAT(d.origin, ' → ', d.destination) as destination_name 
                       FROM transactions t
                       JOIN passengers p ON t.passenger_id = p.id
                       JOIN destinations d ON t.destination_id = d.id
                       WHERE t.collector_id = ?
                       ORDER BY t.transaction_date DESC
                       LIMIT 10");
$stmt->execute([$collector_id]);
$recentTransactions = $stmt->fetchAll();

// Include header
include_once '../includes/header.php';
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6">
    Welcome, <?php echo htmlspecialchars($collector_name); ?>!
</h1>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Today's Passengers</p>
                <p class="text-2xl font-bold"><?php echo number_format($today_passengers); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-coins text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Today's Collection</p>
                <p class="text-2xl font-bold">₱<?php echo number_format($today_collection, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-gem text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Points Issued Today</p>
                <p class="text-2xl font-bold"><?php echo number_format($today_points); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-clock mr-2 text-gray-600"></i>Recent Transactions
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passenger</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($recentTransactions) > 0): ?>
                    <?php foreach ($recentTransactions as $transaction): 
                        $is_cancelled = ($transaction['is_refunded'] == 1);
                        $amount_class = $is_cancelled ? 'text-red-600 line-through' : 'text-green-600';
                        $points_class = $is_cancelled ? 'text-red-500' : 'text-yellow-600';
                        $points_prefix = $is_cancelled ? '-' : '+';
                        $badge_class = $is_cancelled ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
                        $badge_text = $is_cancelled ? 'Cancelled' : 'Completed';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm"><?php echo date('h:i A', strtotime($transaction['transaction_date'])); ?></td>
                        <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($transaction['receipt_no']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($transaction['passenger_name']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($transaction['destination_name']); ?></td>
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
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-receipt text-4xl mb-2 block"></i>
                            No transactions yet today
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>