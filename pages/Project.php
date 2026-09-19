<?php
/**
 * project.php — Projects Listing Page
 * Realty Smartz Pathshala
 *
 * ── PRESERVED FUNCTIONALITY (unchanged from original project.php) ──────────
 *   - session_start()
 *   - Auth guard: redirects to ../auth/login.html if user is not logged in
 *   - $userName resolution from $_SESSION['user_name'] / $_SESSION['user_email']
 *   - Shared includes/navbar.php and includes/footer.php (same pattern as about.php)
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Only the FRONTEND/UI below the auth guard has been redesigned.
 * All project data is sourced exclusively from Project_Details_Merged.xlsx
 * (see inc/projects-data.php, which is a direct, non-fabricated export of
 * that workbook — 16 unique projects, de-duplicated across its two sheets,
 * multi-row configurations merged into one record per project).
 */

session_start();

if (!isset($_SESSION['user_email'])) {
    header("Location: ../auth/login.html");
    exit;
}

$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name']
    : (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null);

/* ─────────────────────────────────────────────────────────────────────────
   DATA LOAD
   ───────────────────────────────────────────────────────────────────────── */
$PROJECTS = require __DIR__ . '/inc/projects_data_db.php';
require_once __DIR__ . '/../includes/maps_helper.php';


/* ─────────────────────────────────────────────────────────────────────────
   HELPERS
   All helpers are pure/derived from the Excel fields only — nothing here
   invents project facts. Where source data is missing, output stays empty.
   ───────────────────────────────────────────────────────────────────────── */

// Known builder brand prefixes present in the dataset (used only to split
// "Builder" from "Project Name" for display — does not alter the data).
function rsp_builder($title)
{
    $builders = ['Smart World', 'Emaar', 'Adani', 'Signature Global', 'Godrej', 'Sobha', 'Ashiana', 'Titanium', 'Experion'];
    foreach ($builders as $b) {
        if (stripos($title, $b) === 0)
            return $b;
    }
    $parts = explode(' ', $title);
    return $parts[0];
}

function rsp_slug($title, $sector)
{
    $s = strtolower(trim($title . '-' . $sector));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function rsp_location_label($loc)
{
    $loc = trim($loc);
    $loc = str_replace(['Golf-Course', 'Exp.', 'Rd.'], ['Golf Course', 'Expressway', 'Road'], $loc);
    return trim(preg_replace('/\s+/', ' ', $loc));
}

function rsp_location_key($loc)
{
    $label = rsp_location_label($loc);
    // Group the Manesar micro-market string down to just "Manesar"
    if (stripos($label, 'Manesar') !== false)
        $label = 'Manesar';
    $key = strtolower($label);
    $key = preg_replace('/[^a-z0-9]+/', '-', $key);
    return trim($key, '-');
}

function rsp_possession_display($raw)
{
    if (!$raw)
        return '';
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw)) {
        $ts = strtotime($raw);
        return $ts ? date('M Y', $ts) : $raw;
    }
    if (preg_match('/^(\d{4})\s+([A-Za-z]+)\.?$/', trim($raw), $m)) {
        return substr($m[2], 0, 3) . ' ' . $m[1];
    }
    if (preg_match('/^\d{4}$/', trim($raw))) {
        return 'Year ' . trim($raw);
    }
    return $raw;
}

function rsp_possession_year($raw)
{
    if ($raw && preg_match('/(20\d{2})/', $raw, $m))
        return (int) $m[1];
    return null;
}

function rsp_status($year)
{
    $currentYear = (int) date('Y');
    if ($year === null)
        return 'Under Construction';
    if ($year <= $currentYear)
        return 'Ready to Move';
    if ($year - $currentYear <= 1)
        return 'Nearing Possession';
    return 'Under Construction';
}

function rsp_price_label($min, $max)
{
    if ($min === null && $max === null)
        return 'Price on Request';
    if ($min === null)
        $min = $max;
    if ($max === null)
        $max = $min;
    if ($min == $max)
        return '₹' . rtrim(rtrim(number_format($min, 2), '0'), '.') . ' Cr*';
    return '₹' . rtrim(rtrim(number_format($min, 2), '0'), '.') . ' - ' . rtrim(rtrim(number_format($max, 2), '0'), '.') . ' Cr*';
}

function rsp_initials($title)
{
    $words = preg_split('/\s+/', trim($title));
    $letters = '';
    foreach ($words as $w) {
        if (ctype_alpha(substr($w, 0, 1)))
            $letters .= strtoupper($w[0]);
        if (strlen($letters) >= 2)
            break;
    }
    return $letters ?: 'RS';
}

// Never-broken placeholder image (inline SVG data URI). Used only as a
// fallback if a real photo hasn't been added yet at the given path.
function rsp_placeholder_svg($initials)
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0%" stop-color="%230f0f25"/><stop offset="100%" stop-color="%231a1a3e"/>'
        . '</linearGradient></defs>'
        . '<rect width="800" height="600" fill="url(%23g)"/>'
        . '<text x="400" y="330" font-family="Georgia,serif" font-size="140" font-weight="700" '
        . 'fill="%23C9933A" fill-opacity="0.55" text-anchor="middle">' . $initials . '</text>'
        . '</svg>';
    return 'data:image/svg+xml;utf8,' . $svg;
}

