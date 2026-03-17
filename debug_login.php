<?php
// Enhanced Login Debugger
session_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Debugger</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .debug-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 10px 0; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        body { padding: 20px; font-family: monospace; }
    </style>
</head>
<body>
<div class='container'>
<h1>Admin Login Debugger</h1>";

// Include database
require 'db.php';

echo "<div class='debug-box'>
<h3>Current Session State</h3>
<pre>";
echo "admin_logged_in: " . (isset($_SESSION['admin_logged_in']) ? $_SESSION['admin_logged_in'] : 'NOT SET') . "\n";
echo "admin_id: " . (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'NOT SET') . "\n";
echo "admin_name: " . (isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'NOT SET') . "\n";
echo "Session ID: " . session_id() . "\n";
echo "</pre></div>";

// Test database
echo "<div class='debug-box'>
<h3>Database Connection</h3>";
try {
    $pdo->query("SELECT 1");
    echo "<p class='success'>✓ Database connection OK</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Database error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Check tables
echo "<div class='debug-box'>
<h3>Database Tables</h3>";
try {
    $tables = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='life_skills_coaching'")->fetchAll();
    echo "<p>" . count($tables) . " tables found:</p><pre>";
    foreach ($tables as $table) {
        echo $table['TABLE_NAME'] . "\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Check admins table
echo "<div class='debug-box'>
<h3>Admins Table Analysis</h3>";
try {
    $count = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    echo "<p class='success'>✓ Admins table exists</p>";
    echo "<p>Total records: <strong>$count</strong></p>";
    
    if ($count > 0) {
        echo "<h4>Admin Records:</h4><pre>";
        $admins = $pdo->query("SELECT id, name, email, created_at FROM admins")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($admins as $admin) {
            echo "ID: {$admin['id']} | Name: {$admin['name']} | Email: {$admin['email']}\n";
        }
        echo "</pre>";
    } else {
        echo "<p class='warning'>⚠ No admin records found!</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p class='warning'>To fix: Visit <a href='setup_admin_table.php'>setup_admin_table.php</a></p>";
}
echo "</div>";

// Test login credentials
echo "<div class='debug-box'>
<h3>Test Credentials</h3>";
$test_email = 'admin@example.com';
$test_pass = 'admin123';
echo "<p>Testing: email='$test_email', password='$test_pass'</p>";

try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$test_email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p class='success'>✓ Admin found in database</p>";
        echo "<pre>ID: {$admin['id']}\nName: {$admin['name']}\nPassword hash: " . substr($admin['password'], 0, 30) . "...\n</pre>";
        
        if ($test_pass === $admin['password']) {
            echo "<p class='success'>✓ Password verification PASSED</p>";
        } else {
            echo "<p class='error'>✗ Password verification FAILED</p>";
            echo "<p class='warning'>The password '$test_pass' does not match the stored value.</p>";
        }
    } else {
        echo "<p class='error'>✗ Admin not found with email: $test_email</p>";
        echo "<p class='warning'>Create an admin account first via setup_admin_table.php</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test the login flow
echo "<div class='debug-box'>
<h3>Manual Login Test Form</h3>";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_login'])) {
    echo "<h4>Login Attempt Result:</h4>";
    $role = $_POST['role'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "<pre>Role: $role\nEmail: $username\nPassword: " . str_repeat('*', strlen($password)) . "\n</pre>";
    
    if ($role === 'Admin') {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && $password === $admin['password']) {
            echo "<p class='success'>✓ Login successful! (Session would be set here)</p>";
        } else {
            echo "<p class='error'>✗ Login failed - Invalid credentials</p>";
        }
    }
}

echo "
<form method='POST' class='mt-3' style='background:#fff3cd; padding:15px; border-radius:5px;'>
    <h5>Quick Login Test:</h5>
    <div class='mb-2'>
        <select class='form-select' name='role' required>
            <option value='Admin'>Admin</option>
        </select>
    </div>
    <div class='mb-2'>
        <input type='text' class='form-control' name='username' placeholder='Email' value='admin@example.com'>
    </div>
    <div class='mb-2'>
        <input type='password' class='form-control' name='password' placeholder='Password' value='admin123'>
    </div>
    <button type='submit' name='test_login' class='btn btn-primary' value='1'>Test Login</button>
</form>
</div>";

echo "</div></body></html>";
?>
