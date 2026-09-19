admin_profile_details


<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
  header("Location: ../auth/login.php");
  exit();
}
$uid = intval($_GET['id'] ?? 0);
if ($uid <= 0) {
  header("Location: ../admin/admin_profiles.php");
  exit();
}
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$profiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
if (empty($profiles)) {
  header("Location: ../admin/admin_profiles.php?msg=notfound");
  exit();
}
$total = 1;
$freshers = count(array_filter($profiles, fn($r) => strtolower($r['experience'] ?? '') === 'fresher'));
$experienced = count(array_filter($profiles, fn($r) => strtolower($r['experience'] ?? '') === 'experienced'));
$conn->close();
// ── Helpers ──
// Build a root-relative URL for uploaded documents/photos. Handles every
// historical storage format (bare filename, "uploads/filename", a full
// filesystem path) as well as the newer per-user "folder/filename" format,
// so both old and new records display correctly.
function filePath($val)
{
  if (empty($val))
    return null;
  $normalized = str_replace('\\', '/', $val);
  $pos = strripos($normalized, '/uploads/');
  if ($pos !== false) {
    $normalized = substr($normalized, $pos + strlen('/uploads/'));
  } elseif (str_starts_with($normalized, 'uploads/')) {
    $normalized = substr($normalized, strlen('uploads/'));
  }
  return "/uploads/" . ltrim($normalized, '/');
}
function docCell($val, $color = '#1565c0', $bg = '#e3f2fd', $border = '#bbdefb')
{
  $path = filePath($val);
  if ($path) {
    return "<a href='" . htmlspecialchars($path) . "' target='_blank'
               class='doc-view-btn'
               style='color:{$color}; background:{$bg}; border-color:{$border};'>
               <i class='bi bi-eye-fill me-1'></i> View Document
            </a>";
  }
  return "<span class='not-uploaded'><i class='bi bi-x-circle me-1'></i>Not Uploaded</span>";
}
function eduLabel($val)
{
  $map = [
    '10th' => '10th',
    '12th' => '12th',
    'graduation' => 'Graduation',
    'post_graduation' => 'Post Grad',
    'diploma' => 'Diploma',
  ];
  return $map[$val] ?? ucfirst($val ?? '');
}
function socialIcon($platform)
{
  $platform = strtolower(trim($platform));
  $icons = [
    'instagram' => ['bi bi-instagram', '#E1306C', '#fce4ec', '#f48fb1'],
    'linkedin' => ['bi bi-linkedin', '#0077B5', '#e3f2fd', '#90caf9'],
    'facebook' => ['bi bi-facebook', '#1877F2', '#e8f0fe', '#90caf9'],
    'twitter' => ['bi bi-twitter-x', '#000000', '#f5f5f5', '#bdbdbd'],
    'youtube' => ['bi bi-youtube', '#FF0000', '#ffebee', '#ef9a9a'],
    'github' => ['bi bi-github', '#333333', '#f5f5f5', '#bdbdbd'],
    'whatsapp' => ['bi bi-whatsapp', '#25D366', '#e8f5e9', '#a5d6a7'],
    'telegram' => ['bi bi-telegram', '#0088cc', '#e3f2fd', '#90caf9'],
    'snapchat' => ['bi bi-snapchat', '#FFFC00', '#fffde7', '#fff176'],
    'pinterest' => ['bi bi-pinterest', '#E60023', '#ffebee', '#ef9a9a'],
    'threads' => ['bi bi-threads', '#000000', '#f5f5f5', '#bdbdbd'],
  ];
  return $icons[$platform] ?? ['bi bi-link-45deg', '#555555', '#f5f5f5', '#bdbdbd'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Details — Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    /* ── Base ── */
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background: #f0f2f5;
      font-family: 'Segoe UI', system-ui, sans-serif;
      color: #333;
      padding: 24px 20px 48px;
    }

    /* ── Page Header ── */
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 24px;
    }

    .page-title {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .page-title h2 {
      font-size: 21px;
      font-weight: 700;
      color: #1a1a2e;
      margin: 0;
    }

    .page-title .title-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #e53935, #b71c1c);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 18px;
      flex-shrink: 0;
    }

    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #fff;
      color: #555;
      border: 1px solid #e0e0e0;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.15s;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
    }

    .back-btn:hover {
      background: #f5f5f5;
      color: #222;
      border-color: #ccc;
    }

    /* ── Flash Message ── */
    .flash-msg {
      border-radius: 10px;
      padding: 13px 18px;
      font-size: 13px;
      margin-bottom: 20px;
      border-left: 4px solid;
      display: flex;
      align-items: center;
      gap: 10px;
      animation: fadeOut 0.5s ease 4s forwards;
    }

    @keyframes fadeOut {
      to {
        opacity: 0;
        pointer-events: none;
      }
    }

    .flash-success {
      background: #e8f5e9;
      color: #2e7d32;
      border-color: #43a047;
    }

    .flash-danger {
      background: #ffebee;
      color: #c62828;
      border-color: #e53935;
    }

    /* ── Info Banner ── */
    .info-banner {
      background: #fff8e1;
      border: 1px solid #ffe082;
      border-radius: 10px;
      padding: 12px 18px;
      margin-bottom: 22px;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .info-banner span {
      font-size: 13px;
      color: #666;
    }

    .info-banner strong {
      color: #1a1a1a;
    }

    /* ── Section Card ── */
    .section-card {
      background: #fff;
      border-radius: 14px;
      border: 1px solid #e8e8e8;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      margin-bottom: 22px;
      overflow: hidden;
    }

    .section-head {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 15px 20px;
      border-bottom: 1px solid #f0f0f0;
      background: #fafafa;
    }

    .section-head-icon {
      width: 34px;
      height: 34px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      flex-shrink: 0;
    }

    .section-head h5 {
      font-size: 15px;
      font-weight: 700;
      color: #1a1a1a;
      margin: 0;
    }

    .section-body {
      padding: 22px;
    }

    /* ── Profile Hero ── */
    .profile-hero {
      display: flex;
      align-items: flex-start;
      gap: 24px;
      flex-wrap: wrap;
    }

    .profile-avatar {
      flex-shrink: 0;
    }

    .avatar-img {
      width: 120px;
      height: 120px;
      border-radius: 16px;
      object-fit: cover;
      border: 3px solid #e8e8e8;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
    }

    .avatar-placeholder {
      width: 120px;
      height: 120px;
      border-radius: 16px;
      background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
      border: 3px solid #e8e8e8;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 48px;
      color: #bbb;
    }

    .profile-info {
      flex: 1;
      min-width: 0;
    }

    .profile-name {
      font-size: 22px;
      font-weight: 700;
      color: #1a1a1a;
      margin-bottom: 6px;
    }

    .profile-email {
      font-size: 14px;
      color: #777;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .profile-badges {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 18px;
    }

    /* ── Info Grid ── */
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 14px;
      margin-top: 6px;
    }

    .info-item {
      background: #f8f9fa;
      border: 1px solid #f0f0f0;
      border-radius: 10px;
      padding: 12px 14px;
    }

    .info-item-label {
      font-size: 10px;
      font-weight: 700;
      color: #aaa;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      margin-bottom: 5px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .info-item-value {
      font-size: 13px;
      font-weight: 600;
      color: #222;
    }

    /* ── Badges ── */
    .badge-fresher {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #e3f2fd;
      color: #1565c0;
      border: 1px solid #bbdefb;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
    }

    .badge-experienced {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #fff3e0;
      color: #e65100;
      border: 1px solid #ffe0b2;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
    }

    .badge-edu {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #f3e5f5;
      color: #6a1b9a;
      border: 1px solid #e1bee7;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
    }

    /* ── Address Cards ── */
    .address-card {
      background: #f8f9fa;
      border: 1px solid #eeeeee;
      border-radius: 12px;
      padding: 18px;
      height: 100%;
    }

    .address-card-title {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .address-text {
      font-size: 13px;
      color: #444;
      line-height: 1.7;
    }

    /* ── Document Cards ── */
    .doc-group-title {
      font-size: 12px;
      font-weight: 700;
      color: #888;
      text-transform: uppercase;
      letter-spacing: 0.7px;
      margin-bottom: 14px;
      padding-bottom: 8px;
      border-bottom: 2px solid #f0f0f0;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .doc-card {
      background: #f8f9fa;
      border: 1px solid #eeeeee;
      border-radius: 12px;
      padding: 16px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 10px;
      text-align: center;
      height: 100%;
      transition: box-shadow 0.2s, transform 0.2s;
    }

    .doc-card:hover {
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
      transform: translateY(-2px);
    }

    .doc-card-icon {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    .doc-card-title {
      font-size: 12px;
      font-weight: 700;
      color: #444;
    }

    .doc-view-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      border: 1.5px solid;
      text-decoration: none;
      transition: all 0.15s;
      white-space: nowrap;
    }

    .doc-view-btn:hover {
      filter: brightness(0.88);
      transform: translateY(-1px);
    }

    .not-uploaded {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      color: #bbb;
      font-weight: 600;
    }

    /* ── Contact Cards ── */
    .contact-card {
      background: #f8f9fa;
      border: 1px solid #eee;
      border-radius: 12px;
      padding: 18px;
      height: 100%;
    }

    .contact-card-title {
      font-size: 13px;
      font-weight: 700;
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .contact-row {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 9px 0;
      border-bottom: 1px solid #f0f0f0;
      font-size: 13px;
    }

    .contact-row:last-child {
      border-bottom: none;
    }

    .contact-label {
      font-size: 11px;
      font-weight: 700;
      color: #aaa;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      min-width: 80px;
      padding-top: 1px;
    }

    .contact-value {
      color: #333;
      font-weight: 500;
      flex: 1;
      line-height: 1.5;
    }

    /* ── Social Profiles ── */
    .social-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 14px;
    }

    .social-card {
      border: 1.5px solid;
      border-radius: 12px;
      padding: 16px 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      transition: all 0.2s;
    }

    .social-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
      filter: brightness(0.93);
    }

    .social-card-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      flex-shrink: 0;
    }

    .social-card-info {
      min-width: 0;
    }

    .social-card-name {
      font-size: 13px;
      font-weight: 700;
      display: block;
    }

    .social-card-open {
      font-size: 10px;
      font-weight: 600;
      opacity: 0.7;
      display: flex;
      align-items: center;
      gap: 3px;
      margin-top: 2px;
    }

    .no-social {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
      color: #ccc;
      gap: 10px;
      text-align: center;
    }

    .no-social i {
      font-size: 42px;
      color: #e0e0e0;
    }

    .no-social span {
      font-size: 14px;
      color: #bbb;
      font-weight: 600;
    }

    /* ── Action Buttons ── */
    .action-section {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      padding: 20px 22px;
    }

    .btn-edit {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: linear-gradient(135deg, #ff9800, #f57c00);
      color: #fff;
      border: none;
      padding: 11px 22px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.15s;
      box-shadow: 0 3px 10px rgba(255, 152, 0, 0.3);
    }

    .btn-edit:hover {
      background: linear-gradient(135deg, #f57c00, #e65100);
      color: #fff;
      box-shadow: 0 5px 16px rgba(255, 152, 0, 0.4);
      transform: translateY(-1px);
    }

    .btn-del {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: linear-gradient(135deg, #e53935, #b71c1c);
      color: #fff;
      border: none;
      padding: 11px 22px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.15s;
      box-shadow: 0 3px 10px rgba(229, 57, 53, 0.3);
    }

    .btn-del:hover {
      background: linear-gradient(135deg, #b71c1c, #7f0000);
      box-shadow: 0 5px 16px rgba(229, 57, 53, 0.4);
      transform: translateY(-1px);
    }

    /* ── Modal ── */
    .modal-content {
      border-radius: 16px;
      border: none;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
      overflow: hidden;
    }

    .modal-header.hdr-red {
      background: linear-gradient(135deg, #c62828, #e53935);
      padding: 16px 20px;
      border: none;
    }

    .modal-title {
      font-weight: 700;
      color: #fff;
      font-size: 15px;
    }

    .modal-body {
      padding: 28px 24px;
      background: #fff;
    }

    .modal-footer {
      border-top: 1px solid #f0f0f0;
      padding: 14px 20px;
      background: #fafafa;
    }

    /* ── Divider ── */
    .section-divider {
      border: none;
      border-top: 2px dashed #f0f0f0;
      margin: 20px 0;
    }

    /* ── Responsive ── */
    @media (max-width: 576px) {
      body {
        padding: 16px 12px 40px;
      }

      .profile-hero {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }

      .profile-badges {
        justify-content: center;
      }

      .info-grid {
        grid-template-columns: 1fr 1fr;
      }

      .social-grid {
        grid-template-columns: 1fr 1fr;
      }

      .action-section {
        flex-direction: column;
      }

      .btn-edit,
      .btn-del {
        width: 100%;
        justify-content: center;
      }
    }

    @media (max-width: 400px) {
      .info-grid {
        grid-template-columns: 1fr;
      }

      .social-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <!-- ══════════════════════════════
     PAGE HEADER
══════════════════════════════ -->
  <div class="page-header">
    <div class="page-title">
      <div class="title-icon"><i class="bi bi-person-lines-fill"></i></div>
      <h2>Profile Details</h2>
    </div>
    <a href="../admin/admin_profiles.php" class="back-btn">
      <i class="bi bi-arrow-left"></i> Back to Profiles
    </a>
  </div>
  <!-- ══════════════════════════════
     FLASH MESSAGES
══════════════════════════════ -->
  <?php
  $msgMap = [
    'deleted' => ['success', 'Profile deleted successfully!'],
    'updated' => ['success', 'Profile updated successfully!'],
    'invalid' => ['danger', 'Invalid Profile ID!'],
    'notfound' => ['danger', 'Profile not found!'],
    'error' => ['danger', 'Operation failed! Please try again.'],
  ];
  if (isset($_GET['msg']) && isset($msgMap[$_GET['msg']])):
    [$type, $text] = $msgMap[$_GET['msg']];
    $icon = $type === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
    ?>
    <div class="flash-msg flash-<?= $type ?>">
      <i class="bi <?= $icon ?>" style="font-size:17px; flex-shrink:0;"></i>
      <div><?= htmlspecialchars($text) ?></div>
    </div>
  <?php endif; ?>
  <!-- ══════════════════════════════
     INFO BANNER
══════════════════════════════ -->
  <div class="info-banner">
    <i class="bi bi-person-check-fill" style="color:#f9a825; font-size:18px; flex-shrink:0;"></i>
    <span>Viewing profile of:
      <strong><?= htmlspecialchars($profiles[0]['user_name'] ?? 'Unknown') ?></strong>
    </span>
    <a href="../admin/admin_profiles.php" class="back-btn" style="margin-left:auto;">
      <i class="bi bi-arrow-left"></i> All Profiles
    </a>
  </div>
  <?php
  /* ════════════════════════════════
     ROW DATA & COMPUTED FIELDS
  ════════════════════════════════ */
  $row = $profiles[0];
  $photoSrc = !empty($row['profile_photo']) ? htmlspecialchars(filePath($row['profile_photo'])) : null;
  $exp = strtolower(trim($row['experience'] ?? ''));
  if ($exp === 'fresher') {
    $expBadge = "<span class='badge-fresher'><i class='bi bi-mortarboard-fill'></i> Fresher</span>";
  } elseif ($exp === 'experienced') {
    $expBadge = "<span class='badge-experienced'><i class='bi bi-briefcase-fill'></i> Experienced</span>";
  } else {
    $expBadge = "<span style='color:#bbb;font-size:13px;'>—</span>";
  }
  $eduBadge = "<span class='badge-edu'><i class='bi bi-book-fill'></i> " . htmlspecialchars(eduLabel($row['education'])) . "</span>";
  // Emergency Contact
  $relation = $row['contact_relation'] ?? '';
  if ($relation === 'father') {
    $ref1Relation = 'Father';
    $ref1Name = $row['father_name'] ?? '';
    $ref1Phone = $row['father_contact'] ?? '';
    $ref1Address = $row['father_address'] ?? '';
  } elseif ($relation === 'mother') {
    $ref1Relation = 'Mother';
    $ref1Name = $row['mother_name'] ?? '';
    $ref1Phone = $row['mother_contact'] ?? '';
    $ref1Address = $row['mother_address'] ?? '';
  } else {
    $ref1Relation = ucfirst($relation);
    $ref1Name = $ref1Phone = $ref1Address = '';
  }
  $ref2Relation = ucfirst($row['other_relation'] ?? '');
  $ref2Name = $row['other_name'] ?? '';
  $ref2Phone = $row['other_contact'] ?? '';
  $ref2Address = $row['other_address'] ?? '';
  // Social Data
  $socialData = [];
  $p1 = trim($row['social_platform_1'] ?? '');
  $u1 = trim($row['social_url_1'] ?? '');
  if (!empty($p1) && !empty($u1))
    $socialData[] = ['platform' => $p1, 'url' => $u1];
  $u2 = trim($row['social_url_2'] ?? '');
  if (!empty($u2))
    $socialData[] = ['platform' => 'linkedin', 'url' => $u2];
  $extraPlatforms = json_decode($row['social_platform_extra'] ?? '[]', true) ?: [];
  $extraUrls = json_decode($row['social_url_extra'] ?? '[]', true) ?: [];
  foreach ($extraPlatforms as $idx => $ep) {
    $eu = $extraUrls[$idx] ?? '';
    if (!empty($ep) && !empty($eu))
      $socialData[] = ['platform' => $ep, 'url' => $eu];
  }
  ?>
  <!-- ══════════════════════════════
     1. PERSONAL INFORMATION
══════════════════════════════ -->
  <div class="section-card">
    <div class="section-head">
      <div class="section-head-icon" style="background:#fce4ec; color:#c62828;">
        <i class="bi bi-person-fill"></i>
      </div>
      <h5>Personal Information</h5>
    </div>
    <div class="section-body">
      <div class="profile-hero">
        <!-- Avatar -->
        <div class="profile-avatar">
          <?php if ($photoSrc): ?>
            <a href="<?= $photoSrc ?>" target="_blank">
              <img src="<?= $photoSrc ?>" class="avatar-img" alt="Profile Photo">
            </a>
          <?php else: ?>
            <div class="avatar-placeholder">
              <i class="bi bi-person-fill"></i>
            </div>
          <?php endif; ?>
        </div>
        <!-- Name + Email + Badges -->
        <div class="profile-info">
          <div class="profile-name"><?= htmlspecialchars($row['user_name']) ?></div>
          <div class="profile-email">
            <i class="bi bi-envelope-fill" style="color:#e53935;"></i>
            <?= htmlspecialchars($row['email']) ?>
          </div>
          <div class="profile-badges">
            <?= $expBadge ?>
            <?= $eduBadge ?>
          </div>
          <!-- Info Grid -->
          <div class="info-grid">
            <div class="info-item">
              <div class="info-item-label">
                <i class="bi bi-telephone-fill" style="color:#e53935;"></i> Contact
              </div>
              <div class="info-item-value">
                <?= htmlspecialchars($row['contact_no'] ?: '—') ?>
              </div>
            </div>
            <div class="info-item">
              <div class="info-item-label">
                <i class="bi bi-calendar-fill" style="color:#e53935;"></i> Date of Birth
              </div>
              <div class="info-item-value">
                <?= htmlspecialchars($row['dob'] ?: '—') ?>
              </div>
            </div>
            <div class="info-item">
              <div class="info-item-label">
                <i class="bi bi-clock-fill" style="color:#e53935;"></i> Joined
              </div>
              <div class="info-item-value">
                <?= !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '—' ?>
              </div>
            </div>
            <div class="info-item">
              <div class="info-item-label">
                <i class="bi bi-hash" style="color:#e53935;"></i> User ID
              </div>
              <div class="info-item-value">#<?= intval($row['user_id']) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- ══════════════════════════════
     2. ADDRESS DETAILS
══════════════════════════════ -->
  <div class="section-card">
    <div class="section-head">
      <div class="section-head-icon" style="background:#e8f5e9; color:#2e7d32;">
        <i class="bi bi-geo-alt-fill"></i>
      </div>
      <h5>Address Details</h5>
    </div>
    <div class="section-body">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="address-card">
            <div class="address-card-title" style="color:#1565c0;">
              <i class="bi bi-house-fill"></i> Current Address
            </div>
            <div class="address-text">
              <?= nl2br(htmlspecialchars($row['current_address'] ?: '—')) ?>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="address-card">
            <div class="address-card-title" style="color:#2e7d32;">
              <i class="bi bi-house-check-fill"></i> Permanent Address
            </div>
            <div class="address-text">
              <?= nl2br(htmlspecialchars($row['permanent_address'] ?: '—')) ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- ══════════════════════════════
     3. DOCUMENTS
══════════════════════════════ -->
  <div class="section-card">
    <div class="section-head">
      <div class="section-head-icon" style="background:#e3f2fd; color:#1565c0;">
        <i class="bi bi-folder2-open"></i>
      </div>
      <h5>Documents</h5>
    </div>
    <div class="section-body">
      <!-- Identity Documents -->
      <div class="doc-group-title">
        <i class="bi bi-person-vcard-fill" style="color:#1565c0;"></i>
        Identity Documents
      </div>
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="doc-card">
            <div class="doc-card-icon" style="background:#e3f2fd; color:#1565c0;">
              <i class="bi bi-person-vcard-fill"></i>
            </div>
            <div class="doc-card-title">Aadhaar Card</div>
            <?= docCell($row['aadhar_doc']) ?>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="doc-card">
            <div class="doc-card-icon" style="background:#e8f5e9; color:#2e7d32;">
              <i class="bi bi-credit-card-fill"></i>
            </div>
            <div class="doc-card-title">PAN Card</div>
            <?= docCell($row['pan_doc'], '#2e7d32', '#e8f5e9', '#c8e6c9') ?>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="doc-card">
            <div class="doc-card-icon" style="background:#f3e5f5; color:#6a1b9a;">
              <i class="bi bi-bank"></i>
            </div>
            <div class="doc-card-title">Cancelled Cheque</div>
            <?= docCell($row['cheque_doc'], '#6a1b9a', '#f3e5f5', '#e1bee7') ?>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="doc-card">
            <div class="doc-card-icon" style="background:#fff8e1; color:#f57f17;">
              <i class="bi bi-journal-bookmark-fill"></i>
            </div>
            <div class="doc-card-title">Passbook</div>
            <?= docCell($row['passbook_doc'], '#f57f17', '#fff8e1', '#ffe082') ?>
          </div>
        </div>
      </div>
      <hr class="section-divider">
      <!-- Employment Documents -->
      <div class="doc-group-title">
        <i class="bi bi-briefcase-fill" style="color:#e65100;"></i>
        Employment Documents
      </div>
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="doc-card">
            <div class="doc-card-icon" style="background:#fff3e0; color:#e65100;">
              <i class="bi bi-file-earmark-text-fill"></i>
            </div>
            <div class="doc-card-title">Offer Letter</div>
            <?= docCell($row['offer_letter_doc'], '#e65100', '#fff3e0', '#ffe0b2') ?>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="doc-card">
            <div class="doc-card-icon" style="background:#fff3e0; color:#e65100;">
              <i class="bi bi-file-earmark-check-fill"></i>
            </div>
            <div class="doc-card-title">Relieving Letter</div>
            <?= docCell($row['relieving_letter_doc'], '#e65100', '#fff3e0', '#ffe0b2') ?>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="doc-card">
            <div class="doc-card-icon" style="background:#fff3e0; color:#e65100;">
              <i class="bi bi-receipt"></i>
            </div>
            <div class="doc-card-title">Salary Slip</div>
            <?= docCell($row['salary_slip_doc'], '#e65100', '#fff3e0', '#ffe0b2') ?>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="doc-card">
            <div class="doc-card-icon" style="background:#fff3e0; color:#e65100;">
              <i class="bi bi-envelope-fill"></i>
            </div>
            <div class="doc-card-title">Rehire Mail</div>
            <?= docCell($row['up_rehire_mail_doc'], '#e65100', '#fff3e0', '#ffe0b2') ?>
          </div>
        </div>
      </div>
      <hr class="section-divider">
      <!-- Education Documents -->
      <div class="doc-group-title">
        <i class="bi bi-mortarboard-fill" style="color:#2e7d32;"></i>
        Education Documents
      </div>
      <div class="row g-3">
        <div class="col-6 col-md-3">
          <div class="doc-card">
            <div class="doc-card-icon" style="background:#e8f5e9; color:#2e7d32;">
              <i class="bi bi-file-earmark-ruled-fill"></i>
            </div>
            <div class="doc-card-title">Marksheet</div>
            <?= docCell($row['marksheet_doc'], '#2e7d32', '#e8f5e9', '#c8e6c9') ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- ══════════════════════════════
     4. EMERGENCY CONTACTS
══════════════════════════════ -->
  <div class="section-card">
    <div class="section-head">
      <div class="section-head-icon" style="background:#fce4ec; color:#ad1457;">
        <i class="bi bi-person-heart"></i>
      </div>
      <h5>Emergency Contacts</h5>
    </div>
    <div class="section-body">
      <div class="row g-3">
        <!-- Primary -->
        <div class="col-md-6">
          <div class="contact-card">
            <div class="contact-card-title" style="color:#1565c0;">
              <i class="bi bi-person-fill-check"></i> Primary Contact
            </div>
            <div class="contact-row">
              <span class="contact-label">Relation</span>
              <span class="contact-value"><?= htmlspecialchars($ref1Relation ?: '—') ?></span>
            </div>
            <div class="contact-row">
              <span class="contact-label">Name</span>
              <span class="contact-value"><?= htmlspecialchars($ref1Name ?: '—') ?></span>
            </div>
            <div class="contact-row">
              <span class="contact-label">Phone</span>
              <span class="contact-value">
                <?php if (!empty($ref1Phone)): ?>
                  <a href="tel:<?= htmlspecialchars($ref1Phone) ?>" style="color:#1565c0; text-decoration:none;">
                    <i class="bi bi-telephone-fill me-1"></i><?= htmlspecialchars($ref1Phone) ?>
                  </a>
                <?php else: ?>—<?php endif; ?>
              </span>
            </div>
            <div class="contact-row">
              <span class="contact-label">Address</span>
              <span class="contact-value">
                <?= nl2br(htmlspecialchars($ref1Address ?: '—')) ?>
              </span>
            </div>
          </div>
        </div>
        <!-- Secondary -->
        <div class="col-md-6">
          <div class="contact-card">
            <div class="contact-card-title" style="color:#2e7d32;">
              <i class="bi bi-person-fill-add"></i> Secondary Contact
            </div>
            <div class="contact-row">
              <span class="contact-label">Relation</span>
              <span class="contact-value"><?= htmlspecialchars($ref2Relation ?: '—') ?></span>
            </div>
            <div class="contact-row">
              <span class="contact-label">Name</span>
              <span class="contact-value"><?= htmlspecialchars($ref2Name ?: '—') ?></span>
            </div>
            <div class="contact-row">
              <span class="contact-label">Phone</span>
              <span class="contact-value">
                <?php if (!empty($ref2Phone)): ?>
                  <a href="tel:<?= htmlspecialchars($ref2Phone) ?>" style="color:#2e7d32; text-decoration:none;">
                    <i class="bi bi-telephone-fill me-1"></i><?= htmlspecialchars($ref2Phone) ?>
                  </a>
                <?php else: ?>—<?php endif; ?>
              </span>
            </div>
            <div class="contact-row">
              <span class="contact-label">Address</span>
              <span class="contact-value">
                <?= nl2br(htmlspecialchars($ref2Address ?: '—')) ?>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- ══════════════════════════════
     5. SOCIAL PROFILES
══════════════════════════════ -->
  <div class="section-card">
    <div class="section-head">
      <div class="section-head-icon" style="background:#e8f0fe; color:#1877F2;">
        <i class="bi bi-share-fill"></i>
      </div>
      <h5>Social Profiles</h5>
    </div>
    <div class="section-body">
      <?php if (!empty($socialData)): ?>
        <div class="social-grid">
          <?php foreach ($socialData as $social): ?>
            <?php
            [$icon, $color, $bg, $border] = socialIcon($social['platform']);
            $label = ucfirst($social['platform']);
            ?>
            <a href="<?= htmlspecialchars($social['url']) ?>" target="_blank" class="social-card"
              style="background:<?= $bg ?>; border-color:<?= $border ?>; color:<?= $color ?>;">
              <div class="social-card-icon">
                <i class="<?= $icon ?>"></i>
              </div>
              <div class="social-card-info">
                <span class="social-card-name"><?= htmlspecialchars($label) ?></span>
                <span class="social-card-open">
                  <i class="bi bi-box-arrow-up-right"></i> Open Profile
                </span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="no-social">
          <i class="bi bi-slash-circle"></i>
          <span>No Social Profiles Available</span>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <!-- ══════════════════════════════
     6. ACTIONS
══════════════════════════════ -->
  <div class="section-card">
    <div class="section-head">
      <div class="section-head-icon" style="background:#f5f5f5; color:#555;">
        <i class="bi bi-gear-fill"></i>
      </div>
      <h5>Actions</h5>
    </div>
    <div class="action-section">
      <a href="../profile/edit_profile.php?id=<?= intval($row['user_id']) ?>" class="btn-edit">
        <i class="bi bi-pencil-fill"></i> Edit Profile
      </a>
      <a href="export_profile_excel.php?id=<?= intval($row['user_id']) ?>" class="btn-edit"
        style="background:linear-gradient(135deg,#2e7d32,#1b5e20); box-shadow:0 3px 10px rgba(46,125,50,0.3);">
        <i class="bi bi-file-earmark-excel-fill"></i> Download Excel
      </a>
      <a href="export_profile_pdf.php?id=<?= intval($row['user_id']) ?>" class="btn-edit"
        style="background:linear-gradient(135deg,#c62828,#8e0000); box-shadow:0 3px 10px rgba(198,40,40,0.3);">
        <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
      </a>
      <button class="btn-del" onclick="confirmDelete(<?= intval($row['user_id']) ?>)">
        <i class="bi bi-trash-fill"></i> Delete Profile
      </button>
    </div>
  </div>
  <!-- ══════════════════════════════
     DELETE CONFIRM MODAL
══════════════════════════════ -->
  <div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header hdr-red">
          <h5 class="modal-title">
            <i class="bi bi-trash-fill me-2"></i>Delete Profile
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <div style="width:56px; height:56px; background:#ffebee; border-radius:50%;
                display:flex; align-items:center; justify-content:center;
                margin:0 auto 16px; font-size:24px; color:#e53935;">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <p style="color:#444; font-size:14px; line-height:1.7;">
            Are you sure you want to<br>
            <strong style="color:#1a1a1a;">delete this profile?</strong>
          </p>
          <p style="color:#bbb; font-size:12px; margin-top:8px;">
            This action cannot be undone.
          </p>
        </div>
        <div class="modal-footer justify-content-center gap-2">
          <a href="#" id="confirmDeleteBtn" class="btn btn-danger btn-sm px-4">
            <i class="bi bi-trash-fill me-1"></i> Yes, Delete
          </a>
          <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
  <!-- ── Scripts ── -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    function confirmDelete(id) {
      document.getElementById('confirmDeleteBtn').href = '../profile/delete_profile.php?id=' + id;
      deleteModal.show();
    }
  </script>
</body>

</html>