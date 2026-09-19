<?php
session_start();
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name']
          : (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About Us | Realty Smartz Pathshala</title>
    <meta name="description" content="Learn about Realty Smartz Pathshala — empowering real estate professionals with world-class training and community.">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicon.png">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Site CSS -->
    <link rel="stylesheet" href="../assets/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="../assets/css/animate.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <style>
        /* ═══════════════════════════════════════════
           PAGE WRAPPER
        ═══════════════════════════════════════════ */
        .about-page-wrapper {
            padding-top: 100px;
            background: var(--bg-primary);
            transition: background 0.3s ease;
            min-height: 100vh;
        }
        /* ═══════════════════════════════════════════
           HERO BANNER
        ═══════════════════════════════════════════ */
        .about-hero {
            position: relative;
            padding: 80px 0 70px;
            text-align: center;
            overflow: hidden;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color, rgba(255,255,255,0.08));
        }
        .about-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(201,168,76,0.10) 0%, transparent 70%);
            pointer-events: none;
        }
        .about-hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 700;
            color: var(--gold, #c9a84c);
            margin-bottom: 12px;
            position: relative;
        }
        .about-breadcrumb {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.88rem;
            color: var(--text-secondary);
            position: relative;
        }
        .about-breadcrumb a {
            color: var(--gold, #c9a84c);
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .about-breadcrumb a:hover { opacity: 0.75; }
        .about-breadcrumb .sep {
            color: var(--text-secondary);
            opacity: 0.5;
        }
        /* ═══════════════════════════════════════════
           SECTION COMMON
        ═══════════════════════════════════════════ */
        .about-section-pad {
            padding: 80px 0;
        }
        .section-label {
            display: inline-block;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--gold, #c9a84c);
            margin-bottom: 10px;
        }
        .section-heading {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.6rem, 3.5vw, 2.4rem);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0;
            line-height: 1.3;
        }
        .gold-underline {
            width: 56px;
            height: 3px;
            background: var(--gold, #c9a84c);
            border-radius: 2px;
            margin: 14px 0 24px;
        }
        /* ═══════════════════════════════════════════
           ABOUT INTRO SECTION
        ═══════════════════════════════════════════ */
        .about-intro-card {
            background: var(--card-bg, rgba(255,255,255,0.05));
            border: 1px solid var(--border-color, rgba(255,255,255,0.10));
            border-radius: 24px;
            overflow: hidden;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 8px 40px rgba(0,0,0,0.15);
        }
        .about-intro-text {
            padding: 48px 44px;
        }
        .about-intro-text p {
            font-size: 1rem;
            line-height: 1.85;
            color: var(--text-secondary);
            margin-bottom: 0;
        }
        .about-intro-img {
            height: 100%;
            min-height: 340px;
            object-fit: cover;
            width: 100%;
            display: block;
        }
        /* ═══════════════════════════════════════════
           LEARNER OUTCOMES SECTION
        ═══════════════════════════════════════════ */
        .outcomes-section {
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color, rgba(255,255,255,0.07));
            border-bottom: 1px solid var(--border-color, rgba(255,255,255,0.07));
        }
        .outcomes-img-wrap {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0,0,0,0.18);
        }
        .outcomes-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .outcome-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 18px;
            background: var(--card-bg, rgba(255,255,255,0.04));
            border: 1px solid var(--border-color, rgba(255,255,255,0.08));
            border-radius: 14px;
            margin-bottom: 14px;
            transition: border-color 0.25s ease, transform 0.25s ease;
        }
        .outcome-item:hover {
            border-color: var(--gold, #c9a84c);
            transform: translateX(4px);
        }
        .outcome-icon {
            width: 36px;
            height: 36px;
            background: rgba(201,168,76,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--gold, #c9a84c);
            font-size: 0.9rem;
        }
        .outcome-text {
            font-size: 0.95rem;
            color: var(--text-secondary);
            line-height: 1.6;
            margin: 0;
            padding-top: 6px;
        }
        /* ═══════════════════════════════════════════
           STATS STRIP
        ═══════════════════════════════════════════ */
        .stats-strip {
            padding: 56px 0;
            background: var(--bg-primary);
        }
        .stat-item {
            text-align: center;
            padding: 24px 16px;
        }
        .stat-number {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 700;
            color: var(--gold, #c9a84c);
            display: block;
            line-height: 1;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-divider {
            width: 1px;
            background: var(--border-color, rgba(255,255,255,0.10));
            align-self: stretch;
            margin: 12px 0;
        }
        /* ═══════════════════════════════════════════
           LIGHT THEME OVERRIDES
        ═══════════════════════════════════════════ */
        [data-theme="light"] .about-intro-card {
            background: rgba(255,255,255,0.92);
            border-color: rgba(0,0,0,0.08);
        }
        [data-theme="light"] .outcome-item {
            background: rgba(0,0,0,0.02);
            border-color: rgba(0,0,0,0.08);
        }
        [data-theme="light"] .stat-divider {
            background: rgba(0,0,0,0.08);
        }
        [data-theme="light"] .about-hero {
            background: #f5f5f0;
        }
        [data-theme="light"] .outcomes-section {
            background: #f9f8f5;
        }
        /* ═══════════════════════════════════════════
           RESPONSIVE
        ═══════════════════════════════════════════ */
        @media (max-width: 768px) {
            .about-intro-text {
                padding: 30px 24px;
            }
            .about-section-pad {
                padding: 50px 0;
            }
            .stat-divider { display: none; }
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="about-page-wrapper">
    <!-- ══════════════════════════════
         HERO BANNER
    ══════════════════════════════ -->
    <section class="about-hero">
        <div class="container">
            <h1 class="about-hero-title">About Us</h1>
            <nav class="about-breadcrumb" aria-label="breadcrumb">
                <a href="../index.php"><i class="fas fa-home"></i> Home</a>
                <span class="sep">/</span>
                <span>About Us</span>
            </nav>
        </div>
    </section>
    <!-- ══════════════════════════════
         INTRO SECTION
    ══════════════════════════════ -->
    <section class="about-section-pad">
        <div class="container">
            <div class="about-intro-card">
                <div class="row g-0 align-items-stretch">
                    <!-- Text -->
                    <div class="col-lg-6">
                        <div class="about-intro-text h-100 d-flex flex-column justify-content-center">
                            <span class="section-label">Who We Are</span>
                            <h2 class="section-heading">Welcome to Realty Smartz Pathshala</h2>
                            <div class="gold-underline"></div>
                            <p>
                                Your trusted destination for quality, innovation, and excellence.
                                We are dedicated to delivering value through projects that matter.
                                We focus on techniques to engage effectively with real estate
                                professionals by creating safe, understanding, and inclusive
                                learning environments. Through empathy, patience, and consistent
                                support, we help build trust and professional connection.
                            </p>
                            <p class="mt-3">
                                Our platform connects millions of people from around the world,
                                learning together in a space where online education feels easy,
                                natural, and empowering. We believe everyone deserves the chance
                                to grow, connect, and thrive — no matter their background or
                                circumstances.
                            </p>
                        </div>
                    </div>
                    <!-- Image -->
                    <div class="col-lg-6">
                        <img src="../assets/img/elements/a2.jpg"
                             alt="Realty Smartz Pathshala"
                             class="about-intro-img">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ══════════════════════════════
         STATS STRIP
    ══════════════════════════════ -->
    <section class="stats-strip">
        <div class="container">
            <div class="row justify-content-center align-items-center text-center g-0">
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Professionals Trained</span>
                    </div>
                </div>
                <div class="col-auto d-none d-md-block stat-divider"></div>
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">20+</span>
                        <span class="stat-label">Top Projects</span>
                    </div>
                </div>
                <div class="col-auto d-none d-md-block stat-divider"></div>
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">10+</span>
                        <span class="stat-label">Expert Mentors</span>
                    </div>
                </div>
                <div class="col-auto d-none d-md-block stat-divider"></div>
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">5★</span>
                        <span class="stat-label">Community Rating</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ══════════════════════════════
         LEARNER OUTCOMES
    ══════════════════════════════ -->
    <section class="outcomes-section about-section-pad">
        <div class="container">
            <div class="row align-items-center g-5">
                <!-- Image -->
                <div class="col-lg-5">
                    <div class="outcomes-img-wrap">
                        <img src="../assets/img/gallery/about3.png" alt="Learner Outcomes">
                    </div>
                </div>
                <!-- Content -->
                <div class="col-lg-7">
                    <span class="section-label">What You'll Gain</span>
                    <h2 class="section-heading">Learner outcomes on courses you will take</h2>
                    <div class="gold-underline"></div>
                    <div class="outcome-item">
                        <div class="outcome-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <p class="outcome-text">
                            Techniques to engage effectively with clients and build lasting
                            professional relationships in real estate.
                        </p>
                    </div>
                    <div class="outcome-item">
                        <div class="outcome-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <p class="outcome-text">
                            Join millions of people from around the world learning together
                            in an inclusive and supportive community.
                        </p>
                    </div>
                    <div class="outcome-item">
                        <div class="outcome-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <p class="outcome-text">
                            Online learning made easy and natural — grow at your own pace
                            with expert-guided content and assessments.
                        </p>
                    </div>
                    <div class="outcome-item">
                        <div class="outcome-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <p class="outcome-text">
                            Gain hands-on knowledge of top real estate projects, market
                            trends, and sales strategies from industry experts.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div><!-- /.about-page-wrapper -->
<?php include '../includes/footer.php'; ?>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>