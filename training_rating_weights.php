<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
include 'navbar.php';
require_once 'auth.php';
require_once 'track_history.php';

$query = "SELECT rating_weight, likes_weight, execution_weight FROM training_rating_weights WHERE id = 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $weights = $result->fetch_assoc();
} else {
    $insert_query = "INSERT INTO training_rating_weights (id, rating_weight, likes_weight, execution_weight) VALUES (1, 0, 0, 0)";
    if ($conn->query($insert_query)) {
        $weights = ['rating_weight' => 0, 'likes_weight' => 0, 'execution_weight' => 0];
    } else {
        echo "Ошибка создания записи: " . $conn->error;
        $weights = ['rating_weight' => 0, 'likes_weight' => 0, 'execution_weight' => 0];
    }
}

$error_message = "";
$success_message = ""; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rating_weight = (float) $_POST['rating_weight'];
    $likes_weight = (float) $_POST['likes_weight'];
    $executions_weight = (float) $_POST['executions_weight'];

    $update_stmt = $conn->prepare("UPDATE training_rating_weights SET rating_weight = ?, likes_weight = ?, execution_weight = ? WHERE id = 1");
    $update_stmt->bind_param("ddd", $rating_weight, $likes_weight, $executions_weight);
    
    if ($update_stmt->execute()) {
        $success_message = "Данные успешно обновлены!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();  
    } else {
        $error_message = "Ошибка обновления данных: " . $update_stmt->error;
    }
}


$trainer_query = "
    SELECT 
        u.id AS trainer_id,
        u.username,
        COUNT(DISTINCT tl.id) AS total_likes,
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
        $avg_rating = $row['avg_rating'] ?? 0;
        $like_ratio = $row['total_likes'] ?? 0;
        $execution_ratio = $row['total_executions'] > 0 ? 1 : 0;

        $overall_rating = round(($avg_rating * $weights['rating_weight'] + $like_ratio * $weights['likes_weight'] + $execution_ratio * $weights['execution_weight']), 1);
        
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
    <h1>Настройки коэффициентов</h1>

    <?php if ($success_message): ?>
        <div class="success-message"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <div class="current-weights">
        <p><strong>Вес для рейтинга тренера:</strong> <?php echo htmlspecialchars($weights['rating_weight']); ?></p>
        <p><strong>Вес для лайков:</strong> <?php echo htmlspecialchars($weights['likes_weight']); ?></p>
        <p><strong>Вес для выполнений:</strong> <?php echo htmlspecialchars($weights['execution_weight']); ?></p>
    </div>

    <form method="POST" action="">
        <label for="rating_weight">Коэффициент для рейтинга:</label>
        <input type="number" step="0.1" min="0" max="1" name="rating_weight" id="rating_weight" value="<?php echo htmlspecialchars($weights['rating_weight']); ?>" required>

        <label for="likes_weight">Коэффициент для лайков:</label>
        <input type="number" step="0.1" min="0" max="1" name="likes_weight" id="likes_weight" value="<?php echo htmlspecialchars($weights['likes_weight']); ?>" required>

        <label for="executions_weight">Коэффициент для выполнений:</label>
        <input type="number" step="0.1" min="0" max="1" name="executions_weight" id="execution_weight" value="<?php echo htmlspecialchars($weights['execution_weight']); ?>" required>

        <button type="submit">Сохранить</button>
    </form>

</div>

</body>
</html>
