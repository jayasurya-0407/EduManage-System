<?php
require_once 'header.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get current password from DB
    $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    $isValid = false;
    if ($admin) {
        $isValid = password_verify($current_password, $admin['password']) || $current_password === $admin['password'];
    }

    if (!$isValid) {
        $message = "Incorrect current password.";
        $message_type = "danger";
    } elseif ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
        $message_type = "danger";
    } elseif (strlen($new_password) < 6) {
        $message = "New password must be at least 6 characters long.";
        $message_type = "danger";
    } else {
        // Hash the new password before storing
        $updated_password = password_hash($new_password, PASSWORD_DEFAULT);
        try {
            $upd_stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $upd_stmt->execute([$updated_password, $_SESSION['admin_id']]);
            $message = "Password updated successfully.";
            $message_type = "success";
        } catch(PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}
?>

<div class="row justify-content-center mt-4 mb-5">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                    <a href="admin_profile.php" class="btn btn-sm btn-outline-secondary me-3 rounded-circle" style="width: 32px; height: 32px; padding: 0; line-height: 30px; text-align: center;">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h4 class="fw-bold text-dark mb-0">Change Password</h4>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="change_password.php">
                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-medium text-dark">Current Password</label>
                        <input type="password" class="form-control form-control-lg bg-light" id="current_password" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-medium text-dark">New Password</label>
                        <input type="password" class="form-control form-control-lg bg-light" id="new_password" name="new_password" required>
                        <div class="form-text mt-1">Must be at least 6 characters long.</div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-medium text-dark">Confirm New Password</label>
                        <input type="password" class="form-control form-control-lg bg-light" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-semibold rounded-pill">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
