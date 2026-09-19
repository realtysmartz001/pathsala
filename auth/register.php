<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start(); // ✅ buffer all output so stray warnings never leak into the styled page

include '../includes/db_connect.php';
require_once '../auth/send_mail.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ✅ Auto Detect URL - Works on Localhost + Live Server
    $base_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // ══ NEW: Job Role capture + validation ══
    $job_role = trim($_POST['job_role'] ?? '');
    $allowedJobRoles = ['tele_sales', 'sales_consultant', 'team_leader', 'others'];
    if (!in_array($job_role, $allowedJobRoles, true)) {
        ob_end_clean();
        echo "⚠️ Please select a valid Job Role.";
        exit;
    }
    $custom_job_role = null;
    if ($job_role === 'others') {
        $custom_job_role = trim($_POST['custom_job_role'] ?? '');
        if ($custom_job_role === '') {
            ob_end_clean();
            echo "⚠️ Please specify your Job Role.";
            exit;
        }
        if (mb_strlen($custom_job_role) > 100) {
            ob_end_clean();
            echo "⚠️ Job Role must be under 100 characters.";
            exit;
        }
    }

    if ($password !== $confirm_password) {
        ob_end_clean();
        echo "⚠️ Password and Confirm Password do not match.";
        exit;
    }

    // ✅ Check if email already exists
    $checkQuery = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $checkResult = $stmt->get_result();

    if ($checkResult->num_rows > 0) {
        ob_end_clean();
        echo "⚠️ User already registered with this email.";
        exit;
    }
    $stmt->close();

    // ✅ ROLE-BASED SET ASSIGNMENT — computed once, here, at registration
    // time. take_test.php simply reads this same stored value later
    // rather than recalculating it — this guarantees the Manage Users
    // table and the actual test served always match exactly.
    //
    // Two modes per role (set via the Change Active Set admin panel):
    //   'manual' → every new user of this role gets the one specific set
    //              the admin picked (old behavior)
    //   'auto'   → new users of this role are round-robined across every
    //              set that exists for that role in the questions table
    $assigned_set = 'rs001'; // safety fallback — used if anything below fails

    // This project runs mysqli in "exception mode", so any DB error here
    // throws instead of returning false. try/catch guarantees this whole
    // lookup can NEVER crash the page or block registration / the
    // confirmation email, no matter what goes wrong.
    try {
        $modeStmt = $conn->prepare("SELECT mode, set_code FROM active_set WHERE role_key = ? LIMIT 1");
        $modeStmt->bind_param("s", $job_role);
        $modeStmt->execute();
        $modeRow = $modeStmt->get_result()->fetch_assoc();
        $modeStmt->close();

        $mode = $modeRow['mode'] ?? 'manual';

        if ($mode === 'manual') {
            // Manual mode — everyone of this role gets the one set the
            // admin explicitly chose.
            if (!empty($modeRow['set_code'])) {
                $assigned_set = $modeRow['set_code'];
            }
        } else {
            // Auto mode — round-robin across every set that exists for
            // this specific role in the questions table.
            $roleSetsStmt = $conn->prepare("SELECT DISTINCT set_id FROM questions WHERE job_role = ? ORDER BY set_id ASC");
            $roleSetsStmt->bind_param("s", $job_role);
            $roleSetsStmt->execute();
            $roleSetsResult = $roleSetsStmt->get_result();
            $roleSets = [];
            while ($setRow = $roleSetsResult->fetch_assoc()) {
                $roleSets[] = $setRow['set_id'];
            }
            $roleSetsStmt->close();

            if (!empty($roleSets)) {
                $roleCountStmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE job_role = ?");
                $roleCountStmt->bind_param("s", $job_role);
                $roleCountStmt->execute();
                $roleCountRow = $roleCountStmt->get_result()->fetch_assoc();
                $roleCountStmt->close();

                $totalForRole = (int) ($roleCountRow['total'] ?? 0);
                $setIndex = $totalForRole % count($roleSets);
                $assigned_set = $roleSets[$setIndex];
            }
        }
    } catch (\Throwable $e) {
        // Silently keep the 'rs001' fallback set above — registration and
        // the confirmation email must always continue regardless.
        error_log("Role-based set assignment failed during registration: " . $e->getMessage());
    }

    // ✅ Register user
    $verification_code = bin2hex(random_bytes(16));
    $plainPassword = $password;

    $insertQuery = "INSERT INTO users 
                    (name, email, password, is_verified, is_approved, verification_code, assigned_set, job_role, custom_job_role) 
                    VALUES (?, ?, ?, 0, 0, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("sssssss", $name, $email, $plainPassword, $verification_code, $assigned_set, $job_role, $custom_job_role);

    if ($stmt->execute()) {

        // ✅ Verify Link — Auto Localhost + Live
        // $verify_link = $base_url . "../auth/verify.php?code=" . $verification_code;
        $verify_link = $base_url . "/auth/verify.php?code=" . urlencode($verification_code);

        // ✅ User Verification Email
        $verifyBody = '
<html><body>
<div style="font-family:Arial;padding:24px;max-width:500px;
            margin:auto;border:1px solid #eee;border-radius:8px;">
  <h2 style="color:#e53935;">Email Verification</h2>
  <p>Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
  <p>Please click below to verify your email:</p>
  <div style="text-align:center;margin:24px 0;">
    <a href="' . $verify_link . '"
       style="background:#e53935;color:#fff;padding:12px 28px;
              border-radius:8px;text-decoration:none;font-weight:700;">
      ✅ Verify Email
    </a>
  </div>
  <p style="color:#999;font-size:12px;">
    Agar aapne register nahi kiya toh ignore karein.
  </p>
  <p>Regards,<br>Team RealtySmartz Pathshala</p>
</div>
</body></html>';

        sendMail($email, $name, "Verify Your Email - RealtySmartz Pathshala", $verifyBody);

        // ✅ Admin Notification Email
        $adminBody = '
<html><body>
<div style="font-family:Arial;padding:24px;max-width:500px;
            margin:auto;border:1px solid #eee;border-radius:8px;">
  <h2 style="color:#1565c0;">New User Registered</h2>
  <p><strong>Name:</strong> ' . htmlspecialchars($name) . '</p>
  <p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>
<p><strong>Job Role:</strong> ' . htmlspecialchars($job_role === 'others' && $custom_job_role ? 'Others — ' . $custom_job_role : $job_role) . '</p>  <p><strong>Assigned Set:</strong> ' . htmlspecialchars($assigned_set) . '</p>
  <p>Please login to admin panel to approve or reject.</p>
  <p>Regards,<br>System Auto-Mailer</p>
</div>
</body></html>';

        sendMail(
            'admin@realtysmartzpathshala.in',
            'Admin',
            "New User Registration - RealtySmartz Pathshala",
            $adminBody
        );

        // ✅ Registration successful — styled success page (UI ONLY, logic unchanged)
        ob_end_clean(); // discard any warnings/notices produced above (e.g. local mail() SMTP warnings)
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Registration Successful - RealtySmartz Pathshala</title>
            <link rel="icon" href="../assets/img/logo/Pathshala.webp">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <style>
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }

                body {
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: 'Segoe UI', Arial, sans-serif;
                    background: radial-gradient(circle at top, #1b1b1b 0%, #0d0d0d 70%);
                    padding: 20px;
                }

                .success-card {
                    width: 100%;
                    max-width: 460px;
                    background: rgba(255, 255, 255, 0.04);
                    backdrop-filter: blur(14px);
                    -webkit-backdrop-filter: blur(14px);
                    border: 1px solid rgba(229, 57, 53, 0.25);
                    border-radius: 18px;
                    padding: 44px 34px;
                    text-align: center;
                    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
                    animation: fadeInUp 0.6s ease;
                }

                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(24px);
                    }

                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .check-wrap {
                    width: 84px;
                    height: 84px;
                    margin: 0 auto 22px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #e53935, #c62828);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 0 0 8px rgba(229, 57, 53, 0.12);
                    animation: pop 0.5s ease 0.15s both;
                }

                @keyframes pop {
                    0% {
                        transform: scale(0);
                        opacity: 0;
                    }

                    70% {
                        transform: scale(1.15);
                        opacity: 1;
                    }

                    100% {
                        transform: scale(1);
                    }
                }

                .check-wrap i {
                    color: #fff;
                    font-size: 38px;
                    animation: checkIn 0.4s ease 0.5s both;
                }

                @keyframes checkIn {
                    from {
                        opacity: 0;
                        transform: scale(0.5);
                    }

                    to {
                        opacity: 1;
                        transform: scale(1);
                    }
                }

                h1 {
                    color: #fff;
                    font-size: 24px;
                    margin-bottom: 8px;
                }

                .sub {
                    color: #e53935;
                    font-weight: 600;
                    font-size: 15px;
                    margin-bottom: 18px;
                }

                p.body-text {
                    color: #bbb;
                    font-size: 14.5px;
                    line-height: 1.65;
                    margin-bottom: 8px;
                }

                .note {
                    margin-top: 18px;
                    font-size: 12.5px;
                    color: #888;
                    border-top: 1px solid rgba(255, 255, 255, 0.08);
                    padding-top: 14px;
                }

                .btn-row {
                    display: flex;
                    gap: 12px;
                    margin-top: 26px;
                    flex-wrap: wrap;
                }

                .btn-row a {
                    flex: 1;
                    min-width: 140px;
                    padding: 13px 18px;
                    border-radius: 10px;
                    text-decoration: none;
                    font-weight: 700;
                    font-size: 14px;
                    transition: all 0.25s ease;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                }

                .btn-primary {
                    background: linear-gradient(135deg, #e53935, #c62828);
                    color: #fff;
                    box-shadow: 0 8px 20px rgba(229, 57, 53, 0.35);
                }

                .btn-primary:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 12px 26px rgba(229, 57, 53, 0.5);
                }

                .btn-secondary {
                    background: transparent;
                    color: #fff;
                    border: 1px solid rgba(255, 255, 255, 0.25);
                }

                .btn-secondary:hover {
                    background: rgba(255, 255, 255, 0.08);
                    transform: translateY(-3px);
                }

                @media (max-width: 480px) {
                    .success-card {
                        padding: 34px 22px;
                    }

                    .btn-row {
                        flex-direction: column;
                    }
                }
            </style>
        </head>

        <body>

            <div class="success-card">
                <div class="check-wrap"><i class="fas fa-check"></i></div>

                <h1>Registration Successful!</h1>
                <div class="sub">Your account has been created successfully.</div>

                <p class="body-text">
                    We have sent a verification email to your registered email address.
                    Please verify your email before logging in.
                </p>
                <p class="body-text">
                    If you don't see the email, please check your <strong>Spam</strong> or <strong>Promotions</strong> folder.
                </p>

                <div class="btn-row">
                    <a href="https://mail.google.com" target="_blank" class="btn-primary">
                        <i class="fas fa-envelope-open-text"></i> Open Gmail
                    </a>
                    <a href="../auth/login.html" class="btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>

                <div class="note">
                    Didn't receive the email? Wait a few minutes before requesting another verification email.
                </div>
            </div>

        </body>

        </html>
        <?php
        exit;

    } else {
        ob_end_clean();
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}

ob_end_flush();
$conn->close();
?>