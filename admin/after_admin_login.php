<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
  header("Location: ../auth/login.php");
  exit();
}
$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');

include '../includes/db_connect.php';

// ── Dashboard stats ──
$totalUsersRes = $conn->query("SELECT COUNT(*) AS c FROM users");
$totalUsers = (int) ($totalUsersRes->fetch_assoc()['c'] ?? 0);

$pendingRes = $conn->query("SELECT COUNT(*) AS c FROM users WHERE is_approved = 0 AND is_verified = 1");
$pendingApprovals = (int) ($pendingRes->fetch_assoc()['c'] ?? 0);

$todayAttemptsRes = $conn->query("SELECT COUNT(*) AS c FROM user_attempts WHERE DATE(start_time) = CURDATE()");
$todayAttempts = (int) ($todayAttemptsRes->fetch_assoc()['c'] ?? 0);

$avgScoreRes = $conn->query("SELECT AVG(score) AS a FROM user_attempts WHERE end_time IS NOT NULL AND end_time != '0000-00-00 00:00:00'");
$avgScoreRow = $avgScoreRes->fetch_assoc();
$avgScore = $avgScoreRow['a'] !== null ? round((float) $avgScoreRow['a'], 1) : 0;

// ── Recently Registered (last 5) ──
$recentUsersRes = $conn->query("SELECT id, name, email, job_role, is_approved, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recentUsers = [];
while ($ru = $recentUsersRes->fetch_assoc()) {
  $recentUsers[] = $ru;
}
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
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: #0a0a0d;
      color: #e8e6e1;
      height: 100vh;
      overflow: hidden;
    }

    /* ══ TOP NAVBAR ══ */
    .top-navbar {
      height: 56px;
      background: #121215;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 16px;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      border-bottom: 1px solid rgba(212, 175, 106, 0.12);
    }

    .top-navbar .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: 'Playfair Display', serif;
      font-size: 18px;
      font-weight: 700;
      color: #f0e6d2;
      letter-spacing: 0.3px;
    }

    .top-navbar .brand i {
      color: #d4af6a;
      font-size: 22px;
    }

    .hamburger {
      background: none;
      border: none;
      color: #cfcbc0;
      font-size: 22px;
      cursor: pointer;
      padding: 6px 10px;
      border-radius: 50%;
      transition: background 0.2s;
      margin-right: 12px;
    }

    .hamburger:hover {
      background: rgba(212, 175, 106, 0.10);
    }

    .admin-info {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .admin-avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: linear-gradient(135deg, #d4af6a, #a3803f);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 14px;
      color: #0a0a0d;
    }

    .admin-name {
      font-size: 14px;
      font-weight: 600;
      color: #cfcbc0;
    }

    .logout-btn {
      background: rgba(210, 90, 80, 0.12);
      color: #e5877e;
      border: 1px solid rgba(210, 90, 80, 0.3);
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 13px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s;
    }

    .logout-btn:hover {
      background: #c94f42;
      color: #fff;
      border-color: #c94f42;
    }

    /* ══ LAYOUT ══ */
    .layout {
      display: flex;
      height: calc(100vh - 56px);
      margin-top: 56px;
      position: relative;
    }

    /* ══ SIDEBAR ══ */
    .sidebar {
      width: 240px;
      min-width: 240px;
      background: #121215;
      overflow-y: auto;
      overflow-x: hidden;
      transition: width 0.3s ease, min-width 0.3s ease;
      border-right: 1px solid rgba(212, 175, 106, 0.12);
      padding: 8px 0;
      position: relative;
      z-index: 100;
      flex-shrink: 0;
    }

    .sidebar.collapsed {
      width: 64px;
      min-width: 64px;
    }

    .sidebar-label {
      font-size: 11px;
      font-weight: 700;
      color: #8a8578;
      text-transform: uppercase;
      padding: 10px 18px 4px;
      letter-spacing: 1px;
      white-space: nowrap;
      overflow: hidden;
      transition: opacity 0.2s;
    }

    .sidebar.collapsed .sidebar-label {
      opacity: 0;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 11px 18px;
      cursor: pointer;
      border-radius: 8px;
      margin: 2px 8px;
      transition: background 0.2s, color 0.2s;
      white-space: nowrap;
      overflow: hidden;
      text-decoration: none;
      color: #b5b1a6;
      font-size: 14px;
      font-weight: 500;
      border: none;
      background: none;
      width: calc(100% - 16px);
      text-align: left;
      position: relative;
      z-index: 101;
    }

    .nav-item:hover {
      background: rgba(212, 175, 106, 0.08);
      color: #f0e6d2;
    }

    .nav-item.active {
      background: linear-gradient(135deg, rgba(212, 175, 106, 0.16), rgba(212, 175, 106, 0.06));
      color: #e8ceA0;
      border-left: 2px solid #d4af6a;
      padding-left: 16px;
    }

    .nav-item i {
      font-size: 18px;
      min-width: 20px;
      text-align: center;
      flex-shrink: 0;
    }

    .nav-label {
      opacity: 1;
      transition: opacity 0.2s;
      overflow: hidden;
    }

    .sidebar.collapsed .nav-label {
      opacity: 0;
      width: 0;
    }

    .sidebar.collapsed .nav-item {
      justify-content: center;
      padding: 11px 0;
      margin: 2px 8px;
      width: calc(100% - 16px);
    }

    .sidebar.collapsed .nav-item.active {
      padding-left: 0;
      border-left: none;
    }

    .sidebar::-webkit-scrollbar {
      width: 4px;
    }

    .sidebar::-webkit-scrollbar-track {
      background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
      background: rgba(212, 175, 106, 0.25);
      border-radius: 4px;
    }

    .welcome-screen::-webkit-scrollbar {
      width: 6px;
    }

    .welcome-screen::-webkit-scrollbar-track {
      background: transparent;
    }

    .welcome-screen::-webkit-scrollbar-thumb {
      background: rgba(212, 175, 106, 0.20);
      border-radius: 4px;
    }

    /* ══ CONTENT AREA ══ */
    .content-area {
      flex: 1;
      overflow: hidden;
      background: #101013;
      display: flex;
      flex-direction: column;
      position: relative;
      z-index: 1;
    }

    /* ── Welcome screen ── */
    .welcome-screen {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      color: #8a8578;
      gap: 16px;
      overflow-y: auto;
      padding: 40px 20px 40px;
    }

    .welcome-screen i {
      font-size: 64px;
      color: #d4af6a;
      opacity: 0.85;
    }

    .welcome-screen h3 {
      font-family: 'Playfair Display', serif;
      font-size: 24px;
      font-weight: 700;
      color: #f0e6d2;
    }

    .welcome-screen p {
      font-size: 14px;
      color: #8a8578;
    }

    .stat-cards {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      justify-content: center;
      margin-top: 24px;
      max-width: 780px;
    }

    .stat-card {
      background: #16161a;
      border: 1px solid rgba(212, 175, 106, 0.12);
      border-radius: 12px;
      padding: 16px 22px;
      display: flex;
      align-items: center;
      gap: 14px;
      min-width: 170px;
    }

    .stat-icon {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      background: rgba(212, 175, 106, 0.10);
      color: #d4af6a;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 19px;
      flex-shrink: 0;
    }

    .stat-num {
      font-size: 22px;
      font-weight: 700;
      color: #f0e6d2;
      line-height: 1.1;
    }

    .stat-lbl {
      font-size: 11px;
      color: #8a8578;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-top: 2px;
    }

    .recent-users-widget {
      background: #16161a;
      border: 1px solid rgba(212, 175, 106, 0.12);
      border-radius: 12px;
      padding: 16px 18px;
      margin-top: 20px;
      width: 100%;
      max-width: 460px;
      text-align: left;
    }

    .recent-users-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 12.5px;
      font-weight: 700;
      color: #f0e6d2;
      margin-bottom: 12px;
      letter-spacing: 0.3px;
    }

    .recent-users-header i {
      color: #d4af6a;
      margin-right: 6px;
    }

    .recent-users-header a {
      color: #d4af6a;
      font-size: 11.5px;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
    }

    .recent-users-header a:hover {
      text-decoration: underline;
    }

    .recent-users-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .recent-user-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding-bottom: 10px;
      border-bottom: 1px solid rgba(212, 175, 106, 0.08);
    }

    .recent-user-row:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .recent-user-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: linear-gradient(135deg, #d4af6a, #a3803f);
      color: #0a0a0d;
      font-size: 12px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .recent-user-info {
      flex: 1;
      min-width: 0;
    }

    .recent-user-name {
      font-size: 12.5px;
      font-weight: 600;
      color: #cfcbc0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .recent-user-meta {
      font-size: 10.5px;
      color: #6a675e;
      margin-top: 1px;
    }

    .recent-user-status {
      font-size: 9.5px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 20px;
      white-space: nowrap;
      flex-shrink: 0;
    }

    .status-ok {
      background: rgba(74, 160, 90, 0.12);
      color: #7bc98a;
    }

    .status-pending {
      background: rgba(212, 175, 106, 0.10);
      color: #e0bd7e;
    }

    .quick-cards {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      justify-content: center;
      margin-top: 20px;
      max-width: 700px;
    }

    .quick-card {
      background: #16161a;
      border: 1px solid rgba(212, 175, 106, 0.12);
      border-radius: 10px;
      padding: 16px 24px;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      color: #cfcbc0;
      font-weight: 500;
      min-width: 160px;
    }

    .quick-card:hover {
      background: #1c1c21;
      color: #f0e6d2;
      border-color: #d4af6a;
      transform: translateY(-2px);
    }

    .quick-card i {
      font-size: 20px;
      color: #d4af6a;
    }

    /* ── iframe wrapper ── */
    .iframe-wrapper {
      flex: 1;
      display: none;
      flex-direction: column;
      position: relative;
      z-index: 1;
    }

    .iframe-wrapper.visible {
      display: flex;
    }

    .iframe-topbar {
      background: #121215;
      padding: 8px 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      border-bottom: 1px solid rgba(212, 175, 106, 0.12);
      font-size: 14px;
      color: #b5b1a6;
      flex-shrink: 0;
      position: relative;
      z-index: 2;
    }

    .iframe-topbar .page-title {
      font-weight: 600;
      color: #f0e6d2;
      font-size: 15px;
    }

    .iframe-topbar .breadcrumb-sep {
      color: #4a473e;
    }

    .loading-bar {
      height: 3px;
      background: linear-gradient(90deg, #d4af6a, #a3803f);
      width: 0%;
      transition: width 0.3s ease;
      flex-shrink: 0;
    }

    #main-iframe {
      flex: 1;
      border: none;
      width: 100%;
      background: #fff;
      display: block;
      position: relative;
      z-index: 1;
    }

    /* ══ MOBILE OVERLAY ══ */
    .overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 900;
    }

    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        top: 56px;
        left: 0;
        height: calc(100vh - 56px);
        z-index: 950;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        width: 240px !important;
        min-width: 240px !important;
      }

      .sidebar.mobile-open {
        transform: translateX(0);
      }

      .sidebar.collapsed .nav-label {
        opacity: 1;
        width: auto;
      }

      .sidebar.collapsed .sidebar-label {
        opacity: 1;
      }

      .overlay.active {
        display: block;
      }

      .admin-name {
        display: none;
      }
    }
  </style>
