<?php
require_once 'db.php';

// Fetch active courses
$courses = [];
try {
    $stmt = $pdo->query("SELECT * FROM courses ORDER BY id DESC");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If table doesn't exist or DB is down, just show empty
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Life Skills Coaching - Empower Your Future</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="landing-page">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary fs-3" href="index.php">
            <span class="brand-icon">LC</span> Life Skills Coaching
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item">
                    <a class="nav-link text-dark fw-medium mx-2" href="#courses">Courses</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-primary fw-medium px-4 mx-2 rounded-pill" href="login.php">Login</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-primary fw-medium px-4 rounded-pill" href="student/student_register.php">Register</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section d-flex align-items-center text-center text-white" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.9), rgba(124, 58, 237, 0.9)), url('https://images.unsplash.com/photo-1513258496099-48168024aec0?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80') center/cover; min-height: 80vh;">
    <div class="container py-5">
        <h1 class="display-3 fw-bolder mb-4 text-shadow anim-fade-up">Master Life's Essential Skills</h1>
        <p class="lead fw-light mb-5 mx-auto text-shadow anim-fade-up delay-1" style="max-width: 800px; font-size: 1.25rem;">
            Join our expert-led programs to unlock your true potential. Experience transformative growth with multiple courses designed beautifully for your success.
        </p>
        <div class="anim-fade-up delay-2">
            <a href="student/student_register.php" class="btn btn-light btn-lg px-5 py-3 rounded-pill fw-bold me-3 shadow-lg hover-scale">Begin Your Journey</a>
            <a href="#courses" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill fw-bold shadow-lg hover-scale">Explore Courses</a>
        </div>
    </div>
</section>

<!-- Courses Section -->
<section id="courses" class="py-5 bg-light">
    <div class="container py-5">
        <div class="text-center mb-5 pb-3">
            <h2 class="fw-bold display-5 text-dark mb-3">Our Transformative Courses</h2>
            <div class="divider mx-auto bg-primary mb-4" style="height: 4px; width: 60px; border-radius: 2px;"></div>
            <p class="text-muted fs-5">Select from a variety of paths to elevate your personal and professional life.</p>
        </div>
        
        <div class="row g-4">
            <?php if (empty($courses)): ?>
                <div class="col-12 text-center">
                    <div class="p-5 bg-white rounded-4 shadow-sm">
                        <h4 class="text-muted">No courses available at the moment. Please check back later!</h4>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card course-card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                        <div class="card-img-top bg-gradient-primary" style="height: 160px;"></div>
                        <div class="card-body p-4 text-center">
                            <div class="course-icon shadow-sm mb-3 mx-auto">
                                <i class="fs-2 text-primary">🎓</i>
                            </div>
                            <h4 class="card-title fw-bold text-dark mb-3"><?= htmlspecialchars($course['course_name']) ?></h4>
                            <p class="card-text text-muted mb-4"><?= htmlspecialchars($course['description'] ?? 'Discover new perspectives and gain mastery in this engaging course.') ?></p>
                            <a href="student/student_register.php" class="btn btn-outline-primary rounded-pill w-100 fw-medium hover-fill">Join Course</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-4 mt-auto">
    <div class="container">
        <p class="mb-0 fw-light">&copy; <?= date("Y") ?> Life Skills Coaching. All rights reserved.</p>
        <p class="small text-muted mt-2">
            <a href="setup_instructions.html" class="text-decoration-none text-muted hover-white">Setup Instructions</a> | 
            <a href="admin_login_diagnostic.php" class="text-decoration-none text-muted hover-white">Diagnostics</a>
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
