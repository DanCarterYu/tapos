<?php
// management/login.php
// Management Login Page
require_once 'session_config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'management') {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';  // Changed: added ../
    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['full_name'] = $admin['full_name'];
            $_SESSION['role'] = 'management';
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Login - RFID Fare System</title>
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
                <p class="text-sm text-gray-500 mt-2">Management Login</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        <i class="fas fa-user mr-2"></i>Username
                    </label>
                    <input type="text" name="username" id="username" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-yellow-500"
                        placeholder="Enter username">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input type="password" name="password" id="password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-yellow-500"
                        placeholder="Enter password">
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
            </form>
            

            <!-- <div class="mt-6 text-center text-sm text-gray-500">
                <a href="../collector/login.php" class="text-green-600 hover:text-green-800">
                    <i class="fas fa-user-tie mr-1"></i> Collector Login
                </a>
                <span class="mx-2">|</span>
                <a href="../passenger/login.php" class="text-yellow-600 hover:text-yellow-800">
                    <i class="fas fa-user mr-1"></i> Passenger Login
                </a>
            </div> -->

            
        </div>
    </div>
</body>
</html> 