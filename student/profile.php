<?php
require_once 'header.php';

$student_id = $_SESSION['student_id'];
$success = '';
$error = '';

// Fetch student details and enrolled courses
$stmt = $pdo->prepare("
    SELECT s.*, GROUP_CONCAT(c.course_name SEPARATOR ', ') as course_names 
    FROM students s 
    LEFT JOIN student_courses sc ON s.student_id = sc.student_id 
    LEFT JOIN courses c ON sc.course_id = c.id 
    WHERE s.student_id = ?
    GROUP BY s.student_id
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } else {
        try {
            // Check if email already exists for another student
            $check_stmt = $pdo->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ?");
            $check_stmt->execute([$email, $student_id]);
            if ($check_stmt->rowCount() > 0) {
                $error = "This email is already in use.";
            } else {
                $update_stmt = $pdo->prepare("UPDATE students SET name = ?, email = ? WHERE student_id = ?");
                $update_stmt->execute([$name, $email, $student_id]);
                $_SESSION['student_name'] = $name; // Update session
                $student['name'] = $name;
                $student['email'] = $email;
                $success = "Profile updated successfully!";
            }
        } catch(PDOException $e) {
            $error = "Failed to update profile: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } else {
        // Support both hashed and legacy plain-text passwords
        $currentOk = password_verify($current_password, $student['password']) || $current_password === $student['password'];
        if (!$currentOk) {
            $error = "Current password is incorrect.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters.";
        } else {
            try {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                $update_stmt->execute([$hashed, $student_id]);
                $success = "Password changed successfully!";
            } catch(PDOException $e) {
                $error = "Failed to change password: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="row mb-5">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%); color: white;">
            <div class="card-body p-4 p-md-5 d-flex align-items-center gap-4">
                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 80px; height: 80px; font-size: 2.5rem; font-weight: bold;">
                    <?= strtoupper(substr($student['name'], 0, 1)) ?>
                </div>
                <div>
                    <h3 class="fw-bold mb-1"><?= htmlspecialchars($student['name']) ?></h3>
                    <p class="mb-0 fs-5 text-white-50"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($student['email']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

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

<div class="row g-4">
    <!-- Academic Profile -->
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-4 border-bottom pb-2">Academic Info</h5>
                
                <div class="mb-4">
                    <p class="text-muted small fw-semibold text-uppercase mb-1">Enrolled Course</p>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-journal-bookmark fs-4 text-primary"></i>
                        <span class="fs-5 fw-semibold text-dark"><?= !empty($student['course_names']) ? htmlspecialchars($student['course_names']) : 'None' ?></span>
                    </div>
                </div>

                <div>
                    <p class="text-muted small fw-semibold text-uppercase mb-1">Registration Context</p>
                    <p class="text-dark"><i class="bi bi-check-circle-fill text-success me-2"></i>Active Student Account</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Profile Form -->
    <div class="col-12 col-md-8">
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-4 border-bottom pb-2">Personal Information</h5>
                <form action="profile.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>
                        </div>
                        <div class="col-12 mt-4 text-end">
                            <button type="submit" name="update_profile" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i>Update Profile</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Form -->
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-4 border-bottom pb-2">Change Password</h5>
                <form action="profile.php" method="POST">
                    <div class="row g-3">
                        <div class="col-12 mb-2">
                            <label class="form-label fw-semibold">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <div class="col-12 mt-4 text-end">
                            <button type="submit" name="change_password" class="btn btn-dark px-4"><i class="bi bi-shield-lock me-2"></i>Change Password</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
