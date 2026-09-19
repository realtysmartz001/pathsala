<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../../auth/login.php");
    exit();
}
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/youtube_helper.php';

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
            // Prevent duplicate youtube_id
            $stmtCheck = $conn->prepare("SELECT id FROM youtube_videos WHERE youtube_id = ?");
            $stmtCheck->bind_param("s", $youtube_id);
            $stmtCheck->execute();
            $existing = $stmtCheck->get_result();

            if ($existing->num_rows > 0) {
                $error = 'This YouTube video has already been added.';
            } else {
                $thumbnail_url = getYoutubeThumbnail($youtube_id);
                $embed_url     = getYoutubeEmbedUrl($youtube_id);

                $stmt = $conn->prepare("INSERT INTO youtube_videos
                    (title, youtube_url, youtube_id, thumbnail_url, embed_url, description, location, display_order, published)
                    VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param(
                    "sssssssii",
                    $title, $youtube_url, $youtube_id, $thumbnail_url, $embed_url, $description, $location, $display_order, $published
                );
                $stmt->execute();

                header("Location: manage.php?msg=added");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add YouTube Video</title>
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
</style>
</head><body>
<h4 class="mb-3"><i class="bi bi-youtube text-danger"></i> Add YouTube Video</h4>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST" class="card p-4">
  <div class="row g-3">
    <div class="col-md-6">
      <label>Video Title *</label>
      <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
    </div>
    <div class="col-md-6">
      <label>YouTube Video URL *</label>
      <input type="text" name="youtube_url" class="form-control" placeholder="https://youtu.be/XXXXXXXXXXX or https://www.youtube.com/watch?v=XXXXXXXXXXX" value="<?= htmlspecialchars($_POST['youtube_url'] ?? '') ?>" required>
      <div class="hint">Thumbnail &amp; embed link are generated automatically — no upload needed.</div>
    </div>

    <div class="col-md-6">
      <label>Short Description</label>
      <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($_POST['description'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label>Location</label>
      <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label>Display Order</label>
      <input type="number" name="display_order" class="form-control" value="<?= htmlspecialchars($_POST['display_order'] ?? '0') ?>">
    </div>

    <div class="col-12 mt-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="published" id="pub" checked>
        <label class="form-check-label" for="pub">Published</label>
      </div>
    </div>

    <div class="col-12 mt-3">
      <button type="submit" class="btn btn-red px-4"><i class="bi bi-check-circle"></i> Save Video</button>
      <a href="manage.php" class="btn btn-outline-light px-4">Cancel</a>
    </div>
  </div>
</form>
</body></html>