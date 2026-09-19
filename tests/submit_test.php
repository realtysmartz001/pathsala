<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
include '../includes/db_connect.php';
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    die("Login required.");
}
$userName = $_SESSION['user_name'] ?? ($_SESSION['user_email'] ?? null);
// ✅ Session se set_no lo agar dashboard se aa rahe ho
if (isset($_SESSION['view_set_no'])) {
    $set_no = $_SESSION['view_set_no'];
    unset($_SESSION['view_set_no']);
}
// ✅ GET request = sirf result dekhna hai (dashboard se)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ✅ Latest submitted attempt lo directly DB se
    $chk = mysqli_query($conn, "SELECT * FROM user_attempts 
                                WHERE user_id = $user_id 
                                AND end_time IS NOT NULL 
                                ORDER BY attempt_no DESC LIMIT 1");
    if (!$chk || mysqli_num_rows($chk) === 0) {
        header("Location: ../tests/take_test.php");
        exit();
    }
    $attempt = mysqli_fetch_assoc($chk);
    $attempt_no = $attempt['attempt_no'];
    $score = $attempt['score'];
    $saved_set = mysqli_real_escape_string($conn, $attempt['set_no']);
    $qRes = mysqli_query($conn, "SELECT q.id, q.question_text, 
                                        q.option_a, q.option_b, q.option_c, q.option_d, 
                                        q.correct_option, q.explanation,
                                        ua.answer AS user_answer
                                 FROM questions q
                                 LEFT JOIN user_answers ua 
                                        ON ua.question_id = q.id 
                                        AND ua.user_id    = $user_id
                                        AND ua.attempt_no = $attempt_no
                                 WHERE q.set_id = '$saved_set'");
    $questions = [];
    while ($row = mysqli_fetch_assoc($qRes)) {
        $questions[] = $row;
    }
    $total = count($questions);
} else {
    // ✅ POST = naya submission
    $attempt_no = $_SESSION['attempt_no'] ?? 1;
    $answers = $_POST['ans'] ?? [];
    $check = mysqli_query($conn, "SELECT start_time, set_no FROM user_attempts 
                                  WHERE user_id  = $user_id 
                                  AND attempt_no = $attempt_no");
    if (!$check || mysqli_num_rows($check) === 0) {
        die("Attempt not found.");
    }
    $attemptRow = mysqli_fetch_assoc($check);
    $start_time = $attemptRow['start_time'] ?? null;
    $set_no = mysqli_real_escape_string($conn, $attemptRow['set_no']);
    if (!$start_time || $start_time === '0000-00-00 00:00:00') {
        die("Test has not been started.");
    }
    // ✅ Already submitted check — double submit rokne ke liye
    $dupCheck = mysqli_query($conn, "SELECT id FROM user_attempts 
                                     WHERE user_id  = $user_id 
                                     AND attempt_no = $attempt_no 
                                     AND end_time IS NOT NULL");
    if ($dupCheck && mysqli_num_rows($dupCheck) > 0) {
        header("Location: ../tests/submit_test.php");
        exit();
    }
    // ✅ Questions fetch karo
    $qRes = mysqli_query($conn, "SELECT * FROM questions WHERE set_id = '$set_no'");
    $questions = [];
    while ($qrow = mysqli_fetch_assoc($qRes)) {
        $questions[] = $qrow;
    }
    $total = count($questions);
    // ✅ Score calculate karo
    $score = 0;
    foreach ($questions as $qrow) {
        $qid = (int) $qrow['id'];
        $user_answer = $answers[$qid] ?? null;
        $correct = $qrow['correct_option'];
        $is_correct = ($user_answer && $user_answer === $correct) ? 1 : 0;
        if ($is_correct)
            $score++;
        if ($user_answer !== null && $user_answer !== '') {
            $ua = mysqli_real_escape_string($conn, $user_answer);
            mysqli_query($conn, "INSERT INTO user_answers 
                                 (user_id, question_id, answer, is_correct, attempt_no)
                                 VALUES ($user_id, $qid, '$ua', $is_correct, $attempt_no)");
        }
    }
    // ✅ Score + end_time save karo
    $end_time = date("Y-m-d H:i:s"); // Asia/Kolkata already set hai upar
    mysqli_query($conn, "UPDATE user_attempts 
                     SET score    = $score, 
                         end_time = '$end_time' 
                     WHERE user_id  = $user_id 
                     AND attempt_no = $attempt_no");
    // Extra chance consume ho gaya
    mysqli_query($conn, "
UPDATE users
SET extra_attempt = 0
WHERE id = $user_id
");
    // ✅ Saved answers ke saath questions reload karo
    $qRes = mysqli_query($conn, "SELECT q.id, q.question_text, 
                                        q.option_a, q.option_b, q.option_c, q.option_d, 
                                        q.correct_option, q.explanation,
                                        ua.answer AS user_answer
                                 FROM questions q
                                 LEFT JOIN user_answers ua 
                                        ON ua.question_id = q.id 
                                        AND ua.user_id    = $user_id
                                        AND ua.attempt_no = $attempt_no
                                 WHERE q.set_id = '$set_no'");
    $questions = [];
    while ($row = mysqli_fetch_assoc($qRes)) {
        $questions[] = $row;
    }
}
// ✅ Percent + ring math (presentation only — no scoring logic touched)
$percent = $total > 0 ? round(($score / $total) * 100) : 0;
$ringRadius = 70;
$ringCircumference = 2 * M_PI * $ringRadius;
$ringOffset = $ringCircumference - ($percent / 100) * $ringCircumference;
if ($percent >= 80) {
    $ratingText = "Excellent!";
    $ratingEmoji = "🏆";
} elseif ($percent >= 60) {
    $ratingText = "Good Job!";
    $ratingEmoji = "👍";
} elseif ($percent >= 40) {
    $ratingText = "Average";
    $ratingEmoji = "😐";
} else {
    $ratingText = "Needs Improvement";
    $ratingEmoji = "😔";
}
?>
<!DOCTYPE>
<html lang="en" data-theme="dark">
<head>
    <title>Test Result</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <meta name="description" content="Empowering real estate professionals with world-class training.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="/assets/img/favicon.png">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
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
            user-select: none;
            min-height: 100vh;
        }
        .container {
            width: 70%;
            margin: 120px auto 60px;
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow-card);
        }
        @media (max-width: 768px) {
            .container {
                width: 92%;
                padding: 20px;
            }
        }
        h2 {
            text-align: center;
            color: var(--text-main);
            font-family: 'Playfair Display', serif;
        }
        hr {
            border-color: var(--card-border);
            opacity: .5;
            margin: 34px 0;
        }
        /* ============ RESULT HERO ============ */
        .result-hero {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 36px;
            align-items: center;
            padding: 28px;
            border-radius: 18px;
            background: var(--glass);
            border: 1px solid var(--card-border);
        }
        @media (max-width: 700px) {
            .result-hero {
                grid-template-columns: 1fr;
                justify-items: center;
                text-align: center;
                gap: 20px;
            }
        }
        .score-ring-wrap {
            position: relative;
            width: 168px;
            height: 168px;
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
            stroke-width: 12;
        }
        .score-ring-progress {
            fill: none;
            stroke: url(#ringGradient);
            stroke-width: 12;
            stroke-linecap: round;
            stroke-dasharray: <?php echo $ringCircumference; ?>;
            stroke-dashoffset: <?php echo $ringCircumference; ?>;
            animation: ringFill 1.1s cubic-bezier(.4, 0, .2, 1) 0.15s forwards;
        }
        @keyframes ringFill {
            to {
                stroke-dashoffset: <?php echo $ringOffset; ?>;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .score-ring-progress {
                animation: none;
                stroke-dashoffset: <?php echo $ringOffset; ?>;
            }
        }
        .score-ring-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .score-ring-percent {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1;
        }
        .score-ring-fraction {
            font-size: 13px;
            color: var(--text-main);
            opacity: .6;
            margin-top: 6px;
        }
        .result-summary-text h2 {
            text-align: left;
            margin-bottom: 4px;
            font-size: 26px;
        }
        @media (max-width: 700px) {
            .result-summary-text h2 {
                text-align: center;
            }
        }
        .result-rating {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: var(--gold-primary, #C9933A);
            font-size: 16px;
            margin-bottom: 18px;
        }
        .stat-pills {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        @media (max-width: 480px) {
            .stat-pills {
                grid-template-columns: 1fr;
            }
        }
        .stat-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
        }
        .stat-pill .stat-num {
            font-size: 18px;
            font-weight: 800;
        }
        .stat-pill.correct {
            background: rgba(52, 211, 153, 0.12);
            color: #34d399;
            border: 1px solid rgba(52, 211, 153, 0.25);
        }
        .stat-pill.wrong {
            background: rgba(248, 113, 113, 0.12);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.25);
        }
        .stat-pill.skipped {
            background: rgba(250, 204, 21, 0.12);
            color: #facc15;
            border: 1px solid rgba(250, 204, 21, 0.25);
        }
        /* ============ SECTION HEADER + FILTERS ============ */
        .review-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 20px;
        }
        .review-header h3 {
            margin: 0;
            color: var(--text-main);
        }
        .filter-tabs {
            position: sticky;
            top: 10px;
            z-index: 5;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding: 6px;
            background: var(--glass);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        .filter-tab {
            border: none;
            background: transparent;
            color: var(--text-main);
            opacity: .65;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: all .2s ease;
            white-space: nowrap;
        }
        .filter-tab:hover {
            opacity: 1;
        }
        .filter-tab.active {
            background: linear-gradient(135deg, var(--gold-primary, #C9933A), var(--gold-hover, #b97f2b));
            color: var(--bg-dark);
            opacity: 1;
        }
        /* ============ QUESTION CARDS ============ */
        .question-block {
            background: var(--glass);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }
        .question-block:hover {
            border-color: rgba(201, 147, 58, 0.25);
        }
        /* ✅ Status-aware card border highlight (added — status badges/colors unchanged) */
        .question-block[data-status="correct"] {
            border-color: rgba(52, 211, 153, 0.35);
        }
        .question-block[data-status="wrong"] {
            border-color: rgba(248, 113, 113, 0.35);
        }
        .q-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
        }
        .q-head-left {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .q-eyebrow {
            flex-shrink: 0;
            font-family: 'Playfair Display', serif;
            font-size: 13px;
            font-weight: 700;
            color: var(--gold-primary, #C9933A);
            border: 1px solid rgba(201, 147, 58, 0.35);
            border-radius: 8px;
            padding: 3px 8px;
            margin-top: 2px;
        }
        .question-block p.q-text {
            font-weight: 600;
            margin: 0;
            color: var(--text-main);
            line-height: 1.5;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .badge-correct {
            background: #34d399;
            color: #0a0f1d;
        }
        .badge-wrong {
            background: #f87171;
            color: #0a0f1d;
        }
        .badge-skipped {
            background: #facc15;
            color: #0a0f1d;
        }
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        @media (max-width: 600px) {
            .options-grid {
                grid-template-columns: 1fr;
            }
            .q-head{
                flex-direction: column-reverse;
            }
        }
        .option {
            padding: 9px 12px;
            border-radius: 8px;
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.03);
            font-size: 14px;
        }
        .correct-answer {
            background: rgba(52, 211, 153, 0.15);
            color: #34d399;
            font-weight: bold;
            border: 1px solid rgba(52, 211, 153, 0.3);
        }
        .wrong-answer {
            background: rgba(248, 113, 113, 0.15);
            color: #f87171;
            font-weight: bold;
            border: 1px solid rgba(248, 113, 113, 0.3);
        }
        .not-attempted {
            grid-column: 1 / -1;
            background: rgba(250, 204, 21, 0.15);
            color: #facc15;
            font-style: italic;
            border: 1px solid rgba(250, 204, 21, 0.3);
            border-radius: 8px;
            padding: 9px 12px;
            margin-top: 8px;
            font-size: 14px;
        }
        /* ============ EXPLANATION BOX (NEW) ============ */
        .explanation-box {
            grid-column: 1 / -1;
            margin-top: 12px;
            padding: 12px 14px;
            border-radius: 10px;
            background: rgba(201, 147, 58, 0.06);
            border: 1px solid rgba(201, 147, 58, 0.20);
            font-size: 13.5px;
            line-height: 1.65;
            color: var(--text-main);
        }
        .explanation-box .exp-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            color: var(--gold-primary, #C9933A);
            font-size: 12.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 5px;
        }
        .explanation-box .exp-text {
            opacity: 0.92;
        }
        .explanation-box .exp-text.exp-pending {
            opacity: 0.55;
            font-style: italic;
        }
        .no-results-msg {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-main);
            opacity: .6;
            display: none;
        }
        .back-btn {
            display: block;
            text-align: center;
            margin-top: 30px;
        }
        .back-btn a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-hover));
            color: var(--bg-dark) !important;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            box-shadow: 0 4px 20px rgba(201, 147, 58, 0.30);
            transition: all 0.3s ease;
        }
        .back-btn a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(201, 147, 58, 0.45);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="container submit-test-section">
        <h2>🎉 Aptitude Test Result</h2>
        <?php
        // Presentation-only counters for the stat pills (no scoring logic touched)
        $correctCount = 0;
        $wrongCount = 0;
        $skippedCount = 0;
        foreach ($questions as $qc) {
            $ua = $qc['user_answer'] ?? null;
            if ($ua === null || $ua === '') {
                $skippedCount++;
            } elseif ($ua === $qc['correct_option']) {
                $correctCount++;
            } else {
                $wrongCount++;
            }
        }
        ?>
        <!-- Result Hero -->
        <div class="result-hero">
            <div class="score-ring-wrap">
                <svg viewBox="0 0 168 168">
                    <defs>
                        <linearGradient id="ringGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="var(--gold-primary, #C9933A)" />
                            <stop offset="100%" stop-color="var(--gold-hover, #b97f2b)" />
                        </linearGradient>
                    </defs>
                    <circle class="score-ring-track" cx="84" cy="84" r="<?php echo $ringRadius; ?>" />
                    <circle class="score-ring-progress" cx="84" cy="84" r="<?php echo $ringRadius; ?>" />
                </svg>
                <div class="score-ring-center">
                    <div class="score-ring-percent"><?php echo $percent; ?>%</div>
                    <div class="score-ring-fraction"><?php echo $score; ?> / <?php echo $total; ?></div>
                </div>
            </div>
            <div class="result-summary-text">
                <h2 style="font-size:26px;">Your Score: <?php echo $score; ?> / <?php echo $total; ?></h2>
                <div class="result-rating"><?php echo $ratingEmoji . ' ' . $ratingText; ?></div>
                <div class="stat-pills">
                    <div class="stat-pill correct">
                        <span class="stat-num"><?php echo $correctCount; ?></span> Correct
                    </div>
                    <div class="stat-pill wrong">
                        <span class="stat-num"><?php echo $wrongCount; ?></span> Wrong
                    </div>
                    <div class="stat-pill skipped">
                        <span class="stat-num"><?php echo $skippedCount; ?></span> Skipped
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="review-header">
            <h3>📋 Question Review</h3>
            <div class="filter-tabs" role="tablist" aria-label="Filter questions">
                <button class="filter-tab active" data-filter="all">All (<?php echo $total; ?>)</button>
                <button class="filter-tab" data-filter="correct">Correct (<?php echo $correctCount; ?>)</button>
                <button class="filter-tab" data-filter="wrong">Wrong (<?php echo $wrongCount; ?>)</button>
                <button class="filter-tab" data-filter="skipped">Skipped (<?php echo $skippedCount; ?>)</button>
            </div>
        </div>
        <div id="questionList">
            <?php
            $qNum = 1;
            foreach ($questions as $q):
                $user_ans = $q['user_answer'] ?? null;
                $correct = $q['correct_option'];
                // Options map
                $options = [
                    'A' => $q['option_a'],
                    'B' => $q['option_b'],
                    'C' => $q['option_c'],
                    'D' => $q['option_d'],
                ];
                if ($user_ans === null || $user_ans === '') {
                    $status = 'skipped';
                } elseif ($user_ans === $correct) {
                    $status = 'correct';
                } else {
                    $status = 'wrong';
                }
                // ✅ Explanation — fallback text when NULL/empty (backward compatible)
                $explanationText = trim($q['explanation'] ?? '');
                $hasExplanation = ($explanationText !== '');
                ?>
                <div class="question-block" data-status="<?php echo $status; ?>">
                    <div class="q-head">
                        <div class="q-head-left">
                            <span class="q-eyebrow">Q<?php echo str_pad($qNum, 2, '0', STR_PAD_LEFT); ?></span>
                            <p class="q-text"><?php echo htmlspecialchars($q['question_text']); ?></p>
                        </div>
                        <?php if ($status === 'correct'): ?>
                            <span class="badge badge-correct">✔ Correct</span>
                        <?php elseif ($status === 'wrong'): ?>
                            <span class="badge badge-wrong">✘ Wrong</span>
                        <?php else: ?>
                            <span class="badge badge-skipped">⚠ Skipped</span>
                        <?php endif; ?>
                    </div>
                    <?php $qNum++; ?>
                    <div class="options-grid">
                        <?php foreach ($options as $key => $val): ?>
                            <?php
                            $cls = '';
                            if ($key === $correct && $key === $user_ans) {
                                $cls = 'correct-answer'; // user sahi tha
                            } elseif ($key === $correct) {
                                $cls = 'correct-answer'; // sahi answer highlight
                            } elseif ($key === $user_ans) {
                                $cls = 'wrong-answer';   // user ka galat answer
                            }
                            ?>
                            <div class="option <?php echo $cls; ?>">
                                <b><?php echo $key; ?>.</b> <?php echo htmlspecialchars($val); ?>
                                <?php if ($key === $correct): ?>
                                    <small>(✔ Correct Answer)</small>
                                <?php endif; ?>
                                <?php if ($key === $user_ans && $key !== $correct): ?>
                                    <small>(Your Answer)</small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($status === 'skipped'): ?>
                            <div class="not-attempted">⚠ You did not attempt this question.</div>
                        <?php endif; ?>

                        <!-- ✅ Explanation — always shown, all 3 statuses -->
                        <div class="explanation-box">
                            <div class="exp-label"><i class="bi bi-lightbulb-fill"></i> Explanation</div>
                            <?php if ($hasExplanation): ?>
                                <div class="exp-text"><?php echo nl2br(htmlspecialchars($explanationText)); ?></div>
                            <?php else: ?>
                                <div class="exp-text exp-pending">Explanation will be added soon.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="no-results-msg" id="noResultsMsg">No questions match this filter.</p>
        <div class="back-btn">
            <a href="../auth/after_login.php">🏠 Go to Dashboard</a>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter tabs — pure presentation, does not touch any PHP/session/DB logic
        (function () {
            var tabs = document.querySelectorAll('.filter-tab');
            var blocks = document.querySelectorAll('.question-block');
            var noResults = document.getElementById('noResultsMsg');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    tabs.forEach(function (t) { t.classList.remove('active'); });
                    tab.classList.add('active');
                    var filter = tab.getAttribute('data-filter');
                    var visibleCount = 0;
                    blocks.forEach(function (block) {
                        var match = (filter === 'all') || (block.getAttribute('data-status') === filter);
                        block.style.display = match ? '' : 'none';
                        if (match) visibleCount++;
                    });
                    noResults.style.display = visibleCount === 0 ? 'block' : 'none';
                });
            });
        })();
    </script>
</body>
</html>