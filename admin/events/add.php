<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../../auth/login.php");
    exit();
}
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/events_helper.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $event_time = trim($_POST['event_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $event_type = trim($_POST['event_type'] ?? '');
    $registration_link = trim($_POST['registration_link'] ?? '');
    $button_text = trim($_POST['button_text'] ?? '') ?: 'Register Now';
    $display_order = $_POST['display_order'] !== '' ? (int) $_POST['display_order'] : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $published = isset($_POST['published']) ? 1 : 0;

    if ($title === '' || $event_date === '') {
        $error = 'Event Title and Event Date are required.';
    } elseif ($registration_link !== '' && !filter_var($registration_link, FILTER_VALIDATE_URL)) {
        $error = 'Registration Link must be a valid URL (e.g. https://...).';
    } else {
        $upload = uploadEventImage('image');

        if (isset($upload['error'])) {
            $error = $upload['error'];
        } else {
            $image = $upload['filename'];

            $stmt = $conn->prepare("INSERT INTO events
                (title, subtitle, description, image, event_date, event_time, location, event_type,
                 registration_link, button_text, display_order, featured, published)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param(
                "ssssssssssiii",
                $title,
                $subtitle,
                $description,
                $image,
                $event_date,
                $event_time,
                $location,
                $event_type,
                $registration_link,
                $button_text,
                $display_order,
                $featured,
                $published
            );
            $stmt->execute();

            header("Location: manage.php?msg=added");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: #181818;
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }

        .card {
            background: #202020;
            border: 1px solid #383838;
            border-radius: 12px;
        }

        label {
            color: #aaa;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .form-control {
            background: #181818;
            border: 1px solid #383838;
            color: #fff;
        }

        .form-control:focus {
            background: #181818;
            border-color: #ff0000;
            color: #fff;
            box-shadow: none;
        }

        .btn-red {
            background: #ff0000;
            border: none;
            color: #fff;
        }

        .btn-red:hover {
            background: #cc0000;
            color: #fff;
        }

        .form-check-label {
            color: #ccc;
            font-size: 13px;
        }

        .hint {
            color: #777;
            font-size: 12px;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    <h4 class="mb-3"><i class="bi bi-calendar-plus-fill text-danger"></i> Add Event</h4>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="card p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label>Event Title *</label>
                <input type="text" name="title" class="form-control"
                    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label>Subtitle</label>
                <input type="text" name="subtitle" class="form-control"
                    value="<?= htmlspecialchars($_POST['subtitle'] ?? '') ?>">
            </div>

            <div class="col-12">
                <label>Description</label>
                <textarea name="description" class="form-control"
                    rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <label>Banner Image *</label>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                <div class="hint">JPG, PNG or WEBP — max 5 MB. Landscape banner image recommended.</div>
            </div>

            <div class="col-md-4">
                <label>Event Date *</label>
                <input type="date" name="event_date" class="form-control"
                    value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label>Event Time</label>
                <input type="text" name="event_time" class="form-control" placeholder="e.g. 10:00 AM - 4:00 PM"
                    value="<?= htmlspecialchars($_POST['event_time'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label>Event Type</label>
                <input type="text" name="event_type" class="form-control" placeholder="e.g. Workshop, Webinar"
                    value="<?= htmlspecialchars($_POST['event_type'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label>Event Location</label>
                <input type="text" name="location" class="form-control"
                    value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label>Registration Link</label>
                <input type="text" name="registration_link" class="form-control" placeholder="https://..."
                    value="<?= htmlspecialchars($_POST['registration_link'] ?? '') ?>">
                <div class="hint">Leave empty to hide the CTA button automatically on the homepage.</div>
            </div>

            <div class="col-md-6">
                <label>Button Text</label>
                <input type="text" name="button_text" class="form-control" placeholder="Register Now"
                    value="<?= htmlspecialchars($_POST['button_text'] ?? 'Register Now') ?>">
            </div>
            <div class="col-md-3">
                <label>Display Order</label>
                <input type="number" name="display_order" class="form-control"
                    value="<?= htmlspecialchars($_POST['display_order'] ?? '0') ?>">
            </div>

            <div class="col-12 mt-2 d-flex gap-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="published" id="pub" checked>
                    <label class="form-check-label" for="pub">Published</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="featured" id="feat">
                    <label class="form-check-label" for="feat">Featured (shown on homepage)</label>
                </div>
            </div>

            <div class="col-12 mt-3">
                <button type="submit" class="btn btn-red px-4"><i class="bi bi-check-circle"></i> Save Event</button>
                <a href="manage.php" class="btn btn-outline-light px-4">Cancel</a>
            </div>
        </div>
    </form>
</body>

</html>