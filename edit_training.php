<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: trainer_programs.php");
    exit();
}

$training_id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM training_programs WHERE id = ? AND created_by = ?");
$stmt->bind_param("ii", $training_id, $user_id);
$stmt->execute();
$training = $stmt->get_result()->fetch_assoc();

if (!$training) {
    header("Location: trainer_programs.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_training'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $calories_burned = $_POST['calories_burned'];
    $stmt = $conn->prepare("
        UPDATE training_programs 
        SET name = ?, description = ?, duration = ?, calories_burned = ?
        WHERE id = ? AND created_by = ?
    ");
    $stmt->bind_param("ssiiii", $name, $description, $duration, $calories_burned, $training_id, $user_id);

    if ($stmt->execute()) {
        $success_message = "Тренировка успешно обновлена!";
        $update_success = true;
    } else {
        $error_message = "Ошибка при обновлении тренировки: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать тренировку</title>
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
            max-width: 600px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
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

        function redirectToDetails(trainingId) {
            setTimeout(function () {
                window.location.href = 'training_details.php?id=' + trainingId;
            }, 1000);
        }
    </script>
</head>
<body>

<div class="container">
    <h1>Редактировать тренировку</h1>
    <?php if (isset($success_message)): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Название тренировки:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($training['name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Описание:</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($training['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="duration">Длительность (мин):</label>
            <input type="number" id="duration" min="5" max="120" name="duration" value="<?php echo htmlspecialchars($training['duration']); ?>" required>
        </div>

        <div class="form-group">
            <label for="calories_burned">Калории (ккал):</label>
            <input type="number" id="calories_burned" min ="10" max="1000" name="calories_burned" value="<?php echo htmlspecialchars($training['calories_burned']); ?>" required>
        </div>

        <button type="submit" name="update_training" class="btn-submit">Сохранить изменения</button>
    </form>
</div>

<?php if (isset($update_success) && $update_success): ?>
    <script>
        redirectToDetails(<?php echo $training_id; ?>);
    </script>
<?php endif; ?>

</body>
</html>
