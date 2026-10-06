<?php
// management/balance_requests_action.php
// AJAX handler for approve/reject actions

header('Content-Type: application/json');

// Use the same session name as management
session_name('MANAGEMENT_SESSION');
session_start();

// Check if logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'management') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Now include database
require_once '../config/database.php';

$action = $_POST['action'] ?? '';
$request_id = intval($_POST['request_id'] ?? 0);

if ($request_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit();
}

if ($action === 'approve') {
    // Get request details
    $stmt = $pdo->prepare("SELECT * FROM balance_requests WHERE id = ? AND status = 'pending'");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update passenger balance
        $stmt = $pdo->prepare("UPDATE passengers SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$request['amount'], $request['passenger_id']]);
        
        // Update request status
        $stmt = $pdo->prepare("UPDATE balance_requests SET 
                               status = 'approved', 
                               approved_by = ?, 
                               approved_date = NOW() 
                               WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $request_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Request #' . $request_id . ' approved! ₱' . number_format($request['amount'], 2) . ' loaded.'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to approve: ' . $e->getMessage()]);
    }
} elseif ($action === 'reject') {
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    
    if (empty($rejection_reason)) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE balance_requests SET 
                               status = 'rejected', 
                               rejection_reason = ?,
                               approved_by = ?,
                               approved_date = NOW() 
                               WHERE id = ? AND status = 'pending'");
        $stmt->execute([$rejection_reason, $_SESSION['user_id'], $request_id]);
        
        // Check if any row was actually updated
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Request #' . $request_id . ' rejected.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to reject: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}