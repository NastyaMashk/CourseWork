<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'navbar.php';
require 'db.php';
require_once 'auth.php';
require_once 'track_history.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : null;
    $age = isset($_POST['age']) ? $_POST['age'] : null;
    $weight = isset($_POST['weight']) ? $_POST['weight'] : null;
    $height = isset($_POST['height']) ? $_POST['height'] : null;
    $gender = isset($_POST['gender']) ? $_POST['gender'] : null;
    $activity_level = isset($_POST['activity_level']) ? $_POST['activity_level'] : null;
    $errors = [];
    if ($username !== null && $username === '') {
        $errors[] = "Имя пользователя не может быть пустым или состоять только из пробелов.";
    }

    if (!empty($errors)) {
        $error_message = implode("<br>", $errors);
    } else {

        $update_query = "UPDATE users SET ";
        $update_params = [];
        $param_types = '';
        $fields = [];

        if ($username !== null) {
            $fields[] = "username = ?";
            $update_params[] = $username;
            $param_types .= "s";
        }

        if ($age !== null) {
            $fields[] = "age = ?";
            $update_params[] = $age;
            $param_types .= "i";
        }

        if ($weight !== null) {
            $fields[] = "weight = ?";
            $update_params[] = $weight;
            $param_types .= "d";
        }

        if ($height !== null) {
            $fields[] = "height = ?";
            $update_params[] = $height;
            $param_types .= "i";
        }

        if ($gender !== null) {
            $fields[] = "gender = ?";
            $update_params[] = $gender;
            $param_types .= "s";
        }

        if ($activity_level !== null) {
            $fields[] = "activity_level = ?";
            $update_params[] = $activity_level;
            $param_types .= "s";
        }


        if (empty($fields)) {
            die("Нет данных для обновления.");
        }

        $update_query .= implode(", ", $fields);
        $update_query .= " WHERE id = ?";
        $update_params[] = $user_id;
        $param_types .= "i";

        $stmt = $conn->prepare($update_query);
        if ($stmt === false) {
            die("Ошибка подготовки запроса: " . $conn->error);
        }

        $stmt->bind_param($param_types, ...$update_params);

        if ($stmt->execute()) {
            $success_message = "Данные успешно обновлены!";
        } else {
            $error_message = "Ошибка обновления данных: " . $stmt->error;
        }

        $stmt->close();
    }
}


$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$avatar = null;
$img_id = $user['img_id']; 
if ($img_id) {
    $stmt = $conn->prepare("SELECT image FROM images WHERE img_id = ?");
    $stmt->bind_param("i", $img_id);

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $avatar = "display_avatar.php?img_id=" . $img_id;  
    } else {
        $avatar = "display_avatar.php?img_id=1";
    }

    $stmt->close();
} else {
    $avatar = "display_avatar.php?img_id=1";
}

