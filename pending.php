<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
include 'navbar.php';
require_once 'auth.php';
require_once 'track_history.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$is_admin = $user['role_id'] == 2;

if (!$is_admin) {
    echo "У вас нет прав для доступа к этой странице.";
    exit();
}

$stmt = $conn->prepare("SELECT id, name, description, created_by, training_type_id, duration, calories_burned FROM training_programs WHERE status = 'pending'");
$stmt->execute();
$programs_result = $stmt->get_result();
$programs = $programs_result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $program_id = $_POST['program_id'];
    $action = $_POST['action']; 

    if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE training_programs SET status = 'approved' WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE training_programs SET status = 'rejected' WHERE id = ?");
    }

    $stmt->bind_param("i", $program_id);
    if ($stmt->execute()) {
        $success_message = "Программа успешно " . ($action == 'approve' ? "одобрена" : "отклонена") . ".";
    } else {
        $error_message = "Ошибка при обновлении программы: " . $stmt->error;
    }
    $stmt->close();
    header("Location: pending.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рассмотрение программ тренировок</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #bdacbb;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            margin-top: 50px;
            text-align: center;
        }

        .container {
            width: 80%;
            max-width: 1200px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: rgba(139, 83, 179, 0.62);
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        button {
            padding: 8px 16px;
            background-color: rgba(139, 83, 179, 0.62);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button.reject {
            background-color: #d9534f;
        }

        button:hover {
            background-color: #715ac8;
        }

        .message {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

    </style>
</head>
<body>

<div class="container">
    <h1>Рассмотрение программ тренировок</h1>

    <?php if (isset($success_message)): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <table>
        <thead>
        <tr>
            <th>Название программы</th>
            <th>Описание</th>
            <th>Продолжительность (мин)</th>
            <th>Калории</th>
            <th>Действие</th>
        </tr>
        </thead>
        <tbody>
        <?php if (count($programs) > 0): ?>
            <?php foreach ($programs as $program): ?>
                <tr>
                    <td><?php echo htmlspecialchars($program['name']); ?></td>
                    <td><?php echo htmlspecialchars($program['description']); ?></td>
                    <td><?php echo htmlspecialchars($program['duration']); ?> мин</td>
                    <td><?php echo htmlspecialchars($program['calories_burned']); ?> ккал</td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
                            <button type="submit" name="action" value="approve">Одобрить</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
                            <button type="submit" name="action" value="reject" class="reject">Отклонить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">Нет программ на рассмотрении.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
