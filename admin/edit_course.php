<?php
require_once 'header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_course.php");
    exit;
}

$id = $_GET['id'];
$error = '';

// Fetch the course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course) {
    header("Location: view_course.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = trim($_POST['course_name']);
    $description = trim($_POST['description']);

    if (empty($course_name)) {
        $error = "Course name is required.";
    } else {
        try {
            $update_stmt = $pdo->prepare("UPDATE courses SET course_name = ?, description = ? WHERE id = ?");
            if ($update_stmt->execute([$course_name, $description, $id])) {
                header("Location: view_course.php?msg=updated");
                exit;
            } else {
                $error = "Failed to update course.";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Edit Course</h5>
        <a href="view_course.php" class="btn btn-sm btn-outline-secondary">Back to Courses</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit_course.php?id=<?= $id ?>">
        <div class="mb-3">
            <label for="course_name" class="form-label fw-medium">Course Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="course_name" name="course_name" required value="<?= htmlspecialchars($course['course_name']) ?>">
        </div>
        <div class="mb-4">
            <label for="description" class="form-label fw-medium">Course Description</label>
            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i> Update Course</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>
