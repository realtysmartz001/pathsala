<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
  header("Location: ../../auth/login.php");
  exit();
}
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/maps_helper.php';

$error = '';
$project_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($project_id <= 0) {
  header("Location: manage.php");
  exit();
}

// ── Load existing project ──
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
  header("Location: manage.php");
  exit();
}

// ── Load existing builder name (projects stores builder_id, add.php form uses builder name text input) ──
$stmtB = $conn->prepare("SELECT name FROM builders WHERE id = ?");
$stmtB->bind_param("i", $project['builder_id']);
$stmtB->execute();
$builderRow = $stmtB->get_result()->fetch_assoc();
$builder_name_current = $builderRow['name'] ?? '';

// ── Load existing configurations ──
$stmtC = $conn->prepare("SELECT config_size, typology, price_label FROM project_configurations WHERE project_id = ? ORDER BY sort_order");
$stmtC->bind_param("i", $project_id);
$stmtC->execute();
$existingConfigs = $stmtC->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Load existing amenity IDs (to pre-check the boxes) ──
$stmtA = $conn->prepare("SELECT amenity_id FROM project_amenities WHERE project_id = ?");
$stmtA->bind_param("i", $project_id);
$stmtA->execute();
$existingAmenityIds = array_column($stmtA->get_result()->fetch_all(MYSQLI_ASSOC), 'amenity_id');



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
    // ── Resolve / create builder ──
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

    // ── Slug: recompute only if title or sector actually changed, so existing
    //         links / footer slugs are not silently broken on unrelated edits ──
    if ($title !== $project['title'] || $sector !== $project['sector_raw']) {
      $slug = strtolower(trim($title . '-' . $sector));
      $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
      $slug = trim($slug, '-');
    } else {
      $slug = $project['slug'];
    }

    // ── Optional cover image replace ──
    $cover_filename = $project['cover_image'];
    if (!empty($_FILES['cover_image']['name'])) {
      $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
      if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        $new_cover = $slug . '.' . $ext;
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], __DIR__ . '/../../assets/img/projects/' . $new_cover)) {
          $cover_filename = $new_cover;
        }
      }
    }

    $stmt = $conn->prepare("UPDATE projects SET
            builder_id=?, title=?, slug=?, sector_raw=?, location=?, lat=?, lng=?, google_maps_link=?, land=?, land_num=?,
            towers=?, height=?, clubhouse=?, possession_raw=?, payment_plan=?, usp_raw=?,
            min_price=?, max_price=?, cover_image=?, is_featured=?, is_luxury=?, is_published=?
            WHERE id=?");
    $stmt->bind_param(
      "issssddssdssssssddsiiii",
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
      $is_published,
      $project_id
    );
    $stmt->execute();

    // ── Configurations: simplest safe approach — replace all rows ──
    $conn->query("DELETE FROM project_configurations WHERE project_id = " . (int) $project_id);
    if (!empty($_POST['config_size'])) {
      $sizes = $_POST['config_size'];
      $typologies = $_POST['config_typology'] ?? [];
      $prices = $_POST['config_price'] ?? [];
      $stmtC2 = $conn->prepare("INSERT INTO project_configurations (project_id, config_size, typology, price_label, sort_order) VALUES (?,?,?,?,?)");
      foreach ($sizes as $idx => $sz) {
        $sz = trim($sz);
        if ($sz === '')
          continue;
        $ty = trim($typologies[$idx] ?? '');
        $pr = trim($prices[$idx] ?? '');
        $stmtC2->bind_param("isssi", $project_id, $sz, $ty, $pr, $idx);
        $stmtC2->execute();
      }
    }

    // ── Amenities: replace all rows ──
    $conn->query("DELETE FROM project_amenities WHERE project_id = " . (int) $project_id);
    if (!empty($_POST['amenities'])) {
      $stmtA2 = $conn->prepare("INSERT INTO project_amenities (project_id, amenity_id) VALUES (?,?)");
      foreach ($_POST['amenities'] as $amenity_id) {
        $amenity_id = (int) $amenity_id;
        $stmtA2->bind_param("ii", $project_id, $amenity_id);
        $stmtA2->execute();
      }
    }



    header("Location: manage.php?updated=1");
    exit();
  }

  // On error, keep the submitted values visible instead of the DB values
  $project = array_merge($project, $_POST, ['slug' => $slug ?? $project['slug'], 'cover_image' => $cover_filename ?? $project['cover_image']]);
  $builder_name_current = $builder_name;
}



