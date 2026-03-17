<?php
include "../db.php";
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

// Fetch email for dropdown display
$s_stmt = $pdo->prepare("SELECT email FROM students WHERE student_id = ?");
$s_stmt->execute([$_SESSION['student_id']]);
$s_row = $s_stmt->fetch(PDO::FETCH_ASSOC);
$student_email = $s_row['email'] ?? '';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Life Skills Coaching</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #0f172a; min-height: 100vh; display: flex; flex-direction: column; }

        /* ── Navbar ── */
        .student-nav {
            background: linear-gradient(90deg, #0f172a, #1e293b);
            border-bottom: 1px solid rgba(99,102,241,.2);
            padding: .875rem 0;
            position: sticky; top: 0; z-index: 1040;
        }
        .student-nav .navbar-brand { font-weight: 700; color: #f1f5f9; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; }
        .brand-orb {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; font-weight: 700; color: #fff;
            box-shadow: 0 0 12px rgba(99,102,241,.4);
        }
        .student-nav .nav-link { color: #94a3b8 !important; font-size: .875rem; font-weight: 500; padding: .35rem .85rem; border-radius: 8px; transition: all .2s; }
        .student-nav .nav-link:hover, .student-nav .nav-link.active { color: #c7d2fe !important; background: rgba(99,102,241,.12); }

        /* ── Profile Avatar ── */
        .profile-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, #6f4cff, #7c3aed);
            color: #fff; font-weight: 700; font-size: .9rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            box-shadow: 0 0 0 2px rgba(111,76,255,.35);
            transition: box-shadow .2s, transform .2s;
            user-select: none;
        }
        .profile-avatar:hover { box-shadow: 0 0 0 3px rgba(111,76,255,.65); transform: scale(1.06); }

        /* ── Dropdown Popup ── */
        #profileWrap { position: relative; }

        .profile-dropdown {
            position: absolute; right: 0; top: calc(100% + 14px);
            background: #1e2340;
            border: 1px solid rgba(99,102,241,.25);
            border-radius: 16px;
            min-width: 250px;
            box-shadow: 0 20px 50px rgba(0,0,0,.55), 0 0 0 1px rgba(99,102,241,.1);
            opacity: 0; visibility: hidden; transform: translateY(-10px) scale(.97);
            transition: opacity .2s, transform .2s, visibility .2s;
            overflow: hidden;
            z-index: 9999;
        }
        .profile-dropdown.open { opacity: 1; visibility: visible; transform: translateY(0) scale(1); }

        /* Header row */
        .pd-header {
            padding: 1.1rem 1.25rem;
            display: flex; align-items: center; gap: .875rem;
            border-bottom: 1px solid rgba(255,255,255,.06);
            background: rgba(99,102,241,.06);
        }
        .pd-avatar-lg {
            width: 44px; height: 44px; border-radius: 12px;
            background: linear-gradient(135deg, #6f4cff, #7c3aed);
            color: #fff; font-weight: 700; font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .pd-name  { font-size: .875rem; font-weight: 600; color: #f1f5f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 165px; }
        .pd-email { font-size: .72rem; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 165px; }

        /* Menu items */
        .pd-menu { padding: .4rem 0; }
        .pd-item {
            display: flex; align-items: center; gap: .75rem;
            padding: .65rem 1.25rem; font-size: .84rem; font-weight: 500;
            color: #94a3b8; cursor: pointer; transition: all .15s;
            text-decoration: none;
        }
        .pd-item i { font-size: 1rem; width: 20px; text-align: center; }
        .pd-item:hover { background: rgba(99,102,241,.1); color: #c7d2fe; }
        .pd-item.danger { color: #f87171; }
        .pd-item.danger:hover { background: rgba(239,68,68,.08); color: #fca5a5; }
        .pd-divider { border: none; border-top: 1px solid rgba(255,255,255,.06); margin: .25rem 0; }

        /* ── Page Body ── */
        .student-main { flex: 1; padding: 2rem 0; }

        /* Cards */
        .s-card { background: #111827; border: 1px solid rgba(99,102,241,.12); border-radius: 16px; padding: 1.75rem; margin-bottom: 1.5rem; color: #e2e8f0; }
        .s-card h5, .s-card h4, .s-card h6 { color: #f1f5f9; }

        /* Material cards */
        .material-card { background: #1e293b; border: 1px solid rgba(99,102,241,.1); border-radius: 14px; padding: 1.25rem; transition: all .25s; }
        .material-card:hover { border-color: rgba(99,102,241,.4); transform: translateY(-3px); box-shadow: 0 8px 24px rgba(99,102,241,.12); }
        .material-card.viewed { border-left: 3px solid #6366f1; }

        /* Badges */
        .badge-viewed  { background: rgba(16,185,129,.1); color: #34d399; border: 1px solid rgba(16,185,129,.2); font-size: .72rem; font-weight: 500; padding: .3em .7em; border-radius: 6px; }
        .badge-pending { background: rgba(100,116,139,.1); color: #94a3b8; border: 1px solid rgba(100,116,139,.2); font-size: .72rem; font-weight: 500; padding: .3em .7em; border-radius: 6px; }

        /* Progress bar */
        .prog-wrap { background: rgba(255,255,255,.06); border-radius: 10px; height: 10px; overflow: hidden; }
        .prog-bar  { background: linear-gradient(90deg,#6366f1,#8b5cf6); height: 100%; border-radius: 10px; transition: width .6s ease; }

        /* Forms */
        .form-control, .form-select { background: rgba(15,23,42,.8); border: 1px solid rgba(99,102,241,.2); color: #e2e8f0; border-radius: 10px; padding: .65rem 1rem; }
        .form-control:focus, .form-select:focus { background: rgba(15,23,42,.9); border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); color: #f1f5f9; }
        .form-control::placeholder { color: #475569; }
        .form-label { color: #94a3b8; font-size: .875rem; font-weight: 500; }

        /* Buttons */
        .btn-indigo { background: linear-gradient(135deg,#6366f1,#4f46e5); color: #fff; border: none; border-radius: 10px; font-weight: 500; transition: all .2s; box-shadow: 0 4px 12px rgba(99,102,241,.3); }
        .btn-indigo:hover { background: linear-gradient(135deg,#4f46e5,#4338ca); color: #fff; transform: translateY(-1px); }
        .btn-outline-indigo { color: #818cf8; border: 1.5px solid rgba(99,102,241,.4); border-radius: 10px; font-weight: 500; transition: all .2s; background: transparent; }
        .btn-outline-indigo:hover { background: rgba(99,102,241,.1); color: #a5b4fc; }
        .btn-green { background: linear-gradient(135deg,#10b981,#059669); color: #fff; border: none; border-radius: 10px; font-weight: 500; }
        .btn-green:hover { background: linear-gradient(135deg,#059669,#047857); color: #fff; }

        /* Alert */
        .s-alert-success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.2); color: #6ee7b7; border-radius: 12px; padding: .875rem 1.25rem; }
        .s-alert-danger   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.2);  color: #fca5a5; border-radius: 12px; padding: .875rem 1.25rem; }
        .s-alert-warning  { background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.2); color: #fcd34d; border-radius: 12px; padding: .875rem 1.25rem; }

        .text-muted { color: #64748b !important; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 3px; }
        .option-item { background: #1e293b; border: 1px solid rgba(99,102,241,.15); border-radius: 10px; cursor: pointer; transition: all .2s; }
        .option-item:hover { background: rgba(99,102,241,.08); border-color: rgba(99,102,241,.35); }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
<body>
<nav class="student-nav navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="student_dashboard.php">
            <div class="brand-orb">LC</div>
            Life Skills Portal
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sNav"
                style="border-color:rgba(255,255,255,.2);">
            <span class="navbar-toggler-icon" style="filter:invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="sNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-3" style="gap:.35rem;">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page=='student_dashboard.php'?'active':'' ?>" href="student_dashboard.php">
                        Progress
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page=='browse_courses.php'?'active':'' ?>" href="browse_courses.php">
                        Explore Courses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page=='chat.php'?'active':'' ?>" href="chat.php">
                        Chat
                    </a>
                </li>

            </ul>

            <!-- ── Profile Avatar Dropdown ── -->
            <div id="profileWrap">
                <div class="profile-avatar" id="profileBtn" onclick="toggleProfile(event)"
                     title="<?= htmlspecialchars($_SESSION['student_name'] ?? 'Student') ?>">
                    <?= strtoupper(substr($_SESSION['student_name'] ?? 'S', 0, 1)) ?>
                </div>

                <div class="profile-dropdown" id="profileDropdown">
                    <!-- User info header -->
                    <div class="pd-header">
                        <div class="pd-avatar-lg">
                            <?= strtoupper(substr($_SESSION['student_name'] ?? 'S', 0, 1)) ?>
                        </div>
                        <div style="overflow:hidden;">
                            <div class="pd-name"><?= htmlspecialchars($_SESSION['student_name'] ?? 'Student') ?></div>
                            <div class="pd-email"><?= htmlspecialchars($student_email) ?></div>
                        </div>
                    </div>
                    <!-- Menu -->
                    <div class="pd-menu">
                        <a href="profile.php" class="pd-item">
                            <i class="bi bi-person-circle" style="color:#818cf8;"></i>
                            My Profile
                        </a>
                        <a href="feedback.php" class="pd-item">
                            <i class="bi bi-chat-left-text" style="color:#34d399;"></i>
                            Feedback
                        </a>
                        <a href="chat.php" class="pd-item">
                            <i class="bi bi-chat-dots" style="color:#fbbf24;"></i>
                            Chat with Admin
                        </a>
                        <hr class="pd-divider">
                        <a href="student_logout.php" class="pd-item danger">
                            <i class="bi bi-box-arrow-right"></i>
                            Log out
                        </a>
                    </div>
                </div>
            </div>
            <!-- ── End Profile Dropdown ── -->
        </div>
    </div>
</nav>

<div class="student-main">
<div class="container">

<?php

