<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") { header("Location: ../../auth/login.php"); exit(); }
require_once __DIR__ . '/../../includes/db_connect.php';

$error = '';
$success = '';
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($project_id <= 0) {
    header("Location: manage.php");
    exit();
}

$stmt = $conn->prepare("SELECT id, title, slug, brochure_path FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header("Location: manage.php");
    exit();
}

// ── Map PHP upload error codes to real, human-readable messages ──
function brochure_upload_error_message($code) {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The file is larger than this server allows (upload_max_filesize limit exceeded). Please use a smaller PDF or contact the developer to raise the server limit.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The file exceeds the maximum size allowed by the upload form.';
        case UPLOAD_ERR_PARTIAL:
            return 'The file was only partially uploaded. Please try again — this often happens on a slow or interrupted connection.';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was received by the server. Please choose a PDF file and try again.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Server configuration error: missing temporary upload folder. Please contact the developer.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Server configuration error: failed to write file to disk. Please contact the developer.';
        case UPLOAD_ERR_EXTENSION:
            return 'The upload was stopped by a server extension. Please contact the developer.';
        default:
            return 'An unknown upload error occurred (code ' . $code . ').';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['brochure']) || $_FILES['brochure']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please choose a PDF file to upload.';
    } elseif ($_FILES['brochure']['error'] !== UPLOAD_ERR_OK) {
        $error = brochure_upload_error_message($_FILES['brochure']['error']);
    } else {
        $file = $_FILES['brochure'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $maxSize = 25 * 1024 * 1024; // 25 MB application-level cap

        if ($ext !== 'pdf') {
            $error = 'Only PDF files are allowed. You uploaded a .' . htmlspecialchars($ext) . ' file.';
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if ($mime !== 'application/pdf') {
                $error = 'The uploaded file is not a valid PDF (detected type: ' . htmlspecialchars($mime) . ').';
            }
        }

        if ($error === '' && $file['size'] > $maxSize) {
            $error = 'File is too large (' . round($file['size'] / 1048576, 2) . ' MB). Maximum allowed is 25 MB.';
        }

        if ($error === '') {
            $brochureDir = __DIR__ . '/../../assets/brochures/';

            // Ensure the folder exists
            if (!is_dir($brochureDir)) {
                if (!mkdir($brochureDir, 0755, true)) {
                    $error = 'Could not create the brochures folder on the server. Please check folder permissions.';
                }
            }

            // Ensure the folder is writable
            if ($error === '' && !is_writable($brochureDir)) {
                $error = 'The brochures folder exists but is not writable by the server. Please set its permissions to 755 (or 777 if your host requires it) via File Manager.';
            }

            if ($error === '') {
                $uniqueName = $project['slug'] . '-' . bin2hex(random_bytes(8)) . '.pdf';
                $destPath = $brochureDir . $uniqueName;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    // Delete old brochure file if replacing
                    if (!empty($project['brochure_path'])) {
                        $oldPath = $brochureDir . basename($project['brochure_path']);
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }

                    $stmtU = $conn->prepare("UPDATE projects SET brochure_path = ? WHERE id = ?");
                    $stmtU->bind_param("si", $uniqueName, $project_id);
                    $stmtU->execute();

                    header("Location: manage.php?brochure_msg=success");
                    exit();
                } else {
                    $error = 'move_uploaded_file() failed. The server could not save the file — this usually means a folder permission issue.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload Brochure</title>
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
.alert-danger{background:#3a1414;border:1px solid #8b2626;color:#ff9999;}
</style>
</head><body>
<h4 class="mb-3"><i class="bi bi-file-earmark-pdf-fill text-danger"></i> <?= !empty($project['brochure_path']) ? 'Replace' : 'Upload' ?> Brochure</h4>
<p class="text-muted">Project: <strong class="text-white"><?= htmlspecialchars($project['title']) ?></strong></p>

<?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (!empty($project['brochure_path'])): ?>
<div class="card p-3 mb-3">
  <label class="mb-2">Current Brochure</label>
  <a href="../../assets/brochures/<?= htmlspecialchars($project['brochure_path']) ?>" target="_blank" class="btn btn-outline-light btn-sm" style="width:fit-content;">
    <i class="bi bi-file-earmark-pdf"></i> View Current Brochure
  </a>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="card p-4">
  <label>Select PDF File (max 25 MB)</label>
  <input type="file" name="brochure" class="form-control mb-3" accept="application/pdf,.pdf" required>
  <div>
    <button type="submit" class="btn btn-red px-4"><i class="bi bi-upload"></i> Save Brochure</button>
    <a href="manage.php" class="btn btn-outline-light px-4">Cancel</a>
  </div>
</form>
</body></html>