/* ─────────────────────────────────────────────────────────────────────────
   BUILD DISPLAY-READY PROJECT LIST (derived fields only, source data intact)
   ───────────────────────────────────────────────────────────────────────── */
$projects = [];
foreach ($PROJECTS as $p) {
    $slug = rsp_slug($p['title'], $p['sector_raw']);
    $builder = rsp_builder($p['title']);
    $locLabel = rsp_location_label($p['location']);
    $locKey = rsp_location_key($p['location']);
    $possYear = rsp_possession_year($p['possession_raw']);
    $status = rsp_status($possYear);
    $priceLabel = rsp_price_label($p['min_price'], $p['max_price']);
    $isLuxury = $p['min_price'] !== null && $p['min_price'] >= 5.0;
    $isAfford = $p['min_price'] !== null && $p['min_price'] < 2.0;
    $isNewLaunch = $possYear !== null && $possYear >= 2032;
    $isFeatured = $p['land_num'] !== null && $p['land_num'] >= 14.5;
    $uspLines = [];
    foreach (preg_split('/\r\n|\r|\n/', trim($p['usp_raw'])) as $line) {
        $line = trim(preg_replace('/^\d+\.\s*/', '', $line));
        if ($line !== '')
            $uspLines[] = $line;
    }

    $tags = ['all', $locKey];
    if ($isFeatured)
        $tags[] = 'featured';
    if ($isLuxury)
        $tags[] = 'luxury';
    if ($isAfford)
        $tags[] = 'affordable';
    if ($isNewLaunch)
        $tags[] = 'new-launch';

    $projects[] = [
        'slug' => $slug,
        'image' => (!empty($p['cover_image']) && file_exists(__DIR__ . '/../assets/img/projects/' . $p['cover_image']))
            ? $p['cover_image']
            : '',
        'title' => $p['title'],
        'builder' => $builder,
        'sector' => $p['sector_raw'],
        'location' => $locLabel,
        'location_key' => $locKey,
        'lat' => $p['lat'] ?? null,
        'lng' => $p['lng'] ?? null,
        'land' => $p['land'],
        'towers' => $p['towers'],
        'height' => $p['height'],
        'clubhouse' => $p['clubhouse'],
        'possession' => rsp_possession_display($p['possession_raw']),
        'possession_year' => $possYear,
        'payment_plan' => $p['payment_plan'],
        'typologies' => $p['typologies'],
        'configs' => $p['configs'],
        'min_price' => $p['min_price'],
        'max_price' => $p['max_price'],
        'price_label' => $priceLabel,
        'status' => $status,
        'featured' => $isFeatured,
        'luxury' => $isLuxury,
        'affordable' => $isAfford,
        'new_launch' => $isNewLaunch,
        'usp_lines' => $uspLines,
        'initials' => rsp_initials($p['title']),
        'tags' => implode(' ', array_unique($tags)),
    ];
}

// Location filter chips, generated from whatever micro-markets actually
// exist in the data (never hardcoded).
$locationChips = [];
foreach ($projects as $p) {
    $locationChips[$p['location_key']] = $p['location'];
}
asort($locationChips);

