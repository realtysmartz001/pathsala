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

if ($id <= 0 || $project_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit();
}

$stmt = $conn->prepare("SELECT image_path FROM project_gallery WHERE id = ? AND project_id = ?");
$stmt->bind_param("ii", $id, $project_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Image not found.']);
    exit();
}

$filePath = __DIR__ . '/../../uploads/project_gallery/' . $row['image_path'];
if (is_file($filePath)) {
    @unlink($filePath);
}

$del = $conn->prepare("DELETE FROM project_gallery WHERE id = ? AND project_id = ?");
$del->bind_param("ii", $id, $project_id);
$del->execute();

echo json_encode(['success' => true]);