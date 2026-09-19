<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../admin/login.php");
    exit();
}
include '../includes/db_connect.php';

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("UPDATE contact_form SET status = 'read' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}
header("Location: admin_contact.php" . (isset($_GET['search']) ? "?search=" . urlencode($_GET['search']) : ""));
exit();