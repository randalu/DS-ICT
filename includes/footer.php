<?php
declare(strict_types=1);

require_once __DIR__ . '/review_widget_snippets.php';
require_once __DIR__ . '/published_page_links.php';

$businessPartnersPublicEnabled = business_partners_public_enabled();
$footerSocialLinks = array_slice(fetch_active_social_links(), 0, 18);
$conditionalPolicySlugs = [
    'privacy-policy',
    'cookie-policy',
    'travel-insurance',
    'accessibility',
    'complaints',
    'responsible-travel',
    'company-credentials',
    'traveller-support',
    'booking-terms-and-conditions',
    'cancellation-refund-policy',
    'payment-deposit-policy',
    'book-with-confidence',
    'trust-safety',
    'drivers-vehicles',
    'how-booking-works',
    'tour-inclusions-exclusions',
];
$footerBottomLinks = array_values(array_filter(
    fetch_footer_bottom_links(),
    static function (array $link) use ($conditionalPolicySlugs): bool {
        $url = trim((string) ($link['url'] ?? ''));
        if ($url === '') {
            return false;
        }

        $path = trim((string) (parse_url($url, PHP_URL_PATH) ?: $url), '/');
        if (!in_array($path, $conditionalPolicySlugs, true)) {
            return true;
        }

        return find_published_static_page_by_slug($path) !== null;
    }
));
$footerServiceLinks = fetch_footer_service_links();
$footerBookingLinks = booking_page_links_excluding(
    published_booking_page_links(),
    array_merge(
        [request_path()],
        array_map(static fn(array $link): string => base_url((string) ($link['url'] ?? '')), $footerBottomLinks)
    )
);
$tripadvisorFooterWidget = empty($disableTripadvisorFooterWidget) ? tripadvisor_footer_trust_widget() : '';
$footerDestinations = fetch_all(
    "SELECT name, slug
     FROM destinations
     WHERE is_published = 1
     ORDER BY is_featured DESC, sort_order ASC, name ASC"
);
$footerExperiences = db_table_exists('experiences') ? fetch_all(
    "SELECT title, slug
     FROM experiences
     WHERE is_published = 1
     ORDER BY is_featured DESC, sort_order ASC, title ASC"
) : [];
$footerTours = fetch_all(
    "SELECT name, slug
     FROM tours
     WHERE is_published = 1
     ORDER BY is_featured DESC, sort_order ASC, name ASC"
);
$footerItineraries = fetch_all(
    "SELECT name, slug
     FROM itineraries
     WHERE is_published = 1
     ORDER BY is_featured DESC, sort_order ASC, days ASC, name ASC"
);
$mergeFooterLinks = static function (array $primary, array $fallback, int $limit = 20): array {
    $merged = [];
    $seen = [];
    foreach (array_merge($primary, $fallback) as $item) {
        $slug = trim((string) ($item['slug'] ?? ''));
        if ($slug === '' || isset($seen[$slug])) {
            continue;
        }
        $seen[$slug] = true;
        $merged[] = $item;
        if (count($merged) >= $limit) {
            break;
        }
    }
    return $merged;
};
if (db_table_exists('page_views')) {
    $popularDestinations = fetch_all(
        "SELECT d.name, d.slug
         FROM destinations d
         INNER JOIN page_views pv ON pv.path IN (CONCAT('/destinations/', d.slug), CONCAT('/destinations/', d.slug, '/'))
         WHERE d.is_published = 1
         GROUP BY d.id, d.name, d.slug
         ORDER BY COUNT(*) DESC, d.name ASC"
    );
    $footerDestinations = $mergeFooterLinks($footerDestinations, $popularDestinations, 10);

    if (db_table_exists('experiences')) {
        $popularExperiences = fetch_all(
            "SELECT e.title, e.slug
             FROM experiences e
             INNER JOIN page_views pv ON pv.path IN (CONCAT('/experiences/', e.slug), CONCAT('/experiences/', e.slug, '/'))
             WHERE e.is_published = 1
             GROUP BY e.id, e.title, e.slug
          ORDER BY COUNT(*) DESC, e.title ASC"
        );
        $footerExperiences = $mergeFooterLinks($footerExperiences, $popularExperiences, 10);
    }

    $popularItineraries = fetch_all(
        "SELECT i.name, i.slug
         FROM itineraries i
         INNER JOIN page_views pv ON pv.path IN (CONCAT('/itineraries/', i.slug), CONCAT('/itineraries/', i.slug, '/'))
         WHERE i.is_published = 1
         GROUP BY i.id, i.name, i.slug
         ORDER BY COUNT(*) DESC, i.name ASC"
    );
    $footerItineraries = $mergeFooterLinks($footerItineraries, $popularItineraries, 10);
}
$sitePrimaryPhone = site_primary_phone();
$siteSecondaryPhone = site_secondary_phone();
$siteEmail = site_contact_email();
$siteWhatsappDisplay = site_whatsapp_display_number();
$siteWhatsappUrl = site_whatsapp_url(site_default_whatsapp_text());
$footerMonths = [
    ['key' => 'january', 'abbr' => 'Jan'],
    ['key' => 'february', 'abbr' => 'Feb'],
    ['key' => 'march', 'abbr' => 'Mar'],
    ['key' => 'april', 'abbr' => 'Apr'],
    ['key' => 'may', 'abbr' => 'May'],
    ['key' => 'june', 'abbr' => 'Jun'],
    ['key' => 'july', 'abbr' => 'Jul'],
    ['key' => 'august', 'abbr' => 'Aug'],
    ['key' => 'september', 'abbr' => 'Sep'],
    ['key' => 'october', 'abbr' => 'Oct'],
    ['key' => 'november', 'abbr' => 'Nov'],
    ['key' => 'december', 'abbr' => 'Dec'],
];
?>
</main>

