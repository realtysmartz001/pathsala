<?php
// ── Active Nav Link Detection ─────────────────
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];

$nav_home    = ($current_page === 'index.php' || strpos($current_path, 'index') !== false)                                          ? 'active' : '';
$nav_project = (strpos($current_path, 'Project') !== false || strpos($current_path, 'project') !== false)                          ? 'active' : '';
$nav_about   = (strpos($current_path, 'about')   !== false)                                                                        ? 'active' : '';
$nav_blog    = (strpos($current_path, 'blog')    !== false)                                                                        ? 'active' : '';
$nav_contact = (strpos($current_path, 'contact') !== false)                                                                        ? 'active' : '';
?>

<button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
    <span class="icon-dark">🌙</span>
    <span class="icon-light">☀️</span>
</button>

<div class="cursor-glow" id="cursorGlow"></div>

<div id="preloader-active">
    <div class="pre-wrap">
        <div class="pre-loader">
            <img src="../assets/img/logo/Pathshala.webp" alt="Loading...">
        </div>
        <div class="pre-text">PATHSHALA</div>
    </div>
</div>

<nav class="rsp-nav" id="rspNav">
    <div class="nav-inner">
        <div class="nav-logo">
            <a href="../index.php">
                <img src="../assets/img/logo/Pathshala.webp" alt="Realty Smartz Pathshala">
            </a>
        </div>

        <!-- Desktop Nav Links -->
        <ul class="nav-links">
            <li class="<?= $nav_home ?>">
                <a href="../index.php">Home</a>
            </li>
            <li class="<?= $nav_project ?>">
                <a href="../pages/Project.php">Projects</a>
            </li>
            <li class="<?= $nav_about ?>">
                <a href="../pages/about.php">About</a>
            </li>
            
            <li class="<?= $nav_contact ?>">
                <a href="../pages/contact.php">Contact</a>
            </li>
        </ul>

        <!-- Desktop Right Side -->
        <div class="nav-right">
            <?php if (isset($userName) && $userName): ?>
            <div class="nav-account">
                <button class="nav-account-btn">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($userName); ?>
                    <i class="fas fa-chevron-down arr"></i>
                </button>
                <ul class="nav-dropdown">
                    <li>
                        <a href="../profile/profile_instructions.php">
                            <i class="fas fa-id-card"></i> Create Profile
                        </a>
                    </li>
                    <li>
                        <a href="../tests/take_test.php">
                            <i class="fas fa-file-alt"></i> Aptitude Test
                        </a>
                    </li>
                    <!-- <li>
                        <a href="#">
                            <i class="fas fa-comments"></i> Chat with Others
                        </a>
                    </li> -->
                    <li>
                        <a href="#">
                            <i class="fas fa-users"></i> Know Your Team
                        </a>
                    </li>
                    <li class="logout-li">
                        <a href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
            <?php else: ?>
            <a href="../auth/login.html" class="nav-login-btn">
                <i class="fas fa-sign-in-alt"></i> Log In
            </a>
            <?php endif; ?>
        </div>

        <!-- Hamburger -->
        <div class="nav-hamburger" id="navHamburger">
            <span></span><span></span><span></span>
        </div>
    </div>
</nav>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <ul>
        <li class="<?= $nav_home ?>">
            <a href="../index.php">
                <i class="fas fa-home" style="color:var(--gold);width:18px"></i> Home
            </a>
        </li>
        <li class="<?= $nav_project ?>">
            <a href="../pages/Project.php">
                <i class="fas fa-building" style="color:var(--gold);width:18px"></i> Projects
            </a>
        </li>
        <li class="<?= $nav_about ?>">
            <a href="../pages/about.php">
                <i class="fas fa-info-circle" style="color:var(--gold);width:18px"></i> About
            </a>
        </li>
        
        <li class="<?= $nav_contact ?>">
            <a href="../pages/contact.php">
                <i class="fas fa-envelope" style="color:var(--gold);width:18px"></i> Contact
            </a>
        </li>
    </ul>

    <div class="mobile-menu-actions">
        <?php if (isset($userName) && $userName): ?>
        <div class="mobile-account-section">
            <div class="mobile-account-header" id="mobileAccountToggle">
                <div class="mobile-account-avatar">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                </div>
                <div>
                    <div class="mobile-account-name">
                        <?php echo htmlspecialchars($userName); ?>
                    </div>
                </div>
                <i class="fas fa-chevron-down mobile-arrow"></i>
            </div>
            <ul class="mobile-menu-items">
                <li>
                    <a href="../profile/profile_instructions.php">
                        <i class="fas fa-id-card"></i> Create Profile
                    </a>
                </li>
                <li>
                    <a href="../tests/take_test.php">
                        <i class="fas fa-file-alt"></i> Aptitude Test
                    </a>
                </li>
                <!-- <li>
                    <a href="#">
                        <i class="fas fa-comments"></i> Chat with Others
                    </a>
                </li> -->
                <li>
                    <a href="#">
                        <i class="fas fa-users"></i> Know Your Team
                    </a>
                </li>
                <li class="mobile-logout">
                    <a href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
        <?php else: ?>
        <a href="../auth/login.html" class="mobile-login-btn">
            <i class="fas fa-sign-in-alt"></i> Log In to Your Account
        </a>
        <?php endif; ?>
    </div>
</div>