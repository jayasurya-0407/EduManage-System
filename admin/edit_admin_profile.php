<?php
require_once 'header.php';

// Fetch current admin details
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $profile_image = $admin['profile_image']; // Keep existing by default

    // Validate inputs
    if (empty($name) || empty($email)) {
        $message = "Name and Email are required.";
        $message_type = "danger";
    } else {
        // Handle File Upload if exists
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $filename = $_FILES['profile_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $new_filename = 'admin_' . $_SESSION['admin_id'] . '_' . time() . '.' . $ext;
                $upload_dir = '../uploads/';
                
                // Ensure directory exists
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
                    // Delete old image if it exists
                    if ($profile_image && file_exists($upload_dir . $profile_image)) {
                        unlink($upload_dir . $profile_image);
                    }
                    $profile_image = $new_filename;
                } else {
                    $message = "Failed to upload image.";
                    $message_type = "danger";
                }
            } else {
                $message = "Invalid file type. Only JPG, PNG, and WEBP are allowed.";
                $message_type = "danger";
            }
        }

        if (empty($message)) {
            // Check if email is already taken by another admin
            $check_stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $check_stmt->execute([$email, $_SESSION['admin_id']]);
            if ($check_stmt->rowCount() > 0) {
                $message = "Email is already in use by another account.";
                $message_type = "danger";
            } else {
                // Update database
                try {
                    $upd_stmt = $pdo->prepare("UPDATE admins SET name = ?, email = ?, profile_image = ? WHERE id = ?");
                    $upd_stmt->execute([$name, $email, $profile_image, $_SESSION['admin_id']]);
                    
                    // Update session variables
                    $_SESSION['admin_name'] = $name;
                    
                    $message = "Profile updated successfully!";
                    $message_type = "success";
                    
                    // Refresh data for the view
                    $admin['name'] = $name;
                    $admin['email'] = $email;
                    $admin['profile_image'] = $profile_image;
                    
                    // Automatically refresh header data by redirecting or just letting user see success message and click back.
                } catch(PDOException $e) {
                    $message = "Database error: " . $e->getMessage();
                    $message_type = "danger";
                }
            }
        }
    }
}
?>

<div class="row justify-content-center mt-4 mb-5">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                    <a href="admin_profile.php" class="btn btn-sm btn-outline-secondary me-3 rounded-circle" style="width: 32px; height: 32px; padding: 0; line-height: 30px; text-align: center;">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h4 class="fw-bold text-dark mb-0">Edit Profile</h4>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="edit_admin_profile.php" enctype="multipart/form-data">
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <?php if ($admin['profile_image'] && file_exists("../uploads/" . $admin['profile_image'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($admin['profile_image']) ?>" alt="Admin Profile" class="rounded-circle object-fit-cover border shadow-sm mb-2 mt-2" style="width: 100px; height: 100px;" id="preview_image">
                            <?php else: ?>
                                <div class="bg-primary mt-2 text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm mx-auto mb-2" style="width: 100px; height: 100px; font-size: 2.5rem;" id="preview_placeholder">
                                    <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                                </div>
                                <img src="" alt="Preview" class="rounded-circle object-fit-cover border shadow-sm mb-2 mt-2 d-none" style="width: 100px; height: 100px;" id="preview_image">
                            <?php endif; ?>
                            
                            <label for="profile_image" class="position-absolute bottom-0 end-0 bg-white border rounded-circle shadow-sm" style="width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; transform: translate(10%, -10%);">
                                <i class="bi bi-camera-fill text-primary"></i>
                            </label>
                            <input type="file" id="profile_image" name="profile_image" class="d-none" accept=".jpg,.jpeg,.png,.webp" onchange="previewImage(this)">
                        </div>
                        <div class="text-muted small">Click the camera icon to update picture</div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-medium text-dark">Full Name</label>
                        <input type="text" class="form-control form-control-lg bg-light" id="name" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label fw-medium text-dark">Email Address</label>
                        <input type="email" class="form-control form-control-lg bg-light" id="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-semibold rounded-pill">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('preview_image');
            var placeholder = document.getElementById('preview_placeholder');
            
            img.src = e.target.result;
            img.classList.remove('d-none');
            
            if (placeholder) {
                placeholder.classList.add('d-none');
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'footer.php'; ?>