$amenities = $conn->query("SELECT id, name FROM amenities ORDER BY name");
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Project</title>
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

    .hint {
      color: #777;
      font-size: 12px;
      margin-top: 4px;
    }

    .cover-preview {
      width: 180px;
      border-radius: 8px;
      border: 1px solid #383838;
      margin-top: 8px;
    }

    .gallery-thumb-wrap {
      position: relative;
      display: inline-block;
      margin: 0 8px 8px 0;
    }

    .gallery-thumb {
      width: 90px;
      height: 70px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid #383838;
    }

    .gallery-del-btn {
      position: absolute;
      top: -6px;
      right: -6px;
      background: #ff0000;
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 11px;
      line-height: 1;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <h4 class="mb-3"><i class="bi bi-pencil-square text-danger"></i> Edit Project</h4>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="card p-4">
    <div class="row g-3">
      <div class="col-md-4"><label>Builder *</label><input type="text" name="builder" class="form-control"
          value="<?= htmlspecialchars($builder_name_current) ?>" required></div>
      <div class="col-md-4"><label>Project Name *</label><input type="text" name="title" class="form-control"
          value="<?= htmlspecialchars($project['title']) ?>" required></div>
      <div class="col-md-4"><label>Sector *</label><input type="text" name="sector" class="form-control"
          value="<?= htmlspecialchars($project['sector_raw']) ?>" placeholder="Sec-113" required></div>

      <div class="col-md-4"><label>Location *</label><input type="text" name="location" class="form-control"
          value="<?= htmlspecialchars($project['location']) ?>" required></div>
      <div class="col-md-4"><label>Latitude</label><input type="text" name="lat" class="form-control"
          value="<?= htmlspecialchars($project['lat'] ?? '') ?>"></div>
      <div class="col-md-4"><label>Longitude</label><input type="text" name="lng" class="form-control"
          value="<?= htmlspecialchars($project['lng'] ?? '') ?>"></div>
      <div class="col-md-12"><label>Google Maps Link</label><input type="text" name="google_maps_link"
          class="form-control"
          placeholder="https://maps.app.goo.gl/xxxxxxx or https://maps.google.com/?q=28.4595,77.0266"
          value="<?= htmlspecialchars($project['google_maps_link'] ?? '') ?>"></div>
      <div class="col-12">
        <div class="hint"><i class="bi bi-info-circle"></i> Fill in Latitude &amp; Longitude to make this project appear
          as a pin on the Interactive Gurugram Map on the Projects page.</div>
      </div>

      <div class="col-md-3"><label>Land Area (display)</label><input type="text" name="land" class="form-control"
          value="<?= htmlspecialchars($project['land'] ?? '') ?>" placeholder="16 Acres"></div>
      <div class="col-md-3"><label>Land Area (acres, numeric)</label><input type="text" name="land_num"
          class="form-control" value="<?= htmlspecialchars($project['land_num'] ?? '') ?>" placeholder="16.0"></div>
      <div class="col-md-3"><label>No. of Towers</label><input type="text" name="towers" class="form-control"
          value="<?= htmlspecialchars($project['towers'] ?? '') ?>"></div>
      <div class="col-md-3"><label>Height</label><input type="text" name="height" class="form-control"
          value="<?= htmlspecialchars($project['height'] ?? '') ?>" placeholder="G+29"></div>

      <div class="col-md-6"><label>Clubhouse</label><input type="text" name="clubhouse" class="form-control"
          value="<?= htmlspecialchars($project['clubhouse'] ?? '') ?>"></div>
      <div class="col-md-6"><label>Possession</label><input type="text" name="possession" class="form-control"
          value="<?= htmlspecialchars($project['possession_raw'] ?? '') ?>" placeholder="2030"></div>

      <div class="col-md-6"><label>Payment Plan</label><input type="text" name="payment_plan" class="form-control"
          value="<?= htmlspecialchars($project['payment_plan'] ?? '') ?>"></div>
      <div class="col-md-3"><label>Minimum Price (Cr)</label><input type="text" name="min_price" class="form-control"
          value="<?= htmlspecialchars($project['min_price'] ?? '') ?>"></div>
      <div class="col-md-3"><label>Maximum Price (Cr)</label><input type="text" name="max_price" class="form-control"
          value="<?= htmlspecialchars($project['max_price'] ?? '') ?>"></div>

      <div class="col-12"><label>USP (one point per line)</label>
        <textarea name="usp_raw" class="form-control" rows="4"
          placeholder="1. Point one&#10;2. Point two"><?= htmlspecialchars($project['usp_raw'] ?? '') ?></textarea>
      </div>

      <div class="col-12 d-flex gap-4 mt-2">
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_featured" id="feat"
            <?= !empty($project['is_featured']) ? 'checked' : '' ?>><label class="form-check-label"
            for="feat">Featured</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_luxury" id="lux"
            <?= !empty($project['is_luxury']) ? 'checked' : '' ?>><label class="form-check-label"
            for="lux">Luxury</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_published" id="pub"
            <?= !empty($project['is_published']) ? 'checked' : '' ?>><label class="form-check-label"
            for="pub">Published</label></div>
      </div>

      <div class="col-12 mt-3">
        <hr style="border-color:#383838;"><label class="fw-bold text-white">Configurations</label>
        <div id="cfgWrap">
          <?php foreach ($existingConfigs as $cfg): ?>
            <div class="cfg-row row g-2">
              <div class="col-md-4"><input type="text" name="config_size[]" class="form-control"
                  value="<?= htmlspecialchars($cfg['config_size']) ?>" placeholder="3 BHK"></div>
              <div class="col-md-4"><input type="text" name="config_typology[]" class="form-control"
                  value="<?= htmlspecialchars($cfg['typology']) ?>" placeholder="2500 Sq. ft."></div>
              <div class="col-md-3"><input type="text" name="config_price[]" class="form-control"
                  value="<?= htmlspecialchars($cfg['price_label']) ?>" placeholder="5 Cr*"></div>
              <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger"
                  onclick="this.closest('.cfg-row').remove()"><i class="bi bi-x"></i></button></div>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light mt-2" onclick="addCfgRow()"><i class="bi bi-plus"></i>
          Add Configuration</button>
      </div>

      <div class="col-12 mt-3">
        <hr style="border-color:#383838;"><label class="fw-bold text-white">Amenities</label><br>
        <?php while ($a = $amenities->fetch_assoc()): ?>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $a['id'] ?>"
              id="am<?= $a['id'] ?>" <?= in_array($a['id'], $existingAmenityIds) ? 'checked' : '' ?>>
            <label class="form-check-label" for="am<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></label>
          </div>
        <?php endwhile; ?>
      </div>

      <div class="col-12 mt-3">
        <hr style="border-color:#383838;">
        <label>Cover Image</label><br>
        <?php if (!empty($project['cover_image'])): ?>
          <img class="cover-preview" src="../../assets/img/projects/<?= htmlspecialchars($project['cover_image']) ?>"
            alt="Current cover">
        <?php endif; ?>
        <input type="file" name="cover_image" class="form-control mt-2 mb-3" accept=".jpg,.jpeg,.png,.webp">
        <div class="hint">Leave empty to keep the existing cover image.</div>

        <label class="mt-3 d-block">Project Gallery</label>
        <a href="gallery_manage.php?id=<?= $project_id ?>" class="btn btn-outline-light"><i class="bi bi-images"></i>
          Manage Gallery Images</a>
        <div class="hint">Upload, reorder, set a featured image, and edit alt text on the dedicated Gallery Management
          page.</div>
      </div>

      <div class="col-12 mt-4">
        <button type="submit" class="btn btn-red px-4"><i class="bi bi-check-circle"></i> Update Project</button>
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
  </script>
</body>

</html>