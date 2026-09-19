<?php
session_start();
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name']
    : (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null);

// ── Load Projects Data ─────────────────────────
require_once __DIR__ . '/../includes/db_connect.php';
$projects = require __DIR__ . '/inc/projects_data_db.php';
require_once __DIR__ . '/../includes/maps_helper.php';

// ── Get Slug from URL ──────────────────────────
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// ── Slug Generator Helper ──────────────────────
function makeSlug($title, $sector)
{
    $slug = strtolower(trim($title . '-' . $sector));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

// ── Find Matching Project ──────────────────────
$project = null;
$project_index = -1;
foreach ($projects as $i => $p) {
    if (makeSlug($p['title'], $p['sector_raw']) === $slug) {
        $project = $p;
        $project_index = $i;
        break;
    }
}

// ── 404 if not found ──────────────────────────
if (!$project) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="en" data-theme="dark">

    <head>
        <meta charset="UTF-8">
        <title>Project Not Found | Realty Smartz Pathshala</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link
            href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap"
            rel="stylesheet">
        <style>
            body {
                background: #0d0d0d;
                color: #fff;
                font-family: 'Inter', sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                text-align: center;
            }

            .err-wrap {
                padding: 40px;
            }

            .err-code {
                font-size: 8rem;
                font-family: 'Playfair Display', serif;
                color: #c9a84c;
                line-height: 1;
                margin: 0;
            }

            .err-title {
                font-size: 1.8rem;
                margin: 16px 0 10px;
            }

            .err-msg {
                color: #aaa;
                margin-bottom: 32px;
            }

            .err-btn {
                display: inline-block;
                padding: 14px 36px;
                background: linear-gradient(135deg, #c9a84c, #e6c96a);
                color: #111;
                border-radius: 50px;
                text-decoration: none;
                font-weight: 700;
                font-size: 0.95rem;
                transition: all 0.3s;
            }

            .err-btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 30px rgba(201, 168, 76, 0.4);
                color: #111;
            }
        </style>
    </head>

    <body>
        <div class="err-wrap">
            <p class="err-code">404</p>
            <h1 class="err-title">Project Not Found</h1>
            <p class="err-msg">The project you're looking for doesn't exist or has been removed.</p>
            <a href="Project.php" class="err-btn">← Back to All Projects</a>
        </div>
    </body>

    </html>
    <?php
    exit;
}

// ── Related Projects (same location, exclude current) ──
$related = [];
foreach ($projects as $i => $p) {
    if ($i === $project_index)
        continue;
    if (isset($p['location']) && strtolower($p['location']) === strtolower($project['location'])) {
        $related[] = $p;
    }
    if (count($related) >= 3)
        break;
}
// If not enough by location, fill from others
if (count($related) < 3) {
    foreach ($projects as $i => $p) {
        if ($i === $project_index)
            continue;
        $already = false;
        foreach ($related as $r) {
            if ($r['title'] === $p['title']) {
                $already = true;
                break;
            }
        }
        if (!$already)
            $related[] = $p;
        if (count($related) >= 3)
            break;
    }
}

// ── Parse USP List ─────────────────────────────
function parseUSP($usp_raw)
{
    if (empty($usp_raw))
        return [];
    // Handle numbered list like "1. point\n2. point"
    $lines = preg_split('/\r\n|\r|\n/', trim($usp_raw));
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line))
            continue;
        // Remove leading number+dot
        $line = preg_replace('/^\d+[\.\)]\s*/', '', $line);
        if (!empty($line))
            $items[] = $line;
    }
    return $items;
}

// ── Parse Configs ──────────────────────────────
function parseConfigs($configs)
{
    if (is_array($configs))
        return $configs;
    return [];
}

$usp_items = parseUSP($project['usp_raw'] ?? '');
$configs = parseConfigs($project['configs'] ?? []);
$image_path = !empty($project['cover_image'])
    ? "../assets/img/projects/{$project['cover_image']}"
    : '';
$image_exists = $image_path !== '' && file_exists(__DIR__ . '/../assets/img/projects/' . $project['cover_image']);
// ── Load dynamic gallery images for this project ──
$stmtGal = $conn->prepare("SELECT image_path, alt_text, is_featured FROM project_gallery WHERE project_id = (SELECT id FROM projects WHERE slug = ?) ORDER BY is_featured DESC, display_order ASC");
$stmtGal->bind_param("s", $slug);
$stmtGal->execute();
$dbGalleryImages = $stmtGal->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Format Price ───────────────────────────────
function formatPrice($price)
{
    if (empty($price))
        return 'On Request';
    return $price;
}

