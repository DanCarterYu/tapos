<?php
// collector/register_rfid.php
// Collector registers new passenger with RFID card

require_once 'session_config.php';
require_once '../config/database.php';

// Check if logged in as collector
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'collector') {
    header('Location: login.php');
    exit();
}

$collector_id = $_SESSION['user_id'];
$collector_name = $_SESSION['full_name'];
$employee_number = $_SESSION['username']; // e.g., VGL001

$error = '';
$success = '';
$rfid_uid = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rfid_uid = trim($_POST['rfid_uid']);
    $first_name = strtoupper(trim($_POST['first_name']));
    $middle_name = strtoupper(trim($_POST['middle_name']));
    $last_name = strtoupper(trim($_POST['last_name']));
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $date_of_birth = trim($_POST['date_of_birth']);
    $sex = trim($_POST['sex']);
    $address = trim($_POST['address']);
    
    // Validate
    if (empty($rfid_uid)) {
        $error = 'Please tap the RFID card on the reader';
    } elseif (empty($first_name)) {
        $error = 'Please enter first name';
    } elseif (empty($last_name)) {
        $error = 'Please enter last name';
    } elseif (empty($date_of_birth)) {
        $error = 'Please enter date of birth';
    } elseif (empty($sex)) {
        $error = 'Please select sex';
    } elseif (empty($address)) {
        $error = 'Please enter address';
    } else {
        // Check if RFID already exists
        $stmt = $pdo->prepare("SELECT id FROM passengers WHERE rfid_uid = ?");
        $stmt->execute([$rfid_uid]);
        if ($stmt->fetch()) {
            $error = 'RFID card already registered to another passenger';
        } else {
            // Check if passenger with same FIRST NAME + LAST NAME already exists
            $stmt = $pdo->prepare("SELECT id FROM passengers WHERE first_name = ? AND last_name = ?");
            $stmt->execute([$first_name, $last_name]);
            if ($stmt->fetch()) {
                $error = 'A passenger with this name already exists. Please check for duplicates.';
            } else {
                // Default password is the RFID UID
                $default_password = $rfid_uid;
                $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
                
                // Insert passenger with collector as creator
                $stmt = $pdo->prepare("INSERT INTO passengers (rfid_uid, first_name, middle_name, last_name, password, email, contact, date_of_birth, sex, address, created_by, created_by_role) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$rfid_uid, $first_name, $middle_name, $last_name, $hashed_password, $email, $contact, $date_of_birth, $sex, $address, $employee_number, 'collector'])) {
                    // VERIFY: Check if password was stored correctly
                    $verify_stmt = $pdo->prepare("SELECT password FROM passengers WHERE rfid_uid = ?");
                    $verify_stmt->execute([$rfid_uid]);
                    $stored = $verify_stmt->fetch();
                    
                    if (password_verify($default_password, $stored['password'])) {
                        $success = "Passenger registered successfully!<br>
                                RFID Card: <strong>$rfid_uid</strong><br>
                                Default Password: <strong>$rfid_uid</strong><br>
                                (Passenger can change password after logging in)";
                        // Clear form
                        $rfid_uid = '';
                        $_POST = [];
                    } else {
                        // Something went wrong - delete the passenger
                        $delete_stmt = $pdo->prepare("DELETE FROM passengers WHERE rfid_uid = ?");
                        $delete_stmt->execute([$rfid_uid]);
                        $error = 'Password encryption failed. Please try again.';
                    }
                } else {
                    $error = 'Failed to register passenger';
                }
            }
        }
    }
}

// Include header
include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-id-card mr-2 text-blue-500"></i>Register RFID Card
    </h1>
    <div class="text-sm text-gray-500">
        Collector: <span class="font-semibold"><?php echo htmlspecialchars($collector_name); ?></span>
        <span class="mx-2">|</span>
        Employee: <span class="font-semibold"><?php echo htmlspecialchars($employee_number); ?></span>
    </div>
</div>