$stmt = $conn->prepare("SELECT COUNT(*) AS training_count FROM training_executions WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$training_count = $result->fetch_assoc()['training_count'];
$stmt->close();

$achievements = [];
$milestones = [1, 5, 10, 20, 50, 100, 200, 500]; 
foreach ($milestones as $milestone) {
    if ($training_count >= $milestone) {
        $achievements[] = "Вы достигли $milestone тренировок!";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #bdacbb;
            padding: 20px;
        }
        .profile-container {
            display: flex;
            gap: 20px;
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
        }
        .profile-item {
            margin-bottom: 15px;
        }
        .profile-item label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        .profile-form {
            flex-grow: 1;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background-color: rgba(139, 83, 179, 0.62);
            color: white;
            padding: 10px;
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
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .profile-avatar {
            text-align: center;
        }
        .profile-avatar img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 15px;
        }
        .avatar-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .avatar-gallery label {
            display: inline-block;
            cursor: pointer;
        }
        .avatar-gallery img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 2px solid transparent;
            transition: border-color 0.3s ease;
        }
        .avatar-gallery input[type="radio"] {
            display: none; 
        }
        .avatar-gallery input[type="radio"]:checked + img {
            border-color: #715ac8; 
        }
        .avatar-gallery label:hover img {
            border-color: #715ac8; 
        }
        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }
        .popup-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 500px;
            width: 80%;
        }
        .popup a {
            color: #4CAF50;
            text-decoration: none;
        }
        .popup a:hover {
            text-decoration: underline;
        }
        .achievements {
            margin-top: 20px;
        }
        .achievements h3 {
            margin-bottom: 10px;
        }
        .achievements ul {
            list-style: none;
            padding: 0;
        }
        .achievements li {
            background-color: #d4edda;
            color:rgb(43, 136, 65);
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
        }
        .profile-actions {
            margin: 20px auto; 
            text-align: center;
            background-color: #ffffff; 
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
        }
        .profile-actions form {
            margin-bottom: 20px; 
            
        }
        .profile-actions button {
            background-color: rgba(139, 83, 179, 0.62);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .profile-actions button:hover {
            background-color: #715ac8;
        }

    </style>
</head>
<body>
<div class="profile-container">

    <?php
        if (isset($_SESSION['error_message'])) {
            echo "<div class='message error'>" . htmlspecialchars($_SESSION['error_message']) . "</div>";
            unset($_SESSION['error_message']);
        }

        if (isset($_SESSION['success_message'])) {
            echo "<div class='message success'>" . htmlspecialchars($_SESSION['success_message']) . "</div>";
            unset($_SESSION['success_message']);
        }
    ?>

    <?php if (isset($success_message)): ?>
        <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="profile-avatar">
        <img src="<?php echo $avatar; ?>" alt="Аватар">
        <div class="achievements">
            <h3>Достижения</h3>
            <ul>
                <?php foreach ($achievements as $achievement): ?>
                    <li><?php echo htmlspecialchars($achievement); ?></li>
                <?php endforeach; ?>
                <?php if (empty($achievements)): ?>
                    <li>Пока нет достижений.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="profile-form">
        <h2>Профиль пользователя</h2>
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="profile-item">
                <label>Имя пользователя:</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
            </div>
            <div class="profile-item">
                <label>Возраст:</label>
                <input type="number" name="age" value="<?php echo htmlspecialchars($user['age'] ?? ''); ?>" required>
            </div>
            <div class="profile-item">
                <label>Вес (кг):</label>
                <input type="number" step="0.01" name="weight" value="<?php echo htmlspecialchars($user['weight'] ?? ''); ?>" required>
            </div>
            <div class="profile-item">
                <label>Рост (см):</label>
                <input type="number" name="height" value="<?php echo htmlspecialchars($user['height'] ?? ''); ?>" required>
            </div>
            <div class="profile-item">
                <label>Пол:</label>
                <select name="gender">
                    <option value="male" <?php echo ($user['gender'] === 'male') ? 'selected' : ''; ?>>Мужской</option>
                    <option value="female" <?php echo ($user['gender'] === 'female') ? 'selected' : ''; ?>>Женский</option>
                    <option value="other" <?php echo ($user['gender'] === 'other') ? 'selected' : ''; ?>>Другой</option>
                </select>
            </div>
            <div class="profile-item">
                <label>Уровень активности:</label>
                <select name="activity_level">
                    <option value="low" <?php echo ($user['activity_level'] === 'low') ? 'selected' : ''; ?>>Низкий</option>
                    <option value="medium" <?php echo ($user['activity_level'] === 'medium') ? 'selected' : ''; ?>>Средний</option>
                    <option value="high" <?php echo ($user['activity_level'] === 'high') ? 'selected' : ''; ?>>Высокий</option>
                </select>
            </div>
            <button type="submit">Сохранить изменения</button>
        </form>
    </div>
</div>

    
<div class="profile-actions">
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <label>Загрузить новый аватар:</label>
        <input type="file" name="avatar" accept="image/*" required>
        <button type="submit">Загрузить</button>
    </form>

    <form action="select_avatar.php" method="post">
        <label>Выбрать существующий аватар:</label>
        <div class="avatar-gallery">
            <?php
            $stmt = $conn->prepare("SELECT img_id, image FROM images WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<label>";
                    echo "<input type='radio' name='existing_avatar' value='" . $row['img_id'] . "'>";
                    echo "<img src='display_avatar.php?img_id=" . $row['img_id'] . "' alt='Аватар' class='avatar-img'>";
                    echo "</label>";
                }
            } else {
                echo "<p>Нет доступных аватаров</p>";
            }

            $stmt->close();
            ?>
        </div>
        <button type="submit">Выбрать аватар</button>
    </form>

    <button onclick="showHistory()">Посмотреть историю посещений</button>
</div>

    <!-- Pop-up окно для истории посещений -->
    <div id="historyPopup" class="popup">
        <div class="popup-content">
            <h3>История посещений</h3>
            <div id="historyContent"></div>
            <button onclick="closeHistory()">Закрыть</button>
        </div>
    </div>


    <script>
        function showHistory() {
            let historyPopup = document.getElementById("historyPopup");
            let historyContent = document.getElementById("historyContent");

            let pageHistory = <?php 
                $decryptedHistory = decrypt($_COOKIE["page_history"] ?? '');
                echo json_encode($decryptedHistory, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); 
            ?>;
            pageHistory = JSON.parse(pageHistory);
            console.log("Данные из PHP (pageHistory):", pageHistory);

            if (pageHistory) {
                try {
                    let historyHtml = '<ul>';
                    pageHistory.forEach(function(page) {
                        historyHtml += `<li><a href="${page}" target="_blank">${page}</a></li>`;
                    });
                    historyHtml += '</ul>';
                    historyContent.innerHTML = historyHtml;
                } catch (e) {
                    console.error("Ошибка при обработке истории: ", e);
                    historyContent.innerHTML = "<p>Не удалось загрузить историю посещений.</p>";
                }
            } else {
                historyContent.innerHTML = "<p>История посещений пуста.</p>";
            }

            historyPopup.style.display = "block";
    }


        function closeHistory() {
            let historyPopup = document.getElementById("historyPopup");
            historyPopup.style.display = "none";
        }

        function getCookie(name) {
            let nameEQ = name + "=";
            let ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) {
                    return decodeURIComponent(c.substring(nameEQ.length, c.length));
                }
            }
            return null;
        }


    </script>

</body>
</html>
