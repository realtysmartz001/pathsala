<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
  header("Location: ../auth/login.php");
  exit();
}
include '../includes/db_connect.php';
$user_id = intval($_GET['user_id'] ?? 0);
$attempt = intval($_GET['attempt'] ?? 1);
if ($user_id === 0) {
  die("❌ Error: user_id missing from URL!");
}
// 🔹 Get user info
$user_query = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $user_query->fetch_assoc();
// 🔹 Get attempt result
$result_query = $conn->query("
    SELECT * FROM user_attempts 
    WHERE user_id = $user_id AND attempt_no = $attempt
");
$result = $result_query->fetch_assoc();
$set_no = $result['set_no'] ?? '';
// 🔹 Get answers
$answers_query = $conn->query("
    SELECT 
        q.id          AS question_id,
        q.question_text,
        q.option_a,
        q.option_b,
        q.option_c,
        q.option_d,
        q.correct_option,
        q.explanation,
        ua.answer,
        ua.is_correct
    FROM questions q
    LEFT JOIN user_answers ua 
        ON  ua.question_id = q.id 
        AND ua.user_id     = $user_id 
        AND ua.attempt_no  = $attempt
    WHERE q.set_id = '$set_no'
    ORDER BY q.id ASC
");
if ($answers_query === false) {
  die("❌ Query Error: " . $conn->error);
}
// Pre-fetch all rows & compute summary
$rows = [];
$correct_count = 0;
$wrong_count = 0;
$skipped_count = 0;
if ($answers_query->num_rows > 0) {
  $rows = $answers_query->fetch_all(MYSQLI_ASSOC);
  foreach ($rows as $r) {
    if (is_null($r['answer']) || $r['answer'] === '') {
      $skipped_count++;
    } elseif ($r['is_correct']) {
      $correct_count++;
    } else {
      $wrong_count++;
    }
  }
}
$total = count($rows);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Result Detail — <?= htmlspecialchars($user['name'] ?? 'User') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f6f9;
      color: #333;
      padding: 24px 20px;
    }

    /* ══ TOP ACTION BAR ══ */
    .action-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 20px;
    }

    .action-bar-left {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 9px 16px;
      border-radius: 9px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      border: none;
      transition: all 0.2s;
      font-family: 'Segoe UI', sans-serif;
    }

    .btn-back {
      background: #f1f3f5;
      color: #555;
      border: 1.5px solid #e0e0e0;
    }

    .btn-back:hover {
      background: #e2e6ea;
      color: #333;
    }

    .btn-edit {
      background: rgba(142, 68, 173, 0.08);
      color: #8e44ad;
      border: 1.5px solid rgba(142, 68, 173, 0.25);
    }

    .btn-edit:hover {
      background: #8e44ad;
      color: #fff;
    }

    .btn-print {
      background: rgba(220, 53, 69, 0.08);
      color: #dc3545;
      border: 1.5px solid rgba(220, 53, 69, 0.25);
    }

    .btn-print:hover {
      background: #dc3545;
      color: #fff;
    }

    /* ══ PAGE HEADER ══ */
    .page-header {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 20px;
    }

    .header-icon {
      width: 46px;
      height: 46px;
      background: rgba(220, 53, 69, 0.1);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #dc3545;
      font-size: 22px;
      flex-shrink: 0;
    }

    .page-header h2 {
      font-size: 20px;
      font-weight: 700;
      color: #1a1a1a;
    }

    .page-header p {
      font-size: 13px;
      color: #888;
      margin-top: 2px;
    }

    /* ══ INFO CARD ══ */
    .info-card {
      background: #fff;
      border: 1px solid #e0e0e0;
      border-radius: 14px;
      padding: 18px 22px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      display: flex;
      flex-wrap: wrap;
      gap: 14px 28px;
    }

    .info-item {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 13px;
      color: #555;
    }

    .info-item i {
      color: #dc3545;
      font-size: 15px;
    }

    .info-item strong {
      color: #222;
    }

    /* ══ SUMMARY CARDS ══ */
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-bottom: 20px;
    }

    .summary-card {
      background: #fff;
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 16px 18px;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    }

    .summary-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }

    .icon-total {
      background: rgba(44, 62, 80, 0.08);
      color: #2c3e50;
    }

    .icon-correct {
      background: rgba(21, 163, 74, 0.10);
      color: #15a34a;
    }

    .icon-wrong {
      background: rgba(220, 53, 69, 0.10);
      color: #dc3545;
    }

    .icon-skipped {
      background: rgba(202, 138, 4, 0.10);
      color: #ca8a04;
    }

    .summary-info p {
      font-size: 11px;
      color: #999;
      text-transform: uppercase;
      letter-spacing: 0.7px;
      font-weight: 600;
    }

    .summary-info h3 {
      font-size: 22px;
      font-weight: 800;
      color: #1a1a1a;
      line-height: 1.2;
    }

    /* ══ TABLE CARD ══ */
    .table-card {
      background: #fff;
      border: 1px solid #e0e0e0;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }

    .data-table thead tr {
      background: #f8f9fa;
      border-bottom: 2px solid #e0e0e0;
    }

    .data-table thead th {
      padding: 12px 14px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: #888;
      white-space: nowrap;
    }

    .data-table tbody tr {
      border-bottom: 1px solid #f0f0f0;
      transition: opacity 0.15s;
    }

    .data-table tbody tr:last-child {
      border-bottom: none;
    }

    .data-table tbody td {
      padding: 12px 14px;
      vertical-align: middle;
    }

    /* Row Colors */
    .correct-row {
      background: #f0fdf4 !important;
    }

    .wrong-row {
      background: #fff5f5 !important;
    }

    .skipped-row {
      background: #fffbeb !important;
    }

    /* Q# cell */
    .q-num {
      font-weight: 700;
      color: #dc3545;
      font-size: 13px;
      white-space: nowrap;
    }

    /* Options */
    .option-list {
      list-style: none;
    }

    .option-list li {
      padding: 2px 0;
      font-size: 12px;
      color: #555;
    }

    .option-list li strong {
      color: #333;
    }

    /* Answer cells */
    .ans-correct {
      color: #15a34a;
      font-weight: 700;
    }

    .ans-wrong {
      color: #dc3545;
      font-weight: 700;
    }

    .ans-skipped {
      color: #ca8a04;
      font-weight: 700;
    }

    /* Status Badges */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      white-space: nowrap;
    }

    .badge-correct {
      background: #dcfce7;
      color: #15a34a;
      border: 1px solid #bbf7d0;
    }

    .badge-wrong {
      background: #fee2e2;
      color: #dc2626;
      border: 1px solid #fecaca;
    }

    .badge-skipped {
      background: #fef9c3;
      color: #ca8a04;
      border: 1px solid #fde68a;
    }

    /* ══ EXPLANATION SUB-ROW (NEW) ══ */
    .explanation-row td {
      padding: 10px 14px 16px 14px !important;
      border-bottom: 1px solid #f0f0f0;
    }

    .explanation-box {
      background: #fffaf0;
      border: 1px solid #fde9c8;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 12.5px;
      line-height: 1.6;
      color: #555;
    }

    .explanation-box .exp-label {
      display: flex;
      align-items: center;
      gap: 6px;
      font-weight: 700;
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

    /* ══ EMPTY STATE ══ */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
    }

    .empty-state i {
      font-size: 48px;
      color: #ddd;
      display: block;
      margin-bottom: 14px;
    }

    .empty-state p {
      color: #aaa;
      font-size: 15px;
      font-weight: 500;
    }

    .empty-state small {
      display: block;
      color: #ccc;
      font-size: 13px;
      margin-top: 6px;
    }

    .empty-state-btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      margin-top: 22px;
      padding: 11px 24px;
      background: rgba(220, 53, 69, 0.08);
      color: #dc3545;
      border: 1.5px solid rgba(220, 53, 69, 0.25);
      border-radius: 9px;
      font-size: 13.5px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
    }

    .empty-state-btn:hover {
      background: #dc3545;
      color: #fff;
    }

    /* ══ PRINT STYLES ══ */
    @media print {
      body {
        background: #fff;
        padding: 0;
      }

      .action-bar,
      .no-print {
        display: none !important;
      }

      .info-card,
      .summary-grid,
      .table-card {
        box-shadow: none !important;
        border: 1px solid #ccc !important;
      }

      .summary-grid {
        grid-template-columns: repeat(4, 1fr);
      }

      .data-table thead tr {
        background: #eee !important;
      }

      .correct-row {
        background: #f0fdf4 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .wrong-row {
        background: #fff5f5 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .skipped-row {
        background: #fffbeb !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .badge-correct {
        background: #dcfce7 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .badge-wrong {
        background: #fee2e2 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .badge-skipped {
        background: #fef9c3 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .page-header h2 {
        font-size: 18px;
      }

      .data-table {
        font-size: 12px;
      }
    }

    /* ══ RESPONSIVE ══ */
    @media (max-width: 768px) {
      .summary-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      body {
        padding: 14px 10px;
      }
    }

    @media (max-width: 480px) {
      .summary-grid {
        grid-template-columns: 1fr 1fr;
      }
    }
  </style>
