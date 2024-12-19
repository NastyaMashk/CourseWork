<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

$response = ['success' => false, 'message' => '', 'new_trainer_html' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png'];

        if (in_array($file['type'], $allowed_types)) {
            $upload_dir = 'uploads/trainers/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = uniqid() . "_" . basename($file['name']);
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $name = $_POST['name'];
                $bio = $_POST['bio'];
                $experience = $_POST['experience'];

                $stmt = $conn->prepare("INSERT INTO trainers (name, bio, experience, photo_path) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssis", $name, $bio, $experience, $file_path);
                $stmt->execute();

                $response['success'] = true;
                $response['message'] = 'Тренер добавлен!';
            } else {
                $response['message'] = 'Ошибка загрузки файла.';
            }
        } else {
            $response['message'] = 'Неверный формат изображения.';
        }
    } else {
        $response['message'] = 'Ошибка при загрузке изображения.';
    }
} else {
    $response['message'] = 'Неверный запрос.';
}

echo json_encode($response);
?>
