<?php
// management/passenger_view.php
// AJAX endpoint for viewing passenger details

// Use the same session name as management
session_name('MANAGEMENT_SESSION');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    echo '<div class="text-center text-red-500 py-8">Unauthorized access</div>';
    exit();
}

require_once '../config/database.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo '<div class="text-center text-red-500 py-8">Invalid passenger ID</div>';
    exit();
}

// Helper function to calculate age
function calculateAge($date_of_birth) {
    if (empty($date_of_birth)) {
        return '-';
    }
    $birthDate = new DateTime($date_of_birth);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}

// Get passenger details
$stmt = $pdo->prepare("SELECT * FROM passengers WHERE id = ?");
$stmt->execute([$id]);
$passenger = $stmt->fetch();

if (!$passenger) {
    echo '<div class="text-center text-red-500 py-8">Passenger not found</div>';
    exit();
}

// Get recent transactions (last 5)
$stmt = $pdo->prepare("SELECT t.*, 
                       CONCAT(d.origin, ' → ', d.destination) as route_name
                       FROM transactions t
                       JOIN destinations d ON t.destination_id = d.id
                       WHERE t.passenger_id = ?
                       ORDER BY t.transaction_date DESC 
                       LIMIT 5");
$stmt->execute([$id]);
$recentTransactions = $stmt->fetchAll();

// Build full name
$full_name = trim($passenger['first_name'] . ' ' . 
             ($passenger['middle_name'] ? $passenger['middle_name'] . ' ' : '') . 
             $passenger['last_name']);

// Get created by display
if ($passenger['created_by_role'] == 'management') {
    $created_by = 'Management';
} else {
    $created_by = 'Collector: ' . htmlspecialchars($passenger['created_by']);
}
?>

<div class="space-y-6">
    <!-- Personal Information -->
    <div>
        <h3 class="font-bold text-gray-700 border-b pb-2 mb-3">
            <i class="fas fa-id-card mr-2 text-blue-500"></i>Personal Information
        </h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">RFID UID</p>
                <p class="font-mono font-semibold"><?php echo htmlspecialchars($passenger['rfid_uid']); ?></p>
            </div>
            <div>
                <p class="text-gray-500">Full Name</p>
                <p class="font-semibold"><?php echo htmlspecialchars($full_name); ?></p>
            </div>
            <div>
                <p class="text-gray-500">Date of Birth</p>
                <p class="font-semibold">
                    <?php if (!empty($passenger['date_of_birth'])): ?>
                        <?php echo date('F d, Y', strtotime($passenger['date_of_birth'])); ?>
                        <span class="text-gray-400 text-sm">(Age: <?php echo calculateAge($passenger['date_of_birth']); ?>)</span>
                    <?php else: ?>
                        <span class="text-gray-400">Not provided</span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <p class="text-gray-500">Sex</p>
                <p class="font-semibold">
                    <?php if (!empty($passenger['sex'])): ?>
                        <?php echo $passenger['sex'] == 'M' ? 'Male' : 'Female'; ?>
                    <?php else: ?>
                        <span class="text-gray-400">Not provided</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-span-2">
                <p class="text-gray-500">Address</p>
                <p class="font-semibold">
                    <?php if (!empty($passenger['address'])): ?>
                        <?php echo nl2br(htmlspecialchars($passenger['address'])); ?>
                    <?php else: ?>
                        <span class="text-gray-400">Not provided</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Contact Information -->
    <div>
        <h3 class="font-bold text-gray-700 border-b pb-2 mb-3">
            <i class="fas fa-phone mr-2 text-green-500"></i>Contact Information
        </h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Email</p>
                <p class="font-semibold">
                    <?php if (!empty($passenger['email'])): ?>
                        <?php echo htmlspecialchars($passenger['email']); ?>
                    <?php else: ?>
                        <span class="text-gray-400">Not provided</span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <p class="text-gray-500">Contact Number</p>
                <p class="font-semibold">
                    <?php if (!empty($passenger['contact'])): ?>
                        <?php echo htmlspecialchars($passenger['contact']); ?>
                    <?php else: ?>
                        <span class="text-gray-400">Not provided</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Account Information -->
    <div>
        <h3 class="font-bold text-gray-700 border-b pb-2 mb-3">
            <i class="fas fa-wallet mr-2 text-yellow-500"></i>Account Information
        </h3>
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Balance</p>
                <p class="font-bold text-green-600 text-lg">₱<?php echo number_format($passenger['balance'], 2); ?></p>
            </div>
            <div>
                <p class="text-gray-500">Loyalty Points</p>
                <p class="font-bold text-yellow-600 text-lg"><?php echo number_format($passenger['loyalty_points']); ?></p>
            </div>
            <div>
                <p class="text-gray-500">Status</p>
                <p>
                    <span class="px-2 py-1 rounded text-xs <?php 
                        echo $passenger['status'] == 'active' ? 'bg-green-100 text-green-800' : 
                            ($passenger['status'] == 'lost' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                    ?>">
                        <?php echo ucfirst($passenger['status']); ?>
                    </span>
                </p>
            </div>
            <div>
                <p class="text-gray-500">Created By</p>
                <p class="font-semibold"><?php echo $created_by; ?></p>
            </div>
            <div>
                <p class="text-gray-500">Created On</p>
                <p class="font-semibold"><?php echo date('F d, Y h:i A', strtotime($passenger['created_at'])); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div>
        <h3 class="font-bold text-gray-700 border-b pb-2 mb-3">
            <i class="fas fa-receipt mr-2 text-gray-500"></i>Recent Transactions
        </h3>
        <?php if (count($recentTransactions) > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($recentTransactions as $transaction): ?>
                    <tr>
                        <td class="px-4 py-2"><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($transaction['route_name']); ?></td>
                        <td class="px-4 py-2 font-bold text-green-600">₱<?php echo number_format($transaction['net_payment'], 2); ?></td>
                        <td class="px-4 py-2 text-yellow-600">+<?php echo $transaction['loyalty_points_earned']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-gray-500 text-sm">No transactions yet</p>
        <?php endif; ?>
    </div>
    
    <!-- Action Buttons -->
    <div class="border-t pt-4 flex flex-wrap gap-2">
        <a href="passengers_edit.php?id=<?php echo $passenger['id']; ?>" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm">
            <i class="fas fa-edit mr-2"></i>Edit
        </a>
        <a href="passengers_load_balance.php?id=<?php echo $passenger['id']; ?>" 
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition text-sm">
            <i class="fas fa-plus-circle mr-2"></i>Load Balance
        </a>
        <a href="passengers_replace_rfid.php?id=<?php echo $passenger['id']; ?>" 
           class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition text-sm">
            <i class="fas fa-id-card mr-2"></i>Replace RFID
        </a>
        <button onclick="closeViewModal()" 
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition text-sm">
            Close
        </button>
    </div>
</div>