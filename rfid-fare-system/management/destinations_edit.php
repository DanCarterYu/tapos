<?php
// management/destinations_edit.php
// Edit route with vessel, section fares, and schedules

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if logged in as management
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    header('Location: login.php');
    exit();
}

$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: destinations.php');
    exit();
}

// Get destination data
$stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
$stmt->execute([$id]);
$destination = $stmt->fetch();

if (!$destination) {
    header('Location: destinations.php');
    exit();
}

// Get existing schedules for this route
$stmt = $pdo->prepare("SELECT * FROM trip_schedules WHERE destination_id = ? ORDER BY departure_time ASC");
$stmt->execute([$id]);
$schedules = $stmt->fetchAll();

// Get all active vessels for dropdown
$stmt = $pdo->query("SELECT id, name, type FROM vessels WHERE status = 'active' ORDER BY name");
$vessels = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $origin = strtoupper(trim($_POST['origin']));
    $destination_name = strtoupper(trim($_POST['destination']));
    $vessel_id = intval($_POST['vessel_id']);
    $aircon_fare = isset($_POST['aircon_fare']) ? floatval($_POST['aircon_fare']) : 0;
    $non_aircon_fare = isset($_POST['non_aircon_fare']) ? floatval($_POST['non_aircon_fare']) : 0;
    
    // Get schedules from POST
    $departure_times = $_POST['departure_time'] ?? [];
    $arrival_times = $_POST['arrival_time'] ?? [];
    $schedule_ids = $_POST['schedule_id'] ?? [];
    
    // Validate
    if (empty($origin)) {
        $error = 'Please enter origin';
    } elseif (empty($destination_name)) {
        $error = 'Please enter destination';
    } elseif (empty($vessel_id)) {
        $error = 'Please select a vessel';
    } elseif ($aircon_fare <= 0 && $non_aircon_fare <= 0) {
        $error = 'Please enter valid fares for at least one section';
    } else {
        // Check if this route already exists for this vessel (excluding current)
        $stmt = $pdo->prepare("SELECT id FROM destinations WHERE origin = ? AND destination = ? AND vessel_id = ? AND id != ?");
        $stmt->execute([$origin, $destination_name, $vessel_id, $id]);
        if ($stmt->fetch()) {
            $error = 'This route already exists for this vessel!';
        } else {
            // Get vessel type to validate fares
            $stmt = $pdo->prepare("SELECT type FROM vessels WHERE id = ?");
            $stmt->execute([$vessel_id]);
            $vessel = $stmt->fetch();
            
            // Validate based on vessel type
            if ($vessel['type'] == 'aircon' && $aircon_fare <= 0) {
                $error = 'Please enter a valid aircon fare for this vessel';
            } elseif ($vessel['type'] == 'non_aircon' && $non_aircon_fare <= 0) {
                $error = 'Please enter a valid non-aircon fare for this vessel';
            } elseif ($vessel['type'] == 'both' && ($aircon_fare <= 0 || $non_aircon_fare <= 0)) {
                $error = 'Please enter valid fares for both aircon and non-aircon sections';
            } elseif (empty($departure_times) || empty($arrival_times)) {
                $error = 'Please add at least one schedule (departure & arrival time)';
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Update route
                    $stmt = $pdo->prepare("UPDATE destinations SET 
                                           origin = ?, 
                                           destination = ?, 
                                           vessel_id = ?,
                                           aircon_fare = ?,
                                           non_aircon_fare = ?
                                           WHERE id = ?");
                    $stmt->execute([$origin, $destination_name, $vessel_id, $aircon_fare, $non_aircon_fare, $id]);
                    
                    // =============================================
                    // Update schedules
                    // =============================================
                    
                    // 1. Delete schedules that were removed
                    $existing_ids = array_filter($schedule_ids, function($val) { return !empty($val); });
                    if (!empty($existing_ids)) {
                        $placeholders = implode(',', array_fill(0, count($existing_ids), '?'));
                        $stmt = $pdo->prepare("DELETE FROM trip_schedules 
                                               WHERE destination_id = ? AND id NOT IN ($placeholders)");
                        $stmt->execute(array_merge([$id], $existing_ids));
                    } else {
                        // No existing schedules, delete all
                        $stmt = $pdo->prepare("DELETE FROM trip_schedules WHERE destination_id = ?");
                        $stmt->execute([$id]);
                    }
                    
                    // 2. Insert or update schedules
                    $stmt_insert = $pdo->prepare("INSERT INTO trip_schedules 
                                                  (destination_id, departure_time, arrival_time) 
                                                  VALUES (?, ?, ?)");
                    $stmt_update = $pdo->prepare("UPDATE trip_schedules SET 
                                                  departure_time = ?, 
                                                  arrival_time = ? 
                                                  WHERE id = ? AND destination_id = ?");
                    
                    $schedule_count = 0;
                    foreach ($departure_times as $index => $departure) {
                        if (!empty($departure) && !empty($arrival_times[$index])) {
                            $schedule_id = !empty($schedule_ids[$index]) ? $schedule_ids[$index] : 0;
                            
                            if ($schedule_id > 0) {
                                // Update existing schedule
                                $stmt_update->execute([$departure, $arrival_times[$index], $schedule_id, $id]);
                            } else {
                                // Insert new schedule
                                $stmt_insert->execute([$id, $departure, $arrival_times[$index]]);
                            }
                            $schedule_count++;
                        }
                    }
                    
                    $pdo->commit();
                    
                    $success = "Route updated successfully with {$schedule_count} schedule(s)!";
                    
                    // Refresh data
                    $stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
                    $stmt->execute([$id]);
                    $destination = $stmt->fetch();
                    
                    $stmt = $pdo->prepare("SELECT * FROM trip_schedules WHERE destination_id = ? ORDER BY departure_time ASC");
                    $stmt->execute([$id]);
                    $schedules = $stmt->fetchAll();
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Failed to update route: ' . $e->getMessage();
                }
            }
        }
    }
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-edit mr-2"></i>Edit Route
    </h1>
    <a href="destinations.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
        <i class="fas fa-arrow-left mr-2"></i>Back to Routes
    </a>
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

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="" id="routeForm">
        <!-- Route Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Origin *</label>
                <input type="text" name="origin" required id="originInput"
                       value="<?php echo htmlspecialchars($destination['origin']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <p class="text-sm text-gray-500 mt-1">Departure point</p>
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2">Destination *</label>
                <input type="text" name="destination" required id="destinationInput"
                       value="<?php echo htmlspecialchars($destination['destination']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <p class="text-sm text-gray-500 mt-1">Arrival point</p>
            </div>
        </div>
        
        <div class="mt-4">
            <label class="block text-gray-700 font-bold mb-2">Vessel *</label>
            <select name="vessel_id" id="vesselSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" onchange="updateFareFields()">
                <option value="">-- Select Vessel --</option>
                <?php foreach ($vessels as $vessel): ?>
                    <option value="<?php echo $vessel['id']; ?>" 
                            data-type="<?php echo $vessel['type']; ?>"
                            <?php echo $destination['vessel_id'] == $vessel['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($vessel['name']); ?> (<?php echo ucfirst($vessel['type']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Section Fares -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4" id="sectionFares">
            <div>
                <label class="block text-gray-700 font-bold mb-2" id="airconLabel">Aircon Fare (₱) *</label>
                <div class="flex items-center">
                    <span class="text-xl mr-2">₱</span>
                    <input type="number" name="aircon_fare" id="airconFare" step="1" min="1" required
                           value="<?php echo $destination['aircon_fare']; ?>"
                           placeholder="0.00"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-2" id="nonAirconLabel">Non-Aircon Fare (₱) *</label>
                <div class="flex items-center">
                    <span class="text-xl mr-2">₱</span>
                    <input type="number" name="non_aircon_fare" id="nonAirconFare" step="1" min="1" required
                           value="<?php echo $destination['non_aircon_fare']; ?>"
                           placeholder="0.00"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
            </div>
        </div>
        
        <!-- ============================================= -->
        <!-- TRIP SCHEDULES SECTION -->
        <!-- ============================================= -->
        <div class="mt-6 border-t pt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-clock mr-2 text-blue-500"></i>Trip Schedules
            </h3>
            <p class="text-sm text-gray-500 mb-4">Add or remove schedules for this route.</p>
            
            <div id="schedulesContainer">
                <?php if (count($schedules) > 0): ?>
                    <?php foreach ($schedules as $index => $schedule): ?>
                        <div class="schedule-row grid grid-cols-1 md:grid-cols-3 gap-4 items-end mb-3">
                            <input type="hidden" name="schedule_id[]" value="<?php echo $schedule['id']; ?>">
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Departure Time *</label>
                                <input type="time" name="departure_time[]" required 
                                       value="<?php echo $schedule['departure_time']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Arrival Time *</label>
                                <input type="time" name="arrival_time[]" required 
                                       value="<?php echo $schedule['arrival_time']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                            <div class="flex gap-2">
                                <button type="button" onclick="addScheduleRow()" 
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition text-sm">
                                    <i class="fas fa-plus mr-1"></i> Add
                                </button>
                                <button type="button" onclick="removeScheduleRow(this)" 
                                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition text-sm">
                                    <i class="fas fa-trash mr-1"></i> Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default empty row -->
                    <div class="schedule-row grid grid-cols-1 md:grid-cols-3 gap-4 items-end mb-3">
                        <input type="hidden" name="schedule_id[]" value="">
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Departure Time *</label>
                            <input type="time" name="departure_time[]" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-bold mb-2">Arrival Time *</label>
                            <input type="time" name="arrival_time[]" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="addScheduleRow()" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition text-sm">
                                <i class="fas fa-plus mr-1"></i> Add
                            </button>
                            <button type="button" onclick="removeScheduleRow(this)" 
                                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition text-sm">
                                <i class="fas fa-trash mr-1"></i> Remove
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="scheduleCount" class="text-sm text-gray-500 mt-2">
                Total schedules: <span id="scheduleCounter"><?php echo count($schedules) > 0 ? count($schedules) : 1; ?></span>
            </div>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4 mt-6">
            <p class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Created:</strong> <?php echo date('F d, Y', strtotime($destination['created_at'])); ?>
            </p>
            <p class="text-sm text-gray-600 mt-1">
                <strong>Current Route:</strong> <?php echo htmlspecialchars($destination['origin'] . ' → ' . $destination['destination']); ?>
            </p>
        </div>
        
        <div class="bg-blue-50 rounded-lg p-4 mt-6">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>How schedules work:</strong><br>
                • Each route can have multiple schedules (different departure times)<br>
                • Example: SURIGAO → SAN JOSE can have 5:00 AM and 12:00 PM schedules<br>
                • The system will generate daily trips from these schedules
            </p>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                <i class="fas fa-save mr-2"></i>Save Changes
            </button>
            <a href="destinations.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition ml-2">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
    // Auto-uppercase for origin and destination fields
    document.getElementById('originInput').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    document.getElementById('destinationInput').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Update fare fields based on vessel type
    function updateFareFields() {
        const vesselSelect = document.getElementById('vesselSelect');
        const selectedOption = vesselSelect.options[vesselSelect.selectedIndex];
        const vesselType = selectedOption ? selectedOption.dataset.type : '';
        
        const airconFare = document.getElementById('airconFare');
        const nonAirconFare = document.getElementById('nonAirconFare');
        const airconLabel = document.getElementById('airconLabel');
        const nonAirconLabel = document.getElementById('nonAirconLabel');
        
        if (vesselType === 'aircon') {
            airconFare.disabled = false;
            airconFare.required = true;
            nonAirconFare.disabled = true;
            nonAirconFare.required = false;
            nonAirconFare.value = 0;
            airconLabel.textContent = 'Aircon Fare (₱) *';
            nonAirconLabel.textContent = 'Non-Aircon Fare (₱) (N/A)';
        } else if (vesselType === 'non_aircon') {
            airconFare.disabled = true;
            airconFare.required = false;
            airconFare.value = 0;
            nonAirconFare.disabled = false;
            nonAirconFare.required = true;
            airconLabel.textContent = 'Aircon Fare (₱) (N/A)';
            nonAirconLabel.textContent = 'Non-Aircon Fare (₱) *';
        } else if (vesselType === 'both') {
            airconFare.disabled = false;
            airconFare.required = true;
            nonAirconFare.disabled = false;
            nonAirconFare.required = true;
            airconLabel.textContent = 'Aircon Fare (₱) *';
            nonAirconLabel.textContent = 'Non-Aircon Fare (₱) *';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateFareFields();
    });

    // =============================================
    // SCHEDULE ROW MANAGEMENT
    // =============================================
    let scheduleCount = <?php echo count($schedules) > 0 ? count($schedules) : 1; ?>;

    function addScheduleRow() {
        scheduleCount++;
        
        const container = document.getElementById('schedulesContainer');
        const row = document.createElement('div');
        row.className = 'schedule-row grid grid-cols-1 md:grid-cols-3 gap-4 items-end mb-3';
        row.innerHTML = `
            <input type="hidden" name="schedule_id[]" value="">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Departure Time *</label>
                <input type="time" name="departure_time[]" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2">Arrival Time *</label>
                <input type="time" name="arrival_time[]" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="addScheduleRow()" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition text-sm">
                    <i class="fas fa-plus mr-1"></i> Add
                </button>
                <button type="button" onclick="removeScheduleRow(this)" 
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition text-sm">
                    <i class="fas fa-trash mr-1"></i> Remove
                </button>
            </div>
        `;
        container.appendChild(row);
        document.getElementById('scheduleCounter').textContent = scheduleCount;
    }

    function removeScheduleRow(button) {
        const row = button.closest('.schedule-row');
        if (document.querySelectorAll('.schedule-row').length > 1) {
            row.remove();
            scheduleCount--;
            document.getElementById('scheduleCounter').textContent = scheduleCount;
        } else {
            alert('You must have at least one schedule.');
        }
    }
</script>

<?php include_once '../includes/footer.php'; ?>