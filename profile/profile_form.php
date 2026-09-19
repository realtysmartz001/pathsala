<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_email'])) {
  header("Location: ../auth/login.php");
  exit();
}

$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];

// Check edit_allowed from users table
$editAllowed = false;
$chkStmt = $conn->prepare("SELECT edit_allowed, job_role FROM users WHERE email = ?");
$chkStmt->bind_param("s", $userEmail);
$chkStmt->execute();
$chkResult = $chkStmt->get_result();
$chkRow = $chkResult->fetch_assoc();
$chkStmt->close();

if (!empty($chkRow['edit_allowed']) && $chkRow['edit_allowed'] == 1) {
  $editAllowed = true;
}

// ✅ Job Role selected at registration — display-only on this form
$jobRoleLabels = [
  'tele_sales'       => 'Tele Sales',
  'sales_consultant' => 'Sales Consultant',
  'team_leader'      => 'Team Leader',
];
$userJobRoleKey = $chkRow['job_role'] ?? '';
$userJobRoleLabel = $jobRoleLabels[$userJobRoleKey] ?? ($userJobRoleKey !== '' ? $userJobRoleKey : 'Not Set');

// Check if profile already exists
$query = "SELECT user_id FROM user_profiles WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// If profile exists and edit is not allowed, redirect to view profile
if ($result->num_rows > 0 && !$editAllowed) {
  header("Location: ../profile/view_profile.php");
  exit();
}
?>
!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complete Your Profile | Realty Smartz Pathshala</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <meta name="description" content="Empowering real estate professionals with world-class training.">
  <link rel="shortcut icon" type="image/x-icon" href="/assets/img/favicon.png">
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/fontawesome-all.min.css">
  <link rel="stylesheet" href="/assets/css/owl.carousel.min.css">
  <link rel="stylesheet" href="/assets/css/animate.min.css">
  <link rel="stylesheet" href="/assets/css/magnific-popup.css">
  <link rel="stylesheet" href="/assets/css/slick.css">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/layout.css">
  <style>
    body {
      margin: 0;
      font-family: 'Plus Jakarta Sans', Arial, sans-serif;
      background-color: var(--bg-dark);
      background-image: linear-gradient(var(--bg-overlay-1), var(--bg-overlay-2)), url('../assets/img/gallery/section_bg02.png');
      background-position: center;
      background-size: cover;
      background-attachment: fixed;
      color: var(--text-main);
      min-height: 100vh;
    }

    /* ══════════════════════════════════
       WIZARD SHELL
    ══════════════════════════════════ */
    .pf-shell {
      max-width: 720px;
      margin: 120px auto 60px;
      padding: 0 16px;
    }

    @media (max-width: 768px) {
      .pf-shell {
        margin: 100px auto 40px;
      }
    }

    .pf-welcome {
      text-align: center;
      color: var(--text-muted);
      font-size: 13px;
      letter-spacing: 0.5px;
      margin-bottom: 18px;
    }

    .pf-welcome strong {
      color: var(--gold-primary);
    }

    /* ── Luxury Title Badge ── */
    .pf-title-badge {
      display: flex;
      align-items: center;
      gap: 9px;
      width: fit-content;
      margin: 0 auto 20px;
      background: rgba(201, 147, 58, 0.10);
      border: 1px solid rgba(201, 147, 58, 0.30);
      color: var(--gold-primary);
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      padding: 8px 20px;
      border-radius: 100px;
      backdrop-filter: blur(10px);
    }

    .pf-title-badge-dot {
      width: 6px;
      height: 6px;
      background: var(--gold-primary);
      border-radius: 50%;
      flex-shrink: 0;
      animation: pfTitleBlink 1.6s ease infinite;
    }

    @keyframes pfTitleBlink {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.35; }
    }

    .pf-form-title {
      text-align: center;
      color: var(--text-main);
      font-family: 'Playfair Display', serif;
      font-weight: 800;
      font-size: clamp(1.9rem, 4vw, 2.6rem);
      letter-spacing: 0.3px;
      line-height: 1.25;
      margin-bottom: 8px;
    }

    .pf-form-title span {
      background: linear-gradient(135deg, var(--gold-primary), var(--gold-hover));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .pf-title-divider {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 34px;
    }

    .pf-title-divider .pf-title-line {
      width: 54px;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--gold-primary));
    }

    .pf-title-divider .pf-title-line.pf-title-line-r {
      background: linear-gradient(90deg, var(--gold-primary), transparent);
    }

    .pf-title-divider .pf-title-diamond {
      width: 7px;
      height: 7px;
      background: var(--gold-primary);
      transform: rotate(45deg);
      flex-shrink: 0;
      box-shadow: 0 0 10px rgba(201, 147, 58, 0.55);
    }

    /* ── Profile Completion Progress Card ── */
    .profile-progress-card {
      background: var(--card-bg);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 22px 26px;
      margin-bottom: 28px;
    }

    .ppc-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
      flex-wrap: wrap;
      gap: 6px;
    }

    .ppc-title {
      font-weight: 700;
      color: var(--text-main);
      font-size: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .ppc-title i {
      color: var(--gold-primary);
    }

    .ppc-percent {
      font-weight: 800;
      color: var(--gold-primary);
      font-size: 18px;
    }

    .ppc-bar-track {
      width: 100%;
      height: 10px;
      background: var(--glass);
      border: 1px solid var(--card-border);
      border-radius: 100px;
      overflow: hidden;
    }

    .ppc-bar-fill {
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, var(--gold-primary), var(--gold-hover));
      border-radius: 100px;
      transition: width 0.4s ease;
    }

    .ppc-sub {
      font-size: 12px;
      color: var(--text-muted);
      margin: 8px 0 14px;
    }

    .ppc-checklist {
      list-style: none;
      margin: 0;
      padding: 0;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px 16px;
    }

    .ppc-checklist li {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: var(--text-main);
    }

    .ppc-checklist li i.ppc-done {
      color: #34d399;
    }

    .ppc-checklist li i.ppc-pending {
      color: #f87171;
    }

    @media (max-width: 600px) {
      .ppc-checklist {
        grid-template-columns: 1fr;
      }
    }

    /* ── Progress Stepper ── */
    .pf-stepper {
      display: flex;
      align-items: flex-start;
      justify-content: center;
      gap: 4px;
      margin-bottom: 30px;
      flex-wrap: nowrap;
      overflow-x: auto;
      padding-bottom: 4px;
    }

    .pf-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      flex-shrink: 0;
    }

    .pf-step-circle {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      border: 1px solid var(--card-border);
      color: var(--text-muted);
      background: var(--glass);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 700;
      transition: all 0.25s;
    }

    .pf-step-label {
      font-size: 10.5px;
      color: var(--text-muted);
      white-space: nowrap;
      transition: color 0.25s;
    }

    .pf-step.is-active .pf-step-circle {
      background: linear-gradient(135deg, var(--gold-primary), var(--gold-hover));
      border-color: transparent;
      color: var(--bg-dark);
      box-shadow: 0 4px 14px rgba(201, 147, 58, 0.35);
    }

    .pf-step.is-active .pf-step-label {
      color: var(--gold-primary);
      font-weight: 700;
    }

    .pf-step.is-done .pf-step-circle {
      background: transparent;
      border-color: var(--gold-primary);
      color: var(--gold-primary);
    }

    .pf-step.is-done .pf-step-label {
      color: var(--gold-primary);
    }

    .pf-step-line {
      width: 22px;
      height: 1px;
      background: var(--card-border);
      margin-top: 17px;
      flex-shrink: 0;
    }

    .pf-step-line.is-done {
      background: var(--gold-primary);
    }

    @media (max-width: 600px) {
      .pf-step-label {
        display: none;
      }

      .pf-step-line {
        width: 14px;
      }
    }

    /* ── Wizard Card ── */
    .pf-container {
      background: var(--card-bg);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      box-shadow: var(--shadow-card);
      border: 1px solid var(--card-border);
      border-radius: 18px;
      padding: 32px 34px;
    }

    @media (max-width: 600px) {
      .pf-container {
        padding: 22px 18px;
      }
    }

    .profile-pic-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 0 0 20px;
      flex-direction: column;
    }

    .profile-pic-wrapper input[type="file"] {
      display: none !important;
    }

    .profile-pic-label {
      display: inline-block;
      position: relative;
      cursor: pointer;
      width: 100px;
      height: 100px;
      border-radius: 50%;
      overflow: hidden;
      border: 4px solid var(--gold-primary);
      box-shadow: 0 4px 14px rgba(201, 147, 58, 0.35);
      transition: box-shadow 0.3s;
      flex-shrink: 0;
    }

    .profile-pic-label:hover {
      box-shadow: 0 6px 20px rgba(201, 147, 58, 0.55);
    }

    .profile-pic-label img#profilePreview {
      width: 130px !important;
      height: 130px !important;
      border-radius: 50% !important;
      object-fit: cover !important;
      border: none !important;
      display: block !important;
    }

    .camera-icon {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.45);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s;
    }

    .profile-pic-label:hover .camera-icon {
      opacity: 1;
    }

    .camera-icon img {
      width: 36px !important;
      height: 36px !important;
      border-radius: 0 !important;
      border: none !important;
      object-fit: contain !important;
    }

    .profile-pic-hint {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 8px;
    }

    /* ── Mobile-only Take Photo / Choose from Gallery buttons ──
       Hidden by default so desktop UI is completely unchanged;
       revealed only inside the existing mobile media query below. */
    .pf-photo-mobile-actions {
      display: none;
      gap: 10px;
      margin-top: 14px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .pf-photo-mobile-btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 9px 16px;
      border-radius: 8px;
      border: 1px solid rgba(201, 147, 58, 0.35);
      background: rgba(201, 147, 58, 0.08);
      color: var(--gold-primary);
      font-size: 12.5px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.25s;
    }

    .pf-photo-mobile-btn:hover {
      background: rgba(201, 147, 58, 0.16);
    }

    .section {
      display: none;
    }

    .section.active {
      display: block;
    }

    .section h3 {
      margin-top: 0;
      color: var(--text-main);
      border-bottom: 1px solid var(--card-border);
      padding-bottom: 8px;
      font-family: 'Playfair Display', serif;
    }

    .field {
      margin-bottom: 15px;
    }

    .field label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
      color: var(--text-main);
    }

    .field input,
    .field select,
    .field textarea {
      width: 98%;
      padding: 8px;
      border-radius: 8px;
      border: 1px solid var(--card-border);
      background: var(--glass);
      color: var(--text-main);
    }

    /* Force light rendering just for the native <select> popup so Windows
       dark mode can't override option text color and make it invisible */
    .field select {
      color-scheme: light;
    }

    .field input::placeholder,
    .field textarea::placeholder {
      color: var(--text-muted);
    }

    /* Fix: dropdown option list was inheriting white text on white background */
    .field select option {
      color: #1a1a1a;
      background: #ffffff;
    }

    .file-field a {
      margin-left: 10px;
      color: var(--gold-primary);
      text-decoration: underline;
      cursor: pointer;
      font-size: 14px;
    }

    .file-field p {
      color: var(--text-muted);
      font-size: 13px;
      margin: 4px 0;
    }

    .doc-tag {
      font-size: 11px;
      font-weight: 700;
      padding: 2px 9px;
      border-radius: 12px;
      margin-left: 8px;
      vertical-align: middle;
      display: inline-block;
    }

    .doc-tag.compulsory {
      background: rgba(248, 113, 113, 0.15);
      color: #f87171;
    }

    .doc-tag.optional {
      background: rgba(148, 148, 148, 0.15);
      color: var(--text-muted);
    }

    .nav-buttons {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
    }

    .nav-buttons button {
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      background: linear-gradient(135deg, var(--gold-primary), var(--gold-hover));
      color: var(--bg-dark);
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.3s;
    }

    .nav-buttons button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(201, 147, 58, 0.35);
    }

    .submit-btn1 {
      display: block;
      margin: 30px auto 0;
      padding: 15px 40px;
      border: none;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--gold-primary), var(--gold-hover));
      color: var(--bg-dark);
      font-size: 18px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.3s;
      width: 70%;
    }

    .submit-btn1:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 30px rgba(201, 147, 58, 0.45);
    }

    .submit-btn1:disabled {
      opacity: 0.75;
      cursor: not-allowed;
    }

    .btn-spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 3px solid rgba(10, 10, 26, 0.3);
      border-top-color: var(--bg-dark);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      vertical-align: middle;
      margin-right: 8px;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    .input-error {
      border: 2px solid #f87171 !important;
      background-color: rgba(248, 113, 113, 0.08) !important;
    }

    .error-msg {
      color: #f87171;
      font-size: 12px;
      margin-top: 4px;
      display: block;
    }

    .validation-warning {
      background: rgba(250, 204, 21, 0.12);
      border: 1px solid rgba(250, 204, 21, 0.35);
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 15px;
      display: none;
    }

    .validation-warning p {
      margin: 0 0 6px 0;
      font-weight: bold;
      color: #facc15;
    }

    .validation-warning ul {
      margin: 0;
      padding-left: 20px;
      color: #facc15;
      font-size: 13px;
    }

    .reference-group {
      background: var(--glass);
      border: 1px solid var(--card-border);
      border-radius: 10px;
      padding: 14px;
      margin-bottom: 14px;
    }

    .reference-group h4 {
      margin: 0 0 10px 0;
      color: var(--gold-primary);
      font-size: 15px;
    }

    .reference-group .field {
      margin-bottom: 10px;
    }

    /* ── Social Media Section Styles ── */
    .social-link-group {
      background: var(--glass);
      border: 1px solid var(--card-border);
      border-radius: 10px;
      padding: 14px;
      margin-bottom: 14px;
      position: relative;
    }

    .social-link-group h4 {
      margin: 0 0 10px 0;
      color: var(--gold-primary);
      font-size: 15px;
    }

    .social-input-row {
      display: flex;
      gap: 10px;
      align-items: flex-start;
    }

    @media (max-width: 600px) {
      .social-input-row {
        flex-direction: column;
      }

      .social-input-row select,
      .social-input-row input {
        width: 95% !important;
      }
    }

    .social-input-row select {
      width: 38%;
      padding: 8px;
      border-radius: 8px;
      border: 1px solid var(--card-border);
      background: var(--card-bg);
      color: var(--text-main);
      color-scheme: light;
    }

    /* Popup list itself renders white (light color-scheme) — force dark
       text on the options so it isn't white-on-white and invisible */
    .social-input-row select option {
      color: #1a1a1a;
      background: #ffffff;
    }

    .social-input-row input {
      width: 60%;
      padding: 8px;
      border-radius: 8px;
      border: 1px solid var(--card-border);
      background: var(--card-bg);
      color: var(--text-main);
    }

    .remove-social-btn {
      background: #f87171;
      color: #0a0f1d;
      border: none;
      border-radius: 6px;
      padding: 7px 12px;
      cursor: pointer;
      font-size: 16px;
      flex-shrink: 0;
      transition: 0.3s;
    }

    .remove-social-btn:hover {
      background: #ef4444;
    }

    .add-social-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      background: rgba(201, 147, 58, 0.08);
      border: 2px dashed rgba(201, 147, 58, 0.45);
      border-radius: 10px;
      color: var(--gold-primary);
      font-size: 15px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
      margin-top: 8px;
    }

    .add-social-btn:hover {
      background: rgba(201, 147, 58, 0.15);
      border-color: var(--gold-primary);
    }

    /* ── File Size Alert Modal ── */
    .size-alert-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.6);
      z-index: 99999;
      justify-content: center;
      align-items: center;
    }

    .size-alert-overlay.show {
      display: flex;
    }

    .size-alert-box {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      backdrop-filter: blur(20px);
      border-radius: 16px;
      padding: 28px 30px;
      max-width: 460px;
      width: 90%;
      box-shadow: var(--shadow-card);
      text-align: center;
      animation: popIn 0.25s ease;
    }

    @keyframes popIn {
      from {
        transform: scale(0.85);
        opacity: 0;
      }

      to {
        transform: scale(1);
        opacity: 1;
      }
    }

    .size-alert-box .alert-icon {
      font-size: 48px;
      margin-bottom: 10px;
    }

    .size-alert-box h3 {
      margin: 0 0 10px;
      color: #f87171;
      font-size: 20px;
    }

    .size-alert-box ul {
      text-align: left;
      padding-left: 20px;
      color: var(--text-muted);
      font-size: 14px;
      margin: 10px 0 18px;
    }

    .size-alert-box ul li {
      margin-bottom: 6px;
    }

    .size-alert-close {
      padding: 10px 30px;
      background: linear-gradient(135deg, var(--gold-primary), var(--gold-hover));
      color: var(--bg-dark);
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.3s;
    }

    .size-alert-close:hover {
      transform: translateY(-2px);
    }

    .pf-errors-box {
      max-width: 720px;
      margin: 120px auto 0;
      background: rgba(250, 204, 21, 0.12);
      border: 1px solid rgba(250, 204, 21, 0.35);
      border-radius: 12px;
      padding: 16px 20px;
    }

    .pf-errors-box p {
      margin: 0 0 8px 0;
      font-weight: bold;
      color: #facc15;
    }

    .pf-errors-box ul {
      margin: 0;
      padding-left: 20px;
      color: #facc15;
      font-size: 14px;
    }

    @media (max-width: 600px) {
      .pf-form-title {
        font-size: 22px;
      }

      .section h3 {
        font-size: 16px;
      }

      .camera-icon {
        opacity: 1 !important;
      }

      .pf-photo-mobile-actions {
        display: flex;
      }
    }
  </style>
