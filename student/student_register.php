<?php
session_start();
require_once '../db.php';

if (isset($_SESSION['student_id'])) {
    header("Location: student_dashboard.php");
    exit;
}

$success = '';
$error = '';

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
        $error = "Please select at least one course.";
    } else {
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->fetchColumn() > 0) {
            $error = "Email address is already registered.";
        } else {
            try {
                $pdo->beginTransaction();
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO students (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $hashed]);
                $student_id = $pdo->lastInsertId();
                $cs = $pdo->prepare("INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
                foreach ($course_ids as $cid) { $cs->execute([$student_id, $cid]); }
                $pdo->commit();
                $success = "Registration successful! You can now log in.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Life Skills Coaching</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        body { background-color: var(--background); background-image: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%); }
        .auth-card { border: none; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 2.5rem; width: 100%; max-width: 500px; background: white; }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-dark">Create an Account</h4>
            <p class="text-muted small">Register as a student to access study materials</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success py-2 text-center">
                <?= htmlspecialchars($success) ?>
                <div class="mt-2"><a href="student_login.php" class="btn btn-sm btn-success">Go to Login</a></div>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="student_register.php">
                <div class="mb-3">
                    <label for="name" class="form-label fw-medium">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" required placeholder="John Doe">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-medium">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="john@example.com">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label fw-medium">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Create a password">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-medium">Select Course(s)</label>
                    <?php if (empty($courses)): ?>
                        <p class="text-muted small">No courses available to enroll yet.</p>
                    <?php else: ?>
                        <?php foreach ($courses as $c): ?>
                            <div class="form-check border rounded px-3 py-2 mb-2">
                                <input class="form-check-input" type="checkbox" name="course_ids[]" id="c<?= $c['id'] ?>" value="<?= $c['id'] ?>">
                                <label class="form-check-label w-100" for="c<?= $c['id'] ?>"><?= htmlspecialchars($c['course_name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary py-2 fw-medium">Register</button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="text-center mt-3 border-top pt-3">
            <p class="text-muted small mb-0">Already have an account? <a href="student_login.php" class="text-decoration-none fw-medium text-primary">Log in here</a></p>
        </div>
    </div>
</div>

</body>
</html>
