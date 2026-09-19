<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../../auth/login.php");
    exit();
}
require_once __DIR__ . '/../../includes/db_connect.php';

// Toggle publish/unpublish
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $conn->query("UPDATE events SET published = 1 - published WHERE id = $id");
    header("Location: manage.php");
    exit();
}

// Toggle featured
if (isset($_GET['togglefeatured'])) {
    $id = (int) $_GET['togglefeatured'];
    $conn->query("UPDATE events SET featured = 1 - featured WHERE id = $id");
    header("Location: manage.php");
    exit();
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $conn->prepare("SELECT image FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row && !empty($row['image'])) {
        $imgPath = __DIR__ . '/../../uploads/events/' . $row['image'];
        if (is_file($imgPath)) {
            @unlink($imgPath);
        }
    }

    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage.php?msg=deleted");
    exit();
}

// Quick reorder (move up / move down) — swaps display_order with neighbour
if (isset($_GET['move']) && isset($_GET['dir'])) {
    $id  = (int) $_GET['move'];
    $dir = $_GET['dir'] === 'up' ? 'up' : 'down';

    $stmt = $conn->prepare("SELECT id, display_order FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();

    if ($current) {
        if ($dir === 'up') {
            $neighbourQ = $conn->prepare("SELECT id, display_order FROM events WHERE display_order < ? ORDER BY display_order DESC LIMIT 1");
        } else {
            $neighbourQ = $conn->prepare("SELECT id, display_order FROM events WHERE display_order > ? ORDER BY display_order ASC LIMIT 1");
        }
        $neighbourQ->bind_param("i", $current['display_order']);
        $neighbourQ->execute();
        $neighbour = $neighbourQ->get_result()->fetch_assoc();

        if ($neighbour) {
            $swap = $conn->prepare("UPDATE events SET display_order = ? WHERE id = ?");
            $swap->bind_param("ii", $neighbour['display_order'], $current['id']);
            $swap->execute();
            $swap->bind_param("ii", $current['display_order'], $neighbour['id']);
            $swap->execute();
        }
    }
    header("Location: manage.php");
    exit();
}

$result = $conn->query("SELECT id, title, image, event_date, location, display_order, featured, published
                         FROM events
                         ORDER BY display_order ASC, id DESC");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Events</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body { background:#181818; color:#fff; font-family:'Segoe UI',sans-serif; padding:20px; }
    .card { background:#202020; border:1px solid #383838; border-radius:12px; }
    table { color:#ddd; }
    thead th { color:#aaa; border-bottom:1px solid #383838 !important; font-size:12px; text-transform:uppercase; }
    tbody td { border-color:#2c2c2c !important; vertical-align:middle; }
    .badge-pub { background:#1e7e34; }
    .badge-hide { background:#555; }
    .badge-feat { background:#b8860b; }
    .btn-red { background:#ff0000; border:none; color:#fff; }
    .btn-red:hover { background:#cc0000; color:#fff; }
    a.icon-btn { color:#ccc; text-decoration:none; margin-right:8px; }
    a.icon-btn:hover { color:#fff; }
    .thumb { width:80px; height:50px; object-fit:cover; border-radius:6px; border:1px solid #383838; }
  </style>
</head>
<body>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="bi bi-calendar-event-fill text-danger"></i> Manage Events</h4>
    <a href="add.php" class="btn btn-red"><i class="bi bi-plus-circle"></i> Add Event</a>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
      <?= $_GET['msg'] === 'added' ? 'Event added successfully.' : 'Event deleted successfully.' ?>
    </div>
  <?php endif; ?>

  <div class="card p-3">
    <table class="table table-borderless mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Image</th>
          <th>Title</th>
          <th>Date</th>
          <th>Location</th>
          <th>Order</th>
          <th>Featured</th>
          <th>Published</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><img class="thumb" src="../../uploads/events/<?= htmlspecialchars($row['image']) ?>" alt=""></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars(date('d M Y', strtotime($row['event_date']))) ?></td>
            <td><?= htmlspecialchars($row['location']) ?></td>
            <td>
              <?= (int) $row['display_order'] ?>
              &nbsp;
              <a class="icon-btn" href="manage.php?move=<?= $row['id'] ?>&dir=up" title="Move Up"><i class="bi bi-arrow-up-circle"></i></a>
              <a class="icon-btn" href="manage.php?move=<?= $row['id'] ?>&dir=down" title="Move Down"><i class="bi bi-arrow-down-circle"></i></a>
            </td>
            <td>
              <a class="icon-btn" href="manage.php?togglefeatured=<?= $row['id'] ?>" title="Toggle Featured">
                <?php if ($row['featured']): ?>
                  <span class="badge badge-feat">Featured</span>
                <?php else: ?>
                  <span class="badge badge-hide">No</span>
                <?php endif; ?>
              </a>
            </td>
            <td>
              <?php if ($row['published']): ?>
                <span class="badge badge-pub">Published</span>
              <?php else: ?>
                <span class="badge badge-hide">Hidden</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="icon-btn" href="edit.php?id=<?= $row['id'] ?>" title="Edit"><i class="bi bi-pencil-fill"></i></a>
              <a class="icon-btn" href="manage.php?toggle=<?= $row['id'] ?>" title="Publish/Unpublish"><i class="bi bi-eye-fill"></i></a>
              <a class="icon-btn text-danger" href="manage.php?delete=<?= $row['id'] ?>" title="Delete"
                 onclick="return confirm('Delete this event?');"><i class="bi bi-trash-fill"></i></a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>