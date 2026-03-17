<?php
require_once 'header.php';

$student_id = $_SESSION['student_id'];
$course_id = $_SESSION['course_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['quiz_id']) && isset($_POST['answers'])) {
    $quiz_id = $_POST['quiz_id'];
    $answers = $_POST['answers']; // Associative array of question_id => selected option_id

    // Verify quiz exists
    $stmt = $pdo->prepare("SELECT title FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        echo "<div class='alert alert-danger m-4'>Quiz not found.</div>";
        require_once 'footer.php';
        exit;
    }

    $score = 0;
    $total_questions = count($answers);

    // Calculate score
    foreach ($answers as $q_id => $selected_opt_id) {
        $check_stmt = $pdo->prepare("SELECT is_correct FROM quiz_options WHERE id = ? AND question_id = ?");
        $check_stmt->execute([$selected_opt_id, $q_id]);
        $opt = $check_stmt->fetch();
        if ($opt && $opt['is_correct'] == 1) {
            $score++;
        }
    }

    // Insert result (original)
    try {
        $insert_stmt = $pdo->prepare("INSERT INTO quiz_results (student_id, quiz_id, score, total_questions) VALUES (?, ?, ?, ?)");
        $insert_stmt->execute([$student_id, $quiz_id, $score, $total_questions]);
    } catch(PDOException $e) {}

    $percentage = ($total_questions > 0) ? round(($score / $total_questions) * 100) : 0;
    
    // New logic: attempt tracking
    $attempt_number = 1;
    try {
        $attempt_stmt = $pdo->prepare("SELECT MAX(attempt_number) FROM quiz_attempts WHERE student_id = ? AND quiz_id = ?");
        $attempt_stmt->execute([$student_id, $quiz_id]);
        $max_attempt = $attempt_stmt->fetchColumn();
        if ($max_attempt) {
            $attempt_number = $max_attempt + 1;
        }
    } catch(PDOException $e) {}

    try {
        $ins_attempt = $pdo->prepare("INSERT INTO quiz_attempts (student_id, quiz_id, score, attempt_number) VALUES (?, ?, ?, ?)");
        $ins_attempt->execute([$student_id, $quiz_id, $score, $attempt_number]);
    } catch(PDOException $e) {}
    
    // New logic: credit points system
    $points = 2; // default < 60
    if ($percentage >= 80) {
        $points = 10;
    } elseif ($percentage >= 60) {
        $points = 5;
    }

    // Insert or update credit points (keep highest overall points)
    try {
        $cred_stmt = $pdo->prepare("INSERT INTO student_credits (student_id, quiz_id, points) VALUES (?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE points = GREATEST(points, VALUES(points))");
        $cred_stmt->execute([$student_id, $quiz_id, $points]);
    } catch(PDOException $e) {}
    $status_color = $percentage >= 80 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
    $status_icon = $percentage >= 80 ? 'check-circle-fill' : ($percentage >= 50 ? 'exclamation-circle-fill' : 'x-circle-fill');
    $message = $percentage >= 80 ? 'Excellent work! You have mastered this material.' : ($percentage >= 50 ? 'Good effort, but there is room for improvement. Review the material!' : 'It seems you struggled. Please review the course materials and try again.');

?>
    <div class="row align-items-center justify-content-center min-vh-50 mt-5">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg text-center" style="border-radius: 16px; overflow: hidden;">
                <!-- Result Header -->
                <div class="bg-<?= $status_color ?> text-white p-5 position-relative">
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.1; background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 20px 20px;"></div>
                    <i class="bi bi-<?= $status_icon ?> position-relative" style="font-size: 5rem; z-index: 2; text-shadow: 0 4px 10px rgba(0,0,0,0.2);"></i>
                    <h2 class="fw-bold mt-3 position-relative" style="z-index: 2;">Score: <?= $score ?> / <?= $total_questions ?></h2>
                    <h5 class="fw-normal bg-white bg-opacity-25 d-inline-block px-4 py-2 rounded-pill mt-2 position-relative" style="z-index: 2;"><?= $percentage ?>% Correct</h5>
                </div>
                
                <!-- Result Body -->
                <div class="card-body p-5 bg-white">
                    <h4 class="fw-bold text-dark mb-3">Quiz: <?= htmlspecialchars($quiz['title']) ?></h4>
                    <p class="text-muted fs-5 mb-4"><?= $message ?></p>
                    
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mt-4">
                        <a href="quiz.php" class="btn btn-outline-primary btn-lg rounded-pill px-4 fw-semibold"><i class="bi bi-arrow-left me-2"></i>Back to Quizzes</a>
                        <a href="student_dashboard.php" class="btn btn-primary btn-lg rounded-pill px-4 fw-semibold"><i class="bi bi-house-door me-2"></i>Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
} else {
    header("Location: quiz.php");
    exit;
}

require_once 'footer.php';
?>
