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

        // Toggle mobile account submenu
        $('#mobileAccountToggle').on('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            $('.mobile-account-section').toggleClass('active');
        });

        // Close mobile menu on general link click
        $('#mobileMenu a').on('click', function () {
            if($(this).closest('.mobile-account-header').length){
                return;
            }
            $('#navHamburger').removeClass('open');
            $('#mobileMenu').removeClass('active');
            $('body').removeClass('menu-open');
        });

        // Close mobile menu when resizing back to desktop
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

        // ── Project Filter Tabs (UI only) ──
        $('.proj-filter-btn').on('click', function () {
            $('.proj-filter-btn').removeClass('active');
            $(this).addClass('active');
        });

    });