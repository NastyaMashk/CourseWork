<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "coursach_db";

if (!function_exists('handleDbError')) {
    function handleDbError($message) {
        error_log($message, 3, 'db_errors.log'); 
        die("Произошла ошибка при подключении к базе данных. Попробуйте позже.");
    }
}

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8");

} catch (mysqli_sql_exception $e) {
    handleDbError($e->getMessage());
}

?>