<?php
// fix_password.php - Fix passenger ID 1 password
// DELETE AFTER USE!

require_once 'config/database.php';

$rfid_uid = '3484554904';
$hashed = password_hash($rfid_uid, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE passengers SET password = ? WHERE id = 1");
$stmt->execute([$hashed]);

echo "<h2 style='color:green;'>✅ Password fixed for Passenger ID 1</h2>";
echo "<p>RFID UID: <strong>3484554904</strong></p>";
echo "<p>Default Password: <strong>3484554904</strong></p>";
echo "<p>New Hash: <code>" . $hashed . "</code></p>";
echo "<hr>";
echo "<h3>Testing the fix...</h3>";

// Verify the fix
$stmt = $pdo->prepare("SELECT password FROM passengers WHERE id = 1");
$stmt->execute();
$pass = $stmt->fetch();

if (password_verify('3484554904', $pass['password'])) {
    echo "<p style='color:green; font-weight:bold;'>✅ Password verification SUCCESS! You can now login.</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>❌ Still not working. Please try again.</p>";
}

echo "<p style='color:red; font-weight:bold;'>⚠️ DELETE THIS FILE AFTER USE!</p>";
?>