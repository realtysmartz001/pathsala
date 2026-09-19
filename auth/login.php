<?php
// If "Remember Me" was checked, extend the session cookie's lifetime to
// 30 days before the session starts (must happen before session_start()).
// This only changes how long the login persists in the browser — no new
// database columns or tables needed.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remember'])) {
    session_set_cookie_params(30 * 24 * 60 * 60); // 30 days
}
session_start();
include '../includes/db_connect.php';

// 🔹 Admin credentials (fixed)
$admin_email = "admin@realtysmartzpathshala.in";  
$admin_password = "realtysmartz@admin";           

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    // 🔹 Check for admin login
    if ($email === $admin_email && $password === $admin_password) {
        $_SESSION['admin_email'] = $admin_email;
        $_SESSION['admin_name']  = "Admin";
        $_SESSION['role']        = "admin";   // ✅ role defined
        header("Location: ../admin/after_admin_login.php");
        exit;
    }

    // 🔹 Normal user login from database
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt  = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) {
            if ($user['is_approved'] != 1) {
                echo "⚠️ Your registration is pending admin approval.";
                exit;
            }

            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role']       = "user";   // ✅ role defined

            header("Location: ../index.php?msg=approved");
            exit;
        } else {
            echo "❌ Incorrect password.";
        }
    } else {
        echo "❌ No account found with this email.";
    }

    $stmt->close();
}
$conn->close();
?>
