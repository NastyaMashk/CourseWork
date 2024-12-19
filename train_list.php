<?php
session_start();if (session_status() == PHP_SESSION_NONE) {
    session_start();
}require 'db.php';
include 'navbar.php';

if (isset($_GET['training_type'])) {
    $training_type_id = $_GET['training_type'];
    $stmt = $conn->prepare("
        SELECT 
            t.id, 
            t.name, 
            t.description, 
            t.duration, 
            t.calories_burned, 
            u.username AS trainer_name
        FROM training_programs t
        JOIN users u ON t.created_by = u.id
        WHERE t.training_type_id = ? AND t.status = 'approved'
    ");
    $stmt->bind_param("i", $training_type_id);
    $stmt->execute();
    $programs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список тренировок</title>
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

        .program-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .program-list li {
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .program-list li a {
            text-decoration: none;
            color: rgba(139, 83, 179, 0.62);
            font-weight: bold;
        }

        .program-list li a:hover {
            color: #715ac8;
        }

        .program-list li p {
            margin: 5px 0;
        }

        .program-list li .duration {
            font-style: italic;
            color: #666;
        }

        .program-list li .calories {
            color: #333;
        }

        .trainer-info {
            font-size: 0.9em;
            font-style: italic;
            color: #888;
            margin-left: 20px;
        }
    </style>
</head>
<body>
<h1>Список тренировок</h1>
<div class="container">

    <?php if (!empty($programs)): ?>
        <ul class="program-list">
            <?php foreach ($programs as $program): ?>
                <li>
                    <div>
                        <a href="training_details.php?id=<?php echo $program['id']; ?>"><?php echo htmlspecialchars($program['name']); ?></a>
                        <p><?php echo htmlspecialchars($program['description']); ?></p>
                        <p class="duration">(<?php echo htmlspecialchars($program['duration']); ?> минут)</p>
                        <p class="calories">Калории: <?php echo htmlspecialchars($program['calories_burned']); ?> ккал</p>
                    </div>
                    <div class="trainer-info">
                        Тренер: <?php echo htmlspecialchars($program['trainer_name']); ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Тренировки данного типа отсутствуют.</p>
    <?php endif; ?>
</div>

</body>
</html>
