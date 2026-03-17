<?php
require_once 'header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = trim($_POST['course_name']);
    $description = trim($_POST['description']);

    if (empty($course_name)) {
        $error = "Course name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO courses (course_name, description) VALUES (?, ?)");
            if ($stmt->execute([$course_name, $description])) {
                $success = "Course added successfully.";
            } else {
                $error = "Failed to add course.";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Add New Course</h5>
        <a href="view_course.php" class="btn btn-sm btn-outline-secondary">View All Courses</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="add_course.php">
        <div class="mb-3">
            <label for="course_name" class="form-label fw-medium">Course Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="course_name" name="course_name" required placeholder="e.g., Time Management Basics">
        </div>
        <div class="mb-4">
            <label for="description" class="form-label fw-medium">Course Description</label>
            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Brief description of the course..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i> Save Course</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>
