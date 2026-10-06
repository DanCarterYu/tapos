<?php
// passenger/balance_request.php
// Passenger Balance Request with Receipt Upload

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/upload_helper.php';

// Check if logged in as passenger
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header('Location: login.php');
    exit();
}

$passenger_id = $_SESSION['user_id'];
$passenger_name = $_SESSION['full_name'];

// Get passenger data
$stmt = $pdo->prepare("SELECT * FROM passengers WHERE id = ?");
$stmt->execute([$passenger_id]);
$passenger = $stmt->fetch();

$error = '';
$success = '';
$image_error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $amount = floatval($_POST['amount']);
    $payment_method = trim($_POST['payment_method']);
    $reference_no = trim($_POST['reference_no']);
    $sender_name = strtoupper(trim($_POST['sender_name']));
    $date_paid = trim($_POST['date_paid']);
    
    // Validate
    if ($amount < 100) {
        $error = 'Minimum request amount is ₱100.00';
    } elseif (empty($payment_method)) {
        $error = 'Please select a payment method';
    } elseif (empty($reference_no)) {
        $error = 'Please enter a reference/OR number';
    } elseif (empty($sender_name)) {
        $error = 'Please enter sender/payee name';
    } elseif (empty($date_paid)) {
        $error = 'Please select date paid';
    } else {
        // Handle image upload
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = uploadReceiptImage($_FILES['receipt_image']);
            
            if ($upload_result['success']) {
                $image_path = $upload_result['filepath'];
                
                // Check if reference number already exists
                $stmt = $pdo->prepare("SELECT id FROM balance_requests WHERE reference_no = ?");
                $stmt->execute([$reference_no]);
                if ($stmt->fetch()) {
                    $error = 'This reference number has already been used. Please check your receipt and enter a valid reference number.';
                } else {
                    // Insert request
                    $stmt = $pdo->prepare("INSERT INTO balance_requests 
                                           (passenger_id, amount, payment_method, reference_no, sender_name, date_paid, receipt_image) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$passenger_id, $amount, $payment_method, $reference_no, $sender_name, $date_paid, $image_path])) {
                        $success = 'Your balance request has been submitted! Please wait for management approval.';
                        $_POST = [];
                    } else {
                        $error = 'Failed to submit request';
                    }
                }
            } else {
                $error = 'Image upload failed: ' . $upload_result['message'];
            }
        } else {
            $error = 'Please upload a receipt image (JPG or PNG, max 2MB)';
        }
    }
}

// Get request history
$stmt = $pdo->prepare("SELECT * FROM balance_requests WHERE passenger_id = ? ORDER BY request_date DESC");
$stmt->execute([$passenger_id]);
$requests = $stmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-plus-circle mr-2 text-green-500"></i>Request Balance Load
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
    <!-- Request Form -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold">
                <i class="fas fa-hand-holding-usd mr-2 text-green-500"></i>Request Balance
            </h2>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <p class="text-gray-500 text-sm">Current Balance</p>
                <p class="text-3xl font-bold text-green-600">₱<?php echo number_format($passenger['balance'], 2); ?></p>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Amount (₱) *</label>
                        <input type="number" name="amount" step="1" min="100" required 
                               value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
                               placeholder="100.00"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <p class="text-sm text-gray-500 mt-1">Minimum: ₱100.00</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Payment Method *</label>
                        <select name="payment_method" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="">-- Select Payment Method --</option>
                            <option value="GCash" <?php echo ($_POST['payment_method'] ?? '') == 'GCash' ? 'selected' : ''; ?>>GCash</option>
                            <option value="Maya" <?php echo ($_POST['payment_method'] ?? '') == 'Maya' ? 'selected' : ''; ?>>Maya</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Reference/OR Number *</label>
                        <input type="text" name="reference_no" required 
                               value="<?php echo htmlspecialchars($_POST['reference_no'] ?? ''); ?>"
                               placeholder="e.g., 2026-08-15-001"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <p class="text-sm text-gray-500 mt-1">This reference number can only be used once</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Sender/Payee Name *</label>
                        <input type="text" name="sender_name" required 
                               value="<?php echo htmlspecialchars($_POST['sender_name'] ?? ''); ?>"
                               placeholder="e.g., Juan Dela Cruz"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Date Paid *</label>
                        <input type="date" name="date_paid" required 
                               value="<?php echo htmlspecialchars($_POST['date_paid'] ?? date('Y-m-d')); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Upload Receipt *</label>
                        <input type="file" name="receipt_image" accept=".jpg,.jpeg,.png" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        <p class="text-sm text-gray-500 mt-1">Accepted formats: JPG, PNG (Max 2MB)</p>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>How it works:</strong><br>
                            1. Fill up the form and upload your payment receipt<br>
                            2. Submit your request<br>
                            3. Management will verify your payment<br>
                            4. Your balance will be updated once approved
                        </p>
                    </div>
                    
                    <button type="submit" name="submit_request" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg transition text-lg font-bold">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Request History -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold">
                <i class="fas fa-history mr-2 text-gray-500"></i>Request History
            </h2>
        </div>
        <div class="p-4 max-h-[500px] overflow-y-auto">
            <?php if (count($requests) > 0): ?>
                <div class="space-y-3">
                    <?php foreach ($requests as $request): ?>
                    <div class="flex justify-between items-center border-b pb-2">
                        <div>
                            <p class="font-bold <?php 
                                echo $request['status'] == 'approved' ? 'text-green-600' : 
                                    ($request['status'] == 'pending' ? 'text-yellow-600' : 'text-red-600'); 
                            ?>">
                                ₱<?php echo number_format($request['amount'], 2); ?>
                                <span class="text-xs font-normal text-gray-500">
                                    (<?php echo $request['payment_method']; ?>)
                                </span>
                            </p>
                            <p class="text-xs text-gray-500">Ref: <?php echo htmlspecialchars($request['reference_no']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo date('M d, Y h:i A', strtotime($request['request_date'])); ?></p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 rounded text-xs <?php 
                                echo $request['status'] == 'approved' ? 'bg-green-100 text-green-800' : 
                                    ($request['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                            ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                            <?php if ($request['status'] == 'rejected' && !empty($request['rejection_reason'])): ?>
                                <p class="text-xs text-red-500 mt-1"><?php echo htmlspecialchars($request['rejection_reason']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-8">
                    <i class="fas fa-receipt text-4xl mb-2 block"></i>
                    No balance requests yet
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>