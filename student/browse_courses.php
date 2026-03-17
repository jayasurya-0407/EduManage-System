<?php
require_once 'header.php';

$student_id = $_SESSION['student_id'];

// Handle enrollment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_course_id'])) {
    $cid = (int)$_POST['enroll_course_id'];
    // Check not already enrolled
    $chk = $pdo->prepare("SELECT 1 FROM student_courses WHERE student_id = ? AND course_id = ?");
    $chk->execute([$student_id, $cid]);
    if (!$chk->fetch()) {
        $ins = $pdo->prepare("INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
        $ins->execute([$student_id, $cid]);
        $flash_success = "You have been enrolled successfully!";
    } else {
        $flash_error = "You are already enrolled in this course.";
    }
}

// Handle un-enroll POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unenroll_course_id'])) {
    $cid = (int)$_POST['unenroll_course_id'];
    $del = $pdo->prepare("DELETE FROM student_courses WHERE student_id = ? AND course_id = ?");
    $del->execute([$student_id, $cid]);
    $flash_success = "You have been unenrolled from the course.";
}

// Fetch all courses + whether student is enrolled
$stmt = $pdo->prepare("
    SELECT c.*,
           COUNT(DISTINCT m.id) AS material_count,
           COUNT(DISTINCT q.id) AS quiz_count,
           IF(sc.student_id IS NOT NULL, 1, 0) AS enrolled
    FROM courses c
    LEFT JOIN materials m ON m.course_id = c.id
    LEFT JOIN quizzes q   ON q.course_id = c.id
    LEFT JOIN student_courses sc ON sc.course_id = c.id AND sc.student_id = ?
    GROUP BY c.id
    ORDER BY c.course_name ASC
");
$stmt->execute([$student_id]);
$all_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$enrolled_count  = count(array_filter($all_courses, fn($c) => $c['enrolled']));
$available_count = count($all_courses) - $enrolled_count;

$gradients = [
    'linear-gradient(135deg,#4f46e5,#7c3aed)',
    'linear-gradient(135deg,#0891b2,#0e7490)',
    'linear-gradient(135deg,#059669,#047857)',
    'linear-gradient(135deg,#d97706,#b45309)',
    'linear-gradient(135deg,#dc2626,#b91c1c)',
    'linear-gradient(135deg,#7c3aed,#6d28d9)',
];

?>

<!-- Page heading -->
<div style="margin-bottom:2rem;">
    <div style="font-size:.75rem;color:#6366f1;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:.35rem;">Explore &amp; Enroll</div>
    <h2 style="color:#f1f5f9;font-weight:700;margin-bottom:.25rem;">Available Courses</h2>
    <p style="color:#475569;font-size:.875rem;">Browse all courses and enroll in the ones you want to join.</p>
</div>

<!-- Flash messages -->
<?php if (!empty($flash_success)): ?>
    <div class="s-alert-success mb-4"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flash_success) ?></div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
    <div class="s-alert-danger mb-4"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($flash_error) ?></div>
<?php endif; ?>

<!-- Summary chips -->
<div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:2rem;">
    <div style="background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.2);color:#a5b4fc;padding:.4rem 1rem;border-radius:20px;font-size:.8rem;font-weight:500;">
        <i class="bi bi-collection me-1"></i><?= count($all_courses) ?> Total Courses
    </div>
    <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);color:#34d399;padding:.4rem 1rem;border-radius:20px;font-size:.8rem;font-weight:500;">
        <i class="bi bi-check-circle me-1"></i><?= $enrolled_count ?> Enrolled
    </div>
    <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);color:#fbbf24;padding:.4rem 1rem;border-radius:20px;font-size:.8rem;font-weight:500;">
        <i class="bi bi-plus-circle me-1"></i><?= $available_count ?> Available to Join
    </div>
</div>

