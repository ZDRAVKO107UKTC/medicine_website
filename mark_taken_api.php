<?php
header('Content-Type: application/json');

$pdo = new PDO("mysql:host=localhost;dbname=medicine_db", "root", "707508");

if (!isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No ID provided']);
    exit();
}

$id = intval($_POST['id']);

$stmt = $pdo->prepare("UPDATE medicine_routines SET is_taken = 1, taken_at = NOW(), status = 'taken' WHERE id = ?");
if ($stmt->execute([$id])) {
    echo json_encode(['status' => 'ok', 'message' => 'Updated']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Update failed']);
}
