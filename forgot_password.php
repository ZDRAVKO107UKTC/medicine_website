<?php
session_start();
require 'db.php';
require 'phpmailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$response = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $new_password_plain = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 10);
        $new_password_hashed = password_hash($new_password_plain, PASSWORD_DEFAULT);

        $update_query = "UPDATE users SET password='$new_password_hashed' WHERE email='$email'";
        if (mysqli_query($conn, $update_query)) {
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
                $mail->Subject = 'Възстановяване на парола';
                $mail->CharSet = 'UTF-8'; // Задаване на UTF-8 кодировка
                $mail->Body = 'Вашата нова парола е: <strong>' . $new_password_plain . '</strong>';

                $mail->send();
                $response = "success|Нова парола беше изпратена на вашия имейл!";
            } catch (Exception $e) {
                $response = "error|Имейлът не можа да бъде изпратен. Грешка: {$mail->ErrorInfo}";
            }
        } else {
            $response = "error|Грешка при обновяване на паролата.";
        }
    } else {
        $response = "error|Няма регистриран потребител с този имейл.";
    }

    echo $response;
    exit();
}
?>

<!-- HTML + Tailwind -->
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Забравена парола</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-400 to-purple-500 min-h-screen flex items-center justify-center">

<div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center text-gray-700">Забравена парола</h2>

    <div id="alert" class="hidden p-4 mb-4 text-sm rounded-lg" role="alert"></div>

    <form id="forgotForm" class="space-y-5">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Имейл адрес</label>
            <input type="email" name="email" id="email" required
                   class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-400 focus:border-transparent">
        </div>
        <button type="submit"
                class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition">
            Изпрати нова парола
        </button>
        <a href="login.php" class="text-blue-600 hover:underline">Върни се обратно</a>
    </form>
</div>

<script>
document.getElementById('forgotForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const alertBox = document.getElementById('alert');

    const formData = new FormData();
    formData.append('email', email);

    const response = await fetch('forgot_password.php', {
        method: 'POST',
        body: formData
    });

    const text = await response.text();
    const [status, message] = text.split('|');

    if (status === 'success') {
        alertBox.className = 'p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg';
    } else {
        alertBox.className = 'p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg';
    }

    alertBox.textContent = message;
    alertBox.classList.remove('hidden');
});
</script>

</body>
</html>
