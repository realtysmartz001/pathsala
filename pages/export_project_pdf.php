<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ── Load Projects Data (same source as project-details.php) ──
$projects = require __DIR__ . '/inc/projects_data_db.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

function pdfp_makeSlug($title, $sector)
{
    $s = strtolower(trim($title . '-' . $sector));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

$project = null;
foreach ($projects as $p) {
    if (pdfp_makeSlug($p['title'], $p['sector_raw']) === $slug) {
        $project = $p;
        break;
    }
}

if (!$project) {
    http_response_code(404);
    die('Project not found.');
}

// ── Helpers (mirrors project-details.php logic) ──
function pdfp_parseUSP($usp_raw)
{
    if (empty($usp_raw)) {
        return [];
    }
    $lines = preg_split('/\r\n|\r|\n/', trim($usp_raw));
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $line = preg_replace('/^\d+[\.\)]\s*/', '', $line);
        if ($line !== '') {
            $items[] = $line;
        }
    }
    return $items;
}
function pdfp_parseConfigs($configs)
{
    if (is_array($configs)) {
        return $configs;
    }
    return [];
}
function pdfp_esc($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
function pdfp_formatPrice($price)
{
    if (empty($price)) {
        return 'On Request';
    }
    return $price;
}

$usp_items = pdfp_parseUSP($project['usp_raw'] ?? '');
$configs = pdfp_parseConfigs($project['configs'] ?? []);

$title = $project['title'] ?? '';
$location = $project['location'] ?? '';
$sector = $project['sector_raw'] ?? '';
$land = $project['land'] ?? '';
$towers = $project['towers'] ?? '';
$height = $project['height'] ?? '';
$clubhouse = $project['clubhouse'] ?? '';
$possession = $project['possession_raw'] ?? '';
$payment = $project['payment_plan'] ?? '';
$min_price = pdfp_formatPrice($project['min_price'] ?? '');
$max_price = pdfp_formatPrice($project['max_price'] ?? '');
$land_num = $project['land_num'] ?? '';
$lat = $project['lat'] ?? null;
$lng = $project['lng'] ?? null;

// Builder — same prefix-matching logic already used on project.php
function pdfp_builder($title)
{
    $builders = array('Smart World', 'Emaar', 'Adani', 'Signature Global', 'Godrej', 'Sobha', 'Ashiana', 'Titanium', 'Experion');
    foreach ($builders as $b) {
        if (stripos($title, $b) === 0) {
            return $b;
        }
    }
    $parts = explode(' ', $title);
    return $parts[0];
}
$builder = pdfp_builder($title);

// Status — same year-based logic already used on project.php
function pdfp_possessionYear($raw)
{
    if ($raw && preg_match('/(20\d{2})/', $raw, $m)) {
        return (int) $m[1];
    }
    return null;
}
function pdfp_status($year)
{
    $currentYear = (int) date('Y');
    if ($year === null) {
        return 'Under Construction';
    }
    if ($year <= $currentYear) {
        return 'Ready to Move';
    }
    if ($year - $currentYear <= 1) {
        return 'Nearing Possession';
    }
    return 'Under Construction';
}
$possYear = pdfp_possessionYear($possession);
$status = pdfp_status($possYear);

// Project image — same resolution used by project.php's image map, falls back gracefully
$imageRel = !empty($project['image']) ? $project['image'] : 'default-project.webp';
$imageAbs = realpath(__DIR__ . '/../assets/img/projects/' . $imageRel);
$imageTag = '';
if ($imageAbs && file_exists($imageAbs)) {
    $ext = strtolower(pathinfo($imageAbs, PATHINFO_EXTENSION));
    $mimeMap = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');
    $mime = isset($mimeMap[$ext]) ? $mimeMap[$ext] : 'image/jpeg';
    try {
        $imgData = base64_encode(file_get_contents($imageAbs));
        $imageTag = '<img src="data:' . $mime . ';base64,' . $imgData . '" class="hero-image">';
    } catch (\Throwable $e) {
        $imageTag = '';
    }
}

// Static Amenities list — identical to the list already shown on project-details.php for every project
$amenities = array(
    'Swimming Pool', 'Modern Gym', "Kids' Play Area", 'Jogging Track',
    'Clubhouse', 'Yoga Deck', 'Tennis Court', 'Basketball Court',
    'Landscaped Garden', '24x7 Security', 'Co-working Space', 'EV Charging',
    'Ample Parking', 'Concierge Service', 'High-Speed WiFi', 'Green Spaces'
);

// Static Location Advantages list — identical to the list already shown on project-details.php for every project
$locationAdvantages = array(
    array('Dwarka Expressway', '2 Min Drive'),
    array('IGI Airport', '20 Min Drive'),
    array('Metro Station', '5 Min Walk'),
    array('Top Schools', '5 Min Drive'),
    array('Leading Hospitals', '10 Min Drive'),
    array('Cyber Hub', '15 Min Drive'),
    array('Golf Course', '10 Min Drive'),
    array('Mall & Shopping', '8 Min Drive'),
);

$generatedOn = date('d M Y, h:i A');
$generatedBy = 'Realty Smartz Pathshala';
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $generatedBy = htmlspecialchars_decode($_SESSION['admin_name'] ?? 'Admin');
} elseif (isset($_SESSION['user_name'])) {
    $generatedBy = $_SESSION['user_name'];
} elseif (isset($_SESSION['user_email'])) {
    $generatedBy = $_SESSION['user_email'];
}

