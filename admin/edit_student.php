<?php
require_once 'header.php';

$success = '';
$error = '';
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$student_id) {
    echo "Invalid student ID.";
    exit;
}

// Fetch student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    echo "Student not found.";
    exit;
}

// Fetch all courses
$courses_stmt = $pdo->query("SELECT id, course_name FROM courses ORDER BY course_name ASC");
$all_courses = $courses_stmt->fetchAll();

// Fetch currently enrolled courses
$enrolled_stmt = $pdo->prepare("SELECT course_id FROM student_courses WHERE student_id = ?");
$enrolled_stmt->execute([$student_id]);
$enrolled_courses = $enrolled_stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $course_ids = isset($_POST['course_ids']) ? $_POST['course_ids'] : [];
    $password = $_POST['password'];

    if (empty($name) || empty($email)) {
        $error = "Name and email are required.";
    } else {
        try {
            $pdo->beginTransaction();

            // Update student
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, password = ? WHERE student_id = ?");
                $update_stmt->execute([$name, $email, $hashed_password, $student_id]);
            } else {
                $update_stmt = $pdo->prepare("UPDATE students SET name = ?, email = ? WHERE student_id = ?");
                $update_stmt->execute([$name, $email, $student_id]);
            }

            // Update courses
            $pdo->prepare("DELETE FROM student_courses WHERE student_id = ?")->execute([$student_id]);
            if (!empty($course_ids)) {
                $insert_course = $pdo->prepare("INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
                foreach ($course_ids as $cid) {
                    $insert_course->execute([$student_id, $cid]);
                }
            }

            $pdo->commit();
            $success = "Student updated successfully.";
            
            // Refresh student details
            $student['name'] = $name;
            $student['email'] = $email;
            $enrolled_courses = $course_ids;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Edit Student: <?= htmlspecialchars($student['name']) ?></h5>
        <a href="view_students.php" class="btn btn-sm btn-outline-secondary">Back to Students</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit_student.php?id=<?= $student_id ?>">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="password" class="form-label fw-medium">New Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current">
            </div>
            <div class="col-md-6 mb-4">
                <label class="form-label fw-medium">Enrolled Courses</label>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (empty($all_courses)): ?>
                        <div class="text-muted">No courses available.</div>
                    <?php else: ?>
                        <?php foreach ($all_courses as $c): ?>
                            <div class="form-check form-check-inline border rounded px-3 py-2 w-100 mb-1">
                                <input class="form-check-input" type="checkbox" name="course_ids[]" id="course_<?= $c['id'] ?>" value="<?= $c['id'] ?>" <?= in_array($c['id'], $enrolled_courses) ? 'checked' : '' ?>>
                                <label class="form-check-label w-100" style="cursor: pointer;" for="course_<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['course_name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i> Save Changes</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>
