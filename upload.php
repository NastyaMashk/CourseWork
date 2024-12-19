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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $allowed_types = ['image/jpeg', 'image/png']; 
    $allowed_extensions = ['jpg', 'jpeg', 'png']; 
    $max_file_size = 5 * 1024 * 1024; 

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_extension, $allowed_extensions)) {
        $error_message = "Недопустимый формат файла. Разрешены только JPG, JPEG и PNG.";
    } elseif (!in_array($file['type'], $allowed_types)) {
        $error_message = "Неверный MIME-тип файла. Убедитесь, что вы загружаете корректное изображение.";
    } elseif ($file['size'] > $max_file_size) {
        $error_message = "Файл слишком большой. Максимальный размер файла: 5 МБ.";
    } else {
        $image_data = file_get_contents($file['tmp_name']);

        if (@getimagesizefromstring($image_data) === false) {
            $error_message = "Файл поврежден или не является допустимым изображением.";
        } else {
            $stmt = $conn->prepare("INSERT INTO images (image, user_id) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("si", $image_data, $user_id);
                $stmt->execute();

                $img_id = $stmt->insert_id;

                $update_stmt = $conn->prepare("UPDATE users SET img_id = ? WHERE id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("ii", $img_id, $user_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    $success_message = "Аватар успешно загружен!";
                } else {
                    $error_message = "Ошибка при обновлении профиля: " . $conn->error;
                }

                $stmt->close();
            } else {
                $error_message = "Ошибка подготовки запроса: " . $conn->error;
            }
        }
    }
} else {
    $error_message = "Файл не выбран.";
}

if (isset($error_message)) {
    $_SESSION['error_message'] = $error_message;
}
if (isset($success_message)) {
    $_SESSION['success_message'] = $success_message;
}

header("Location: profile.php");
exit();
