<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
include 'navbar.php';
require_once 'auth.php';
require_once 'track_history.php';

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role_id);
$stmt->fetch();
$stmt->close();

function displayMessage($type, $message) {
    echo "<div style='background-color: " . ($type === 'error' ? '#ffdddd' : '#ddffdd') . "; border: 1px solid " . ($type === 'error' ? '#ff8888' : '#88ff88') . "; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo htmlspecialchars($message);
    echo "</div>";
}

if (isset($_SESSION['error_message'])) {
    displayMessage('error', $_SESSION['error_message']);
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    displayMessage('success', $_SESSION['success_message']);
    unset($_SESSION['success_message']);
}


if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
    SELECT training_id, notification_text
    FROM user_notifications
    WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);

    if (!empty($notifications)) {
        $updateStmt = $conn->prepare("
            UPDATE user_notifications
            SET is_read = 1
            WHERE user_id = ? AND is_read = 0");
        $updateStmt->bind_param("i", $user_id);
        $updateStmt->execute();
    }

} else {
    $notifications = [];
}

?>



<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #bdacbb;
            margin-top: 50px;
            padding: 0;
        }

        h1, h2, h3 {
            margin-top: 30px;
            color: #333;
        }

        .trainer {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            padding: 20px;
            max-width: 800px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .trainer img {
            max-width: 150px;
            max-height: 150px;
            margin-right: 20px;
            border-radius: 8px;
        }

        .trainer-content {
            text-align: left;
        }

        .add-trainer-form {
            margin: 20px auto;
            padding: 20px;
            max-width: 600px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        .add-trainer-form form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .add-trainer-form label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .add-trainer-form input, 
        .add-trainer-form textarea, 
        .add-trainer-form button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .add-trainer-form button {
            background-color: rgb(139, 83, 179);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .add-trainer-form button:hover {
            background-color:rgb(150, 83, 179);
        }
        .notification-popup {
            position: fixed; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            background-color: #333; 
            color: #fff; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
            z-index: 1000; 
            display: none; 
            text-align: center; 
            width: 80%; 
            max-width: 400px; 
        }
    </style>
</head>
<body>

<h1>Добро пожаловать на главную страницу!</h1>

<?php
if (isset($_SESSION['user_id'])) {
    echo "<p>Привет, пользователь! Добро пожаловать на наш сайт!</p>";
} else {
    echo "<p>Пожалуйста, войдите или зарегистрируйтесь, чтобы получить доступ к дополнительным функциям.</p>";
}
?>

<h2>НАШИ ТРЕНЕРЫ</h2>





<div id="message-container"></div>
<div id="trainers-list">
    <?php
    $result = $conn->query("SELECT COUNT(*) AS count FROM trainers");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        echo "<p>Список тренеров пока пуст.</p>";
    } else {
        $result = $conn->query("SELECT * FROM trainers");
        while ($row = $result->fetch_assoc()) {
            echo "
            <div class='trainer'>
                <img src='" . htmlspecialchars($row['photo_path']) . "' alt='" . htmlspecialchars($row['name']) . "'>
                <div class='trainer-content'>
                    <h3>" . htmlspecialchars($row['name']) . "</h3>
                    <p>" . htmlspecialchars($row['bio']) . "</p>
                    <ul>
                        <li>Стаж: " . htmlspecialchars($row['experience']) . " лет</li>
                    </ul>
                </div>
            </div>";
        }
    }
    ?>
</div>

<?php if ($role_id == 2): ?>
<div class="add-trainer-form">
    <h3>Добавить нового тренера</h3>
    <form id="trainer-form" action="add_trainer.php" method="POST" enctype="multipart/form-data">
        <label for="name">Имя тренера:</label>
        <input type="text" name="name" id="name" required>

        <label for="bio">Биография:</label>
        <textarea name="bio" id="bio" required></textarea>

        <label for="experience">Опыт (в годах):</label>
        <input type="number" name="experience" id="experience" min="0" required>

        <label for="photo">Фотография (PNG, JPG, JPEG):</label>
        <input type="file" name="photo" id="photo" accept=".jpg, .jpeg, .png" required>

        <button type="submit">Добавить тренера</button>
    </form>
</div>
<?php endif; ?>

</body>
</html>


<script>
    const notifications = <?php echo json_encode($notifications); ?>;

    if (notifications.length > 0) {
        notifications.forEach(notification => {
            showNotification(notification.notification_text);
            
        });
    }

    console.log(notifications);

    function showNotification(message) {
        const popup = document.createElement('div');
        popup.className = 'notification-popup';
        popup.innerHTML = `<p>${message}</p><button onclick="closeNotification(this)">Закрыть</button>`;
        document.body.appendChild(popup);
        popup.style.display = 'block';

        setTimeout(() => {
            popup.remove();
        }, 10000);
    }

    function closeNotification(button) {
        const popup = button.parentElement;
        popup.remove();
    }
</script>
</body>
</html>
