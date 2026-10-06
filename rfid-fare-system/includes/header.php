<?php
// includes/header.php
// Common header for ALL dashboards (Management, Collector, Passenger)
// Mobile Responsive with Hamburger Menu

// Get current page name for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';

// Set portal name based on role
switch ($role) {
    case 'management':
        $portal_name = 'Management Portal';
        $home_link = '../management/dashboard.php';
        $logout_link = '../management/logout.php';
        break;
    case 'collector':
        $portal_name = 'Collector Portal';
        $home_link = '../collector/dashboard.php';
        $logout_link = '../collector/logout.php';
        break;
    case 'passenger':
        $portal_name = 'Passenger Portal';
        $home_link = '../passenger/dashboard.php';
        $logout_link = '../passenger/logout.php';
        break;
    default:
        $portal_name = 'Portal';
        $home_link = '#';
        $logout_link = '#';
}

// Determine which directory we are in for correct pathing
$current_dir = basename(dirname($_SERVER['SCRIPT_FILENAME']));
$path_prefix = ($current_dir === 'management' || $current_dir === 'collector' || $current_dir === 'passenger') ? '../' : '';

// =============================================
// FIX: Get user name from database instead of session
// =============================================
$display_name = $_SESSION['full_name'] ?? '';

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    try {
        require_once '../config/database.php';
        
        if ($_SESSION['role'] === 'management') {
            $stmt = $pdo->prepare("SELECT full_name FROM admin WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                $display_name = $user['full_name'];
            }
        } elseif ($_SESSION['role'] === 'collector') {
            $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', 
                                   IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                                   last_name) as full_name 
                                   FROM collectors WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                $display_name = $user['full_name'];
            }
        } elseif ($_SESSION['role'] === 'passenger') {
            $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', 
                                   IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                                   last_name) as full_name 
                                   FROM passengers WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                $display_name = $user['full_name'];
            }
        }
    } catch (Exception $e) {
        // If database query fails, fallback to session
        $display_name = $_SESSION['full_name'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title><?php echo $portal_name; ?> - RFID Fare System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .sidebar-open {
                transform: translateX(0) !important;
            }
            .sidebar-closed {
                transform: translateX(-100%) !important;
            }
            .table-container {
                -webkit-overflow-scrolling: touch;
            }
            /* Larger touch targets for mobile */
            .touch-target {
                min-height: 44px;
                min-width: 44px;
            }
            .touch-target-sm {
                min-height: 36px;
                min-width: 36px;
            }
        }
        /* Smooth sidebar transition */
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        /* Prevent body scroll when sidebar is open on mobile */
        .no-scroll {
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Top Navigation Bar -->
    <nav class="bg-blue-800 text-white shadow-lg sticky top-0 z-50">
        <div class="px-3 sm:px-6 py-2 sm:py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2 sm:space-x-4">
                <!-- Hamburger Menu Button (Mobile) -->
                <button id="menuToggle" class="lg:hidden text-white hover:text-gray-200 focus:outline-none touch-target" aria-label="Toggle menu">
                    <i class="fas fa-bars text-xl sm:text-2xl"></i>
                </button>
                <!-- LOGO -->
                <img src="../assets/images/vgl_logoNT_nobg.png" alt="Vince Gabriel Liners" 
                     class="h-10 w-auto sm:h-10">
                <span class="font-bold text-sm sm:text-xl truncate max-w-[120px] sm:max-w-none">
                    Vince Gabriel Liners
                </span>
                <span class="text-xs sm:text-sm bg-blue-600 px-1.5 sm:px-2 py-0.5 rounded hidden sm:inline-block"><?php echo $portal_name; ?></span>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-4">
                <span class="text-xs sm:text-sm hidden md:inline-block"><i class="fas fa-user mr-1 sm:mr-2"></i><?php echo htmlspecialchars($display_name); ?></span>
                <?php if ($role === 'collector'): ?>
                    <span class="text-xs hidden sm:inline-block"><i class="fas fa-id-card mr-1 sm:mr-2"></i><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
                <?php endif; ?>
                <?php if ($role === 'passenger'): ?>
                    <span class="text-xs hidden sm:inline-block"><i class="fas fa-id-card mr-1 sm:mr-2"></i><?php echo htmlspecialchars($_SESSION['rfid_uid'] ?? ''); ?></span>
                <?php endif; ?>
                <a href="<?php echo $logout_link; ?>" class="bg-red-600 hover:bg-red-700 px-2 sm:px-4 py-1 rounded text-xs sm:text-sm transition touch-target-sm">
                    <i class="fas fa-sign-out-alt mr-1"></i><span class="hidden xs:inline">Logout</span>
                </a>
            </div>
        </div>
    </nav>
    
    <div class="flex min-h-screen">
        <!-- Overlay for mobile -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
        
        <!-- Sidebar -->
        <div id="sidebar" class="fixed lg:fixed top-16 left-0 z-50 w-64 bg-gray-800 text-white h-[calc(100vh-4rem)] sidebar-transition sidebar-closed lg:sidebar-closed lg:translate-x-0 overflow-y-auto">
            <div class="p-3 sm:p-4">
                <!-- Close button for mobile -->
                <button id="closeSidebar" class="lg:hidden text-gray-400 hover:text-white float-right touch-target">
                    <i class="fas fa-times text-xl"></i>
                </button>
                <div class="clear-both"></div>
                
                <?php if ($role === 'management'): ?>
                    <!-- ===== MANAGEMENT SIDEBAR ===== -->
                    <div class="mb-4 sm:mb-6">
                        <a href="../management/dashboard.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'dashboard.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                        </a>
                    </div>
                    
                    <div class="mb-4 sm:mb-6">
                        <div class="text-gray-400 text-xs uppercase mb-2 px-3 sm:px-4">Management</div>
                        <a href="../management/passengers.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'passengers.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-users mr-2"></i> Passenger Accounts
                        </a>
                        <a href="../management/collectors.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'collectors.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-user-tie mr-2"></i> Collector Accounts
                        </a>
                        <!-- ADD THIS -->
                        <a href="../management/collector_assignments.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'collector_assignments.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-link mr-2"></i> Collector Assignments
                        </a>
                    </div>
                    
                    <div class="mb-4 sm:mb-6">
                        <div class="text-gray-400 text-xs uppercase mb-2 px-3 sm:px-4">Configuration</div>
                        <a href="../management/vessels.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'vessels.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-ship mr-2"></i> Vessels
                        </a>
                        <a href="../management/destinations.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'destinations.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-map-marker-alt mr-2"></i> Destinations & Fares
                        </a>
                        <a href="../management/discounts.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'discounts.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-percent mr-2"></i> Discount Types
                        </a>
                        <a href="../management/loyalty_points.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'loyalty_points.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-gem mr-2"></i> Loyalty Points
                        </a>
                    </div>
                    
                    <div class="mb-4 sm:mb-6">
                        <div class="text-gray-400 text-xs uppercase mb-2 px-3 sm:px-4">Finance</div>
                        <a href="../management/balance_requests.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'balance_requests.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-hand-holding-usd mr-2"></i> Balance Requests
                        </a>

                        <a href="../management/redemption_settings.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'redemption_settings.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-gem mr-2 text-white-500"></i> Points Shop
                        </a>

                    </div>

    
                    <div class="mb-4 sm:mb-6">
                        <div class="text-gray-400 text-xs uppercase mb-2 px-3 sm:px-4">Reports</div>


                        <a href="../management/manifests.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'manifests.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-list mr-2"></i> Manifests
                        </a>

                        <a href="../management/transactions.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'transactions.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-receipt mr-2"></i> Transactions
                        </a>

                        <a href="../management/trip_reports.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'trip_reports.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-chart-bar mr-2"></i> Trip Reports
                        </a>


                        <a href="../management/load_history.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'load_history.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-history mr-2"></i> Load History
                        </a>
                    </div>
                    
                <?php elseif ($role === 'collector'): ?>
                    <!-- ===== COLLECTOR SIDEBAR ===== -->
                    <div class="mb-4 sm:mb-6">
                        <a href="../collector/dashboard.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'dashboard.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                        </a>
                    </div>
                    
                    <div class="mb-4 sm:mb-6">
                        <div class="text-gray-400 text-xs uppercase mb-2 px-3 sm:px-4">Operations</div>
                        <a href="../collector/fare_collection.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'fare_collection.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-credit-card mr-2"></i> Fare Collection
                        </a>

                        <a href="../collector/trips.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'trips.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-ship mr-2"></i> My Trips
                        </a>

                        <a href="../collector/register_rfid.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'register_rfid.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-id-card mr-2"></i> Register RFID Card
                        </a>

                        <a href="../collector/load_balance.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'load_balance.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-plus-circle mr-2"></i> Load Balance
                        </a>
                        <div class="text-gray-400 text-xs uppercase mb-2 px-3 sm:px-4">Activity</div>
                        <a href="../collector/transactions.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'transactions.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-receipt mr-2"></i> Transactions
                        </a>

                        <a href="../collector/manifest.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'manifest.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-list mr-2"></i> Manifest
                        </a>

                        <a href="../collector/my_passengers.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'my_passengers.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-users mr-2"></i> My Passengers
                        </a>

                        <a href="../collector/load_history.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'load_history.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-history mr-2"></i> Load History
                        </a>
                    </div>
                        
                    
                    <div class="mb-4 sm:mb-6">
                        <div class="text-gray-400 text-xs uppercase mb-2 px-3 sm:px-4">Account</div>
                        <a href="../collector/profile.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'profile.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-user-cog mr-2"></i> My Profile
                        </a>
                    </div>
                    
                <?php elseif ($role === 'passenger'): ?>
                    <!-- ===== PASSENGER SIDEBAR ===== -->
                    <div class="mb-4 sm:mb-6">
                        <a href="../passenger/dashboard.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'dashboard.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                        </a>
                    </div>
                    
                    <div class="mb-4 sm:mb-6">
                        <div class="text-gray-400 text-xs uppercase mb-2 px-3 sm:px-4">My Account</div>
                        <a href="../passenger/balance_request.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'balance_request.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-plus-circle mr-2"></i> Request Balance
                        </a>

                        <a href="../passenger/points_shop.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'points_shop.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-gem mr-2 text-white-500"></i> Points Shop
                        </a>

                        <div class="text-gray-400 text-xs uppercase mb-2 px-3 sm:px-4">Activity</div>
                        <a href="../passenger/balance_history.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'balance_history.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-history mr-2"></i> Balance History
                        </a>
                        <a href="../passenger/receipts.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'receipts.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-receipt mr-2"></i> My Receipts
                        </a>

                        <div class="text-gray-400 text-xs uppercase mb-2 px-3 sm:px-4">Profile</div>
                        <a href="../passenger/profile.php" class="block py-2.5 sm:py-2 px-3 sm:px-4 rounded text-sm sm:text-base <?php echo ($current_page == 'profile.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?> touch-target">
                            <i class="fas fa-user mr-2"></i> Profile
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 min-w-0 ml-0 lg:ml-64">
            <div class="p-3 sm:p-6">