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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['existing_avatar'])) {
    $selected_img_id = intval($_POST['existing_avatar']);

    $stmt = $conn->prepare("UPDATE users SET img_id = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $selected_img_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $success_message = "Аватар успешно обновлен!";
    } else {
        $error_message = "Ошибка при обновлении аватара: " . $conn->error;
    }

    $conn->close();
}

header("Location: profile.php");
exit();
?>
