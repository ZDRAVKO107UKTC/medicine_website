<?php
function checkForUpcomingMedicine($conn, $userId = null) {
    $medicines = [];
    $current_time = date("H:i");

    $sql = "SELECT mr.*, u.email FROM medicine_routines mr
            JOIN users u ON mr.user_id = u.id";
    if ($userId !== null) {
        $sql .= " WHERE mr.user_id = ?";
    }

    $stmt = $conn->prepare($sql);
    if ($userId !== null) {
        $stmt->bind_param("i", $userId);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $pill_time = date("H:i", strtotime($row['time']));
        $pill_time_minus_15 = date("H:i", strtotime("-15 minutes", strtotime($pill_time)));

        if ($current_time >= $pill_time_minus_15 && $current_time <= $pill_time) {
            $medicines[] = $row;
        }
    }

    return $medicines;
}

function sendReminderEmail($to, $subject, $body) {
    $headers = "From: reminder@medicine-system.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return mail($to, $subject, $body, $headers);
}
?>
