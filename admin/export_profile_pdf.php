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

$uid = intval($_GET['id'] ?? 0);
if ($uid <= 0) {
    header("Location: ../admin/admin_profiles.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row) {
    header("Location: ../admin/admin_profiles.php?msg=notfound");
    exit();
}

// ── Helpers ──
function pdf_filePathAbs($val)
{
    if (empty($val)) {
        return null;
    }
    $normalized = str_replace('\\', '/', $val);
    $pos = strripos($normalized, '/uploads/');
    if ($pos !== false) {
        $normalized = substr($normalized, $pos + strlen('/uploads/'));
    } elseif (str_starts_with($normalized, 'uploads/')) {
        $normalized = substr($normalized, strlen('uploads/'));
    }
    $real = realpath(__DIR__ . '/../uploads/' . ltrim($normalized, '/'));
    if ($real) {
        return $real;
    }
    return null;
}

function pdf_filePathUrl($val)
{
    if (empty($val)) {
        return null;
    }
    $normalized = str_replace('\\', '/', $val);
    $pos = strripos($normalized, '/uploads/');
    if ($pos !== false) {
        $normalized = substr($normalized, $pos + strlen('/uploads/'));
    } elseif (str_starts_with($normalized, 'uploads/')) {
        $normalized = substr($normalized, strlen('uploads/'));
    }
    $scheme = 'http://';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https://';
    }
    $host = $_SERVER['HTTP_HOST'];
    $base = rtrim(str_replace('/admin', '', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $scheme . $host . $base . '/uploads/' . ltrim($normalized, '/');
}

function pdf_eduLabel($val)
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

function pdf_esc($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

$exp = strtolower(trim($row['experience'] ?? ''));
if ($exp === 'fresher') {
    $expLabel = 'Fresher';
} elseif ($exp === 'experienced') {
    $expLabel = 'Experienced';
} else {
    $expLabel = '—';
}
$eduLabelVal = pdf_eduLabel($row['education']);

// Emergency Contact
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

// Social Data
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

// Documents list
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

// Profile photo
function pdf_fixOrientation($absPath)
{
    $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
    if ($ext !== 'jpg' && $ext !== 'jpeg') {
        return $absPath;
    }
    if (!function_exists('exif_read_data')) {
        return $absPath;
    }
    $exif = @exif_read_data($absPath);
    if (!$exif || empty($exif['Orientation'])) {
        return $absPath;
    }
    $orientation = $exif['Orientation'];
    if ($orientation == 1) {
        return $absPath;
    }
    $image = @imagecreatefromjpeg($absPath);
    if (!$image) {
        return $absPath;
    }
    switch ($orientation) {
        case 3:
            $image = imagerotate($image, 180, 0);
            break;
        case 6:
            $image = imagerotate($image, -90, 0);
            break;
        case 8:
            $image = imagerotate($image, 90, 0);
            break;
    }
    $tmpPath = sys_get_temp_dir() . '/' . uniqid('profile_fixed_') . '.jpg';
    imagejpeg($image, $tmpPath, 90);
    imagedestroy($image);
    return $tmpPath;
}

$photoTag = '';
if (!empty($row['profile_photo'])) {
    $photoAbs = pdf_filePathAbs($row['profile_photo']);
    if ($photoAbs && file_exists($photoAbs)) {
        $photoAbsFixed = pdf_fixOrientation($photoAbs);
        $imgData = base64_encode(file_get_contents($photoAbsFixed));
        $ext = strtolower(pathinfo($photoAbs, PATHINFO_EXTENSION));
        if ($ext === 'png') {
            $mime = 'image/png';
        } else {
            $mime = 'image/jpeg';
        }
        $photoTag = '<img src="data:' . $mime . ';base64,' . $imgData . '" class="profile-photo">';
        if ($photoAbsFixed !== $photoAbs && file_exists($photoAbsFixed)) {
            @unlink($photoAbsFixed);
        }
    }
}
if ($photoTag === '') {
    $nameForInitial = trim($row['user_name'] ?? 'U');
    if ($nameForInitial === '') {
        $nameForInitial = 'U';
    }
    $initials = strtoupper(substr($nameForInitial, 0, 1));
    $photoTag = '<div class="profile-photo-placeholder">' . pdf_esc($initials) . '</div>';
}

// Documents HTML builder
function pdf_documentsTableHtml($documents)
{
    $html = '';
    foreach ($documents as $groupName => $docs) {
        $html .= '<div class="doc-group-title">' . pdf_esc($groupName) . '</div>';
        $html .= '<table class="doc-table"><thead><tr><th>Document Name</th><th>Status</th><th>Link</th></tr></thead><tbody>';
        foreach ($docs as $docItem) {
            $docName = $docItem[0];
            $docVal = $docItem[1];
            $absPath = pdf_filePathAbs($docVal);
            $absExists = ($absPath !== null && file_exists($absPath));
            $html .= '<tr><td>' . pdf_esc($docName) . '</td>';
            if (!empty($docVal) && $absExists) {
                $url = pdf_filePathUrl($docVal);
                $html .= '<td class="status-uploaded">Uploaded</td>';
                $html .= '<td><a href="' . pdf_esc($url) . '">View Document</a></td>';
            } else {
                $html .= '<td class="status-not-uploaded">Not Uploaded</td><td>&mdash;</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    }
    return $html;
}

// Emergency contacts HTML
function pdf_contactCardHtml($title, $relationVal, $name, $phone, $address, $colorClass)
{
    $html = '<table class="contact-card ' . $colorClass . '">';
    $html .= '<tr><th colspan="2">' . pdf_esc($title) . '</th></tr>';
    $html .= '<tr><td class="label">Relation</td><td>' . pdf_esc($relationVal !== '' ? $relationVal : '—') . '</td></tr>';
    $html .= '<tr><td class="label">Name</td><td>' . pdf_esc($name !== '' ? $name : '—') . '</td></tr>';
    $html .= '<tr><td class="label">Phone</td><td>' . pdf_esc($phone !== '' ? $phone : '—') . '</td></tr>';
    $html .= '<tr><td class="label">Address</td><td>' . nl2br(pdf_esc($address !== '' ? $address : '—')) . '</td></tr>';
    $html .= '</table>';
    return $html;
}

// Social profiles HTML
function pdf_socialProfilesHtml($socialData)
{
    if (empty($socialData)) {
        return '<p class="muted">No Social Profiles Available</p>';
    }
    $html = '<table class="doc-table"><thead><tr><th>Platform</th><th>Profile Link</th></tr></thead><tbody>';
    foreach ($socialData as $s) {
        $html .= '<tr><td>' . pdf_esc($s['platform']) . '</td><td><a href="' . pdf_esc($s['url']) . '">' . pdf_esc($s['url']) . '</a></td></tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}

$generatedOn = date('d M Y, h:i A');
$exportedBy = htmlspecialchars_decode($_SESSION['admin_name'] ?? 'Admin');
$userIdLabel = '#' . intval($row['user_id']);

$joinedDateDisplay = '—';
if (!empty($row['created_at'])) {
    $joinedDateDisplay = date('d M Y', strtotime($row['created_at']));
}

/* Build HTML report */
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
$bodyHtml .= '<div class="title">USER PROFILE REPORT</div>';
$bodyHtml .= '</div>';

$bodyHtml .= '<div class="footer"><table><tr>';
$bodyHtml .= '<td style="text-align:left;">Generated By: Realty Smartz Pathshala Admin &nbsp;|&nbsp; Exported By: ' . pdf_esc($exportedBy) . '</td>';
$bodyHtml .= '<td style="text-align:right;">Confidential HR Document &nbsp;|&nbsp; Page <span class="page-number"></span></td>';
$bodyHtml .= '</tr></table></div>';

$bodyHtml .= '<div class="hero">';
$bodyHtml .= $photoTag;
$bodyHtml .= '<div class="hero-name">' . pdf_esc($row['user_name']) . '</div>';
$bodyHtml .= '<div class="hero-sub">' . pdf_esc($expLabel) . ' &nbsp;&bull;&nbsp; ' . pdf_esc($eduLabelVal) . ' &nbsp;&bull;&nbsp; ' . pdf_esc($row['email']) . ' &nbsp;&bull;&nbsp; ' . pdf_esc($row['contact_no']) . ' &nbsp;&bull;&nbsp; ' . pdf_esc($userIdLabel) . '</div>';
$bodyHtml .= '</div>';

$bodyHtml .= '<div class="section-title">1. PERSONAL INFORMATION</div>';
$bodyHtml .= '<table class="info-table">';
$bodyHtml .= '<tr><td class="label">Full Name</td><td>' . pdf_esc($row['user_name']) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Email</td><td>' . pdf_esc($row['email']) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Category</td><td>' . pdf_esc($expLabel) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Qualification</td><td>' . pdf_esc($eduLabelVal) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Contact Number</td><td>' . pdf_esc($row['contact_no']) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Date of Birth</td><td>' . pdf_esc($row['dob']) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Joined Date</td><td>' . pdf_esc($joinedDateDisplay) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">User ID</td><td>' . pdf_esc($userIdLabel) . '</td></tr>';
$bodyHtml .= '</table>';

$bodyHtml .= '<div class="section-title">2. ADDRESS DETAILS</div>';
$bodyHtml .= '<table class="info-table">';
$bodyHtml .= '<tr><td class="label">Current Address</td><td>' . nl2br(pdf_esc($row['current_address'] ?: '—')) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Permanent Address</td><td>' . nl2br(pdf_esc($row['permanent_address'] ?: '—')) . '</td></tr>';
$bodyHtml .= '</table>';

$bodyHtml .= '<div class="section-title">3. DOCUMENTS</div>';
$bodyHtml .= pdf_documentsTableHtml($documents);

$bodyHtml .= '<div class="section-title">4. EMERGENCY CONTACTS</div>';
$bodyHtml .= pdf_contactCardHtml('Primary Contact', $ref1Relation, $ref1Name, $ref1Phone, $ref1Address, 'primary');
$bodyHtml .= pdf_contactCardHtml('Secondary Contact', $ref2Relation, $ref2Name, $ref2Phone, $ref2Address, 'secondary');

$bodyHtml .= '<div class="section-title">5. SOCIAL PROFILES</div>';
$bodyHtml .= pdf_socialProfilesHtml($socialData);

$bodyHtml .= '<div class="section-title">6. REPORT INFORMATION</div>';
$bodyHtml .= '<table class="info-table">';
$bodyHtml .= '<tr><td class="label">Generated By</td><td>Realty Smartz Pathshala Admin Panel</td></tr>';
$bodyHtml .= '<tr><td class="label">Generated On</td><td>' . pdf_esc($generatedOn) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">Exported By</td><td>' . pdf_esc($exportedBy) . '</td></tr>';
$bodyHtml .= '<tr><td class="label">User ID</td><td>' . pdf_esc($userIdLabel) . '</td></tr>';
$bodyHtml .= '</table>';

$fullHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>' . $bodyHtml . '</body></html>';

/* Render PDF */
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($fullHtml);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['user_name'] ?: ('User_' . $uid));
$filename = $safeName . '_Profile.pdf';

while (ob_get_level()) {
    ob_end_clean();
}
$dompdf->stream($filename, array('Attachment' => true));
exit();