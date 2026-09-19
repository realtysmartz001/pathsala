<?php
session_start();
include '../includes/db.php'; // DB connection

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT id, password, is_approved FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $hashed_password, $is_approved);
    $stmt->fetch();

    if (password_verify($password, $hashed_password)) {
        if ($is_approved == 1) {
            $_SESSION['user_id'] = $id;
            header("Location: ../auth/login.html");
            exit;
        } elseif ($is_approved == 0) {
            echo "Your account has been rejected by admin.";
        } else {
            echo "Wait for admin approval.";
        }
    } else {
        echo "Invalid password.";
    }
} else {
    echo "No account found with that email.";
}
$stmt->close();
$conn->close();
?>
