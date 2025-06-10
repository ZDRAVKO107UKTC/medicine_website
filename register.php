<?php
session_start();
require_once 'db.php';
require 'phpmailer/vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateRandomPassword($length = 10) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()'), 0, $length);
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $age = trim($_POST["age"]);

    if (!empty($username) && !empty($email) && !empty($age)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $generatedPassword = generateRandomPassword();
            $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);

            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, age, password) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssis", $username, $email, $age, $hashedPassword);

            if ($insert_stmt->execute()) {
                // Изпращане на имейл
                $mail = new PHPMailer(true);
                try {
                    // SMTP настройки
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'kralia1231@gmail.com'; // <--- смени с твоя Gmail
                    $mail->Password = 'obpk grzn hriv lzja '; // <--- App Password, не обикновена парола!
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('kralia1231@gmail.com', 'Medicine System');
                    $mail->addAddress($email, $username);
                    $mail->isHTML(true);
                    $mail->Subject = 'Password for Medicine System';
                    $mail->CharSet = 'UTF-8'; // Задаване на UTF-8 кодировка
                    $mail->Body = "Здравей, $username!<br>Вашата автоматично генерирана парола е: <strong>$generatedPassword</strong><br><a href='login.php'>Влезте тук</a>";

                    $mail->send();
                    $success = "Регистрацията е успешна! Паролата е изпратена на вашия имейл.";
                } catch (Exception $e) {
                    $error = "Имейл не можа да бъде изпратен. Грешка: {$mail->ErrorInfo}";
                }
            } else {
                $error = "Грешка при регистрацията.";
            }
        } else {
            $error = "Потребителското име е вече заето.";
        }
    } else {
        $error = "Моля, попълнете всички полета.";
    }
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold text-center mb-6">Регистрация на потребител</h2>

        <?php if ($error): ?>
            <p class="text-red-600 mb-4 text-sm"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="text-green-600 mb-4 text-sm"><?php echo $success; ?></p>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Потребителско име:</label>
                <input type="text" name="username" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Имейл:</label>
                <input type="email" name="email" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Възраст:</label>
                <input type="number" name="age" min="1" max="120" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400">
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
                Регистрирай ме
            </button>
        </form>

        <p class="mt-4 text-center text-sm">
            Вече имате акаунт? <a href="login.php" class="text-blue-600 hover:underline">Влезте тук</a>.
        </p>
    </div>
</body>
</html>