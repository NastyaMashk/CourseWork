<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';
include 'navbar.php';
require_once 'auth.php';
require_once 'track_history.php';


$query = "SELECT approved_weight, likes_weight, execution_weight FROM rating_weights WHERE id = 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $weights = $result->fetch_assoc();
} else {
    $_SESSION['error_message'] = "Ошибка загрузки данных.";
    header("Location: admin_rating_weights.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
 
    $approved_weight = (float) $_POST['approved_weight'];
    $likes_weight = (float) $_POST['likes_weight'];
    $execution_weight = (float) $_POST['execution_weight'];

    $total_weight = $approved_weight + $likes_weight + $execution_weight;
    if ($total_weight != 10) {
        $_SESSION['error_message'] = "Сумма весов должна равняться 10!";
        header("Location: admin_rating_weights.php");
        exit();
    } else {
        
        $update_stmt = $conn->prepare("UPDATE rating_weights SET approved_weight = ?, likes_weight = ?, execution_weight = ? WHERE id = 1");
        $update_stmt->bind_param("ddd", $approved_weight, $likes_weight, $execution_weight);

        if ($update_stmt->execute()) {
           
            $trainer_query = "
                SELECT 
                    u.id AS trainer_id,
                    u.username,
                    COUNT(DISTINCT tl.id) AS total_likes,
                    SUM(CASE WHEN t.status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
                    SUM(CASE WHEN t.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
                    COUNT(te.id) AS total_executions
                FROM users u
                LEFT JOIN training_programs t ON u.id = t.created_by
                LEFT JOIN training_likes tl ON t.id = tl.training_id
                LEFT JOIN training_executions te ON t.id = te.training_id
                WHERE u.role_id = 3
                GROUP BY u.id
            ";
            $trainer_result = $conn->query($trainer_query);

            if ($trainer_result && $trainer_result->num_rows > 0) {
                while ($row = $trainer_result->fetch_assoc()) {
                    $approved_total = $row['approved_count'] + $row['rejected_count'];
                    $approval_ratio = $approved_total > 0 ? $row['approved_count'] / $approved_total : 0;
                    $like_ratio = $approved_total > 0 ? $row['total_likes'] / $approved_total : 0;
                    $execution_ratio = ($row['total_executions'] > 0) ? 1 : 0;

                    
                    $overall_rating = round(($approval_ratio * $weights['approved_weight'] + $like_ratio * $weights['likes_weight'] + $execution_ratio * $weights['execution_weight']), 1);

          
                    $update_trainer_rating = $conn->prepare("UPDATE users SET trainer_rating = ? WHERE id = ?");
                    $update_trainer_rating->bind_param("di", $overall_rating, $row['trainer_id']);
                    $update_trainer_rating->execute();
                }
            }

            $_SESSION['success_message'] = "Данные успешно обновлены! Нажмите повторно для сохранения данных в бд";
            header("Location: admin_rating_weights.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Ошибка обновления данных: " . $update_stmt->error;
            header("Location: admin_rating_weights.php");
            exit();
        }
    }
}


$trainer_query = "
    SELECT 
        u.id AS trainer_id,
        u.username,
        COUNT(DISTINCT tl.id) AS total_likes,
        SUM(CASE WHEN t.status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN t.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
        COUNT(te.id) AS total_executions
    FROM users u
    LEFT JOIN training_programs t ON u.id = t.created_by
    LEFT JOIN training_likes tl ON t.id = tl.training_id
    LEFT JOIN training_executions te ON t.id = te.training_id
    WHERE u.role_id = 3
    GROUP BY u.id
";
$trainer_result = $conn->query($trainer_query);

$trainers = [];
if ($trainer_result && $trainer_result->num_rows > 0) {
    while ($row = $trainer_result->fetch_assoc()) {
        $approved_total = $row['approved_count'] + $row['rejected_count'];
        $approval_ratio = $approved_total > 0 ? $row['approved_count'] / $approved_total : 0;
        $like_ratio = $approved_total > 0 ? $row['total_likes'] / $approved_total : 0;
        $execution_ratio = ($row['total_executions'] > 0) ? 1 : 0;

        $overall_rating = round(($approval_ratio * $weights['approved_weight'] + $like_ratio * $weights['likes_weight'] + $execution_ratio * $weights['execution_weight']), 1);

        $trainers[] = [
            'username' => $row['username'],
            'overall_rating' => $overall_rating
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки рейтинга тренеров</title>
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
        .container {
            width: 80%;
            max-width: 600px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
        }
        h1, h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .current-weights {
            background-color: #eaeaea;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
        .success-message {
            color: green;
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-weight: bold;
        }
        input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px;
            background-color: rgba(139, 83, 179, 0.62);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #715ac8;
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
            text-align: center;
        }
        th {
            background-color: rgba(139, 83, 179, 0.62);
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Настройки рейтинга тренеров</h1>

    <div class="current-weights">
        <p><strong>Текущий вес для утвержденных программ:</strong> <?php echo htmlspecialchars($weights['approved_weight']); ?></p>
        <p><strong>Текущий вес для лайков:</strong> <?php echo htmlspecialchars($weights['likes_weight']); ?></p>
        <p><strong>Текущий вес для выполнений:</strong> <?php echo htmlspecialchars($weights['execution_weight']); ?></p>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="success-message"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="approved_weight">Вес для утвержденных программ:</label>
        <input type="number" step="1" min="0" max="10" name="approved_weight" id="approved_weight" value="<?php echo htmlspecialchars($weights['approved_weight']); ?>" required>

        <label for="likes_weight">Вес для лайков:</label>
        <input type="number" step="1" min="0" max="10" name="likes_weight" id="likes_weight" value="<?php echo htmlspecialchars($weights['likes_weight']); ?>" required>

        <label for="execution_weight">Вес для выполнений:</label>
        <input type="number" step="1" min="0" max="10" name="execution_weight" id="execution_weight" value="<?php echo htmlspecialchars($weights['execution_weight']); ?>" required>

        <button type="submit">Сохранить</button>
    </form>

    <h2>Рейтинг тренеров</h2>
    <table>
        <thead>
            <tr>
                <th>Имя пользователя</th>
                <th>Конечный рейтинг</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trainers as $trainer): ?>
                <tr>
                    <td><?php echo htmlspecialchars($trainer['username']); ?></td>
                    <td><?php echo htmlspecialchars($trainer['overall_rating']); ?>/10</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>