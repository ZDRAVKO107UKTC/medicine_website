<?php
session_start();
require_once 'db.php';

// Ако не е логнат
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
   
    function isStrongPassword($password) {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/', $password);
    }
    if (!isStrongPassword($_POST["new_password"])) {
        $error = "Паролата трябва да е поне 8 символа, да съдържа главни и малки букви, цифри и специални символи.";
    }    
    $current = trim($_POST["old_password"]);
    $new = trim($_POST["new_password"]);
    $confirm = trim($_POST["confirm_password"]);

    if (!empty($current) && !empty($new) && !empty($confirm)) {
        if ($new !== $confirm) {
            $error = "Новите пароли не съвпадат.";
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if (password_verify($current, $result["password"])) {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->bind_param("si", $newHash, $_SESSION['user_id']);
                if ($update->execute()) {
                    $success = "Паролата беше успешно променена.";
                } else {
                    $error = "Възникна грешка при записа.";
                }
            } else {
                $error = "Текущата парола е грешна.";
            }
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
    <title>Смяна на парола</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold text-center mb-6">Смяна на парола</h2>

        <?php if (!empty($error)): ?>
            <p class="text-red-600 mb-4 text-sm"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="text-green-600 mb-4 text-sm"><?php echo $success; ?></p>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Текуща парола:</label>
                <input type="password" name="old_password" required class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Нова парола:</label>
                <input type="password" name="new_password" required class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Потвърди новата парола:</label>
                <input type="password" name="confirm_password" required class="w-full border rounded px-3 py-2">
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
                Промени паролата
            </button>
        </form>

        <p class="mt-4 text-center text-sm">
            <a href="index.php" class="text-blue-600 hover:underline">Обратно към началната страница</a>
        </p>
    </div>
</body>
</html>