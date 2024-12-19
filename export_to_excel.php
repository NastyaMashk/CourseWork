<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=training_programs.xls");
header("Pragma: no-cache");
header("Expires: 0");
echo "\xEF\xBB\xBF";
echo "Название\tДлительность (мин)\tКалории (ккал)\tВыполнено раз\tВыполнили\tСтатус\tЛайки\n";

$stmt = $conn->prepare("
    SELECT 
        t.name,
        t.duration,
        t.calories_burned,
        COUNT(te.id) AS execution_count,
        GROUP_CONCAT(DISTINCT CONCAT(u.username, ' (', 
            (SELECT COUNT(te_inner.id) FROM training_executions te_inner WHERE te_inner.user_id = te.user_id AND te_inner.training_id = t.id), 
            ' раз)') SEPARATOR ', ') AS executed_by,
        t.status,
        COUNT(DISTINCT tl.id) AS like_count
    FROM training_programs t
    LEFT JOIN training_executions te ON t.id = te.training_id
    LEFT JOIN users u ON te.user_id = u.id
    LEFT JOIN training_likes tl ON t.id = tl.training_id
    WHERE t.created_by = ?
    GROUP BY t.id
    ORDER BY t.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo $row['name'] . "\t" .
         $row['duration'] . "\t" .
         $row['calories_burned'] . "\t" .
         $row['execution_count'] . "\t" .
         ($row['executed_by'] ?: 'Нет выполнений') . "\t" .
         $row['status'] . "\t" .
         $row['like_count'] . "\n"; 
}

$stmt->close();
$conn->close();
?>