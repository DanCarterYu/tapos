<?php
// management/passengers_replace_rfid.php
// Replace lost or damaged RFID card

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

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

// Build full name from split fields
$full_name = trim($passenger['first_name'] . ' ' . 
             ($passenger['middle_name'] ? $passenger['middle_name'] . ' ' : '') . 
             $passenger['last_name']);

$error = '';
$success = '';
$new_rfid_uid = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_rfid_uid = trim($_POST['new_rfid_uid']);
    $reason = trim($_POST['reason']);
    
    if (empty($new_rfid_uid)) {
        $error = 'Please tap the new RFID card on the reader';
    } else {
        // Check if new RFID is already used by another passenger
        $stmt = $pdo->prepare("SELECT id FROM passengers WHERE rfid_uid = ? AND id != ?");
        $stmt->execute([$new_rfid_uid, $id]);
        if ($stmt->fetch()) {
            $error = 'This RFID card is already registered to another passenger';
        } else {
            // Record replacement
            $stmt = $pdo->prepare("INSERT INTO rfid_replacements (passenger_id, old_rfid_uid, new_rfid_uid, reason, replaced_by) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $passenger['rfid_uid'], $new_rfid_uid, $reason, $_SESSION['user_id']]);
            
            // Update passenger with new RFID
            $stmt = $pdo->prepare("UPDATE passengers SET rfid_uid = ?, status = 'active' WHERE id = ?");
            $stmt->execute([$new_rfid_uid, $id]);
            
            $success = "RFID card replaced successfully! Old card has been deactivated.";
            
            // Refresh passenger data
            $stmt = $pdo->prepare("SELECT * FROM passengers WHERE id = ?");
            $stmt->execute([$id]);
            $passenger = $stmt->fetch();
            $full_name = trim($passenger['first_name'] . ' ' . 
                         ($passenger['middle_name'] ? $passenger['middle_name'] . ' ' : '') . 
                         $passenger['last_name']);
            $new_rfid_uid = '';
        }
    }
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-id-card mr-2"></i>Replace RFID Card
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
    <div class="border-b pb-4 mb-6">
        <h2 class="text-lg font-bold">Current Card Information</h2>
        <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
                <p class="text-gray-500 text-sm">Passenger Name</p>
                <p class="font-medium"><?php echo htmlspecialchars($full_name); ?></p>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Current RFID UID</p>
                <p class="font-mono text-red-600"><?php echo htmlspecialchars($passenger['rfid_uid']); ?></p>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Current Balance</p>
                <p class="font-bold text-green-600">₱<?php echo number_format($passenger['balance'], 2); ?></p>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Loyalty Points</p>
                <p class="text-yellow-600 font-bold"><?php echo number_format($passenger['loyalty_points']); ?></p>
            </div>
        </div>
    </div>
    
    <!-- New RFID Tap Section -->
    <div class="bg-yellow-50 rounded-lg p-6 mb-6 text-center">
        <div class="bg-yellow-100 inline-block p-4 rounded-full mb-4">
            <i class="fas fa-id-card text-yellow-600 text-4xl"></i>
        </div>
        <h2 class="text-xl font-bold mb-2">Tap New RFID Card</h2>
        <p class="text-gray-600 mb-4">Place the NEW RFID card on the reader to capture its UID</p>
        
        <form method="POST" action="" id="rfidForm">
            <input type="text" name="new_rfid_uid" id="rfidInput" 
                   value="<?php echo htmlspecialchars($new_rfid_uid); ?>"
                   placeholder="New card UID will appear here after tapping..."
                   class="w-full max-w-md text-center font-mono text-lg px-4 py-3 border-2 border-yellow-300 rounded-lg bg-white"
                   readonly
                   style="cursor: pointer;">
        </form>
    </div>
    
    <!-- Replacement Form -->
    <form method="POST" action="">
        <input type="hidden" name="new_rfid_uid" id="hiddenRfidUid" value="<?php echo htmlspecialchars($new_rfid_uid); ?>">
        
        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2">Reason for Replacement</label>
            <select name="reason" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <option value="lost">Lost Card</option>
                <option value="damaged">Damaged Card</option>
                <option value="stolen">Stolen Card</option>
            </select>
        </div>
        
        <div class="bg-red-50 rounded-lg p-4 mb-6">
            <p class="text-sm text-red-800">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Warning:</strong> The old RFID card will be permanently deactivated. 
                The passenger will keep their current balance and loyalty points.
            </p>
        </div>
        
        <div class="flex gap-2">
            <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-2 rounded-lg transition">
                <i class="fas fa-exchange-alt mr-2"></i>Replace RFID Card
            </button>
            <a href="passengers.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
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