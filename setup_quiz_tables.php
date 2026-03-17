<?php
require 'db.php';

try {
    $sql_quiz_attempts = "
    CREATE TABLE IF NOT EXISTS quiz_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        quiz_id INT NOT NULL,
        score INT NOT NULL,
        attempt_number INT NOT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql_quiz_attempts);
    echo "Created quiz_attempts table.\n";

    $sql_student_credits = "
    CREATE TABLE IF NOT EXISTS student_credits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        quiz_id INT NOT NULL,
        points INT NOT NULL,
        earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
        UNIQUE KEY student_quiz (student_id, quiz_id)
    )";
    $pdo->exec($sql_student_credits);
    echo "Created student_credits table.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
