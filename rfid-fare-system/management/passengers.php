<?php
// management/passengers.php
// Passenger Accounts Management (Clean Table with View Modal)

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Helper function to calculate age from date of birth
function calculateAge($date_of_birth) {
    if (empty($date_of_birth)) {
        return '-';
    }
    $birthDate = new DateTime($date_of_birth);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT * FROM passengers WHERE id = ?");
    $stmt->execute([$id]);
    $passenger = $stmt->fetch();
    
    if ($passenger) {
        $stmt = $pdo->prepare("DELETE FROM passengers WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Passenger deleted successfully!";
    } else {
        $error = "Passenger not found!";
    }
}

// Get search parameter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$query = "SELECT * FROM passengers WHERE 1=1";
$params = [];

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

// Get counts for summary
$stmt = $pdo->query("SELECT COUNT(*) as total FROM passengers");
$totalPassengers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM passengers WHERE status = 'active'");
$activePassengers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT SUM(balance) as total_balance FROM passengers");
$totalBalance = $stmt->fetch()['total_balance'] ?? 0;

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-users mr-2"></i>Passenger Accounts
    </h1>
    <a href="passengers_create.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
        <i class="fas fa-plus mr-2"></i>Add New Passenger
    </a>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-full p-3 mr-4">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Passengers</p>
                <p class="text-2xl font-bold"><?php echo $totalPassengers; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-user-check text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Active Passengers</p>
                <p class="text-2xl font-bold"><?php echo $activePassengers; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-full p-3 mr-4">
                <i class="fas fa-coins text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Balance (All Passengers)</p>
                <p class="text-2xl font-bold">₱<?php echo number_format($totalBalance, 2); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="" class="flex flex-wrap gap-4">
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
            <a href="passengers.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition ml-2">
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

<!-- Clean Passengers Table -->
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">RFID UID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Full Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sex</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (count($passengers) > 0): ?>
                    <?php foreach ($passengers as $passenger): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($passenger['rfid_uid']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <?php 
                                $full_name = trim($passenger['first_name'] . ' ' . 
                                             ($passenger['middle_name'] ? $passenger['middle_name'] . ' ' : '') . 
                                             $passenger['last_name']);
                                echo htmlspecialchars($full_name); 
                            ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <?php 
                                if (!empty($passenger['date_of_birth'])) {
                                    echo calculateAge($passenger['date_of_birth']);
                                } else {
                                    echo '-';
                                }
                            ?>
                        </td>
                        <td class="px-6 py-4 text-sm"><?php echo $passenger['sex'] == 'M' ? 'Male' : 'Female'; ?></td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <button onclick="viewPassenger(<?php echo $passenger['id']; ?>)" 
                                    class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <a href="?delete=<?php echo $passenger['id']; ?>" onclick="return confirm('Are you sure you want to delete this passenger?')" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-2 block"></i>
                            No passengers found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================= -->
<!-- VIEW PASSENGER MODAL -->
<!-- ============================================= -->
<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold">
                <i class="fas fa-user mr-2 text-blue-500"></i>Passenger Details
            </h2>
            <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]" id="viewPassengerDetails">
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-spinner fa-spin text-3xl"></i>
                <p class="mt-2">Loading...</p>
            </div>
        </div>
    </div>
</div>

<script>
    // =============================================
    // VIEW PASSENGER DETAILS
    // =============================================
    function viewPassenger(id) {
        document.getElementById('viewModal').classList.remove('hidden');
        loadPassengerDetails(id);
    }
    
    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }
    
    function loadPassengerDetails(id) {
        fetch(`passenger_view.php?id=${id}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('viewPassengerDetails').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('viewPassengerDetails').innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <i class="fas fa-exclamation-triangle text-3xl"></i>
                        <p class="mt-2">Failed to load passenger details</p>
                    </div>
                `;
            });
    }
    
    // =============================================
    // CLOSE MODAL ON ESC KEY
    // =============================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeViewModal();
        }
    });
    
    // =============================================
    // CLOSE MODAL ON OVERLAY CLICK
    // =============================================
    document.getElementById('viewModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeViewModal();
        }
    });
</script>

<?php include_once '../includes/footer.php'; ?>