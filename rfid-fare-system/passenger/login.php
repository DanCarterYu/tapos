<?php
// passenger/login.php
// Passenger Login Page (RFID UID + Password)

require_once 'session_config.php';

// If already logged in as passenger, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'passenger') {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $rfid_uid = trim($_POST['rfid_uid']);
    $password = trim($_POST['password']);
    
    if (empty($rfid_uid) || empty($password)) {
        $error = 'Please enter RFID UID and password';
    } else {
        // Query passenger by RFID UID with full name combined
        $stmt = $pdo->prepare("SELECT *, 
                               CONCAT(first_name, ' ', 
                                      IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                                      last_name) as full_name 
                               FROM passengers WHERE rfid_uid = ? AND status = 'active'");
        $stmt->execute([$rfid_uid]);
        $passenger = $stmt->fetch();
        
        // Verify password
        if ($passenger && password_verify($password, $passenger['password'])) {
            // Login successful
            $_SESSION['user_id'] = $passenger['id'];
            $_SESSION['rfid_uid'] = $passenger['rfid_uid'];
            $_SESSION['full_name'] = $passenger['full_name'];
            $_SESSION['role'] = 'passenger';
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid RFID UID or password. Please register at the management office.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Login - RFID Fare System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
            <div class="text-center mb-8">
                <div class="bg-blue-600 text-white w-40 h-40 rounded-full flex items-center justify-center mx-auto mb-4 overflow-hidden">
                    <img src="../assets/images/vgl_logo.png" alt="Vince Gabriel Liners" 
                        class="w-full h-full object-cover">
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Vince Gabriel Liners</h1>
                <p class="text-gray-600">Automated Fare Collection System</p>
                <p class="text-sm text-gray-500 mt-2">Passenger Portal</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="rfid_uid">
                        <i class="fas fa-id-card mr-2"></i>RFID UID
                    </label>
                    <input type="text" name="rfid_uid" id="rfid_uid" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                        placeholder="Enter your RFID UID">
                    <p class="text-xs text-gray-500 mt-1">The number on your RFID card</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input type="password" name="password" id="password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                        placeholder="Enter your password">
                    <p class="text-xs text-gray-500 mt-1">Default password is your RFID UID</p>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
            </form>
            
            <!-- <div class="mt-6 text-center text-sm text-gray-500">
                <p>Don't have an account? <a href="../management/login.php" class="text-blue-600 hover:text-blue-800">Contact Management</a></p>
                <p class="mt-2">
                    <a href="../management/login.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-1"></i> Management Login
                    </a>
                    <span class="mx-2">|</span>
                    <a href="../collector/login.php" class="text-green-600 hover:text-green-800">
                        <i class="fas fa-user-tie mr-1"></i> Collector Login
                    </a>
                </p>
            </div> -->
        </div>
    </div>
</body>
</html>