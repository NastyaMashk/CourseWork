<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
include 'navbar.php';
//require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
 
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];
        $userId = $user['id'];
        $randomBytes = bin2hex(random_bytes(16)); 
        $token_data = base64_encode($userId . ':' . $randomBytes); 
        $signature = hash_hmac('sha256', $token_data, 'secret_key'); 

        $token_with_signature = $token_data . ':' . $signature;

        setcookie('user_token', $token_with_signature, time() + (30 * 24 * 60 * 60), "/", "", true, true); 
    
        header("Location: main.php");
        exit();
    } else {
        echo "Неверный email или пароль.";
    }
    

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма входа</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #bdacbb;
        }
        .login-form {
            max-width: 300px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #e1e1e1;
        }
        .login-form input {
            width: 100%;
            padding: 10px;
            margin: 5px 0 15px;
            border: 1px solid #d8d7d7;;
            border-radius: 5px;
        }
        .login-form button {
            width: 100%;
            padding: 10px;
            background-color: rgba(139, 83, 179, 0.62);
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .login-form button:hover {
            background-color: #715ac8;
        }
        .registr-btn{
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="login-form">
    <h2>Вход</h2>
    <form method="POST" action="">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="Введите email" required>

        <label for="password">Пароль</label>
        <input type="password" name="password" id="password" placeholder="Введите пароль" required>
        <button type="submit">Войти</button>
        <button onclick="window.location.href='registr.php';" class="registr-btn" > Зарегистрируйтесь</button>
    </form>
</div>

</body>
</html>
