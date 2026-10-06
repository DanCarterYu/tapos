<?php
// config/database.php
// Database connection for RFID Fare System
// Vince Gabriel Liners

$host = 'localhost';
$dbname = 'rfid_fare_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// DO NOT start session here - let each portal handle its own session
// This file should ONLY handle database connection
?>