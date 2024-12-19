<?php
require 'db.php';
require_once 'auth.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$is_admin = false;
$is_trainer = false;

if ($user_id) {
    $stmt = $conn->prepare("
        SELECT r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $is_admin = ($user['role_name'] === 'admin');
    $is_trainer = ($user['role_name'] === 'trainer');
}
?>

<nav>
    <ul>
        <li><a href="main.php">Главная</a></li>

        <?php if ($user_id): ?>
            <?php if ($is_admin): ?>
                <li><a href="delete_user.php">Удалить пользователя</a></li>
                <li><a href="pending.php">Рассмотрение тренировок</a></li>
                <li><a href="admin_rating_weights.php">Веса тренера</a></li>
                <li><a href="training_rating_weights.php">Веса </a></li>
                <li><a href="logout.php">Выйти</a></li>
                <!-- <li><a href="backup.php">Бэкап</a></li> -->
               
            <?php elseif ($is_trainer): ?>
               
                <li><a href=" training_requests.php">Запросы</a></li>
                <li><a href="trainer_programs.php">Создать программу</a></li>
                <li><a href="training_overview.php">Статистика</a></li>
                <li><a href="logout.php">Выйти</a></li>
            <?php else: ?>
                <li><a href="recommendation.php">Для вас</a></li>
                <li><a href="train_choose.php">Доступные тренировки</a></li>
                <li><a href="workout_history.php">История тренировок</a></li>
                <li><a href="my_training_requests.php">Запросы на тренировки</a></li>
                <li><a href="profile.php">Профиль</a></li>
                <li><a href="logout.php">Выйти</a></li>
            <?php endif; ?>
        <?php else: ?>

            <li><a href="login.php">Войти</a></li>
        <?php endif; ?>
    </ul>
</nav>

<style>
    nav {
        background-color: rgba(139, 83, 179, 0.62);
        height: 40px;
        width: 100%;
        position: fixed;
        top: 0;
        left: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    body {
        margin: 50px 0 0 0;
        padding-top: 50px;
    }

    nav ul {
        list-style-type: none;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }

    nav ul li {
        margin-right: 20px;
    }

    nav ul li a {
        color: white;
        text-decoration: none;
        font-size: 16px;
        padding: 8px 16px;
        display: block;
        transition: background-color 0.3s;
    }

    nav ul li a:hover {
        background-color: #715ac8;
        border-radius: 4px;
    }

    @media (max-width: 768px) {
        nav ul {
            flex-direction: column;
        }

        nav ul li {
            margin-right: 0;
            margin-bottom: 10px;
        }
    }
</style>
