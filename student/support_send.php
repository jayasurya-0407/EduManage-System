<?php
require_once '../db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$student_id = (int)$_SESSION['student_id'];
$data       = json_decode(file_get_contents('php://input'), true);
$msg        = trim($data['message'] ?? '');

if ($msg === '') {
    echo json_encode(['ok' => false, 'error' => 'Empty message']);
    exit;
}

$ins = $pdo->prepare("INSERT INTO chat_messages (student_id, sender, message) VALUES (?, 'student', ?)");
$ins->execute([$student_id, $msg]);
$newId = (int)$pdo->lastInsertId();

echo json_encode(['ok' => true, 'id' => $newId]);
exit;
