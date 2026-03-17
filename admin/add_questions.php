<?php
require_once 'header.php';

$success = '';
$error   = '';
$quiz_id = $_GET['quiz_id'] ?? null;

if (!$quiz_id) { header("Location: view_quizzes.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();
if (!$quiz) { echo "Quiz not found."; exit; }

// ── Manual question POST ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $question       = trim($_POST['question'] ?? '');
    $options        = $_POST['options'] ?? [];
    $correct_option = $_POST['correct_option'] ?? '';
    if (empty($question) || count($options) != 4 || $correct_option === '') {
        $error = "Please provide the question, 4 options, and select the correct answer.";
    } else {
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare("INSERT INTO quiz_questions (quiz_id, question) VALUES (?, ?)");
            $s->execute([$quiz_id, $question]);
            $qid = $pdo->lastInsertId();
            $o = $pdo->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
            for ($i = 0; $i < 4; $i++) {
                $o->execute([$qid, trim($options[$i]), ($correct_option == $i) ? 1 : 0]);
            }
            $pdo->commit();
            $success = "Question added successfully!";
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// ── JSON bulk import POST ──────────────────────────────────────────────────────
$jsonSuccess = '';
$jsonError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['json_import'])) {
    $raw = '';
    if (!empty($_FILES['json_file']['tmp_name'])) {
        $raw = file_get_contents($_FILES['json_file']['tmp_name']);
    } elseif (!empty($_POST['json_text'])) {
        $raw = trim($_POST['json_text']);
    }
    if ($raw === '') {
        $jsonError = "Please upload a JSON file or paste JSON content.";
    } else {
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonError = "Invalid JSON: " . json_last_error_msg();
        } elseif (!is_array($data) || empty($data)) {
            $jsonError = "JSON must be a non-empty array of question objects.";
        } else {
            $imported = 0; $skipped = 0;
            try {
                $pdo->beginTransaction();
                $sq = $pdo->prepare("INSERT INTO quiz_questions (quiz_id, question) VALUES (?, ?)");
                $so = $pdo->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                foreach ($data as $item) {
                    $qText   = trim($item['question'] ?? '');
                    $opts    = $item['options'] ?? [];
                    $correct = $item['correct'] ?? null;
                    if ($qText === '' || count($opts) !== 4 || $correct === null) { $skipped++; continue; }
                    $sq->execute([$quiz_id, $qText]);
                    $qid = $pdo->lastInsertId();
                    foreach ($opts as $i => $optText) {
                        $so->execute([$qid, trim($optText), ($i == (int)$correct) ? 1 : 0]);
                    }
                    $imported++;
                }
                $pdo->commit();
                $jsonSuccess = "$imported question(s) imported successfully!" . ($skipped ? " ($skipped skipped — invalid format)" : "");
            } catch(PDOException $e) {
                $pdo->rollBack();
                $jsonError = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch existing questions
$q_stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC");
$q_stmt->execute([$quiz_id]);
$existing_questions = $q_stmt->fetchAll();
?>

<!-- Breadcrumb -->
<div class="row align-items-center mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="view_quizzes.php">Quizzes</a></li>
                <li class="breadcrumb-item active">Manage Questions</li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">Quiz: <?= htmlspecialchars($quiz['title']) ?></h4>
    </div>
</div>

<div class="row g-4">
    <!-- Left Panel: Forms -->
    <div class="col-12 col-lg-5">

        <!-- Tab Switcher -->
        <div style="display:flex;gap:.4rem;background:#111827;border:1px solid rgba(99,102,241,.15);border-radius:12px;padding:.3rem;margin-bottom:1.25rem;">
            <button onclick="switchTab('manual')" id="tabManual"
                style="flex:1;padding:.5rem;border-radius:9px;border:none;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;font-family:'Poppins',sans-serif;">
                <i class="bi bi-pencil-square me-1"></i>Manual
            </button>
            <button onclick="switchTab('json')" id="tabJson"
                style="flex:1;padding:.5rem;border-radius:9px;border:none;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s;background:transparent;color:#64748b;font-family:'Poppins',sans-serif;">
                <i class="bi bi-filetype-json me-1"></i>JSON Import
            </button>
        </div>

        <!-- ── Manual Form ── -->
        <div id="panelManual" class="content-card">
            <h5 class="fw-bold border-bottom pb-3 mb-4"><i class="bi bi-plus-circle text-primary me-2"></i>Add New Question</h5>
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form action="add_questions.php?quiz_id=<?= $quiz_id ?>" method="POST">
                <div class="mb-4">
                    <label class="form-label fw-semibold">Question Text <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="question" rows="3" required placeholder="What is the capital of France?"></textarea>
                </div>
                <label class="form-label fw-semibold">Options <span class="text-danger">*</span></label>
                <div style="background:#1e293b;border:1px solid rgba(99,102,241,.2);border-radius:10px;" class="p-3 mb-4">
                    <p class="small mb-3" style="color:#475569;"><i class="bi bi-info-circle"></i> Select the radio button for the correct option.</p>
                    <?php for($i=0;$i<4;$i++): ?>
                    <div class="input-group mb-2">
                        <div class="input-group-text">
                            <input class="form-check-input mt-0" type="radio" name="correct_option" value="<?= $i ?>" <?= $i==0?'required':'' ?>>
                        </div>
                        <input type="text" class="form-control" name="options[]" placeholder="Option <?= $i+1 ?>" required>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" name="add_question" class="btn btn-primary"><i class="bi bi-save me-2"></i>Save Question</button>
                    <a href="view_quizzes.php" class="btn btn-secondary">Done</a>
                </div>
            </form>
        </div>

        <!-- ── JSON Import Panel ── -->
        <div id="panelJson" class="content-card" style="display:none;">
            <h5 class="fw-bold border-bottom pb-3 mb-4"><i class="bi bi-filetype-json me-2" style="color:#f59e0b;"></i>Bulk Import via JSON</h5>

            <?php if ($jsonSuccess): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($jsonSuccess) ?></div>
            <?php endif; ?>
            <?php if ($jsonError): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($jsonError) ?></div>
            <?php endif; ?>

            <!-- Format reference -->
            <div style="background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.2);border-left:3px solid #f59e0b;border-radius:0 12px 12px 0;padding:1rem;margin-bottom:1.25rem;">
                <div style="font-size:.7rem;color:#f59e0b;font-weight:700;margin-bottom:.5rem;letter-spacing:.5px;"><i class="bi bi-info-circle me-1"></i>REQUIRED JSON FORMAT</div>
<pre style="font-size:.75rem;color:#cbd5e1;margin:0;line-height:1.6;overflow-x:auto;">[
  {
    "question": "What is 2 + 2?",
    "options": ["3", "4", "5", "6"],
    "correct": 1
  }
]</pre>
                <div style="font-size:.7rem;color:#a16207;margin-top:.5rem;"><i class="bi bi-lightbulb me-1"></i><strong>correct</strong> = 0-based index of the correct option (0 = first option)</div>
            </div>

            <form action="add_questions.php?quiz_id=<?= $quiz_id ?>" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><i class="bi bi-upload me-1"></i>Upload JSON File</label>
                    <input type="file" name="json_file" accept=".json,application/json" class="form-control" onchange="previewJson(this)">
                    <div style="font-size:.72rem;color:#475569;margin-top:.3rem;">Accepts <code>.json</code> files</div>
                </div>

                <div style="display:flex;align-items:center;gap:.75rem;margin:.75rem 0;">
                    <hr style="flex:1;border-color:rgba(255,255,255,.08);margin:0;">
                    <span style="color:#334155;font-size:.75rem;flex-shrink:0;">OR paste below</span>
                    <hr style="flex:1;border-color:rgba(255,255,255,.08);margin:0;">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Paste JSON</label>
                    <textarea name="json_text" id="jsonTextArea" rows="10" class="form-control"
                        style="font-family:monospace;font-size:.78rem;"
                        placeholder='[&#10;  {&#10;    "question": "...",&#10;    "options": ["A","B","C","D"],&#10;    "correct": 0&#10;  }&#10;]'
                        oninput="validateJson()"></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" onclick="validateJson(true)" class="btn btn-outline-primary">
                        <i class="bi bi-check2-circle me-2"></i>Validate JSON
                    </button>
                    <button type="submit" name="json_import"
                        style="padding:.7rem;background:linear-gradient(135deg,#f59e0b,#d97706);border:none;color:#000;border-radius:10px;font-size:.875rem;font-weight:700;cursor:pointer;font-family:'Poppins',sans-serif;transition:all .2s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                        <i class="bi bi-cloud-upload me-2"></i>Import All Questions
                    </button>
                </div>
                <div id="jsonValidationMsg" style="margin-top:.75rem;font-size:.8rem;"></div>
            </form>
        </div>
    </div>

    <!-- Right: Existing Questions -->
    <div class="col-12 col-lg-7">
        <div class="content-card">
            <h5 class="fw-bold border-bottom pb-3 mb-4">Questions in this Quiz (<?= count($existing_questions) ?>)</h5>
            <?php if (count($existing_questions) > 0): ?>
                <div class="accordion" id="questionsAccordion">
                    <?php foreach ($existing_questions as $index => $q): ?>
                    <div class="accordion-item border rounded mb-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $q['id'] ?>">
                                Q<?= $index + 1 ?>: <?= htmlspecialchars($q['question']) ?>
                            </button>
                        </h2>
                        <div id="collapse<?= $q['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#questionsAccordion">
                            <div class="accordion-body">
                                <ul class="list-group list-group-flush border rounded overflow-hidden">
                                    <?php
                                    $opt_stmt = $pdo->prepare("SELECT * FROM quiz_options WHERE question_id = ?");
                                    $opt_stmt->execute([$q['id']]);
                                    foreach ($opt_stmt->fetchAll() as $opt):
                                        $ic = $opt['is_correct'] == 1;
                                    ?>
                                    <li class="list-group-item d-flex align-items-center gap-3 <?= $ic?'fw-bold':'' ?>">
                                        <?= $ic ? '<i class="bi bi-check-circle-fill text-success fs-5"></i>' : '<i class="bi bi-circle text-muted"></i>' ?>
                                        <span><?= htmlspecialchars($opt['option_text']) ?></span>
                                        <?php if($ic): ?><span class="badge bg-success ms-auto">Correct</span><?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill fs-4 me-2"></i>No questions yet. Use the form or JSON import to add questions.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    const isJson = tab === 'json';
    document.getElementById('panelManual').style.display = isJson ? 'none'  : 'block';
    document.getElementById('panelJson').style.display   = isJson ? 'block' : 'none';
    const tJ = document.getElementById('tabJson');
    const tM = document.getElementById('tabManual');
    tJ.style.background = isJson ? 'linear-gradient(135deg,#f59e0b,#d97706)' : 'transparent';
    tJ.style.color      = isJson ? '#fff' : '#64748b';
    tM.style.background = isJson ? 'transparent' : 'linear-gradient(135deg,#6366f1,#4f46e5)';
    tM.style.color      = isJson ? '#64748b' : '#fff';
}

function previewJson(input) {
    if (!input.files.length) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('jsonTextArea').value = e.target.result;
        validateJson(true);
    };
    reader.readAsText(input.files[0]);
}

function validateJson(alert) {
    const raw = document.getElementById('jsonTextArea').value.trim();
    const msg = document.getElementById('jsonValidationMsg');
    if (!raw) { msg.innerHTML = ''; return; }
    try {
        const d = JSON.parse(raw);
        if (!Array.isArray(d)) throw new Error('Top-level value must be an array [ ].');
        let valid = 0, invalid = 0;
        d.forEach(item => {
            (item.question && Array.isArray(item.options) && item.options.length === 4 && item.correct !== undefined)
                ? valid++ : invalid++;
        });
        msg.innerHTML = `<span style="color:#34d399;"><i class="bi bi-check-circle-fill me-1"></i><strong>${valid}</strong> question(s) ready to import`
            + (invalid ? ` &bull; <strong>${invalid}</strong> will be skipped (missing fields)` : '') + `</span>`;
    } catch(e) {
        msg.innerHTML = `<span style="color:#f87171;"><i class="bi bi-x-circle-fill me-1"></i>Invalid JSON: ${e.message}</span>`;
    }
}

// Auto-switch to JSON tab if there's a json result from server
<?php if ($jsonSuccess || $jsonError): ?>switchTab('json');<?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>
