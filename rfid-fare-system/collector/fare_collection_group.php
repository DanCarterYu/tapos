<?php
// collector/fare_collection_group.php
// Process Group Payment (Multiple Passengers)

require_once 'session_config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if logged in as collector
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'collector') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$collector_id = $_SESSION['user_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$rfid_uid = $input['rfid_uid'] ?? '';
$passengers = $input['passengers'] ?? [];
$total_fare = $input['total_fare'] ?? 0;
$card_holder_boarding = $input['card_holder_boarding'] ?? true;
$card_holder_type = $input['card_holder_type'] ?? 4;
$trip_id = $input['trip_id'] ?? 0;

// Validate
if (empty($rfid_uid)) {
    echo json_encode(['success' => false, 'message' => 'Please tap RFID card']);
    exit();
}

if (count($passengers) === 0) {
    echo json_encode(['success' => false, 'message' => 'No passengers added']);
    exit();
}

if ($trip_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid trip']);
    exit();
}

// Get passenger info (card holder)
$stmt = $pdo->prepare("SELECT *, 
                       CONCAT(first_name, ' ', 
                              IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(middle_name, ' '), ''), 
                              last_name) as full_name 
                       FROM passengers WHERE rfid_uid = ? AND status = 'active'");
$stmt->execute([$rfid_uid]);
$passenger = $stmt->fetch();

if (!$passenger) {
    echo json_encode(['success' => false, 'message' => 'Invalid or unregistered RFID card']);
    exit();
}

// =============================================
// CHECK SEAT AVAILABILITY FOR GROUP
// =============================================
$stmt = $pdo->prepare("
    SELECT aircon_capacity, non_aircon_capacity, 
           aircon_count, non_aircon_count 
    FROM trips WHERE id = ?
");
$stmt->execute([$trip_id]);
$seat_data = $stmt->fetch();

$aircon_capacity = $seat_data['aircon_capacity'] ?? 0;
$non_aircon_capacity = $seat_data['non_aircon_capacity'] ?? 0;
$aircon_count = $seat_data['aircon_count'] ?? 0;
$non_aircon_count = $seat_data['non_aircon_count'] ?? 0;

$aircon_remaining = $aircon_capacity - $aircon_count;
$non_aircon_remaining = $non_aircon_capacity - $non_aircon_count;

// Count how many passengers want each section
$aircon_needed = 0;
$non_aircon_needed = 0;

foreach ($passengers as $p) {
    if (($p['section'] ?? 'aircon') == 'aircon') {
        $aircon_needed++;
    } else {
        $non_aircon_needed++;
    }
}

// Check if enough seats available
if ($aircon_needed > $aircon_remaining) {
    echo json_encode([
        'success' => false, 
        'message' => 'Not enough Aircon seats available. Available: ' . $aircon_remaining . ', Needed: ' . $aircon_needed
    ]);
    exit();
}

if ($non_aircon_needed > $non_aircon_remaining) {
    echo json_encode([
        'success' => false, 
        'message' => 'Not enough Non-Aircon seats available. Available: ' . $non_aircon_remaining . ', Needed: ' . $non_aircon_needed
    ]);
    exit();
}

// Check if both sections are full
if ($aircon_remaining <= 0 && $non_aircon_remaining <= 0) {
    echo json_encode(['success' => false, 'message' => 'Vessel is fully booked. No seats available.']);
    exit();
}

// =============================================
// CHECK IF CARD HOLDER ALREADY PAID FOR THIS TRIP
// =============================================
if ($card_holder_boarding) {
    $stmt = $pdo->prepare("SELECT id FROM transactions WHERE passenger_id = ? AND trip_id = ?");
    $stmt->execute([$passenger['id'], $trip_id]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false, 
            'message' => 'This passenger has already paid for this trip.'
        ]);
        exit();
    }
}

// Check balance
if ($passenger['balance'] < $total_fare) {
    echo json_encode([
        'success' => false, 
        'message' => 'Insufficient balance. Current balance: ₱' . number_format($passenger['balance'], 2) . ' needed: ₱' . number_format($total_fare, 2)
    ]);
    exit();
}

// Calculate total points (1 point per passenger)
$points_earned = count($passengers);

// Calculate new balance
$new_balance = $passenger['balance'] - $total_fare;
$new_points = $passenger['loyalty_points'] + $points_earned;

// Generate receipt number
$receipt_no = 'GRP-' . date('YmdHis') . rand(100, 999);

