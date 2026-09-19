<?php
include '../includes/db_user.php';

// Step 1: ID validation
if(!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: ../admin/admin_user.php?msg=invalid");
    exit();
}

$id = intval($_GET['id']);

// Step 2: Check connection variable
// If db_user.php uses $conn then the code below is correct
// If it uses $connection then replace $conn with $connection

// Step 3: Prepared statement — safe delete
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");

if(!$stmt){
    die("Prepare failed: " . $conn->error); // This error will show if $conn is incorrect
}

$stmt->bind_param("i", $id);

if($stmt->execute()){
    header("Location: ../admin/admin_user.php?msg=deleted");
    exit();
} else {
    die("Delete failed: " . $stmt->error);
}
?>
