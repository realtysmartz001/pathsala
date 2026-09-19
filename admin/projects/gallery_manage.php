<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../../auth/login.php");
    exit();
}
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/gallery_helper.php';

$project_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($project_id <= 0) {
    header("Location: manage.php");
    exit();
}

$stmt = $conn->prepare("SELECT id, title, slug FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header("Location: manage.php");
    exit();
}

$error = '';

// ── Handle plain-form upload (progressive-enhancement fallback if JS/AJAX is unavailable) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $maxOrderRow = $conn->prepare("SELECT COALESCE(MAX(display_order), -1) AS max_order FROM project_gallery WHERE project_id = ?");
    $maxOrderRow->bind_param("i", $project_id);
    $maxOrderRow->execute();
    $nextOrder = (int) $maxOrderRow->get_result()->fetch_assoc()['max_order'] + 1;

    $stmtIns = $conn->prepare("INSERT INTO project_gallery (project_id, image_path, alt_text, display_order, is_featured) VALUES (?,?,?,?,0)");

    foreach ($_FILES['images']['name'] as $idx => $name) {
        if ($name === '') continue;

        $singleFile = [
            'name'     => $_FILES['images']['name'][$idx],
            'type'     => $_FILES['images']['type'][$idx],
            'tmp_name' => $_FILES['images']['tmp_name'][$idx],
            'error'    => $_FILES['images']['error'][$idx],
            'size'     => $_FILES['images']['size'][$idx],
        ];

        $result = uploadGalleryImage($singleFile, $project['slug']);

        if (isset($result['error'])) {
            $error .= $result['error'] . ' ';
            continue;
        }

        $altText = pathinfo($name, PATHINFO_FILENAME);
        $stmtIns->bind_param("issi", $project_id, $result['filename'], $altText, $nextOrder);
        $stmtIns->execute();
        $nextOrder++;
    }

    header("Location: gallery_manage.php?id=" . $project_id . ($error ? '&upload_error=1' : '&uploaded=1'));
    exit();
}

