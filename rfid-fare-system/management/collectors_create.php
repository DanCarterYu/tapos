<?php
// management/collectors_create.php
// Register new collector account with AUTO-GENERATED employee number

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

// Function to generate next employee number
function generateEmployeeNumber($pdo) {
    // Get the latest employee number
    $stmt = $pdo->query("SELECT employee_number FROM collectors ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetch();
    
    if ($last) {
        // Extract number from VGL-XXX format
        $last_num = intval(substr($last['employee_number'], 4));
        $next_num = $last_num + 1;
    } else {
        $next_num = 1;
    }
    
    // Format as VGL-001, VGL-002, etc.
    return 'VGL' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
}

$error = '';
$success = '';
$generated_employee_number = generateEmployeeNumber($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_number = trim($_POST['employee_number']);
    $first_name = strtoupper(trim($_POST['first_name']));
    $middle_name = strtoupper(trim($_POST['middle_name']));
    $last_name = strtoupper(trim($_POST['last_name']));
    $contact = trim($_POST['contact']);
    $default_password = $employee_number; // Password same as employee number
    
    // Validate
    if (empty($first_name)) {
        $error = 'Please enter first name';
    } elseif (empty($last_name)) {
        $error = 'Please enter last name';
    } else {
        // Check if employee number already exists (should not happen with auto-gen)
        $stmt = $pdo->prepare("SELECT id FROM collectors WHERE employee_number = ?");
        $stmt->execute([$employee_number]);
        if ($stmt->fetch()) {
            $error = 'Employee number already exists. Please try again.';
        } else {
            // Check if collector with same FIRST NAME + LAST NAME already exists
            $stmt = $pdo->prepare("SELECT id FROM collectors WHERE first_name = ? AND last_name = ?");
            $stmt->execute([$first_name, $last_name]);
            if ($stmt->fetch()) {
                $error = 'A collector with this name already exists. Please check for duplicates.';
            } else {
                // Hash the password
                $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

                // Insert collector with split names
                $stmt = $pdo->prepare("INSERT INTO collectors (employee_number, first_name, middle_name, last_name, password, contact, created_by) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$employee_number, $first_name, $middle_name, $last_name, $hashed_password, $contact, $_SESSION['user_id']])) {
                    $success = "Collector registered successfully!<br>
                               Employee Number: <strong>$employee_number</strong><br>
                               Default Password: <strong>$employee_number</strong>";
                    
                    // Generate next employee number for next registration
                    $generated_employee_number = generateEmployeeNumber($pdo);
                    $_POST = [];
                } else {
                    $error = 'Failed to register collector';
                }
            }
        }
    }
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-user-plus mr-2"></i>Register New Collector
    </h1>
    <a href="collectors.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
        <i class="fas fa-arrow-left mr-2"></i>Back to Collectors
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
        <!-- Auto-generated Employee Number Display -->
        <div class="bg-blue-50 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-gray-700 font-bold mb-1">Employee Number (Auto-generated)</label>
                    <p class="text-2xl font-mono font-bold text-blue-700"><?php echo $generated_employee_number; ?></p>
                    <p class="text-sm text-gray-600 mt-1">This will be the collector's username and default password</p>
                </div>
                <div class="bg-blue-200 rounded-full p-3">
                    <i class="fas fa-id-card text-blue-700 text-2xl"></i>
                </div>
            </div>
            <input type="hidden" name="employee_number" value="<?php echo $generated_employee_number; ?>">
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-gray-700 font-bold mb-2">First Name *</label>
                <input type="text" name="first_name" required id="createFirstName"
                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                       placeholder="JUAN"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">Middle Name</label>
                <input type="text" name="middle_name" id="createMiddleName"
                       value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>"
                       placeholder="DELA"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Optional</p>
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">Last Name *</label>
                <input type="text" name="last_name" required id="createLastName"
                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                       placeholder="CRUZ"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
        </div>
        
        <div class="mt-4">
            <label class="block text-gray-700 font-bold mb-2">Contact Number</label>
            <input type="text" name="contact" 
                   value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>"
                   placeholder="e.g., 09123456789"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
            <p class="text-sm text-yellow-800">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Default Login Credentials:</strong><br>
                • Username: <strong><?php echo $generated_employee_number; ?></strong><br>
                • Password: <strong><?php echo $generated_employee_number; ?></strong> (same as username)<br>
                • The collector can change their password after logging in to their portal.
            </p>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                <i class="fas fa-save mr-2"></i>Register Collector
            </button>
            <a href="collectors.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition ml-2">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
    // Auto-uppercase for name fields
    document.getElementById('createFirstName').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    document.getElementById('createMiddleName').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    document.getElementById('createLastName').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
</script>

<?php include_once '../includes/footer.php'; ?>