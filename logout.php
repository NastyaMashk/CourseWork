<?php

session_start();
session_unset();
session_destroy();

setcookie('user_token', '', time() - 3600, '/', '', true, true);
setcookie('page_history', '', time() - 3600, '/', '', true, true);

header_remove();
header("Location: login.php?logout_success=1", true, 303); 
exit();
?>

