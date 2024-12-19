<?php
include 'shifr.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = $_SERVER['REQUEST_URI'];

if (isset($_COOKIE['page_history'])) {

    $historyJson = decrypt($_COOKIE['page_history']);
    $history = json_decode($historyJson, true);
    
    if (!is_array($history)) {
        $history = [];  
    }
    if (($key = array_search($currentPage, $history)) !== false) {
        unset($history[$key]);
    }
    array_unshift($history, $currentPage);
    $history = array_slice($history, 0, 10);
} else {
    $history = [$currentPage];
}
if ($_SERVER['SCRIPT_NAME'] === '/login.php' || $_SERVER['SCRIPT_NAME'] === '/logout.php') {
    return;
}

$historyJson = json_encode($history);
$historyJson = encrypt($historyJson);

setcookie('page_history', $historyJson, time() + (7 * 24 * 60 * 60), "/", "", false, false);

?>