</head>

<body>

  <?php include '../includes/navbar.php'; ?>

  <!-- ── File Size Alert Modal ── -->
  <div class="size-alert-overlay" id="sizeAlertOverlay">
    <div class="size-alert-box">
      <div class="alert-icon">🚫</div>
      <h3>File Size Limit Exceeded!</h3>
      <p style="color:var(--text-muted);font-size:14px;margin:0 0 6px;">
        The following file(s) exceed the allowed size limit:
      </p>
      <ul id="sizeAlertList"></ul>
      <p style="color:var(--text-muted);font-size:13px;margin:0 0 18px;">
        Please re-upload smaller files before proceeding.
      </p>
      <button class="size-alert-close" onclick="closeSizeAlert()">OK, Got it!</button>
    </div>
  </div>

  <?php if (!empty($_SESSION['form_errors'])): ?>
    <div class="pf-errors-box">
      <p>⚠️ Please fix the following errors before submitting:</p>
      <ul>
        <?php foreach ($_SESSION['form_errors'] as $err): ?>
          <li><?php echo htmlspecialchars($err); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php unset($_SESSION['form_errors']); ?>
  <?php endif; ?>

  <div class="pf-shell">

    <div class="pf-welcome">Welcome, <strong><?php echo htmlspecialchars($userName); ?></strong></div>

    <div class="pf-title-badge">
      <span class="pf-title-badge-dot"></span> Employee Onboarding
    </div>

    <h1 class="pf-form-title">Complete Your <span>Profile Details</span></h1>

    <div class="pf-title-divider">
      <span class="pf-title-line"></span>
      <span class="pf-title-diamond"></span>
      <span class="pf-title-line pf-title-line-r"></span>
    </div>

    <!-- ── Profile Completion Progress ── -->
    <div class="profile-progress-card" id="profileProgressCard">
      <div class="ppc-head">
        <div class="ppc-title"><i class="fas fa-chart-line"></i> Profile Completion</div>
        <div class="ppc-percent" id="ppcPercent">0%</div>
      </div>
      <div class="ppc-bar-track">
        <div class="ppc-bar-fill" id="ppcBarFill"></div>
      </div>
      <div class="ppc-sub" id="ppcSub">0 of 7 sections completed</div>
      <ul class="ppc-checklist" id="ppcChecklist">
        <li data-section="personal"><i class="fas fa-times-circle ppc-pending"></i> Personal Information</li>
        <li data-section="education"><i class="fas fa-times-circle ppc-pending"></i> Education</li>
        <li data-section="address"><i class="fas fa-times-circle ppc-pending"></i> Address</li>
        <li data-section="bank"><i class="fas fa-times-circle ppc-pending"></i> Bank Details</li>
        <li data-section="documents"><i class="fas fa-times-circle ppc-pending"></i> Documents</li>
        <li data-section="emergency"><i class="fas fa-times-circle ppc-pending"></i> Emergency Contact</li>
        <li data-section="social"><i class="fas fa-times-circle ppc-pending"></i> Social Details</li>
      </ul>
    </div>

    <!-- ── Progress Stepper ── -->
    <div class="pf-stepper" id="pfStepper">
      <div class="pf-step is-active" id="step-0" onclick="showSection(0)">
        <div class="pf-step-circle">1</div>
        <div class="pf-step-label">Personal</div>
      </div>
      <div class="pf-step-line"></div>
      <div class="pf-step" id="step-1" onclick="showSection(1)">
        <div class="pf-step-circle">2</div>
        <div class="pf-step-label">KYC</div>
      </div>
      <div class="pf-step-line" id="stepLine-exp" style="display:none;"></div>
      <div class="pf-step" id="step-2" onclick="showSection(2)" style="display:none;">
        <div class="pf-step-circle">3</div>
        <div class="pf-step-label">Experience</div>
      </div>
      <div class="pf-step-line"></div>
      <div class="pf-step" id="step-3" onclick="showSection(3)">
        <div class="pf-step-circle">4</div>
        <div class="pf-step-label">Education</div>
      </div>
      <div class="pf-step-line"></div>
      <div class="pf-step" id="step-4" onclick="showSection(4)">
        <div class="pf-step-circle">5</div>
        <div class="pf-step-label">Emergency</div>
      </div>
      <div class="pf-step-line"></div>
      <div class="pf-step" id="step-5" onclick="showSection(5)">
        <div class="pf-step-circle">6</div>
        <div class="pf-step-label">Social</div>
      </div>
    </div>

    <div class="pf-container">
      <form id="profileForm" action="../profile/profile_submit.php" method="POST" enctype="multipart/form-data">

        <!-- ════════════════════════════════════════
           SECTION 0 — Personal Information
      ════════════════════════════════════════ -->
        <div class="section active" id="section-0">
          <h3>Personal Information</h3>

          <div class="profile-pic-wrapper">
            <label for="profilePhotoInput" class="profile-pic-label">
              <img id="profilePreview" src="data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%23333333'/%3E%3Ccircle cx='50' cy='38' r='18' fill='%23777777'/%3E%3Cellipse cx='50' cy='90' rx='32' ry='24' fill='%23777777'/%3E%3C/svg%3E" alt="Profile Picture">
              <div class="camera-icon">
                <i class="fas fa-camera" style="font-size:26px;color:#fff;"></i>
              </div>
            </label>
            <input type="file" id="profilePhotoInput" name="profile_photo" accept="image/*"
              onchange="previewProfilePic(this)">
            <div class="profile-pic-hint">Upload your profile photo *</div>
            <div class="profile-pic-hint" style="margin-top:2px;">(Supported: JPG / JPEG / PNG &bull; Maximum File Size: 5 MB)</div>
            <a href="#" id="changePhotoLink" style="display:none;color:var(--gold-primary);font-size:12px;margin-top:4px;text-decoration:underline;"
              onclick="document.getElementById('profilePhotoInput').click();return false;">🔄 Choose another photo</a>

            <!-- Hidden camera-capture input — mobile only via the buttons below.
                 Captured photo is copied into the real profilePhotoInput above,
                 so it still submits under name="profile_photo" unchanged. -->
            <input type="file" id="profilePhotoCameraInput" accept="image/*" capture="environment"
              style="display:none;" onchange="handleCameraCapture(this)">

            <div class="pf-photo-mobile-actions">
              <button type="button" class="pf-photo-mobile-btn"
                onclick="document.getElementById('profilePhotoCameraInput').click()">
                📷 Take Photo
              </button>
              <button type="button" class="pf-photo-mobile-btn"
                onclick="document.getElementById('profilePhotoInput').click()">
                🖼️ Choose from Gallery
              </button>
            </div>
          </div>

          <div class="validation-warning" id="warn-0">
            <p>⚠️ Please fill these required fields:</p>
            <ul id="warn-list-0"></ul>
          </div>

          <div class="field">
            <label>Full Name *</label>
            <input type="text" name="user_name" id="user_name" placeholder="Enter your name">
            <span class="error-msg" id="err-user_name"></span>
          </div>
          <div class="field">
            <label>Email ID *</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly
              style="cursor:not-allowed;opacity:0.85;">
            <span class="error-msg" id="err-email"></span>
          </div>
          <div class="field">
            <label>Job Role</label>
            <input type="text" id="job_role_display" value="<?php echo htmlspecialchars($userJobRoleLabel); ?>" readonly
              style="cursor:not-allowed;opacity:0.85;">
          </div>
          <div class="field">
            <label>Contact Number *</label>
            <input type="text" name="contact_no" id="contact_no" placeholder="Your contact number">
            <span class="error-msg" id="err-contact_no"></span>
          </div>
          <div class="field">
            <label>Date of Birth *</label>
            <input type="date" name="dob" id="dob">
            <span class="error-msg" id="err-dob"></span>
          </div>
          <div class="field">
            <label>Current Address</label>
            <input type="text" name="current_address" placeholder="Enter your current address">
          </div>
          <div class="field">
            <label>Permanent Address *</label>
            <input type="text" name="permanent_address" id="permanent_address"
              placeholder="Enter your permanent address">
            <span class="error-msg" id="err-permanent_address"></span>
          </div>
          <div class="field">
            <label>Are you a Fresher or Experienced? *</label>
            <select name="experience" id="experienceSelect" onchange="toggleExperienceMenu()">
              <option value="">-- Select --</option>
              <option value="fresher">Fresher</option>
              <option value="experienced">Experienced</option>
            </select>
            <span class="error-msg" id="err-experienceSelect"></span>
          </div>

          <div class="nav-buttons">
            <span></span>
            <button type="button" onclick="nextSection()">Next ➡️</button>
          </div>
        </div>

        <!-- ════════════════════════════════════════
           SECTION 1 — KYC & Bank Details
      ════════════════════════════════════════ -->
        <div class="section" id="section-1">
          <h3>KYC &amp; Bank Details</h3>

          <div class="validation-warning" id="warn-1">
            <p>⚠️ Please fill these required fields:</p>
            <ul id="warn-list-1"></ul>
          </div>

          <div class="field file-field">
            <label>Aadhar Card <span class="doc-tag compulsory">Compulsory</span></label>
            <p>Supported Formats: JPG / JPEG / PNG / PDF &bull; Maximum File Size: 600 KB</p>
            <input type="file" name="aadhar_doc" id="aadhar_doc" accept="image/*,application/pdf"
              onchange="previewFile(this,'aadharView')">
            <a href="#" id="aadharView" target="_blank" style="display:none;">View</a>
            <a href="#" id="aadharRemove" style="display:none;color:#f87171;" onclick="removeFile('aadhar_doc','aadharView');return false;">✕ Remove</a>
            <span class="error-msg" id="err-aadhar_doc"></span>
          </div>

          <div class="field file-field">
            <label>PAN Card <span class="doc-tag compulsory">Compulsory</span></label>
            <p>Supported Formats: JPG / JPEG / PNG / PDF &bull; Maximum File Size: 600 KB</p>
            <input type="file" name="pan_doc" id="pan_doc" accept="image/*,application/pdf" onchange="previewFile(this,'panView')">
            <a href="#" id="panView" target="_blank" style="display:none;">View</a>
            <a href="#" id="panRemove" style="display:none;color:#f87171;" onclick="removeFile('pan_doc','panView');return false;">✕ Remove</a>
            <span class="error-msg" id="err-pan_doc"></span>
          </div>

          <div class="field file-field">
            <label>Cancelled Cheque <span class="doc-tag optional">Optional</span></label>
            <p>Supported Formats: JPG / JPEG / PNG / PDF &bull; Maximum File Size: 600 KB</p>
            <input type="file" name="cheque_doc" id="cheque_doc" accept="image/*,application/pdf" onchange="previewFile(this,'chequeView')">
            <a href="#" id="chequeView" target="_blank" style="display:none;">View</a>
            <a href="#" id="chequeRemove" style="display:none;color:#f87171;" onclick="removeFile('cheque_doc','chequeView');return false;">✕ Remove</a>
          </div>

          <div class="field file-field">
            <label>Front Page of Passbook <span class="doc-tag optional">Optional</span></label>
            <p>Supported Formats: JPG / JPEG / PNG / PDF &bull; Maximum File Size: 600 KB</p>
            <input type="file" name="passbook_doc" id="passbook_doc" accept="image/*,application/pdf" onchange="previewFile(this,'passbookView')">
            <a href="#" id="passbookView" target="_blank" style="display:none;">View</a>
            <a href="#" id="passbookRemove" style="display:none;color:#f87171;" onclick="removeFile('passbook_doc','passbookView');return false;">✕ Remove</a>
          </div>

          <div class="nav-buttons">
            <button type="button" onclick="prevSection()">⬅️ Previous</button>
            <button type="button" onclick="nextSection()">Next ➡️</button>
          </div>
        </div>

        <!-- ════════════════════════════════════════
           SECTION 2 — Experience Details
      ════════════════════════════════════════ -->
        <div class="section" id="section-2">
          <h3>Last Organization Details</h3>

          <div class="validation-warning" id="warn-2">
            <p>⚠️ Please fill these required fields:</p>
            <ul id="warn-list-2"></ul>
          </div>

          <div class="field file-field">
            <label>Offer Letter <span class="doc-tag optional">Optional</span></label>
            <p>Supported Formats: JPG / JPEG / PNG / PDF &bull; Maximum File Size: 600 KB</p>
            <input type="file" name="offer_letter_doc" id="offer_letter_doc" accept="image/*,application/pdf"
              onchange="previewFile(this,'offerView')">
            <a href="#" id="offerView" target="_blank" style="display:none;">View</a>
            <a href="#" id="offerRemove" style="display:none;color:#f87171;" onclick="removeFile('offer_letter_doc','offerView');return false;">✕ Remove</a>
          </div>

          <div class="field file-field">
            <label>Relieving Letter <span class="doc-tag optional">Optional</span></label>
            <p>Supported Formats: JPG / JPEG / PNG / PDF &bull; Maximum File Size: 600 KB</p>
            <input type="file" name="relieving_letter_doc" id="relieving_letter_doc" accept="image/*,application/pdf"
              onchange="previewFile(this,'relievingView')">
            <a href="#" id="relievingView" target="_blank" style="display:none;">View</a>
            <a href="#" id="relievingRemove" style="display:none;color:#f87171;" onclick="removeFile('relieving_letter_doc','relievingView');return false;">✕ Remove</a>
          </div>

          <div class="field file-field">
            <label>Salary Slip (Last 3 Months) <span class="doc-tag optional">Optional</span></label>
            <p>Supported Formats: JPG / JPEG / PNG / PDF &bull; Maximum File Size: 600 KB</p>
            <input type="file" name="salary_slip_doc" id="salary_slip_doc" accept="image/*,application/pdf"
              onchange="previewFile(this,'salaryView')">
            <a href="#" id="salaryView" target="_blank" style="display:none;">View</a>
            <a href="#" id="salaryRemove" style="display:none;color:#f87171;" onclick="removeFile('salary_slip_doc','salaryView');return false;">✕ Remove</a>
          </div>

          <div class="field file-field">
            <label>Rehire Mail (if any) <span class="doc-tag optional">Optional</span></label>
            <p>Supported Formats: JPG / JPEG / PNG / PDF &bull; Maximum File Size: 600 KB</p>
            <input type="file" name="up_rehire_mail_doc" id="up_rehire_mail_doc" accept="image/*,application/pdf"
              onchange="previewFile(this,'rehireView')">
            <a href="#" id="rehireView" target="_blank" style="display:none;">View</a>
            <a href="#" id="rehireRemove" style="display:none;color:#f87171;" onclick="removeFile('up_rehire_mail_doc','rehireView');return false;">✕ Remove</a>
          </div>

          <div class="field">
            <label>Previous Company HR Number <span style="color:#f87171;">*</span></label>
            <input type="text" name="prev_hr_contact" id="prev_hr_contact"
              placeholder="Enter HR's 10-digit mobile number" maxlength="10">
            <span class="error-msg" id="err-prev_hr_contact"></span>
          </div>

          <div class="field">
            <label>Previous Company TL Number
              <span style="color:var(--text-muted);font-size:12px;">(Optional)</span>
            </label>
            <input type="text" name="prev_tl_contact" id="prev_tl_contact"
              placeholder="Enter TL's 10-digit mobile number (optional)" maxlength="10">
            <span class="error-msg" id="err-prev_tl_contact"></span>
          </div>

          <div class="nav-buttons">
            <button type="button" onclick="prevSection()">⬅️ Previous</button>
            <button type="button" onclick="nextSection()">Next ➡️</button>
          </div>
        </div>

        <!-- ════════════════════════════════════════
           SECTION 3 — Education Details
      ════════════════════════════════════════ -->
        <div class="section" id="section-3">
          <h3>Education Details</h3>

          <div class="validation-warning" id="warn-3">
            <p>⚠️ Please fill these required fields:</p>
            <ul id="warn-list-3"></ul>
          </div>

          <div class="field">
            <label>Your Education Qualification *</label>
            <select name="education" id="education">
              <option value="">-- Select --</option>
              <option value="10th">10th</option>
              <option value="12th">12th</option>
              <option value="graduation">Graduation</option>
              <option value="post_graduation">Post Graduation</option>
              <option value="diploma">Diploma</option>
            </select>
            <span class="error-msg" id="err-education"></span>
          </div>

          <div class="field file-field">
            <label>Upload Your Marksheet <span class="doc-tag optional">Optional</span></label>
            <p>Supported Formats: JPG / JPEG / PNG / PDF &bull; Maximum File Size: 600 KB</p>
            <input type="file" name="marksheet_doc" id="marksheet_doc" accept="image/*,application/pdf"
              onchange="previewFile(this,'marksheetView')">
            <a href="#" id="marksheetView" target="_blank" style="display:none;">View</a>
            <a href="#" id="marksheetRemove" style="display:none;color:#f87171;" onclick="removeFile('marksheet_doc','marksheetView');return false;">✕ Remove</a>
          </div>

          <div class="nav-buttons">
            <button type="button" onclick="prevSection()">⬅️ Previous</button>
            <button type="button" onclick="nextSection()">Next ➡️</button>
          </div>
        </div>

        <!-- ════════════════════════════════════════
           SECTION 4 — Emergency Contact
      ════════════════════════════════════════ -->
        <div class="section" id="section-4">
          <h3>Emergency Contact Details</h3>

          <div class="validation-warning" id="warn-4">
            <p>⚠️ Please fill these required fields:</p>
            <ul id="warn-list-4"></ul>
          </div>

          <p style="color:var(--text-muted);font-size:13px;margin-bottom:16px;">
            📌 <strong>2 emergency contacts are compulsory:</strong><br>
            1️⃣ Father <strong>or</strong> Mother<br>
            2️⃣ Other (Friend / Sibling / Spouse etc.)
          </p>

          <!-- Reference 1 -->
          <div class="reference-group">
            <h4>📌 Reference 1 — Father / Mother (Compulsory)</h4>

            <div class="field">
              <label>Select Relation *</label>
              <select name="contact_relation" id="contact_relation" onchange="toggleRef1Fields()">
                <option value="">-- Select --</option>
                <option value="father">Father</option>
                <option value="mother">Mother</option>
              </select>
              <span class="error-msg" id="err-contact_relation"></span>
            </div>

            <!-- Father Fields -->
            <div id="fatherFields" style="display:none;">
              <div class="field">
                <label>Father's Name *</label>
                <input type="text" name="father_name" id="father_name" placeholder="Enter father's name">
                <span class="error-msg" id="err-father_name"></span>
              </div>
              <div class="field">
                <label>Father's Contact No *</label>
                <input type="text" name="father_contact" id="father_contact" placeholder="Father's contact number"
                  maxlength="10">
                <span class="error-msg" id="err-father_contact"></span>
              </div>
              <div class="field">
                <label>Father's Address *</label>
                <textarea name="father_address" id="father_address" placeholder="Father's address"></textarea>
                <span class="error-msg" id="err-father_address"></span>
              </div>
            </div>

            <!-- Mother Fields -->
            <div id="motherFields" style="display:none;">
              <div class="field">
                <label>Mother's Name *</label>
                <input type="text" name="mother_name" id="mother_name" placeholder="Enter mother's name">
                <span class="error-msg" id="err-mother_name"></span>
              </div>
              <div class="field">
                <label>Mother's Contact No *</label>
                <input type="text" name="mother_contact" id="mother_contact" placeholder="Mother's contact number"
                  maxlength="10">
                <span class="error-msg" id="err-mother_contact"></span>
              </div>
              <div class="field">
                <label>Mother's Address *</label>
                <textarea name="mother_address" id="mother_address" placeholder="Mother's address"></textarea>
                <span class="error-msg" id="err-mother_address"></span>
              </div>
            </div>
          </div>

          <!-- Reference 2 -->
          <div class="reference-group">
            <h4>📌 Reference 2 — Other Contact (Compulsory)</h4>

            <div class="field">
              <label>Name *</label>
              <input type="text" name="other_name" id="other_name" placeholder="Enter name">
              <span class="error-msg" id="err-other_name"></span>
            </div>
            <div class="field">
              <label>Relationship *</label>
              <input type="text" name="other_relation" id="other_relation" placeholder="e.g. Friend, Sibling, Spouse">
              <span class="error-msg" id="err-other_relation"></span>
            </div>
            <div class="field">
              <label>Contact No *</label>
              <input type="text" name="other_contact" id="other_contact" placeholder="10-digit contact number"
                maxlength="10">
              <span class="error-msg" id="err-other_contact"></span>
            </div>
            <div class="field">
              <label>Address *</label>
              <textarea name="other_address" id="other_address" placeholder="Enter address"></textarea>
              <span class="error-msg" id="err-other_address"></span>
            </div>
          </div>

          <div class="nav-buttons">
            <button type="button" onclick="prevSection()">⬅️ Previous</button>
            <button type="button" onclick="nextSection()">Next ➡️</button>
          </div>
        </div>

        <!-- ════════════════════════════════════════
           SECTION 5 — Social Media Links
      ════════════════════════════════════════ -->
        <div class="section" id="section-5">
          <h3>Social Media Links</h3>

          <div class="validation-warning" id="warn-5">
            <p>⚠️ Please fill these required fields:</p>
            <ul id="warn-list-5"></ul>
          </div>

          <p style="color:var(--text-muted);font-size:13px;margin-bottom:16px;">
            📌 <strong>1 social media link is compulsory:</strong><br>
            1️⃣ Instagram, Facebook, <strong>or</strong> LinkedIn profile link<br>
            <span style="color:var(--text-muted);">➕ Additional links are optional.</span>
          </p>
          <!-- Compulsory Link: Instagram / Facebook / LinkedIn -->
          <div class="social-link-group">
            <h4>📌 Social Profile Link (Compulsory)</h4>
            <div class="social-input-row">
              <select name="social_platform_1" id="social_platform_1">
                <option value="">-- Select Platform --</option>
                <option value="instagram">📸 Instagram</option>
                <option value="facebook">📘 Facebook</option>
                <option value="linkedin">💼 LinkedIn</option>
              </select>
              <input type="url" name="social_url_1" id="social_url_1" placeholder="Paste your profile link here">
            </div>
            <span class="error-msg" id="err-social_1"></span>
          </div>

          <!-- Optional Extra Links Container -->
          <div id="extraLinksContainer"></div>

          <!-- Add More Link Button -->
          <button type="button" class="add-social-btn" onclick="addExtraSocialLink()">
            ➕ Add More Link
          </button>

          <div class="nav-buttons" style="margin-top:20px;">
            <button type="button" onclick="prevSection()">⬅️ Previous</button>
          </div>

          <!-- Submit Button -->
          <button type="button" class="submit-btn1" id="submitBtn" onclick="triggerFinalSubmit()">
            <span id="btnText">🚀 Submit Profile</span>
            <span id="btnLoader" style="display:none;">
              <span class="btn-spinner"></span> Submitting... Please Wait
            </span>
          </button>

        </div>
      </form>
    </div>
  </div>

  <?php include '../includes/footer.php'; ?>

  <script>
    let currentIndex = 0;
    let extraLinkCount = 0;

    // ══════════════════════════════════════════════
    // PROFILE COMPLETION PROGRESS (client-side, live)
    // ══════════════════════════════════════════════
    function ppcVal(id) {
      const el = document.getElementById(id);
      return el ? el.value.trim() : '';
    }

    function ppcHasFile(id) {
      const el = document.getElementById(id);
      return !!(el && el.files && el.files.length > 0);
    }

    function calcProfileProgress() {
      const sections = {};

      // 1. Personal Information
      sections.personal = !!(ppcVal('user_name') && ppcVal('contact_no') && ppcVal('dob') && ppcVal('experienceSelect'));

      // 2. Education
      sections.education = !!ppcVal('education');

      // 3. Address
      sections.address = !!ppcVal('permanent_address');

      // 4. Bank Details — at least one of Cheque / Passbook
      sections.bank = ppcHasFile('cheque_doc') || ppcHasFile('passbook_doc');

      // 5. Documents — both Aadhar and PAN required (matches compulsory validation)
      sections.documents = ppcHasFile('aadhar_doc') && ppcHasFile('pan_doc');

      // 6. Emergency Contact — Reference 1 (father/mother) + Reference 2 (other), all required
      const relation = ppcVal('contact_relation');
      let ref1Ok = false;
      if (relation === 'father') {
        ref1Ok = !!(ppcVal('father_name') && ppcVal('father_contact') && ppcVal('father_address'));
      } else if (relation === 'mother') {
        ref1Ok = !!(ppcVal('mother_name') && ppcVal('mother_contact') && ppcVal('mother_address'));
      }
      const ref2Ok = !!(ppcVal('other_name') && ppcVal('other_relation') && ppcVal('other_contact') && ppcVal('other_address'));
      sections.emergency = ref1Ok && ref2Ok;

      // 7. Social Details — the one compulsory link
      sections.social = !!(ppcVal('social_platform_1') && ppcVal('social_url_1'));

      renderProfileProgress(sections);
    }

    function renderProfileProgress(sections) {
      const total = 7;
      const completed = Object.values(sections).filter(Boolean).length;
      const percent = Math.round((completed / total) * 100);

      const percentEl = document.getElementById('ppcPercent');
      const fillEl = document.getElementById('ppcBarFill');
      const subEl = document.getElementById('ppcSub');
      if (percentEl) percentEl.textContent = percent + '%';
      if (fillEl) fillEl.style.width = percent + '%';
      if (subEl) subEl.textContent = completed + ' of ' + total + ' sections completed';

      Object.keys(sections).forEach(key => {
        const li = document.querySelector('.ppc-checklist li[data-section="' + key + '"]');
        if (!li) return;
        const icon = li.querySelector('i');
        if (!icon) return;
        icon.className = sections[key] ? 'fas fa-check-circle ppc-done' : 'fas fa-times-circle ppc-pending';
      });
    }

    // Available platforms for extra links
    const extraPlatforms = [
      { value: 'twitter', label: '🐦 Twitter / X' },
      { value: 'youtube', label: '▶️ YouTube' },
      { value: 'snapchat', label: '👻 Snapchat' },
      { value: 'pinterest', label: '📌 Pinterest' },
      { value: 'telegram', label: '✈️ Telegram' },
      { value: 'other', label: '🔗 Other' },
    ];

    // ── Show custom file size alert modal ──
    function showSizeAlert(messages) {
      const list = document.getElementById('sizeAlertList');
      list.innerHTML = messages.map(m => `<li>${m}</li>`).join('');
      document.getElementById('sizeAlertOverlay').classList.add('show');
    }

    // ── Close custom file size alert modal ──
    function closeSizeAlert() {
      document.getElementById('sizeAlertOverlay').classList.remove('show');
    }

    // ── Check single file size — returns error string or null ──
    // Limit: 600 KB
    function checkFileSize(inputId, label) {
      const input = document.getElementById(inputId);
      if (input && input.files && input.files.length > 0) {
        const sizeKB = input.files[0].size / 1024;
        if (sizeKB > 600) {
          return `<strong>${label}</strong> — ${sizeKB.toFixed(1)} KB (limit: 600 KB)`;
        }
      }
      return null;
    }

    // Show the selected section and update the stepper (visual only — same
    // indices/logic as the original left-menu version)
    function showSection(index) {
      document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
      document.getElementById('section-' + index).classList.add('active');
      updateStepper(index);
      currentIndex = index;
      document.querySelector('.pf-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Update stepper circles: done / active / upcoming states
    function updateStepper(index) {
      const order = [0, 1, 2, 3, 4, 5];
      order.forEach(i => {
        const step = document.getElementById('step-' + i);
        if (!step) return;
        step.classList.remove('is-active', 'is-done');
        if (i < index) step.classList.add('is-done');
        if (i === index) step.classList.add('is-active');
      });
    }

    // Show or hide the Experience step based on selection (was experienceMenuItem)
    function toggleExperienceMenu() {
      const val = document.getElementById('experienceSelect').value;
      const item = document.getElementById('step-2');
      const line = document.getElementById('stepLine-exp');
      const show = (val === 'experienced');
      item.style.display = show ? 'flex' : 'none';
      line.style.display = show ? 'block' : 'none';

      // Keep step numbering sequential whether Experience step is shown or hidden
      document.querySelector('#step-3 .pf-step-circle').textContent = show ? '4' : '3';
      document.querySelector('#step-4 .pf-step-circle').textContent = show ? '5' : '4';
      document.querySelector('#step-5 .pf-step-circle').textContent = show ? '6' : '5';
    }

    // Show Father or Mother fields based on selected relation
    function toggleRef1Fields() {
      const val = document.getElementById('contact_relation').value;
      document.getElementById('fatherFields').style.display = (val === 'father') ? 'block' : 'none';
      document.getElementById('motherFields').style.display = (val === 'mother') ? 'block' : 'none';
    }

    // Preview profile picture on file select
    function previewProfilePic(input) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
          document.getElementById('profilePreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);

        const changeLink = document.getElementById('changePhotoLink');
        if (changeLink) changeLink.style.display = 'inline-block';
      }
      calcProfileProgress();
    }

    // Mobile "Take Photo" — copies the captured photo into the real
    // profilePhotoInput (so it still submits as name="profile_photo"),
    // then reuses the existing preview logic unchanged.
    function handleCameraCapture(captureInput) {
      if (captureInput.files && captureInput.files[0]) {
        const dt = new DataTransfer();
        dt.items.add(captureInput.files[0]);
        const realInput = document.getElementById('profilePhotoInput');
        realInput.files = dt.files;
        previewProfilePic(realInput);
      }
    }

    // Preview uploaded file and show View link
    function previewFile(input, viewId) {
      const link = document.getElementById(viewId);
      if (input.files && input.files[0]) {
        link.href = URL.createObjectURL(input.files[0]);
        link.style.display = 'inline';
      }
      // Show the Remove link alongside View once a file is chosen
      const removeId = viewId.replace('View', 'Remove');
      const removeLink = document.getElementById(removeId);
      if (removeLink) {
        removeLink.style.display = input.files && input.files[0] ? 'inline' : 'none';
      }
    }

    // Clear a chosen file so the user can pick a different one
    function removeFile(inputId, viewId) {
      const input = document.getElementById(inputId);
      const link = document.getElementById(viewId);
      const removeId = viewId.replace('View', 'Remove');
      const removeLink = document.getElementById(removeId);

      if (input) input.value = '';
      if (link) { link.style.display = 'none'; link.href = '#'; }
      if (removeLink) removeLink.style.display = 'none';
      calcProfileProgress();
    }

    // Highlight a field with error styling and message
    function setFieldError(id, msg) {
      const el = document.getElementById(id);
      if (el) el.classList.add('input-error');
      const err = document.getElementById('err-' + id);
      if (err) err.textContent = msg;
    }

    // Remove error styling and message from a field
    function clearFieldError(id) {
      const el = document.getElementById(id);
      if (el) el.classList.remove('input-error');
      const err = document.getElementById('err-' + id);
      if (err) err.textContent = '';
    }

    // Show or hide the validation warning box for a section
    function showWarning(section, messages) {
      const box = document.getElementById('warn-' + section);
      const list = document.getElementById('warn-list-' + section);
      if (!box || !list) return;
      if (messages.length === 0) {
        box.style.display = 'none';
        list.innerHTML = '';
      } else {
        list.innerHTML = messages.map(m => `<li>${m}</li>`).join('');
        box.style.display = 'block';
      }
    }

    // Add an extra optional social media link row
    function addExtraSocialLink() {
      extraLinkCount++;
      const count = extraLinkCount;
      const container = document.getElementById('extraLinksContainer');

      const options = extraPlatforms
        .map(p => `<option value="${p.value}">${p.label}</option>`)
        .join('');

      const div = document.createElement('div');
      div.className = 'social-link-group';
      div.id = `extraLink_${count}`;
      div.innerHTML = `
      <h4>🔗 Extra Link ${count} <span style="color:var(--text-muted);font-size:12px;">(Optional)</span></h4>
      <div class="social-input-row">
        <select name="social_platform_extra[]">
          <option value="">-- Select Platform --</option>
          ${options}
        </select>
        <input type="url" name="social_url_extra[]"
               placeholder="Paste your profile link here">
        <button type="button" class="remove-social-btn"
                onclick="removeExtraSocialLink(${count})">✕</button>
      </div>
    `;
      container.appendChild(div);
    }

    // Remove an extra social media link row
    function removeExtraSocialLink(count) {
      const el = document.getElementById(`extraLink_${count}`);
      if (el) el.remove();
    }

    // ══════════════════════════════════════════════
    // SECTION VALIDATORS
    // ══════════════════════════════════════════════

    // Validate Section 0 - Personal Information
    // Check if DOB makes the person at least 18 years old
    function isAtLeast18(dobValue) {
      if (!dobValue) return false;
      const dob = new Date(dobValue);
      const today = new Date();
      let age = today.getFullYear() - dob.getFullYear();
      const m = today.getMonth() - dob.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        age--;
      }
      return age >= 18;
    }
    function validateSection0() {
      let errors = [];
      const fields = [
        { id: 'user_name', label: 'Full Name' },
        { id: 'email', label: 'Email ID' },
        { id: 'contact_no', label: 'Contact Number' },
        { id: 'dob', label: 'Date of Birth' },
        { id: 'permanent_address', label: 'Permanent Address' },
        { id: 'experienceSelect', label: 'Fresher / Experienced' },
      ];
      fields.forEach(f => {
        const el = document.getElementById(f.id);
        if (!el || !el.value.trim()) {
          setFieldError(f.id, `${f.label} is required`);
          errors.push(f.label);
        } else {
          clearFieldError(f.id);
        }
      });
      const contact = document.getElementById('contact_no');
      if (contact && contact.value.trim() && !/^\d{10}$/.test(contact.value.trim())) {
        setFieldError('contact_no', 'Enter a valid 10-digit contact number');
        if (!errors.includes('Contact Number')) errors.push('Contact Number (10 digits)');
      }
      const dobField = document.getElementById('dob');
      if (dobField && dobField.value.trim() && !isAtLeast18(dobField.value.trim())) {
        setFieldError('dob', 'You must be at least 18 years old to create your profile.');
        errors.push('You must be at least 18 years old to create your profile.');
      }
      showWarning(0, errors);
      return errors.length === 0;
    }

    // Validate Section 1 - KYC & Bank Details
    function validateSection1() {
      let errors = [];

      // ── 600 KB size checks first ──
      const sizeChecks = [
        { id: 'aadhar_doc', label: 'Aadhar Card' },
        { id: 'pan_doc', label: 'PAN Card' },
        { id: 'cheque_doc', label: 'Cancelled Cheque' },
        { id: 'passbook_doc', label: 'Front Page of Passbook' },
      ];
      let sizeErrors = [];
      sizeChecks.forEach(f => {
        const msg = checkFileSize(f.id, f.label);
        if (msg) sizeErrors.push(msg);
      });
      if (sizeErrors.length > 0) {
        showSizeAlert(sizeErrors);
        return false;
      }

      // Required file checks
      const aadhar = document.getElementById('aadhar_doc');
      const pan = document.getElementById('pan_doc');
      if (!aadhar || !aadhar.files.length) {
        setFieldError('aadhar_doc', 'Aadhar Card is required');
        errors.push('Aadhar Card');
      } else { clearFieldError('aadhar_doc'); }
      if (!pan || !pan.files.length) {
        setFieldError('pan_doc', 'PAN Card is required');
        errors.push('PAN Card');
      } else { clearFieldError('pan_doc'); }

      showWarning(1, errors);
      return errors.length === 0;
    }

    // Validate Section 2 - Experience Details
    function validateSection2() {
      const exp = document.getElementById('experienceSelect');
      if (exp.value !== 'experienced') return true;

      // ── 600 KB size checks first ──
      const sizeChecks = [
        { id: 'offer_letter_doc', label: 'Offer Letter' },
        { id: 'relieving_letter_doc', label: 'Relieving Letter' },
        { id: 'salary_slip_doc', label: 'Salary Slip' },
        { id: 'up_rehire_mail_doc', label: 'Rehire Mail' },
      ];
      let sizeErrors = [];
      sizeChecks.forEach(f => {
        const msg = checkFileSize(f.id, f.label);
        if (msg) sizeErrors.push(msg);
      });
      if (sizeErrors.length > 0) {
        showSizeAlert(sizeErrors);
        return false;
      }

      let errors = [];
      const hr = document.getElementById('prev_hr_contact');
      if (!hr || !hr.value.trim()) {
        setFieldError('prev_hr_contact', 'HR Number is required');
        errors.push('Previous Company HR Number');
      } else if (!/^\d{10}$/.test(hr.value.trim())) {
        setFieldError('prev_hr_contact', 'Enter a valid 10-digit HR number');
        errors.push('HR Number (must be 10 digits)');
      } else {
        clearFieldError('prev_hr_contact');
      }

      const tl = document.getElementById('prev_tl_contact');
      if (tl && tl.value.trim() && !/^\d{10}$/.test(tl.value.trim())) {
        setFieldError('prev_tl_contact', 'Enter a valid 10-digit TL number');
        errors.push('TL Number (must be 10 digits)');
      } else {
        clearFieldError('prev_tl_contact');
      }

      showWarning(2, errors);
      return errors.length === 0;
    }

    // Validate Section 3 - Education Details
    function validateSection3() {
      // ── 600 KB size check first ──
      const sizeErrors = [];
      const msg = checkFileSize('marksheet_doc', 'Marksheet');
      if (msg) sizeErrors.push(msg);
      if (sizeErrors.length > 0) {
        showSizeAlert(sizeErrors);
        return false;
      }

      let errors = [];
      const edu = document.getElementById('education');
      if (!edu || !edu.value) {
        setFieldError('education', 'Education Qualification is required');
        errors.push('Education Qualification');
      } else { clearFieldError('education'); }

      showWarning(3, errors);
      return errors.length === 0;
    }

    // Validate Section 4 - Emergency Contact Details
    function validateSection4() {
      let errors = [];
      const relation = document.getElementById('contact_relation').value;

      if (!relation) {
        setFieldError('contact_relation', 'Please select Father or Mother');
        errors.push('Reference 1 — Relation (Father/Mother)');
      } else {
        clearFieldError('contact_relation');
      }

      if (relation === 'father') {
        ['father_name', 'father_contact', 'father_address'].forEach(id => {
          const el = document.getElementById(id);
          const label = id.replace('father_', "Father's ").replace('_', ' ');
          if (!el || !el.value.trim()) {
            setFieldError(id, `${label} is required`);
            errors.push(label.charAt(0).toUpperCase() + label.slice(1));
          } else { clearFieldError(id); }
        });
        const fc = document.getElementById('father_contact');
        if (fc && fc.value.trim() && !/^\d{10}$/.test(fc.value.trim())) {
          setFieldError('father_contact', 'Enter valid 10-digit number');
          errors.push("Father's Contact (10 digits)");
        }
      }

      if (relation === 'mother') {
        ['mother_name', 'mother_contact', 'mother_address'].forEach(id => {
          const el = document.getElementById(id);
          const label = id.replace('mother_', "Mother's ").replace('_', ' ');
          if (!el || !el.value.trim()) {
            setFieldError(id, `${label} is required`);
            errors.push(label.charAt(0).toUpperCase() + label.slice(1));
          } else { clearFieldError(id); }
        });
        const mc = document.getElementById('mother_contact');
        if (mc && mc.value.trim() && !/^\d{10}$/.test(mc.value.trim())) {
          setFieldError('mother_contact', 'Enter valid 10-digit number');
          errors.push("Mother's Contact (10 digits)");
        }
      }

      const ref2Fields = [
        { id: 'other_name', label: 'Other Contact — Name' },
        { id: 'other_relation', label: 'Other Contact — Relationship' },
        { id: 'other_contact', label: 'Other Contact — Contact No' },
        { id: 'other_address', label: 'Other Contact — Address' },
      ];
      ref2Fields.forEach(f => {
        const el = document.getElementById(f.id);
        if (!el || !el.value.trim()) {
          setFieldError(f.id, `${f.label} is required`);
          errors.push(f.label);
        } else { clearFieldError(f.id); }
      });
      const oc = document.getElementById('other_contact');
      if (oc && oc.value.trim() && !/^\d{10}$/.test(oc.value.trim())) {
        setFieldError('other_contact', 'Enter valid 10-digit number');
        errors.push('Other Contact — Contact No (10 digits)');
      }

      showWarning(4, errors);
      if (errors.length > 0) {
        document.getElementById('warn-4').scrollIntoView({ behavior: 'smooth' });
        return false;
      }
      return true;
    }

    // Validate Section 5 - Social Media Links
    function validateSection5() {
      let errors = [];

      const platform1 = document.getElementById('social_platform_1');
      const url1 = document.getElementById('social_url_1');

      if (!platform1.value) {
        platform1.classList.add('input-error');
        errors.push('Please select Instagram, Facebook, or LinkedIn');
      } else {
        platform1.classList.remove('input-error');
      }

      if (!url1.value.trim()) {
        url1.classList.add('input-error');
        document.getElementById('err-social_1').textContent = 'Profile link is required';
        errors.push('Social Profile URL is required');
      } else if (!isValidUrl(url1.value.trim())) {
        url1.classList.add('input-error');
        document.getElementById('err-social_1').textContent = 'Enter a valid URL (https://...)';
        errors.push('Enter a valid URL');
      } else {
        url1.classList.remove('input-error');
        document.getElementById('err-social_1').textContent = '';
      }

      showWarning(5, errors);
      if (errors.length > 0) {
        document.getElementById('warn-5').scrollIntoView({ behavior: 'smooth' });
        return false;
      }
      return true;
    }

    // Simple URL format validator
    function isValidUrl(url) {
      try {
        const u = new URL(url);
        return u.protocol === 'http:' || u.protocol === 'https:';
      } catch (_) {
        return false;
      }
    }

    // Navigate to the next section with validation
    function nextSection() {
      const exp = document.getElementById('experienceSelect');
      if (currentIndex === 0 && !validateSection0()) return;
      if (currentIndex === 1 && !validateSection1()) return;
      if (currentIndex === 2 && !validateSection2()) return;
      if (currentIndex === 3 && !validateSection3()) return;
      if (currentIndex === 4 && !validateSection4()) return;

      if (currentIndex === 0) showSection(1);
      else if (currentIndex === 1) showSection(exp.value === 'experienced' ? 2 : 3);
      else if (currentIndex === 2) showSection(3);
      else if (currentIndex === 3) showSection(4);
      else if (currentIndex === 4) showSection(5);
    }

    // Navigate to the previous section
    function prevSection() {
      const exp = document.getElementById('experienceSelect');
      if (currentIndex === 5) showSection(4);
      else if (currentIndex === 4) showSection(3);
      else if (currentIndex === 3) showSection(exp.value === 'experienced' ? 2 : 1);
      else if (currentIndex === 2) showSection(1);
      else if (currentIndex === 1) showSection(0);
    }

    // Validate profile photo and final section before submitting
    function handleFinalSubmit() {
      const photoInput = document.getElementById('profilePhotoInput');

      // Profile photo is optional — only validate size if one was chosen
      if (photoInput && photoInput.files && photoInput.files.length > 0) {
        const photoSizeKB = photoInput.files[0].size / 1024;
        if (photoSizeKB > 5120) {
          showSizeAlert([
            `<strong>Profile Photo</strong> — ${photoSizeKB.toFixed(1)} KB (limit: 5 MB)`
          ]);
          return false;
        }
      }

      return true;
    }

    // Trigger submit: validate, show spinner, then submit form
    function triggerFinalSubmit() {
      const isValid = handleFinalSubmit();
      if (!isValid) return;

      const btn = document.getElementById('submitBtn');
      const btnText = document.getElementById('btnText');
      const btnLoader = document.getElementById('btnLoader');

      btn.disabled = true;
      btnText.style.display = 'none';
      btnLoader.style.display = 'inline';

      document.getElementById('profileForm').submit();
    }

    // Ensure step numbering is correct on initial page load
    toggleExperienceMenu();

    // ── Profile Completion Progress — live updates on any form interaction ──
    document.getElementById('profileForm').addEventListener('input', calcProfileProgress);
    document.getElementById('profileForm').addEventListener('change', calcProfileProgress);
    calcProfileProgress();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>