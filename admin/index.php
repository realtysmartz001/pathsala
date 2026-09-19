<?php
session_start();

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: after_admin_login.php');
    exit();
}

header('Location: ../auth/login.html');
exit();
?>
