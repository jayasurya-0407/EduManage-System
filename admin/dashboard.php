<?php 
require_once 'header.php';

// Fetch statistics
$totalCourses  = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalMaterials= $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
$totalQuizzes  = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
$totalQuizAttempts  = $pdo->query("SELECT COUNT(*) FROM quiz_attempts")->fetchColumn();
$totalCreditsEarned = $pdo->query("SELECT SUM(points) FROM student_credits")->fetchColumn() ?: 0;

$topStudent = $pdo->query("
    SELECT s.name FROM student_credits sc
    JOIN students s ON sc.student_id = s.student_id
    GROUP BY sc.student_id ORDER BY SUM(sc.points) DESC LIMIT 1
")->fetchColumn() ?: "None";

// Leaderboard - use student_courses join
$leaderboard = $pdo->query("
    SELECT s.name AS student_name,
           GROUP_CONCAT(c.course_name SEPARATOR ', ') AS course_name,
           COALESCE(SUM(sc.points), 0) AS total_credits
    FROM students s
    LEFT JOIN student_courses stc ON s.student_id = stc.student_id
    LEFT JOIN courses c ON stc.course_id = c.id
    LEFT JOIN student_credits sc ON s.student_id = sc.student_id
    GROUP BY s.student_id
    ORDER BY total_credits DESC, student_name ASC
")->fetchAll();
?>

<!-- Stat Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Total Courses</div>
                <div class="stat-value"><?= $totalCourses ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(99,102,241,.12);color:#818cf8;">
                <i class="bi bi-collection-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Students</div>
                <div class="stat-value"><?= $totalStudents ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(16,185,129,.12);color:#34d399;">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Study Materials</div>
                <div class="stat-value"><?= $totalMaterials ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(59,130,246,.12);color:#60a5fa;">
                <i class="bi bi-file-earmark-pdf-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div>
                <div class="stat-label">Quizzes</div>
                <div class="stat-value"><?= $totalQuizzes ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(245,158,11,.12);color:#fbbf24;">
                <i class="bi bi-patch-question-fill"></i>
            </div>
        </div>
    </div>
</div>

<!-- Stat Row 2 -->
<div class="row g-3 mb-5">
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div>
                <div class="stat-label">Quiz Attempts</div>
                <div class="stat-value"><?= $totalQuizAttempts ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(139,92,246,.12);color:#a78bfa;">
                <i class="bi bi-controller"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div>
                <div class="stat-label">Credits Earned</div>
                <div class="stat-value"><?= number_format($totalCreditsEarned) ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(245,158,11,.12);color:#fbbf24;">
                <i class="bi bi-star-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div>
                <div class="stat-label">Top Student</div>
                <div class="stat-value" style="font-size:1.1rem;color:#818cf8;" title="<?= htmlspecialchars($topStudent) ?>"><?= htmlspecialchars($topStudent) ?></div>
            </div>
            <div class="stat-icon" style="background:rgba(239,68,68,.12);color:#f87171;">
                <i class="bi bi-trophy-fill"></i>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Row -->
<div class="row g-4">
    <!-- Quick Actions -->
    <div class="col-12 col-xl-7">
        <div class="content-card h-100">
            <h5 class="fw-bold mb-1" style="color:#f1f5f9;">System Overview</h5>
            <p class="mb-4" style="color:#64748b;font-size:.875rem;">Manage your Life Skills Coaching platform — courses, students, and study materials.</p>

            <div class="row g-3">
                <div class="col-6">
                    <a href="add_course.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none" style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.15);transition:all .2s;" onmouseover="this.style.background='rgba(99,102,241,.15)'" onmouseout="this.style.background='rgba(99,102,241,.08)'">
                        <div class="stat-icon" style="background:rgba(99,102,241,.15);color:#818cf8;width:40px;height:40px;font-size:.9rem;">
                            <i class="bi bi-plus-circle-fill"></i>
                        </div>
                        <span style="color:#c7d2fe;font-weight:500;font-size:.875rem;">Add Course</span>
                    </a>
                </div>
                <div class="col-6">
                    <a href="add_student.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none" style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.15);transition:all .2s;" onmouseover="this.style.background='rgba(16,185,129,.15)'" onmouseout="this.style.background='rgba(16,185,129,.08)'">
                        <div class="stat-icon" style="background:rgba(16,185,129,.15);color:#34d399;width:40px;height:40px;font-size:.9rem;">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <span style="color:#6ee7b7;font-weight:500;font-size:.875rem;">Register Student</span>
                    </a>
                </div>
                <div class="col-6">
                    <a href="add_material.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.15);transition:all .2s;" onmouseover="this.style.background='rgba(59,130,246,.15)'" onmouseout="this.style.background='rgba(59,130,246,.08)'">
                        <div class="stat-icon" style="background:rgba(59,130,246,.15);color:#60a5fa;width:40px;height:40px;font-size:.9rem;">
                            <i class="bi bi-cloud-upload-fill"></i>
                        </div>
                        <span style="color:#93c5fd;font-weight:500;font-size:.875rem;">Upload Material</span>
                    </a>
                </div>
                <div class="col-6">
                    <a href="add_quiz.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none" style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.15);transition:all .2s;" onmouseover="this.style.background='rgba(245,158,11,.15)'" onmouseout="this.style.background='rgba(245,158,11,.08)'">
                        <div class="stat-icon" style="background:rgba(245,158,11,.15);color:#fbbf24;width:40px;height:40px;font-size:.9rem;">
                            <i class="bi bi-patch-plus-fill"></i>
                        </div>
                        <span style="color:#fcd34d;font-weight:500;font-size:.875rem;">Create Quiz</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaderboard -->
    <div class="col-12 col-xl-5">
        <div class="content-card h-100">
            <h5 class="fw-bold mb-4" style="color:#f1f5f9;"><i class="bi bi-bar-chart-fill me-2" style="color:#fbbf24;"></i>Student Credits</h5>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th class="text-end">Credits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($leaderboard) > 0): ?>
                            <?php foreach($leaderboard as $idx => $lb): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;flex-shrink:0;">
                                                <?= strtoupper(substr($lb['student_name'],0,1)) ?>
                                            </div>
                                            <div>
                                                <div style="font-size:.85rem;font-weight:600;color:#e2e8f0;"><?= htmlspecialchars($lb['student_name']) ?></div>
                                                <div style="font-size:.72rem;color:#475569;"><?= htmlspecialchars(substr($lb['course_name'] ?: 'No Course', 0, 25)) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge rounded-pill px-3 py-2 <?= $idx==0 ? 'bg-warning text-dark' : '' ?>" style="<?= $idx!=0 ? 'background:rgba(99,102,241,.2);color:#a5b4fc;' : '' ?>">
                                            <?= $idx==0 ? '<i class="bi bi-trophy-fill me-1"></i>' : '' ?><?= intval($lb['total_credits']) ?> Pts
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" class="text-center py-4" style="color:#475569;">No credits earned yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
