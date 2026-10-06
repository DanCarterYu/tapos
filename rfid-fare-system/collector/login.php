<?php
// collector/login.php
// Collector Login Page

require_once 'session_config.php';

// If already logged in as collector, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'collector') {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter employee number and password';
    } else {
        // Query collector with full name from split fields
        $stmt = $pdo->prepare("SELECT *, 
                               CONCAT(first_name, ' ', 
                                      IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                                      last_name) as full_name 
                               FROM collectors WHERE employee_number = ? AND status = 'active'");
        $stmt->execute([$username]);
        $collector = $stmt->fetch();

        if ($collector && password_verify($password, $collector['password'])) {
            // Login successful
            $_SESSION['user_id'] = $collector['id'];
            $_SESSION['username'] = $collector['employee_number'];
            $_SESSION['full_name'] = $collector['full_name'];
            $_SESSION['role'] = 'collector';
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid employee number or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collector Login - RFID Fare System</title>
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
                <p class="text-sm text-gray-500 mt-2">Collector Portal</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        <i class="fas fa-id-card mr-2"></i>Employee Number
                    </label>
                    <input type="text" name="username" id="username" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-yellow-500"
                        placeholder="Enter your employee number">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input type="password" name="password" id="password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-yellow-500"
                        placeholder="Enter your password">
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
            </form>
        </div>
    </div>
</body>
</html>