<?php
session_start();
require_once 'db.php';

// Пренасочване, ако потребителят вече е логнат
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Защита от сесийни атаки
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                header("Location: index.php");
                exit();
            } else {
                $error = "Грешна парола.";
            }
        } else {
            $error = "Потребителят не съществува.";
        }
    } else {
        $error = "Всички полета са задължителни.";
    }
}
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold text-center mb-6">Вход</h2>

        <?php if (!empty($error)): ?>
            <p class="text-red-600 mb-4 text-sm"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post" action="" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Потребителско име:</label>
                <input type="text" name="username" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Парола:</label>
                <input type="password" name="password" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-400">
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
                Вход
            </button>
        </form>

        <p class="mt-4 text-center text-sm">
            Нямате акаунт? <a href="register.php" class="text-blue-600 hover:underline">Регистрирайте се тук</a>.
            <br>
            <a href="forgot_password.php" class="text-blue-600 hover:underline">Забравена парола</a>
        </p>
    </div>
</body>
</html>