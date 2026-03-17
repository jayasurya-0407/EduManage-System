<?php
require_once 'header.php';

$student_id = $_SESSION['student_id'];
$c_stmt = $pdo->prepare("SELECT course_id FROM student_courses WHERE student_id = ?");
$c_stmt->execute([$student_id]);
$course_ids = $c_stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($course_ids)) {
    echo "<div class='alert alert-warning m-4'>You are not assigned to any courses.</div>";
    require_once 'footer.php';
    exit;
}

$quiz_id = $_GET['id'] ?? null;

// If a specific quiz is selected, show the questions
if ($quiz_id) {
    // Verify quiz belongs to one of their courses
    $in_placeholders = str_repeat('?,', count($course_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND course_id IN ($in_placeholders)");
    $params = array_merge([$quiz_id], $course_ids);
    $stmt->execute($params);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        echo "<div class='alert alert-danger m-4'>Quiz not found or you don't have access.</div>";
        require_once 'footer.php';
        exit;
    }
    
    // Check if they already took it recently (optional, let's allow retakes since we track Total attempts)
    // Fetch questions
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();
    
    if (count($questions) == 0) {
        echo "<div class='alert alert-info m-4'>This quiz has no questions yet.</div>";
        require_once 'footer.php';
        exit;
    }
?>
    <div class="row mb-4">
        <div class="col-12 col-md-8 mx-auto">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="quiz.php">Quizzes</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($quiz['title']) ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-3"><i class="bi bi-patch-question-fill text-primary"></i> <?= htmlspecialchars($quiz['title']) ?></h3>
            <p class="text-muted border-bottom pb-3">Answer all questions below and click submit when you're finished.</p>
            
            <form action="quiz_result.php" method="POST">
                <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
                
                <?php foreach ($questions as $index => $q): ?>
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                        <div class="card-body p-4">
                            <h5 class="fw-semibold mb-4 d-flex gap-3 text-dark">
                                <span class="badge bg-primary rounded-circle fs-6 py-1 px-2" style="height: fit-content;"><?= $index + 1 ?></span>
                                <?= htmlspecialchars($q['question']) ?>
                            </h5>
                            
                            <?php
                            $opt_stmt = $pdo->prepare("SELECT * FROM quiz_options WHERE question_id = ? ORDER BY RAND()");
                            $opt_stmt->execute([$q['id']]);
                            $options = $opt_stmt->fetchAll();
                            ?>
                            
                            <div class="d-flex flex-column gap-3 ms-4 ms-md-5">
                                <?php foreach ($options as $opt): ?>
                                    <label class="form-check d-flex align-items-center bg-light border p-3 rounded cursor-pointer option-item">
                                        <input class="form-check-input me-3 mt-0 fs-5" type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $opt['id'] ?>" required>
                                        <span class="fs-6"><?= htmlspecialchars($opt['option_text']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="card border-0 bg-transparent mb-5 text-center">
                    <div class="card-body">
                        <h5 class="text-muted fw-normal mb-4">Make sure you've answered every question.</h5>
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow fw-semibold rounded-pill">
                            Submit Quiz Responses <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <style>
        .cursor-pointer { cursor: pointer; transition: all 0.2s; }
        .cursor-pointer:hover { background-color: var(--bs-primary-bg-subtle) !important; border-color: var(--bs-primary-border-subtle) !important; }
        .form-check-input:checked + span { font-weight: 600; color: var(--bs-primary); }
    </style>
<?php

} else {
    // List available quizzes
    $stmt = $pdo->prepare("
        SELECT q.*, COUNT(qq.id) as question_count 
        FROM quizzes q 
        LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id 
        WHERE q.course_id IN ($in_placeholders) 
        GROUP BY q.id 
        ORDER BY q.created_at DESC
    ");
    $stmt->execute($course_ids);
    $quizzes = $stmt->fetchAll();
    
    // Get past results
    $res_stmt = $pdo->prepare("
        SELECT qr.*, q.title 
        FROM quiz_results qr 
        JOIN quizzes q ON qr.quiz_id = q.id 
        WHERE qr.student_id = ? 
        ORDER BY qr.submitted_at DESC
    ");
    $res_stmt->execute([$student_id]);
    $results = $res_stmt->fetchAll();
?>
    <div class="row mb-5">
        <div class="col-12 col-xl-8">
            <h4 class="fw-bold mb-4 text-dark"><i class="bi bi-patch-question me-2 text-primary"></i> Assessment Center</h4>
            
            <div class="row g-4">
                <?php if (count($quizzes) > 0): ?>
                    <?php foreach ($quizzes as $quiz): ?>
                        <div class="col-12 col-md-6">
                            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s;">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                            <i class="bi bi-ui-checks text-primary fs-3"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($quiz['title']) ?></h5>
                                            <span class="badge bg-secondary opacity-75 rounded-pill"><?= $quiz['question_count'] ?> Questions</span>
                                        </div>
                                    </div>
                                    
                                    <p class="text-muted small mb-4 flex-grow-1">Test your understanding of the course materials. Completing quizzes is a great way to retain knowledge.</p>
                                    
                                    <?php if ($quiz['question_count'] > 0): ?>
                                        <a href="quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-outline-primary w-100 fw-semibold">Take Quiz</a>
                                    <?php else: ?>
                                        <button class="btn btn-light w-100 text-muted" disabled>Questions pending</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5 bg-white rounded shadow-sm border text-muted">
                            <i class="bi bi-journal-x fs-1 opacity-50 d-block mb-3"></i>
                            <p class="mb-0">No quizzes are available for your course yet.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-12 col-xl-4">
            <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-award me-2 text-warning"></i> Previous Results</h5>
            
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-0">
                    <?php if (count($results) > 0): ?>
                        <div class="list-group list-group-flush rounded-bottom">
                            <?php foreach($results as $res): 
                                $pct = ($res['total_questions'] > 0) ? round(($res['score'] / $res['total_questions']) * 100) : 0;
                                $color = $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
                            ?>
                                <div class="list-group-item px-4 py-3 border-bottom-0 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0 fw-bold text-dark w-75 text-truncate"><?= htmlspecialchars($res['title']) ?></h6>
                                        <span class="badge bg-<?= $color ?> rounded-pill fs-6"><?= $res['score'] ?>/<?= $res['total_questions'] ?></span>
                                    </div>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('M j, Y • h:i A', strtotime($res['submitted_at'])) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-clipboard-x text-muted fs-1 mb-2 d-block opacity-50"></i>
                            <p class="text-muted small mb-0 px-4">You haven't taken any quizzes yet. Results will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php
}

require_once 'footer.php';
?>
