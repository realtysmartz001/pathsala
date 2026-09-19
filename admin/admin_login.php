<?php
// Keep the old admin URL compatible with the current authentication flow.
header('Location: ../auth/login.html');
exit;
/*
ob_start();
session_start();
include '../includes/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admin WHERE name = ? AND password = ?");
    $stmt->bind_param("ss", $name, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $_SESSION['admin_name'] = $name;
        header("Location: ../admin/after_admin_login.php");
        exit();
    } else {
        $error = "Invalid name or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        body { background: #f2f2f2; font-family: Arial; }
        .login-box {
            width: 300px;
            margin: 100px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px #aaa;
        }
        .login-box h2 {
            text-align: center;
        }
        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
        }
        .login-box input[type="submit"] {
            width: 100%;
            background: #333;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
        }
        .error { color: red; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Login</h2>
        <form method="post">
            <input type="text" name="name" placeholder="Enter Admin Name" required>
            <input type="password" name="password" placeholder="Enter Password" required>
            <input type="submit" value="Login">
        </form>
        <div class="error"><?php echo $error; ?></div>
    </div>
</body>
</html>

ob_end_flush();
*/
?>
