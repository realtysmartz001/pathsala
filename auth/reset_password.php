<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/db_connect.php';

$message  = "";
$showForm = false;
$token    = "";

// GET - Token verify karo
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['token'])) {

    $token = trim(urldecode($_GET['token']));

    $stmt = $conn->prepare("SELECT id, email, token_expiry FROM users WHERE reset_token = ?");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (strtotime($row['token_expiry']) > time()) {
            $showForm = true;
        } else {
            $message = "<p class='error'>❌ Token expired. Please request a new reset link.</p>";
        }
    } else {
        $message = "<p class='error'>❌ Invalid token. Please check your email link.</p>";
    }

    $stmt->close();
}

// POST - Password update karo
elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $token            = trim(urldecode($_POST['token']));
    $new_password     = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($new_password) || empty($confirm_password)) {
        $message  = "<p class='error'>❌ Please fill both fields.</p>";
        $showForm = true;

    } elseif ($new_password !== $confirm_password) {
        $message  = "<p class='error'>❌ Passwords do not match.</p>";
        $showForm = true;

    } else {

        $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND token_expiry > NOW()");

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row     = $result->fetch_assoc();
            $user_id = $row['id'];
            $stmt->close();

            // ✅ Plain text password save karo
            $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE id = ?");

            if (!$update) {
                die("Update prepare failed: " . $conn->error);
            }

            $update->bind_param("si", $new_password, $user_id);

            if ($update->execute()) {
                $message = "<p class='success'>✅ Password reset successful! Redirecting to login...</p>
                <script>
                    setTimeout(function(){ window.location.href='../auth/login.php'; }, 3000);
                </script>";
                $showForm = false;
            } else {
                $message  = "<p class='error'>❌ Failed to update. Error: " . $update->error . "</p>";
                $showForm = true;
            }

            $update->close();

        } else {
            $message = "<p class='error'>❌ Invalid or expired token. Please request new link.</p>";
            $stmt->close();
        }
    }
}

if (isset($conn) && $conn) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: url('background_image_forgot_password.jpg')
                        no-repeat center center/cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 0;
        }

        .container {
            background: white;
            padding: 35px 30px;
            border-radius: 15px;
            box-shadow: 0 6px 30px rgba(0,0,0,0.2);
            width: 380px;
            max-width: 92%;
            text-align: center;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.8s ease-in-out;
        }

        h2 {
            color: #4facfe;
            margin-bottom: 20px;
            font-size: 24px;
        }

        label {
            display: block;
            text-align: left;
            font-size: 13px;
            margin: 10px 0 4px;
            color: #555;
            font-weight: 600;
        }

        input[type="password"] {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border 0.3s;
        }

        input:focus { border-color: #4facfe; }

        button {
            width: 100%;
            padding: 12px;
            background: #4facfe;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 18px;
            transition: background 0.3s;
        }

        button:hover { background: #007bff; }

        .success {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .error {
            color: #721c24;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .container { padding: 20px; }
            h2 { font-size: 20px; }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>🔑 Reset Password</h2>

    <?php if (!empty($message)) echo $message; ?>

    <?php if ($showForm): ?>
    <form method="POST" action="">
        <input type="hidden" name="token"
               value="<?php echo htmlspecialchars($token); ?>">

        <label>New Password:</label>
        <input type="password" name="new_password"
               placeholder="Enter new password" required>

        <label>Confirm Password:</label>
        <input type="password" name="confirm_password"
               placeholder="Confirm new password" required>

        <button type="submit">🔄 Reset Password</button>
    </form>
    <?php endif; ?>

</div>
</body>
</html>