</head>

<body>
  <!-- ════ TOP NAVBAR ════ -->
  <nav class="top-navbar">
    <div class="d-flex align-items-center">
      <button class="hamburger" id="hamburgerBtn" title="Toggle Sidebar">
        <i class="bi bi-list"></i>
      </button>
      <div class="brand">
        <i class="bi bi-gem"></i>
        AdminPanel
      </div>
    </div>
    <div class="admin-info">
      <div class="admin-avatar">
        <?php echo strtoupper(substr($adminName, 0, 1)); ?>
      </div>
      <span class="admin-name"><?php echo $adminName; ?></span>
      <a href="../auth/logout.php" class="logout-btn">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </nav>
  <!-- Mobile overlay -->
  <div class="overlay" id="overlay"></div>
  <!-- ════ LAYOUT ════ -->
  <div class="layout">
    <!-- ══ SIDEBAR ══ -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-label">Main Menu</div>
      <button class="nav-item" onclick="loadPage(this, '../admin/admin_user.php', 'Manage Users')" title="Manage Users">
        <i class="bi bi-people-fill"></i>
        <span class="nav-label">Manage Users</span>
      </button>
      <button class="nav-item" onclick="loadPage(this, '../admin/admin_approve_reject.php', 'Approve Users')"
        title="Approve Users">
        <i class="bi bi-shield-check"></i>
        <span class="nav-label">Approve Users</span>
      </button>
      <button class="nav-item" onclick="loadPage(this, '../tests/manage_questions.php', 'Test Details')"
        title="Test Details">
        <i class="bi bi-journal-text"></i>
        <span class="nav-label">Test Details</span>
      </button>
      <button class="nav-item" onclick="loadPage(this, '../admin/admin_contact.php', 'Contact Messages')"
        title="Contact Messages">
        <i class="bi bi-chat-dots-fill"></i>
        <span class="nav-label">Contact Messages</span>
      </button>
      <div class="sidebar-label" style="margin-top:8px;">Profiles & Results</div>
      <button class="nav-item" onclick="loadPage(this, '../admin/admin_profiles.php', 'Profile Details')"
        title="Profile Details">
        <i class="bi bi-person-badge-fill"></i>
        <span class="nav-label">Profile Details</span>
      </button>
      <button class="nav-item" onclick="loadPage(this, '../tests/view_results.php', 'View Test Results')"
        title="View Test Results">
        <i class="bi bi-bar-chart-fill"></i>
        <span class="nav-label">View Test Results</span>
      </button>
      <button class="nav-item" onclick="loadPage(this, '../admin/guest_tests.php', 'Guest Test Links')"
        title="Guest Test Links">
        <i class="bi bi-person-plus-fill"></i>
        <span class="nav-label">Guest Test Links</span>
      </button>
      <div class="sidebar-label" style="margin-top:8px;">Settings</div>
      <button class="nav-item" onclick="loadPage(this, '../admin/admin_change_set.php', 'Change Active Set')"
        title="Change Active Set">
        <i class="bi bi-sliders"></i>
        <span class="nav-label">Change Active Set</span>
      </button>
      <button class="nav-item" onclick="loadPage(this, '../tests/add_question.php', 'Bulk Upload Questions')"
        title="Bulk Upload Questions">
        <i class="bi bi-cloud-upload-fill"></i>
        <span class="nav-label">Bulk Upload Questions</span>
      </button>
      <div class="sidebar-label" style="margin-top:8px;">Project Management</div>
      <button class="nav-item" onclick="loadPage(this, '../admin/projects/manage.php', 'Manage Projects')"
        title="Manage Projects">
        <i class="bi bi-building-fill"></i>
        <span class="nav-label">Manage Projects</span>
      </button>
      <button class="nav-item" onclick="loadPage(this, '../admin/projects/add.php', 'Add Project')" title="Add Project">
        <i class="bi bi-plus-circle-fill"></i>
        <span class="nav-label">Add Project</span>
      </button>
      <div class="sidebar-label" style="margin-top:8px;">YouTube Videos</div>
      <button class="nav-item" onclick="loadPage(this, '../admin/youtube/manage.php', 'Manage YouTube Videos')"
        title="Manage YouTube Videos">
        <i class="bi bi-youtube"></i>
        <span class="nav-label">Manage Videos</span>
      </button>
      <button class="nav-item" onclick="loadPage(this, '../admin/youtube/add.php', 'Add YouTube Video')"
        title="Add YouTube Video">
        <i class="bi bi-plus-circle-fill"></i>
        <span class="nav-label">Add Video</span>
      </button>
      <div class="sidebar-label" style="margin-top:8px;">Events Management</div>
      <button class="nav-item" onclick="loadPage(this, '../admin/events/manage.php', 'Manage Events')"
        title="Manage Events">
        <i class="bi bi-calendar-event-fill"></i>
        <span class="nav-label">Manage Events</span>
      </button>
      <button class="nav-item" onclick="loadPage(this, '../admin/events/add.php', 'Add Event')" title="Add Event">
        <i class="bi bi-plus-circle-fill"></i>
        <span class="nav-label">Add Event</span>
      </button>
    </aside>
    <!-- ══ CONTENT AREA ══ -->
    <main class="content-area" id="contentArea">
      <!-- Welcome Screen -->
      <div class="welcome-screen" id="welcomeScreen">
        <i class="bi bi-gem"></i>
        <h3>Welcome back, <?php echo $adminName; ?></h3>
        <p>Select a section from the sidebar to get started.</p>
        <div class="stat-cards">
          <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div>
              <div class="stat-num"><?php echo $totalUsers; ?></div>
              <div class="stat-lbl">Total Users</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div>
              <div class="stat-num"><?php echo $pendingApprovals; ?></div>
              <div class="stat-lbl">Pending Approvals</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
            <div>
              <div class="stat-num"><?php echo $todayAttempts; ?></div>
              <div class="stat-lbl">Today's Attempts</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div>
              <div class="stat-num"><?php echo $avgScore; ?></div>
              <div class="stat-lbl">Average Score</div>
            </div>
          </div>
        </div>
        <?php if (!empty($recentUsers)): ?>
          <div class="recent-users-widget">
            <div class="recent-users-header">
              <span><i class="bi bi-person-plus-fill"></i> Recently Registered</span>
              <a href="#"
                onclick="event.preventDefault(); loadPageFromCard('../admin/admin_user.php', 'Manage Users');">View
                all</a>
            </div>
            <div class="recent-users-list">
              <?php foreach ($recentUsers as $ru): ?>
                <?php
                $ruRole = $jobRoleLabels[$ru['job_role'] ?? ''] ?? '—';
                $ruWhen = !empty($ru['created_at']) ? date('d M, h:i A', strtotime($ru['created_at'])) : '—';
                $ruApproved = (int) $ru['is_approved'] === 1;
                ?>
                <div class="recent-user-row">
                  <div class="recent-user-avatar"><?php echo strtoupper(substr($ru['name'] ?: 'U', 0, 1)); ?></div>
                  <div class="recent-user-info">
                    <div class="recent-user-name"><?php echo htmlspecialchars($ru['name']); ?></div>
                    <div class="recent-user-meta"><?php echo htmlspecialchars($ruRole); ?> &middot; <?php echo $ruWhen; ?>
                    </div>
                  </div>
                  <span class="recent-user-status <?php echo $ruApproved ? 'status-ok' : 'status-pending'; ?>">
                    <?php echo $ruApproved ? 'Approved' : 'Pending'; ?>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        <div class="quick-cards">
          <div class="quick-card" onclick="loadPageFromCard('../admin/admin_user.php', 'Manage Users')">
            <i class="bi bi-people-fill"></i> Manage Users
          </div>
          <div class="quick-card" onclick="loadPageFromCard('../admin/admin_approve_reject.php', 'Approve Users')">
            <i class="bi bi-shield-check"></i> Approve Users
          </div>
          <div class="quick-card" onclick="loadPageFromCard('../admin/admin_profiles.php', 'Profile Details')">
            <i class="bi bi-person-badge-fill"></i> Profile Details
          </div>
          <div class="quick-card" onclick="loadPageFromCard('../tests/view_results.php', 'View Test Results')">
            <i class="bi bi-bar-chart-fill"></i> View Results
          </div>
          <div class="quick-card" onclick="loadPageFromCard('../admin/guest_tests.php', 'Guest Test Links')">
            <i class="bi bi-person-plus-fill"></i> Guest Test Links
          </div>
          <div class="quick-card" onclick="loadPageFromCard('../admin/admin_contact.php', 'Contact Messages')">
            <i class="bi bi-chat-dots-fill"></i> Contact Messages
          </div>
          <div class="quick-card" onclick="loadPageFromCard('../tests/add_question.php', 'Bulk Upload Questions')">
            <i class="bi bi-cloud-upload-fill"></i> Bulk Upload
          </div>
          <div class="quick-card" onclick="loadPageFromCard('../admin/youtube/manage.php', 'Manage YouTube Videos')">
            <i class="bi bi-youtube"></i> YouTube Videos
          </div>
        </div>
      </div>
      <!-- iframe wrapper -->
      <div class="iframe-wrapper" id="iframeWrapper">
        <div class="iframe-topbar">
          <i class="bi bi-house-fill" style="color:#d4af6a; cursor:pointer;" onclick="goHome()"
            title="Go to Dashboard"></i>
          <span class="breadcrumb-sep">/</span>
          <span class="page-title" id="pageTitle">—</span>
          <div style="flex:1;"></div>
          <button onclick="reloadIframe()" style="background:#1c1c21;border:1px solid rgba(212,175,106,0.15);color:#cfcbc0;
                       padding:4px 12px;border-radius:6px;cursor:pointer;
                       font-size:13px;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-arrow-clockwise"></i> Reload
          </button>
        </div>
        <div class="loading-bar" id="loadingBar"></div>
        <iframe id="main-iframe" src="about:blank" title="Admin Content"></iframe>
      </div>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // ════ STATE ════
    let currentBtn = null;
    let currentPage = '';
    const sidebar = document.getElementById('sidebar');
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const overlay = document.getElementById('overlay');
    const iframe = document.getElementById('main-iframe');
    const iframeWrapper = document.getElementById('iframeWrapper');
    const welcomeScreen = document.getElementById('welcomeScreen');
    const pageTitle = document.getElementById('pageTitle');
    const loadingBar = document.getElementById('loadingBar');
    // ════ HAMBURGER ════
    hamburgerBtn.addEventListener('click', function () {
      const isMobile = window.innerWidth <= 768;
      if (isMobile) {
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
      } else {
        sidebar.classList.toggle('collapsed');
      }
    });
    overlay.addEventListener('click', function () {
      sidebar.classList.remove('mobile-open');
      overlay.classList.remove('active');
    });
    // ════ LOAD PAGE — sidebar buttons ════
    function loadPage(btn, url, title) {
      if (currentBtn) currentBtn.classList.remove('active');
      btn.classList.add('active');
      currentBtn = btn;
      currentPage = url;
      welcomeScreen.style.display = 'none';
      iframeWrapper.classList.add('visible');
      pageTitle.textContent = title;
      loadingBar.style.width = '40%';
      iframe.src = url;
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
      }
    }
    // ════ LOAD PAGE — quick cards ════
    function loadPageFromCard(url, title) {
      const btns = document.querySelectorAll('.nav-item');
      btns.forEach(b => {
        b.classList.remove('active');
        if (b.getAttribute('onclick') &&
          b.getAttribute('onclick').includes(url)) {
          b.classList.add('active');
          currentBtn = b;
        }
      });
      currentPage = url;
      welcomeScreen.style.display = 'none';
      iframeWrapper.classList.add('visible');
      pageTitle.textContent = title;
      loadingBar.style.width = '40%';
      iframe.src = url;
    }
    // ════ IFRAME LOAD EVENT — only once ════
    iframe.addEventListener('load', function () {
      loadingBar.style.width = '100%';
      setTimeout(() => { loadingBar.style.width = '0%'; }, 400);
      try {
        const loc = iframe.contentWindow.location.href;
        if (loc.includes('../profile/edit_profile.php')) {
          pageTitle.textContent = 'Edit Profile';
        }
        if (loc.includes('../admin/admin_profile_details.php')) {
          pageTitle.textContent = 'Profile Details';
        }
      } catch (e) { /* cross-origin — ignore */ }
    });
    // ════ RELOAD ════
    function reloadIframe() {
      if (currentPage) {
        loadingBar.style.width = '40%';
        iframe.src = currentPage;
      }
    }
    // ════ GO HOME ════
    function goHome() {
      if (currentBtn) currentBtn.classList.remove('active');
      currentBtn = null;
      currentPage = '';
      iframeWrapper.classList.remove('visible');
      welcomeScreen.style.display = 'flex';
      iframe.src = 'about:blank';
      pageTitle.textContent = '—';
    }
  </script>
</body>

</html>