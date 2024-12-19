<?php
include 'shifr.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_token'])) {

    $token = $_COOKIE['user_token'];
    
    list($encrypted_token_data, $signature) = explode(':', $token);
    $expected_signature = hash_hmac('sha256', $encrypted_token_data, 'secret_key');

    if (hash_equals($expected_signature, $signature)) {
        $token_data = decrypt($encrypted_token_data);  
       @list($userId, $randomBytes) = explode(':', $token_data);
        $_SESSION['user_id'] = $userId;
        $new_randomBytes = bin2hex(random_bytes(16));
        $new_token_data = $userId . ':' . $new_randomBytes;
        $encrypted_new_token_data = encrypt($new_token_data); 
        $new_signature = hash_hmac('sha256', $encrypted_new_token_data, 'secret_key');
        $new_token_with_signature = $encrypted_new_token_data . ':' . $new_signature;
        setcookie('user_token', $new_token_with_signature, time() + (30 * 24 * 60 * 60), "/", "", true, true);
    } else {
        setcookie('user_token', '', time() - 3600, '/', '', true, true);
    }
}

if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['SCRIPT_NAME'] !== '/coursach/login.php') {
        header("Location: login.php");
        exit();
    }
}
?>
