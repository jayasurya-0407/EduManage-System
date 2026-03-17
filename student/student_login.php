<?php
session_start();
require_once '../db.php';

if (isset($_SESSION['student_id'])) {
    header("Location: student_dashboard.php"); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['email']);   // field named email but accepts username too
    $password = $_POST['password'];

    // Match by email OR name
    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = :u OR name = :u");
    $stmt->execute(['u' => $username]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $isValid = password_verify($password, $student['password']) || $password === $student['password'];
        if ($isValid) {
            // Silently upgrade plain-text to hashed
            if ($password === $student['password']) {
                $upd = $pdo->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                $upd->execute([password_hash($password, PASSWORD_DEFAULT), $student['student_id']]);
            }
            $_SESSION['student_id']   = $student['student_id'];
            $_SESSION['student_name'] = $student['name'];
            header("Location: student_dashboard.php"); exit;
        }
    }
    $error = 'Invalid username/email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login — Life Skills Coaching</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #1a1b2e;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: fixed; top: -120px; right: -120px;
            width: 400px; height: 400px; border-radius: 50%;
            background: radial-gradient(circle, rgba(111,76,255,.18), transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed; bottom: -100px; left: -100px;
            width: 350px; height: 350px; border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,.12), transparent 70%);
            pointer-events: none;
        }
        .brand-name {
            text-align: center;
            font-size: .78rem; font-weight: 600; color: #a5b4fc;
            letter-spacing: 3px; text-transform: uppercase; margin-bottom: 2rem;
        }
        .login-box { width: 100%; max-width: 440px; position: relative; z-index: 10; }
        .welcome-heading {
            font-size: 2rem; font-weight: 700; color: #f1f5f9;
            text-align: center; margin-bottom: .5rem; letter-spacing: -.5px;
        }
        .welcome-sub {
            text-align: center; font-size: .82rem; color: #475569; margin-bottom: 2rem;
        }
        .field-label { font-size: .75rem; font-weight: 600; color: #94a3b8; margin-bottom: .45rem; display: block; }
        .login-input {
            width: 100%; background: #252641;
            border: 1.5px solid transparent; border-radius: 12px;
            padding: .9rem 1.1rem; font-size: .9rem; color: #e2e8f0;
            font-family: 'Poppins', sans-serif; outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .login-input::placeholder { color: #475569; }
        .login-input:focus {
            border-color: #6f4cff;
            box-shadow: 0 0 0 4px rgba(111,76,255,.15);
        }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; right: 1rem; top: 50%;
            transform: translateY(-50%);
            color: #475569; font-size: 1rem; cursor: pointer; transition: color .2s;
        }
        .input-icon:hover { color: #a5b4fc; }
        .field-group { margin-bottom: 1.25rem; }
        .error-box {
            background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25);
            border-radius: 10px; padding: .75rem 1rem;
            color: #fca5a5; font-size: .825rem; margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: .5rem;
        }
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #6f4cff, #7c3aed);
            color: #fff; border: none; border-radius: 12px;
            padding: .95rem; font-size: .95rem; font-weight: 600;
            font-family: 'Poppins', sans-serif; cursor: pointer;
            transition: all .25s;
            box-shadow: 0 6px 20px rgba(111,76,255,.35); margin-top: .5rem;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(111,76,255,.45); }
        .btn-login:active { transform: translateY(0); }
        .or-divider {
            text-align: center; position: relative; margin: 1.5rem 0;
            color: #334155; font-size: .78rem;
        }
        .or-divider::before, .or-divider::after {
            content: ''; position: absolute; top: 50%;
            width: calc(50% - 20px); height: 1px; background: #1e293b;
        }
        .or-divider::before { left: 0; }
        .or-divider::after  { right: 0; }
        .login-footer { margin-top: 1.75rem; text-align: center; font-size: .82rem; color: #475569; }
        .login-footer a { color: #a5b4fc; text-decoration: underline; text-underline-offset: 3px; font-weight: 500; }
        .login-footer a:hover { color: #c7d2fe; }
        .badge-student {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(99,102,241,.12); color: #818cf8;
            border: 1px solid rgba(99,102,241,.2);
            border-radius: 8px; padding: .3rem .85rem; font-size: .75rem;
            font-weight: 600; margin: 0 auto 1.5rem; width: max-content;
            display: flex; margin: 0 auto 1.75rem;
        }
    </style>
</head>
<body>

<div class="login-box">
    <div class="brand-name">Life Skills Coaching</div>
    <div class="welcome-heading">Welcome back!</div>
    <p class="welcome-sub">Sign in to access your study materials and quizzes.</p>

    <div class="badge-student"><i class="bi bi-mortarboard-fill"></i> Student Portal</div>

    <?php if (!empty($error)): ?>
        <div class="error-box"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="student_login.php">
        <div class="field-group">
            <label class="field-label" for="email">Username or Email</label>
            <div class="input-wrap">
                <input class="login-input" type="text" id="email" name="email"
                       placeholder="Your username or email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
        </div>

        <div class="field-group">
            <label class="field-label" for="password">Password</label>
            <div class="input-wrap">
                <input class="login-input" type="password" id="password" name="password"
                       placeholder="Your password" required style="padding-right:3rem;">
                <span class="input-icon" onclick="togglePwd()" title="Show/hide">
                    <i class="bi bi-eye-slash" id="pwdIcon"></i>
                </span>
            </div>
        </div>

        <button type="submit" class="btn-login">Log in</button>
    </form>

    <div class="or-divider">or</div>

    <div class="login-footer">
        Don't have an account? <a href="student_register.php">Create an account</a><br>
        <span style="margin-top:.5rem;display:block;">Admin? <a href="../login.php">Go to unified login</a></span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePwd() {
    const inp = document.getElementById('password');
    const ico = document.getElementById('pwdIcon');
    if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bi bi-eye'; }
    else { inp.type = 'password'; ico.className = 'bi bi-eye-slash'; }
}
</script>
</body>
</html>
