<?php
session_start();
include '../includes/db.php';
// ── Auth Check ──
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_user  = isset($_SESSION['user_id']);
if (!$is_admin && !$is_user) {
    redirectTo("../auth/login.php");
    exit();
}
// ── POST Check ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo($is_admin
        ? "../admin/admin_profile_details.php?msg=invalid"
        : "../auth/after_login.php");
    exit();
}
// ════════════════════════════════════════
// HELPER — iframe-safe redirect
// ════════════════════════════════════════
function redirectTo($url) {
    // Redirect within the current frame — this keeps the redirect inside
    // the admin panel's iframe (so the sidebar stays visible) instead of
    // breaking out to replace the entire browser tab.
    echo "<!DOCTYPE html><html><head>
    <script>
      window.location.href = " . json_encode($url) . ";
    </script>
    </head><body></body></html>";
    exit();
}
// ── ID Validation ──
if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
    redirectTo($is_admin
        ? "../admin/admin_profile_details.php?msg=invalid"
        : "../auth/after_login.php");
    exit();
}
$id = intval($_POST['id']);
// ── User can only update own profile ──
if (!$is_admin && $id !== intval($_SESSION['user_id'])) {
    redirectTo("../auth/after_login.php?msg=not_allowed");
    exit();
}
// ── Fetch Existing Profile ──
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$profile) {
    redirectTo("../admin/admin_profile_details.php?msg=notfound");
    exit();
}
// ════════════════════════════════════════
// HELPER — clean file path
// ════════════════════════════════════════
function cleanPath($val) {
    if (empty($val)) return '';
    $val = str_replace('uploads/', '', $val);
    return basename($val);
}
// ════════════════════════════════════════
// HELPER — check if DOB means age >= 18
// ════════════════════════════════════════
function isAtLeast18($dobValue) {
    if (empty($dobValue)) return false;
    $dobDate = DateTime::createFromFormat('Y-m-d', $dobValue);
    if (!$dobDate) return false;
    $today = new DateTime('today');
    $age = $today->diff($dobDate)->y;
    return $age >= 18;
}
// ════════════════════════════════════════
// 1. COLLECT POST FIELDS
// ════════════════════════════════════════
$user_name         = trim($_POST['user_name']         ?? '');
$email             = trim($_POST['email']             ?? '');
$contact_no        = trim($_POST['contact_no']        ?? '');
$dob               = trim($_POST['dob']               ?? '');
$experience        = trim($_POST['experience']        ?? $profile['experience'] ?? '');
$education         = trim($_POST['education']         ?? $profile['education']  ?? '');
$current_address   = trim($_POST['current_address']   ?? '');
$permanent_address = trim($_POST['permanent_address'] ?? '');
$contact_relation  = trim($_POST['contact_relation']  ?? '');
$father_name    = trim($_POST['father_name']    ?? '');
$father_contact = trim($_POST['father_contact'] ?? '');
$father_address = trim($_POST['father_address'] ?? '');
$mother_name    = trim($_POST['mother_name']    ?? '');
$mother_contact = trim($_POST['mother_contact'] ?? '');
$mother_address = trim($_POST['mother_address'] ?? '');
$other_name     = trim($_POST['other_name']     ?? '');
$other_relation = trim($_POST['other_relation'] ?? '');
$other_contact  = trim($_POST['other_contact']  ?? '');
$other_address  = trim($_POST['other_address']  ?? '');
// ── Email fallback — use existing if not posted ──
if (empty($email)) {
    $email = $profile['email'];
}
// ════════════════════════════════════════
// 2. VALIDATION
// ════════════════════════════════════════
$errors = [];
if (empty($user_name)) {
    $errors[] = "Full Name is required.";
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid Email ID is required.";
}
// Email duplicate check
$emailCheck = $conn->prepare(
    "SELECT user_id FROM user_profiles WHERE email = ? AND user_id != ?"
);
$emailCheck->bind_param("si", $email, $id);
$emailCheck->execute();
if ($emailCheck->get_result()->num_rows > 0) {
    $errors[] = "This email is already used by another profile.";
}
$emailCheck->close();
if (empty($contact_no) || !preg_match('/^\d{10}$/', $contact_no)) {
    $errors[] = "Valid 10-digit Contact Number is required.";
}
if (empty($dob)) {
    $errors[] = "Date of Birth is required.";
} elseif (!isAtLeast18($dob)) {
    $errors[] = "You must be at least 18 years old to create your profile.";
}
if (empty($permanent_address)) {
    $errors[] = "Permanent Address is required.";
}
// Experience — fallback to existing DB value, skip validation failure
if (!in_array($experience, ['fresher', 'experienced'])) {
    $experience = $profile['experience'] ?? 'fresher';
}
// Education — fallback to existing DB value
$validEdu = ['10th', '12th', 'graduation', 'post_graduation', 'diploma'];
if (!in_array($education, $validEdu)) {
    $education = $profile['education'] ?? '10th';
}
// KYC — only required if no existing file
$existingAadhar = cleanPath($profile['aadhar_doc']);
if (
    empty($existingAadhar) &&
    (empty($_FILES['aadhar_doc']['name']) ||
     $_FILES['aadhar_doc']['error'] !== UPLOAD_ERR_OK)
) {
    $errors[] = "Aadhar Card document is required.";
}
$existingPan = cleanPath($profile['pan_doc']);
if (
    empty($existingPan) &&
    (empty($_FILES['pan_doc']['name']) ||
     $_FILES['pan_doc']['error'] !== UPLOAD_ERR_OK)
) {
    $errors[] = "PAN Card document is required.";
}
// Emergency Ref 1
if (!in_array($contact_relation, ['father', 'mother', 'other'])) {
    $errors[] = "Emergency Reference 1: Please select a relation.";
}
if ($contact_relation === 'father') {
    if (empty($father_name))    $errors[] = "Father's Name is required.";
    if (empty($father_contact) || !preg_match('/^\d{10}$/', $father_contact))
        $errors[] = "Valid 10-digit Father's Contact is required.";
    if (empty($father_address)) $errors[] = "Father's Address is required.";
}
if ($contact_relation === 'mother') {
    if (empty($mother_name))    $errors[] = "Mother's Name is required.";
    if (empty($mother_contact) || !preg_match('/^\d{10}$/', $mother_contact))
        $errors[] = "Valid 10-digit Mother's Contact is required.";
    if (empty($mother_address)) $errors[] = "Mother's Address is required.";
}
// Emergency Ref 2 — Other relation field
// ✅ If other_relation empty, use existing DB value — don't block save
if (empty($other_relation)) {
    $other_relation = $profile['other_relation'] ?? '';
}
// Only validate Ref2 fields if any ref2 data exists
$hasRef2 = !empty($other_name) || !empty($other_contact);
if ($hasRef2) {
    if (empty($other_name))   $errors[] = "Other Contact Name is required.";
    if (empty($other_contact) || !preg_match('/^\d{10}$/', $other_contact))
        $errors[] = "Valid 10-digit Other Contact is required.";
}
// ── Errors → redirect back with JS (iframe-safe) ──
if (!empty($errors)) {
    $_SESSION['edit_errors'] = $errors;
    redirectTo("../profile/edit_profile.php?id=" . $id);
    exit();
}
// ════════════════════════════════════════
// 3. FILE UPLOAD HANDLER
// ════════════════════════════════════════

