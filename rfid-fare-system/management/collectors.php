<?php
// management/collectors.php
// Collector Accounts Management

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT * FROM collectors WHERE id = ?");
    $stmt->execute([$id]);
    $collector = $stmt->fetch();
    
    if ($collector) {
        $stmt = $pdo->prepare("DELETE FROM collectors WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Collector deleted successfully!";
    } else {
        $error = "Collector not found!";
    }
}

// Handle status toggle (activate/deactivate)
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    
    $stmt = $pdo->prepare("SELECT status FROM collectors WHERE id = ?");
    $stmt->execute([$id]);
    $collector = $stmt->fetch();
    
    if ($collector) {
        $new_status = ($collector['status'] == 'active') ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE collectors SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        $status_text = $new_status == 'active' ? 'activated' : 'deactivated';
        $success = "Collector " . $status_text . " successfully!";
    } else {
        $error = "Collector not found!";
    }
}

// Handle password reset to default (employee number)
if (isset($_GET['reset_password']) && is_numeric($_GET['reset_password'])) {
    $id = $_GET['reset_password'];
    
    $stmt = $pdo->prepare("SELECT employee_number FROM collectors WHERE id = ?");
    $stmt->execute([$id]);
    $collector = $stmt->fetch();
    
    if ($collector) {
        $default_password = $collector['employee_number'];
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE collectors SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $id]);
        $success = "Password reset to default (Employee Number) for " . htmlspecialchars($collector['employee_number']);
    } else {
        $error = "Collector not found!";
    }
}

// Get search parameter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$query = "SELECT c.*, a.full_name as created_by_name 
          FROM collectors c 
          LEFT JOIN admin a ON c.created_by = a.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (c.employee_number LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR c.contact LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($status_filter)) {
    $query .= " AND c.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$collectors = $stmt->fetchAll();

// Get counts for summary
$stmt = $pdo->query("SELECT COUNT(*) as total FROM collectors");
$totalCollectors = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM collectors WHERE status = 'active'");
$activeCollectors = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM collectors WHERE status = 'inactive'");
$inactiveCollectors = $stmt->fetch()['total'];

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-user-tie mr-2"></i>Collector Accounts
    </h1>
    <a href="collectors_create.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
        <i class="fas fa-plus mr-2"></i>Add New Collector
    </a>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-user-tie text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Collectors</p>
                <p class="text-2xl font-bold"><?php echo $totalCollectors; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-user-check text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Active Collectors</p>
                <p class="text-2xl font-bold"><?php echo $activeCollectors; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-red-100 rounded-full p-3 mr-4">
                <i class="fas fa-user-slash text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Inactive Collectors</p>
                <p class="text-2xl font-bold"><?php echo $inactiveCollectors; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" placeholder="Search by Employee #, Name, or Contact..." 
                   value="<?php echo htmlspecialchars($search); ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <option value="">All Status</option>
                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <a href="collectors.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition ml-2">
                <i class="fas fa-sync-alt mr-2"></i>Reset
            </a>
        </div>
    </form>
</div>

<!-- Success/Error Messages -->
<?php if (isset($success)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Collectors Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Full Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created At</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($collectors) > 0): ?>
                    <?php foreach ($collectors as $collector): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-mono font-bold"><?php echo htmlspecialchars($collector['employee_number']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <?php 
                                $full_name = trim($collector['first_name'] . ' ' . 
                                             ($collector['middle_name'] ? $collector['middle_name'] . ' ' : '') . 
                                             $collector['last_name']);
                                echo htmlspecialchars($full_name); 
                            ?>
                        </td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($collector['contact']); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs <?php echo $collector['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($collector['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($collector['created_by_name'] ?? 'System'); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo date('M d, Y', strtotime($collector['created_at'])); ?></td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <a href="collectors_edit.php?id=<?php echo $collector['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="?reset_password=<?php echo $collector['id']; ?>" class="text-orange-600 hover:text-orange-800" onclick="return confirm('Reset password to default (Employee Number)?')">
                                <i class="fas fa-key"></i> Reset PWD
                            </a>
                            <a href="?toggle_status=<?php echo $collector['id']; ?>" class="text-yellow-600 hover:text-yellow-800" onclick="return confirm('Toggle collector status?')">
                                <i class="fas <?php echo $collector['status'] == 'active' ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i> 
                                <?php echo $collector['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                            </a>
                            <a href="?delete=<?php echo $collector['id']; ?>" onclick="return confirm('Are you sure you want to delete this collector?')" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-user-tie text-4xl mb-2 block"></i>
                            No collectors found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>