<?php if (empty($disableServicesSection)): ?>
    <?php require __DIR__ . '/services_section.php'; ?>
<?php endif; ?>

<footer class="site-footer-modern footer-layout">

    <div class="container">
        <div class="site-footer-grid">

            <!-- Brand + description + contact + social (spans 2fr) -->
            <div class="footer-brand-col">
                <div class="footer-brand-row">
                    <a href="<?= e(base_url()) ?>" class="footer-logo-link" aria-label="<?= e(APP_NAME) ?>">
                        <img src="<?= e(base_url('assets/images/island_culture_travels_logo.png')) ?>" alt="<?= e(APP_NAME) ?>" class="footer-logo-img" loading="lazy">
                    </a>
                    <div class="footer-brand-text">
                        <span class="footer-brand"><?= e(APP_NAME) ?></span>
                        <span class="footer-tagline">Sri Lanka Private Travel</span>
                    </div>
                </div>
                <p class="footer-desc">
                    Your trusted Sri Lanka private travel partner. We build custom routes with professional drivers,
                    curated hotels, and local support from arrival to departure.
                </p>
                <div class="footer-contact-group">
                    <div class="footer-contact-items">
                        <a href="tel:<?= e(preg_replace('/\s+/', '', $sitePrimaryPhone)) ?>" class="footer-contact-item">
                            <span class="footer-contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                            </span>
                            <span class="footer-contact-text">
                                <span class="footer-contact-label">Calls</span>
                                <span class="footer-contact-value"><?= e($sitePrimaryPhone) ?></span>
                            </span>
                        </a>
                        <?php if ($siteSecondaryPhone !== '' && preg_replace('/\D+/', '', $siteSecondaryPhone) !== preg_replace('/\D+/', '', $sitePrimaryPhone)): ?>
                        <a href="tel:<?= e(preg_replace('/\s+/', '', $siteSecondaryPhone)) ?>" class="footer-contact-item">
                            <span class="footer-contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                            </span>
                            <span class="footer-contact-text">
                                <span class="footer-contact-label">Additional calls</span>
                                <span class="footer-contact-value"><?= e($siteSecondaryPhone) ?></span>
                            </span>
                        </a>
                        <?php endif; ?>
                        <a href="mailto:<?= e($siteEmail) ?>" class="footer-contact-item">
                            <span class="footer-contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7"/></svg>
                            </span>
                            <span class="footer-contact-text">
                                <span class="footer-contact-label">Email</span>
                                <span class="footer-contact-value"><?= e($siteEmail) ?></span>
                            </span>
                        </a>
                    </div>
                    <a href="<?= e($siteWhatsappUrl) ?>" target="_blank" rel="noopener" class="footer-wa-item">
                        <span class="footer-wa-icon">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </span>
                        <span>WhatsApp <?= e($siteWhatsappDisplay) ?></span>
                    </a>
                </div>
                <?php if ($footerSocialLinks): ?>
                    <div class="footer-social-icons" aria-label="Follow Island Culture Travels">
                        <?php foreach ($footerSocialLinks as $social): ?>
                            <?php
                            $iconText = trim((string) ($social['icon_text'] ?? ''));
                            $iconImage = trim((string) ($social['icon_image'] ?? ''));
                            $label = trim((string) ($social['label'] ?? 'Social'));
                            $url = trim((string) ($social['url'] ?? ''));
                            if ($url === '') continue;
                            ?>
                            <a href="<?= e($url) ?>" class="footer-social-btn" aria-label="<?= e($label) ?>" target="_blank" rel="noopener">
                                <?php if ($iconImage !== ''): ?>
                                    <img src="<?= e(stored_media_url($iconImage)) ?>" alt="<?= e($label) ?>">
                                <?php else: ?>
                                    <span><?= e($iconText !== '' ? $iconText : strtoupper(substr($label, 0, 1))) ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="footer-trust-row">
                    <?php if ($tripadvisorFooterWidget !== ''): ?>
                        <div class="footer-tripadvisor-widget" aria-label="TripAdvisor trust badge">
                            <?= $tripadvisorFooterWidget ?>
                        </div>
                    <?php endif; ?>
                    <div class="footer-trustpilot-widget" aria-label="Trustpilot reviews">
                        <a href="https://www.trustpilot.com/review/islandculturetravels.com" target="_blank" rel="noopener">Read reviews on Trustpilot</a>
                        <?php ob_start(); ?>
                        <div class="trustpilot-widget" data-locale="en-US" data-template-id="56278e9abfbbba0bdcd568bc" data-businessunit-id="69f0e87e25fe3531660ce70c" data-style-height="60px" data-style-width="200px" data-token="0056426e-fadf-4b27-a0de-68fe8cd6c1e6">
                            <a href="https://www.trustpilot.com/review/islandculturetravels.com" target="_blank" rel="noopener">Trustpilot</a>
                        </div>
                        <?= ict_privacy_optional_template('external_reviews', 'trustpilot-footer', (string) ob_get_clean()) ?>
                    </div>
                </div>
            </div>

            <!-- Destinations -->
            <div class="footer-link-col">
                <div class="footer-col-header" style="cursor:default;pointer-events:none;">
                    <span>Destinations</span>
                </div>
                <ul class="footer-col-links">
                    <?php foreach ($footerDestinations as $destination): ?>
                        <li><a href="<?= e(base_url('destinations/' . $destination['slug'])) ?>"><?= e($destination['name']) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?= e(base_url('destinations')) ?>">All Destinations &rarr;</a></li>
                </ul>
            </div>

            <!-- Experiences -->
            <div class="footer-link-col">
                <div class="footer-col-header" style="cursor:default;pointer-events:none;">
                    <span>Experiences</span>
                </div>
                <ul class="footer-col-links">
                    <li><a href="<?= e(base_url('experiences')) ?>">Experiences &amp; Hidden Gems</a></li>
                    <?php foreach ($footerExperiences as $experience): ?>
                        <li><a href="<?= e(base_url('experiences/' . $experience['slug'])) ?>"><?= e($experience['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Travel Guide -->
            <div class="footer-link-col footer-guide-col">
                <div class="footer-col-header" style="cursor:default;pointer-events:none;">
                    <span>Travel Guide</span>
                </div>
                <ul class="footer-col-links">
                    <li><a href="<?= e(base_url('tours')) ?>">Private Tours</a></li>
                    <li><a href="<?= e(base_url('day-tours')) ?>">Day Tours</a></li>
                    <li><a href="<?= e(base_url('seasonal')) ?>">When to Visit</a></li>
                    <li><a href="<?= e(base_url('from')) ?>">Travel Guides by Country</a></li>
                    <li><a href="<?= e(base_url('blog')) ?>">Sri Lanka Travel Blog</a></li>
                    <li><a href="<?= e(base_url('plan')) ?>">Trip Planner</a></li>
                    <li><a href="<?= e(base_url('why-choose-us')) ?>">Why Choose Us</a></li>
                    <li><a href="<?= e(base_url('faq')) ?>">FAQ</a></li>
                    <li><a href="<?= e(base_url('reviews')) ?>">Reviews</a></li>
                    <li><a href="<?= e(base_url('contact')) ?>">Contact</a></li>
                </ul>
            </div>

        </div>

        <?php if ($footerBookingLinks): ?>
            <nav class="footer-booking-nav" aria-label="Booking and trust">
                <h2 class="footer-booking-title">Booking &amp; Trust</h2>
                <ul class="footer-booking-links">
                    <?php foreach ($footerBookingLinks as $bookingLink): ?>
                        <li><a href="<?= e($bookingLink['url']) ?>"><?= e($bookingLink['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <div class="footer-bottom">
            <p class="footer-copy">
                &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.
            </p>
            <nav class="footer-utility-links" aria-label="Useful links">
                <?php if ($footerBottomLinks): ?>
                    <?php foreach ($footerBottomLinks as $fbl): ?>
                        <?php $fblUrl = trim((string) ($fbl['url'] ?? '')); ?>
                        <?php $fblLabel = trim((string) ($fbl['label'] ?? '')); ?>
                        <?php if ($fblUrl === '' || $fblLabel === '') continue; ?>
                        <a href="<?= e(base_url($fblUrl)) ?>"><?= e($fblLabel) ?></a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="<?= e(ict_privacy_url('privacy_choices.php')) ?>" data-analytics-open data-no-track="1">Privacy choices</a>
            </nav>
            <p class="footer-credit">Website by <a href="https://randalu.lk" target="_blank" rel="noopener">Randalu Web Solutions</a></p>
        </div>
    </div>

</footer>
<?php require __DIR__ . '/privacy_preferences.php'; ?>

<a href="<?= e($siteWhatsappUrl) ?>" class="whatsapp-float" aria-label="Chat on WhatsApp" target="_blank" rel="noopener">
    <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>

<?php require __DIR__ . '/sticky_cta.php'; ?>

<nav class="scroll-spy-track" aria-label="Page sections" id="scrollSpyTrack"></nav>

<script src="<?= e(ict_privacy_url('assets/vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js')) ?>" defer></script>
<script src="<?= e(base_url('assets/js/app.js')) ?>" defer></script>
<?php render_google_tag_queued_events(); ?>
<script>
(function () {
    var revealItems = document.querySelectorAll('.new-home-itinerary-card, .new-home-destination-card, .new-home-experience-card, .new-home-day-tour-card, .new-home-collection-card, .related-mini-card, .compact-related-card, .experience-card, .destination-card, .itinerary-card, .home-itinerary-card, .home-destination-card');
    if (!revealItems.length) return;
    revealItems.forEach(function (item) { item.classList.add('motion-reveal'); });
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        revealItems.forEach(function (item) { item.classList.add('is-visible'); });
        return;
    }
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });
    revealItems.forEach(function (item) { observer.observe(item); });
})();
</script>
<script>
(function () {
    if (window.matchMedia('(hover: none)').matches) return;
    var links = document.querySelectorAll('a.content-hover-link[data-preview-type][data-preview-id]');
    if (!links.length) return;
    var popover = document.createElement('div');
    popover.className = 'content-preview-popover';
    popover.style.display = 'none';
    document.body.appendChild(popover);
    var cache = {};

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (ch) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[ch];
        });
    }

    function positionPopover(anchor) {
        var rect = anchor.getBoundingClientRect();
        var left = Math.min(rect.left, window.innerWidth - popover.offsetWidth - 14);
        var top = rect.bottom + 10;
        if (top + popover.offsetHeight > window.innerHeight) {
            top = Math.max(14, rect.top - popover.offsetHeight - 10);
        }
        popover.style.left = Math.max(14, left) + 'px';
        popover.style.top = top + 'px';
    }

    function render(data, anchor) {
        popover.innerHTML = '<img src="' + escapeHtml(data.image) + '" alt="">'
            + '<div><small>' + escapeHtml(data.label) + '</small><strong>' + escapeHtml(data.title) + '</strong><p>' + escapeHtml(data.excerpt || '') + '</p></div>';
        popover.style.display = 'grid';
        positionPopover(anchor);
    }

    links.forEach(function (link) {
        var key = link.getAttribute('data-preview-type') + ':' + link.getAttribute('data-preview-id');
        link.addEventListener('mouseenter', function () {
            if (cache[key]) {
                render(cache[key], link);
                return;
            }
            fetch('<?= e(base_url('content-preview.php')) ?>?type=' + encodeURIComponent(link.getAttribute('data-preview-type')) + '&id=' + encodeURIComponent(link.getAttribute('data-preview-id')))
                .then(function (res) { return res.ok ? res.json() : null; })
                .then(function (data) {
                    if (!data) return;
                    cache[key] = data;
                    render(data, link);
                })
                .catch(function () {});
        });
        link.addEventListener('mouseleave', function () {
            popover.style.display = 'none';
        });
    });
})();
</script>
<script>
(function () {
    var endpoint = '<?= e(ict_privacy_url('track-event.php')) ?>';
    var csrfToken = <?= json_encode(csrf_token(), JSON_UNESCAPED_SLASHES) ?>;
    var canBeacon = typeof navigator !== 'undefined' && typeof navigator.sendBeacon === 'function';

    var allowedEvents = ['whatsapp_click', 'phone_click', 'email_click', 'contact_cta_click', 'sticky_cta_click', 'external_link_click'];
    var canTrack = function (transport) {
        return window.ICTPrivacy && typeof window.ICTPrivacy.canTrack === 'function' && window.ICTPrivacy.canTrack(transport);
    };
    var pushGoogleTagEvent = function (eventType) {
        if (!canTrack('gtm')) return;
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ event: eventType, source_page: window.ICTPrivacy.pageGroup });
    };
    var sendEvent = function (eventType) {
        if (!canTrack('first_party')) return;
        var payload = JSON.stringify({ event_type: eventType, _token: csrfToken });
        if (canBeacon) {
            try {
                var blob = new Blob([payload], { type: 'application/json' });
                if (navigator.sendBeacon(endpoint, blob)) {
                    return;
                }
            } catch (e) {}
        }

        if (typeof fetch === 'function') {
            fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true
            }).catch(function () {});
        }
    };

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link) return;
        if (link.getAttribute('data-no-track') === '1') return;

        var href = link.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#') return;

        var resolved;
        try {
            resolved = new URL(href, window.location.href);
        } catch (e) {
            return;
        }

        var target = resolved.href;
        var eventType = '';
        var lowerTarget = target.toLowerCase();
        var lowerText = (link.textContent || '').toLowerCase();
        var customEventType = (link.getAttribute('data-track-event') || '').trim().toLowerCase();

        if (customEventType) {
            eventType = customEventType;
        }

        if (!eventType && (lowerTarget.indexOf('wa.me/') !== -1 || lowerTarget.indexOf('whatsapp') !== -1)) {
            eventType = 'whatsapp_click';
        } else if (!eventType && lowerTarget.indexOf('tel:') === 0) {
            eventType = 'phone_click';
        } else if (!eventType && lowerTarget.indexOf('mailto:') === 0) {
            eventType = 'email_click';
        } else if (!eventType && resolved.pathname.replace(/\/+$/, '') === '/contact' && (link.classList.contains('btn') || /plan|quote|tour|contact/.test(lowerText))) {
            eventType = 'contact_cta_click';
        } else if (!eventType && resolved.origin !== window.location.origin) {
            eventType = 'external_link_click';
        }

        if (allowedEvents.indexOf(eventType) === -1) return;
        pushGoogleTagEvent(eventType);
        sendEvent(eventType);
    }, true);
})();
</script>
<script>
(function () {
    if (window.__ictAccordionFallbackInit) return;
    window.__ictAccordionFallbackInit = true;

    if (window.bootstrap && window.bootstrap.Collapse) return;

    var buttons = Array.prototype.slice.call(
        document.querySelectorAll('.accordion-button[data-bs-toggle="collapse"][data-bs-target]')
    );
    if (!buttons.length) return;

    var getButtonForPanel = function (panelId) {
        for (var i = 0; i < buttons.length; i++) {
            if ((buttons[i].getAttribute('data-bs-target') || '') === ('#' + panelId)) {
                return buttons[i];
            }
        }
        return null;
    };

    var setPanelState = function (panel, expanded) {
        panel.classList.toggle('show', expanded);
        var btn = getButtonForPanel(panel.id);
        if (!btn) return;
        btn.classList.toggle('collapsed', !expanded);
        btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    };

    buttons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            var targetSelector = button.getAttribute('data-bs-target');
            if (!targetSelector) return;

            var panel = document.querySelector(targetSelector);
            if (!panel) return;

            var willExpand = !panel.classList.contains('show');
            var parentSelector = panel.getAttribute('data-bs-parent');

            if (parentSelector) {
                var parent = document.querySelector(parentSelector);
                if (parent) {
                    parent.querySelectorAll('.accordion-collapse.show').forEach(function (openPanel) {
                        if (openPanel !== panel) setPanelState(openPanel, false);
                    });
                }
            }

            setPanelState(panel, willExpand);
        });
    });
})();
</script>
<?php
$tawkPropertyId = preg_replace('/[^a-zA-Z0-9]/', '', TAWK_TO_PROPERTY_ID);
$tawkWidgetId = preg_replace('/[^a-zA-Z0-9_-]/', '', TAWK_TO_WIDGET_ID);
if ($tawkWidgetId === '') {
    $tawkWidgetId = 'default';
}
?>
<?= ict_privacy_optional_template('chat', 'tawk', '<script>window.Tawk_API=window.Tawk_API||{};window.Tawk_LoadStart=new Date();</script><script async charset="UTF-8" crossorigin="anonymous" src="https://embed.tawk.to/' . e($tawkPropertyId) . '/' . e($tawkWidgetId) . '"></script>') ?>
<script>
document.addEventListener('alpine:init', function () {
    // ── Mobile menu ────────────────────────────────────────────
    Alpine.data('mobileMenu', function () {
        return {
            menuOpen: false,
            init: function () {
                var self = this;
                this.$watch('menuOpen', function (val) {
                    if (val) {
                        document.body.classList.add('menu-open');
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.classList.remove('menu-open');
                        document.body.style.overflow = '';
                    }
                });
            },
            toggleMenu: function () {
                this.menuOpen = !this.menuOpen;
            },
            closeMenu: function () {
                this.menuOpen = false;
            }
        };
    });

    // ── Sticky header on scroll ────────────────────────────────
    Alpine.data('scrollHeader', function () {
        return {
            scrolled: false,
            init: function () {
                var self = this;
                window.addEventListener('scroll', function () {
                    self.scrolled = window.scrollY > 100;
                }, { passive: true });
            }
        };
    });

    // ── Scroll spy ─────────────────────────────────────────────
    Alpine.data('scrollSpy', function () {
        return {
            sections: [],
            activeSection: null,
            observer: null,
            init: function () {
                var self = this;
                this.sections = Array.from(document.querySelectorAll('section[id]'));
                this.observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            self.activeSection = entry.target.id;
                        }
                    });
                }, { threshold: 0.3, rootMargin: '-100px 0px -100px 0px' });
                this.sections.forEach(function (s) { self.observer.observe(s); });
            },
            scrollTo: function (sectionId) {
                var el = document.getElementById(sectionId);
                if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
            },
            destroy: function () {
                var self = this;
                if (this.observer) { this.observer.disconnect(); this.observer = null; }
            }
        };
    });

    // ── Title scroll animations ────────────────────────────────
    Alpine.data('titleAnimate', function () {
        return {
            visible: false,
            observer: null,
            init: function () {
                var self = this;
                this.observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            self.visible = true;
                        } else {
                            self.visible = false;
                        }
                    });
                }, { threshold: 0.3 });
                this.observer.observe(this.$el);
            },
            destroy: function () {
                if (this.observer) { this.observer.disconnect(); this.observer = null; }
            }
        };
    });

    // ── Card reveal on scroll ──────────────────────────────────
    Alpine.data('cardReveal', function () {
        return {
            visible: false,
            observer: null,
            init: function () {
                var self = this;
                this.observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            self.visible = true;
                            self.observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.3 });
                this.observer.observe(this.$el);
            },
            destroy: function () {
                if (this.observer) { this.observer.disconnect(); this.observer = null; }
            }
        };
    });

    // ── Hero section (Phase 2.2) ───────────────────────────────
    Alpine.data('heroSection', function () {
        return {
            priceExpanded: false,
            compareExpanded: false,
            togglePrice: function () { this.priceExpanded = !this.priceExpanded; },
            toggleCompare: function () { this.compareExpanded = !this.compareExpanded; }
        };
    });

    Alpine.data('durAutoScroll', function () {
        return {
            track: null,
            timer: null,
            init: function () {
                var self = this;
                this.track = this.$el.querySelector('.itin-dur-track');
                if (!this.track) return;
                this.timer = setInterval(function () {
                    if (!self.track) return;
                    if (self.track.matches(':hover')) return;
                    var pills = self.track.querySelectorAll('.itin-dur-pill');
                    if (pills.length < 2) return;
                    var activePill = self.track.querySelector('.itin-dur-pill.active');
                    var nextIdx = 0;
                    if (activePill) {
                        var currentIdx = Array.from(pills).indexOf(activePill);
                        nextIdx = (currentIdx + 1) % pills.length;
                    }
                    if (pills[nextIdx]) {
                        var scrollY = window.scrollY;
                        pills[nextIdx].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                        requestAnimationFrame(function() { window.scrollTo(0, scrollY); });
                    }
                }, 4000);
            },
            destroy: function () {
                if (this.timer) { clearInterval(this.timer); this.timer = null; }
            }
        };
    });
    Alpine.data('themeFilter', function () {
        return {
            current: new URLSearchParams(window.location.search).get('theme') || 'all',
            timer: null,
            paused: false,
            startAutoRotate() {
                var self = this;
                var items = this.$el.querySelectorAll('.itin-theme-option');
                if (items.length < 2) return;
                var idx = 0;
                this.timer = setInterval(function () {
                    if (self.paused) return;
                    idx = (idx + 1) % items.length;
                    self.current = items[idx].getAttribute('href')
                        ? new URL(items[idx].href).searchParams.get('theme') || 'all'
                        : 'all';
                }, 3000);
                this.$el.addEventListener('mouseenter', function () { self.paused = true; });
                this.$el.addEventListener('mouseleave', function () {
                    self.paused = false;
                    if (!self.timer) self.startAutoRotate();
                });
            },
            stopAutoRotate() {
                this.paused = true;
                if (this.timer) { clearInterval(this.timer); this.timer = null; }
            }
        };
    });
    Alpine.data('itinCarousel', function () {
        return {
            current: 0,
            total: 0,
            init() {
                this.total = this.$refs.track?.children.length || 0;
            },
            onScroll() {
                var track = this.$refs.track;
                if (!track) return;
                var slideWidth = track.children[0]?.offsetWidth || 1;
                this.current = Math.round(track.scrollLeft / slideWidth);
            },
            prev() {
                var track = this.$refs.track;
                if (!track || this.current <= 0) return;
                this.current--;
                track.children[this.current]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
            },
            next() {
                var track = this.$refs.track;
                if (!track || this.current >= this.total - 1) return;
                this.current++;
                track.children[this.current]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
            }
        };
    });

    Alpine.data('destCarousel', function () {
        return {
            current: 0,
            get trackStyle() {
                return 'transform: translateX(-' + (this.current * 100) + '%)';
            },
            onScroll() {
                const track = this.$refs.track;
                if (!track) return;
                const slideWidth = track.children[0]?.offsetWidth || 1;
                this.current = Math.round(track.scrollLeft / slideWidth);
            },
            prev() {
                const track = this.$refs.track;
                if (!track || this.current <= 0) return;
                this.current--;
            },
            next() {
                const track = this.$refs.track;
                if (!track || this.current >= track.children.length - 1) return;
                this.current++;
            },
            goTo(idx) {
                this.current = idx;
            }
        };
    });
});
</script>
</body>
</html>
