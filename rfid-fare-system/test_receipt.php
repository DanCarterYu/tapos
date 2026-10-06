<?php
require_once 'includes/receipt_template.php';
echo renderReceipt([
    'receipt_no' => 'TEST-12345',
    'transaction_date' => date('Y-m-d H:i:s'),
    'passenger_name' => 'JUAN DELA CRUZ',
    'route' => 'SURIGAO CITY → DINAGAT',
    'section' => 'aircon',
    'base_fare' => 480,
    'discount_amount' => 96,
    'discount_name' => 'student',
    'discount_percent' => 20,
    'total_amount' => 384,
    'points_earned' => 1,
    'employee_number' => 'VGL001'
]);