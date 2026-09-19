<?php
session_start();
include '../includes/mail_config.php';
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name']
    : (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us | Realty Smartz Pathshala</title>
    <meta name="description"
        content="Get in touch with Realty Smartz Pathshala. We're here to help you with any queries about real estate training and career opportunities.">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/favicon.png">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
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
        .contact-page-wrapper {
            padding-top: 100px;
            background: var(--bg-primary);
            transition: background 0.3s ease;
            min-height: 100vh;
        }

        /* ═══════════════════════════════════════════
           HERO BANNER
        ═══════════════════════════════════════════ */
        .contact-hero {
            position: relative;
            padding: 80px 0 70px;
            text-align: center;
            overflow: hidden;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(201, 168, 76, 0.10) 0%, transparent 70%);
            pointer-events: none;
        }

        .contact-hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 700;
            color: var(--gold, #c9a84c);
            margin-bottom: 12px;
            position: relative;
        }

        .contact-breadcrumb {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.88rem;
            color: var(--text-secondary);
            position: relative;
        }

        .contact-breadcrumb a {
            color: var(--gold, #c9a84c);
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .contact-breadcrumb a:hover {
            opacity: 0.75;
        }

        .contact-breadcrumb .sep {
            opacity: 0.5;
        }

        /* ═══════════════════════════════════════════
           SECTION COMMON
        ═══════════════════════════════════════════ */
        .section-pad {
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
            margin: 14px 0 28px;
        }

        /* ═══════════════════════════════════════════
           MAP SECTION
        ═══════════════════════════════════════════ */
        .map-section {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.07));
            padding: 56px 0;
        }

        .map-wrap {
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.10));
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.18);
            position: relative;
        }

        .map-wrap iframe {
            display: block;
            width: 100%;
            height: 420px;
            border: 0;
            filter: grayscale(20%);
            transition: filter 0.3s ease;
        }

        .map-wrap:hover iframe {
            filter: grayscale(0%);
        }

        /* ═══════════════════════════════════════════
           CONTACT FORM CARD
        ═══════════════════════════════════════════ */
        .contact-card {
            background: var(--card-bg, rgba(255, 255, 255, 0.05));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.10));
            border-radius: 24px;
            padding: 44px 40px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.14);
            height: 100%;
        }

        /* Form Controls */
        .contact-form .form-control {
            background: var(--input-bg, rgba(255, 255, 255, 0.06));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.93rem;
            padding: 13px 16px;
            transition: all 0.25s ease;
            resize: none;
        }

        .contact-form .form-control:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.20);
            border-color: var(--gold, #c9a84c);
            background: var(--input-focus-bg, rgba(255, 255, 255, 0.09));
        }

        .contact-form .form-control::placeholder {
            color: var(--text-secondary);
            opacity: 0.65;
        }

        .contact-form label {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        /* Submit Button */
        .btn-contact-submit {
            background: linear-gradient(135deg, #c9a84c, #e6c96a);
            color: #1a1a1a;
            border: none;
            border-radius: 12px;
            padding: 13px 36px;
            font-size: 0.93rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
        }

        .btn-contact-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(201, 168, 76, 0.35);
            background: linear-gradient(135deg, #e6c96a, #c9a84c);
        }

        .btn-contact-submit:active {
            transform: translateY(0);
        }

        /* Form Success / Error Alert */
        .form-alert {
            display: none;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .form-alert.success {
            background: rgba(40, 167, 69, 0.15);
            border: 1px solid rgba(40, 167, 69, 0.35);
            color: #4caf7d;
        }

        .form-alert.error {
            background: rgba(220, 53, 69, 0.12);
            border: 1px solid rgba(220, 53, 69, 0.30);
            color: #e57373;
        }

        /* ═══════════════════════════════════════════
           CONTACT INFO CARD
        ═══════════════════════════════════════════ */
        .info-card {
            background: var(--card-bg, rgba(255, 255, 255, 0.05));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.10));
            border-radius: 24px;
            padding: 44px 36px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.14);
            height: 100%;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 20px 0;
            border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.07));
            transition: transform 0.2s ease;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item:hover {
            transform: translateX(4px);
        }

        .info-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(201, 168, 76, 0.12);
            border: 1px solid rgba(201, 168, 76, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: var(--gold, #c9a84c);
            flex-shrink: 0;
            transition: background 0.25s ease;
        }

        .info-item:hover .info-icon-wrap {
            background: rgba(201, 168, 76, 0.22);
        }

        .info-content h5 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .info-content p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.5;
        }

        .info-content a {
            color: var(--text-primary);
            text-decoration: none;
            transition: color 0.2s;
        }

        .info-content a:hover {
            color: var(--gold, #c9a84c);
        }

        /* Social Links in Info Card */
        .contact-social-wrap {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color, rgba(255, 255, 255, 0.07));
        }

        .contact-social-wrap p {
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 14px;
        }

        .contact-socials {
            display: flex;
            gap: 10px;
        }

        .contact-socials a {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--card-bg, rgba(255, 255, 255, 0.06));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.10));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .contact-socials a:hover {
            background: var(--gold, #c9a84c);
            color: #1a1a1a;
            border-color: var(--gold, #c9a84c);
            transform: translateY(-3px);
        }

        /* ═══════════════════════════════════════════
           FAQ STRIP
        ═══════════════════════════════════════════ */
        .faq-strip {
            background: var(--bg-secondary);
            padding: 70px 0;
            border-top: 1px solid var(--border-color, rgba(255, 255, 255, 0.07));
        }

        .faq-item {
            background: var(--card-bg, rgba(255, 255, 255, 0.04));
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            border-radius: 16px;
            padding: 22px 24px;
            margin-bottom: 14px;
            transition: border-color 0.25s;
            cursor: pointer;
        }

        .faq-item:hover {
            border-color: var(--gold, #c9a84c);
        }

        .faq-question {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .faq-question i {
            color: var(--gold, #c9a84c);
            font-size: 0.85rem;
            transition: transform 0.25s ease;
        }

        .faq-answer {
            font-size: 0.88rem;
            color: var(--text-secondary);
            line-height: 1.7;
            margin-top: 12px;
            display: none;
        }

        .faq-item.open .faq-answer {
            display: block;
        }

        .faq-item.open .faq-question i {
            transform: rotate(180deg);
        }

        /* ═══════════════════════════════════════════
           LIGHT THEME OVERRIDES
        ═══════════════════════════════════════════ */
        [data-theme="light"] .contact-card,
        [data-theme="light"] .info-card {
            background: #fff;
            border-color: rgba(0, 0, 0, 0.08);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
        }

        [data-theme="light"] .contact-form .form-control {
            background: #f8f8f6;
            border-color: rgba(0, 0, 0, 0.12);
            color: #1a1a1a;
        }

        [data-theme="light"] .contact-form .form-control:focus {
            background: #fff;
        }

        [data-theme="light"] .info-item {
            border-color: rgba(0, 0, 0, 0.07);
        }

        [data-theme="light"] .contact-socials a {
            background: #f5f5f0;
            border-color: rgba(0, 0, 0, 0.08);
            color: #555;
        }

        [data-theme="light"] .faq-item {
            background: #fff;
            border-color: rgba(0, 0, 0, 0.08);
        }

        [data-theme="light"] .contact-hero,
        [data-theme="light"] .map-section,
        [data-theme="light"] .faq-strip {
            background: #f5f5f0;
        }

        [data-theme="light"] .map-wrap {
            border-color: rgba(0, 0, 0, 0.10);
        }

        /* ═══════════════════════════════════════════
           RESPONSIVE
        ═══════════════════════════════════════════ */
        @media (max-width: 991px) {

            .contact-card,
            .info-card {
                padding: 32px 24px;
            }
        }

        @media (max-width: 767px) {
            .map-wrap iframe {
                height: 280px;
            }

            .section-pad {
                padding: 50px 0;
            }

            .map-section {
                padding: 36px 0;
            }
        }

        /* ═══════════════════════════════════════════
           FORM VALIDATION + LOADING STATES
        ═══════════════════════════════════════════ */
        .invalid-feedback-text {
            display: none;
            color: #e57373;
            font-size: 0.78rem;
            margin-top: 6px;
        }

        .form-control.is-invalid {
            border-color: #e57373 !important;
            box-shadow: 0 0 0 3px rgba(229, 115, 115, 0.15) !important;
        }

        .form-control.is-invalid~.invalid-feedback-text,
        .invalid-feedback-text.show {
            display: block;
        }

        .btn-contact-submit:disabled {
            opacity: 0.75;
            cursor: not-allowed;
            transform: none !important;
        }

        .spinner-ring {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(26, 26, 26, 0.3);
            border-top-color: #1a1a1a;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 6px;
            vertical-align: -2px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="contact-page-wrapper">
        <!-- ══════════════════════════════
         HERO BANNER
    ══════════════════════════════ -->
        <section class="contact-hero">
            <div class="container">
                <h1 class="contact-hero-title">Contact Us</h1>
                <nav class="contact-breadcrumb" aria-label="breadcrumb">
                    <a href="../index.php"><i class="fas fa-home"></i> Home</a>
                    <span class="sep">/</span>
                    <span>Contact Us</span>
                </nav>
            </div>
        </section>
        <!-- ══════════════════════════════
         MAP SECTION
    ══════════════════════════════ -->
        <section class="map-section">
            <div class="container">
                <div class="text-center mb-4">
                    <span class="section-label">Find Us</span>
                    <h2 class="section-heading">Our Location</h2>
                    <div class="gold-underline mx-auto"></div>
                </div>
                <div class="map-wrap">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1514.9099369185642!2d77.03727222899911!3d28.39946832849089!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390d230e36259593%3A0x833ef7f4c569d38!2sREALTY%20SMARTZ%20PVT%20LTD!5e1!3m2!1sen!2sin!4v1753340363743!5m2!1sen!2sin"
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </section>
        <!-- ══════════════════════════════
         CONTACT FORM + INFO
    ══════════════════════════════ -->
        <section class="section-pad">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="section-label">Reach Out</span>
                    <h2 class="section-heading">Get in Touch</h2>
                    <div class="gold-underline mx-auto"></div>
                    <p
                        style="color: var(--text-secondary); max-width: 520px; margin: 0 auto; font-size: 0.95rem; line-height: 1.7;">
                        Have a question or want to know more about our programs?
                        Fill in the form and our team will get back to you shortly.
                    </p>
                </div>
                <div class="row g-4 align-items-stretch">
                    <!-- ── Contact Form ── -->
                    <div class="col-lg-7">
                        <div class="contact-card">
                            <div class="form-alert success" id="form-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Your message has been sent successfully! We'll get back to you soon.
                            </div>
                            <div class="form-alert error" id="form-error">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Something went wrong. Please try again.
                            </div>
                            <form id="contact-form" class="contact-form" action="../process/contact_process.php"
                                method="post" novalidate>
                                <?php
                                if (!isset($_SESSION['csrf_token'])) {
                                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                                }
                                ?>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
                                <div class="row g-3">
                                    <!-- First Name -->
                                    <div class="col-sm-6">
                                        <div class="mb-0">
                                            <label for="first_name">First Name</label>
                                            <input class="form-control" type="text" name="first_name" id="first_name"
                                                placeholder="e.g. Rahul" required minlength="2" maxlength="50">
                                            <div class="invalid-feedback-text" data-error-for="first_name"></div>
                                        </div>
                                    </div>
                                    <!-- Last Name -->
                                    <div class="col-sm-6">
                                        <div class="mb-0">
                                            <label for="last_name">Last Name</label>
                                            <input class="form-control" type="text" name="last_name" id="last_name"
                                                placeholder="e.g. Sharma" required minlength="2" maxlength="50">
                                            <div class="invalid-feedback-text" data-error-for="last_name"></div>
                                        </div>
                                    </div>
                                    <!-- Email -->
                                    <div class="col-12">
                                        <div class="mb-0">
                                            <label for="email">Email Address</label>
                                            <input class="form-control" type="email" name="email" id="email"
                                                placeholder="you@example.com" required>
                                            <div class="invalid-feedback-text" data-error-for="email"></div>
                                        </div>
                                    </div>
                                    <!-- Phone -->
                                    <div class="col-12">
                                        <div class="mb-0">
                                            <label for="phone">Phone Number</label>
                                            <input class="form-control" type="tel" name="phone" id="phone"
                                                placeholder="+91 XXXXX XXXXX" required>
                                            <div class="invalid-feedback-text" data-error-for="phone"></div>
                                        </div>
                                    </div>
                                    <!-- Message -->
                                    <div class="col-12">
                                        <div class="mb-0">
                                            <label for="message">Your Message</label>
                                            <textarea class="form-control" name="message" id="message" rows="5"
                                                placeholder="Write your message here..." required minlength="10"
                                                maxlength="2000"></textarea>
                                            <div class="invalid-feedback-text" data-error-for="message"></div>
                                        </div>
                                    </div>
                                    <!-- Submit -->
                                    <div class="col-12 mt-2">
                                        <button type="submit" class="btn-contact-submit" id="contact-submit-btn">
                                            <span class="btn-label"><i class="fas fa-paper-plane"></i> Send
                                                Message</span>
                                            <span class="btn-spinner" style="display:none;">
                                                <span class="spinner-ring"></span> Sending...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- ── Contact Info ── -->
                    <div class="col-lg-5">
                        <div class="info-card">
                            <span class="section-label">Contact Info</span>
                            <h3 class="section-heading" style="font-size: 1.5rem;">We're always ready to help</h3>
                            <div class="gold-underline"></div>
                            <!-- Address -->
                            <div class="info-item">
                                <div class="info-icon-wrap">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="info-content">
                                    <h5>Office Address</h5>
                                    <p>Realty Smartz Pvt. Ltd.<br>
                                        Sector 69, Gurugram,<br>
                                        Haryana — 122101, India</p>
                                </div>
                            </div>
                            <!-- Phone -->
                            <div class="info-item">
                                <div class="info-icon-wrap">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="info-content">
                                    <h5>Phone Number</h5>
                                    <p>
                                        <a href="tel:+911234567890">MS. Chhavi Mittal +91 8800992566</a><br>
                                        Mon – Sat &nbsp;|&nbsp; 9 AM to 6 PM
                                    </p>
                                </div>
                            </div>
                            <!-- Email -->
                            <div class="info-item">
                                <div class="info-icon-wrap">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="info-content">
                                    <h5>Email Address</h5>
                                    <p>
                                        <a href="mailto:info@realtysmartz.in">info@realtysmartz.in</a><br>
                                        We reply within 24 hours
                                    </p>
                                </div>
                            </div>
                            <!-- Working Hours -->
                            <div class="info-item">
                                <div class="info-icon-wrap">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="info-content">
                                    <h5>Working Hours</h5>
                                    <p>Monday – Saturday: 9 AM – 6 PM<br>
                                        Sunday: Closed</p>
                                </div>
                            </div>
                            <!-- Social Links -->
                            <div class="contact-social-wrap">
                                <p>Follow Us</p>
                                <div class="contact-socials">
                                    <a href="https://www.linkedin.com/company/realtysmartz/posts/?feedView=all"
                                        target="_blank" title="LinkedIn">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                    <a href="https://www.instagram.com/realtysmartz/?hl=en" target="_blank"
                                        title="Instagram">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                    <a href="https://www.youtube.com/results?search_query=Realthy+smartz+pvt+ltd"
                                        target="_blank" title="YouTube">
                                        <i class="fab fa-youtube"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- ══════════════════════════════
         FAQ STRIP
    ══════════════════════════════ -->
        <section class="faq-strip">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="section-label">Help Center</span>
                    <h2 class="section-heading">Frequently Asked Questions</h2>
                    <div class="gold-underline mx-auto"></div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="faq-item">
                            <p class="faq-question">
                                How can I enroll in a course at Realty Smartz Pathshala?
                                <i class="fas fa-chevron-down"></i>
                            </p>
                            <div class="faq-answer">
                                You can register on our platform, browse available courses, and enroll directly.
                                Our team will guide you through the onboarding process.
                            </div>
                        </div>
                        <div class="faq-item">
                            <p class="faq-question">
                                What is the typical response time for queries?
                                <i class="fas fa-chevron-down"></i>
                            </p>
                            <div class="faq-answer">
                                We aim to respond to all queries within 24 business hours. For urgent matters,
                                please call us directly during working hours.
                            </div>
                        </div>
                        <div class="faq-item">
                            <p class="faq-question">
                                Are the courses available online or only offline?
                                <i class="fas fa-chevron-down"></i>
                            </p>
                            <div class="faq-answer">
                                We offer both online and offline learning options. Our platform supports
                                self-paced online learning as well as in-person sessions at our Gurugram office.
                            </div>
                        </div>
                        <div class="faq-item">
                            <p class="faq-question">
                                Can I get a demo session before enrolling?
                                <i class="fas fa-chevron-down"></i>
                            </p>
                            <div class="faq-answer">
                                Yes! We offer free demo sessions for selected courses. Contact us via the form
                                above or call us to book your demo session today.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div><!-- /.contact-page-wrapper -->
    <?php include '../includes/footer.php'; ?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== 'YOUR_RECAPTCHA_SITE_KEY_HERE' && RECAPTCHA_SITE_KEY !== ''): ?>
        <script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_SITE_KEY; ?>"></script>
    <?php endif; ?>
    <script>
        // ── FAQ Toggle ──────────────────────────────
        document.querySelectorAll('.faq-item').forEach(item => {
            item.addEventListener('click', () => {
                const isOpen = item.classList.contains('open');
                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
                if (!isOpen) item.classList.add('open');
            });
        });

        // ── Contact Form: Validation + AJAX Submit ───
        const form = document.getElementById('contact-form');
        const success = document.getElementById('form-success');
        const error = document.getElementById('form-error');
        const submitBtn = document.getElementById('contact-submit-btn');
        const btnLabel = submitBtn.querySelector('.btn-label');
        const btnSpinner = submitBtn.querySelector('.btn-spinner');
        const recaptchaSiteKey = "<?php echo defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : ''; ?>";

        const rules = {
            first_name: v => v.trim().length >= 2 && /^[a-zA-Z\s'-]+$/.test(v.trim()) ? '' : 'Enter a valid first name (min 2 letters).',
            last_name: v => v.trim().length >= 2 && /^[a-zA-Z\s'-]+$/.test(v.trim()) ? '' : 'Enter a valid last name (min 2 letters).',
            email: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) ? '' : 'Enter a valid email address.',
            phone: v => /^[0-9+\-\s()]{7,20}$/.test(v.trim()) ? '' : 'Enter a valid phone number.',
            message: v => v.trim().length >= 10 ? '' : 'Message must be at least 10 characters.'
        };

        function showFieldError(field, msg) {
            const input = document.getElementById(field);
            const errBox = document.querySelector(`[data-error-for="${field}"]`);
            if (msg) {
                input.classList.add('is-invalid');
                errBox.textContent = msg;
                errBox.classList.add('show');
            } else {
                input.classList.remove('is-invalid');
                errBox.classList.remove('show');
                errBox.textContent = '';
            }
        }

        function validateForm() {
            let valid = true;
            for (const field in rules) {
                const val = document.getElementById(field).value;
                const msg = rules[field](val);
                showFieldError(field, msg);
                if (msg) valid = false;
            }
            return valid;
        }

        Object.keys(rules).forEach(field => {
            document.getElementById(field).addEventListener('blur', function () {
                showFieldError(field, rules[field](this.value));
            });
        });

        function setLoading(isLoading) {
            submitBtn.disabled = isLoading;
            btnLabel.style.display = isLoading ? 'none' : 'inline-flex';
            btnSpinner.style.display = isLoading ? 'inline-flex' : 'none';
        }

        function getRecaptchaToken() {
            return new Promise((resolve) => {
                if (!recaptchaSiteKey || typeof grecaptcha === 'undefined') {
                    resolve('');
                    return;
                }
                const timeout = setTimeout(() => resolve(''), 3000);
                grecaptcha.ready(() => {
                    grecaptcha.execute(recaptchaSiteKey, { action: 'contact_submit' })
                        .then(token => { clearTimeout(timeout); resolve(token); })
                        .catch(() => { clearTimeout(timeout); resolve(''); });
                });
            });
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            success.style.display = 'none';
            error.style.display = 'none';

            if (!validateForm()) return;

            setLoading(true);

            const token = await getRecaptchaToken();
            document.getElementById('recaptcha_token').value = token;

            const data = new FormData(form);

            try {
                const res = await fetch('../process/contact_process.php', {
                    method: 'POST',
                    body: data
                });
                const result = await res.json();

                if (result.success) {
                    success.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + result.message;
                    success.style.display = 'block';
                    form.reset();
                    setTimeout(() => { success.style.display = 'none'; }, 6000);
                } else {
                    if (result.errors && Object.keys(result.errors).length) {
                        Object.keys(result.errors).forEach(field => {
                            showFieldError(field, result.errors[field]);
                        });
                    }
                    error.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (result.message || 'Something went wrong.');
                    error.style.display = 'block';
                    setTimeout(() => { error.style.display = 'none'; }, 6000);
                }
            } catch (err) {
                error.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Network error. Please try again.';
                error.style.display = 'block';
            } finally {
                setLoading(false);
            }
        });
    </script>
</body>

</html>