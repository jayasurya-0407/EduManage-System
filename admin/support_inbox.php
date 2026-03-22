<?php
require_once 'header.php';

$selId    = (int)($_GET['student'] ?? 0);
$activeTab = $_GET['tab'] ?? 'chat';   // 'chat' | 'broadcast'

// ── Handle direct message to a student ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $sid = (int)($_POST['student_id'] ?? 0);
    $msg = trim($_POST['message'] ?? '');
    if ($sid && $msg !== '') {
        $pdo->prepare("INSERT INTO chat_messages (student_id, sender, message, is_read) VALUES (?, 'admin', ?, 0)")
            ->execute([$sid, $msg]);
        $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE student_id = ? AND sender = 'student'")
            ->execute([$sid]);
    }
    echo "<script>window.location.href='support_inbox.php?tab=chat&student=" . $sid . "';</script>";
    exit;
}

// ── Handle broadcast message ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_broadcast'])) {
    $msg = trim($_POST['broadcast_message'] ?? '');
    if ($msg !== '') {
        $pdo->prepare("INSERT INTO broadcast_messages (message) VALUES (?)")->execute([$msg]);
        $bSuccess = 'Broadcast sent to all students!';
    } else {
        $bError = 'Message cannot be empty.';
    }
    $activeTab = 'broadcast';
}

