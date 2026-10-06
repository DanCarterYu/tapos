<?php
// management/vessels_create.php
// Add New Vessel

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = strtoupper(trim($_POST['name']));
    $type = $_POST['type'];
    
    // =============================================
    // FIX: Check if array keys exist before using
    // =============================================
    $aircon_capacity = isset($_POST['aircon_capacity']) ? intval($_POST['aircon_capacity']) : 0;
    $non_aircon_capacity = isset($_POST['non_aircon_capacity']) ? intval($_POST['non_aircon_capacity']) : 0;
    
    $status = $_POST['status'];
    
    // Validate
    if (empty($name)) {
        $error = 'Please enter vessel name';
    } elseif (empty($type)) {
        $error = 'Please select vessel type';
    } elseif ($type == 'aircon' && $aircon_capacity <= 0) {
        $error = 'Please enter valid aircon capacity';
    } elseif ($type == 'non_aircon' && $non_aircon_capacity <= 0) {
        $error = 'Please enter valid non-aircon capacity';
    } elseif ($type == 'both' && ($aircon_capacity <= 0 || $non_aircon_capacity <= 0)) {
        $error = 'Please enter valid capacities for both sections';
    } else {
        // Check if vessel name already exists
        $stmt = $pdo->prepare("SELECT id FROM vessels WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $error = 'A vessel with this name already exists!';
        } else {
            // Insert vessel
            $stmt = $pdo->prepare("INSERT INTO vessels (name, type, aircon_capacity, non_aircon_capacity, status) 
                                   VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $type, $aircon_capacity, $non_aircon_capacity, $status])) {
                $success = "Vessel '{$name}' added successfully!";
                $_POST = [];
            } else {
                $error = 'Failed to add vessel';
            }
        }
    }
}

include_once '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-plus-circle mr-2"></i>Add New Vessel
    </h1>
    <a href="vessels.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
        <i class="fas fa-arrow-left mr-2"></i>Back to Vessels
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
    <form method="POST" action="">
        <div class="space-y-4">
            <!-- Vessel Name -->
            <div>
                <label class="block text-gray-700 font-bold mb-2">Vessel Name *</label>
                <input type="text" name="name" required id="vesselName"
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                       placeholder="e.g., VINCE GABRIEL 2"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <p class="text-sm text-gray-500 mt-1">Auto-uppercase</p>
            </div>

            <!-- Vessel Type -->
            <div>
                <label class="block text-gray-700 font-bold mb-2">Vessel Type *</label>
                <select name="type" id="vesselType" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" onchange="toggleCapacityFields()">
                    <option value="">-- Select Type --</option>
                    <option value="aircon" <?php echo ($_POST['type'] ?? '') == 'aircon' ? 'selected' : ''; ?>>Aircon Only</option>
                    <option value="non_aircon" <?php echo ($_POST['type'] ?? '') == 'non_aircon' ? 'selected' : ''; ?>>Non-Aircon Only</option>
                    <option value="both" <?php echo ($_POST['type'] ?? '') == 'both' ? 'selected' : ''; ?>>Both (Aircon + Non-Aircon)</option>
                </select>
            </div>

            <!-- Capacities -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="capacityFields">
                <div>
                    <label class="block text-gray-700 font-bold mb-2" id="airconLabel">Aircon Capacity (seats)</label>
                    <input type="number" name="aircon_capacity" id="airconCapacity" min="0"
                           value="<?php echo htmlspecialchars($_POST['aircon_capacity'] ?? 0); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <p class="text-sm text-gray-500 mt-1">Number of aircon seats</p>
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2" id="nonAirconLabel">Non-Aircon Capacity (seats)</label>
                    <input type="number" name="non_aircon_capacity" id="nonAirconCapacity" min="0"
                           value="<?php echo htmlspecialchars($_POST['non_aircon_capacity'] ?? 0); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <p class="text-sm text-gray-500 mt-1">Number of non-aircon seats</p>
                </div>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-gray-700 font-bold mb-2">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <option value="active" <?php echo ($_POST['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($_POST['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Capacity Notes:</strong><br>
                    • <strong>Aircon Only:</strong> Only aircon capacity is used<br>
                    • <strong>Non-Aircon Only:</strong> Only non-aircon capacity is used<br>
                    • <strong>Both:</strong> Both capacities are used (total seats = aircon + non-aircon)
                </p>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                    <i class="fas fa-save mr-2"></i>Add Vessel
                </button>
                <a href="vessels.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition ml-2">
                    Cancel
                </a>
            </div>
        </div>
    </form>
</div>

<script>
    // Auto-uppercase for vessel name
    document.getElementById('vesselName').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Toggle capacity fields based on vessel type
    function toggleCapacityFields() {
        const type = document.getElementById('vesselType').value;
        const airconField = document.getElementById('airconCapacity');
        const nonAirconField = document.getElementById('nonAirconCapacity');
        const airconLabel = document.getElementById('airconLabel');
        const nonAirconLabel = document.getElementById('nonAirconLabel');
        
        if (type === 'aircon') {
            airconField.disabled = false;
            airconField.required = true;
            nonAirconField.disabled = true;
            nonAirconField.required = false;
            nonAirconField.value = 0;
            airconLabel.textContent = 'Aircon Capacity (seats) *';
            nonAirconLabel.textContent = 'Non-Aircon Capacity (seats)';
        } else if (type === 'non_aircon') {
            airconField.disabled = true;
            airconField.required = false;
            airconField.value = 0;
            nonAirconField.disabled = false;
            nonAirconField.required = true;
            airconLabel.textContent = 'Aircon Capacity (seats)';
            nonAirconLabel.textContent = 'Non-Aircon Capacity (seats) *';
        } else if (type === 'both') {
            airconField.disabled = false;
            airconField.required = true;
            nonAirconField.disabled = false;
            nonAirconField.required = true;
            airconLabel.textContent = 'Aircon Capacity (seats) *';
            nonAirconLabel.textContent = 'Non-Aircon Capacity (seats) *';
        } else {
            airconField.disabled = true;
            airconField.required = false;
            nonAirconField.disabled = true;
            nonAirconField.required = false;
            airconLabel.textContent = 'Aircon Capacity (seats)';
            nonAirconLabel.textContent = 'Non-Aircon Capacity (seats)';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleCapacityFields();
    });
</script>

<?php include_once '../includes/footer.php'; ?>