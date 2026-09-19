<?php
session_start();
include "../includes/db.php";

if(!isset($_SESSION['admin'])){
    die("Access Denied!");
}

// Same filter logic as view_results.php
$where = "1=1";

if(isset($_GET['search']) && !empty($_GET['search'])){
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%')";
}

if(isset($_GET['set_no']) && $_GET['set_no'] != ""){
    $set_no = (int)$_GET['set_no'];
    $where .= " AND ua.set_no = $set_no";
}

if(!empty($_GET['from_date']) && !empty($_GET['to_date'])){
    $from = $_GET['from_date'];
    $to   = $_GET['to_date'];
    $where .= " AND DATE(ua.submitted_at) BETWEEN '$from' AND '$to'";
}

// Query
$sql = "SELECT ua.*, u.name, u.email 
        FROM user_attempts ua
        JOIN users u ON ua.user_id = u.id
        WHERE $where
        ORDER BY ua.submitted_at DESC";

$result = mysqli_query($conn, $sql);

// Headers for CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=test_results.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, array('User Name', 'Email', 'Set No', 'Score', 'Total Questions', 'Submitted At'));

// Rows
while($row = mysqli_fetch_assoc($result)){
    fputcsv($output, array(
        $row['name'],
        $row['email'],
        $row['set_no'],
        $row['score'],
        $row['total_questions'],
        $row['submitted_at']
    ));
}

fclose($output);
exit;
?>
