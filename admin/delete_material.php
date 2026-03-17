<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../login.php");
    exit;
}
require_once '../db.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    try {
        // Fetch material to get file path
        $stmt = $pdo->prepare("SELECT file_path FROM materials WHERE id = ?");
        $stmt->execute([$id]);
        $material = $stmt->fetch();

        if ($material) {
            $file_actual_path = '../' . $material['file_path'];
            
            // Delete record from DB
            $del_stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
            if ($del_stmt->execute([$id])) {
                // Remove file from filesystem
                if (file_exists($file_actual_path)) {
                    unlink($file_actual_path);
                }
            }
        }
    } catch (PDOException $e) {
        // Handle error if needed
    }
}

header("Location: view_materials.php?msg=deleted");
exit;
?>
