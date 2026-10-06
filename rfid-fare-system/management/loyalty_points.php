<?php
// management/loyalty_points.php
// Loyalty Points Configuration - Management Controls

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// =============================================
// GET CURRENT SETTINGS
// =============================================
// Check if loyalty_settings table exists and get settings
$stmt = $pdo->query("SELECT * FROM loyalty_settings LIMIT 1");
$settings = $stmt->fetch();

// If no settings exist, create default
if (!$settings) {
    $stmt = $pdo->prepare("INSERT INTO loyalty_settings (points_per_travel, is_enabled) VALUES (?, ?)");
    $stmt->execute([1, 1]);
    $stmt = $pdo->query("SELECT * FROM loyalty_settings LIMIT 1");
    $settings = $stmt->fetch();
}

// =============================================
// HANDLE FORM SUBMISSION
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $points_per_travel = intval($_POST['points_per_travel']);
    $is_enabled = isset($_POST['is_enabled']) ? 1 : 0;
    
    if ($points_per_travel < 0) {
        $error = 'Points per travel must be 0 or greater';
    } else {
        // Check if columns exist
        $stmt = $pdo->query("SHOW COLUMNS FROM loyalty_settings LIKE 'points_per_travel'");
        $has_points_per_travel = $stmt->rowCount() > 0;
        
        $stmt = $pdo->query("SHOW COLUMNS FROM loyalty_settings LIKE 'is_enabled'");
        $has_is_enabled = $stmt->rowCount() > 0;
        
        if ($has_points_per_travel && $has_is_enabled) {
            // Update with both columns
            $stmt = $pdo->prepare("UPDATE loyalty_settings SET 
                                   points_per_travel = ?, 
                                   is_enabled = ?,
                                   updated_by = ?,
                                   updated_at = NOW()
                                   WHERE id = ?");
            $stmt->execute([$points_per_travel, $is_enabled, $_SESSION['user_id'], $settings['id']]);
        } elseif ($has_points_per_travel) {
            // Update with points_per_travel only
            $stmt = $pdo->prepare("UPDATE loyalty_settings SET 
                                   points_per_travel = ?,
                                   updated_by = ?,
                                   updated_at = NOW()
                                   WHERE id = ?");
            $stmt->execute([$points_per_travel, $_SESSION['user_id'], $settings['id']]);
        } else {
            // Fallback - add columns first
            $pdo->exec("ALTER TABLE loyalty_settings ADD COLUMN points_per_travel INT DEFAULT 1");
            $pdo->exec("ALTER TABLE loyalty_settings ADD COLUMN is_enabled TINYINT(1) DEFAULT 1");
            $stmt = $pdo->prepare("UPDATE loyalty_settings SET 
                                   points_per_travel = ?, 
                                   is_enabled = ?,
                                   updated_by = ?,
                                   updated_at = NOW()
                                   WHERE id = ?");
            $stmt->execute([$points_per_travel, $is_enabled, $_SESSION['user_id'], $settings['id']]);
        }
        
        $success = 'Loyalty points settings updated successfully!';
        
        // Refresh settings
        $stmt = $pdo->query("SELECT * FROM loyalty_settings LIMIT 1");
        $settings = $stmt->fetch();
    }
}

// =============================================
// GET LOYALTY POINTS SUMMARY
// =============================================
$stmt = $pdo->query("SELECT 
                     SUM(loyalty_points) as total_points,
                     AVG(loyalty_points) as avg_points,
                     COUNT(*) as passengers_with_points
                     FROM passengers WHERE loyalty_points > 0");
$pointsSummary = $stmt->fetch();

// Get total passengers count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM passengers");
$totalPassengers = $stmt->fetch()['total'];

// Get top passengers by loyalty points
$stmt = $pdo->query("SELECT 
                     CONCAT(first_name, ' ', 
                            IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                            last_name) as full_name, 
                     loyalty_points, 
                     balance, 
                     rfid_uid 
                     FROM passengers 
                     WHERE loyalty_points > 0 
                     ORDER BY loyalty_points DESC 
                     LIMIT 10");
$topPassengers = $stmt->fetchAll();

// Get recent points earning transactions
$stmt = $pdo->query("SELECT t.*, 
                     CONCAT(p.first_name, ' ', 
                            IF(p.middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                            p.last_name) as passenger_name, 
                     CONCAT(d.origin, ' → ', d.destination) as destination_name 
                     FROM transactions t
                     JOIN passengers p ON t.passenger_id = p.id 
                     JOIN destinations d ON t.destination_id = d.id 
                     WHERE t.loyalty_points_earned > 0
                     ORDER BY t.transaction_date DESC 
                     LIMIT 10");
$recentPointsTransactions = $stmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-gem mr-2 text-yellow-500"></i>Loyalty Points Configuration
    </h1>
</div>

<?php if ($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- ============================================= -->
<!-- SETTINGS FORM -->
<!-- ============================================= -->
<div class="bg-white rounded-lg shadow mb-8">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-sliders-h mr-2 text-gray-600"></i>Points Settings
        </h2>
        <p class="text-gray-500 text-sm">Configure how loyalty points are earned</p>
    </div>
    <form method="POST" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-gray-700 font-bold mb-2">
                    <i class="fas fa-star mr-2 text-yellow-500"></i>Points per Travel
                </label>
                <div class="flex items-center">
                    <input type="number" name="points_per_travel" step="1" min="0" 
                           value="<?php echo $settings['points_per_travel'] ?? 1; ?>" required
                           class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <span class="ml-2 text-gray-500">point(s) per passenger</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    Each passenger earns this many points per travel
                </p>
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">
                    <i class="fas fa-toggle-on mr-2 text-green-500"></i>Status
                </label>
                <div class="flex items-center mt-2">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_enabled" value="1" 
                               <?php echo ($settings['is_enabled'] ?? 1) == 1 ? 'checked' : ''; ?>
                               class="w-6 h-6 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-3 text-gray-700">
                            <?php echo ($settings['is_enabled'] ?? 1) == 1 ? '<span class="text-green-600 font-bold">Enabled</span>' : '<span class="text-red-600 font-bold">Disabled</span>'; ?>
                        </span>
                    </label>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    When enabled, passengers earn points. When disabled, no points are earned.
                </p>
            </div>
        </div>
        
        <div class="bg-blue-50 rounded-lg p-4 mt-6">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>How it works:</strong><br>
                • Each passenger earns <strong><?php echo $settings['points_per_travel'] ?? 1; ?> point(s)</strong> per travel<br>
                • For groups: <?php echo $settings['points_per_travel'] ?? 1; ?> point(s) × number of passengers<br>
                • Points are automatically added to the passenger's account
            </p>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                <i class="fas fa-save mr-2"></i>Save Settings
            </button>
        </div>
    </form>
</div>

<!-- ============================================= -->
<!-- LOYALTY POINTS SUMMARY -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-gem text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Points Issued</p>
                <p class="text-2xl font-bold"><?php echo number_format($pointsSummary['total_points'] ?? 0); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-users text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Passengers with Points</p>
                <p class="text-2xl font-bold">
                    <?php echo $pointsSummary['passengers_with_points'] ?? 0; ?>
                    <span class="text-sm text-gray-500">/ <?php echo $totalPassengers; ?></span>
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-chart-simple text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Average Points per Passenger</p>
                <p class="text-2xl font-bold"><?php echo number_format($pointsSummary['avg_points'] ?? 0, 1); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- TOP PASSENGERS -->
<!-- ============================================= -->
<div class="bg-white rounded-lg shadow mb-8">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-trophy mr-2 text-yellow-500"></i>Top Passengers by Loyalty Points
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passenger Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">RFID UID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loyalty Points</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($topPassengers) > 0): ?>
                    <?php $rank = 1; ?>
                    <?php foreach ($topPassengers as $passenger): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">
                            <?php if ($rank == 1): ?>
                                <span class="text-2xl">🏆</span> <?php echo $rank; ?>
                            <?php elseif ($rank == 2): ?>
                                <span class="text-2xl">🥈</span> <?php echo $rank; ?>
                            <?php elseif ($rank == 3): ?>
                                <span class="text-2xl">🥉</span> <?php echo $rank; ?>
                            <?php else: ?>
                                <?php echo $rank; ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($passenger['full_name']); ?></td>
                        <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($passenger['rfid_uid']); ?></td>
                        <td class="px-6 py-4 text-sm text-yellow-600 font-bold"><?php echo number_format($passenger['loyalty_points']); ?> pts</td>
                        <td class="px-6 py-4 text-sm">₱<?php echo number_format($passenger['balance'], 2); ?></td>
                    </tr>
                    <?php $rank++; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-gem text-4xl mb-2 block"></i>
                            No passengers with loyalty points yet
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================= -->
<!-- RECENT TRANSACTIONS -->
<!-- ============================================= -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-receipt mr-2 text-gray-600"></i>Recent Points Earning Transactions
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passenger</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points Earned</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($recentPointsTransactions) > 0): ?>
                                        <?php foreach ($recentPointsTransactions as $transaction): 
                        $is_cancelled = ($transaction['is_refunded'] == 1);
                        $points_class = $is_cancelled ? 'text-red-500 line-through' : 'text-yellow-600';
                        $points_prefix = $is_cancelled ? '-' : '+';
                        $badge_class = $is_cancelled ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
                        $badge_text = $is_cancelled ? 'Cancelled' : 'Earned';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($transaction['receipt_no']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($transaction['passenger_name']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($transaction['destination_name']); ?></td>
                        <td class="px-6 py-4 text-sm <?php echo $points_class; ?> font-bold"><?php echo $points_prefix . $transaction['loyalty_points_earned']; ?> pts</td>
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
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-receipt text-4xl mb-2 block"></i>
                            No points earning transactions yet
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================= -->
<!-- QUICK REFERENCE -->
<!-- ============================================= -->
<div class="bg-gray-50 rounded-lg p-6 mt-8">
    <h3 class="font-bold text-gray-800 mb-4">
        <i class="fas fa-question-circle mr-2"></i>Loyalty Points Reference
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="border rounded-lg p-3">
            <p class="font-bold text-sm mb-2">Current Settings:</p>
            <ul class="text-sm text-gray-600 space-y-1">
                <li>• <strong><?php echo $settings['points_per_travel'] ?? 1; ?></strong> point(s) per passenger per travel</li>
                <li>• Status: <strong class="<?php echo ($settings['is_enabled'] ?? 1) == 1 ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo ($settings['is_enabled'] ?? 1) == 1 ? 'Enabled' : 'Disabled'; ?>
                </strong></li>
            </ul>
        </div>
        <div class="border rounded-lg p-3">
            <p class="font-bold text-sm mb-2">Example:</p>
            <ul class="text-sm text-gray-600 space-y-1">
                <li>• 1 passenger → <strong><?php echo $settings['points_per_travel'] ?? 1; ?> point(s)</strong></li>
                <li>• 5 passengers (group) → <strong><?php echo ($settings['points_per_travel'] ?? 1) * 5; ?> point(s)</strong></li>
                <li>• 10 passengers (group) → <strong><?php echo ($settings['points_per_travel'] ?? 1) * 10; ?> point(s)</strong></li>
            </ul>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>