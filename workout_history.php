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
$records_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

$stmt = $conn->prepare("
    SELECT te.execution_date, tp.name, tp.duration, tp.calories_burned 
    FROM training_executions te
    JOIN training_programs tp ON te.training_id = tp.id
    WHERE te.user_id = ?
    ORDER BY te.execution_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $records_per_page, $offset);
$stmt->execute();
$history_result = $stmt->get_result();
$workout_history = $history_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total FROM training_executions 
    WHERE user_id = ?
");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_records / $records_per_page);

$chart_stmt = $conn->prepare("
    SELECT DATE(execution_date) AS execution_date, COUNT(*) AS training_count
    FROM training_executions
    WHERE user_id = ?
    GROUP BY DATE(execution_date)
    ORDER BY DATE(execution_date)
");
$chart_stmt->bind_param("i", $user_id);
$chart_stmt->execute();
$chart_result = $chart_stmt->get_result();
$chart_data = $chart_result->fetch_all(MYSQLI_ASSOC);
$chart_stmt->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История выполненных тренировок</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color:rgb(189, 172, 187);
            margin: 50px 0 0 0;
            padding: 0;
        }
        .container {
            width: 80%;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .pagination {
            text-align: center;
            margin-top: 20px;
        }
        .pagination a {
            margin: 0 5px;
            text-decoration: none;
            color:rgb(160, 57, 148);
        }
        .pagination a.active {
            font-weight: bold;
            color: #000;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>История выполненных тренировок</h1>

    <table>
        <tr>
            <th>Дата выполнения</th>
            <th>Название тренировки</th>
            <th>Длительность (мин)</th>
            <th>Сожженные калории</th>
        </tr>
        <?php if (count($workout_history) > 0): ?>
            <?php foreach ($workout_history as $workout): ?>
                <tr>
                    <td><?php echo htmlspecialchars($workout['execution_date']); ?></td>
                    <td><?php echo htmlspecialchars($workout['name']); ?></td>
                    <td><?php echo htmlspecialchars($workout['duration']); ?></td>
                    <td><?php echo htmlspecialchars($workout['calories_burned']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4">История тренировок пуста.</td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>

    <canvas id="workoutChart" width="800" height="400"></canvas>
</div>

<script>
    const chartLabels = <?php echo json_encode(array_column($chart_data, 'execution_date')); ?>;
    const chartData = <?php echo json_encode(array_column($chart_data, 'training_count')); ?>;

    const ctx = document.getElementById('workoutChart').getContext('2d');
    const workoutChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Количество тренировок',
                data: chartData,
                borderColor: 'rgb(185, 118, 178, 1)',
                backgroundColor: 'rgba(185, 118, 178, 0.2)',
                borderWidth: 2,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Дата'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Количество тренировок'
                    },
                    beginAtZero: true
                }
            }
        }
    });
</script>

</body>
</html>