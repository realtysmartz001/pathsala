<?php
include '../includes/db_connect.php';
session_start();

// Token from URL
if (!isset($_GET['token'])) {
    die("Invalid link.");
}

$token = $_GET['token'];

// Check token validity
$stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Reset link is invalid or expired.");
}

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Update password & remove token
    $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
    $update->bind_param("si", $new_password, $user['id']);
    if ($update->execute()) {
        echo "<p>Password reset successful. <a href='../auth/login.php'>Login here</a></p>";
    } else {
        echo "<p>Error updating password.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h2>Enter your new password</h2>
    <form method="POST">
        <input type="password" name="password" placeholder="New Password" required>
        <button type="submit">Update Password</button>
    </form>
</body>
</html>
