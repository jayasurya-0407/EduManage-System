<?php
require_once 'header.php';

// Fetch all courses
$query = $pdo->query("
SELECT 
id,
ROW_NUMBER() OVER (ORDER BY id) AS display_id,
subcode,
course_name,
description
FROM courses
");

$courses = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Manage Courses</h5>
        <a href="add_course.php" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Add Course</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-success py-2 alert-dismissible fade show" role="alert">
            Course deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
        <div class="alert alert-success py-2 alert-dismissible fade show" role="alert">
            Course updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle border">
            <thead class="table-light">
                <tr>
                   <th scope="col" width="5%">ID</th>
                   <th scope="col" width="25%">Course Name</th>
                   <th scope="col" width="35%">Description</th>
                   <th scope="col" width="20%" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($courses) > 0): ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?= $course['display_id'] ?></td>
                            <td class="fw-medium text-dark"><?= htmlspecialchars($course['course_name']) ?></td>
                            <td class="text-muted small"><?= nl2br(htmlspecialchars($course['description'] ?? '')) ?></td>
                            <td class="text-end">
                                <a href="edit_course.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-outline-primary mb-1"><i class="bi bi-pencil"></i></a>
                                <a href="delete_course.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-outline-danger mb-1" onclick="return confirm('Are you sure you want to delete this course? This will also delete associated students and materials references.')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No courses found. Add a course to get started.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
