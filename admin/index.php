<?php
session_start();
// If already logged in, go to panel
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: panel.php');
    exit;
}
// Otherwise go to login
header('Location: login.php');
exit;
