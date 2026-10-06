<?php
// management/dashboard.php
// Main Dashboard for Management

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

// Get summary data
$stmt = $pdo->query("SELECT COUNT(*) as total FROM passengers WHERE status = 'active'");
$totalPassengers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM collectors WHERE status = 'active'");
$totalCollectors = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM passengers WHERE status = 'active' AND rfid_uid IS NOT NULL");
$totalRFIDCards = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT SUM(net_payment) as total FROM transactions WHERE is_refunded = 0");
$totalFare = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT SUM(net_payment) as today_total FROM transactions WHERE DATE(transaction_date) = CURDATE() AND is_refunded = 0");
$todayCollection = $stmt->fetch()['today_total'] ?? 0;

$stmt = $pdo->query("SELECT SUM(loyalty_points) as total FROM passengers");
$totalLoyaltyPoints = $stmt->fetch()['total'] ?? 0;

//recent transaction
$stmt = $pdo->query("SELECT t.*, 
                     CONCAT(p.first_name, ' ', 
                            IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                            p.last_name) as passenger_name, 
                     CONCAT(d.origin, ' → ', d.destination) as destination_name 
                     FROM transactions t 
                     JOIN passengers p ON t.passenger_id = p.id 
                     JOIN destinations d ON t.destination_id = d.id 
                     ORDER BY t.transaction_date DESC 
                     LIMIT 10");
$recentTransactions = $stmt->fetchAll();

// Include header
include_once '../includes/header.php';
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6">Dashboard Overview</h1>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-credit-card text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">RFID Cards Issued</p>
                <p class="text-2xl font-bold"><?php echo $totalRFIDCards; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-users text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Active Passengers</p>
                <p class="text-2xl font-bold"><?php echo $totalPassengers; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-purple-100 rounded-full p-3 mr-4">
                <i class="fas fa-user-tie text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Active Collectors</p>
                <p class="text-2xl font-bold"><?php echo $totalCollectors; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-gem text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Loyalty Points Issued</p>
                <p class="text-2xl font-bold"><?php echo number_format($totalLoyaltyPoints); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Second Row of Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Today's Collection</p>
                <p class="text-2xl font-bold">₱<?php echo number_format($todayCollection, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-red-100 rounded-full p-3 mr-4">
                <i class="fas fa-coins text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Collection (All Time)</p>
                <p class="text-2xl font-bold">₱<?php echo number_format($totalFare, 2); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-receipt mr-2 text-gray-600"></i>Recent Transactions
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
                        <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passenger</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points Earned</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
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
                        <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($transaction['passenger_name']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($transaction['destination_name']); ?></td>
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
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No transactions yet</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include footer
include_once '../includes/footer.php';
?>