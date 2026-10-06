<?php
// hash_existing_passwords.php
// ONE-TIME SCRIPT: Hash all existing plain text passwords

require_once 'config/database.php';

echo "<h1>Hashing Existing Passwords</h1>";

// Hash collectors passwords
$stmt = $pdo->query("SELECT id, password FROM collectors");
$collectors = $stmt->fetchAll();

echo "<h3>Collectors:</h3>";
foreach ($collectors as $collector) {
    // Check if password is already hashed (starts with $2y$)
    if (strpos($collector['password'], '$2y$') !== 0) {
        $hashed = password_hash($collector['password'], PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE collectors SET password = ? WHERE id = ?");
        $update->execute([$hashed, $collector['id']]);
        echo "Hashed collector ID: " . $collector['id'] . "<br>";
    } else {
        echo "Already hashed: " . $collector['id'] . "<br>";
    }
}

// Hash passengers passwords
$stmt = $pdo->query("SELECT id, password FROM passengers");
$passengers = $stmt->fetchAll();

echo "<h3>Passengers:</h3>";
foreach ($passengers as $passenger) {
    if (strpos($passenger['password'], '$2y$') !== 0) {
        $hashed = password_hash($passenger['password'], PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE passengers SET password = ? WHERE id = ?");
        $update->execute([$hashed, $passenger['id']]);
        echo "Hashed passenger ID: " . $passenger['id'] . "<br>";
    } else {
        echo "Already hashed: " . $passenger['id'] . "<br>";
    }
}

// Hash admin passwords
$stmt = $pdo->query("SELECT id, password FROM admin");
$admins = $stmt->fetchAll();

echo "<h3>Admin:</h3>";
foreach ($admins as $admin) {
    if (strpos($admin['password'], '$2y$') !== 0) {
        $hashed = password_hash($admin['password'], PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $update->execute([$hashed, $admin['id']]);
        echo "Hashed admin ID: " . $admin['id'] . "<br>";
    } else {
        echo "Already hashed: " . $admin['id'] . "<br>";
    }
}

echo "<h2 style='color:green;'>✅ All passwords hashed successfully!</h2>";
echo "<p style='color:red;'><strong>IMPORTANT:</strong> Delete this file after running to prevent security issues.</p>";
?>