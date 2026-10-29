/**
 * First-party interaction events.
 *
 * Only pushes to the existing GTM dataLayer, and only when a dataLayer is
 * already present (i.e. consent-gated GTM has loaded). No third-party script is
 * injected here, and no personal data is sent — just the element's label, the
 * page path and the event type.
 */
const dataLayer = window.dataLayer;

if (Array.isArray(dataLayer)) {
    const push = (payload) => {
        try {
            dataLayer.push(payload);
        } catch (error) {
            // Never let analytics break the page.
        }
    };

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element
            ? event.target.closest('[data-track], .ic-btn, .ic-wa')
            : null;

        if (!target || target.hasAttribute('data-no-track')) {
            return;
        }

        const label = (target.getAttribute('data-track-label')
            || target.textContent
            || ''
        ).trim().replace(/\s+/g, ' ').slice(0, 80);

        const href = target.getAttribute('href') || '';

        push({
            event: target.getAttribute('data-track') || 'ic_cta_click',
            ic_label: label,
            ic_href: href,
            ic_page: window.location.pathname,
        });
    }, { passive: true });

    push({
        event: 'ic_page_view',
        ic_page: window.location.pathname,
        ic_template: document.body.className,
    });
}
