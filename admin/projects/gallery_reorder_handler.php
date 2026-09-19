<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}
require_once __DIR__ . '/../../includes/db_connect.php';

$project_id = (int) ($_POST['project_id'] ?? 0);
$orderJson  = $_POST['order'] ?? '';

if ($project_id <= 0 || $orderJson === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit();
}

$orderedIds = json_decode($orderJson, true);
if (!is_array($orderedIds)) {
    echo json_encode(['success' => false, 'error' => 'Invalid order data.']);
    exit();
}

$stmt = $conn->prepare("UPDATE project_gallery SET display_order = ? WHERE id = ? AND project_id = ?");
foreach ($orderedIds as $index => $id) {
    $id = (int) $id;
    $stmt->bind_param("iii", $index, $id, $project_id);
    $stmt->execute();
}

echo json_encode(['success' => true]);