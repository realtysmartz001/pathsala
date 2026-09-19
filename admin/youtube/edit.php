<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../../auth/login.php");
    exit();
}
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/youtube_helper.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: manage.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM youtube_videos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$video = $stmt->get_result()->fetch_assoc();

if (!$video) {
    header("Location: manage.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $youtube_url   = trim($_POST['youtube_url'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $location      = trim($_POST['location'] ?? '');
    $display_order = $_POST['display_order'] !== '' ? (int) $_POST['display_order'] : 0;
    $published     = isset($_POST['published']) ? 1 : 0;

    if ($title === '' || $youtube_url === '') {
        $error = 'Video Title and YouTube URL are required.';
    } else {
        $youtube_id = extractYoutubeId($youtube_url);

        if ($youtube_id === false) {
            $error = 'Could not detect a valid YouTube Video ID from that URL. Please check the link and try again.';
        } else {
            // Prevent duplicate youtube_id on OTHER rows
            $stmtCheck = $conn->prepare("SELECT id FROM youtube_videos WHERE youtube_id = ? AND id != ?");
            $stmtCheck->bind_param("si", $youtube_id, $id);
            $stmtCheck->execute();
            $existing = $stmtCheck->get_result();

            if ($existing->num_rows > 0) {
                $error = 'Another video already uses this YouTube URL.';
            } else {
                $thumbnail_url = getYoutubeThumbnail($youtube_id);
                $embed_url     = getYoutubeEmbedUrl($youtube_id);

                $stmt = $conn->prepare("UPDATE youtube_videos SET
                    title = ?, youtube_url = ?, youtube_id = ?, thumbnail_url = ?, embed_url = ?,
                    description = ?, location = ?, display_order = ?, published = ?
                    WHERE id = ?");
                $stmt->bind_param(
                    "sssssssiii",
                    $title, $youtube_url, $youtube_id, $thumbnail_url, $embed_url, $description, $location, $display_order, $published, $id
                );
                $stmt->execute();

                header("Location: manage.php?msg=updated");
                exit();
            }
        }
    }

    // Keep form populated with submitted values on error
    $video = array_merge($video, $_POST);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit YouTube Video</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#181818;color:#fff;font-family:'Segoe UI',sans-serif;padding:20px;}
.card{background:#202020;border:1px solid #383838;border-radius:12px;}
label{color:#aaa;font-size:13px;margin-bottom:4px;}
.form-control{background:#181818;border:1px solid #383838;color:#fff;}
.form-control:focus{background:#181818;border-color:#ff0000;color:#fff;box-shadow:none;}
.btn-red{background:#ff0000;border:none;color:#fff;}
.btn-red:hover{background:#cc0000;color:#fff;}
.form-check-label{color:#ccc;font-size:13px;}
.hint{color:#777;font-size:12px;margin-top:4px;}
.thumb-preview{width:200px;border-radius:8px;border:1px solid #383838;margin-top:8px;}
</style>
</head><body>
<h4 class="mb-3"><i class="bi bi-youtube text-danger"></i> Edit YouTube Video</h4>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST" class="card p-4">
  <div class="row g-3">
    <div class="col-md-6">
      <label>Video Title *</label>
      <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($video['title']) ?>" required>
    </div>
    <div class="col-md-6">
      <label>YouTube Video URL *</label>
      <input type="text" name="youtube_url" class="form-control" value="<?= htmlspecialchars($video['youtube_url']) ?>" required>
      <div class="hint">Changing this will regenerate the thumbnail &amp; embed link automatically.</div>
      <img class="thumb-preview" src="<?= htmlspecialchars($video['thumbnail_url']) ?>" alt="Current thumbnail">
    </div>

    <div class="col-md-6">
      <label>Short Description</label>
      <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($video['description'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label>Location</label>
      <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($video['location'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label>Display Order</label>
      <input type="number" name="display_order" class="form-control" value="<?= htmlspecialchars($video['display_order']) ?>">
    </div>

    <div class="col-12 mt-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="published" id="pub" <?= $video['published'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="pub">Published</label>
      </div>
    </div>

    <div class="col-12 mt-3">
      <button type="submit" class="btn btn-red px-4"><i class="bi bi-check-circle"></i> Update Video</button>
      <a href="manage.php" class="btn btn-outline-light px-4">Cancel</a>
    </div>
  </div>
</form>
</body></html>