</head>

<body>
  <!-- ══ ACTION BAR ══ -->
  <div class="action-bar no-print">
    <div class="action-bar-left">
      <a href="../tests/view_results.php" class="btn btn-back">
        <i class="bi bi-arrow-left"></i> Back to Results
      </a>
      <a href="../profile/edit_profile.php?id=<?= $user_id ?>" class="btn btn-edit">
        <i class="bi bi-pencil-square"></i> Edit Profile
      </a>
    </div>
    <button onclick="window.print()" class="btn btn-print">
      <i class="bi bi-printer"></i> Print Result
    </button>
    <a href="../admin/export_result_detail_pdf.php?user_id=<?= $user_id ?>&attempt=<?= $attempt ?>"
      class="btn btn-print" style="background:rgba(198,40,40,0.10); color:#c62828; border-color:rgba(198,40,40,0.25);">
      <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
    </a>
  </div>
  <!-- ══ PAGE HEADER ══ -->
  <div class="page-header">
    <div class="header-icon">
      <i class="bi bi-clipboard2-data"></i>
    </div>
    <div>
      <h2>Result Detail — <?= htmlspecialchars($user['name'] ?? 'User') ?></h2>
      <p>Attempt #<?= $attempt ?> &nbsp;•&nbsp; Set: <?= htmlspecialchars($set_no) ?></p>
    </div>
  </div>
  <!-- ══ INFO CARD ══ -->
  <div class="info-card">
    <div class="info-item">
      <i class="bi bi-person-fill"></i>
      <span><strong>Name:</strong> <?= htmlspecialchars($user['name'] ?? 'N/A') ?></span>
    </div>
    <div class="info-item">
      <i class="bi bi-envelope-fill"></i>
      <span><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? 'N/A') ?></span>
    </div>
    <div class="info-item">
      <i class="bi bi-arrow-repeat"></i>
      <span><strong>Attempt No:</strong> <?= $attempt ?></span>
    </div>
    <div class="info-item">
      <i class="bi bi-trophy-fill"></i>
      <span><strong>Score:</strong> <?= $result['score'] ?? 'N/A' ?></span>
    </div>
    <div class="info-item">
      <i class="bi bi-collection-fill"></i>
      <span><strong>Set:</strong> <?= htmlspecialchars($set_no) ?></span>
    </div>
    <div class="info-item">
      <i class="bi bi-calendar-event-fill"></i>
      <span><strong>Submitted:</strong> <?= $result['submitted_at'] ?? 'N/A' ?></span>
    </div>
    <div class="info-item">
      <i class="bi bi-display"></i>
      <span><strong>Tab Switches:</strong> <?= $result['tab_switches'] ?? '0' ?></span>
    </div>
  </div>
  <!-- ══ SUMMARY CARDS ══ -->
  <div class="summary-grid">
    <div class="summary-card">
      <div class="summary-icon icon-total">
        <i class="bi bi-list-ol"></i>
      </div>
      <div class="summary-info">
        <p>Total</p>
        <h3><?= $total ?></h3>
      </div>
    </div>
    <div class="summary-card">
      <div class="summary-icon icon-correct">
        <i class="bi bi-check-circle-fill"></i>
      </div>
      <div class="summary-info">
        <p>Correct</p>
        <h3><?= $correct_count ?></h3>
      </div>
    </div>
    <div class="summary-card">
      <div class="summary-icon icon-wrong">
        <i class="bi bi-x-circle-fill"></i>
      </div>
      <div class="summary-info">
        <p>Wrong</p>
        <h3><?= $wrong_count ?></h3>
      </div>
    </div>
    <div class="summary-card">
      <div class="summary-icon icon-skipped">
        <i class="bi bi-skip-forward-fill"></i>
      </div>
      <div class="summary-info">
        <p>Skipped</p>
        <h3><?= $skipped_count ?></h3>
      </div>
    </div>
  </div>
  <!-- ══ TABLE CARD ══ -->
  <div class="table-card">
    <?php if ($total === 0): ?>
      <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <p>No questions found for Set ID: <strong><?= htmlspecialchars($set_no) ?></strong></p>
        <small>This set may not have any questions uploaded yet, or the set ID may have changed.</small>
        <a href="../tests/view_results.php" class="empty-state-btn">
          <i class="bi bi-arrow-left"></i> Back to All Results
        </a>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Question</th>
            <th>Options</th>
            <th>Your Answer</th>
            <th>Correct Answer</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1;
          foreach ($rows as $row):
            $user_ans = strtoupper(trim($row['answer'] ?? ''));
            $correct_ans = strtoupper(trim($row['correct_option'] ?? ''));
            $is_skipped = ($user_ans === '' || is_null($row['answer']));
            $is_correct = !$is_skipped && $row['is_correct'];
            $options = [
              'A' => $row['option_a'],
              'B' => $row['option_b'],
              'C' => $row['option_c'],
              'D' => $row['option_d'],
            ];
            $user_ans_text = $options[$user_ans] ?? '—';
            $correct_ans_text = $options[$correct_ans] ?? $correct_ans;
            if ($is_skipped) {
              $row_class = 'skipped-row';
              $ans_class = 'ans-skipped';
            } elseif ($is_correct) {
              $row_class = 'correct-row';
              $ans_class = 'ans-correct';
            } else {
              $row_class = 'wrong-row';
              $ans_class = 'ans-wrong';
            }
            // ✅ Explanation — fallback text when NULL/empty (backward compatible)
            $explanationText = trim($row['explanation'] ?? '');
            $hasExplanation = ($explanationText !== '');
            ?>
            <tr class="<?= $row_class ?>">
              <td class="q-num"><?= $i++ ?></td>
              <td><?= htmlspecialchars($row['question_text']) ?></td>
              <td>
                <ul class="option-list">
                  <li><strong>A:</strong> <?= htmlspecialchars($row['option_a']) ?></li>
                  <li><strong>B:</strong> <?= htmlspecialchars($row['option_b']) ?></li>
                  <li><strong>C:</strong> <?= htmlspecialchars($row['option_c']) ?></li>
                  <li><strong>D:</strong> <?= htmlspecialchars($row['option_d']) ?></li>
                </ul>
              </td>
              <td class="<?= $ans_class ?>">
                <?php if ($is_skipped): ?>
                  <i class="bi bi-dash-circle"></i> Not Attempted
                <?php else: ?>
                  <?= $user_ans ?> — <?= htmlspecialchars($user_ans_text) ?>
                <?php endif; ?>
              </td>
              <td class="ans-correct">
                <?= $correct_ans ?> — <?= htmlspecialchars($correct_ans_text) ?>
              </td>
              <td>
                <?php if ($is_skipped): ?>
                  <span class="badge badge-skipped"><i class="bi bi-skip-forward-fill"></i> Skipped</span>
                <?php elseif ($is_correct): ?>
                  <span class="badge badge-correct"><i class="bi bi-check-circle-fill"></i> Correct</span>
                <?php else: ?>
                  <span class="badge badge-wrong"><i class="bi bi-x-circle-fill"></i> Wrong</span>
                <?php endif; ?>
              </td>
            </tr>
            <!-- ✅ Explanation sub-row — spans all columns, shown for every question -->
            <tr class="explanation-row <?= $row_class ?>">
              <td colspan="6">
                <div class="explanation-box">
                  <div class="exp-label"><i class="bi bi-lightbulb-fill"></i> Explanation</div>
                  <?php if ($hasExplanation): ?>
                    <div class="exp-text"><?= nl2br(htmlspecialchars($explanationText)) ?></div>
                  <?php else: ?>
                    <div class="exp-text exp-pending">Explanation will be added soon.</div>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>

</html>