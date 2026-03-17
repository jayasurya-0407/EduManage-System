<?php
require_once 'header.php';

// Fetch all students along with their assigned courses
$query = "SELECT s.student_id, s.name, s.email, 
          GROUP_CONCAT(c.course_name SEPARATOR '||') as course_names 
          FROM students s 
          LEFT JOIN student_courses sc ON s.student_id = sc.student_id
          LEFT JOIN courses c ON sc.course_id = c.id 
          GROUP BY s.student_id
          ORDER BY s.student_id ASC";
$stmt = $pdo->query($query);
$students = $stmt->fetchAll();
?>

<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Registered Students</h5>
        <a href="add_student.php" class="btn btn-sm btn-primary"><i class="bi bi-person-plus"></i> Add Student</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-success py-2 alert-dismissible fade show" role="alert">
            Student deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle border">
            <thead class="table-light">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Assigned Course</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= $student['student_id'] ?></td>
                            <td class="fw-medium text-dark">
                                <i class="bi bi-person-circle text-secondary me-2"></i>
                                <?= htmlspecialchars($student['name']) ?>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($student['email']) ?></td>
                            <td>
                                <?php if ($student['course_names']): ?>
                                    <?php 
                                        $course_list = explode('||', $student['course_names']);
                                        foreach ($course_list as $c_name): 
                                    ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-pill me-1 mb-1">
                                            <?= htmlspecialchars($c_name) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill">
                                        No course assigned
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="edit_student.php?id=<?= $student['student_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="delete_student.php?id=<?= $student['student_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this student?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No students registered yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
