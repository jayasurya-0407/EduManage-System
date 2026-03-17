<?php
require_once 'header.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = $_POST['course_id'] ?? '';
    $title = trim($_POST['title'] ?? '');

    if (empty($course_id) || empty($title)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO quizzes (course_id, title) VALUES (?, ?)");
            $stmt->execute([$course_id, $title]);
            $success = "Quiz created successfully!" . " <a href='view_quizzes.php' class='alert-link'>View Quizzes</a>";
        } catch (PDOException $e) {
            $error = "Error adding quiz: " . $e->getMessage();
        }
    }
}

// Fetch courses for dropdown
$courses = $pdo->query("SELECT * FROM courses ORDER BY course_name ASC")->fetchAll();
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-3"><i class="bi bi-patch-plus text-primary"></i> Create New Quiz</h4>
        <p class="text-muted">Create a new quiz and assign it to a course. You can add questions later.</p>
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="content-card">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="add_quiz.php" method="POST">
                <div class="mb-4">
                    <label for="course_id" class="form-label fw-semibold">Select Course <span
                            class="text-danger">*</span></label>
                    <select class="form-select" id="course_id" name="course_id" required>
                        <option value="" selected disabled>Choose a course...</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="title" class="form-label fw-semibold">Quiz Title <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Unit 1 Assessment"
                        required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-plus-circle me-2"></i>Create Quiz
                    </button>
                    <a href="view_quizzes.php" class="btn btn-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>