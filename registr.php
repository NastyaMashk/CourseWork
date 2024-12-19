<?php

include 'db.php';
include 'navbar.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role_id = 1;
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        echo '<script>alert("Ошибка: Этот адрес электронной почты уже зарегистрирован.");</script>';
    } else {
        $stmt = $conn->prepare("INSERT INTO users (email, password_hash, role_id) VALUES (?, ?, ?)");
        if ($stmt === false) {
            die("Ошибка подготовки запроса: " . $conn->error);
        }
        $stmt->bind_param("ssi", $email, $password, $role_id);

        if ($stmt->execute()) {
            echo '<script>
                alert("Регистрация прошла успешно!");
                setTimeout(function() {
                    window.location.href = "login.php";
                }, 1000);
            </script>';
        } else {
            echo '<script>alert("Ошибка при регистрации: ' . $stmt->error . '");</script>';
        }
        $stmt->close();
    }


}

$conn->close();
?>

<div class="form-container">
    <div class="form-box">
        <h2>Регистрация</h2>
        <form method="POST" action="">
            <label for="email">Email</label>
                <input type="email" name="email" placeholder="Email" required>
            <label for="password">Пароль</label>
                <input type="password" name="password" placeholder="Пароль" required>
            </label><br>
            <button type="submit">Зарегистрироваться</button>
        </form>
    </div>
</div>

<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #bdacbb;
    }
    .form-container button{
        width: 100%;
        padding: 10px;
        background-color: rgba(139, 83, 179, 0.62);
        border: none;
        color: #ffffff;
        border-radius: 5px;
        cursor: pointer;
    }
    .form-container {
        max-width: 300px;
        margin: 50px auto;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 10px;
        background-color: #e1e1e1;
    }

    .form-container input {
        width: 100%;
        padding: 10px;
        margin: 5px 0 15px;
        border: 1px solid #d8d7d7;
        border-radius: 5px;
    }
    
    .form-container button:hover {
        background-color: #715ac8;
    }
</style>