/* ══════════════════════════════
   BUILD HTML — PREMIUM BROCHURE STYLE
══════════════════════════════ */
$css = '
    @page { margin: 95px 40px 65px 40px; }
    body { font-family: "DejaVu Sans", sans-serif; color: #222; font-size: 10.5px; }
    .header {
        position: fixed; top: -75px; left: 0px; right: 0px; height: 75px;
        text-align: center; border-bottom: 2px solid #C9933A; padding-bottom: 10px;
    }
    .header .company { font-size: 20px; font-weight: bold; color: #C9933A; }
    .header .title { font-size: 13px; font-weight: bold; color: #1A1A2E; margin-top: 2px; }
    .footer {
        position: fixed; bottom: -50px; left: 0px; right: 0px; height: 46px;
        border-top: 1px solid #ddd; padding-top: 6px; font-size: 8.5px; color: #777;
    }
    .footer table { width: 100%; }

    .hero-image { width: 100%; max-height: 260px; object-fit: cover; border-radius: 4px; margin-bottom: 10px; }
    .hero-title { font-size: 22px; font-weight: bold; color: #1A1A2E; margin-bottom: 4px; }
    .hero-badge { display: inline-block; background: #FCF3E3; color: #B8860B; font-size: 9px; font-weight: bold; padding: 3px 10px; border-radius: 20px; margin-bottom: 8px; }
    .hero-meta { font-size: 10px; color: #555; margin-bottom: 8px; }
    .hero-price { font-size: 16px; font-weight: bold; color: #B8860B; margin-bottom: 4px; }

    .section-title {
        background: #0F1729; color: #fff; font-size: 12px; font-weight: bold;
        padding: 7px 10px; margin-top: 18px; margin-bottom: 8px;
        border-top: 3px solid #C9933A;
    }
    table.info-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.info-table td { padding: 6px 8px; border: 1px solid #eee; font-size: 10px; }
    table.info-table td.label { font-weight: bold; width: 150px; background: #FAF8F4; color: #888; }

    table.cfg-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.cfg-table th { background: #1E3A5F; color: #fff; text-align: left; padding: 6px 8px; font-size: 9.5px; border: 1px solid #eee; }
    table.cfg-table td { padding: 6px 8px; font-size: 10px; border: 1px solid #eee; }

    .amenity-grid { width: 100%; }
    .amenity-item { display: inline-block; width: 24%; font-size: 9.5px; padding: 4px 0; }
    .amenity-item:before { content: "\2022"; color: #C9933A; font-weight: bold; margin-right: 4px; }

    .highlight-item { padding: 5px 0 5px 14px; font-size: 10px; border-bottom: 1px solid #f2f2f2; }
    .highlight-item:before { content: "\2605"; color: #C9933A; margin-right: 6px; }

    table.loc-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.loc-table td { padding: 5px 8px; font-size: 9.5px; border: 1px solid #eee; }
    table.loc-table td.place { font-weight: bold; }
    table.loc-table td.dist { color: #888; text-align: right; }
';

$bodyHtml = '';
$bodyHtml .= '<div class="header">';
$bodyHtml .= '<div class="company">Realty Smartz Pathshala</div>';
$bodyHtml .= '<div class="title">PROJECT INFORMATION REPORT</div>';
$bodyHtml .= '</div>';

$bodyHtml .= '<div class="footer"><table><tr>';
$bodyHtml .= '<td style="text-align:left;">Generated By: ' . pdfp_esc($generatedBy) . '</td>';
$bodyHtml .= '<td style="text-align:right;">Realty Smartz Pathshala &nbsp;|&nbsp; Generated On: ' . pdfp_esc($generatedOn) . '</td>';
$bodyHtml .= '</tr></table></div>';

if ($imageTag !== '') {
    $bodyHtml .= $imageTag;
}
$bodyHtml .= '<div class="hero-badge">PREMIUM RESIDENTIAL PROJECT</div>';
$bodyHtml .= '<div class="hero-title">' . pdfp_esc($title) . '</div>';
$bodyHtml .= '<div class="hero-meta">' . pdfp_esc($builder) . ' &nbsp;•&nbsp; ' . pdfp_esc($sector) . ', ' . pdfp_esc($location) . ' &nbsp;•&nbsp; ' . pdfp_esc($status) . '</div>';
$bodyHtml .= '<div class="hero-price">Starting From ' . pdfp_esc($min_price);
if ($max_price && $max_price !== $min_price) {
    $bodyHtml .= ' — ' . pdfp_esc($max_price);
}
$bodyHtml .= '</div>';

$bodyHtml .= '<div class="section-title">PROJECT OVERVIEW</div>';
$bodyHtml .= '<table class="info-table">';
$overviewRows = array(
    array('Location', $location),
    array('Sector', $sector),
    array('Land Area', $land),
    array('Towers', $towers),
    array('Height', $height),
    array('Clubhouse', $clubhouse),
    array('Payment Plan', $payment),
    array('Possession', $possession),
);
foreach ($overviewRows as $row) {
    if (empty($row[1])) {
        continue;
    }
    $bodyHtml .= '<tr><td class="label">' . pdfp_esc($row[0]) . '</td><td>' . pdfp_esc($row[1]) . '</td></tr>';
}
$bodyHtml .= '</table>';

if (!empty($configs)) {
    $bodyHtml .= '<div class="section-title">UNIT CONFIGURATIONS</div>';
    $bodyHtml .= '<table class="cfg-table"><thead><tr><th>Configuration</th><th>Area</th><th>Price</th></tr></thead><tbody>';
    foreach ($configs as $cfg) {
        $cType = $cfg['config'] ?? $cfg['type'] ?? $cfg['size'] ?? 'Configuration';
        $cArea = $cfg['area'] ?? $cfg['typology'] ?? '';
        $cPrice = $cfg['price'] ?? '';
        $bodyHtml .= '<tr><td>' . pdfp_esc($cType) . '</td><td>' . pdfp_esc($cArea) . '</td><td>' . pdfp_esc($cPrice) . '</td></tr>';
    }
    $bodyHtml .= '</tbody></table>';
}

if (!empty($usp_items)) {
    $bodyHtml .= '<div class="section-title">PROJECT HIGHLIGHTS</div>';
    foreach ($usp_items as $usp) {
        $bodyHtml .= '<div class="highlight-item">' . pdfp_esc($usp) . '</div>';
    }
}

$bodyHtml .= '<div class="section-title">PREMIUM AMENITIES</div>';
$bodyHtml .= '<div class="amenity-grid">';
foreach ($amenities as $am) {
    $bodyHtml .= '<div class="amenity-item">' . pdfp_esc($am) . '</div>';
}
$bodyHtml .= '</div>';

$bodyHtml .= '<div class="section-title">LOCATION ADVANTAGES</div>';
$bodyHtml .= '<table class="loc-table">';
foreach ($locationAdvantages as $loc) {
    $bodyHtml .= '<tr><td class="place">' . pdfp_esc($loc[0]) . '</td><td class="dist">' . pdfp_esc($loc[1]) . '</td></tr>';
}
$bodyHtml .= '</table>';

if ($lat !== null && $lng !== null) {
    $bodyHtml .= '<div class="section-title">MAP COORDINATES</div>';
    $bodyHtml .= '<table class="info-table">';
    $bodyHtml .= '<tr><td class="label">Latitude</td><td>' . pdfp_esc($lat) . '</td></tr>';
    $bodyHtml .= '<tr><td class="label">Longitude</td><td>' . pdfp_esc($lng) . '</td></tr>';
    $bodyHtml .= '</table>';
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

$safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $title ?: 'Project');
$filename = $safeName . '_Details.pdf';

while (ob_get_level()) {
    ob_end_clean();
}
$dompdf->stream($filename, array('Attachment' => true));
exit();