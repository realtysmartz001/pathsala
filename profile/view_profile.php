<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_email'])) {
  header("Location: ../auth/login.php");
  exit();
}

// ✅ Dynamic base URL — hardcode nahi
$baseURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
  . '://' . $_SERVER['HTTP_HOST'] . '/';

$userEmail = $_SESSION['user_email'];

$query = "SELECT * FROM user_profiles WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$userEmail = $_SESSION['user_email'];
$userName = $profile['user_name'] ?? $userEmail; // ADD THIS LINE
$stmt->close();

if (!$profile) {
  echo "<!DOCTYPE html>
    <html><head>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
      body { background: #0b0f19; color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
      .alert-custom { background: rgba(31, 41, 55, 0.8); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 16px; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); backdrop-filter: blur(12px); }
      .btn-gold { background: linear-gradient(135deg, #d4af37 0%, #aa7c11 100%); color: #000; font-weight: 600; border: none; padding: 10px 24px; border-radius: 8px; text-decoration: none; display: inline-block; transition: all 0.3s ease; }
      .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4); color: #000; }
    </style>
    </head><body>
    <div class='container py-5'>
      <div class='alert-custom text-center max-w-md mx-auto'>
        <h4 class='mb-3' style='color: #d4af37;'>⚠️ No Profile Found</h4>
        <p class='text-muted mb-4'>It looks like you haven't set up your professional real estate profile yet.</p>
        <a href='../profile/profile_form.php' class='btn-gold'>Create Profile</a>
      </div>
    </div></body></html>";
  exit();
}

// ✅ Build full URL for any file — relative path, same pattern as admin_profile_details.php
function buildFileURL($filename, $baseURL)
{
  if (empty($filename))
    return null;
  if (str_starts_with($filename, 'http'))
    return $filename;
  $normalized = str_replace('\\', '/', $filename);
  $pos = strripos($normalized, '/uploads/');
  if ($pos !== false) {
    $normalized = substr($normalized, $pos + strlen('/uploads/'));
  } elseif (str_starts_with($normalized, 'uploads/')) {
    $normalized = substr($normalized, strlen('uploads/'));
  }
  return '/uploads/' . ltrim($normalized, '/');
}


// ✅ Education label helper
function eduLabel($val)
{
  $map = [
    '10th'            => '10th',
    '12th'            => '12th',
    'graduation'      => 'Graduation',
    'post_graduation' => 'Post Graduation',
    'diploma'         => 'Diploma',
  ];
  return $map[$val] ?? ucfirst($val);
}

// ✅ Social Media platform label + icon helper
function socialIcon($platform)
{
  $map = [
    'instagram' => ['icon' => 'bi-instagram',  'color' => '#E1306C', 'label' => 'Instagram'],
    'facebook'  => ['icon' => 'bi-facebook',   'color' => '#1877F2', 'label' => 'Facebook'],
    'linkedin'  => ['icon' => 'bi-linkedin',   'color' => '#0A66C2', 'label' => 'LinkedIn'],
    'twitter'   => ['icon' => 'bi-twitter-x',  'color' => '#ffffff', 'label' => 'Twitter / X'],
    'youtube'   => ['icon' => 'bi-youtube',    'color' => '#FF0000', 'label' => 'YouTube'],
    'snapchat'  => ['icon' => 'bi-snapchat',   'color' => '#FFFC00', 'label' => 'Snapchat'],
    'pinterest' => ['icon' => 'bi-pinterest',  'color' => '#E60023', 'label' => 'Pinterest'],
    'telegram'  => ['icon' => 'bi-telegram',   'color' => '#0088CC', 'label' => 'Telegram'],
    'other'     => ['icon' => 'bi-link-45deg', 'color' => '#d4af37', 'label' => 'Other'],
  ];
  return $map[$platform] ?? ['icon' => 'bi-link-45deg', 'color' => '#d4af37', 'label' => ucfirst($platform)];
}

$profilePhoto = buildFileURL($profile['profile_photo'] ?? '', $baseURL);

// ✅ Fetch social media links from DB
$socialLinks = [];

// Compulsory Link 1 (Instagram/Facebook)
if (!empty($profile['social_platform_1']) && !empty($profile['social_url_1'])) {
  $socialLinks[] = [
    'platform' => $profile['social_platform_1'],
    'url'      => $profile['social_url_1'],
    'required' => true,
  ];
}

// Compulsory Link 2 (LinkedIn)
if (!empty($profile['social_url_2'])) {
  $socialLinks[] = [
    'platform' => 'linkedin',
    'url'      => $profile['social_url_2'],
    'required' => true,
  ];
}

// Extra optional links
// Stored as JSON array or comma-separated — handle both
$extraPlatforms = [];
$extraUrls      = [];

if (!empty($profile['social_platform_extra'])) {
  $decoded = json_decode($profile['social_platform_extra'], true);
  $extraPlatforms = is_array($decoded) ? $decoded : explode(',', $profile['social_platform_extra']);
}
if (!empty($profile['social_url_extra'])) {
  $decoded = json_decode($profile['social_url_extra'], true);
  $extraUrls = is_array($decoded) ? $decoded : explode(',', $profile['social_url_extra']);
}

foreach ($extraPlatforms as $i => $platform) {
  $url = $extraUrls[$i] ?? '';
  if (!empty($platform) && !empty($url)) {
    $socialLinks[] = [
      'platform' => trim($platform),
      'url'      => trim($url),
      'required' => false,
    ];
  }
}

// ══════════════════════════════════════════════════════════════
// ✅ PROFILE COMPLETION PROGRESS — calculated from existing $profile
// (no extra DB queries; 7 sections, same rules used across the site)
// ══════════════════════════════════════════════════════════════
function calcProfileCompletion($profile)
{
  $sections = [];

  // 1. Personal Information
  $sections['personal'] = !empty($profile['user_name']) && !empty($profile['contact_no'])
    && !empty($profile['dob']) && !empty($profile['experience']);

  // 2. Education
  $sections['education'] = !empty($profile['education']);

  // 3. Address
  $sections['address'] = !empty($profile['permanent_address']);

  // 4. Bank Details — at least one of Cheque / Passbook
  $sections['bank'] = !empty($profile['cheque_doc']) || !empty($profile['passbook_doc']);

  // 5. Documents — both Aadhar and PAN required
  $sections['documents'] = !empty($profile['aadhar_doc']) && !empty($profile['pan_doc']);

  // 6. Emergency Contact — Reference 1 (father/mother) + Reference 2 (other)
  $relation = $profile['contact_relation'] ?? '';
  $ref1Ok = false;
  if ($relation === 'father') {
    $ref1Ok = !empty($profile['father_name']) && !empty($profile['father_contact']) && !empty($profile['father_address']);
  } elseif ($relation === 'mother') {
    $ref1Ok = !empty($profile['mother_name']) && !empty($profile['mother_contact']) && !empty($profile['mother_address']);
  }
  $ref2Ok = !empty($profile['other_name']) && !empty($profile['other_relation'])
    && !empty($profile['other_contact']) && !empty($profile['other_address']);
  $sections['emergency'] = $ref1Ok && $ref2Ok;

  // 7. Social Details — the one compulsory link
  $sections['social'] = !empty($profile['social_platform_1']) && !empty($profile['social_url_1']);

  $total = count($sections);
  $completed = count(array_filter($sections));
  $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

  return [
    'sections'  => $sections,
    'completed' => $completed,
    'total'     => $total,
    'percent'   => $percent,
  ];
}

$progress = calcProfileCompletion($profile);
$progressLabels = [
  'personal'  => 'Personal Information',
  'education' => 'Education',
  'address'   => 'Address',
  'bank'      => 'Bank Details',
  'documents' => 'Documents',
  'emergency' => 'Emergency Contact',
  'social'    => 'Social Details',
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile | Realty Smartz</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <meta name="description" content="Empowering real estate professionals with world-class training.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="/assets/img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="/assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="/assets/css/animate.min.css">
    <link rel="stylesheet" href="/assets/css/magnific-popup.css">
    <link rel="stylesheet" href="/assets/css/slick.css">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/layout.css">
<style>
    /* No local :root needed — layout.css already provides
       --bg-dark, --card-bg, --card-border, --gold-primary,
       --gold-hover, --text-main, --text-muted, --bg-overlay-1/2
       as theme-aware aliases for both dark and light mode. */

    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background-color: var(--bg-dark);
      background-image: linear-gradient(var(--bg-overlay-1), var(--bg-overlay-2)), url('../assets/img/gallery/section_bg02.png');
      background-position: center;
      background-size: cover;
      background-attachment: fixed;
      color: var(--text-main);
      min-height: 100vh;
    }

    /* ── Luxury Card Overrides ── */
    .card {
      background: var(--card-bg) !important;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid var(--card-border) !important;
      border-radius: 20px !important;
      box-shadow: var(--shadow-card) !important;
      color: var(--text-main);
    }

    /* ── Sidebar Card ── */
    .profile-card {
      text-align: center;
      padding: 28px 20px;
    }

    /* ── Profile Photo ── */
    .profile-photo-wrapper {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      border: 3px solid transparent;
      background: linear-gradient(var(--card-bg), var(--card-bg)) padding-box,
                  linear-gradient(135deg, var(--gold-primary), var(--gold-hover)) border-box;
      overflow: hidden;
      margin: 0 auto 18px auto;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 10px 25px rgba(201, 147, 58, 0.2);
    }

    .profile-photo-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .profile-photo-wrapper .default-icon {
      font-size: 72px;
      color: var(--text-muted);
    }

    /* ── Smart Bootstrap Color Overrides for Luxury Theme ── */
    .text-primary { color: var(--gold-primary) !important; }
    .text-success { color: #34d399 !important; }
    .text-danger { color: #f87171 !important; }
    .text-warning { color: #facc15 !important; }
    .text-info { color: #38bdf8 !important; }
    .text-muted { color: var(--text-muted) !important; }
    .text-white { color: var(--text-main) !important; }

    .bg-primary { background-color: var(--gold-primary) !important; color: var(--bg-dark) !important; font-weight: 600; }
    .bg-success { background-color: rgba(52, 211, 153, 0.15) !important; color: #34d399 !important; border: 1px solid rgba(52, 211, 153, 0.3); }
    .bg-warning { background-color: rgba(250, 204, 21, 0.15) !important; color: #facc15 !important; border: 1px solid rgba(250, 204, 21, 0.3); }
    .bg-info { background-color: rgba(56, 189, 248, 0.15) !important; color: #38bdf8 !important; border: 1px solid rgba(56, 189, 248, 0.3); }
    .bg-secondary { background-color: rgba(148, 163, 184, 0.15) !important; color: var(--text-muted) !important; border: 1px solid var(--card-border); }

    /* ── Luxury Buttons ── */
    .btn-primary {
      background: linear-gradient(135deg, var(--gold-primary) 0%, var(--gold-hover) 100%) !important;
      border: none !important;
      color: var(--bg-dark) !important;
      font-weight: 600;
      box-shadow: 0 4px 15px rgba(201, 147, 58, 0.25);
      transition: all 0.3s ease;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(201, 147, 58, 0.4);
      color: var(--bg-dark) !important;
    }

    .btn-outline-secondary {
      background: var(--card-bg) !important;
      border: 1px solid var(--card-border) !important;
      color: var(--text-main) !important;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    .btn-outline-secondary:hover {
      background: var(--glass-hover) !important;
      border-color: var(--gold-primary) !important;
      color: var(--gold-primary) !important;
      transform: translateY(-2px);
      color: var(--bg-dark) !important;
    }

    .btn-outline-primary, .btn-outline-warning {
      background: rgba(201, 147, 58, 0.05) !important;
      border: 1px solid rgba(201, 147, 58, 0.3) !important;
      color: var(--gold-primary) !important;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    .btn-outline-primary:hover, .btn-outline-warning:hover {
      background: var(--gold-primary) !important;
      color: var(--bg-dark) !important;
      border-color: var(--gold-primary) !important;
      box-shadow: 0 4px 12px rgba(201, 147, 58, 0.3);
    }

    /* ── Tabs ── */
    .nav-tabs {
      border-bottom: 1px solid var(--card-border);
      gap: 8px;
      padding-bottom: 12px;
    }
    .nav-tabs .nav-link {
      color: var(--text-muted);
      font-weight: 600;
      border: 1px solid transparent;
      border-radius: 10px;
      padding: 10px 18px;
      transition: all 0.3s ease;
      background: var(--card-bg);
    }
    .nav-tabs .nav-link:hover {
      color: var(--text-main);
      background: var(--glass-hover);
      border-color: var(--card-border);
    }
    .nav-tabs .nav-link.active {
      background: linear-gradient(135deg, rgba(201, 147, 58, 0.15) 0%, rgba(201, 147, 58, 0.05) 100%) !important;
      color: var(--gold-primary) !important;
      border: 1px solid rgba(201, 147, 58, 0.4) !important;
      box-shadow: 0 4px 15px rgba(201, 147, 58, 0.15);
    }
    .nav-tabs .nav-link i {
      font-size: 15px;
      vertical-align: middle;
      margin-right: 4px;
    }

    /* ── Field rows ── */
    .field {
      padding: 14px 16px;
      border-radius: 12px;
      margin-bottom: 14px;
      background: var(--glass);
      border: 1px solid var(--card-border);
      transition: all 0.3s ease;
      color: var(--text-main);
    }
    .field:hover {
      background: var(--glass-hover);
      border-color: rgba(201, 147, 58, 0.25);
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    }
    .field strong {
      display: inline-block;
      min-width: 180px;
      color: var(--text-main);
      font-weight: 600;
    }

    /* ── Section title ── */
    .section-title {
      border-left: 4px solid var(--gold-primary);
      padding-left: 14px;
      margin-bottom: 24px;
      color: var(--text-main);
      font-weight: 700;
      font-size: 1.25rem;
      letter-spacing: 0.5px;
    }

    /* ── Document links ── */
    .document-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s ease;
    }
    .document-link:hover {
      transform: translateY(-2px);
    }

    /* ── Emergency reference cards ── */
    .ref-card {
      border-radius: 16px;
      margin-bottom: 20px;
      overflow: hidden;
      background: var(--glass);
      border: 1px solid var(--card-border);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }
    .ref-card .ref-header {
      padding: 14px 18px;
      font-weight: 700;
      font-size: 15px;
      letter-spacing: 0.5px;
      color: #fff;
    }
    .ref-card .ref-header.ref1 {
      background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
      border-bottom: 1px solid rgba(56, 189, 248, 0.2);
      color: #38bdf8;
    }
    .ref-card .ref-header.ref2 {
      background: linear-gradient(135deg, #064e3b 0%, #0f172a 100%);
      border-bottom: 1px solid rgba(52, 211, 153, 0.2);
      color: #34d399;
    }
    .ref-card .ref-body {
      padding: 18px;
      background: transparent;
    }

    /* ── Badge ── */
    .status-badge {
      font-size: 12px;
      padding: 6px 12px;
      border-radius: 20px;
      letter-spacing: 0.3px;
      font-weight: 600;
    }

    /* ── Social Media Cards ── */
    .social-card {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 16px;
      border-radius: 14px;
      border: 1px solid var(--card-border);
      margin-bottom: 16px;
      background: var(--glass);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      text-decoration: none;
      color: var(--text-main);
    }
    .social-card:hover {
      transform: translateY(-4px);
      background: var(--glass-hover);
      border-color: var(--gold-primary);
      box-shadow: 0 10px 25px rgba(0,0,0,0.15), 0 0 15px rgba(201, 147, 58, 0.15);
      text-decoration: none;
      color: var(--text-main);
    }
    .social-icon-wrap {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-size: 24px;
      color: #fff;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    .social-card-info strong {
      display: block;
      font-size: 16px;
      color: var(--text-main);
    }
    .social-card-info span {
      font-size: 13px;
      color: var(--text-muted);
      word-break: break-all;
    }
    .social-badge-required {
      font-size: 10px;
      padding: 3px 8px;
      border-radius: 20px;
      background: rgba(201, 147, 58, 0.15);
      border: 1px solid rgba(201, 147, 58, 0.3);
      color: var(--gold-primary);
      margin-left: 8px;
      font-weight: 600;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .view-profpad {
      padding-top: 8rem;
      padding-bottom: 6rem;
    }

    .alert-info {
      background: rgba(56, 189, 248, 0.1) !important;
      border: 1px solid rgba(56, 189, 248, 0.2) !important;
      color: #38bdf8 !important;
      border-radius: 12px;
    }

    /* ── Profile Completion Progress Card ── */
    .progress-card {
      padding: 22px 24px;
      margin-bottom: 24px;
    }

    .progress-card .pc-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
      flex-wrap: wrap;
      gap: 6px;
    }

    .progress-card .pc-title {
      font-weight: 700;
      color: var(--text-main);
      font-size: 15px;
    }

    .progress-card .pc-title i {
      color: var(--gold-primary);
      margin-right: 6px;
    }

    .progress-card .pc-percent {
      font-weight: 800;
      color: var(--gold-primary);
      font-size: 18px;
    }

    .progress-card .progress {
      height: 10px;
      border-radius: 100px;
      background: var(--glass);
      border: 1px solid var(--card-border);
    }

    .progress-card .progress-bar {
      background: linear-gradient(90deg, var(--gold-primary), var(--gold-hover));
    }

    .progress-card .pc-sub {
      font-size: 12px;
      color: var(--text-muted);
      margin: 8px 0 14px;
    }

    .progress-card .pc-checklist {
      list-style: none;
      margin: 0;
      padding: 0;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px 16px;
    }

    .progress-card .pc-checklist li {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: var(--text-main);
    }

    .progress-card .pc-checklist li i.pc-done {
      color: #34d399;
    }

    .progress-card .pc-checklist li i.pc-pending {
      color: #f87171;
    }

    @media (max-width: 576px) {
      .field strong { min-width: 130px; font-size: 14px; }
      .social-card  { flex-direction: column; align-items: flex-start; }
      .nav-tabs .nav-link { width: 100%; text-align: left; }
      .view-doc-btn{padding: 25px 30px !important;  }
      .progress-card .pc-checklist { grid-template-columns: 1fr; }
    }
</style>
</head>

<body>

<?php include '../includes/navbar.php'; ?>

  <div class="container view-profpad">

    <!-- ✅ Profile Completion Progress -->
    <div class="card progress-card">
      <div class="pc-head">
        <div class="pc-title"><i class="bi bi-graph-up-arrow"></i>Profile Completion</div>
        <div class="pc-percent"><?php echo $progress['percent']; ?>%</div>
      </div>
      <div class="progress">
        <div class="progress-bar" role="progressbar" style="width: <?php echo $progress['percent']; ?>%;"
          aria-valuenow="<?php echo $progress['percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
      </div>
      <div class="pc-sub">
        <?php echo $progress['completed']; ?> of <?php echo $progress['total']; ?> sections completed
      </div>
      <ul class="pc-checklist">
        <?php foreach ($progressLabels as $key => $label): ?>
          <li>
            <?php if ($progress['sections'][$key]): ?>
              <i class="bi bi-check-circle-fill pc-done"></i>
            <?php else: ?>
              <i class="bi bi-x-circle-fill pc-pending"></i>
            <?php endif; ?>
            <?php echo htmlspecialchars($label); ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="row g-4">

      <div class="col-lg-3">
        <div class="card profile-card">

          <div class="profile-photo-wrapper">
            <?php if ($profilePhoto): ?>
              <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="Profile Photo"
                   onerror="this.style.display='none';
                            document.getElementById('defaultIcon').style.display='block';">
              <i id="defaultIcon" class="bi bi-person-fill default-icon" style="display:none;"></i>
            <?php else: ?>
              <i class="bi bi-person-fill default-icon"></i>
            <?php endif; ?>
          </div>

          <h5 class="mb-1 fw-bold text-main" style="font-size: 1.3rem;">
            <?php echo htmlspecialchars($profile['user_name'] ?? 'N/A'); ?>
          </h5>
          <p class="mb-2 small" style="color: var(--text-muted);">
            <i class="bi bi-envelope-fill text-primary me-1"></i>
            <?php echo htmlspecialchars($profile['email'] ?? ''); ?>
          </p>
          <p class="mb-3 small" style="color: var(--text-muted);">
            <i class="bi bi-telephone-fill text-success me-1"></i>
            <?php echo htmlspecialchars($profile['contact_no'] ?? ''); ?>
          </p>
          <p class="mb-2">
            <span class="badge bg-<?php echo ($profile['experience'] === 'experienced')
              ? 'warning text-dark' : 'info text-dark'; ?> status-badge">
              <i class="bi bi-briefcase-fill me-1"></i>
              <?php echo ucfirst($profile['experience'] ?? ''); ?>
            </span>
          </p>
          <p class="mb-4">
            <span class="badge bg-secondary status-badge">
              <i class="bi bi-mortarboard-fill me-1"></i>
              <?php echo eduLabel($profile['education'] ?? ''); ?>
            </span>
          </p>

          <div class="d-grid gap-2">
            <a href="../index.php" class="btn btn-outline-secondary btn-sm py-2">
              <i class="bi bi-house me-1"></i> Dashboard
            </a>
            <?php
            if (!isset($conn) || !$conn->ping()) include_once '../includes/db_connect.php';
            $chkStmt = $conn->prepare("SELECT edit_allowed FROM users WHERE email = ?");
            $chkStmt->bind_param("s", $userEmail);
            $chkStmt->execute();
            $chkResult = $chkStmt->get_result();
            $chkRow    = $chkResult->fetch_assoc();
            $chkStmt->close();
            if (!empty($chkRow['edit_allowed']) && $chkRow['edit_allowed'] == 1): ?>
              <a href="../profile/user_edit_profile.php" class="btn btn-primary btn-sm py-2 mt-1">
                <i class="bi bi-pencil-fill me-1"></i> Edit Profile
              </a>
            <?php endif; ?>
          </div>

        </div>
      </div>

      <div class="col-lg-9">
        <div class="card">
          <div class="card-body p-4 p-md-5">

            <ul class="nav nav-tabs flex-wrap" id="profileTabs" role="tablist">
              <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab"
                        data-bs-target="#personal" type="button">
                  <i class="bi bi-person-fill"></i> Personal
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#kyc" type="button">
                  <i class="bi bi-card-checklist"></i> KYC
                </button>
              </li>
              <?php if (($profile['experience'] ?? '') === 'experienced'): ?>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#expDocs" type="button">
                  <i class="bi bi-briefcase-fill"></i> Experience
                </button>
              </li>
              <?php endif; ?>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#education" type="button">
                  <i class="bi bi-book-fill"></i> Education
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#emergency" type="button">
                  <i class="bi bi-exclamation-triangle-fill text-danger"></i> Emergency
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#social" type="button">
                  <i class="bi bi-share-fill text-primary"></i> Social
                </button>
              </li>
            </ul>

            <div class="tab-content pt-4">

              <div class="tab-pane fade show active" id="personal">
                <h5 class="section-title">👤 Personal Information</h5>
                <div class="row">
                  <div class="col-md-6">
                    <div class="field">
                      <strong><i class="bi bi-calendar-date text-primary me-2"></i> Date of Birth:</strong>
                      <?php echo htmlspecialchars($profile['dob'] ?? 'N/A'); ?>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="field">
                      <strong><i class="bi bi-briefcase text-primary me-2"></i> Experience:</strong>
                      <?php echo ucfirst(htmlspecialchars($profile['experience'] ?? 'N/A')); ?>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="field">
                      <strong><i class="bi bi-geo-alt-fill text-danger me-2"></i> Current Address:</strong>
                      <?php echo nl2br(htmlspecialchars($profile['current_address'] ?? 'N/A')); ?>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="field">
                      <strong><i class="bi bi-house-fill text-success me-2"></i> Permanent Address:</strong>
                      <?php echo nl2br(htmlspecialchars($profile['permanent_address'] ?? 'N/A')); ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="kyc">
                <h5 class="section-title">🪪 KYC & Bank Documents</h5>
                <div class="row">
                  <?php
                  $kycDocs = [
                    'aadhar_doc'   => ['label' => 'Aadhar Card',      'icon' => 'bi-card-text'],
                    'pan_doc'      => ['label' => 'PAN Card',          'icon' => 'bi-card-text'],
                    'cheque_doc'   => ['label' => 'Cancelled Cheque',  'icon' => 'bi-cash'],
                    'passbook_doc' => ['label' => 'Passbook',          'icon' => 'bi-journal-text'],
                  ];
                  foreach ($kycDocs as $field => $info):
                    $url = buildFileURL($profile[$field] ?? '', $baseURL);
                  ?>
                    <div class="col-md-6">
                      <div class="field">
                        <strong>
                          <i class="bi <?php echo $info['icon']; ?> text-primary me-2"></i>
                          <?php echo $info['label']; ?>:
                        </strong><br>
                        <?php if ($url): ?>
                          <a href="<?php echo htmlspecialchars($url); ?>"
                             class="btn btn-sm btn-outline-primary document-link mt-2 view-doc-btn"
                             target="_blank">
                            <i class="bi bi-eye"></i> <span>View Document</span>
                          </a>
                        <?php else: ?>
                          <span class="badge bg-secondary mt-2">Not Uploaded</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <?php if (($profile['experience'] ?? '') === 'experienced'): ?>
              <div class="tab-pane fade" id="expDocs">
                <h5 class="section-title">💼 Experience Documents</h5>
                <div class="row">
                  <?php
                  $expDocs = [
                    'offer_letter_doc'     => ['label' => 'Offer Letter',     'icon' => 'bi-file-earmark-text'],
                    'relieving_letter_doc' => ['label' => 'Relieving Letter', 'icon' => 'bi-file-earmark-text'],
                    'salary_slip_doc'      => ['label' => 'Salary Slip',      'icon' => 'bi-file-earmark-text'],
                    'up_rehire_mail_doc'   => ['label' => 'Rehire Mail',      'icon' => 'bi-envelope'],
                  ];
                  foreach ($expDocs as $field => $info):
                    $url = buildFileURL($profile[$field] ?? '', $baseURL);
                  ?>
                    <div class="col-md-6">
                      <div class="field">
                        <strong>
                          <i class="bi <?php echo $info['icon']; ?> text-warning me-2"></i>
                          <?php echo $info['label']; ?>:
                        </strong><br>
                        <?php if ($url): ?>
                          <a href="<?php echo htmlspecialchars($url); ?>"
                             class="btn btn-sm btn-outline-warning document-link mt-2"
                             target="_blank">
                            <i class="bi bi-eye"></i> View Document
                          </a>
                        <?php else: ?>
                          <span class="badge bg-secondary mt-2">Not Uploaded</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>

              <div class="tab-pane fade" id="education">
                <h5 class="section-title">🎓 Education Details</h5>
                <div class="row">
                  <div class="col-md-6">
                    <div class="field">
                      <strong>
                        <i class="bi bi-mortarboard-fill text-primary me-2"></i> Qualification:
                      </strong>
                      <?php echo htmlspecialchars(eduLabel($profile['education'] ?? '')); ?>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="field">
                      <strong>
                        <i class="bi bi-file-earmark-text text-primary me-2"></i> Marksheet:
                      </strong><br>
                      <?php $url = buildFileURL($profile['marksheet_doc'] ?? '', $baseURL); ?>
                      <?php if ($url): ?>
                        <a href="<?php echo htmlspecialchars($url); ?>"
                           class="btn btn-sm btn-outline-primary document-link mt-2"
                           target="_blank">
                          <i class="bi bi-eye"></i> View Document
                        </a>
                      <?php else: ?>
                        <span class="badge bg-secondary mt-2">Not Uploaded</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="emergency">
                <h5 class="section-title">🚨 Emergency Contact Details</h5>
                <?php
                $relation = $profile['contact_relation'] ?? '';
                if ($relation === 'father') {
                  $ref1Label = 'Father';
                  $ref1Name  = $profile['father_name']    ?? '';
                  $ref1Phone = $profile['father_contact'] ?? '';
                  $ref1Addr  = $profile['father_address'] ?? '';
                  $ref1Rel   = 'Father';
                } elseif ($relation === 'mother') {
                  $ref1Label = 'Mother';
                  $ref1Name  = $profile['mother_name']    ?? '';
                  $ref1Phone = $profile['mother_contact'] ?? '';
                  $ref1Addr  = $profile['mother_address'] ?? '';
                  $ref1Rel   = 'Mother';
                } else {
                  $ref1Label = 'Primary';
                  $ref1Name  = '';
                  $ref1Phone = '';
                  $ref1Addr  = '';
                  $ref1Rel   = ucfirst($relation ?: 'N/A');
                }
                $ref2Name  = $profile['other_name']     ?? '';
                $ref2Rel   = $profile['other_relation'] ?? '';
                $ref2Phone = $profile['other_contact']  ?? '';
                $ref2Addr  = $profile['other_address']  ?? '';
                ?>

                <div class="ref-card">
                  <div class="ref-header ref1">
                    <i class="bi bi-person-fill me-2"></i>
                    Reference 1 — <?php echo htmlspecialchars($ref1Label); ?>
                  </div>
                  <div class="ref-body">
                    <?php if (!empty($ref1Name) || !empty($ref1Phone)): ?>
                      <div class="row">
                        <div class="col-md-4">
                          <div class="field">
                            <strong><i class="bi bi-person text-primary me-1"></i> Name:</strong><br>
                            <?php echo htmlspecialchars($ref1Name ?: '—'); ?>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="field">
                            <strong><i class="bi bi-telephone text-success me-1"></i> Contact:</strong><br>
                            <?php if (!empty($ref1Phone)): ?>
                              <a href="tel:<?php echo htmlspecialchars($ref1Phone); ?>"
                                 class="text-decoration-none text-info">
                                <?php echo htmlspecialchars($ref1Phone); ?>
                              </a>
                            <?php else: ?>
                              <span class="text-muted">—</span>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="field">
                            <strong><i class="bi bi-geo-alt text-danger me-1"></i> Address:</strong><br>
                            <?php echo nl2br(htmlspecialchars($ref1Addr ?: '—')); ?>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="field">
                            <strong><i class="bi bi-diagram-2 text-info me-1"></i> Relation:</strong><br>
                            <span class="badge bg-info mt-1">
                              <?php echo htmlspecialchars($ref1Rel); ?>
                            </span>
                          </div>
                        </div>
                      </div>
                    <?php else: ?>
                      <p class="text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i> No Reference 1 contact details found.
                      </p>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="ref-card">
                  <div class="ref-header ref2">
                    <i class="bi bi-person-lines-fill me-2"></i>
                    Reference 2 — Other Contact
                  </div>
                  <div class="ref-body">
                    <?php if (!empty($ref2Name) || !empty($ref2Phone)): ?>
                      <div class="row">
                        <div class="col-md-4">
                          <div class="field">
                            <strong><i class="bi bi-person text-primary me-1"></i> Name:</strong><br>
                            <?php echo htmlspecialchars($ref2Name ?: '—'); ?>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="field">
                            <strong><i class="bi bi-telephone text-success me-1"></i> Contact:</strong><br>
                            <?php if (!empty($ref2Phone)): ?>
                              <a href="tel:<?php echo htmlspecialchars($ref2Phone); ?>"
                                 class="text-decoration-none text-info">
                                <?php echo htmlspecialchars($ref2Phone); ?>
                              </a>
                            <?php else: ?>
                              <span class="text-muted">—</span>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="field">
                            <strong><i class="bi bi-geo-alt text-danger me-1"></i> Address:</strong><br>
                            <?php echo nl2br(htmlspecialchars($ref2Addr ?: '—')); ?>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="field">
                            <strong><i class="bi bi-diagram-2 text-info me-1"></i> Relation:</strong><br>
                            <span class="badge bg-success mt-1">
                              <?php echo htmlspecialchars(ucfirst($ref2Rel ?: 'Other')); ?>
                            </span>
                          </div>
                        </div>
                      </div>
                    <?php else: ?>
                      <p class="text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i> No Reference 2 contact details found.
                      </p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="social">
                <h5 class="section-title">🌐 Social Media Links</h5>

                <?php if (!empty($socialLinks)): ?>
                  <div class="row">
                    <?php foreach ($socialLinks as $link):
                      $info = socialIcon($link['platform']);
                    ?>
                      <div class="col-md-6">
                        <a href="<?php echo htmlspecialchars($link['url']); ?>"
                           target="_blank" class="social-card">
                          <div class="social-icon-wrap"
                               style="background-color: <?php echo $info['color']; ?>;">
                            <i class="bi <?php echo $info['icon']; ?>"></i>
                          </div>
                          <div class="social-card-info">
                            <strong>
                              <?php echo htmlspecialchars($info['label']); ?>
                              <?php if ($link['required']): ?>
                                <span class="social-badge-required">Compulsory</span>
                              <?php endif; ?>
                            </strong>
                            <span><?php echo htmlspecialchars($link['url']); ?></span>
                          </div>
                          <i class="bi bi-box-arrow-up-right ms-auto" style="color: var(--text-muted);"></i>
                        </a>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> No social media links added yet.
                  </div>
                <?php endif; ?>
              </div>
              </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <?php include '../includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>