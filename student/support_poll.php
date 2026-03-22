<?php
require_once '../db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['messages' => []]);
    exit;
}

$student_id = (int)$_SESSION['student_id'];
$last_id    = (int)($_GET['last'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT id, sender, message, created_at FROM chat_messages
     WHERE student_id = ? AND id > ? AND sender = 'admin'
     ORDER BY created_at ASC"
);
$stmt->execute([$student_id, $last_id]);

$pdo->prepare(
    "UPDATE chat_messages SET is_read = 1 WHERE student_id = ? AND sender = 'admin' AND id > ?"
)->execute([$student_id, $last_id]);

$results = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $results[] = [
        'id'      => (int)$row['id'],
        'sender'  => 'admin',
        'message' => nl2br(htmlspecialchars($row['message'])),
        'time'    => date('M j, g:i A', strtotime($row['created_at'])),
        'type'    => 'personal',
    ];
}

echo json_encode(['messages' => $results]);
exit;
