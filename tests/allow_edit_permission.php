<?php
session_start();
include '../includes/db_connect.php';

if ($_SESSION['role'] !== 'admin') { die("Unauthorized"); }

$user_id = (int)($_GET['user_id'] ?? 0);
if (!$user_id) { die("Invalid user."); }

mysqli_query($conn, "UPDATE users SET allow_edit = 1 WHERE id = $user_id");

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
