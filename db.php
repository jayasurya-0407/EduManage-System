<?php

$host = "127.0.0.1";
$db   = "life_skills_coaching";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=life_skills_coaching;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Auto-create student_courses table if not exists (M:N relationship)
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_courses (
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (student_id, course_id),
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )");

    // If students still have old course_id column, migrate and drop it
    $col = $pdo->query("SHOW COLUMNS FROM students LIKE 'course_id'")->rowCount();
    if ($col > 0) {
        $pdo->exec("INSERT IGNORE INTO student_courses (student_id, course_id)
                    SELECT student_id, course_id FROM students WHERE course_id IS NOT NULL");
        $fkRow = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA='life_skills_coaching' AND TABLE_NAME='students'
            AND COLUMN_NAME='course_id' AND REFERENCED_TABLE_NAME IS NOT NULL")->fetchColumn();
        if ($fkRow) {
            $pdo->exec("ALTER TABLE students DROP FOREIGN KEY `$fkRow`");
        }
        $pdo->exec("ALTER TABLE students DROP COLUMN course_id");
    }

    // ── Feedback table ──
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

    // ── Chat messages table ──
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        sender     ENUM('student','admin') NOT NULL,
        message    TEXT NOT NULL,
        is_read    TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
    )");

    // ── Broadcast / announcement messages ──
    $pdo->exec("CREATE TABLE IF NOT EXISTS broadcast_messages (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        message    TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>