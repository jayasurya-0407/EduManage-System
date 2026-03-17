<?php
require_once 'header.php';

$student_id = $_SESSION['student_id'];
$course_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$course_id) { header("Location: student_dashboard.php"); exit; }

// Verify enrollment
$enroll = $pdo->prepare("SELECT 1 FROM student_courses WHERE student_id = ? AND course_id = ?");
$enroll->execute([$student_id, $course_id]);
if (!$enroll->fetch()) { header("Location: student_dashboard.php"); exit; }

// Course info
$cs = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$cs->execute([$course_id]);
$course = $cs->fetch(PDO::FETCH_ASSOC);
if (!$course) { header("Location: student_dashboard.php"); exit; }

// Materials
$m_stmt = $pdo->prepare("SELECT * FROM materials WHERE course_id = ? ORDER BY id ASC");
$m_stmt->execute([$course_id]);
$materials = $m_stmt->fetchAll(PDO::FETCH_ASSOC);
$total_materials = count($materials);

// Viewed materials
$v_stmt = $pdo->prepare("SELECT material_id FROM progress WHERE student_id = ? AND status='completed'");
$v_stmt->execute([$student_id]);
$viewed_ids = $v_stmt->fetchAll(PDO::FETCH_COLUMN);
$viewed_count = count(array_filter($materials, fn($m) => in_array($m['id'], $viewed_ids)));
$progress_pct = $total_materials > 0 ? round($viewed_count / $total_materials * 100) : 0;

