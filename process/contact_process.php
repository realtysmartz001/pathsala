<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

include '../includes/db_connect.php';
include '../includes/mail_config.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function respond($success, $message, $errors = [])
{
    echo json_encode(['success' => $success, 'message' => $message, 'errors' => $errors]);
    exit();
}

// ── Method check ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// ── CSRF check ────────────────────────────────────
if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    respond(false, 'Invalid or expired form session. Please refresh the page and try again.');
}

// ── Gather + sanitize input ──────────────────────
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');
$recaptchaToken = $_POST['recaptcha_token'] ?? '';

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
if (strpos($ip, ',') !== false) {
    $ip = trim(explode(',', $ip)[0]);
}
$browser = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// ── Server-side validation ───────────────────────
$errors = [];

if ($first_name === '' || strlen($first_name) < 2 || strlen($first_name) > 50) {
    $errors['first_name'] = 'Please enter a valid first name.';
} elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $first_name)) {
    $errors['first_name'] = 'First name contains invalid characters.';
}

if ($last_name === '' || strlen($last_name) < 2 || strlen($last_name) > 50) {
    $errors['last_name'] = 'Please enter a valid last name.';
} elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $last_name)) {
    $errors['last_name'] = 'Last name contains invalid characters.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}

if ($phone === '' || !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
    $errors['phone'] = 'Please enter a valid phone number.';
}

if ($message === '' || strlen($message) < 10) {
    $errors['message'] = 'Message must be at least 10 characters long.';
} elseif (strlen($message) > 2000) {
    $errors['message'] = 'Message is too long (max 2000 characters).';
}

// Header injection guard
foreach ([$first_name, $last_name, $email, $phone] as $field) {
    if (preg_match('/[\r\n]/', $field)) {
        respond(false, 'Invalid input detected.');
    }
}

if (!empty($errors)) {
    respond(false, 'Please correct the highlighted fields.', $errors);
}

// ── reCAPTCHA v3 verification ────────────────────
if (RECAPTCHA_SECRET_KEY !== 'YOUR_RECAPTCHA_SECRET_KEY_HERE') {
    if (empty($recaptchaToken)) {
        respond(false, 'reCAPTCHA verification failed. Please try again.');
    }
    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $ch = curl_init($verifyUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptchaToken,
        'remoteip' => $ip
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $captchaResult = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (empty($captchaResult['success']) || ($captchaResult['score'] ?? 0) < 0.5) {
        respond(false, 'We could not verify you are human. Please try again.');
    }
}

// ── Rate limiting: max 3 submissions / 10 min / IP ──
$rateStmt = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM contact_form WHERE ip_address = ? AND created_at > (NOW() - INTERVAL 10 MINUTE)"
);
$rateStmt->bind_param("s", $ip);
$rateStmt->execute();
$rateCount = $rateStmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$rateStmt->close();

if ($rateCount >= 3) {
    respond(false, 'Too many submissions. Please try again after some time.');
}

// ── Insert into DB ────────────────────────────────
$stmt = $conn->prepare(
    "INSERT INTO contact_form (first_name, last_name, email, phone, message, ip_address, browser, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'unread')"
);
$stmt->bind_param("sssssss", $first_name, $last_name, $email, $phone, $message, $ip, $browser);

if (!$stmt->execute()) {
    error_log("Contact form DB insert failed: " . $stmt->error);
    respond(false, 'Something went wrong. Please try again later.');
}
$stmt->close();

// ── Send email via PHPMailer ─────────────────────
$dateNow = date('d M Y');
$timeNow = date('h:i A');
$safeMessage = nl2br(htmlspecialchars($message));

$emailBody = "
<div style='font-family: Segoe UI, Arial, sans-serif; background:#f4f6f9; padding:30px;'>
  <div style='max-width:600px;margin:0 auto;background:#181824;border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,0.08);'>
    <div style='background:linear-gradient(135deg,#c9a84c,#e6c96a);padding:24px 30px;'>
      <h2 style='margin:0;color:#1a1a1a;font-size:20px;'>New Contact Enquiry</h2>
      <p style='margin:4px 0 0;color:#1a1a1a;font-size:13px;'>Realty Smartz Pathshala</p>
    </div>
    <div style='padding:28px 30px;color:#e5e5e5;'>
      <table style='width:100%;border-collapse:collapse;font-size:14px;'>
        <tr><td style='padding:8px 0;color:#c9a84c;width:140px;'>First Name</td><td style='padding:8px 0;'>" . htmlspecialchars($first_name) . "</td></tr>
        <tr><td style='padding:8px 0;color:#c9a84c;'>Last Name</td><td style='padding:8px 0;'>" . htmlspecialchars($last_name) . "</td></tr>
        <tr><td style='padding:8px 0;color:#c9a84c;'>Email</td><td style='padding:8px 0;'>" . htmlspecialchars($email) . "</td></tr>
        <tr><td style='padding:8px 0;color:#c9a84c;'>Phone</td><td style='padding:8px 0;'>" . htmlspecialchars($phone) . "</td></tr>
        <tr><td style='padding:8px 0;color:#c9a84c;vertical-align:top;'>Message</td><td style='padding:8px 0;'>{$safeMessage}</td></tr>
        <tr><td colspan='2' style='padding:16px 0 8px;border-top:1px solid rgba(255,255,255,0.1);'></td></tr>
        <tr><td style='padding:6px 0;color:#888;font-size:12px;'>Date</td><td style='padding:6px 0;color:#aaa;font-size:12px;'>{$dateNow}</td></tr>
        <tr><td style='padding:6px 0;color:#888;font-size:12px;'>Time</td><td style='padding:6px 0;color:#aaa;font-size:12px;'>{$timeNow}</td></tr>
        <tr><td style='padding:6px 0;color:#888;font-size:12px;'>IP Address</td><td style='padding:6px 0;color:#aaa;font-size:12px;'>" . htmlspecialchars($ip) . "</td></tr>
        <tr><td style='padding:6px 0;color:#888;font-size:12px;'>Browser</td><td style='padding:6px 0;color:#aaa;font-size:12px;'>" . htmlspecialchars($browser) . "</td></tr>
      </table>
    </div>
  </div>
</div>";

// Skip email entirely if SMTP isn't configured yet — avoids long connection timeouts
if (SMTP_HOST !== 'smtp.yourprovider.com' && SMTP_PASSWORD !== 'YOUR_SMTP_PASSWORD_HERE') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->Timeout = 5;               // fail fast instead of hanging
        $mail->SMTPKeepAlive = false;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress(CONTACT_RECEIVER_EMAIL);
        $mail->addReplyTo($email, $first_name . ' ' . $last_name);

        $mail->isHTML(true);
        $mail->Subject = 'New Contact Enquiry - Realty Smartz Pathshala';
        $mail->Body = $emailBody;

        $mail->send();
    } catch (Exception $e) {
        // Enquiry is already saved in DB even if email fails — log and continue
        error_log("PHPMailer error: " . $mail->ErrorInfo);
    }
} else {
    error_log("Contact form: SMTP not configured yet — email skipped, enquiry saved to DB only.");
}

respond(true, "Thank you! Your enquiry has been received successfully. We will contact you shortly.");