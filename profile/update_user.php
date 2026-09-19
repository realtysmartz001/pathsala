<?php
include '../includes/db_user.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id           = (int)$_POST['id'];
    $name         = trim($_POST['name']);
    $email        = trim($_POST['email']);
    $password     = trim($_POST['password']);
    $assigned_set = trim($_POST['assigned_set']);
    $is_verified  = (int)$_POST['is_verified'];
    $is_approved  = (int)$_POST['is_approved'];

    $stmt = $conn->prepare("UPDATE users 
                            SET name=?, email=?, password=?, 
                                assigned_set=?, is_verified=?, is_approved=? 
                            WHERE id=?");
    $stmt->bind_param("sssssii",
        $name,
        $email,
        $password,
        $assigned_set,
        $is_verified,
        $is_approved,
        $id
    );

    if ($stmt->execute()) {
        header("Location: ../admin/admin_user.php?msg=updated");
        exit();
    } else {
        echo "❌ Update Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