// Quizzes for this course
$q_stmt = $pdo->prepare("
    SELECT q.*, COUNT(qq.id) as question_count,
           (SELECT MAX(qr.score) FROM quiz_results qr WHERE qr.quiz_id = q.id AND qr.student_id = ?) as best_score,
           (SELECT MAX(qr.total_questions) FROM quiz_results qr WHERE qr.quiz_id = q.id AND qr.student_id = ?) as total_q,
           (SELECT sc.points FROM student_credits sc WHERE sc.quiz_id = q.id AND sc.student_id = ? LIMIT 1) as points_earned
    FROM quizzes q
    LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id
    WHERE q.course_id = ?
    GROUP BY q.id
");
$q_stmt->execute([$student_id, $student_id, $student_id, $course_id]);
$quizzes = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
$total_quizzes   = count($quizzes);
$attempted_count = count(array_filter($quizzes, fn($q) => $q['best_score'] !== null));

// Credits for this course
$cr_stmt = $pdo->prepare("
    SELECT SUM(sc.points)
    FROM student_credits sc
    JOIN quizzes q ON sc.quiz_id = q.id
    WHERE sc.student_id = ? AND q.course_id = ?
");
$cr_stmt->execute([$student_id, $course_id]);
$course_credits = $cr_stmt->fetchColumn() ?: 0;

// Quiz completion % for donut chart
$quiz_pct = $total_quizzes > 0 ? round($attempted_count / $total_quizzes * 100) : 0;
?>

<!-- Back + Heading -->
<div style="margin-bottom:1.5rem;">
    <a href="student_dashboard.php" style="color:#818cf8;font-size:.825rem;text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1rem;">
        <i class="bi bi-arrow-left"></i> Back to My Courses
    </a>
    <h2 style="color:#f1f5f9;font-weight:700;margin-bottom:.25rem;"><?= htmlspecialchars($course['course_name']) ?></h2>
    <?php if (!empty($course['description'])): ?>
        <p style="color:#475569;font-size:.875rem;"><?= htmlspecialchars($course['description']) ?></p>
    <?php endif; ?>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-5">
    <div class="col-6 col-md-3">
        <div class="s-card text-center py-3">
            <div style="font-size:1.6rem;font-weight:700;color:#818cf8;"><?= $total_materials ?></div>
            <div style="font-size:.75rem;color:#475569;margin-top:.2rem;">Materials</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="s-card text-center py-3">
            <div style="font-size:1.6rem;font-weight:700;color:#34d399;"><?= $viewed_count ?>/<?= $total_materials ?></div>
            <div style="font-size:.75rem;color:#475569;margin-top:.2rem;">Lessons Viewed</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="s-card text-center py-3">
            <div style="font-size:1.6rem;font-weight:700;color:#fbbf24;">⭐ <?= $course_credits ?></div>
            <div style="font-size:.75rem;color:#475569;margin-top:.2rem;">Credits Earned</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="s-card text-center py-3">
            <div style="font-size:1.6rem;font-weight:700;color:#f87171;"><?= $attempted_count ?>/<?= $total_quizzes ?></div>
            <div style="font-size:.75rem;color:#475569;margin-top:.2rem;">Quizzes Done</div>
        </div>
    </div>
</div>

<!-- Main content + sidebar -->
<div class="row g-4">

    <!-- LEFT: Materials + Quizzes -->
    <div class="col-12 col-xl-8">

        <!-- Overall progress bar -->
        <div class="s-card mb-4">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;">
                <span style="font-weight:600;color:#f1f5f9;">Overall Progress</span>
                <span style="font-size:.8rem;color:#6366f1;font-weight:600;"><?= $progress_pct ?>%</span>
            </div>
            <div class="prog-wrap"><div class="prog-bar" style="width:<?= $progress_pct ?>%;"></div></div>
            <div style="font-size:.75rem;color:#475569;margin-top:.5rem;"><?= $viewed_count ?> of <?= $total_materials ?> lessons completed</div>
        </div>

        <!-- Materials -->
        <div style="font-size:.75rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:.9rem;">
            <i class="bi bi-file-earmark-pdf me-2" style="color:#818cf8;"></i>Study Materials
        </div>
        <?php if (empty($materials)): ?>
            <div class="s-alert-warning mb-4"><i class="bi bi-info-circle me-2"></i>No materials uploaded for this course yet.</div>
        <?php else: ?>
            <div class="s-card p-0 mb-5" style="overflow:hidden;">
                <?php foreach ($materials as $idx => $mat):
                    $is_viewed = in_array($mat['id'], $viewed_ids);
                ?>
                    <div style="
                        display:flex;align-items:center;justify-content:space-between;
                        padding:1rem 1.25rem;
                        border-bottom:<?= $idx < count($materials)-1 ? '1px solid rgba(255,255,255,.05)' : 'none' ?>;
                        transition:background .15s;
                    " onmouseover="this.style.background='rgba(99,102,241,.06)'" onmouseout="this.style.background='transparent'">
                        <div style="display:flex;align-items:center;gap:.875rem;flex:1;overflow:hidden;">
                            <div style="
                                width:32px;height:32px;border-radius:8px;flex-shrink:0;
                                background:<?= $is_viewed ? 'rgba(16,185,129,.12)' : 'rgba(99,102,241,.1)' ?>;
                                color:<?= $is_viewed ? '#34d399' : '#818cf8' ?>;
                                display:flex;align-items:center;justify-content:center;font-size:.9rem;
                            ">
                                <?php if ($is_viewed): ?>
                                    <i class="bi bi-check-circle-fill"></i>
                                <?php else: ?>
                                    <span style="font-size:.7rem;font-weight:700;"><?= str_pad($idx+1, 2, '0', STR_PAD_LEFT) ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="overflow:hidden;">
                                <div style="font-size:.875rem;font-weight:600;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars($mat['title']) ?>
                                </div>
                                <div style="font-size:.7rem;color:#475569;"><?= $is_viewed ? 'Completed' : 'Not viewed yet' ?></div>
                            </div>
                        </div>
                        <div style="display:flex;gap:.5rem;flex-shrink:0;margin-left:1rem;">
                            <a href="view_material.php?id=<?= $mat['id'] ?>" target="_blank"
                               style="font-size:.75rem;padding:.35rem .8rem;background:<?= $is_viewed ? 'rgba(99,102,241,.1)' : 'rgba(99,102,241,.2)' ?>;color:#a5b4fc;border-radius:8px;text-decoration:none;font-weight:500;border:1px solid rgba(99,102,241,.2);transition:all .15s;"
                               onmouseover="this.style.background='rgba(99,102,241,.3)'" onmouseout="this.style.background='<?= $is_viewed ? 'rgba(99,102,241,.1)' : 'rgba(99,102,241,.2)' ?>'">
                                <i class="bi bi-play-fill me-1"></i>View
                            </a>
                            <a href="download_material.php?id=<?= $mat['id'] ?>"
                               style="font-size:.75rem;padding:.35rem .8rem;background:rgba(16,185,129,.1);color:#34d399;border-radius:8px;text-decoration:none;font-weight:500;border:1px solid rgba(16,185,129,.15);transition:all .15s;"
                               onmouseover="this.style.background='rgba(16,185,129,.2)'" onmouseout="this.style.background='rgba(16,185,129,.1)'">
                                <i class="bi bi-download me-1"></i>Save
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Quizzes -->
        <div style="font-size:.75rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:.9rem;">
            <i class="bi bi-patch-question me-2" style="color:#fbbf24;"></i>Quizzes
        </div>
        <?php if (empty($quizzes)): ?>
            <div class="s-alert-warning"><i class="bi bi-info-circle me-2"></i>No quizzes available for this course yet.</div>
        <?php else: ?>
            <div class="s-card p-0" style="overflow:hidden;">
                <?php foreach ($quizzes as $idx => $qz):
                    $attempted = $qz['best_score'] !== null;
                    $score_pct = ($attempted && $qz['total_q'] > 0) ? round($qz['best_score']/$qz['total_q']*100) : 0;
                ?>
                    <div style="
                        display:flex;align-items:center;justify-content:space-between;
                        padding:1rem 1.25rem;
                        border-bottom:<?= $idx < count($quizzes)-1 ? '1px solid rgba(255,255,255,.05)' : 'none' ?>;
                    ">
                        <div style="display:flex;align-items:center;gap:.875rem;flex:1;overflow:hidden;">
                            <div style="
                                width:32px;height:32px;border-radius:8px;flex-shrink:0;
                                background:<?= $attempted ? 'rgba(245,158,11,.12)' : 'rgba(99,102,241,.1)' ?>;
                                color:<?= $attempted ? '#fbbf24' : '#818cf8' ?>;
                                display:flex;align-items:center;justify-content:center;font-size:.9rem;
                            ">
                                <i class="bi bi-<?= $attempted ? 'patch-check-fill' : 'patch-question' ?>"></i>
                            </div>
                            <div>
                                <div style="font-size:.875rem;font-weight:600;color:#e2e8f0;"><?= htmlspecialchars($qz['title']) ?></div>
                                <div style="font-size:.7rem;color:#475569;">
                                    <?= $qz['question_count'] ?> questions
                                    <?php if ($attempted): ?>
                                        · Best: <span style="color:#34d399;font-weight:600;"><?= $qz['best_score'] ?>/<?= $qz['total_q'] ?> (<?= $score_pct ?>%)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:.75rem;flex-shrink:0;margin-left:1rem;">
                            <?php if ($qz['points_earned']): ?>
                                <span style="font-size:.72rem;background:rgba(245,158,11,.12);color:#fbbf24;padding:.25em .65em;border-radius:6px;font-weight:600;">⭐ <?= $qz['points_earned'] ?> pts</span>
                            <?php endif; ?>
                            <a href="quiz.php?id=<?= $qz['id'] ?>"
                               style="font-size:.75rem;padding:.35rem .9rem;background:linear-gradient(135deg,#6f4cff,#7c3aed);color:#fff;border-radius:8px;text-decoration:none;font-weight:600;box-shadow:0 4px 10px rgba(111,76,255,.25);transition:all .2s;"
                               onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                                <?= $attempted ? 'Retake' : 'Start' ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT: Donut Chart Sidebar -->
    <div class="col-12 col-xl-4">

        <!-- Quiz Progress Donut -->
        <div class="s-card text-center mb-4">
            <div style="font-size:.75rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:1.25rem;">Quiz Completion</div>
            <div style="position:relative;width:160px;height:160px;margin:0 auto 1.25rem;">
                <canvas id="quizDonut" width="160" height="160"></canvas>
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                    <span style="font-size:1.75rem;font-weight:700;color:#f1f5f9;"><?= $quiz_pct ?>%</span>
                    <span style="font-size:.7rem;color:#475569;">completed</span>
                </div>
            </div>
            <div style="font-size:.8rem;color:#475569;"><?= $attempted_count ?> of <?= $total_quizzes ?> quizzes attempted</div>
        </div>

        <!-- Material Progress Donut -->
        <div class="s-card text-center mb-4">
            <div style="font-size:.75rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:1.25rem;">Material Progress</div>
            <div style="position:relative;width:160px;height:160px;margin:0 auto 1.25rem;">
                <canvas id="materialDonut" width="160" height="160"></canvas>
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                    <span style="font-size:1.75rem;font-weight:700;color:#f1f5f9;"><?= $progress_pct ?>%</span>
                    <span style="font-size:.7rem;color:#475569;">viewed</span>
                </div>
            </div>
            <div style="font-size:.8rem;color:#475569;"><?= $viewed_count ?> of <?= $total_materials ?> materials viewed</div>
        </div>

        <!-- Credits Card -->
        <div class="s-card text-center">
            <i class="bi bi-star-fill" style="font-size:2rem;color:#fbbf24;"></i>
            <div style="font-size:2rem;font-weight:700;color:#f1f5f9;margin:.5rem 0 .25rem;"><?= $course_credits ?></div>
            <div style="font-size:.8rem;color:#64748b;">Credits earned in this course</div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function makeDonut(id, pct, color) {
    const ctx = document.getElementById(id).getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [pct, 100 - pct],
                backgroundColor: [color, 'rgba(255,255,255,.06)'],
                borderWidth: 0,
                hoverOffset: 0,
            }]
        },
        options: {
            cutout: '75%',
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            animation: { animateRotate: true, duration: 900 },
        }
    });
}
makeDonut('quizDonut',     <?= $quiz_pct ?>,     '#6f4cff');
makeDonut('materialDonut', <?= $progress_pct ?>, '#34d399');
</script>

<?php require_once 'footer.php'; ?>
