<?php
// cron_notify.php
require 'db.php';
require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Вземаме настоящото време
$current_time = date('Y-m-d H:i:s');

// Вземаме времето след 5 минути
$time_plus_5min = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Вземаме лекарства, които трябва да се пият в следващите 5 минути и още не са уведомени
$query = "
SELECT mr.id, mr.medicine_name, mr.created_at, u.email
FROM medicine_routines mr
JOIN users u ON mr.user_id = u.id
WHERE mr.created_at BETWEEN '$current_time' AND '$time_plus_5min'
AND (mr.notified IS NULL OR mr.notified = 0)
";

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $email = $row['email'];
    $medicine_name = $row['medicine_name'];
    $created_at = date('H:i', strtotime($row['crated_at']));

    // Изпращаме имейл
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kralia1231@gmail.com';
        $mail->Password = 'obpk grzn hriv lzja ';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('kralia1231@gmail.com', 'Medicine App');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Kратко напомняне за лекарство';
        $mail->CharSet = 'UTF-8'; // Задаване на UTF-8 кодировка
        $mail->Body = "
            Здравейте,<br><br>
            Напомняме ви, че в <strong>$created_at</strong> трябва да вземете вашето лекарство: 
            <strong>$medicine_name</strong>.<br><br>
            Поздрави,<br>Вашето приложение за лекарства.
        ";

        $mail->send();

        // Маркираме като уведомено
        $update_query = "UPDATE medicine_routines SET notified = 1 WHERE id = {$row['id']}";
        mysqli_query($conn, $update_query);

    } catch (Exception $e) {
        // Можем да логнем грешката ако искаш
        error_log("Email не беше изпратен до $email. Грешка: {$mail->ErrorInfo}");
    }
}
?>
