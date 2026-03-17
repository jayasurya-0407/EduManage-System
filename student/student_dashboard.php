<?php
require_once 'header.php';

$student_id = $_SESSION['student_id'];

// Get all enrolled courses with progress
$stmt = $pdo->prepare("
    SELECT c.id, c.course_name, c.description,
           COUNT(DISTINCT m.id) AS total_materials,
           COUNT(DISTINCT p.material_id) AS viewed_materials,
           COUNT(DISTINCT q.id) AS total_quizzes,
           COALESCE(SUM(sc.points), 0) AS total_credits
    FROM student_courses stc
    JOIN courses c ON stc.course_id = c.id
    LEFT JOIN materials m ON m.course_id = c.id
    LEFT JOIN progress p ON p.material_id = m.id AND p.student_id = ?
    LEFT JOIN quizzes q ON q.course_id = c.id
    LEFT JOIN student_credits sc ON sc.student_id = ? AND sc.quiz_id IN (
        SELECT id FROM quizzes WHERE course_id = c.id
    )
    WHERE stc.student_id = ?
    GROUP BY c.id, c.course_name, c.description
");
$stmt->execute([$student_id, $student_id, $student_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Overall student credits
$tp = $pdo->prepare("SELECT SUM(points) FROM student_credits WHERE student_id = ?");
$tp->execute([$student_id]);
$total_points = $tp->fetchColumn() ?: 0;
?>

<!-- Page Heading -->
<div style="margin-bottom:2rem;">
    <div style="font-size:.75rem;color:#6366f1;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:.35rem;">Student Dashboard</div>
    <h2 style="color:#f1f5f9;font-weight:700;margin-bottom:.25rem;">Your Courses</h2>
    <p style="color:#475569;font-size:.875rem;">Select a course to view its materials, quizzes, and your progress.</p>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-5">
    <div class="col-6 col-md-3">
        <div class="s-card text-center py-3">
            <div style="font-size:1.75rem;font-weight:700;color:#818cf8;"><?= count($courses) ?></div>
            <div style="font-size:.75rem;color:#475569;margin-top:.2rem;">Enrolled Courses</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="s-card text-center py-3">
            <div style="font-size:1.75rem;font-weight:700;color:#fbbf24;">⭐ <?= number_format($total_points) ?></div>
            <div style="font-size:.75rem;color:#475569;margin-top:.2rem;">Total Credits</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="s-card text-center py-3">
            <?php
                $totalM = array_sum(array_column($courses,'total_materials'));
                $viewedM = array_sum(array_column($courses,'viewed_materials'));
                $overallPct = $totalM > 0 ? round($viewedM/$totalM*100) : 0;
            ?>
            <div style="font-size:1.75rem;font-weight:700;color:#34d399;"><?= $overallPct ?>%</div>
            <div style="font-size:.75rem;color:#475569;margin-top:.2rem;">Overall Progress</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="s-card text-center py-3">
            <div style="font-size:1.75rem;font-weight:700;color:#f87171;"><?= array_sum(array_column($courses,'total_quizzes')) ?></div>
            <div style="font-size:.75rem;color:#475569;margin-top:.2rem;">Total Quizzes</div>
        </div>
    </div>
</div>

<!-- Course Cards Grid -->
<?php if (empty($courses)): ?>
    <div class="s-alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>You are not enrolled in any courses yet. Please contact your administrator.</div>
<?php else: ?>
    <div style="font-size:.75rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:1rem;">YOUR PROGRESS</div>
    <div class="row g-4">
        <?php
        $gradients = [
            'linear-gradient(135deg,#4f46e5,#7c3aed)',
            'linear-gradient(135deg,#0891b2,#0e7490)',
            'linear-gradient(135deg,#059669,#047857)',
            'linear-gradient(135deg,#d97706,#b45309)',
            'linear-gradient(135deg,#dc2626,#b91c1c)',
            'linear-gradient(135deg,#7c3aed,#6d28d9)',
        ];
        foreach ($courses as $idx => $course):
            $pct = $course['total_materials'] > 0
                ? round($course['viewed_materials'] / $course['total_materials'] * 100) : 0;
            $grad = $gradients[$idx % count($gradients)];
            $icon = 'bi-journal-bookmark-fill';
        ?>
        <div class="col-12 col-md-6 col-lg-4">
            <a href="course.php?id=<?= $course['id'] ?>" class="text-decoration-none">
                <div class="course-card" style="
                    background:#111827; border:1px solid rgba(99,102,241,.12);
                    border-radius:18px; overflow:hidden; transition:all .3s;
                    cursor:pointer; position:relative;
                ">
                    <!-- Coloured top bar -->
                    <div style="background:<?= $grad ?>;padding:1.5rem;position:relative;overflow:hidden;">
                        <div style="position:absolute;top:-20px;right:-20px;width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,.08);"></div>
                        <div style="position:absolute;bottom:-30px;left:-10px;width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,.05);"></div>
                        <div style="font-size:.65rem;font-weight:600;color:rgba(255,255,255,.6);letter-spacing:1px;text-transform:uppercase;margin-bottom:.35rem;">COURSE</div>
                        <h5 style="color:#fff;font-weight:700;margin-bottom:0;position:relative;z-index:1;"><?= htmlspecialchars($course['course_name']) ?></h5>
                        <div style="position:absolute;top:1.25rem;right:1.25rem;">
                            <div style="width:44px;height:44px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                                <i class="bi <?= $icon ?>" style="font-size:1.3rem;color:#fff;"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom info -->
                    <div style="padding:1.25rem;">
                        <!-- Progress bar -->
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
                            <span style="font-size:.75rem;color:#64748b;"><?= $pct ?>% complete</span>
                            <span style="font-size:.72rem;color:#475569;"><?= $course['viewed_materials'] ?>/<?= $course['total_materials'] ?> lessons</span>
                        </div>
                        <div style="background:rgba(255,255,255,.06);border-radius:6px;height:6px;overflow:hidden;margin-bottom:1rem;">
                            <div style="width:<?= $pct ?>%;height:100%;background:<?= $grad ?>;border-radius:6px;transition:width .6s;"></div>
                        </div>

                        <!-- Meta row -->
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <div style="display:flex;gap:1rem;">
                                <span style="font-size:.75rem;color:#64748b;"><i class="bi bi-file-earmark-pdf me-1" style="color:#818cf8;"></i><?= $course['total_materials'] ?> materials</span>
                                <span style="font-size:.75rem;color:#64748b;"><i class="bi bi-patch-question me-1" style="color:#fbbf24;"></i><?= $course['total_quizzes'] ?> quizzes</span>
                            </div>
                            <?php if ($course['total_credits'] > 0): ?>
                            <span style="font-size:.72rem;background:rgba(245,158,11,.12);color:#fbbf24;padding:.25em .65em;border-radius:6px;font-weight:600;">⭐ <?= $course['total_credits'] ?> pts</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Hover arrow -->
                    <div style="position:absolute;bottom:1.25rem;right:1.25rem;opacity:0;transition:opacity .2s;" class="course-arrow">
                        <i class="bi bi-arrow-right-circle-fill" style="font-size:1.3rem;color:#6366f1;"></i>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.course-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(99,102,241,.18); border-color: rgba(99,102,241,.3) !important; }
.course-card:hover .course-arrow { opacity:1 !important; }
</style>

<?php require_once 'footer.php'; ?>
