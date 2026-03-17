<?php require_once 'header.php'; ?>

<?php
// Top 5 materials viewed
$queryViews = "
    SELECT m.title, c.course_name, COUNT(p.id) AS views 
    FROM progress p 
    JOIN materials m ON p.material_id = m.id
    JOIN courses c ON m.course_id = c.id 
    GROUP BY p.material_id 
    ORDER BY views DESC LIMIT 5
";
$topViewedMaterials = $pdo->query($queryViews)->fetchAll();

// Top 5 materials downloaded
$queryDownloads = "
    SELECT m.title, c.course_name, COUNT(d.id) AS downloads 
    FROM downloads d 
    JOIN materials m ON d.material_id = m.id 
    JOIN courses c ON m.course_id = c.id 
    GROUP BY d.material_id 
    ORDER BY downloads DESC LIMIT 5
";
$topDownloadedMaterials = $pdo->query($queryDownloads)->fetchAll();

// Recent Downloads by Students
$queryRecentDownloads = "
    SELECT d.downloaded_at, s.name as student_name, m.title as material_title 
    FROM downloads d 
    JOIN students s ON d.student_id = s.student_id 
    JOIN materials m ON d.material_id = m.id 
    ORDER BY d.downloaded_at DESC LIMIT 10
";
$recentDownloads = $pdo->query($queryRecentDownloads)->fetchAll();
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill text-primary"></i> Material Analytics</h4>
        <p class="text-muted">Track student engagement with course materials.</p>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-12 col-lg-6">
        <div class="content-card h-100">
            <h5 class="fw-bold mb-4">Most Viewed Materials <i class="bi bi-eye text-primary float-end"></i></h5>
            <?php if(count($topViewedMaterials) > 0): ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Material Title</th>
                            <th>Course</th>
                            <th>Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($topViewedMaterials as $row): ?>
                        <tr>
                            <td class="fw-semibold text-primary"><?= htmlspecialchars($row['title']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($row['course_name']) ?></td>
                            <td><span class="badge bg-primary rounded-pill"><?= $row['views'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-2 d-block mb-2"></i> No views recorded yet.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-12 col-lg-6">
        <div class="content-card h-100">
            <h5 class="fw-bold mb-4">Most Downloaded Materials <i class="bi bi-download text-success float-end"></i></h5>
            <?php if(count($topDownloadedMaterials) > 0): ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Material Title</th>
                            <th>Course</th>
                            <th>Downloads</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($topDownloadedMaterials as $row): ?>
                        <tr>
                            <td class="fw-semibold text-success"><?= htmlspecialchars($row['title']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($row['course_name']) ?></td>
                            <td><span class="badge bg-success rounded-pill"><?= $row['downloads'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-2 d-block mb-2"></i> No downloads recorded yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="fw-bold mb-4">Recent Student Downloads</h5>
            <?php if(count($recentDownloads) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Student Name</th>
                            <th>Material Downloaded</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentDownloads as $row): ?>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-person-circle me-2 text-muted"></i> <?= htmlspecialchars($row['student_name']) ?></td>
                            <td><i class="bi bi-file-earmark-pdf text-danger me-1"></i> <?= htmlspecialchars($row['material_title']) ?></td>
                            <td class="text-muted small"><?= date('M j, Y h:i A', strtotime($row['downloaded_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted bg-light rounded"><i class="bi bi-clock-history fs-1 d-block mb-3"></i><p>No downloads activity yet.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
