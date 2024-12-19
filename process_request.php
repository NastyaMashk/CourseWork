<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = (int) $_POST['request_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $new_status = 'approved';
    } elseif ($action === 'reject') {
        $new_status = 'rejected';
    } elseif ($action === 'in_process') { 
        $new_status = 'pending'; 
    } else {
        $_SESSION['message'] = "Неверное действие.";
        header("Location: training_requests.php");
        exit();
    }

    $stmt = $conn->prepare("UPDATE training_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $request_id);
    $stmt->execute();

    if ($new_status === 'approved') {
        $stmt = $conn->prepare("
            SELECT tr.user_id, tr.training_id, t.calories_burned 
            FROM training_requests tr
            JOIN training_programs t ON tr.training_id = t.id
            WHERE tr.id = ?
        ");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();

        $user_id = $request['user_id'];
        $training_id = $request['training_id'];

        $stmt = $conn->prepare("INSERT INTO training_executions (execution_date, training_id, user_id) VALUES (NOW(), ?, ?)");
        $stmt->bind_param("ii", $training_id, $user_id);
        $stmt->execute();
    }

    $_SESSION['message'] = "Запрос успешно обработан.";
    header("Location: training_requests.php");
    exit();
}


?>
