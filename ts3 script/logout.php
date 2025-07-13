<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'logout', 'Kullanıcı çıkış yaptı');
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>