$stmtG = $conn->prepare("SELECT id, image_path, alt_text, display_order, is_featured FROM project_gallery WHERE project_id = ? ORDER BY is_featured DESC, display_order ASC");
$stmtG->bind_param("i", $project_id);
$stmtG->execute();
$galleryImages = $stmtG->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Gallery</title>
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
.hint{color:#777;font-size:12px;margin-top:4px;}

.drop-zone{
  border:2px dashed #383838;border-radius:12px;padding:40px 20px;text-align:center;
  cursor:pointer;transition:all .2s;background:#181818;
}
.drop-zone.dragover{border-color:#ff0000;background:rgba(255,0,0,0.06);}
.drop-zone i{font-size:36px;color:#ff0000;margin-bottom:10px;display:block;}
.drop-zone p{color:#aaa;margin:0;font-size:14px;}
#fileInput{display:none;}

#previewWrap{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px;}
.preview-thumb{width:90px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #383838;}

.gallery-manage-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-top:16px;}
.gal-item{
  background:#181818;border:1px solid #383838;border-radius:10px;overflow:hidden;
  cursor:grab;position:relative;transition:border-color .2s;
}
.gal-item.dragging{opacity:0.4;}
.gal-item.drag-over{border-color:#ff0000;}
.gal-item img{width:100%;height:130px;object-fit:cover;display:block;}
.gal-item-body{padding:8px;}
.gal-item .featured-badge{
  position:absolute;top:8px;left:8px;background:#b8860b;color:#fff;font-size:10px;
  font-weight:700;padding:3px 8px;border-radius:100px;z-index:2;
}
.gal-alt-input{
  width:100%;background:#0d0d0d;border:1px solid #383838;color:#fff;font-size:11px;
  padding:4px 6px;border-radius:4px;margin-bottom:6px;
}
.gal-actions{display:flex;gap:4px;}
.gal-actions button{
  flex:1;font-size:11px;padding:4px 6px;border-radius:4px;border:1px solid #383838;
  background:#202020;color:#ccc;cursor:pointer;
}
.gal-actions button:hover{background:#383838;color:#fff;}
.gal-actions button.set-featured.active{background:#b8860b;border-color:#b8860b;color:#fff;}
.gal-actions button.del-btn:hover{background:#ff0000;border-color:#ff0000;color:#fff;}
.drag-handle{cursor:grab;color:#666;font-size:12px;padding:4px 0;text-align:center;}
</style>
</head><body>
<h4 class="mb-1"><i class="bi bi-images text-danger"></i> Manage Gallery</h4>
<p class="text-muted mb-3">Project: <strong class="text-white"><?= htmlspecialchars($project['title']) ?></strong></p>

<?php if (isset($_GET['uploaded'])): ?>
  <div class="alert alert-success">Images uploaded successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['upload_error']) || $error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars(trim($error)) ?: 'Some files could not be uploaded. Please check the format/size and try again.' ?></div>
<?php endif; ?>

<div class="card p-4 mb-4">
  <form method="POST" enctype="multipart/form-data" id="uploadForm">
    <div class="drop-zone" id="dropZone">
      <i class="bi bi-cloud-arrow-up-fill"></i>
      <p><strong>Drag &amp; drop images here</strong>, or click to select</p>
      <p class="hint mt-1">JPG, PNG or WEBP — max 10 MB per image. You can select multiple files at once.</p>
    </div>
    <input type="file" name="images[]" id="fileInput" accept="image/jpeg,image/png,image/webp" multiple>
    <div id="previewWrap"></div>
    <button type="submit" class="btn btn-red px-4 mt-3" id="uploadBtn" disabled><i class="bi bi-upload"></i> Upload Selected Images</button>
    <a href="manage.php" class="btn btn-outline-light px-4 mt-3">Back to Projects</a>
  </form>
</div>

<div class="card p-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <label class="fw-bold text-white mb-0">Existing Gallery Images (<?= count($galleryImages) ?>)</label>
    <span class="hint mb-0"><i class="bi bi-arrows-move"></i> Drag tiles to reorder — saved automatically</span>
  </div>

  <?php if (empty($galleryImages)): ?>
    <p class="hint">No images uploaded yet for this project.</p>
  <?php else: ?>
    <div class="gallery-manage-grid" id="galGrid">
      <?php foreach ($galleryImages as $g): ?>
        <div class="gal-item" draggable="true" data-id="<?= $g['id'] ?>">
          <div class="drag-handle"><i class="bi bi-grip-horizontal"></i></div>
          <?php if ($g['is_featured']): ?><span class="featured-badge">Featured</span><?php endif; ?>
          <img src="../../uploads/project_gallery/<?= htmlspecialchars($g['image_path']) ?>" alt="<?= htmlspecialchars($g['alt_text'] ?? '') ?>">
          <div class="gal-item-body">
            <input type="text" class="gal-alt-input" placeholder="Alt text" value="<?= htmlspecialchars($g['alt_text'] ?? '') ?>" data-id="<?= $g['id'] ?>" data-original="<?= htmlspecialchars($g['alt_text'] ?? '') ?>">
            <div class="gal-actions">
              <button type="button" class="set-featured <?= $g['is_featured'] ? 'active' : '' ?>" data-id="<?= $g['id'] ?>"><i class="bi bi-star-fill"></i></button>
              <button type="button" class="del-btn" data-id="<?= $g['id'] ?>"><i class="bi bi-trash-fill"></i></button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
const dropZone   = document.getElementById('dropZone');
const fileInput  = document.getElementById('fileInput');
const previewWrap= document.getElementById('previewWrap');
const uploadBtn  = document.getElementById('uploadBtn');
const projectId  = <?= (int) $project_id ?>;

// ── Drag & drop + click-to-select ──
dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropZone.classList.remove('dragover');
  fileInput.files = e.dataTransfer.files;
  renderPreviews();
});
fileInput.addEventListener('change', renderPreviews);

function renderPreviews() {
  previewWrap.innerHTML = '';
  const files = fileInput.files;
  if (!files.length) { uploadBtn.disabled = true; return; }
  uploadBtn.disabled = false;
  Array.from(files).forEach(file => {
    if (!file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = document.createElement('img');
      img.className = 'preview-thumb';
      img.src = e.target.result;
      previewWrap.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

// ── Set Featured ──
document.querySelectorAll('.set-featured').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.id;
    fetch('gallery_featured_handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id=' + encodeURIComponent(id) + '&project_id=' + projectId
    }).then(r => r.json()).then(data => {
      if (data.success) location.reload();
      else alert(data.error || 'Failed to set featured image.');
    });
  });
});

// ── Delete ──
document.querySelectorAll('.del-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    if (!confirm('Delete this image?')) return;
    const id = btn.dataset.id;
    fetch('gallery_delete_handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id=' + encodeURIComponent(id) + '&project_id=' + projectId
    }).then(r => r.json()).then(data => {
      if (data.success) btn.closest('.gal-item').remove();
      else alert(data.error || 'Failed to delete image.');
    });
  });
});

// ── Alt text save on blur ──
document.querySelectorAll('.gal-alt-input').forEach(input => {
  input.addEventListener('blur', () => {
    if (input.value === input.dataset.original) return;
    const id = input.dataset.id;
    fetch('gallery_alt_handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id=' + encodeURIComponent(id) + '&project_id=' + projectId + '&alt_text=' + encodeURIComponent(input.value)
    }).then(r => r.json()).then(data => {
      if (data.success) input.dataset.original = input.value;
    });
  });
});

// ── Drag-to-reorder ──
const galGrid = document.getElementById('galGrid');
let dragEl = null;

if (galGrid) {
  galGrid.querySelectorAll('.gal-item').forEach(item => {
    item.addEventListener('dragstart', () => {
      dragEl = item;
      item.classList.add('dragging');
    });
    item.addEventListener('dragend', () => {
      item.classList.remove('dragging');
      saveOrder();
    });
    item.addEventListener('dragover', (e) => {
      e.preventDefault();
      const afterEl = getDragAfterElement(galGrid, e.clientY, e.clientX);
      if (afterEl == null) {
        galGrid.appendChild(dragEl);
      } else {
        galGrid.insertBefore(dragEl, afterEl);
      }
    });
  });
}

function getDragAfterElement(container, y, x) {
  const items = [...container.querySelectorAll('.gal-item:not(.dragging)')];
  return items.reduce((closest, child) => {
    const box = child.getBoundingClientRect();
    const offsetX = x - box.left - box.width / 2;
    const offsetY = y - box.top - box.height / 2;
    const distance = Math.sqrt(offsetX * offsetX + offsetY * offsetY);
    if (distance < closest.distance) {
      return { distance: distance, element: child };
    }
    return closest;
  }, { distance: Infinity }).element;
}

function saveOrder() {
  const ids = [...galGrid.querySelectorAll('.gal-item')].map(el => el.dataset.id);
  fetch('gallery_reorder_handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'order=' + encodeURIComponent(JSON.stringify(ids)) + '&project_id=' + projectId
  });
}
</script>
</body></html>