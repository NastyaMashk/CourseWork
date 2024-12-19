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

$is_trainer = $user['role_id'] == 3;

if (!$is_trainer) {
    echo "У вас нет прав для доступа к этой странице.";
    exit();
}


$stmt = $conn->prepare("SELECT id, type_name FROM training_types");
$stmt->execute();
$types_result = $stmt->get_result();
$types = $types_result->fetch_all(MYSQLI_ASSOC);



if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_program'])) {
    $program_name = trim($_POST['program_name']);
    $description = trim($_POST['description']);
    $training_type_id = $_POST['training_type'];
    $duration = $_POST['duration'];
    $calories_burned = $_POST['calories_burned'];

    if (empty($program_name)) {
        $error_message = "Название программы не может состоять только из пробелов! Исправьте неточность.";
    } elseif (empty($description)) {
        $error_message = "Описание программы не может состоять только из пробелов! Исправьте неточность.";
    } else {
        $status = 'pending'; 
        $stmt = $conn->prepare("
            INSERT INTO training_programs (name, description, created_by, training_type_id, duration, calories_burned, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssiiiss", $program_name, $description, $user_id, $training_type_id, $duration, $calories_burned, $status);

        if ($stmt->execute()) {
            $success_message = "Программа отправлена на рассмотрение администратору!";
        } else {
            $error_message = "Ошибка при добавлении программы: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание программы тренировки</title>
    <style>
        body {
            margin: 0;

            font-family: Arial, sans-serif;
            background-color: #bdacbb;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 10px;
        }

        input[type="text"], input[type="number"], textarea, select {
            width: 100%;
            padding: 10px;
            margin: 5px 0 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        button {
            padding: 10px 15px;
            background-color: rgba(139, 83, 179, 0.62);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #715ac8;
        }

        .message {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Создание программы тренировки</h1>

    <?php if (isset($success_message)): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="program_name">Название программы:</label>
        <input type="text" id="program_name" name="program_name" required>

        <label for="description">Описание программы:</label>
        <textarea id="description" name="description" rows="5" required></textarea>

        <label for="training_type">Тип тренировки:</label>
        <select id="training_type" name="training_type" required>
            <option value="">Выберите тип тренировки</option>
            <?php foreach ($types as $type): ?>
                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="duration">Примерное время тренировки (минут):</label>
        <input type="number" id="duration"  min ="5" max="120" name="duration" required>

       <label for="calories_burned">Примерное количество потраченных калорий:</label>
       <input type="number" id="calories_burned" min="20" max="1000" name="calories_burned" >

        <button type="submit" name="add_program">Добавить программу</button>
    </form>
</div>
</body>
</html>
