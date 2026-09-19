<?php
session_start();
include '../includes/db_connect.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
  header("Location: ../auth/login.php");
  exit();
}
function resolveUploadURL($val)
{
  if (empty($val))
    return '';
  if (str_starts_with($val, 'http'))
    return $val;
  $normalized = str_replace('\\', '/', $val);
  $pos = strripos($normalized, '/uploads/');
  if ($pos !== false) {
    $normalized = substr($normalized, $pos + strlen('/uploads/'));
  } elseif (str_starts_with($normalized, 'uploads/')) {
    $normalized = substr($normalized, strlen('uploads/'));
  }
  return '/uploads/' . ltrim($normalized, '/');
}
$query = "SELECT user_id,
                 user_name,
                 email,
                 contact_no,
                 experience,
                 profile_photo,
                 created_at
          FROM user_profiles
          ORDER BY created_at DESC, user_id DESC";
$result = $conn->query($query);
$totalRes = $conn->query("SELECT COUNT(*) as t FROM user_profiles");
$totalCount = $totalRes->fetch_assoc()['t'];
$fresherRes = $conn->query("SELECT COUNT(*) as t FROM user_profiles WHERE experience='fresher'");
$fresherCount = $fresherRes->fetch_assoc()['t'];
$expRes = $conn->query("SELECT COUNT(*) as t FROM user_profiles WHERE experience='experienced'");
$expCount = $expRes->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profiles</title>
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
      min-width: 140px;
      background: linear-gradient(160deg, #1a1a1f, #141417);
      border-radius: 12px;
      border: 1px solid rgba(212, 175, 106, 0.18);
      padding: 16px 20px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
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

    .sum-fresher .sum-icon {
      background: rgba(107, 155, 214, 0.16);
      color: #8fb4e0 !important;
    }

    .sum-exp .sum-icon {
      background: rgba(90, 190, 120, 0.16);
      color: #6bd191 !important;
    }

    .sum-icon i {
      color: inherit !important;
    }

    .main-card {
      background: #141417;
      border-radius: 16px;
      border: 1px solid rgba(212, 175, 106, 0.16);
      box-shadow: 0 6px 24px rgba(0, 0, 0, 0.4);
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

    .btn-pdf-all {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #a5423a;
      color: #fff;
      border: none;
      padding: 9px 18px;
      border-radius: 9px;
      font-size: 12.5px;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.15s;
    }

    .btn-pdf-all:hover {
      background: #c14e3f;
      box-shadow: 0 4px 14px rgba(165, 66, 58, 0.4);
      transform: translateY(-1px);
    }

    .dataTables_wrapper {
      padding: 18px 22px;
      background: #141417;
    }

    table.dataTable {
      background: #141417 !important;
      font-size: 13px !important;
      border-collapse: collapse !important;
      width: 100% !important;
      border: none !important;
    }

    table.dataTable> :not(caption)>*>* {
      border-bottom-width: 0 !important;
      box-shadow: none !important;
    }

    #profilesTable,
    #profilesTable th,
    #profilesTable td,
    #profilesTable tr {
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

    table.dataTable thead th {
      background: #1c1c21 !important;
      color: #d4af6a !important;
      border-bottom: 2px solid rgba(212, 175, 106, 0.25) !important;
      padding: 13px 14px !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      text-transform: uppercase;
      letter-spacing: 0.7px;
      white-space: nowrap;
    }

    table.dataTable thead>tr>th.sorting::before,
    table.dataTable thead>tr>th.sorting_asc::before,
    table.dataTable thead>tr>th.sorting_desc::before {
      color: #d4af6a !important;
      opacity: 1 !important;
    }

    table.dataTable thead>tr>th.sorting::after,
    table.dataTable thead>tr>th.sorting_asc::after,
    table.dataTable thead>tr>th.sorting_desc::after {
      color: #f19387 !important;
      opacity: 1 !important;
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
      padding: 12px 14px !important;
      vertical-align: middle !important;
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
      font-size: 13px;
    }

    .email-cell {
      color: #c9c4b6 !important;
      font-size: 12px;
    }

    .phone-cell {
      color: #a8a496 !important;
      font-size: 12px;
      font-family: 'Courier New', monospace;
    }

    .date-cell {
      color: #6a675e !important;
      font-size: 12px;
      white-space: nowrap;
    }

    .avatar-img {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid rgba(212, 175, 106, 0.25);
      cursor: pointer;
      transition: transform 0.2s, border-color 0.2s;
    }

    .avatar-img:hover {
      transform: scale(1.12);
      border-color: #d4af6a;
    }

    .avatar-default {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: #1e1e23;
      border: 2px solid rgba(212, 175, 106, 0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #6a675e;
      font-size: 16px;
    }

    .badge-fresher {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: rgba(107, 155, 214, 0.16);
      color: #8fb4e0;
      border: 1px solid rgba(107, 155, 214, 0.35);
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      white-space: nowrap;
    }

    .badge-experienced {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: rgba(90, 190, 120, 0.16);
      color: #6bd191;
      border: 1px solid rgba(90, 190, 120, 0.35);
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      white-space: nowrap;
    }

    .badge-other {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: #1e1e23;
      color: #8a8578;
      border: 1px solid rgba(212, 175, 106, 0.15);
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      white-space: nowrap;
    }

    .tbtn-view {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 6px 13px;
      border-radius: 7px;
      font-size: 11px;
      font-weight: 700;
      background: #2c5f8a;
      color: #fff;
      border: none;
      cursor: pointer;
      transition: all 0.15s;
      white-space: nowrap;
      text-decoration: none;
    }

    .tbtn-view:hover {
      background: #3576ab;
      color: #fff;
      box-shadow: 0 3px 10px rgba(44, 95, 138, 0.4);
    }

    #photoModal .modal-content {
      background: transparent;
      border: none;
      box-shadow: none;
    }

    #photoModal .modal-body {
      padding: 10px;
    }

    #photoModal img {
      max-width: 320px;
      max-height: 320px;
      border-radius: 12px;
      border: 4px solid #1e1e23;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    }
  </style>
</head>

<body>
  <!-- SUMMARY CARDS -->
  <div class="summary-row">
    <div class="sum-card sum-total">
      <div class="sum-icon"><i class="bi bi-people-fill"></i></div>
      <div>
        <div class="sum-num"><?= $totalCount ?></div>
        <div class="sum-lbl">Total Profiles</div>
      </div>
    </div>
    <div class="sum-card sum-fresher">
      <div class="sum-icon"><i class="bi bi-mortarboard-fill"></i></div>
      <div>
        <div class="sum-num"><?= $fresherCount ?></div>
        <div class="sum-lbl">Freshers</div>
      </div>
    </div>
    <div class="sum-card sum-exp">
      <div class="sum-icon"><i class="bi bi-briefcase-fill"></i></div>
      <div>
        <div class="sum-num"><?= $expCount ?></div>
        <div class="sum-lbl">Experienced</div>
      </div>
    </div>
  </div>
  <!-- MAIN TABLE CARD -->
  <div class="main-card">
    <div class="table-topbar">
      <div class="tbl-title">
        <i class="bi bi-person-lines-fill"></i>
        User Profiles
        <span class="rec-count"><?= $totalCount ?> profiles</span>
      </div>
      <a href="export_all_profiles_pdf.php" class="btn-pdf-all">
        <i class="bi bi-file-earmark-pdf-fill"></i> Download All Users PDF
      </a>
    </div>
    <div class="table-responsive">
      <table id="profilesTable" class="table mb-0">
        <thead>
          <tr>
            <th class="text-center">#</th>
            <th class="text-center">Photo</th>
            <th>Name</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Experience</th>
            <th>Registered On</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          while ($row = $result->fetch_assoc()):
            $uid = (int) $row['user_id'];
            $uname = htmlspecialchars($row['user_name']);
            $email = htmlspecialchars($row['email']);
            $phone = htmlspecialchars($row['contact_no']);
            $exp = strtolower(trim($row['experience']));
            $regDate = !empty($row['created_at'])
              ? date('d M Y', strtotime($row['created_at']))
              : '—';
            $photoPath = '';
            if (!empty($row['profile_photo'])) {
              $photoPath = htmlspecialchars(resolveUploadURL($row['profile_photo']));
            }
            if ($exp === 'fresher') {
              $expBadge = "<span class='badge-fresher'>
                           <i class='bi bi-mortarboard'></i> Fresher
                         </span>";
            } elseif ($exp === 'experienced') {
              $expBadge = "<span class='badge-experienced'>
                           <i class='bi bi-briefcase'></i> Experienced
                         </span>";
            } else {
              $expBadge = "<span class='badge-other'>" . ucfirst($exp) . "</span>";
            }
            ?>
            <tr>
              <td class="sr-cell"></td>
              <td class="text-center">
                <?php if ($photoPath): ?>
                  <img src="<?= $photoPath ?>" alt="photo" class="avatar-img" data-bs-toggle="modal"
                    data-bs-target="#photoModal" onclick="showPhoto('<?= $photoPath ?>')">
                <?php else: ?>
                  <div class="avatar-default d-flex mx-auto" style="width:38px; height:38px;">
                    <i class="bi bi-person-fill"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td class="name-cell"><?= $uname ?></td>
              <td class="email-cell"><?= $email ?></td>
              <td class="phone-cell"><?= $phone ?></td>
              <td><?= $expBadge ?></td>
              <td class="date-cell" data-order="<?= strtotime($row['created_at']) ?>">
                <i class="bi bi-calendar3" style="color:#6a675e; margin-right:4px;"></i>
                <?= $regDate ?>
              </td>
              <td class="text-center">
                <a href="../admin/admin_profile_details.php?id=<?= $uid ?>" class="tbtn-view">
                  <i class="bi bi-eye-fill"></i> View
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- PHOTO PREVIEW MODAL -->
  <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-transparent border-0">
        <div class="modal-body text-center position-relative">
          <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2"
            data-bs-dismiss="modal" aria-label="Close"></button>
          <img id="modalPhoto" src="" alt="Profile Photo" style="max-width:300px; max-height:300px;
                    border-radius:12px; border:4px solid #1e1e23;
                    box-shadow:0 8px 32px rgba(0,0,0,0.5);">
        </div>
      </div>
    </div>
  </div>
  <!-- SCRIPTS -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(document).ready(function () {
      $('#profilesTable').DataTable({
        order: [[6, 'desc']],
        pageLength: 15,
        columnDefs: [
          { orderable: false, targets: [0, 1, 5, 7] }
        ],
        drawCallback: function () {
          var api = this.api();
          api.column(0, {
            search: 'applied',
            order: 'applied',
            page: 'current'
          }).nodes().each(function (cell, i) {
            cell.innerHTML = api.page.info().start + i + 1;
          });
        },
        language: {
          search: '',
          searchPlaceholder: '🔍 Search profiles...',
          lengthMenu: 'Show _MENU_',
          info: 'Showing _START_–_END_ of _TOTAL_ profiles',
          paginate: {
            previous: '<i class="bi bi-chevron-left"></i>',
            next: '<i class="bi bi-chevron-right"></i>'
          }
        }
      });
    });
    function showPhoto(src) {
      document.getElementById('modalPhoto').src = src;
    }
  </script>
</body>

</html>