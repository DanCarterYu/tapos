<?php
// management/collector_assignments.php
// Assign Routes to Collectors

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

// =============================================
// Handle form submission (add assignment)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_assignment') {
        $collector_id = intval($_POST['collector_id']);
        $destination_id = intval($_POST['destination_id']);
        
        if ($collector_id <= 0 || $destination_id <= 0) {
            $error = 'Please select both a collector and a route';
        } else {
            // Check if this route is already assigned to ANY collector
            $stmt = $pdo->prepare("SELECT id FROM collector_assignments WHERE destination_id = ?");
            $stmt->execute([$destination_id]);
            if ($stmt->fetch()) {
                $error = 'This route is already assigned to a collector.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO collector_assignments (collector_id, destination_id) VALUES (?, ?)");
                if ($stmt->execute([$collector_id, $destination_id])) {
                    $success = 'Route assigned to collector successfully!';
                } else {
                    $error = 'Failed to assign route.';
                }
            }
        }
    }
    
    if ($action === 'remove_assignment') {
        $assignment_id = intval($_POST['assignment_id']);
        
        $stmt = $pdo->prepare("DELETE FROM collector_assignments WHERE id = ?");
        if ($stmt->execute([$assignment_id])) {
            $success = 'Assignment removed successfully!';
        } else {
            $error = 'Failed to remove assignment.';
        }
    }
}

// =============================================
// Get all assignments with collector and route info
// =============================================
$stmt = $pdo->query("
    SELECT ca.*, 
           c.employee_number, 
           CONCAT(c.first_name, ' ', 
                  IF(c.middle_name IS NOT NULL AND c.middle_name != '', CONCAT(c.middle_name, ' '), ''), 
                  c.last_name) as collector_name,
           d.origin, 
           d.destination,
           v.name as vessel_name
    FROM collector_assignments ca
    JOIN collectors c ON ca.collector_id = c.id
    JOIN destinations d ON ca.destination_id = d.id
    LEFT JOIN vessels v ON d.vessel_id = v.id
    ORDER BY c.employee_number, d.origin
");
$assignments = $stmt->fetchAll();

// =============================================
// Get collectors and routes for dropdowns
// =============================================

// Active collectors
$stmt = $pdo->query("
    SELECT id, employee_number,
           CONCAT(first_name, ' ', 
                  IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                  last_name) as full_name
    FROM collectors 
    WHERE status = 'active'
    ORDER BY employee_number
");
$collectors = $stmt->fetchAll();

// Active routes with vessel info
$stmt = $pdo->query("
    SELECT d.id, d.origin, d.destination, v.name as vessel_name
    FROM destinations d
    LEFT JOIN vessels v ON d.vessel_id = v.id
    WHERE d.is_active = 1
    ORDER BY d.origin
");
$routes = $stmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-user-tie mr-2"></i>Collector Route Assignments
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
    <!-- ============================================= -->
    <!-- LEFT: Add Assignment Form -->
    <!-- ============================================= -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold">
                <i class="fas fa-plus-circle mr-2 text-green-500"></i>Assign Route to Collector
            </h2>
        </div>
        <div class="p-6">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_assignment">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Select Collector *</label>
                        <select name="collector_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="">-- Select Collector --</option>
                            <?php foreach ($collectors as $collector): ?>
                                <option value="<?php echo $collector['id']; ?>">
                                    <?php echo htmlspecialchars($collector['employee_number'] . ' - ' . $collector['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Select Route *</label>
                        <select name="destination_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="">-- Select Route --</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?php echo $route['id']; ?>">
                                    <?php echo htmlspecialchars($route['origin'] . ' → ' . $route['destination']); ?>
                                    <?php if ($route['vessel_name']): ?>
                                        (<?php echo htmlspecialchars($route['vessel_name']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition">
                        <i class="fas fa-link mr-2"></i>Assign Route
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ============================================= -->
    <!-- RIGHT: Current Assignments List -->
    <!-- ============================================= -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-bold">
                <i class="fas fa-list mr-2 text-blue-500"></i>Current Assignments
                <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo count($assignments); ?>)</span>
            </h2>
        </div>
        <div class="p-4 max-h-[500px] overflow-y-auto">
            <?php if (count($assignments) > 0): ?>
                <div class="space-y-3">
                    <?php foreach ($assignments as $assignment): ?>
                    <div class="flex justify-between items-center border-b pb-3">
                        <div>
                            <p class="font-semibold">
                                <?php echo htmlspecialchars($assignment['employee_number'] . ' - ' . $assignment['collector_name']); ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-arrow-right mr-1"></i>
                                <?php echo htmlspecialchars($assignment['origin'] . ' → ' . $assignment['destination']); ?>
                                <?php if ($assignment['vessel_name']): ?>
                                    <span class="text-xs text-gray-400">(<?php echo htmlspecialchars($assignment['vessel_name']); ?>)</span>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-gray-400">
                                Assigned: <?php echo date('M d, Y', strtotime($assignment['assigned_at'])); ?>
                            </p>
                        </div>
                        <form method="POST" action="" onsubmit="return confirm('Remove this assignment?')">
                            <input type="hidden" name="action" value="remove_assignment">
                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-user-tie text-4xl mb-2 block text-gray-300"></i>
                    <p>No assignments yet</p>
                    <p class="text-sm mt-1">Assign a collector to a route using the form</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Info Box -->
<div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <p class="text-sm text-blue-800">
        <i class="fas fa-info-circle mr-2"></i>
        <strong>How Assignments Work:</strong><br>
        • Each collector can be assigned to <strong>multiple routes</strong><br>
        • Each route can be assigned to <strong>only one collector</strong><br>
        • Only active collectors and routes are shown<br>
        • The collector will see their assigned routes on the <strong>"My Trips"</strong> page
    </p>
</div>

<?php include_once '../includes/footer.php'; ?>