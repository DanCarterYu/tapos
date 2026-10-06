<?php
// management/balance_requests_get_pending.php
// AJAX endpoint for loading pending requests

// Use the same session name as management
session_name('MANAGEMENT_SESSION');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    echo '<div class="text-center text-red-500 py-8">Unauthorized access</div>';
    exit();
}

// Now include database
require_once '../config/database.php';

// Get pending requests
$stmt = $pdo->query("SELECT br.*, 
                     CONCAT(p.first_name, ' ', 
                            IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                            p.last_name) as passenger_name 
                     FROM balance_requests br
                     JOIN passengers p ON br.passenger_id = p.id
                     WHERE br.status = 'pending' 
                     ORDER BY br.request_date ASC");
$pendingRequests = $stmt->fetchAll();

if (count($pendingRequests) == 0): ?>
    <div class="text-center text-gray-500 py-8">
        <i class="fas fa-check-circle text-4xl text-green-500 mb-2 block"></i>
        No pending requests
    </div>
<?php else: ?>
    <div class="space-y-3">
        <?php foreach ($pendingRequests as $request): ?>
        <div class="pending-item border rounded-lg p-4 hover:bg-gray-50 transition">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center space-x-4">
                        <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">#<?php echo $request['id']; ?></span>
                        <span class="font-medium"><?php echo htmlspecialchars($request['passenger_name']); ?></span>
                        <span class="font-bold text-green-600">₱<?php echo number_format($request['amount'], 2); ?></span>
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        <?php echo htmlspecialchars($request['payment_method']); ?> | 
                        Ref: <?php echo htmlspecialchars($request['reference_no']); ?>
                        <span class="ml-4">
                            <?php echo date('M d, Y', strtotime($request['request_date'])); ?>
                        </span>
                    </div>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <button onclick="viewRequest(<?php echo $request['id']; ?>)" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button onclick="approveRequest(<?php echo $request['id']; ?>)" 
                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button onclick="openRejectModal(<?php echo $request['id']; ?>)" 
                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>