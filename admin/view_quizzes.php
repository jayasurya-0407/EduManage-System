<?php
require_once 'header.php';

// Handle deletion of quiz
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_msg = "Quiz deleted successfully!";
    } catch(PDOException $e) {
        $error_msg = "Error deleting quiz: " . $e->getMessage();
    }
}

// Fetch quizzes with their course names and question counts
$query = "
    SELECT q.*, c.course_name, COUNT(qq.id) as question_count 
    FROM quizzes q
    JOIN courses c ON q.course_id = c.id
    LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id
    GROUP BY q.id
    ORDER BY q.created_at DESC
";
$quizzes = $pdo->query($query)->fetchAll();
?>

<div class="row mb-4 flex-column flex-md-row gap-3 gap-md-0">
    <div class="col-12 col-md-8">
        <h4 class="fw-bold mb-2"><i class="bi bi-patch-question text-primary"></i> Manage Quizzes</h4>
        <p class="text-muted mb-0">View system quizzes, manage their questions, or remove them entirely.</p>
    </div>
    <div class="col-12 col-md-4 text-md-end align-self-center">
        <a href="add_quiz.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-2"></i>Create New Quiz
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="content-card">
            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= $success_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (count($quizzes) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Quiz Title</th>
                                <th>Course</th>
                                <th>Questions</th>
                                <th>Created At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quizzes as $index => $quiz): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($quiz['title']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($quiz['course_name']) ?></td>
                                    <td>
                                        <span class="badge <?= $quiz['question_count'] > 0 ? 'bg-success' : 'bg-warning text-dark' ?> rounded-pill">
                                            <?= $quiz['question_count'] ?> Questions
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?= date('M j, Y', strtotime($quiz['created_at'])) ?></td>
                                    <td class="text-end text-nowrap">
                                        <a href="add_questions.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-sm btn-outline-primary me-2">
                                            <i class="bi bi-list-task"></i> Manage Questions
                                        </a>
                                        <a href="view_quizzes.php?delete_id=<?= $quiz['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this quiz and all its questions? This cannot be undone.');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted bg-light rounded shadow-sm">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    <p class="mb-0">No quizzes found in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
