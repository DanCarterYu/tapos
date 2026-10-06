<?php
// collector/load_balance.php
// Collector loads balance for passenger

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

$error = '';
$success = '';
$passenger = null;
$rfid_uid = '';

// Handle RFID capture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'get_passenger') {
        $rfid_uid = trim($_POST['rfid_uid']);
        
        if (empty($rfid_uid)) {
            $error = 'Please tap the RFID card';
        } else {
            // Get passenger info
            $stmt = $pdo->prepare("SELECT *, 
                                   CONCAT(first_name, ' ', 
                                          IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                                          last_name) as full_name 
                                   FROM passengers WHERE rfid_uid = ? AND status = 'active'");
            $stmt->execute([$rfid_uid]);
            $passenger = $stmt->fetch();
            
            if (!$passenger) {
                $error = 'Invalid or inactive RFID card';
            }
        }
    }
    
    if ($action === 'load_balance') {
        $rfid_uid = trim($_POST['rfid_uid']);
        $amount = floatval($_POST['amount']);
        
        if (empty($rfid_uid)) {
            $error = 'RFID card not found';
        } elseif ($amount <= 0) {
            $error = 'Please enter a valid amount';
        } elseif ($amount < 10) {
            $error = 'Minimum load amount is ₱10.00';
        } else {
            // Get passenger
            $stmt = $pdo->prepare("SELECT * FROM passengers WHERE rfid_uid = ? AND status = 'active'");
            $stmt->execute([$rfid_uid]);
            $passenger = $stmt->fetch();
            
            if (!$passenger) {
                $error = 'Invalid or inactive RFID card';
            } else {
                // Update balance
                $new_balance = $passenger['balance'] + $amount;
                $stmt = $pdo->prepare("UPDATE passengers SET balance = ? WHERE id = ?");
                $stmt->execute([$new_balance, $passenger['id']]);
                
                // Record in balance_loads
                $reference_no = 'LOAD-' . date('YmdHis') . rand(100, 999);
                $stmt = $pdo->prepare("INSERT INTO balance_loads (passenger_id, amount, reference_no, loaded_by, loaded_by_role) 
                                    VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$passenger['id'], $amount, $reference_no, $collector_id, 'collector']);
                
                $success = "Balance loaded successfully!<br>
                           Passenger: <strong>" . htmlspecialchars($passenger['full_name']) . "</strong><br>
                           Amount: <strong>₱" . number_format($amount, 2) . "</strong><br>
                           New Balance: <strong>₱" . number_format($new_balance, 2) . "</strong>";
                
                $passenger = null;
                $rfid_uid = '';
            }
        }
    }
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-plus-circle mr-2 text-green-500"></i>Load Balance
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Left: RFID Tap Section -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold">
                <i class="fas fa-id-card mr-2 text-blue-500"></i>Find Passenger
            </h2>
        </div>
        <div class="p-6">
            <div class="bg-blue-50 rounded-lg p-4 mb-6 text-center">
                <div class="bg-blue-100 inline-block p-3 rounded-full mb-3">
                    <i class="fas fa-id-card text-blue-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">Tap RFID Card</h3>
                <p class="text-sm text-gray-600 mb-3">Place the passenger's RFID card on the reader</p>
                
                <form method="POST" action="" id="rfidForm">
                    <input type="hidden" name="action" value="get_passenger">
                    <input type="text" name="rfid_uid" id="rfidInput" 
                           value="<?php echo htmlspecialchars($rfid_uid); ?>"
                           placeholder="Card UID will appear here after tapping..."
                           class="w-full text-center font-mono text-md px-4 py-3 border-2 border-blue-300 rounded-lg bg-white"
                           readonly
                           style="cursor: pointer;">
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle"></i> Tap the RFID card on the reader to auto-fill
                    </p>
                    <button type="submit" class="mt-3 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-search mr-2"></i>Find Passenger
                    </button>
                </form>
            </div>
            
            <!-- Passenger Info Display -->
            <?php if ($passenger): ?>
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <h4 class="font-bold text-gray-700 mb-2">Passenger Information</h4>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <p class="text-gray-500">Name</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($passenger['full_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">RFID UID</p>
                        <p class="font-mono"><?php echo htmlspecialchars($passenger['rfid_uid']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Current Balance</p>
                        <p class="font-bold text-green-600">₱<?php echo number_format($passenger['balance'], 2); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Loyalty Points</p>
                        <p class="text-yellow-600"><?php echo number_format($passenger['loyalty_points']); ?> pts</p>
                    </div>
                </div>
            </div>
            
            <!-- Load Balance Form -->
            <form method="POST" action="">
                <input type="hidden" name="action" value="load_balance">
                <input type="hidden" name="rfid_uid" value="<?php echo htmlspecialchars($passenger['rfid_uid']); ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Amount to Load (₱) *</label>
                    <input type="number" name="amount" step="1" min="10" required
                           placeholder="10.00"
                           class="w-full px-3 py-3 text-2xl border border-gray-300 rounded-lg focus:outline-none focus:border-green-500">
                    <p class="text-sm text-gray-500 mt-2">Minimum load amount: ₱10.00</p>
                </div>
                
                <!-- Quick Amount Buttons -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <button type="button" onclick="setAmount(10)" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm transition">₱10</button>
                    <button type="button" onclick="setAmount(20)" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm transition">₱20</button>
                    <button type="button" onclick="setAmount(50)" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm transition">₱50</button>
                    <button type="button" onclick="setAmount(100)" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm transition">₱100</button>
                    <button type="button" onclick="setAmount(200)" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm transition">₱200</button>
                    <button type="button" onclick="setAmount(500)" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm transition">₱500</button>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Note:</strong> Collect the cash payment from the passenger before loading.
                        The cash must be submitted to management at the end of the day.
                    </p>
                </div>
                
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg transition text-lg font-bold">
                    <i class="fas fa-plus-circle mr-2"></i>Load Balance
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right: Info and Guidelines -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold">
                <i class="fas fa-info-circle mr-2 text-blue-500"></i>Guidelines
            </h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="font-bold text-blue-800 mb-2">How to Load Balance</h4>
                    <ol class="text-sm text-blue-700 space-y-2">
                        <li>1. <strong>Tap</strong> the passenger's RFID card on the reader</li>
                        <li>2. <strong>Verify</strong> the passenger information displayed</li>
                        <li>3. <strong>Enter</strong> the amount the passenger wants to load</li>
                        <li>4. <strong>Collect</strong> the cash payment from the passenger</li>
                        <li>5. <strong>Click</strong> "Load Balance" to complete the transaction</li>
                    </ol>
                </div>
                
                <div class="bg-yellow-50 rounded-lg p-4">
                    <h4 class="font-bold text-yellow-800 mb-2">Important Notes</h4>
                    <ul class="text-sm text-yellow-700 space-y-1">
                        <li>• <strong>Cash Submission:</strong> All cash collected must be submitted to management at the end of the day</li>
                        <li>• <strong>No Discounts:</strong> Balance loads are not subject to discounts</li>
                        <li>• <strong>Loyalty Points:</strong> Loading balance does not earn loyalty points</li>
                        <li>• <strong>Minimum Load:</strong> ₱10.00 minimum load amount</li>
                    </ul>
                </div>
                
                <div class="bg-green-50 rounded-lg p-4">
                    <h4 class="font-bold text-green-800 mb-2">Benefits</h4>
                    <ul class="text-sm text-green-700 space-y-1">
                        <li>• ✅ Passengers can load at ANY port</li>
                        <li>• ✅ No need to travel to main port</li>
                        <li>• ✅ Immediate balance update</li>
                        <li>• ✅ Digital record of all loads</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function setAmount(amount) {
        document.querySelector('input[name="amount"]').value = amount;
    }
    
    // RFID Keyboard Emulation Capture
    let rfidBuffer = '';
    let rfidTimer = null;
    let rfidInputField = document.getElementById('rfidInput');
    
    document.addEventListener('DOMContentLoaded', function() {
        if (rfidInputField) {
            rfidInputField.focus();
        }
    });
    
    document.addEventListener('keypress', function(e) {
        clearTimeout(rfidTimer);
        rfidBuffer += e.key;
        
        if (e.key === 'Enter') {
            e.preventDefault();
            let cardUid = rfidBuffer.replace(/Enter/g, '').trim();
            rfidBuffer = '';
            
            if (cardUid.length > 0 && rfidInputField) {
                rfidInputField.value = cardUid;
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
    
    if (rfidInputField) {
        rfidInputField.addEventListener('click', function() {
            this.focus();
        });
    }
</script>

<?php include_once '../includes/footer.php'; ?>