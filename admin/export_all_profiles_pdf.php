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

$result = $conn->query("SELECT * FROM user_profiles ORDER BY created_at DESC, user_id DESC");
$allRows = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

$totalCount = count($allRows);
$fresherCount = 0;
$expCount = 0;
foreach ($allRows as $r) {
    $e = strtolower(trim($r['experience'] ?? ''));
    if ($e === 'fresher') {
        $fresherCount++;
    } elseif ($e === 'experienced') {
        $expCount++;
    }
}

// ── Helpers (mirrors export_profile_pdf.php) ──
function bulk_filePathAbs($val)
{
    if (empty($val)) {
        return null;
    }
    $clean = basename(str_replace('uploads/', '', $val));
    $real = realpath(__DIR__ . '/../profile/uploads/' . $clean);
    if ($real) {
        return $real;
    }
    return null;
}

function bulk_filePathUrl($val)
{
    if (empty($val)) {
        return null;
    }
    $clean = basename(str_replace('uploads/', '', $val));
    $scheme = 'http://';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https://';
    }
    $host = $_SERVER['HTTP_HOST'];
    $base = rtrim(str_replace('/admin', '', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $scheme . $host . $base . '/profile/uploads/' . $clean;
}

function bulk_eduLabel($val)
{
    $map = array(
        '10th' => '10th',
        '12th' => '12th',
        'graduation' => 'Graduation',
        'post_graduation' => 'Post Grad',
        'diploma' => 'Diploma'
    );
    if (isset($map[$val])) {
        return $map[$val];
    }
    return ucfirst($val ?? '');
}

function bulk_esc($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function bulk_documentsTableHtml($documents)
{
    $html = '';
    foreach ($documents as $groupName => $docs) {
        $html .= '<div class="doc-group-title">' . bulk_esc($groupName) . '</div>';
        $html .= '<table class="doc-table"><thead><tr><th>Document Name</th><th>Status</th><th>Link</th></tr></thead><tbody>';
        foreach ($docs as $docItem) {
            $docName = $docItem[0];
            $docVal = $docItem[1];
            $absPath = bulk_filePathAbs($docVal);
            $absExists = ($absPath !== null && file_exists($absPath));
            $html .= '<tr><td>' . bulk_esc($docName) . '</td>';
            if (!empty($docVal) && $absExists) {
                $url = bulk_filePathUrl($docVal);
                $html .= '<td class="status-uploaded">Uploaded</td>';
                $html .= '<td><a href="' . bulk_esc($url) . '">View Document</a></td>';
            } else {
                $html .= '<td class="status-not-uploaded">Not Uploaded</td><td>&mdash;</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    }
    return $html;
}

function bulk_contactCardHtml($title, $relationVal, $name, $phone, $address, $colorClass)
{
    $html = '<table class="contact-card ' . $colorClass . '">';
    $html .= '<tr><th colspan="2">' . bulk_esc($title) . '</th></tr>';
    $html .= '<tr><td class="label">Relation</td><td>' . bulk_esc($relationVal !== '' ? $relationVal : '—') . '</td></tr>';
    $html .= '<tr><td class="label">Name</td><td>' . bulk_esc($name !== '' ? $name : '—') . '</td></tr>';
    $html .= '<tr><td class="label">Phone</td><td>' . bulk_esc($phone !== '' ? $phone : '—') . '</td></tr>';
    $html .= '<tr><td class="label">Address</td><td>' . nl2br(bulk_esc($address !== '' ? $address : '—')) . '</td></tr>';
    $html .= '</table>';
    return $html;
}

function bulk_socialProfilesHtml($socialData)
{
    if (empty($socialData)) {
        return '<p class="muted">No Social Profiles Available</p>';
    }
    $html = '<table class="doc-table"><thead><tr><th>Platform</th><th>Profile Link</th></tr></thead><tbody>';
    foreach ($socialData as $s) {
        $html .= '<tr><td>' . bulk_esc($s['platform']) . '</td><td><a href="' . bulk_esc($s['url']) . '">' . bulk_esc($s['url']) . '</a></td></tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}

/* ── Builds one full user page's HTML (mirrors export_profile_pdf.php's report body) ── */
function bulk_buildUserPageHtml($row, $exportedBy, $generatedOn, $isFirst)
{
    $exp = strtolower(trim($row['experience'] ?? ''));
    if ($exp === 'fresher') {
        $expLabel = 'Fresher';
    } elseif ($exp === 'experienced') {
        $expLabel = 'Experienced';
    } else {
        $expLabel = '—';
    }
    $eduLabelVal = bulk_eduLabel($row['education']);

    $relation = $row['contact_relation'] ?? '';
    if ($relation === 'father') {
        $ref1Relation = 'Father';
        $ref1Name = $row['father_name'] ?? '';
        $ref1Phone = $row['father_contact'] ?? '';
        $ref1Address = $row['father_address'] ?? '';
    } elseif ($relation === 'mother') {
        $ref1Relation = 'Mother';
        $ref1Name = $row['mother_name'] ?? '';
        $ref1Phone = $row['mother_contact'] ?? '';
        $ref1Address = $row['mother_address'] ?? '';
    } else {
        $ref1Relation = ucfirst($relation);
        $ref1Name = '';
        $ref1Phone = '';
        $ref1Address = '';
    }
    $ref2Relation = ucfirst($row['other_relation'] ?? '');
    $ref2Name = $row['other_name'] ?? '';
    $ref2Phone = $row['other_contact'] ?? '';
    $ref2Address = $row['other_address'] ?? '';

    $socialData = array();
    $p1 = trim($row['social_platform_1'] ?? '');
    $u1 = trim($row['social_url_1'] ?? '');
    if (!empty($p1) && !empty($u1)) {
        $socialData[] = array('platform' => ucfirst($p1), 'url' => $u1);
    }
    $u2 = trim($row['social_url_2'] ?? '');
    if (!empty($u2)) {
        $socialData[] = array('platform' => 'LinkedIn', 'url' => $u2);
    }
    $extraPlatforms = json_decode($row['social_platform_extra'] ?? '[]', true);
    if (!is_array($extraPlatforms)) {
        $extraPlatforms = array();
    }
    $extraUrls = json_decode($row['social_url_extra'] ?? '[]', true);
    if (!is_array($extraUrls)) {
        $extraUrls = array();
    }
    foreach ($extraPlatforms as $idx => $ep) {
        $eu = isset($extraUrls[$idx]) ? $extraUrls[$idx] : '';
        if (!empty($ep) && !empty($eu)) {
            $socialData[] = array('platform' => ucfirst($ep), 'url' => $eu);
        }
    }

    $documents = array(
        'Identity Documents' => array(
            array('Aadhaar Card', $row['aadhar_doc']),
            array('PAN Card', $row['pan_doc']),
            array('Cancelled Cheque', $row['cheque_doc']),
            array('Passbook', $row['passbook_doc'])
        ),
        'Employment Documents' => array(
            array('Offer Letter', $row['offer_letter_doc']),
            array('Relieving Letter', $row['relieving_letter_doc']),
            array('Salary Slip', $row['salary_slip_doc']),
            array('Rehire Mail', $row['up_rehire_mail_doc'])
        ),
        'Education Documents' => array(
            array('Marksheet', $row['marksheet_doc'])
        )
    );

    $photoTag = '';
    if (!empty($row['profile_photo'])) {
        $photoAbs = bulk_filePathAbs($row['profile_photo']);
        if ($photoAbs && file_exists($photoAbs)) {
            $imgData = base64_encode(file_get_contents($photoAbs));
            $ext = strtolower(pathinfo($photoAbs, PATHINFO_EXTENSION));
            if ($ext === 'png') {
                $mime = 'image/png';
            } else {
                $mime = 'image/jpeg';
            }
            $photoTag = '<img src="data:' . $mime . ';base64,' . $imgData . '" class="profile-photo">';
        }
    }
    if ($photoTag === '') {
        $nameForInitial = trim($row['user_name'] ?? 'U');
        if ($nameForInitial === '') {
            $nameForInitial = 'U';
        }
        $initials = strtoupper(substr($nameForInitial, 0, 1));
        $photoTag = '<div class="profile-photo-placeholder">' . bulk_esc($initials) . '</div>';
    }

    $userIdLabel = '#' . intval($row['user_id']);
    $joinedDateDisplay = '—';
    if (!empty($row['created_at'])) {
        $joinedDateDisplay = date('d M Y', strtotime($row['created_at']));
    }

    $pageClass = $isFirst ? 'user-page' : 'user-page page-break';

    $html = '<div class="' . $pageClass . '">';

    $html .= '<div class="hero">';
    $html .= $photoTag;
    $html .= '<div class="hero-name">' . bulk_esc($row['user_name']) . '</div>';
    $html .= '<div class="hero-sub">' . bulk_esc($expLabel) . ' &nbsp;&bull;&nbsp; ' . bulk_esc($eduLabelVal) . ' &nbsp;&bull;&nbsp; ' . bulk_esc($row['email']) . ' &nbsp;&bull;&nbsp; ' . bulk_esc($row['contact_no']) . ' &nbsp;&bull;&nbsp; ' . bulk_esc($userIdLabel) . '</div>';
    $html .= '</div>';

    $html .= '<div class="section-title">1. PERSONAL INFORMATION</div>';
    $html .= '<table class="info-table">';
    $html .= '<tr><td class="label">Full Name</td><td>' . bulk_esc($row['user_name']) . '</td></tr>';
    $html .= '<tr><td class="label">Email</td><td>' . bulk_esc($row['email']) . '</td></tr>';
    $html .= '<tr><td class="label">Category</td><td>' . bulk_esc($expLabel) . '</td></tr>';
    $html .= '<tr><td class="label">Qualification</td><td>' . bulk_esc($eduLabelVal) . '</td></tr>';
    $html .= '<tr><td class="label">Contact Number</td><td>' . bulk_esc($row['contact_no']) . '</td></tr>';
    $html .= '<tr><td class="label">Date of Birth</td><td>' . bulk_esc($row['dob']) . '</td></tr>';
    $html .= '<tr><td class="label">Joined Date</td><td>' . bulk_esc($joinedDateDisplay) . '</td></tr>';
    $html .= '<tr><td class="label">User ID</td><td>' . bulk_esc($userIdLabel) . '</td></tr>';
    $html .= '</table>';

    $html .= '<div class="section-title">2. ADDRESS DETAILS</div>';
    $html .= '<table class="info-table">';
    $html .= '<tr><td class="label">Current Address</td><td>' . nl2br(bulk_esc($row['current_address'] ?: '—')) . '</td></tr>';
    $html .= '<tr><td class="label">Permanent Address</td><td>' . nl2br(bulk_esc($row['permanent_address'] ?: '—')) . '</td></tr>';
    $html .= '</table>';

    $html .= '<div class="section-title">3. DOCUMENTS</div>';
    $html .= bulk_documentsTableHtml($documents);

    $html .= '<div class="section-title">4. EMERGENCY CONTACTS</div>';
    $html .= bulk_contactCardHtml('Primary Contact', $ref1Relation, $ref1Name, $ref1Phone, $ref1Address, 'primary');
    $html .= bulk_contactCardHtml('Secondary Contact', $ref2Relation, $ref2Name, $ref2Phone, $ref2Address, 'secondary');

    $html .= '<div class="section-title">5. SOCIAL PROFILES</div>';
    $html .= bulk_socialProfilesHtml($socialData);

    $html .= '<div class="section-title">6. REPORT INFORMATION</div>';
    $html .= '<table class="info-table">';
    $html .= '<tr><td class="label">Generated By</td><td>Realty Smartz Pathshala Admin Panel</td></tr>';
    $html .= '<tr><td class="label">Generated On</td><td>' . bulk_esc($generatedOn) . '</td></tr>';
    $html .= '<tr><td class="label">Exported By</td><td>' . bulk_esc($exportedBy) . '</td></tr>';
    $html .= '<tr><td class="label">User ID</td><td>' . bulk_esc($userIdLabel) . '</td></tr>';
    $html .= '</table>';

    $html .= '</div>'; // .user-page

    return $html;
}

$generatedOn = date('d M Y, h:i A');
$exportedBy = htmlspecialchars_decode($_SESSION['admin_name'] ?? 'Admin');

/* ══════════════════════════════
   BUILD FULL DOCUMENT HTML
══════════════════════════════ */
$css = '
    @page { margin: 90px 40px 70px 40px; }
    body { font-family: "DejaVu Sans", sans-serif; color: #222; font-size: 11px; }
    .header {
        position: fixed; top: -70px; left: 0px; right: 0px; height: 70px;
        text-align: center; border-bottom: 2px solid #C9933A; padding-bottom: 8px;
    }
    .header .company { font-size: 20px; font-weight: bold; color: #C9933A; }
    .header .title { font-size: 14px; font-weight: bold; color: #1A1A2E; margin-top: 2px; }
    .footer {
        position: fixed; bottom: -55px; left: 0px; right: 0px; height: 50px;
        border-top: 1px solid #ddd; padding-top: 6px; font-size: 9px; color: #777;
    }
    .footer table { width: 100%; }
    .footer td { padding: 1px 0; }
    .page-number:before { content: counter(page); }

    .cover-page { text-align: center; padding-top: 160px; }
    .cover-company { font-size: 30px; font-weight: bold; color: #C9933A; margin-bottom: 10px; }
    .cover-title { font-size: 20px; font-weight: bold; color: #1A1A2E; margin-bottom: 30px; }
    .cover-meta { font-size: 12px; color: #555; margin-bottom: 6px; }
    .cover-total { margin-top: 40px; font-size: 16px; font-weight: bold; color: #C9933A; }

    .summary-title { font-size: 16px; font-weight: bold; color: #1A1A2E; margin-bottom: 16px; text-align: center; }
    table.summary-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table.summary-table td { padding: 10px 14px; border: 1px solid #eee; font-size: 12px; }
    table.summary-table td.label { font-weight: bold; width: 220px; background: #fafafa; color: #888; }
    table.summary-table td.value { font-weight: bold; font-size: 14px; color: #C9933A; }

    .page-break { page-break-before: always; }

    .hero { text-align: center; margin-bottom: 18px; }
    .profile-photo { width: 100px; height: 100px; border-radius: 10px; object-fit: cover; border: 2px solid #e0e0e0; }
    .profile-photo-placeholder {
        width: 100px; height: 100px; border-radius: 10px; background: #f0f0f0; color: #999;
        font-size: 36px; font-weight: bold; text-align: center; line-height: 100px; margin: 0 auto;
        border: 2px solid #e0e0e0;
    }
    .hero-name { font-size: 18px; font-weight: bold; color: #1A1A2E; margin-top: 10px; }
    .hero-sub { font-size: 11px; color: #666; margin-top: 2px; }

    .section-title {
        background: #C9933A; color: #fff; font-size: 12px; font-weight: bold;
        padding: 6px 10px; margin-top: 16px; margin-bottom: 8px;
    }
    table.info-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.info-table td { padding: 5px 8px; border: 1px solid #eee; font-size: 10.5px; }
    table.info-table td.label { font-weight: bold; width: 160px; background: #fafafa; }

    .doc-group-title { font-weight: bold; font-size: 10.5px; color: #888; text-transform: uppercase; margin: 10px 0 4px; }
    table.doc-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.doc-table th { background: #f5f5f5; text-align: left; padding: 5px 8px; font-size: 10px; border: 1px solid #eee; }
    table.doc-table td { padding: 5px 8px; font-size: 10px; border: 1px solid #eee; }
    .status-uploaded { color: #2E7D32; font-weight: bold; }
    .status-not-uploaded { color: #C62828; font-weight: bold; }

    table.contact-card { width: 48%; display: inline-table; vertical-align: top; border-collapse: collapse; margin-right: 2%; }
    table.contact-card th { background: #1565C0; color: #fff; padding: 6px 8px; font-size: 11px; text-align: left; }
    table.contact-card.secondary th { background: #2E7D32; }
    table.contact-card td { padding: 5px 8px; font-size: 10px; border: 1px solid #eee; }
    table.contact-card td.label { font-weight: bold; width: 80px; background: #fafafa; }

    .muted { color: #999; font-style: italic; font-size: 10.5px; }
    a { color: #1565C0; text-decoration: none; }
';

$bodyHtml = '';
$bodyHtml .= '<div class="header">';
$bodyHtml .= '<div class="company">Realty Smartz Pathshala</div>';
$bodyHtml .= '<div class="title">COMPLETE USER DATABASE REPORT</div>';
$bodyHtml .= '</div>';

$bodyHtml .= '<div class="footer"><table><tr>';
$bodyHtml .= '<td style="text-align:left;">Generated By: Realty Smartz Pathshala Admin &nbsp;|&nbsp; Exported By: ' . bulk_esc($exportedBy) . '</td>';
$bodyHtml .= '<td style="text-align:right;">Confidential HR Document &nbsp;|&nbsp; Page <span class="page-number"></span></td>';
$bodyHtml .= '</tr></table></div>';

// ── COVER PAGE ──
$bodyHtml .= '<div class="cover-page">';
$bodyHtml .= '<div class="cover-company">REALTY SMARTZ PATHSHALA</div>';
$bodyHtml .= '<div class="cover-title">COMPLETE USER DATABASE REPORT</div>';
$bodyHtml .= '<div class="cover-meta">Generated On: ' . bulk_esc($generatedOn) . '</div>';
$bodyHtml .= '<div class="cover-meta">Generated By: ' . bulk_esc($exportedBy) . '</div>';
$bodyHtml .= '<div class="cover-total">Total Registered Users: ' . intval($totalCount) . '</div>';
$bodyHtml .= '</div>';

// ── SUMMARY PAGE ──
$bodyHtml .= '<div class="page-break">';
$bodyHtml .= '<div class="summary-title">SUMMARY</div>';
$bodyHtml .= '<table class="summary-table">';
$bodyHtml .= '<tr><td class="label">Total Users</td><td class="value">' . intval($totalCount) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Freshers</td><td class="value">' . intval($fresherCount) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Experienced</td><td class="value">' . intval($expCount) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Total Profiles Completed</td><td class="value">' . intval($totalCount) . '</td></tr>';
$bodyHtml .= '</table>';
$bodyHtml .= '</div>';

// ── ONE PAGE PER USER ──
foreach ($allRows as $row) {
    $bodyHtml .= bulk_buildUserPageHtml($row, $exportedBy, $generatedOn, false);
}

$fullHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>' . $bodyHtml . '</body></html>';

/* ══════════════════════════════
   RENDER PDF
══════════════════════════════ */
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($fullHtml);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'All_Users_Database_Report_' . date('Y-m-d') . '.pdf';

while (ob_get_level()) {
    ob_end_clean();
}
$dompdf->stream($filename, array('Attachment' => true));
exit();