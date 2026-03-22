<?php
require_once 'header.php';

$student_id   = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'] ?? 'Student';
$initials     = strtoupper(substr($student_name, 0, 1));

// Mark all admin messages as read on page load
$pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE student_id = ? AND sender = 'admin'")->execute([$student_id]);

// Fetch personal messages (initial load)
$mStmt = $pdo->prepare("SELECT id, sender, message, created_at FROM chat_messages WHERE student_id = ? ORDER BY created_at ASC");
$mStmt->execute([$student_id]);
$personal = $mStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch broadcast messages (initial load)
$broadcasts = $pdo->query("SELECT id, 'broadcast' AS sender, message, created_at FROM broadcast_messages ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);

// Merge and sort
$messages = array_merge($personal, $broadcasts);
usort($messages, fn($a, $b) => strtotime($a['created_at']) <=> strtotime($b['created_at']));

$lastId = !empty($personal) ? (int)end($personal)['id'] : 0;
?>

<!-- Page Heading -->
<div style="margin-bottom:1.5rem;">
    <div style="font-size:.75rem;color:#6366f1;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:.35rem;">Communication</div>
    <h2 style="color:#f1f5f9;font-weight:700;margin-bottom:.25rem;"><i class="bi bi-chat-dots-fill me-2" style="color:#6366f1;"></i>Chat with Admin</h2>
    <p style="color:#475569;font-size:.875rem;">Send a message to your admin — they'll reply as soon as possible.</p>
</div>

<!-- Chat Window -->
<div style="background:#111827;border:1px solid rgba(99,102,241,.15);border-radius:20px;overflow:hidden;display:flex;flex-direction:column;height:72vh;max-height:680px;">

    <!-- Header Bar -->
    <div style="background:linear-gradient(90deg,rgba(99,102,241,.15),rgba(139,92,246,.1));border-bottom:1px solid rgba(99,102,241,.15);padding:1rem 1.5rem;display:flex;align-items:center;gap:.85rem;">
        <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;">
            <i class="bi bi-shield-person-fill"></i>
        </div>
        <div>
            <div style="font-size:.9rem;font-weight:600;color:#f1f5f9;">Admin</div>
            <div style="font-size:.7rem;color:#475569;"><i class="bi bi-circle-fill me-1" style="font-size:.45rem;color:#34d399;"></i>Life Skills Coaching Support</div>
        </div>
        <div style="margin-left:auto;font-size:.72rem;color:#334155;">
            <i class="bi bi-circle-fill me-1" style="font-size:.5rem;color:#34d399;"></i>Live
        </div>
    </div>

    <!-- Messages Area -->
    <div id="msgBody" style="flex:1;overflow-y:auto;padding:1.5rem;display:flex;flex-direction:column;gap:.85rem;">
        <?php if (empty($messages)): ?>
            <div style="margin:auto;text-align:center;color:#334155;" id="emptyNote">
                <i class="bi bi-chat-square-text" style="font-size:3rem;display:block;margin-bottom:.75rem;"></i>
                <div style="font-size:.85rem;">No messages yet. Say hello! 👋</div>
            </div>
        <?php endif; ?>

        <?php foreach ($messages as $m):
            $isBroadcast = !isset($m['student_id']) || (isset($m['sender']) && $m['sender'] === 'broadcast');
            $isStudent   = !$isBroadcast && $m['sender'] === 'student';
        ?>
            <?php if ($isBroadcast): ?>
            <div style="display:flex;justify-content:center;">
                <div style="max-width:78%;">
                    <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);border-left:3px solid #f59e0b;border-radius:12px;padding:.7rem 1rem;font-size:.82rem;line-height:1.55;color:#fcd34d;">
                        <div style="font-size:.65rem;color:#f59e0b;font-weight:700;margin-bottom:.3rem;letter-spacing:.5px;"><i class="bi bi-broadcast me-1"></i>ADMIN ANNOUNCEMENT</div>
                        <?= nl2br(htmlspecialchars($m['message'])) ?>
                    </div>
                    <div style="font-size:.62rem;color:#334155;margin-top:.25rem;text-align:center;"><?= date('M j, g:i A', strtotime($m['created_at'])) ?></div>
                </div>
            </div>
            <?php elseif ($isStudent): ?>
            <div style="display:flex;justify-content:flex-end;">
                <div style="max-width:68%;">
                    <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border-radius:18px 18px 4px 18px;padding:.65rem 1rem;font-size:.85rem;line-height:1.55;box-shadow:0 4px 14px rgba(99,102,241,.3);"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
                    <div style="font-size:.65rem;color:#334155;margin-top:.3rem;text-align:right;"><?= date('M j, g:i A', strtotime($m['created_at'])) ?> <i class="bi bi-check2-all ms-1" style="color:#818cf8;"></i></div>
                </div>
                <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#6d28d9);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:#fff;flex-shrink:0;margin-left:.5rem;align-self:flex-end;"><?= $initials ?></div>
            </div>
            <?php else: ?>
            <div style="display:flex;justify-content:flex-start;">
                <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:.7rem;color:#fff;flex-shrink:0;margin-right:.5rem;align-self:flex-end;"><i class="bi bi-shield-person-fill"></i></div>
                <div style="max-width:68%;">
                    <div style="background:#1e293b;color:#e2e8f0;border:1px solid rgba(99,102,241,.12);border-radius:18px 18px 18px 4px;padding:.65rem 1rem;font-size:.85rem;line-height:1.55;box-shadow:0 2px 8px rgba(0,0,0,.2);"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
                    <div style="font-size:.65rem;color:#334155;margin-top:.3rem;"><?= date('M j, g:i A', strtotime($m['created_at'])) ?></div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Input Box -->
    <div style="border-top:1px solid rgba(99,102,241,.12);padding:1rem 1.25rem;background:rgba(15,23,42,.6);">
        <div style="display:flex;gap:.75rem;align-items:flex-end;">
            <textarea id="msgInput" rows="1" placeholder="Type a message…"
                style="flex:1;resize:none;background:rgba(30,41,59,.8);border:1px solid rgba(99,102,241,.2);color:#e2e8f0;border-radius:14px;padding:.7rem 1rem;font-family:'Poppins',sans-serif;font-size:.875rem;outline:none;transition:border-color .2s;max-height:120px;overflow-y:auto;"
                onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='rgba(99,102,241,.2)'"
                onkeydown="handleKey(event)"></textarea>
            <button id="sendBtn" onclick="sendMsg()"
                style="width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;color:#fff;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(99,102,241,.4);transition:all .2s;"
                onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                <i class="bi bi-send-fill"></i>
            </button>
        </div>
        <div style="font-size:.65rem;color:#334155;margin-top:.4rem;">Press <kbd style="background:#1e293b;color:#64748b;border:1px solid rgba(255,255,255,.08);border-radius:4px;padding:.1em .4em;">Enter</kbd> to send, <kbd style="background:#1e293b;color:#64748b;border:1px solid rgba(255,255,255,.08);border-radius:4px;padding:.1em .4em;">Shift+Enter</kbd> for new line</div>
    </div>
