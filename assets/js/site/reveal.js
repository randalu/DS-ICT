/**
 * Scroll reveal.
 *
 * Progressive enhancement only: elements marked [data-ic-reveal] fade in when
 * they enter the viewport. If IntersectionObserver is missing, or the visitor
 * prefers reduced motion, the elements are shown immediately and no observer
 * is created.
 */
const targets = Array.from(document.querySelectorAll('[data-ic-reveal]'));

if (targets.length > 0) {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduceMotion || !('IntersectionObserver' in window)) {
        targets.forEach((element) => element.classList.add('is-visible'));
    } else {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

        targets.forEach((element) => {
            element.classList.add('ic-reveal');
            observer.observe(element);
        });
    }
}
