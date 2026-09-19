<?php
include '../includes/db_user.php';

// ✅ Job Role labels — same mapping used across the site
$jobRoleLabels = [
  'tele_sales' => 'Tele Sales',
  'sales_consultant' => 'Sales Consultant',
  'team_leader' => 'Team Leader',
  'others' => 'Others',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Users</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

  <style>
    /* ══════════════════════════════════════
       BASE
    ══════════════════════════════════════ */
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background: #f5f6fa;
      color: #333;
      font-family: 'Segoe UI', sans-serif;
      padding: 24px 20px;
    }

    /* ── Page Header ── */
    .page-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
    }

    .page-header i {
      font-size: 24px;
      color: #e53935;
    }

    .page-header h2 {
      font-size: 20px;
      font-weight: 700;
      color: #1a1a1a;
      margin: 0;
    }

    /* ── Alerts ── */
    .alert {
      border-radius: 10px;
      font-size: 13px;
      margin-bottom: 16px;
      border: none;
    }

    .alert-success {
      background: #e8f5e9;
      color: #2e7d32;
      border-left: 4px solid #43a047;
    }

    .alert-danger {
      background: #ffebee;
      color: #c62828;
      border-left: 4px solid #e53935;
    }

    .alert-warning {
      background: #fff8e1;
      color: #f57f17;
      border-left: 4px solid #ffb300;
    }

    /* ── Main Card ── */
    .main-card {
      background: #fff;
      border-radius: 14px;
      border: 1px solid #e8e8e8;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
      overflow: hidden;
    }

    /* ══════════════════════════════════════
       DATATABLE CONTROLS
    ══════════════════════════════════════ */
    .dataTables_wrapper {
      padding: 16px 18px;
    }

    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label {
      color: #666 !important;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .dataTables_wrapper .dataTables_length select {
      background: #fff !important;
      border: 1px solid #ddd !important;
      color: #333 !important;
      border-radius: 7px !important;
      padding: 4px 10px !important;
      font-size: 13px;
      outline: none !important;
    }

    .dataTables_wrapper .dataTables_filter input {
      background: #f9f9f9 !important;
      border: 1px solid #ddd !important;
      color: #333 !important;
      border-radius: 7px !important;
      padding: 6px 12px !important;
      font-size: 13px;
      outline: none !important;
      width: 220px;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
      border-color: #e53935 !important;
      box-shadow: 0 0 0 3px rgba(229, 57, 53, 0.1) !important;
    }

    .dataTables_wrapper .dataTables_info {
      color: #999 !important;
      font-size: 12px;
      padding-top: 10px !important;
    }

    /* ══════════════════════════════════════
       TABLE
    ══════════════════════════════════════ */
    table.dataTable {
      font-size: 13px !important;
      border-collapse: collapse !important;
      width: 100% !important;
    }

    /* TH */
    table.dataTable thead th {
      background: #fafafa !important;
      color: #888 !important;
      border-bottom: 2px solid #f0f0f0 !important;
      border-top: none !important;
      border-left: none !important;
      border-right: none !important;
      padding: 11px 14px !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      text-transform: uppercase;
      letter-spacing: 0.7px;
      white-space: nowrap;
    }

    table.dataTable thead th.sorting:after,
    table.dataTable thead th.sorting_asc:after,
    table.dataTable thead th.sorting_desc:after {
      color: #e53935 !important;
      opacity: 0.6;
    }

    /* TR / TD */
    table.dataTable tbody tr {
      background: #fff !important;
      border-bottom: 1px solid #f3f3f3 !important;
      transition: background 0.12s;
    }

    table.dataTable tbody tr:hover {
      background: #fef9f9 !important;
    }

    table.dataTable tbody td {
      padding: 10px 14px !important;
      vertical-align: middle !important;
      border: none !important;
      color: #444;
    }

    /* ══════════════════════════════════════
       PAGINATION
    ══════════════════════════════════════ */
    .dataTables_wrapper .dataTables_paginate {
      padding-top: 10px !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
      background: #fff !important;
      border: 1px solid #e8e8e8 !important;
      color: #666 !important;
      border-radius: 7px !important;
      margin: 0 2px !important;
      font-size: 12px !important;
      padding: 5px 11px !important;
      transition: all 0.15s !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: #fff5f5 !important;
      border-color: #e53935 !important;
      color: #e53935 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
      background: #e53935 !important;
      border-color: #e53935 !important;
      color: #fff !important;
      font-weight: 700 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
      opacity: 0.3 !important;
    }

    /* ══════════════════════════════════════
       CELL STYLES
    ══════════════════════════════════════ */
    .sr-cell {
      color: #bbb;
      font-size: 12px;
      font-weight: 600;
      text-align: center;
      width: 40px;
    }

    .name-cell {
      font-weight: 600;
      color: #222;
      font-size: 13px;
    }

    .email-cell {
      color: #777;
      font-size: 12px;
    }

    .pass-cell {
      color: #bbb;
      font-family: 'Courier New', monospace;
      font-size: 11px;
      max-width: 120px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .date-cell {
      color: #aaa;
      font-size: 11px;
      white-space: nowrap;
    }

    /* ══════════════════════════════════════
       BADGES
    ══════════════════════════════════════ */
    .badge-yes {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: #e8f5e9;
      color: #2e7d32;
      border: 1px solid #c8e6c9;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      white-space: nowrap;
    }

    .badge-pending {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: #fff8e1;
      color: #e65100;
      border: 1px solid #ffe0b2;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      white-space: nowrap;
    }

    .badge-no {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: #ffebee;
      color: #c62828;
      border: 1px solid #ffcdd2;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      white-space: nowrap;
    }

    .set-badge {
      display: inline-block;
      background: #ede7f6;
      color: #6a1b9a;
      border: 1px solid #d1c4e9;
      padding: 3px 10px;
      border-radius: 10px;
      font-size: 11px;
      font-weight: 700;
      white-space: nowrap;
    }

    .badge-unassigned {
      display: inline-block;
      background: #f5f5f5;
      color: #bbb;
      border: 1px solid #e8e8e8;
      padding: 3px 10px;
      border-radius: 10px;
      font-size: 11px;
    }

    .role-badge {
      display: inline-block;
      background: #fff3e0;
      color: #e65100;
      border: 1px solid #ffe0b2;
      padding: 3px 10px;
      border-radius: 10px;
      font-size: 11px;
      font-weight: 700;
      white-space: nowrap;
    }
    .custom-role-sub {
      font-size: 10.5px;
      color: #8a8578;
      margin-top: 3px;
      font-style: italic;
    }

    /* ══════════════════════════════════════
       BUTTONS
    ══════════════════════════════════════ */
    .tbtn {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 5px 11px;
      border-radius: 7px;
      font-size: 11px;
      font-weight: 600;
      border: 1px solid transparent;
      cursor: pointer;
      transition: all 0.15s;
      white-space: nowrap;
      text-decoration: none;
      line-height: 1.4;
    }

    /* Edit */
    .tbtn-edit {
      background: #e3f2fd;
      color: #1565c0;
      border-color: #bbdefb;
    }

    .tbtn-edit:hover {
      background: #1565c0;
      color: #fff;
      border-color: #1565c0;
    }

    /* Delete */
    .tbtn-delete {
      background: #ffebee;
      color: #c62828;
      border-color: #ffcdd2;
    }

    .tbtn-delete:hover {
      background: #c62828;
      color: #fff;
      border-color: #c62828;
    }

    /* Allow */
    .tbtn-allow {
      background: #fff8e1;
      color: #e65100;
      border-color: #ffe0b2;
    }

    .tbtn-allow:hover {
      background: #e65100;
      color: #fff;
      border-color: #e65100;
    }

    /* Revoke */
    .tbtn-revoke {
      background: #e8f5e9;
      color: #2e7d32;
      border-color: #c8e6c9;
    }

    .tbtn-revoke:hover {
      background: #2e7d32;
      color: #fff;
      border-color: #2e7d32;
    }

    .action-cell {
      display: flex;
      align-items: center;
      gap: 5px;
      flex-wrap: nowrap;
    }

    /* ══════════════════════════════════════
       MODALS
    ══════════════════════════════════════ */
    .modal-content {
      background: #fff;
      border: 1px solid #e8e8e8;
      border-radius: 14px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    }

    .modal-header {
      border-bottom: 1px solid #f0f0f0;
      padding: 16px 20px;
      border-radius: 14px 14px 0 0;
    }

    .modal-header.hdr-blue {
      background: linear-gradient(135deg, #1565c0, #1976d2);
    }

    .modal-header.hdr-red {
      background: linear-gradient(135deg, #c62828, #e53935);
    }

    .modal-title {
      font-weight: 700;
      color: #fff;
      font-size: 15px;
    }

    .modal-body {
      padding: 20px;
      background: #fff;
    }

    .modal-footer {
      border-top: 1px solid #f0f0f0;
      padding: 14px 20px;
      background: #fafafa;
      border-radius: 0 0 14px 14px;
    }

    .form-label {
      color: #666;
      font-size: 11px;
      font-weight: 700;
      margin-bottom: 5px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .form-control,
    .form-select {
      background: #fafafa !important;
      border: 1px solid #e0e0e0 !important;
      color: #333 !important;
      border-radius: 8px !important;
      font-size: 13px;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #e53935 !important;
      box-shadow: 0 0 0 3px rgba(229, 57, 53, 0.1) !important;
      background: #fff !important;
    }

    small.hint {
      color: #aaa;
      font-size: 11px;
      margin-top: 4px;
      display: block;
    }

    /* Divider in table top area */
    .table-topbar {
      padding: 16px 18px 0;
      border-bottom: 1px solid #f3f3f3;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .table-topbar .tbl-title {
      font-size: 15px;
      font-weight: 700;
      color: #1a1a1a;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .table-topbar .tbl-title i {
      color: #e53935;
    }

    .user-count {
      background: #f0f0f0;
      color: #888;
      font-size: 11px;
      font-weight: 600;
      padding: 2px 10px;
      border-radius: 20px;
    }

    .role-filter-form {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .role-filter-select {
      background: #1c1c21 !important;
      border: 1px solid rgba(212, 175, 106, 0.15) !important;
      color: #cfcbc0 !important;
      border-radius: 8px !important;
      font-size: 12.5px;
      padding: 6px 10px !important;
      outline: none !important;
    }

    .role-filter-select:focus {
      border-color: #d4af6a !important;
    }

    .role-filter-clear {
      color: #e5877e;
      font-size: 12px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .role-filter-download {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: rgba(74, 160, 90, 0.12);
      color: #7bc98a;
      border: 1px solid rgba(74, 160, 90, 0.3);
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.15s;
    }

    .role-filter-download:hover {
      background: #4aa05a;
      color: #fff;
    }

    .role-filter-clear:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>

  <!-- ══════════════════════════════════════
     FLASH MESSAGES
══════════════════════════════════════ -->
  <?php
  $msgMap = [
    'deleted' => ['success', '<i class="bi bi-check-circle me-1"></i> User deleted successfully!'],
    'updated' => ['success', '<i class="bi bi-check-circle me-1"></i> User updated successfully!'],
    'invalid' => ['danger', '<i class="bi bi-x-circle me-1"></i> Invalid User ID!'],
    'error' => ['danger', '<i class="bi bi-x-circle me-1"></i> Operation failed! Try again.'],
    'edit_allowed' => ['success', '<i class="bi bi-unlock me-1"></i> Edit permission granted!'],
    'edit_removed' => ['warning', '<i class="bi bi-lock me-1"></i> Edit permission removed!'],
  ];
  if (isset($_GET['msg']) && isset($msgMap[$_GET['msg']])):
    [$type, $text] = $msgMap[$_GET['msg']];
    ?>
    <div class="alert alert-<?= $type ?> alert-dismissible fade show d-flex align-items-center mb-3">
      <?= $text ?>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- ══════════════════════════════════════
     MAIN CARD
══════════════════════════════════════ -->
  <div class="main-card">

    <!-- Card Top Bar -->
    <div class="table-topbar">
      <div class="tbl-title">
        <i class="bi bi-people-fill"></i>
        All Users
        <?php
        $countRes = $conn->query("SELECT COUNT(*) as total FROM users");
        $total = $countRes->fetch_assoc()['total'];
        ?>
        <span class="user-count"><?= $total ?> total</span>
      </div>
      <form method="GET" class="role-filter-form">
        <select name="job_role" class="form-select role-filter-select" onchange="this.form.submit()">
          <option value="">All Job Roles</option>
          <?php foreach ($jobRoleLabels as $rk => $rl): ?>
            <option value="<?= htmlspecialchars($rk) ?>" <?= (($_GET['job_role'] ?? '') === $rk) ? 'selected' : '' ?>>
              <?= htmlspecialchars($rl) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($_GET['job_role'])): ?>
          <a href="admin_user.php" class="role-filter-clear"><i class="bi bi-x-circle"></i> Clear</a>
        <?php endif; ?>
        <a href="export_users_excel.php?job_role=<?= urlencode($_GET['job_role'] ?? '') ?>"
          class="role-filter-download">
          <i class="bi bi-file-earmark-excel-fill"></i> Download Excel
        </a>
      </form>
    </div>

    <!-- Table -->
    <div class="table-responsive">
      <table id="usersTable" class="table mb-0">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Password</th>
            <th>Assigned Set</th>
            <th>Job Role</th>
            <th>Verified</th>
            <th>Approved</th>
            <th>Created At</th>
            <th>Edit Access</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $roleFilter = trim($_GET['job_role'] ?? '');
          if ($roleFilter !== '' && array_key_exists($roleFilter, $jobRoleLabels)) {
            $roleFilterEsc = mysqli_real_escape_string($conn, $roleFilter);
            $sql = "SELECT u.*
        FROM users u
        WHERE u.job_role = '$roleFilterEsc'
        ORDER BY u.created_at DESC";
          } else {
            $sql = "SELECT u.*
        FROM users u
        ORDER BY u.created_at DESC";
          }
          $result = $conn->query($sql);
          $sr = 0;
          while ($row = $result->fetch_assoc()):
            $sr++;

            $verifiedBadge = $row['is_verified']
              ? "<span class='badge-yes'><i class='bi bi-check2-circle'></i> Yes</span>"
              : "<span class='badge-pending'><i class='bi bi-clock'></i> Pending</span>";

            $approvedBadge = $row['is_approved']
              ? "<span class='badge-yes'><i class='bi bi-check2-circle'></i> Yes</span>"
              : "<span class='badge-no'><i class='bi bi-x-circle'></i> No</span>";

            // Show the user's actual assigned set — the exact set they
            // were given (whether via registration, guest link, or admin
            // edit) — so this always matches what Guest Test Links and
            // View Test Results show for the same user.
            $displaySet = $row['assigned_set'] ?? '';
            $assignedSet = !empty($displaySet)
              ? "<span class='set-badge'>" . htmlspecialchars($displaySet) . "</span>"
              : "<span class='badge-unassigned'>—</span>";

            $roleKey = $row['job_role'] ?? '';
            $roleDisp = $jobRoleLabels[$roleKey] ?? ($roleKey !== '' ? $roleKey : null);
            $customRole = trim($row['custom_job_role'] ?? '');
            $jobRoleBadge = $roleDisp
              ? "<span class='role-badge'>" . htmlspecialchars($roleDisp) . "</span>"
              : "<span class='badge-unassigned'>—</span>";
            if ($roleKey === 'others' && $customRole !== '') {
              $jobRoleBadge .= "<div class='custom-role-sub'>" . htmlspecialchars($customRole) . "</div>";
            }

            $editAccessBtn = $row['edit_allowed']
              ? "<a href='../tests/allow_edit.php?id={$row['id']}'
                  class='tbtn tbtn-revoke'
                  onclick=\"return confirm('Remove edit permission?')\">
                  <i class='bi bi-lock-fill'></i> Revoke
               </a>"
              : "<a href='../tests/allow_edit.php?id={$row['id']}'
                  class='tbtn tbtn-allow'
                  onclick=\"return confirm('Grant edit permission?')\">
                  <i class='bi bi-unlock-fill'></i> Allow
               </a>";

            $eName = htmlspecialchars($row['name'], ENT_QUOTES);
            $eEmail = htmlspecialchars($row['email'], ENT_QUOTES);
            $ePass = htmlspecialchars($row['password'], ENT_QUOTES);
            $eSet = htmlspecialchars($row['assigned_set'], ENT_QUOTES);
            ?>
            <tr>
              <td class="sr-cell"></td>
              <td class="name-cell"><?= htmlspecialchars($row['name']) ?></td>
              <td class="email-cell"><?= htmlspecialchars($row['email']) ?></td>
              <td class="pass-cell" title="<?= $ePass ?>"><?= $ePass ?></td>
              <td><?= $assignedSet ?></td>
              <td><?= $jobRoleBadge ?></td>
              <td><?= $verifiedBadge ?></td>
              <td><?= $approvedBadge ?></td>
              <td class="date-cell"><?= htmlspecialchars($row['created_at']) ?></td>
              <td><?= $editAccessBtn ?></td>
              <td>
                <div class="action-cell">
                  <button class="tbtn tbtn-edit editBtn" data-id="<?= $row['id'] ?>" data-name="<?= $eName ?>"
                    data-email="<?= $eEmail ?>" data-password="<?= $ePass ?>" data-verified="<?= $row['is_verified'] ?>"
                    data-approved="<?= $row['is_approved'] ?>" data-assigned="<?= $eSet ?>">
                    <i class="bi bi-pencil-fill"></i> Edit
                  </button>
                  <button class="tbtn tbtn-delete deleteBtn" data-id="<?= $row['id'] ?>" data-name="<?= $eName ?>">
                    <i class="bi bi-trash-fill"></i> Delete
                  </button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div><!-- /main-card -->

  <!-- ══════════════════════════════════════
     EDIT MODAL
══════════════════════════════════════ -->
  <div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <form method="POST" action="../profile/update_user.php">
        <div class="modal-content">

          <div class="modal-header hdr-blue">
            <h5 class="modal-title">
              <i class="bi bi-pencil-fill me-2"></i>Edit User
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <input type="hidden" name="id" id="edit_id">
            <div class="row g-3">

              <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="text" name="password" id="edit_password" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Assigned Set</label>
                <select name="assigned_set" id="edit_assigned" class="form-select">
                  <?php
                  $setsRes = $conn->query(
                    "SELECT DISTINCT set_id, set_name FROM questions ORDER BY set_id ASC"
                  );
                  while ($s = $setsRes->fetch_assoc()):
                    ?>
                    <option value="<?= htmlspecialchars($s['set_id']) ?>">
                      <?= htmlspecialchars($s['set_id']) . ' — ' . htmlspecialchars($s['set_name']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
                <small class="hint">⚠️ Changing set affects user's next test.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label">Verified</label>
                <select name="is_verified" id="edit_verified" class="form-select">
                  <option value="0">No</option>
                  <option value="1">Yes</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Approved</label>
                <select name="is_approved" id="edit_approved" class="form-select">
                  <option value="0">No</option>
                  <option value="1">Yes</option>
                </select>
              </div>

            </div>
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn btn-success btn-sm">
              <i class="bi bi-save me-1"></i> Update
            </button>
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
              Cancel
            </button>
          </div>

        </div>
      </form>
    </div>
  </div>

  <!-- ══════════════════════════════════════
     DELETE MODAL
══════════════════════════════════════ -->
  <div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">

        <div class="modal-header hdr-red">
          <h5 class="modal-title">
            <i class="bi bi-trash-fill me-2"></i>Delete User
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center" style="padding: 28px 20px;">
          <div style="
          width: 56px; height: 56px;
          background: #ffebee;
          border-radius: 50%;
          display: flex; align-items: center; justify-content: center;
          margin: 0 auto 14px;
        ">
            <i class="bi bi-exclamation-triangle-fill" style="font-size: 24px; color: #e53935;"></i>
          </div>
          <p style="color: #444; font-size: 14px; line-height: 1.6;">
            Delete <strong id="delete_user_name" style="color: #1a1a1a;"></strong>?
          </p>
          <p style="color: #bbb; font-size: 11px; margin-top: 6px;">
            This action cannot be undone.
          </p>
        </div>

        <div class="modal-footer justify-content-center gap-2">
          <a href="#" id="confirmDeleteBtn" class="btn btn-danger btn-sm">
            <i class="bi bi-trash-fill me-1"></i> Yes, Delete
          </a>
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">
            Cancel
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════
     SCRIPTS
══════════════════════════════════════ -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <script>
    $(document).ready(function () {

      $('#usersTable').DataTable({
        order: [[8, 'desc']], // ✅ Column 8 = "Created At" — latest user pehle
        pageLength: 10,
        columnDefs: [
          { orderable: false, targets: [0, 9, 10] } // ✅ Sr.No, Edit Access, Action — non-sortable
        ],
        language: {
          search: '',
          searchPlaceholder: '🔍 Search users...',
          lengthMenu: 'Show _MENU_',
          info: 'Showing _START_–_END_ of _TOTAL_ users',
          paginate: {
            previous: '<i class="bi bi-chevron-left"></i>',
            next: '<i class="bi bi-chevron-right"></i>'
          }
        },

        // ✅ Sr. No. fix — always 1, 2, 3... regardless of sort/search/page
        rowCallback: function (row, data, displayIndex) {
          $('td:eq(0)', row).text(displayIndex + 1);
        }

      });


      // Edit Modal
      $(document).on('click', '.editBtn', function () {
        $('#edit_id').val($(this).data('id'));
        $('#edit_name').val($(this).data('name'));
        $('#edit_email').val($(this).data('email'));
        $('#edit_password').val($(this).data('password'));
        $('#edit_verified').val($(this).data('verified'));
        $('#edit_approved').val($(this).data('approved'));
        $('#edit_assigned').val($(this).data('assigned'));
        $('#editModal').modal('show');
      });

      // Delete Modal
      $(document).on('click', '.deleteBtn', function () {
        $('#delete_user_name').text($(this).data('name'));
        $('#confirmDeleteBtn').attr('href', '../process/delete_user.php?id=' + $(this).data('id'));
        $('#deleteModal').modal('show');
      });

    });
  </script>
</body>

</html>