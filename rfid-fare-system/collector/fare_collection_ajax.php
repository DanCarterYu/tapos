<?php
// collector/fare_collection_ajax.php
// AJAX endpoint for fetching passenger details

require_once 'session_config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if logged in as collector
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'collector') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

if ($action === 'get_passenger') {
    $rfid_uid = trim($_GET['rfid_uid'] ?? '');
    
    if (empty($rfid_uid)) {
        echo json_encode(['success' => false, 'message' => 'No RFID UID provided']);
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT *, 
                           CONCAT(first_name, ' ', 
                                  IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                                  last_name) as full_name
                           FROM passengers 
                           WHERE rfid_uid = ? AND status = 'active'");
    $stmt->execute([$rfid_uid]);
    $passenger = $stmt->fetch();
    
    if (!$passenger) {
        echo json_encode(['success' => false, 'message' => 'Passenger not found']);
        exit();
    }
    
    $age = null;
    if (!empty($passenger['date_of_birth'])) {
        $birthDate = new DateTime($passenger['date_of_birth']);
        $today = new DateTime('today');
        $age = $birthDate->diff($today)->y;
    }
    
    echo json_encode([
        'success' => true,
        'passenger' => [
            'id' => $passenger['id'],
            'rfid_uid' => $passenger['rfid_uid'],
            'full_name' => $passenger['full_name'],
            'age' => $age,
            'sex' => $passenger['sex'],
            'address' => $passenger['address'],
            'balance' => $passenger['balance']
        ]
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>