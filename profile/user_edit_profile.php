<?php
session_start();
include '../includes/db_connect.php';
// Auth check
if (!isset($_SESSION['user_email'])) {
  header("Location: ../auth/login.php");
  exit();
}
$userEmail = $_SESSION['user_email'];
// ✅ Check edit_allowed permission first
$permStmt = $conn->prepare("SELECT edit_allowed FROM users WHERE email = ?");
$permStmt->bind_param("s", $userEmail);
$permStmt->execute();
$permRow = $permStmt->get_result()->fetch_assoc();
$permStmt->close();
if (empty($permRow['edit_allowed']) || $permRow['edit_allowed'] != 1) {
  header("Location: ../profile/view_profile.php");
  exit();
}
// ✅ Fetch profile data by email
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE email = ?");
$stmt->bind_param("s", $userEmail);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$profile) {
  header("Location: ../profile/view_profile.php");
  exit();
}
// ✅ Base URL
$baseURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
  . '://' . $_SERVER['HTTP_HOST'] . '/';
// ✅ Clean path helper
function cleanPath($val)
{
  if (empty($val))
    return '';
  $val = str_replace('uploads/', '', $val);
  return basename($val);
}
// ✅ Build full URL helper
function buildURL($val, $baseURL)
{
  $clean = cleanPath($val);
  if (empty($clean))
    return '';
  return $baseURL . 'uploads/' . $clean;
}
// ✅ Success / Error message
$successMsg = $_SESSION['update_success'] ?? '';
$errorMsg = $_SESSION['update_error'] ?? '';
unset($_SESSION['update_success'], $_SESSION['update_error']);
// ✅ Parse existing extra social links (JSON stored in DB)
$extraPlatforms = [];
$extraUrls = [];
if (!empty($profile['social_platform_extra'])) {
  $decoded = json_decode($profile['social_platform_extra'], true);
  $extraPlatforms = is_array($decoded) ? $decoded : explode(',', $profile['social_platform_extra']);
}
if (!empty($profile['social_url_extra'])) {
  $decoded = json_decode($profile['social_url_extra'], true);
  $extraUrls = is_array($decoded) ? $decoded : explode(',', $profile['social_url_extra']);
}
// ✅ Available platform options
$platformOptions = [
  'instagram' => 'Instagram',
  'facebook' => 'Facebook',
  'linkedin' => 'LinkedIn',
  'twitter' => 'Twitter / X',
  'youtube' => 'YouTube',
  'snapchat' => 'Snapchat',
  'pinterest' => 'Pinterest',
  'telegram' => 'Telegram',
  'other' => 'Other',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit My Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    body {
      background: url('assets/img/gallery/section_bg02.png') no-repeat center center fixed;
      background-size: cover;
      font-family: 'Segoe UI', sans-serif;
    }
    .section-header {
      background: linear-gradient(135deg, #0d6efd, #0a58ca);
      color: white;
      padding: 12px 18px;
      border-radius: 8px;
      margin: 20px 0 15px 0;
    }
    .photo-placeholder {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: #e9ecef;
      border: 3px solid #0d6efd;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      color: #adb5bd;
      margin-bottom: 8px;
    }
    .photo-img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #0d6efd;
      margin-bottom: 8px;
      display: block;
    }
    /* ✅ Social Media Styles */
    .social-row {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 10px;
      padding: 14px 16px;
      margin-bottom: 14px;
      position: relative;
    }
    .social-row-required {
      border-left: 4px solid #0d6efd;
    }
    .social-row-extra {
      border-left: 4px solid #198754;
    }
    .remove-social-btn {
      position: absolute;
      top: 10px;
      right: 12px;
    }
    .social-badge {
      font-size: 11px;
      padding: 2px 8px;
      border-radius: 20px;
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="card shadow">
      <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <i class="bi bi-pencil-square"></i> Edit My Profile —
          <?php echo htmlspecialchars($profile['user_name'] ?? 'N/A'); ?>
        </h5>
        <a href="../profile/view_profile" class="btn btn-sm btn-outline-light">
          <i class="bi bi-arrow-left"></i> Back
        </a>
      </div>
      <div class="card-body">
        <!-- ✅ Success / Error Alert -->
        <?php if (!empty($successMsg)): ?>
          <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo htmlspecialchars($successMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if (!empty($errorMsg)): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php echo htmlspecialchars($errorMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <!-- ✅ Form -->
        <form method="POST" action="../profile/user_update_profile.php" enctype="multipart/form-data">
          <input type="hidden" name="user_email" value="<?php echo htmlspecialchars($profile['email']); ?>">
          <!-- ======================== -->
          <!-- BASIC INFO              -->
          <!-- ======================== -->
          <div class="section-header">
            <i class="bi bi-person-fill"></i> Basic Information
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Full Name:</label>
              <input type="text" name="user_name" class="form-control"
                value="<?php echo htmlspecialchars($profile['user_name'] ?? ''); ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Email:</label>
              <input type="email" class="form-control" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>"
                readonly>
              <small class="text-muted">Email cannot be changed</small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Contact No:</label>
              <input type="text" name="contact_no" class="form-control" maxlength="10"
                value="<?php echo htmlspecialchars($profile['contact_no'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Date of Birth:</label>
              <input type="date" name="dob" class="form-control"
                value="<?php echo htmlspecialchars($profile['dob'] ?? ''); ?>">
            </div>
            <!-- ══ NEW: Experience ══ -->
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Experience:</label>
              <select name="experience" class="form-select">
                <option value="fresher" <?php echo (($profile['experience'] ?? '') === 'fresher') ? 'selected' : ''; ?>>
                  Fresher
                </option>
                <option value="experienced" <?php echo (($profile['experience'] ?? '') === 'experienced') ? 'selected' : ''; ?>>
                  Experienced
                </option>
              </select>
            </div>
            <!-- ══ NEW: Education ══ -->
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Education Qualification:</label>
              <select name="education" class="form-select">
                <?php
                $eduOptions = [
                  '10th' => '10th',
                  '12th' => '12th',
                  'graduation' => 'Graduation',
                  'post_graduation' => 'Post Graduation',
                  'diploma' => 'Diploma',
                ];
                foreach ($eduOptions as $val => $label):
                  $sel = (($profile['education'] ?? '') === $val) ? 'selected' : '';
                  ?>
                  <option value="<?php echo $val; ?>" <?php echo $sel; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Current Address:</label>
              <textarea name="current_address" class="form-control" rows="2"><?php
              echo htmlspecialchars($profile['current_address'] ?? '');
              ?></textarea>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Permanent Address:</label>
              <textarea name="permanent_address" class="form-control" rows="2"><?php
              echo htmlspecialchars($profile['permanent_address'] ?? '');
              ?></textarea>
            </div>
          </div>
          <!-- ======================== -->
          <!-- PROFILE PHOTO           -->
          <!-- ======================== -->
          <div class="section-header">
            <i class="bi bi-image-fill"></i> Profile Photo
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <?php $photoURL = buildURL($profile['profile_photo'] ?? '', $baseURL); ?>
              <?php if (!empty($photoURL)): ?>
                <img src="<?php echo htmlspecialchars($photoURL); ?>" class="photo-img" alt="Profile Photo"
                  id="photoPreview">
              <?php else: ?>
                <div class="photo-placeholder" id="photoPlaceholder">
                  <i class="bi bi-person-fill"></i>
                </div>
              <?php endif; ?>
              <input type="file" name="profile_photo" class="form-control" accept="image/*"
                onchange="previewNewPhoto(this)">
              <small class="text-muted">Leave empty to keep the existing photo</small>
            </div>
          </div>
          <!-- ======================== -->
          <!-- KYC DOCUMENTS           -->
          <!-- ======================== -->
          <div class="section-header">
            <i class="bi bi-card-checklist"></i> KYC Documents
          </div>
          <div class="row">
            <?php
            $kycDocs = [
              'aadhar_doc' => 'Aadhar Card',
              'pan_doc' => 'PAN Card',
              'cheque_doc' => 'Cancelled Cheque',
              'passbook_doc' => 'Passbook',
            ];
            foreach ($kycDocs as $field => $label):
              $fileURL = buildURL($profile[$field] ?? '', $baseURL);
              ?>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold"><?php echo $label; ?>:</label><br>
                <?php if (!empty($fileURL)): ?>
                  <a href="<?php echo htmlspecialchars($fileURL); ?>" target="_blank"
                    class="btn btn-sm btn-outline-primary mb-2">
                    <i class="bi bi-eye"></i> View Current
                  </a><br>
                <?php else: ?>
                  <span class="text-muted small d-block mb-1">Not uploaded</span>
                <?php endif; ?>
                <input type="file" name="<?php echo $field; ?>" class="form-control">
                <small class="text-muted">Leave empty to keep the existing file</small>
              </div>
            <?php endforeach; ?>
          </div>
          <!-- ======================== -->
          <!-- EXPERIENCE DOCS         -->
          <!-- ======================== -->
          <?php if (($profile['experience'] ?? '') === 'experienced'): ?>
            <div class="section-header">
              <i class="bi bi-briefcase-fill"></i> Experience Documents
            </div>
            <div class="row">
              <?php
              $expDocs = [
                'offer_letter_doc' => 'Offer Letter',
                'relieving_letter_doc' => 'Relieving Letter',
                'salary_slip_doc' => 'Salary Slip',
                'up_rehire_mail_doc' => 'Rehire Mail',
              ];
              foreach ($expDocs as $field => $label):
                $fileURL = buildURL($profile[$field] ?? '', $baseURL);
                ?>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-bold"><?php echo $label; ?>:</label><br>
                  <?php if (!empty($fileURL)): ?>
                    <a href="<?php echo htmlspecialchars($fileURL); ?>" target="_blank"
                      class="btn btn-sm btn-outline-warning mb-2">
                      <i class="bi bi-eye"></i> View Current
                    </a><br>
                  <?php else: ?>
                    <span class="text-muted small d-block mb-1">Not uploaded</span>
                  <?php endif; ?>
                  <input type="file" name="<?php echo $field; ?>" class="form-control">
                  <small class="text-muted">Leave empty to keep the existing file</small>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <!-- ======================== -->
          <!-- EDUCATION               -->
          <!-- ======================== -->
          <div class="section-header">
            <i class="bi bi-mortarboard-fill"></i> Education
          </div>
          <div class="row">
            <?php $markURL = buildURL($profile['marksheet_doc'] ?? '', $baseURL); ?>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Marksheet:</label><br>
              <?php if (!empty($markURL)): ?>
                <a href="<?php echo htmlspecialchars($markURL); ?>" target="_blank"
                  class="btn btn-sm btn-outline-primary mb-2">
                  <i class="bi bi-eye"></i> View Current
                </a><br>
              <?php else: ?>
                <span class="text-muted small d-block mb-1">Not uploaded</span>
              <?php endif; ?>
              <input type="file" name="marksheet_doc" class="form-control">
              <small class="text-muted">Leave empty to keep the existing file</small>
            </div>
          </div>
          <!-- ======================== -->
          <!-- EMERGENCY CONTACT       -->
          <!-- ======================== -->
          <div class="section-header">
            <i class="bi bi-people-fill"></i> Emergency Contact
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">Contact Relation:</label>
              <select name="contact_relation" class="form-select">
                <option value="father" <?php echo (($profile['contact_relation'] ?? '') === 'father') ? 'selected' : ''; ?>>
                  Father
                </option>
                <option value="mother" <?php echo (($profile['contact_relation'] ?? '') === 'mother') ? 'selected' : ''; ?>>
                  Mother
                </option>
                <option value="other" <?php echo (($profile['contact_relation'] ?? '') === 'other') ? 'selected' : ''; ?>>
                  Other
                </option>
              </select>
            </div>
            <!-- Father -->
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold">Father Name:</label>
              <input type="text" name="father_name" class="form-control"
                value="<?php echo htmlspecialchars($profile['father_name'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold">Father Contact:</label>
              <input type="text" name="father_contact" class="form-control" maxlength="10"
                value="<?php echo htmlspecialchars($profile['father_contact'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold">Father Address:</label>
              <input type="text" name="father_address" class="form-control"
                value="<?php echo htmlspecialchars($profile['father_address'] ?? ''); ?>">
            </div>
            <!-- Mother -->
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold">Mother Name:</label>
              <input type="text" name="mother_name" class="form-control"
                value="<?php echo htmlspecialchars($profile['mother_name'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold">Mother Contact:</label>
              <input type="text" name="mother_contact" class="form-control" maxlength="10"
                value="<?php echo htmlspecialchars($profile['mother_contact'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold">Mother Address:</label>
              <input type="text" name="mother_address" class="form-control"
                value="<?php echo htmlspecialchars($profile['mother_address'] ?? ''); ?>">
            </div>
            <!-- Other -->
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold">Other Name:</label>
              <input type="text" name="other_name" class="form-control"
                value="<?php echo htmlspecialchars($profile['other_name'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold">Other Contact:</label>
              <input type="text" name="other_contact" class="form-control" maxlength="10"
                value="<?php echo htmlspecialchars($profile['other_contact'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-bold">Other Address:</label>
              <input type="text" name="other_address" class="form-control"
                value="<?php echo htmlspecialchars($profile['other_address'] ?? ''); ?>">
            </div>
          </div>
          <!-- ================================ -->
          <!-- ✅ SOCIAL MEDIA LINKS — NEW     -->
          <!-- ================================ -->
          <div class="section-header">
            <i class="bi bi-share-fill"></i> Social Media Links
          </div>
          <!-- Compulsory Link 1: Instagram / Facebook -->
          <div class="social-row social-row-required">
            <span class="badge bg-primary social-badge mb-2">
              <i class="bi bi-lock-fill"></i> Compulsory Link 1
            </span>
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label fw-bold">Platform:</label>
                <select name="social_platform_1" class="form-select">
                  <?php foreach ($platformOptions as $val => $lbl):
                    $selected = (($profile['social_platform_1'] ?? '') === $val) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $val; ?>" <?php echo $selected; ?>>
                      <?php echo $lbl; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label fw-bold">Profile URL:</label>
                <input type="url" name="social_url_1" class="form-control"
                  placeholder="https://instagram.com/yourprofile"
                  value="<?php echo htmlspecialchars($profile['social_url_1'] ?? ''); ?>">
              </div>
            </div>
          </div>
          <!-- Compulsory Link 2: LinkedIn -->
          <div class="social-row">
            <span class="badge bg-secondary social-badge mb-2">
              <i class="bi bi-unlock-fill"></i> LinkedIn (Optional)
            </span>
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label fw-bold">Platform:</label>
                <input type="text" class="form-control" value="LinkedIn" readonly>
              </div>
              <div class="col-md-8">
                <label class="form-label fw-bold">LinkedIn (Optional):</label>
                <input type="url" name="social_url_2" class="form-control"
                  placeholder="If you have a LinkedIn profile, paste the URL here (Optional)"
                  value="<?php echo htmlspecialchars($profile['social_url_2'] ?? ''); ?>">
              </div>
            </div>
          </div>
          <!-- ✅ Extra Social Links (Dynamic) -->
          <div id="extraSocialContainer">
            <?php foreach ($extraPlatforms as $i => $platform):
              $url = $extraUrls[$i] ?? '';
              if (empty($platform) && empty($url))
                continue;
              ?>
              <div class="social-row social-row-extra" id="extraRow_<?php echo $i; ?>">
                <span class="badge bg-success social-badge mb-2">
                  <i class="bi bi-plus-circle-fill"></i> Extra Link
                </span>
                <button type="button" class="btn btn-sm btn-outline-danger remove-social-btn"
                  onclick="removeExtraRow(this)">
                  <i class="bi bi-trash"></i>
                </button>
                <div class="row g-2">
                  <div class="col-md-4">
                    <label class="form-label fw-bold">Platform:</label>
                    <select name="social_platform_extra[]" class="form-select">
                      <?php foreach ($platformOptions as $val => $lbl):
                        $sel = ($platform === $val) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $val; ?>" <?php echo $sel; ?>>
                          <?php echo $lbl; ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-8">
                    <label class="form-label fw-bold">Profile URL:</label>
                    <input type="url" name="social_url_extra[]" class="form-control" placeholder="https://..."
                      value="<?php echo htmlspecialchars($url); ?>">
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <!-- Add Extra Link Button -->
          <div class="mb-4">
            <button type="button" class="btn btn-outline-success btn-sm" onclick="addExtraRow()">
              <i class="bi bi-plus-circle"></i> Add Another Social Link
            </button>
            <small class="text-muted ms-2">You can add multiple extra links</small>
          </div>
          <!-- ======================== -->
          <!-- BUTTONS                 -->
          <!-- ======================== -->
          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-success btn-lg">
              <i class="bi bi-save"></i> Save Changes
            </button>
            <a href="../profile/view_profile.php" class="btn btn-secondary btn-lg">
              <i class="bi bi-x-circle"></i> Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // ✅ Preview new photo before upload
    function previewNewPhoto(input) {
      if (input.files && input.files[0]) {
        if (input.files[0].size > 5 * 1024 * 1024) {
          alert("Photo size must not exceed 5 MB.");
          input.value = '';
          return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
          var placeholder = document.getElementById('photoPlaceholder');
          if (placeholder) placeholder.style.display = 'none';
          var preview = document.getElementById('photoPreview');
          if (!preview) {
            preview = document.createElement('img');
            preview.id = 'photoPreview';
            preview.className = 'photo-img';
            preview.alt = 'Profile Photo';
            input.parentNode.insertBefore(preview, input);
          }
          preview.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
      }
    }
    // ✅ Platform options for dynamic rows
    const platformOptions = <?php echo json_encode($platformOptions); ?>;
    // ✅ Add extra social link row dynamically
    function addExtraRow() {
      const container = document.getElementById('extraSocialContainer');
      const div = document.createElement('div');
      div.className = 'social-row social-row-extra';
      let optionsHTML = '';
      for (const [val, lbl] of Object.entries(platformOptions)) {
        optionsHTML += `<option value="${val}">${lbl}</option>`;
      }
      div.innerHTML = `
      <span class="badge bg-success social-badge mb-2">
        <i class="bi bi-plus-circle-fill"></i> Extra Link
      </span>
      <button type="button" class="btn btn-sm btn-outline-danger remove-social-btn"
              onclick="removeExtraRow(this)">
        <i class="bi bi-trash"></i>
      </button>
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label fw-bold">Platform:</label>
          <select name="social_platform_extra[]" class="form-select">
            ${optionsHTML}
          </select>
        </div>
        <div class="col-md-8">
          <label class="form-label fw-bold">Profile URL:</label>
          <input type="url" name="social_url_extra[]" class="form-control"
                 placeholder="https://...">
        </div>
      </div>`;
      container.appendChild(div);
    }
    // ✅ Remove extra social link row
    function removeExtraRow(btn) {
      btn.closest('.social-row').remove();
    }
    // ── DOB 18+ check before submit (added) ──
    document.querySelector('form[action="../profile/user_update_profile.php"]').addEventListener('submit', function (e) {
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