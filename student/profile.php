<?php
require_once 'header.php';

$student_id = $_SESSION['student_id'];
$success = '';
$error = '';

// Fetch student details + enrolled courses
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

// Fetch enrolled courses individually (for exit-course feature)
$courses_stmt = $pdo->prepare("
    SELECT c.id, c.course_name
    FROM student_courses sc
    JOIN courses c ON sc.course_id = c.id
    WHERE sc.student_id = ?
    ORDER BY c.course_name ASC
");
$courses_stmt->execute([$student_id]);
$enrolled_courses = $courses_stmt->fetchAll();

/* ── Update Profile ── */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } else {
        try {
            $check_stmt = $pdo->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ?");
            $check_stmt->execute([$email, $student_id]);
            if ($check_stmt->rowCount() > 0) {
                $error = "This email is already in use.";
            } else {
                $pdo->prepare("UPDATE students SET name = ?, email = ? WHERE student_id = ?")
                    ->execute([$name, $email, $student_id]);
                $_SESSION['student_name'] = $name;
                $student['name']  = $name;
                $student['email'] = $email;
                $success = "Profile updated successfully!";
            }
        } catch (PDOException $e) {
            $error = "Failed to update profile: " . $e->getMessage();
        }
    }
}

/* ── Change Password ── */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password  = $_POST['current_password'];
    $new_password      = $_POST['new_password'];
    $confirm_password  = $_POST['confirm_password'];
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } else {
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
                $pdo->prepare("UPDATE students SET password = ? WHERE student_id = ?")
                    ->execute([$hashed, $student_id]);
                $success = "Password changed successfully!";
            } catch (PDOException $e) {
                $error = "Failed to change password: " . $e->getMessage();
            }
        }
    }
}

/* ── Exit Course ── */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['exit_course'])) {
    $course_id    = (int)$_POST['course_id'];
    $pwd_confirm  = $_POST['exit_password'];
    $pwdOk = password_verify($pwd_confirm, $student['password']) || $pwd_confirm === $student['password'];
    if (!$pwdOk) {
        $error = "Incorrect password. Could not unenroll from course.";
    } else {
        try {
            $pdo->prepare("DELETE FROM student_courses WHERE student_id = ? AND course_id = ?")
                ->execute([$student_id, $course_id]);
            // Refresh enrolled courses list
            $courses_stmt->execute([$student_id]);
            $enrolled_courses = $courses_stmt->fetchAll();
            // Refresh course_names in $student
            $names = array_column($enrolled_courses, 'course_name');
            $student['course_names'] = implode(', ', $names);
            $success = "You have successfully exited the course.";
        } catch (PDOException $e) {
            $error = "Failed to exit course: " . $e->getMessage();
        }
    }
}

/* ── Delete Account ── */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
    $pwd_confirm = $_POST['delete_password'];
    $pwdOk = password_verify($pwd_confirm, $student['password']) || $pwd_confirm === $student['password'];
    if (!$pwdOk) {
        $error = "Incorrect password. Account not deleted.";
    } else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM student_courses WHERE student_id = ?")->execute([$student_id]);
            $pdo->prepare("DELETE FROM students WHERE student_id = ?")->execute([$student_id]);
            $pdo->commit();
            session_destroy();
            header("Location: ../login.php?msg=account_deleted");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Failed to delete account: " . $e->getMessage();
        }
    }
}
?>

