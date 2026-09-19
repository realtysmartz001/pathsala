<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/db_connect.php';

$message      = "";
$skip_duplicate = 0;

// ✅ Role definitions — shared everywhere a role dropdown is needed
$jobRoles = [
    'tele_sales'       => 'Tele Sales',
    'sales_consultant' => 'Sales Consultant',
    'team_leader'      => 'Team Leader',
    'others'           => 'Others',
];

// ── Helper: normalize a CSV job_role cell to a canonical key ──
// Accepts "Tele Sales", "tele sales", "tele_sales", "TELE_SALES" etc.
function normalizeJobRole($raw, $jobRoles)
{
    $key = strtolower(trim($raw));
    $key = str_replace([' ', '-'], '_', $key);
    return array_key_exists($key, $jobRoles) ? $key : null;
}

// ── Single Question Add ──
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['single_submit'])) {
    $job_role       = trim($_POST['job_role']);
    $set_name       = trim($_POST['set_name']);
    $set_id         = trim($_POST['set_id']);
    $question_text  = trim($_POST['question_text']);
    $option_a       = trim($_POST['option_a']);
    $option_b       = trim($_POST['option_b']);
    $option_c       = trim($_POST['option_c']);
    $option_d       = trim($_POST['option_d']);
    $correct_option = trim($_POST['correct_option']);
    $explanation    = trim($_POST['explanation'] ?? '');
    $explanation    = ($explanation === '') ? null : $explanation;

    if (!array_key_exists($job_role, $jobRoles)) {
        $message = "error|Please select a valid Job Role.";
    } else {
        $stmt = $conn->prepare("INSERT INTO questions 
            (job_role, set_name, set_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssssssss",
            $job_role, $set_name, $set_id, $question_text,
            $option_a, $option_b, $option_c, $option_d, $correct_option, $explanation);

        if ($stmt->execute()) {
            $message = "success|Question added successfully! (Role: " . htmlspecialchars($jobRoles[$job_role]) . " | Set: " . htmlspecialchars($set_id) . ")";
        } else {
            $message = "error|Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// ── Bulk Upload via CSV ──
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bulk_submit'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);

        if (strtolower($ext) !== 'csv') {
            $message = "error|Only CSV files are allowed!";
        } else {
            $handle        = fopen($_FILES['csv_file']['tmp_name'], "r");
            $row           = 0;
            $success_count = 0;
            $error_count   = 0;
            $error_details = [];

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($row == 0)                   { $row++; continue; }
                if (empty(array_filter($data)))  { $row++; continue; }

                // ✅ 9 required columns: job_role, set_name, set_id, question,
                // option_a, option_b, option_c, option_d, correct_option
                // ✅ 10th column (explanation) is OPTIONAL — old 9-column CSVs still work
                if (count($data) < 9) {
                    $error_count++;
                    $error_details[] = "Row $row: Less than 9 columns found";
                    $row++; continue;
                }

                $job_role_raw   = trim($data[0]);
                $set_name       = trim($data[1]);
                $set_id         = trim($data[2]);
                $question_text  = trim($data[3]);
                $option_a       = trim($data[4]);
                $option_b       = trim($data[5]);
                $option_c       = trim($data[6]);
                $option_d       = trim($data[7]);
                $correct_option = strtoupper(trim($data[8]));
                $explanation    = isset($data[9]) ? trim($data[9]) : '';
                $explanation    = ($explanation === '') ? null : $explanation;

                $job_role = normalizeJobRole($job_role_raw, $jobRoles);
                if ($job_role === null) {
                    $error_count++;
                    $error_details[] = "Row $row: Invalid job_role '$job_role_raw' (must be Tele Sales, Sales Consultant, or Team Leader)";
                    $row++; continue;
                }

                if (!in_array($correct_option, ['A','B','C','D'])) {
                    $error_count++;
                    $error_details[] = "Row $row: Invalid correct_option '$correct_option'";
                    $row++; continue;
                }

                // Duplicate check
                $dupCheck = $conn->prepare(
                    "SELECT id FROM questions WHERE set_id = ? AND question_text = ?"
                );
                $dupCheck->bind_param("ss", $set_id, $question_text);
                $dupCheck->execute();
                $dupCheck->store_result();

                if ($dupCheck->num_rows > 0) {
                    $skip_duplicate++;
                    $dupCheck->close();
                    $row++; continue;
                }
                $dupCheck->close();

                $stmt = $conn->prepare("INSERT INTO questions 
                    (job_role, set_name, set_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssssssssss",
                    $job_role, $set_name, $set_id, $question_text,
                    $option_a, $option_b, $option_c, $option_d, $correct_option, $explanation);

                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                    $error_details[] = "Row $row DB Error: " . $stmt->error;
                }
                $stmt->close();
                $row++;
            }
            fclose($handle);

            $parts = [];
            $parts[] = "$success_count questions uploaded successfully!";
            if ($skip_duplicate > 0)
                $parts[] = "$skip_duplicate duplicates skipped.";
            if ($error_count > 0)
                $parts[] = "$error_count rows had errors.";

            $message = "bulk|" . implode("||", $parts);
            if (!empty($error_details)) {
                $message .= "||" . implode("~~", $error_details);
            }
        }
    } else {
        $message = "error|Please select a valid CSV file!";
    }
}

// Parse message
$msgType    = '';
$msgText    = '';
$bulkErrors = [];

if ($message) {
    $parts   = explode("|", $message, 3);
    $msgType = $parts[0];

    if ($msgType === 'bulk') {
        $lines      = explode("||", $parts[1] ?? '');
        $msgText    = implode(' ', $lines);
        $errStr     = $parts[2] ?? '';
        $bulkErrors = $errStr ? explode("~~", $errStr) : [];
    } else {
        $msgText = $parts[1] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Questions</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    /* ══════════════════════════════════════
       BASE — white theme
    ══════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: #f5f6fa;
      color: #333;
      font-family: 'Segoe UI', sans-serif;
      padding: 24px 20px;
    }

    /* ── Page Header ── */
    .page-header {
      display: flex; align-items: center;
      justify-content: space-between;
      flex-wrap: wrap; gap: 10px;
      margin-bottom: 24px;
    }
    .page-header-left {
      display: flex; align-items: center; gap: 10px;
    }
    .page-header h2 {
      font-size: 20px; font-weight: 700;
      color: #1a1a1a; margin: 0;
    }
    .title-icon { font-size: 22px; color: #e53935; }

    .back-btn {
      display: inline-flex; align-items: center; gap: 6px;
      background: #f0f0f0; color: #555;
      border: 1px solid #e0e0e0;
      padding: 7px 14px; border-radius: 8px;
      font-size: 12px; font-weight: 600;
      text-decoration: none; transition: all 0.15s;
    }
    .back-btn:hover { background: #e0e0e0; color: #333; }

    /* ── Alert Messages ── */
    .msg-box {
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 13px;
      margin-bottom: 20px;
      border-left: 4px solid;
      display: flex; align-items: flex-start;
      gap: 10px;
    }
    .msg-success {
      background: #e8f5e9; color: #2e7d32;
      border-color: #43a047;
    }
    .msg-error {
      background: #ffebee; color: #c62828;
      border-color: #e53935;
    }
    .msg-bulk {
      background: #e3f2fd; color: #1565c0;
      border-color: #1976d2;
    }
    .err-detail {
      background: #fff8e1; color: #e65100;
      border: 1px solid #ffe0b2;
      border-radius: 6px;
      padding: 8px 12px;
      font-size: 11px; margin-top: 6px;
      font-family: 'Courier New', monospace;
    }

    /* ── Section Cards ── */
    .section-card {
      background: #fff;
      border-radius: 14px;
      border: 1px solid #e8e8e8;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      margin-bottom: 20px;
      overflow: hidden;
    }
    .section-header {
      padding: 14px 20px;
      border-bottom: 1px solid #f3f3f3;
      display: flex; align-items: center; gap: 8px;
    }
    .section-header i  { font-size: 16px; color: #e53935; }
    .section-header h3 {
      font-size: 14px; font-weight: 700;
      color: #1a1a1a; margin: 0;
    }
    .section-body { padding: 20px 24px; }

    /* ── Form Fields ── */
    .form-label {
      color: #666;
      font-size: 11px;
      font-weight: 700;
      margin-bottom: 5px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .form-control, .form-select {
      background: #fafafa !important;
      border: 1px solid #e0e0e0 !important;
      color: #333 !important;
      border-radius: 8px !important;
      font-size: 13px;
      padding: 9px 12px !important;
    }
    .form-control:focus, .form-select:focus {
      border-color: #e53935 !important;
      box-shadow: 0 0 0 3px rgba(229,57,53,0.1) !important;
      background: #fff !important;
    }
    textarea.form-control { resize: vertical; min-height: 90px; }

    /* Option Grid */
    .option-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    /* Option Label Badge */
    .opt-label {
      display: inline-flex; align-items: center; justify-content: center;
      width: 24px; height: 24px;
      background: #e53935; color: #fff;
      border-radius: 50%; font-size: 11px;
      font-weight: 700; flex-shrink: 0;
    }
    .opt-label.b { background: #1565c0; }
    .opt-label.c { background: #2e7d32; }
    .opt-label.d { background: #e65100; }

    .opt-row {
      display: flex; align-items: center; gap: 8px;
    }
    .opt-row .form-control { flex: 1; }

    /* ── Submit Button ── */
    .btn-submit {
      display: flex; align-items: center;
      justify-content: center; gap: 8px;
      width: 100%; padding: 11px;
      background: #1a1a2e; color: #fff;
      border: none; border-radius: 9px;
      font-size: 14px; font-weight: 600;
      cursor: pointer; transition: all 0.15s;
      margin-top: 4px;
    }
    .btn-submit:hover { background: #0d0d1a; }
    .btn-submit.green { background: #2e7d32; }
    .btn-submit.green:hover { background: #1b5e20; }

    /* ── Divider ── */
    .section-divider {
      border: none; border-top: 2px dashed #e8e8e8;
      margin: 0;
    }

    /* ── CSV Note ── */
    .csv-note {
      background: #fff8e1;
      border: 1px solid #ffe0b2;
      border-left: 4px solid #ffb300;
      border-radius: 10px;
      padding: 14px 16px;
      font-size: 12px;
      color: #555;
      margin-bottom: 18px;
      line-height: 1.8;
    }
    .csv-note strong { color: #333; }
    .csv-note code {
      background: #fff3e0;
      padding: 1px 6px;
      border-radius: 4px;
      font-size: 11px;
      color: #e65100;
    }

    /* ── File Input ── */
    .file-drop {
      background: #fafafa;
      border: 2px dashed #e0e0e0;
      border-radius: 10px;
      padding: 20px;
      text-align: center;
      transition: border-color 0.15s;
      margin-bottom: 14px;
    }
    .file-drop:hover { border-color: #e53935; }
    .file-drop i { font-size: 28px; color: #bbb; display: block; margin-bottom: 8px; }
    .file-drop span { font-size: 12px; color: #999; display: block; margin-bottom: 10px; }
    .file-drop input[type="file"] { font-size: 12px; }

    /* ── Download Link ── */
    .download-link {
      display: inline-flex; align-items: center; gap: 6px;
      background: #e8f5e9; color: #2e7d32;
      border: 1px solid #c8e6c9;
      padding: 7px 14px; border-radius: 8px;
      font-size: 12px; font-weight: 600;
      text-decoration: none; transition: all 0.15s;
    }
    .download-link:hover { background: #2e7d32; color: #fff; }
  </style>
</head>
<body>

<!-- ══════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════ -->
<div class="page-header">
  <div class="page-header-left">
    <i class="bi bi-plus-circle-fill title-icon"></i>
    <h2>Add Questions</h2>
  </div>
  <a href="manage_questions.php" class="back-btn">
    <i class="bi bi-arrow-left"></i> Back to Manage Questions
  </a>
</div>

<!-- ══════════════════════════════════════
     FLASH MESSAGES
══════════════════════════════════════ -->
<?php if ($msgType === 'success'): ?>
  <div class="msg-box msg-success">
    <i class="bi bi-check-circle-fill" style="font-size:18px; flex-shrink:0;"></i>
    <div><?= htmlspecialchars($msgText) ?></div>
  </div>
<?php elseif ($msgType === 'error'): ?>
  <div class="msg-box msg-error">
    <i class="bi bi-x-circle-fill" style="font-size:18px; flex-shrink:0;"></i>
    <div><?= htmlspecialchars($msgText) ?></div>
  </div>
<?php elseif ($msgType === 'bulk'): ?>
  <div class="msg-box msg-bulk">
    <i class="bi bi-cloud-upload-fill" style="font-size:18px; flex-shrink:0;"></i>
    <div>
      <?= htmlspecialchars($msgText) ?>
      <?php foreach ($bulkErrors as $err): ?>
        <?php if (trim($err)): ?>
          <div class="err-detail">⚠️ <?= htmlspecialchars($err) ?></div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- ══════════════════════════════════════
     SINGLE QUESTION FORM
══════════════════════════════════════ -->
<div class="section-card">
  <div class="section-header">
    <i class="bi bi-pencil-square"></i>
    <h3>Single Question</h3>
  </div>
  <div class="section-body">
    <form method="post">

      <div class="mb-3">
        <label class="form-label">Job Role</label>
        <select name="job_role" class="form-select" required>
          <option value="">-- Select Job Role --</option>
          <?php foreach ($jobRoles as $key => $label): ?>
            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Set Name</label>
          <input type="text" name="set_name" class="form-control"
                 placeholder="e.g. RS Set 1" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Set ID</label>
          <input type="text" name="set_id" class="form-control"
                 placeholder="e.g. ts001" required>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Question</label>
        <textarea name="question_text" class="form-control"
                  placeholder="Type your question here..." required></textarea>
      </div>

      <div class="option-grid mb-3">
        <div>
          <label class="form-label">Option A</label>
          <div class="opt-row">
            <span class="opt-label">A</span>
            <input type="text" name="option_a" class="form-control"
                   placeholder="Option A" required>
          </div>
        </div>
        <div>
          <label class="form-label">Option B</label>
          <div class="opt-row">
            <span class="opt-label b">B</span>
            <input type="text" name="option_b" class="form-control"
                   placeholder="Option B" required>
          </div>
        </div>
        <div>
          <label class="form-label">Option C</label>
          <div class="opt-row">
            <span class="opt-label c">C</span>
            <input type="text" name="option_c" class="form-control"
                   placeholder="Option C" required>
          </div>
        </div>
        <div>
          <label class="form-label">Option D</label>
          <div class="opt-row">
            <span class="opt-label d">D</span>
            <input type="text" name="option_d" class="form-control"
                   placeholder="Option D" required>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Correct Option (A / B / C / D)</label>
        <select name="correct_option" class="form-select" required>
          <option value="">-- Select Correct Option --</option>
          <option value="A">A</option>
          <option value="B">B</option>
          <option value="C">C</option>
          <option value="D">D</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Explanation <span style="text-transform:none;font-weight:400;color:#bbb;">(optional)</span></label>
        <textarea name="explanation" class="form-control"
                  placeholder="Explain why the correct answer is correct (shown to users on their result page)..."></textarea>
      </div>

      <button type="submit" name="single_submit" class="btn-submit">
        <i class="bi bi-plus-circle-fill"></i> Add Question
      </button>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════
     BULK UPLOAD FORM
══════════════════════════════════════ -->
<div class="section-card">
  <div class="section-header">
    <i class="bi bi-cloud-upload-fill"></i>
    <h3>Bulk Upload via CSV</h3>
  </div>
  <div class="section-body">

    <!-- CSV Note -->
    <div class="csv-note">
      <strong>📌 CSV Column Order:</strong><br>
      <code>job_role, set_name, set_id, question, option_a, option_b, option_c, option_d, correct_option, explanation</code>
      <br><br>
      ⚠️ <strong>Row 1</strong> = Header row (skipped automatically)<br>
⚠️ <strong>job_role</strong> must be one of: <code>tele_sales</code>, <code>sales_consultant</code>, <code>team_leader</code>, <code>others</code>      (spaces/hyphens and different casing like "Tele Sales" are also accepted)<br>
      ⚠️ <strong>set_id</strong> must be like: <code>ts001</code>, <code>sc001</code>, <code>tl001</code><br>
      ⚠️ <strong>correct_option</strong> must be: <code>A</code>, <code>B</code>, <code>C</code> or <code>D</code> only<br>
      ⚠️ <strong>explanation</strong> (10th column) is <strong>optional</strong> — you can leave it blank, or omit the column entirely (old 9-column CSVs still work)<br>
      ⚠️ Duplicate questions (same set_id + question) are <strong>automatically skipped</strong>
    </div>

    <form method="post" enctype="multipart/form-data">

      <!-- File Drop Area -->
      <div class="file-drop">
        <i class="bi bi-file-earmark-spreadsheet"></i>
        <span>Select your <strong>.csv</strong> file to upload</span>
        <input type="file" name="csv_file" accept=".csv" required>
      </div>

      <button type="submit" name="bulk_submit" class="btn-submit green">
        <i class="bi bi-upload"></i> Upload CSV
      </button>
    </form>

    <div class="text-center mt-3">
      <a href="../sample_questions.csv" download class="download-link">
        <i class="bi bi-download"></i> Download Sample CSV Format
      </a>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>