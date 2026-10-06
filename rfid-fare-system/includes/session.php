<?php
// includes/session.php
// Session management for all user roles

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: ../management/login.php');  // Changed from login.php to management/login.php
        exit();
    }
}

function redirectIfNotRole($allowedRole) {
    redirectIfNotLoggedIn();
    if ($_SESSION['role'] !== $allowedRole) {
        header('Location: ../' . $_SESSION['role'] . '/dashboard.php');
        exit();
    }
}

function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}
?>