<!-- ── Danger Zone styles (scoped here) ── -->
<style>
.danger-zone-card {
    background: rgba(239,68,68,.05);
    border: 1px solid rgba(239,68,68,.2) !important;
    border-radius: 16px;
}
.danger-zone-card .dz-title {
    color: #f87171;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}
.course-exit-item {
    background: #1e293b;
    border: 1px solid rgba(99,102,241,.12);
    border-radius: 12px;
    padding: .85rem 1.1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .6rem;
    transition: border-color .2s;
}
.course-exit-item:hover { border-color: rgba(239,68,68,.3); }
.course-exit-item .course-exit-name { color: #cbd5e1; font-size: .875rem; font-weight: 500; }

/* Modals dark style */
.modal-dark .modal-content {
    background: #111827 !important;
    border: 1px solid rgba(99,102,241,.18) !important;
    border-radius: 18px !important;
    color: #e2e8f0 !important;
}
.modal-dark .modal-header {
    border-bottom: 1px solid rgba(255,255,255,.07) !important;
    background: transparent !important;
}
.modal-dark .modal-footer {
    border-top: 1px solid rgba(255,255,255,.07) !important;
    background: transparent !important;
}
.modal-dark .modal-title { color: #f1f5f9 !important; }
.modal-dark .btn-close { filter: invert(1) grayscale(1); }
.modal-dark .form-control {
    background: #1e293b !important;
    border: 1.5px solid rgba(99,102,241,.2) !important;
    color: #e2e8f0 !important;
    border-radius: 10px;
}
.modal-dark .form-control:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,.15) !important;
    color: #f1f5f9 !important;
}
.modal-dark .form-control::placeholder { color: #475569 !important; }
.modal-dark .form-label { color: #94a3b8 !important; font-size: .82rem; font-weight: 600; }
.modal-backdrop.show { opacity: .7; }
</style>

<!-- ── Profile Hero ── -->
<div class="row mb-5">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:12px;background:linear-gradient(135deg,#6366f1 0%,#4338ca 100%);color:white;">
            <div class="card-body p-4 p-md-5 d-flex align-items-center gap-4">
                <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.2);font-size:2.5rem;font-weight:700;display:flex;align-items:center;justify-content:center;">
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

<!-- ── Alerts ── -->
<?php if ($success): ?>
    <div class="s-alert-success mb-4 d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="s-alert-danger mb-4 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- ── Main Grid ── -->
<div class="row g-4">

    <!-- Left: Academic Info -->
    <div class="col-12 col-md-4">
        <div class="s-card h-100">
            <h5 class="mb-4 pb-2" style="border-bottom:1px solid rgba(99,102,241,.15);">
                <i class="bi bi-journal-bookmark me-2 text-indigo" style="color:#818cf8;"></i>Academic Info
            </h5>
            <p class="text-muted small fw-semibold text-uppercase mb-1">Enrolled Courses</p>
            <?php if (!empty($enrolled_courses)): ?>
                <?php foreach ($enrolled_courses as $ec): ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-check-circle-fill" style="color:#34d399;font-size:.85rem;"></i>
                        <span style="color:#cbd5e1;font-size:.875rem;"><?= htmlspecialchars($ec['course_name']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#64748b;font-size:.85rem;">No courses enrolled.</p>
            <?php endif; ?>

            <div class="mt-4">
                <p class="text-muted small fw-semibold text-uppercase mb-1">Account Status</p>
                <span style="color:#34d399;font-size:.85rem;"><i class="bi bi-shield-fill-check me-1"></i>Active Student</span>
            </div>
        </div>
    </div>

    <!-- Right: Forms -->
    <div class="col-12 col-md-8">

        <!-- Personal Info -->
        <div class="s-card mb-4">
            <h5 class="mb-4 pb-2" style="border-bottom:1px solid rgba(99,102,241,.15);">
                <i class="bi bi-person-lines-fill me-2" style="color:#818cf8;"></i>Personal Information
            </h5>
            <form action="profile.php" method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>
                    </div>
                    <div class="col-12 text-end mt-3">
                        <button type="submit" name="update_profile" class="btn btn-indigo px-4">
                            <i class="bi bi-save me-2"></i>Update Profile
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="s-card mb-4">
            <h5 class="mb-4 pb-2" style="border-bottom:1px solid rgba(99,102,241,.15);">
                <i class="bi bi-shield-lock me-2" style="color:#fbbf24;"></i>Change Password
            </h5>
            <form action="profile.php" method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <div class="col-12 text-end mt-3">
                        <button type="submit" name="change_password" class="btn btn-outline-indigo px-4">
                            <i class="bi bi-key me-2"></i>Change Password
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ── Danger Zone ── -->
        <div class="s-card danger-zone-card">
            <div class="dz-title mb-4"><i class="bi bi-exclamation-octagon-fill me-2"></i>Danger Zone</div>

            <!-- Exit Course -->
            <?php if (!empty($enrolled_courses)): ?>
                <div class="mb-4">
                    <p style="color:#94a3b8;font-size:.85rem;margin-bottom:.75rem;">
                        <i class="bi bi-door-open me-1 text-warning"></i>
                        <strong style="color:#f1f5f9;">Exit a Course</strong> — Unenroll from a course. Your progress will be lost.
                    </p>
                    <?php foreach ($enrolled_courses as $ec): ?>
                        <div class="course-exit-item">
                            <span class="course-exit-name">
                                <i class="bi bi-journal-text me-2" style="color:#818cf8;"></i>
                                <?= htmlspecialchars($ec['course_name']) ?>
                            </span>
                            <button type="button" class="btn btn-sm"
                                    onclick="openExitModal(<?= $ec['id'] ?>, '<?= htmlspecialchars(addslashes($ec['course_name'])) ?>')"
                                    style="background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.25);color:#fbbf24;border-radius:8px;font-size:.78rem;font-weight:600;">
                                <i class="bi bi-door-open me-1"></i>Exit
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr style="border-color:rgba(239,68,68,.15);margin:1.25rem 0;">
            <?php endif; ?>

            <!-- Delete Account -->
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <p style="color:#f1f5f9;font-size:.875rem;font-weight:600;margin:0 0 .25rem;">Delete My Account</p>
                    <p style="color:#64748b;font-size:.8rem;margin:0;">Permanently delete your account and all data. This cannot be undone.</p>
                </div>
                <button type="button" class="btn btn-sm"
                        onclick="document.getElementById('deleteModal').style.display='flex'"
                        style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#f87171;border-radius:10px;font-size:.82rem;font-weight:600;padding:.5rem 1rem;white-space:nowrap;">
                    <i class="bi bi-trash3 me-1"></i>Delete Account
                </button>
            </div>
        </div>

    </div><!-- /col-8 -->
</div><!-- /row -->


<!-- ═══════════════════════════════════════
     EXIT COURSE MODAL
═══════════════════════════════════════ -->
<div id="exitCourseModal"
     style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);">
    <div style="background:#111827;border:1px solid rgba(245,158,11,.25);border-radius:18px;width:100%;max-width:420px;margin:1rem;box-shadow:0 24px 60px rgba(0,0,0,.6);overflow:hidden;animation:fadeUp .25s ease both;">
        <!-- Header -->
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:.75rem;">
            <div style="width:38px;height:38px;border-radius:10px;background:rgba(245,158,11,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-door-open" style="color:#fbbf24;font-size:1.1rem;"></i>
            </div>
            <div>
                <div style="color:#f1f5f9;font-weight:700;font-size:.95rem;">Exit Course</div>
                <div id="exitCourseName" style="color:#64748b;font-size:.78rem;"></div>
            </div>
            <button onclick="closeExitModal()" style="margin-left:auto;background:transparent;border:none;color:#64748b;font-size:1.25rem;line-height:1;cursor:pointer;">&times;</button>
        </div>
        <!-- Body -->
        <form action="profile.php" method="POST" id="exitCourseForm" style="padding:1.5rem;">
            <input type="hidden" name="exit_course" value="1">
            <input type="hidden" name="course_id" id="exitCourseId">
            <div class="s-alert-warning mb-3" style="font-size:.82rem;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                You will lose all progress in this course. This action cannot be undone.
            </div>
            <label style="color:#94a3b8;font-size:.8rem;font-weight:600;display:block;margin-bottom:.45rem;">
                Enter your password to confirm
            </label>
            <input type="password" name="exit_password" class="form-control" placeholder="Your password" required autocomplete="current-password" style="margin-bottom:1.25rem;">
            <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                <button type="button" onclick="closeExitModal()"
                        style="background:rgba(148,163,184,.08);border:1px solid rgba(148,163,184,.15);color:#94a3b8;border-radius:10px;padding:.55rem 1.2rem;font-size:.85rem;font-weight:500;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit"
                        style="background:linear-gradient(135deg,#d97706,#b45309);border:none;color:#fff;border-radius:10px;padding:.55rem 1.2rem;font-size:.85rem;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(217,119,6,.3);">
                    <i class="bi bi-door-open me-1"></i>Exit Course
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ═══════════════════════════════════════
     DELETE ACCOUNT MODAL
═══════════════════════════════════════ -->
<div id="deleteModal"
     style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);">
    <div style="background:#111827;border:1px solid rgba(239,68,68,.25);border-radius:18px;width:100%;max-width:420px;margin:1rem;box-shadow:0 24px 60px rgba(0,0,0,.6),0 0 0 1px rgba(239,68,68,.08);overflow:hidden;animation:fadeUp .25s ease both;">
        <!-- Header -->
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:.75rem;">
            <div style="width:38px;height:38px;border-radius:10px;background:rgba(239,68,68,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-trash3-fill" style="color:#f87171;font-size:1.1rem;"></i>
            </div>
            <div>
                <div style="color:#f1f5f9;font-weight:700;font-size:.95rem;">Delete Account</div>
                <div style="color:#64748b;font-size:.78rem;">This action is permanent</div>
            </div>
            <button onclick="document.getElementById('deleteModal').style.display='none'"
                    style="margin-left:auto;background:transparent;border:none;color:#64748b;font-size:1.25rem;line-height:1;cursor:pointer;">&times;</button>
        </div>
        <!-- Body -->
        <form action="profile.php" method="POST" style="padding:1.5rem;">
            <input type="hidden" name="delete_account" value="1">
            <div class="s-alert-danger mb-3" style="font-size:.82rem;">
                <i class="bi bi-exclamation-octagon-fill me-2"></i>
                All your data — profile, courses, progress, and chat history — will be <strong>permanently deleted</strong>.
            </div>
            <label style="color:#94a3b8;font-size:.8rem;font-weight:600;display:block;margin-bottom:.45rem;">
                Enter your password to confirm
            </label>
            <input type="password" name="delete_password" class="form-control" placeholder="Your password" required autocomplete="current-password" style="margin-bottom:1.25rem;">
            <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('deleteModal').style.display='none'"
                        style="background:rgba(148,163,184,.08);border:1px solid rgba(148,163,184,.15);color:#94a3b8;border-radius:10px;padding:.55rem 1.2rem;font-size:.85rem;font-weight:500;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit"
                        style="background:linear-gradient(135deg,#ef4444,#dc2626);border:none;color:#fff;border-radius:10px;padding:.55rem 1.2rem;font-size:.85rem;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(239,68,68,.35);">
                    <i class="bi bi-trash3 me-1"></i>Delete My Account
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
</style>

<script>
function openExitModal(courseId, courseName) {
    document.getElementById('exitCourseId').value = courseId;
    document.getElementById('exitCourseName').textContent = courseName;
    document.getElementById('exitCourseModal').style.display = 'flex';
}
function closeExitModal() {
    document.getElementById('exitCourseModal').style.display = 'none';
}

// Close modals on backdrop click
document.getElementById('exitCourseModal').addEventListener('click', function(e) {
    if (e.target === this) closeExitModal();
});
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('exitCourseModal').style.display = 'none';
        document.getElementById('deleteModal').style.display = 'none';
    }
});
</script>

<?php require_once 'footer.php'; ?>
