<?php
// collector/profile.php
// Collector Profile and Password Change

require_once 'session_config.php';
require_once '../config/database.php';

// Check if logged in as collector
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'collector') {
    header('Location: login.php');
    exit();
}

$collector_id = $_SESSION['user_id'];
$collector_name = $_SESSION['full_name'];
$employee_number = $_SESSION['username'];

// Get collector data with full name
$stmt = $pdo->prepare("SELECT *, 
                       CONCAT(first_name, ' ', 
                              IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                              last_name) as full_name 
                       FROM collectors WHERE id = ?");
$stmt->execute([$collector_id]);
$collector = $stmt->fetch();

// Build full name variable for display
$full_name = $collector['full_name'];

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $contact = trim($_POST['contact']);
        
        $stmt = $pdo->prepare("UPDATE collectors SET contact = ? WHERE id = ?");
        if ($stmt->execute([$contact, $collector_id])) {
            $success = 'Contact number updated successfully!';
            // Refresh data
            $stmt = $pdo->prepare("SELECT *, 
                                   CONCAT(first_name, ' ', 
                                          IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                                          last_name) as full_name 
                                   FROM collectors WHERE id = ?");
            $stmt->execute([$collector_id]);
            $collector = $stmt->fetch();
            $full_name = $collector['full_name'];
        } else {
            $error = 'Failed to update contact number';
        }
    }
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate current password
        if (!password_verify($current_password, $collector['password'])) {
            $error = 'Current password is incorrect';
        } elseif (strlen($new_password) < 4) {
            $error = 'New password must be at least 4 characters';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } else {
            // Hash and update new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE collectors SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $collector_id])) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password';
            }
        }
    }
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-user-cog mr-2"></i>My Profile
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Profile Information -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold">
                <i class="fas fa-id-card mr-2 text-blue-500"></i>Profile Information
            </h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div>
                    <p class="text-gray-500 text-sm">Employee Number</p>
                    <p class="font-mono font-bold text-lg"><?php echo htmlspecialchars($collector['employee_number']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Full Name</p>
                    <p class="font-semibold text-lg"><?php echo htmlspecialchars($full_name); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Status</p>
                    <span class="px-2 py-1 rounded text-xs <?php echo $collector['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo ucfirst($collector['status']); ?>
                    </span>
                </div>
                
                <!-- Update Contact Form -->
                <form method="POST" action="" class="mt-4 pt-4 border-t">
                    <input type="hidden" name="action" value="update_profile">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Contact Number</label>
                        <div class="flex gap-2">
                            <input type="text" name="contact" value="<?php echo htmlspecialchars($collector['contact']); ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                                <i class="fas fa-save mr-2"></i>Update
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold">
                <i class="fas fa-key mr-2 text-yellow-500"></i>Change Password
            </h2>
        </div>
        <div class="p-6">
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Current Password</label>
                        <input type="password" name="current_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                               placeholder="Enter current password">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">New Password</label>
                        <input type="password" name="new_password" required minlength="4"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                               placeholder="Enter new password (min 4 characters)">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                               placeholder="Confirm new password">
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <p class="text-sm text-yellow-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            Password must be at least 4 characters long
                        </p>
                    </div>
                    
                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white py-2 rounded-lg transition">
                        <i class="fas fa-key mr-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>