<?php
require_once 'header.php';

$success = $error = '';

// Handle reply POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_feedback'])) {
    $fid   = (int)($_POST['feedback_id'] ?? 0);
    $reply = trim($_POST['admin_reply'] ?? '');
    if ($fid && $reply !== '') {
        $upd = $pdo->prepare("UPDATE feedback SET admin_reply = ?, replied_at = NOW(), status = 'closed' WHERE id = ?");
        $upd->execute([$reply, $fid]);
        $success = 'Reply sent and feedback marked as resolved.';
    } else {
        $error = 'Reply cannot be empty.';
    }
}

// Handle mark open
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_open'])) {
    $fid = (int)($_POST['feedback_id'] ?? 0);
    if ($fid) {
        $pdo->prepare("UPDATE feedback SET status = 'open', admin_reply = NULL, replied_at = NULL WHERE id = ?")->execute([$fid]);
        $success = 'Feedback re-opened.';
    }
}

// Fetch all feedback with student info
$rows = $pdo->query("
    SELECT f.*, s.name AS student_name, s.email AS student_email
    FROM feedback f
    JOIN students s ON s.student_id = f.student_id
    ORDER BY f.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$open_count   = count(array_filter($rows, fn($r) => $r['status'] === 'open'));
$closed_count = count($rows) - $open_count;

// Which feedback to expand (from GET or POST redirect)
$expandId = (int)($_GET['id'] ?? 0);
?>

<script>document.getElementById('page-title').textContent = 'Feedback Inbox';</script>

<!-- Heading -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.75rem;">
    <div>
        <div style="font-size:.72rem;color:#6366f1;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:.3rem;">Communication</div>
        <h2 style="color:#f1f5f9;font-weight:700;margin-bottom:.25rem;">Feedback Inbox</h2>
        <p style="color:#64748b;font-size:.85rem;margin:0;">Review and reply to student feedback submissions.</p>
    </div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <div style="background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.2);color:#a5b4fc;padding:.45rem 1.1rem;border-radius:20px;font-size:.8rem;font-weight:600;">
            <i class="bi bi-envelope-open me-1"></i><?= $open_count ?> Open
        </div>
        <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);color:#34d399;padding:.45rem 1.1rem;border-radius:20px;font-size:.8rem;font-weight:600;">
            <i class="bi bi-check-circle me-1"></i><?= $closed_count ?> Resolved
        </div>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (empty($rows)): ?>
    <div class="alert alert-info"><i class="bi bi-inbox me-2"></i>No feedback submissions yet.</div>
<?php else: ?>
    <div style="display:flex;flex-direction:column;gap:1rem;">
        <?php foreach ($rows as $fb):
            $isOpen = $fb['status'] === 'open';
            $isExpanded = ($expandId === (int)$fb['id']);
        ?>
        <div style="background:#111827;border:1px solid <?= $isOpen ? 'rgba(99,102,241,.18)' : 'rgba(16,185,129,.18)' ?>;border-radius:16px;overflow:hidden;">
            <!-- Header row -->
            <div style="display:flex;align-items:center;gap:1rem;padding:1.1rem 1.4rem;cursor:pointer;flex-wrap:wrap;"
                 onclick="toggleFeedback(<?= $fb['id'] ?>)">
                <!-- Avatar -->
                <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:700;color:#fff;flex-shrink:0;">
                    <?= strtoupper(substr($fb['student_name'], 0, 1)) ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.875rem;font-weight:600;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($fb['student_name']) ?></div>
                    <div style="font-size:.72rem;color:#475569;"><?= htmlspecialchars($fb['student_email']) ?></div>
                </div>
                <div style="flex:2;min-width:0;">
                    <div style="font-size:.83rem;color:#e2e8f0;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($fb['subject']) ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:.75rem;flex-shrink:0;">
                    <?php if ($isOpen): ?>
                        <span style="background:rgba(99,102,241,.15);color:#a5b4fc;font-size:.65rem;font-weight:700;padding:.25em .75em;border-radius:20px;letter-spacing:.5px;">OPEN</span>
                    <?php else: ?>
                        <span style="background:rgba(16,185,129,.15);color:#34d399;font-size:.65rem;font-weight:700;padding:.25em .75em;border-radius:20px;letter-spacing:.5px;">RESOLVED</span>
                    <?php endif; ?>
                    <div style="font-size:.7rem;color:#334155;"><?= date('M j, Y', strtotime($fb['created_at'])) ?></div>
                    <i class="bi bi-chevron-down" style="color:#475569;transition:transform .2s;" id="chevron-<?= $fb['id'] ?>"></i>
                </div>
            </div>

            <!-- Expandable content -->
            <div id="fb-detail-<?= $fb['id'] ?>" style="display:<?= $isExpanded ? 'block' : 'none' ?>;border-top:1px solid rgba(255,255,255,.05);">
                <div style="padding:1.25rem 1.4rem;">
                    <!-- Student message -->
                    <div style="background:#1e293b;border-radius:12px;padding:1rem;margin-bottom:1rem;">
                        <div style="font-size:.7rem;color:#475569;font-weight:600;margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.5px;">Student Message</div>
                        <p style="font-size:.875rem;color:#cbd5e1;margin:0;line-height:1.65;"><?= nl2br(htmlspecialchars($fb['message'])) ?></p>
                        <div style="font-size:.68rem;color:#334155;margin-top:.6rem;"><i class="bi bi-calendar3 me-1"></i><?= date('M j, Y g:i A', strtotime($fb['created_at'])) ?></div>
                    </div>

                    <?php if (!empty($fb['admin_reply'])): ?>
                        <!-- Existing reply -->
                        <div style="background:rgba(99,102,241,.08);border-left:3px solid #6366f1;border-radius:0 12px 12px 0;padding:1rem;margin-bottom:1rem;">
                            <div style="font-size:.7rem;color:#6366f1;font-weight:600;margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.5px;"><i class="bi bi-shield-check me-1"></i>Your Reply</div>
                            <p style="font-size:.875rem;color:#c7d2fe;margin:0;line-height:1.65;"><?= nl2br(htmlspecialchars($fb['admin_reply'])) ?></p>
                            <div style="font-size:.68rem;color:#475569;margin-top:.5rem;"><?= date('M j, Y g:i A', strtotime($fb['replied_at'])) ?></div>
                        </div>
                        <!-- Re-open button -->
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                            <button name="mark_open" type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Re-open
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Reply form -->
                        <form method="POST">
                            <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label" style="font-size:.8rem;">Your Reply</label>
                                <textarea name="admin_reply" class="form-control" rows="4" placeholder="Type your reply…" required></textarea>
                            </div>
                            <button name="reply_feedback" type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="bi bi-send-fill me-2"></i>Send Reply & Resolve
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function toggleFeedback(id) {
    const el  = document.getElementById('fb-detail-' + id);
    const chv = document.getElementById('chevron-' + id);
    const open = el.style.display !== 'none';
    el.style.display = open ? 'none' : 'block';
    chv.style.transform = open ? '' : 'rotate(180deg)';
}
// Auto-expand if one is targeted
<?php if ($expandId): ?>toggleFeedback(<?= $expandId ?>);<?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>
