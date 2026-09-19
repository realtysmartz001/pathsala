<?php
date_default_timezone_set('Asia/Kolkata');

if (str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost')) {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'realtys1_pathshala_db';
} else {
    $host = 'localhost';
    $username = 'realtys1_pathshala_user';
    $password = 'gU=(*rLf@5g@NNju';
    $database = 'realtys1_pathshala_db';
}

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ MySQL session timezone IST set karo — $conn ke BAAD
$conn->query("SET time_zone = '+05:30'");
?>
