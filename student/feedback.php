<?php
require_once 'header.php';

$student_id = $_SESSION['student_id'];
$success = $error = '';

// Handle POST — submit new feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($subject === '' || $message === '') {
        $error = 'Please fill in both Subject and Message.';
    } else {
        $ins = $pdo->prepare("INSERT INTO feedback (student_id, subject, message) VALUES (?, ?, ?)");
        $ins->execute([$student_id, $subject, $message]);
        $success = 'Your feedback has been submitted successfully!';
    }
}

// Fetch past submissions
$fStmt = $pdo->prepare("SELECT * FROM feedback WHERE student_id = ? ORDER BY created_at DESC");
$fStmt->execute([$student_id]);
$feedbacks = $fStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Heading -->
<div style="margin-bottom:2rem;">
    <div style="font-size:.75rem;color:#6366f1;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:.35rem;">Communication</div>
    <h2 style="color:#f1f5f9;font-weight:700;margin-bottom:.25rem;">Feedback</h2>
    <p style="color:#475569;font-size:.875rem;">Share your thoughts, suggestions, or concerns with the admin.</p>
</div>

<?php if ($success): ?>
    <div class="s-alert-success mb-4"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="s-alert-danger mb-4"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Submit Form -->
    <div class="col-12 col-lg-5">
        <div class="s-card h-100">
            <h5 style="margin-bottom:1.25rem;"><i class="bi bi-pencil-square me-2" style="color:#6366f1;"></i>New Feedback</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="e.g. Course content suggestion" required maxlength="255">
                </div>
                <div class="mb-4">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="6" placeholder="Write your feedback here…" required></textarea>
                </div>
                <button type="submit" name="submit_feedback" class="btn btn-indigo w-100 py-2">
                    <i class="bi bi-send-fill me-2"></i>Submit Feedback
                </button>
            </form>
        </div>
    </div>

    <!-- Past Submissions -->
    <div class="col-12 col-lg-7">
        <div class="s-card">
            <h5 style="margin-bottom:1.25rem;"><i class="bi bi-clock-history me-2" style="color:#a78bfa;"></i>My Submissions</h5>
            <?php if (empty($feedbacks)): ?>
                <div class="s-alert-warning"><i class="bi bi-info-circle me-2"></i>You haven't submitted any feedback yet.</div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:.85rem;max-height:520px;overflow-y:auto;padding-right:.25rem;">
                    <?php foreach ($feedbacks as $fb): ?>
                        <?php $isOpen = $fb['status'] === 'open'; ?>
                        <div style="background:#1e293b;border:1px solid <?= $isOpen ? 'rgba(99,102,241,.2)' : 'rgba(16,185,129,.2)' ?>;border-radius:14px;padding:1.1rem;">
                            <!-- Header row -->
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;margin-bottom:.6rem;">
                                <div style="font-size:.88rem;font-weight:600;color:#e2e8f0;"><?= htmlspecialchars($fb['subject']) ?></div>
                                <?php if ($isOpen): ?>
                                    <span style="flex-shrink:0;background:rgba(99,102,241,.15);color:#a5b4fc;font-size:.65rem;font-weight:700;padding:.25em .75em;border-radius:20px;letter-spacing:.5px;">OPEN</span>
                                <?php else: ?>
                                    <span style="flex-shrink:0;background:rgba(16,185,129,.15);color:#34d399;font-size:.65rem;font-weight:700;padding:.25em .75em;border-radius:20px;letter-spacing:.5px;">RESOLVED</span>
                                <?php endif; ?>
                            </div>
                            <!-- Message -->
                            <p style="font-size:.8rem;color:#64748b;margin-bottom:.6rem;line-height:1.6;"><?= nl2br(htmlspecialchars($fb['message'])) ?></p>
                            <!-- Admin Reply -->
                            <?php if (!empty($fb['admin_reply'])): ?>
                                <div style="background:rgba(99,102,241,.08);border-left:3px solid #6366f1;border-radius:0 8px 8px 0;padding:.65rem .85rem;margin-top:.6rem;">
                                    <div style="font-size:.68rem;color:#6366f1;font-weight:600;margin-bottom:.25rem;"><i class="bi bi-shield-check me-1"></i>Admin Reply</div>
                                    <p style="font-size:.8rem;color:#c7d2fe;margin:0;line-height:1.6;"><?= nl2br(htmlspecialchars($fb['admin_reply'])) ?></p>
                                    <div style="font-size:.68rem;color:#475569;margin-top:.35rem;"><?= date('M j, Y g:i A', strtotime($fb['replied_at'])) ?></div>
                                </div>
                            <?php endif; ?>
                            <!-- Date -->
                            <div style="font-size:.68rem;color:#334155;margin-top:.5rem;"><i class="bi bi-calendar3 me-1"></i><?= date('M j, Y g:i A', strtotime($fb['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