// Turn a stored value (any historical format — bare filename,
// "uploads/filename", a full filesystem path, or the new
// "folder/filename" format) into a path relative to /uploads/.
function resolveUploadRelative($val)
{
    if (empty($val)) return '';
    $normalized = str_replace('\\', '/', $val);
    $pos = strripos($normalized, '/uploads/');
    if ($pos !== false) {
        $normalized = substr($normalized, $pos + strlen('/uploads/'));
    } elseif (str_starts_with($normalized, 'uploads/')) {
        $normalized = substr($normalized, strlen('uploads/'));
    }
    return ltrim($normalized, '/');
}

// Build a safe, readable per-user folder name: sanitized name + numeric ID.
function sanitizeForFolderName($name)
{
    $name = strtolower(trim($name));
    $name = preg_replace('/[^a-z0-9]+/', '_', $name);
    $name = trim($name, '_');
    return $name === '' ? 'user' : $name;
}

// Every user has their own personal upload subfolder.
$userFolderRelative = sanitizeForFolderName($user_name) . '_' . $id;
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $userFolderRelative . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$pdfTypes   = ['application/pdf'];
$docMimeMap = [
    'profile_photo'        => $imageTypes,
    'aadhar_doc'           => $imageTypes,
    'pan_doc'              => $imageTypes,
    'cheque_doc'           => $imageTypes,
    'passbook_doc'         => $imageTypes,
    'offer_letter_doc'     => array_merge($imageTypes, $pdfTypes),
    'relieving_letter_doc' => array_merge($imageTypes, $pdfTypes),
    'salary_slip_doc'      => array_merge($imageTypes, $pdfTypes),
    'up_rehire_mail_doc'   => array_merge($imageTypes, $pdfTypes),
    'marksheet_doc'        => array_merge($imageTypes, $pdfTypes),
];
function handleFileUpload($fieldName, $existingFile, $allowedMimes, $uploadDir, $userFolderRelative) {
    if (
        isset($_FILES[$fieldName]) &&
        $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK &&
        !empty($_FILES[$fieldName]['name'])
    ) {
        $fileSize = $_FILES[$fieldName]['size'];
        $tmpPath  = $_FILES[$fieldName]['tmp_name'];
        // Detect MIME type with fallbacks — some hosting environments
        // disable the fileinfo extension, which makes mime_content_type()
        // throw a fatal "undefined function" error.
        $fileMime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileMime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $fileMime = mime_content_type($tmpPath);
        } else {
            $extForMime = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
            $mimeMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'pdf' => 'application/pdf',
            ];
            $fileMime = $mimeMap[$extForMime] ?? 'application/octet-stream';
        }
        if ($fileSize > 2 * 1024 * 1024) { // 2MB limit
            return [
                'path'  => resolveUploadRelative($existingFile),
                'error' => "File '{$fieldName}' exceeds 2MB limit."
            ];
        }
        if (!in_array($fileMime, $allowedMimes)) {
            return [
                'path'  => resolveUploadRelative($existingFile),
                'error' => "File '{$fieldName}' has invalid type ({$fileMime})."
            ];
        }
        $ext     = strtolower(
            pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION)
        );
        $newName = $fieldName . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $dest    = $uploadDir . $newName;
        if (move_uploaded_file($tmpPath, $dest)) {
            // Delete the old physical file, wherever it was stored
            if (!empty($existingFile)) {
                $oldRelative = resolveUploadRelative($existingFile);
                $oldFile = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $oldRelative;
                if (file_exists($oldFile)) unlink($oldFile);
            }
            // Store the RELATIVE path, e.g. "rahul_sharma_42/aadhar_doc_xxx.jpg"
            return ['path' => $userFolderRelative . '/' . $newName, 'error' => null];
        }
        return [
            'path'  => resolveUploadRelative($existingFile),
            'error' => "Failed to save '{$fieldName}'. Check folder permissions."
        ];
    }
    // No new file — keep existing
    return ['path' => resolveUploadRelative($existingFile), 'error' => null];
}
$fileFields = [
    'profile_photo', 'aadhar_doc', 'pan_doc',
    'cheque_doc', 'passbook_doc', 'offer_letter_doc',
    'relieving_letter_doc', 'salary_slip_doc',
    'up_rehire_mail_doc', 'marksheet_doc'
];
$uploadResults = [];
$uploadErrors  = [];
foreach ($fileFields as $field) {
    $res = handleFileUpload(
        $field,
        $profile[$field],
        $docMimeMap[$field],
        $uploadDir,
        $userFolderRelative
    );
    $uploadResults[$field] = $res['path'];
    if (!empty($res['error'])) $uploadErrors[] = $res['error'];
}
if (!empty($uploadErrors)) {
    $_SESSION['edit_errors'] = $uploadErrors;
    redirectTo("../profile/edit_profile.php?id=" . $id);
    exit();
}
// ════════════════════════════════════════
// 4. DATABASE UPDATE
// ════════════════════════════════════════
$updateStmt = $conn->prepare("
    UPDATE user_profiles SET
        user_name            = ?,
        email                = ?,
        contact_no           = ?,
        dob                  = ?,
        experience           = ?,
        education            = ?,
        current_address      = ?,
        permanent_address    = ?,
        contact_relation     = ?,
        father_name          = ?,
        father_contact       = ?,
        father_address       = ?,
        mother_name          = ?,
        mother_contact       = ?,
        mother_address       = ?,
        other_name           = ?,
        other_relation       = ?,
        other_contact        = ?,
        other_address        = ?,
        profile_photo        = ?,
        aadhar_doc           = ?,
        pan_doc              = ?,
        cheque_doc           = ?,
        passbook_doc         = ?,
        offer_letter_doc     = ?,
        relieving_letter_doc = ?,
        salary_slip_doc      = ?,
        up_rehire_mail_doc   = ?,
        marksheet_doc        = ?
    WHERE user_id = ?
");
$updateStmt->bind_param(
    "sssssssssssssssssssssssssssssi",
    $user_name,
    $email,
    $contact_no,
    $dob,
    $experience,
    $education,
    $current_address,
    $permanent_address,
    $contact_relation,
    $father_name,
    $father_contact,
    $father_address,
    $mother_name,
    $mother_contact,
    $mother_address,
    $other_name,
    $other_relation,
    $other_contact,
    $other_address,
    $uploadResults['profile_photo'],
    $uploadResults['aadhar_doc'],
    $uploadResults['pan_doc'],
    $uploadResults['cheque_doc'],
    $uploadResults['passbook_doc'],
    $uploadResults['offer_letter_doc'],
    $uploadResults['relieving_letter_doc'],
    $uploadResults['salary_slip_doc'],
    $uploadResults['up_rehire_mail_doc'],
    $uploadResults['marksheet_doc'],
    $id
);
if ($updateStmt->execute()) {
    unset($_SESSION['edit_errors']);
    $updateStmt->close();
    // Reset edit_allowed for normal user
    if (!$is_admin) {
        $reset = $conn->prepare(
            "UPDATE users SET edit_allowed = 0 WHERE id = ?"
        );
        $reset->bind_param("i", $_SESSION['user_id']);
        $reset->execute();
        $reset->close();
    }
    $conn->close();
    // ✅ iframe-safe success redirect
    $successUrl = $is_admin
        ? "../admin/admin_profile_details.php?msg=updated"
        : "../auth/after_login.php?msg=profile_updated";
    redirectTo($successUrl);
    exit();
} else {
    error_log("Profile Update Error [ID:{$id}]: " . $updateStmt->error);
    $updateStmt->close();
    $conn->close();
    $_SESSION['edit_errors'] = ["Database error. Please try again."];
    redirectTo("../profile/edit_profile.php?id=" . $id);
    exit();
}
?>