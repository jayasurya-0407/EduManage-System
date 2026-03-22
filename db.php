<?php

// ══════════════════════════════════════════════════════
//  DATABASE CONFIGURATION
//  • Local  (XAMPP): use 127.0.0.1, port 3307, root / no password
//  • InfinityFree  : update the four values below with your
//    InfinityFree control-panel credentials and remove the
//    ";port=3307" part (InfinityFree uses the default port 3306)
// ══════════════════════════════════════════════════════

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'life_skills_coaching');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3307');   // Change to 3306 (or remove) on InfinityFree

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ── Create tables only if they don't already exist ──────────────────────

    $pdo->exec("CREATE TABLE IF NOT EXISTS student_courses (
        student_id INT NOT NULL,
        course_id  INT NOT NULL,
        enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (student_id, course_id),
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
        FOREIGN KEY (course_id)  REFERENCES courses(id)          ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        student_id  INT NOT NULL,
        subject     VARCHAR(255) NOT NULL,
        message     TEXT NOT NULL,
        admin_reply TEXT NULL,
        replied_at  DATETIME NULL,
        status      ENUM('open','closed') NOT NULL DEFAULT 'open',
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        sender     ENUM('student','admin') NOT NULL,
        message    TEXT NOT NULL,
        is_read    TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS broadcast_messages (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        message    TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>