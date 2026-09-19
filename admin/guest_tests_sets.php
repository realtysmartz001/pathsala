<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    http_response_code(403);
    echo json_encode([]);
    exit();
}

include '../includes/db_connect.php';

$jobRole = trim($_GET['job_role'] ?? '');
$allowedRoles = ['tele_sales', 'sales_consultant', 'team_leader', 'others'];

if (!in_array($jobRole, $allowedRoles, true)) {
    echo json_encode([]);
    exit();
}

$roleEsc = mysqli_real_escape_string($conn, $jobRole);
$res = mysqli_query($conn, "SELECT DISTINCT set_id FROM questions WHERE job_role = '$roleEsc' ORDER BY set_id");

$sets = [];
while ($row = mysqli_fetch_assoc($res)) {
    $sets[] = $row['set_id'];
}

echo json_encode($sets);