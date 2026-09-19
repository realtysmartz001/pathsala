<?php
session_start();
require_once __DIR__ . '/includes/db_connect.php';

$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name']
    : (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null);

// Optional homepage content should not prevent the rest of the page from loading
// when a fresh hosting database has not been fully imported yet.
$homeVideos = false;
$events = false;

try {
    $homeVideos = $conn->query("SELECT title, embed_url, description, location
                                 FROM youtube_videos
                                 WHERE published = 1
                                 ORDER BY display_order ASC, id DESC
                                 LIMIT 4");
} catch (mysqli_sql_exception $exception) {
    $homeVideos = false;
}

try {
    $events = $conn->query("SELECT title, subtitle, description, image, event_date, event_time,
                                    location, event_type, registration_link, button_text
                             FROM events
                             WHERE published = 1 AND featured = 1
                             ORDER BY display_order ASC, id DESC");
} catch (mysqli_sql_exception $exception) {
    $events = false;
}
?>
<!doctype html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Realty Smartz Pathshala</title>
    <meta name="description" content="Empowering real estate professionals with world-class training.">
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
   CSS VARIABLES — DARK & LIGHT THEME
   ================================================ */
        :root,
        [data-theme="dark"] {
            --gold: #FF6B35;
            --gold-light: #FF8C5A;
            --gold-pale: #FFB088;
            --accent-blue: #4ECDC4;
            --accent-purple: #A855F7;
            --accent-pink: #EC4899;

            --bg-1: #0f0f23;
            --bg-2: #1a1a2e;
            --bg-3: #16213e;
            --bg-4: #0f3460;

            --glass: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 107, 53, 0.25);
            --glass-hover: rgba(255, 107, 53, 0.15);

            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.85);
            --text-muted: rgba(255, 255, 255, 0.65);
            --text-dim: rgba(255, 255, 255, 0.40);

            --nav-bg: rgba(15, 15, 35, 0.90);
            --card-bg: rgba(255, 255, 255, 0.06);
            --card-border: rgba(255, 255, 255, 0.10);
            --section-line: rgba(255, 107, 53, 0.50);

            --proj-overlay-start: rgba(15, 15, 35, 0.98);
            --proj-overlay-mid: rgba(15, 15, 35, 0.60);
            --footer-bg: #0f0f23;
            --scrollbar-track: #1a1a2e;

            --shadow-card: 0 25px 60px rgba(0, 0, 0, 0.5);
            --shadow-hover: 0 35px 80px rgba(0, 0, 0, 0.6);
            --shadow-gold: 0 15px 40px rgba(255, 107, 53, 0.3);
        }

        [data-theme="light"] {
            --gold: #FF6B35;
            --gold-light: #FF8C5A;
            --gold-pale: #FFB088;
            --accent-blue: #4ECDC4;
            --accent-purple: #A855F7;
            --accent-pink: #EC4899;

            --bg-1: #ffffff;
            --bg-2: #f8fafc;
            --bg-3: #f1f5f9;
            --bg-4: #e2e8f0;

            --glass: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 107, 53, 0.20);
            --glass-hover: rgba(255, 107, 53, 0.10);

            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --text-dim: #94a3b8;

            --nav-bg: rgba(255, 255, 255, 0.95);
            --card-bg: rgba(255, 255, 255, 0.90);
            --card-border: rgba(255, 107, 53, 0.15);
            --section-line: rgba(255, 107, 53, 0.30);

            --proj-overlay-start: rgba(30, 41, 59, 0.95);
            --proj-overlay-mid: rgba(30, 41, 59, 0.50);
            --footer-bg: #1e293b;
            --scrollbar-track: #f1f5f9;

            --shadow-card: 0 20px 50px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 30px 70px rgba(0, 0, 0, 0.12);
            --shadow-gold: 0 12px 35px rgba(255, 107, 53, 0.25);
        }

        /* ================================================
   BASE
   ================================================ */
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
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--scrollbar-track);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(var(--gold), var(--accent-purple));
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(var(--accent-pink), var(--accent-blue));
        }

        /* ================================================
   THEME TOGGLE BUTTON
   ================================================ */
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
            -webkit-backdrop-filter: blur(20px);
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

        /* ================================================
   CURSOR GLOW
   ================================================ */
        .cursor-glow {
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(201, 147, 58, 0.06) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            transform: translate(-50%, -50%);
            transition: left 0.3s ease, top 0.3s ease;
        }

        [data-theme="light"] .cursor-glow {
            display: none;
        }

        /* ==========================================
   PRELOADER
========================================== */

        #preloader-active {
            position: fixed;
            inset: 0;
            background: #090b18;
            /* black background */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 999999;
        }

        .pre-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 28px;
        }

        /* Loader */
        .pre-loader {
            position: relative;
            width: 90px;
            height: 90px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Circle */
        .pre-loader::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, .12);
            border-top-color: #b68a35;
            /* gold */
            animation: spin 1s linear infinite;
        }

        /* Book */
        .pre-loader img {
            width: 38px;
            animation: floatBook 1.5s ease-in-out infinite;
            filter: drop-shadow(0 0 15px rgba(103, 115, 255, .45));
        }

        /* Text */
        .pre-text {
            color: #8d93ad;
            font-size: 11px;
            letter-spacing: 5px;
            text-transform: uppercase;
            font-weight: 600;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes floatBook {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-4px);
            }
        }

        /* ================================================
   NAVBAR
   ================================================ */
        .rsp-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            transition: all 0.4s ease;
        }

        .rsp-nav.scrolled {
            background: var(--nav-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
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
            border: 1px solid rgba(255, 107, 53, 0.40);
            padding: 9px 22px;
            border-radius: 8px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 107, 53, 0.08);
        }

        .nav-login-btn:hover {
            background: linear-gradient(135deg, var(--gold), var(--accent-pink));
            color: #fff !important;
            border-color: var(--gold);
            box-shadow: 0 0 25px rgba(255, 107, 53, 0.4);
            transform: translateY(-2px);
        }

        .nav-account {
            position: relative;
            padding-bottom: 10px;
        }

        .nav-account-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: linear-gradient(135deg, var(--gold), var(--accent-purple));
            color: #ffffff;
            font-size: 13.5px;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(255, 107, 53, 0.35);
            white-space: nowrap;
        }

        .nav-account-btn:hover {
            box-shadow: 0 8px 35px rgba(255, 107, 53, 0.5);
            transform: translateY(-2px);
        }

        .nav-account-btn i.arr {
            font-size: 10px;
            transition: transform 0.25s;
        }

        .nav-account:hover .nav-account-btn i.arr {
            transform: rotate(180deg);
        }

        .nav-dropdown {
            display: none;
            position: absolute;
            /* top: calc(100% + 12px); */
            top: 100%;
            margin-top: 2px;
            right: 0;
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            min-width: 220px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            list-style: none;
            padding: 8px;
            z-index: 99999;
            animation: dropIn 0.2s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        @keyframes dropIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .nav-account:hover .nav-dropdown,
        .nav-dropdown:hover {
            display: block;
        }

        .nav-dropdown {
            display: none;
        }

        .nav-dropdown li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            color: var(--text-secondary) !important;
            font-size: 13.5px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-dropdown li a i {
            color: var(--gold);
            width: 16px;
            font-size: 13px;
        }

        .nav-dropdown li a:hover {
            background: var(--glass-hover);
            color: var(--gold) !important;
        }

        .nav-dropdown li.logout-li {
            /* border-top: 1px solid var(--card-border); */
            margin-top: 4px;
            padding-top: 4px;
        }

        .nav-dropdown li.logout-li a {
            color: #ef4444 !important;
            border-top: 1px solid var(--glass-border);
        }

        .nav-dropdown li.logout-li a i {
            color: #ef4444 !important;

        }

        /* Hamburger */
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

        .nav-hamburger:hover {
            border-color: var(--gold);
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.15), rgba(168, 85, 247, 0.15));
        }

        .nav-hamburger span {
            display: block;
            width: 22px;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--accent-purple));
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

        /* Mobile Menu Drawer */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 76px;
            left: 0;
            right: 0;
            background: var(--nav-bg);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-bottom: 1px solid var(--glass-border);
            z-index: 9998;
            padding: 16px 24px 24px;
            animation: slideDown 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);

        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .mobile-menu ul li+li {
            margin-top: 4px;
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
            background: linear-gradient(135deg, var(--gold), var(--accent-pink));
            color: #ffffff !important;
            font-weight: 700;
            font-size: 14.5px;
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }

        .mobile-account-section {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .mobile-account-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
        }

        .mobile-account-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a0a1a;
            font-size: 16px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .mobile-account-name {
            font-weight: 700;
            font-size: 14px;
            color: var(--text-primary);
        }

        .mobile-account-role {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* .mobile-menu-items { list-style: none; padding: 0; } */
        .mobile-menu-items {
            list-style: none;
            padding: 0;

            display: none;
        }

        .mobile-account-section.active .mobile-menu-items {
            display: block;
        }

        .mobile-menu-items li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.25s;
        }

        .mobile-account-header {
            cursor: pointer;
            justify-content: space-between;
        }

        .mobile-arrow {
            color: var(--gold);
            transition: .3s;
        }

        .mobile-account-section.active .mobile-arrow {
            transform: rotate(180deg);
        }

        .mobile-menu-items li a i {
            color: var(--gold);
            width: 18px;
        }

        .mobile-menu-items li a:hover {
            background: var(--glass-hover);
            color: var(--gold);
        }

        .mobile-logout a {
            color: #ef4444 !important;
            border-top: 1px solid var(--glass-border);
            margin-top: 4px;
            padding-top: 12px;
        }

        .mobile-logout a i {
            color: #ef4444 !important;
        }

        /* ================================================
   HERO
   ================================================ */
        .rsp-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            overflow: hidden;
            background: var(--bg-1);
        }

        .hero-particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            z-index: 0;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: var(--gold);
            opacity: 0.12;
            animation: floatP linear infinite;
            box-shadow: 0 0 20px var(--gold);
        }

        .particle:nth-child(1) {
            width: 3px;
            height: 3px;
            top: 15%;
            left: 10%;
            animation-duration: 12s;
        }

        .particle:nth-child(2) {
            width: 2px;
            height: 2px;
            top: 70%;
            left: 20%;
            animation-duration: 18s;
            animation-delay: 2s;
        }

        .particle:nth-child(3) {
            width: 4px;
            height: 4px;
            top: 30%;
            left: 80%;
            animation-duration: 15s;
            animation-delay: 1s;
        }

        .particle:nth-child(4) {
            width: 2px;
            height: 2px;
            top: 80%;
            left: 70%;
            animation-duration: 20s;
            animation-delay: 3s;
        }

        .particle:nth-child(5) {
            width: 3px;
            height: 3px;
            top: 50%;
            left: 50%;
            animation-duration: 14s;
            animation-delay: 5s;
        }

        .particle:nth-child(6) {
            width: 2px;
            height: 2px;
            top: 20%;
            left: 60%;
            animation-duration: 16s;
            animation-delay: 4s;
        }

        .particle:nth-child(7) {
            width: 5px;
            height: 5px;
            top: 60%;
            left: 35%;
            animation-duration: 22s;
            animation-delay: 2s;
        }

        .particle:nth-child(8) {
            width: 2px;
            height: 2px;
            top: 90%;
            left: 90%;
            animation-duration: 13s;
            animation-delay: 6s;
        }

        @keyframes floatP {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.08;
            }

            50% {
                opacity: 0.15;
            }

            100% {
                transform: translateY(-120px) rotate(360deg);
                opacity: 0;
            }
        }

        /* ================================================
   APPROVAL SUCCESS BANNER
   ================================================ */
        .approval-banner {
            position: fixed;
            top: 90px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10000;
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.95), rgba(34, 197, 94, 0.95));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(52, 211, 153, 0.3);
            border-radius: 12px;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 8px 30px rgba(52, 211, 153, 0.3);
            animation: slideDown 0.5s ease;
            max-width: 90%;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        .approval-banner i {
            font-size: 20px;
            color: #fff;
        }

        .approval-banner-text {
            color: #fff;
            font-size: 14px;
            font-weight: 600;
        }

        .approval-banner-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #fff;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s;
        }

        .approval-banner-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .hero-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
        }

        .hero-orb-1 {
            width: 600px;
            height: 600px;
            background: linear-gradient(135deg, var(--gold), var(--accent-purple));
            opacity: 0.15;
            top: -200px;
            right: -100px;
            animation: orbPulse 8s ease-in-out infinite alternate;
            filter: blur(60px);
        }

        .hero-orb-2 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-pink));
            opacity: 0.12;
            bottom: -100px;
            left: -50px;
            animation: orbPulse 10s ease-in-out infinite alternate-reverse;
            filter: blur(50px);
        }

        .hero-orb-3 {
            width: 300px;
            height: 300px;
            background: var(--accent-blue);
            opacity: 0.08;
            top: 50%;
            left: 30%;
            animation: orbPulse 12s ease-in-out infinite alternate;
            filter: blur(40px);
        }

        @keyframes orbPulse {
            from {
                transform: scale(1);
                opacity: 0.06;
            }

            to {
                transform: scale(1.2);
                opacity: 0.12;
            }
        }

        [data-theme="light"] .hero-orb-1 {
            opacity: 0.05;
        }

        [data-theme="light"] .hero-orb-2 {
            opacity: 0.04;
        }

        .rsp-hero-overlay {
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 70% 50%, rgba(201, 147, 58, 0.04) 0%, transparent 60%),
                linear-gradient(135deg, rgba(10, 10, 26, 0.98) 0%, rgba(10, 10, 26, 0.80) 100%);
            z-index: 1;
        }

        [data-theme="light"] .rsp-hero-overlay {
            background: linear-gradient(135deg, rgba(248, 246, 242, 0.98) 0%, rgba(248, 246, 242, 0.85) 100%);
        }

        [data-theme="light"] .hero-particles {
            opacity: 0.3;
        }

        .rsp-hero-content {
            position: relative;
            z-index: 2;
            max-width: 1400px;
            margin: 0 auto;
            padding: 100px 48px 60px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            min-height: 100vh;
        }

        .hero-left {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(201, 147, 58, 0.10);
            border: 1px solid rgba(201, 147, 58, 0.25);
            color: var(--gold);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            padding: 8px 18px;
            border-radius: 100px;
            margin-bottom: 28px;
            width: fit-content;
            backdrop-filter: blur(10px);
            opacity: 0;
            animation: fadeUp 0.7s ease 0.2s forwards;
        }

        .hero-badge-dot {
            width: 6px;
            height: 6px;
            background: var(--gold);
            border-radius: 50%;
            animation: blink 1.5s ease infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }

        .hero-h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.8rem, 5.5vw, 5rem);
            font-weight: 800;
            line-height: 1.08;
            color: var(--text-primary);
            margin-bottom: 24px;
            opacity: 0;
            animation: fadeUp 0.7s ease 0.4s forwards;
        }

        .hero-h1 .gold-text {
            background: linear-gradient(135deg, var(--gold), var(--gold-light), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
        }

        .hero-desc {
            color: var(--text-muted);
            font-size: 16.5px;
            line-height: 1.80;
            max-width: 480px;
            margin-bottom: 40px;
            opacity: 0;
            animation: fadeUp 0.7s ease 0.6s forwards;
        }

        .hero-btns {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 52px;
            opacity: 0;
            animation: fadeUp 0.7s ease 0.8s forwards;
        }

        .btn-luxury {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--gold), var(--accent-pink));
            color: #ffffff !important;
            font-weight: 700;
            font-size: 14px;
            padding: 15px 34px;
            border-radius: 12px;
            transition: all 0.3s;
            box-shadow: 0 8px 30px rgba(255, 107, 53, 0.4);
            position: relative;
            overflow: hidden;
        }

        .btn-luxury::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .btn-luxury:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 16px 50px rgba(255, 107, 53, 0.5);
        }

        .btn-luxury:hover::before {
            opacity: 1;
        }

        .btn-glass {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--glass);
            color: var(--text-primary) !important;
            font-weight: 600;
            font-size: 14px;
            padding: 15px 34px;
            border-radius: 12px;
            border: 1px solid var(--card-border);
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .btn-glass:hover {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            border-color: var(--accent-blue);
            color: #ffffff !important;
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 12px 35px rgba(78, 205, 196, 0.3);
        }

        .hero-mini-stats {
            display: flex;
            gap: 28px;
            opacity: 0;
            animation: fadeUp 0.7s ease 1s forwards;
        }

        .hms-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .hms-num {
            font-family: 'Playfair Display', serif;
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--gold);
            line-height: 1;
        }

        .hms-label {
            color: var(--text-dim);
            font-size: 12px;
            font-weight: 500;
        }

        .hms-divider {
            width: 1px;
            background: var(--card-border);
            align-self: stretch;
        }

        .hero-right {
            position: relative;
            opacity: 0;
            animation: fadeLeft 0.9s ease 0.6s forwards;
        }

        @keyframes fadeLeft {
            from {
                opacity: 0;
                transform: translateX(40px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hero-glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 36px;
            box-shadow: var(--shadow-card), inset 0 1px 0 rgba(255, 255, 255, 0.06);
            position: relative;
            overflow: hidden;
        }

        .hero-glass-card::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(201, 147, 58, 0.10), transparent 70%);
            border-radius: 50%;
        }

        .hgc-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .hgc-sub {
            color: var(--text-muted);
            font-size: 13px;
            margin-bottom: 28px;
        }

        .hgc-stat-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 24px;
        }

        .hgc-stat {
            background: rgba(201, 147, 58, 0.06);
            border: 1px solid rgba(201, 147, 58, 0.14);
            border-radius: 12px;
            padding: 18px;
            transition: all 0.3s;
        }

        .hgc-stat:hover {
            background: rgba(201, 147, 58, 0.12);
            border-color: rgba(201, 147, 58, 0.30);
        }

        .hgc-stat-num {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem;
            font-weight: 700;
            color: var(--gold);
            line-height: 1;
            margin-bottom: 4px;
        }

        .hgc-stat-label {
            color: var(--text-muted);
            font-size: 12px;
        }

        .hgc-projects {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .hgc-proj-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            transition: all 0.3s;
        }

        .hgc-proj-item:hover {
            background: rgba(201, 147, 58, 0.06);
            border-color: rgba(201, 147, 58, 0.15);
        }

        .hgc-proj-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .hgc-proj-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a0a1a;
            font-size: 14px;
        }

        .hgc-proj-name {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .hgc-proj-type {
            font-size: 11.5px;
            color: var(--text-muted);
        }

        .hgc-proj-badge {
            font-size: 10.5px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 100px;
            background: rgba(201, 147, 58, 0.15);
            color: var(--gold);
            border: 1px solid rgba(201, 147, 58, 0.25);
        }

        .hero-chip {
            position: absolute;
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(201, 147, 58, 0.20);
            border-radius: 100px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-primary);
            font-size: 12.5px;
            font-weight: 600;
            white-space: nowrap;
            animation: chipFloat 4s ease-in-out infinite alternate;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            z-index: 1;
        }

        .hero-chip i {
            color: var(--gold);
        }

        .hero-chip-1 {
            top: -20px;
            left: -30px;
            animation-delay: 0s;
        }

        .hero-chip-2 {
            bottom: 40px;
            right: -30px;
            animation-delay: 1s;
        }

        @keyframes chipFloat {
            from {
                transform: translateY(0);
            }

            to {
                transform: translateY(-12px);
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(28px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ================================================
   COMMON
   ================================================ */
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

        .section-divider {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--section-line), transparent);
        }

        /* ================================================
   SERVICES
   ================================================ */
        .rsp-services {
            padding: 110px 0;
            background: var(--bg-2);
            position: relative;
            overflow: hidden;
        }

        .srv-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .srv-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 44px 36px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            cursor: default;
            backdrop-filter: blur(10px);
        }

        .srv-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 0% 0%, rgba(201, 147, 58, 0.08), transparent 60%);
            opacity: 0;
            transition: opacity 0.4s;
        }

        .srv-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .srv-card:hover {
            background: rgba(201, 147, 58, 0.06);
            border-color: rgba(201, 147, 58, 0.25);
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }

        .srv-card:hover::before {
            opacity: 1;
        }

        .srv-card:hover::after {
            transform: scaleX(1);
        }

        .srv-num {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 800;
            color: rgba(201, 147, 58, 0.2);
            line-height: 1;
            margin-bottom: 20px;
        }

        .srv-icon {
            width: 62px;
            height: 62px;
            background: rgba(201, 147, 58, 0.08);
            border: 1px solid rgba(201, 147, 58, 0.18);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            transition: all 0.4s;
        }

        .srv-icon img {
            width: 30px;
            filter: invert(1) sepia(1) saturate(2) hue-rotate(5deg);
        }

        [data-theme="light"] .srv-icon img {
            filter: none;
        }

        .srv-card:hover .srv-icon {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            border-color: var(--gold);
            box-shadow: 0 8px 25px rgba(201, 147, 58, 0.30);
        }

        /* .srv-card:hover .srv-icon img { filter:brightness(0) invert(0); } */
        .srv-card h3 {
            font-size: 19px;
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 14px;
        }

        .srv-card p {
            color: var(--text-muted);
            font-size: 14.5px;
            line-height: 1.80;
        }

        .srv-arrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--gold);
            font-size: 13px;
            font-weight: 600;
            margin-top: 22px;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s;
        }

        .srv-card:hover .srv-arrow {
            opacity: 1;
            transform: translateX(0);
        }

        /* ================================================
   YOUTUBE
   ================================================ */
        .rsp-videos {
            padding: 110px 0;
            background: var(--bg-1);
            position: relative;
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
        }

        .vid-frame iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: none;
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

        /* ================================================
   ABOUT
   ================================================ */
        .rsp-about {
            padding: 110px 0;
            background: var(--bg-2);
            position: relative;
            overflow: hidden;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .about-img-wrap {
            position: relative;
            border-radius: 20px;
            overflow: visible;
        }

        .about-img-frame {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-card);
        }

        .about-img-frame img {
            width: 100%;
            display: block;
            transition: transform 0.6s ease;
        }

        .about-img-wrap:hover .about-img-frame img {
            transform: scale(1.04);
        }

        .about-img-frame::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 50%, rgba(10, 10, 26, 0.5) 100%);
        }

        .about-deco {
            position: absolute;
            top: -15px;
            left: -15px;
            right: 15px;
            bottom: 15px;
            border: 1px solid rgba(201, 147, 58, 0.12);
            border-radius: 22px;
            z-index: -1;
        }

        .about-play-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 72px;
            height: 72px;
            background: rgba(201, 147, 58, 0.15);
            backdrop-filter: blur(16px);
            border: 2px solid rgba(201, 147, 58, 0.40);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold) !important;
            font-size: 22px;
            z-index: 3;
            transition: all 0.3s;
            padding-left: 4px;
        }

        .about-play-btn:hover {
            background: var(--gold);
            color: #0a0a1a !important;
            transform: translate(-50%, -50%) scale(1.12);
            box-shadow: 0 0 50px rgba(201, 147, 58, 0.50);
        }

        .about-float-card {
            position: absolute;
            bottom: -24px;
            right: -24px;
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(201, 147, 58, 0.25);
            border-radius: 16px;
            padding: 20px 24px;
            z-index: 4;
            box-shadow: var(--shadow-card);
            animation: chipFloat 5s ease-in-out infinite alternate;
        }

        .afc-num {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--gold);
            line-height: 1;
        }

        .afc-label {
            color: var(--text-muted);
            font-size: 12px;
            margin-top: 4px;
        }

        .feat-row {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 32px;
        }

        .feat-item {
            display: flex;
            align-items: flex-start;
            gap: 18px;
            padding: 20px 22px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            border-left: 3px solid var(--gold);
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .feat-item:hover {
            background: rgba(201, 147, 58, 0.06);
            border-color: rgba(201, 147, 58, 0.25);
            transform: translateX(8px);
            box-shadow: var(--shadow-card);
        }

        .feat-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a0a1a;
            font-size: 17px;
            flex-shrink: 0;
            box-shadow: 0 6px 20px rgba(201, 147, 58, 0.25);
        }

        .feat-item h4 {
            font-size: 15px;
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .feat-item p {
            color: var(--text-muted);
            font-size: 13.5px;
            line-height: 1.65;
            margin: 0;
        }

        /* ================================================
   PROJECTS — COMPLETELY REDESIGNED 🔥
   ================================================ */
        .rsp-projects {
            padding: 110px 0;
            background: var(--bg-1);
            position: relative;
        }

        /* Filter Tabs */
        .proj-filters {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 48px;
        }

        .proj-filter-btn {
            padding: 9px 22px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
            font-family: 'Inter', sans-serif;
        }

        .proj-filter-btn:hover {
            border-color: var(--gold);
            color: var(--gold);
            background: rgba(201, 147, 58, 0.06);
        }

        .proj-filter-btn.active {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #0a0a1a;
            border-color: transparent;
            box-shadow: 0 6px 20px rgba(201, 147, 58, 0.35);
        }

        /* Bento Grid Layout */
        .proj-bento {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            grid-template-rows: auto;
            gap: 16px;
        }

        /* Card Base */
        .proj-bento-card {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            cursor: pointer;
            border: 1px solid var(--card-border);
            transition: all 0.45s cubic-bezier(0.23, 1, 0.32, 1);
            background: var(--card-bg);
            min-height: 300px;
        }

        /* Grid placement */
        .proj-bento-card:nth-child(1) {
            grid-column: span 5;
            grid-row: span 2;
            min-height: 500px;
        }

        .proj-bento-card:nth-child(2) {
            grid-column: span 4;
            min-height: 240px;
        }

        .proj-bento-card:nth-child(3) {
            grid-column: span 3;
            min-height: 240px;
        }

        .proj-bento-card:nth-child(4) {
            grid-column: span 4;
            min-height: 240px;
        }

        .proj-bento-card:nth-child(5) {
            grid-column: span 3;
            min-height: 240px;
        }

        .proj-bento-card:nth-child(6) {
            grid-column: span 5;
            grid-row: span 2;
            min-height: 500px;
        }

        .proj-bento-card:nth-child(7) {
            grid-column: span 4;
            min-height: 240px;
        }

        .proj-bento-card:nth-child(8) {
            grid-column: span 3;
            min-height: 240px;
        }

        .proj-bento-card:hover {
            transform: translateY(-8px) scale(1.01);
            border-color: rgba(201, 147, 58, 0.40);
            box-shadow:
                0 30px 80px rgba(0, 0, 0, 0.45),
                0 0 0 1px rgba(201, 147, 58, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.06);
            z-index: 2;
        }

        .proj-bento-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1), filter 0.5s ease;
            filter: brightness(0.78) saturate(1.1);
        }

        .proj-bento-card:hover .proj-bento-img {
            transform: scale(1.08);
            filter: brightness(0.65) saturate(1.2);
        }

        /* Gradient overlays */
        .proj-bento-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top,
                    rgba(5, 5, 18, 0.98) 0%,
                    rgba(5, 5, 18, 0.55) 40%,
                    rgba(5, 5, 18, 0.10) 70%,
                    transparent 100%);
            transition: all 0.45s ease;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 26px 24px;
        }

        .proj-bento-card:hover .proj-bento-overlay {
            background: linear-gradient(to top,
                    rgba(5, 5, 18, 1) 0%,
                    rgba(5, 5, 18, 0.80) 50%,
                    rgba(5, 5, 18, 0.25) 80%,
                    rgba(201, 147, 58, 0.04) 100%);
        }

        /* Top badge */
        .proj-bento-top {
            position: absolute;
            top: 18px;
            left: 18px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .proj-bento-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(10, 10, 26, 0.65);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(201, 147, 58, 0.30);
            color: var(--gold);
            font-size: 10.5px;
            font-weight: 700;
            padding: 5px 13px;
            border-radius: 100px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .proj-bento-card:hover .proj-bento-badge {
            background: rgba(201, 147, 58, 0.20);
            border-color: rgba(201, 147, 58, 0.55);
        }

        /* Location chip */
        .proj-bento-loc {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(10, 10, 26, 0.55);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.10);
            color: rgba(255, 255, 255, 0.75);
            font-size: 10px;
            font-weight: 500;
            padding: 5px 11px;
            border-radius: 100px;
        }

        .proj-bento-loc i {
            color: var(--gold);
            font-size: 9px;
        }

        /* Content */
        .proj-bento-content {
            position: relative;
            z-index: 2;
        }

        .proj-bento-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 6px;
            line-height: 1.3;
            transition: color 0.3s;
        }

        .proj-bento-card:nth-child(1) h3,
        .proj-bento-card:nth-child(6) h3 {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .proj-bento-card:hover h3 {
            color: var(--gold-light);
        }

        .proj-bento-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.55);
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 14px;
        }

        .proj-bento-meta-dot {
            width: 3px;
            height: 3px;
            background: var(--gold);
            border-radius: 50%;
        }

        /* CTA row — always visible, styled nicely */
        .proj-bento-cta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .proj-bento-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #0a0a1a !important;
            font-size: 12.5px;
            font-weight: 700;
            padding: 9px 18px;
            border-radius: 8px;
            transition: all 0.3s;
            opacity: 0;
            transform: translateY(10px);
            box-shadow: 0 4px 16px rgba(201, 147, 58, 0.30);
        }

        .proj-bento-card:hover .proj-bento-link {
            opacity: 1;
            transform: translateY(0);
        }

        .proj-bento-link:hover {
            box-shadow: 0 8px 28px rgba(201, 147, 58, 0.55);
            transform: translateY(-2px) !important;
        }

        .proj-bento-icon-btn {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.70);
            font-size: 13px;
            transition: all 0.3s;
            flex-shrink: 0;
            opacity: 0;
            transform: scale(0.8);
        }

        .proj-bento-card:hover .proj-bento-icon-btn {
            opacity: 1;
            transform: scale(1);
        }

        .proj-bento-icon-btn:hover {
            background: rgba(201, 147, 58, 0.20);
            border-color: rgba(201, 147, 58, 0.40);
            color: var(--gold);
        }

        /* Price tag (large cards) */
        .proj-bento-price {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(201, 147, 58, 0.12);
            border: 1px solid rgba(201, 147, 58, 0.25);
            color: var(--gold);
            font-size: 11.5px;
            font-weight: 700;
            padding: 5px 13px;
            border-radius: 100px;
            opacity: 0;
            transform: translateY(8px);
            transition: all 0.4s ease 0.05s;
        }

        .proj-bento-card:hover .proj-bento-price {
            opacity: 1;
            transform: translateY(0);
        }

        .view-more-wrap {
            text-align: center;
            margin-top: 55px;
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

        /* ================================================
   ACHIEVEMENTS
   ================================================ */
        .rsp-achievements {
            padding: 110px 0;
            background: var(--bg-2);
            position: relative;
            overflow: hidden;
        }

        .ach-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        /* ================================================
   EVENTS
   ================================================ */
        .rsp-events {
            padding: 110px 0;
            background: var(--bg-2);
            position: relative;
            overflow: hidden;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 26px;
        }

        .event-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            overflow: hidden;
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
        }

        .event-card:hover {
            transform: translateY(-10px);
            border-color: rgba(201, 147, 58, 0.30);
            box-shadow: var(--shadow-hover);
        }

        .event-media {
            position: relative;
            aspect-ratio: 16 / 9;
            overflow: hidden;
            background: #10102a;
        }

        .event-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.6s ease;
        }

        .event-card:hover .event-media img {
            transform: scale(1.06);
        }

        .event-date-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--card-bg);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(201, 147, 58, 0.30);
            border-radius: 12px;
            padding: 8px 14px;
            text-align: center;
            line-height: 1.2;
        }

        .event-date-badge .edb-day {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--gold);
            display: block;
        }

        .event-date-badge .edb-month {
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .event-type-chip {
            position: absolute;
            top: 16px;
            right: 16px;
            background: rgba(10, 10, 26, 0.65);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(201, 147, 58, 0.30);
            color: var(--gold);
            font-size: 10.5px;
            font-weight: 700;
            padding: 5px 13px;
            border-radius: 100px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .event-body {
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .event-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .event-subtitle {
            color: var(--gold);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 14px;
        }

        .event-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 14px;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .event-meta-item i {
            color: var(--gold);
            width: 14px;
            font-size: 12px;
        }

        .event-desc {
            color: var(--text-muted);
            font-size: 13.5px;
            line-height: 1.7;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .event-cta {
            margin-top: auto;
        }

        .event-cta .btn-outline-gold {
            width: 100%;
            justify-content: center;
            padding: 12px 24px;
            font-size: 13px;
        }

        @media (max-width: 640px) {
            .events-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ================================================
   TEAM
   ================================================ */
        .rsp-team {
            padding: 110px 0;
            background: var(--bg-1);
            position: relative;
            overflow: hidden;
        }

        .rsp-team::before {
            content: '';
            position: absolute;
            top: -200px;
            right: -200px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(201, 147, 58, 0.06), transparent 70%);
            border-radius: 50%;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 18px;
        }

        .team-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 32px 20px 28px;
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .team-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% 0%, rgba(201, 147, 58, 0.08), transparent 60%);
            opacity: 0;
            transition: opacity 0.4s;
        }

        .team-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
            transform: scaleX(0);
            transition: transform 0.4s;
        }

        .team-card:hover {
            background: rgba(201, 147, 58, 0.06);
            border-color: rgba(201, 147, 58, 0.22);
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
        }

        .team-card:hover::before {
            opacity: 1;
        }

        .team-card:hover::after {
            transform: scaleX(1);
        }

        .t-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            margin: 0 auto 18px;
            overflow: hidden;
            border: 2px solid rgba(201, 147, 58, 0.25);
            transition: all 0.4s;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        }

        .team-card:hover .t-avatar {
            border-color: var(--gold);
            box-shadow: 0 0 30px rgba(201, 147, 58, 0.30), 0 10px 40px rgba(0, 0, 0, 0.5);
            transform: scale(1.08);
        }

        .t-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .t-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(201, 147, 58, 0.10);
            color: var(--gold);
            font-size: 10px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 100px;
            margin-bottom: 12px;
            border: 1px solid rgba(201, 147, 58, 0.22);
        }

        .team-card h5 {
            color: var(--text-primary);
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .team-card p {
            color: var(--text-muted);
            font-size: 12px;
            margin: 0;
        }

        /* ================================================
   TRAINING
   ================================================ */
        .rsp-training {
            padding: 110px 0;
            background: var(--bg-2);
            position: relative;
            overflow: hidden;
        }

        .training-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .training-img-wrap {
            position: relative;
        }

        .training-img-frame {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-card);
        }

        .training-img-frame img {
            width: 100%;
            display: block;
            transition: transform 0.6s;
        }

        .training-img-wrap:hover .training-img-frame img {
            transform: scale(1.04);
        }

        .training-img-frame::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            z-index: 2;
        }

        .training-float-badge {
            position: absolute;
            top: 24px;
            right: -20px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(201, 147, 58, 0.25);
            border-radius: 14px;
            padding: 16px 20px;
            z-index: 4;
            box-shadow: var(--shadow-card);
            animation: chipFloat 5s ease-in-out infinite alternate;
            animation-delay: 1s;
        }

        .tfb-icon {
            font-size: 1.6rem;
            margin-bottom: 4px;
        }

        .tfb-label {
            color: var(--text-muted);
            font-size: 11px;
        }

        .tfb-val {
            color: var(--gold);
            font-size: 14px;
            font-weight: 700;
        }

        .train-points {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin: 32px 0 38px;
        }

        .train-point {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 18px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-secondary);
            font-size: 14.5px;
            font-weight: 500;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }

        .train-point:hover {
            background: rgba(201, 147, 58, 0.06);
            border-color: rgba(201, 147, 58, 0.18);
            color: var(--text-primary);
            transform: translateX(6px);
        }

        .train-point-icon {
            width: 38px;
            height: 38px;
            background: rgba(201, 147, 58, 0.10);
            border: 1px solid rgba(201, 147, 58, 0.20);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 15px;
            flex-shrink: 0;
            transition: all 0.3s;
        }

        .train-point:hover .train-point-icon {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #0a0a1a;
            border-color: transparent;
        }

        /* ================================================
   FOOTER
   ================================================ */
        .rsp-footer {
            background: var(--footer-bg);
            padding: 80px 0 0;
            border-top: 1px solid rgba(201, 147, 58, 0.15);
            position: relative;
            overflow: hidden;
        }

        .rsp-footer::before {
            content: '';
            position: absolute;
            top: -200px;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 400px;
            background: radial-gradient(ellipse, rgba(201, 147, 58, 0.04), transparent 70%);
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

        .footer-bottom .fa-heart {
            color: var(--gold);
            display: inline-block;
            animation: pulse 0.4s ease-in-out infinite;
            transform-origin: center;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.4);
            }
        }


        /* ================================================
   SCROLL TOP
   ================================================ */
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

        /* ================================================
   RESPONSIVE — FIXED 🔧
   ================================================ */

        /* Tablet */
        @media (max-width: 1199px) {
            .rsp-container {
                padding: 0 32px;
            }

            .nav-inner {
                padding: 0 32px;
            }

            .rsp-hero-content {
                grid-template-columns: 1fr;
                padding: 130px 32px 70px;
                min-height: auto;
            }

            .hero-right {
                display: none;
            }

            .hero-mini-stats {
                flex-wrap: wrap;
                gap: 20px;
            }

            .vid-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .team-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            /* Projects tablet */
            .proj-bento {
                grid-template-columns: repeat(6, 1fr);
            }

            .proj-bento-card:nth-child(1) {
                grid-column: span 3;
                grid-row: span 2;
                min-height: 420px;
            }

            .proj-bento-card:nth-child(2) {
                grid-column: span 3;
                min-height: 200px;
            }

            .proj-bento-card:nth-child(3) {
                grid-column: span 3;
                min-height: 200px;
            }

            .proj-bento-card:nth-child(4) {
                grid-column: span 3;
                min-height: 200px;
            }

            .proj-bento-card:nth-child(5) {
                grid-column: span 3;
                min-height: 200px;
            }

            .proj-bento-card:nth-child(6) {
                grid-column: span 3;
                grid-row: span 2;
                min-height: 420px;
            }

            .proj-bento-card:nth-child(7) {
                grid-column: span 3;
                min-height: 200px;
            }

            .proj-bento-card:nth-child(8) {
                grid-column: span 3;
                min-height: 200px;
            }
        }

        /* Mobile */
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

            .rsp-hero-content {
                padding: 110px 20px 60px;
                gap: 40px;
            }

            .hero-desc {
                font-size: 15px;
            }

            .hero-btns {
                gap: 12px;
            }

            .btn-luxury,
            .btn-glass {
                padding: 13px 26px;
                font-size: 13.5px;
            }

            .srv-grid {
                grid-template-columns: 1fr;
            }

            .about-grid,
            .ach-grid,
            .training-grid {
                grid-template-columns: 1fr;
                gap: 50px;
            }

            .about-float-card {
                bottom: -16px;
                right: -10px;
            }

            .training-float-badge {
                right: 16px;
            }

            .team-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .footer-logo img {
                filter: drop-shadow(0 0 8px rgba(201, 147, 58, 0.20));
            }

            /* Projects mobile — single column bento */
            .proj-bento {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .proj-bento-card:nth-child(1) {
                grid-column: span 2;
                grid-row: span 1;
                min-height: 280px;
            }

            .proj-bento-card:nth-child(2) {
                grid-column: span 1;
                min-height: 200px;
            }

            .proj-bento-card:nth-child(3) {
                grid-column: span 1;
                min-height: 200px;
            }

            .proj-bento-card:nth-child(4) {
                grid-column: span 1;
                min-height: 200px;
            }

            .proj-bento-card:nth-child(5) {
                grid-column: span 1;
                min-height: 200px;
            }

            .proj-bento-card:nth-child(6) {
                grid-column: span 2;
                grid-row: span 1;
                min-height: 280px;
            }

            .proj-bento-card:nth-child(7) {
                grid-column: span 1;
                min-height: 200px;
            }

            .proj-bento-card:nth-child(8) {
                grid-column: span 1;
                min-height: 200px;
            }

            /* On mobile always show CTA */
            .proj-bento-link {
                opacity: 1;
                transform: translateY(0);
            }

            .proj-bento-icon-btn {
                opacity: 1;
                transform: scale(1);
            }

            .proj-bento-price {
                opacity: 1;
                transform: translateY(0);
            }

            .mobile-account-header {

                padding: 8px 15px;
            }


            .mobile-account-avatar {
                width: 35px;
                height: 35px;
                font-size: 14px;
            }

            /* .mobile-menu {
        top: 56px;
    } */

            .mobile-menu ul li a {
                padding: 11px 16px;
            }

            .mobile-menu ul li+li {
                margin-top: 3px;
            }

            .mobile-menu-actions {

                padding-top: 11px;
            }

            .mobile-account-section {

                gap: 4px;
            }

            .mobile-menu ul {

                margin-bottom: 8px;
            }

        }


        @media (max-width: 640px) {
            .rsp-hero-content {
                padding: 100px 16px 50px;
            }

            .rsp-container {
                padding: 0 16px;
            }

            .hero-h1 {
                font-size: 2.2rem;
            }

            .hero-mini-stats {
                gap: 14px;
            }

            .hms-num {
                font-size: 1.5rem;
            }

            .hero-btns {
                flex-direction: column;
            }

            .btn-luxury,
            .btn-glass {
                width: 100%;
                justify-content: center;
            }

            .proj-bento {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .proj-bento-card:nth-child(n) {
                grid-column: span 1;
                grid-row: span 1;
                min-height: 240px;
            }

            .vid-grid {
                grid-template-columns: 1fr;
            }

            .team-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .proj-filters {
                gap: 8px;
            }

            .proj-filter-btn {
                padding: 7px 16px;
                font-size: 12px;
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

        @media (max-width: 400px) {
            .team-grid {
                grid-template-columns: 1fr;
            }

            .hero-h1 {
                font-size: 1.9rem;
            }

            .section-h {
                font-size: 1.7rem;
            }

            .about-float-card {
                display: none;
            }

            .training-float-badge {
                display: none;
            }
        }

        /* ================================================
   SOCIAL MEDIA — FOLLOW US SECTION (NEW, ISOLATED)
   ================================================ */
        .rsp-social-follow {
            padding: 70px 0;
            background: var(--bg-1);
            position: relative;
        }

        .social-follow-head {
            text-align: center;
            margin-bottom: 44px;
        }

        .social-follow-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .social-follow-card {
            display: flex;
            align-items: center;
            gap: 18px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 26px 24px;
            transition: all 0.35s cubic-bezier(0.23, 1, 0.32, 1);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-card);
        }

        .social-follow-card:hover {
            transform: translateY(-8px);
            border-color: rgba(201, 147, 58, 0.40);
            box-shadow: var(--shadow-hover);
        }

        .social-follow-icon {
            width: 56px;
            height: 56px;
            flex-shrink: 0;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #0a0a1a;
            box-shadow: 0 8px 24px rgba(201, 147, 58, 0.30);
            transition: transform 0.35s ease;
        }

        .social-follow-card:hover .social-follow-icon {
            transform: scale(1.1) rotate(-6deg);
        }

        .social-follow-info {
            flex: 1;
            min-width: 0;
        }

        .social-follow-platform {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 4px;
        }

        .social-follow-handle {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .social-follow-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12.5px;
            font-weight: 700;
            padding: 9px 20px;
            border-radius: 8px;
            border: 1px solid rgba(201, 147, 58, 0.40);
            color: var(--gold) !important;
            background: rgba(201, 147, 58, 0.06);
            transition: all 0.3s;
        }

        .social-follow-btn:hover {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #0a0a1a !important;
            border-color: transparent;
            box-shadow: 0 6px 20px rgba(201, 147, 58, 0.35);
        }

        @media (max-width: 991px) {
            .social-follow-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .social-follow-card:nth-child(3) {
                grid-column: span 2;
                max-width: 400px;
                margin: 0 auto;
            }
        }

        @media (max-width: 640px) {
            .rsp-social-follow {
                padding: 50px 0;
            }

            .social-follow-grid {
                grid-template-columns: 1fr;
            }

            .social-follow-card:nth-child(3) {
                grid-column: span 1;
                max-width: none;
                margin: 0;
            }

            .social-follow-card {
                padding: 22px 20px;
            }
        }
    </style>
</head>

<body>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'approved'): ?>
    <div class="approval-banner" id="approvalBanner">
        <i class="fas fa-check-circle"></i>
        <span class="approval-banner-text">🎉 Congratulations! Your account has been approved. Welcome to Realty Smartz Pathshala!</span>
        <button class="approval-banner-close" onclick="document.getElementById('approvalBanner').remove()">×</button>
    </div>
    <?php endif; ?>

    <div class="cursor-glow" id="cursorGlow"></div>

    <!-- Theme Toggle -->
    <button class="theme-toggle" id="themeToggle" title="Toggle Theme">
        <span class="icon-dark">🌙</span>
        <span class="icon-light">☀️</span>
    </button>

    <div id="preloader-active">
        <div class="pre-wrap">
            <div class="pre-loader">
                <img src="./assets/img/logo/loader.webp" alt="Loading">
            </div>

            <p class="pre-text">
                Preparing Your Success Path...
            </p>
        </div>
    </div>

    <!-- NAVBAR -->
    <nav class="rsp-nav" id="rspNav">
        <div class="nav-inner">
            <div class="nav-logo">
                <a href="./index.php"><img src="./assets/img/logo/Pathshala.webp" alt="Realty Smartz Pathshala"></a>
            </div>
            <ul class="nav-links">
                <li class="active"><a href="./index.php">Home</a></li>
                <li><a href="./pages/Project.html">Projects</a></li>
                <li><a href="./pages/about.html">About</a></li>
                <li><a href="#">Blog</a></li>
                <li><a href="./pages/contact.html">Contact</a></li>
            </ul>
            <div class="nav-right">
                <?php if ($userName): ?>
                    <div class="nav-account">
                        <button class="nav-account-btn">
                            <i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($userName); ?>
                            <i class="fas fa-chevron-down arr"></i>
                        </button>
                        <ul class="nav-dropdown">
                            <li><a href="./profile/profile_instructions.php"><i class="fas fa-id-card"></i> Create
                                    Profile</a></li>
                            <li><a href="./tests/take_test.php"><i class="fas fa-file-alt"></i> Aptitude Test</a></li>
                            <!-- <li><a href="#"><i class="fas fa-comments"></i> Chat with Others</a></li> -->
                            <li><a href="#"><i class="fas fa-users"></i> Know Your Team</a></li>
                            <li class="logout-li"><a href="./auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="./auth/login.html" class="nav-login-btn">
                        <i class="fas fa-sign-in-alt"></i> Log In
                    </a>
                <?php endif; ?>
            </div>
            <div class="nav-hamburger" id="navHamburger">
                <span></span><span></span><span></span>
            </div>
        </div>
    </nav>

    <!-- MOBILE MENU DRAWER -->
    <div class="mobile-menu" id="mobileMenu">
        <ul>
            <li class="active"><a href="./index.php"><i class="fas fa-home" style="color:var(--gold);width:18px"></i>
                    Home</a></li>
            <li><a href="./pages/Project.html"><i class="fas fa-building" style="color:var(--gold);width:18px"></i>
                    Projects</a></li>
            <li><a href="./pages/about.html"><i class="fas fa-info-circle" style="color:var(--gold);width:18px"></i>
                    About</a></li>
            <li><a href="#"><i class="fas fa-book" style="color:var(--gold);width:18px"></i> Blog</a></li>
            <li><a href="./pages/contact.html"><i class="fas fa-envelope" style="color:var(--gold);width:18px"></i>
                    Contact</a></li>
        </ul>
        <div class="mobile-menu-actions">
            <?php if ($userName): ?>
                <div class="mobile-account-section">
                    <div class="mobile-account-header" id="mobileAccountToggle">
                        <div class="mobile-account-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></div>
                        <div>
                            <div class="mobile-account-name"><?php echo htmlspecialchars($userName); ?></div>
                        </div>
                        <i class="fas fa-chevron-down mobile-arrow"></i>
                    </div>
                    <ul class="mobile-menu-items">
                        <li><a href="./profile/profile_instructions.php"><i class="fas fa-id-card"></i> Create Profile</a>
                        </li>
                        <li><a href="./tests/take_test.php"><i class="fas fa-file-alt"></i> Aptitude Test</a></li>
                        <!-- <li><a href="#"><i class="fas fa-comments"></i> Chat with Others</a></li> -->
                        <li><a href="#"><i class="fas fa-users"></i> Know Your Team</a></li>
                        <li class="mobile-logout"><a href="./auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="./auth/login.html" class="mobile-login-btn">
                    <i class="fas fa-sign-in-alt"></i> Log In to Your Account
                </a>
            <?php endif; ?>
        </div>
    </div>

    <main>

        <!-- HERO -->
        <section class="rsp-hero">
            <div class="hero-particles">
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
            </div>
            <div class="hero-orb hero-orb-1"></div>
            <div class="hero-orb hero-orb-2"></div>
            <div class="hero-orb hero-orb-3"></div>
            <div class="rsp-hero-overlay"></div>
            <div class="rsp-hero-content">
                <div class="hero-left">
                    <div class="hero-badge"><span class="hero-badge-dot"></span> India's #1 Real Estate Training</div>
                    <h1 class="hero-h1">Master the Art of<span class="gold-text">Real Estate</span></h1>
                    <p class="hero-desc">Empowering businesses with skilled professionals — we build high-performing
                        sales teams that drive smarter strategies and stronger results.</p>
                    <div class="hero-btns">
                        <a href="#" class="btn-luxury"><i class="fas fa-rocket"></i> Explore Now</a>
                        <a href="./pages/about.html" class="btn-glass"><i class="fas fa-play-circle"></i> Learn More</a>
                    </div>
                    <div class="hero-mini-stats">
                        <div class="hms-item">
                            <div class="hms-num">500+</div>
                            <div class="hms-label">Professionals Trained</div>
                        </div>
                        <div class="hms-divider"></div>
                        <div class="hms-item">
                            <div class="hms-num">50+</div>
                            <div class="hms-label">Live Projects</div>
                        </div>
                        <div class="hms-divider"></div>
                        <div class="hms-item">
                            <div class="hms-num">4.9★</div>
                            <div class="hms-label">Success Rate</div>
                        </div>
                    </div>
                </div>
                <div class="hero-right">
                    <div class="hero-chip hero-chip-1"><i class="fas fa-shield-alt"></i> Trusted by 500+ Professionals
                    </div>
                    <div class="hero-chip hero-chip-2"><i class="fas fa-star"></i> Top Rated Training</div>
                    <div class="hero-glass-card">
                        <div class="hgc-title">Live Training Dashboard</div>
                        <div class="hgc-sub">Real-time performance overview</div>
                        <div class="hgc-stat-row">
                            <div class="hgc-stat">
                                <div class="hgc-stat-num">₹2.4Cr</div>
                                <div class="hgc-stat-label">Avg Deal Closed</div>
                            </div>
                            <div class="hgc-stat">
                                <div class="hgc-stat-num">98%</div>
                                <div class="hgc-stat-label">Placement Rate</div>
                            </div>
                            <div class="hgc-stat">
                                <div class="hgc-stat-num">10+</div>
                                <div class="hgc-stat-label">Years Active</div>
                            </div>
                            <div class="hgc-stat">
                                <div class="hgc-stat-num">50+</div>
                                <div class="hgc-stat-label">Projects</div>
                            </div>
                        </div>
                        <div class="hgc-projects">
                            <div class="hgc-proj-item">
                                <div class="hgc-proj-left">
                                    <div class="hgc-proj-icon"><i class="fas fa-building"></i></div>
                                    <div>
                                        <div class="hgc-proj-name">DLF Privana</div>
                                        <div class="hgc-proj-type">Luxury · Gurgaon</div>
                                    </div>
                                </div>
                                <div class="hgc-proj-badge">Live</div>
                            </div>
                            <div class="hgc-proj-item">
                                <div class="hgc-proj-left">
                                    <div class="hgc-proj-icon"><i class="fas fa-building"></i></div>
                                    <div>
                                        <div class="hgc-proj-name">Signature Global</div>
                                        <div class="hgc-proj-type">High Rise · Dwarka</div>
                                    </div>
                                </div>
                                <div class="hgc-proj-badge">Hot 🔥</div>
                            </div>
                            <div class="hgc-proj-item">
                                <div class="hgc-proj-left">
                                    <div class="hgc-proj-icon"><i class="fas fa-globe"></i></div>
                                    <div>
                                        <div class="hgc-proj-name">Dubai Projects</div>
                                        <div class="hgc-proj-type">International · UAE</div>
                                    </div>
                                </div>
                                <div class="hgc-proj-badge">New</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        </section>

        <!-- FOLLOW US — SOCIAL MEDIA (NEW SECTION) -->
        <section class="rsp-social-follow">
            <div class="rsp-container">
                <div class="social-follow-head">
                    <div class="section-tag" style="justify-content:center;">Stay Connected</div>
                    <h2 class="section-h">Follow Us on <span>Social Media</span></h2>
                </div>
                <div class="social-follow-grid">

                    <a href="https://www.instagram.com/realtysmartz/?hl=en" target="_blank" rel="noopener"
                        class="social-follow-card">
                        <div class="social-follow-icon"><i class="fab fa-instagram"></i></div>
                        <div class="social-follow-info">
                            <div class="social-follow-platform">Instagram</div>
                            <div class="social-follow-handle">@realtysmartz</div>
                            <span class="social-follow-btn"><i class="fas fa-plus"></i> Follow</span>
                        </div>
                    </a>

                    <a href="https://www.linkedin.com/company/realtysmartz/posts/?feedView=all" target="_blank"
                        rel="noopener" class="social-follow-card">
                        <div class="social-follow-icon"><i class="fab fa-linkedin-in"></i></div>
                        <div class="social-follow-info">
                            <div class="social-follow-platform">LinkedIn</div>
                            <div class="social-follow-handle">Realty Smartz Pvt. Ltd.</div>
                            <span class="social-follow-btn"><i class="fas fa-link"></i> Connect</span>
                        </div>
                    </a>

                    <a href="https://www.youtube.com/@realtysmartz" target="_blank" rel="noopener"
                        class="social-follow-card">
                        <div class="social-follow-icon"><i class="fab fa-youtube"></i></div>
                        <div class="social-follow-info">
                            <div class="social-follow-platform">YouTube</div>
                            <div class="social-follow-handle">Realty Smartz Pvt. Ltd.</div>
                            <span class="social-follow-btn"><i class="fas fa-bell"></i> Subscribe</span>
                        </div>
                    </a>

                </div>
            </div>
        </section>

        <!-- SERVICES -->
        <section class="rsp-services"></section>

        <!-- SERVICES -->
        <section class="rsp-services">
            <div class="section-divider"></div>
            <div class="rsp-container">
                <div class="sec-head">
                    <div class="section-tag">What We Offer</div>
                    <h2 class="section-h">Our Core <span>Services</span></h2>
                    <p class="section-p">Delivering excellence in real estate training, employee growth, and market
                        expertise.</p>
                </div>
                <div class="srv-grid">
                    <div class="srv-card">
                        <div class="srv-num">01</div>
                        <div class="srv-icon"><img src="./assets/img/icon/icon1.svg" alt="Real Estate"></div>
                        <h3>Real Estate Expertise</h3>
                        <p>Delivering top-tier property solutions with trusted market insights and proven strategies.
                        </p>
                        <div class="srv-arrow"><i class="fas fa-arrow-right"></i> Know More</div>
                    </div>
                    <div class="srv-card">
                        <div class="srv-num">02</div>
                        <div class="srv-icon"><img src="./assets/img/icon/icon2.svg" alt="Achievements"></div>
                        <h3>Employee Achievements</h3>
                        <p>Celebrating our team's milestones, growth, and outstanding contributions every step.</p>
                        <div class="srv-arrow"><i class="fas fa-arrow-right"></i> Know More</div>
                    </div>
                    <div class="srv-card">
                        <div class="srv-num">03</div>
                        <div class="srv-icon"><img src="./assets/img/icon/icon3.svg" alt="Training"></div>
                        <h3>Project Training</h3>
                        <p>Empowering employees through expert-led training on the latest real estate developments.</p>
                        <div class="srv-arrow"><i class="fas fa-arrow-right"></i> Know More</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- YOUTUBE -->
        <section class="rsp-videos">
            <div class="section-divider"></div>
            <div class="rsp-container">
                <div class="sec-head text-center">
                    <div class="section-tag">YouTube</div>
                    <h2 class="section-h">We are Viral on <span>YouTube</span> 🔥</h2>
                    <p class="section-p">Watch our most popular real estate content loved by thousands across India.</p>
                </div>
                <div class="vid-grid">
                    <?php if ($homeVideos && $homeVideos->num_rows > 0): ?>
                        <?php while ($v = $homeVideos->fetch_assoc()): ?>
                            <div class="vid-card">
                                <div class="vid-frame"><iframe src="<?= htmlspecialchars($v['embed_url']) ?>" allowfullscreen
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                                </div>
                                <div class="vid-body">
                                    <div class="vid-views"><i class="fas fa-eye"></i> Watch Now</div>
                                    <h3><?= htmlspecialchars($v['title']) ?></h3>
                                    <p><?= htmlspecialchars($v['location'] ?: $v['description']) ?></p>
                                    <div class="vid-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                                            class="fas fa-star"></i><i class="fas fa-star"></i><i
                                            class="fas fa-star-half-alt"></i></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
                <div class="view-more-wrap">
                    <a href="/videos.php" class="btn-outline-gold">View All Videos <i
                            class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </section>

        <!-- ABOUT -->
        <section class="rsp-about">
            <div class="section-divider"></div>
            <div class="rsp-container">
                <div class="about-grid">
                    <div class="about-img-wrap">
                        <div class="about-deco"></div>
                        <div class="about-img-frame">
                            <img src="./assets/img/learn-video-thumbnail.jpg" alt="Learn with Realty Smartz">
                        </div>
                        <a class="about-play-btn popup-video" href="./assets/img/gallery/video_V2.mp4"><i
                                class="fas fa-play"></i></a>
                        <div class="about-float-card">
                            <div class="afc-num">500+</div>
                            <div class="afc-label">Trained Professionals</div>
                        </div>
                    </div>
                    <div>
                        <div class="section-tag">About Us</div>
                        <h2 class="section-h">Learn New Skills with Top <span>Realtors</span></h2>
                        <p class="section-p">Learn new skills, gain real-world insights, and grow your career by working
                            alongside top-performing realtors at Realty Smartz.</p>
                        <div class="feat-row">
                            <div class="feat-item">
                                <div class="feat-icon"><i class="fas fa-chart-line"></i></div>
                                <div>
                                    <h4>Live Market Exposure</h4>
                                    <p>Learn directly from experts actively closing deals in Gurgaon's dynamic real
                                        estate market.</p>
                                </div>
                            </div>
                            <div class="feat-item">
                                <div class="feat-icon"><i class="fas fa-handshake"></i></div>
                                <div>
                                    <h4>Expert Mentorship</h4>
                                    <p>Get one-on-one guidance from seasoned professionals with proven sales strategies.
                                    </p>
                                </div>
                            </div>
                            <div class="feat-item">
                                <div class="feat-icon"><i class="fas fa-building"></i></div>
                                <div>
                                    <h4>Live Project Training</h4>
                                    <p>Work on actual live projects — gain practical knowledge that accelerates your
                                        growth.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =================== PROJECTS — BENTO GRID =================== -->
        <section class="rsp-projects">
            <div class="section-divider"></div>
            <div class="rsp-container">
                <div class="sec-head text-center">
                    <div class="section-tag">Portfolio</div>
                    <h2 class="section-h">Explore Top <span>Projects</span></h2>
                    <p class="section-p">Handpicked premium real estate projects curated for our professionals.</p>
                </div>

                <!-- Filter Tabs -->
                <div class="proj-filters">
                    <button class="proj-filter-btn active" data-filter="all">All Projects</button>
                    <button class="proj-filter-btn" data-filter="featured">⭐ Featured</button>
                    <button class="proj-filter-btn" data-filter="luxury">💎 Luxury</button>
                    <button class="proj-filter-btn" data-filter="new">🌿 New Launch</button>
                    <button class="proj-filter-btn" data-filter="intl">🌍 International</button>
                </div>

                <!-- Bento Grid -->
                <div class="proj-bento">

                    <!-- Card 1 — Large Featured -->
                    <div class="proj-bento-card" data-category="featured">
                        <img class="proj-bento-img" src="./assets/img/gallery/topic1.png" alt="Shapoorji Pallonji">
                        <div class="proj-bento-top">
                            <div class="proj-bento-badge"><i class="fas fa-star"></i> Featured</div>
                            <div class="proj-bento-loc"><i class="fas fa-map-marker-alt"></i> Gurgaon</div>
                        </div>
                        <div class="proj-bento-overlay">
                            <div class="proj-bento-content">
                                <h3>Shapoorji Pallonji</h3>
                                <div class="proj-bento-meta">
                                    Luxury Residences <span class="proj-bento-meta-dot"></span> 3BHK & 4BHK
                                </div>
                                <div class="proj-bento-cta">
                                    <a href="./pages/Project.html" class="proj-bento-link"><i
                                            class="fas fa-arrow-right"></i> View Details</a>
                                    <div class="proj-bento-price"><i class="fas fa-tag"></i> ₹3.2Cr+</div>
                                    <div class="proj-bento-icon-btn"><i class="fas fa-heart"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="proj-bento-card">
                        <img class="proj-bento-img" src="./assets/img/gallery/topic2.png" alt="DLF Privana">
                        <div class="proj-bento-top">
                            <div class="proj-bento-badge"><i class="fas fa-gem"></i> Premium</div>
                            <div class="proj-bento-loc"><i class="fas fa-map-marker-alt"></i> Gurgaon</div>
                        </div>
                        <div class="proj-bento-overlay">
                            <div class="proj-bento-content">
                                <h3>DLF Privana</h3>
                                <div class="proj-bento-meta">Ultra Luxury <span class="proj-bento-meta-dot"></span>
                                    Sector 76</div>
                                <div class="proj-bento-cta">
                                    <a href="./pages/Project.html" class="proj-bento-link"><i
                                            class="fas fa-arrow-right"></i> View Details</a>
                                    <div class="proj-bento-icon-btn"><i class="fas fa-heart"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="proj-bento-card">
                        <img class="proj-bento-img" src="./assets/img/gallery/topic3.png" alt="Signature Global Daxin">
                        <div class="proj-bento-top">
                            <div class="proj-bento-badge"><i class="fas fa-fire"></i> Hot</div>
                            <div class="proj-bento-loc"><i class="fas fa-map-marker-alt"></i> Sohna</div>
                        </div>
                        <div class="proj-bento-overlay">
                            <div class="proj-bento-content">
                                <h3>Signature Global Daxin</h3>
                                <div class="proj-bento-meta">Affordable Luxury <span class="proj-bento-meta-dot"></span>
                                    South GGN</div>
                                <div class="proj-bento-cta">
                                    <a href="./pages/Project.html" class="proj-bento-link"><i
                                            class="fas fa-arrow-right"></i> View Details</a>
                                    <div class="proj-bento-icon-btn"><i class="fas fa-heart"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4 -->
                    <div class="proj-bento-card">
                        <img class="proj-bento-img" src="./assets/img/gallery/topic4.png" alt="Heritage Plots">
                        <div class="proj-bento-top">
                            <div class="proj-bento-badge"><i class="fas fa-leaf"></i> New Launch</div>
                        </div>
                        <div class="proj-bento-overlay">
                            <div class="proj-bento-content">
                                <h3>Heritage Plots, Pataudi</h3>
                                <div class="proj-bento-meta">Plotted Dev. <span class="proj-bento-meta-dot"></span>
                                    Pataudi Road</div>
                                <div class="proj-bento-cta">
                                    <a href="./pages/Project.html" class="proj-bento-link"><i
                                            class="fas fa-arrow-right"></i> View Details</a>
                                    <div class="proj-bento-icon-btn"><i class="fas fa-heart"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 5 -->
                    <div class="proj-bento-card">
                        <img class="proj-bento-img" src="./assets/img/gallery/topic5.png" alt="Omaxe The State">
                        <div class="proj-bento-top">
                            <div class="proj-bento-badge"><i class="fas fa-star"></i> Featured</div>
                        </div>
                        <div class="proj-bento-overlay">
                            <div class="proj-bento-content">
                                <h3>Omaxe The State</h3>
                                <div class="proj-bento-meta">Commercial <span class="proj-bento-meta-dot"></span> Dwarka
                                </div>
                                <div class="proj-bento-cta">
                                    <a href="./pages/Project.html" class="proj-bento-link"><i
                                            class="fas fa-arrow-right"></i> View Details</a>
                                    <div class="proj-bento-icon-btn"><i class="fas fa-heart"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 6 — Large -->
                    <div class="proj-bento-card">
                        <img class="proj-bento-img" src="./assets/img/gallery/topic6.png" alt="Ganga Realty">
                        <div class="proj-bento-top">
                            <div class="proj-bento-badge"><i class="fas fa-bolt"></i> Trending</div>
                            <div class="proj-bento-loc"><i class="fas fa-map-marker-alt"></i> Gurgaon</div>
                        </div>
                        <div class="proj-bento-overlay">
                            <div class="proj-bento-content">
                                <h3>Ganga Realty</h3>
                                <div class="proj-bento-meta">
                                    Premium Residences <span class="proj-bento-meta-dot"></span> Sector 85
                                </div>
                                <div class="proj-bento-cta">
                                    <a href="./pages/Project.html" class="proj-bento-link"><i
                                            class="fas fa-arrow-right"></i> View Details</a>
                                    <div class="proj-bento-price"><i class="fas fa-tag"></i> ₹1.8Cr+</div>
                                    <div class="proj-bento-icon-btn"><i class="fas fa-heart"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 7 -->
                    <div class="proj-bento-card">
                        <img class="proj-bento-img" src="./assets/img/gallery/topic7.png" alt="Trehan">
                        <div class="proj-bento-top">
                            <div class="proj-bento-badge"><i class="fas fa-gem"></i> Premium</div>
                        </div>
                        <div class="proj-bento-overlay">
                            <div class="proj-bento-content">
                                <h3>Trehan, Sector 35</h3>
                                <div class="proj-bento-meta">Residential <span class="proj-bento-meta-dot"></span>
                                    Sector 35</div>
                                <div class="proj-bento-cta">
                                    <a href="./pages/Project.html" class="proj-bento-link"><i
                                            class="fas fa-arrow-right"></i> View Details</a>
                                    <div class="proj-bento-icon-btn"><i class="fas fa-heart"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 8 -->
                    <div class="proj-bento-card">
                        <img class="proj-bento-img" src="./assets/img/gallery/topic8.png" alt="Dubai Projects">
                        <div class="proj-bento-top">
                            <div class="proj-bento-badge"><i class="fas fa-globe"></i> International</div>
                            <div class="proj-bento-loc"><i class="fas fa-map-marker-alt"></i> UAE</div>
                        </div>
                        <div class="proj-bento-overlay">
                            <div class="proj-bento-content">
                                <h3>Dubai Projects</h3>
                                <div class="proj-bento-meta">International <span class="proj-bento-meta-dot"></span>
                                    Dubai, UAE</div>
                                <div class="proj-bento-cta">
                                    <a href="./pages/Project.html" class="proj-bento-link"><i
                                            class="fas fa-arrow-right"></i> View Details</a>
                                    <div class="proj-bento-icon-btn"><i class="fas fa-heart"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="view-more-wrap">
                    <a href="./pages/Project.html" class="btn-outline-gold">View All Projects <i
                            class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </section>

        <!-- ACHIEVEMENTS -->
        <section class="rsp-achievements">
            <div class="section-divider"></div>
            <div class="rsp-container">
                <div class="ach-grid">
                    <div>
                        <div class="section-tag">Recognition</div>
                        <h2 class="section-h">Celebrating Employee <span>Achievements</span> 🏆</h2>
                        <p class="section-p">We recognize every milestone our team achieves in Gurgaon's competitive
                            real estate landscape.</p>
                        <div class="feat-row" style="margin-top:32px;">
                            <div class="feat-item">
                                <div class="feat-icon"><i class="fas fa-trophy"></i></div>
                                <div>
                                    <h4>Record-Breaking Sales</h4>
                                    <p>Our team consistently surpasses sales targets and delivers exceptional results
                                        across projects.</p>
                                </div>
                            </div>
                            <div class="feat-item">
                                <div class="feat-icon"><i class="fas fa-gem"></i></div>
                                <div>
                                    <h4>Client Excellence</h4>
                                    <p>Employees recognized for outstanding service and long-term client relationships.
                                    </p>
                                </div>
                            </div>
                            <div class="feat-item">
                                <div class="feat-icon"><i class="fas fa-star"></i></div>
                                <div>
                                    <h4>Culture of Growth</h4>
                                    <p>We celebrate individual milestones, fostering a culture of appreciation and
                                        learning.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="about-img-wrap">
                        <div class="about-deco"></div>
                        <div class="about-img-frame"><img src="./assets/img/gallery/about3.png" alt="Achievements">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- EVENTS -->
        <?php if ($events && $events->num_rows > 0): ?>
            <section class="rsp-events">
                <div class="section-divider"></div>
                <div class="rsp-container">
                    <div class="sec-head text-center">
                        <div class="section-tag">What's Coming Up</div>
                        <h2 class="section-h">Upcoming <span>Events</span> 📅</h2>
                        <p class="section-p">Join our upcoming workshops, webinars and networking events curated for real
                            estate professionals.</p>
                    </div>
                    <div class="events-grid">
                        <?php while ($e = $events->fetch_assoc()): ?>
                            <?php
                            $ts = strtotime($e['event_date']);
                            $day = date('d', $ts);
                            $month = date('M', $ts);
                            ?>
                            <div class="event-card">
                                <div class="event-media">
                                    <img src="/uploads/events/<?= htmlspecialchars($e['image']) ?>"
                                        alt="<?= htmlspecialchars($e['title']) ?>" loading="lazy">
                                    <div class="event-date-badge">
                                        <span class="edb-day"><?= htmlspecialchars($day) ?></span>
                                        <span class="edb-month"><?= htmlspecialchars($month) ?></span>
                                    </div>
                                    <?php if (!empty($e['event_type'])): ?>
                                        <div class="event-type-chip"><?= htmlspecialchars($e['event_type']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="event-body">
                                    <h3 class="event-title"><?= htmlspecialchars($e['title']) ?></h3>
                                    <?php if (!empty($e['subtitle'])): ?>
                                        <div class="event-subtitle"><?= htmlspecialchars($e['subtitle']) ?></div>
                                    <?php endif; ?>
                                    <div class="event-meta">
                                        <?php if (!empty($e['event_time'])): ?>
                                            <div class="event-meta-item"><i class="fas fa-clock"></i>
                                                <?= htmlspecialchars($e['event_time']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($e['location'])): ?>
                                            <div class="event-meta-item"><i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($e['location']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($e['description'])): ?>
                                        <p class="event-desc"><?= htmlspecialchars($e['description']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($e['registration_link'])): ?>
                                        <div class="event-cta">
                                            <a href="<?= htmlspecialchars($e['registration_link']) ?>" target="_blank"
                                                rel="noopener" class="btn-outline-gold">
                                                <?= htmlspecialchars($e['button_text'] ?: 'Register Now') ?> <i
                                                    class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>



        <!-- TRAINING -->
        <section class="rsp-training">
            <div class="section-divider"></div>
            <div class="rsp-container">
                <div class="training-grid">
                    <div class="training-img-wrap">
                        <div class="training-img-frame"><img src="./assets/img/gallery/about2.png" alt="Training"></div>
                        <div class="training-float-badge">
                            <div class="tfb-icon">🎓</div>
                            <div class="tfb-label">Certified Program</div>
                            <div class="tfb-val">Industry Recognized</div>
                        </div>
                    </div>
                    <div>
                        <div class="section-tag">Training</div>
                        <h2 class="section-h">Empowering Through <span>Training</span> 🚀</h2>
                        <p class="section-p">Our dedicated training programs equip team members with in-depth real
                            estate knowledge, sales skills, and project insights.</p>
                        <div class="train-points">
                            <div class="train-point">
                                <div class="train-point-icon"><i class="fas fa-graduation-cap"></i></div>Expert-led
                                sessions on live real estate projects
                            </div>
                            <div class="train-point">
                                <div class="train-point-icon"><i class="fas fa-chart-bar"></i></div>Market analytics and
                                sales strategy workshops
                            </div>
                            <div class="train-point">
                                <div class="train-point-icon"><i class="fas fa-certificate"></i></div>Certifications and
                                performance-based recognition
                            </div>
                        </div>
                        <a href="#" class="btn-luxury"><i class="fas fa-graduation-cap"></i> Explore Programs</a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="rsp-footer">
        <div class="rsp-container">
            <div class="footer-grid">
                <div class="footer-about">
                    <div class="footer-logo"><a href="index.php"><img src="./assets/img/logo/Pathshala.webp"
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
                        <li><a href="./pages/Project.html"><i class="fas fa-chevron-right"></i> DLF</a></li>
                        <li><a href="./pages/Project.html"><i class="fas fa-chevron-right"></i> Shapoorji Pallonji</a>
                        </li>
                        <li><a href="./pages/Project.html"><i class="fas fa-chevron-right"></i> Ganga Realty</a></li>
                        <li><a href="./pages/Project.html"><i class="fas fa-chevron-right"></i> Signature Global</a>
                        </li>
                        <li><a href="./pages/Project.html"><i class="fas fa-chevron-right"></i> M3M</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="./pages/Project.html"><i class="fas fa-chevron-right"></i> Heritage Homes Plots</a>
                        </li>
                        <li><a href="./pages/Project.html"><i class="fas fa-chevron-right"></i> Omaxe The State</a></li>
                        <li><a href="./pages/Project.html"><i class="fas fa-chevron-right"></i> Trehan</a></li>
                        <li><a href="./pages/Project.html"><i class="fas fa-chevron-right"></i> Danube Properties</a>
                        </li>
                        <li><a href="./pages/Project.html"><i class="fas fa-chevron-right"></i> Dubai Real Estate</a>
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

    <!-- JS -->
    <script src="./assets/js/vendor/jquery-1.12.4.min.js"></script>
    <script src="./assets/js/vendor/modernizr-3.5.0.min.js"></script>
    <script src="./assets/js/popper.min.js"></script>
    <script src="./assets/js/bootstrap.min.js"></script>
    <script src="./assets/js/owl.carousel.min.js"></script>
    <script src="./assets/js/slick.min.js"></script>
    <script src="./assets/js/wow.min.js"></script>
    <script src="./assets/js/jquery.magnific-popup.js"></script>
    <script src="./assets/js/jquery.counterup.min.js"></script>
    <script src="./assets/js/waypoints.min.js"></script>
    <script src="./assets/js/plugins.js"></script>
    <script src="./assets/js/main.js"></script>

    <script>
        $(document).ready(function () {

            // ── Preloader ──
            $(window).on('load', function () {
                setTimeout(function () { $('#preloader-active').fadeOut(700); }, 1200);
            });

            // ── Navbar scroll ──
            $(window).on('scroll', function () {
                if ($(this).scrollTop() > 60) {
                    $('#rspNav').addClass('scrolled');
                    $('#back-top').fadeIn(300);
                } else {
                    $('#rspNav').removeClass('scrolled');
                    $('#back-top').fadeOut(300);
                }
            });

            // ── Scroll top ──
            $('#back-top a').on('click', function (e) {
                e.preventDefault();
                $('html, body').animate({ scrollTop: 0 }, 600);
            });

            // ── Video popup ──
            if (typeof $.fn.magnificPopup !== 'undefined') {
                $('.popup-video').magnificPopup({ type: 'iframe', mainClass: 'mfp-fade' });
            }

            // ── Mobile Hamburger — NEW DRAWER ──
            $('#navHamburger').on('click', function () {
                $(this).toggleClass('open');
                $('#mobileMenu').toggleClass('active');
                $('body').toggleClass('menu-open');
            });



            // Close mobile menu on link click

            $('#mobileAccountToggle').on('click', function (e) {

                e.preventDefault();
                e.stopPropagation();

                $('.mobile-account-section').toggleClass('active');

            });

            $('#mobileMenu a').on('click', function () {

                if ($(this).closest('.mobile-account-header').length) {
                    return;
                }

                $('#navHamburger').removeClass('open');
                $('#mobileMenu').removeClass('active');
                $('body').removeClass('menu-open');

            });

            // Close mobile menu when resizing to desktop
            $(window).on('resize', function () {
                if ($(window).width() > 991) {
                    $('#navHamburger').removeClass('open');
                    $('#mobileMenu').removeClass('active');
                    $('body').removeClass('menu-open');
                }
            });

            // ── Cursor Glow ──
            $(document).on('mousemove', function (e) {
                $('#cursorGlow').css({ left: e.clientX + 'px', top: e.clientY + 'px' });
            });

            // ── Theme Toggle ──
            var savedTheme = localStorage.getItem('rsp-theme') || 'dark';
            $('html').attr('data-theme', savedTheme);

            $('#themeToggle').on('click', function () {
                var current = $('html').attr('data-theme');
                var next = current === 'dark' ? 'light' : 'dark';
                $('html').attr('data-theme', next);
                localStorage.setItem('rsp-theme', next);
            });

            // ── Project Filter Tabs (now fully functional) ──
            $('.proj-filter-btn').on('click', function () {
                $('.proj-filter-btn').removeClass('active');
                $(this).addClass('active');

                var filter = $(this).data('filter');
                var $cards = $('.proj-bento-card');
                var visibleCount = 0;

                $cards.each(function () {
                    var cats = ($(this).data('category') || '').toString().split(' ');
                    var show = (filter === 'all') || (cats.indexOf(filter) !== -1);
                    if (show) {
                        $(this).stop(true, true).fadeIn(300);
                        visibleCount++;
                    } else {
                        $(this).stop(true, true).fadeOut(200);
                    }
                });

                if (visibleCount === 0) {
                    if ($('#projBentoEmpty').length === 0) {
                        $('.proj-bento').after('<p id="projBentoEmpty" class="section-p text-center" style="grid-column:1/-1;margin-top:20px;">No projects found.</p>');
                    } else {
                        $('#projBentoEmpty').show();
                    }
                } else {
                    $('#projBentoEmpty').hide();
                }
            });

        });
    </script>

</body>

</html>