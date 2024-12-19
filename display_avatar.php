<?php
require 'db.php';

if (isset($_GET['img_id'])) {
    $img_id = intval($_GET['img_id']);

    $stmt = $conn->prepare("SELECT image FROM images WHERE img_id = ?");
    $stmt->bind_param("i", $img_id);
    $stmt->execute();
    $stmt->bind_result($image);
    $stmt->fetch();
    $stmt->close();
    $conn->close();

    if ($image) {
        header("Content-Type: image/jpeg"); 
        echo $image;
    } else {
        http_response_code(404);
        echo "Image not found.";
    }
} else {
    http_response_code(400);
    echo "Invalid request.";
}
?>