try {
    $pdo->beginTransaction();

    // Get destination_id from trip_schedules
    $stmt = $pdo->prepare("
        SELECT ts.destination_id 
        FROM trips t
        JOIN trip_schedules ts ON t.schedule_id = ts.id
        WHERE t.id = ?
    ");
    $stmt->execute([$trip_id]);
    $trip_result = $stmt->fetch();
    $destination_id = $trip_result['destination_id'] ?? 0;

    if ($destination_id == 0) {
        throw new Exception('Destination not found for this trip');
    }

    // Fetch destination fares for discount computation
    $stmt = $pdo->prepare("SELECT aircon_fare, non_aircon_fare FROM destinations WHERE id = ?");
    $stmt->execute([$destination_id]);
    $destination_fares = $stmt->fetch();
    $aircon_base = $destination_fares['aircon_fare'] ?? 0;
    $non_aircon_base = $destination_fares['non_aircon_fare'] ?? 0;

    // Update passenger balance and points
    $stmt = $pdo->prepare("UPDATE passengers SET balance = ?, loyalty_points = ? WHERE id = ?");
    $stmt->execute([$new_balance, $new_points, $passenger['id']]);

    // Record single transaction for the group
    $stmt = $pdo->prepare("INSERT INTO transactions 
                           (receipt_no, passenger_id, collector_id, destination_id, trip_id, is_group, passenger_count,
                            base_fare, discount_percentage, discount_amount, net_payment, 
                            balance_before, balance_after, loyalty_points_earned) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $receipt_no,
        $passenger['id'],
        $collector_id,
        $destination_id,
        $trip_id,
        '1',
        count($passengers),
        0,
        0,
        0,
        $total_fare,
        $passenger['balance'],
        $new_balance,
        $points_earned
    ]);

    // Get the transaction ID by looking up its unique receipt_no
    $stmt = $pdo->prepare("SELECT id FROM transactions WHERE receipt_no = ?");
    $stmt->execute([$receipt_no]);
    $txn_row = $stmt->fetch();
    $transaction_id = $txn_row['id'] ?? 0;

    // Update passenger count on trip
    $stmt = $pdo->prepare("UPDATE trips SET passenger_count = passenger_count + ? WHERE id = ?");
    $stmt->execute([count($passengers), $trip_id]);

    // =============================================
    // UPDATE SEAT COUNTS
    // =============================================
    if ($aircon_needed > 0) {
        $stmt = $pdo->prepare("UPDATE trips SET aircon_count = aircon_count + ? WHERE id = ?");
        $stmt->execute([$aircon_needed, $trip_id]);
    }
    if ($non_aircon_needed > 0) {
        $stmt = $pdo->prepare("UPDATE trips SET non_aircon_count = non_aircon_count + ? WHERE id = ?");
        $stmt->execute([$non_aircon_needed, $trip_id]);
    }

    // =============================================
    // ADD ALL PASSENGERS TO MANIFEST
    // =============================================

    // 1. Add the card holder if they are boarding
    if ($card_holder_boarding) {
        $age = null;
        if (!empty($passenger['date_of_birth'])) {
            $birthDate = new DateTime($passenger['date_of_birth']);
            $today = new DateTime('today');
            $age = $birthDate->diff($today)->y;
        }

        $stmt = $pdo->prepare("SELECT name FROM discount_types WHERE id = ?");
        $stmt->execute([$card_holder_type]);
        $type = $stmt->fetch();
        $type_name = $type['name'] ?? 'regular';

        // Get the card holder's section from the passengers array
        $card_holder_section = 'aircon';
        foreach ($passengers as $p) {
            if (isset($p['is_card_holder']) && $p['is_card_holder'] === true) {
                $card_holder_section = $p['section'] ?? 'aircon';
                break;
            }
        }

                // Get card holder's fare and section from the passengers array
        $card_holder_fare = 0;
        foreach ($passengers as $p) {
            if (isset($p['is_card_holder']) && $p['is_card_holder'] === true) {
                $card_holder_fare = $p['fare'] ?? 0;
                break;
            }
        }

        // Compute card holder discount
        $card_holder_base = ($card_holder_section == 'aircon') ? $aircon_base : $non_aircon_base;
        $card_holder_discount = $card_holder_base - $card_holder_fare;

        $stmt = $pdo->prepare("INSERT INTO manifests 
                               (transaction_id, trip_id, passenger_id, passenger_name, age, sex, address, passenger_type, section, fare_paid, discount_amount) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $transaction_id,
            $trip_id,
            $passenger['id'],
            $passenger['full_name'],
            $age,
            $passenger['sex'],
            $passenger['address'] ?? '',
            $type_name,
            $card_holder_section,
            $card_holder_fare,
            $card_holder_discount
        ]);
    }

    // 2. Add all group passengers to manifest
    foreach ($passengers as $group_passenger) {
        // Skip the card holder (already added)
        if (isset($group_passenger['is_card_holder']) && $group_passenger['is_card_holder'] === true) {
            continue;
        }
        
        // Compute this passenger's discount
        $pax_section = $group_passenger['section'] ?? 'aircon';
        $pax_base = ($pax_section == 'aircon') ? $aircon_base : $non_aircon_base;
        $pax_fare = $group_passenger['fare'] ?? 0;
        $pax_discount = $pax_base - $pax_fare;
        
        $stmt = $pdo->prepare("INSERT INTO manifests 
                               (transaction_id, trip_id, passenger_id, passenger_name, age, sex, address, passenger_type, section, fare_paid, discount_amount) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $transaction_id,
            $trip_id,
            0,
            $group_passenger['name'],
            $group_passenger['age'],
            $group_passenger['sex'],
            $group_passenger['address'],
            $group_passenger['type_name'] ?? 'regular',
            $pax_section,
            $pax_fare,
            $pax_discount
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Group payment successful! ' . count($passengers) . ' passengers boarded.',
        'receipt_no' => $receipt_no,
        'transaction_id' => $transaction_id, 
        'passenger_count' => count($passengers),
        'total_fare' => $total_fare,
        'points_earned' => $points_earned
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
?>