<?php
// management/balance_requests.php
// Manage passenger balance requests

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query - ONLY APPROVED AND REJECTED (Approval History)
$query = "SELECT br.*, 
          CONCAT(p.first_name, ' ', 
                 IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                 p.last_name) as passenger_name 
          FROM balance_requests br
          JOIN passengers p ON br.passenger_id = p.id
          WHERE br.status IN ('approved', 'rejected')";
$params = [];

if (!empty($search)) {
    $query .= " AND (CONCAT(p.first_name, ' ', IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), p.last_name) LIKE ? OR br.reference_no LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $query .= " AND br.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY br.approved_date DESC, br.request_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get counts for summary - ALL requests
$stmt = $pdo->query("SELECT COUNT(*) as total FROM balance_requests");
$totalRequests = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM balance_requests WHERE status = 'pending'");
$pendingRequests = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM balance_requests WHERE status = 'approved'");
$approvedRequests = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM balance_requests WHERE status = 'rejected'");
$rejectedRequests = $stmt->fetch()['total'];

// Get pending requests for popup modal
$stmt = $pdo->query("SELECT br.*, 
                     CONCAT(p.first_name, ' ', 
                            IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                            p.last_name) as passenger_name 
                     FROM balance_requests br
                     JOIN passengers p ON br.passenger_id = p.id
                     WHERE br.status = 'pending' 
                     ORDER BY br.request_date ASC");
$pendingRequestsList = $stmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-hand-holding-usd mr-2"></i>Balance Requests
    </h1>
    <button onclick="openPendingModal()" 
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition <?php echo $pendingRequests > 0 ? 'animate-pulse' : ''; ?>">
        <i class="fas fa-clock mr-2"></i>Balance Approval
        <?php if ($pendingRequests > 0): ?>
            <span class="bg-red-500 text-white text-xs rounded-full px-2 py-1 ml-1"><?php echo $pendingRequests; ?></span>
        <?php endif; ?>
    </button>
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

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-list text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Requests</p>
                <p class="text-2xl font-bold"><?php echo $totalRequests; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Pending</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $pendingRequests; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Approved</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $approvedRequests; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-red-100 rounded-full p-3 mr-4">
                <i class="fas fa-times-circle text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Rejected</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $rejectedRequests; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" placeholder="Search by passenger or reference..." 
                   value="<?php echo htmlspecialchars($search); ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <option value="">All Status</option>
                <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>
        <div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <a href="balance_requests.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition ml-2">
                <i class="fas fa-sync-alt mr-2"></i>Reset
            </a>
        </div>
    </form>
</div>

<!-- Approval History Table -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-history mr-2 text-gray-600"></i>Approval History
        </h2>
        <p class="text-sm text-gray-500">Approved and rejected requests</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Request ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passenger</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Method</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved/Rejected Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($requests) > 0): ?>
                    <?php foreach ($requests as $request): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-mono">#<?php echo $request['id']; ?></td>
                        <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($request['passenger_name']); ?></td>
                        <td class="px-6 py-4 text-sm font-bold text-green-600">₱<?php echo number_format($request['amount'], 2); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($request['payment_method']); ?></td>
                        <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($request['reference_no']); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs <?php 
                                echo $request['status'] == 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                            ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                            <?php if ($request['status'] == 'rejected' && !empty($request['rejection_reason'])): ?>
                                <span class="text-xs text-red-500 block mt-1" title="<?php echo htmlspecialchars($request['rejection_reason']); ?>">
                                    <i class="fas fa-info-circle"></i> <?php echo substr(htmlspecialchars($request['rejection_reason']), 0, 30); ?>...
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php 
                                $date = $request['approved_date'] ?? $request['request_date'];
                                echo date('M d, Y h:i A', strtotime($date)); 
                            ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <button onclick="viewRequest(<?php echo $request['id']; ?>)" 
                                    class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-history text-4xl mb-2 block"></i>
                            No approval history found
                            <p class="text-sm mt-2">Approved and rejected requests will appear here</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================= -->
<!-- PENDING REQUESTS POPUP MODAL -->
<!-- ============================================= -->
<div id="pendingModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold">
                <i class="fas fa-clock mr-2 text-yellow-500"></i>Pending Requests
                <span id="pendingCount" class="text-sm font-normal text-gray-500"></span>
            </h2>
            <button onclick="closePendingModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]" id="pendingRequestsList">
            <!-- Dynamic content loaded via JavaScript -->
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-spinner fa-spin text-3xl"></i>
                <p class="mt-2">Loading...</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- VIEW REQUEST DETAILS MODAL -->
<!-- ============================================= -->
<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold">
                <i class="fas fa-file-invoice mr-2 text-blue-500"></i>Request Details
            </h2>
            <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]" id="viewRequestDetails">
            <!-- Dynamic content loaded via JavaScript -->
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-spinner fa-spin text-3xl"></i>
                <p class="mt-2">Loading...</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- REJECT MODAL -->
