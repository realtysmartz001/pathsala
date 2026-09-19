<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../../auth/login.php");
    exit();
}
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/events_helper.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: manage.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    header("Location: manage.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title              = trim($_POST['title'] ?? '');
    $subtitle           = trim($_POST['subtitle'] ?? '');
    $description        = trim($_POST['description'] ?? '');
    $event_date         = trim($_POST['event_date'] ?? '');
    $event_time         = trim($_POST['event_time'] ?? '');
    $location           = trim($_POST['location'] ?? '');
    $event_type         = trim($_POST['event_type'] ?? '');
    $registration_link  = trim($_POST['registration_link'] ?? '');
    $button_text        = trim($_POST['button_text'] ?? '') ?: 'Register Now';
    $display_order      = $_POST['display_order'] !== '' ? (int) $_POST['display_order'] : 0;
    $featured           = isset($_POST['featured']) ? 1 : 0;
    $published          = isset($_POST['published']) ? 1 : 0;

    if ($title === '' || $event_date === '') {
        $error = 'Event Title and Event Date are required.';
    } elseif ($registration_link !== '' && !filter_var($registration_link, FILTER_VALIDATE_URL)) {
        $error = 'Registration Link must be a valid URL (e.g. https://...).';
    } else {
        $image = $event['image'];

        if (!empty($_FILES['image']['name'])) {
            $upload = uploadEventImage('image');

            if (isset($upload['error'])) {
                $error = $upload['error'];
            } else {
                $oldPath = __DIR__ . '/../../uploads/events/' . $event['image'];
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
                $image = $upload['filename'];
            }
        }

        if ($error === '') {
            $stmt = $conn->prepare("UPDATE events SET
                title = ?, subtitle = ?, description = ?, image = ?, event_date = ?, event_time = ?,
                location = ?, event_type = ?, registration_link = ?, button_text = ?,
                display_order = ?, featured = ?, published = ?
                WHERE id = ?");
            $stmt->bind_param(
                "ssssssssssiiii",
                $title, $subtitle, $description, $image, $event_date, $event_time,
                $location, $event_type, $registration_link, $button_text,
                $display_order, $featured, $published, $id
            );
            $stmt->execute();

            header("Location: manage.php?msg=updated");
            exit();
        }
    }

    $event = array_merge($event, $_POST, ['image' => $image]);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Event</title>
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
.img-preview{width:220px;border-radius:8px;border:1px solid #383838;margin-top:8px;}
</style>
</head><body>
<h4 class="mb-3"><i class="bi bi-calendar-check-fill text-danger"></i> Edit Event</h4>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="card p-4">
  <div class="row g-3">
    <div class="col-md-6">
      <label>Event Title *</label>
      <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($event['title']) ?>" required>
    </div>
    <div class="col-md-6">
      <label>Subtitle</label>
      <input type="text" name="subtitle" class="form-control" value="<?= htmlspecialchars($event['subtitle'] ?? '') ?>">
    </div>

    <div class="col-12">
      <label>Description</label>
      <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
    </div>

    <div class="col-12">
      <label>Banner Image</label><br>
      <img class="img-preview" src="../../uploads/events/<?= htmlspecialchars($event['image']) ?>" alt="Current image">
      <input type="file" name="image" class="form-control mt-2" accept="image/jpeg,image/png,image/webp">
      <div class="hint">Leave empty to keep the existing image. JPG, PNG or WEBP — max 5 MB.</div>
    </div>

    <div class="col-md-4">
      <label>Event Date *</label>
      <input type="date" name="event_date" class="form-control" value="<?= htmlspecialchars($event['event_date']) ?>" required>
    </div>
    <div class="col-md-4">
      <label>Event Time</label>
      <input type="text" name="event_time" class="form-control" value="<?= htmlspecialchars($event['event_time'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label>Event Type</label>
      <input type="text" name="event_type" class="form-control" value="<?= htmlspecialchars($event['event_type'] ?? '') ?>">
    </div>

    <div class="col-md-6">
      <label>Event Location</label>
      <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($event['location'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label>Registration Link</label>
      <input type="text" name="registration_link" class="form-control" value="<?= htmlspecialchars($event['registration_link'] ?? '') ?>">
      <div class="hint">Leave empty to hide the CTA button automatically on the homepage.</div>
    </div>

    <div class="col-md-6">
      <label>Button Text</label>
      <input type="text" name="button_text" class="form-control" value="<?= htmlspecialchars($event['button_text'] ?? 'Register Now') ?>">
    </div>
    <div class="col-md-3">
      <label>Display Order</label>
      <input type="number" name="display_order" class="form-control" value="<?= htmlspecialchars($event['display_order']) ?>">
    </div>

    <div class="col-12 mt-2 d-flex gap-4">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="published" id="pub" <?= $event['published'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="pub">Published</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="featured" id="feat" <?= $event['featured'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="feat">Featured (shown on homepage)</label>
      </div>
    </div>

    <div class="col-12 mt-3">
      <button type="submit" class="btn btn-red px-4"><i class="bi bi-check-circle"></i> Update Event</button>
      <a href="manage.php" class="btn btn-outline-light px-4">Cancel</a>
    </div>
  </div>
</form>
</body></html>