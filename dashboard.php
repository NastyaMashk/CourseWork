<?php
include 'db.php';
include 'navbar.php';
require_once 'auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role_id'] != 1) {
    echo "У вас нет доступа к этой странице.";
    exit();
}

echo "Добро пожаловать, администратор!";
?>
