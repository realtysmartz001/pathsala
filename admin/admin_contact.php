<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../admin/login.php");
    exit();
}
include '../includes/db_connect.php';
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $stmt = $conn->prepare("SELECT * FROM contact_form WHERE 
        first_name LIKE ? OR 
        last_name LIKE ? OR 
        email LIKE ? OR 
        message LIKE ?");
    $searchTerm = "%$search%";
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
} else {
    $stmt = $conn->prepare("SELECT * FROM contact_form ORDER BY id DESC");
}
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$totalRecords = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Messages</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', sans-serif;
      background: radial-gradient(ellipse at top, #131316 0%, #0a0a0d 60%);
      color: #e5e2da;
      padding: 28px 24px;
      min-height: 100vh;
    }
    /* ══ PAGE HEADER ══ */
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 24px;
    }
    .page-header-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .header-icon {
      width: 46px;
      height: 46px;
      background: rgba(220, 100, 90, 0.16);
      border: 1px solid rgba(220, 100, 90, 0.35);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #f19387;
      font-size: 22px;
    }
    .page-header h2 {
      font-family: 'Playfair Display', serif;
      font-size: 21px;
      font-weight: 700;
      color: #fbf5e8;
    }
    .page-header p {
      font-size: 13px;
      color: #a39d8c;
      margin-top: 2px;
    }
    .total-badge {
      background: linear-gradient(160deg, #1a1a1f, #141417);
      border: 1px solid rgba(212, 175, 106, 0.2);
      border-radius: 20px;
      padding: 7px 18px;
      font-size: 13px;
      font-weight: 600;
      color: #c9c4b6;
      display: flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 4px 14px rgba(0,0,0,.3);
    }
    .total-badge span {
      background: #a5423a;
      color: #fff;
      border-radius: 20px;
      padding: 2px 10px;
      font-size: 12px;
      font-weight: 700;
    }
    /* ══ SEARCH BAR CARD ══ */
    .search-card {
      background: linear-gradient(160deg, #1a1a1f, #141417);
      border: 1px solid rgba(212, 175, 106, 0.18);
      border-radius: 14px;
      padding: 18px 20px;
      margin-bottom: 20px;
      box-shadow: 0 4px 14px rgba(0,0,0,.3);
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    .search-wrapper {
      position: relative;
      flex: 1;
      min-width: 200px;
    }
    .search-wrapper i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #6a675e;
      font-size: 15px;
    }
    .search-input {
      width: 100%;
      padding: 10px 14px 10px 36px;
      border: 1px solid rgba(212, 175, 106, 0.22);
      border-radius: 10px;
      font-size: 14px;
      font-family: 'Segoe UI', sans-serif;
      background: #1e1e23;
      color: #e5e2da;
      outline: none;
      transition: border-color 0.2s, background 0.2s;
    }
    .search-input:focus {
      border-color: #d4af6a;
      background: #22222a;
      box-shadow: 0 0 0 3px rgba(212,175,106,.15);
    }
    .search-input::placeholder { color: #6a675e; }
    .btn {
      padding: 10px 18px;
      border-radius: 9px;
      font-size: 14px;
      font-weight: 700;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
      text-decoration: none;
    }
    .btn-search {
      background: linear-gradient(135deg, #d4af6a, #a3803f);
      color: #14140f;
    }
    .btn-search:hover { box-shadow: 0 4px 14px rgba(212,175,106,0.35); transform: translateY(-1px); }
    .btn-reset {
      background: #262629;
      color: #c9c4b6;
      border: 1px solid rgba(212,175,106,0.18);
    }
    .btn-reset:hover {
      background: #2e2e34;
      color: #fbf5e8;
      border-color: #d4af6a;
    }
    /* ══ TABLE CARD ══ */
    .table-card {
      background: #141417;
      border: 1px solid rgba(212, 175, 106, 0.16);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 6px 24px rgba(0,0,0,.4);
    }
    /* ══ TABLE ══ */
    .data-table,
    .data-table th,
    .data-table td,
    .data-table tr {
      border-left: none !important;
      border-right: none !important;
      box-shadow: none !important;
    }
    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13.5px;
    }
    .data-table thead tr {
      background: #1c1c21;
      border-bottom: 2px solid rgba(212, 175, 106, 0.25);
    }
    .data-table thead th {
      padding: 14px 16px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: #d4af6a;
      white-space: nowrap;
    }
    .data-table tbody tr {
      background-color: #16161a;
      border-bottom: 1px solid rgba(212, 175, 106, 0.10);
      border-left: 2px solid transparent;
      transition: background-color 0.15s, border-color 0.15s;
    }
    .data-table tbody tr:nth-child(even) { background-color: #131316; }
    .data-table tbody tr:hover,
    .data-table tbody tr:hover td {
      background-color: #22222a;
      border-left: 2px solid #d4af6a;
    }
    .data-table tbody td {
      background-color: transparent;
      padding: 14px 16px;
      color: #f0ede4;
      vertical-align: middle;
    }
    /* ID Cell */
    .id-cell {
      font-weight: 700;
      color: #a5423a;
      font-size: 12.5px;
    }
    /* Name Cell */
    .name-cell {
      font-weight: 700;
      color: #ffffff;
    }
    /* Email Cell */
    .email-cell {
      color: #c9c4b6;
      font-size: 12.5px;
    }
    .email-cell a {
      color: #8fb4e0;
      text-decoration: none;
    }
    .email-cell a:hover { color: #d4af6a; }
    /* Message Cell */
    .msg-cell {
      max-width: 220px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: #a8a496;
      font-size: 12.5px;
    }
    /* Date Cell */
    .date-cell {
      font-size: 11.5px;
      color: #6a675e;
      white-space: nowrap;
    }
    /* ══ DELETE BUTTON ══ */
    .btn-delete {
      background: #262629;
      color: #b5b1a6;
      border: 1px solid rgba(212,175,106,0.15);
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 12.5px;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.2s;
      font-family: 'Segoe UI', sans-serif;
    }
    .btn-delete:hover {
      background: #a5423a;
      color: #fff;
      border-color: #a5423a;
    }
    /* ══ EMPTY STATE ══ */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #6a675e;
    }
    .empty-state i {
      font-size: 48px;
      margin-bottom: 14px;
      display: block;
      color: rgba(212,175,106,0.25);
    }
    .empty-state p {
      font-size: 15px;
      font-weight: 600;
      color: #a39d8c;
    }
    .empty-state small {
      font-size: 13px;
      color: #6a675e;
    }
    /* ══ RESPONSIVE ══ */
    @media (max-width: 768px) {
      body { padding: 16px 12px; }
      .data-table { font-size: 12.5px; }
      .msg-cell { max-width: 120px; }
    }
  </style>
</head>
<body>
  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-left">
      <div class="header-icon">
        <i class="bi bi-chat-left-text"></i>
      </div>
      <div>
        <h2>Contact Form Submissions</h2>
        <p>View and manage all contact messages</p>
      </div>
    </div>
    <div class="total-badge">
      <i class="bi bi-inbox" style="color:#d4af6a;"></i> Total Records
      <span><?php echo $totalRecords; ?></span>
    </div>
  </div>
  <!-- Search Card -->
  <div class="search-card">
    <form method="GET" action="" style="display:flex; gap:10px; flex-wrap:wrap; width:100%; align-items:center;">
      <div class="search-wrapper">
        <i class="bi bi-search"></i>
        <input type="text"
               name="search"
               class="search-input"
               placeholder="Search by name, email or message..."
               value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <button type="submit" class="btn btn-search">
        <i class="bi bi-search"></i> Search
      </button>
      <a href="../admin/admin_contact.php" class="btn btn-reset">
        <i class="bi bi-arrow-counterclockwise"></i> Reset
      </a>
    </form>
  </div>
  <!-- Table Card -->
  <div class="table-card">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>First Name</th>
          <th>Last Name</th>
          <th>Email</th>
          <th>Message</th>
          <th>Created At</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($rows) > 0): ?>
          <?php foreach ($rows as $row): ?>
          <tr>
            <td class="id-cell">#<?php echo $row['id']; ?></td>
            <td class="name-cell"><?php echo htmlspecialchars($row['first_name']); ?></td>
            <td class="name-cell"><?php echo htmlspecialchars($row['last_name']); ?></td>
            <td class="email-cell">
              <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>">
                <?php echo htmlspecialchars($row['email']); ?>
              </a>
            </td>
            <td class="msg-cell" title="<?php echo htmlspecialchars($row['message']); ?>">
              <?php echo htmlspecialchars($row['message']); ?>
            </td>
            <td class="date-cell">
              <i class="bi bi-clock" style="margin-right:4px;"></i>
              <?php echo htmlspecialchars($row['created_at']); ?>
            </td>
            <td>
              <form method="POST" action="../process/delete_contact.php"
                    onsubmit="return confirm('Are you sure you want to delete this record?');"
                    style="display:inline;">
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                <button type="submit" class="btn-delete">
                  <i class="bi bi-trash3"></i> Delete
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7">
              <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>No records found</p>
                <small>
                  <?php echo $search
                    ? "No results for \"" . htmlspecialchars($search) . "\""
                    : "No contact form submissions yet"; ?>
                </small>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>