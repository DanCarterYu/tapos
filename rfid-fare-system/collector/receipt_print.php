<?php
// collector/receipt_print.php
// Returns a fully rendered receipt HTML for a given transaction_id
// Format: 58mm thermal printer (monochrome)

require_once 'session_config.php';
require_once '../config/database.php';
require_once '../includes/receipt_template.php';

// Auth: must be collector
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'collector') {
    http_response_code(403);
    echo 'Unauthorized';
    exit();
}

$transaction_id = intval($_GET['transaction_id'] ?? 0);

if ($transaction_id <= 0) {
    echo 'Invalid transaction';
    exit();
}

// Get the transaction (must belong to this collector)
$stmt = $pdo->prepare("
    SELECT t.*, 
           CONCAT(d.origin, ' → ', d.destination) as route,
           c.employee_number
    FROM transactions t
    JOIN destinations d ON t.destination_id = d.id
    JOIN collectors c ON t.collector_id = c.id
    WHERE t.id = ? AND t.collector_id = ?
");
$stmt->execute([$transaction_id, $_SESSION['user_id']]);
$transaction = $stmt->fetch();

if (!$transaction) {
    echo 'Transaction not found';
    exit();
}

// Get the manifest rows for this transaction (each = one passenger's receipt)
$stmt = $pdo->prepare("
    SELECT m.*, dt.percentage as discount_percent
    FROM manifests m
    LEFT JOIN discount_types dt ON dt.name = m.passenger_type
    WHERE m.transaction_id = ?
    ORDER BY m.id ASC
");
$stmt->execute([$transaction_id]);
$passengers = $stmt->fetchAll();

// Fallback if no manifest rows found
if (count($passengers) == 0) {
    $passengers = [[
        'passenger_name'   => 'Passenger',
        'section'          => 'aircon',
        'passenger_type'   => 'regular',
        'fare_paid'        => $transaction['net_payment'],
        'discount_amount'  => $transaction['discount_amount'],
        'discount_percent' => $transaction['discount_percentage']
    ]];
}

// If a specific passenger index was requested, show only that one
if (isset($_GET['passenger_index'])) {
    $idx = intval($_GET['passenger_index']);
    if (isset($passengers[$idx])) {
        $passengers = [$passengers[$idx]];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?php echo htmlspecialchars($transaction['receipt_no']); ?></title>
    <style>
        /* ============================================= */
        /* 58mm thermal printer page setup                */
        /* ============================================= */
        @page {
            size: 58mm auto;
            margin: 0;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Courier New', Courier, monospace;
            padding: 10px; 
            background: #f5f5f5; 
            margin: 0;
        }
        
        .receipt-wrapper {
            page-break-after: always;
            padding: 5mm 0;
        }
        .receipt-wrapper:last-child {
            page-break-after: auto;
        }
        
        /* ============================================= */
        /* Print styles                                   */
        /* ============================================= */
        @media print {
            body { 
                background: white; 
                padding: 0; 
                margin: 0;
            }
            .no-print { 
                display: none; 
            }
            .receipt-wrapper {
                padding: 2mm 0;
            }
        }
        
        /* ============================================= */
        /* On-screen print button                         */
        /* ============================================= */
        .no-print {
            text-align: center;
            margin-bottom: 15px;
        }
        .no-print button {
            background: #1e40af;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        .no-print button:hover {
            background: #1e3a8a;
        }
        .no-print p {
            font-size: 12px;
            color: #666;
            margin: 8px 0 0 0;
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>

<!-- Print Button (hidden when printing) -->
<div class="no-print">
    <button onclick="window.print();">🖨️ Print <?php echo count($passengers) > 1 ? 'All Receipts' : 'Receipt'; ?></button>
    <p>
        <?php echo count($passengers); ?> receipt<?php echo count($passengers) == 1 ? '' : 's'; ?>
        <?php if (count($passengers) > 1): ?> — one per passenger<?php endif; ?>
    </p>
</div>

<!-- Receipts -->
<?php foreach ($passengers as $p): 
    // Compute base fare = fare paid + discount amount
    $base_fare = floatval($p['fare_paid']) + floatval($p['discount_amount']);
    
    echo '<div class="receipt-wrapper">';
    echo renderReceipt([
        'receipt_no'       => $transaction['receipt_no'],
        'transaction_date' => $transaction['transaction_date'],
        'passenger_name'   => $p['passenger_name'],
        'route'            => $transaction['route'],
        'section'          => $p['section'],
        'base_fare'        => $base_fare,
        'discount_amount'  => floatval($p['discount_amount']),
        'discount_name'    => $p['passenger_type'] ?? 'regular',
        'discount_percent' => floatval($p['discount_percent'] ?? 0),
        'total_amount'     => floatval($p['fare_paid']),
        'points_earned'    => 1,
        'employee_number'  => $transaction['employee_number']
    ]);
    echo '</div>';
endforeach; ?>

</body>
</html>