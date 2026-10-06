<?php
// passenger/profile.php
// Passenger Profile and Password Change

require_once 'session_config.php';
require_once '../config/database.php';

// Check if logged in as passenger
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header('Location: login.php');
    exit();
}

// =============================================
// ADDED: Helper function to calculate age
// =============================================
function calculateAge($date_of_birth) {
    if (empty($date_of_birth)) {
        return '-';
    }
    $birthDate = new DateTime($date_of_birth);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}

$passenger_id = $_SESSION['user_id'];
$passenger_name = $_SESSION['full_name'];

// Get passenger data
$stmt = $pdo->prepare("SELECT * FROM passengers WHERE id = ?");
$stmt->execute([$passenger_id]);
$passenger = $stmt->fetch();

$error = '';
$success = '';

    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
            if ($action === 'update_profile') {
            $email = trim($_POST['email']);
            $contact = trim($_POST['contact']);
            
            $stmt = $pdo->prepare("UPDATE passengers SET 
                                email = ?, 
                                contact = ?
                                WHERE id = ?");
            if ($stmt->execute([$email, $contact, $passenger_id])) {
                $success = 'Profile updated successfully!';
                // Refresh data
                $stmt = $pdo->prepare("SELECT * FROM passengers WHERE id = ?");
                $stmt->execute([$passenger_id]);
                $passenger = $stmt->fetch();
            } else {
                $error = 'Failed to update profile';
            }
        }
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate current password
        if (!password_verify($current_password, $passenger['password'])) {
            $error = 'Current password is incorrect';
        } elseif (strlen($new_password) < 4) {
            $error = 'New password must be at least 4 characters';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } else {
            // Hash and update new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE passengers SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $passenger_id])) {
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
                    <p class="text-gray-500 text-sm">RFID UID</p>
                    <p class="font-mono font-bold text-lg"><?php echo htmlspecialchars($passenger['rfid_uid']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Full Name</p>
                    <p class="font-semibold text-lg">
                        <?php 
                            $full_name = trim($passenger['first_name'] . ' ' . 
                                        ($passenger['middle_name'] ? $passenger['middle_name'] . ' ' : '') . 
                                        $passenger['last_name']);
                            echo htmlspecialchars($full_name); 
                        ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Current Balance</p>
                    <p class="font-bold text-green-600 text-lg">₱<?php echo number_format($passenger['balance'], 2); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Loyalty Points</p>
                    <p class="font-bold text-yellow-600 text-lg"><?php echo number_format($passenger['loyalty_points']); ?></p>
                </div>
                
                <!-- ============================================= -->
                <!-- ADDED: Date of Birth, Sex, Address Display -->
                <!-- ============================================= -->
                <div>
                    <p class="text-gray-500 text-sm">Date of Birth</p>
                    <p class="font-semibold text-lg">
                        <?php if (!empty($passenger['date_of_birth'])): ?>
                            <?php echo date('F d, Y', strtotime($passenger['date_of_birth'])); ?>
                            <span class="text-sm text-gray-500">(Age: <?php echo calculateAge($passenger['date_of_birth']); ?>)</span>
                        <?php else: ?>
                            <span class="text-gray-400">Not provided</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div>
                    <p class="text-gray-500 text-sm">Sex</p>
                    <p class="font-semibold text-lg">
                        <?php if (!empty($passenger['sex'])): ?>
                            <?php echo $passenger['sex'] == 'M' ? 'Male' : 'Female'; ?>
                        <?php else: ?>
                            <span class="text-gray-400">Not provided</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div>
                    <p class="text-gray-500 text-sm">Address</p>
                    <p class="font-semibold text-lg">
                        <?php if (!empty($passenger['address'])): ?>
                            <?php echo nl2br(htmlspecialchars($passenger['address'])); ?>
                        <?php else: ?>
                            <span class="text-gray-400">Not provided</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div>
                    <p class="text-gray-500 text-sm">Status</p>
                    <span class="px-2 py-1 rounded text-xs <?php echo $passenger['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo ucfirst($passenger['status']); ?>
                    </span>
                </div>
                
                <!-- Update Profile Form -->
                <!-- ============================================= -->
                <!-- CHANGED: Added DOB, Sex, Address to form -->
                <!-- ============================================= -->
                <form method="POST" action="" class="mt-4 pt-4 border-t">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($passenger['email']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-yellow-500">
                            <p class="text-xs text-gray-500 mt-1">Optional</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Contact Number</label>
                            <input type="text" name="contact" value="<?php echo htmlspecialchars($passenger['contact']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-yellow-500">
                            <p class="text-xs text-gray-500 mt-1">Optional</p>
                        </div>
                    </div>
                    
                    <button type="submit" class="mt-3 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Update Profile
                    </button>
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
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-yellow-500"
                               placeholder="Enter current password">
                        <p class="text-xs text-gray-500 mt-1">Default password is your RFID UID</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">New Password</label>
                        <input type="password" name="new_password" required minlength="4"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-yellow-500"
                               placeholder="Enter new password (min 4 characters)">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-yellow-500"
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