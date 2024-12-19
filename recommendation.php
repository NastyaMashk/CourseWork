<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
include 'navbar.php';
require_once 'auth.php';
require_once 'track_history.php';


$current_user_id = $_SESSION['user_id']; 


$query = "SELECT rating_weight, likes_weight, execution_weight FROM training_rating_weights WHERE id = 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $weights = $result->fetch_assoc();
} else {
    echo "Ошибка загрузки данных.";
    $weights = ['rating_weight' => 0, 'likes_weight' => 0, 'execution_weight' => 0];
}

function calculateTrainingRating($trainer_rating, $likes, $executions, $weights) {
   
    $w_r = $weights['rating_weight'];
    $w_l = $weights['likes_weight'];
    $w_e = $weights['execution_weight'];

    $execution_score = log(1 + $executions);

    $score = $w_r * $trainer_rating + $w_l * $likes + $w_e * $execution_score;

    return round($score, 1); 
}


$query = "
    SELECT 
        t.id,
        t.name,
        t.duration,
        t.calories_burned,
        u.username AS trainer_username,
        u.id AS trainer_id,
        u.trainer_rating,  
        COUNT(DISTINCT CASE WHEN tl.user_id = $current_user_id THEN tl.id END) AS like_count,
        COUNT(DISTINCT CASE WHEN te.user_id = $current_user_id THEN te.id END) AS execution_count
    FROM training_programs t
    LEFT JOIN users u ON u.id = t.created_by
    LEFT JOIN training_likes tl ON t.id = tl.training_id
    LEFT JOIN training_executions te ON t.id = te.training_id
    WHERE t.status = 'approved'
    GROUP BY t.id
";

$result = $conn->query($query);
$training_data = [];

while ($row = $result->fetch_assoc()) {
    $trainer_rating = $row['trainer_rating'];

    $training_rating = calculateTrainingRating($trainer_rating, $row['like_count'], $row['execution_count'], $weights);

    $training_data[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'duration' => $row['duration'],
        'calories_burned' => $row['calories_burned'],
        'trainer_username' => $row['trainer_username'],
        'trainer_id' => $row['trainer_id'],
        'trainer_rating' => $trainer_rating, 
        'like_count' => $row['like_count'],
        'execution_count' => $row['execution_count'],
        'training_rating' => $training_rating 
    ];
}

usort($training_data, function($a, $b) {
    return $b['training_rating'] <=> $a['training_rating'];  
});
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рекомендации</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #bdacbb;
            margin: 50px;
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
            margin-top: 30px;
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
    </style>
</head>
<body>

<div class="container">
    <h1>Рекомендованные тренировки</h1>

    <?php if (!empty($training_data)): ?>
        <table>
            <thead>
                <tr>
                    <th>Название тренировки</th>
                    <th>Длительность (мин)</th>
                    <th>Калории (ккал)</th>
                    <th>Тренер</th>
                    <th>Рейтинг тренера</th>
                    <th>Лайки</th>
                    <th>Выполнено раз</th>
                    <th>Рейтинг тренировки</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($training_data as $training): ?>
                    <tr>
                        <td>
                            <a href="training_details.php?id=<?= $training['id'] ?>">
                                <?= htmlspecialchars($training['name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($training['duration']) ?></td>
                        <td><?= htmlspecialchars($training['calories_burned']) ?></td>
                        <td><?= htmlspecialchars($training['trainer_username']) ?></td>
                        <td class="rating-badge"><?= $training['trainer_rating'] ?></td>
                        <td><?= $training['like_count'] ?></td>
                        <td><?= $training['execution_count'] ?></td>
                        <td class="training-rating"><?= $training['training_rating'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Нет данных для отображения.</p>
    <?php endif; ?>
</div>

</body>
</html>
