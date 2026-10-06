<?php
// passenger/dashboard.php
// Passenger Dashboard

require_once 'session_config.php';
require_once '../config/database.php';

// Check if logged in as passenger
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header('Location: login.php');
    exit();
}

$passenger_id = $_SESSION['user_id'];
$rfid_uid = $_SESSION['rfid_uid'];

// Get passenger data with full name
$stmt = $pdo->prepare("SELECT *, 
                       CONCAT(first_name, ' ', 
                              IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                              last_name) as full_name 
                       FROM passengers WHERE id = ?");
$stmt->execute([$passenger_id]);
$passenger = $stmt->fetch();
$passenger_name = $passenger['full_name'];

// =============================================
// SUMMARY — completed only
// =============================================
$stmt = $pdo->prepare("SELECT 
                       COUNT(*) as total_trips,
                       SUM(net_payment) as total_spent,
                       SUM(loyalty_points_earned) as total_points_earned
                       FROM transactions 
                       WHERE passenger_id = ? AND is_refunded = 0");
$stmt->execute([$passenger_id]);
$summary = $stmt->fetch();

// =============================================
// RECENT TRANSACTIONS — last 5 (includes cancelled, flagged)
// =============================================
$stmt = $pdo->prepare("SELECT t.*, 
                       CONCAT(p.first_name, ' ', 
                              IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                              p.last_name) as passenger_name, 
                       CONCAT(d.origin, ' → ', d.destination) as destination_name 
                       FROM transactions t
                       JOIN passengers p ON t.passenger_id = p.id
                       JOIN destinations d ON t.destination_id = d.id
                       WHERE t.passenger_id = ?
                       ORDER BY t.transaction_date DESC
                       LIMIT 5");
$stmt->execute([$passenger_id]);
$recentTransactions = $stmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-user mr-2"></i>Welcome, <?php echo htmlspecialchars($passenger_name); ?>!
    </h1>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-wallet text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Current Balance</p>
                <p class="text-2xl font-bold text-green-600">₱<?php echo number_format($passenger['balance'], 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-gem text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Loyalty Points</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($passenger['loyalty_points']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-ship text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Trips</p>
                <p class="text-2xl font-bold"><?php echo number_format($summary['total_trips'] ?? 0); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Add Balance Quick Link -->
<div class="bg-blue-50 rounded-lg shadow p-4 mb-8">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm">Need more balance?</p>
            <p class="font-semibold">Load money to your account</p>
        </div>
        <a href="balance_request.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
            <i class="fas fa-plus-circle mr-2"></i>Add Balance
        </a>
    </div>
</div>

<!-- Second Row -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-purple-100 rounded-full p-3 mr-4">
                <i class="fas fa-coins text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Spent</p>
                <p class="text-2xl font-bold">₱<?php echo number_format($summary['total_spent'] ?? 0, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-indigo-100 rounded-full p-3 mr-4">
                <i class="fas fa-star text-indigo-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Points Earned (All Time)</p>
                <p class="text-2xl font-bold"><?php echo number_format($summary['total_points_earned'] ?? 0); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h2 class="text-lg font-bold">
            <i class="fas fa-clock mr-2 text-gray-600"></i>Recent Trips
        </h2>
        <a href="receipts.php" class="text-blue-600 hover:text-blue-800 text-sm">
            View All <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt</th>
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
                        <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($transaction['receipt_no']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($transaction['destination_name']); ?></td>
                        <td class="px-6 py-4 text-sm font-bold <?php echo $amount_class; ?>">₱<?php echo number_format($transaction['net_payment'], 2); ?></td>
                        <td class="px-6 py-4 text-sm <?php echo $points_class; ?>"><?php echo $points_prefix . $transaction['loyalty_points_earned']; ?> pts</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs font-medium <?php echo $badge_class; ?>">
                                <?php echo $badge_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('M d, Y h:i A', strtotime($transaction['transaction_date'])); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <a href="receipts.php?receipt=<?php echo $transaction['receipt_no']; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-ship text-4xl mb-2 block"></i>
                            No trips yet. Start riding with Vince Gabriel Liners!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>