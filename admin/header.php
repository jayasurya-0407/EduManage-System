<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_id'])) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}
require_once '../db.php';

$admin_name = "Administrator";
$admin_image = null;
if (isset($_SESSION['admin_id'])) {
    $stmt = $pdo->prepare("SELECT name, profile_image FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin_data = $stmt->fetch();
    if ($admin_data) {
        $admin_name = $admin_data['name'];
        $admin_image = $admin_data['profile_image'];
    }
} elseif (isset($_SESSION['admin_name'])) {
    $admin_name = $_SESSION['admin_name'];
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Life Skills Coaching</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        /* ── Admin Tech Sidebar ── */
        body { font-family: 'Poppins', sans-serif; background: #0d0f14; }

        .admin-layout { display: flex; min-height: 100vh; }

        /* Sidebar */
        .admin-sidebar {
            width: 260px;
            min-width: 260px;
            background: linear-gradient(180deg, #0d0f14 0%, #111827 100%);
            border-right: 1px solid rgba(99,102,241,.15);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1040;
        }

        .sidebar-brand {
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.06);
            display: flex; align-items: center; gap: 12px;
        }
        .sidebar-brand .brand-dot {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff; font-weight: 700;
            box-shadow: 0 0 16px rgba(99,102,241,.5);
        }
        .sidebar-brand .brand-text { color: #f1f5f9; font-weight: 700; font-size: 1rem; line-height: 1.2; }
        .sidebar-brand .brand-sub  { color: #64748b; font-size: .7rem; font-weight: 400; }

        .sidebar-section-label {
            padding: .75rem 1.25rem .3rem;
            font-size: .65rem; font-weight: 600;
            color: #475569; text-transform: uppercase; letter-spacing: 1px;
        }

        .sidebar-nav { list-style: none; padding: .5rem 0; margin: 0; }
        .sidebar-nav .nav-item { padding: 0 .75rem; margin-bottom: 2px; }
        .sidebar-nav .nav-link {
            display: flex; align-items: center; gap: 10px;
            color: #94a3b8; padding: .6rem .85rem; border-radius: 10px;
            font-size: .875rem; font-weight: 500;
            transition: all .2s ease; position: relative;
            text-decoration: none;
        }
        .sidebar-nav .nav-link i { font-size: 1rem; width: 20px; text-align: center; }
        .sidebar-nav .nav-link:hover {
            background: rgba(99,102,241,.1); color: #c7d2fe;
        }
        .sidebar-nav .nav-link.active {
            background: linear-gradient(90deg, rgba(99,102,241,.2), rgba(99,102,241,.05));
            color: #a5b4fc;
            border-left: 3px solid #6366f1;
        }
        .sidebar-nav .nav-link.active i { color: #818cf8; }

        .sidebar-divider { border-color: rgba(255,255,255,.06); margin: .5rem 1.25rem; }

        .sidebar-footer {
            margin-top: auto;
            padding: 1rem .75rem;
            border-top: 1px solid rgba(255,255,255,.06);
        }
        .sidebar-footer .nav-link {
            display: flex; align-items: center; gap: 10px;
            color: #ef4444; padding: .6rem .85rem; border-radius: 10px;
            font-size: .875rem; font-weight: 500;
            text-decoration: none; transition: all .2s;
        }
        .sidebar-footer .nav-link:hover { background: rgba(239,68,68,.1); }

        /* Main area */
        .admin-main {
            flex: 1;
            margin-left: 260px;
            background: #0d0f14;
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        /* Top Bar */
        .admin-topbar {
            background: rgba(17,24,39,.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(99,102,241,.12);
            padding: .875rem 2rem;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 1030;
        }
        .topbar-title { font-size: 1.15rem; font-weight: 600; color: #f1f5f9; }
        .topbar-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: .95rem;
            cursor: pointer; box-shadow: 0 0 12px rgba(99,102,241,.4);
        }
        .topbar-avatar img { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; }

        /* Content area */
        .admin-content {
            padding: 2rem;
            flex: 1;
        }

        /* Cards */
        .card, .content-card {
            background: #111827;
            border: 1px solid rgba(99,102,241,.12);
            border-radius: 16px;
            color: #e2e8f0;
        }
        .content-card { padding: 1.75rem; margin-bottom: 1.5rem; }

        /* Stat Cards */
        .stat-card {
            background: linear-gradient(135deg, #111827 60%, #1a2035);
            border: 1px solid rgba(99,102,241,.15);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            transition: transform .25s, box-shadow .25s;
            position: relative; overflow: hidden;
        }
        .stat-card::before {
            content: ''; position: absolute; top: -30px; right: -30px;
            width: 100px; height: 100px; border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,.15), transparent);
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 32px rgba(99,102,241,.2); }
        .stat-label { font-size: .78rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: .25rem; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #f1f5f9; line-height: 1; }
        .stat-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; flex-shrink: 0;
        }

        /* Table */
        .table { color: #cbd5e1; }
        .table th { color: #64748b; font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; border-color: rgba(255,255,255,.06); background: rgba(15,23,42,.5); font-weight: 600; padding: 1rem; }
        .table td { border-color: rgba(255,255,255,.06); padding: 1rem; vertical-align: middle; }
        .table tbody tr:hover { background: rgba(99,102,241,.05); }
        .table thead th:first-child { border-top-left-radius: 10px; }
        .table thead th:last-child { border-top-right-radius: 10px; }

        /* Buttons */
        .btn-primary { background: linear-gradient(135deg,#6366f1,#4f46e5); border: none; box-shadow: 0 4px 12px rgba(99,102,241,.3); }
        .btn-primary:hover { background: linear-gradient(135deg,#4f46e5,#4338ca); transform: translateY(-1px); }
        .btn-outline-primary { color: #818cf8; border-color: #4f46e5; }
        .btn-outline-primary:hover { background: #4f46e5; color: #fff; }
        .btn-sm { font-size: .8rem; }

        /* Badges */
        .badge { font-weight: 500; }

        /* Forms */
        .form-control, .form-select {
            background: rgba(15,23,42,.8);
            border: 1px solid rgba(99,102,241,.2);
            color: #e2e8f0; border-radius: 10px; padding: .65rem 1rem;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(15,23,42,.9);
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.15);
            color: #f1f5f9;
        }
        .form-control::placeholder { color: #475569; }
        .form-label { color: #94a3b8; font-weight: 500; font-size: .875rem; }
        .form-check-input { background-color: rgba(15,23,42,.8); border-color: rgba(99,102,241,.3); }
        .form-check-input:checked { background-color: #6366f1; border-color: #6366f1; }

        /* Alert */
        .alert-success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.2); color: #6ee7b7; }
        .alert-danger  { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.2);  color: #fca5a5; }
        .alert-warning { background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.2); color: #fcd34d; }
        .alert-info    { background: rgba(99,102,241,.1); border: 1px solid rgba(99,102,241,.2); color: #a5b4fc; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #0d0f14; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 3px; }

        h1,h2,h3,h4,h5,h6 { color: #f1f5f9; }
        .text-muted { color: #64748b !important; }
        .text-dark  { color: #e2e8f0 !important; }
        .fw-bold, .fw-semibold { color: #f1f5f9; }
        .border-bottom { border-color: rgba(255,255,255,.06) !important; }

        /* Dropdown */
        .dropdown-menu {
            background: #1e293b;
            border: 1px solid rgba(99,102,241,.2);
            border-radius: 12px;
            box-shadow: 0 16px 48px rgba(0,0,0,.4);
        }
        .dropdown-item { color: #cbd5e1; font-size: .875rem; }
        .dropdown-item:hover { background: rgba(99,102,241,.1); color: #a5b4fc; }
        .dropdown-divider { border-color: rgba(255,255,255,.08); }

        /* Progress bar */
        .progress { background: rgba(255,255,255,.06); border-radius: 10px; }
        .progress-bar { background: linear-gradient(90deg,#6366f1,#8b5cf6); border-radius: 10px; }

        /* Mobile Responsiveness */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: #f1f5f9;
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 1rem;
        }

        @media (max-width: 992px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: 5px 0 25px rgba(0,0,0,0.5);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .admin-main {
                margin-left: 0;
            }
            .mobile-toggle {
                display: block;
            }
            .admin-topbar {
                padding: .875rem 1rem;
            }
            .admin-content {
                padding: 1rem;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                backdrop-filter: blur(3px);
                z-index: 1035;
            }
            .sidebar-overlay.show {
                display: block;
            }
        }
    </style>

</head>
<body>
<div class="admin-layout">

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <div class="brand-dot">LC</div>
            <div>
                <div class="brand-text">LSC Admin</div>
                <div class="brand-sub">Life Skills Coaching</div>
            </div>
        </div>

        <!-- Main nav -->
        <div class="sidebar-section-label">Main</div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?= $current_page=='dashboard.php' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label">Courses</div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="add_course.php" class="nav-link <?= $current_page=='add_course.php' ? 'active' : '' ?>">
                    <i class="bi bi-plus-circle-fill"></i> Add Course
                </a>
            </li>
            <li class="nav-item">
                <a href="view_course.php" class="nav-link <?= $current_page=='view_course.php' ? 'active' : '' ?>">
                    <i class="bi bi-collection-fill"></i> View Courses
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label">Students</div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="add_student.php" class="nav-link <?= $current_page=='add_student.php' ? 'active' : '' ?>">
                    <i class="bi bi-person-plus-fill"></i> Add Student
                </a>
            </li>
            <li class="nav-item">
                <a href="view_students.php" class="nav-link <?= $current_page=='view_students.php' ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i> View Students
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label">Resources</div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="add_material.php" class="nav-link <?= $current_page=='add_material.php' ? 'active' : '' ?>">
                    <i class="bi bi-cloud-upload-fill"></i> Upload Material
                </a>
            </li>
            <li class="nav-item">
                <a href="view_materials.php" class="nav-link <?= $current_page=='view_materials.php' ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-pdf-fill"></i> View Materials
                </a>
            </li>
            <li class="nav-item">
                <a href="analytics.php" class="nav-link <?= $current_page=='analytics.php' ? 'active' : '' ?>">
                    <i class="bi bi-graph-up-arrow"></i> Analytics
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label">Quizzes</div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="add_quiz.php" class="nav-link <?= $current_page=='add_quiz.php' ? 'active' : '' ?>">
                    <i class="bi bi-patch-plus-fill"></i> Add Quiz
                </a>
            </li>
            <li class="nav-item">
                <a href="view_quizzes.php" class="nav-link <?= $current_page=='view_quizzes.php' ? 'active' : '' ?>">
                    <i class="bi bi-patch-question-fill"></i> View Quizzes
                </a>
            </li>
            <li class="nav-item">
                <a href="quiz_results.php" class="nav-link <?= $current_page=='quiz_results.php' ? 'active' : '' ?>">
                    <i class="bi bi-card-checklist"></i> Quiz Results
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label">Communication</div>
            <ul class="nav flex-column mb-auto">
                <li class="nav-item">
                    <a href="feedback_inbox.php" class="nav-link <?= $current_page == 'feedback_inbox.php' ? 'active' : '' ?>">
                        <i class="bi bi-envelope-open"></i> Feedback Inbox
                    </a>
                </li>
                <li class="nav-item">
                    <a href="support_inbox.php" class="nav-link <?= $current_page == 'support_inbox.php' ? 'active' : '' ?>">
                        <i class="bi bi-chat-dots"></i> Student Chats
                        <?php
                        $unread_chats = $pdo->query("SELECT COUNT(*) FROM chat_messages WHERE sender='student' AND is_read=0")->fetchColumn();
                        if ($unread_chats > 0): ?>
                            <span class="badge bg-danger ms-2 rounded-pill"><?= $unread_chats ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

        <div class="sidebar-footer">
            <a href="logout.php" class="nav-link">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div style="display: flex; align-items: center;">
                <button class="mobile-toggle" id="mobileToggleBtn">
                    <i class="bi bi-list"></i>
                </button>
                <span class="topbar-title" id="page-title">Dashboard</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Admin Profile Dropdown -->
                <div id="adminProfileWrap" style="position:relative;">
                    <div style="display:flex;align-items:center;gap:.6rem;cursor:pointer;" onclick="toggleAdminProfile(event)" id="adminProfileBtn">
                        <div style="text-align:right;">
                            <div style="font-size:.8rem;color:#94a3b8;font-weight:500;"><?= htmlspecialchars($admin_name) ?></div>
                            <div style="font-size:.65rem;color:#475569;">Administrator</div>
                        </div>
                        <?php if ($admin_image && file_exists("../uploads/" . $admin_image)): ?>
                            <div class="topbar-avatar"><img src="../uploads/<?= htmlspecialchars($admin_image) ?>" alt=""></div>
                        <?php else: ?>
                            <div class="topbar-avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Custom Dropdown Panel -->
                    <div id="adminProfileDropdown" style="
                        position:absolute;right:0;top:calc(100% + 14px);
                        background:#1e2340;
                        border:1px solid rgba(99,102,241,.25);
                        border-radius:16px;
                        min-width:230px;
                        box-shadow:0 20px 50px rgba(0,0,0,.55),0 0 0 1px rgba(99,102,241,.1);
                        opacity:0;visibility:hidden;transform:translateY(-10px) scale(.97);
                        transition:opacity .2s,transform .2s,visibility .2s;
                        overflow:hidden;z-index:9999;
                    ">
                        <!-- Info header -->
                        <div style="padding:1rem 1.2rem;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:.75rem;background:rgba(99,102,241,.06);">
                            <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;color:#fff;flex-shrink:0;">
                                <?= strtoupper(substr($admin_name, 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-size:.875rem;font-weight:600;color:#f1f5f9;"><?= htmlspecialchars($admin_name) ?></div>
                                <div style="font-size:.68rem;color:#475569;">Administrator</div>
                            </div>
                        </div>
                        <!-- Menu items -->
                        <div style="padding:.4rem 0;">
                            <a href="admin_profile.php" style="display:flex;align-items:center;gap:.75rem;padding:.65rem 1.2rem;font-size:.84rem;font-weight:500;color:#94a3b8;text-decoration:none;transition:all .15s;" onmouseover="this.style.background='rgba(99,102,241,.1)';this.style.color='#c7d2fe'" onmouseout="this.style.background='transparent';this.style.color='#94a3b8'">
                                <i class="bi bi-person-circle" style="font-size:1rem;width:20px;text-align:center;color:#818cf8;"></i> View Profile
                            </a>
                            <a href="change_password.php" style="display:flex;align-items:center;gap:.75rem;padding:.65rem 1.2rem;font-size:.84rem;font-weight:500;color:#94a3b8;text-decoration:none;transition:all .15s;" onmouseover="this.style.background='rgba(99,102,241,.1)';this.style.color='#c7d2fe'" onmouseout="this.style.background='transparent';this.style.color='#94a3b8'">
                                <i class="bi bi-key" style="font-size:1rem;width:20px;text-align:center;color:#fbbf24;"></i> Change Password
                            </a>
                            <a href="support_inbox.php" style="display:flex;align-items:center;gap:.75rem;padding:.65rem 1.2rem;font-size:.84rem;font-weight:500;color:#94a3b8;text-decoration:none;transition:all .15s;justify-content:space-between;" onmouseover="this.style.background='rgba(99,102,241,.1)';this.style.color='#c7d2fe'" onmouseout="this.style.background='transparent';this.style.color='#94a3b8'">
                                <div><i class="bi bi-chat-dots me-2 text-warning"></i>Messages</div>
                                <?php
                                $_unread = $pdo->query("SELECT COUNT(*) FROM chat_messages WHERE sender='student' AND is_read=0")->fetchColumn();
                                if ($_unread > 0): ?>
                                    <span style="background:#ef4444;color:#fff;font-size:.6rem;font-weight:700;padding:.15em .55em;border-radius:10px;"><?= $_unread ?></span>
                                <?php endif; ?>
                            </a>
                            <hr style="border:none;border-top:1px solid rgba(255,255,255,.06);margin:.3rem 0;">
                            <a href="logout.php" style="display:flex;align-items:center;gap:.75rem;padding:.65rem 1.2rem;font-size:.84rem;font-weight:500;color:#f87171;text-decoration:none;transition:all .15s;" onmouseover="this.style.background='rgba(239,68,68,.08)';this.style.color='#fca5a5'" onmouseout="this.style.background='transparent';this.style.color='#f87171'">
                                <i class="bi bi-box-arrow-right" style="font-size:1rem;width:20px;text-align:center;"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dynamic content starts here -->
        <div class="admin-content">
