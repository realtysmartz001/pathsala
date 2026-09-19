<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include '../includes/db_connect.php';

// ── Same auth pattern as profile_form.php — no new logic invented ──
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_email'])) {
  header("Location: ../auth/login.php");
  exit();
}

$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];

// Check edit_allowed from users table (mirrors profile_form.php exactly)
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

// ✅ Job Role selected at registration — shown on the onboarding badge below
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
// (identical rule to profile_form.php, so onboarding page never traps a user)
if ($result->num_rows > 0 && !$editAllowed) {
  header("Location: ../profile/view_profile.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Instructions | Realty Smartz Pathshala</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <meta name="description" content="Read these instructions before creating your RealtySmartz Pathshala profile.">
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
       SHELL
    ══════════════════════════════════ */
    .pi-shell {
      max-width: 860px;
      margin: 120px auto 60px;
      padding: 0 16px;
    }

    @media (max-width: 768px) {
      .pi-shell {
        margin: 100px auto 40px;
      }
    }

    /* ── Hero ── */
    .pi-hero {
      text-align: center;
      margin-bottom: 34px;
    }

    .pi-hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(201, 147, 58, 0.10);
      border: 1px solid rgba(201, 147, 58, 0.30);
      color: var(--gold-primary);
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      padding: 7px 18px;
      border-radius: 100px;
      margin-bottom: 22px;
    }

    .pi-hero h1 {
      font-family: 'Playfair Display', serif;
      font-weight: 800;
      font-size: clamp(1.8rem, 4vw, 2.6rem);
      color: var(--text-main);
      margin-bottom: 14px;
      line-height: 1.25;
    }

    .pi-hero h1 span {
      background: linear-gradient(135deg, var(--gold-primary), var(--gold-hover));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .pi-hero p {
      color: var(--text-muted);
      font-size: 15px;
      line-height: 1.8;
      max-width: 560px;
      margin: 0 auto;
    }

    /* ── Card shell reused from profile form language ── */
    .pi-card {
      background: var(--card-bg);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      box-shadow: var(--shadow-card);
      border: 1px solid var(--card-border);
      border-radius: 18px;
      padding: 30px 32px;
      margin-bottom: 22px;
    }

    @media (max-width: 600px) {
      .pi-card {
        padding: 22px 18px;
      }
    }

    .pi-card h2 {
      font-family: 'Playfair Display', serif;
      font-size: 1.35rem;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .pi-card-icon {
      width: 38px;
      height: 38px;
      flex-shrink: 0;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--gold-primary), var(--gold-hover));
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--bg-dark);
      font-size: 16px;
    }

    .pi-card > p.pi-lead {
      color: var(--text-muted);
      font-size: 14px;
      line-height: 1.8;
      margin: 12px 0 0;
    }

    .pi-divider {
      border: none;
      border-top: 1px solid var(--card-border);
      margin: 20px 0;
    }

    /* ── Checklist (Before You Start) ── */
    .pi-checklist {
      list-style: none;
      margin: 16px 0 0;
      padding: 0;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    @media (max-width: 600px) {
      .pi-checklist {
        grid-template-columns: 1fr;
      }
    }

    .pi-checklist li {
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--glass);
      border: 1px solid var(--card-border);
      border-radius: 10px;
      padding: 12px 14px;
      font-size: 13.5px;
      color: var(--text-main);
    }

    .pi-checklist li i {
      color: var(--gold-primary);
      font-size: 15px;
      flex-shrink: 0;
    }

    /* ── Supported formats ── */
    .pi-format-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 14px;
    }

    .pi-format-chip {
      background: rgba(201, 147, 58, 0.10);
      border: 1px solid rgba(201, 147, 58, 0.30);
      color: var(--gold-primary);
      font-size: 12.5px;
      font-weight: 700;
      padding: 7px 16px;
      border-radius: 100px;
    }

    .pi-size-chip {
      background: rgba(248, 113, 113, 0.10);
      border: 1px solid rgba(248, 113, 113, 0.30);
      color: #f87171;
      font-size: 12.5px;
      font-weight: 700;
      padding: 7px 16px;
      border-radius: 100px;
    }

    /* ── Steps ── */
    .pi-steps {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: 16px;
    }

    .pi-step {
      display: flex;
      align-items: center;
      gap: 16px;
      background: var(--glass);
      border: 1px solid var(--card-border);
      border-left: 3px solid var(--gold-primary);
      border-radius: 12px;
      padding: 14px 18px;
      transition: all 0.25s;
    }

    .pi-step:hover {
      background: rgba(201, 147, 58, 0.06);
      transform: translateX(6px);
    }

    .pi-step-num {
      width: 36px;
      height: 36px;
      flex-shrink: 0;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--gold-primary), var(--gold-hover));
      color: var(--bg-dark);
      font-weight: 800;
      font-size: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .pi-step-title {
      font-size: 14.5px;
      font-weight: 700;
      color: var(--text-main);
    }

    /* ── Important Instructions cards ── */
    .pi-imp-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-top: 16px;
    }

    @media (max-width: 600px) {
      .pi-imp-grid {
        grid-template-columns: 1fr;
      }
    }

    .pi-imp-card {
      background: rgba(250, 204, 21, 0.08);
      border: 1px solid rgba(250, 204, 21, 0.25);
      border-radius: 12px;
      padding: 14px 16px;
      display: flex;
      align-items: flex-start;
      gap: 10px;
      font-size: 13.5px;
      color: var(--text-main);
      line-height: 1.6;
    }

    .pi-imp-card i {
      color: #facc15;
      font-size: 15px;
      margin-top: 2px;
      flex-shrink: 0;
    }

    /* ── Need Help ── */
    .pi-help-box {
      background: rgba(201, 147, 58, 0.06);
      border: 1px dashed rgba(201, 147, 58, 0.35);
      border-radius: 12px;
      padding: 18px 20px;
      display: flex;
      align-items: flex-start;
      gap: 14px;
      margin-top: 14px;
    }

    .pi-help-box i {
      color: var(--gold-primary);
      font-size: 22px;
      margin-top: 2px;
      flex-shrink: 0;
    }

    .pi-help-box p {
      color: var(--text-muted);
      font-size: 13.5px;
      line-height: 1.75;
      margin: 0;
    }

    /* ── Confirm & Start ── */
    .pi-confirm-card {
      text-align: center;
    }

    .pi-confirm-check {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      background: var(--glass);
      border: 1px solid var(--card-border);
      border-radius: 12px;
      padding: 16px 18px;
      margin: 6px auto 22px;
      max-width: 560px;
      text-align: left;
      cursor: pointer;
    }

    .pi-confirm-check input[type="checkbox"] {
      width: 20px;
      height: 20px;
      margin-top: 2px;
      accent-color: var(--gold-primary);
      cursor: pointer;
      flex-shrink: 0;
    }

    .pi-confirm-check label {
      font-size: 14px;
      color: var(--text-main);
      font-weight: 600;
      cursor: pointer;
      line-height: 1.6;
    }

    .pi-start-btn {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 15px 46px;
      border: none;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--gold-primary), var(--gold-hover));
      color: var(--bg-dark);
      font-size: 16.5px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.3s;
      text-decoration: none;
    }

    .pi-start-btn:hover:not(.disabled) {
      transform: translateY(-2px);
      box-shadow: 0 10px 34px rgba(201, 147, 58, 0.45);
      color: var(--bg-dark);
    }

    .pi-start-btn.disabled {
      opacity: 0.4;
      cursor: not-allowed;
      pointer-events: none;
    }
  </style>
