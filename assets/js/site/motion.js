/**
 * Deep Coast — motion and shell behaviour.
 *
 * Three jobs, all progressive:
 *  1. header goes solid once the page scrolls past the hero
 *  2. [data-ix-reveal] elements fade/slide in once, staggered by data-delay
 *  3. hero media drifts slowly on scroll (transform only, rAF-throttled)
 *
 * Every effect is disabled when the visitor prefers reduced motion, and the
 * page is complete and readable if this file never runs.
 */
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* Header -------------------------------------------------------------- */

const header = document.querySelector('[data-ix-header]');

if (header) {
    const setStuck = () => {
        header.classList.toggle('is-stuck', window.scrollY > 24);
    };

    setStuck();
    window.addEventListener('scroll', setStuck, { passive: true });
}

/* Reveal -------------------------------------------------------------- */

const revealItems = Array.from(document.querySelectorAll('[data-ix-reveal]'));

if (revealItems.length > 0) {
    if (reduced || !('IntersectionObserver' in window)) {
        revealItems.forEach((el) => el.classList.add('is-in'));
    } else {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const delay = Number(entry.target.dataset.delay || 0);
                entry.target.style.setProperty('--ix-delay', `${delay}ms`);
                entry.target.classList.add('is-in');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.12 });

        revealItems.forEach((el) => observer.observe(el));
    }
}

/* Hero drift ---------------------------------------------------------- */

const heroMedia = document.querySelector('[data-ix-hero-media]');
const hero = document.querySelector('.ix-hero');

if (hero && !reduced) {
    requestAnimationFrame(() => hero.classList.add('is-ready'));

    let ticking = false;

    const drift = () => {
        const offset = Math.min(window.scrollY, window.innerHeight);
        if (heroMedia) {
            heroMedia.style.transform = `scale(1.06) translate3d(0, ${offset * 0.12}px, 0)`;
        }
        ticking = false;
    };

    window.addEventListener('scroll', () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(drift);
        }
    }, { passive: true });
} else if (hero) {
    hero.classList.add('is-ready');
}

/* Counters ------------------------------------------------------------ */

const counters = Array.from(document.querySelectorAll('[data-ix-count]'));

if (counters.length > 0) {
    const run = (el) => {
        const target = Number(el.dataset.ixCount || 0);
        const suffix = el.dataset.ixSuffix || '';

        if (reduced || target <= 0) {
            el.textContent = target + suffix;
            return;
        }

        const started = performance.now();
        const duration = 1100;
        const tick = (now) => {
            const progress = Math.min((now - started) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(target * eased) + suffix;
            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };
        requestAnimationFrame(tick);
    };

    if ('IntersectionObserver' in window) {
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    run(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.6 });

        counters.forEach((el) => counterObserver.observe(el));
    } else {
        counters.forEach(run);
    }
}
