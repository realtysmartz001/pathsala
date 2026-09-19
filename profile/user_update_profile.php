<?php
session_start();
include '../includes/db_connect.php';

// ✅ Dual Auth — Admin OR User
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isUser  = isset($_SESSION['user_email']);

if (!$isAdmin && !$isUser) {
    header("Location: ../auth/login.php");
    exit();
}

// ✅ Determine which email to use
if ($isAdmin) {
    $userEmail = trim($_POST['email'] ?? '');
    if (empty($userEmail)) {
        $_SESSION['update_error'] = "Missing user email.";
        header("Location: ../admin/admin_profile_details.php?msg=error");
        exit();
    }
} else {
    $userEmail = $_SESSION['user_email'];

    // ✅ Check edit_allowed for normal user
    $permStmt = $conn->prepare("SELECT edit_allowed FROM users WHERE email = ?");
    $permStmt->bind_param("s", $userEmail);
    $permStmt->execute();
    $permRow = $permStmt->get_result()->fetch_assoc();
    $permStmt->close();

    if (empty($permRow['edit_allowed']) || $permRow['edit_allowed'] != 1) {
        header("Location: ../profile/view_profile.php");
        exit();
    }
}

// ✅ Fetch existing profile
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE email = ?");
$stmt->bind_param("s", $userEmail);
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

// Turn a stored value (in any historical format — bare filename,
// "uploads/filename", a full filesystem path, or the new
// "folder/filename" format) into a clean path relative to /uploads/.
// This lets old and new records both resolve correctly.
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

// Build a safe, readable per-user folder name: sanitized name + numeric ID,
// e.g. "rahul_sharma_42" — the trailing ID guarantees uniqueness even if
// two users share the same name.
function sanitizeForFolderName($name)
{
    $name = strtolower(trim($name));
    $name = preg_replace('/[^a-z0-9]+/', '_', $name);
    $name = trim($name, '_');
    return $name === '' ? 'user' : $name;
}

// ✅ Handle file upload — saves into the user's own subfolder
function handleFileUpload($fieldName, $existingFile, $userFolderRelative, $maxSize = 600 * 1024)
{
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $userFolderRelative . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (
        isset($_FILES[$fieldName]) &&
        $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK &&
        !empty($_FILES[$fieldName]['name'])
    ) {
        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'application/pdf'
        ];
        // Detect MIME type with fallbacks — some hosting environments
        // disable the fileinfo extension, which makes mime_content_type()
        // throw a fatal "undefined function" error.
        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES[$fieldName]['tmp_name']);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($_FILES[$fieldName]['tmp_name']);
        } else {
            $extForMime = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
            $mimeMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'pdf' => 'application/pdf',
            ];
            $mime = $mimeMap[$extForMime] ?? 'application/octet-stream';
        }

        if (!in_array($mime, $allowedMimes)) return resolveUploadRelative($existingFile);
        if ($_FILES[$fieldName]['size'] > $maxSize) return resolveUploadRelative($existingFile); // default 600 KB; profile_photo overrides to 5 MB

        $ext     = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
        $newName = $fieldName . '_' . time() . '_' . rand(100, 999) . '.' . $ext;

        if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $uploadDir . $newName)) {
            // Delete the old physical file, wherever it was (old flat
            // location or a previous per-user folder)
            if (!empty($existingFile)) {
                $oldRelative = resolveUploadRelative($existingFile);
                $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $oldRelative;
                if (file_exists($oldPath)) unlink($oldPath);
            }
            // Store the RELATIVE path only, e.g. "rahul_sharma_42/aadhar_doc_xxx.jpg"
            return $userFolderRelative . '/' . $newName;
        }
    }

    return resolveUploadRelative($existingFile);
}

// ✅ Text fields
$user_name         = trim($_POST['user_name']         ?? '');
$contact_no        = trim($_POST['contact_no']        ?? '');
$dob               = trim($_POST['dob']               ?? '');
$current_address   = trim($_POST['current_address']   ?? '');
$permanent_address = trim($_POST['permanent_address'] ?? '');
$contact_relation  = trim($_POST['contact_relation']  ?? '');
$father_name       = trim($_POST['father_name']       ?? '');
$father_contact    = trim($_POST['father_contact']    ?? '');
$father_address    = trim($_POST['father_address']    ?? '');
$mother_name       = trim($_POST['mother_name']       ?? '');
$mother_contact    = trim($_POST['mother_contact']    ?? '');
$mother_address    = trim($_POST['mother_address']    ?? '');
$other_name        = trim($_POST['other_name']        ?? '');
$other_contact     = trim($_POST['other_contact']     ?? '');
$other_address     = trim($_POST['other_address']     ?? '');

// ══ NEW: Experience & Education — validated, falls back to existing DB value if missing/invalid ══
$experience = trim($_POST['experience'] ?? '');
if (!in_array($experience, ['fresher', 'experienced'], true)) {
    $experience = $profile['experience'] ?? 'fresher';
}

$education = trim($_POST['education'] ?? '');
$validEdu = ['10th', '12th', 'graduation', 'post_graduation', 'diploma'];
if (!in_array($education, $validEdu, true)) {
    $education = $profile['education'] ?? '10th';
}

// ✅ Social Media fields
$social_platform_1 = trim($_POST['social_platform_1'] ?? '');
$social_url_1      = trim($_POST['social_url_1']      ?? '');
$social_url_2      = trim($_POST['social_url_2']      ?? '');

// ✅ Extra social links — arrays
$rawPlatforms = $_POST['social_platform_extra'] ?? [];
$rawUrls      = $_POST['social_url_extra']      ?? [];

$cleanedPlatforms = [];
$cleanedUrls      = [];

