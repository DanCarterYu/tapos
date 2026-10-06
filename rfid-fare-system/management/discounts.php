<?php
// management/discounts.php
// Discount Types Management
require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

// Handle edit percentage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_discount'])) {
    $id = $_POST['id'];
    $percentage = floatval($_POST['percentage']);
    
    if ($percentage < 0 || $percentage > 100) {
        $error = "Percentage must be between 0 and 100";
    } else {
        $stmt = $pdo->prepare("UPDATE discount_types SET percentage = ? WHERE id = ?");
        if ($stmt->execute([$percentage, $id])) {
            $success = "Discount percentage updated successfully!";
        } else {
            $error = "Failed to update discount percentage";
        }
    }
}

// Handle toggle status (activate/deactivate)
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    
    $stmt = $pdo->prepare("SELECT is_active FROM discount_types WHERE id = ?");
    $stmt->execute([$id]);
    $discount = $stmt->fetch();
    
    if ($discount) {
        // Prevent deactivating Regular discount (id = 4)
        if ($id == 4) {
            $error = "Cannot deactivate Regular discount type. This is required for passengers without special discounts.";
        } else {
            $new_status = ($discount['is_active'] == 1) ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE discount_types SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            $status_text = $new_status == 1 ? 'activated' : 'deactivated';
            $success = "Discount type " . $status_text . " successfully!";
        }
    } else {
        $error = "Discount type not found!";
    }
}

// Get all discount types
$stmt = $pdo->query("SELECT * FROM discount_types ORDER BY id");
$discounts = $stmt->fetchAll();

// Get counts for summary
$stmt = $pdo->query("SELECT COUNT(*) as total FROM discount_types WHERE is_active = 1");
$activeDiscounts = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM discount_types");
$totalDiscounts = $stmt->fetch()['total'];

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-percent mr-2"></i>Discount Types
    </h1>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-tag text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Discount Types</p>
                <p class="text-2xl font-bold"><?php echo $totalDiscounts; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Active Discount Types</p>
                <p class="text-2xl font-bold"><?php echo $activeDiscounts; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Info Box -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex items-start">
        <div class="bg-blue-100 rounded-full p-2 mr-3">
            <i class="fas fa-info-circle text-blue-600"></i>
        </div>
        <div>
            <p class="text-sm text-blue-800">
                <strong>How Discounts Work:</strong><br>
                • Discounts are applied during <strong>fare collection</strong> by the collector<br>
                • The collector selects the passenger type (Regular, Senior, PWD, Student)<br>
                • The system automatically calculates the discounted fare<br>
                • <strong>Regular discount type cannot be deactivated</strong> (required for all passengers)
            </p>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
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

<!-- Discount Types Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Percentage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($discounts as $discount): ?>
                <?php 
                    // Set color based on discount type
                    $bg_color = '';
                    $text_color = '';
                    switch($discount['name']) {
                        case 'senior':
                            $bg_color = 'bg-purple-100';
                            $text_color = 'text-purple-800';
                            break;
                        case 'pwd':
                            $bg_color = 'bg-blue-100';
                            $text_color = 'text-blue-800';
                            break;
                        case 'student':
                            $bg_color = 'bg-green-100';
                            $text_color = 'text-green-800';
                            break;
                        case 'regular':
                            $bg_color = 'bg-gray-100';
                            $text_color = 'text-gray-800';
                            break;
                        default:
                            $bg_color = 'bg-gray-100';
                            $text_color = 'text-gray-800';
                    }
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">
                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $bg_color . ' ' . $text_color; ?>">
                            <?php echo ucfirst($discount['name']); ?>
                        </span>
                     </td>
                    <td class="px-6 py-4 text-sm">
                        <?php if ($discount['id'] == 4): ?>
                            <span class="font-bold"><?php echo $discount['percentage']; ?>%</span>
                            <p class="text-xs text-gray-500 mt-1">No discount applied</p>
                        <?php else: ?>
                            <form method="POST" action="" class="flex items-center gap-2">
                                <input type="hidden" name="id" value="<?php echo $discount['id']; ?>">
                                <input type="number" name="percentage" step="1" min="0" max="100" 
                                       value="<?php echo $discount['percentage']; ?>"
                                       class="w-20 px-2 py-1 text-center border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                <span class="text-gray-500">% off</span>
                                <button type="submit" name="update_discount" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 rounded text-xs <?php echo $discount['is_active'] == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $discount['is_active'] == 1 ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <?php if ($discount['id'] != 4): ?>
                            <a href="?toggle_status=<?php echo $discount['id']; ?>" 
                               class="text-yellow-600 hover:text-yellow-800" 
                               onclick="return confirm('Toggle discount type status?')">
                                <i class="fas <?php echo $discount['is_active'] == 1 ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i> 
                                <?php echo $discount['is_active'] == 1 ? 'Deactivate' : 'Activate'; ?>
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400 text-sm">
                                <i class="fas fa-lock mr-1"></i> Cannot modify
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Reference Guide -->
<!-- <div class="bg-gray-50 rounded-lg p-6 mt-8">
    <h3 class="font-bold text-gray-800 mb-4">
        <i class="fas fa-question-circle mr-2"></i>Discount Application Guide
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="border rounded-lg p-3">
            <div class="flex items-center mb-2">
                <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs font-bold mr-2">SENIOR</span>
                <span class="text-sm">Senior Citizen</span>
            </div>
            <p class="text-sm text-gray-600">20% discount on base fare. Requires valid Senior Citizen ID.</p>
        </div>
        <div class="border rounded-lg p-3">
            <div class="flex items-center mb-2">
                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-bold mr-2">PWD</span>
                <span class="text-sm">Persons with Disability</span>
            </div>
            <p class="text-sm text-gray-600">20% discount on base fare. Requires valid PWD ID.</p>
        </div>
        <div class="border rounded-lg p-3">
            <div class="flex items-center mb-2">
                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold mr-2">STUDENT</span>
                <span class="text-sm">Student</span>
            </div>
            <p class="text-sm text-gray-600">10% discount on base fare. Requires valid School ID.</p>
        </div>
        <div class="border rounded-lg p-3">
            <div class="flex items-center mb-2">
                <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-bold mr-2">REGULAR</span>
                <span class="text-sm">Regular Passenger</span>
            </div>
            <p class="text-sm text-gray-600">No discount applied. Full base fare is charged.</p>
        </div>
    </div>
</div> -->

<?php include_once '../includes/footer.php'; ?>