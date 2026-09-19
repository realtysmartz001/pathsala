<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

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

// ── Helpers (unchanged from working version) ──
function filePathAbs($val)
{
    if (empty($val))
        return null;
    $clean = basename(str_replace('uploads/', '', $val));
    return realpath(__DIR__ . '/../profile/uploads/' . $clean) ?: null;
}
function filePathUrl($val)
{
    if (empty($val))
        return null;
    $clean = basename(str_replace('uploads/', '', $val));
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base = rtrim(str_replace('/admin', '', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $scheme . $host . $base . '/profile/uploads/' . $clean;
}
function eduLabel($val)
{
    $map = ['10th' => '10th', '12th' => '12th', 'graduation' => 'Graduation', 'post_graduation' => 'Post Grad', 'diploma' => 'Diploma'];
    return $map[$val] ?? ucfirst($val ?? '');
}

$exp = strtolower(trim($row['experience'] ?? ''));
$expLabel = $exp === 'fresher' ? 'Fresher' : ($exp === 'experienced' ? 'Experienced' : '—');
$eduLabelVal = eduLabel($row['education']);

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
    $ref1Name = $ref1Phone = $ref1Address = '';
}
$ref2Relation = ucfirst($row['other_relation'] ?? '');
$ref2Name = $row['other_name'] ?? '';
$ref2Phone = $row['other_contact'] ?? '';
$ref2Address = $row['other_address'] ?? '';

// Social Data
$socialData = [];
$p1 = trim($row['social_platform_1'] ?? '');
$u1 = trim($row['social_url_1'] ?? '');
if (!empty($p1) && !empty($u1))
    $socialData[] = ['platform' => ucfirst($p1), 'url' => $u1];
$u2 = trim($row['social_url_2'] ?? '');
if (!empty($u2))
    $socialData[] = ['platform' => 'LinkedIn', 'url' => $u2];
$extraPlatforms = json_decode($row['social_platform_extra'] ?? '[]', true) ?: [];
$extraUrls = json_decode($row['social_url_extra'] ?? '[]', true) ?: [];
foreach ($extraPlatforms as $idx => $ep) {
    $eu = $extraUrls[$idx] ?? '';
    if (!empty($ep) && !empty($eu))
        $socialData[] = ['platform' => ucfirst($ep), 'url' => $eu];
}

// Documents list: [Label, db field, short "Open X" label]
$documents = [
    'Identity Documents' => [
        ['Aadhaar Card', $row['aadhar_doc'], 'Open Aadhaar'],
        ['PAN Card', $row['pan_doc'], 'Open PAN'],
        ['Cancelled Cheque', $row['cheque_doc'], 'Open Cheque'],
        ['Passbook', $row['passbook_doc'], 'Open Passbook'],
    ],
    'Employment Documents' => [
        ['Offer Letter', $row['offer_letter_doc'], 'Open Offer Letter'],
        ['Relieving Letter', $row['relieving_letter_doc'], 'Open Relieving Letter'],
        ['Salary Slip', $row['salary_slip_doc'], 'Open Salary Slip'],
        ['Rehire Mail', $row['up_rehire_mail_doc'], 'Open Rehire Mail'],
    ],
    'Education Documents' => [
        ['Marksheet', $row['marksheet_doc'], 'Open Marksheet'],
    ],
];

/* ══════════════════════════════
   BUILD SPREADSHEET — PREMIUM HR REPORT DESIGN
══════════════════════════════ */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Profile Report');

// ── Premium palette ──
$goldColor   = 'B8860B'; // deep antique gold — richer than before
$goldLight   = 'C9933A';
$navyDark    = '0F1729'; // near-black navy for headers
$navyColor   = '1E3A5F'; // steel navy for accents
$greenColor  = '1B5E20';
$redColor    = 'B71C1C';
$cardBg      = 'FCFBF7'; // warm off-white card background
$labelGray   = '8A8A8A';
$valueDark   = '1A1A1A';
$borderSoft  = 'E2DDD3'; // warm soft border
$borderStrong= 'B8860B';
$zebraTint   = 'F5F2EC';

$sheet->getColumnDimension('A')->setWidth(24);
$sheet->getColumnDimension('B')->setWidth(36);
$sheet->getColumnDimension('C')->setWidth(24);
$sheet->getColumnDimension('D')->setWidth(36);
$sheet->getDefaultRowDimension()->setRowHeight(-1);

$r = 1;

/* ── Style helpers (font properties always chained BEFORE getColor()->setRGB) ── */
function boxRange($sheet, $range, $color)
{
    $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($color);
}
function thickTop($sheet, $range, $color)
{
    $sheet->getStyle($range)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($color);
}

/* Section band — premium dark navy bar with gold accent stripe */
function sectionHeader($sheet, &$r, $icon, $title, $navyDark, $goldColor)
{
    $sheet->setCellValue("A{$r}", $icon . '  ' . strtoupper($title));
    $sheet->mergeCells("A{$r}:D{$r}");
    $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(12)->setName('Calibri')->getColor()->setRGB('FFFFFF');
    $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navyDark);
    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
    $sheet->getRowDimension($r)->setRowHeight(26);
    $r++;
    // Gold accent underline
    $sheet->getStyle("A{$r}:D{$r}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($goldColor);
}

/* Stacked field card: label chip + large value, boxed */
function stackedField($sheet, &$r, $icon, $label, $value, $borderSoft, $cardBg, $labelGray, $valueDark)
{
    $sheet->setCellValue("A{$r}", $icon . '  ' . strtoupper($label));
    $sheet->mergeCells("A{$r}:D{$r}");
    $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(8.5)->setName('Calibri')->getColor()->setRGB($labelGray);
    $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
    $sheet->getStyle("A{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension($r)->setRowHeight(15);
    $r++;

    $sheet->setCellValue("A{$r}", $value !== '' && $value !== null ? $value : '—');
    $sheet->mergeCells("A{$r}:D{$r}");
    $sheet->getStyle("A{$r}")->getFont()->setSize(11.5)->setBold(true)->setName('Calibri')->getColor()->setRGB($valueDark);
    $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($cardBg);
    $sheet->getStyle("A{$r}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);
    $sheet->getRowDimension($r)->setRowHeight(24);
    $r++;

    boxRange($sheet, "A" . ($r - 2) . ":D" . ($r - 1), $borderSoft);
    $r++; // breathing space
}

// ══════════════════════════════
// SECTION 1 — BRANDED COMPANY HEADER
// ══════════════════════════════
$headerBandStart = $r;

// Full-width navy band behind the header block
$sheet->getStyle("A{$r}:D" . ($r + 3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navyDark);

$sheet->getRowDimension($r)->setRowHeight(46);
$r++;

$sheet->setCellValue("A{$r}", 'REALTY SMARTZ PATHSHALA');
$sheet->mergeCells("A{$r}:D{$r}");
$sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(19)->setName('Calibri')->getColor()->setRGB('F5D98B');
$sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension($r)->setRowHeight(28);
$r++;

$sheet->setCellValue("A{$r}", 'USER PROFILE REPORT');
$sheet->mergeCells("A{$r}:D{$r}");
$sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(11)->setName('Calibri')->getColor()->setRGB('FFFFFF');
$sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension($r)->setRowHeight(18);
$r++;

$sheet->setCellValue("A{$r}", 'Generated On: ' . date('d M Y, h:i A') . '   •   Generated By: Realty Smartz Pathshala Admin');
$sheet->mergeCells("A{$r}:D{$r}");
$sheet->getStyle("A{$r}")->getFont()->setSize(8.5)->setItalic(true)->setName('Calibri')->getColor()->setRGB('B8C0CC');
$sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension($r)->setRowHeight(16);
$r++;

thickTop($sheet, "A{$headerBandStart}:D{$headerBandStart}", $goldColor);
$sheet->getStyle("A" . ($r - 1) . ":D" . ($r - 1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($goldColor);
$r += 2;

// ══════════════════════════════
// SECTION 2 — PROFILE HERO / IDENTITY CARD
// ══════════════════════════════
$summaryStartRow = $r;

$photoAbs = !empty($row['profile_photo']) ? filePathAbs($row['profile_photo']) : null;
$photoEmbedded = false;
if ($photoAbs && file_exists($photoAbs)) {
    try {
        $drawing = new Drawing();
        $drawing->setName('Profile Photo');
        $drawing->setPath($photoAbs);
        $drawing->setHeight(118);
        $drawing->setCoordinates("A{$r}");
        $drawing->setOffsetX(8);
        $drawing->setOffsetY(8);
        $drawing->setWorksheet($sheet);
        $photoEmbedded = true;
    } catch (\Throwable $e) {
        $photoEmbedded = false;
    }
}
if (!$photoEmbedded) {
    $sheet->setCellValue("A{$r}", 'No Profile Photo Available');
    $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9)->setName('Calibri')->getColor()->setRGB('AAAAAA');
    $sheet->getStyle("A{$r}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// Large employee name banner (row directly under card top, spanning B:D)
$sheet->setCellValue("B{$r}", $row['user_name']);
$sheet->mergeCells("B{$r}:D{$r}");
$sheet->getStyle("B{$r}")->getFont()->setBold(true)->setSize(17)->setName('Calibri')->getColor()->setRGB($navyDark);
$sheet->getStyle("B{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension($r)->setRowHeight(26);
$nameRow = $r;
$r++;

$sheet->setCellValue("B{$r}", $expLabel . '   •   ' . $eduLabelVal . '   •   User ID #' . intval($row['user_id']));
$sheet->mergeCells("B{$r}:D{$r}");
$sheet->getStyle("B{$r}")->getFont()->setBold(true)->setSize(9.5)->setName('Calibri')->getColor()->setRGB($goldColor);
$sheet->getStyle("B{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension($r)->setRowHeight(16);
$r++;

$heroFacts = [
    ['📧 Email', $row['email']],
    ['📱 Contact Number', $row['contact_no']],
    ['📅 Date of Birth', $row['dob']],
    ['🗓 Joined Date', !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '—'],
];
foreach ($heroFacts as $hf) {
    $sheet->setCellValue("B{$r}", $hf[0]);
    $sheet->getStyle("B{$r}")->getFont()->setBold(true)->setSize(9)->setName('Calibri')->getColor()->setRGB($labelGray);
    $sheet->setCellValue("C{$r}", $hf[1] ?: '—');
    $sheet->mergeCells("C{$r}:D{$r}");
    $sheet->getStyle("C{$r}")->getFont()->setSize(10.5)->setBold(true)->setName('Calibri')->getColor()->setRGB($valueDark);
    $sheet->getRowDimension($r)->setRowHeight(18);
    $r++;
}

$summaryEndRow = max($r - 1, $summaryStartRow + 6);
$sheet->mergeCells("A{$summaryStartRow}:A{$summaryEndRow}");
$sheet->getStyle("A{$summaryStartRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($cardBg);
$sheet->getStyle("B{$summaryStartRow}:D{$summaryEndRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($cardBg);
boxRange($sheet, "A{$summaryStartRow}:D{$summaryEndRow}", $borderStrong);
$sheet->getStyle("A{$summaryStartRow}:D{$summaryStartRow}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK)->getColor()->setRGB($goldColor);
$r = $summaryEndRow + 2;

// ══════════════════════════════
// SECTION 3 — PERSONAL INFORMATION
// ══════════════════════════════
sectionHeader($sheet, $r, '👤', '3. Personal Information', $navyDark, $goldColor);
$r++;
stackedField($sheet, $r, '👤', 'Full Name', $row['user_name'], $borderSoft, $cardBg, $labelGray, $valueDark);
stackedField($sheet, $r, '📧', 'Email', $row['email'], $borderSoft, $cardBg, $labelGray, $valueDark);
stackedField($sheet, $r, '🎓', 'Category', $expLabel, $borderSoft, $cardBg, $labelGray, $valueDark);
stackedField($sheet, $r, '🎓', 'Qualification', $eduLabelVal, $borderSoft, $cardBg, $labelGray, $valueDark);
stackedField($sheet, $r, '📱', 'Contact Number', $row['contact_no'], $borderSoft, $cardBg, $labelGray, $valueDark);
stackedField($sheet, $r, '📅', 'Date of Birth', $row['dob'], $borderSoft, $cardBg, $labelGray, $valueDark);
stackedField($sheet, $r, '🗓', 'Joined Date', !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '', $borderSoft, $cardBg, $labelGray, $valueDark);
stackedField($sheet, $r, '🆔', 'User ID', '#' . intval($row['user_id']), $borderSoft, $cardBg, $labelGray, $valueDark);

// ══════════════════════════════
// SECTION 4 — ADDRESS DETAILS
// ══════════════════════════════
sectionHeader($sheet, $r, '🏠', '4. Address Details', $navyDark, $goldColor);
$r++;
stackedField($sheet, $r, '🏠', 'Current Address', $row['current_address'], $borderSoft, $cardBg, $labelGray, $valueDark);
stackedField($sheet, $r, '🏠', 'Permanent Address', $row['permanent_address'], $borderSoft, $cardBg, $labelGray, $valueDark);

// ══════════════════════════════
// SECTION 5 — DOCUMENTS
// ══════════════════════════════
sectionHeader($sheet, $r, '📁', '5. Documents', $navyDark, $goldColor);
$r++;

$sheet->setCellValue("A{$r}", 'DOCUMENT');
$sheet->setCellValue("C{$r}", 'STATUS');
$sheet->setCellValue("D{$r}", 'OPEN DOCUMENT');
$sheet->mergeCells("A{$r}:B{$r}");
$sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true)->setSize(9)->setName('Calibri')->getColor()->setRGB('FFFFFF');
$sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navyColor);
$sheet->getStyle("A{$r}:D{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension($r)->setRowHeight(19);
$r++;

$docTableStart = $r;
$zebra = false;
foreach ($documents as $groupName => $docs) {
    $sheet->setCellValue("A{$r}", strtoupper($groupName));
    $sheet->mergeCells("A{$r}:D{$r}");
    $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setItalic(true)->setSize(8.5)->setName('Calibri')->getColor()->setRGB($navyColor);
    $sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EDF1F6');
    $sheet->getStyle("A{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension($r)->setRowHeight(15);
    $r++;
    foreach ($docs as $doc) {
        $docName = $doc[0];
        $docVal = $doc[1];
        $openLabel = '🔗 ' . $doc[2];
        $sheet->setCellValue("A{$r}", '📄  ' . $docName);
        $sheet->mergeCells("A{$r}:B{$r}");
        $sheet->getStyle("A{$r}")->getFont()->setSize(10)->setName('Calibri');
        $sheet->getStyle("A{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);

        $absExists = filePathAbs($docVal) && file_exists(filePathAbs($docVal));
        if (!empty($docVal) && $absExists) {
            $url = filePathUrl($docVal);
            $sheet->setCellValue("C{$r}", 'Uploaded');
            $sheet->getStyle("C{$r}")->getFont()->setBold(true)->setSize(9.5)->setName('Calibri')->getColor()->setRGB($greenColor);
            $sheet->setCellValue("D{$r}", $openLabel);
            $sheet->getCell("D{$r}")->getHyperlink()->setUrl($url);
            $sheet->getStyle("D{$r}")->getFont()->setBold(true)->setSize(9.5)->setUnderline(true)->setName('Calibri')->getColor()->setRGB($navyColor);
        } else {
            $sheet->setCellValue("C{$r}", 'Not Uploaded');
            $sheet->getStyle("C{$r}")->getFont()->setBold(true)->setSize(9.5)->setName('Calibri')->getColor()->setRGB($redColor);
            $sheet->setCellValue("D{$r}", '—');
            $sheet->getStyle("D{$r}")->getFont()->setSize(9.5)->setName('Calibri')->getColor()->setRGB('BBBBBB');
        }
        $sheet->getStyle("A{$r}:D{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        if ($zebra) {
            $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($zebraTint);
        }
        $zebra = !$zebra;
        $sheet->getRowDimension($r)->setRowHeight(18);
        $r++;
    }
}
boxRange($sheet, "A{$docTableStart}:D" . ($r - 1), $borderSoft);
$r++;

// ══════════════════════════════
// SECTION 6 — EMERGENCY CONTACTS
// ══════════════════════════════
sectionHeader($sheet, $r, '📞', '6. Emergency Contacts', $navyDark, $goldColor);
$r++;

$sheet->setCellValue("A{$r}", '🅿️  PRIMARY CONTACT');
$sheet->mergeCells("A{$r}:B{$r}");
$sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(9.5)->setName('Calibri')->getColor()->setRGB('FFFFFF');
$sheet->getStyle("A{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navyColor);
$sheet->getStyle("A{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);

$sheet->setCellValue("C{$r}", '🅂  SECONDARY CONTACT');
$sheet->mergeCells("C{$r}:D{$r}");
$sheet->getStyle("C{$r}")->getFont()->setBold(true)->setSize(9.5)->setName('Calibri')->getColor()->setRGB('FFFFFF');
$sheet->getStyle("C{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($greenColor);
$sheet->getStyle("C{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension($r)->setRowHeight(19);
$r++;

$contactStartRow = $r;
$contactRows = [
    ['Name', $ref1Name, 'Name', $ref2Name],
    ['Relation', $ref1Relation, 'Relation', $ref2Relation],
    ['Phone', $ref1Phone, 'Phone', $ref2Phone],
    ['Address', $ref1Address, 'Address', $ref2Address],
];
foreach ($contactRows as $cr) {
    $sheet->setCellValue("A{$r}", strtoupper($cr[0]));
    $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(9)->setName('Calibri')->getColor()->setRGB($labelGray);
    $sheet->getStyle("A{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_TOP);
    $sheet->setCellValue("B{$r}", $cr[1] ?: '—');
    $sheet->getStyle("B{$r}")->getFont()->setSize(10)->setName('Calibri')->getColor()->setRGB($valueDark);
    $sheet->getStyle("B{$r}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

    $sheet->setCellValue("C{$r}", strtoupper($cr[2]));
    $sheet->getStyle("C{$r}")->getFont()->setBold(true)->setSize(9)->setName('Calibri')->getColor()->setRGB($labelGray);
    $sheet->getStyle("C{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_TOP);
    $sheet->setCellValue("D{$r}", $cr[3] ?: '—');
    $sheet->getStyle("D{$r}")->getFont()->setSize(10)->setName('Calibri')->getColor()->setRGB($valueDark);
    $sheet->getStyle("D{$r}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

    $sheet->getRowDimension($r)->setRowHeight(20);
    $r++;
}
boxRange($sheet, "A{$contactStartRow}:B" . ($r - 1), $borderSoft);
boxRange($sheet, "C{$contactStartRow}:D" . ($r - 1), $borderSoft);
$r++;

// ══════════════════════════════
// SECTION 7 — SOCIAL PROFILES
// ══════════════════════════════
sectionHeader($sheet, $r, '🔗', '7. Social Profiles', $navyDark, $goldColor);
$r++;

$sheet->setCellValue("A{$r}", 'PLATFORM');
$sheet->setCellValue("C{$r}", 'PROFILE LINK');
$sheet->mergeCells("A{$r}:B{$r}");
$sheet->mergeCells("C{$r}:D{$r}");
$sheet->getStyle("A{$r}:D{$r}")->getFont()->setBold(true)->setSize(9)->setName('Calibri')->getColor()->setRGB('FFFFFF');
$sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($navyColor);
$sheet->getStyle("A{$r}:D{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension($r)->setRowHeight(19);
$r++;

$socialStartRow = $r;
$knownPlatforms = ['Instagram', 'LinkedIn', 'Facebook', 'Twitter'];

$displayList = [];
foreach ($knownPlatforms as $kp) {
    $match = null;
    foreach ($socialData as $s) {
        if (strcasecmp($s['platform'], $kp) === 0) {
            $match = $s;
            break;
        }
    }
    $displayList[] = ['platform' => $kp, 'url' => $match ? $match['url'] : ''];
}
foreach ($socialData as $s) {
    $already = false;
    foreach ($knownPlatforms as $kp) {
        if (strcasecmp($s['platform'], $kp) === 0) {
            $already = true;
            break;
        }
    }
    if (!$already) {
        $displayList[] = $s;
    }
}

$zebra = false;
foreach ($displayList as $s) {
    $sheet->setCellValue("A{$r}", $s['platform']);
    $sheet->mergeCells("A{$r}:B{$r}");
    $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(10)->setName('Calibri')->getColor()->setRGB($valueDark);
    $sheet->getStyle("A{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);

    if (!empty($s['url'])) {
        $sheet->setCellValue("C{$r}", $s['url']);
        $sheet->mergeCells("C{$r}:D{$r}");
        $sheet->getCell("C{$r}")->getHyperlink()->setUrl($s['url']);
        $sheet->getStyle("C{$r}")->getFont()->setSize(10)->setUnderline(true)->setName('Calibri')->getColor()->setRGB($navyColor);
    } else {
        $sheet->setCellValue("C{$r}", '—');
        $sheet->mergeCells("C{$r}:D{$r}");
        $sheet->getStyle("C{$r}")->getFont()->setSize(10)->setName('Calibri')->getColor()->setRGB('BBBBBB');
    }
    $sheet->getStyle("C{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);
    if ($zebra) {
        $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($zebraTint);
    }
    $zebra = !$zebra;
    $sheet->getRowDimension($r)->setRowHeight(18);
    $r++;
}
boxRange($sheet, "A{$socialStartRow}:D" . ($r - 1), $borderSoft);
$r++;

// ══════════════════════════════
// SECTION 8 — REPORT FOOTER
// ══════════════════════════════
sectionHeader($sheet, $r, '📋', '8. Report Footer', $navyDark, $goldColor);
$r++;
$exportedBy = htmlspecialchars_decode($_SESSION['admin_name'] ?? 'Admin');

$footerRows = [
    ['Generated By', 'Realty Smartz Pathshala Admin'],
    ['Generated On', date('d M Y, h:i A')],
    ['Exported By', $exportedBy],
    ['User ID', '#' . intval($row['user_id'])],
];
$footerStart = $r;
foreach ($footerRows as $fr) {
    $sheet->setCellValue("A{$r}", strtoupper($fr[0]));
    $sheet->mergeCells("A{$r}:B{$r}");
    $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(9)->setName('Calibri')->getColor()->setRGB($labelGray);
    $sheet->getStyle("A{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->setCellValue("C{$r}", $fr[1]);
    $sheet->mergeCells("C{$r}:D{$r}");
    $sheet->getStyle("C{$r}")->getFont()->setSize(10)->setBold(true)->setName('Calibri')->getColor()->setRGB($valueDark);
    $sheet->getStyle("C{$r}")->getAlignment()->setIndent(1)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension($r)->setRowHeight(18);
    $r++;
}
boxRange($sheet, "A{$footerStart}:D" . ($r - 1), $borderSoft);
$r++;

$sheet->setCellValue("A{$r}", '🔒  CONFIDENTIAL HR DOCUMENT   —   Realty Smartz Pathshala   —   Page 1');
$sheet->mergeCells("A{$r}:D{$r}");
$sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setBold(true)->setSize(8.5)->setName('Calibri')->getColor()->setRGB($labelGray);
$sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension($r)->setRowHeight(18);

// ── Global print/layout settings (unchanged behavior, refined margins) ──
$sheet->freezePane('A7');
$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);
$sheet->getPageSetup()->setPrintArea("A1:D{$r}");
$sheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.4)->setRight(0.4);
$sheet->getPageMargins()->setHeader(0.2)->setFooter(0.2);

// ── Output (unchanged) ──
$safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['user_name'] ?: ('User_' . $uid));
$filename = $safeName . '.xlsx';

while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();    