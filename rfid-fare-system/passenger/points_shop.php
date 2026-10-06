<?php
// passenger/points_shop.php
// Points Shop - Redeem loyalty points for balance

require_once 'session_config.php';
require_once '../config/database.php';

// Check if logged in as passenger
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header('Location: login.php');
    exit();
}

$passenger_id = $_SESSION['user_id'];
$passenger_name = $_SESSION['full_name'];

// Get passenger data
$stmt = $pdo->prepare("SELECT * FROM passengers WHERE id = ?");
$stmt->execute([$passenger_id]);
$passenger = $stmt->fetch();

// =============================================
// HANDLE REDEMPTION
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem'])) {
    $reward_id = intval($_POST['reward_id']);
    
    // Get reward details
    $stmt = $pdo->prepare("SELECT * FROM redemption_settings WHERE id = ? AND is_active = 1");
    $stmt->execute([$reward_id]);
    $reward = $stmt->fetch();
    
    if (!$reward) {
        $error = 'Invalid reward selected.';
    } elseif ($passenger['loyalty_points'] < $reward['points_required']) {
        $error = 'You do not have enough points. You need ' . $reward['points_required'] . ' points, but you only have ' . $passenger['loyalty_points'] . '.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Deduct points
            $new_points = $passenger['loyalty_points'] - $reward['points_required'];
            $new_balance = $passenger['balance'] + $reward['balance_amount'];
            
            $stmt = $pdo->prepare("UPDATE passengers SET balance = ?, loyalty_points = ? WHERE id = ?");
            $stmt->execute([$new_balance, $new_points, $passenger_id]);
            
            // Record redemption
            $stmt = $pdo->prepare("INSERT INTO redemptions (passenger_id, balance_amount, points_used) VALUES (?, ?, ?)");
            $stmt->execute([$passenger_id, $reward['balance_amount'], $reward['points_required']]);
            
            $pdo->commit();
            
            $success = 'Successfully redeemed ₱' . number_format($reward['balance_amount'], 2) . ' for ' . $reward['points_required'] . ' points!';
            
            // Refresh passenger data
            $stmt = $pdo->prepare("SELECT * FROM passengers WHERE id = ?");
            $stmt->execute([$passenger_id]);
            $passenger = $stmt->fetch();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to redeem: ' . $e->getMessage();
        }
    }
}

// =============================================
// GET AVAILABLE REWARDS
// =============================================
$stmt = $pdo->query("SELECT * FROM redemption_settings WHERE is_active = 1 ORDER BY points_required ASC");
$rewards = $stmt->fetchAll();

// =============================================
// GET REDEMPTION HISTORY
// =============================================
$stmt = $pdo->prepare("SELECT * FROM redemptions WHERE passenger_id = ? ORDER BY created_at DESC");
$stmt->execute([$passenger_id]);
$history = $stmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-gem mr-2 text-blue-500"></i>Points Shop
    </h1>
</div>

<!-- ============================================= -->
<!-- POINTS & BALANCE SUMMARY -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-gradient-to-r from-blue-400 to-blue-500 rounded-lg shadow p-6 text-white">
        <p class="text-sm text-yellow-100">Your Points</p>
        <p class="text-3xl font-bold"><?php echo number_format($passenger['loyalty_points']); ?></p>
        <p class="text-xs text-yellow-100 mt-1">Earn points by riding with us</p>
    </div>
    <div class="bg-gradient-to-r from-green-400 to-green-500 rounded-lg shadow p-6 text-white">
        <p class="text-sm text-green-100">Current Balance</p>
        <p class="text-3xl font-bold">₱<?php echo number_format($passenger['balance'], 2); ?></p>
        <p class="text-xs text-green-100 mt-1">Load balance to use for fares</p>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- ============================================= -->
<!-- AVAILABLE REWARDS -->
<!-- ============================================= -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-gift mr-2 text-blue-500"></i>Available Rewards
        </h2>
        <p class="text-sm text-gray-500">Redeem your points for balance</p>
    </div>
    <div class="p-6">
        <?php if (count($rewards) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ($rewards as $reward): 
                    $can_afford = $passenger['loyalty_points'] >= $reward['points_required'];
                ?>
                <div class="border rounded-lg p-4 text-center <?php echo $can_afford ? 'hover:shadow-lg transition' : 'opacity-50'; ?>">
                    <p class="text-2xl font-bold text-green-600">₱<?php echo number_format($reward['balance_amount'], 2); ?></p>
                    <p class="text-sm text-gray-500">Balance</p>
                    <p class="text-yellow-600 font-bold mt-2"><?php echo $reward['points_required']; ?> points</p>
                    <form method="POST" action="" class="mt-3">
                        <input type="hidden" name="reward_id" value="<?php echo $reward['id']; ?>">
                        <?php if ($can_afford): ?>
                            <button type="submit" name="redeem" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition w-full text-sm">
                                <i class="fas fa-exchange-alt mr-1"></i> Redeem
                            </button>
                        <?php else: ?>
                            <button type="button" disabled class="bg-gray-300 text-gray-500 px-4 py-2 rounded-lg w-full text-sm cursor-not-allowed">
                                Need <?php echo $reward['points_required']; ?> pts
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-gem text-4xl mb-2 block text-gray-300"></i>
                <p>No rewards available at the moment.</p>
                <p class="text-sm mt-1">Check back later!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- REDEMPTION HISTORY -->
<!-- ============================================= -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-history mr-2 text-gray-600"></i>Redemption History
        </h2>
        <p class="text-sm text-gray-500">Your previous redemptions</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance Added</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points Used</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($history) > 0): ?>
                    <?php foreach ($history as $item): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('M d, Y h:i A', strtotime($item['created_at'])); ?></td>
                        <td class="px-6 py-4 text-sm font-bold text-green-600">₱<?php echo number_format($item['balance_amount'], 2); ?></td>
                        <td class="px-6 py-4 text-sm text-yellow-600"><?php echo $item['points_used']; ?> pts</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800">
                                <?php echo ucfirst($item['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-history text-4xl mb-2 block text-gray-300"></i>
                            No redemption history yet
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>