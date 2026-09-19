<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/db_connect.php';

// ✅ Role labels for display badges
$jobRoleLabels = [
    'tele_sales'       => 'Tele Sales',
    'sales_consultant' => 'Sales Consultant',
    'team_leader'      => 'Team Leader',
    'others'           => 'Others',
];


// Delete Question
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM questions WHERE id=$id");
    header("Location: ../tests/manage_questions.php");
    exit;
}

// Fetch Questions
$result = mysqli_query($conn, "SELECT * FROM questions ORDER BY set_id, id");

// Count total
$totalRes   = mysqli_query($conn, "SELECT COUNT(*) as t FROM questions");
$totalRow   = mysqli_fetch_assoc($totalRes);
$totalCount = $totalRow['t'];

// Count distinct sets
$setRes   = mysqli_query($conn, "SELECT COUNT(DISTINCT set_id) as s FROM questions");
$setRow   = mysqli_fetch_assoc($setRes);
$setCount = $setRow['s'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Questions</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: #f5f6fa;
      color: #333;
      font-family: 'Segoe UI', sans-serif;
      padding: 24px 20px;
    }

    /* ── Summary Cards ── */
    .summary-row {
      display: flex;
      gap: 12px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .sum-card {
      flex: 1;
      min-width: 140px;
      background: #fff;
      border-radius: 12px;
      border: 1px solid #e8e8e8;
      padding: 14px 18px;
      box-shadow: 0 1px 6px rgba(0,0,0,0.05);
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .sum-icon {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }
    .sum-num {
      font-size: 22px;
      font-weight: 700;
      color: #1a1a1a;
      line-height: 1;
    }
    .sum-lbl {
      font-size: 11px;
      color: #999;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      margin-top: 2px;
    }
    .sum-total .sum-icon { background: #f0f0f0; color: #555; }
    .sum-sets  .sum-icon { background: #e3f2fd; color: #1565c0; }

    /* ── Add Button ── */
    .btn-add {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #e8f5e9;
      color: #2e7d32;
      border: 1px solid #c8e6c9;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.15s;
      margin-bottom: 16px;
    }
    .btn-add:hover {
      background: #2e7d32;
      color: #fff;
      border-color: #2e7d32;
    }

    /* ── Main Card ── */
    .main-card {
      background: #fff;
      border-radius: 14px;
      border: 1px solid #e8e8e8;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      overflow: hidden;
    }

    /* ── Card Top Bar ── */
    .table-topbar {
      padding: 16px 18px;
      border-bottom: 1px solid #f3f3f3;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
    }
    .tbl-title {
      font-size: 15px;
      font-weight: 700;
      color: #1a1a1a;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .tbl-title i { color: #e53935; }
    .rec-count {
      background: #f0f0f0;
      color: #888;
      font-size: 11px;
      font-weight: 600;
      padding: 2px 10px;
      border-radius: 20px;
    }

    /* ── DataTable Controls ── */
    .dataTables_wrapper { padding: 16px 18px; }

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
      box-shadow: 0 0 0 3px rgba(229,57,53,0.1) !important;
    }
    .dataTables_wrapper .dataTables_info {
      color: #999 !important;
      font-size: 12px;
      padding-top: 10px !important;
    }

    /* ── Table ── */
    table.dataTable {
      font-size: 13px !important;
      border-collapse: collapse !important;
      width: 100% !important;
    }
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
    table.dataTable tbody tr {
      background: #fff !important;
      border-bottom: 1px solid #f3f3f3 !important;
      transition: background 0.12s;
    }
    table.dataTable tbody tr:hover { background: #fef9f9 !important; }
    table.dataTable tbody td {
      padding: 10px 14px !important;
      vertical-align: middle !important;
      border: none !important;
      color: #444;
    }

    /* ── Pagination ── */
    .dataTables_wrapper .dataTables_paginate { padding-top: 10px !important; }
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

    /* ── Cell Styles ── */
    .sr-cell { color: #bbb; font-size: 12px; font-weight: 600; text-align: center; width: 40px; }
    .date-cell { color: #999; font-size: 12px; white-space: nowrap; }
    .set-badge {
      display: inline-block;
      background: #ede7f6; color: #6a1b9a;
      border: 1px solid #d1c4e9;
      padding: 3px 10px; border-radius: 10px;
      font-size: 11px; font-weight: 700; white-space: nowrap;
    }
    .role-badge {
      display: inline-block;
      background: #fff3e0; color: #e65100;
      border: 1px solid #ffe0b2;
      padding: 3px 10px; border-radius: 10px;
      font-size: 11px; font-weight: 700; white-space: nowrap;
    }
    .answer-badge {
      display: inline-flex; align-items: center; justify-content: center;
      width: 28px; height: 28px;
      background: #e3f2fd; color: #1565c0;
      border: 1px solid #bbdefb;
      border-radius: 50%; font-size: 12px; font-weight: 700;
    }
    .question-text {
      color: #333; font-size: 13px;
      max-width: 420px; line-height: 1.5;
    }

    /* ── Action Buttons ── */
    .tbtn {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 5px 11px; border-radius: 7px;
      font-size: 11px; font-weight: 600;
      border: 1px solid transparent;
      cursor: pointer; transition: all 0.15s;
      white-space: nowrap; text-decoration: none; line-height: 1.4;
    }
    .tbtn-edit  { background: #e3f2fd; color: #1565c0; border-color: #bbdefb; }
    .tbtn-edit:hover  { background: #1565c0; color: #fff; border-color: #1565c0; }
    .tbtn-delete { background: #ffebee; color: #c62828; border-color: #ffcdd2; }
    .tbtn-delete:hover { background: #c62828; color: #fff; border-color: #c62828; }
    .action-cell { display: flex; align-items: center; gap: 5px; flex-wrap: nowrap; }

    /* ── Modal ── */
    .modal-content {
      background: #fff; border: 1px solid #e8e8e8;
      border-radius: 14px; box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    }
    .modal-header {
      border-bottom: 1px solid #f0f0f0;
      padding: 16px 20px; border-radius: 14px 14px 0 0;
    }
    .modal-header.hdr-red { background: linear-gradient(135deg,#c62828,#e53935); }
    .modal-title  { font-weight: 700; color: #fff; font-size: 15px; }
    .modal-body   { padding: 28px 20px; background: #fff; }
    .modal-footer {
      border-top: 1px solid #f0f0f0; padding: 14px 20px;
      background: #fafafa; border-radius: 0 0 14px 14px;
    }
  </style>
</head>
<body>

<!-- ════ SUMMARY CARDS ════ -->
<div class="summary-row">
  <div class="sum-card sum-total">
    <div class="sum-icon"><i class="bi bi-question-circle-fill"></i></div>
    <div>
      <div class="sum-num"><?= $totalCount ?></div>
      <div class="sum-lbl">Total Questions</div>
    </div>
  </div>
  <div class="sum-card sum-sets">
    <div class="sum-icon"><i class="bi bi-collection-fill"></i></div>
    <div>
      <div class="sum-num"><?= $setCount ?></div>
      <div class="sum-lbl">Total Sets</div>
    </div>
  </div>
</div>

<!-- ── Add New Question Button ── -->
<a href="../tests/add_question.php" class="btn-add">
  <i class="bi bi-plus-circle-fill"></i> Add New Question
</a>

<!-- ════ MAIN TABLE CARD ════ -->
<div class="main-card">

  <div class="table-topbar">
    <div class="tbl-title">
      <i class="bi bi-list-check"></i>
      Manage Questions
      <span class="rec-count"><?= $totalCount ?> questions</span>
    </div>
  </div>

  <div class="table-responsive">
    <table id="questionsTable" class="table mb-0">
      <thead>
        <tr>
          <th class="text-center">#</th>
          <th>Set ID</th>
          <th>Job Role</th>
          <th>Question</th>
          <th class="text-center">Correct Answer</th>
          <th>Added On</th>
          <th class="text-center">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <?php
          $id       = (int)$row['id'];
          $setId    = htmlspecialchars($row['set_id']);
          $question = htmlspecialchars($row['question_text']);
          $answer   = htmlspecialchars($row['correct_option']);
          $roleKey  = $row['job_role'] ?? '';
          $roleDisp = $jobRoleLabels[$roleKey] ?? ($roleKey !== '' ? htmlspecialchars($roleKey) : '—');
          $addedOn  = !empty($row['created_at'])
                      ? date('d M Y', strtotime($row['created_at']))
                      : '—';
        ?>
        <tr>
          <td class="sr-cell"></td>   <!-- ✅ Empty — JS fills it -->
          <td><span class="set-badge"><?= $setId ?></span></td>
          <td><span class="role-badge"><?= htmlspecialchars($roleDisp) ?></span></td>
          <td class="question-text"><?= $question ?></td>
          <td class="text-center">
            <span class="answer-badge"><?= $answer ?></span>
          </td>
          <td class="date-cell">
            <i class="bi bi-calendar3" style="color:#ccc; margin-right:4px;"></i>
            <?= $addedOn ?>
          </td>
          <td>
            <div class="action-cell justify-content-center">
              <a href="../tests/edit_question.php?id=<?= $id ?>" class="tbtn tbtn-edit">
                <i class="bi bi-pencil-fill"></i> Edit
              </a>
              <button class="tbtn tbtn-delete" onclick="confirmDelete(<?= $id ?>)">
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

<!-- ════ DELETE CONFIRM MODAL ════ -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header hdr-red">
        <h5 class="modal-title">
          <i class="bi bi-trash-fill me-2"></i>Delete Question
        </h5>
        <button type="button" class="btn-close btn-close-white"
                data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <div style="width:52px;height:52px;background:#ffebee;border-radius:50%;
                    display:flex;align-items:center;justify-content:center;
                    margin:0 auto 14px;font-size:22px;color:#e53935;">
          <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <p style="color:#444;font-size:14px;line-height:1.6;">
          Are you sure you want to<br>
          <strong style="color:#1a1a1a;">delete this question?</strong>
        </p>
        <p style="color:#bbb;font-size:11px;margin-top:6px;">
          This action cannot be undone.
        </p>
      </div>
      <div class="modal-footer justify-content-center gap-2">
        <a href="#" id="confirmDeleteBtn" class="btn btn-danger btn-sm">
          <i class="bi bi-trash-fill me-1"></i> Yes, Delete
        </a>
        <button type="button" class="btn btn-light btn-sm"
                data-bs-dismiss="modal">Cancel</button>
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
  $('#questionsTable').DataTable({
    order: [[5, 'desc']],
    pageLength: 15,
    columnDefs: [
      { orderable: false, targets: [0, 4, 6] }
    ],
    language: {
      search: '',
      searchPlaceholder: '🔍 Search questions...',
      lengthMenu: 'Show _MENU_',
      info: 'Showing _START_–_END_ of _TOTAL_ questions',
      paginate: {
        previous: '<i class="bi bi-chevron-left"></i>',
        next:     '<i class="bi bi-chevron-right"></i>'
      }
    },
    // ✅ Sr. No. always fixed
    rowCallback: function(row, data, displayIndex) {
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