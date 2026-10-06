<?php
// passenger/get_transaction_details.php
// AJAX endpoint for fetching transaction details (all types)

require_once 'session_config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if logged in as passenger
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$passenger_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (empty($type) || $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// =============================================
// LOAD BALANCE
// =============================================
if ($type === 'load') {
    $stmt = $pdo->prepare("
        SELECT bl.*,
               CASE 
                   WHEN bl.loaded_by_role = 'management' THEN 'Management'
                   ELSE c.employee_number
               END as loaded_by_display
        FROM balance_loads bl
        LEFT JOIN collectors c ON bl.loaded_by = c.id
        WHERE bl.id = ? AND bl.passenger_id = ?
    ");
    $stmt->execute([$id, $passenger_id]);
    $load = $stmt->fetch();

    if (!$load) {
        echo json_encode(['success' => false, 'message' => 'Load not found']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'type' => 'load',
        'data' => [
            'id' => $load['id'],
            'reference_no' => $load['reference_no'],
            'amount' => $load['amount'],
            'load_date' => $load['load_date'],
            'loaded_by_role' => $load['loaded_by_role'],
            'loaded_by_display' => $load['loaded_by_display']
            // Removed: 'collector_name'
        ]
    ]);
    exit();
}

// =============================================
// FARE PAYMENT
// =============================================
if ($type === 'fare') {
    $stmt = $pdo->prepare("
        SELECT t.*,
               CONCAT(p.first_name, ' ', 
                      IF(p.middle_name IS NOT NULL AND p.middle_name != '', CONCAT(p.middle_name, ' '), ''), 
                      p.last_name) as passenger_name,
               c.employee_number,
               CONCAT(d.origin, ' → ', d.destination) as route_name,
               dt.name as discount_type
        FROM transactions t
        JOIN passengers p ON t.passenger_id = p.id
        JOIN collectors c ON t.collector_id = c.id
        JOIN destinations d ON t.destination_id = d.id
        LEFT JOIN discount_types dt ON t.discount_percentage = dt.percentage
        WHERE t.id = ? AND t.passenger_id = ?
    ");
    $stmt->execute([$id, $passenger_id]);
    $fare = $stmt->fetch();

    if (!$fare) {
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'type' => 'fare',
        'data' => [
            'id' => $fare['id'],
            'receipt_no' => $fare['receipt_no'],
            'route_name' => $fare['route_name'],
            'base_fare' => $fare['base_fare'],
            'discount_percentage' => $fare['discount_percentage'],
            'discount_amount' => $fare['discount_amount'],
            'net_payment' => $fare['net_payment'],
            'loyalty_points_earned' => $fare['loyalty_points_earned'],
            'balance_before' => $fare['balance_before'],
            'balance_after' => $fare['balance_after'],
            'transaction_date' => $fare['transaction_date'],
            'employee_number' => $fare['employee_number']
            // Removed: 'collector_name'
        ]
    ]);
    exit();
}

// =============================================
// REFUND
// =============================================
if ($type === 'refund') {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               t.receipt_no,
               CONCAT(d.origin, ' → ', d.destination) as route_name
        FROM refunds r
        JOIN transactions t ON r.transaction_id = t.id
        JOIN destinations d ON t.destination_id = d.id
        WHERE r.id = ? AND r.passenger_id = ?
    ");
    $stmt->execute([$id, $passenger_id]);
    $refund = $stmt->fetch();

    if (!$refund) {
        echo json_encode(['success' => false, 'message' => 'Refund not found']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'type' => 'refund',
        'data' => [
            'id' => $refund['id'],
            'receipt_no' => $refund['receipt_no'],
            'route_name' => $refund['route_name'],
            'amount' => $refund['amount'],
            'points_deducted' => $refund['points_deducted'],
            'reason' => $refund['reason'],
            'refunded_at' => $refund['refunded_at']
        ]
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid transaction type']);
?>