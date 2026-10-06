<?php
// management/balance_requests_view.php
// AJAX endpoint for viewing request details

// Use the same session name as management
session_name('MANAGEMENT_SESSION');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    echo '<div class="text-center text-red-500 py-8">Unauthorized access</div>';
    exit();
}

// Now include database
require_once '../config/database.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo '<div class="text-center text-red-500 py-8">Invalid request ID</div>';
    exit();
}

// Get request details
$stmt = $pdo->prepare("SELECT br.*, 
                       CONCAT(p.first_name, ' ', 
                              IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                              p.last_name) as passenger_name 
                       FROM balance_requests br
                       JOIN passengers p ON br.passenger_id = p.id
                       WHERE br.id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch();

if (!$request) {
    echo '<div class="text-center text-red-500 py-8">Request not found</div>';
    exit();
}
?>

<!-- Rest of the HTML remains the same -->
<div class="space-y-4">
    <!-- Request Details -->
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-gray-500 text-sm">Request ID</p>
            <p class="font-mono font-bold">#<?php echo $request['id']; ?></p>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Status</p>
            <span class="px-2 py-1 rounded text-xs <?php 
                echo $request['status'] == 'approved' ? 'bg-green-100 text-green-800' : 
                    ($request['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
            ?>">
                <?php echo ucfirst($request['status']); ?>
            </span>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Passenger</p>
            <p class="font-semibold"><?php echo htmlspecialchars($request['passenger_name']); ?></p>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Amount</p>
            <p class="font-bold text-green-600">₱<?php echo number_format($request['amount'], 2); ?></p>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Payment Method</p>
            <p><?php echo htmlspecialchars($request['payment_method']); ?></p>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Reference/OR No.</p>
            <p class="font-mono"><?php echo htmlspecialchars($request['reference_no']); ?></p>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Sender/Payee</p>
            <p><?php echo htmlspecialchars($request['sender_name']); ?></p>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Date Paid</p>
            <p><?php echo date('F d, Y', strtotime($request['date_paid'])); ?></p>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Request Date</p>
            <p><?php echo date('F d, Y h:i A', strtotime($request['request_date'])); ?></p>
        </div>
        <?php if ($request['status'] == 'rejected' && !empty($request['rejection_reason'])): ?>
        <div class="col-span-2">
            <p class="text-gray-500 text-sm">Rejection Reason</p>
            <p class="text-red-600"><?php echo htmlspecialchars($request['rejection_reason']); ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Receipt Image -->
    <div class="border-t pt-4">
        <p class="text-gray-500 text-sm mb-2">Receipt Image</p>
        <div class="bg-gray-100 rounded-lg p-4">
            <img src="../<?php echo $request['receipt_image']; ?>" 
                 alt="Receipt Image" 
                 class="max-w-full max-h-96 mx-auto rounded-lg shadow">
            <p class="text-center text-sm text-gray-500 mt-2">
                <?php echo basename($request['receipt_image']); ?>
            </p>
        </div>
    </div>
    
    <!-- Action Buttons (if pending) -->
    <?php if ($request['status'] == 'pending'): ?>
    <div class="border-t pt-4 flex gap-2">
        <button onclick="approveRequest(<?php echo $request['id']; ?>)" 
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition flex-1">
            <i class="fas fa-check mr-2"></i>Approve
        </button>
        <button onclick="openRejectModal(<?php echo $request['id']; ?>)" 
                class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition flex-1">
            <i class="fas fa-times mr-2"></i>Reject
        </button>
        <button onclick="closeViewModal()" 
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition">
            Close
        </button>
    </div>
    <?php else: ?>
    <div class="border-t pt-4">
        <button onclick="closeViewModal()" 
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition">
            Close
        </button>
    </div>
    <?php endif; ?>
</div>