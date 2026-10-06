<?php
// management/passengers_edit.php
// Edit passenger information with split names

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Helper function to calculate age from date of birth
function calculateAge($date_of_birth) {
    if (empty($date_of_birth)) {
        return '-';
    }
    $birthDate = new DateTime($date_of_birth);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: passengers.php');
    exit();
}

// Get passenger data
$stmt = $pdo->prepare("SELECT * FROM passengers WHERE id = ?");
$stmt->execute([$id]);
$passenger = $stmt->fetch();

if (!$passenger) {
    header('Location: passengers.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = strtoupper(trim($_POST['first_name']));
    $middle_name = strtoupper(trim($_POST['middle_name']));
    $last_name = strtoupper(trim($_POST['last_name']));
    $date_of_birth = trim($_POST['date_of_birth']);
    $sex = trim($_POST['sex']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $status = $_POST['status'];
    
    if (empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($sex) || empty($address)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if passenger with same FIRST NAME + LAST NAME already exists (excluding current)
        $stmt = $pdo->prepare("SELECT id FROM passengers WHERE first_name = ? AND last_name = ? AND id != ?");
        $stmt->execute([$first_name, $last_name, $id]);
        if ($stmt->fetch()) {
            $error = 'A passenger with this name already exists.';
        } else {
            $stmt = $pdo->prepare("UPDATE passengers SET 
                                   first_name = ?, 
                                   middle_name = ?, 
                                   last_name = ?, 
                                   date_of_birth = ?,
                                   sex = ?,
                                   address = ?,
                                   email = ?, 
                                   contact = ?, 
                                   status = ? 
                                   WHERE id = ?");
            if ($stmt->execute([$first_name, $middle_name, $last_name, $date_of_birth, $sex, $address, $email, $contact, $status, $id])) {
                $success = 'Passenger information updated successfully!';
                // Refresh data
                $stmt = $pdo->prepare("SELECT * FROM passengers WHERE id = ?");
                $stmt->execute([$id]);
                $passenger = $stmt->fetch();
            } else {
                $error = 'Failed to update passenger';
            }
        }
    }
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-user-edit mr-2"></i>Edit Passenger
    </h1>
    <a href="passengers.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
        <i class="fas fa-arrow-left mr-2"></i>Back to Passengers
    </a>
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

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-gray-700 font-bold mb-2">RFID UID</label>
                <input type="text" value="<?php echo htmlspecialchars($passenger['rfid_uid']); ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                <p class="text-sm text-gray-500 mt-1">RFID UID cannot be changed</p>
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2">Created By</label>
                <p class="text-sm font-medium mt-1">
                    <?php 
                        if ($passenger['created_by_role'] == 'management') {
                            echo '<span class="text-blue-600">Management</span>';
                        } else {
                            echo '<span class="text-green-600">Collector: ' . htmlspecialchars($passenger['created_by']) . '</span>';
                        }
                    ?>
                </p>
                <p class="text-xs text-gray-500">Created on <?php echo date('F d, Y', strtotime($passenger['created_at'])); ?></p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
            <div>
                <label class="block text-gray-700 font-bold mb-2">First Name *</label>
                <input type="text" name="first_name" required id="editFirstName"
                       value="<?php echo htmlspecialchars($passenger['first_name']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">Middle Name</label>
                <input type="text" name="middle_name" id="editMiddleName"
                       value="<?php echo htmlspecialchars($passenger['middle_name']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Optional</p>
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">Last Name *</label>
                <input type="text" name="last_name" required id="editLastName"
                       value="<?php echo htmlspecialchars($passenger['last_name']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Date of Birth *</label>
                <input type="date" name="date_of_birth" required 
                    value="<?php echo htmlspecialchars($passenger['date_of_birth']); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <?php if (!empty($passenger['date_of_birth'])): ?>
                    <p class="text-xs text-gray-500 mt-1">
                        Age: <strong><?php echo calculateAge($passenger['date_of_birth']); ?></strong> years old
                    </p>
                <?php endif; ?>
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">Sex *</label>
                <select name="sex" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <option value="">-- Select --</option>
                    <option value="M" <?php echo ($passenger['sex'] ?? '') == 'M' ? 'selected' : ''; ?>>Male</option>
                    <option value="F" <?php echo ($passenger['sex'] ?? '') == 'F' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-gray-700 font-bold mb-2">Address *</label>
            <textarea name="address" id="editAddress" required rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                placeholder="Enter complete address"><?php echo htmlspecialchars($passenger['address'] ?? ''); ?></textarea>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Email</label>
                <input type="email" name="email" 
                    value="<?php echo htmlspecialchars($passenger['email']); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Optional</p>
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">Contact Number</label>
                <input type="text" name="contact" 
                    value="<?php echo htmlspecialchars($passenger['contact']); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Optional</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <option value="active" <?php echo $passenger['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="lost" <?php echo $passenger['status'] == 'lost' ? 'selected' : ''; ?>>Lost Card</option>
                    <option value="blocked" <?php echo $passenger['status'] == 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                </select>
            </div>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4 mt-6">
            <p class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Account Info:</strong> Created on <?php echo date('F d, Y', strtotime($passenger['created_at'])); ?>
                | Balance: ₱<?php echo number_format($passenger['balance'], 2); ?>
                | Loyalty Points: <?php echo number_format($passenger['loyalty_points']); ?>
            </p>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                <i class="fas fa-save mr-2"></i>Save Changes
            </button>
            <a href="passengers.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition ml-2">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
    // Auto-uppercase for name fields
    document.getElementById('editFirstName').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    document.getElementById('editMiddleName').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    document.getElementById('editLastName').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    // Auto-uppercase for address
    document.getElementById('editAddress').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
</script>

<?php include_once '../includes/footer.php'; ?>