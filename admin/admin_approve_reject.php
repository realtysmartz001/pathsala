<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/db_connect.php';
// ════ AJAX — Approve / Reject / Delete ════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
  header('Content-Type: application/json');
  $id = intval($_POST['id']);
  $action = $_POST['action'];
  if ($action === 'approve' || $action === 'reject') {
    $is_approved = ($action === 'approve') ? 1 : 2;
    $stmt = $conn->prepare("UPDATE users SET is_approved = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_approved, $id);
    $ok = $stmt->execute();
    $stmt->close();
    $eq = $conn->prepare("SELECT email, name FROM users WHERE id = ? LIMIT 1");
    $eq->bind_param("i", $id);
    $eq->execute();
    $eq->bind_result($userEmail, $userName);
    $eq->fetch();
    $eq->close();
    if ($ok && !empty($userEmail)) {
      $status = ($action === 'approve') ? 'approved' : 'rejected';
      $color = ($action === 'approve') ? '#2e7d32' : '#c62828';
      $subject = "Your Account Has Been " . ucfirst($status);
      $htmlMessage = '
            <html><head></head><body>
            <div style="font-family:Arial;background:#fff;border-radius:8px;
                        padding:24px;max-width:500px;margin:auto;
                        border:1px solid #eee;">
                <h2 style="color:' . $color . ';">Account ' . ucfirst($status) . '</h2>
                <p>Hello <strong>' . htmlspecialchars($userName) . '</strong>,</p>
                <p>Your account has been <strong>' . ucfirst($status) . '</strong>
                   by our admin team.</p>
                <p>' . ($action === 'approve'
        ? 'You can now <a href="https://realtysmartzpathshala.in/auth/login.php">log in</a> and access all features.'
        : 'Unfortunately, your account was not approved at this time.') . '</p>
                <p>Regards,<br>Team RealtySmartz Pathshala</p>
            </div></body></html>';
      $headers = "MIME-Version: 1.0\r\n";
      $headers .= "Content-type:text/html;charset=UTF-8\r\n";
      $headers .= "From: Admin@realtysmartzpathshala.in\r\n";
      $headers .= "Reply-To: Admin@realtysmartzpathshala.in\r\n";
      @mail($userEmail, $subject, $htmlMessage, $headers);
    }
    echo json_encode($ok
      ? ['success' => true, 'action' => $action]
      : ['success' => false, 'error' => 'Update failed']);
  } elseif ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode($ok
      ? ['success' => true, 'deleted' => true]
      : ['success' => false, 'error' => 'Delete failed']);
  }
  $conn->close();
  exit;
}
// ════ Fetch users ════
$sql = "SELECT id, name, email, is_verified, is_approved, created_at
        FROM users
        ORDER BY created_at DESC, id DESC";