<!-- Success/Error Messages -->
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
    <!-- RFID Tap Section -->
    <div class="bg-blue-50 rounded-lg p-6 mb-6 text-center">
        <div class="bg-blue-100 inline-block p-4 rounded-full mb-4">
            <i class="fas fa-id-card text-blue-600 text-4xl"></i>
        </div>
        <h2 class="text-xl font-bold mb-2">Tap RFID Card</h2>
        <p class="text-gray-600 mb-4">Place the unregistered RFID card on the reader</p>
        
        <form method="POST" action="" id="rfidForm">
            <input type="text" name="rfid_uid" id="rfidInput" 
                   value="<?php echo htmlspecialchars($rfid_uid); ?>"
                   placeholder="Card UID will appear here after tapping..."
                   class="w-full max-w-md text-center font-mono text-lg px-4 py-3 border-2 border-blue-300 rounded-lg bg-white"
                   readonly
                   style="cursor: pointer;">
            <p class="text-sm text-gray-500 mt-2">
                <i class="fas fa-info-circle"></i> Tap the RFID card on the reader to auto-fill this field
            </p>
        </form>
    </div>
    
    <!-- Passenger Information Form -->
    <form method="POST" action="">
        <input type="hidden" name="rfid_uid" id="hiddenRfidUid" value="<?php echo htmlspecialchars($rfid_uid); ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-gray-700 font-bold mb-2">First Name *</label>
                <input type="text" name="first_name" required id="firstNameInput"
                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                       placeholder="JUAN"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">Middle Name</label>
                <input type="text" name="middle_name" id="middleNameInput"
                       value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>"
                       placeholder="DELA"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Optional</p>
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">Last Name *</label>
                <input type="text" name="last_name" required id="lastNameInput"
                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                       placeholder="CRUZ"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Date of Birth *</label>
                <input type="date" name="date_of_birth" required 
                    value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">Sex *</label>
                <select name="sex" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <option value="">-- Select --</option>
                    <option value="M" <?php echo ($_POST['sex'] ?? '') == 'M' ? 'selected' : ''; ?>>Male</option>
                    <option value="F" <?php echo ($_POST['sex'] ?? '') == 'F' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-gray-700 font-bold mb-2">Address *</label>
            <textarea name="address" id="addressInput" required rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                    placeholder="Enter complete address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Email </label>
                <input type="email" name="email"  
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    placeholder="juan@example.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">Contact Number </label>
                <input type="text" name="contact"  
                    value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>"
                    placeholder="09123456789"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
            <p class="text-sm text-yellow-800">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Default Password:</strong> The default password for this passenger is the <strong>RFID UID</strong>.
                The passenger can change their password after logging in to their portal.
            </p>
            <p class="text-sm text-yellow-800 mt-2">
                <i class="fas fa-building mr-2"></i>
                <strong>Registered By:</strong> This passenger will be registered under your collector account (<?php echo htmlspecialchars($employee_number); ?>).
                Management can view this in the passenger list.
            </p>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                <i class="fas fa-save mr-2"></i>Register Passenger
            </button>
            <button type="reset" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition ml-2">
                <i class="fas fa-undo mr-2"></i>Reset
            </button>
        </div>
    </form>
</div>

<script>
    // Auto-uppercase for name fields
    document.getElementById('firstNameInput').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    document.getElementById('middleNameInput').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    document.getElementById('lastNameInput').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    // Auto-uppercase for address
    document.getElementById('addressInput').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // RFID Keyboard Emulation Capture
    let rfidBuffer = '';
    let rfidTimer = null;
    let rfidInputField = document.getElementById('rfidInput');
    let hiddenRfidField = document.getElementById('hiddenRfidUid');
    
    document.addEventListener('DOMContentLoaded', function() {
        rfidInputField.focus();
    });
    
    document.addEventListener('keypress', function(e) {
        clearTimeout(rfidTimer);
        rfidBuffer += e.key;
        
        if (e.key === 'Enter') {
            e.preventDefault();
            let cardUid = rfidBuffer.replace(/Enter/g, '').trim();
            rfidBuffer = '';
            
            if (cardUid.length > 0) {
                rfidInputField.value = cardUid;
                hiddenRfidField.value = cardUid;
                
                rfidInputField.classList.add('border-green-500', 'bg-green-50');
                setTimeout(() => {
                    rfidInputField.classList.remove('border-green-500', 'bg-green-50');
                }, 1000);
            }
        }
        
        rfidTimer = setTimeout(() => {
            rfidBuffer = '';
        }, 100);
    });
    
    rfidInputField.addEventListener('click', function() {
        this.focus();
    });
    
    rfidInputField.addEventListener('input', function() {
        hiddenRfidField.value = this.value;
    });
</script>

<?php include_once '../includes/footer.php'; ?>