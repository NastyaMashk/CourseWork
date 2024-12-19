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
    SELECT tr.id, tr.user_id, tr.training_id, tr.request_date, tr.status, 
           u.username AS trainee_name, t.name AS training_name
    FROM training_requests tr
    JOIN users u ON tr.user_id = u.id
    JOIN training_programs t ON tr.training_id = t.id
    WHERE t.created_by = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Запросы на выполнение тренировок</title>
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

        .container {
            width: 80%;
            max-width: 1000px;
            padding: 20px;
            background-color: #ffffff; 
            border-radius: 10px; 
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); 
            margin-top: 50px;
        }

        h1 {
            text-align: center;
            margin: 0;
            padding-bottom: 20px;
            color: #333;
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

        button {
            padding: 5px 10px;
            border: none;
            background-color: #5c80d1;
            color: white;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #4a6fb1;
        }
    </style>
</head>
<body>
    <div class="container"> 
        <h1>Запросы на выполнение тренировок</h1>
        <table>
            <thead>
                <tr>
                    <th>Пользователь</th>
                    <th>Тренировка</th>
                    <th>Дата запроса</th>
                    <th>Статус</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['trainee_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['training_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                        <td>
                            <?php
                            if ($row['status'] === 'pending') {
                                echo '<span class="status-pending">Ожидает</span>';
                            } elseif ($row['status'] === 'approved') {
                                echo '<span class="status-approved">Одобрено</span>';
                            } elseif ($row['status'] === 'rejected') {
                                echo '<span class="status-rejected">Отклонено</span>';
                            } elseif ($row['status'] === 'in_process') {
                                echo '<span class="status-pending">В процессе</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <form method="POST" action="process_request.php">
                                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <button type="submit" name="action" value="approve">Одобрить</button>
                                    <button type="submit" name="action" value="reject">Отклонить</button>
                                <?php elseif ($row['status'] === 'approved' || $row['status'] === 'rejected'): ?>
                                    <button type="submit" name="action" value="in_process">Редактировать</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>