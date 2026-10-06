<?php
// management/passengers_load_balance.php
// Load balance to passenger account

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $reference_no = 'LOAD-' . date('YmdHis') . rand(100, 999);
    
    if ($amount <= 0) {
        $error = 'Please enter a valid amount';
    } else {
        // Update passenger balance
        $new_balance = $passenger['balance'] + $amount;
        $stmt = $pdo->prepare("UPDATE passengers SET balance = ? WHERE id = ?");
        $stmt->execute([$new_balance, $id]);
        
        // Record the load transaction
        $stmt = $pdo->prepare("INSERT INTO balance_loads (passenger_id, amount, reference_no, loaded_by, loaded_by_role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $amount, $reference_no, $_SESSION['user_id'], 'management']);
        
        $success = "₱" . number_format($amount, 2) . " loaded successfully! New balance: ₱" . number_format($new_balance, 2);
        
        // Refresh passenger data
        $stmt = $pdo->prepare("SELECT * FROM passengers WHERE id = ?");
        $stmt->execute([$id]);
        $passenger = $stmt->fetch();
        $full_name = trim($passenger['first_name'] . ' ' . 
                     ($passenger['middle_name'] ? $passenger['middle_name'] . ' ' : '') . 
                     $passenger['last_name']);
    }
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-plus-circle mr-2"></i>Load Balance
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
        <h2 class="text-lg font-bold">Passenger Information</h2>
        <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
                <p class="text-gray-500 text-sm">Full Name</p>
                <p class="font-medium"><?php echo htmlspecialchars($full_name); ?></p>
            </div>
            <div>
                <p class="text-gray-500 text-sm">RFID UID</p>
                <p class="font-mono"><?php echo htmlspecialchars($passenger['rfid_uid']); ?></p>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Current Balance</p>
                <p class="text-2xl font-bold text-green-600">₱<?php echo number_format($passenger['balance'], 2); ?></p>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Loyalty Points</p>
                <p class="text-yellow-600 font-bold"><?php echo number_format($passenger['loyalty_points']); ?></p>
            </div>
        </div>
    </div>
    
    <form method="POST" action="">
        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2">Load Amount (₱)</label>
            <div class="flex items-center">
                <span class="text-2xl mr-2">₱</span>
                <input type="number" name="amount" step="1" min="1" required
                       placeholder="0.00"
                       class="flex-1 px-3 py-3 text-2xl border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <p class="text-sm text-gray-500 mt-2">Minimum load amount: ₱1.00</p>
        </div>
        
        <div class="bg-blue-50 rounded-lg p-4 mb-6">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>
                This will add the amount to the passenger's balance. The transaction will be recorded in the balance_loads table.
            </p>
        </div>
        
        <div class="flex gap-2">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition">
                <i class="fas fa-plus-circle mr-2"></i>Load Balance
            </button>
            <a href="passengers.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include_once '../includes/footer.php'; ?>