<?php if (empty($all_courses)): ?>
    <div class="s-alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>No courses have been created yet. Please check back later.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($all_courses as $idx => $c):
            $grad = $gradients[$idx % count($gradients)];
            $icon = 'bi-journal-bookmark-fill';
            $is_enrolled = (bool)$c['enrolled'];
        ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div style="
                background:#111827;
                border:1px solid <?= $is_enrolled ? 'rgba(16,185,129,.25)' : 'rgba(99,102,241,.12)' ?>;
                border-radius:18px; overflow:hidden;
                transition:all .3s; position:relative;
                <?= $is_enrolled ? 'box-shadow:0 0 0 1px rgba(16,185,129,.1);' : '' ?>
            " class="h-100 d-flex flex-column course-browse-card">

                <!-- Coloured header strip -->
                <div style="background:<?= $grad ?>;padding:1.4rem;position:relative;overflow:hidden;">
                    <div style="position:absolute;top:-15px;right:-15px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.08);"></div>
                    <div style="font-size:.62rem;font-weight:600;color:rgba(255,255,255,.6);letter-spacing:1px;text-transform:uppercase;margin-bottom:.3rem;">COURSE</div>
                    <h5 style="color:#fff;font-weight:700;margin-bottom:0;position:relative;z-index:1;font-size:1.1rem;"><?= htmlspecialchars($c['course_name']) ?></h5>
                    <!-- Icon badge -->
                    <div style="position:absolute;top:1.1rem;right:1.1rem;width:40px;height:40px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi <?= $icon ?>" style="font-size:1.15rem;color:#fff;"></i>
                    </div>
                    <!-- Enrolled badge -->
                    <?php if ($is_enrolled): ?>
                    <div style="position:absolute;bottom:.85rem;right:1rem;">
                        <span style="background:rgba(16,185,129,.85);color:#fff;font-size:.65rem;font-weight:700;padding:.25em .65em;border-radius:20px;letter-spacing:.5px;">
                            <i class="bi bi-check2 me-1"></i>ENROLLED
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Body -->
                <div style="padding:1.25rem;flex:1;display:flex;flex-direction:column;">
                    <?php if (!empty($c['description'])): ?>
                        <p style="font-size:.82rem;color:#64748b;margin-bottom:.85rem;flex:1;line-height:1.5;"><?= htmlspecialchars($c['description']) ?></p>
                    <?php else: ?>
                        <p style="font-size:.82rem;color:#334155;margin-bottom:.85rem;flex:1;font-style:italic;">No description provided.</p>
                    <?php endif; ?>

                    <!-- Meta row -->
                    <div style="display:flex;gap:1rem;margin-bottom:1rem;">
                        <span style="font-size:.75rem;color:#64748b;"><i class="bi bi-file-earmark-pdf me-1" style="color:#818cf8;"></i><?= $c['material_count'] ?> materials</span>
                        <span style="font-size:.75rem;color:#64748b;"><i class="bi bi-patch-question me-1" style="color:#fbbf24;"></i><?= $c['quiz_count'] ?> quizzes</span>
                    </div>

                    <!-- Action button -->
                    <?php if ($is_enrolled): ?>
                        <div style="display:flex;gap:.5rem;">
                            <a href="course.php?id=<?= $c['id'] ?>"
                               style="flex:1;text-align:center;padding:.65rem;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border-radius:10px;font-size:.82rem;font-weight:600;text-decoration:none;transition:all .2s;"
                               onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                                <i class="bi bi-play-fill me-1"></i>Go to Course
                            </a>
                            <form method="POST" style="flex-shrink:0;">
                                <input type="hidden" name="unenroll_course_id" value="<?= $c['id'] ?>">
                                <button type="submit"
                                    onclick="return confirm('Are you sure you want to unenroll from this course?')"
                                    style="padding:.65rem .85rem;background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);border-radius:10px;font-size:.82rem;cursor:pointer;transition:all .2s;"
                                    onmouseover="this.style.background='rgba(239,68,68,.2)'" onmouseout="this.style.background='rgba(239,68,68,.1)'">
                                    <i class="bi bi-dash-circle"></i>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="enroll_course_id" value="<?= $c['id'] ?>">
                            <button type="submit" style="
                                width:100%;padding:.7rem;
                                background:rgba(99,102,241,.1);
                                color:#a5b4fc;border:1.5px solid rgba(99,102,241,.25);
                                border-radius:10px;font-size:.85rem;font-weight:600;
                                cursor:pointer;transition:all .25s;font-family:'Poppins',sans-serif;
                            "
                            onmouseover="this.style.background='rgba(99,102,241,.2)';this.style.borderColor='rgba(99,102,241,.5)';this.style.color='#c7d2fe';"
                            onmouseout="this.style.background='rgba(99,102,241,.1)';this.style.borderColor='rgba(99,102,241,.25)';this.style.color='#a5b4fc';">
                                <i class="bi bi-plus-circle me-1"></i>Enroll Now
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.course-browse-card:hover { transform: translateY(-5px); box-shadow: 0 16px 36px rgba(99,102,241,.15) !important; }
</style>

<?php require_once 'footer.php'; ?>
