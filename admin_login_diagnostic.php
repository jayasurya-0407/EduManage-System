<?php
// Comprehensive Admin Login Diagnostic
echo "<h2>Admin Login Diagnostic Report</h2>";
echo "<pre>";

// 1. Check Database Connection
echo "=== 1. DATABASE CONNECTION ===\n";
try {
    require 'db.php';
    echo "✓ Database connection successful\n";
    echo "  Host: 127.0.0.1:3307\n";
    echo "  Database: life_skills_coaching\n";
} catch (Exception $e) {
    echo "✗ Database connection FAILED: " . $e->getMessage() . "\n";
    exit;
}

// 2. Check if admins table exists
echo "\n=== 2. ADMINS TABLE ===\n";
try {
    $result = $pdo->query("SHOW TABLES LIKE 'admins'")->fetch();
    if ($result) {
        echo "✓ 'admins' table EXISTS\n";
        
        // Check table structure
        $columns = $pdo->query("DESCRIBE admins")->fetchAll();
        echo "  Table columns:\n";
        foreach ($columns as $col) {
            echo "    - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    } else {
        echo "✗ 'admins' table DOES NOT EXIST\n";
        echo "  Action: Run setup_admin_table.php or import database.sql\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking table: " . $e->getMessage() . "\n";
}

// 3. Check admin records
echo "\n=== 3. ADMIN RECORDS ===\n";
try {
    $count = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    echo "Total admins in database: $count\n";
    
    if ($count > 0) {
        $admins = $pdo->query("SELECT id, name, email, created_at FROM admins")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($admins as $admin) {
            echo "  - ID: " . $admin['id'] . ", Name: " . $admin['name'] . ", Email: " . $admin['email'] . ", Created: " . $admin['created_at'] . "\n";
        }
    } else {
        echo "✗ NO ADMIN RECORDS FOUND\n";
        echo "  Action: Add admin record or run setup_admin_table.php\n";
    }
} catch (Exception $e) {
    echo "✗ Error querying admins: " . $e->getMessage() . "\n";
}

// 4. Test default admin login
echo "\n=== 4. TEST DEFAULT ADMIN LOGIN ===\n";
$test_email = 'admin@example.com';
$test_password = 'admin123';

try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$test_email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✓ Admin with email '$test_email' found\n";
        echo "  Password in DB: " . substr($admin['password'], 0, 20) . "...\n";
        
        if ($test_password === $admin['password']) {
            echo "✓ Password verification successful with '$test_password'\n";
        } else {
            echo "✗ Password verification FAILED with '$test_password'\n";
            echo "  Action: Check if password value is correct\n";
        }
    } else {
        echo "✗ Admin with email '$test_email' NOT FOUND\n";
        echo "  Action: Create this admin or update test email\n";
    }
} catch (Exception $e) {
    echo "✗ Error testing login: " . $e->getMessage() . "\n";
}

// 5. Check other related tables
echo "\n=== 5. OTHER TABLES ===\n";
$tables = ['courses', 'students', 'materials', 'quizzes', 'quiz_questions', 'quiz_options', 'quiz_results', 'quiz_attempts'];
foreach ($tables as $table) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        $status = $result ? "✓" : "✗";
        echo "$status $table\n";
    } catch (Exception $e) {
        echo "✗ Error checking $table\n";
    }
}

// 6. PHP Session configuration
echo "\n=== 6. SESSION CONFIGURATION ===\n";
echo "Session save path: " . session_save_path() . "\n";
echo "Session cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "Session cookie_httponly: " . (ini_get('session.cookie_httponly') ? "Yes" : "No") . "\n";

// 7. File permissions
echo "\n=== 7. FILE PERMISSIONS ===\n";
$critical_files = [
    'db.php',
    'login.php',
    'admin/header.php',
    'admin/dashboard.php'
];
foreach ($critical_files as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        $perms = substr(sprintf('%o', fileperms($full_path)), -4);
        echo "✓ $file (permissions: $perms)\n";
    } else {
        echo "✗ $file NOT FOUND\n";
    }
}

echo "</pre>";
echo "<hr>";
echo "<h3>Summary & Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>If 'admins' table doesn't exist:</strong> Visit <a href='setup_admin_table.php'>setup_admin_table.php</a> to create it</li>";
echo "<li><strong>If no admin records exist:</strong> Run setup_admin_table.php to insert default admin (admin@example.com / admin123)</li>";
echo "<li><strong>If password verification fails:</strong> The password hash in DB is invalid; re-create the admin</li>";
echo "<li><strong>If everything passes:</strong> Check browser console for JS errors and verify form submission method</li>";
echo "</ol>";
?>
