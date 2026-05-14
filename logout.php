<?php
session_start();
session_unset();
session_destroy();

// Clear cookies
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, '/');
    setcookie('user_type', '', time() - 3600, '/');
}

header('Location: login.php');
exit();
?>