</head>

<body>

  <?php include '../includes/navbar.php'; ?>

  <div class="pi-shell">

    <!-- ── Hero ── -->
    <div class="pi-hero">
      <div class="pi-hero-badge"><i class="bi bi-person-check-fill"></i>&nbsp; Onboarding &bull; <?php echo htmlspecialchars($userJobRoleLabel); ?></div>
      <h1>Welcome to <span>RealtySmartz Pathshala</span></h1>
      <p>Before creating your profile, please read these instructions carefully. Completing your profile
        correctly is mandatory before appearing for the aptitude test.</p>
    </div>

    <!-- ── Section 1: Purpose ── -->
    <div class="pi-card">
      <h2><span class="pi-card-icon"><i class="bi bi-bullseye"></i></span> Purpose</h2>
      <p class="pi-lead">
        Every new intern or employee joining RealtySmartz Pathshala must complete their profile before
        officially joining the company. This profile helps our HR team verify your identity, education,
        and documentation, ensuring a smooth and transparent onboarding process for everyone.
      </p>
    </div>

    <!-- ── Section 2: Before You Start ── -->
    <div class="pi-card">
      <h2><span class="pi-card-icon"><i class="bi bi-clipboard2-check-fill"></i></span> Before You Start</h2>
      <p class="pi-lead">Please keep the following ready before starting:</p>
      <ul class="pi-checklist">
        <li><i class="bi bi-person-vcard-fill"></i> Aadhaar Card</li>
        <li><i class="bi bi-credit-card-fill"></i> PAN Card</li>
        <li><i class="bi bi-mortarboard-fill"></i> Latest Educational Marksheet</li>
        <li><i class="bi bi-bank"></i> Bank Details (if applicable)</li>
        <li><i class="bi bi-camera-fill"></i> Passport-size Photograph (optional if applicable)</li>
      </ul>
    </div>

    <!-- ── Section 3: Supported File Formats ── -->
    <div class="pi-card">
      <h2><span class="pi-card-icon"><i class="bi bi-cloud-arrow-up-fill"></i></span> Supported File Formats</h2>
      <p class="pi-lead">Supported formats:</p>
      <div class="pi-format-row">
        <span class="pi-format-chip">JPG</span>
        <span class="pi-format-chip">JPEG</span>
        <span class="pi-format-chip">PNG</span>
        <span class="pi-format-chip">PDF</span>
        <span class="pi-size-chip"><i class="bi bi-hdd-fill"></i>&nbsp; Max Upload Size: 600 KB</span>
      </div>
    </div>

    <!-- ── Section 4: Profile Completion Steps ── -->
    <div class="pi-card">
      <h2><span class="pi-card-icon"><i class="bi bi-list-ol"></i></span> Profile Completion Steps</h2>
      <div class="pi-steps">
        <div class="pi-step">
          <div class="pi-step-num">1</div>
          <div class="pi-step-title">Personal Information</div>
        </div>
        <div class="pi-step">
          <div class="pi-step-num">2</div>
          <div class="pi-step-title">KYC &amp; Bank Details</div>
        </div>
        <div class="pi-step">
          <div class="pi-step-num">3</div>
          <div class="pi-step-title">Education Details</div>
        </div>
        <div class="pi-step">
          <div class="pi-step-num">4</div>
          <div class="pi-step-title">Emergency Contact</div>
        </div>
        <div class="pi-step">
          <div class="pi-step-num">5</div>
          <div class="pi-step-title">Social Details</div>
        </div>
      </div>
    </div>

    <!-- ── Section 5: Important Instructions ── -->
    <div class="pi-card">
      <h2><span class="pi-card-icon"><i class="bi bi-exclamation-circle-fill"></i></span> Important Instructions</h2>
      <div class="pi-imp-grid">
        <div class="pi-imp-card"><i class="bi bi-check-circle-fill"></i> Use your real information.</div>
        <div class="pi-imp-card"><i class="bi bi-check-circle-fill"></i> Enter correct phone number and email.</div>
        <div class="pi-imp-card"><i class="bi bi-check-circle-fill"></i> Upload only clear documents.</div>
        <div class="pi-imp-card"><i class="bi bi-check-circle-fill"></i> Ensure every compulsory field is completed.</div>
        <div class="pi-imp-card"><i class="bi bi-check-circle-fill"></i> Double-check your information before submitting.</div>
        <div class="pi-imp-card"><i class="bi bi-check-circle-fill"></i> Do not refresh or close the browser while filling the profile.</div>
        <div class="pi-imp-card"><i class="bi bi-check-circle-fill"></i> Complete all five steps.</div>
        <div class="pi-imp-card"><i class="bi bi-check-circle-fill"></i> Your profile must be completed before you can proceed with the aptitude test.</div>
      </div>
    </div>

    <!-- ── Section 6: Need Help ── -->
    <div class="pi-card">
      <h2><span class="pi-card-icon"><i class="bi bi-person-hearts"></i></span> Need Help?</h2>
      <div class="pi-help-box">
        <i class="bi bi-headset"></i>
        <p>If you face any issue while filling the profile, please contact the HR Team or your reporting
          manager before submitting incorrect information.</p>
      </div>
    </div>

    <!-- ── Confirm & Start ── -->
    <div class="pi-card pi-confirm-card">
      <label class="pi-confirm-check" for="piAgreeCheck">
        <input type="checkbox" id="piAgreeCheck" onchange="toggleStartBtn()">
        <span>I have read and understood all the above instructions.</span>
      </label>

      <a href="../profile/profile_form.php" class="pi-start-btn disabled" id="piStartBtn">
        <i class="bi bi-box-arrow-in-right"></i> Start Profile
      </a>
    </div>

  </div>

  <?php include '../includes/footer.php'; ?>

  <script>
    function toggleStartBtn() {
      const checked = document.getElementById('piAgreeCheck').checked;
      const btn = document.getElementById('piStartBtn');
      if (checked) {
        btn.classList.remove('disabled');
      } else {
        btn.classList.add('disabled');
      }
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>