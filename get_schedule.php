<?php
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=medicine_db", "root", "707508");
    $stmt = $pdo->query("SELECT id, scheduled_at FROM medicine_routines WHERE is_taken = 0 ORDER BY scheduled_at ASC LIMIT 1");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode($data);
    } else {
        echo json_encode(['status' => 'none']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
