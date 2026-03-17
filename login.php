<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin/dashboard.php"); exit;
}
if (isset($_SESSION['student_id'])) {
    header("Location: student/student_dashboard.php"); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role     = $_POST['role'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($role === 'Admin') {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :user OR name = :user");
        $stmt->execute(['user' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            $isValid = password_verify($password, $admin['password']) || $password === $admin['password'];
            if ($isValid) {
                if ($password === $admin['password']) {
                    $upd = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $upd->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
                }
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                header("Location: admin/dashboard.php"); exit;
            }
        }
        $error = 'Invalid Admin credentials. Please try again.';
    } elseif ($role === 'Student') {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = :user OR name = :user");
        $stmt->execute(['user' => $username]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($student) {
            $isValid = password_verify($password, $student['password']) || $password === $student['password'];
            if ($isValid) {
                if ($password === $student['password']) {
                    $upd = $pdo->prepare("UPDATE students SET password = ? WHERE student_id = ?");
                    $upd->execute([password_hash($password, PASSWORD_DEFAULT), $student['student_id']]);
                }
                $_SESSION['student_id']   = $student['student_id'];
                $_SESSION['student_name'] = $student['name'];
                header("Location: student/student_dashboard.php"); exit;
            }
        }
        $error = 'Invalid credentials. Please check your username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Life Skills Coaching</title>
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

        /* Subtle background blobs */
        body::before {
            content: '';
            position: fixed; top: -120px; right: -120px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(111,76,255,.18), transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed; bottom: -100px; left: -100px;
            width: 350px; height: 350px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,.12), transparent 70%);
            pointer-events: none;
        }

        /* Brand name */
        .brand-name {
            text-align: center;
            font-size: .95rem;
            font-weight: 600;
            color: #a5b4fc;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 2rem;
        }

        /* Card */
        .login-box {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 10;
        }

        .welcome-heading {
            font-size: 2rem;
            font-weight: 700;
            color: #f1f5f9;
            text-align: center;
            margin-bottom: 2rem;
            letter-spacing: -.5px;
        }

        /* Role tabs */
        .role-tabs {
            display: flex;
            background: #252641;
            border-radius: 12px;
            padding: 5px;
            margin-bottom: 1.75rem;
        }
        .role-tab {
            flex: 1;
            text-align: center;
            padding: .5rem;
            border-radius: 9px;
            font-size: .85rem;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all .25s;
            user-select: none;
        }
        .role-tab.active {
            background: linear-gradient(135deg, #6f4cff, #7c3aed);
            color: #fff;
            box-shadow: 0 4px 14px rgba(111,76,255,.35);
        }

        /* Labels */
        .field-label {
            font-size: .75rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: .45rem;
            display: block;
        }

        /* Inputs */
        .login-input {
            width: 100%;
            background: #252641;
            border: 1.5px solid transparent;
            border-radius: 12px;
            padding: .9rem 1.1rem;
            font-size: .9rem;
            color: #e2e8f0;
            font-family: 'Poppins', sans-serif;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .login-input::placeholder { color: #475569; }
        .login-input:focus {
            border-color: #6f4cff;
            box-shadow: 0 0 0 4px rgba(111,76,255,.15);
        }
        /* ── Kill browser autofill white background ── */
        .login-input:-webkit-autofill,
        .login-input:-webkit-autofill:hover,
        .login-input:-webkit-autofill:focus,
        .login-input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #252641 inset !important;
            box-shadow: 0 0 0 1000px #252641 inset !important;
            -webkit-text-fill-color: #e2e8f0 !important;
            border-color: rgba(111,76,255,.3) !important;
            caret-color: #e2e8f0;
            transition: background-color 99999s ease-in-out 0s;
        }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; right: 1rem; top: 50%;
            transform: translateY(-50%);
            color: #475569; font-size: 1rem; cursor: pointer;
            transition: color .2s;
        }
        .input-icon:hover { color: #a5b4fc; }

        .field-group { margin-bottom: 1.25rem; }

        /* Error */
        .error-box {
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.25);
            border-radius: 10px;
            padding: .75rem 1rem;
            color: #fca5a5;
            font-size: .825rem;
            margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: .5rem;
        }

        /* Button */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #6f4cff, #7c3aed);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: .95rem;
            font-size: .95rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all .25s;
            box-shadow: 0 6px 20px rgba(111,76,255,.35);
            margin-top: .5rem;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(111,76,255,.45);
            background: linear-gradient(135deg, #7c3aed, #6f4cff);
        }
        .btn-login:active { transform: translateY(0); }

        /* Divider */
        .or-divider {
            text-align: center; position: relative; margin: 1.5rem 0;
            color: #334155; font-size: .78rem;
        }
        .or-divider::before, .or-divider::after {
            content: ''; position: absolute; top: 50%;
            width: calc(50% - 24px); height: 1px; background: #1e293b;
        }
        .or-divider::before { left: 0; }
        .or-divider::after  { right: 0; }

        /* Footer text */
        .login-footer {
            margin-top: 1.75rem;
            text-align: center;
            font-size: .82rem;
            color: #475569;
        }
        .login-footer a {
            color: #a5b4fc;
            text-decoration: underline;
            text-underline-offset: 3px;
            font-weight: 500;
        }
        .login-footer a:hover { color: #c7d2fe; }
    </style>
</head>
<body>

<div class="login-box">
    <!-- Brand -->
    <div class="brand-name">Life Skills Coaching</div>

    <!-- Heading -->
    <div class="welcome-heading">Welcome back!</div>

    <!-- Hidden real select for form submission -->
    <input type="hidden" name="role" id="roleInput" form="loginForm" value="Student">

    <!-- Role Tab Switch -->
    <div class="role-tabs" id="roleTabs">
        <div class="role-tab active" data-role="Student" onclick="switchRole(this)">
            <i class="bi bi-person-fill me-1"></i> Student
        </div>
        <div class="role-tab" data-role="Admin" onclick="switchRole(this)">
            <i class="bi bi-shield-fill me-1"></i> Admin
        </div>
    </div>

    <!-- Error -->
    <?php if (!empty($error)): ?>
        <div class="error-box"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="login.php" id="loginForm">
        <input type="hidden" name="role" id="roleField" value="Student">

        <div class="field-group">
            <label class="field-label" for="username">Username or Email</label>
            <div class="input-wrap">
                <input class="login-input" type="text" id="username" name="username"
                       placeholder="Your username or email" required
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
        </div>

        <div class="field-group">
            <label class="field-label" for="password">Password</label>
            <div class="input-wrap">
                <input class="login-input" type="password" id="password" name="password"
                       placeholder="Your password" required style="padding-right:3rem;">
                <span class="input-icon" onclick="togglePwd()" title="Toggle password">
                    <i class="bi bi-eye-slash" id="pwdIcon"></i>
                </span>
            </div>
        </div>

        <button type="submit" class="btn-login">Log in</button>
    </form>

    <div class="or-divider">or</div>

    <!-- Footer -->
    <div class="login-footer">
        Don't have an account? <a href="student/student_register.php">Create an account</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchRole(el) {
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('roleField').value = el.dataset.role;
}

function togglePwd() {
    const inp = document.getElementById('password');
    const ico = document.getElementById('pwdIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'bi bi-eye';
    } else {
        inp.type = 'password';
        ico.className = 'bi bi-eye-slash';
    }
}

// Pre-select role tab if server returned error with a role
<?php if (!empty($_POST['role'])): ?>
    document.querySelectorAll('.role-tab').forEach(t => {
        if (t.dataset.role === '<?= $_POST['role'] ?>') switchRole(t);
    });
<?php endif; ?>
</script>
</body>
</html>