<?php
require_once 'header.php';

// Fetch all materials with their related course info
$query = "SELECT m.id, m.title, m.file_path, c.course_name 
          FROM materials m 
          LEFT JOIN courses c ON m.course_id = c.id 
          ORDER BY m.id DESC";
$stmt = $pdo->query($query);
$materials = $stmt->fetchAll();
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Study Materials</h5>
        <a href="add_material.php" class="btn btn-sm btn-primary"><i class="bi bi-upload"></i> Upload Material</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-success py-2 alert-dismissible fade show" role="alert">
            Material deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (count($materials) > 0): ?>
            <?php foreach ($materials as $material): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; transition: transform 0.2s;">
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="bg-danger bg-opacity-10 text-danger rounded p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="bi bi-file-earmark-pdf-fill fs-3"></i>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h6 class="card-title fw-bold text-truncate mb-1" title="<?= htmlspecialchars($material['title']) ?>"><?= htmlspecialchars($material['title']) ?></h6>
                                    <p class="card-text small text-muted text-truncate mb-0">
                                        Course: <?= htmlspecialchars($material['course_name'] ?? 'Unknown Course') ?>
                                    </p>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-auto pt-3 border-top">
                                <!-- Base URL is one level up logically, so from /admin/ it's ../ -->
                                <?php $actual_path = '../' . $material['file_path']; ?>
                                <a href="<?= htmlspecialchars($actual_path) ?>" target="_blank" class="btn btn-sm btn-outline-primary flex-fill">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="<?= htmlspecialchars($actual_path) ?>" download class="btn btn-sm btn-outline-success flex-fill">
                                    <i class="bi bi-download"></i> Download
                                </a>
                                <a href="delete_material.php?id=<?= $material['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this material? The file will be permanently removed.')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5 text-muted bg-light rounded" style="border: 1px dashed #cbd5e1;">
                    <i class="bi bi-folder2-open fs-1 text-secondary mb-3 d-block"></i>
                    <p>No study materials uploaded yet.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
