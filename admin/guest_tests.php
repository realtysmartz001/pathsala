<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}
include '../includes/db_connect.php';
$jobRoles = [
    'tele_sales'       => 'Tele Sales',
    'sales_consultant' => 'Sales Consultant',
    'team_leader'      => 'Team Leader',
    'others'           => 'Others',
];
$msgType = '';
$msgText = '';
// ── CREATE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_link'])) {
    $name    = trim($_POST['candidate_name'] ?? '');
    $email   = trim($_POST['candidate_email'] ?? '');
    $phone   = trim($_POST['candidate_phone'] ?? '');
    $role    = trim($_POST['job_role'] ?? '');
    $set_id  = trim($_POST['set_id'] ?? '');
    $expiry  = trim($_POST['expiry_date'] ?? '');
    $maxAtt  = max(1, (int) ($_POST['max_attempts'] ?? 1));
    if ($name === '' || !array_key_exists($role, $jobRoles) || $set_id === '' || $expiry === '') {
        $msgType = 'error';
        $msgText = 'Please fill in all fields correctly.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msgType = 'error';
        $msgText = 'Please enter a valid email address.';
    } else {
        $token = bin2hex(random_bytes(24));
        $stmt = $conn->prepare("INSERT INTO guest_test_links
            (token, candidate_name, candidate_email, candidate_phone, job_role, set_id, expiry_date, max_attempts)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $token, $name, $email, $phone, $role, $set_id, $expiry, $maxAtt);
        if ($stmt->execute()) {
            $msgType = 'success';
            $msgText = 'Guest test link created successfully for <strong>' . htmlspecialchars($name) . '</strong>.';
        } else {
            $msgType = 'error';
            $msgText = 'Error creating link: ' . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}
// ── DISABLE / ENABLE ──
if (isset($_GET['disable'])) {
    $id = (int) $_GET['disable'];
    $conn->query("UPDATE guest_test_links SET is_disabled = 1 WHERE id = $id");
    header("Location: guest_tests.php?msg=disabled");
    exit();
}
if (isset($_GET['enable'])) {
    $id = (int) $_GET['enable'];
    $conn->query("UPDATE guest_test_links SET is_disabled = 0 WHERE id = $id");
    header("Location: guest_tests.php?msg=enabled");
    exit();
}
// ── DELETE ──
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $conn->query("DELETE FROM guest_test_links WHERE id = $id");
    header("Location: guest_tests.php?msg=deleted");
    exit();
}
// ── REGENERATE TOKEN ──
if (isset($_GET['regenerate'])) {
    $id = (int) $_GET['regenerate'];
    $newToken = bin2hex(random_bytes(24));
    $stmt = $conn->prepare("UPDATE guest_test_links SET token = ? WHERE id = ?");
    $stmt->bind_param("si", $newToken, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: guest_tests.php?msg=regenerated");
    exit();
}
// ── Flash messages from redirects ──
$flashMap = [
    'disabled'    => ['success', 'Link disabled successfully.'],
    'enabled'     => ['success', 'Link enabled successfully.'],
    'deleted'     => ['success', 'Link deleted successfully.'],
    'regenerated' => ['success', 'New secure token generated — old link is now invalid.'],
];
if (isset($_GET['msg']) && isset($flashMap[$_GET['msg']])) {
    [$msgType, $msgText] = $flashMap[$_GET['msg']];
}
// ── Fetch all links with computed status + attempts used ──
$links = [];
$res = $conn->query("
    SELECT gtl.*, u.name AS linked_user_name
    FROM guest_test_links gtl
    LEFT JOIN users u ON u.id = gtl.linked_user_id
    ORDER BY gtl.created_at DESC
");
while ($row = $res->fetch_assoc()) {
    $attemptsUsed = 0;
    if (!empty($row['linked_user_id'])) {
        $uid = (int) $row['linked_user_id'];
        $c = $conn->query("SELECT COUNT(*) AS c FROM user_attempts WHERE user_id = $uid")->fetch_assoc();
        $attemptsUsed = (int) $c['c'];
    }
    $row['attempts_used'] = $attemptsUsed;
    $links[] = $row;
}
// ── Totals for summary cards ──
$totalLinks = count($links);
$activeLinks = count(array_filter($links, function ($l) {
    return !$l['is_disabled'] && strtotime($l['expiry_date']) >= time() && $l['attempts_used'] < $l['max_attempts'];
}));
$expiredLinks = count(array_filter($links, fn($l) => strtotime($l['expiry_date']) < time()));
$disabledLinks = count(array_filter($links, fn($l) => (int) $l['is_disabled'] === 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Guest Test Links</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      background: radial-gradient(ellipse at top, #131316 0%, #0a0a0d 60%);
      color: #e5e2da;
      font-family: 'Segoe UI', sans-serif;
      padding: 24px 20px;
      min-height: 100vh;
    }
    .summary-row {
      display: flex;
      gap: 14px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .sum-card {
      flex: 1;
      min-width: 130px;
      background: linear-gradient(160deg, #1a1a1f, #141417);
      border-radius: 12px;
      border: 1px solid rgba(212, 175, 106, 0.18);
      padding: 16px 20px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, .35);
      display: flex;
      align-items: center;
      gap: 14px;
      transition: border-color .2s, transform .2s;
    }
    .sum-card:hover { border-color: rgba(212,175,106,0.4); transform: translateY(-2px); }
    .sum-icon {
      width: 44px;
      height: 44px;
      border-radius: 11px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 19px;
      flex-shrink: 0;
    }
    .sum-num {
      font-size: 24px;
      font-weight: 800;
      color: #fbf5e8;
      line-height: 1;
    }
    .sum-lbl {
      font-size: 11px;
      color: #a39d8c;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .6px;
      margin-top: 3px;
    }
    .sum-total .sum-icon { background: rgba(212,175,106,0.16); color: #e6c98a !important; }
    .sum-active .sum-icon { background: rgba(90,190,120,0.16); color: #6bd191 !important; }
    .sum-expired .sum-icon { background: rgba(212,175,106,0.16); color: #f0d29c !important; }
    .sum-disabled .sum-icon { background: rgba(220,100,90,0.16); color: #f19387 !important; }
    .sum-icon i { color: inherit !important; }
    .msg-box {
      border-radius: 12px;
      padding: 14px 18px;
      font-size: 13px;
      margin-bottom: 18px;
      border-left: 4px solid;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .msg-success {
      background: rgba(90,190,120,0.12);
      color: #6bd191;
      border-color: #4aa05a;
    }
    .msg-error {
      background: rgba(220,100,90,0.12);
      color: #f19387;
      border-color: #a5423a;
    }
    .section-card {
      background: #141417;
      border-radius: 16px;
      border: 1px solid rgba(212, 175, 106, 0.16);
      box-shadow: 0 6px 24px rgba(0, 0, 0, .4);
      margin-bottom: 20px;
      overflow: hidden;
    }
    .section-header {
      padding: 16px 20px;
      border-bottom: 1px solid rgba(212, 175, 106, 0.14);
      display: flex;
      align-items: center;
      gap: 10px;
      background: linear-gradient(90deg, rgba(212,175,106,0.05), transparent);
    }
    .section-header i {
      color: #d4af6a;
      font-size: 17px;
    }
    .section-header h3 {
      font-family: 'Playfair Display', serif;
      font-size: 16px;
      font-weight: 700;
      color: #fbf5e8;
    }
    .section-body { padding: 22px; }
    .form-label {
      color: #a39d8c;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .5px;
      margin-bottom: 6px;
      display: block;
    }
    .form-control, .form-select {
      background: #1e1e23 !important;
      border: 1px solid rgba(212,175,106,0.22) !important;
      color: #e5e2da !important;
      border-radius: 8px !important;
      font-size: 13px;
      padding: 9px 12px !important;
    }
    .form-control::placeholder { color: #6a675e; }
    .form-control:focus, .form-select:focus {
      border-color: #d4af6a !important;
      box-shadow: 0 0 0 3px rgba(212,175,106,.15) !important;
      background: #22222a !important;
    }
    .btn-create {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #d4af6a, #a3803f);
      color: #14140f;
      border: none;
      border-radius: 9px;
      padding: 11px 26px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      transition: all .15s;
      margin-top: 6px;
    }
    .btn-create:hover { box-shadow: 0 4px 14px rgba(212,175,106,0.35); transform: translateY(-1px); }
    .table-topbar {
      padding: 18px 22px;
      border-bottom: 1px solid rgba(212, 175, 106, 0.14);
      background: linear-gradient(90deg, rgba(212,175,106,0.05), transparent);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
    }
    .tbl-title {
      font-family: 'Playfair Display', serif;
      font-size: 17px;
      font-weight: 700;
      color: #fbf5e8;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .tbl-title i { color: #d4af6a; }
    .rec-count {
      background: rgba(212,175,106,0.12);
      color: #d4af6a;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 11px;
      border-radius: 20px;
      border: 1px solid rgba(212,175,106,0.25);
    }
    .dataTables_wrapper { padding: 18px 22px; background: #141417; }
    table.dataTable {
      background: #141417 !important;
      border: none !important;
    }
    table.dataTable,
    table.dataTable th,
    table.dataTable td {
      border-left: none !important;
      border-right: none !important;
      box-shadow: none !important;
    }
    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label {
      color: #c9c4b6 !important;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .dataTables_wrapper .dataTables_length select {
      background: #1e1e23 !important;
      border: 1px solid rgba(212,175,106,0.22) !important;
      color: #e5e2da !important;
      border-radius: 7px !important;
      padding: 5px 10px !important;
      font-size: 13px;
      outline: none !important;
    }
    .dataTables_wrapper .dataTables_filter input {
      background: #1e1e23 !important;
      border: 1px solid rgba(212,175,106,0.22) !important;
      color: #e5e2da !important;
      border-radius: 8px !important;
      padding: 8px 14px !important;
      font-size: 13px;
      outline: none !important;
      width: 240px;
    }
    .dataTables_wrapper .dataTables_filter input::placeholder { color: #6a675e; }
    .dataTables_wrapper .dataTables_info {
      color: #8a8578 !important;
      font-size: 12px;
      padding-top: 12px !important;
    }
    table.dataTable {
      font-size: 12.5px !important;
      border-collapse: collapse !important;
      width: 100% !important;
      border: none !important;
    }
    table.dataTable > :not(caption) > * > * {
      border-bottom-width: 0 !important;
      box-shadow: none !important;
    }
    #guestTable,
    #guestTable th,
    #guestTable td,
    #guestTable tr {
      border-left: none !important;
      border-right: none !important;
      box-shadow: none !important;
    }
    table.dataTable thead th {
      background: #1c1c21 !important;
      color: #d4af6a !important;
      border-bottom: 2px solid rgba(212,175,106,0.25) !important;
      padding: 12px 12px !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      text-transform: uppercase;
      letter-spacing: .6px;
      white-space: nowrap;
    }
    table.dataTable tbody tr {
      background-color: #16161a !important;
      border-bottom: 1px solid rgba(212,175,106,0.10) !important;
      border-left: 2px solid transparent !important;
      transition: background-color .15s, border-color .15s;
    }
    table.dataTable tbody tr:nth-child(even) { background-color: #131316 !important; }
    table.dataTable tbody tr:hover,
    table.dataTable tbody tr:hover td {
      background-color: #22222a !important;
      border-left: 2px solid #d4af6a !important;
    }
    table.dataTable tbody td {
      background-color: transparent !important;
      padding: 12px !important;
      vertical-align: middle !important;
      color: #f0ede4 !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
      background: #1e1e23 !important;
      border: 1px solid rgba(212,175,106,0.20) !important;
      color: #c9c4b6 !important;
      border-radius: 7px !important;
      margin: 0 2px !important;
      font-size: 12px !important;
      padding: 5px 11px !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: linear-gradient(135deg, #d4af6a, #a3803f) !important;
      border-color: #d4af6a !important;
      color: #14140f !important;
      font-weight: 700 !important;
    }
    .name-cell {
      font-weight: 700;
      color: #ffffff !important;
    }
    .email-cell { color: #c9c4b6; font-size: 11.5px; }
    .token-cell {
      font-family: 'Courier New', monospace;
      font-size: 11px;
      color: #6a675e;
      max-width: 110px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      display: inline-block;
    }
    .role-badge {
      display: inline-block;
      background: rgba(212,175,106,0.16);
      color: #f0d29c;
      border: 1px solid rgba(212,175,106,0.35);
      padding: 3px 9px;
      border-radius: 10px;
      font-size: 10.5px;
      font-weight: 700;
      white-space: nowrap;
    }
    .set-badge {
      display: inline-block;
      background: rgba(154,130,210,0.16);
      color: #b39ddb;
      border: 1px solid rgba(154,130,210,0.35);
      padding: 3px 9px;
      border-radius: 10px;
      font-size: 10.5px;
      font-weight: 700;
    }
    .attempts-badge { font-size: 11.5px; font-weight: 700; color: #f0ede4; white-space: nowrap; }
    .status-active {
      display: inline-flex; align-items: center; gap: 4px;
      background: rgba(90,190,120,0.16); color: #6bd191;
      border: 1px solid rgba(90,190,120,0.35);
      padding: 3px 10px; border-radius: 20px; font-size: 10.5px; font-weight: 700;
    }
    .status-expired {
      display: inline-flex; align-items: center; gap: 4px;
      background: rgba(212,175,106,0.16); color: #f0d29c;
      border: 1px solid rgba(212,175,106,0.35);
      padding: 3px 10px; border-radius: 20px; font-size: 10.5px; font-weight: 700;
    }
    .status-disabled {
      display: inline-flex; align-items: center; gap: 4px;
      background: rgba(220,100,90,0.16); color: #f19387;
      border: 1px solid rgba(220,100,90,0.35);
      padding: 3px 10px; border-radius: 20px; font-size: 10.5px; font-weight: 700;
    }
    .status-exhausted {
      display: inline-flex; align-items: center; gap: 4px;
      background: #1e1e23; color: #8a8578;
      border: 1px solid rgba(212,175,106,0.15);
      padding: 3px 10px; border-radius: 20px; font-size: 10.5px; font-weight: 700;
    }
    .tbtn {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 6px 11px;
      border-radius: 7px;
      font-size: 10.5px;
      font-weight: 700;
      border: 1px solid transparent;
      cursor: pointer;
      transition: all .15s;
      white-space: nowrap;
      text-decoration: none;
      line-height: 1.4;
    }
    .tbtn-copy { background: #2c5f8a; color: #fff; }
    .tbtn-copy:hover { background: #3576ab; box-shadow: 0 3px 10px rgba(44,95,138,0.4); }
    .tbtn-toggle { background: #a3803f; color: #fff; }
    .tbtn-toggle:hover { background: #bd934c; box-shadow: 0 3px 10px rgba(163,128,63,0.4); }
    .tbtn-regen { background: #6a4a9c; color: #fff; }
    .tbtn-regen:hover { background: #7d5bb3; box-shadow: 0 3px 10px rgba(106,74,156,0.4); }
    .tbtn-delete { background: #262629; color: #b5b1a6; border-color: rgba(212,175,106,0.15); }
    .tbtn-delete:hover { background: #a5423a; color: #fff; border-color: #a5423a; }
    .action-cell { display: flex; gap: 5px; flex-wrap: nowrap; }
    .modal-content {
      background: #17171b;
      border: 1px solid rgba(212,175,106,0.2);
      border-radius: 14px;
      box-shadow: 0 12px 40px rgba(0,0,0,.6);
    }
    .modal-header {
      border-bottom: 1px solid rgba(212,175,106,0.14);
      padding: 16px 20px;
      border-radius: 14px 14px 0 0;
    }
    .modal-header.hdr-red { background: linear-gradient(135deg,#8a352c,#b34c3f); }
    .modal-title { font-weight: 700; color: #fff; font-size: 15px; }
    .modal-body { padding: 24px 20px; background: #17171b; }
    .modal-footer {
      border-top: 1px solid rgba(212,175,106,0.12);
      padding: 14px 20px;
      background: #1c1c21;
      border-radius: 0 0 14px 14px;
    }
    .toast-copied {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: linear-gradient(135deg, #d4af6a, #a3803f);
      color: #14140f;
      padding: 10px 18px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 700;
      box-shadow: 0 6px 20px rgba(0,0,0,.4);
      display: none;
      z-index: 2000;
    }
  </style>
</head>
<body>
  <!-- ════ SUMMARY CARDS ════ -->
  <div class="summary-row">
    <div class="sum-card sum-total">
      <div class="sum-icon"><i class="bi bi-link-45deg"></i></div>
      <div><div class="sum-num"><?= $totalLinks ?></div><div class="sum-lbl">Total Links</div></div>
    </div>
    <div class="sum-card sum-active">
      <div class="sum-icon"><i class="bi bi-check-circle-fill"></i></div>
      <div><div class="sum-num"><?= $activeLinks ?></div><div class="sum-lbl">Active</div></div>
    </div>
    <div class="sum-card sum-expired">
      <div class="sum-icon"><i class="bi bi-hourglass-bottom"></i></div>
      <div><div class="sum-num"><?= $expiredLinks ?></div><div class="sum-lbl">Expired</div></div>
    </div>
    <div class="sum-card sum-disabled">
      <div class="sum-icon"><i class="bi bi-slash-circle-fill"></i></div>
      <div><div class="sum-num"><?= $disabledLinks ?></div><div class="sum-lbl">Disabled</div></div>
    </div>
  </div>
  <!-- ════ FLASH MESSAGE ════ -->
  <?php if ($msgType): ?>
    <div class="msg-box msg-<?= $msgType ?>">
      <i class="bi <?= $msgType === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>" style="font-size:16px;"></i>
      <div><?= $msgText ?></div>
    </div>
  <?php endif; ?>
  <!-- ════ CREATE FORM ════ -->
  <div class="section-card">
    <div class="section-header">
      <i class="bi bi-plus-circle-fill"></i>
      <h3>Create Guest Test Link</h3>
    </div>
    <div class="section-body">
      <form method="POST">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Candidate Name</label>
            <input type="text" name="candidate_name" class="form-control" placeholder="e.g. Rahul Sharma" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Email <span style="color:#6a675e; font-weight:400; text-transform:none;">(optional)</span></label>
            <input type="email" name="candidate_email" class="form-control" placeholder="candidate@example.com">
          </div>
          <div class="col-md-4">
            <label class="form-label">Phone <span style="color:#6a675e; font-weight:400; text-transform:none;">(optional)</span></label>
            <input type="text" name="candidate_phone" class="form-control" placeholder="10-digit number">
          </div>
          <div class="col-md-4">
            <label class="form-label">Job Role</label>
            <select name="job_role" id="createJobRole" class="form-select" required>
              <option value="">-- Select Job Role --</option>
              <?php foreach ($jobRoles as $key => $label): ?>
                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Assigned Test Set</label>
            <select name="set_id" id="createSetId" class="form-select" required>
              <option value="">-- Select Job Role First --</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Expiry Date</label>
            <input type="datetime-local" name="expiry_date" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Maximum Attempts</label>
            <input type="number" name="max_attempts" class="form-control" value="1" min="1" required>
          </div>
        </div>
        <button type="submit" name="create_link" class="btn-create">
          <i class="bi bi-link-45deg"></i> Generate Secure Link
        </button>
      </form>
    </div>
  </div>
  <!-- ════ LINKS TABLE ════ -->
  <div class="section-card">
    <div class="table-topbar">
      <div class="tbl-title">
        <i class="bi bi-list-check"></i>
        Guest Test Links
        <span class="rec-count"><?= $totalLinks ?> links</span>
      </div>
    </div>
    <div class="table-responsive">
      <table id="guestTable" class="table mb-0">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>Candidate</th>
            <th>Job Role</th>
            <th>Set</th>
            <th class="text-center">Attempts</th>
            <th>Expiry</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($links as $l):
            $isExpired   = strtotime($l['expiry_date']) < time();
            $isDisabled  = (int) $l['is_disabled'] === 1;
            $isExhausted = $l['attempts_used'] >= (int) $l['max_attempts'];
            if ($isDisabled) {
                $statusHtml = "<span class='status-disabled'><i class='bi bi-slash-circle-fill'></i> Disabled</span>";
            } elseif ($isExpired) {
                $statusHtml = "<span class='status-expired'><i class='bi bi-hourglass-bottom'></i> Expired</span>";
            } elseif ($isExhausted) {
                $statusHtml = "<span class='status-exhausted'><i class='bi bi-flag-fill'></i> Exhausted</span>";
            } else {
                $statusHtml = "<span class='status-active'><i class='bi bi-check-circle-fill'></i> Active</span>";
            }
            $fullLink = '/tests/guest_test.php?token=' . $l['token'];
          ?>
          <tr>
            <td class="sr-cell text-center"></td>
            <td>
              <div class="name-cell"><?= htmlspecialchars($l['candidate_name']) ?></div>
              <div class="email-cell"><?= htmlspecialchars($l['candidate_email']) ?></div>
            </td>
            <td><span class="role-badge"><?= htmlspecialchars($jobRoles[$l['job_role']] ?? $l['job_role']) ?></span></td>
            <td><span class="set-badge"><?= htmlspecialchars($l['set_id']) ?></span></td>
            <td class="text-center">
              <span class="attempts-badge"><?= $l['attempts_used'] ?> / <?= (int) $l['max_attempts'] ?></span>
            </td>
            <td class="date-cell" style="font-size:11.5px; white-space:nowrap; color:#8a8578;">
              <?= date('d M Y, h:i A', strtotime($l['expiry_date'])) ?>
            </td>
            <td><?= $statusHtml ?></td>
            <td>
              <div class="action-cell">
                <button class="tbtn tbtn-copy" onclick="copyLink('<?= htmlspecialchars($fullLink, ENT_QUOTES) ?>')">
                  <i class="bi bi-clipboard"></i> Copy
                </button>
                <?php if ($isDisabled): ?>
                  <a href="?enable=<?= $l['id'] ?>" class="tbtn tbtn-toggle"><i class="bi bi-unlock-fill"></i> Enable</a>
                <?php else: ?>
                  <a href="?disable=<?= $l['id'] ?>" class="tbtn tbtn-toggle"><i class="bi bi-lock-fill"></i> Disable</a>
                <?php endif; ?>
                <a href="?regenerate=<?= $l['id'] ?>" class="tbtn tbtn-regen"
                   onclick="return confirm('Generate a new link? The old link will stop working immediately.');">
                  <i class="bi bi-arrow-repeat"></i> Regen
                </a>
                <button class="tbtn tbtn-delete" onclick="confirmDelete(<?= $l['id'] ?>)">
                  <i class="bi bi-trash-fill"></i> Delete
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- ════ DELETE MODAL ════ -->
  <div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header hdr-red">
          <h5 class="modal-title"><i class="bi bi-trash-fill me-2"></i>Delete Link</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <div style="width:52px;height:52px;background:rgba(220,100,90,0.16);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:22px;color:#f19387;">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <p style="color:#e5e2da;font-size:14px;line-height:1.6;">Delete this guest test link?</p>
          <p style="color:#6a675e;font-size:11px;margin-top:6px;">This does not delete the candidate's existing test results.</p>
        </div>
        <div class="modal-footer justify-content-center gap-2">
          <a href="#" id="confirmDeleteBtn" class="btn btn-danger btn-sm"><i class="bi bi-trash-fill me-1"></i> Yes, Delete</a>
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>
  <div class="toast-copied" id="copyToast"><i class="bi bi-check2"></i> Link copied to clipboard!</div>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>
    // ── Job Role -> Set dropdown (mirrors the pattern already used in give_chance.php) ──
    const roleSetsUrl = 'guest_tests_sets.php';
    document.getElementById('createJobRole').addEventListener('change', function () {
      const role = this.value;
      const setSelect = document.getElementById('createSetId');
      setSelect.innerHTML = '<option value="">Loading...</option>';
      if (!role) {
        setSelect.innerHTML = '<option value="">-- Select Job Role First --</option>';
        return;
      }
      fetch(roleSetsUrl + '?job_role=' + encodeURIComponent(role))
        .then(r => r.json())
        .then(sets => {
          if (!sets.length) {
            setSelect.innerHTML = '<option value="">No sets found for this role</option>';
            return;
          }
          setSelect.innerHTML = '<option value="">-- Select Set --</option>' +
            sets.map(s => `<option value="${s}">${s}</option>`).join('');
        })
        .catch(() => {
          setSelect.innerHTML = '<option value="">Failed to load sets</option>';
        });
    });
    // ── DataTable ──
    $(document).ready(function () {
      $('#guestTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 15,
        columnDefs: [{ orderable: false, targets: [0, 7] }],
        language: {
          search: '',
          searchPlaceholder: '🔍 Search candidates...',
          lengthMenu: 'Show _MENU_',
          info: 'Showing _START_–_END_ of _TOTAL_ links',
          paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
        },
        rowCallback: function (row, data, displayIndex) {
          $('td:eq(0)', row).text(displayIndex + 1);
        }
      });
    });
    // ── Copy link ──
    function copyLink(relativePath) {
      const fullUrl = window.location.origin + relativePath;
      navigator.clipboard.writeText(fullUrl).then(() => {
        const toast = document.getElementById('copyToast');
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 2000);
      });
    }
    // ── Delete modal ──
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    function confirmDelete(id) {
      document.getElementById('confirmDeleteBtn').href = '?delete=' + id;
      deleteModal.show();
    }
  </script>
</body>
</html>