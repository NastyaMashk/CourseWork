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


$stmt = $conn->prepare("
    SELECT 
        tr.id AS request_id,
        t.name AS training_name,
        tr.request_date,
        tr.status, 
        tr.training_id AS train_id
    FROM training_requests tr
    JOIN training_programs t ON tr.training_id = t.id
    WHERE tr.user_id = ?
    ORDER BY tr.request_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои запросы на тренировки</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #bdacbb;
            margin: 50px 0 0 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            width: 80%;
            max-width: 800px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        h1 {
            text-align: center;
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

        .status-pending {
            color: orange;
        }

        .status-approved {
            color: green;
        }

        .status-rejected {
            color: red;
        }
        a {
            color: #6c5ce7;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Мои запросы на тренировки</h1>
        <?php if (empty($requests)): ?>
            <p>У вас пока нет запросов на тренировки.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Тренировка</th>
                        <th>Дата запроса</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td>
                                <a href="training_details.php?id=<?php echo $request['train_id']; ?>">
                                    <?php echo htmlspecialchars($request['training_name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($request['request_date']))); ?></td>
                            <td class="status-<?php echo htmlspecialchars($request['status']); ?>">
                                <?php 
                                switch ($request['status']) {
                                    case 'pending':
                                        echo 'В ожидании';
                                        break;
                                    case 'approved':
                                        echo 'Одобрено';
                                        break;
                                    case 'rejected':
                                        echo 'Отклонено';
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
