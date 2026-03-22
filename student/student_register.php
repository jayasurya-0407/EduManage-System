<?php
session_start();
require_once '../db.php';

if (isset($_SESSION['student_id'])) {
    header("Location: student_dashboard.php");
    exit;
}

$success = '';
$error = '';

$courses_stmt = $pdo->query("SELECT id, course_name FROM courses ORDER BY course_name ASC");
$courses = $courses_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $course_ids = isset($_POST['course_ids']) ? $_POST['course_ids'] : [];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Name, email, and password are required.";
    } elseif (empty($course_ids)) {
        $error = "Please select at least one course.";
    } else {
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->fetchColumn() > 0) {
            $error = "Email address is already registered.";
        } else {
            try {
                $pdo->beginTransaction();
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO students (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $hashed]);
                $student_id = $pdo->lastInsertId();
                $cs = $pdo->prepare("INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
                foreach ($course_ids as $cid) { $cs->execute([$student_id, $cid]); }
                $pdo->commit();
                $success = "Registration successful! You can now log in.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — Life Skills Coaching</title>
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
            padding: 2rem 1.5rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Background blobs */
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

        /* Register box */
        .register-box {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 10;
        }

        /* Brand name */
        .brand-name {
            text-align: center;
            font-size: .95rem;
            font-weight: 600;
            color: #a5b4fc;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }

        /* Logo icon */
        .brand-logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #6366f1, #7c3aed);
            color: #fff;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 20px rgba(99,102,241,.35);
        }

        /* Heading */
        .page-heading {
            font-size: 1.75rem;
            font-weight: 700;
            color: #f1f5f9;
            text-align: center;
            margin-bottom: .4rem;
            letter-spacing: -.5px;
        }
        .page-subheading {
            font-size: .83rem;
            color: #64748b;
            text-align: center;
            margin-bottom: 1.75rem;
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
        .reg-input {
            width: 100%;
            background: #252641;
            border: 1.5px solid transparent;
            border-radius: 12px;
            padding: .85rem 1.1rem;
            font-size: .9rem;
            color: #e2e8f0;
            font-family: 'Poppins', sans-serif;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .reg-input::placeholder { color: #475569; }
        .reg-input:focus {
            border-color: #6f4cff;
            box-shadow: 0 0 0 4px rgba(111,76,255,.15);
        }
        /* Kill browser autofill white background */
        .reg-input:-webkit-autofill,
        .reg-input:-webkit-autofill:hover,
        .reg-input:-webkit-autofill:focus,
        .reg-input:-webkit-autofill:active {
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

        .field-group { margin-bottom: 1.15rem; }

        /* Course checkboxes */
        .course-list { display: flex; flex-direction: column; gap: .55rem; }
        .course-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            background: #252641;
            border: 1.5px solid rgba(99,102,241,.15);
            border-radius: 10px;
            padding: .7rem 1rem;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .course-item:hover { border-color: rgba(99,102,241,.4); background: #2a2b4a; }
        .course-item input[type="checkbox"] {
            width: 17px; height: 17px;
            accent-color: #6366f1;
            cursor: pointer;
            flex-shrink: 0;
        }
        .course-item label {
            color: #94a3b8;
            font-size: .875rem;
            font-weight: 500;
            cursor: pointer;
            margin: 0;
        }
        .course-item:has(input:checked) {
            border-color: rgba(99,102,241,.5);
            background: rgba(99,102,241,.08);
        }
        .course-item:has(input:checked) label { color: #c7d2fe; }

        /* Error / success boxes */
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
        .success-box {
            background: rgba(16,185,129,.1);
            border: 1px solid rgba(16,185,129,.25);
            border-radius: 10px;
            padding: 1.25rem;
            color: #6ee7b7;
            font-size: .875rem;
            text-align: center;
            margin-bottom: 1.25rem;
        }
        .success-box .success-icon {
            font-size: 2rem;
            display: block;
            margin-bottom: .5rem;
        }

        /* Register button */
        .btn-register {
            width: 100%;
            background: linear-gradient(135deg, #6f4cff, #7c3aed);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: .9rem;
            font-size: .95rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all .25s;
            box-shadow: 0 6px 20px rgba(111,76,255,.35);
            margin-top: .75rem;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(111,76,255,.45);
            background: linear-gradient(135deg, #7c3aed, #6f4cff);
        }
        .btn-register:active { transform: translateY(0); }

        .btn-login-link {
            display: inline-block;
            width: 100%;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: .7rem;
            font-size: .9rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            text-align: center;
            transition: all .25s;
            box-shadow: 0 4px 14px rgba(16,185,129,.3);
        }
        .btn-login-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(16,185,129,.4);
            color: #fff;
        }

        /* Footer */
        .reg-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: .82rem;
            color: #475569;
        }
        .reg-footer a {
            color: #a5b4fc;
            text-decoration: underline;
            text-underline-offset: 3px;
            font-weight: 500;
        }
        .reg-footer a:hover { color: #c7d2fe; }

        /* Divider */
        .section-divider {
            border: none;
            border-top: 1px solid rgba(99,102,241,.1);
            margin: 1.5rem 0;
        }
    </style>
</head>
<body>

<div class="register-box">
    <!-- Brand -->
    <div class="brand-name">Life Skills Coaching</div>

    <!-- Logo -->
    <div class="brand-logo">
        <i class="bi bi-mortarboard-fill"></i>
    </div>

    <h1 class="page-heading">Create an Account</h1>
    <p class="page-subheading">Register as a student to access study materials</p>

    <?php if ($success): ?>
        <!-- Success State -->
        <div class="success-box">
            <span class="success-icon">🎉</span>
            <strong><?= htmlspecialchars($success) ?></strong>
        </div>
        <a href="student_login.php" class="btn-login-link">
            <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
        </a>

    <?php else: ?>
        <!-- Error -->
        <?php if ($error): ?>
            <div class="error-box">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="student_register.php">

            <!-- Full Name -->
            <div class="field-group">
                <label class="field-label" for="name">Full Name</label>
                <div class="input-wrap">
                    <input class="reg-input" type="text" id="name" name="name" required
                           placeholder="John Doe"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
            </div>

            <!-- Email -->
            <div class="field-group">
                <label class="field-label" for="email">Email Address</label>
                <div class="input-wrap">
                    <input class="reg-input" type="email" id="email" name="email" required
                           placeholder="john@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <!-- Password -->
            <div class="field-group">
                <label class="field-label" for="password">Password</label>
                <div class="input-wrap">
                    <input class="reg-input" type="password" id="password" name="password" required
                           placeholder="Create a password" style="padding-right:3rem;">
                    <span class="input-icon" onclick="togglePwd()" title="Toggle password">
                        <i class="bi bi-eye-slash" id="pwdIcon"></i>
                    </span>
                </div>
            </div>

            <!-- Course Selection -->
            <div class="field-group">
                <label class="field-label">Select Course(s)</label>
                <?php if (empty($courses)): ?>
                    <p style="color:#64748b;font-size:.8rem;">No courses available to enroll yet.</p>
                <?php else: ?>
                    <div class="course-list">
                        <?php foreach ($courses as $c): ?>
                            <div class="course-item">
                                <input type="checkbox" name="course_ids[]"
                                       id="c<?= $c['id'] ?>" value="<?= $c['id'] ?>"
                                       <?= (isset($_POST['course_ids']) && in_array($c['id'], $_POST['course_ids'])) ? 'checked' : '' ?>>
                                <label for="c<?= $c['id'] ?>"><?= htmlspecialchars($c['course_name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-register">
                <i class="bi bi-person-plus-fill me-2"></i>Create Account
            </button>
        </form>

    <?php endif; ?>

    <hr class="section-divider">
    <div class="reg-footer">
        Already have an account? <a href="student_login.php">Log in here</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
</script>
</body>
</html>
