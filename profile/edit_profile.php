<?php
session_start();
include '../includes/db_connect.php';
// ── Dual Auth — Admin OR allowed User ──
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isUser  = isset($_SESSION['user_email']);
if (!$isAdmin && !$isUser) {
    header("Location: ../auth/login.php");
    exit();
}
// ── Fetch Profile ──
if ($isAdmin && isset($_GET['id'])) {
    $profileId = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $profileId);
} else {
    $userEmail = $_SESSION['user_email'];
    $permStmt = $conn->prepare("SELECT edit_allowed FROM users WHERE email = ?");
    $permStmt->bind_param("s", $userEmail);
    $permStmt->execute();
    $permRow = $permStmt->get_result()->fetch_assoc();
    $permStmt->close();
    if (empty($permRow['edit_allowed']) || $permRow['edit_allowed'] != 1) {
        header("Location: ../profile/view_profile.php");
        exit();
    }
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE email = ?");
    $stmt->bind_param("s", $userEmail);
}
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$profile) {
    if ($isAdmin) {
        header("Location: ../admin/admin_profile_details.php?msg=notfound");
    } else {
        header("Location: ../profile/profile_form.php");
    }
    exit();
}
// ── Helpers ──
function cleanPath($val) {
    if (empty($val)) return '';
    $val = str_replace('uploads/', '', $val);
    return basename($val);
}
// Build a root-relative URL for uploaded documents/photos. Handles every
// historical storage format as well as the newer per-user
// "folder/filename" format, so both old and new records display correctly.
function buildURL($val) {
    if (empty($val)) return '';
    $normalized = str_replace('\\', '/', $val);
    $pos = strripos($normalized, '/uploads/');
    if ($pos !== false) {
        $normalized = substr($normalized, $pos + strlen('/uploads/'));
    } elseif (str_starts_with($normalized, 'uploads/')) {
        $normalized = substr($normalized, strlen('uploads/'));
    }
    return '/uploads/' . ltrim($normalized, '/');
}
// ── Flash Messages ──
$successMsg = $_SESSION['update_success'] ?? '';
$errorMsg   = $_SESSION['update_error']   ?? '';
// ✅ Also read edit_errors from update_profile.php
$editErrors = $_SESSION['edit_errors'] ?? [];
unset($_SESSION['update_success'], $_SESSION['update_error'], $_SESSION['edit_errors']);
// ── Back URL ──
$backURL = $isAdmin ? '../admin/admin_profile_details.php' : '../profile/view_profile.php';
// ── Social Profiles — existing values (needed because the form previously had no fields for these) ──
$socialPlatform1 = $profile['social_platform_1'] ?? '';
$socialUrl1      = $profile['social_url_1'] ?? '';
$socialUrl2      = $profile['social_url_2'] ?? '';
$extraPlatforms  = json_decode($profile['social_platform_extra'] ?? '[]', true) ?: [];
$extraUrls       = json_decode($profile['social_url_extra'] ?? '[]', true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #f5f6fa;
      color: #333;
      font-family: 'Segoe UI', sans-serif;
      padding: 24px 20px;
    }
    .page-header {
      display: flex; align-items: center;
      justify-content: space-between;
      flex-wrap: wrap; gap: 10px;
      margin-bottom: 24px;
    }
    .page-header-left { display: flex; align-items: center; gap: 10px; }
    .page-header h2  { font-size: 20px; font-weight: 700; color: #1a1a1a; margin: 0; }
    .title-icon      { font-size: 22px; color: #e53935; }
    .back-btn {
      display: inline-flex; align-items: center; gap: 6px;
      background: #f0f0f0; color: #555; border: 1px solid #e0e0e0;
      padding: 7px 14px; border-radius: 8px;
      font-size: 12px; font-weight: 600;
      text-decoration: none; transition: all 0.15s;
    }
    .back-btn:hover { background: #e0e0e0; color: #333; }
    .msg-box {
      border-radius: 10px; padding: 12px 16px;
      font-size: 13px; margin-bottom: 20px;
      border-left: 4px solid;
      display: flex; align-items: flex-start; gap: 10px;
    }
    .msg-success { background: #e8f5e9; color: #2e7d32; border-color: #43a047; }
    .msg-error   { background: #ffebee; color: #c62828; border-color: #e53935; }
    .msg-error ul { margin: 6px 0 0 0; padding-left: 18px; }
    .msg-error ul li { margin-bottom: 3px; font-size: 12px; }
    .section-card {
      background: #fff; border-radius: 14px;
      border: 1px solid #e8e8e8;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      margin-bottom: 18px; overflow: hidden;
    }
    .section-hdr {
      padding: 12px 20px;
      border-bottom: 1px solid #f3f3f3;
      display: flex; align-items: center; gap: 8px;
      background: #fafafa;
    }
    .section-hdr i  { font-size: 15px; color: #e53935; }
    .section-hdr h3 { font-size: 13px; font-weight: 700; color: #1a1a1a; margin: 0; }
    .section-body   { padding: 20px 24px; }
    .form-label {
      color: #666; font-size: 11px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.5px;
      margin-bottom: 5px; display: block;
    }
    .form-control, .form-select {
      background: #fafafa !important; border: 1px solid #e0e0e0 !important;
      color: #333 !important; border-radius: 8px !important;
      font-size: 13px; padding: 9px 12px !important;
    }
    .form-control:focus, .form-select:focus {
      border-color: #e53935 !important;
      box-shadow: 0 0 0 3px rgba(229,57,53,0.1) !important;
      background: #fff !important;
    }
    .form-control[readonly] {
      background: #f0f0f0 !important; color: #999 !important; cursor: not-allowed;
    }
    textarea.form-control { resize: vertical; min-height: 80px; }
    .field-hint { font-size: 11px; color: #bbb; margin-top: 4px; }
    .photo-wrap {
      display: flex; flex-direction: column;
      align-items: flex-start; gap: 10px;
    }
    .photo-circle {
      width: 80px; height: 80px; border-radius: 50%;
      object-fit: cover; border: 3px solid #e8e8e8;
    }
    .photo-default {
      width: 80px; height: 80px; border-radius: 50%;
      background: #f0f0f0; border: 3px solid #e8e8e8;
      display: flex; align-items: center;
      justify-content: center; color: #bbb; font-size: 28px;
    }
    .doc-row { display: flex; flex-direction: column; gap: 6px; }
    .doc-view-btn {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 5px 12px; border-radius: 7px;
      font-size: 11px; font-weight: 600;
      border: 1px solid; text-decoration: none;
      transition: all 0.15s; width: fit-content;
    }
    .doc-blue   { color: #1565c0; background: #e3f2fd; border-color: #bbdefb; }
    .doc-orange { color: #e65100; background: #fff3e0; border-color: #ffe0b2; }
    .doc-green  { color: #2e7d32; background: #e8f5e9; border-color: #c8e6c9; }
    .doc-view-btn:hover { filter: brightness(0.88); }
    .no-file { font-size: 11px; color: #bbb; }
    .btn-save {
      display: inline-flex; align-items: center; gap: 8px;
      background: #2e7d32; color: #fff; border: none;
      border-radius: 9px; padding: 11px 24px;
      font-size: 14px; font-weight: 700;
      cursor: pointer; transition: all 0.15s;
    }
    .btn-save:hover {
      background: #1b5e20;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(46,125,50,0.25);
    }
    .btn-cancel {
      display: inline-flex; align-items: center; gap: 8px;
      background: #f0f0f0; color: #666;
      border: 1px solid #e0e0e0; border-radius: 9px;
      padding: 11px 24px; font-size: 14px; font-weight: 600;
      text-decoration: none; transition: all 0.15s;
    }
    .btn-cancel:hover { background: #e0e0e0; color: #333; }
    .admin-notice {
      display: inline-flex; align-items: center; gap: 6px;
      background: #fff3e0; color: #e65100;
      border: 1px solid #ffe0b2; border-radius: 8px;
      padding: 6px 12px; font-size: 11px; font-weight: 700;
      margin-bottom: 16px;
    }
    .sub-divider {
      font-size: 11px; font-weight: 700; color: #e53935;
      text-transform: uppercase; letter-spacing: 0.5px;
      margin-bottom: 8px; padding-bottom: 4px;
      border-bottom: 1px dashed #f0f0f0;
    }
  </style>
</head>
<body>
<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-left">
    <i class="bi bi-pencil-square title-icon"></i>
    <h2>Edit Profile —
      <span style="color:#e53935;">
        <?= htmlspecialchars($profile['user_name'] ?? 'N/A') ?>
      </span>
    </h2>
  </div>
  <a href="<?= $backURL ?>" class="back-btn">
    <i class="bi bi-arrow-left"></i> Back
  </a>
</div>
<!-- Admin Notice -->
<?php if ($isAdmin): ?>
  <div class="admin-notice">
    <i class="bi bi-shield-fill-check"></i>
    Admin Mode — Editing user ID: <?= intval($profile['user_id']) ?>
  </div>
<?php endif; ?>
<!-- ✅ VALIDATION ERRORS from update_profile.php -->
<?php if (!empty($editErrors)): ?>
  <div class="msg-box msg-error">
    <i class="bi bi-x-circle-fill" style="font-size:17px; flex-shrink:0; margin-top:2px;"></i>
    <div>
      <strong>Please fix the following errors:</strong>
      <ul>
        <?php foreach ($editErrors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>
<!-- Success Message -->
<?php if (!empty($successMsg)): ?>
  <div class="msg-box msg-success">
    <i class="bi bi-check-circle-fill" style="font-size:17px; flex-shrink:0;"></i>
    <div><?= htmlspecialchars($successMsg) ?></div>
  </div>
<?php endif; ?>
<!-- General Error Message -->
<?php if (!empty($errorMsg)): ?>
  <div class="msg-box msg-error">
    <i class="bi bi-x-circle-fill" style="font-size:17px; flex-shrink:0;"></i>
    <div><?= htmlspecialchars($errorMsg) ?></div>
  </div>
<?php endif; ?>
<!-- ══════════════════════════════════════
     FORM — action fixed to point to update_profile.php
     (this is the script actually built for this form's
     fields: id-based lookup, experience/education,
     iframe-safe redirectTo(), etc.)
══════════════════════════════════════ -->
<form method="POST"
      action="../profile/update_profile.php"
      enctype="multipart/form-data"
      >
  <!-- ✅ CRITICAL HIDDEN FIELDS — update_profile.php ko chahiye -->
  <input type="hidden" name="id"
         value="<?= intval($profile['user_id']) ?>">
  <input type="hidden" name="email"
         value="<?= htmlspecialchars($profile['email']) ?>">
  <input type="hidden" name="experience"
         value="<?= htmlspecialchars($profile['experience'] ?? '') ?>">
  <input type="hidden" name="education"
         value="<?= htmlspecialchars($profile['education'] ?? '') ?>">
  <input type="hidden" name="other_relation"
         value="<?= htmlspecialchars($profile['other_relation'] ?? '') ?>">
  <?php if ($isAdmin): ?>
    <input type="hidden" name="from_admin" value="1">
  <?php endif; ?>
  <!-- ══ BASIC INFO ══ -->
  <div class="section-card">
    <div class="section-hdr">
      <i class="bi bi-person-fill"></i>
      <h3>Basic Information</h3>
    </div>
    <div class="section-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Full Name</label>
          <input type="text" name="user_name" class="form-control"
                 value="<?= htmlspecialchars($profile['user_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" class="form-control"
                 value="<?= htmlspecialchars($profile['email'] ?? '') ?>" readonly>
          <div class="field-hint">Email cannot be changed</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Contact No</label>
          <input type="text" name="contact_no" class="form-control"
                 maxlength="10"
                 value="<?= htmlspecialchars($profile['contact_no'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Date of Birth</label>
          <input type="date" name="dob" class="form-control"
                 value="<?= htmlspecialchars($profile['dob'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Current Address</label>
          <textarea name="current_address" class="form-control"><?=
            htmlspecialchars($profile['current_address'] ?? '')
          ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Permanent Address</label>
          <textarea name="permanent_address" class="form-control"><?=
            htmlspecialchars($profile['permanent_address'] ?? '')
          ?></textarea>
        </div>
      </div>
    </div>
  </div>
  <!-- ══ PROFILE PHOTO ══ -->
  <div class="section-card">
    <div class="section-hdr">
      <i class="bi bi-image-fill"></i>
      <h3>Profile Photo</h3>
    </div>
    <div class="section-body">
      <?php $photoURL = buildURL($profile['profile_photo'] ?? ''); ?>
      <div class="photo-wrap">
        <?php if (!empty($photoURL)): ?>
          <img src="<?= htmlspecialchars($photoURL) ?>"
               class="photo-circle" id="photoPreview" alt="">
        <?php else: ?>
          <div class="photo-default" id="photoPlaceholder">
            <i class="bi bi-person-fill"></i>
          </div>
        <?php endif; ?>
        <input type="file" name="profile_photo"
               class="form-control" accept="image/*"
               style="max-width:320px;"
               onchange="previewPhoto(this)">
        <div class="field-hint">Max 2MB. Leave empty to keep existing photo.</div>
      </div>
    </div>
  </div>
  <!-- ══ KYC DOCUMENTS ══ -->
  <div class="section-card">
    <div class="section-hdr">
      <i class="bi bi-card-checklist"></i>
      <h3>KYC Documents</h3>
    </div>
    <div class="section-body">
      <div class="row g-3">
        <?php
        $kycDocs = [
          'aadhar_doc'   => 'Aadhar Card',
          'pan_doc'      => 'PAN Card',
          'cheque_doc'   => 'Cancelled Cheque',
          'passbook_doc' => 'Passbook',
        ];
        foreach ($kycDocs as $field => $label):
          $fileURL = buildURL($profile[$field] ?? '');
        ?>
        <div class="col-md-6">
          <label class="form-label"><?= $label ?></label>
          <div class="doc-row">
            <?php if (!empty($fileURL)): ?>
              <a href="<?= htmlspecialchars($fileURL) ?>"
                 target="_blank" class="doc-view-btn doc-blue">
                <i class="bi bi-eye-fill"></i> View Current
              </a>
            <?php else: ?>
              <span class="no-file">Not uploaded yet</span>
            <?php endif; ?>
            <input type="file" name="<?= $field ?>" class="form-control">
            <div class="field-hint">Leave empty to keep existing file</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <!-- ══ EXPERIENCE DOCUMENTS ══ -->
  <?php if (($profile['experience'] ?? '') === 'experienced'): ?>
  <div class="section-card">
    <div class="section-hdr">
      <i class="bi bi-briefcase-fill"></i>
      <h3>Experience Documents</h3>
    </div>
    <div class="section-body">
      <div class="row g-3">
        <?php
        $expDocs = [
          'offer_letter_doc'     => 'Offer Letter',
          'relieving_letter_doc' => 'Relieving Letter',
          'salary_slip_doc'      => 'Salary Slip',
          'up_rehire_mail_doc'   => 'Rehire Mail',
        ];
        foreach ($expDocs as $field => $label):
          $fileURL = buildURL($profile[$field] ?? '');
        ?>
        <div class="col-md-6">
          <label class="form-label"><?= $label ?></label>
          <div class="doc-row">
            <?php if (!empty($fileURL)): ?>
              <a href="<?= htmlspecialchars($fileURL) ?>"
                 target="_blank" class="doc-view-btn doc-orange">
                <i class="bi bi-eye-fill"></i> View Current
              </a>
            <?php else: ?>
              <span class="no-file">Not uploaded yet</span>
            <?php endif; ?>
            <input type="file" name="<?= $field ?>" class="form-control">
            <div class="field-hint">Leave empty to keep existing file</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <!-- ══ EDUCATION ══ -->
  <div class="section-card">
    <div class="section-hdr">
      <i class="bi bi-mortarboard-fill"></i>
      <h3>Education</h3>
    </div>
    <div class="section-body">
      <div class="row g-3">
        <?php $markURL = buildURL($profile['marksheet_doc'] ?? ''); ?>
        <div class="col-md-6">
          <label class="form-label">Marksheet</label>
          <div class="doc-row">
            <?php if (!empty($markURL)): ?>
              <a href="<?= htmlspecialchars($markURL) ?>"
                 target="_blank" class="doc-view-btn doc-green">
                <i class="bi bi-eye-fill"></i> View Current
              </a>
            <?php else: ?>
              <span class="no-file">Not uploaded yet</span>
            <?php endif; ?>
            <input type="file" name="marksheet_doc" class="form-control">
            <div class="field-hint">Leave empty to keep existing file</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- ══ EMERGENCY CONTACT ══ -->
  <div class="section-card">
    <div class="section-hdr">
      <i class="bi bi-people-fill"></i>
      <h3>Emergency Contact</h3>
    </div>
    <div class="section-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Contact Relation</label>
          <select name="contact_relation" class="form-select">
            <?php
            $rel = $profile['contact_relation'] ?? '';
            foreach (['father' => 'Father', 'mother' => 'Mother', 'other' => 'Other'] as $v => $l):
            ?>
              <option value="<?= $v ?>" <?= $rel === $v ? 'selected' : '' ?>>
                <?= $l ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Father -->
        <div class="col-12">
          <div class="sub-divider">Father Details</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Father Name</label>
          <input type="text" name="father_name" class="form-control"
                 value="<?= htmlspecialchars($profile['father_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Father Contact</label>
          <input type="text" name="father_contact" class="form-control"
                 maxlength="10"
                 value="<?= htmlspecialchars($profile['father_contact'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Father Address</label>
          <input type="text" name="father_address" class="form-control"
                 value="<?= htmlspecialchars($profile['father_address'] ?? '') ?>">
        </div>
        <!-- Mother -->
        <div class="col-12">
          <div class="sub-divider">Mother Details</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Mother Name</label>
          <input type="text" name="mother_name" class="form-control"
                 value="<?= htmlspecialchars($profile['mother_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Mother Contact</label>
          <input type="text" name="mother_contact" class="form-control"
                 maxlength="10"
                 value="<?= htmlspecialchars($profile['mother_contact'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Mother Address</label>
          <input type="text" name="mother_address" class="form-control"
                 value="<?= htmlspecialchars($profile['mother_address'] ?? '') ?>">
        </div>
        <!-- Other -->
        <div class="col-12">
          <div class="sub-divider">Other Contact Details</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Other Name</label>
          <input type="text" name="other_name" class="form-control"
                 value="<?= htmlspecialchars($profile['other_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Other Contact</label>
          <input type="text" name="other_contact" class="form-control"
                 maxlength="10"
                 value="<?= htmlspecialchars($profile['other_contact'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Other Address</label>
          <input type="text" name="other_address" class="form-control"
                 value="<?= htmlspecialchars($profile['other_address'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>
  <!-- ══ SOCIAL PROFILES ══ (was missing — this is why social data was never submitted) -->
  <div class="section-card">
    <div class="section-hdr">
      <i class="bi bi-share-fill"></i>
      <h3>Social Media Links</h3>
    </div>
    <div class="section-body">
      <div class="row g-3 mb-2">
        <div class="col-md-4">
          <label class="form-label">Platform (Link 1)</label>
          <select name="social_platform_1" class="form-select">
            <option value="">-- Select --</option>
            <option value="instagram" <?= $socialPlatform1 === 'instagram' ? 'selected' : '' ?>>Instagram</option>
            <option value="facebook"  <?= $socialPlatform1 === 'facebook'  ? 'selected' : '' ?>>Facebook</option>
          </select>
        </div>
        <div class="col-md-8">
          <label class="form-label">Profile URL (Link 1)</label>
          <input type="url" name="social_url_1" class="form-control"
                 value="<?= htmlspecialchars($socialUrl1) ?>" placeholder="https://...">
        </div>
      </div>
      <div class="row g-3 mb-2">
        <div class="col-md-4">
          <label class="form-label">Platform (Link 2)</label>
          <input type="text" class="form-control" value="LinkedIn" readonly>
        </div>
        <div class="col-md-8">
          <label class="form-label">LinkedIn URL</label>
          <input type="url" name="social_url_2" class="form-control"
                 value="<?= htmlspecialchars($socialUrl2) ?>" placeholder="https://linkedin.com/in/...">
        </div>
      </div>
      <div id="socialExtraWrap">
        <?php foreach ($extraPlatforms as $idx => $ep): ?>
          <div class="row g-3 mb-2 social-extra-row">
            <div class="col-md-4">
              <select name="social_platform_extra[]" class="form-select">
                <option value="">-- Select --</option>
                <?php foreach (['twitter','youtube','snapchat','pinterest','telegram','other'] as $opt): ?>
                  <option value="<?= $opt ?>" <?= $ep === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-7">
              <input type="url" name="social_url_extra[]" class="form-control"
                     value="<?= htmlspecialchars($extraUrls[$idx] ?? '') ?>" placeholder="https://...">
            </div>
            <div class="col-md-1">
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.social-extra-row').remove()">
                <i class="bi bi-trash-fill"></i>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="back-btn" onclick="addSocialExtraRow()">
        <i class="bi bi-plus-circle"></i> Add Another Link
      </button>
    </div>
  </div>
  <!-- ══ SAVE BUTTONS ══ -->
  <div style="display:flex; gap:12px; margin-top:8px; flex-wrap:wrap;">
    <button type="submit" class="btn-save">
      <i class="bi bi-save-fill"></i> Save Changes
    </button>
    <a href="<?= $backURL ?>" class="btn-cancel">
      <i class="bi bi-x-circle"></i> Cancel
    </a>
  </div>
</form>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewPhoto(input) {
  if (!input.files || !input.files[0]) return;
  if (input.files[0].size > 2 * 1024 * 1024) {
    alert("Photo size must not exceed 2MB.");
    input.value = '';
    return;
  }
  const reader = new FileReader();
  reader.onload = function (e) {
    const ph = document.getElementById('photoPlaceholder');
    if (ph) ph.style.display = 'none';
    let preview = document.getElementById('photoPreview');
    if (!preview) {
      preview = document.createElement('img');
      preview.id        = 'photoPreview';
      preview.className = 'photo-circle';
      preview.alt       = '';
      input.parentNode.insertBefore(preview, input);
    }
   preview.src           = e.target.result;
    preview.style.display = 'block';
  };
  reader.readAsDataURL(input.files[0]);
}
// ── Social Profiles — add extra link row (new, needed for social_platform_extra[]/social_url_extra[]) ──
function addSocialExtraRow() {
  const wrap = document.getElementById('socialExtraWrap');
  const row = document.createElement('div');
  row.className = 'row g-3 mb-2 social-extra-row';
  row.innerHTML = `
    <div class="col-md-4">
      <select name="social_platform_extra[]" class="form-select">
        <option value="">-- Select --</option>
        <option value="twitter">Twitter</option>
        <option value="youtube">Youtube</option>
        <option value="snapchat">Snapchat</option>
        <option value="pinterest">Pinterest</option>
        <option value="telegram">Telegram</option>
        <option value="other">Other</option>
      </select>
    </div>
    <div class="col-md-7">
      <input type="url" name="social_url_extra[]" class="form-control" placeholder="https://...">
    </div>
    <div class="col-md-1">
      <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.social-extra-row').remove()">
        <i class="bi bi-trash-fill"></i>
      </button>
    </div>
  `;
  wrap.appendChild(row);
}
// ── DOB 18+ check before submit (added) ──
document.querySelector('form[action="../profile/update_profile.php"]').addEventListener('submit', function (e) {
  const dobInput = this.querySelector('input[name="dob"]');
  if (dobInput && dobInput.value) {
    const dob = new Date(dobInput.value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    if (age < 18) {
      e.preventDefault();
      alert("You must be at least 18 years old to create your profile.");
    }
  }
});
</script>
</body>
</html>