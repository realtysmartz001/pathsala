<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}
include '../includes/db_connect.php';

$msgType = "";
$msgText = "";

// ✅ Role definitions — add a new role here later and it will automatically
// appear in this panel and everywhere else that loops over $jobRoles.
$jobRoles = [
    'tele_sales'       => 'Tele Sales',
    'sales_consultant' => 'Sales Consultant',
    'team_leader'      => 'Team Leader',
    'others'           => 'Others',
];

// Update active set mode — now scoped to a specific role, supports both
// 'manual' (one fixed set for everyone) and 'auto' (round-robin across
// every set that exists for that role in the questions table)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['role_key'])) {
    $role_key = trim($_POST['role_key']);
    $mode = ($_POST['mode'] ?? 'manual') === 'auto' ? 'auto' : 'manual';
    $new_set = trim($_POST['set_code'] ?? '');

    if (!array_key_exists($role_key, $jobRoles)) {
        $msgType = "error";
        $msgText = "Invalid role selected.";
    } elseif ($mode === 'manual' && $new_set === '') {
        $msgType = "error";
        $msgText = "Please enter a set code for Manual mode.";
    } else {
        if ($mode === 'auto') {
            $stmt = $conn->prepare("UPDATE active_set SET mode = 'auto', updated_at = NOW() WHERE role_key = ?");
            $stmt->bind_param("s", $role_key);
        } else {
            $stmt = $conn->prepare("UPDATE active_set SET mode = 'manual', set_code = ?, updated_at = NOW() WHERE role_key = ?");
            $stmt->bind_param("ss", $new_set, $role_key);
        }

        if ($stmt->execute()) {
            $msgType = "success";
            if ($mode === 'auto') {
                $msgText = "<strong>" . htmlspecialchars($jobRoles[$role_key]) . "</strong> is now set to <strong>Auto Round-Robin</strong> — new users of this role will automatically cycle through every set available for this role.";
            } else {
                $msgText = "Active set for <strong>" . htmlspecialchars($jobRoles[$role_key]) . "</strong> changed to: <strong>" . htmlspecialchars($new_set) . "</strong> (Manual mode).";
            }
        } else {
            $msgType = "error";
            $msgText = "Error: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}

// Fetch current active set + mode for every role
$activeSets = [];
$res = $conn->query("SELECT role_key, mode, set_code, updated_at FROM active_set");
while ($row = $res->fetch_assoc()) {
    $activeSets[$row['role_key']] = $row;
}

// For roles in Auto mode, also fetch how many sets exist for that role,
// so the admin can see what it's cycling through
$roleSetCounts = [];
foreach ($jobRoles as $roleKey => $roleLabel) {
    $countStmt = $conn->prepare("SELECT COUNT(DISTINCT set_id) AS total FROM questions WHERE job_role = ?");
    $countStmt->bind_param("s", $roleKey);
    $countStmt->execute();
    $countRow = $countStmt->get_result()->fetch_assoc();
    $countStmt->close();
    $roleSetCounts[$roleKey] = (int) ($countRow['total'] ?? 0);
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Change Active Set</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    /* ══════════════════════════════════════
       BASE — white theme
    ══════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: #f5f6fa;
      color: #333;
      font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
      padding: 24px 20px 60px;
      min-height: 100vh;
    }

    /* ══════════════════════════════════════
       PREMIUM PAGE HEADER
    ══════════════════════════════════════ */
    .premium-header {
      position: relative;
      max-width: 1100px;
      margin: 0 auto 28px;
      background: linear-gradient(135deg, #ffffff 0%, #fff5f5 100%);
      border: 1px solid #f0d9d9;
      border-radius: 18px;
      padding: 26px 28px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.05);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 14px;
    }
    .premium-header::before {
      content: "";
      position: absolute;
      top: -60px; right: -60px;
      width: 200px; height: 200px;
      background: radial-gradient(circle, rgba(229,57,53,0.09) 0%, rgba(229,57,53,0) 70%);
      border-radius: 50%;
      pointer-events: none;
    }
    .page-header-left {
      display: flex; align-items: center; gap: 14px;
      position: relative; z-index: 1;
    }
    .title-icon-wrap {
      width: 48px; height: 48px;
      background: linear-gradient(135deg, #e53935, #c62828);
      border-radius: 13px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 6px 16px rgba(229,57,53,0.30);
      flex-shrink: 0;
    }
    .title-icon { font-size: 22px; color: #fff; }
    .page-header h2 {
      font-size: 21px; font-weight: 800;
      color: #1a1a1a; margin: 0;
      letter-spacing: -0.2px;
    }
    .page-header p.subtitle {
      font-size: 12.5px; color: #999;
      margin-top: 2px; font-weight: 500;
    }

    .back-btn {
      display: inline-flex; align-items: center; gap: 6px;
      background: #fff; color: #555;
      border: 1px solid #e0e0e0;
      padding: 9px 16px; border-radius: 9px;
      font-size: 12.5px; font-weight: 700;
      text-decoration: none; transition: all 0.2s ease;
      position: relative; z-index: 1;
    }
    .back-btn:hover {
      background: #1a1a1a; color: #fff; border-color: #1a1a1a;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* ── Center Wrapper ── */
    .center-wrap {
      max-width: 1100px;
      margin: 0 auto;
    }

    /* ── Alert Messages ── */
    .msg-box {
      border-radius: 12px;
      padding: 14px 18px;
      font-size: 13.5px;
      margin-bottom: 22px;
      border-left: 4px solid;
      display: flex; align-items: center; gap: 12px;
      animation: slideDown 0.35s ease;
      box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-8px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .msg-success {
      background: #e8f5e9; color: #2e7d32; border-color: #43a047;
    }
    .msg-error {
      background: #ffebee; color: #c62828; border-color: #e53935;
    }

    /* ── Role Section Wrapper ── */
    .role-section {
      margin-bottom: 26px;
    }

    .role-section-title {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 12px;
      padding-left: 2px;
    }

    .role-section-title .role-dot {
      width: 9px; height: 9px;
      border-radius: 50%;
      background: #e53935;
      flex-shrink: 0;
    }

    .role-section-title h4 {
      font-size: 14.5px;
      font-weight: 800;
      color: #1a1a1a;
      letter-spacing: -0.1px;
    }

    /* ── Layout Grid (per role) ── */
    .set-layout {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
    }
    @media (min-width: 860px) {
      .set-layout {
        grid-template-columns: 1.05fr 1fr;
        align-items: start;
      }
    }

    /* ══════════════════════════════════════
       CURRENT ACTIVE SET CARD
    ══════════════════════════════════════ */
    .active-set-card {
      position: relative;
      background: linear-gradient(160deg, #ffffff 0%, #fbfffc 100%);
      border-radius: 18px;
      border: 1px solid #e8e8e8;
      box-shadow: 0 4px 18px rgba(0,0,0,0.06);
      padding: 28px 26px;
      display: flex;
      align-items: flex-start;
      gap: 18px;
      overflow: hidden;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .active-set-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 28px rgba(0,0,0,0.09);
    }
    .active-set-card::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; height: 4px;
      background: linear-gradient(90deg, #43a047, #66bb6a, #43a047);
    }
    .active-set-icon {
      width: 58px; height: 58px;
      background: linear-gradient(135deg, #e8f5e9, #d7ecd8);
      border-radius: 14px;
      display: flex; align-items: center;
      justify-content: center;
      font-size: 24px; color: #2e7d32;
      flex-shrink: 0;
    }
    .active-set-body { flex: 1; min-width: 0; }
    .active-set-top-row {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 6px; flex-wrap: wrap;
    }
    .active-set-label {
      font-size: 11px; color: #999;
      font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.6px;
    }

    /* animated pulsing Active badge */
    .live-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: #e8f5e9; color: #2e7d32;
      font-size: 10.5px; font-weight: 800;
      text-transform: uppercase; letter-spacing: 0.5px;
      padding: 3px 10px 3px 8px;
      border-radius: 20px;
      border: 1px solid #c8e6c9;
    }
    .live-dot {
      width: 7px; height: 7px;
      background: #43a047;
      border-radius: 50%;
      position: relative;
      flex-shrink: 0;
    }
    .live-dot::after {
      content: "";
      position: absolute;
      top: 50%; left: 50%;
      width: 7px; height: 7px;
      background: #43a047;
      border-radius: 50%;
      transform: translate(-50%, -50%);
      animation: pulseRing 1.6s ease-out infinite;
    }
    @keyframes pulseRing {
      0%   { transform: translate(-50%, -50%) scale(1);   opacity: 0.7; }
      70%  { transform: translate(-50%, -50%) scale(3.2); opacity: 0; }
      100% { transform: translate(-50%, -50%) scale(3.2); opacity: 0; }
    }

    .active-set-code {
      font-size: 26px; font-weight: 800;
      color: #1a1a1a; font-family: 'Courier New', monospace;
      letter-spacing: 1px; line-height: 1.2;
      word-break: break-all;
    }
    .active-set-time {
      font-size: 11.5px; color: #aaa;
      margin-top: 10px;
      display: flex; align-items: center; gap: 5px;
    }

    /* ══════════════════════════════════════
       FORM CARD
    ══════════════════════════════════════ */
    .form-card {
      background: #fff;
      border-radius: 18px;
      border: 1px solid #e8e8e8;
      box-shadow: 0 4px 18px rgba(0,0,0,0.06);
      overflow: hidden;
      transition: box-shadow 0.25s ease;
    }
    .form-card:hover { box-shadow: 0 10px 28px rgba(0,0,0,0.08); }

    .form-card-header {
      padding: 16px 22px;
      border-bottom: 1px solid #f3f3f3;
      display: flex; align-items: center; gap: 10px;
      background: #fcfcfc;
    }
    .form-card-header-icon {
      width: 30px; height: 30px;
      background: #ffebee;
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .form-card-header-icon i { font-size: 14px; color: #e53935; }
    .form-card-header h3 {
      font-size: 14.5px; font-weight: 800;
      color: #1a1a1a; margin: 0;
    }
    .form-card-body { padding: 24px 22px; }

    /* ── Form Fields ── */
    .form-label-custom {
      color: #666; font-size: 11px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.6px;
      margin-bottom: 8px; display: block;
    }

    .input-group-custom {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-group-custom i.field-prefix-icon {
      position: absolute;
      left: 14px;
      font-size: 15px;
      color: #bbb;
      pointer-events: none;
    }
    .form-control-custom {
      width: 100%;
      background: #fafafa;
      border: 1px solid #e0e0e0;
      color: #333;
      border-radius: 10px;
      font-size: 14.5px;
      padding: 12px 14px 12px 40px;
      outline: none;
      transition: all 0.2s ease;
      font-family: 'Courier New', monospace;
      letter-spacing: 0.5px;
    }
    .form-control-custom:focus {
      border-color: #e53935;
      box-shadow: 0 0 0 4px rgba(229,57,53,0.10);
      background: #fff;
    }
    .form-control-custom::placeholder { color: #bbb; font-family: 'Plus Jakarta Sans', sans-serif; }

    /* ── Hint Text ── */
    .field-hint {
      font-size: 11.5px; color: #b0b0b0;
      margin-top: 7px;
      display: flex; align-items: center; gap: 5px;
    }

    /* ── Submit Button ── */
    .btn-update {
      display: flex; align-items: center;
      justify-content: center; gap: 8px;
      width: 100%; padding: 13px;
      background: linear-gradient(135deg, #e53935, #c62828);
      color: #fff;
      border: none; border-radius: 10px;
      font-size: 14px; font-weight: 700;
      cursor: pointer; transition: all 0.2s ease;
      margin-top: 20px;
      letter-spacing: 0.3px;
      box-shadow: 0 4px 14px rgba(229,57,53,0.25);
    }
    .btn-update:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(229,57,53,0.35);
    }
    .btn-update:active { transform: translateY(0); }
    .btn-update i { transition: transform 0.4s ease; }
    .btn-update:hover i { transform: rotate(180deg); }

    /* ── Info Note ── */
    .info-note {
      background: #fff8e1;
      border: 1px solid #ffe0b2;
      border-left: 4px solid #ffb300;
      border-radius: 10px;
      padding: 12px 14px;
      font-size: 12px; color: #777;
      margin-top: 18px;
      display: flex; align-items: flex-start; gap: 8px;
      line-height: 1.6;
    }
    .info-note i { color: #ffb300; font-size: 14px; flex-shrink: 0; margin-top: 1px; }
    .info-note code {
      background: #fff3e0; padding: 1px 6px;
      border-radius: 4px; font-size: 11px; color: #e65100;
    }

    /* ── Divider between role sections ── */
    .role-divider {
      border: none;
      border-top: 2px dashed #eee;
      margin: 30px 0;
    }

    @media (max-width: 480px) {
      .active-set-code { font-size: 21px; }
      .premium-header { padding: 20px; }
    }
  </style>
</head>
<body>

<!-- ══════════════════════════════════════
     PREMIUM PAGE HEADER
══════════════════════════════════════ -->
<div class="premium-header">
  <div class="page-header-left">
    <div class="title-icon-wrap">
      <i class="bi bi-arrow-repeat title-icon"></i>
    </div>
    <div class="page-header">
      <h2>Change Active Set</h2>
      <p class="subtitle">Control which question set is live for each job role — independently</p>
    </div>
  </div>
  <a href="../admin/after_admin_login.php" class="back-btn">
    <i class="bi bi-arrow-left"></i> Back to Dashboard
  </a>
</div>

<!-- ── Center Wrapper ── -->
<div class="center-wrap">

  <!-- ══════════════════════════════════════
       FLASH MESSAGE
  ══════════════════════════════════════ -->
  <?php if ($msgType === 'success'): ?>
    <div class="msg-box msg-success">
      <i class="bi bi-check-circle-fill" style="font-size:18px; flex-shrink:0;"></i>
      <div><?= $msgText ?></div>
    </div>
  <?php elseif ($msgType === 'error'): ?>
    <div class="msg-box msg-error">
      <i class="bi bi-x-circle-fill" style="font-size:18px; flex-shrink:0;"></i>
      <div><?= $msgText ?></div>
    </div>
  <?php endif; ?>

  <!-- ══════════════════════════════════════
       ONE SECTION PER ROLE — loops over $jobRoles,
       so adding a new role later needs no new HTML.
  ══════════════════════════════════════ -->
  <?php $roleIndex = 0; foreach ($jobRoles as $roleKey => $roleLabel): $roleIndex++; ?>

    <?php if ($roleIndex > 1): ?><hr class="role-divider"><?php endif; ?>

    <div class="role-section">
      <div class="role-section-title">
        <span class="role-dot"></span>
        <h4><?= htmlspecialchars($roleLabel) ?></h4>
      </div>

      <div class="set-layout">

        <!-- Current Active Set for this role -->
        <div class="active-set-card">
          <div class="active-set-icon">
            <i class="bi bi-collection-fill"></i>
          </div>
          <div class="active-set-body">
            <div class="active-set-top-row">
              <div class="active-set-label">
                <?= ($activeSets[$roleKey]['mode'] ?? 'manual') === 'auto' ? 'Auto Round-Robin' : 'Current Active Set' ?>
              </div>
              <?php if (($activeSets[$roleKey]['mode'] ?? 'manual') === 'auto'): ?>
                <span class="live-badge" style="background:#e3f2fd;color:#1565c0;border-color:#bbdefb;">
                  <span class="live-dot" style="background:#1565c0;"></span> Auto
                </span>
              <?php else: ?>
                <span class="live-badge"><span class="live-dot"></span> Manual</span>
              <?php endif; ?>
            </div>

            <?php if (($activeSets[$roleKey]['mode'] ?? 'manual') === 'auto'): ?>
              <div class="active-set-code" style="font-size:18px;">
                Cycling across <?= $roleSetCounts[$roleKey] ?> set<?= $roleSetCounts[$roleKey] === 1 ? '' : 's' ?>
              </div>
              <div class="active-set-time">
                <i class="bi bi-info-circle"></i>
                New users of this role rotate automatically through every set tagged for <?= htmlspecialchars($roleLabel) ?>
              </div>
            <?php else: ?>
              <div class="active-set-code">
                <?= htmlspecialchars($activeSets[$roleKey]['set_code'] ?? '—') ?>
              </div>
            <?php endif; ?>

            <div class="active-set-time">
              <i class="bi bi-clock"></i>
              Last updated: <?= htmlspecialchars($activeSets[$roleKey]['updated_at'] ?? 'N/A') ?>
            </div>
          </div>
        </div>

        <!-- Update Form for this role -->
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-header-icon">
              <i class="bi bi-pencil-fill"></i>
            </div>
            <h3>Update — <?= htmlspecialchars($roleLabel) ?></h3>
          </div>
          <div class="form-card-body">

            <?php
            $currentMode = $activeSets[$roleKey]['mode'] ?? 'manual';
            $formId = 'form_' . $roleKey;
            ?>
            <form method="POST" id="<?= $formId ?>">
              <input type="hidden" name="role_key" value="<?= htmlspecialchars($roleKey) ?>">

              <label class="form-label-custom">Assignment Mode</label>
              <div style="display:flex; gap:10px; margin-bottom:16px;">
                <label style="flex:1; display:flex; align-items:center; gap:8px; padding:10px 12px; border:1.5px solid <?= $currentMode === 'manual' ? '#e53935' : '#e0e0e0' ?>; border-radius:10px; cursor:pointer; font-size:13px; font-weight:600; color:<?= $currentMode === 'manual' ? '#e53935' : '#666' ?>;">
                  <input type="radio" name="mode" value="manual" <?= $currentMode === 'manual' ? 'checked' : '' ?>
                    onchange="toggleSetInput('<?= $formId ?>', true)" style="accent-color:#e53935;">
                  Manual (Fixed Set)
                </label>
                <label style="flex:1; display:flex; align-items:center; gap:8px; padding:10px 12px; border:1.5px solid <?= $currentMode === 'auto' ? '#1565c0' : '#e0e0e0' ?>; border-radius:10px; cursor:pointer; font-size:13px; font-weight:600; color:<?= $currentMode === 'auto' ? '#1565c0' : '#666' ?>;">
                  <input type="radio" name="mode" value="auto" <?= $currentMode === 'auto' ? 'checked' : '' ?>
                    onchange="toggleSetInput('<?= $formId ?>', false)" style="accent-color:#1565c0;">
                  Auto (Round-Robin)
                </label>
              </div>

              <div id="<?= $formId ?>_setwrap" style="<?= $currentMode === 'auto' ? 'display:none;' : '' ?>">
                <label class="form-label-custom">New Set Code</label>
                <div class="input-group-custom">
                  <i class="bi bi-hash field-prefix-icon"></i>
                  <input
                    type="text"
                    name="set_code"
                    class="form-control-custom"
                    placeholder="e.g. ts002, sc003, tl001"
                    autocomplete="off">
                </div>
                <div class="field-hint">
                  <i class="bi bi-info-circle"></i>
                  Every new <?= htmlspecialchars($roleLabel) ?> user will get exactly this set
                </div>
              </div>

              <div id="<?= $formId ?>_autonote" style="<?= $currentMode === 'auto' ? '' : 'display:none;' ?> font-size:12px; color:#1565c0; background:#e3f2fd; border:1px solid #bbdefb; border-radius:8px; padding:10px 12px; margin-bottom:4px;">
                <i class="bi bi-info-circle"></i>
                New <?= htmlspecialchars($roleLabel) ?> users will automatically cycle through all <?= $roleSetCounts[$roleKey] ?> set<?= $roleSetCounts[$roleKey] === 1 ? '' : 's' ?> tagged for this role — no set code needed.
              </div>

              <button type="submit" class="btn-update">
                <i class="bi bi-arrow-repeat"></i>
                Save — <?= htmlspecialchars($roleLabel) ?>
              </button>
            </form>

          </div>
        </div>

      </div><!-- /set-layout -->
    </div><!-- /role-section -->

  <?php endforeach; ?>

  <!-- Note -->
  <div class="info-note">
    <i class="bi bi-lightbulb-fill"></i>
    <div>
      Changing a role's active set will immediately affect which question set is shown to
      <strong>new</strong> test-takers of that role only — the other two roles are never affected.<br>
      Example codes: <code>ts001</code>, <code>sc001</code>, <code>tl001</code>
    </div>
  </div>

</div><!-- /center-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Show/hide the "New Set Code" input depending on Manual vs Auto mode
  function toggleSetInput(formId, showSetInput) {
    var setWrap = document.getElementById(formId + '_setwrap');
    var autoNote = document.getElementById(formId + '_autonote');
    if (setWrap) setWrap.style.display = showSetInput ? '' : 'none';
    if (autoNote) autoNote.style.display = showSetInput ? 'none' : '';

    // Only require the set code field when Manual mode is actually selected
    var setInput = document.querySelector('#' + formId + ' input[name="set_code"]');
    if (setInput) setInput.required = showSetInput;
  }
</script>
</body>
</html>