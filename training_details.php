<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
include 'navbar.php';
require_once 'auth.php';
require_once 'track_history.php';


if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$training_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$user_role = $user_info['role_id'];

$stmt = $conn->prepare("
    SELECT
        t.id, 
        t.name,
        t.description,
        t.duration,
        t.calories_burned,
        u.username AS trainer_name,
        t.created_by,
        COUNT(tl.id) AS like_count  
    FROM training_programs t
    JOIN users u ON t.created_by = u.id
    LEFT JOIN training_likes tl ON t.id = tl.training_id  
    WHERE t.id = ?
    GROUP BY t.id  
");
$stmt->bind_param("i", $training_id);
$stmt->execute();
$program = $stmt->get_result()->fetch_assoc();

$is_trainer = $program['created_by'] == $user_id || $user_role == 2; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_training']) && !$is_trainer) {

    $stmt = $conn->prepare("INSERT INTO training_requests (user_id, training_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $training_id);
    $stmt->execute();
    
    $_SESSION['message'] = "Запрос на выполнение отправлен тренеру.";
    header("Location: my_training_requests.php");
    exit();
}

    $stmt = $conn->prepare("SELECT calories_burned FROM calorie_tracker WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $current_burned_calories = $result ? $result['calories_burned'] : 0;

    if ($result) {
        $stmt = $conn->prepare("UPDATE calorie_tracker SET calories_burned = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $new_burned_calories, $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO calorie_tracker (user_id, calories_burned) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $new_burned_calories);
    }
    $stmt->execute();
    $training_completed = true;  

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Информация о тренировке</title>
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
            max-width: 800px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
            text-align: center;
        }

        p {
            margin: 10px 0;
            font-size: 16px;
        }

        .duration, .calories {
            font-style: italic;
            color: #666;
        }

        button {
            padding: 10px 20px;
            background-color: rgba(139, 83, 179, 0.62);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }

        button:hover {
            background-color: #715ac8;
        }

        .message {
            margin-top: 20px;
            font-size: 18px;
            color: #333;
        }

        .trainer-info {
            font-size: 0.9em;
            font-style: italic;
            color: #888;
        }
    </style>
</head>
<body>
<div class="container">
    <h1><?php echo htmlspecialchars($program['name']); ?> <span class="trainer-info">(Тренер: <?php echo htmlspecialchars($program['trainer_name']); ?>)</span></h1>
    <p><?php echo htmlspecialchars($program['description']); ?></p>
    <p class="duration">Длительность: <?php echo htmlspecialchars($program['duration']); ?> минут</p>
    <p class="calories">Калории: <?php echo htmlspecialchars($program['calories_burned']); ?> ккал</p>

    <?php if (!$is_trainer): ?>
        <form method="POST" action="like_training.php">
            <input type="hidden" name="training_id" value="<?php echo htmlspecialchars($program['id']); ?>">
            <button type="submit" class="btn">
                Лайк (<?php echo isset($program['like_count']) ? htmlspecialchars($program['like_count']) : 0; ?>)
            </button>
        </form>
    <?php endif; ?>

    <form method="POST" action="">
        <?php if ($is_trainer): ?>
            <button type="button" onclick="window.location.href='edit_training.php?id=<?php echo $training_id; ?>'">Редактировать тренировку</button>
        <?php else: ?>
            <button type="submit" name="complete_training">Выполнить тренировку</button>
        <?php endif; ?>
    </form>

    <?php if (isset($_SESSION['message'])): ?>
        <p class="message"><?php echo htmlspecialchars($_SESSION['message']); ?></p>
        <?php unset($_SESSION['message']);  ?>
    <?php endif; ?>
</div>



</body>
</html>
