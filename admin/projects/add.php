<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
  header("Location: ../../auth/login.php");
  exit();
}
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/maps_helper.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $builder_name = trim($_POST['builder'] ?? '');
  $title = trim($_POST['title'] ?? '');
  $sector = trim($_POST['sector'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $lat = $_POST['lat'] !== '' ? (float) $_POST['lat'] : null;
  $lng = $_POST['lng'] !== '' ? (float) $_POST['lng'] : null;
  $google_maps_link = trim($_POST['google_maps_link'] ?? '');
  if ($google_maps_link !== '' && !isValidGoogleMapsUrl($google_maps_link)) {
    $error = 'Please enter a valid Google Maps URL (e.g. https://maps.app.goo.gl/... or https://maps.google.com/...).';
  }
  $land = trim($_POST['land'] ?? '');
  $land_num = $_POST['land_num'] !== '' ? (float) $_POST['land_num'] : null;
  $towers = trim($_POST['towers'] ?? '');
  $height = trim($_POST['height'] ?? '');
  $clubhouse = trim($_POST['clubhouse'] ?? '');
  $possession = trim($_POST['possession'] ?? '');
  $payment_plan = trim($_POST['payment_plan'] ?? '');
  $usp_raw = trim($_POST['usp_raw'] ?? '');
  $min_price = $_POST['min_price'] !== '' ? (float) $_POST['min_price'] : null;
  $max_price = $_POST['max_price'] !== '' ? (float) $_POST['max_price'] : null;
  $is_featured = isset($_POST['is_featured']) ? 1 : 0;
  $is_luxury = isset($_POST['is_luxury']) ? 1 : 0;
  $is_published = isset($_POST['is_published']) ? 1 : 0;

  if ($title === '' || $sector === '' || $location === '' || $builder_name === '') {
    $error = 'Builder, Project Name, Sector and Location are required.';
  } elseif ($error !== '') {
    // Google Maps URL validation error already set above — fall through, don't overwrite it.
  } else {
    $slug = strtolower(trim($title . '-' . $sector));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    $stmt = $conn->prepare("SELECT id FROM builders WHERE name = ?");
    $stmt->bind_param("s", $builder_name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
      $builder_id = $res->fetch_assoc()['id'];
    } else {
      $stmt2 = $conn->prepare("INSERT INTO builders (name) VALUES (?)");
      $stmt2->bind_param("s", $builder_name);
      $stmt2->execute();
      $builder_id = $stmt2->insert_id;
    }

    $cover_filename = null;
    if (!empty($_FILES['cover_image']['name'])) {
      $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
      if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        $cover_filename = $slug . '.' . $ext;
        move_uploaded_file($_FILES['cover_image']['tmp_name'], __DIR__ . '/../../assets/img/projects/' . $cover_filename);
      }
    }

    $stmt = $conn->prepare("INSERT INTO projects
            (builder_id, title, slug, sector_raw, location, lat, lng, google_maps_link, land, land_num, towers, height, clubhouse, possession_raw, payment_plan, usp_raw, min_price, max_price, cover_image, is_featured, is_luxury, is_published)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param(
      "issssddssdssssssddsiii",
      $builder_id,
      $title,
      $slug,
      $sector,
      $location,
      $lat,
      $lng,
      $google_maps_link,
      $land,
      $land_num,
      $towers,
      $height,
      $clubhouse,
      $possession,
      $payment_plan,
      $usp_raw,
      $min_price,
      $max_price,
      $cover_filename,
      $is_featured,
      $is_luxury,
      $is_published
    );
    $stmt->execute();
    $project_id = $stmt->insert_id;

    if (!empty($_POST['config_size'])) {
      $sizes = $_POST['config_size'];
      $typologies = $_POST['config_typology'] ?? [];
      $prices = $_POST['config_price'] ?? [];
      $stmtC = $conn->prepare("INSERT INTO project_configurations (project_id, config_size, typology, price_label, sort_order) VALUES (?,?,?,?,?)");
      foreach ($sizes as $idx => $sz) {
        $sz = trim($sz);
        if ($sz === '')
          continue;
        $ty = trim($typologies[$idx] ?? '');
        $pr = trim($prices[$idx] ?? '');
        $stmtC->bind_param("isssi", $project_id, $sz, $ty, $pr, $idx);
        $stmtC->execute();
      }
    }



    if (!empty($_POST['amenities'])) {
      $stmtA = $conn->prepare("INSERT INTO project_amenities (project_id, amenity_id) VALUES (?,?)");
      foreach ($_POST['amenities'] as $amenity_id) {
        $amenity_id = (int) $amenity_id;
        $stmtA->bind_param("ii", $project_id, $amenity_id);
        $stmtA->execute();
      }
    }

    header("Location: manage.php");
    exit();
  }
}

$amenities = $conn->query("SELECT id, name FROM amenities ORDER BY name");
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Project</title>
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

    .form-control,
    .form-select {
      background: #181818;
      border: 1px solid #383838;
      color: #fff;
    }

    .form-control:focus,
    .form-select:focus {
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

    .cfg-row {
      background: #181818;
      border: 1px solid #383838;
      border-radius: 8px;
      padding: 10px;
      margin-bottom: 8px;
    }

    .form-check-label {
      color: #ccc;
      font-size: 13px;
    }
  </style>
</head>

<body>
  <h4 class="mb-3"><i class="bi bi-plus-circle-fill text-danger"></i> Add Project</h4>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="card p-4">
    <div class="row g-3">
      <div class="col-md-4"><label>Builder *</label><input type="text" name="builder" class="form-control" required>
      </div>
      <div class="col-md-4"><label>Project Name *</label><input type="text" name="title" class="form-control" required>
      </div>
      <div class="col-md-4"><label>Sector *</label><input type="text" name="sector" class="form-control"
          placeholder="Sec-113" required></div>

      <div class="col-md-4"><label>Location *</label><input type="text" name="location" class="form-control" required>
      </div>
      <div class="col-md-4"><label>Latitude</label><input type="text" name="lat" class="form-control"></div>
      <div class="col-md-4"><label>Longitude</label><input type="text" name="lng" class="form-control"></div>
      <div class="col-md-12"><label>Google Maps Link</label><input type="text" name="google_maps_link"
          class="form-control"
          placeholder="https://maps.app.goo.gl/xxxxxxx or https://maps.google.com/?q=28.4595,77.0266"
          value="<?= htmlspecialchars($_POST['google_maps_link'] ?? '') ?>"></div>


      <div class="col-md-3"><label>Land Area (display)</label><input type="text" name="land" class="form-control"
          placeholder="16 Acres"></div>
      <div class="col-md-3"><label>Land Area (acres, numeric)</label><input type="text" name="land_num"
          class="form-control" placeholder="16.0"></div>
      <div class="col-md-3"><label>No. of Towers</label><input type="text" name="towers" class="form-control"></div>
      <div class="col-md-3"><label>Height</label><input type="text" name="height" class="form-control"
          placeholder="G+29"></div>

      <div class="col-md-6"><label>Clubhouse</label><input type="text" name="clubhouse" class="form-control"></div>
      <div class="col-md-6"><label>Possession</label><input type="text" name="possession" class="form-control"
          placeholder="2030"></div>

      <div class="col-md-6"><label>Payment Plan</label><input type="text" name="payment_plan" class="form-control">
      </div>
      <div class="col-md-3"><label>Minimum Price (Cr)</label><input type="text" name="min_price" class="form-control">
      </div>
      <div class="col-md-3"><label>Maximum Price (Cr)</label><input type="text" name="max_price" class="form-control">
      </div>

      <div class="col-12"><label>USP (one point per line)</label>
        <textarea name="usp_raw" class="form-control" rows="4" placeholder="1. Point one&#10;2. Point two"></textarea>
      </div>

      <div class="col-12 d-flex gap-4 mt-2">
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_featured" id="feat"><label
            class="form-check-label" for="feat">Featured</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_luxury" id="lux"><label
            class="form-check-label" for="lux">Luxury</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_published" id="pub"
            checked><label class="form-check-label" for="pub">Published</label></div>
      </div>

      <div class="col-12 mt-3">
        <hr style="border-color:#383838;"><label class="fw-bold text-white">Configurations</label>
        <div id="cfgWrap"></div>
        <button type="button" class="btn btn-sm btn-outline-light mt-2" onclick="addCfgRow()"><i class="bi bi-plus"></i>
          Add Configuration</button>
      </div>

      <div class="col-12 mt-3">
        <hr style="border-color:#383838;"><label class="fw-bold text-white">Amenities</label><br>
        <?php while ($a = $amenities->fetch_assoc()): ?>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $a['id'] ?>"
              id="am<?= $a['id'] ?>">
            <label class="form-check-label" for="am<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></label>
          </div>
        <?php endwhile; ?>
      </div>

      <div class="col-12 mt-3">
        <hr style="border-color:#383838;">
        <label>Cover Image</label><input type="file" name="cover_image" class="form-control mb-3"
          accept=".jpg,.jpeg,.png,.webp">

      </div>

      <div class="col-12 mt-4">
        <button type="submit" class="btn btn-red px-4"><i class="bi bi-check-circle"></i> Save Project</button>
        <a href="manage.php" class="btn btn-outline-light px-4">Cancel</a>
      </div>
    </div>
  </form>

  <script>
    function addCfgRow() {
      const wrap = document.getElementById('cfgWrap');
      const row = document.createElement('div');
      row.className = 'cfg-row row g-2';
      row.innerHTML = `
    <div class="col-md-4"><input type="text" name="config_size[]" class="form-control" placeholder="3 BHK"></div>
    <div class="col-md-4"><input type="text" name="config_typology[]" class="form-control" placeholder="2500 Sq. ft."></div>
    <div class="col-md-3"><input type="text" name="config_price[]" class="form-control" placeholder="5 Cr*"></div>
    <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.cfg-row').remove()"><i class="bi bi-x"></i></button></div>`;
      wrap.appendChild(row);
    }
    addCfgRow();
  </script>
</body>

</html>