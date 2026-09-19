<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = intval($_GET['user_id'] ?? 0);
$attempt = intval($_GET['attempt'] ?? 1);
if ($user_id === 0) {
    die("Error: user_id missing from URL!");
}
$user_query = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user = $user_query->fetch_assoc();

$result_query = $conn->query("
    SELECT * FROM user_attempts 
    WHERE user_id = $user_id AND attempt_no = $attempt
");
$result = $result_query->fetch_assoc();
$set_no = $result['set_no'] ?? '';

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
    die("Query Error: " . $conn->error);
}

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

function pdf_esc($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

$generatedOn = date('d M Y, h:i A');
$exportedBy = htmlspecialchars_decode($_SESSION['admin_name'] ?? 'Admin');

$css = '
    @page { margin: 100px 30px 60px 30px; }
    body { font-family: "DejaVu Sans", sans-serif; color: #222; font-size: 10px; }
    .header {
        position: fixed; top: -80px; left: 0px; right: 0px; height: 78px;
        text-align: center; border-bottom: 2px solid #C9933A; padding-bottom: 8px;
    }
    .header .company { font-size: 20px; font-weight: bold; color: #C9933A; }
    .header .title { font-size: 13px; font-weight: bold; color: #1A1A2E; margin-top: 2px; }
    .header .sub { font-size: 9.5px; color: #888; margin-top: 4px; }
    .footer {
        position: fixed; bottom: -50px; left: 0px; right: 0px; height: 44px;
        border-top: 1px solid #ddd; padding-top: 6px; font-size: 9px; color: #777;
    }
    .footer table { width: 100%; }
    .footer td { padding: 1px 0; }
    .page-number:before { content: counter(page); }
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .info-table td {
        padding: 5px 8px; border: 1px solid #eee; font-size: 9.5px;
    }
    .info-table td.label { font-weight: bold; width: 110px; background: #fafafa; }
    .summary-row table { width: 100%; margin-bottom: 14px; }
    .summary-row td {
        background: #fafafa; border: 1px solid #eee; padding: 8px 6px;
        text-align: center; width: 25%;
    }
    .summary-num { font-size: 15px; font-weight: bold; }
    .summary-lbl { font-size: 8.5px; color: #888; text-transform: uppercase; margin-top: 2px; }
    .num-total { color: #1A1A2E; }
    .num-correct { color: #15A34A; }
    .num-wrong { color: #DC3545; }
    .num-skipped { color: #CA8A04; }
    table.q-table { width: 100%; border-collapse: collapse; }
    table.q-table th {
        background: #C9933A; color: #fff; padding: 5px 6px; font-size: 8.5px;
        text-align: left; border: 1px solid #C9933A;
    }
    table.q-table td {
        padding: 5px 6px; font-size: 8.5px; border: 1px solid #eee; vertical-align: top;
    }
    .q-num { font-weight: bold; color: #C9933A; }
    .row-correct td { background: #f0fdf4; }
    .row-wrong td { background: #fff5f5; }
    .row-skipped td { background: #fffbeb; }
    .ans-correct { color: #15A34A; font-weight: bold; }
    .ans-wrong { color: #DC3545; font-weight: bold; }
    .ans-skipped { color: #CA8A04; font-weight: bold; }
    .exp-row td {
        background: #fffaf0; border: 1px solid #fde9c8; font-size: 8.5px;
        color: #555; padding: 6px 8px;
    }
    .exp-label { font-weight: bold; color: #b8860b; font-size: 7.5px; text-transform: uppercase; }
    .exp-pending { color: #aaa; font-style: italic; }
';

$bodyHtml = '';
$bodyHtml .= '<div class="header">';
$bodyHtml .= '<div class="company">Realty Smartz Pathshala</div>';
$bodyHtml .= '<div class="title">TEST RESULT DETAIL</div>';
$bodyHtml .= '<div class="sub">' . pdf_esc($user['name'] ?? 'User') . ' &nbsp;|&nbsp; Attempt #' . $attempt . ' &nbsp;|&nbsp; Set: ' . pdf_esc($set_no) . '</div>';
$bodyHtml .= '</div>';

$bodyHtml .= '<div class="footer"><table><tr>';
$bodyHtml .= '<td style="text-align:left;">Generated By: Realty Smartz Pathshala Admin &nbsp;|&nbsp; Exported By: ' . pdf_esc($exportedBy) . '</td>';
$bodyHtml .= '<td style="text-align:right;">Confidential &nbsp;|&nbsp; Page <span class="page-number"></span></td>';
$bodyHtml .= '</tr></table></div>';

$bodyHtml .= '<table class="info-table">';
$bodyHtml .= '<tr><td class="label">Name</td><td>' . pdf_esc($user['name'] ?? 'N/A') . '</td><td class="label">Email</td><td>' . pdf_esc($user['email'] ?? 'N/A') . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Attempt No</td><td>' . $attempt . '</td><td class="label">Score</td><td>' . pdf_esc($result['score'] ?? 'N/A') . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Set</td><td>' . pdf_esc($set_no) . '</td><td class="label">Submitted</td><td>' . pdf_esc($result['submitted_at'] ?? 'N/A') . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Tab Switches</td><td>' . pdf_esc($result['tab_switches'] ?? '0') . '</td><td class="label">Generated On</td><td>' . pdf_esc($generatedOn) . '</td></tr>';
$bodyHtml .= '</table>';

$bodyHtml .= '<div class="summary-row"><table><tr>';
$bodyHtml .= '<td><div class="summary-num num-total">' . $total . '</div><div class="summary-lbl">Total</div></td>';
$bodyHtml .= '<td><div class="summary-num num-correct">' . $correct_count . '</div><div class="summary-lbl">Correct</div></td>';
$bodyHtml .= '<td><div class="summary-num num-wrong">' . $wrong_count . '</div><div class="summary-lbl">Wrong</div></td>';
$bodyHtml .= '<td><div class="summary-num num-skipped">' . $skipped_count . '</div><div class="summary-lbl">Skipped</div></td>';
$bodyHtml .= '</tr></table></div>';

if ($total === 0) {
    $bodyHtml .= '<p style="text-align:center; color:#999; padding:20px;">No questions found for Set ID: ' . pdf_esc($set_no) . '</p>';
} else {
    $bodyHtml .= '<table class="q-table">';
    $bodyHtml .= '<thead><tr><th>#</th><th>Question</th><th>Options</th><th>Your Answer</th><th>Correct Answer</th><th>Status</th></tr></thead><tbody>';

    $i = 1;
    foreach ($rows as $row) {
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
            $rowClass = 'row-skipped';
            $ansClass = 'ans-skipped';
            $statusLabel = 'Skipped';
        } elseif ($is_correct) {
            $rowClass = 'row-correct';
            $ansClass = 'ans-correct';
            $statusLabel = 'Correct';
        } else {
            $rowClass = 'row-wrong';
            $ansClass = 'ans-wrong';
            $statusLabel = 'Wrong';
        }

        $optionsHtml = 'A: ' . pdf_esc($row['option_a']) . '<br>B: ' . pdf_esc($row['option_b']) . '<br>C: ' . pdf_esc($row['option_c']) . '<br>D: ' . pdf_esc($row['option_d']);
        $yourAnsHtml = $is_skipped ? 'Not Attempted' : ($user_ans . ' — ' . pdf_esc($user_ans_text));

        $bodyHtml .= '<tr class="' . $rowClass . '">';
        $bodyHtml .= '<td class="q-num">' . $i++ . '</td>';
        $bodyHtml .= '<td>' . pdf_esc($row['question_text']) . '</td>';
        $bodyHtml .= '<td>' . $optionsHtml . '</td>';
        $bodyHtml .= '<td class="' . $ansClass . '">' . $yourAnsHtml . '</td>';
        $bodyHtml .= '<td class="ans-correct">' . $correct_ans . ' — ' . pdf_esc($correct_ans_text) . '</td>';
        $bodyHtml .= '<td>' . $statusLabel . '</td>';
        $bodyHtml .= '</tr>';

        $explanationText = trim($row['explanation'] ?? '');
        $hasExplanation = ($explanationText !== '');
        $bodyHtml .= '<tr><td colspan="6" class="exp-row">';
        $bodyHtml .= '<div class="exp-label">Explanation</div>';
        if ($hasExplanation) {
            $bodyHtml .= '<div>' . nl2br(pdf_esc($explanationText)) . '</div>';
        } else {
            $bodyHtml .= '<div class="exp-pending">Explanation will be added soon.</div>';
        }
        $bodyHtml .= '</td></tr>';
    }

    $bodyHtml .= '</tbody></table>';
}

$fullHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>' . $bodyHtml . '</body></html>';

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($fullHtml);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $user['name'] ?? ('User_' . $user_id));
$filename = $safeName . '_Attempt' . $attempt . '_Result.pdf';
while (ob_get_level()) {
    ob_end_clean();
}
$dompdf->stream($filename, array('Attachment' => true));
exit();