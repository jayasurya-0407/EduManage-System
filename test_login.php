<?php
require 'db.php';
$username = 'admin@example.com';
$password = 'admin123';

$stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "Found admin!\n";
    echo "Password in DB: " . $admin['password'] . "\n";
    if ($password === $admin['password']) {
        echo "Password verification SUCCESS!\n";
    } else {
        echo "Password verification FAILED!\n";
    }
} else {
    echo "NO ADMIN FOUND WITH EMAIL: $username\n";
}
?>
