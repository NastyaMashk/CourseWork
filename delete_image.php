<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Нет доступа']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$recipe_id = $data['recipe_id'];

if (empty($recipe_id)) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID рецепта']);
    exit();
}

$stmt = $conn->prepare("SELECT image_path FROM recipes WHERE id = ?");
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$stmt->bind_result($image_path);
$stmt->fetch();
$stmt->close();

if ($image_path && file_exists($image_path)) {
    unlink($image_path);

    $stmt = $conn->prepare("UPDATE recipes SET image_path = NULL WHERE id = ?");
    $stmt->bind_param("i", $recipe_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Изображение успешно удалено']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при удалении записи из базы данных']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Изображение не найдено']);
}
?>
