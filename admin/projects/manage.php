<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
  header("Location: ../../auth/login.php");
  exit();
}
require_once __DIR__ . '/../../includes/db_connect.php';

if (isset($_GET['toggle'])) {
  $id = (int) $_GET['toggle'];
  $conn->query("UPDATE projects SET is_published = 1 - is_published WHERE id = $id");
  header("Location: manage.php");
  exit();
}
if (isset($_GET['delete'])) {
  $id = (int) $_GET['delete'];
  $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  header("Location: manage.php");
  exit();
}

$result = $conn->query("SELECT p.id, p.slug, p.title, p.sector_raw, p.location, p.min_price, p.max_price, p.is_published, p.is_featured, p.brochure_path, b.name AS builder_name
                         FROM projects p JOIN builders b ON b.id = p.builder_id
                         ORDER BY p.title ASC");
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Projects</title>
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

    table {
      color: #ddd;
    }

    thead th {
      color: #aaa;
      border-bottom: 1px solid #383838 !important;
      font-size: 12px;
      text-transform: uppercase;
    }

    tbody td {
      border-color: #2c2c2c !important;
      vertical-align: middle;
    }

    .badge-pub {
      background: #1e7e34;
    }

    .badge-hide {
      background: #555;
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

    a.icon-btn {
      color: #ccc;
      text-decoration: none;
      margin-right: 8px;
    }

    a.icon-btn:hover {
      color: #fff;
    }
  </style>
</head>

<body>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="bi bi-building-fill text-danger"></i> Manage Projects</h4>
    <a href="add.php" class="btn btn-red"><i class="bi bi-plus-circle"></i> Add Project</a>
  </div>
  <?php if (isset($_GET['brochure_msg'])): ?>
    <div class="alert alert-success">
      <?= $_GET['brochure_msg'] === 'success' ? 'Brochure uploaded successfully.' : 'Brochure deleted successfully.' ?>
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">Project updated successfully.</div>
  <?php endif; ?>
  <div class="card p-3">
    <table class="table table-borderless mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Builder</th>
          <th>Sector</th>
          <th>Location</th>
          <th>Price</th>
          <th>Status</th>
          <th>Brochure</th>
          <th>Action</th>
        </tr>
      </thead>/thead>
      <tbody>
        <?php $i = 1;
        while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['title']) ?><?php if ($row['is_featured']): ?> <span
                  class="badge bg-warning text-dark">Featured</span><?php endif; ?></td>
            <td><?= htmlspecialchars($row['builder_name']) ?></td>
            <td><?= htmlspecialchars($row['sector_raw']) ?></td>
            <td><?= htmlspecialchars($row['location']) ?></td>
            <td>₹<?= htmlspecialchars($row['min_price']) ?> - <?= htmlspecialchars($row['max_price']) ?> Cr</td>
            <td>
              <?php if ($row['is_published']): ?>
                <span class="badge badge-pub">Published</span>
              <?php else: ?>
                <span class="badge badge-hide">Hidden</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($row['brochure_path'])): ?>
                <span class="badge badge-pub">Uploaded</span><br>
                <a class="icon-btn" href="brochure_manage.php?id=<?= $row['id'] ?>" title="Replace Brochure"><i
                    class="bi bi-arrow-repeat"></i></a>
                <a class="icon-btn text-danger" href="brochure_delete.php?id=<?= $row['id'] ?>" title="Delete Brochure"
                  onclick="return confirm('Delete this project\'s brochure?');"><i class="bi bi-trash-fill"></i></a>
              <?php else: ?>
                <span class="badge badge-hide">Not Uploaded</span><br>
                <a class="icon-btn" href="brochure_manage.php?id=<?= $row['id'] ?>" title="Upload Brochure"><i
                    class="bi bi-upload"></i></a>
              <?php endif; ?>
            </td>
            <td>
              <a class="icon-btn" href="../../pages/project-details.php?slug=<?= urlencode($row['slug']) ?>"
                target="_blank" title="View Project Page"><i class="bi bi-box-arrow-up-right"></i></a>
              <a class="icon-btn" href="edit.php?id=<?= $row['id'] ?>" title="Edit Project"><i
                  class="bi bi-pencil-fill"></i></a>
              <a class="icon-btn" href="gallery_manage.php?id=<?= $row['id'] ?>" title="Manage Gallery"><i
                  class="bi bi-images"></i></a>
              <a class="icon-btn" href="manage.php?toggle=<?= $row['id'] ?>" title="Hide/Publish"><i
                  class="bi bi-eye-fill"></i></a>
              <a class="icon-btn text-danger" href="manage.php?delete=<?= $row['id'] ?>" title="Delete"
                onclick="return confirm('Delete this project?');"><i class="bi bi-trash-fill"></i></a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>

</html>