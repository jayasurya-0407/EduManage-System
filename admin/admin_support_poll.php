<?php
/**
 * admin_support_poll.php  –  Lightweight AJAX endpoint for admin support polling
 * Called by admin/support_inbox.php every 8 seconds via fetch().
 * Returns JSON: { "messages": [ { id, sender, message, time, initials } ] }
 */
require_once '../db.php';
session_start();

header('Content-Type: application/json');

// Basic admin session guard
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['messages' => []]);
    exit;
}

$student_id = (int)($_GET['student'] ?? 0);
$last_id    = (int)($_GET['last'] ?? 0);

if (!$student_id) {
    echo json_encode(['messages' => []]);
    exit;
}

// Fetch student name for initials
$sRow = $pdo->prepare("SELECT name FROM students WHERE student_id = ?");
$sRow->execute([$student_id]);
$sName    = $sRow->fetchColumn() ?: 'S';
$initials = strtoupper(substr($sName, 0, 1));

// New messages since last_id
$stmt = $pdo->prepare(
    "SELECT id, sender, message, created_at FROM chat_messages
     WHERE student_id = ? AND id > ?
     ORDER BY created_at ASC"
);
$stmt->execute([$student_id, $last_id]);

$results = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $results[] = [
        'id'       => (int)$row['id'],
        'sender'   => $row['sender'],
        'message'  => nl2br(htmlspecialchars($row['message'])),
        'time'     => date('M j, g:i A', strtotime($row['created_at'])),
        'initials' => $initials,
    ];
}

echo json_encode(['messages' => $results]);
exit;
