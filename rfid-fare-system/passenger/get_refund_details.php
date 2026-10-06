<?php
// passenger/get_refund_details.php
// AJAX endpoint for fetching refund details

require_once 'session_config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if logged in as passenger
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$passenger_id = $_SESSION['user_id'];
$refund_id = intval($_GET['id'] ?? 0);

if ($refund_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid refund ID']);
    exit();
}

// Get refund details - ensure it belongs to this passenger
$stmt = $pdo->prepare("
    SELECT r.*, 
           t.receipt_no,
           CONCAT(d.origin, ' → ', d.destination) as route_name
    FROM refunds r
    JOIN transactions t ON r.transaction_id = t.id
    JOIN destinations d ON t.destination_id = d.id
    WHERE r.id = ? AND r.passenger_id = ?
");
$stmt->execute([$refund_id, $passenger_id]);
$refund = $stmt->fetch();

if (!$refund) {
    echo json_encode(['success' => false, 'message' => 'Refund not found']);
    exit();
}

echo json_encode([
    'success' => true,
    'refund' => [
        'id' => $refund['id'],
        'receipt_no' => $refund['receipt_no'],
        'route_name' => $refund['route_name'],
        'amount' => $refund['amount'],
        'points_deducted' => $refund['points_deducted'],
        'reason' => $refund['reason'],
        'refunded_at' => $refund['refunded_at']
    ]
]);
?>