<?php
require_once 'header.php';

$success = '';
$error = '';

// Fetch courses for dropdown
$courses_stmt = $pdo->query("SELECT id, course_name FROM courses ORDER BY course_name ASC");
$courses = $courses_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $course_id = $_POST['course_id'];

    if (empty($title) || empty($course_id)) {
        $error = "Title and Course selection are required.";
    } elseif (!isset($_FILES['material_file']) || $_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please successfully upload a PDF file.";
    } else {
        $file_tmp = $_FILES['material_file']['tmp_name'];
        $file_name = $_FILES['material_file']['name'];
        $file_size = $_FILES['material_file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_ext !== 'pdf') {
            $error = "Only PDF files are allowed.";
        } else {
            // Create uploads directory if it doesn't exist just in case
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate unique filename to avoid overwrites
            $new_file_name = uniqid() . '-' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $file_name);
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                try {
                    // Store the relative path to be accessed from browser
                    $file_path = 'uploads/' . $new_file_name;
                    $stmt = $pdo->prepare("INSERT INTO materials (course_id, title, file_path) VALUES (?, ?, ?)");
                    if ($stmt->execute([$course_id, $title, $file_path])) {
                        $success = "Study material uploaded successfully.";
                    } else {
                        $error = "Failed to save material information to database.";
                        // Clean up file if DB insert fails
                        if (file_exists($destination)) {
                            unlink($destination);
                        }
                    }
                } catch (PDOException $e) {
                    $error = "Error: " . $e->getMessage();
                    if (file_exists($destination)) {
                        unlink($destination);
                    }
                }
            } else {
                $error = "Failed to move uploaded file.";
            }
        }
    }
}
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Upload Study Material</h5>
        <a href="view_materials.php" class="btn btn-sm btn-outline-secondary">View All Materials</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="add_material.php" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="title" class="form-label fw-medium">Material Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Chapter 1: Introduction">
        </div>
        
        <div class="mb-3">
            <label for="course_id" class="form-label fw-medium">Assign to Course <span class="text-danger">*</span></label>
            <select class="form-select" id="course_id" name="course_id" required>
                <option value="" disabled selected>Select a course</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label for="material_file" class="form-label fw-medium">Upload PDF File <span class="text-danger">*</span></label>
            <input class="form-control" type="file" id="material_file" name="material_file" accept=".pdf" required>
            <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i>Only .pdf format is supported. Ensure the file size is reasonable.</div>
        </div>
        
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-cloud-arrow-up me-2"></i> Upload Material</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>
