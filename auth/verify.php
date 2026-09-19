<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/db_connect.php';

$message   = '';
$type      = 'error';
$showLogin = false;
$code      = '';

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE verification_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if ($user['is_verified'] == 1) {

            $is_approved = (int)$user['is_approved'];

            if ($is_approved === 1) {
                $message   = "✅ Email verified & Admin approved! You can now login.";
                $type      = 'success';
                $showLogin = true;

            } elseif ($is_approved === 2) {
                $message   = "❌ Your account has been rejected by admin. Please contact support.";
                $type      = 'error';
                $showLogin = false;

            } else {
                // ⏳ PENDING — Polling start hogi
                $message   = "⏳ Email verified! Waiting for admin approval...";
                $type      = 'warning';
                $showLogin = false;
            }

        } else {
            $update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE verification_code = ?");
            $update->bind_param("s", $code);

            if ($update->execute()) {

                $checkInsert = $conn->prepare("SELECT id FROM admin_approve_reject WHERE email = ?");
                $checkInsert->bind_param("s", $user['email']);
                $checkInsert->execute();
                $checkInsert->store_result();

                if ($checkInsert->num_rows == 0) {
                    $insert = $conn->prepare("INSERT INTO admin_approve_reject (name, email, is_verified, status) VALUES (?, ?, 1, 'pending')");
                    $insert->bind_param("ss", $user['name'], $user['email']);
                    $insert->execute();
                    $insert->close();
                }
                $checkInsert->close();

                $message   = "☑️ Email verified! Waiting for admin approval...";
                $type      = 'success';
                $showLogin = false;

            } else {
                $message = "❌ Error updating verification.";
                $type    = 'error';
            }
            $update->close();
        }

    } else {
        $message = "❌ Invalid verification code.";
        $type    = 'error';
    }

    $stmt->close();

} else {
    $message = "❌ No verification code provided.";
    $type    = 'error';
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial;
            padding: 50px;
            background-color: #f2f2f2;
            text-align: center;
        }
        .box {
            background: white;
            display: inline-block;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            min-width: 320px;
        }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error   { color: red;   font-weight: bold; }

        .login-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #28a745;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
        }
        .login-btn:hover { background: #218838; }

        /* ── Spinner ── */
        .spinner-wrap {
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #e65100;
            font-size: 13px;
            font-weight: 600;
        }
        .spinner {
            width: 20px; height: 20px;
            border: 3px solid #ffe0b2;
            border-top-color: #e65100;
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ── Success Animation ── */
        .approved-wrap {
            display: none;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .approved-wrap .tick {
            width: 56px; height: 56px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            animation: popIn 0.4s ease;
        }
        @keyframes popIn {
            0%   { transform: scale(0.5); opacity: 0; }
            80%  { transform: scale(1.1); }
            100% { transform: scale(1);   opacity: 1; }
        }
    </style>
</head>
<body>
<div class="box">
    <h2>Email Verification</h2>

    <div id="mainMsg">
        <p class="<?php echo $type; ?>" id="statusText">
            <?php echo $message; ?>
        </p>
    </div>

    <?php if ($showLogin): ?>
        <!-- Already approved on load -->
        <a href="../auth/login.html" class="login-btn">🔐 Go to Login</a>

    <?php else: ?>
        <!-- ⏳ Pending state — polling UI -->
        <?php if ($type === 'warning' || $type === 'success'): ?>

        <!-- Spinner — visible while polling -->
        <div class="spinner-wrap" id="spinnerWrap">
            <div class="spinner"></div>
            Checking approval status...
        </div>

        <!-- Success UI — hidden until approved -->
        <div class="approved-wrap" id="approvedWrap">
            <div class="tick">✅</div>
            <p class="success" style="margin:0;">
                Admin approved! Redirecting to login...
            </p>
            <a href="../auth/login.php" class="login-btn">🔐 Go to Login</a>
        </div>

        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (!$showLogin && ($type === 'warning' || $type === 'success')): ?>
<script>
    // ── Polling every 5 seconds ──
    const verifyCode  = <?php echo json_encode($code); ?>;
    const spinnerWrap = document.getElementById('spinnerWrap');
    const approvedWrap= document.getElementById('approvedWrap');
    const statusText  = document.getElementById('statusText');

    const interval = setInterval(() => {
        fetch('../tests/check_status.php?code=' + encodeURIComponent(verifyCode))
            .then(res => res.json())
            .then(data => {

                if (data.is_approved === 1) {
                    // ✅ Admin ne approve kiya!
                    clearInterval(interval); // Polling band karo

                    // UI Update
                    statusText.className   = 'success';
                    statusText.textContent = '✅ Email verified & Admin approved!';
                    spinnerWrap.style.display  = 'none';
                    approvedWrap.style.display = 'flex';

                    // Auto redirect after 3 seconds
                    setTimeout(() => {
                        window.location.href = '../auth/login.html';
                    }, 3000);

                } else if (data.is_approved === 2) {
                    // ❌ Admin ne reject kiya
                    clearInterval(interval);

                    statusText.className   = 'error';
                    statusText.textContent = '❌ Your account has been rejected by admin.';
                    spinnerWrap.style.display = 'none';
                }
            })
            .catch(err => console.error('Polling error:', err));

    }, 5000); // ← Har 5 second mein check karega
</script>
<?php endif; ?>

</body>
</html>
