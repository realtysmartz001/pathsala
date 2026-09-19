<?php
session_start();
include '../includes/db_connect.php';

// ============================================================
// GUEST BRANCH (ADDED) — runs only for guest sessions, never
// touches $_SESSION['user_id']/user_name/user_email or user_attempts
// ============================================================
if (!empty($_SESSION['guest_user'])) {

    $user_name = $_SESSION['guest_name'];
    $user_email = $_SESSION['guest_email'];
    $set_no = $_SESSION['guest_set_no'];
    $start_time = $_SESSION['guest_start_time'] ?? null;

    // Start button pressed
    if (isset($_POST['startTest']) && empty($start_time)) {
        $start_time = date("Y-m-d H:i:s");
        $_SESSION['guest_start_time'] = $start_time;
        header("Location: ../tests/take_test.php");
        exit();
    }

    // Fetch questions for guest's assigned set
    $questions = [];
    if (!empty($set_no)) {
        $set_no_esc = mysqli_real_escape_string($conn, $set_no);
        $result = mysqli_query($conn, "SELECT * FROM questions WHERE set_id = '$set_no_esc'");
        while ($row = mysqli_fetch_assoc($result)) {
            $questions[] = $row;
        }
    }

    $remaining_time = 600;
    if ($start_time) {
        $elapsed = time() - strtotime($start_time);
        $remaining_time = 600 - $elapsed;
        if ($remaining_time < 0)
            $remaining_time = 0;
    }

    $test_started = ($start_time !== null);

} else {
    // ============================================================
// EXISTING LOGIC — UNCHANGED
// ============================================================

    $user_id = $_SESSION['user_id'];

    // ✅ Users table se naam aur email nikal lo
    $userRes = mysqli_query($conn, "SELECT name, email FROM users WHERE id = $user_id");
    $userData = mysqli_fetch_assoc($userRes);

    $user_name = $userData['name'];
    $user_email = $userData['email'];

    $set_no = '';
    $start_time = null;

    // ✅ Check karo user_attempts mein entry hai ya nahi
    $checkSubmit = mysqli_query($conn, "
SELECT *
FROM user_attempts
WHERE user_id=$user_id
ORDER BY attempt_no DESC
LIMIT 1
");

    if (mysqli_num_rows($checkSubmit) > 0) {

        $row = mysqli_fetch_assoc($checkSubmit);

        $_SESSION['attempt_no'] = $row['attempt_no'];

        $set_no = $row['set_no'];

        $start_time = (!empty($row['start_time']) && $row['start_time'] !== '0000-00-00 00:00:00')
            ? $row['start_time']
            : null;

        // Sirf tab result pe bhejo jab extra chance pending na ho
        $extra = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT extra_attempt FROM users WHERE id=$user_id"
        ));

        if (!empty($row['end_time']) && (int) $extra['extra_attempt'] === 0) {
            header("Location: ../tests/submit_test.php?set_no=" . $row['set_no']);
            exit();
        }
    } else {
        // ══════════════════════════════════════════════════════════
        // ✅ Use the assigned_set already computed once at registration
        // (see register.php — auto round-robin or manual, per role).
        // This is intentionally NOT recalculated here, so a user's set
        // always matches exactly what Manage Users shows for them and
        // never silently changes after the fact if admin later updates
        // that role's active set / mode.
        // ══════════════════════════════════════════════════════════
        $assignedRes = mysqli_query($conn, "SELECT assigned_set, job_role FROM users WHERE id = $user_id");
        $assignedRow = mysqli_fetch_assoc($assignedRes);
        $job_role = trim($assignedRow['job_role'] ?? '');
        $set_no = $assignedRow['assigned_set'] ?? 'rs001';

        $set_no = mysqli_real_escape_string($conn, $set_no);
        $job_role_to_store = mysqli_real_escape_string($conn, $job_role);

        // ✅ user_attempts mein insert karo — role bhi save karo (nullable, safe for old rows)
        if ($job_role_to_store !== '') {
            mysqli_query($conn, "INSERT INTO user_attempts (user_id, set_no, job_role) VALUES ($user_id, '$set_no', '$job_role_to_store')");
        } else {
            mysqli_query($conn, "INSERT INTO user_attempts (user_id, set_no) VALUES ($user_id, '$set_no')");
        }
        $_SESSION['attempt_no'] = mysqli_insert_id($conn);
        $start_time = null;
    }

    // ✅ Start button dabaya
    if (isset($_POST['startTest'])) {
        $start_time = date("Y-m-d H:i:s");

        mysqli_query(
            $conn,
            "
        UPDATE user_attempts
        SET start_time = '$start_time'
        WHERE user_id = $user_id
        AND attempt_no = " . (int) $_SESSION['attempt_no']
        );

        header("Location: ../tests/take_test.php");
        exit();
    }

    // ✅ Questions fetch karo — set_id = set_no (rs001, rs002 etc.)
    $questions = [];
    if (!empty($set_no)) {
        $query = "SELECT * FROM questions WHERE set_id = '$set_no'";
        $result = mysqli_query($conn, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            $questions[] = $row;
        }
    }

    // ✅ Remaining time calculate karo
    $remaining_time = 600; // 15 min default
    if ($start_time) {
        $elapsed = time() - strtotime($start_time);
        $remaining_time = 600 - $elapsed;
        if ($remaining_time < 0)
            $remaining_time = 0;
    }

    // ✅ Test started check
    $test_started = ($start_time !== null) ? true : false;

} // ============ END GUEST/EXISTING WRAP ============
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aptitude Skill Test | Realty Smartz Pathshala</title>
    <meta name="description" content="Attempt your Aptitude Skill Test on Realty Smartz Pathshala.">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Site CSS -->
    <link rel="stylesheet" href="../assets/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="../assets/css/animate.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/layout.css">

    <style>
        /* ───────────────────────────────────────────
           PAGE WRAPPER — pushes content below navbar
        ─────────────────────────────────────────── */
        .test-page-wrapper {
            min-height: 100vh;
            padding-top: 100px;
            padding-bottom: 60px;
            background: var(--bg-primary);
            transition: background 0.3s ease;
        }

        /* ───────────────────────────────────────────
           TEST CARD
        ─────────────────────────────────────────── */
        .test-card {
            background: var(--card-bg, rgba(255, 255, 255, 0.07));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
            border-radius: 20px;
            padding: 40px 36px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.18);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            max-width: 860px;
            margin: 0 auto;
            color: var(--text-primary);
            user-select: none;
        }

        /* ───────────────────────────────────────────
           PAGE HEADING
        ─────────────────────────────────────────── */
        .test-page-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.6rem, 3.5vw, 2.2rem);
            font-weight: 700;
            color: var(--gold, #c9a84c);
            text-align: center;
            margin-bottom: 4px;
        }

        .test-page-subtitle {
            text-align: center;
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-bottom: 28px;
        }

        /* ───────────────────────────────────────────
           USER INFO STRIP
        ─────────────────────────────────────────── */
        .user-info-strip {
            display: flex;
            align-items: center;
            gap: 14px;
            background: var(--bg-secondary, rgba(255, 255, 255, 0.05));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
            border-radius: 14px;
            padding: 14px 20px;
            margin-bottom: 26px;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--gold, #c9a84c);
            color: #fff;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .user-info-text .uname {
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-primary);
        }

        .user-info-text .uemail {
            font-size: 0.82rem;
            color: var(--text-secondary);
        }

        /* ───────────────────────────────────────────
           ATTEMPT BADGE
        ─────────────────────────────────────────── */
        .attempt-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(201, 168, 76, 0.15);
            border: 1px solid var(--gold, #c9a84c);
            color: var(--gold, #c9a84c);
            padding: 5px 16px;
            border-radius: 30px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        /* ───────────────────────────────────────────
           NOTE BOX
        ─────────────────────────────────────────── */
        .test-note-box {
            background: rgba(201, 168, 76, 0.08);
            border-left: 4px solid var(--gold, #c9a84c);
            border-radius: 0 10px 10px 0;
            padding: 12px 18px;
            margin-bottom: 28px;
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .test-note-box i {
            color: var(--gold, #c9a84c);
            margin-right: 6px;
        }

        /* ───────────────────────────────────────────
           PROGRESS BAR
        ─────────────────────────────────────────── */
        .test-progress-bar {
            margin-bottom: 20px;
        }

        .progress-bar-track {
            width: 100%;
            height: 8px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.08);
            overflow: hidden;
            margin-bottom: 6px;
        }

        .progress-bar-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(135deg, #c9a84c, #e2c97e);
            border-radius: 20px;
            transition: width 0.3s ease;
        }

        .progress-bar-label {
            font-size: 0.82rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        [data-theme="light"] .progress-bar-track {
            background: rgba(0, 0, 0, 0.08);
        }

        /* ───────────────────────────────────────────
           TIMER
        ─────────────────────────────────────────── */
        .test-timer-bar {
            display: none;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 24px;
        }

        .test-timer-bar .timer-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .test-timer-bar .timer-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: #e74c3c;
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            border-radius: 10px;
            padding: 4px 16px;
            letter-spacing: 1px;
            font-variant-numeric: tabular-nums;
        }

        /* ───────────────────────────────────────────
           START BUTTON SECTION
        ─────────────────────────────────────────── */
        .start-section {
            text-align: center;
            padding: 30px 0 10px;
        }

        .btn-start-test {
            background: linear-gradient(135deg, #c9a84c, #e2c97e);
            color: #1a1a2e;
            font-weight: 700;
            font-size: 1rem;
            padding: 13px 40px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(201, 168, 76, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-start-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(201, 168, 76, 0.5);
        }

        /* ───────────────────────────────────────────
           QUESTION BLOCK
        ─────────────────────────────────────────── */
        .question-block {
            background: var(--bg-secondary, rgba(255, 255, 255, 0.04));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.09));
            border-radius: 14px;
            padding: 22px 24px;
            margin-bottom: 18px;
            transition: border-color 0.25s ease;
        }

        .question-block:hover {
            border-color: var(--gold, #c9a84c);
        }

        .question-text {
            font-weight: 600;
            font-size: 0.98rem;
            color: var(--text-primary);
            margin-bottom: 14px;
            line-height: 1.6;
        }

        /* ───────────────────────────────────────────
           OPTION LABELS
        ─────────────────────────────────────────── */
        .option-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            border-radius: 10px;
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            margin-bottom: 8px;
            cursor: pointer;
            font-size: 0.92rem;
            color: var(--text-primary);
            transition: all 0.2s ease;
            background: transparent;
        }

        .option-label:hover {
            background: rgba(201, 168, 76, 0.09);
            border-color: var(--gold, #c9a84c);
        }

        .option-label input[type="radio"] {
            accent-color: var(--gold, #c9a84c);
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        /* ───────────────────────────────────────────
           SUBMIT BUTTON
        ─────────────────────────────────────────── */
        .btn-submit-test {
            background: linear-gradient(135deg, #c9a84c, #e2c97e);
            color: #1a1a2e;
            font-weight: 700;
            font-size: 1rem;
            padding: 13px 44px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(201, 168, 76, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(201, 168, 76, 0.5);
        }

        /* ───────────────────────────────────────────
           DIVIDER
        ─────────────────────────────────────────── */
        .test-divider {
            border: none;
            border-top: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            margin: 10px 0 18px;
        }

        /* ───────────────────────────────────────────
           NO QUESTIONS
        ─────────────────────────────────────────── */
        .no-questions-box {
            text-align: center;
            padding: 30px;
            color: #e74c3c;
            font-size: 1rem;
        }

        /* ───────────────────────────────────────────
           LIGHT THEME OVERRIDES
        ─────────────────────────────────────────── */
        [data-theme="light"] .test-card {
            background: rgba(255, 255, 255, 0.92);
            border-color: rgba(0, 0, 0, 0.08);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.10);
        }

        [data-theme="light"] .user-info-strip {
            background: rgba(0, 0, 0, 0.03);
            border-color: rgba(0, 0, 0, 0.08);
        }

        [data-theme="light"] .question-block {
            background: rgba(0, 0, 0, 0.02);
            border-color: rgba(0, 0, 0, 0.08);
        }

        [data-theme="light"] .option-label {
            border-color: rgba(0, 0, 0, 0.1);
        }

        [data-theme="light"] .option-label:hover {
            background: rgba(201, 168, 76, 0.1);
        }

        /* ───────────────────────────────────────────
           RESPONSIVE
        ─────────────────────────────────────────── */
        @media (max-width: 576px) {
            .test-card {
                padding: 24px 16px;
            }

            .test-page-title {
                font-size: 1.4rem;
            }

            .btn-start-test,
            .btn-submit-test {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body oncontextmenu="return false">

    <button class="theme-toggle" id="themeToggle">
        <span class="icon-dark">🌙</span>
        <span class="icon-light">☀️</span>
    </button>

    <?php // include '../includes/navbar.php'; ?>

    <div class="test-page-wrapper">
        <div class="container">
            <div class="test-card">

                <!-- ── Page Title ── -->
                <h1 class="test-page-title">📝 Aptitude Skill Test</h1>
                <p class="test-page-subtitle">Realty Smartz Pathshala — Assessment Portal</p>

                <!-- ── User Info Strip ── -->
                <div class="user-info-strip">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div class="user-info-text">
                        <div class="uname"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="uemail"><?php echo htmlspecialchars($user_email); ?></div>
                    </div>
                </div>

                <?php /*
<div class="text-center mb-3">
...
</div>
*/ ?>

                <!-- ── Note Box ── -->
                <div class="test-note-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>Important:</strong> Please read all the articles on the website carefully before attempting
                    the test.
                    Switching tabs will be detected and may result in automatic submission.
                </div>

                <!-- ── Timer Bar ── -->
                <div class="test-timer-bar" id="timerBar">
                    <span class="timer-label"><i class="fas fa-clock"></i> Time Remaining:</span>
                    <span class="timer-value" id="timer">10:00</span>
                </div>
                <!-- ── Progress Bar ── -->
                <div class="test-progress-bar" id="progressBar" style="display:none;">
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill" id="progressBarFill"></div>
                    </div>
                    <span class="progress-bar-label" id="progressBarLabel">0 of <?php echo count($questions); ?>
                        answered</span>
                </div>

                <!-- ── Start Button ── -->
                <?php if (!$test_started): ?>
                    <div class="start-section" id="startSection">
                        <form method="post">
                            <button type="submit" name="startTest" class="btn-start-test">
                                <i class="fas fa-play-circle"></i> Start Test
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- ── Test Form ── -->
                <form method="post" action="../tests/submit_test.php" id="testForm"
                    style="display: <?php echo $test_started ? 'block' : 'none'; ?>;"
                    onsubmit="return confirm('Are you sure you want to submit your test?');">

                    <?php if (count($questions) > 0): ?>
                        <?php $i = 1;
                        foreach ($questions as $qrow): ?>

                            <div class="question-block">
                                <div class="question-text">
                                    Q<?php echo $i++; ?>.&nbsp;<?php echo htmlspecialchars($qrow['question_text']); ?>
                                </div>

                                <?php
                                $options = [
                                    'A' => $qrow['option_a'],
                                    'B' => $qrow['option_b'],
                                    'C' => $qrow['option_c'],
                                    'D' => $qrow['option_d'],
                                ];
                                foreach ($options as $key => $val): ?>
                                    <label class="option-label">
                                        <input type="radio" name="ans[<?php echo $qrow['id']; ?>]" value="<?php echo $key; ?>">
                                        <span><strong><?php echo $key; ?>.</strong>&nbsp;<?php echo htmlspecialchars($val); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <hr class="test-divider">
                        <?php endforeach; ?>

                        <div class="text-center mt-4 mb-2">
                            <button type="submit" class="btn-submit-test">
                                <i class="fas fa-paper-plane"></i> Submit Test
                            </button>
                        </div>

                    <?php else: ?>
                        <div class="no-questions-box">
                            ⚠️ No questions found for set: <strong><?php echo htmlspecialchars($set_no); ?></strong>
                        </div>
                    <?php endif; ?>

                </form>

            </div><!-- /.test-card -->
        </div><!-- /.container -->
    </div><!-- /.test-page-wrapper -->

    <?php // include '../includes/footer.php'; ?>

    <!-- jQuery (required for preloader fade-out, same as other pages) -->
    <script src="../assets/js/vendor/jquery-1.12.4.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        /* ── Preloader — same logic as index.php, was missing here ── */
        $(window).on('load', function () {
            setTimeout(function () { $('#preloader-active').fadeOut(700); }, 1200);
        });

        /* ── Timer ── */
        let timeLeft = <?php echo (int) $remaining_time; ?>;
        let timerInterval;

        function updateTimer() {
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            document.getElementById("timer").textContent =
                (minutes < 10 ? "0" : "") + minutes + ":" +
                (seconds < 10 ? "0" : "") + seconds;
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                document.getElementById("testForm").submit();
            }
            timeLeft--;
        }

        <?php if ($test_started): ?>
            document.getElementById("timerBar").style.display = "flex";
            timerInterval = setInterval(updateTimer, 1000);
            document.getElementById("progressBar").style.display = "block";
        <?php endif; ?>
            /* ── Progress Bar Update ── */
            (function () {
                var totalQuestions = <?php echo count($questions); ?>;
                var progressFill = document.getElementById("progressBarFill");
                var progressLabel = document.getElementById("progressBarLabel");
                if (!progressFill || !progressLabel || totalQuestions === 0) return;
                function updateProgress() {
                    var answeredQuestionIds = {};
                    document.querySelectorAll('#testForm input[type="radio"]:checked').forEach(function (input) {
                        // name is like "ans[123]" — extract the question id inside the brackets
                        var match = input.name.match(/ans\[(\d+)\]/);
                        if (match) answeredQuestionIds[match[1]] = true;
                    });
                    var answeredCount = Object.keys(answeredQuestionIds).length;
                    var percent = Math.round((answeredCount / totalQuestions) * 100);
                    progressFill.style.width = percent + "%";
                    progressLabel.textContent = answeredCount + " of " + totalQuestions + " answered";
                }
                document.querySelectorAll('#testForm input[type="radio"]').forEach(function (input) {
                    input.addEventListener("change", updateProgress);
                });
                updateProgress();
            })();

        /* ── Tab Switch Detection ── */
        let cheatCount = 0;
        let submittingNow = false;
        let testStarted = <?php echo $test_started ? 'true' : 'false'; ?>;

        document.addEventListener("visibilitychange", function () {
            if (!testStarted) return;
            if (document.hidden && !submittingNow) {
                cheatCount++;
                fetch('../process/update_tab_switch.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'count=' + cheatCount
                });
                if (cheatCount === 1) {
                    alert("⚠️ Warning: Tab switching detected! Next time your test will be submitted automatically.");
                } else if (cheatCount >= 2) {
                    alert("❌ Cheating detected! Your test is being submitted now.");
                    submittingNow = true;
                    document.getElementById("testForm").onsubmit = null;
                    document.getElementById("testForm").submit();
                }
            }
        });

        document.getElementById("testForm").addEventListener("submit", function () {
            submittingNow = true;
        });

        /* ── Keyboard Shortcuts Block ── */
        document.onkeydown = function (e) {
            if (e.ctrlKey && ["c", "u", "v", "x", "a"].includes(e.key)) return false;
            if (e.key === "F12" || e.key === "Escape") return false;
        };
    </script>
    <script>
        const themeToggle = document.getElementById("themeToggle");

        function applyTheme(theme) {
            document.documentElement.setAttribute("data-theme", theme);
            localStorage.setItem("theme", theme);
        }

        const savedTheme = localStorage.getItem("theme") || "dark";
        applyTheme(savedTheme);

        if (themeToggle) {
            themeToggle.addEventListener("click", function () {
                const current = document.documentElement.getAttribute("data-theme");
                applyTheme(current === "dark" ? "light" : "dark");
            });
        }
    </script>
</body>

</html>