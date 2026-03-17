<?php
require 'db.php';

try {
    $sql_admins = "
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        profile_image VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_admins);
    echo "Created admins table.\n";

    // Insert default admin if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = 'admin@example.com'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $name = "Super Admin";
        $email = "admin@example.com";
        // Store password as plain text (no hashing)
        $password = "admin123";
        
        $insert_stmt = $pdo->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
        $insert_stmt->execute([$name, $email, $password]);
        echo "Inserted default admin (admin@example.com / admin123).\n";
    } else {
        echo "Default admin already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
