<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['training_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$training_id = $_POST['training_id'];

$stmt = $conn->prepare("SELECT * FROM training_likes WHERE user_id = ? AND training_id = ?");
$stmt->bind_param("ii", $user_id, $training_id);
$stmt->execute();
$like = $stmt->get_result()->fetch_assoc();

if ($like) {
   
    $stmt = $conn->prepare("DELETE FROM training_likes WHERE user_id = ? AND training_id = ?");
    $stmt->bind_param("ii", $user_id, $training_id);
    $stmt->execute();

   
    $_SESSION['message'] = "Лайк удален.";
} else {
   
    $stmt = $conn->prepare("INSERT INTO training_likes (user_id, training_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $training_id);
    $stmt->execute();

    $_SESSION['message'] = "Лайк добавлен.";
}

header("Location: training_details.php?id=" . $training_id);
exit();
?>
