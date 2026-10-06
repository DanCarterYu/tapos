<?php
// management/redemption_settings.php
// Manage Redemption Settings & View Redemption History

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
// HANDLE ADD REWARD
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_reward') {
        $balance_amount = floatval($_POST['balance_amount']);
        $points_required = intval($_POST['points_required']);
        
        if ($balance_amount <= 0) {
            $error = 'Please enter a valid balance amount';
        } elseif ($points_required <= 0) {
            $error = 'Please enter valid points required';
        } else {
            $stmt = $pdo->prepare("INSERT INTO redemption_settings (balance_amount, points_required) VALUES (?, ?)");
            if ($stmt->execute([$balance_amount, $points_required])) {
                $success = 'Reward added successfully!';
            } else {
                $error = 'Failed to add reward';
            }
        }
    }
    
    if ($action === 'toggle_status') {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("SELECT is_active FROM redemption_settings WHERE id = ?");
        $stmt->execute([$id]);
        $setting = $stmt->fetch();
        
        if ($setting) {
            $new_status = $setting['is_active'] == 1 ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE redemption_settings SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            $success = 'Reward status updated!';
        }
    }
    
    if ($action === 'delete_reward') {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM redemption_settings WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Reward deleted successfully!';
    }
}

// =============================================
// GET REDEMPTION SETTINGS
// =============================================
$stmt = $pdo->query("SELECT * FROM redemption_settings ORDER BY balance_amount ASC");
$settings = $stmt->fetchAll();

// =============================================
// GET REDEMPTION HISTORY
// =============================================
$stmt = $pdo->query("
    SELECT r.*, 
           CONCAT(p.first_name, ' ', 
                  IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                  p.last_name) as passenger_name
    FROM redemptions r
    JOIN passengers p ON r.passenger_id = p.id
    ORDER BY r.created_at DESC
    LIMIT 50
");
$redemptions = $stmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-gem mr-2 text-yellow-500"></i>Redemption Settings
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
<!-- ADD REWARD FORM -->
<!-- ============================================= -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-plus-circle mr-2 text-green-500"></i>Add New Reward
        </h2>
    </div>
    <form method="POST" action="" class="p-6">
        <input type="hidden" name="action" value="add_reward">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Balance Amount (₱) *</label>
                <input type="number" name="balance_amount" step="1" min="1" required
                       placeholder="e.g., 50"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2">Points Required *</label>
                <input type="number" name="points_required" step="1" min="1" required
                       placeholder="e.g., 60"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition w-full">
                    <i class="fas fa-plus mr-2"></i>Add Reward
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ============================================= -->
<!-- AVAILABLE REWARDS LIST -->
<!-- ============================================= -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-list mr-2 text-blue-500"></i>Available Rewards
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points Required</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($settings) > 0): ?>
                    <?php foreach ($settings as $setting): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-bold text-green-600">₱<?php echo number_format($setting['balance_amount'], 2); ?></td>
                        <td class="px-6 py-4 text-sm font-bold text-yellow-600"><?php echo $setting['points_required']; ?> points</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs <?php echo $setting['is_active'] == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $setting['is_active'] == 1 ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?php echo $setting['id']; ?>">
                                <button type="submit" class="text-yellow-600 hover:text-yellow-800">
                                    <i class="fas <?php echo $setting['is_active'] == 1 ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                    <?php echo $setting['is_active'] == 1 ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Delete this reward?')">
                                <input type="hidden" name="action" value="delete_reward">
                                <input type="hidden" name="id" value="<?php echo $setting['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-gem text-4xl mb-2 block text-gray-300"></i>
                            No rewards configured yet. Add your first reward above.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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
        <p class="text-sm text-gray-500">All passenger redemptions</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passenger</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance Added</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points Used</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($redemptions) > 0): ?>
                    <?php foreach ($redemptions as $redemption): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($redemption['passenger_name']); ?></td>
                        <td class="px-6 py-4 text-sm font-bold text-green-600">₱<?php echo number_format($redemption['balance_amount'], 2); ?></td>
                        <td class="px-6 py-4 text-sm text-yellow-600"><?php echo $redemption['points_used']; ?> pts</td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('M d, Y h:i A', strtotime($redemption['created_at'])); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800">
                                <?php echo ucfirst($redemption['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-history text-4xl mb-2 block text-gray-300"></i>
                            No redemptions yet
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>