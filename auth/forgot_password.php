<?php
include '../includes/db_connect.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Step 1: Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 1) {
        // Step 2: Generate secure token
        $token = bin2hex(random_bytes(16)); 
        $token_for_url = urlencode($token); // Safer for URL

        // Step 3: Save token & expiry
        $update = $conn->prepare("UPDATE users SET reset_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
        $update->bind_param("ss", $token, $email);
        $update->execute();

        // ✅ Step 4: Use HTTPS & correct domain
        $reset_link = "https://realtysmartzpathshala.in/reset_password.php?token=" . $token_for_url;

        // Step 5: HTML Email content
        $subject = "Reset your password - RealtySmartz Pathshala";
        $htmlMessage = "
        <html>
        <body>
            <p>Hi,</p>
           <!--  <p style='text-align:center; margin:20px 0;'>
                <a href='{$reset_link}' target='_blank' style='display:inline-block;
                    padding:10px 18px; background:#007bff; color:#fff; text-decoration:none; border-radius:6px;'>Reset Password</a>
            </p>
            <p>If the button doesn't work, copy & paste this URL into your browser:</p>--> 
            <p><a href='{$reset_link}' target='_blank'>{$reset_link}</a></p>
            <br>
            <p>— RealtySmartz Pathshala Team</p>
        </body>
        </html>
        ";

        // ✅ Step 6: Proper headers
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: RealtySmartz Pathshala <admin@realtysmartzpathshala.in>\r\n";
        $headers .= "Reply-To: admin@realtysmartzpathshala.in\r\n";

        // ✅ Step 7: Send email
        if (mail($email, $subject, $htmlMessage, $headers)) {
            $message = "<p class='success'>✅ Reset link sent to your email. Please check your Gmail. </p>";
        } else {
            $message = "<p class='error'>❌ Email sending failed. Please contact support.</p>";
        }

        $update->close();
    } else {
        $message = "<p class='warning'>⚠️ No user found with this email.</p>";
    }

    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html>

<head>
  <title>Forgot Password</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
<style>
  body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: url('background_image_forgot_password.jpg') no-repeat center center/cover;
    margin: 0;
    padding: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
  }

  body::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    z-index: 0;
  }

  .container {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0px 6px 30px rgba(0, 0, 0, 0.15);
    width: 350px;
    max-width: 90%;
    text-align: center;
    animation: fadeIn 0.8s ease-in-out;
    z-index: 1;
    position: relative;
  }

  .container h2 {
    margin-bottom: 20px;
    color: #4facfe;
    font-size: 24px;
  }

  /* Message styles */
  .success {
    color: #155724;
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    font-weight: 600;
  }

  .error {
    color: #721c24;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    font-weight: 600;
  }

  .warning {
    color: #856404;
    background-color: #fff3cd;
    border: 1px solid #ffeeba;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    font-weight: 600;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @media screen and (max-width: 480px) {
    .container {
      padding: 20px;
    }

    .container h2 {
      font-size: 20px;
    }
  }
</style>
</head>

<body>
  <div class="container">

    <h2>Forgot Password</h2>
    <!-- Show message here -->
    <?php if (!empty($message)) { echo $message; } ?>

  </div>
</body>

</html>