<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include '../includes/db_connect.php';

// ─── Auth Check ───────────────────────────────────────────────────
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_email'])) {
    header("Location: ../auth/login.php");
    exit();
}

// ─── Only POST allowed ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profile/profile_form.php");
    exit();
}

// ════════════════════════════════════════════════════════════════════
// 1. COLLECT TEXT FIELDS
// ════════════════════════════════════════════════════════════════════
$user_name = trim($_SESSION['user_name'] ?? '');
$email = trim($_SESSION['user_email'] ?? '');

// Look up this user's numeric ID — needed to build their personal upload folder
$userIdRow = null;
$userIdStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$userIdStmt->bind_param("s", $email);
$userIdStmt->execute();
$userIdRow = $userIdStmt->get_result()->fetch_assoc();
$userIdStmt->close();
$userId = $userIdRow['id'] ?? 0;

$contact_no = trim($_POST['contact_no'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$current_address = trim($_POST['current_address'] ?? '');
$permanent_address = trim($_POST['permanent_address'] ?? '');
$experience = trim($_POST['experience'] ?? '');
$education = trim($_POST['education'] ?? '');
$contact_relation = trim($_POST['contact_relation'] ?? '');

// ✅ Experience related contacts
$prev_hr_contact = trim($_POST['prev_hr_contact'] ?? '');
$prev_tl_contact = trim($_POST['prev_tl_contact'] ?? '');

// ✅ Reference 1 — Father
$father_name = trim($_POST['father_name'] ?? '');
$father_contact = trim($_POST['father_contact'] ?? '');
$father_address = trim($_POST['father_address'] ?? '');

// ✅ Reference 1 — Mother
$mother_name = trim($_POST['mother_name'] ?? '');
$mother_contact = trim($_POST['mother_contact'] ?? '');
$mother_address = trim($_POST['mother_address'] ?? '');

// ✅ Reference 2 — Other
$other_name = trim($_POST['other_name'] ?? '');
$other_relation = trim($_POST['other_relation'] ?? '');
$other_contact = trim($_POST['other_contact'] ?? '');
$other_address = trim($_POST['other_address'] ?? '');

// ✅ Social Media fields — were never being collected, causing empty social section
$social_platform_1 = trim($_POST['social_platform_1'] ?? '');
$social_url_1      = trim($_POST['social_url_1']      ?? '');
$social_url_2      = trim($_POST['social_url_2']      ?? '');

$rawPlatforms = $_POST['social_platform_extra'] ?? [];
$rawUrls      = $_POST['social_url_extra']      ?? [];
$cleanedPlatforms = [];
$cleanedUrls      = [];
foreach ($rawPlatforms as $i => $p) {
    $p = trim($p);
    $u = trim($rawUrls[$i] ?? '');
    if (!empty($p) && !empty($u)) {
        $cleanedPlatforms[] = $p;
        $cleanedUrls[]      = $u;
    }
}
$social_platform_extra = !empty($cleanedPlatforms)
    ? json_encode($cleanedPlatforms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    : null;
$social_url_extra = !empty($cleanedUrls)
    ? json_encode($cleanedUrls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    : null;

// ════════════════════════════════════════════════════════════════════
// 2. CHECK EDIT MODE (edit_allowed)
// ════════════════════════════════════════════════════════════════════
$editAllowed = false;
$isEditMode = false;

$chkStmt = $conn->prepare("SELECT edit_allowed FROM users WHERE email = ?");
$chkStmt->bind_param("s", $email);
$chkStmt->execute();
$chkRow = $chkStmt->get_result()->fetch_assoc();
$chkStmt->close();

if (!empty($chkRow['edit_allowed']) && $chkRow['edit_allowed'] == 1) {
    $editAllowed = true;
}

// ✅ Profile already exists check
$profileExists = false;
$existCheck = $conn->prepare("SELECT user_id FROM user_profiles WHERE email = ?");
$existCheck->bind_param("s", $email);
$existCheck->execute();
$existResult = $existCheck->get_result();
if ($existResult->num_rows > 0) {
    $profileExists = true;
}
$existCheck->close();

// ✅ Decide: INSERT or UPDATE
if ($profileExists && $editAllowed) {
    $isEditMode = true;   // UPDATE karega
} elseif ($profileExists && !$editAllowed) {
    // Profile exist karta hai, edit allowed nahi
    $_SESSION['form_errors'] = ["Profile already exists for this email."];
    header("Location: ../profile/profile_form.php?error=1");
    exit();
}

// ── Helper: check if DOB means the person is at least 18 years old ──
function isAtLeast18($dobValue)
{
    if (empty($dobValue)) {
        return false;
    }
    $dobDate = DateTime::createFromFormat('Y-m-d', $dobValue);
    if (!$dobDate) {
        return false;
    }
    $today = new DateTime('today');
    $age = $today->diff($dobDate)->y;
    return $age >= 18;
}

// ════════════════════════════════════════════════════════════════════
// 3. SERVER-SIDE VALIDATION
// ════════════════════════════════════════════════════════════════════
$errors = [];

// ── Personal Info ─────────────────────────────────────────────────
if (empty($user_name)) {
    $errors[] = "Full Name is required.";
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid Email ID is required.";
}
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
if (!in_array($experience, ['fresher', 'experienced'])) {
    $errors[] = "Please select Fresher or Experienced.";
}

// ── Experience HR/TL Validation ───────────────────────────────────
if ($experience === 'experienced') {
    if (empty($prev_hr_contact) || !preg_match('/^\d{10}$/', $prev_hr_contact)) {
        $errors[] = "Valid 10-digit Previous Company HR Number is required.";
    }
    // TL optional — but if filled, must be 10 digits
    if (!empty($prev_tl_contact) && !preg_match('/^\d{10}$/', $prev_tl_contact)) {
        $errors[] = "Previous Company TL Number must be 10 digits.";
    }
}

// ── Education ─────────────────────────────────────────────────────
$validEdu = ['10th', '12th', 'graduation', 'post_graduation', 'diploma'];
if (empty($education) || !in_array($education, $validEdu)) {
    $errors[] = "Valid Education Qualification is required.";
}

// ── KYC Documents (compulsory) ────────────────────────────────────
if (empty($_FILES['aadhar_doc']['name'])) {
    $errors[] = "Aadhar Card document is required.";
}
if (empty($_FILES['pan_doc']['name'])) {
    $errors[] = "PAN Card document is required.";
}

// ── Profile Photo ─────────────────────────────────────────────────
// (optional — no longer required)

// ── Emergency Contact — Reference 1 ──────────────────────────────
if (!in_array($contact_relation, ['father', 'mother'])) {
    $errors[] = "Emergency Reference 1: Please select Father or Mother.";
}
if ($contact_relation === 'father') {
    if (empty($father_name)) {
        $errors[] = "Emergency Reference 1: Father's Name is required.";
    }
    if (empty($father_contact) || !preg_match('/^\d{10}$/', $father_contact)) {
        $errors[] = "Emergency Reference 1: Valid 10-digit Father's Contact is required.";
    }
    if (empty($father_address)) {
        $errors[] = "Emergency Reference 1: Father's Address is required.";
    }
}
if ($contact_relation === 'mother') {
    if (empty($mother_name)) {
        $errors[] = "Emergency Reference 1: Mother's Name is required.";
    }
    if (empty($mother_contact) || !preg_match('/^\d{10}$/', $mother_contact)) {
        $errors[] = "Emergency Reference 1: Valid 10-digit Mother's Contact is required.";
    }
    if (empty($mother_address)) {
        $errors[] = "Emergency Reference 1: Mother's Address is required.";
    }
}

// ── Emergency Contact — Reference 2 ──────────────────────────────
if (empty($other_name)) {
    $errors[] = "Emergency Reference 2: Other Contact Name is required.";
}
if (empty($other_relation)) {
    $errors[] = "Emergency Reference 2: Relationship is required.";
}
if (empty($other_contact) || !preg_match('/^\d{10}$/', $other_contact)) {
    $errors[] = "Emergency Reference 2: Valid 10-digit Contact Number is required.";
}
if (empty($other_address)) {
    $errors[] = "Emergency Reference 2: Address is required.";
}

// ── Errors hain toh wapas bhejo ───────────────────────────────────
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header("Location: ../profile/profile_form.php?error=1");
    exit();
}

// ════════════════════════════════════════════════════════════════════
// 4. FILE UPLOAD FUNCTION
// ════════════════════════════════════════════════════════════════════

// Build a safe, readable folder name from the user's name, always suffixed
// with their numeric ID so two users with the same name never collide.
function sanitizeForFolderName($name)
{
    $name = strtolower(trim($name));
    $name = preg_replace('/[^a-z0-9]+/', '_', $name);
    $name = trim($name, '_');
    return $name === '' ? 'user' : $name;
}

// $folderAbsolute = full filesystem path to save the physical file into
// $folderRelative = the same folder, but relative to /uploads/ — this is
//                    what actually gets stored in the database
function uploadFile($field, $folderAbsolute = null, $folderRelative = '', $allowedTypes = [], $maxSize = 5 * 1024 * 1024)
{
    if ($folderAbsolute === null) {
        $folderAbsolute = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
    }
    if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {

        $fileSize = $_FILES[$field]['size'];
        $tmpPath = $_FILES[$field]['tmp_name'];
        $origName = basename($_FILES[$field]['name']);

        // Detect the real MIME type — works on localhost & live server both
        $fileMime = null;

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileMime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $fileMime = mime_content_type($tmpPath);
        } else {
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $mimeMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'pdf' => 'application/pdf',
            ];
            $fileMime = $mimeMap[$ext] ?? 'application/octet-stream';
        }

        // Sanitize the file name
        $fileName = time() . "_" . preg_replace(
            "/[^a-zA-Z0-9.\-_]/",
            "_",
            $origName
        );
        $targetAbsolute = $folderAbsolute . $fileName;
        $relativePath   = $folderRelative . $fileName;

        // Size check
        if ($fileSize > $maxSize) {
            return ['error' => "File '{$field}' exceeds the maximum allowed size."];
        }

        // MIME type check
        if (!empty($allowedTypes) && !in_array($fileMime, $allowedTypes)) {
            return ['error' => "File '{$field}' has invalid type ({$fileMime})."];
        }

        // Create the folder if it does not exist yet
        if (!is_dir($folderAbsolute)) {
            mkdir($folderAbsolute, 0755, true);
        }

        // Move the uploaded file into place
        if (move_uploaded_file($tmpPath, $targetAbsolute)) {
            // Store the RELATIVE path (e.g. "rahul_sharma_42/aadhar_doc_xxx.jpg"),
            // never the full filesystem path — this is what makes the link
            // work correctly later, regardless of which page displays it.
            return ['path' => $relativePath];
        }

        return ['error' => "Failed to upload '{$field}'."];
    }
    return ['path' => null];
}

// Every user gets their own personal upload subfolder, named from their
// (sanitized) name plus their numeric ID, e.g. "rahul_sharma_42".
// This keeps each user's documents together and easy to find in File
// Manager, while the trailing ID guarantees the folder name is always
// unique even if two users share the same name.
$userFolderName     = sanitizeForFolderName($user_name) . '_' . intval($userId);
$uploadFolderAbsolute = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $userFolderName . '/';
$uploadFolderRelative = $userFolderName . '/';

// ── MIME Types ────────────────────────────────────────────────────
$imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$pdfTypes = ['application/pdf'];
$docTypes = array_merge($imageTypes, $pdfTypes);   // documents: JPG/JPEG/PNG/PDF
$maxDocSize = 600 * 1024;                          // 600 KB = 614400 bytes

// ── Upload All Files ──────────────────────────────────────────────
$uploadResults = [
    'profile_photo' => uploadFile('profile_photo', $uploadFolderAbsolute, $uploadFolderRelative, $imageTypes, 5 * 1024 * 1024), // photo stays 5 MB
    'aadhar_doc' => uploadFile('aadhar_doc', $uploadFolderAbsolute, $uploadFolderRelative, $docTypes, $maxDocSize),
    'pan_doc' => uploadFile('pan_doc', $uploadFolderAbsolute, $uploadFolderRelative, $docTypes, $maxDocSize),
    'cheque_doc' => uploadFile('cheque_doc', $uploadFolderAbsolute, $uploadFolderRelative, $docTypes, $maxDocSize),
    'passbook_doc' => uploadFile('passbook_doc', $uploadFolderAbsolute, $uploadFolderRelative, $docTypes, $maxDocSize),
    'offer_letter_doc' => uploadFile('offer_letter_doc', $uploadFolderAbsolute, $uploadFolderRelative, $docTypes, $maxDocSize),
    'relieving_letter_doc' => uploadFile('relieving_letter_doc', $uploadFolderAbsolute, $uploadFolderRelative, $docTypes, $maxDocSize),
    'salary_slip_doc' => uploadFile('salary_slip_doc', $uploadFolderAbsolute, $uploadFolderRelative, $docTypes, $maxDocSize),
    'up_rehire_mail_doc' => uploadFile('up_rehire_mail_doc', $uploadFolderAbsolute, $uploadFolderRelative, $docTypes, $maxDocSize),
    'marksheet_doc' => uploadFile('marksheet_doc', $uploadFolderAbsolute, $uploadFolderRelative, $docTypes, $maxDocSize),
];

// ✅ Upload errors check
$uploadErrors = [];
foreach ($uploadResults as $field => $result) {
    if (isset($result['error'])) {
        $uploadErrors[] = $result['error'];
    }
}
if (!empty($uploadErrors)) {
    $_SESSION['form_errors'] = $uploadErrors;
    $_SESSION['form_data'] = $_POST;
    header("Location: ../profile/profile_form.php?error=1");
    exit();
}

// ── Extract Paths ─────────────────────────────────────────────────
$profile_photo = $uploadResults['profile_photo']['path'];
$aadhar_doc = $uploadResults['aadhar_doc']['path'];
$pan_doc = $uploadResults['pan_doc']['path'];
$cheque_doc = $uploadResults['cheque_doc']['path'];
$passbook_doc = $uploadResults['passbook_doc']['path'];
$offer_letter_doc = $uploadResults['offer_letter_doc']['path'];
$relieving_letter_doc = $uploadResults['relieving_letter_doc']['path'];
$salary_slip_doc = $uploadResults['salary_slip_doc']['path'];
$up_rehire_mail_doc = $uploadResults['up_rehire_mail_doc']['path'];
$marksheet_doc = $uploadResults['marksheet_doc']['path'];

// ════════════════════════════════════════════════════════════════════
// 5. INSERT OR UPDATE DATABASE
// ════════════════════════════════════════════════════════════════════
if ($isEditMode) {

    // ✅ UPDATE — existing profile edit kar raha hai
    // ✅ File null hai toh purani file rakhho
    $oldData = $conn->prepare("SELECT profile_photo, aadhar_doc, pan_doc,
        cheque_doc, passbook_doc, offer_letter_doc, relieving_letter_doc,
        salary_slip_doc, up_rehire_mail_doc, marksheet_doc
        FROM user_profiles WHERE email = ?");
    $oldData->bind_param("s", $email);
    $oldData->execute();
    $old = $oldData->get_result()->fetch_assoc();
    $oldData->close();

    // ✅ Agar naya file upload nahi hua toh purana path raho
    $profile_photo = $profile_photo ?? $old['profile_photo'];
    $aadhar_doc = $aadhar_doc ?? $old['aadhar_doc'];
    $pan_doc = $pan_doc ?? $old['pan_doc'];
    $cheque_doc = $cheque_doc ?? $old['cheque_doc'];
    $passbook_doc = $passbook_doc ?? $old['passbook_doc'];
    $offer_letter_doc = $offer_letter_doc ?? $old['offer_letter_doc'];
    $relieving_letter_doc = $relieving_letter_doc ?? $old['relieving_letter_doc'];
    $salary_slip_doc = $salary_slip_doc ?? $old['salary_slip_doc'];
    $up_rehire_mail_doc = $up_rehire_mail_doc ?? $old['up_rehire_mail_doc'];
    $marksheet_doc = $marksheet_doc ?? $old['marksheet_doc'];

   $query = "UPDATE user_profiles SET
        user_name=?, contact_no=?, dob=?, current_address=?,
        permanent_address=?, experience=?, education=?,
        prev_hr_contact=?, prev_tl_contact=?,
        contact_relation=?,
        father_name=?, father_contact=?, father_address=?,
        mother_name=?, mother_contact=?, mother_address=?,
        other_name=?, other_relation=?, other_contact=?, other_address=?,
        profile_photo=?, aadhar_doc=?, pan_doc=?, cheque_doc=?,
        passbook_doc=?, offer_letter_doc=?, relieving_letter_doc=?,
        salary_slip_doc=?, up_rehire_mail_doc=?, marksheet_doc=?,
        social_platform_1=?, social_url_1=?, social_url_2=?,
        social_platform_extra=?, social_url_extra=?
        WHERE email=?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssssssssssssssssssssssssssssssssssss",
        $user_name,
        $contact_no,
        $dob,
        $current_address,
        $permanent_address,
        $experience,
        $education,
        $prev_hr_contact,
        $prev_tl_contact,
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
        $profile_photo,
        $aadhar_doc,
        $pan_doc,
        $cheque_doc,
        $passbook_doc,
        $offer_letter_doc,
        $relieving_letter_doc,
        $salary_slip_doc,
        $up_rehire_mail_doc,
        $marksheet_doc,
        $social_platform_1,
        $social_url_1,
        $social_url_2,
        $social_platform_extra,
        $social_url_extra,
        $email
    );

} else {

    // ✅ INSERT — naya profile
$query = "INSERT INTO user_profiles
        (user_name, email, contact_no, dob, current_address, permanent_address,
         experience, education, prev_hr_contact, prev_tl_contact,
         contact_relation,
         father_name, father_contact, father_address,
         mother_name, mother_contact, mother_address,
         other_name, other_relation, other_contact, other_address,
         profile_photo, aadhar_doc, pan_doc, cheque_doc, passbook_doc,
         offer_letter_doc, relieving_letter_doc, salary_slip_doc,
         up_rehire_mail_doc, marksheet_doc,
         social_platform_1, social_url_1, social_url_2,
         social_platform_extra, social_url_extra)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssssssssssssssssssssssssssssssssssss",
        $user_name,
        $email,
        $contact_no,
        $dob,
        $current_address,
        $permanent_address,
        $experience,
        $education,
        $prev_hr_contact,
        $prev_tl_contact,
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
        $profile_photo,
        $aadhar_doc,
        $pan_doc,
        $cheque_doc,
        $passbook_doc,
        $offer_letter_doc,
        $relieving_letter_doc,
        $salary_slip_doc,
        $up_rehire_mail_doc,
        $marksheet_doc,
        $social_platform_1,
        $social_url_1,
        $social_url_2,
        $social_platform_extra,
        $social_url_extra
    );
}

// ════════════════════════════════════════════════════════════════════
// 6. EXECUTE & RESPOND
// ════════════════════════════════════════════════════════════════════
if ($stmt->execute()) {

    // ✅ Edit mode tha toh edit_allowed = 0 reset karo
    if ($isEditMode) {
        $resetStmt = $conn->prepare(
            "UPDATE users SET edit_allowed = 0 WHERE email = ?"
        );
        $resetStmt->bind_param("s", $email);
        $resetStmt->execute();
        $resetStmt->close();
    }

    // ✅ Session clean karo
    unset($_SESSION['form_errors']);
    unset($_SESSION['form_data']);

    echo "<script>
        alert('✅ Profile " . ($isEditMode ? 'updated' : 'submitted') . " successfully!');
        window.location.href = '../profile/view_profile.php';
    </script>";

} else {
    error_log("Profile " . ($isEditMode ? "Update" : "Insert") . " Error: " . $stmt->error);
    echo "<script>
        alert('❌ Something went wrong. Please try again.');
        window.location.href = '../profile/profile_form.php';
    </script>";
}

$stmt->close();
$conn->close();
?>