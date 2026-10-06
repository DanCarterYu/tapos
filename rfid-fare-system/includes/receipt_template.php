<?php
// includes/receipt_template.php
// Shared Receipt Template — 58mm Thermal Format (Monochrome)
//
// USAGE:
//   require_once __DIR__ . '/receipt_template.php';
//   echo renderReceipt($data);
//
// $data keys:
//   - receipt_no       (string)
//   - transaction_date (string, MySQL datetime)
//   - passenger_name   (string)
//   - route            (string)
//   - section          (string) 'aircon' or 'non_aircon'
//   - base_fare        (float)
//   - discount_amount  (float)
//   - discount_name    (string) e.g. 'student'
//   - discount_percent (float)
//   - total_amount     (float)
//   - points_earned    (int)
//   - employee_number  (string, optional)

function renderReceipt($data) {
    // Defaults
    $receipt_no       = $data['receipt_no']       ?? '-';
    $transaction_date = $data['transaction_date'] ?? date('Y-m-d H:i:s');
    $passenger_name   = $data['passenger_name']   ?? '-';
    $route            = $data['route']            ?? '-';
    $section          = $data['section']          ?? '-';
    $base_fare        = floatval($data['base_fare']       ?? 0);
    $discount_amount  = floatval($data['discount_amount'] ?? 0);
    $discount_name    = $data['discount_name']    ?? 'regular';
    $discount_percent = floatval($data['discount_percent'] ?? 0);
    $total_amount     = floatval($data['total_amount']    ?? 0);
    $points_earned    = intval($data['points_earned']     ?? 0);
    $employee_number  = $data['employee_number']  ?? '';
    
    // Format section: 'aircon' → 'AIRCON', 'non_aircon' → 'NON-AIRCON'
    $section_label = strtoupper(str_replace('_', '-', $section));
    
    // Format date
    $date_display = date('M d, Y h:i A', strtotime($transaction_date));
    
    // Build HTML (inline-styled for print reliability)
    ob_start();
    ?>
    <div class="receipt" style="width: 48mm; margin: 0 auto; padding: 2mm 0; font-family: 'Courier New', Courier, monospace; font-size: 10px; line-height: 1.35; color: #000; background: #fff;">

        <!-- Header -->
        <div style="text-align: center; margin-bottom: 6px;">
            <div style="font-weight: bold; font-size: 12px; letter-spacing: 1px;">VINCE GABRIEL LINERS</div>
            <div style="font-size: 9px;">Automated Fare Collection</div>
            <div style="font-size: 9px;">Surigao City, Philippines</div>
        </div>

        <!-- Major Divider -->
        <div style="text-align: center; font-size: 10px; margin: 4px 0;">================================</div>

        <!-- Receipt Info -->
        <div style="font-size: 10px;">
            <div style="display: flex; justify-content: space-between;">
                <span>Receipt No:</span>
                <span style="font-weight: bold;"><?php echo htmlspecialchars($receipt_no); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Date:</span>
                <span><?php echo $date_display; ?></span>
            </div>
        </div>

        <!-- Section Divider -->
        <div style="text-align: center; font-size: 10px; margin: 4px 0;">--------------------------------</div>

        <!-- Passenger Details -->
        <div style="font-size: 10px;">
            <div style="font-weight: bold; margin-bottom: 2px;">PASSENGER</div>
            <div style="display: flex; justify-content: space-between;">
                <span>Name:</span>
                <span style="font-weight: bold; text-align: right;"><?php echo htmlspecialchars($passenger_name); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Route:</span>
                <span style="text-align: right;"><?php echo htmlspecialchars($route); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Section:</span>
                <span><?php echo htmlspecialchars($section_label); ?></span>
            </div>
        </div>

        <!-- Section Divider -->
        <div style="text-align: center; font-size: 10px; margin: 4px 0;">--------------------------------</div>

        <!-- Fare Breakdown -->
        <div style="font-size: 10px;">
            <div style="font-weight: bold; margin-bottom: 2px;">FARE BREAKDOWN</div>
            <div style="display: flex; justify-content: space-between;">
                <span>Base Fare:</span>
                <span>P<?php echo number_format($base_fare, 2); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Discount<?php echo $discount_percent > 0 ? ' (' . ucfirst($discount_name) . ' - ' . $discount_percent . '%)' : ''; ?>:</span>
                <span>-P<?php echo number_format($discount_amount, 2); ?></span>
            </div>
            <div style="text-align: right; font-size: 10px;">--------</div>
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 11px; margin-top: 2px;">
                <span>TOTAL:</span>
                <span>P<?php echo number_format($total_amount, 2); ?></span>
            </div>
        </div>

        <!-- Points -->
        <div style="font-size: 10px; margin-top: 4px;">
            <div style="display: flex; justify-content: space-between;">
                <span>Points Earned:</span>
                <span style="font-weight: bold;">+<?php echo $points_earned; ?> pt<?php echo $points_earned == 1 ? '' : 's'; ?></span>
            </div>
        </div>

        <?php if (!empty($employee_number)): ?>
        <!-- Section Divider -->
        <div style="text-align: center; font-size: 10px; margin: 4px 0;">--------------------------------</div>

        <!-- Processed By -->
        <div style="font-size: 10px;">
            <div style="display: flex; justify-content: space-between;">
                <span>Processed by:</span>
                <span><?php echo htmlspecialchars($employee_number); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Major Divider -->
        <div style="text-align: center; font-size: 10px; margin: 4px 0;">================================</div>

        <!-- Footer -->
        <div style="text-align: center; font-size: 9px;">
            <div>Thank you for riding with us!</div>
            <div>Keep this receipt for your records</div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>