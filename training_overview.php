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

$weights_stmt = $conn->prepare("SELECT approved_weight, likes_weight, execution_weight FROM rating_weights LIMIT 1");
$weights_stmt->execute();
$weights = $weights_stmt->get_result()->fetch_assoc();

if ($weights) {
    $approved_weight = (float) $weights['approved_weight'];
    $likes_weight = (float) $weights['likes_weight'];
    $execution_weight = (float) $weights['execution_weight'];
} else {
    $approved_weight = 0.0;
    $likes_weight = 0.0;
    $execution_weight = 0.0;
}

$stmt = $conn->prepare("
    SELECT 
        u.username,
        COUNT(DISTINCT tl.id) AS total_likes,
        SUM(CASE WHEN t.status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN t.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
        COUNT(te.id) AS total_executions
    FROM users u
    LEFT JOIN training_programs t ON u.id = t.created_by
    LEFT JOIN training_likes tl ON t.id = tl.training_id
    LEFT JOIN training_executions te ON t.id = te.training_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$trainer_rating = $stmt->get_result()->fetch_assoc();

if ($trainer_rating && isset($trainer_rating['username'])) {
    $current_username = $trainer_rating['username'];
    $approved_count = $trainer_rating['approved_count'] ?: 0;
    $rejected_count = $trainer_rating['rejected_count'] ?: 0;
    $total_executions = $trainer_rating['total_executions'] ?: 0;

    $approved_total = $approved_count + $rejected_count;
    $approval_ratio = $approved_total > 0 ? $approved_count / $approved_total : 0;
    $like_ratio = $approved_total > 0 ? $trainer_rating['total_likes'] / $approved_total : 0;
    $execution_ratio = ($total_executions > 0) ? 1 : 0;

    $overall_rating = round(($approval_ratio * $approved_weight + $like_ratio * $likes_weight + $execution_ratio * $execution_weight), 1);
} else {
    $error_message = "Пользователь не найден или данные отсутствуют.";
    $current_username = "Неизвестный пользователь";  
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_training'])) {
    $training_id = (int)$_POST['delete_training'];
    $delete_stmt = $conn->prepare("DELETE FROM training_programs WHERE id = ? AND created_by = ?");
    $delete_stmt->bind_param("ii", $training_id, $user_id);
    if ($delete_stmt->execute()) {
        $success_message = "Тренировка успешно удалена!";
    } else {
        $error_message = "Ошибка при удалении тренировки: " . $delete_stmt->error;
    }
    $delete_stmt->close();
}

$stmt = $conn->prepare("
    SELECT 
        t.id, 
        t.name,
        t.description,
        t.duration,
        t.calories_burned,
        t.status,
        COUNT(te.id) AS execution_count,
        COUNT(DISTINCT tl.id) AS like_count,
        GROUP_CONCAT(DISTINCT CONCAT(u.username, ' (', 
            (SELECT COUNT(te_inner.id) FROM training_executions te_inner WHERE te_inner.user_id = te.user_id AND te_inner.training_id = t.id), 
            ' раз)') SEPARATOR ', ') AS executed_by
    FROM training_programs t
    LEFT JOIN training_executions te ON t.id = te.training_id
    LEFT JOIN users u ON te.user_id = u.id
    LEFT JOIN training_likes tl ON t.id = tl.training_id
    WHERE t.created_by = ?
    GROUP BY t.id
    ORDER BY t.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$programs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$approved_count = 0;
$rejected_count = 0;

foreach ($programs as $program) {
    if ($program['status'] === 'approved') {
        $approved_count++;
    } elseif ($program['status'] === 'rejected') {
        $rejected_count++;
    }
}
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обзор тренировок</title>
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
            margin-top: 70px;
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

        .btn {
            padding: 8px 16px;
            background-color: rgba(139, 83, 179, 0.62);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }

        .btn:hover {
            background-color: #715ac8;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .btn-submit {
            padding: 10px 20px;
            background-color: rgba(139, 83, 179, 0.62);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-submit:hover {
            background-color: #715ac8;
        }

        input[type="text"] {
            padding: 10px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
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
    <script>
        function showEditForm() {
            document.getElementById('current_username_display').style.display = 'none';
            document.getElementById('edit_username_form').style.display = 'block';
        }
    </script>
</head>
<body>

<div class="container">
    <h1>Обзор тренировок</h1>
    <?php if (isset($success_message)): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="form-group">
        <label for="current_username">Текущее имя пользователя:</label>
        <div id="current_username_display">
            <span><?php echo htmlspecialchars($current_username); ?></span>
            <button type="button" class="btn" onclick="showEditForm()">Редактировать</button>
        </div>
        <form method="POST" action="" id="edit_username_form" style="display:none;">
            <input type="text" id="new_username" name="new_username" value="<?php echo htmlspecialchars($current_username); ?>" required>
            <button type="submit" name="update_username" class="btn-submit">Сохранить</button>
        </form>
        <p>Ваш рейтинг: <?php echo htmlspecialchars($overall_rating); ?>/10</p>
        <p>Одобренные тренировки: <?php echo htmlspecialchars($approved_count); ?></p>
        <p>Отвергнутые тренировки: <?php echo htmlspecialchars($rejected_count); ?></p>
        <p>Общее количество выполнений: <?php echo htmlspecialchars($total_executions); ?></p>
    </div>

    <?php if (!empty($programs)): ?>
        <table>
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Длительность (мин)</th>
                    <th>Калории (ккал)</th>
                    <th>Выполнено раз</th>
                    <th>Выполнили</th>
                    <th>Статус</th>
                    <th>Лайки</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($programs as $program): ?>
                    <tr>
                        <td>
                            <a href="training_details.php?id=<?php echo $program['id']; ?>">
                                <?php echo htmlspecialchars($program['name']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($program['duration']); ?></td>
                        <td><?php echo htmlspecialchars($program['calories_burned']); ?></td>
                        <td><?php echo htmlspecialchars($program['execution_count']); ?></td>
                        <td><?php echo htmlspecialchars($program['executed_by'] ?: 'Нет выполнений'); ?></td>
                        <td><?php echo htmlspecialchars($program['status']); ?></td>
                        <td><?php echo htmlspecialchars($program['like_count']); ?></td>
                        <td>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="delete_training" value="<?php echo $program['id']; ?>">
                                <button type="submit" class="btn">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>
        <p>У вас нет созданных тренировок.</p>
    <?php endif; ?>

    <a href="trainer_programs.php" class="btn">Создать новую тренировку</a>
    <a href="export_to_excel.php" class="btn">Экспорт в Excel</a>
</div>

</body>
</html>
