<?php
include "../db.php";
session_start();

if (!isset($_SESSION['student_id']) || !isset($_GET['id'])) {
    header("Location: student_dashboard.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$material_id = $_GET['id'];

// Check if material exists
$stmt = $pdo->prepare("SELECT file_path FROM materials WHERE id = ?");
$stmt->execute([$material_id]);
$material = $stmt->fetch();

if (!$material) {
    echo "Material not found.";
    exit;
}

// Check and record progress
try {
    $check = $pdo->prepare("SELECT id FROM progress WHERE student_id = ? AND material_id = ?");
    $check->execute([$student_id, $material_id]);
    
    if ($check->rowCount() == 0) {
        // Insert new progress
        $insert = $pdo->prepare("INSERT INTO progress (student_id, material_id, status) VALUES (?, ?, 'completed')");
        $insert->execute([$student_id, $material_id]);
    } else {
        // Update viewed_at timestamp just to mark recent activity
        $update = $pdo->prepare("UPDATE progress SET status = 'completed' WHERE student_id = ? AND material_id = ?");
        $update->execute([$student_id, $material_id]);
    }
} catch(PDOException $e) {
    // Ignore duplicate or DB errors for user experience
}

// Base URL is one level up from student logic
$actual_path = '../' . $material['file_path'];

// Redirect to view the file in the browser
header("Location: " . $actual_path);
exit;
