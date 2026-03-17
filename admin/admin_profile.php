<?php
require_once 'header.php';

// Fetch admin details
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if (!$admin) {
    echo "<div class='alert alert-danger m-4'>Admin profile not found.</div>";
    require_once 'footer.php';
    exit;
}
?>

<div class="row justify-content-center mt-4">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-body p-5 text-center">
                <!-- Profile Image -->
                <div class="mb-4 position-relative d-inline-block">
                    <?php if ($admin['profile_image'] && file_exists("../uploads/" . $admin['profile_image'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($admin['profile_image']) ?>" alt="Admin Profile" class="rounded-circle object-fit-cover border shadow-sm" style="width: 120px; height: 120px;">
                    <?php else: ?>
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm mx-auto" style="width: 120px; height: 120px; font-size: 3rem;">
                            <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Admin Details -->
                <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars($admin['name']) ?></h3>
                <p class="text-muted mb-4 fs-5"><?= htmlspecialchars($admin['email']) ?></p>

                <div class="bg-light rounded p-3 mb-4 d-inline-block text-start w-100" style="max-width: 300px;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fw-semibold">Role:</span>
                        <span class="fw-bold text-dark">Administrator</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted fw-semibold">Joined:</span>
                        <span class="fw-bold text-dark"><?= date('M j, Y', strtotime($admin['created_at'])) ?></span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mt-2">
                    <a href="edit_admin_profile.php" class="btn btn-primary px-4 py-2 fw-semibold rounded-pill">
                        <i class="bi bi-pencil-square me-2"></i>Edit Profile
                    </a>
                    <a href="change_password.php" class="btn btn-outline-secondary px-4 py-2 fw-semibold rounded-pill">
                        <i class="bi bi-shield-lock me-2"></i>Change Password
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
