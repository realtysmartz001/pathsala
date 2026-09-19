<?php
session_start();
include '../includes/db.php';

// ✅ Admin check
if(!isset($_SESSION['role']) || $_SESSION['role'] !== "admin"){
    header("Location: ../auth/login.php");
    exit();
}

// ✅ ID Validation
if(!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: ../admin/admin_profile_details.php?msg=invalid");
    exit();
}

$id = intval($_GET['id']);

// ✅ Pehle files fetch karo — delete karne se pehle
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

if(!$profile){
    header("Location: ../admin/admin_profile_details.php?msg=notfound");
    exit();
}

// ✅ Uploaded files delete karo server se
$fileFields = [
    'profile_photo',
    'aadhar_doc', 'pan_doc', 'cheque_doc', 'passbook_doc',
    'offer_letter_doc', 'relieving_letter_doc', 'salary_slip_doc',
    'up_rehire_mail_doc', 'marksheet_doc'
];

foreach($fileFields as $field){
    if(!empty($profile[$field])){
        $filePath = $profile[$field];
        if(strpos($filePath, 'uploads/') !== 0){
            $filePath = 'uploads/' . $filePath;
        }
        if(file_exists($filePath)){
            unlink($filePath); // ✅ File server se delete
        }
    }
}

// ✅ Database se record delete karo
$deleteStmt = $conn->prepare("DELETE FROM user_profiles WHERE user_id = ?");
$deleteStmt->bind_param("i", $id);

if($deleteStmt->execute()){
    header("Location: ../admin/admin_profile_details.php?msg=deleted");
    exit();
} else {
    header("Location: ../admin/admin_profile_details.php?msg=error");
    exit();
}

$conn->close();
?>