$result = $conn->query($sql);
// ── Count totals ──
$totalCount = $conn->query("SELECT COUNT(*) as t FROM users")->fetch_assoc()['t'];
$pendingCount = $conn->query("SELECT COUNT(*) as t FROM users WHERE is_approved = 0 AND is_verified = 1")->fetch_assoc()['t'];
$approvedCount = $conn->query("SELECT COUNT(*) as t FROM users WHERE is_approved = 1")->fetch_assoc()['t'];
$rejectedCount = $conn->query("SELECT COUNT(*) as t FROM users WHERE is_approved = 2")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Approve / Reject Users</title>
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
      margin-bottom: 22px;
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
      letter-spacing: .6px;
      margin-top: 3px;
    }

    .sum-total .sum-icon {
      background: rgba(212, 175, 106, 0.16);
      color: #e6c98a;
    }

    .sum-pending .sum-icon {
      background: rgba(212, 175, 106, 0.16);
      color: #f0d29c;
    }

    .sum-approve .sum-icon {
      background: rgba(90, 190, 120, 0.16);
      color: #6bd191;
    }

    .sum-reject .sum-icon {
      background: rgba(220, 100, 90, 0.16);
      color: #f19387;
    }

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
      letter-spacing: .2px;
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
      width: 240px;
    }

    .dataTables_wrapper .dataTables_filter input::placeholder {
      color: #6a675e;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
      border-color: #d4af6a !important;
      box-shadow: 0 0 0 3px rgba(212, 175, 106, .15) !important;
    }

    .dataTables_wrapper .dataTables_info {
      color: #8a8578 !important;
      font-size: 12px;
      padding-top: 12px !important;
    }

    table.dataTable {
      font-size: 13.5px !important; border-collapse: collapse !important;
      width: 100% !important; border: none !important;
    }
    table.dataTable > :not(caption) > * > * {
      border-bottom-width: 0 !important;
      box-shadow: none !important;
    }
    #approveTable,
    #approveTable th,
    #approveTable td,
    #approveTable tr {
      border-left: none !important;
      border-right: none !important;
      box-shadow: none !important;
    }

    table.dataTable thead th {
      background: #1c1c21 !important;
      color: #d4af6a !important;
      border-bottom: 2px solid rgba(212, 175, 106, 0.25) !important;
      border-top: none !important;
      border-left: none !important;
      border-right: none !important;
      padding: 13px 16px !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      text-transform: uppercase;
      letter-spacing: .8px;
      white-space: nowrap;
    }

    table.dataTable thead th.sorting:after,
    table.dataTable thead th.sorting_asc:after,
    table.dataTable thead th.sorting_desc:after {
      color: #f0d29c !important;
      opacity: .9;
    }

    table.dataTable tbody tr {
      background-color: #16161a !important;
      border-bottom: 1px solid rgba(212, 175, 106, 0.10) !important;
      border-left: 2px solid transparent !important;
      transition: background-color .15s, border-color .15s;
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
      padding: 14px 16px !important;
      vertical-align: middle !important;
      border: none !important;
      color: #f0ede4 !important;
    }

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
      transition: all .15s !important;
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
      opacity: .3 !important;
    }

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
      font-size: 14px;
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

    .email-cell {
      color: #c9c4b6 !important;
      font-size: 13px;
    }

    .date-cell {
      color: #a39d8c !important;
      font-size: 12.5px;
      white-space: nowrap;
    }

    .badge-yes {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: rgba(90, 190, 120, 0.16);
      color: #6bd191;
      border: 1px solid rgba(90, 190, 120, 0.35);
      padding: 4px 11px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
    }

    .badge-no {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: rgba(220, 100, 90, 0.16);
      color: #f19387;
      border: 1px solid rgba(220, 100, 90, 0.35);
      padding: 4px 11px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
    }

    .status-approved {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(90, 190, 120, 0.16);
      color: #6bd191;
      border: 1px solid rgba(90, 190, 120, 0.35);
      padding: 5px 13px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
    }

    .status-rejected {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(220, 100, 90, 0.16);
      color: #f19387;
      border: 1px solid rgba(220, 100, 90, 0.35);
      padding: 5px 13px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
    }

    .status-pending {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(212, 175, 106, 0.16);
      color: #f0d29c;
      border: 1px solid rgba(212, 175, 106, 0.4);
      padding: 5px 13px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
    }

    .status-unverified {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #1e1e23;
      color: #8a8578;
      border: 1px solid rgba(212, 175, 106, 0.15);
      padding: 5px 13px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
    }

    .tbtn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 13px;
      border-radius: 7px;
      font-size: 11.5px;
      font-weight: 700;
      border: 1px solid transparent;
      cursor: pointer;
      transition: all .15s;
      white-space: nowrap;
      line-height: 1.4;
    }

    .tbtn-approve {
      background: #2f7a48;
      color: #fff;
    }

    .tbtn-approve:hover {
      background: #38934f;
      box-shadow: 0 3px 10px rgba(47, 122, 72, 0.4);
    }

    .tbtn-reject {
      background: #a5423a;
      color: #fff;
    }

    .tbtn-reject:hover {
      background: #c14e3f;
      box-shadow: 0 3px 10px rgba(165, 66, 58, 0.4);
    }

    .tbtn-delete {
      background: #262629;
      color: #b5b1a6;
      border-color: rgba(212, 175, 106, 0.15);
    }

    .tbtn-delete:hover {
      background: #38383f;
      color: #fbf5e8;
    }

    .action-cell {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: nowrap;
    }

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

    .modal-header.hdr-green {
      background: linear-gradient(135deg, #254a34, #357a4d);
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
  <div class="summary-row">
    <div class="sum-card sum-total">
      <div class="sum-icon"><i class="bi bi-people-fill"></i></div>
      <div>
        <div class="sum-num"><?= $totalCount ?></div>
        <div class="sum-lbl">Total</div>
      </div>
    </div>
    <div class="sum-card sum-pending">
      <div class="sum-icon"><i class="bi bi-clock-fill"></i></div>
      <div>
        <div class="sum-num"><?= $pendingCount ?></div>
        <div class="sum-lbl">Pending</div>
      </div>
    </div>
    <div class="sum-card sum-approve">
      <div class="sum-icon"><i class="bi bi-check-circle-fill"></i></div>
      <div>
        <div class="sum-num"><?= $approvedCount ?></div>
        <div class="sum-lbl">Approved</div>
      </div>
    </div>
    <div class="sum-card sum-reject">
      <div class="sum-icon"><i class="bi bi-x-circle-fill"></i></div>
      <div>
        <div class="sum-num"><?= $rejectedCount ?></div>
        <div class="sum-lbl">Rejected</div>
      </div>
    </div>
  </div>
  <div class="main-card">
    <div class="table-topbar">
      <div class="tbl-title">
        <i class="bi bi-shield-check"></i>
        Approve / Reject Users
        <span class="rec-count"><?= $totalCount ?> records</span>
      </div>
    </div>
    <div class="table-responsive">
      <table id="approveTable" class="table mb-0">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Verified</th>
            <th>Status</th>
            <th>Registered</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
              $id = (int) $row['id'];
              $name = htmlspecialchars($row['name']);
              $email = htmlspecialchars($row['email']);
              $is_verified = (int) $row['is_verified'];
              $is_approved = (int) $row['is_approved'];
              $created_at = !empty($row['created_at'])
                ? date('d M Y', strtotime($row['created_at']))
                : '—';
              $created_ts = !empty($row['created_at']) ? strtotime($row['created_at']) : 0;
              $verifiedBadge = $is_verified
                ? "<span class='badge-yes'><i class='bi bi-check2-circle'></i> Yes</span>"
                : "<span class='badge-no'><i class='bi bi-x-circle'></i> No</span>";
              if (!$is_verified) {
                $statusBadge = "<span class='status-unverified'>
                  <i class='bi bi-envelope-x'></i> Not Verified</span>";
              } elseif ($is_approved === 1) {
                $statusBadge = "<span class='status-approved'>
                  <i class='bi bi-check-circle-fill'></i> Approved</span>";
              } elseif ($is_approved === 2) {
                $statusBadge = "<span class='status-rejected'>
                  <i class='bi bi-x-circle-fill'></i> Rejected</span>";
              } else {
                $statusBadge = "<span class='status-pending'>
                  <i class='bi bi-clock-fill'></i> Pending</span>";
              }
              $actionBtns = '';
              if ($is_verified && $is_approved === 0) {
                $actionBtns .= "
                  <button class='tbtn tbtn-approve' onclick=\"doAction($id,'approve')\">
                    <i class='bi bi-check2'></i> Approve
                  </button>
                  <button class='tbtn tbtn-reject' onclick=\"doAction($id,'reject')\">
                    <i class='bi bi-x'></i> Reject
                  </button>";
              } elseif ($is_verified && $is_approved === 1) {
                $actionBtns .= "
                  <button class='tbtn tbtn-reject' onclick=\"doAction($id,'reject')\">
                    <i class='bi bi-x'></i> Reject
                  </button>";
              } elseif ($is_verified && $is_approved === 2) {
                $actionBtns .= "
                  <button class='tbtn tbtn-approve' onclick=\"doAction($id,'approve')\">
                    <i class='bi bi-check2'></i> Approve
                  </button>";
              }
              ?>
              <tr id="row-<?= $id ?>">
                <td class="sr-cell"></td>
                <td class="name-cell">
                  <div class="name-cell-wrap">
                    <div class="row-avatar"><?= strtoupper(substr($name, 0, 1)) ?></div>
                    <span><?= $name ?></span>
                  </div>
                </td>
                <td class="email-cell"><?= $email ?></td>
                <td><?= $verifiedBadge ?></td>
                <td id="status-cell-<?= $id ?>"><?= $statusBadge ?></td>
                <td class="date-cell" data-order="<?= $created_ts ?>">
                  <i class="bi bi-calendar3" style="color:#6a675e;margin-right:4px;"></i>
                  <?= $created_at ?>
                </td>
                <td>
                  <div class="action-cell" id="action-cell-<?= $id ?>">
                    <?= $actionBtns ?>
                    <button class="tbtn tbtn-delete" onclick="doAction(<?= $id ?>,'delete')">
                      <i class="bi bi-trash-fill"></i> Delete
                    </button>
                  </div>
                </td>
              </tr>
              <?php
            endwhile;
          else:
            ?>
            <tr>
              <td colspan="7" class="dataTables_empty text-center py-4" style="color:#6a675e; font-size:13px;">
                <i class="bi bi-inbox" style="font-size:28px;display:block;margin-bottom:8px;"></i>
                No records found
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header hdr-red" id="modalHeader">
          <h5 class="modal-title" id="confirmModalTitle">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <div id="modalIcon" style="
            width:52px; height:52px; background:rgba(220,100,90,0.16);
            border-radius:50%; display:flex; align-items:center;
            justify-content:center; margin:0 auto 14px;
            font-size:22px; color:#f19387;">
            <i class="bi bi-question-circle-fill"></i>
          </div>
          <p style="color:#e5e2da;font-size:14px;line-height:1.6;" id="confirmModalMsg">Are you sure?</p>
          <p style="color:#6a675e;font-size:11px;margin-top:6px;">
            This action cannot be undone.
          </p>
        </div>
        <div class="modal-footer justify-content-center gap-2">
          <button id="confirmOkBtn" class="btn btn-danger btn-sm">
            <i class="bi bi-check2 me-1"></i> Yes, Confirm
          </button>
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(document).ready(function () {
      $('#approveTable').DataTable({
        order: [[5, 'desc']],
        pageLength: 10,
        columnDefs: [
          { orderable: false, targets: [0, 3, 4, 6] },
          { type: 'num', targets: 5 }
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
    let pendingAction = null;
    let pendingId = null;
    const confirmModal = new bootstrap.Modal(
      document.getElementById('confirmModal')
    );
    function doAction(id, action) {
      pendingId = id;
      pendingAction = action;
      const config = {
        approve: {
          title: '<i class="bi bi-check2-circle me-2"></i>Approve User',
          msg: 'Approve this user? They will receive login access.',
          btnCls: 'btn-success',
          hdrCls: 'hdr-green',
          iconBg: 'rgba(90,190,120,0.16)',
          iconClr: '#6bd191'
        },
        reject: {
          title: '<i class="bi bi-x-circle me-2"></i>Reject User',
          msg: 'Reject this user? They will be denied access.',
          btnCls: 'btn-danger',
          hdrCls: 'hdr-red',
          iconBg: 'rgba(220,100,90,0.16)',
          iconClr: '#f19387'
        },
        delete: {
          title: '<i class="bi bi-trash-fill me-2"></i>Delete Record',
          msg: 'Delete this record permanently?',
          btnCls: 'btn-danger',
          hdrCls: 'hdr-red',
          iconBg: 'rgba(220,100,90,0.16)',
          iconClr: '#f19387'
        }
      };
      const c = config[action];
      document.getElementById('confirmModalTitle').innerHTML = c.title;
      document.getElementById('confirmModalMsg').textContent = c.msg;
      const hdr = document.getElementById('modalHeader');
      hdr.className = 'modal-header ' + c.hdrCls;
      const ico = document.getElementById('modalIcon');
      ico.style.background = c.iconBg;
      ico.style.color = c.iconClr;
      const btn = document.getElementById('confirmOkBtn');
      btn.className = 'btn btn-sm ' + c.btnCls;
      confirmModal.show();
    }
    document.getElementById('confirmOkBtn').addEventListener('click', function () {
      if (!pendingId || !pendingAction) return;
      confirmModal.hide();
      const fd = new FormData();
      fd.append('id', pendingId);
      fd.append('action', pendingAction);
      fetch('', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            if (pendingAction === 'delete') {
              const table = $('#approveTable').DataTable();
              const row = document.getElementById('row-' + pendingId);
              table.row(row).remove().draw();
            } else {
              const statusCell = document.getElementById('status-cell-' + pendingId);
              const actionCell = document.getElementById('action-cell-' + pendingId);
              const id = pendingId;
              if (pendingAction === 'approve') {
                statusCell.innerHTML =
                  "<span class='status-approved'>" +
                  "<i class='bi bi-check-circle-fill'></i> Approved</span>";
                actionCell.innerHTML =
                  `<button class='tbtn tbtn-reject'
                     onclick="doAction(${id},'reject')">
                     <i class='bi bi-x'></i> Reject
                   </button>
                   <button class='tbtn tbtn-delete'
                     onclick="doAction(${id},'delete')">
                     <i class='bi bi-trash-fill'></i> Delete
                   </button>`;
              } else {
                statusCell.innerHTML =
                  "<span class='status-rejected'>" +
                  "<i class='bi bi-x-circle-fill'></i> Rejected</span>";
                actionCell.innerHTML =
                  `<button class='tbtn tbtn-approve'
                     onclick="doAction(${id},'approve')">
                     <i class='bi bi-check2'></i> Approve
                   </button>
                   <button class='tbtn tbtn-delete'
                     onclick="doAction(${id},'delete')">
                     <i class='bi bi-trash-fill'></i> Delete
                   </button>`;
              }
            }
          } else {
            alert('Action failed: ' + (data.error || 'Unknown error'));
          }
          pendingId = null;
          pendingAction = null;
        })
        .catch(err => {
          console.error(err);
          alert('Server error. Check console.');
        });
    });
  </script>
</body>

</html>