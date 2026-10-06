<?php
// passenger/balance_history.php
// Passenger Balance History (Loads + Fare Payments + Refunds)

require_once 'session_config.php';
require_once '../config/database.php';

// Check if logged in as passenger
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header('Location: login.php');
    exit();
}

$passenger_id = $_SESSION['user_id'];
$passenger_name = $_SESSION['full_name'];

// Get passenger current balance
$stmt = $pdo->prepare("SELECT balance FROM passengers WHERE id = ?");
$stmt->execute([$passenger_id]);
$current_balance = $stmt->fetch()['balance'] ?? 0;

// Get period filter
$period = $_GET['period'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build date condition
$date_condition = "";
switch ($period) {
    case 'today':
        $date_condition = "DATE(date) = CURDATE()";
        $period_label = "Today";
        break;
    case 'week':
        $date_condition = "YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
        $period_label = "This Week";
        break;
    case 'month':
        $date_condition = "MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
        $period_label = "This Month";
        break;
    default:
        $date_condition = "1=1";
        $period_label = "All Time";
}

// =============================================
// GET COMBINED HISTORY (Loads + Fares + Refunds)
// =============================================
$sql = "
    SELECT 
        date,
        type,
        amount,
        direction,
        reference,
        route_name,
        item_id
    FROM (
        -- Loads
        SELECT 
            load_date as date,
            'Load Balance' as type,
            amount,
            'increase' as direction,
            reference_no as reference,
            '' as route_name,
            id as item_id
        FROM balance_loads 
        WHERE passenger_id = ?
        
        UNION ALL
        
        -- Fares
        SELECT 
            transaction_date as date,
            'Fare Payment' as type,
            net_payment as amount,
            'decrease' as direction,
            receipt_no as reference,
            CONCAT(d.origin, ' → ', d.destination) as route_name,
            t.id as item_id
        FROM transactions t
        JOIN destinations d ON t.destination_id = d.id
        WHERE passenger_id = ? AND t.is_refunded = 0
        
        UNION ALL
        
        -- Refunds
        SELECT 
            r.refunded_at as date,
            'Refund' as type,
            r.amount,
            'increase' as direction,
            CONCAT('REF-', LPAD(r.id, 6, '0')) as reference,
            CONCAT('Cancelled: ', d.origin, ' → ', d.destination) as route_name,
            r.id as item_id
        FROM refunds r
        JOIN transactions t ON r.transaction_id = t.id
        JOIN destinations d ON t.destination_id = d.id
        WHERE r.passenger_id = ?
    ) AS history
    WHERE " . $date_condition;

$params = [$passenger_id, $passenger_id, $passenger_id];

// Add search filter
if (!empty($search)) {
    $sql .= " AND (type LIKE ? OR route_name LIKE ? OR reference LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$history = $stmt->fetchAll();

// Get summary
$total_fares = 0;
$total_fare_amount = 0;
$total_trips = 0;

foreach ($history as $item) {
    if ($item['direction'] == 'decrease') {
        $total_fares++;
        $total_fare_amount += $item['amount'];
        $total_trips++;
    }
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-history mr-2 text-blue-500"></i>Balance History
    </h1>
</div>

<!-- Current Balance -->
<div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow p-6 mb-6 text-white">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-blue-100 text-sm">Current Balance</p>
            <p class="text-3xl font-bold">₱<?php echo number_format($current_balance, 2); ?></p>
        </div>
        <div class="text-right">
            <p class="text-blue-100 text-sm">Total Trips</p>
            <p class="text-xl font-bold"><?php echo $total_trips; ?></p>
        </div>
    </div>
</div>

<!-- Period Filter Buttons -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <div class="flex flex-wrap items-center gap-4">
        <span class="text-gray-700 font-bold mr-2">Period:</span>
        <a href="?period=all&<?php echo http_build_query(['search' => $search]); ?>" 
           class="px-4 py-2 rounded-lg transition <?php echo $period == 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
            All Time
        </a>
        <a href="?period=today&<?php echo http_build_query(['search' => $search]); ?>" 
           class="px-4 py-2 rounded-lg transition <?php echo $period == 'today' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
            Today
        </a>
        <a href="?period=week&<?php echo http_build_query(['search' => $search]); ?>" 
           class="px-4 py-2 rounded-lg transition <?php echo $period == 'week' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
            This Week
        </a>
        <a href="?period=month&<?php echo http_build_query(['search' => $search]); ?>" 
           class="px-4 py-2 rounded-lg transition <?php echo $period == 'month' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
            This Month
        </a>
    </div>
</div>

<!-- Search -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="" class="flex flex-wrap gap-4">
        <input type="hidden" name="period" value="<?php echo $period; ?>">
        
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" placeholder="Search by type, route, or reference..." 
                   value="<?php echo htmlspecialchars($search); ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <a href="balance_history.php?period=<?php echo $period; ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition ml-2">
                <i class="fas fa-sync-alt mr-2"></i>Reset
            </a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-red-100 rounded-full p-3 mr-4">
                <i class="fas fa-receipt text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Fare Payments</p>
                <p class="text-2xl font-bold text-red-600">₱<?php echo number_format($total_fare_amount, 2); ?></p>
                <p class="text-xs text-gray-400"><?php echo $total_fares; ?> transactions</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-ship text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Trips</p>
                <p class="text-2xl font-bold"><?php echo $total_trips; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- History Table -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-bold">
            <i class="fas fa-list mr-2 text-gray-600"></i>Transaction History
            <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo $period_label; ?>)</span>
        </h2>
    </div>
    <div class="overflow-x-auto">
        <?php if (count($history) > 0): ?>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($history as $item): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <?php echo date('M d, Y h:i A', strtotime($item['date'])); ?>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <?php if ($item['type'] == 'Load Balance'): ?>
                            <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800">
                                <i class="fas fa-arrow-up mr-1"></i> Load
                            </span>
                        <?php elseif ($item['type'] == 'Refund'): ?>
                            <span class="px-2 py-1 rounded text-xs bg-orange-100 text-orange-800">
                                <i class="fas fa-undo mr-1"></i> Refund
                            </span>
                        <?php else: ?>
                            <span class="px-2 py-1 rounded text-xs bg-red-100 text-red-800">
                                <i class="fas fa-arrow-down mr-1"></i> Fare
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <?php if ($item['type'] == 'Load Balance'): ?>
                            Balance Load
                        <?php else: ?>
                            <?php echo htmlspecialchars($item['route_name'] ?? 'Fare Payment'); ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm font-bold <?php echo $item['direction'] == 'increase' ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo $item['direction'] == 'increase' ? '+' : '-'; ?>₱<?php echo number_format($item['amount'], 2); ?>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <button onclick="viewTransaction('<?php 
                            echo $item['type'] == 'Load Balance' ? 'load' : 
                                ($item['type'] == 'Refund' ? 'refund' : 'fare'); 
                        ?>', <?php echo $item['item_id']; ?>)" 
                                class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                            <i class="fas fa-eye"></i> VIEW
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center text-gray-500 py-12">
            <i class="fas fa-history text-4xl mb-2 block"></i>
            <p>No balance history found</p>
            <p class="text-sm mt-2">Load balance or ride to see transactions here</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- TRANSACTION DETAILS MODAL -->
<!-- ============================================= -->
<div id="transactionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold" id="modalTitle">
                <i class="fas fa-file-invoice mr-2 text-blue-500"></i>Transaction Details
            </h2>
            <button onclick="closeTransactionModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]" id="transactionDetails">
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-spinner fa-spin text-3xl"></i>
                <p class="mt-2">Loading...</p>
            </div>
        </div>
    </div>
</div>

<script>
function viewTransaction(type, id) {
    document.getElementById('transactionModal').classList.remove('hidden');
    document.getElementById('transactionDetails').innerHTML = `
        <div class="text-center text-gray-500 py-8">
            <i class="fas fa-spinner fa-spin text-3xl"></i>
            <p class="mt-2">Loading...</p>
        </div>
    `;
    
    fetch('get_transaction_details.php?type=' + type + '&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                document.getElementById('transactionDetails').innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <i class="fas fa-exclamation-triangle text-3xl"></i>
                        <p class="mt-2">${data.message || 'Failed to load details'}</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            
            // =============================================
            // LOAD DETAILS
            // =============================================
            if (data.type === 'load') {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-arrow-up mr-2 text-green-500"></i>Load Details';
                html = `
                    <div class="space-y-3">
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Reference No:</span>
                            <span class="font-mono font-bold">${data.data.reference_no}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Amount:</span>
                            <span class="font-bold text-green-600">+₱${parseFloat(data.data.amount).toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Loaded By:</span>
                            <span class="font-semibold">${data.data.loaded_by_display}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Date & Time:</span>
                            <span>${new Date(data.data.load_date).toLocaleString()}</span>
                        </div>
                    </div>
                `;
            }
            
            // =============================================
            // FARE DETAILS
            // =============================================
            else if (data.type === 'fare') {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-arrow-down mr-2 text-red-500"></i>Fare Payment Details';
                html = `
                    <div class="space-y-3">
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Receipt No:</span>
                            <span class="font-mono font-bold">${data.data.receipt_no}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Route:</span>
                            <span class="font-semibold">${data.data.route_name}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Base Fare:</span>
                            <span>₱${parseFloat(data.data.base_fare).toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Discount (${data.data.discount_percentage}%):</span>
                            <span class="text-green-600">-₱${parseFloat(data.data.discount_amount).toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Net Payment:</span>
                            <span class="font-bold text-red-600">-₱${parseFloat(data.data.net_payment).toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Points Earned:</span>
                            <span class="font-bold text-yellow-600">+${data.data.loyalty_points_earned} pts</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Balance Before/After:</span>
                            <span>₱${parseFloat(data.data.balance_before).toFixed(2)} → ₱${parseFloat(data.data.balance_after).toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Collector:</span>
                            <span class="font-mono">${data.data.employee_number}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Date & Time:</span>
                            <span>${new Date(data.data.transaction_date).toLocaleString()}</span>
                        </div>
                    </div>
                `;
            }
            
            // =============================================
            // REFUND DETAILS
            // =============================================
            else if (data.type === 'refund') {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-undo mr-2 text-orange-500"></i>Refund Details';
                html = `
                    <div class="space-y-3">
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Refund Reference:</span>
                            <span class="font-mono font-bold">REF-${String(data.data.id).padStart(6, '0')}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Original Receipt:</span>
                            <span class="font-mono">${data.data.receipt_no}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Trip:</span>
                            <span class="font-semibold">${data.data.route_name}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Amount Refunded:</span>
                            <span class="font-bold text-green-600">+₱${parseFloat(data.data.amount).toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Points Deducted:</span>
                            <span class="font-bold text-red-600">-${data.data.points_deducted} points</span>
                        </div>
                        <div class="flex justify-between py-2 border-b">
                            <span class="text-gray-600">Reason:</span>
                            <span>${data.data.reason}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-600">Refunded At:</span>
                            <span>${new Date(data.data.refunded_at).toLocaleString()}</span>
                        </div>
                    </div>
                `;
            }
            
            html += `
                <div class="mt-4 pt-4 border-t text-center">
                    <button onclick="closeTransactionModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg">
                        Close
                    </button>
                </div>
            `;
            
            document.getElementById('transactionDetails').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('transactionDetails').innerHTML = `
                <div class="text-center text-red-500 py-8">
                    <i class="fas fa-exclamation-triangle text-3xl"></i>
                    <p class="mt-2">Error loading details</p>
                </div>
            `;
        });
}

function closeTransactionModal() {
    document.getElementById('transactionModal').classList.add('hidden');
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTransactionModal();
    }
});

// Close modal on overlay click
document.getElementById('transactionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTransactionModal();
    }
});
</script>

<?php include_once '../includes/footer.php'; ?> 