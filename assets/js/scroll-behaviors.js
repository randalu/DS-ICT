document.addEventListener('DOMContentLoaded', function () {
    /* Reduced motion preference */
    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var body = document.body;

    // ── Sticky Header with Logo Shrink & Mobile Hide-on-Scroll ──
    var header = document.querySelector('.site-shell-header');
    var headerThreshold = 100;
    var headerLastScroll = 0;
    var bottomNavLastScroll = 0;
    var headerHideTimeout;

    function updateHeader() {
        var scrollY = window.scrollY;
        if (scrollY > headerThreshold) {
            document.body.classList.add('is-scrolled');
        } else {
            document.body.classList.remove('is-scrolled');
        }
    }

    // Initial check
    updateHeader();

    window.addEventListener('scroll', function () {
        updateHeader();

        // Mobile hide-on-scroll logic, scoped to overlay headers only.
        if (window.innerWidth < 992 && header) {
            if (header.classList.contains('site-shell-header--overlay')) {
                var currentScroll = window.pageYOffset || window.scrollY;

                clearTimeout(headerHideTimeout);

                if (currentScroll > headerLastScroll && currentScroll > headerThreshold) {
                    header.classList.add('header-scroll-hide');
                } else {
                    header.classList.remove('header-scroll-hide');
                }

                headerLastScroll = currentScroll;

                headerHideTimeout = setTimeout(function () {
                    header.classList.remove('header-scroll-hide');
                }, 150);
            } else {
                header.classList.remove('header-scroll-hide');
            }
        }
    }, { passive: true });

    // ── Bottom Nav Auto-Hide ──
    var bottomNav = document.querySelector('.sticky-cta');
    var bottomNavThreshold = 300;
    var scrollStopTimeout;

    if (bottomNav) {
        window.addEventListener('scroll', function () {
            var currentScroll = window.pageYOffset || window.scrollY;

            clearTimeout(scrollStopTimeout);

            if (currentScroll > bottomNavLastScroll && currentScroll > bottomNavThreshold) {
                // Scrolling down — hide bottom nav
                bottomNav.classList.add('hide-bar');
                bottomNav.classList.remove('show-bar');
            } else {
                // Scrolling up or at top — show bottom nav
                bottomNav.classList.remove('hide-bar');
                bottomNav.classList.add('show-bar');
            }

            bottomNavLastScroll = currentScroll;

            // Detect scroll stop — show nav again
            scrollStopTimeout = setTimeout(function () {
                bottomNav.classList.remove('hide-bar');
                bottomNav.classList.add('show-bar');
            }, 150);
        }, { passive: true });
    }

    // ── Back to Top Button ──
    var backToTopBtn = document.querySelector('.back-to-top');
    var backToTopThreshold = 300;

    if (backToTopBtn) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > backToTopThreshold) {
                backToTopBtn.classList.add('is-visible');
            } else {
                backToTopBtn.classList.remove('is-visible');
            }
        }, { passive: true });

        backToTopBtn.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ── Back to top from existing footer links ──
    var existingBackToTopLinks = document.querySelectorAll('a[href="#top"], a[href="#"], .back-to-top-link');
    existingBackToTopLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // ── Lazy Image Loading (Intersection Observer) ──
    var lazyImages = document.querySelectorAll('img.lazy-image');
    if (lazyImages.length > 0 && 'IntersectionObserver' in window) {
        var imageObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        img.classList.add('loaded');
                    }
                    imageObserver.unobserve(img);
                }
            });
        }, { rootMargin: '100px' });

        lazyImages.forEach(function (img) { imageObserver.observe(img); });
    }

    // ── Lazy Background Image Loading (for CSS background images) ──
    var lazyBgElements = document.querySelectorAll('[data-bg-src]');
    if (lazyBgElements.length > 0 && 'IntersectionObserver' in window) {
        var bgObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var el = entry.target;
                    if (el.dataset.bgSrc) {
                        el.style.backgroundImage = 'url(' + el.dataset.bgSrc + ')';
                        el.removeAttribute('data-bg-src');
                        el.classList.add('bg-loaded');
                    }
                    bgObserver.unobserve(el);
                }
            });
        }, { rootMargin: '100px' });

        lazyBgElements.forEach(function (el) { bgObserver.observe(el); });
    }

    // ── Maintain backward compat for .is-scrolled from existing code ──
});
