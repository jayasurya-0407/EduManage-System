<?php
require_once 'header.php';

$stmt = $pdo->query("
SELECT 
    s.name AS student_name,
    c.course_name,
    q.title AS quiz_title,
    a.score,
    (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) AS total_questions,
    a.attempt_number,
    a.submitted_at
FROM quiz_attempts a
JOIN students s ON a.student_id = s.student_id
JOIN quizzes q ON a.quiz_id = q.id
LEFT JOIN courses c ON q.course_id = c.id
ORDER BY a.submitted_at DESC
");

$results = $stmt->fetchAll();
?>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4 text-dark">
            <i class="bi bi-card-checklist me-2 text-primary"></i> Student Quiz Results
        </h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Quiz</th>
                        <th>Score</th>
                        <th>Attempt</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($results) > 0): ?>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                <td><?= htmlspecialchars($row['course_name']) ?></td>
                                <td><?= htmlspecialchars($row['quiz_title']) ?></td>
                                <td>
                                    <span class="badge bg-primary rounded-pill">
                                        <?= $row['score'] ?>/<?= $row['total_questions'] ?>
                                    </span>
                                </td>
                                <td>Attempt <?= $row['attempt_number'] ?></td>
                                <td><?= date('M j, Y', strtotime($row['submitted_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">
                                No quiz attempts found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>