// ── Fetch all students (with chat stats) ──────────────────────────────────────
$students = $pdo->query("
    SELECT s.student_id, s.name, s.email,
           MAX(cm.created_at)                                                      AS last_msg,
           SUM(CASE WHEN cm.sender = 'student' AND cm.is_read = 0 THEN 1 ELSE 0 END) AS unread,
           COUNT(cm.id)                                                            AS total_msgs
    FROM students s
    LEFT JOIN chat_messages cm ON cm.student_id = s.student_id
    GROUP BY s.student_id, s.name, s.email
    ORDER BY last_msg DESC, s.name ASC
")->fetchAll(PDO::FETCH_ASSOC);



// ── Fetch conversation for selected student ────────────────────────────────────
$messages = [];
$selStudent = null;
if ($selId) {
    $st = $pdo->prepare("SELECT name, email FROM students WHERE student_id = ?");
    $st->execute([$selId]);
    $selStudent = $st->fetch(PDO::FETCH_ASSOC);
    $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE student_id = ? AND sender = 'student'")->execute([$selId]);
    $ms = $pdo->prepare("SELECT * FROM chat_messages WHERE student_id = ? ORDER BY created_at ASC");
    $ms->execute([$selId]);
    $messages = $ms->fetchAll(PDO::FETCH_ASSOC);
}

// ── Broadcast history ─────────────────────────────────────────────────────────
$broadcasts = $pdo->query("SELECT * FROM broadcast_messages ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$totalUnread = $pdo->query("SELECT COUNT(*) FROM chat_messages WHERE sender='student' AND is_read=0")->fetchColumn();
?>

<script>document.getElementById('page-title').textContent = 'Student Support';</script>

<!-- Page Heading -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
    <div>
        <div style="font-size:.72rem;color:#6366f1;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:.3rem;">Communication</div>
        <h2 style="color:#f1f5f9;font-weight:700;margin-bottom:.25rem;">Student Support</h2>
        <p style="color:#64748b;font-size:.85rem;margin:0;">Message individual students or broadcast to everyone.</p>
    </div>
    <!-- Tab Switcher -->
    <div style="display:flex;gap:.5rem;background:#111827;border:1px solid rgba(99,102,241,.15);border-radius:12px;padding:.3rem;">
        <a href="support_inbox.php?tab=chat&student=<?= $selId ?>" style="
            padding:.5rem 1.2rem;border-radius:9px;font-size:.82rem;font-weight:600;text-decoration:none;
            background:<?= $activeTab==='chat' ? 'linear-gradient(135deg,#6366f1,#4f46e5)' : 'transparent' ?>;
            color:<?= $activeTab==='chat' ? '#fff' : '#64748b' ?>;transition:all .2s;position:relative;
        ">
            <i class="bi bi-chat-dots me-1"></i>Direct Messages
            <?php if ($totalUnread > 0): ?>
                <span style="margin-left:.35rem;background:#ef4444;color:#fff;font-size:.6rem;font-weight:700;padding:.1em .45em;border-radius:10px;"><?= $totalUnread ?></span>
            <?php endif; ?>
        </a>
        <a href="support_inbox.php?tab=broadcast" style="
            padding:.5rem 1.2rem;border-radius:9px;font-size:.82rem;font-weight:600;text-decoration:none;
            background:<?= $activeTab==='broadcast' ? 'linear-gradient(135deg,#f59e0b,#d97706)' : 'transparent' ?>;
            color:<?= $activeTab==='broadcast' ? '#fff' : '#64748b' ?>;transition:all .2s;
        "><i class="bi bi-broadcast me-1"></i>Broadcast</a>
    </div>
</div>

<?php if ($activeTab === 'broadcast'): ?>
<!-- ═══════════════ BROADCAST TAB ═══════════════ -->
<?php if (!empty($bSuccess)): ?>
    <div class="alert alert-success mb-3"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($bSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($bError)): ?>
    <div class="alert alert-danger mb-3"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($bError) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Compose -->
    <div class="col-12 col-lg-5">
        <div style="background:#111827;border:1px solid rgba(245,158,11,.2);border-radius:18px;padding:1.5rem;">
            <h5 style="color:#f1f5f9;margin-bottom:.25rem;"><i class="bi bi-broadcast me-2" style="color:#f59e0b;"></i>Send Broadcast</h5>
            <p style="color:#64748b;font-size:.8rem;margin-bottom:1.25rem;">This message will be visible to <strong style="color:#e2e8f0;">all students</strong> in their chat window.</p>
            <form method="POST">
                <input type="hidden" name="tab" value="broadcast">
                <div class="mb-3">
                    <label class="form-label">Message to All Students</label>
                    <textarea name="broadcast_message" class="form-control" rows="6"
                        placeholder="Type your announcement, reminder, or update here…" required></textarea>
                </div>
                <button name="send_broadcast" type="submit"
                    style="width:100%;padding:.75rem;background:linear-gradient(135deg,#f59e0b,#d97706);border:none;color:#fff;border-radius:12px;font-size:.875rem;font-weight:600;cursor:pointer;transition:all .2s;font-family:'Poppins',sans-serif;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <i class="bi bi-broadcast me-2"></i>Send to All <?= count($students) ?> Students
                </button>
            </form>
        </div>
    </div>

    <!-- Broadcast History -->
    <div class="col-12 col-lg-7">
        <div style="background:#111827;border:1px solid rgba(99,102,241,.12);border-radius:18px;padding:1.5rem;">
            <h5 style="color:#f1f5f9;margin-bottom:1.25rem;"><i class="bi bi-clock-history me-2" style="color:#a78bfa;"></i>Broadcast History</h5>
            <?php if (empty($broadcasts)): ?>
                <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No broadcasts sent yet.</div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:.85rem;max-height:500px;overflow-y:auto;">
                    <?php foreach ($broadcasts as $b): ?>
                        <div style="background:#1e293b;border:1px solid rgba(245,158,11,.15);border-left:3px solid #f59e0b;border-radius:0 12px 12px 0;padding:1rem;">
                            <div style="font-size:.7rem;color:#f59e0b;font-weight:600;margin-bottom:.4rem;"><i class="bi bi-broadcast me-1"></i>BROADCAST — <?= date('M j, Y g:i A', strtotime($b['created_at'])) ?></div>
                            <p style="font-size:.875rem;color:#e2e8f0;margin:0;line-height:1.6;"><?= nl2br(htmlspecialchars($b['message'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ═══════════════ DIRECT CHAT TAB ═══════════════ -->
<style>
.chat-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1.25rem;
    height: 74vh;
    max-height: 700px;
}
.chat-list-panel {
    background: #111827;
    border: 1px solid rgba(99,102,241,.12);
    border-radius: 18px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}
.chat-conv-panel {
    background: #111827;
    border: 1px solid rgba(99,102,241,.12);
    border-radius: 18px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.chat-back-btn {
    display: none;
    align-items: center;
    gap: .5rem;
    background: none;
    border: none;
    color: #a5b4fc;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    margin-right: .5rem;
}
@media (max-width: 767px) {
    .chat-layout {
        grid-template-columns: 1fr;
        height: auto;
        max-height: none;
    }
    .chat-list-panel {
        height: 60vh;
        max-height: 400px;
    }
    .chat-conv-panel {
        height: 72vh;
    }
    .chat-back-btn {
        display: flex;
    }

    /* JS-based panel switching */
    .chat-layout.chat-open .chat-list-panel {
        display: none;
    }
    .chat-layout:not(.chat-open) .chat-conv-panel {
        display: none;
    }
}
</style>

<div class="chat-layout <?= $selId ? 'chat-open' : '' ?>" id="chatLayout">

    <!-- Left: All Students -->
    <div class="chat-list-panel">
        <div style="padding:.85rem 1rem;border-bottom:1px solid rgba(255,255,255,.05);font-size:.7rem;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.8px;flex-shrink:0;">
            <i class="bi bi-people me-1"></i>All Students (<?= count($students) ?>)
        </div>
        <?php foreach ($students as $row):
            $isActive = $selId === (int)$row['student_id'];
            $hasChat  = (int)$row['total_msgs'] > 0;
        ?>
            <a href="support_inbox.php?tab=chat&student=<?= $row['student_id'] ?>" style="
                display:flex;align-items:center;gap:.8rem;padding:.8rem 1rem;
                background:<?= $isActive ? 'rgba(99,102,241,.15)' : 'transparent' ?>;
                border-left:3px solid <?= $isActive ? '#6366f1' : 'transparent' ?>;
                text-decoration:none;transition:all .15s;flex-shrink:0;
            " onmouseover="if(!<?= $isActive?'true':'false' ?>)this.style.background='rgba(99,102,241,.07)'"
               onmouseout="if(!<?= $isActive?'true':'false' ?>)this.style.background='transparent'">
                <!-- Avatar -->
                <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:.82rem;font-weight:700;color:#fff;flex-shrink:0;position:relative;">
                    <?= strtoupper(substr($row['name'], 0, 1)) ?>
                    <?php if ((int)$row['unread'] > 0): ?>
                        <span style="position:absolute;top:-5px;right:-5px;background:#ef4444;color:#fff;font-size:.5rem;font-weight:700;width:15px;height:15px;border-radius:50%;display:flex;align-items:center;justify-content:center;"><?= min((int)$row['unread'], 9) ?></span>
                    <?php endif; ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.8rem;font-weight:600;color:<?= $isActive?'#c7d2fe':'#e2e8f0' ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($row['name']) ?></div>
                    <div style="font-size:.65rem;color:#475569;">
                        <?php if ($hasChat): ?>
                            <?= date('M j, g:i A', strtotime($row['last_msg'])) ?>
                        <?php else: ?>
                            <span style="color:#334155;"><i class="bi bi-plus-circle me-1"></i>Start conversation</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Right: Conversation -->
    <div class="chat-conv-panel">
        <?php if ($selStudent): ?>

        <!-- Chat Header -->
        <div style="background:linear-gradient(90deg,rgba(99,102,241,.15),rgba(139,92,246,.08));border-bottom:1px solid rgba(99,102,241,.12);padding:.9rem 1.25rem;display:flex;align-items:center;gap:.85rem;flex-shrink:0;">
            <!-- Mobile back button -->
            <button class="chat-back-btn" onclick="goBackToList()">
                <i class="bi bi-arrow-left"></i> Back
            </button>
            <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:.95rem;font-weight:700;color:#fff;">
                <?= strtoupper(substr($selStudent['name'], 0, 1)) ?>
            </div>
            <div>
                <div style="font-size:.9rem;font-weight:600;color:#f1f5f9;"><?= htmlspecialchars($selStudent['name']) ?></div>
                <div style="font-size:.7rem;color:#475569;"><?= htmlspecialchars($selStudent['email']) ?></div>
            </div>
            <div style="margin-left:auto;font-size:.7rem;color:#334155;" id="refreshNote">
                <i class="bi bi-arrow-clockwise me-1"></i>Auto-refresh in <span id="cdTimer">10</span>s
            </div>
        </div>

        <!-- Messages -->
        <div id="chatBody" style="flex:1;overflow-y:auto;padding:1.25rem;display:flex;flex-direction:column;gap:.75rem;">
            <?php if (empty($messages)): ?>
                <div style="margin:auto;text-align:center;color:#334155;">
                    <i class="bi bi-chat-square-text" style="font-size:2.5rem;display:block;margin-bottom:.6rem;"></i>
                    <div style="font-size:.85rem;">No messages yet.</div>
                    <div style="font-size:.75rem;color:#475569;margin-top:.25rem;">Send the first message to start the conversation!</div>
                </div>
            <?php endif; ?>
            <?php foreach ($messages as $m):
                $isAdmin = $m['sender'] === 'admin';
            ?>
                <div style="display:flex;justify-content:<?= $isAdmin ? 'flex-end' : 'flex-start' ?>;">
                    <?php if (!$isAdmin): ?>
                        <div style="width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;flex-shrink:0;margin-right:.45rem;align-self:flex-end;">
                            <?= strtoupper(substr($selStudent['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div style="max-width:75%;">
                        <div style="
                            background:<?= $isAdmin ? 'linear-gradient(135deg,#6366f1,#4f46e5)' : '#1e293b' ?>;
                            color:<?= $isAdmin ? '#fff' : '#e2e8f0' ?>;
                            border:<?= $isAdmin ? 'none' : '1px solid rgba(99,102,241,.12)' ?>;
                            border-radius:<?= $isAdmin ? '18px 18px 4px 18px' : '18px 18px 18px 4px' ?>;
                            padding:.6rem .95rem;font-size:.84rem;line-height:1.55;
                            box-shadow:<?= $isAdmin ? '0 4px 14px rgba(99,102,241,.3)' : '0 2px 6px rgba(0,0,0,.2)' ?>;
                            word-break: break-word;
                        "><?= nl2br(htmlspecialchars($m['message'])) ?></div>
                        <div style="font-size:.63rem;color:#334155;margin-top:.25rem;text-align:<?= $isAdmin ? 'right' : 'left' ?>;">
                            <?= date('M j, g:i A', strtotime($m['created_at'])) ?>
                        </div>
                    </div>
                    <?php if ($isAdmin): ?>
                        <div style="width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;font-size:.6rem;color:#fff;flex-shrink:0;margin-left:.45rem;align-self:flex-end;">
                            <i class="bi bi-shield-person-fill"></i>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Reply Input -->
        <div style="border-top:1px solid rgba(99,102,241,.1);padding:.9rem 1.1rem;background:rgba(15,23,42,.5);flex-shrink:0;">
            <form method="POST" style="display:flex;gap:.65rem;align-items:flex-end;" id="replyForm">
                <input type="hidden" name="student_id" value="<?= $selId ?>">
                <textarea name="message" id="adminMsg" rows="1" placeholder="Message <?= htmlspecialchars($selStudent['name']) ?>…"
                    style="flex:1;resize:none;background:rgba(30,41,59,.8);border:1px solid rgba(99,102,241,.2);color:#e2e8f0;border-radius:12px;padding:.65rem 1rem;font-family:'Poppins',sans-serif;font-size:.85rem;outline:none;transition:border-color .2s;max-height:100px;overflow-y:auto;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='rgba(99,102,241,.2)'"
                    onkeydown="handleKey(event)"></textarea>
                <button type="submit" name="send_reply"
                    style="width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;color:#fff;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(99,102,241,.35);transition:all .2s;"
                    onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
        </div>

        <?php else: ?>
            <div style="flex:1;display:flex;align-items:center;justify-content:center;color:#334155;">
                <div style="text-align:center;"><i class="bi bi-chat-dots" style="font-size:3rem;display:block;margin-bottom:.75rem;"></i>Select a student to start chatting</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const cb = document.getElementById('chatBody');
if (cb) cb.scrollTop = cb.scrollHeight;

function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        const v = document.getElementById('adminMsg')?.value.trim();
        if (v) document.getElementById('replyForm').submit();
    }
}

function goBackToList() {
    const layout = document.getElementById('chatLayout');
    if (layout) layout.classList.remove('chat-open');
}

let cd = 10;
const timer = document.getElementById('cdTimer');

// ─── AJAX polling instead of location.reload() (fixes InfinityFree 403) ───────
<?php if ($selId): ?>
let lastAdminMsgId = <?= !empty($messages) ? (int)end($messages)['id'] : 0 ?>;

function renderBubble(m) {
    const isAdmin = m.sender === 'admin';
    const wrap = document.createElement('div');
    wrap.style.cssText = 'display:flex;justify-content:' + (isAdmin ? 'flex-end' : 'flex-start') + ';';
    if (!isAdmin) {
        wrap.innerHTML = `<div style="width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;flex-shrink:0;margin-right:.45rem;align-self:flex-end;">${m.initials}</div><div style="max-width:75%;"><div style="background:#1e293b;color:#e2e8f0;border:1px solid rgba(99,102,241,.12);border-radius:18px 18px 18px 4px;padding:.6rem .95rem;font-size:.84rem;line-height:1.55;box-shadow:0 2px 6px rgba(0,0,0,.2);word-break:break-word;">${m.message}</div><div style="font-size:.63rem;color:#334155;margin-top:.25rem;">${m.time}</div></div>`;
    } else {
        wrap.innerHTML = `<div style="max-width:75%;"><div style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;border-radius:18px 18px 4px 18px;padding:.6rem .95rem;font-size:.84rem;line-height:1.55;box-shadow:0 4px 14px rgba(99,102,241,.3);word-break:break-word;">${m.message}</div><div style="font-size:.63rem;color:#334155;margin-top:.25rem;text-align:right;">${m.time}</div></div><div style="width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;font-size:.6rem;color:#fff;flex-shrink:0;margin-left:.45rem;align-self:flex-end;"><i class="bi bi-shield-person-fill"></i></div>`;
    }
    cb.appendChild(wrap);
    cb.scrollTop = cb.scrollHeight;
}

function pollAdminChat() {
    fetch('admin_support_poll.php?student=<?= $selId ?>&last=' + lastAdminMsgId)
        .then(r => r.json())
        .then(data => {
            if (data && data.messages && data.messages.length > 0) {
                data.messages.forEach(m => {
                    renderBubble(m);
                    if (m.id > lastAdminMsgId) lastAdminMsgId = m.id;
                });
                if (timer) { cd = 10; timer.textContent = cd; }
            }
        })
        .catch(() => {});
}

if (timer) {
    setInterval(() => {
        cd--;
        if (cd <= 0) { cd = 10; }
        timer.textContent = cd;
    }, 1000);
}
setInterval(pollAdminChat, 8000);
<?php endif; ?>
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
