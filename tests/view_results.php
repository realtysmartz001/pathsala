<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
  header("Location: ../auth/login.php");
  exit();
}
include '../includes/db_connect.php';
// Delete action
if (isset($_GET['delete'])) {
  $delete_id = (int) $_GET['delete'];
  mysqli_query($conn, "DELETE FROM user_attempts WHERE id = $delete_id");
  header("Location: ../tests/view_results.php");
  exit();
}
// Filters
$where = "1=1";
if (!empty($_GET['search'])) {
  $search = mysqli_real_escape_string($conn, $_GET['search']);
  $where .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%')";
}
if (!empty($_GET['set_no'])) {
  $set_no = mysqli_real_escape_string($conn, $_GET['set_no']);
  $where .= " AND ua.set_no = '$set_no'";
}
if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
  $from = $_GET['from_date'];
  $to = $_GET['to_date'];
  $where .= " AND DATE(ua.submitted_at) BETWEEN '$from' AND '$to'";
}
$sql = "SELECT ua.*, u.name, u.email, u.extra_attempt, u.is_guest
        FROM user_attempts ua
        JOIN users u ON ua.user_id = u.id
        WHERE $where
        ORDER BY ua.submitted_at DESC";
$result = mysqli_query($conn, $sql);
$total = mysqli_num_rows($result);
// Stats
$statsRes = mysqli_query($conn, "SELECT COUNT(*) as t FROM user_attempts");
$statsTot = mysqli_fetch_assoc($statsRes)['t'];
$tabRes = mysqli_query($conn, "SELECT COUNT(*) as t FROM user_attempts WHERE tab_switches > 0");
$tabCount = mysqli_fetch_assoc($tabRes)['t'];
$cleanRes = mysqli_query($conn, "SELECT COUNT(*) as t FROM user_attempts WHERE tab_switches = 0");
$cleanCount = mysqli_fetch_assoc($cleanRes)['t'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test Results Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <style>
    #resultsTable,
    #resultsTable th,
    #resultsTable td,
    #resultsTable tr {
      border-left: none !important;
      border-right: none !important;
      box-shadow: none !important;
    }

    #resultsTable td:first-child,
    #resultsTable th:first-child {
      border-left: none !important;
    }

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

    /* ── Page Header ── */
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 20px;
    }

    .page-header-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .page-header h2 {
      font-family: 'Playfair Display', serif;
      font-size: 21px;
      font-weight: 700;
      color: #fbf5e8;
      margin: 0;
    }

    .page-header i.title-icon {
      font-size: 22px;
      color: #d4af6a;
    }

    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #1e1e23;
      color: #c9c4b6;
      border: 1px solid rgba(212, 175, 106, 0.18);
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.15s;
    }

    .back-btn:hover {
      background: #262629;
      color: #fbf5e8;
      border-color: #d4af6a;
    }

    /* ── Summary Cards ── */
    .summary-row {
      display: flex;
      gap: 14px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .sum-card {
      flex: 1;
      min-width: 140px;
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

    .sum-card:hover {
      border-color: rgba(212, 175, 106, 0.4);
      transform: translateY(-2px);
    }

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
      letter-spacing: 0.6px;
      margin-top: 3px;
    }

    .sum-total .sum-icon {
      background: rgba(212, 175, 106, 0.16);
      color: #e6c98a !important;
    }

    .sum-tab .sum-icon {
      background: rgba(220, 100, 90, 0.16);
      color: #f19387 !important;
    }

    .sum-clean .sum-icon {
      background: rgba(90, 190, 120, 0.16);
      color: #6bd191 !important;
    }

    .sum-icon i {
      color: inherit !important;
      font-size: 19px;
    }

    /* ── Filter Card ── */
    .filter-card {
      background: linear-gradient(160deg, #1a1a1f, #141417);
      border-radius: 12px;
      border: 1px solid rgba(212, 175, 106, 0.18);
      padding: 14px 18px;
      margin-bottom: 20px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 10px;
    }

    .filter-card .fi-input {
      background: #1e1e23;
      border: 1px solid rgba(212, 175, 106, 0.22);
      color: #e5e2da;
      border-radius: 8px;
      padding: 8px 14px;
      font-size: 13px;
      outline: none;
      transition: border-color 0.15s;
      min-width: 200px;
    }

    .filter-card .fi-input::placeholder {
      color: #6a675e;
    }

    .filter-card .fi-input:focus {
      border-color: #d4af6a;
      box-shadow: 0 0 0 3px rgba(212, 175, 106, 0.15);
    }

    .btn-search {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: linear-gradient(135deg, #d4af6a, #a3803f);
      color: #14140f;
      border: none;
      border-radius: 8px;
      padding: 8px 18px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.15s;
    }

    .btn-search:hover {
      box-shadow: 0 4px 14px rgba(212, 175, 106, 0.35);
      transform: translateY(-1px);
    }

    .btn-reset {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #262629;
      color: #f19387;
      border: 1px solid rgba(220, 100, 90, 0.3);
      border-radius: 8px;
      padding: 8px 14px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s;
    }

    .btn-reset:hover {
      background: #a5423a;
      color: #fff;
      border-color: #a5423a;
    }

    .btn-download-pdf {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #a5423a;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 8px 18px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      margin-left: auto;
      transition: all 0.15s;
    }

    .btn-download-pdf:hover {
      background: #c14e3f;
      box-shadow: 0 4px 14px rgba(165, 66, 58, 0.4);
      transform: translateY(-1px);
    }

    /* ── Main Card ── */
    .main-card {
      background: #141417;
      border-radius: 16px;
      border: 1px solid rgba(212, 175, 106, 0.16);
      box-shadow: 0 6px 24px rgba(0, 0, 0, .4);
      overflow: hidden;
    }

    .table-topbar {
      padding: 18px 22px;
      border-bottom: 1px solid rgba(212, 175, 106, 0.14);
      background: linear-gradient(90deg, rgba(212, 175, 106, 0.05), transparent);
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

    .tbl-title i {
      color: #d4af6a;
      font-size: 17px;
    }

    .rec-count {
      background: rgba(212, 175, 106, 0.12);
      color: #d4af6a;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 11px;
      border-radius: 20px;
      border: 1px solid rgba(212, 175, 106, 0.25);
    }

    /* ── DataTable Controls ── */
    .dataTables_wrapper {
      padding: 18px 22px;
      background: #141417;
    }

    table.dataTable {
      background: #141417 !important;
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
      border: 1px solid rgba(212, 175, 106, 0.22) !important;
      color: #e5e2da !important;
      border-radius: 7px !important;
      padding: 5px 10px !important;
      font-size: 13px;
      outline: none !important;
    }

    .dataTables_wrapper .dataTables_filter input {
      background: #1e1e23 !important;
      border: 1px solid rgba(212, 175, 106, 0.22) !important;
      color: #e5e2da !important;
      border-radius: 8px !important;
      padding: 8px 14px !important;
      font-size: 13px;
      outline: none !important;
      width: 220px;
    }

    .dataTables_wrapper .dataTables_filter input::placeholder {
      color: #6a675e;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
      border-color: #d4af6a !important;
      box-shadow: 0 0 0 3px rgba(212, 175, 106, 0.15) !important;
    }

    .dataTables_wrapper .dataTables_info {
      color: #8a8578 !important;
      font-size: 12px;
      padding-top: 12px !important;
    }

    /* ── Table ── */
    table.dataTable {
      font-size: 13px !important;
      border-collapse: collapse !important;
      width: 100% !important;
      border: none !important;
    }

    table.dataTable> :not(caption)>*>* {
      border-bottom-width: 0 !important;
      box-shadow: none !important;
    }

    table.dataTable thead th {
      background: #1c1c21 !important;
      color: #d4af6a !important;
      border-bottom: 2px solid rgba(212, 175, 106, 0.25) !important;
      border-top: none !important;
      border-left: none !important;
      border-right: none !important;
      padding: 13px 14px !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      white-space: nowrap;
    }

    table.dataTable thead th.sorting:after,
    table.dataTable thead th.sorting_asc:after,
    table.dataTable thead th.sorting_desc:after {
      color: #f0d29c !important;
      opacity: 0.9;
    }

    table.dataTable tbody tr {
      background-color: #16161a !important;
      border-bottom: 1px solid rgba(212, 175, 106, 0.10) !important;
      border-left: 2px solid transparent !important;
      transition: background-color 0.15s, border-color 0.15s;
    }

    table.dataTable tbody tr:nth-child(even) {
      background-color: #131316 !important;
    }

    table.dataTable tbody tr:hover,
    table.dataTable tbody tr:hover td {
      background-color: #22222a !important;
      border-left: 2px solid #d4af6a !important;
    }

    table.dataTable tbody td {
      background-color: transparent !important;
      padding: 14px !important;
      vertical-align: middle !important;
      border: none !important;
      border-right: none !important;
      border-left: none !important;
      box-shadow: none !important;
      color: #f0ede4 !important;
    }

    table.dataTable,
    table.dataTable th,
    table.dataTable td {
      border-collapse: collapse !important;
    }

    /* ── Pagination ── */
    .dataTables_wrapper .dataTables_paginate {
      padding-top: 14px !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
      background: #1e1e23 !important;
      border: 1px solid rgba(212, 175, 106, 0.20) !important;
      color: #c9c4b6 !important;
      border-radius: 7px !important;
      margin: 0 2px !important;
      font-size: 12px !important;
      padding: 6px 12px !important;
      transition: all 0.15s !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: rgba(212, 175, 106, 0.14) !important;
      border-color: #d4af6a !important;
      color: #fbf5e8 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
      background: linear-gradient(135deg, #d4af6a, #a3803f) !important;
      border-color: #d4af6a !important;
      color: #14140f !important;
      font-weight: 700 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
      opacity: 0.3 !important;
    }

    /* ── Cell Styles ── */
    .sr-cell {
      color: #6a675e;
      font-size: 12px;
      font-weight: 700;
      text-align: center;
      width: 40px;
    }

    .name-cell {
      font-weight: 700;
      color: #ffffff !important;
      font-size: 13.5px;
    }

    .name-cell-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .row-avatar {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background: linear-gradient(135deg, #d4af6a, #a3803f);
      color: #14140f;
      font-size: 12px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .guest-badge {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      background: rgba(154, 130, 210, 0.16);
      color: #b39ddb;
      border: 1px solid rgba(154, 130, 210, 0.35);
      padding: 1px 8px;
      border-radius: 20px;
      font-size: 10px;
      font-weight: 700;
      margin-left: 6px;
      vertical-align: middle;
    }

    .email-cell {
      color: #c9c4b6 !important;
      font-size: 12.5px;
    }

    .date-cell {
      font-size: 11px;
      white-space: nowrap;
      line-height: 1.6;
    }

    .date-main {
      color: #e5e2da;
      font-weight: 600;
    }

    .date-label {
      display: inline-block;
      font-size: 9px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 1px 6px;
      border-radius: 4px;
      margin-bottom: 2px;
    }

    .label-started {
      background: rgba(107, 155, 214, 0.16);
      color: #8fb4e0;
    }

    .label-submitted {
      background: rgba(90, 190, 120, 0.16);
      color: #6bd191;
    }

    .date-separator {
      border: none;
      border-top: 1px dashed rgba(212, 175, 106, 0.12);
      margin: 4px 0;
    }

    .attempt-badge {
      display: inline-block;
      background: rgba(154, 130, 210, 0.16);
      color: #b39ddb;
      border: 1px solid rgba(154, 130, 210, 0.35);
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      white-space: nowrap;
    }

    .chance-label {
      display: block;
      color: #b39ddb;
      font-size: 10px;
      font-weight: 600;
      margin-top: 3px;
      text-align: center;
    }

    .set-badge {
      display: inline-block;
      background: rgba(107, 155, 214, 0.16);
      color: #8fb4e0;
      border: 1px solid rgba(107, 155, 214, 0.35);
      padding: 3px 10px;
      border-radius: 10px;
      font-size: 11px;
      font-weight: 700;
    }

    .score-cell {
      font-weight: 800;
      font-size: 14px;
      color: #fbf5e8;
    }

    .tab-bad {
      color: #f19387;
      font-weight: 700;
    }

    .tab-good {
      color: #6bd191;
      font-weight: 700;
    }

    .status-pending {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: rgba(212, 175, 106, 0.16);
      color: #f0d29c;
      border: 1px solid rgba(212, 175, 106, 0.4);
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
    }

    .status-progress {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: #1e1e23;
      color: #8a8578;
      border: 1px solid rgba(212, 175, 106, 0.15);
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
    }

    /* ── Action Buttons ── */
    .tbtn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 12px;
      border-radius: 7px;
      font-size: 11px;
      font-weight: 700;
      border: 1px solid transparent;
      cursor: pointer;
      transition: all 0.15s;
      white-space: nowrap;
      text-decoration: none;
      line-height: 1.4;
    }

    .tbtn-view {
      background: #2c5f8a;
      color: #fff;
    }

    .tbtn-view:hover {
      background: #3576ab;
      box-shadow: 0 3px 10px rgba(44, 95, 138, 0.4);
    }

    .tbtn-edit {
      background: #6a4a9c;
      color: #fff;
    }

    .tbtn-edit:hover {
      background: #7d5bb3;
      box-shadow: 0 3px 10px rgba(106, 74, 156, 0.4);
    }

    .tbtn-chance {
      background: #2f7a48;
      color: #fff;
    }

    .tbtn-chance:hover {
      background: #38934f;
      box-shadow: 0 3px 10px rgba(47, 122, 72, 0.4);
    }

    .tbtn-delete {
      background: #262629;
      color: #b5b1a6;
      border-color: rgba(212, 175, 106, 0.15);
    }

    .tbtn-delete:hover {
      background: #a5423a;
      color: #fff;
      border-color: #a5423a;
    }

    .action-wrap {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }

    /* ── Delete Modal ── */
    .modal-content {
      background: #17171b;
      border: 1px solid rgba(212, 175, 106, 0.2);
      border-radius: 14px;
      box-shadow: 0 12px 40px rgba(0, 0, 0, .6);
    }

    .modal-header {
      border-bottom: 1px solid rgba(212, 175, 106, 0.14);
      padding: 16px 20px;
      border-radius: 14px 14px 0 0;
    }

    .modal-header.hdr-red {
      background: linear-gradient(135deg, #8a352c, #b34c3f);
    }

    .modal-title {
      font-weight: 700;
      color: #fff;
      font-size: 15px;
    }

    .modal-body {
      padding: 28px 20px;
      background: #17171b;
    }

    .modal-footer {
      border-top: 1px solid rgba(212, 175, 106, 0.12);
      padding: 14px 20px;
      background: #1c1c21;
      border-radius: 0 0 14px 14px;
    }
  </style>
</head>

<body>
  <!-- ════ PAGE HEADER ════ -->
  <div class="page-header">
    <div class="page-header-left">
      <i class="bi bi-bar-chart-fill title-icon"></i>
      <h2>Test Results Report</h2>
    </div>
    <a href="../admin/after_admin_login.php" class="back-btn">
      <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
  </div>
  <!-- ════ SUMMARY CARDS ════ -->
  <div class="summary-row">
    <div class="sum-card sum-total">
      <div class="sum-icon"><i class="bi bi-clipboard-data-fill"></i></div>
      <div>
        <div class="sum-num"><?= $statsTot ?></div>
        <div class="sum-lbl">Total Attempts</div>
      </div>
    </div>
    <div class="sum-card sum-tab">
      <div class="sum-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <div>
        <div class="sum-num"><?= $tabCount ?></div>
        <div class="sum-lbl">Tab Switched</div>
      </div>
    </div>
    <div class="sum-card sum-clean">
      <div class="sum-icon"><i class="bi bi-shield-check-fill" style="color:#6bd191 !important;"></i></div>
      <div>
        <div class="sum-num"><?= $cleanCount ?></div>
        <div class="sum-lbl">Clean Attempts</div>
      </div>
    </div>
  </div>
  <!-- ════ FILTER CARD ════ -->
  <form method="get" class="filter-card">
    <i class="bi bi-funnel-fill" style="color:#6a675e; font-size:15px;"></i>
    <input type="text" name="search" class="fi-input" placeholder="Search name / email..."
      value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    <button type="submit" class="btn-search">
      <i class="bi bi-search"></i> Search
    </button>
    <a href="../tests/view_results.php" class="btn-reset">
      <i class="bi bi-x-circle"></i> Reset
    </a>
    <a href="../admin/export_results_pdf.php?search=<?= urlencode($_GET['search'] ?? '') ?>&set_no=<?= urlencode($_GET['set_no'] ?? '') ?>&from_date=<?= urlencode($_GET['from_date'] ?? '') ?>&to_date=<?= urlencode($_GET['to_date'] ?? '') ?>"
      class="btn-download-pdf">
      <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
    </a>
  </form>
  <!-- ════ MAIN TABLE CARD ════ -->
  <div class="main-card">
    <div class="table-topbar">
      <div class="tbl-title">
        <i class="bi bi-list-check"></i>
        Results
        <span class="rec-count"><?= $total ?> records</span>
      </div>
    </div>
    <div class="table-responsive">
      <table id="resultsTable" class="table mb-0">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>User</th>
            <th>Email</th>
            <th class="text-center">Attempt</th>
            <th class="text-center">Set No</th>
            <th class="text-center">Score</th>
            <th class="text-center">Tab Switches</th>
            <th>Started / Submitted</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($total > 0):
            mysqli_data_seek($result, 0);
            while ($row = mysqli_fetch_assoc($result)):
              $cn = (int) $row['attempt_no'];
              $v = $cn % 100;
              $suffixes = ['th', 'st', 'nd', 'rd'];
              $sfx = (isset($suffixes[($v - 20) % 10]))
                ? $suffixes[($v - 20) % 10]
                : (isset($suffixes[min($v, 3)])
                  ? $suffixes[min($v, 3)] : $suffixes[0]);
              $startedAt = (!empty($row['start_time']) && $row['start_time'] !== '0000-00-00 00:00:00')
                ? date('d M Y, h:i A', strtotime($row['start_time']))
                : '—';
              $submittedAt = (!empty($row['end_time']) && $row['end_time'] !== '0000-00-00 00:00:00')
                ? date('d M Y, h:i A', strtotime($row['end_time']))
                : '—';
              ?>
              <tr>
                <td class="sr-cell"></td>
                <td class="name-cell">
                  <div class="name-cell-wrap">
                    <div class="row-avatar"><?= strtoupper(substr($row['name'] ?: 'U', 0, 1)) ?></div>
                    <span>
                      <?= htmlspecialchars($row['name']) ?>
                      <?php if (!empty($row['is_guest'])): ?>
                        <span class="guest-badge"><i class="bi bi-person-badge"></i> Guest</span>
                      <?php endif; ?>
                    </span>
                  </div>
                </td>
                <td class="email-cell"><?= htmlspecialchars($row['email']) ?></td>
                <td class="text-center">
                  <span class="attempt-badge">
                    <i class="bi bi-arrow-repeat"></i> Attempt <?= $cn ?>
                  </span>
                  <span class="chance-label">(<?= $cn . $sfx ?> Chance)</span>
                </td>
                <td class="text-center">
                  <span class="set-badge"><?= htmlspecialchars($row['set_no']) ?></span>
                </td>
                <td class="text-center score-cell"><?= (int) $row['score'] ?></td>
                <td class="text-center <?= ($row['tab_switches'] > 0) ? 'tab-bad' : 'tab-good' ?>">
                  <?php if ($row['tab_switches'] > 0): ?>
                    <i class="bi bi-exclamation-triangle-fill"></i>
                  <?php else: ?>
                    <i class="bi bi-shield-check-fill"></i>
                  <?php endif; ?>
                  <?= (int) ($row['tab_switches'] ?? 0) ?>
                </td>
                <td class="date-cell" data-order="<?= !empty($row['end_time']) && $row['end_time'] != '0000-00-00 00:00:00'
                  ? strtotime($row['end_time'])
                  : (!empty($row['submitted_at']) ? strtotime($row['submitted_at']) : 0); ?>">
                  <span class="date-label label-started">
                    <i class="bi bi-play-circle-fill"></i> Started
                  </span><br>
                  <span class="date-main"><?= $startedAt ?></span>
                  <hr class="date-separator">
                  <span class="date-label label-submitted">
                    <i class="bi bi-check-circle-fill"></i> Submitted
                  </span><br>
                  <span class="date-main"><?= $submittedAt ?></span>
                </td>
                <td>
                  <div class="action-wrap">
                    <a href="../tests/view_result_detail.php?user_id=<?= $row['user_id'] ?>&attempt=<?= $row['attempt_no'] ?>"
                      class="tbtn tbtn-view">
                      <i class="bi bi-eye-fill"></i> View
                    </a>
                    <a href="../profile/edit_profile.php?id=<?= $row['user_id'] ?>" class="tbtn tbtn-edit">
                      <i class="bi bi-pencil-fill"></i> Edit
                    </a>
                    <?php if ($row['extra_attempt'] == 1): ?>
                      <span class="status-pending">
                        <i class="bi bi-clock-fill"></i> Pending
                      </span>
                    <?php elseif (!empty($row['end_time'])): ?>
                      <a href="../tests/give_chance.php?user_id=<?= $row['user_id'] ?>" class="tbtn tbtn-chance">
                        <i class="bi bi-arrow-counterclockwise"></i> Chance
                      </a>
                    <?php else: ?>
                      <span class="status-progress">
                        <i class="bi bi-lock-fill"></i> In Progress
                      </span>
                    <?php endif; ?>
                    <button class="tbtn tbtn-delete" onclick="confirmDelete(<?= $row['id'] ?>)">
                      <i class="bi bi-trash-fill"></i> Delete
                    </button>
                  </div>
                </td>
              </tr>
              <?php
            endwhile;
          else: ?>
            <tr>
              <td colspan="9" class="dataTables_empty text-center py-5" style="color:#6a675e; font-size:13px;">
                <i class="bi bi-inbox" style="font-size:32px; display:block; margin-bottom:8px;"></i>
                No results found!
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- ════ DELETE CONFIRM MODAL ════ -->
  <div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header hdr-red">
          <h5 class="modal-title">
            <i class="bi bi-trash-fill me-2"></i>Delete Result
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <div style="
          width:52px; height:52px; background:rgba(220,100,90,0.16); border-radius:50%;
          display:flex; align-items:center; justify-content:center;
          margin:0 auto 14px; font-size:22px; color:#f19387;">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <p style="color:#e5e2da; font-size:14px; line-height:1.6;">
            Are you sure you want to<br>
            <strong style="color:#fbf5e8;">delete this result?</strong>
          </p>
          <p style="color:#6a675e; font-size:11px; margin-top:6px;">
            This action cannot be undone.
          </p>
        </div>
        <div class="modal-footer justify-content-center gap-2">
          <a href="#" id="confirmDeleteBtn" class="btn btn-danger btn-sm">
            <i class="bi bi-trash-fill me-1"></i> Yes, Delete
          </a>
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>
  <!-- ════ SCRIPTS ════ -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(document).ready(function () {
      $('#resultsTable').DataTable({
        order: [[7, 'desc']],
        pageLength: 15,
        columnDefs: [
          { orderable: false, targets: [0, 3, 8] },
          { type: "num", targets: 7 }
        ],
        language: {
          search: '',
          searchPlaceholder: '🔍 Search...',
          lengthMenu: 'Show _MENU_',
          info: 'Showing _START_–_END_ of _TOTAL_ records',
          paginate: {
            previous: '<i class="bi bi-chevron-left"></i>',
            next: '<i class="bi bi-chevron-right"></i>'
          }
        },
        rowCallback: function (row, data, displayIndex) {
          $('td:eq(0)', row).text(displayIndex + 1);
        }
      });
    });
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    function confirmDelete(id) {
      document.getElementById('confirmDeleteBtn').href = '?delete=' + id;
      deleteModal.show();
    }
  </script>
</body>

</html>