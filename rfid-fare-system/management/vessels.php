<?php
// management/vessels.php
// Vessels Management - List all vessels

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

// Handle status toggle (activate/deactivate)
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    
    $stmt = $pdo->prepare("SELECT status FROM vessels WHERE id = ?");
    $stmt->execute([$id]);
    $vessel = $stmt->fetch();
    
    if ($vessel) {
        $new_status = ($vessel['status'] == 'active') ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE vessels SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        $success = "Vessel " . ($new_status == 'active' ? 'activated' : 'deactivated') . " successfully!";
    } else {
        $error = "Vessel not found!";
    }
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // =============================================
    // FIXED: Check if vessel is used in destinations (routes)
    // =============================================
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM destinations WHERE vessel_id = ?");
    $stmt->execute([$id]);
    $used = $stmt->fetch()['count'];
    
    if ($used > 0) {
        $error = "Cannot delete this vessel because it is assigned to one or more routes. Deactivate it instead.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM vessels WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Vessel deleted successfully!";
    }
}

// Get all vessels
$stmt = $pdo->query("SELECT * FROM vessels ORDER BY name");
$vessels = $stmt->fetchAll();

// Get counts for summary
$stmt = $pdo->query("SELECT COUNT(*) as total FROM vessels");
$totalVessels = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM vessels WHERE status = 'active'");
$activeVessels = $stmt->fetch()['total'];

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-ship mr-2"></i>Vessels Management
    </h1>
    <a href="vessels_create.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
        <i class="fas fa-plus mr-2"></i>Add New Vessel
    </a>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-ship text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Vessels</p>
                <p class="text-2xl font-bold"><?php echo $totalVessels; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Active Vessels</p>
                <p class="text-2xl font-bold"><?php echo $activeVessels; ?></p>
            </div>
        </div>
    </div>
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

<!-- Vessels Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vessel Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aircon Seats</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Non-Aircon Seats</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Seats</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($vessels) > 0): ?>
                    <?php foreach ($vessels as $vessel): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($vessel['name']); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs <?php 
                                echo $vessel['type'] == 'both' ? 'bg-purple-100 text-purple-800' : 
                                    ($vessel['type'] == 'aircon' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800');
                            ?>">
                                <?php echo ucfirst($vessel['type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm"><?php echo $vessel['aircon_capacity']; ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo $vessel['non_aircon_capacity']; ?></td>
                        <td class="px-6 py-4 text-sm font-bold">
                            <?php echo $vessel['aircon_capacity'] + $vessel['non_aircon_capacity']; ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs <?php echo $vessel['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($vessel['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <a href="vessels_edit.php?id=<?php echo $vessel['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="?toggle_status=<?php echo $vessel['id']; ?>" class="text-yellow-600 hover:text-yellow-800" onclick="return confirm('Toggle vessel status?')">
                                <i class="fas <?php echo $vessel['status'] == 'active' ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                <?php echo $vessel['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                            </a>
                            <a href="?delete=<?php echo $vessel['id']; ?>" onclick="return confirm('Are you sure you want to delete this vessel?')" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-ship text-4xl mb-2 block"></i>
                            No vessels found. <a href="vessels_create.php" class="text-blue-600 hover:underline">Add your first vessel</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>