/* ═══════════════════════════════════════════════════════════
   PROJECT DETAILS — JAVASCRIPT
   Realty Smartz Pathshala
═══════════════════════════════════════════════════════════ */

(function () {
    'use strict';

    /* ── Hero Particles ─────────────────────────────────── */
    function initParticles() {
        const container = document.getElementById('heroParticles');
        if (!container) return;
        const count = 18;
        for (let i = 0; i < count; i++) {
            const p = document.createElement('div');
            p.className = 'hero-particle';
            p.style.cssText = `
                left: ${Math.random() * 100}%;
                width: ${Math.random() * 3 + 1}px;
                height: ${Math.random() * 3 + 1}px;
                animation-duration: ${Math.random() * 12 + 8}s;
                animation-delay: ${Math.random() * 8}s;
                opacity: ${Math.random() * 0.5 + 0.1};
            `;
            container.appendChild(p);
        }
    }

    /* ── Scroll Reveal ──────────────────────────────────── */
    function initScrollReveal() {
        const elements = document.querySelectorAll('[data-reveal]');
        if (!elements.length) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el    = entry.target;
                    const delay = parseInt(el.dataset.delay || 0);
                    setTimeout(() => el.classList.add('revealed'), delay);
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        elements.forEach(el => observer.observe(el));
    }

    /* ── Animated Counters ──────────────────────────────── */
    function animateCounter(el, target, duration) {
        if (isNaN(target) || target <= 0) return;
        const start     = 0;
        const startTime = performance.now();

        function update(now) {
            const elapsed  = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased    = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(start + (target - start) * eased);
            if (progress < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
    }

    function initCounters() {
        const counters  = document.querySelectorAll('[data-count]');
        const observer  = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el  = entry.target;
                    const val = parseInt(el.dataset.count);
                    animateCounter(el, val, 2000);
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(c => observer.observe(c));
    }

    /* ── Smooth Scroll ──────────────────────────────────── */
    function initSmoothScroll() {
        document.querySelectorAll('.scroll-to').forEach(link => {
            link.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href && href.startsWith('#')) {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        const offset = 90;
                        const top    = target.getBoundingClientRect().top + window.scrollY - offset;
                        window.scrollTo({ top, behavior: 'smooth' });
                    }
                }
            });
        });
    }

    /* ── Back to Top ────────────────────────────────────── */
    function initBackToTop() {
        const btn = document.getElementById('backToTop');
        if (!btn) return;

        window.addEventListener('scroll', () => {
            if (window.scrollY > 500) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        }, { passive: true });

        btn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ── Lightbox ───────────────────────────────────────── */
    function initLightbox() {
        const lightbox   = document.getElementById('pdLightbox');
        const lbImg      = document.getElementById('lightboxImg');
        const lbClose    = document.getElementById('lightboxClose');
        const lbBackdrop = document.getElementById('lightboxBackdrop');
        const lbPrev     = document.getElementById('lightboxPrev');
        const lbNext     = document.getElementById('lightboxNext');

        if (!lightbox || !lbImg) return;

        const galleryItems = document.querySelectorAll('[data-lightbox]');
        let currentIndex   = 0;
        const images       = [];

        galleryItems.forEach((item, idx) => {
            const src = item.dataset.lightbox;
            images.push(src);
            item.addEventListener('click', () => openLightbox(idx));
        });

        function openLightbox(index) {
            currentIndex    = index;
            lbImg.src       = images[currentIndex];
            lightbox.classList.add('open');
            document.body.style.overflow = 'hidden';
            lbPrev.style.display = images.length > 1 ? 'flex' : 'none';
            lbNext.style.display = images.length > 1 ? 'flex' : 'none';
        }

        function closeLightbox() {
            lightbox.classList.remove('open');
            document.body.style.overflow = '';
            lbImg.src = '';
        }

        function goNext() {
            currentIndex = (currentIndex + 1) % images.length;
            lbImg.src    = images[currentIndex];
        }

        function goPrev() {
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            lbImg.src    = images[currentIndex];
        }

        lbClose.addEventListener('click', closeLightbox);
        lbBackdrop.addEventListener('click', closeLightbox);
        lbNext.addEventListener('click', goNext);
        lbPrev.addEventListener('click', goPrev);

        document.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('open')) return;
            if (e.key === 'Escape')     closeLightbox();
            if (e.key === 'ArrowRight') goNext();
            if (e.key === 'ArrowLeft')  goPrev();
        });
    }

    /* ── Sticky Nav on Scroll ───────────────────────────── */
    function initStickyNav() {
        const nav = document.getElementById('rspNav');
        if (!nav) return;
        window.addEventListener('scroll', () => {
            if (window.scrollY > 80) {
                nav.classList.add('nav-scrolled');
            } else {
                nav.classList.remove('nav-scrolled');
            }
        }, { passive: true });
    }

    /* ── Enquiry Form Submit ────────────────────────────── */
    function initEnquiryForm() {
        const form    = document.getElementById('pdEnquiryForm');
        const success = document.getElementById('formSuccess');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const name  = form.querySelector('[name="name"]');
            const phone = form.querySelector('[name="phone"]');

            if (!name.value.trim()) {
                name.focus();
                name.style.borderColor = '#e57373';
                setTimeout(() => name.style.borderColor = '', 2500);
                return;
            }

            if (!phone.value.trim()) {
                phone.focus();
                phone.style.borderColor = '#e57373';
                setTimeout(() => phone.style.borderColor = '', 2500);
                return;
            }

            const submitBtn = form.querySelector('.pd-form-submit');
            const origText  = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled  = true;

            const formData = new FormData(form);

            fetch('../includes/enquiry_process.php', {
                method: 'POST',
                body: formData
            })
            .then(res => {
                // Show success regardless (graceful fallback)
                form.style.display = 'none';
                if (success) success.style.display = 'block';
            })
            .catch(() => {
                form.style.display = 'none';
                if (success) success.style.display = 'block';
            })
            .finally(() => {
                submitBtn.innerHTML = origText;
                submitBtn.disabled  = false;
                form.reset();
            });
        });
    }

    /* ── Brochure Button ────────────────────────────────── */
    function initBrochureBtn() {
        const btn = document.getElementById('brochureBtn');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const enquirySection = document.getElementById('enquiry-form');
            if (enquirySection) {
                const top = enquirySection.getBoundingClientRect().top + window.scrollY - 90;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    }

    /* ── Image Fallback ─────────────────────────────────── */
    function initImageFallback() {
        const defaultImg = '../assets/img/projects/default-hero.jpg';
        document.querySelectorAll('img[loading="lazy"]').forEach(img => {
            img.addEventListener('error', function () {
                if (this.src !== defaultImg) this.src = defaultImg;
            });
        });
    }

    /* ── Hero Parallax ──────────────────────────────────── */
    function initParallax() {
        const heroBg = document.getElementById('heroBg');
        if (!heroBg) return;
        window.addEventListener('scroll', () => {
            const scrolled = window.scrollY;
            if (scrolled < window.innerHeight) {
                heroBg.style.transform = `scale(1.05) translateY(${scrolled * 0.2}px)`;
            }
        }, { passive: true });
    }

    /* ── Init All ───────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        initParticles();
        initScrollReveal();
        initCounters();
        initSmoothScroll();
        initBackToTop();
        initLightbox();
        initStickyNav();
        initEnquiryForm();
        initBrochureBtn();
        initImageFallback();
        initParallax();
    });

})();
