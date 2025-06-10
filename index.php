<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

function logUserAction($conn, $userId, $action) {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $userId, $action);
    $stmt->execute();
}

// Маркиране като взето
if (isset($_POST['mark_taken_id'])) {
    $medId = intval($_POST['mark_taken_id']);

    $stmt = $conn->prepare("SELECT scheduled_at FROM medicine_routines WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $medId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $scheduledTime = strtotime($row['scheduled_at']);
        $now = time();

        $status = ($now > $scheduledTime) ? 'late' : 'taken';
        $nowFormatted = date('Y-m-d H:i:s', $now);

        $stmt = $conn->prepare("UPDATE medicine_routines SET is_taken = 1, taken_at = ?, status = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $nowFormatted, $status, $medId, $userId);
        $stmt->execute();

        logUserAction($conn, $userId, "Marked medicine #$medId as $status at $nowFormatted");
    }
}

// Добавяне на ново лекарство
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['medicine_name'])) {
    $name = trim($_POST['medicine_name']);
    $dosage = trim($_POST['dosage']);
    $time = trim($_POST['time_of_day']);
    $notes = trim($_POST['notes']);
    $scheduled = $_POST['scheduled_at'];

    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO medicine_routines (user_id, medicine_name, dosage, time_of_day, notes, scheduled_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $userId, $name, $dosage, $time, $notes, $scheduled);
        $stmt->execute();

        logUserAction($conn, $userId, "Added medicine '$name' scheduled for $scheduled");
    }
}

// Извличане на рутините
$stmt = $conn->prepare("SELECT * FROM medicine_routines WHERE user_id = ? ORDER BY scheduled_at ASC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$routines = []; // <== Фикс тук
while ($row = $result->fetch_assoc()) {
    $routines[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medicine Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 p-6">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4 text-center">Welcome to your Medicine Routine Dashboard</h2>

        <div class="flex justify-center gap-4 mb-6">
            <a href="change_password.php" class="text-blue-600 hover:underline">Change Password</a>
            <a href="profile.php" class="text-blue-600 hover:underline">Моят профил</a>
            <a href="logout.php" class="text-blue-600 hover:underline">Logout</a>
        </div>

        <h3 class="text-xl font-semibold mb-2">Add New Medicine</h3>
        <form method="post" class="space-y-4 mb-6">
            <div>
                <label class="block font-medium">Medicine Name:</label>
                <input type="text" name="medicine_name" required class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block font-medium">Dosage:</label>
                <input type="text" name="dosage" class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block font-medium">Time of Day:</label>
                <select name="time_of_day" class="w-full border rounded p-2">
                    <option value="Morning">Morning</option>
                    <option value="Afternoon">Afternoon</option>
                    <option value="Evening">Evening</option>
                </select>
            </div>
            <div>
                <label class="block font-medium">Scheduled Time:</label>
                <input type="datetime-local" name="scheduled_at" required class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block font-medium">Notes:</label>
                <textarea name="notes" class="w-full border rounded p-2"></textarea>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add</button>
        </form>

        <h3 class="text-xl font-semibold mb-2">Your Routine</h3>

        <?php if (!empty($routines)): ?>
            <ul class="space-y-4">
                <?php foreach ($routines as $routine): ?>
                    <li class="p-4 rounded border <?= htmlspecialchars($routine['status'] ?? 'border-gray-300') ?> bg-white shadow-sm flex flex-col gap-1">
                        <div class="flex items-center justify-between">
                            <form method="post">
                                <?php if (!$routine['is_taken']): ?>
                                    <input type="hidden" name="mark_taken_id" value="<?= $routine['id'] ?>">
                                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Mark as Taken</button>
                                <?php else: ?>
                                    <span class="text-sm font-semibold text-gray-500"><?= $routine['status'] === 'late' ? '✓ Taken (Late)' : '✓ Taken' ?></span>
                                <?php endif; ?>
                            </form>
                            <strong class="text-lg"><?= htmlspecialchars($routine['medicine_name']) ?></strong>
                        </div>
                        <div><?= htmlspecialchars($routine['dosage']) ?> at <?= htmlspecialchars($routine['time_of_day']) ?></div>
                        <div class="text-sm text-gray-600">Scheduled: <?= htmlspecialchars($routine['scheduled_at']) ?></div>
                        <div class="italic text-sm text-gray-500"><?= htmlspecialchars($routine['notes']) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-center text-gray-500 italic">You have no scheduled medicines.</p>
        <?php endif; ?>
    </div>
</body>
</html>
