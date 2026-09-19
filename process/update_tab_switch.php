<?php
session_start();
include '../includes/db_connect.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) { 
    http_response_code(403); 
    exit(); 
}

$count       = (int)($_POST['count'] ?? 0);
$attempt_no  = $_SESSION['attempt_no'] ?? 1;

// ✅ Specific attempt_no ke saath update karo — sirf active attempt
mysqli_query($conn, 
    "UPDATE user_attempts 
     SET tab_switches = $count 
     WHERE user_id   = $user_id 
     AND attempt_no  = $attempt_no
     AND end_time IS NULL");

echo "ok";
?>
