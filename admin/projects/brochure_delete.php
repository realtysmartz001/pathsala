<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") { header("Location: ../../auth/login.php"); exit(); }
require_once __DIR__ . '/../../includes/db_connect.php';

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($project_id > 0) {
    $stmt = $conn->prepare("SELECT brochure_path FROM projects WHERE id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();

    if ($project && !empty($project['brochure_path'])) {
        $filePath = __DIR__ . '/../../assets/brochures/' . basename($project['brochure_path']);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $stmtU = $conn->prepare("UPDATE projects SET brochure_path = NULL WHERE id = ?");
        $stmtU->bind_param("i", $project_id);
        $stmtU->execute();
    }
}

header("Location: manage.php?brochure_msg=deleted");
exit();