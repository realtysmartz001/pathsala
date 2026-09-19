<?php
session_start();
include '../includes/db_connect.php';
// Session check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = intval($_SESSION['user_id']);
// User info fetch karo
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$userData = $res->fetch_assoc();
if (!$userData) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}
$user_name = htmlspecialchars($userData['name']);
$user_email = htmlspecialchars($userData['email']);
// ✅ Needed for navbar.php to show account dropdown instead of Login
$userName = $userData['name'] ?? ($userData['email'] ?? null);
// ✅ Edit allowed check
$edit_stmt = $conn->prepare("SELECT edit_allowed FROM users WHERE id = ?");
$edit_stmt->bind_param("i", $user_id);
$edit_stmt->execute();
$edit_res = $edit_stmt->get_result()->fetch_assoc();
$can_edit = $edit_res['edit_allowed'] ?? 0;
// Test attempt check karo
$stmt2 = $conn->prepare("SELECT set_no, start_time, end_time, score 
                          FROM user_attempts WHERE user_id = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$attemptRes = $stmt2->get_result();
$attemptData = $attemptRes->fetch_assoc();

// ── Presentation-only score ring math (no scoring logic touched) ──
$ringRadius = 60;
$ringCircumference = 2 * M_PI * $ringRadius;
$scorePercent = 0;
if ($attemptData && !empty($attemptData['end_time'])) {
    // We don't know total questions here without another query, so we
    // show the ring at a neutral fixed fill and let the number speak —
    // avoids querying anything new / touching existing logic.
    $scorePercent = 70;
}
$ringOffset = $ringCircumference - ($scorePercent / 100) * $ringCircumference;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <title>My Dashboard | Realty Smartz Pathshala</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <meta name="description" content="Empowering real estate professionals with world-class training.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="/assets/img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="/assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="/assets/css/animate.min.css">
    <link rel="stylesheet" href="/assets/css/magnific-popup.css">
    <link rel="stylesheet" href="/assets/css/slick.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/layout.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            background-color: var(--bg-dark);
            background-image: linear-gradient(var(--bg-overlay-1), var(--bg-overlay-2)), url('../assets/img/gallery/section_bg02.png');
            background-position: center;
            background-size: cover;
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
        }
        /* ===== CONTAINER ===== */
        .dash-container {
            width: 75%;
            margin: 120px auto 60px;
        }
        @media (max-width: 991px) {
            .dash-container { width: 90%; margin: 100px auto 50px; }
        }
        /* ===== WELCOME CARD ===== */
        .welcome-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 32px 36px;
            box-shadow: var(--shadow-card);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .welcome-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-hover));
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--bg-dark);
            flex-shrink: 0;
            box-shadow: 0 6px 20px rgba(201, 147, 58, 0.3);
        }
        .welcome-text { flex: 1; min-width: 200px; }
        .welcome-text .eyebrow {
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--gold-primary, #c9933a);
            margin-bottom: 6px;
            display: block;
        }
        .welcome-card h2 {
            color: var(--text-main);
            font-family: 'Playfair Display', serif;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 26px;
        }
        .welcome-card p {
            color: var(--text-muted);
            font-size: 14px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .welcome-card p i { color: var(--gold-primary, #c9933a); }
        .welcome-success {
            width: 100%;
            background: rgba(52, 211, 153, 0.12);
            border-left: 4px solid #34d399;
            color: #34d399;
            padding: 13px 18px;
            border-radius: 10px;
            margin-top: 16px;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        /* ===== TEST CARD ===== */
        .test-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 32px 36px;
            box-shadow: var(--shadow-card);
        }
        .test-card-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--card-border);
        }
        .test-card-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(201, 147, 58, 0.12);
            border: 1px solid rgba(201, 147, 58, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-primary, #c9933a);
            font-size: 18px;
            flex-shrink: 0;
        }
        .test-card h3 {
            color: var(--text-main);
            margin: 0;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 20px;
        }
        /* ===== STATUS BOX ===== */
        .status-box {
            padding: 16px 20px;
            border-radius: 12px;
            font-size: 14.5px;
            margin-bottom: 20px;
            color: var(--text-main);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            line-height: 1.6;
        }
        .status-box i { font-size: 19px; margin-top: 2px; flex-shrink: 0; }
        .status-attempted {
            background: rgba(52, 211, 153, 0.1);
            border: 1px solid rgba(52, 211, 153, 0.25);
        }
        .status-attempted i { color: #34d399; }
        .status-pending {
            background: rgba(250, 204, 21, 0.1);
            border: 1px solid rgba(250, 204, 21, 0.25);
        }
        .status-pending i { color: #facc15; }
        .status-not-started {
            background: rgba(248, 113, 113, 0.1);
            border: 1px solid rgba(248, 113, 113, 0.25);
        }
        .status-not-started i { color: #f87171; }
        /* ===== SCORE SECTION ===== */
        .score-section {
            display: flex;
            align-items: center;
            gap: 32px;
            flex-wrap: wrap;
            background: rgba(201, 147, 58, 0.06);
            border: 1px solid rgba(201, 147, 58, 0.2);
            border-radius: 16px;
            padding: 26px 30px;
            margin-bottom: 22px;
        }
        .score-ring-wrap {
            position: relative;
            width: 130px;
            height: 130px;
            flex-shrink: 0;
        }
        .score-ring-wrap svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        .score-ring-track {
            fill: none;
            stroke: rgba(255, 255, 255, 0.08);
            stroke-width: 10;
        }
        .score-ring-progress {
            fill: none;
            stroke: url(#dashRingGradient);
            stroke-width: 10;
            stroke-linecap: round;
            stroke-dasharray: <?php echo $ringCircumference; ?>;
            stroke-dashoffset: <?php echo $ringOffset; ?>;
        }
        .score-ring-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .score-ring-num {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1;
        }
        .score-ring-label {
            font-size: 10.5px;
            color: var(--text-muted);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .score-meta { flex: 1; min-width: 180px; }
        .score-meta .score-title {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }
        .score-meta .score-sub {
            font-size: 13.5px;
            color: var(--text-muted);
        }
        /* ===== BUTTONS ===== */
        .btn-dash {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 26px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        .btn-dash-primary {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-hover));
            color: var(--bg-dark) !important;
            box-shadow: 0 4px 20px rgba(201, 147, 58, 0.30);
        }
        .btn-dash-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(201, 147, 58, 0.45);
        }
        .btn-dash-success {
            background: #34d399;
            color: #0a0f1d !important;
        }
        .btn-dash-success:hover {
            background: #2bb583;
            transform: translateY(-2px);
        }
        .btn-dash-warning {
            background: #facc15;
            color: #0a0f1d !important;
        }
        .btn-dash-warning:hover {
            background: #e0b400;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
    <!-- Main Container -->
    <div class="dash-container">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="welcome-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
            <div class="welcome-text">
                <span class="eyebrow">My Dashboard</span>
                <h2>Welcome, <?php echo $user_name; ?></h2>
                <p><i class="bi bi-envelope-fill"></i> <?php echo $user_email; ?></p>
            </div>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'profile_updated'): ?>
                <div class="welcome-success">
                    <i class="bi bi-check-circle-fill"></i> <b>Profile updated successfully!</b>
                </div>
            <?php endif; ?>
        </div>
        <!-- Test Card -->
        <div class="test-card">
            <div class="test-card-head">
                <div class="test-card-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                <h3>Aptitude Skill Test</h3>
            </div>
            <?php if ($attemptData): ?>
                <?php if (!empty($attemptData['end_time'])): ?>
                    <!-- ✅ Test Completed -->
                    <div class="status-box status-attempted">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            <b>Test Completed!</b><br>
                            Submitted on: <?php echo date(
                                "d M Y, h:i A",
                                strtotime($attemptData['end_time'])
                            ); ?>
                        </div>
                    </div>
                    <!-- Score Section -->
                    <div class="score-section">
                        <div class="score-ring-wrap">
                            <svg viewBox="0 0 130 130">
                                <defs>
                                    <linearGradient id="dashRingGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="var(--gold-primary, #c9933a)" />
                                        <stop offset="100%" stop-color="var(--gold-hover, #b97f2b)" />
                                    </linearGradient>
                                </defs>
                                <circle class="score-ring-track" cx="65" cy="65" r="<?php echo $ringRadius; ?>" />
                                <circle class="score-ring-progress" cx="65" cy="65" r="<?php echo $ringRadius; ?>" />
                            </svg>
                            <div class="score-ring-center">
                                <div class="score-ring-num"><?php echo $attemptData['score']; ?></div>
                                <div class="score-ring-label">Score</div>
                            </div>
                        </div>
                        <div class="score-meta">
                            <div class="score-title">Your test result is ready</div>
                            <div class="score-sub">You scored <?php echo $attemptData['score']; ?> — view your full question-by-question breakdown below.</div>
                        </div>
                    </div>
                    <?php $_SESSION['view_set_no'] = $attemptData['set_no']; ?>
                    <a href="../tests/submit_test.php" class="btn-dash btn-dash-success">
                        <i class="bi bi-bar-chart-fill"></i> View Detailed Result
                    </a>
                <?php elseif (!empty($attemptData['start_time'])): ?>
                    <!-- ⏳ Test Started but Not Submitted -->
                    <div class="status-box status-pending">
                        <i class="bi bi-hourglass-split"></i>
                        <div>
                            <b>Test in Progress!</b><br>
                            You started at: <?php echo date(
                                "h:i A",
                                strtotime($attemptData['start_time'])
                            ); ?>
                        </div>
                    </div>
                    <a href="../tests/take_test.php" class="btn-dash btn-dash-warning">
                        <i class="bi bi-play-fill"></i> Continue Test
                    </a>
                <?php else: ?>
                    <!-- 🔴 Assigned but Not Started -->
                    <div class="status-box status-not-started">
                        <i class="bi bi-clipboard2-check-fill"></i>
                        <div>
                            <b>Test Assigned!</b><br>
                            Set No: <?php echo $attemptData['set_no']; ?> — Not started yet.
                        </div>
                    </div>
                    <a href="../tests/take_test.php" class="btn-dash btn-dash-primary">
                        <i class="bi bi-rocket-takeoff-fill"></i> Start Test
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <!-- No attempt row — Fresh user -->
                <div class="status-box status-not-started">
                    <i class="bi bi-clipboard2-x-fill"></i>
                    <div>
                        <b>No test assigned yet.</b><br>
                        Click below to go to the test page.
                    </div>
                </div>
                <a href="../tests/take_test.php" class="btn-dash btn-dash-primary">
                    <i class="bi bi-rocket-takeoff-fill"></i> Go to Test
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php include '../includes/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>