<!-- ============================================= -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold text-red-600">
                <i class="fas fa-times-circle mr-2"></i>Reject Request
            </h2>
            <button onclick="closeRejectModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <form id="rejectForm">
                <input type="hidden" name="request_id" id="rejectRequestId">
                <input type="hidden" name="action" value="reject">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Rejection Reason *</label>
                    <textarea name="rejection_reason" id="rejectionReason" required rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-red-500"
                              placeholder="e.g., Receipt is blurry, please resubmit"></textarea>
                </div>
                
                <div class="flex gap-2">
                    <button type="button" onclick="submitReject()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition flex-1">
                        <i class="fas fa-times mr-2"></i>Reject
                    </button>
                    <button type="button" onclick="closeRejectModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition flex-1">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // =============================================
    // PENDING REQUESTS POPUP
    // =============================================
    function openPendingModal() {
        document.getElementById('pendingModal').classList.remove('hidden');
        loadPendingRequests();
    }
    
    function closePendingModal() {
        document.getElementById('pendingModal').classList.add('hidden');
    }
    
    function loadPendingRequests() {
        fetch('balance_requests_get_pending.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('pendingRequestsList').innerHTML = html;
                // Update pending count
                const count = document.querySelectorAll('.pending-item').length;
                document.getElementById('pendingCount').textContent = `(${count} pending)`;
            })
            .catch(error => {
                document.getElementById('pendingRequestsList').innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <i class="fas fa-exclamation-triangle text-3xl"></i>
                        <p class="mt-2">Failed to load pending requests</p>
                    </div>
                `;
            });
    }
    
    // =============================================
    // VIEW REQUEST DETAILS
    // =============================================
    function viewRequest(id) {
        document.getElementById('viewModal').classList.remove('hidden');
        loadRequestDetails(id);
    }
    
    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }
    
    function loadRequestDetails(id) {
        fetch(`balance_requests_view.php?id=${id}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('viewRequestDetails').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('viewRequestDetails').innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <i class="fas fa-exclamation-triangle text-3xl"></i>
                        <p class="mt-2">Failed to load request details</p>
                    </div>
                `;
            });
    }
    
    // =============================================
    // REJECT MODAL
    // =============================================
    function openRejectModal(id) {
        document.getElementById('rejectRequestId').value = id;
        document.getElementById('rejectionReason').value = '';
        document.getElementById('rejectModal').classList.remove('hidden');
    }
    
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
    
    // =============================================
    // REJECT SUBMIT (AJAX)
    // =============================================
    function submitReject() {
        const requestId = document.getElementById('rejectRequestId').value;
        const rejectionReason = document.getElementById('rejectionReason').value;
        
        if (!rejectionReason.trim()) {
            alert('Please provide a rejection reason');
            return;
        }
        
        if (!confirm('Are you sure you want to reject this request?')) {
            return;
        }
        
        // Show loading
        const rejectBtn = document.querySelector('#rejectForm button[type="button"]');
        const originalText = rejectBtn.textContent;
        rejectBtn.textContent = 'Processing...';
        rejectBtn.disabled = true;
        
        const formData = new FormData();
        formData.append('action', 'reject');
        formData.append('request_id', requestId);
        formData.append('rejection_reason', rejectionReason);
        
        fetch('balance_requests_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            if (data.success) {
                alert(data.message);
                closeRejectModal();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to process request: ' + error.message);
        })
        .finally(() => {
            rejectBtn.textContent = originalText;
            rejectBtn.disabled = false;
        });
    }
    
    // =============================================
    // APPROVE ACTION (AJAX)
    // =============================================
    function approveRequest(id) {
        if (!confirm('Are you sure you want to approve this request?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'approve');
        formData.append('request_id', id);
        
        fetch('balance_requests_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Failed to process request');
        });
    }
    
    // =============================================
    // CLOSE MODALS ON ESC KEY
    // =============================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePendingModal();
            closeViewModal();
            closeRejectModal();
        }
    });
    
    // =============================================
    // CLOSE MODALS ON OVERLAY CLICK
    // =============================================
    document.querySelectorAll('.fixed.inset-0.bg-black').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                if (this.id === 'pendingModal') closePendingModal();
                if (this.id === 'viewModal') closeViewModal();
                if (this.id === 'rejectModal') closeRejectModal();
            }
        });
    });
</script>

<?php include_once '../includes/footer.php'; ?>