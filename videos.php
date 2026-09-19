<?php
session_start();
require_once __DIR__ . '/includes/db_connect.php';

$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name']
    : (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null);

/* ---------- Pagination ---------- */
$perPage = 12;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$totalRow = $conn->query("SELECT COUNT(*) AS cnt FROM youtube_videos WHERE published = 1")->fetch_assoc();
$totalVideos = (int) $totalRow['cnt'];
$totalPages = max(1, (int) ceil($totalVideos / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$stmt = $conn->prepare("SELECT title, embed_url, description, location, youtube_url
                         FROM youtube_videos
                         WHERE published = 1   
                         ORDER BY display_order ASC, id DESC
                         LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$videos = $stmt->get_result();
?>
<!doctype html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>YouTube Videos | Realty Smartz Pathshala</title>
    <meta name="description" content="Watch all our real estate videos on Realty Smartz Pathshala.">
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

    <style>
        /* ================================================
           Same design tokens & component styles as index.php
           so the video cards remain pixel-perfect.
           Only the sections actually used on this page are kept.
           ================================================ */
        :root,
        [data-theme="dark"] {
            --gold: #C9933A;
            --gold-light: #e8b86d;
            --bg-1: #0a0a1a;
            --bg-2: #0f0f25;
            --glass: rgba(255, 255, 255, 0.04);
            --glass-border: rgba(201, 147, 58, 0.18);
            --glass-hover: rgba(201, 147, 58, 0.08);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.70);
            --text-muted: rgba(255, 255, 255, 0.50);
            --text-dim: rgba(255, 255, 255, 0.30);
            --nav-bg: rgba(10, 10, 26, 0.85);
            --card-bg: rgba(255, 255, 255, 0.04);
            --card-border: rgba(255, 255, 255, 0.06);
            --section-line: rgba(201, 147, 58, 0.40);
            --footer-bg: #0a0a1a;
            --scrollbar-track: #0a0a1a;
            --shadow-card: 0 30px 80px rgba(0, 0, 0, 0.4);
            --shadow-hover: 0 40px 100px rgba(0, 0, 0, 0.5);
        }

        [data-theme="light"] {
            --bg-1: #f8f6f2;
            --bg-2: #ffffff;
            --glass: rgba(255, 255, 255, 0.70);
            --glass-border: rgba(201, 147, 58, 0.25);
            --glass-hover: rgba(201, 147, 58, 0.08);
            --text-primary: #0f0f25;
            --text-secondary: #2d2d4e;
            --text-muted: #64748b;
            --text-dim: #94a3b8;
            --nav-bg: rgba(248, 246, 242, 0.92);
            --card-bg: rgba(255, 255, 255, 0.85);
            --card-border: rgba(201, 147, 58, 0.15);
            --section-line: rgba(201, 147, 58, 0.35);
            --footer-bg: #0f0f25;
            --scrollbar-track: #f0ece4;
            --shadow-card: 0 20px 60px rgba(0, 0, 0, 0.10);
            --shadow-hover: 0 30px 80px rgba(0, 0, 0, 0.18);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-1);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.6;
            transition: background 0.4s ease, color 0.4s ease;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            max-width: 100%;
            display: block;
        }

        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-track {
            background: var(--scrollbar-track);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(var(--gold), var(--gold-light));
            border-radius: 10px;
        }

        .theme-toggle {
            position: fixed;
            bottom: 90px;
            right: 32px;
            z-index: 9999;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 1.5px solid var(--glass-border);
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            color: var(--gold);
        }

        .theme-toggle:hover {
            transform: scale(1.12) rotate(20deg);
            box-shadow: 0 12px 40px rgba(201, 147, 58, 0.40);
            border-color: var(--gold);
        }

        .theme-toggle .icon-dark {
            display: block;
        }

        .theme-toggle .icon-light {
            display: none;
        }

        [data-theme="light"] .theme-toggle .icon-dark {
            display: none;
        }

        [data-theme="light"] .theme-toggle .icon-light {
            display: block;
        }

        .rsp-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            transition: all 0.4s ease;
            background: var(--nav-bg);
            backdrop-filter: blur(24px);
            border-bottom: 1px solid var(--glass-border);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.2);
        }

        .nav-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 76px;
            position: relative;
        }

        .nav-logo img {
            height: 60px;
            filter: drop-shadow(0 0 10px rgba(201, 147, 58, 0.3));
        }

        [data-theme="light"] .nav-logo img {
            filter: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
        }

        .nav-links li a {
            color: var(--text-secondary);
            font-size: 13.5px;
            font-weight: 500;
            padding: 9px 16px;
            border-radius: 8px;
            transition: all 0.25s;
            display: block;
        }

        .nav-links li a:hover,
        .nav-links li.active a {
            color: var(--gold);
            background: var(--glass-hover);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-login-btn {
            color: var(--gold) !important;
            font-size: 13.5px;
            font-weight: 600;
            border: 1px solid rgba(201, 147, 58, 0.40);
            padding: 9px 22px;
            border-radius: 8px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(201, 147, 58, 0.06);
        }

        .nav-login-btn:hover {
            background: var(--gold);
            color: #fff !important;
            border-color: var(--gold);
            box-shadow: 0 0 25px rgba(201, 147, 58, 0.35);
        }

        .nav-hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: var(--glass);
            backdrop-filter: blur(10px);
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .nav-hamburger span {
            display: block;
            width: 22px;
            height: 2px;
            background: var(--gold);
            border-radius: 2px;
            transition: all 0.3s;
        }

        .nav-hamburger.open span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .nav-hamburger.open span:nth-child(2) {
            opacity: 0;
            transform: scaleX(0);
        }

        .nav-hamburger.open span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        .mobile-menu {
            display: none;
            position: fixed;
            top: 76px;
            left: 0;
            right: 0;
            background: var(--nav-bg);
            backdrop-filter: blur(30px);
            border-bottom: 1px solid var(--glass-border);
            z-index: 9998;
            padding: 16px 24px 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .mobile-menu.active {
            display: block;
        }

        .mobile-menu ul {
            list-style: none;
            padding: 0;
            margin-bottom: 16px;
        }

        .mobile-menu ul li a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 16px;
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.25s;
            border: 1px solid transparent;
        }

        .mobile-menu ul li a:hover,
        .mobile-menu ul li.active a {
            color: var(--gold);
            background: var(--glass-hover);
            border-color: var(--glass-border);
        }

        .mobile-menu-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-top: 16px;
            border-top: 1px solid var(--glass-border);
        }

        .mobile-login-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 13px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #0a0a1a !important;
            font-weight: 700;
            font-size: 14.5px;
            box-shadow: 0 6px 20px rgba(201, 147, 58, 0.35);
        }

        .rsp-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 48px;
        }

        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--gold);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .section-tag::before {
            content: '';
            width: 30px;
            height: 1.5px;
            background: linear-gradient(var(--gold), var(--gold-light));
            display: block;
        }

        .section-h {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 3.5vw, 2.8rem);
            color: var(--text-primary);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 16px;
        }

        .section-h span {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-p {
            color: var(--text-muted);
            font-size: 15.5px;
            line-height: 1.80;
            max-width: 540px;
        }

        .sec-head {
            margin-bottom: 60px;
        }

        .text-center {
            text-align: center;
        }

        .text-center .section-tag {
            justify-content: center;
        }

        .text-center .section-p {
            margin: 0 auto;
        }

        .rsp-videos-page {
            padding: 150px 0 110px;
            background: var(--bg-1);
            min-height: 60vh;
        }

        .vid-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .vid-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
        }

        .vid-card:hover {
            transform: translateY(-10px);
            border-color: rgba(201, 147, 58, 0.30);
            box-shadow: var(--shadow-hover);
        }

        .vid-frame {
            position: relative;
            padding-top: 56.25%;
            background: #000;
            overflow: hidden;
            display: block;
        }

        .vid-frame img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .vid-play-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 56px;
            height: 56px;
            background: rgba(201, 147, 58, 0.20);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(201, 147, 58, 0.55);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
            transition: all 0.3s;
            padding-left: 3px;
        }

        .vid-card:hover .vid-play-btn {
            background: var(--gold);
            border-color: var(--gold);
            color: #0a0a1a;
            transform: translate(-50%, -50%) scale(1.12);
        }

        .vid-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .vid-views {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(201, 147, 58, 0.10);
            color: var(--gold);
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 100px;
            margin-bottom: 12px;
            width: fit-content;
            border: 1px solid rgba(201, 147, 58, 0.20);
        }

        .vid-body h3 {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 700;
            line-height: 1.5;
            margin-bottom: 8px;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .vid-body p {
            color: var(--text-muted);
            font-size: 12.5px;
            line-height: 1.65;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .vid-stars i {
            color: var(--gold);
            font-size: 11px;
        }

        .btn-outline-gold {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(201, 147, 58, 0.40);
            color: var(--gold) !important;
            padding: 14px 38px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.3s;
            background: rgba(201, 147, 58, 0.05);
            backdrop-filter: blur(10px);
        }

        .btn-outline-gold:hover {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #0a0a1a !important;
            border-color: transparent;
            box-shadow: 0 10px 35px rgba(201, 147, 58, 0.40);
            transform: translateY(-4px);
        }

        .vid-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 55px;
            flex-wrap: wrap;
        }

        .vid-page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            height: 42px;
            padding: 0 12px;
            border-radius: 8px;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            color: var(--text-secondary);
            font-size: 13.5px;
            font-weight: 600;
            transition: all 0.25s;
        }

        .vid-page-link:hover {
            border-color: rgba(201, 147, 58, 0.40);
            color: var(--gold);
        }

        .vid-page-link.active {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #0a0a1a;
            border-color: transparent;
        }

        .vid-page-link.disabled {
            opacity: 0.35;
            pointer-events: none;
        }

        .rsp-footer {
            background: var(--footer-bg);
            padding: 80px 0 0;
            border-top: 1px solid rgba(201, 147, 58, 0.15);
            position: relative;
            overflow: hidden;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr;
            gap: 70px;
            padding-bottom: 60px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .footer-logo img {
            height: 48px;
            margin-bottom: 20px;
            filter: drop-shadow(0 0 12px rgba(201, 147, 58, 0.25));
        }

        .footer-about p {
            color: rgba(255, 255, 255, 0.45);
            font-size: 14.5px;
            line-height: 1.85;
            margin-bottom: 28px;
            max-width: 330px;
        }

        .footer-socials {
            display: flex;
            gap: 10px;
        }

        .footer-socials a {
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.45) !important;
            font-size: 15px;
            transition: all 0.3s;
        }

        .footer-socials a:hover {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #0a0a1a !important;
            border-color: transparent;
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(201, 147, 58, 0.35);
        }

        .footer-col h4 {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-col h4::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(var(--gold), transparent);
            opacity: 0.3;
        }

        .footer-col ul {
            list-style: none;
            padding: 0;
        }

        .footer-col ul li {
            margin-bottom: 14px;
        }

        .footer-col ul li a {
            color: rgba(255, 255, 255, 0.45);
            font-size: 14px;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-col ul li a i {
            color: var(--gold);
            font-size: 10px;
            opacity: 0.6;
            transition: all 0.25s;
        }

        .footer-col ul li a:hover {
            color: var(--gold);
            padding-left: 6px;
        }

        .footer-col ul li a:hover i {
            opacity: 1;
        }

        .footer-bottom {
            padding: 24px 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-bottom p {
            color: rgba(255, 255, 255, 0.25);
            font-size: 13px;
            margin: 0;
            text-align: center;
        }

        .footer-bottom a {
            color: var(--gold);
        }

        #back-top {
            position: fixed;
            bottom: 32px;
            right: 32px;
            z-index: 9999;
            display: none;
        }

        #back-top a {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #0a0a1a !important;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 8px 30px rgba(201, 147, 58, 0.40);
            transition: all 0.3s;
        }

        #back-top a:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 50px rgba(201, 147, 58, 0.55);
        }

        @media (max-width: 1199px) {
            .rsp-container {
                padding: 0 32px;
            }

            .nav-inner {
                padding: 0 32px;
            }

            .vid-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 991px) {
            .rsp-container {
                padding: 0 20px;
            }

            .nav-inner {
                padding: 0 20px;
                height: 68px;
            }

            .nav-logo img {
                height: 38px;
            }

            .nav-links {
                display: none !important;
            }

            .nav-right {
                display: none !important;
            }

            .nav-hamburger {
                display: flex;
            }

            .mobile-menu {
                top: 68px;
            }

            .rsp-videos-page {
                padding: 120px 0 70px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }

        @media (max-width: 640px) {
            .rsp-container {
                padding: 0 16px;
            }

            .vid-grid {
                grid-template-columns: 1fr;
            }

            .theme-toggle {
                bottom: 88px;
                right: 20px;
            }

            #back-top {
                bottom: 24px;
                right: 20px;
            }
        }
    </style>