// Map marker data — reuses the SAME $projects array above (single source of truth).
// Only the fields needed for the popup are kept to minimize payload size.
$mapMarkers = [];
foreach ($projects as $p) {
    if ($p['lat'] === null || $p['lng'] === null)
        continue;
    $mapMarkers[] = [
        'title' => $p['title'],
        'builder' => $p['builder'],
        'sector' => $p['sector'],
        'location' => $p['location'],
        'desc' => !empty($p['usp_lines']) ? $p['usp_lines'][0] : '',
        'lat' => $p['lat'],
        'lng' => $p['lng'],
        'url' => 'project-details.php?slug=' . urlencode($p['slug']),
        'maps_link' => $p['google_maps_link'] ?? '',
    ];
}
?>
<!doctype html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Our Projects | Realty Smartz Pathshala</title>
    <meta name="description"
        content="Explore premium real estate projects across Gurgaon curated by Realty Smartz Pathshala.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="../assets/css/animate.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/layout.css">

    <!-- Leaflet (Interactive Gurugram Map — added, does not affect existing styles) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <style>
        .proj-page-wrapper {
            padding-top: 100px;
            background: var(--bg-primary, #0a0a1a);
            min-height: 100vh;
        }

        .proj-hero {
            position: relative;
            padding: 80px 0 60px;
            text-align: center;
            overflow: hidden;
            background: var(--bg-secondary, #0f0f25);
            border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
        }

        .proj-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(201, 147, 58, 0.10) 0%, transparent 70%);
            pointer-events: none;
        }

        .proj-hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 700;
            color: var(--gold, #C9933A);
            margin-bottom: 12px;
            position: relative;
        }

        .proj-hero-sub {
            color: var(--text-secondary, rgba(255, 255, 255, 0.70));
            font-size: 15px;
            max-width: 560px;
            margin: 0 auto 18px;
            position: relative;
        }

        .proj-breadcrumb {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.88rem;
            color: var(--text-secondary, rgba(255, 255, 255, 0.70));
            position: relative;
        }

        .proj-breadcrumb a {
            color: var(--gold, #C9933A);
            text-decoration: none;
        }

        .proj-breadcrumb a:hover {
            opacity: .75;
        }

        .proj-breadcrumb .sep {
            opacity: .5;
        }

        .proj-toolbar {
            padding: 44px 0 8px;
        }

        .proj-toolbar-row {
            display: flex;
            gap: 14px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 26px;
        }

        .proj-search-wrap {
            position: relative;
            flex: 1 1 320px;
            min-width: 260px;
        }

        .proj-search-wrap i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gold, #C9933A);
            font-size: 14px;
            pointer-events: none;
        }

        .proj-search-input {
            width: 100%;
            padding: 13px 18px 13px 44px;
            border-radius: 12px;
            background: var(--card-bg, rgba(255, 255, 255, 0.05));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.10));
            color: var(--text-primary, #fff);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: border-color .25s, box-shadow .25s;
        }

        .proj-search-input::placeholder {
            color: var(--text-secondary, rgba(255, 255, 255, 0.5));
        }

        .proj-search-input:focus {
            outline: none;
            border-color: var(--gold, #C9933A);
            box-shadow: 0 0 0 3px rgba(201, 147, 58, 0.15);
        }

        .proj-sort-wrap {
            position: relative;
            display: inline-block;
        }

        /* Real <select> stays in the DOM for JS compatibility, just invisible */
        .proj-sort-select--native {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            pointer-events: none;
        }

        .proj-sort-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 13px 18px;
            border-radius: 12px;
            background: var(--card-bg, rgba(255, 255, 255, 0.05));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.10));
            color: var(--text-primary, #fff);
            font-size: 13.5px;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            min-width: 220px;
            text-align: left;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .proj-sort-trigger::after {
            content: '';
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23C9933A' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 16px;
            transition: transform 0.2s ease;
        }

        .proj-sort-wrap.is-open .proj-sort-trigger::after {
            transform: rotate(180deg);
        }

        .proj-sort-trigger:hover,
        .proj-sort-trigger:focus-visible {
            border-color: var(--gold, #C9933A);
            outline: none;
        }

        .proj-sort-wrap.is-open .proj-sort-trigger {
            border-color: var(--gold, #C9933A);
        }

        .proj-sort-list {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            z-index: 50;
            margin: 0;
            padding: 6px;
            list-style: none;
            background: #141427;
            border: 1px solid rgba(201, 147, 58, 0.35);
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.45);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s;
            max-height: 280px;
            overflow-y: auto;
        }

        .proj-sort-wrap.is-open .proj-sort-list {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .proj-sort-option {
            padding: 10px 14px;
            border-radius: 8px;
            color: #fff;
            font-size: 13.5px;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
            white-space: nowrap;
        }

        .proj-sort-option:hover,
        .proj-sort-option.is-active {
            background: rgba(201, 147, 58, 0.16);
            color: var(--gold, #C9933A);
        }

        .proj-sort-option.is-selected {
            color: var(--gold, #C9933A);
            font-weight: 600;
        }

        .proj-filters {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .proj-filter-group-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--text-secondary, rgba(255, 255, 255, 0.5));
            margin-right: 4px;
        }

        .proj-filter-btn {
            padding: 8px 20px;
            border-radius: 100px;
            font-size: 12.5px;
            font-weight: 600;
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.10));
            background: var(--card-bg, rgba(255, 255, 255, 0.05));
            color: var(--text-secondary, rgba(255, 255, 255, 0.65));
            cursor: pointer;
            transition: all .25s;
            font-family: 'Inter', sans-serif;
        }

        .proj-filter-btn:hover {
            border-color: var(--gold, #C9933A);
            color: var(--gold, #C9933A);
        }

        .proj-filter-btn.active {
            background: linear-gradient(135deg, var(--gold, #C9933A), var(--gold-light, #e8b86d));
            color: #0a0a1a;
            border-color: transparent;
            box-shadow: 0 6px 18px rgba(201, 147, 58, 0.35);
        }

        .proj-results-count {
            color: var(--text-secondary, rgba(255, 255, 255, 0.55));
            font-size: 13px;
            margin: 4px 0 30px;
        }

        .proj-results-count strong {
            color: var(--gold, #C9933A);
        }

        .proj-grid-section {
            padding: 10px 0 90px;
        }

        .proj-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 26px;
        }

        .proj-card {
            background: var(--card-bg, rgba(255, 255, 255, 0.05));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.10));
            border-radius: 20px;
            overflow: hidden;
            transition: transform .4s cubic-bezier(.23, 1, .32, 1), box-shadow .4s ease, border-color .4s ease;
            display: flex;
            flex-direction: column;
        }

        .proj-card:hover {
            transform: translateY(-8px);
            border-color: rgba(201, 147, 58, 0.40);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.35);
        }

        .proj-card-media {
            position: relative;
            aspect-ratio: 4/3;
            overflow: hidden;
            background: #10102a;
        }

        .proj-card-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .6s cubic-bezier(.23, 1, .32, 1);
        }

        .proj-card:hover .proj-card-media img {
            transform: scale(1.08);
        }

        .proj-card-media::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(5, 5, 18, 0.85) 0%, rgba(5, 5, 18, 0.05) 55%, transparent 100%);
        }

        .proj-card-badges {
            position: absolute;
            top: 14px;
            left: 14px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            z-index: 2;
            max-width: 80%;
        }

        .proj-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(10, 10, 26, 0.70);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(201, 147, 58, 0.35);
            color: var(--gold, #C9933A);
            font-size: 10px;
            font-weight: 700;
            padding: 5px 11px;
            border-radius: 100px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .proj-wishlist-btn {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 2;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(10, 10, 26, 0.60);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            transition: all .25s;
        }

        .proj-wishlist-btn:hover {
            border-color: var(--gold, #C9933A);
            color: var(--gold, #C9933A);
        }

        .proj-wishlist-btn.active {
            color: #ef4444;
            border-color: #ef4444;
        }

        .proj-card-loc-chip {
            position: absolute;
            bottom: 14px;
            left: 14px;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(10, 10, 26, 0.55);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.85);
            font-size: 11px;
            font-weight: 500;
            padding: 5px 12px;
            border-radius: 100px;
        }

        .proj-card-loc-chip i {
            color: var(--gold, #C9933A);
        }

        .proj-card-body {
            padding: 22px 22px 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .proj-card-builder {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--gold, #C9933A);
            margin-bottom: 4px;
        }

        .proj-card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary, #fff);
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .proj-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 10px;
            font-size: 12px;
            color: var(--text-secondary, rgba(255, 255, 255, 0.6));
            margin-bottom: 12px;
        }

        .proj-card-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .proj-card-meta i {
            color: var(--gold, #C9933A);
            font-size: 11px;
        }

        .proj-card-configs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 14px;
        }

        .proj-config-chip {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            background: rgba(201, 147, 58, 0.08);
            border: 1px solid rgba(201, 147, 58, 0.20);
            color: var(--text-secondary, rgba(255, 255, 255, 0.75));
        }

        .proj-card-usp {
            font-size: 12.5px;
            color: var(--text-secondary, rgba(255, 255, 255, 0.55));
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .proj-card-footer {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            gap: 10px;
        }

        .proj-card-price {
            display: flex;
            flex-direction: column;
        }

        .proj-card-price-label {
            font-size: 10px;
            color: var(--text-secondary, rgba(255, 255, 255, 0.5));
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .proj-card-price-val {
            font-family: 'Playfair Display', serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--gold, #C9933A);
        }

        .proj-card-cta {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: linear-gradient(135deg, var(--gold, #C9933A), var(--gold-light, #e8b86d));
            color: #0a0a1a !important;
            font-size: 12.5px;
            font-weight: 700;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            white-space: nowrap;
            transition: all .3s;
            box-shadow: 0 4px 16px rgba(201, 147, 58, 0.30);
        }

        .proj-card-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 26px rgba(201, 147, 58, 0.50);
        }

        .proj-status-line {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11.5px;
            color: var(--text-secondary, rgba(255, 255, 255, 0.6));
            margin-bottom: 14px;
        }

        .proj-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--gold, #C9933A);
        }

        .proj-empty-state {
            display: none;
            text-align: center;
            padding: 80px 20px;
            color: var(--text-secondary, rgba(255, 255, 255, 0.55));
        }

        .proj-empty-state i {
            font-size: 2.4rem;
            color: var(--gold, #C9933A);
            margin-bottom: 16px;
            display: block;
        }

        .proj-empty-state.show {
            display: block;
        }

        .proj-grid.hidden-by-empty {
            display: none;
        }

        [data-theme="light"] .proj-hero {
            background: #f5f5f0;
        }

        [data-theme="light"] .proj-card {
            background: #fff;
            border-color: rgba(0, 0, 0, 0.08);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        [data-theme="light"] .proj-search-input,
        [data-theme="light"] .proj-sort-select,
        [data-theme="light"] .proj-filter-btn {
            background: rgba(0, 0, 0, 0.03);
            border-color: rgba(0, 0, 0, 0.10);
            color: #0f0f25;
        }

        [data-theme="light"] .proj-filter-btn {
            color: #444;
        }

        /* ===========================
   PROJECT PAGE - LIGHT THEME
=========================== */

        [data-theme="light"] .proj-page-wrapper {
            background: #f7f7f3;
        }

        [data-theme="light"] .proj-toolbar,
        [data-theme="light"] .proj-grid-section {
            background: #f7f7f3;
        }

        [data-theme="light"] .proj-hero {
            background: #f7f7f3;
            border-bottom: 1px solid rgba(0, 0, 0, .08);
        }

        [data-theme="light"] .proj-hero-title {
            color: #C9933A;
        }

        [data-theme="light"] .proj-hero-sub,
        [data-theme="light"] .proj-breadcrumb {
            color: #555;
        }

        [data-theme="light"] .proj-search-input {
            background: #fff;
            color: #111;
            border: 1px solid rgba(0, 0, 0, 0.12);
        }

        [data-theme="light"] .proj-sort-trigger {
            background: #fff;
            color: #111;
            border: 1px solid rgba(0, 0, 0, 0.12);
        }

        [data-theme="light"] .proj-sort-list {
            background: #fff;
            border-color: rgba(201, 147, 58, 0.35);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
        }

        [data-theme="light"] .proj-sort-option {
            color: #111;
        }

        [data-theme="light"] .proj-search-input::placeholder {
            color: #777;
        }

        [data-theme="light"] .proj-filter-group-label {
            color: #666;
        }

        [data-theme="light"] .proj-filter-btn {
            background: #fff;
            color: #333;
            border: 1px solid rgba(0, 0, 0, .12);
        }

        [data-theme="light"] .proj-filter-btn:hover {
            color: #C9933A;
            border-color: #C9933A;
        }

        [data-theme="light"] .proj-filter-btn.active {
            background: linear-gradient(135deg, #C9933A, #E8B86D);
            color: #111;
            border-color: transparent;
        }

        [data-theme="light"] .proj-results-count {
            color: #555;
        }

        [data-theme="light"] .proj-results-count strong {
            color: #C9933A;
        }

        [data-theme="light"] .proj-card {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .08);
            box-shadow: 0 8px 25px rgba(0, 0, 0, .08);
        }

        [data-theme="light"] .proj-card-title {
            color: #111;
        }

        [data-theme="light"] .proj-card-meta,
        [data-theme="light"] .proj-card-usp,
        [data-theme="light"] .proj-status-line {
            color: #666;
        }

        [data-theme="light"] .proj-config-chip {
            background: #faf6ef;
            border: 1px solid rgba(201, 147, 58, .25);
            color: #444;
        }

        [data-theme="light"] .proj-card-footer {
            border-top: 1px solid rgba(0, 0, 0, .08);
        }

        [data-theme="light"] .proj-card-price-label {
            color: #777;
        }

        [data-theme="light"] .proj-empty-state {
            color: #666;
        }

        [data-theme="light"] .proj-empty-state h3 {
            color: #111 !important;
        }

        @media (max-width: 1199px) {
            .proj-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 767px) {
            .proj-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .proj-toolbar-row {
                flex-direction: column;
                align-items: stretch;
            }

            .proj-search-wrap {
                flex-basis: auto;
            }

            .proj-filters {
                gap: 8px;
            }

            .proj-filter-btn {
                padding: 7px 15px;
                font-size: 11.5px;
            }

            .proj-hero {
                padding: 60px 0 44px;
            }
        }

        /* ═══════════════════════════════
           INTERACTIVE GURUGRAM MAP (added)
        ═══════════════════════════════ */
        .proj-map-section {
            padding: 10px 0 50px;
        }

        .proj-map-card {
            background: var(--card-bg, rgba(255, 255, 255, 0.05));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.10));
            border-radius: 20px;
            overflow: hidden;
        }

        .proj-map-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
        }

        .proj-map-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary, #fff);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .proj-map-title i {
            color: var(--gold, #C9933A);
        }

        .proj-map-sub {
            font-size: 12.5px;
            color: var(--text-secondary, rgba(255, 255, 255, 0.55));
            margin: 0;
        }

        #projMap {
            width: 100%;
            height: 460px;
            background: #10102a;
        }

        .proj-map-popup {
            font-family: 'Inter', sans-serif;
            min-width: 200px;
        }

        .proj-map-popup h4 {
            font-family: 'Playfair Display', serif;
            font-size: 15px;
            font-weight: 700;
            margin: 0 0 4px;
            color: #1a1a1a;
        }

        .proj-map-popup .pmp-builder {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #C9933A;
            margin-bottom: 6px;
        }

        .proj-map-popup .pmp-meta {
            font-size: 12px;
            color: #555;
            margin-bottom: 6px;
        }

        .proj-map-popup .pmp-desc {
            font-size: 12px;
            color: #666;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .proj-map-popup .pmp-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #C9933A, #e8b86d);
            color: #0a0a1a !important;
            font-size: 12px;
            font-weight: 700;
            padding: 7px 14px;
            border-radius: 7px;
            text-decoration: none;
        }

        .leaflet-popup-content-wrapper {
            border-radius: 12px;
        }

        [data-theme="light"] .proj-map-card {
            background: #fff;
            border-color: rgba(0, 0, 0, 0.08);
            box-shadow: 0 8px 25px rgba(0, 0, 0, .08);
        }

        [data-theme="light"] .proj-map-title {
            color: #111;
        }

        [data-theme="light"] .proj-map-sub {
            color: #666;
        }

        @media (max-width: 767px) {
            #projMap {
                height: 360px;
            }
        }
    </style>
</head>

<body>

    <?php include '../includes/navbar.php'; ?>

    <div class="proj-page-wrapper">

        <section class="proj-hero">
            <div class="container">
                <h1 class="proj-hero-title">Our Projects</h1>
                <p class="proj-hero-sub">Handpicked premium real estate projects across Gurgaon's top micro-markets —
                    curated for our professionals and clients.</p>
                <nav class="proj-breadcrumb" aria-label="breadcrumb">
                    <a href="../index.php"><i class="fas fa-home"></i> Home</a>
                    <span class="sep">/</span>
                    <span>Projects</span>
                </nav>
            </div>
        </section>

        <section class="proj-toolbar">
            <div class="container">
                <div class="proj-toolbar-row">
                    <div class="proj-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="projSearch" class="proj-search-input"
                            placeholder="Search by project, builder, sector, location or configuration…"
                            aria-label="Search projects">
                    </div>
                    <div class="proj-sort-wrap" id="projSortWrap">
                        <select id="projSort" class="proj-sort-select proj-sort-select--native" aria-hidden="true"
                            tabindex="-1">
                            <option value="featured">Sort: Featured First</option>
                            <option value="price-low">Price: Low to High</option>
                            <option value="price-high">Price: High to Low</option>
                            <option value="possession">Possession: Soonest First</option>
                            <option value="newest">Newest Listed</option>
                            <option value="alpha">Alphabetical (A-Z)</option>
                        </select>

                        <button type="button" class="proj-sort-trigger" id="projSortTrigger" aria-haspopup="listbox"
                            aria-expanded="false" aria-label="Sort projects">
                            <span id="projSortLabel">Sort: Featured First</span>
                        </button>

                        <ul class="proj-sort-list" id="projSortList" role="listbox" tabindex="-1">
                            <li class="proj-sort-option is-selected" role="option" aria-selected="true"
                                data-value="featured">Sort: Featured First</li>
                            <li class="proj-sort-option" role="option" aria-selected="false" data-value="price-low">
                                Price: Low to High</li>
                            <li class="proj-sort-option" role="option" aria-selected="false" data-value="price-high">
                                Price: High to Low</li>
                            <li class="proj-sort-option" role="option" aria-selected="false" data-value="possession">
                                Possession: Soonest First</li>
                            <li class="proj-sort-option" role="option" aria-selected="false" data-value="newest">Newest
                                Listed</li>
                            <li class="proj-sort-option" role="option" aria-selected="false" data-value="alpha">
                                Alphabetical (A-Z)</li>
                        </ul>
                    </div>
                </div>

                <div class="proj-filters" id="projFiltersStatus">
                    <span class="proj-filter-group-label">Show:</span>
                    <button class="proj-filter-btn active" data-filter="all">All Projects</button>
                    <button class="proj-filter-btn" data-filter="featured">Featured</button>
                    <button class="proj-filter-btn" data-filter="luxury">Luxury</button>
                    <button class="proj-filter-btn" data-filter="affordable">Affordable</button>
                    <button class="proj-filter-btn" data-filter="new-launch">New Launch</button>
                </div>

                <?php if (!empty($locationChips)): ?>
                    <div class="proj-filters" id="projFiltersLocation">
                        <span class="proj-filter-group-label">Micro-Market:</span>
                        <button class="proj-filter-btn active" data-loc="all">All Locations</button>
                        <?php foreach ($locationChips as $key => $label): ?>
                            <button class="proj-filter-btn"
                                data-loc="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p class="proj-results-count"><strong id="projCount"><?php echo count($projects); ?></strong> projects
                    found</p>
            </div>
        </section>

        <section class="proj-map-section">
            <div class="container">
                <div class="proj-map-card">
                    <div class="proj-map-head">
                        <h2 class="proj-map-title"><i class="fas fa-map-marked-alt"></i> Interactive Gurugram Map</h2>
                        <p class="proj-map-sub">Explore all <?php echo count($mapMarkers); ?> projects by location</p>
                    </div>
                    <div id="projMap"></div>
                </div>
            </div>
        </section>

        <section class="proj-grid-section">
            <div class="container">
                <div class="proj-grid" id="projGrid">
                    <?php foreach ($projects as $p): ?>
                        <?php
                        $imgPath = '../assets/img/projects/' . $p['image'];
                        $imgFile = __DIR__ . '/../../assets/img/projects/' . $p['image'];
                        $imgVersion = file_exists($imgFile) ? filemtime($imgFile) : time();
                        $img = $imgPath . '?v=' . $imgVersion;
                        $placeholder = rsp_placeholder_svg($p['initials']);
                        $searchBlob = strtolower($p['title'] . ' ' . $p['builder'] . ' ' . $p['location'] . ' ' . $p['sector'] . ' ' . implode(' ', $p['typologies']));
                        ?>
                        <article class="proj-card" data-tags="<?php echo htmlspecialchars($p['tags']); ?>"
                            data-loc="<?php echo htmlspecialchars($p['location_key']); ?>"
                            data-search="<?php echo htmlspecialchars($searchBlob); ?>"
                            data-price-min="<?php echo $p['min_price'] !== null ? $p['min_price'] : ''; ?>"
                            data-possession-year="<?php echo $p['possession_year'] !== null ? $p['possession_year'] : ''; ?>"
                            data-title="<?php echo htmlspecialchars($p['title']); ?>"
                            data-featured="<?php echo $p['featured'] ? '1' : '0'; ?>">

                            <div class="proj-card-media">
                                <div class="proj-card-badges">
                                    <?php if ($p['featured']): ?><span class="proj-badge"><i class="fas fa-star"></i>
                                            Featured</span><?php endif; ?>
                                    <?php if ($p['luxury']): ?><span class="proj-badge"><i class="fas fa-gem"></i>
                                            Luxury</span><?php endif; ?>
                                    <?php if ($p['new_launch']): ?><span class="proj-badge"><i class="fas fa-leaf"></i> New
                                            Launch</span><?php endif; ?>
                                    <?php if ($p['affordable']): ?><span class="proj-badge"><i class="fas fa-home"></i>
                                            Affordable</span><?php endif; ?>
                                </div>
                                <button type="button" class="proj-wishlist-btn"
                                    data-wishlist="<?php echo htmlspecialchars($p['slug']); ?>"
                                    aria-label="Save <?php echo htmlspecialchars($p['title']); ?> to wishlist">
                                    <i class="far fa-heart"></i>
                                </button>
                                <img src="<?php echo $img; ?>"
                                    onerror="this.onerror=null;this.src='<?php echo $placeholder; ?>';"
                                    alt="<?php echo htmlspecialchars($p['title'] . ', ' . $p['sector']); ?>" loading="lazy"
                                    width="800" height="600">
                                <div class="proj-card-loc-chip"><i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($p['location']); ?></div>
                            </div>

                            <div class="proj-card-body">
                                <div class="proj-card-builder"><?php echo htmlspecialchars($p['builder']); ?></div>
                                <h2 class="proj-card-title"><?php echo htmlspecialchars($p['title']); ?></h2>

                                <div class="proj-card-meta">
                                    <span><i class="fas fa-map-pin"></i>
                                        <?php echo htmlspecialchars($p['sector']); ?></span>
                                    <?php if ($p['land']): ?><span><i class="fas fa-vector-square"></i>
                                            <?php echo htmlspecialchars($p['land']); ?></span><?php endif; ?>
                                    <?php if ($p['height']): ?><span><i class="fas fa-building"></i>
                                            <?php echo htmlspecialchars($p['height']); ?></span><?php endif; ?>
                                </div>

                                <?php if (!empty($p['typologies'])): ?>
                                    <div class="proj-card-configs">
                                        <?php foreach ($p['typologies'] as $t): ?>
                                            <span class="proj-config-chip"><?php echo htmlspecialchars($t); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="proj-status-line">
                                    <span class="proj-status-dot"></span>
                                    <?php echo htmlspecialchars($p['status']); ?>
                                    <?php if ($p['possession']): ?>&middot; Possession:
                                        <?php echo htmlspecialchars($p['possession']); ?>     <?php endif; ?>
                                </div>

                                <?php if (!empty($p['usp_lines'])): ?>
                                    <p class="proj-card-usp"><?php echo htmlspecialchars($p['usp_lines'][0]); ?></p>
                                <?php endif; ?>

                                <div class="proj-card-footer">
                                    <div class="proj-card-price">
                                        <span class="proj-card-price-label">Starting Price</span>
                                        <span
                                            class="proj-card-price-val"><?php echo htmlspecialchars($p['price_label']); ?></span>
                                    </div>
                                    <a href="project-details.php?slug=<?php echo urlencode($p['slug']); ?>"
                                        class="proj-card-cta">
                                        View Details <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="proj-empty-state" id="projEmptyState">
                    <i class="fas fa-search"></i>
                    <h3 style="font-family:'Playfair Display',serif;color:var(--text-primary,#fff);margin-bottom:8px;">
                        No projects match your search</h3>
                    <p>Try adjusting your filters or search term.</p>
                </div>
            </div>
        </section>

    </div><!-- /.proj-page-wrapper -->

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            var grid = document.getElementById('projGrid');
            var cards = Array.prototype.slice.call(grid.querySelectorAll('.proj-card'));
            var searchInput = document.getElementById('projSearch');
            var sortSelect = document.getElementById('projSort');
            var statusBtns = document.querySelectorAll('#projFiltersStatus .proj-filter-btn');
            var locBtns = document.querySelectorAll('#projFiltersLocation .proj-filter-btn');
            var countEl = document.getElementById('projCount');
            var emptyState = document.getElementById('projEmptyState');

            var state = { search: '', status: 'all', loc: 'all', sort: 'featured' };
            // ── Custom Sort dropdown (visual layer only) ──
            // Keeps the real <select id="projSort"> as the single source of truth so
            // all existing sorting logic below (which reads/writes sortSelect) needs
            // zero changes — this just makes the UI look right and stays in sync with it.
            (function () {
                var wrap = document.getElementById('projSortWrap');
                var trigger = document.getElementById('projSortTrigger');
                var list = document.getElementById('projSortList');
                var label = document.getElementById('projSortLabel');
                var options = Array.prototype.slice.call(list.querySelectorAll('.proj-sort-option'));

                function closeList() {
                    wrap.classList.remove('is-open');
                    trigger.setAttribute('aria-expanded', 'false');
                }
                function openList() {
                    wrap.classList.add('is-open');
                    trigger.setAttribute('aria-expanded', 'true');
                }

                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    wrap.classList.contains('is-open') ? closeList() : openList();
                });

                options.forEach(function (opt) {
                    opt.addEventListener('click', function () {
                        var value = opt.getAttribute('data-value');

                        options.forEach(function (o) { o.classList.remove('is-selected'); o.setAttribute('aria-selected', 'false'); });
                        opt.classList.add('is-selected');
                        opt.setAttribute('aria-selected', 'true');
                        label.textContent = opt.textContent;

                        // Update the real <select> and fire a native change event so
                        // the existing sort logic below runs exactly as it always has.
                        sortSelect.value = value;
                        sortSelect.dispatchEvent(new Event('change', { bubbles: true }));

                        closeList();
                    });
                });

                document.addEventListener('click', function (e) {
                    if (!wrap.contains(e.target)) closeList();
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') closeList();
                });
            })();

            function applyFilters() {
                var visibleCount = 0;
                cards.forEach(function (card) {
                    var tags = (card.dataset.tags || '').split(' ');
                    var loc = card.dataset.loc || '';
                    var haystack = card.dataset.search || '';

                    var matchesStatus = state.status === 'all' || tags.indexOf(state.status) !== -1;
                    var matchesLoc = state.loc === 'all' || loc === state.loc;
                    var matchesSearch = state.search === '' || haystack.indexOf(state.search) !== -1;

                    var visible = matchesStatus && matchesLoc && matchesSearch;
                    card.style.display = visible ? '' : 'none';
                    if (visible) visibleCount++;
                });

                countEl.textContent = visibleCount;
                emptyState.classList.toggle('show', visibleCount === 0);
                grid.classList.toggle('hidden-by-empty', visibleCount === 0);
            }

            function applySort() {
                var sorted = cards.slice().sort(function (a, b) {
                    switch (state.sort) {
                        case 'price-low':
                            return (parseFloat(a.dataset.priceMin) || Infinity) - (parseFloat(b.dataset.priceMin) || Infinity);
                        case 'price-high':
                            return (parseFloat(b.dataset.priceMin) || -Infinity) - (parseFloat(a.dataset.priceMin) || -Infinity);
                        case 'possession':
                            return (parseInt(a.dataset.possessionYear) || 9999) - (parseInt(b.dataset.possessionYear) || 9999);
                        case 'newest':
                            return cards.indexOf(b) - cards.indexOf(a);
                        case 'alpha':
                            return a.dataset.title.localeCompare(b.dataset.title);
                        case 'featured':
                        default:
                            return (parseInt(b.dataset.featured) || 0) - (parseInt(a.dataset.featured) || 0);
                    }
                });
                sorted.forEach(function (card) { grid.appendChild(card); });
            }

            searchInput.addEventListener('input', function () {
                state.search = this.value.trim().toLowerCase();
                applyFilters();
            });

            sortSelect.addEventListener('change', function () {
                state.sort = this.value;
                applySort();
            });

            statusBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    statusBtns.forEach(function (b) { b.classList.remove('active'); });
                    this.classList.add('active');
                    state.status = this.dataset.filter;
                    applyFilters();
                });
            });

            locBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    locBtns.forEach(function (b) { b.classList.remove('active'); });
                    this.classList.add('active');
                    state.loc = this.dataset.loc;
                    applyFilters();
                });
            });

            var WISHLIST_KEY = 'rsp_wishlist';
            function getWishlist() {
                try { return JSON.parse(localStorage.getItem(WISHLIST_KEY)) || []; }
                catch (e) { return []; }
            }
            function setWishlist(list) {
                try { localStorage.setItem(WISHLIST_KEY, JSON.stringify(list)); } catch (e) { }
            }
            var wishlist = getWishlist();

            document.querySelectorAll('.proj-wishlist-btn').forEach(function (btn) {
                var slug = btn.dataset.wishlist;
                if (wishlist.indexOf(slug) !== -1) {
                    btn.classList.add('active');
                    btn.querySelector('i').className = 'fas fa-heart';
                }
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var idx = wishlist.indexOf(slug);
                    if (idx === -1) {
                        wishlist.push(slug);
                        btn.classList.add('active');
                        btn.querySelector('i').className = 'fas fa-heart';
                    } else {
                        wishlist.splice(idx, 1);
                        btn.classList.remove('active');
                        btn.querySelector('i').className = 'far fa-heart';
                    }
                    setWishlist(wishlist);
                });
            });

            applySort();
            applyFilters();
        })();
    </script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script>
        (function () {
            var mapEl = document.getElementById('projMap');
            if (!mapEl || typeof L === 'undefined') return;

            var markersData = <?php echo json_encode($mapMarkers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            if (!markersData.length) return;

            // Center on Gurugram
            var map = L.map('projMap', { scrollWheelZoom: false }).setView([28.4300, 77.0300], 11);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            var goldIcon = L.divIcon({
                className: 'proj-map-marker',
                html: '<div style="width:16px;height:16px;border-radius:50%;background:linear-gradient(135deg,#C9933A,#e8b86d);border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.4);"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            });

            // Marker clustering (keeps overlapping/nearby markers usable per requirement)
            var clusterGroup = (typeof L.markerClusterGroup === 'function')
                ? L.markerClusterGroup({ maxClusterRadius: 45 })
                : L.layerGroup();

            var bounds = [];

            markersData.forEach(function (p) {
                if (typeof p.lat !== 'number' || typeof p.lng !== 'number') return;

                var marker = L.marker([p.lat, p.lng], { icon: goldIcon });

                var popupHtml =
                    '<div class="proj-map-popup">' +
                    '<div class="pmp-builder">' + escapeHtml(p.builder || '') + '</div>' +
                    '<h4>' + escapeHtml(p.title || '') + '</h4>' +
                    '<div class="pmp-meta"><i class="fas fa-map-marker-alt"></i> ' + escapeHtml(p.sector || '') + ', ' + escapeHtml(p.location || '') + '</div>' +
                    (p.desc ? '<div class="pmp-desc">' + escapeHtml(p.desc) + '</div>' : '') +
                    '<a href="' + p.url + '" class="pmp-btn">View Project <i class="fas fa-arrow-right"></i></a>' +
                    (p.maps_link ? ' <a href="' + p.maps_link + '" target="_blank" rel="noopener" class="pmp-btn" style="margin-left:6px;background:transparent;border:1px solid #C9933A;color:#C9933A !important;">📍 Google Maps</a>' : '') +
                    '</div>';

                marker.bindPopup(popupHtml);
                clusterGroup.addLayer(marker);
                bounds.push([p.lat, p.lng]);
            });

            map.addLayer(clusterGroup);

            if (bounds.length) {
                map.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
            }

            // Enable scroll zoom only after user interacts (avoids trapping page scroll)
            map.on('focus', function () { map.scrollWheelZoom.enable(); });
            map.on('blur', function () { map.scrollWheelZoom.disable(); });

            function escapeHtml(str) {
                var div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }
        })();
    </script>

</body>

</html>