</div>

<script>
const msgBody  = document.getElementById('msgBody');
const msgInput = document.getElementById('msgInput');
const sendBtn  = document.getElementById('sendBtn');
const initials = <?= json_encode($initials) ?>;

msgBody.scrollTop = msgBody.scrollHeight;

msgInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (msgInput.value.trim()) sendMsg();
    }
}

function addBubble(sender, message, time, type) {
    const empty = document.getElementById('emptyNote');
    if (empty) empty.remove();

    const wrap = document.createElement('div');
    if (type === 'broadcast') {
        wrap.style.cssText = 'display:flex;justify-content:center;';
        wrap.innerHTML = `<div style="max-width:78%;"><div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);border-left:3px solid #f59e0b;border-radius:12px;padding:.7rem 1rem;font-size:.82rem;color:#fcd34d;"><div style="font-size:.65rem;color:#f59e0b;font-weight:700;margin-bottom:.3rem;"><i class="bi bi-broadcast me-1"></i>ADMIN ANNOUNCEMENT</div>${message}</div><div style="font-size:.62rem;color:#334155;margin-top:.25rem;text-align:center;">${time}</div></div>`;
    } else if (sender === 'student') {
        wrap.style.cssText = 'display:flex;justify-content:flex-end;';
        wrap.innerHTML = `<div style="max-width:68%;"><div style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border-radius:18px 18px 4px 18px;padding:.65rem 1rem;font-size:.85rem;line-height:1.55;box-shadow:0 4px 14px rgba(99,102,241,.3);">${message}</div><div style="font-size:.65rem;color:#334155;margin-top:.3rem;text-align:right;">${time} <i class="bi bi-check2-all ms-1" style="color:#818cf8;"></i></div></div><div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#6d28d9);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:#fff;flex-shrink:0;margin-left:.5rem;align-self:flex-end;">${initials}</div>`;
    } else {
        wrap.style.cssText = 'display:flex;justify-content:flex-start;';
        wrap.innerHTML = `<div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:.7rem;color:#fff;flex-shrink:0;margin-right:.5rem;align-self:flex-end;"><i class="bi bi-shield-person-fill"></i></div><div style="max-width:68%;"><div style="background:#1e293b;color:#e2e8f0;border:1px solid rgba(99,102,241,.12);border-radius:18px 18px 18px 4px;padding:.65rem 1rem;font-size:.85rem;line-height:1.55;box-shadow:0 2px 8px rgba(0,0,0,.2);">${message}</div><div style="font-size:.65rem;color:#334155;margin-top:.3rem;">${time}</div></div>`;
    }
    msgBody.appendChild(wrap);
    msgBody.scrollTop = msgBody.scrollHeight;
}

function sendMsg() {
    const text = msgInput.value.trim();
    if (!text) return;

    const now = new Date();
    const t = now.toLocaleString('en-US', { month:'short', day:'numeric', hour:'numeric', minute:'2-digit' });
    addBubble('student', text.replace(/\n/g,'<br>'), t, 'personal');

    msgInput.value = '';
    msgInput.style.height = 'auto';
    sendBtn.disabled = true;

    fetch('support_send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
    })
    .then(r => r.json())
    .then(data => { if (data.ok && data.id > lastId) lastId = data.id; })
    .catch(() => {})
    .finally(() => { sendBtn.disabled = false; });
}

let lastId = <?= $lastId ?>;

function pollReplies() {
    fetch('support_poll.php?last=' + lastId)
        .then(r => r.json())
        .then(data => {
            if (data && data.messages) {
                data.messages.forEach(m => {
                    addBubble(m.sender, m.message, m.time, m.type || 'personal');
                    if (m.id > lastId) lastId = m.id;
                });
            }
        })
        .catch(() => {});
}

setInterval(pollReplies, 8000);
</script>

<?php require_once 'footer.php'; ?>
