<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}
include '../includes/db_connect.php';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($user_id === 0) { header("Location: ../tests/view_result_detail.php"); exit(); }
// User Info
$userQuery = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $userQuery->fetch_assoc();
// Attempt Info
$attemptQuery = $conn->query("
    SELECT * FROM user_attempts 
    WHERE user_id = $user_id 
    ORDER BY submitted_at DESC LIMIT 1
");
$attempt = $attemptQuery->fetch_assoc();
// Set No
$set_no = mysqli_real_escape_string($conn, $attempt['set_no']);
// Answers with Questions (INCLUDING UNATTEMPTED)
$answersQuery = $conn->query("
    SELECT 
        q.id AS question_id,
        q.question_text,
        q.option_a,
        q.option_b,
        q.option_c,
        q.option_d,
        q.correct_option,
        q.explanation,
        ua.answer AS user_answer,
        ua.is_correct
    FROM questions q
    LEFT JOIN user_answers ua 
        ON ua.question_id = q.id AND ua.user_id = $user_id
    WHERE q.set_id = '$set_no'
    ORDER BY q.id ASC
");
// Count correct/wrong/skipped
$total   = $answersQuery->num_rows;
$correct = 0;
$wrong   = 0;
$skipped = 0;
$rows    = [];
while ($r = $answersQuery->fetch_assoc()) {
    if ($r['is_correct']) $correct++;
    elseif (!empty($r['user_answer'])) $wrong++;
    else $skipped++;
    $rows[] = $r;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Result - <?= htmlspecialchars($user['name']) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        .container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        /* Header */
        .header { text-align: center; margin-bottom: 25px; }
        .header h2 { color: #2c3e50; font-size: 22px; }
        .header p { color: #666; margin-top: 6px; font-size: 14px; }
        /* Score Card */
        .score-card {
            display: flex;
            justify-content: space-around;
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .score-item { text-align: center; }
        .score-item h3 { font-size: 26px; color: #f39c12; }
        .score-item p { font-size: 12px; color: #bdc3c7; margin-top: 4px; }
        /* Buttons */
        .btn-row {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 22px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-print { background: #27ae60; color: white; }
        .btn-print:hover { background: #219a52; }
        .btn-back  { background: #2c3e50; color: white; }
        .btn-back:hover  { background: #1a252f; }
        /* Question Card */
        .question-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 16px;
            background: #fafafa;
        }
        .question-number { font-size: 12px; color: #999; margin-bottom: 5px; }
        .question-text { font-size: 15px; font-weight: bold; color: #2c3e50; margin-bottom: 14px; }
        /* Options */
        .options { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .option {
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 14px;
            background: #fff;
        }
        .option.correct {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
            font-weight: bold;
        }
        .option.wrong {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
            font-weight: bold;
        }
        .option.skipped {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
            font-weight: bold;
        }
        /* Status Badge */
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }
        .status.correct  { background: #d4edda; color: #155724; }
        .status.wrong    { background: #f8d7da; color: #721c24; }
        .status.skipped  { background: #fff3cd; color: #856404; }
        /* Explanation Box (NEW) */
        .explanation-box {
            margin-top: 12px;
            padding: 10px 14px;
            border-radius: 6px;
            background: #fffaf0;
            border: 1px solid #fde9c8;
            font-size: 13px;
            line-height: 1.6;
            color: #555;
        }
        .explanation-box .exp-label {
            font-weight: bold;
            color: #b8860b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .explanation-box .exp-text.exp-pending {
            color: #aaa;
            font-style: italic;
        }
        /* Print Styles */
        @media print {
            .btn-row   { display: none; }
            body       { background: white; padding: 0; }
            .container { box-shadow: none; padding: 10px; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h2>📋 Test Result Report</h2>
        <p>
            Student: <strong><?= htmlspecialchars($user['name']) ?></strong> &nbsp;|&nbsp;
            Email: <strong><?= htmlspecialchars($user['email']) ?></strong>
        </p>
        <p>Submitted At: <strong><?= $attempt['submitted_at'] ?></strong></p>
    </div>
    <!-- Score Card -->
    <div class="score-card">
        <div class="score-item">
            <h3><?= $attempt['score'] ?></h3>
            <p>Total Score</p>
        </div>
        <div class="score-item">
            <h3><?= $total ?></h3>
            <p>Total Questions</p>
        </div>
        <div class="score-item">
            <h3 style="color:#2ecc71"><?= $correct ?></h3>
            <p>Correct</p>
        </div>
        <div class="score-item">
            <h3 style="color:#e74c3c"><?= $wrong ?></h3>
            <p>Wrong</p>
        </div>
        <div class="score-item">
            <h3 style="color:#f39c12"><?= $skipped ?></h3>
            <p>Skipped</p>
        </div>
        <div class="score-item">
            <h3><?= $attempt['tab_switches'] ?></h3>
            <p>Tab Switches</p>
        </div>
        <div class="score-item">
            <h3><?= $attempt['set_no'] ?></h3>
            <p>Set No</p>
        </div>
    </div>
    <!-- Buttons -->
    <div class="btn-row">
        <a href="../tests/view_results.php" class="btn btn-back">⬅ Back</a>
        <button class="btn btn-print" onclick="window.print()">🖨️ Print Result</button>
    </div>
    <!-- Questions Loop -->
    <?php foreach ($rows as $index => $row):
        $options = [
            'A' => $row['option_a'],
            'B' => $row['option_b'],
            'C' => $row['option_c'],
            'D' => $row['option_d'],
        ];
        $correct_opt = strtoupper(trim($row['correct_option']));
        $user_ans    = strtoupper(trim($row['user_answer'] ?? ''));
        $is_correct  = $row['is_correct'];
        $is_skipped  = empty($row['user_answer']);
        // ✅ Explanation — fallback text when NULL/empty (backward compatible)
        $explanationText = trim($row['explanation'] ?? '');
        $hasExplanation = ($explanationText !== '');
    ?>
    <div class="question-card">
        <div class="question-number">Question <?= $index + 1 ?></div>
        <div class="question-text"><?= htmlspecialchars($row['question_text']) ?></div>
        <div class="options">
            <?php foreach ($options as $key => $value):
                $class = '';
                if ($key === $correct_opt) {
                    $class = 'correct';
                } elseif ($key === $user_ans && !$is_correct) {
                    $class = 'wrong';
                } elseif ($is_skipped && $key === $correct_opt) {
                    $class = 'skipped';
                }
            ?>
            <div class="option <?= $class ?>">
                <strong><?= $key ?>.</strong> <?= htmlspecialchars($value) ?>
                <?php if ($key === $correct_opt): ?> ✅<?php endif; ?>
                <?php if ($key === $user_ans && !$is_correct && !$is_skipped): ?> ❌<?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($is_correct): ?>
            <span class="status correct">✅ Correct</span>
        <?php elseif ($is_skipped): ?>
            <span class="status skipped">⏭️ Skipped &nbsp;|&nbsp; Correct Answer: Option <?= $correct_opt ?></span>
        <?php else: ?>
            <span class="status wrong">❌ Wrong &nbsp;|&nbsp; Correct Answer: Option <?= $correct_opt ?></span>
        <?php endif; ?>

        <!-- ✅ Explanation — always shown, all 3 statuses -->
        <div class="explanation-box">
            <div class="exp-label">💡 Explanation</div>
            <?php if ($hasExplanation): ?>
                <div class="exp-text"><?= nl2br(htmlspecialchars($explanationText)) ?></div>
            <?php else: ?>
                <div class="exp-text exp-pending">Explanation will be added soon.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>