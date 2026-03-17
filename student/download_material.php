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
$stmt = $pdo->prepare("SELECT file_path, title FROM materials WHERE id = ?");
$stmt->execute([$material_id]);
$material = $stmt->fetch();

if (!$material) {
    echo "Material not found.";
    exit;
}

// Record download
try {
    $insert = $pdo->prepare("INSERT INTO downloads (student_id, material_id) VALUES (?, ?)");
    $insert->execute([$student_id, $material_id]);
} catch(PDOException $e) {
    // Ignore DB errors
}

// Base URL is one level up
$actual_path = '../' . $material['file_path'];

if (file_exists($actual_path)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($actual_path).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($actual_path));
    readfile($actual_path);
    exit;
} else {
    echo "File not found on server.";
}
