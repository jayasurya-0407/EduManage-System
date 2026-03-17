<?php
require_once 'db.php';

$sql = file_get_contents('database.sql');

try {
    $pdo->exec($sql);
    echo "Database schema updated successfully.\n";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>