$title = htmlspecialchars($project['title'] ?? '');
$location = htmlspecialchars($project['location'] ?? '');
$sector = htmlspecialchars($project['sector_raw'] ?? '');
$land = htmlspecialchars($project['land'] ?? '');
$towers = htmlspecialchars($project['towers'] ?? '');
$height = htmlspecialchars($project['height'] ?? '');
$clubhouse = htmlspecialchars($project['clubhouse'] ?? '');
$possession = htmlspecialchars($project['possession_raw'] ?? '');
$payment = htmlspecialchars($project['payment_plan'] ?? '');
$min_price = formatPrice($project['min_price'] ?? '');
$max_price = formatPrice($project['max_price'] ?? '');
$typologies = $project['typologies'] ?? [];
$land_num = htmlspecialchars($project['land_num'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?> | Realty Smartz Pathshala</title>
    <meta name="description"
        content="Explore <?= $title ?> in <?= $sector ?>, <?= $location ?>. Premium residences starting from <?= $min_price ?>. Book your site visit today.">
    <meta name="keywords" content="<?= $title ?>, <?= $location ?>, <?= $sector ?>, luxury apartments, real estate">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="../assets/css/fontawesome-all.min.css">

    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/project-details.css">
</head>

<body>

    <?php include '../includes/navbar.php'; ?>

    <!-- ════════════════════════════════════════════
     SECTION 1 — HERO BANNER
════════════════════════════════════════════ -->
    <section class="pd-hero" id="pdHero">
        <div class="pd-hero-bg" id="heroBg"
            style="background-image: url('<?= $image_exists ? $image_path : '../assets/img/projects/default-hero.jpg' ?>');">
        </div>
        <div class="pd-hero-overlay"></div>
        <div class="pd-hero-particles" id="heroParticles"></div>

        <div class="container pd-hero-content">
            <div class="row align-items-end" style="min-height:88vh; padding-bottom:60px;">
                <div class="col-lg-8" data-reveal="left">
                    <div class="pd-hero-badge">
                        <span class="badge-dot"></span>
                        Premium Residential Project
                    </div>
                    <h1 class="pd-hero-title"><?= $title ?></h1>

                    <div class="pd-hero-meta">
                        <?php if ($sector): ?>
                            <div class="pd-meta-pill">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= $sector ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($location): ?>
                            <div class="pd-meta-pill">
                                <i class="fas fa-city"></i>
                                <?= $location ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($possession): ?>
                            <div class="pd-meta-pill">
                                <i class="fas fa-calendar-alt"></i>
                                Possession: <?= $possession ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($payment): ?>
                            <div class="pd-meta-pill">
                                <i class="fas fa-credit-card"></i>
                                <?= $payment ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pd-hero-price">
                        <span class="price-label">Starting From</span>
                        <span class="price-value"><?= $min_price ?></span>
                        <?php if ($max_price && $max_price !== $min_price): ?>
                            <span class="price-sep">—</span>
                            <span class="price-value"><?= $max_price ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="pd-hero-btns">
                        <a href="#enquiry-form" class="pd-btn-primary scroll-to">
                            <i class="fas fa-calendar-check"></i>
                            Book Site Visit
                        </a>
                        <?php if (!empty($project['brochure_path'])): ?>
                            <a href="../assets/brochures/<?= htmlspecialchars($project['brochure_path']) ?>"
                                class="pd-btn-ghost" target="_blank">
                                <i class="fas fa-file-download"></i>
                                Download Brochure
                            </a>
                        <?php endif; ?>
                        <a href="export_project_pdf.php?slug=<?= urlencode($slug) ?>" class="pd-btn-ghost"
                            target="_blank">
                            <i class="fas fa-file-pdf"></i>
                            Download Project Details
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 d-none d-lg-block" data-reveal="right">
                    <div class="pd-hero-stats">
                        <?php if ($towers): ?>
                            <div class="hero-stat">
                                <span class="stat-num" data-count="<?= intval($towers) ?>"><?= intval($towers) ?></span>
                                <span class="stat-label">Towers</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($land_num): ?>
                            <div class="hero-stat">
                                <span class="stat-num" data-count="<?= intval($land_num) ?>"><?= intval($land_num) ?></span>
                                <span class="stat-label">Acres</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($clubhouse): ?>
                            <div class="hero-stat">
                                <span class="stat-num"><?= $clubhouse ?></span>
                                <span class="stat-label">Clubhouse</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-scroll-hint">
            <div class="scroll-line"></div>
            <span>Scroll to Explore</span>
        </div>
    </section>

    <!-- ════════════════════════════════════════════
     SECTION 2 — PROJECT OVERVIEW
════════════════════════════════════════════ -->
    <section class="pd-section pd-overview" id="overview">
        <div class="container">
            <div class="pd-section-head" data-reveal="up">
                <span class="pd-label">Project Overview</span>
                <h2 class="pd-section-title"><?= $title ?></h2>
                <div class="pd-gold-line"></div>
            </div>

            <div class="row g-4 mt-2">
                <?php
                $overview_items = [
                    ['icon' => 'fas fa-map-marker-alt', 'label' => 'Location', 'value' => $location],
                    ['icon' => 'fas fa-compass', 'label' => 'Sector', 'value' => $sector],
                    ['icon' => 'fas fa-expand-arrows-alt', 'label' => 'Land Area', 'value' => $land],
                    ['icon' => 'fas fa-building', 'label' => 'Towers', 'value' => $towers],
                    ['icon' => 'fas fa-layer-group', 'label' => 'Height', 'value' => $height],
                    ['icon' => 'fas fa-swimming-pool', 'label' => 'Clubhouse', 'value' => $clubhouse],
                    ['icon' => 'fas fa-credit-card', 'label' => 'Payment Plan', 'value' => $payment],
                    ['icon' => 'fas fa-calendar-check', 'label' => 'Possession', 'value' => $possession],
                ];
                foreach ($overview_items as $idx => $item):
                    if (empty($item['value']))
                        continue;
                    ?>
                    <div class="col-6 col-md-4 col-lg-3" data-reveal="up" data-delay="<?= $idx * 80 ?>">
                        <div class="pd-overview-card">
                            <div class="ov-icon">
                                <i class="<?= $item['icon'] ?>"></i>
                            </div>
                            <div class="ov-content">
                                <span class="ov-label"><?= $item['label'] ?></span>
                                <span class="ov-value"><?= $item['value'] ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════
     SECTION 3 — CONFIGURATIONS
════════════════════════════════════════════ -->
    <?php if (!empty($configs)): ?>
        <section class="pd-section pd-configs" id="configurations">
            <div class="container">
                <div class="pd-section-head" data-reveal="up">
                    <span class="pd-label">Floor Plans</span>
                    <h2 class="pd-section-title">Unit Configurations</h2>
                    <div class="pd-gold-line"></div>
                </div>

                <div class="row g-4 mt-2 justify-content-center">
                    <?php foreach ($configs as $idx => $cfg): ?>
                        <div class="col-12 col-sm-6 col-lg-4" data-reveal="up" data-delay="<?= $idx * 100 ?>">
                            <div class="pd-config-card">
                                <div class="cfg-top">
                                    <div class="cfg-icon">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <div class="cfg-badge">Unit <?= $idx + 1 ?></div>
                                </div>
                                <h3 class="cfg-type"><?= htmlspecialchars($cfg['config'] ?? $cfg['type'] ?? 'Configuration') ?>
                                </h3>
                                <?php if (!empty($cfg['area'])): ?>
                                    <div class="cfg-detail">
                                        <i class="fas fa-ruler-combined"></i>
                                        <span><?= htmlspecialchars($cfg['area']) ?> sq.ft.</span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($cfg['price'])): ?>
                                    <div class="cfg-price"><?= htmlspecialchars($cfg['price']) ?></div>
                                <?php endif; ?>
                                <a href="#enquiry-form" class="cfg-btn scroll-to">
                                    <i class="fas fa-phone-alt"></i>
                                    Enquire Now
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ════════════════════════════════════════════
     SECTION 4 — HIGHLIGHTS / USP
════════════════════════════════════════════ -->
    <?php if (!empty($usp_items)): ?>
        <section class="pd-section pd-highlights" id="highlights">
            <div class="container">
                <div class="pd-section-head" data-reveal="up">
                    <span class="pd-label">Why Choose</span>
                    <h2 class="pd-section-title">Project Highlights</h2>
                    <div class="pd-gold-line"></div>
                </div>

                <div class="row g-3 mt-2">
                    <?php
                    $highlight_icons = [
                        'fas fa-star',
                        'fas fa-shield-alt',
                        'fas fa-leaf',
                        'fas fa-gem',
                        'fas fa-trophy',
                        'fas fa-bolt',
                        'fas fa-crown',
                        'fas fa-award',
                        'fas fa-check-circle',
                        'fas fa-rocket',
                        'fas fa-heart',
                        'fas fa-fire'
                    ];
                    foreach ($usp_items as $idx => $usp):
                        $icon = $highlight_icons[$idx % count($highlight_icons)];
                        ?>
                        <div class="col-12 col-md-6 col-lg-4" data-reveal="up" data-delay="<?= $idx * 80 ?>">
                            <div class="pd-highlight-card">
                                <div class="hl-icon">
                                    <i class="<?= $icon ?>"></i>
                                </div>
                                <p class="hl-text"><?= htmlspecialchars($usp) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ════════════════════════════════════════════
     SECTION 5 — AMENITIES
════════════════════════════════════════════ -->
    <section class="pd-section pd-amenities" id="amenities">
        <div class="container">
            <div class="pd-section-head" data-reveal="up">
                <span class="pd-label">World Class</span>
                <h2 class="pd-section-title">Premium Amenities</h2>
                <div class="pd-gold-line"></div>
            </div>

            <div class="amenities-grid mt-4" data-reveal="up">
                <?php
                $amenities = [
                    ['icon' => 'fas fa-tint', 'name' => 'Swimming Pool'],
                    ['icon' => 'fas fa-heartbeat', 'name' => 'Modern Gym'],
                    ['icon' => 'fas fa-child', 'name' => "Kids' Play Area"],
                    ['icon' => 'fas fa-road', 'name' => 'Jogging Track'],
                    ['icon' => 'fas fa-building', 'name' => 'Clubhouse'],
                    ['icon' => 'fas fa-leaf', 'name' => 'Yoga Deck'],
                    ['icon' => 'fas fa-table-tennis', 'name' => 'Tennis Court'],
                    ['icon' => 'fas fa-basketball-ball', 'name' => 'Basketball Court'],
                    ['icon' => 'fas fa-tree', 'name' => 'Landscaped Garden'],
                    ['icon' => 'fas fa-shield-alt', 'name' => '24x7 Security'],
                    ['icon' => 'fas fa-laptop', 'name' => 'Co-working Space'],
                    ['icon' => 'fas fa-bolt', 'name' => 'EV Charging'],
                    ['icon' => 'fas fa-car', 'name' => 'Ample Parking'],
                    ['icon' => 'fas fa-bell', 'name' => 'Concierge Service'],
                    ['icon' => 'fas fa-wifi', 'name' => 'High-Speed WiFi'],
                    ['icon' => 'fas fa-tree', 'name' => 'Green Spaces'],
                ];
                foreach ($amenities as $idx => $am):
                    ?>
                    <div class="amenity-card" data-reveal="up" data-delay="<?= $idx * 50 ?>">
                        <div class="am-icon-wrap">
                            <i class="<?= $am['icon'] ?>"></i>
                        </div>
                        <span class="am-name"><?= $am['name'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════
     SECTION 6 — LOCATION ADVANTAGES
════════════════════════════════════════════ -->
    <section class="pd-section pd-location" id="location">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6" data-reveal="left">
                    <span class="pd-label">Connectivity</span>
                    <h2 class="pd-section-title" style="text-align:left;">Location Advantages</h2>
                    <div class="pd-gold-line" style="margin-left:0;"></div>

                    <div class="location-timeline mt-4">
                        <?php
                        $loc_items = [
                            ['icon' => 'fas fa-road', 'place' => 'Dwarka Expressway', 'dist' => '2 Min Drive'],
                            ['icon' => 'fas fa-plane', 'place' => 'IGI Airport', 'dist' => '20 Min Drive'],
                            ['icon' => 'fas fa-subway', 'place' => 'Metro Station', 'dist' => '5 Min Walk'],
                            ['icon' => 'fas fa-university', 'place' => 'Top Schools', 'dist' => '5 Min Drive'],
                            ['icon' => 'fas fa-hospital', 'place' => 'Leading Hospitals', 'dist' => '10 Min Drive'],
                            ['icon' => 'fas fa-briefcase', 'place' => 'Cyber Hub', 'dist' => '15 Min Drive'],
                            ['icon' => 'fas fa-golf-ball', 'place' => 'Golf Course', 'dist' => '10 Min Drive'],
                            ['icon' => 'fas fa-shopping-bag', 'place' => 'Mall & Shopping', 'dist' => '8 Min Drive'],
                        ];
                        foreach ($loc_items as $idx => $loc):
                            ?>
                            <div class="loc-item" data-reveal="left" data-delay="<?= $idx * 80 ?>">
                                <div class="loc-icon">
                                    <i class="<?= $loc['icon'] ?>"></i>
                                </div>
                                <div class="loc-info">
                                    <span class="loc-place"><?= $loc['place'] ?></span>
                                    <span class="loc-dist"><?= $loc['dist'] ?></span>
                                </div>
                                <div class="loc-dot"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-lg-6" data-reveal="right">
                    <div class="location-map-wrap">
                        <div class="map-badge">
                            <i class="fas fa-map-marked-alt"></i>
                            <?= $sector ?>, <?= $location ?>
                        </div>
                        <div class="map-embed">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1514.9099369185642!2d77.03727222899911!3d28.39946832849089!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390d230e36259593%3A0x833ef7f4c569d38!2sREALTY%20SMARTZ%20PVT%20LTD!5e1!3m2!1sen!2sin!4v1753340363743!5m2!1sen!2sin"
                                width="100%" height="380" style="border:0;" allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                        <?php if (!empty($project['google_maps_link'])): ?>
                            <a href="<?= htmlspecialchars($project['google_maps_link']) ?>" target="_blank"
                                class="map-cta-btn">
                                <i class="fas fa-map-marker-alt"></i>
                                View on Google Maps
                            </a>
                            <a href="<?= htmlspecialchars(buildDirectionsUrl($project['lat'] ?? null, $project['lng'] ?? null, $sector . ', ' . $location)) ?>"
                                target="_blank" class="map-cta-btn">
                                <i class="fas fa-directions"></i>
                                Get Directions
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════
     SECTION 7 — GALLERY
════════════════════════════════════════════ -->
    <section class="pd-section pd-gallery" id="gallery">
        <div class="container">
            <div class="pd-section-head" data-reveal="up">
                <span class="pd-label">Visual Tour</span>
                <h2 class="pd-section-title">Project Gallery</h2>
                <div class="pd-gold-line"></div>
            </div>

            <div class="gallery-grid mt-4" id="galleryGrid">
                <?php if (!empty($dbGalleryImages)): ?>
                    <?php foreach ($dbGalleryImages as $gidx => $g): ?>
                        <?php
                        $gclass = $gidx === 0 ? 'gallery-item gallery-main' : 'gallery-item';
                        $gimgUrl = '../uploads/project_gallery/' . htmlspecialchars($g['image_path']);
                        $gAlt = !empty($g['alt_text']) ? htmlspecialchars($g['alt_text']) : ($title . ' - Image ' . ($gidx + 1));
                        ?>
                        <div class="<?= $gclass ?>" data-reveal="up" data-delay="<?= $gidx * 60 ?>">
                            <img src="<?= $gimgUrl ?>" alt="<?= $gAlt ?>" class="gallery-img" data-lightbox="<?= $gimgUrl ?>"
                                loading="lazy" onerror="this.parentElement.classList.add('gallery-placeholder')">
                            <div class="gallery-overlay">
                                <i class="fas fa-search-plus"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php for ($gi = 0; $gi < 6; $gi++): ?>
                        <div class="gallery-item" data-reveal="up" data-delay="<?= $gi * 60 ?>">
                            <div class="gallery-placeholder-inner">
                                <i class="fas fa-image"></i>
                                <span>Image Coming Soon</span>
                            </div>
                        </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════
     SECTION 8 — PRICING
════════════════════════════════════════════ -->
    <section class="pd-section pd-pricing" id="pricing">
        <div class="container">
            <div class="pd-section-head" data-reveal="up">
                <span class="pd-label">Investment</span>
                <h2 class="pd-section-title">Pricing Details</h2>
                <div class="pd-gold-line"></div>
            </div>

            <div class="row justify-content-center mt-4">
                <div class="col-lg-10" data-reveal="up">
                    <div class="pricing-luxury-card">
                        <div class="pricing-glow"></div>
                        <div class="row g-0 align-items-center">
                            <div class="col-md-4 pricing-left">
                                <div class="pricing-crown">
                                    <i class="fas fa-chess-king"></i>
                                </div>
                                <h3 class="pricing-project-name"><?= $title ?></h3>
                                <p class="pricing-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= $sector ?>, <?= $location ?>
                                </p>
                            </div>
                            <div class="col-md-8 pricing-right">
                                <div class="row g-4">
                                    <div class="col-sm-6">
                                        <div class="price-block">
                                            <span class="price-block-label">Starting Price</span>
                                            <span class="price-block-value"><?= $min_price ?></span>
                                        </div>
                                    </div>
                                    <?php if ($max_price && $max_price !== $min_price): ?>
                                        <div class="col-sm-6">
                                            <div class="price-block">
                                                <span class="price-block-label">Maximum Price</span>
                                                <span class="price-block-value"><?= $max_price ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($payment): ?>
                                        <div class="col-sm-6">
                                            <div class="price-block">
                                                <span class="price-block-label">Payment Plan</span>
                                                <span class="price-block-value"
                                                    style="font-size:1.1rem;"><?= $payment ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($possession): ?>
                                        <div class="col-sm-6">
                                            <div class="price-block">
                                                <span class="price-block-label">Possession</span>
                                                <span class="price-block-value"
                                                    style="font-size:1.1rem;"><?= $possession ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="pricing-cta-row mt-4">
                                    <a href="#enquiry-form" class="pd-btn-primary scroll-to">
                                        <i class="fas fa-rupee-sign"></i>
                                        Request Best Price
                                    </a>
                                    <a href="#enquiry-form" class="pd-btn-ghost scroll-to">
                                        <i class="fas fa-calendar-alt"></i>
                                        Schedule Visit
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════
     SECTION 9 — ENQUIRY FORM
════════════════════════════════════════════ -->
    <!-- <section class="pd-section pd-enquiry" id="enquiry-form">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-5" data-reveal="left">
                    <span class="pd-label">Connect With Us</span>
                    <h2 class="pd-section-title" style="text-align:left;">Book Your Site Visit</h2>
                    <div class="pd-gold-line" style="margin-left:0;"></div>
                    <p class="enquiry-desc">
                        Interested in <?= $title ?>? Our property experts will get in touch with you shortly.
                        Book a free site visit and explore your dream home today.
                    </p>
                    <div class="enquiry-trust-badges mt-4">
                        <div class="trust-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>100% Secure</span>
                        </div>
                        <div class="trust-badge">
                            <i class="fas fa-user-tie"></i>
                            <span>Expert Assistance</span>
                        </div>
                        <div class="trust-badge">
                            <i class="fas fa-phone-volume"></i>
                            <span>Quick Response</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7" data-reveal="right">
                    <div class="enquiry-form-card">
                        <div class="form-success-msg" id="formSuccess" style="display:none;">
                            <i class="fas fa-check-circle"></i>
                            <h4>Thank You!</h4>
                            <p>Our expert will contact you within 24 hours.</p>
                        </div>

                        <form id="pdEnquiryForm" class="pd-form" novalidate>
                            <input type="hidden" name="project_name" value="<?= $title ?>">
                            <input type="hidden" name="project_slug" value="<?= $slug ?>">

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="pd-form-group">
                                        <label>Full Name *</label>
                                        <input type="text" name="name" class="pd-input" placeholder="Your full name"
                                            value="<?= $userName ? htmlspecialchars($userName) : '' ?>" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="pd-form-group">
                                        <label>Phone Number *</label>
                                        <input type="tel" name="phone" class="pd-input" placeholder="+91 XXXXX XXXXX"
                                            required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="pd-form-group">
                                        <label>Email Address</label>
                                        <input type="email" name="email" class="pd-input" placeholder="you@example.com">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="pd-form-group">
                                        <label>Interested In</label>
                                        <select name="interest" class="pd-input">
                                            <option value="">Select Configuration</option>
                                            <?php if (!empty($configs)): ?>
                                                <?php foreach ($configs as $cfg): ?>
                                                    <option value="<?= htmlspecialchars($cfg['config'] ?? '') ?>">
                                                        <?= htmlspecialchars($cfg['config'] ?? '') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option>General Enquiry</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="pd-form-group">
                                        <label>Message</label>
                                        <textarea name="message" class="pd-input pd-textarea"
                                            placeholder="Tell us your requirements..." rows="4"></textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="pd-form-submit">
                                        <i class="fas fa-paper-plane"></i>
                                        Send Enquiry
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- ════════════════════════════════════════════
     SECTION 10 — RELATED PROJECTS
════════════════════════════════════════════ -->
    <?php if (!empty($related)): ?>
        <section class="pd-section pd-related" id="related">
            <div class="container">
                <div class="pd-section-head" data-reveal="up">
                    <span class="pd-label">Explore More</span>
                    <h2 class="pd-section-title">Related Projects</h2>
                    <div class="pd-gold-line"></div>
                </div>

                <div class="row g-4 mt-2">
                    <?php foreach ($related as $idx => $rp):
                        $rslug = makeSlug($rp['title'], $rp['sector_raw']);
                        $rimg = !empty($rp['cover_image'])
                            ? "../assets/img/projects/{$rp['cover_image']}"
                            : "../assets/img/projects/default-hero.jpg";
                        $rimg_exist = !empty($rp['cover_image']) && file_exists(__DIR__ . '/../assets/img/projects/' . $rp['cover_image']);
                        ?>
                        <div class="col-md-6 col-lg-4" data-reveal="up" data-delay="<?= $idx * 120 ?>">
                            <div class="related-card">
                                <div class="related-img-wrap">
                                    <img src="<?= $rimg_exist ? $rimg : '../assets/img/projects/default-hero.jpg' ?>"
                                        alt="<?= htmlspecialchars($rp['title']) ?>" class="related-img" loading="lazy"
                                        onerror="this.src='../assets/img/projects/default-hero.jpg'">
                                    <div class="related-img-overlay"></div>
                                    <?php if (!empty($rp['sector_raw'])): ?>
                                        <div class="related-badge"><?= htmlspecialchars($rp['sector_raw']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="related-body">
                                    <h3 class="related-title"><?= htmlspecialchars($rp['title']) ?></h3>
                                    <div class="related-meta">
                                        <?php if (!empty($rp['location'])): ?>
                                            <span><i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($rp['location']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($rp['min_price'])): ?>
                                            <span><i class="fas fa-rupee-sign"></i> <?= htmlspecialchars($rp['min_price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="project-details.php?slug=<?= $rslug ?>" class="related-btn">
                                        View Details <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ════════════════════════════════════════════
     LIGHTBOX MODAL
════════════════════════════════════════════ -->
    <div class="pd-lightbox" id="pdLightbox">
        <div class="lightbox-backdrop" id="lightboxBackdrop"></div>
        <div class="lightbox-content">
            <button class="lightbox-close" id="lightboxClose">
                <i class="fas fa-times"></i>
            </button>
            <button class="lightbox-prev" id="lightboxPrev">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="lightbox-next" id="lightboxNext">
                <i class="fas fa-chevron-right"></i>
            </button>
            <img src="" alt="" id="lightboxImg" class="lightbox-img">
        </div>
    </div>

    <!-- ════════════════════════════════════════════
     SECTION 11 — FLOATING BUTTONS
════════════════════════════════════════════ -->
    <!-- <div class="floating-actions">
        <a href="https://wa.me/911234567890?text=Hi, I'm interested in <?= urlencode($title) ?>" target="_blank"
            class="float-btn float-wa" title="WhatsApp Us">
            <i class="fab fa-whatsapp"></i>
            <span class="float-label">WhatsApp</span>
        </a>
        <a href="tel:+911234567890" class="float-btn float-call" title="Call Now">
            <i class="fas fa-phone-alt"></i>
            <span class="float-label">Call Now</span>
        </a>
        <button class="float-btn float-top" id="backToTop" title="Back to Top">
            <i class="fas fa-chevron-up"></i>
        </button>
    </div> -->

    <!-- ════════════════════════════════════════════
     STICKY ENQUIRY STRIP (Mobile)
════════════════════════════════════════════ -->
    <!-- <div class="sticky-enquiry-strip" id="stickyStrip">
        <a href="tel:+911234567890" class="sticky-btn sticky-call">
            <i class="fas fa-phone-alt"></i> Call Now
        </a>
        <a href="#enquiry-form" class="sticky-btn sticky-enquire scroll-to">
            <i class="fas fa-calendar-check"></i> Book Visit
        </a>
        <a href="https://wa.me/911234567890" target="_blank" class="sticky-btn sticky-whatsapp">
            <i class="fab fa-whatsapp"></i> WhatsApp
        </a>
    </div> -->

    <?php include '../includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/project-details.js"></script>

</body>

</html>