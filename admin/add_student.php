<?php
require_once 'header.php';

$success = '';
$error = '';

// Fetch courses for dropdown
$courses_stmt = $pdo->query("SELECT id, course_name FROM courses ORDER BY course_name ASC");
$courses = $courses_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $course_ids = isset($_POST['course_ids']) ? $_POST['course_ids'] : [];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Name, email, and password are required.";
    } elseif (empty($course_ids)) {
        $error = "Please assign at least one course.";
    } else {
        // Check if email already exists
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->fetchColumn() > 0) {
            $error = "Email address is already registered.";
        } else {
            // Hash password before saving
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO students (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $hashed_password]);
                $student_id = $pdo->lastInsertId();

                $course_stmt = $pdo->prepare("INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
                foreach ($course_ids as $cid) {
                    $course_stmt->execute([$student_id, $cid]);
                }
                
                $pdo->commit();
                $success = "Student registered and courses assigned successfully.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Register New Student</h5>
        <a href="view_students.php" class="btn btn-sm btn-outline-secondary">View All Students</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="add_student.php">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., John Doe">
            </div>
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="john@example.com">
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="password" class="form-label fw-medium">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Create a password">
            </div>
            <div class="col-md-6 mb-4">
                <label class="form-label fw-medium">Assign Courses <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (empty($courses)): ?>
                        <div class="text-muted">No courses available. Please create a course first.</div>
                    <?php else: ?>
                        <?php foreach ($courses as $c): ?>
                            <div class="form-check form-check-inline border rounded px-3 py-2 w-100 mb-1">
                                <input class="form-check-input" type="checkbox" name="course_ids[]" id="course_<?= $c['id'] ?>" value="<?= $c['id'] ?>">
                                <label class="form-check-label w-100" style="cursor: pointer;" for="course_<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['course_name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-person-plus me-2"></i> Register Student</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>
