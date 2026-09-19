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

// Verify the image actually belongs to this project (prevents cross-project tampering)
$check = $conn->prepare("SELECT id FROM project_gallery WHERE id = ? AND project_id = ?");
$check->bind_param("ii", $id, $project_id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Image not found for this project.']);
    exit();
}

// Only ONE featured image per project — unset all first, then set the chosen one
$unset = $conn->prepare("UPDATE project_gallery SET is_featured = 0 WHERE project_id = ?");
$unset->bind_param("i", $project_id);
$unset->execute();

$set = $conn->prepare("UPDATE project_gallery SET is_featured = 1 WHERE id = ? AND project_id = ?");
$set->bind_param("ii", $id, $project_id);
$set->execute();

// Sync the chosen gallery image into projects.cover_image so the Project
// Card on the listing page automatically reflects the new featured image —
// no separate "cover" workflow needed, this keeps Edit Project's Cover
// Image field and the Gallery Manager's Featured star pointing at the
// same underlying value.
$imgRow = $conn->prepare("SELECT image_path FROM project_gallery WHERE id = ? AND project_id = ?");
$imgRow->bind_param("ii", $id, $project_id);
$imgRow->execute();
$img = $imgRow->get_result()->fetch_assoc();

if ($img && !empty($img['image_path'])) {
    // image_path is stored as "{slug}/{filename}" inside uploads/project_gallery/;
    // cover_image on the projects table expects a flat filename that lives in
    // assets/img/projects/ (the same folder add.php/edit.php already use for
    // the single Cover Image upload), so we copy the file there under a
    // predictable, collision-proof name.
    $sourcePath = __DIR__ . '/../../uploads/project_gallery/' . $img['image_path'];
    $ext = strtolower(pathinfo($img['image_path'], PATHINFO_EXTENSION));
    $coverFilename = 'cover_' . $project_id . '.' . $ext;
    $destPath = __DIR__ . '/../../assets/img/projects/' . $coverFilename;

    if (is_file($sourcePath) && copy($sourcePath, $destPath)) {
        $updateCover = $conn->prepare("UPDATE projects SET cover_image = ? WHERE id = ?");
        $updateCover->bind_param("si", $coverFilename, $project_id);
        $updateCover->execute();
    }
}

echo json_encode(['success' => true]);