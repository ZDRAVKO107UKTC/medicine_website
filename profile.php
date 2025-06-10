<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Функция за логване на действия
function logActivity($conn, $user_id, $action) {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $action);
    $stmt->execute();
}

// Зареждане на текущите данни
$stmt = $conn->prepare("SELECT username, email, age FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Потребителят не е намерен.");
}

// Обработка на промяна
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_email = trim($_POST["email"]);
    $new_age = trim($_POST["age"]);

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Невалиден имейл адрес.";
    } elseif (!filter_var($new_age, FILTER_VALIDATE_INT) || $new_age < 1 || $new_age > 120) {
        $error = "Невалидна възраст.";
    } else {
        $update = $conn->prepare("UPDATE users SET email = ?, age = ? WHERE id = ?");
        $update->bind_param("sii", $new_email, $new_age, $user_id);
        if ($update->execute()) {
            $success = "Данните са успешно обновени.";

            // Обновяване на сесията
            $_SESSION['user_email'] = $new_email;
            $_SESSION['user_age'] = $new_age;

            // Обновяване на локалната променлива
            $user['email'] = $new_email;
            $user['age'] = $new_age;

            // Логване на действието
            logActivity($conn, $user_id, 'Редакция на профил');
        } else {
            $error = "Грешка при обновяване.";
        }
    }
}

// Зареждане на последните действия
$logs = [];
$log_stmt = $conn->prepare("SELECT action, timestamp FROM activity_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10");
$log_stmt->bind_param("i", $user_id);
$log_stmt->execute();
$log_result = $log_stmt->get_result();
while ($row = $log_result->fetch_assoc()) {
    $logs[] = $row;
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Моят профил</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 py-8 px-4">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4 text-center">Профил на потребител: <?php echo htmlspecialchars($user['username']); ?></h2>

        <?php if ($error): ?>
            <p class="text-red-600 mb-4 text-sm"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="text-green-600 mb-4 text-sm"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form method="post" class="space-y-4 mb-6">
            <div>
                <label class="block text-sm font-medium mb-1">Имейл:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Възраст:</label>
                <input type="number" name="age" value="<?php echo htmlspecialchars($user['age']); ?>" min="1" max="120" required class="w-full border rounded px-3 py-2">
            </div>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Запази промените</button>
        </form>

        <hr class="my-6">

        <h3 class="text-xl font-semibold mb-3">История на действията</h3>
        <?php if (count($logs) > 0): ?>
            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-700">
                <?php foreach ($logs as $log): ?>
                    <li><?php echo htmlspecialchars($log['timestamp']) . " — " . htmlspecialchars($log['action']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-gray-500 text-sm">Няма записани действия.</p>
        <?php endif; ?>

        <div class="mt-6 text-center space-x-4">
            <a href="change_password.php" class="text-blue-600 hover:underline">Смяна на парола</a>
            <a href="index.php" class="text-blue-600 hover:underline">Начало</a>
            <a href="logout.php" class="text-blue-600 hover:underline">Изход</a>
        </div>
    </div>
</body>
</html>