</head>

<body>

    <button class="theme-toggle" id="themeToggle" title="Toggle Theme">
        <span class="icon-dark">🌙</span>
        <span class="icon-light">☀️</span>
    </button>

    <!-- NAVBAR (identical to homepage) -->
    <nav class="rsp-nav scrolled" id="rspNav">
        <div class="nav-inner">
            <div class="nav-logo">
                <a href="/index.php"><img src="/assets/img/logo/Pathshala.webp" alt="Realty Smartz Pathshala"></a>
            </div>
            <ul class="nav-links">
                <li><a href="/index.php">Home</a></li>
                <li><a href="/pages/Project.html">Projects</a></li>
                <li><a href="/pages/about.html">About</a></li>
                <li><a href="#">Blog</a></li>
                <li><a href="/pages/contact.html">Contact</a></li>
            </ul>
            <div class="nav-right">
                <?php if ($userName): ?>
                    <a href="/profile/profile_form.php" class="nav-login-btn"><i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($userName); ?></a>
                <?php else: ?>
                    <a href="/auth/login.html" class="nav-login-btn"><i class="fas fa-sign-in-alt"></i> Log In</a>
                <?php endif; ?>
            </div>
            <div class="nav-hamburger" id="navHamburger">
                <span></span><span></span><span></span>
            </div>
        </div>
    </nav>

    <div class="mobile-menu" id="mobileMenu">
        <ul>
            <li><a href="/index.php"><i class="fas fa-home" style="color:var(--gold);width:18px"></i> Home</a></li>
            <li><a href="/pages/Project.html"><i class="fas fa-building" style="color:var(--gold);width:18px"></i>
                    Projects</a></li>
            <li><a href="/pages/about.html"><i class="fas fa-info-circle" style="color:var(--gold);width:18px"></i>
                    About</a></li>
            <li><a href="#"><i class="fas fa-book" style="color:var(--gold);width:18px"></i> Blog</a></li>
            <li><a href="/pages/contact.html"><i class="fas fa-envelope" style="color:var(--gold);width:18px"></i>
                    Contact</a></li>
        </ul>
        <div class="mobile-menu-actions">
            <?php if (!$userName): ?>
                <a href="/auth/login.html" class="mobile-login-btn"><i class="fas fa-sign-in-alt"></i> Log In to Your
                    Account</a>
            <?php endif; ?>
        </div>
    </div>

    <main>
        <section class="rsp-videos-page">
            <div class="rsp-container">
                <div class="sec-head text-center">
                    <div class="section-tag">YouTube</div>
                    <h2 class="section-h">All <span>Videos</span></h2>
                    <p class="section-p">Browse our complete library of real estate content.</p>
                </div>

                <?php if ($videos->num_rows === 0): ?>
                    <p class="section-p text-center" style="margin:0 auto;">No videos published yet.</p>
                <?php else: ?>
                    <div class="vid-grid">
                        <?php while ($v = $videos->fetch_assoc()):
                            // Reconstruct thumbnail from embed_url's video id for a clickable static card
                            preg_match('~embed/([A-Za-z0-9_-]{11})~', $v['embed_url'], $m);
                            $vidId = $m[1] ?? '';
                            $thumb = "https://img.youtube.com/vi/{$vidId}/hqdefault.jpg";
                            ?>
                            <div class="vid-card">
                                <a class="vid-frame" href="<?= htmlspecialchars($v['youtube_url']) ?>" target="_blank"
                                    rel="noopener">
                                    <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($v['title']) ?>"
                                        loading="lazy">
                                    <span class="vid-play-btn"><i class="fas fa-play"></i></span>
                                </a>
                                <div class="vid-body">
                                    <h3><?= htmlspecialchars($v['title']) ?></h3>
                                    <?php if (!empty($v['location'])): ?>
                                        <p><?= htmlspecialchars($v['location']) ?></p>
                                    <?php elseif (!empty($v['description'])): ?>
                                        <p><?= htmlspecialchars($v['description']) ?></p>
                                    <?php endif; ?>
                                    <div class="vid-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                                            class="fas fa-star"></i><i class="fas fa-star"></i><i
                                            class="fas fa-star-half-alt"></i></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <div class="vid-pagination">
                            <a class="vid-page-link <?= $page <= 1 ? 'disabled' : '' ?>" href="?page=<?= $page - 1 ?>"><i
                                    class="fas fa-chevron-left"></i></a>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <a class="vid-page-link <?= $p === $page ? 'active' : '' ?>" href="?page=<?= $p ?>"><?= $p ?></a>
                            <?php endfor; ?>
                            <a class="vid-page-link <?= $page >= $totalPages ? 'disabled' : '' ?>"
                                href="?page=<?= $page + 1 ?>"><i class="fas fa-chevron-right"></i></a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- FOOTER (identical to homepage) -->
    <footer class="rsp-footer">
        <div class="rsp-container">
            <div class="footer-grid">
                <div class="footer-about">
                    <div class="footer-logo"><a href="/index.php"><img src="/assets/img/logo/Pathshala.webp"
                                alt="Realty Smartz Pathshala"></a></div>
                    <p>Unlock career growth, continuous learning, and leadership opportunities in a dynamic real estate
                        environment.</p>
                    <div class="footer-socials">
                        <a href="https://www.linkedin.com/company/realtysmartz/" target="_blank"><i
                                class="fab fa-linkedin-in"></i></a>
                        <a href="https://www.instagram.com/realtysmartz/" target="_blank"><i
                                class="fab fa-instagram"></i></a>
                        <a href="https://www.youtube.com/" target="_blank"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Our Solutions</h4>
                    <ul>
                        <li><a href="/pages/Project.html"><i class="fas fa-chevron-right"></i> DLF</a></li>
                        <li><a href="/pages/Project.html"><i class="fas fa-chevron-right"></i> Shapoorji Pallonji</a>
                        </li>
                        <li><a href="/pages/Project.html"><i class="fas fa-chevron-right"></i> Ganga Realty</a></li>
                        <li><a href="/pages/Project.html"><i class="fas fa-chevron-right"></i> Signature Global</a></li>
                        <li><a href="/pages/Project.html"><i class="fas fa-chevron-right"></i> M3M</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="/pages/Project.html"><i class="fas fa-chevron-right"></i> Heritage Homes Plots</a>
                        </li>
                        <li><a href="/pages/Project.html"><i class="fas fa-chevron-right"></i> Omaxe The State</a></li>
                        <li><a href="/pages/Project.html"><i class="fas fa-chevron-right"></i> Trehan</a></li>
                        <li><a href="/pages/Project.html"><i class="fas fa-chevron-right"></i> Danube Properties</a>
                        </li>
                        <li><a href="/pages/Project.html"><i class="fas fa-chevron-right"></i> Dubai Real Estate</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 Realty Smartz Pathshala. All rights reserved | Made with <i class="fa fa-heart"></i> by <a
                        href="https://engagexpert.in/" target="_blank">EngageXpert</a></p>
            </div>
        </div>
    </footer>

    <div id="back-top"><a title="Go to Top" href="#"><i class="fas fa-level-up-alt"></i></a></div>

    <script src="/assets/js/vendor/jquery-1.12.4.min.js"></script>
    <script>
        $(document).ready(function () {
            $(window).on('scroll', function () {
                if ($(this).scrollTop() > 60) { $('#back-top').fadeIn(300); } else { $('#back-top').fadeOut(300); }
            });
            $('#back-top a').on('click', function (e) { e.preventDefault(); $('html, body').animate({ scrollTop: 0 }, 600); });

            $('#navHamburger').on('click', function () {
                $(this).toggleClass('open');
                $('#mobileMenu').toggleClass('active');
            });
            $('#mobileMenu a').on('click', function () {
                $('#navHamburger').removeClass('open');
                $('#mobileMenu').removeClass('active');
            });
            $(window).on('resize', function () {
                if ($(window).width() > 991) { $('#navHamburger').removeClass('open'); $('#mobileMenu').removeClass('active'); }
            });

            var savedTheme = localStorage.getItem('rsp-theme') || 'dark';
            $('html').attr('data-theme', savedTheme);
            $('#themeToggle').on('click', function () {
                var current = $('html').attr('data-theme');
                var next = current === 'dark' ? 'light' : 'dark';
                $('html').attr('data-theme', next);
                localStorage.setItem('rsp-theme', next);
            });
        });
    </script>
</body>

</html>