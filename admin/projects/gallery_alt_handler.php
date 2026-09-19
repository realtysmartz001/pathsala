<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}
require_once __DIR__ . '/../../includes/db_connect.php';

$id = (int) ($_POST['id'] ?? 0);
$project_id = (int) ($_POST['project_id'] ?? 0);
$altText = trim($_POST['alt_text'] ?? '');

if ($id <= 0 || $project_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit();
}

// Cap length defensively even though the column allows up to 255
$altText = mb_substr($altText, 0, 255);

$stmt = $conn->prepare("UPDATE project_gallery SET alt_text = ? WHERE id = ? AND project_id = ?");
$stmt->bind_param("sii", $altText, $id, $project_id);
$stmt->execute();

echo json_encode(['success' => true]);