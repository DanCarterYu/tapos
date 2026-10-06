<?php
// collector/my_passengers.php
// View passengers registered by this collector

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

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$period = $_GET['period'] ?? 'all'; // all, today, week, month

// Build date condition
$date_condition = "";
switch ($period) {
    case 'today':
        $date_condition = "DATE(created_at) = CURDATE()";
        $period_label = "Today";
        break;
    case 'week':
        $date_condition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
        $period_label = "This Week";
        break;
    case 'month':
        $date_condition = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        $period_label = "This Month";
        break;
    default:
        $date_condition = "1=1";
        $period_label = "All Time";
}

// Build query - ONLY passengers registered by THIS collector
$query = "SELECT * FROM passengers 
          WHERE created_by = ? AND created_by_role = 'collector' AND " . $date_condition;
$params = [$employee_number];

if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR rfid_uid LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$passengers = $stmt->fetchAll();

// Get summary data
$total_registered = count($passengers);
$active_count = 0;
$inactive_count = 0;
foreach ($passengers as $p) {
    if ($p['status'] == 'active') $active_count++;
    else $inactive_count++;
}

// Get total counts (all time) for summary cards
$stmt_all = $pdo->prepare("SELECT COUNT(*) as total FROM passengers 
                           WHERE created_by = ? AND created_by_role = 'collector'");
$stmt_all->execute([$employee_number]);
$total_all_time = $stmt_all->fetch()['total'];

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-id-card mr-2 text-blue-500"></i>My Registered Passengers
    </h1>
    <div class="text-sm text-gray-500">
        Collector: <span class="font-semibold"><?php echo htmlspecialchars($collector_name); ?></span>
        <span class="mx-2">|</span>
        Employee: <span class="font-semibold"><?php echo htmlspecialchars($employee_number); ?></span>
    </div>
</div>

<!-- Period Filter Buttons -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="" class="flex flex-wrap items-center gap-4">
        <div>
            <span class="text-gray-700 font-bold mr-2">Period:</span>
            <a href="?period=all&<?php echo http_build_query(['search' => $search, 'status' => $status_filter]); ?>" 
               class="px-4 py-2 rounded-lg transition <?php echo $period == 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
                All Time
            </a>
            <a href="?period=today&<?php echo http_build_query(['search' => $search, 'status' => $status_filter]); ?>" 
               class="px-4 py-2 rounded-lg transition <?php echo $period == 'today' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
                Today
            </a>
            <a href="?period=week&<?php echo http_build_query(['search' => $search, 'status' => $status_filter]); ?>" 
               class="px-4 py-2 rounded-lg transition <?php echo $period == 'week' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
                This Week
            </a>
            <a href="?period=month&<?php echo http_build_query(['search' => $search, 'status' => $status_filter]); ?>" 
               class="px-4 py-2 rounded-lg transition <?php echo $period == 'month' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
                This Month
            </a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Registered</p>
                <p class="text-2xl font-bold"><?php echo $total_registered; ?></p>
                <p class="text-xs text-gray-400"><?php echo $period_label; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-user-check text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Active</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $active_count; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-red-100 rounded-full p-3 mr-4">
                <i class="fas fa-user-slash text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Inactive / Blocked</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $inactive_count; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-purple-100 rounded-full p-3 mr-4">
                <i class="fas fa-id-card text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">All Time Total</p>
                <p class="text-2xl font-bold"><?php echo $total_all_time; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="" class="flex flex-wrap gap-4">
        <input type="hidden" name="period" value="<?php echo $period; ?>">
        
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" placeholder="Search by name or RFID UID..." 
                   value="<?php echo htmlspecialchars($search); ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <option value="">All Status</option>
                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="lost" <?php echo $status_filter == 'lost' ? 'selected' : ''; ?>>Lost Card</option>
                <option value="blocked" <?php echo $status_filter == 'blocked' ? 'selected' : ''; ?>>Blocked</option>
            </select>
        </div>
        <div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <a href="my_passengers.php?period=<?php echo $period; ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition ml-2">
                <i class="fas fa-sync-alt mr-2"></i>Reset
            </a>
        </div>
    </form>
</div>

<!-- Passengers Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <?php if (count($passengers) > 0): ?>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">RFID UID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Full Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registered Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loyalty Points</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($passengers as $passenger): 
                    $full_name = trim($passenger['first_name'] . ' ' . 
                                 ($passenger['middle_name'] ? $passenger['middle_name'] . ' ' : '') . 
                                 $passenger['last_name']);
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($passenger['rfid_uid']); ?></td>
                    <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($full_name); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('M d, Y h:i A', strtotime($passenger['created_at'])); ?></td>
                    <td class="px-6 py-4 text-sm">
                        <span class="px-2 py-1 rounded text-xs <?php 
                            echo $passenger['status'] == 'active' ? 'bg-green-100 text-green-800' : 
                                ($passenger['status'] == 'lost' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                        ?>">
                            <?php echo ucfirst($passenger['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">₱<?php echo number_format($passenger['balance'], 2); ?></td>
                    <td class="px-6 py-4 text-sm text-yellow-600"><?php echo number_format($passenger['loyalty_points']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-gray-50 border-t">
                <tr>
                    <td colspan="2" class="px-6 py-3 text-right font-bold">Total:</td>
                    <td class="px-6 py-3 font-bold"><?php echo $total_registered; ?> passengers</td>
                    <td colspan="3" class="px-6 py-3"></td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
        <div class="text-center text-gray-500 py-12">
            <i class="fas fa-id-card text-4xl mb-2 block"></i>
            <p>No passengers registered for this period</p>
            <p class="text-sm mt-2">Use "Register RFID Card" to add passengers</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>