foreach ($rawPlatforms as $i => $platform) {
    $p = trim($platform);
    $u = trim($rawUrls[$i] ?? '');
    // Only save if BOTH platform and URL are filled
    if (!empty($p) && !empty($u)) {
        $cleanedPlatforms[] = $p;
        $cleanedUrls[]      = $u;
    }
}

// ✅ Store extra links as JSON
$social_platform_extra = !empty($cleanedPlatforms)
    ? json_encode($cleanedPlatforms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    : null;

$social_url_extra = !empty($cleanedUrls)
    ? json_encode($cleanedUrls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    : null;

// Every user has their own personal upload subfolder — built from their
// name plus their database ID, e.g. "rahul_sharma_42". This keeps their
// documents together and always resolves to the same folder whether the
// edit happens here (user editing themselves) or via the admin panel.
$userFolderRelative = sanitizeForFolderName($profile['user_name'] ?? '') . '_' . intval($profile['user_id']);

// ✅ File uploads
$profile_photo        = handleFileUpload('profile_photo',        $profile['profile_photo'],        $userFolderRelative, 5 * 1024 * 1024); // photo stays 5 MB
$aadhar_doc           = handleFileUpload('aadhar_doc',           $profile['aadhar_doc'],           $userFolderRelative);           // 600 KB (default)
$pan_doc              = handleFileUpload('pan_doc',              $profile['pan_doc'],              $userFolderRelative);           // 600 KB (default)
$cheque_doc           = handleFileUpload('cheque_doc',           $profile['cheque_doc'],           $userFolderRelative);           // 600 KB (default)
$passbook_doc         = handleFileUpload('passbook_doc',         $profile['passbook_doc'],         $userFolderRelative);           // 600 KB (default)
$offer_letter_doc     = handleFileUpload('offer_letter_doc',     $profile['offer_letter_doc'],     $userFolderRelative);           // 600 KB (default)
$relieving_letter_doc = handleFileUpload('relieving_letter_doc', $profile['relieving_letter_doc'], $userFolderRelative);           // 600 KB (default)
$salary_slip_doc      = handleFileUpload('salary_slip_doc',      $profile['salary_slip_doc'],      $userFolderRelative);           // 600 KB (default)
$up_rehire_mail_doc   = handleFileUpload('up_rehire_mail_doc',   $profile['up_rehire_mail_doc'],   $userFolderRelative);           // 600 KB (default)
$marksheet_doc        = handleFileUpload('marksheet_doc',        $profile['marksheet_doc'],        $userFolderRelative);           // 600 KB (default)

// ✅ Update DB — Experience, Education, and Social fields included
$updateStmt = $conn->prepare("
    UPDATE user_profiles SET
        user_name=?,            contact_no=?,               dob=?,
        experience=?,           education=?,
        current_address=?,      permanent_address=?,
        contact_relation=?,
        father_name=?,          father_contact=?,            father_address=?,
        mother_name=?,          mother_contact=?,            mother_address=?,
        other_name=?,           other_contact=?,             other_address=?,
        profile_photo=?,        aadhar_doc=?,                pan_doc=?,
        cheque_doc=?,           passbook_doc=?,
        offer_letter_doc=?,     relieving_letter_doc=?,
        salary_slip_doc=?,      up_rehire_mail_doc=?,
        marksheet_doc=?,
        social_platform_1=?,    social_url_1=?,
        social_url_2=?,
        social_platform_extra=?, social_url_extra=?
    WHERE email=?
");

// ✅ Count: 33 variables = 33 s
$updateStmt->bind_param(
    "sssssssssssssssssssssssssssssssss",  // ← exactly 33 s
    $user_name,            // 1
    $contact_no,           // 2
    $dob,                  // 3
    $experience,            // 4  ✅ NEW
    $education,             // 5  ✅ NEW
    $current_address,      // 6
    $permanent_address,    // 7
    $contact_relation,     // 8
    $father_name,          // 9
    $father_contact,       // 10
    $father_address,       // 11
    $mother_name,          // 12
    $mother_contact,       // 13
    $mother_address,       // 14
    $other_name,           // 15
    $other_contact,        // 16
    $other_address,        // 17
    $profile_photo,        // 18
    $aadhar_doc,           // 19
    $pan_doc,              // 20
    $cheque_doc,           // 21
    $passbook_doc,         // 22
    $offer_letter_doc,     // 23
    $relieving_letter_doc, // 24
    $salary_slip_doc,      // 25
    $up_rehire_mail_doc,   // 26
    $marksheet_doc,        // 27
    $social_platform_1,    // 28
    $social_url_1,         // 29
    $social_url_2,         // 30
    $social_platform_extra,// 31
    $social_url_extra,     // 32
    $userEmail             // 33
);


if ($updateStmt->execute()) {

    if (!$isAdmin) {
        $resetStmt = $conn->prepare("UPDATE users SET edit_allowed = 0 WHERE email = ?");
        $resetStmt->bind_param("s", $userEmail);
        $resetStmt->execute();
        $resetStmt->close();
    }

    $_SESSION['update_success'] = "Profile updated successfully!";

    if ($isAdmin) {
        $userId = intval($profile['user_id']);
        header("Location: ../profile/edit_profile.php?id=" . $userId . "&msg=updated");
    } else {
        header("Location: ../profile/view_profile.php?msg=updated");
    }

} else {
    $_SESSION['update_error'] = "Update failed: " . $updateStmt->error;

    if ($isAdmin) {
        $userId = intval($profile['user_id']);
        header("Location: ../profile/edit_profile.php?id=" . $userId . "&msg=error");
    } else {
        header("Location: ../profile/edit_profile.php?msg=error");
    }
}

$updateStmt->close();
$conn->close();
exit();
?>