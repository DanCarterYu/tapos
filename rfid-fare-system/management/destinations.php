<?php
// management/destinations.php
// Destinations & Fares Management with Schedules

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
    
    // Check if destination has transactions
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transactions WHERE destination_id = ?");
    $stmt->execute([$id]);
    $hasTransactions = $stmt->fetch()['count'];
    
    if ($hasTransactions > 0) {
        $error = "Cannot delete this destination because it has existing transactions. Deactivate it instead.";
    } else {
        // Delete schedules first (they will be deleted automatically due to foreign key cascade)
        $stmt = $pdo->prepare("DELETE FROM trip_schedules WHERE destination_id = ?");
        $stmt->execute([$id]);
        
        // Then delete the destination
        $stmt = $pdo->prepare("DELETE FROM destinations WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Destination and its schedules deleted successfully!";
    }
}

// Handle toggle status
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    
    $stmt = $pdo->prepare("SELECT is_active FROM destinations WHERE id = ?");
    $stmt->execute([$id]);
    $destination = $stmt->fetch();
    
    if ($destination) {
        $new_status = ($destination['is_active'] == 1) ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE destinations SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        $status_text = $new_status == 1 ? 'activated' : 'deactivated';
        $success = "Destination " . $status_text . " successfully!";
    } else {
        $error = "Destination not found!";
    }
}

// Get search parameter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query with vessel join
$query = "SELECT d.*, v.name as vessel_name, v.type as vessel_type 
          FROM destinations d
          LEFT JOIN vessels v ON d.vessel_id = v.id
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (d.origin LIKE ? OR d.destination LIKE ? OR v.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter !== '') {
    $query .= " AND d.is_active = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY d.origin ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$destinations = $stmt->fetchAll();

// Get schedules for each destination
foreach ($destinations as &$dest) {
    $stmt = $pdo->prepare("SELECT departure_time, arrival_time FROM trip_schedules 
                           WHERE destination_id = ? AND is_active = 1 
                           ORDER BY departure_time ASC");
    $stmt->execute([$dest['id']]);
    $dest['schedules'] = $stmt->fetchAll();
}

// Get counts for summary
$stmt = $pdo->query("SELECT COUNT(*) as total FROM destinations");
$totalDestinations = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM destinations WHERE is_active = 1");
$activeDestinations = $stmt->fetch()['total'];

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-map-marker-alt mr-2"></i>Destinations & Fares
    </h1>
    <a href="destinations_create.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
        <i class="fas fa-plus mr-2"></i>Add New Route
    </a>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-map-marker-alt text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Routes</p>
                <p class="text-2xl font-bold"><?php echo $totalDestinations; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Active Routes</p>
                <p class="text-2xl font-bold"><?php echo $activeDestinations; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" placeholder="Search by origin, destination, or vessel..." 
                   value="<?php echo htmlspecialchars($search); ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        <div>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <option value="">All Status</option>
                <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <a href="destinations.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition ml-2">
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

<!-- Destinations Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vessel</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fares</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedules</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($destinations) > 0): ?>
                    <?php foreach ($destinations as $destination): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium">
                            <?php echo htmlspecialchars($destination['origin'] . ' → ' . $destination['destination']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <?php if (!empty($destination['vessel_name'])): ?>
                                <span class="font-medium"><?php echo htmlspecialchars($destination['vessel_name']); ?></span>
                                <span class="text-xs text-gray-500 block"><?php echo ucfirst($destination['vessel_type']); ?></span>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">No vessel assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="text-blue-600 font-bold">Aircon: ₱<?php echo number_format($destination['aircon_fare'], 2); ?></div>
                            <div class="text-green-600 font-bold">Non-Aircon: ₱<?php echo number_format($destination['non_aircon_fare'], 2); ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <?php if (count($destination['schedules']) > 0): ?>
                                <?php foreach ($destination['schedules'] as $schedule): ?>
                                    <span class="inline-block bg-gray-100 px-2 py-1 rounded text-xs font-mono mr-1 mb-1">
                                        <?php echo date('h:i A', strtotime($schedule['departure_time'])); ?> - 
                                        <?php echo date('h:i A', strtotime($schedule['arrival_time'])); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">No schedules</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs <?php echo $destination['is_active'] == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $destination['is_active'] == 1 ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <a href="destinations_edit.php?id=<?php echo $destination['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="?toggle_status=<?php echo $destination['id']; ?>" class="text-yellow-600 hover:text-yellow-800" onclick="return confirm('Toggle destination status?')">
                                <i class="fas <?php echo $destination['is_active'] == 1 ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i> 
                                <?php echo $destination['is_active'] == 1 ? 'Deactivate' : 'Activate'; ?>
                            </a>
                            <a href="?delete=<?php echo $destination['id']; ?>" onclick="return confirm('Are you sure you want to delete this destination?')" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-map-marker-alt text-4xl mb-2 block"></i>
                            No routes found. <a href="destinations_create.php" class="text-blue-600 hover:underline">Add your first route</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>