<?php
require_once 'db.php';

try {
    // 1. Create student_courses table
    $createTableQuery = "
    CREATE TABLE IF NOT EXISTS student_courses (
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (student_id, course_id),
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    );";
    $pdo->exec($createTableQuery);
    echo "student_courses table created or already exists.\n";

    // 2. Check if course_id exists in students table
    $checkColumn = $pdo->query("SHOW COLUMNS FROM students LIKE 'course_id'")->rowCount();
    
    if ($checkColumn > 0) {
        // Migrate existing datad
        $migrateQuery = "
        INSERT IGNORE into student_courses (student_id, course_id)
        SELECT student_id, course_id FROM students WHERE course_id IS NOT NULL;
        ";
        $pdo->exec($migrateQuery);
        echo "Data migrated successfully.\n";

        // Drop foreign key if it exists (need to find its name if auto-generated, but let's try dropping the column and foreign key)
        // MariaDB/MySQL foreign key format needs its proper symbol.
        // Let's first try to find the foreign key name
        $fkQuery = "
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = 'life_skills_coaching' 
          AND TABLE_NAME = 'students' 
          AND COLUMN_NAME = 'course_id' 
          AND REFERENCED_TABLE_NAME IS NOT NULL;
        ";
        $stmt = $pdo->query($fkQuery);
        $fkName = $stmt->fetchColumn();

        if ($fkName) {
            $pdo->exec("ALTER TABLE students DROP FOREIGN KEY `$fkName`");
            echo "Foreign key `$fkName` dropped.\n";
        }

        // Drop the column
        $pdo->exec("ALTER TABLE students DROP COLUMN course_id");
        echo "Column course_id dropped from students table.\n";
    } else {
        echo "Column course_id already dropped from students table.\n";
    }

    echo "Database update completed successfully.";

} catch (PDOException $e) {
    die("Database update failed: " . $e->getMessage());
}
?>
