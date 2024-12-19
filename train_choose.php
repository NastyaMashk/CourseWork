<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
include 'navbar.php';
require_once 'auth.php';
require_once 'track_history.php';

$stmt = $conn->prepare("SELECT id, type_name FROM training_types");
$stmt->execute();
$types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор типа тренировки</title>
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

        .train-cont {
            width: 80%;
            max-width: 400px;
            padding: 20px;
            margin-top: 30px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        select {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            padding: 10px 20px;
            background-color: rgba(139, 83, 179, 0.62);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        button:hover {
            background-color: #715ac8;
        }
    </style>
</head>
<body>

<h1>Выберите тип тренировки</h1>

<div class="train-cont">
    <form method="GET" action="train_list.php">
        <label for="training_type">Тип тренировки:</label>
        <select id="training_type" name="training_type" required>
            <?php foreach ($types as $type): ?>
                <option value="<?php echo $type['id']; ?>"><?php echo $type['type_name']; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Показать тренировки</button>
    </form>
</div>

</body>
</html>
