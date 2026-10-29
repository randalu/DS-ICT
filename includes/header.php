<?php
declare(strict_types=1);

$bodyClass = $bodyClass ?? '';
header('Cache-Control: private, no-store');
header('Vary: Cookie', false);
if (!ict_privacy_state()['chosen'] && ict_optional_public_context() && in_array(true, ict_privacy_available_categories(), true)) {
    $bodyClass = trim($bodyClass . ' has-analytics-notice');
}
$recordPageView = $recordPageView ?? true;
if ($recordPageView) {
    record_page_view();
}
$businessPartnersPublicEnabled = business_partners_public_enabled();
$siteUnderConstructionNoticeEnabled = site_under_construction_notice_enabled();
$siteUnderConstructionNoticeMessage = site_under_construction_notice_message();
if ($siteUnderConstructionNoticeEnabled && !preg_match('/(?:^|\s)site-has-construction-notice(?:\s|$)/', $bodyClass)) {
    $bodyClass = trim($bodyClass . ' site-has-construction-notice');
}
$stylePath = __DIR__ . '/../assets/css/style.css';
$styleVersion = is_file($stylePath) ? (string) filemtime($stylePath) : '1';
$scrollBehaviorPath = __DIR__ . '/../assets/js/scroll-behaviors.js';
$scrollBehaviorVersion = is_file($scrollBehaviorPath) ? (string) filemtime($scrollBehaviorPath) : '1';
$mobileRedesignPath = __DIR__ . '/../assets/css/mobile-redesign.css';
$mobileRedesignVersion = is_file($mobileRedesignPath) ? (string) filemtime($mobileRedesignPath) : '1';
$requestPath = request_path();
$isItineraryNavActive = route_is('/itineraries') || str_starts_with($requestPath, '/itineraries/');
$isExperienceNavActive = route_is('/experiences') || str_starts_with($requestPath, '/experiences/');
$activitiesNavPublished = db_table_exists('activity_pages') && (bool)fetch_one('SELECT id FROM activity_pages WHERE is_hub=1 AND is_published=1 LIMIT 1');
$isActivitiesNavActive = (bool) preg_match('/(?:^|\s)page-activit(?:y|ies)(?:\s|$)/', $bodyClass)
    || route_is('/activities') || str_starts_with($requestPath, '/activities/');
$isToursNavActive = route_is('/tours') || str_starts_with($requestPath, '/tours/');
$isPartnerNavActive = $businessPartnersPublicEnabled && (route_is('/business-partners') || str_starts_with($requestPath, '/business-partners/'));
$isBlogNavActive = route_is('/blog') || str_starts_with($requestPath, '/blog/');
$isSeasonalNavActive = route_is('/seasonal') || str_starts_with($requestPath, '/sri-lanka-in-');
$isPlannerNavActive = route_is('/plan') || route_is('/compare');
$isDestinationNavActive = route_is('/destinations') || str_starts_with($requestPath, '/destinations/');
$overlayHeaderBodyClasses = [
    'page-home',
    'page-home-new',
    'page-day-tour',
    'page-day-tours',
    'page-destination',
    'page-tour',
    'page-tour-detail',
    'page-itinerary',
    'page-country',
    'page-destinations',
    'page-tours',
    'page-itineraries',
    'page-from-countries',
    'page-experience',
    'page-experiences',
    'page-activity',
    'page-activities',
    'page-reviews',
    'page-seasonal-hub',
    'page-seasonal',
    'page-blog',
    'page-blog-post',
    'page-business-partners',
    'page-business-partner',
    'page-contact',
    'page-about',
    'page-why',
    'page-faq',
    'page-plan',
    'page-compare',
    'page-404',
];
$usesOverlayHeader = false;
foreach ($overlayHeaderBodyClasses as $overlayHeaderBodyClass) {
    if (str_contains(' ' . $bodyClass . ' ', ' ' . $overlayHeaderBodyClass . ' ')) {
        $usesOverlayHeader = true;
        break;
    }
}
$headerClass = 'site-shell-header sticky-top' . ($usesOverlayHeader ? ' site-shell-header--overlay' : '');
?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/seo.php'; ?>
    <script>window.ICTPrivacy = <?= json_encode(ict_privacy_browser_config(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;</script>
    <script src="<?= e(ict_privacy_url('assets/js/privacy-controls.js')) ?>?v=<?= (int) filemtime(__DIR__ . '/../assets/js/privacy-controls.js') ?>" defer></script>
    <script src="<?= e(ict_privacy_url('assets/js/optional-resources.js')) ?>" defer></script>
    <?php render_google_tag_manager_head(); ?>
    <?= ict_privacy_optional_template('external_reviews', 'trustpilot-loader', '<script async src="https://widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js"></script>') ?>
    <?php if (ict_privacy_settings()['review_invitations_allowed']): ?>
        <?= ict_privacy_optional_template('external_reviews', 'trustpilot-invitations', '<script>window.TrustpilotObject="tp";window.tp=window.tp||function(){(window.tp.q=window.tp.q||[]).push(arguments)};tp("register","bfIhV41lAqOGqanL");</script><script async src="https://invitejs.trustpilot.com/tp.min.js"></script>') ?>
    <?php endif; ?>
    <?= ict_privacy_optional_template('translation', 'gtranslate', '<script>window.gtranslateSettings={"default_language":"en","languages":["en","fr","de","es","it","nl","ja","zh-CN","ko","ru","ar"],"wrapper_selector":".gtranslate_wrapper","switcher_horizontal_position":"inline","alt_flags":{"en":"usa"}};</script><script async src="https://cdn.gtranslate.net/widgets/latest/dwf.js"></script>') ?>
    <link href="<?= e(ict_privacy_url('assets/fonts/brand.css')) ?>" rel="stylesheet">
    <link href="<?= e(ict_privacy_url('assets/vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css')) ?>" rel="stylesheet">
    <link href="<?= e(base_url('assets/css/style.css?v=' . rawurlencode($styleVersion))) ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/components.css')) ?>?v=<?= $styleVersion ?>">
    <link href="<?= e(base_url('assets/css/mobile-redesign.css') . '?v=' . rawurlencode($mobileRedesignVersion)) ?>" rel="stylesheet">
    <script defer src="<?= e(base_url('assets/js/alpine-components.js')) ?>"></script>
    <script defer src="<?= e(ict_privacy_url('assets/vendor/alpinejs-3.14.8/dist/cdn.min.js')) ?>"></script>
    <script defer src="<?= e(base_url('assets/js/scroll-behaviors.js') . '?v=' . rawurlencode($scrollBehaviorVersion)) ?>"></script>
    <?php if (!empty($extraStylesheets) && is_array($extraStylesheets)): ?>
        <?php foreach ($extraStylesheets as $extraStylesheet): ?>
            <?php if (is_string($extraStylesheet) && trim($extraStylesheet) !== ''): ?>
                <?php
                $extraStylesheetPath = trim($extraStylesheet);
                $extraStylesheetVersion = '';
                if (!preg_match('/\Ahttps?:\/\//i', $extraStylesheetPath)) {
                    $extraStylesheetDiskPath = __DIR__ . '/../' . ltrim($extraStylesheetPath, '/\\');
                    if (is_file($extraStylesheetDiskPath)) {
                        $extraStylesheetVersion = (str_contains($extraStylesheetPath, '?') ? '&' : '?')
                            . 'v=' . rawurlencode((string) filemtime($extraStylesheetDiskPath));
                    }
                }
                ?>
                <link href="<?= e(base_url($extraStylesheetPath) . $extraStylesheetVersion) ?>" rel="stylesheet">
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <link href="<?= e(base_url('assets/css/typography.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/typography.css')) ?>" rel="stylesheet">
</head>
<body class="<?= e($bodyClass) ?>">
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php render_google_tag_manager_body(); ?>
<!-- Utility bar -->
<div class="site-topbar hidden-mobile">
    <div class="container site-topbar-inner">
        <div class="site-topbar-left">
            <a href="mailto:hello@islandculturetravels.com" class="site-topbar-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7"/></svg>
                hello@islandculturetravels.com
            </a>
            <a href="tel:<?= e(preg_replace('/\s+/', '', site_primary_phone())) ?>" class="site-topbar-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                <?= e(site_primary_phone()) ?>
            </a>
        </div>
        <div class="site-topbar-right">
            <?php if (ict_optional_allowed('translation')): ?><div class="gtranslate_wrapper"></div><?php else: ?><a class="privacy-service-link" href="<?= e(ict_privacy_url('privacy_choices.php')) ?>" data-analytics-open data-no-track="1">Translation choices</a><?php endif; ?>
        </div>
    </div>
</div>
<header id="site-shell-header" class="<?= e($headerClass) ?>" x-data="mobileMenu()">
    <nav class="navbar navbar-expand-lg site-shell-nav">
        <div class="container site-shell-nav-wrap">
            <a class="navbar-brand site-logo site-shell-brand" href="<?= e(base_url()) ?>" aria-label="<?= e(APP_NAME) ?>">
                <img
                    src="<?= e(base_url('assets/images/island_culture_travels_logo.png')) ?>"
                    alt="<?= e(APP_NAME) ?>"
                    class="site-logo-img"
                >
            </a>

            <button class="navbar-toggler site-shell-toggle" type="button" @click="toggleMenu()" :aria-expanded="menuOpen ? 'true' : 'false'" aria-expanded="false" aria-label="Toggle navigation" aria-controls="siteNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div id="siteNav"
                 class="navbar-collapse site-shell-collapse"
                 :class="{ 'show': menuOpen }"
                 x-show="menuOpen"
                 x-transition:enter="fade-enter"
                 x-transition:enter-start="fade-enter-start"
                 x-transition:enter-end="fade-enter-end"
                 x-transition:leave="fade-leave"
                 x-transition:leave-start="fade-leave-start"
                 x-transition:leave-end="fade-leave-end"
                 @click.self="closeMenu()"
                 @keydown.escape="closeMenu()"
                 @keydown.escape.window="closeMenu()">
                <ul class="navbar-nav ms-auto align-items-lg-center site-shell-menu" @click.stop>
                    <li class="nav-item">
                        <a class="nav-link<?= $isDestinationNavActive ? ' active' : '' ?>" href="<?= e(base_url('destinations')) ?>"<?= $isDestinationNavActive ? ' aria-current="page"' : '' ?> @click="closeMenu()">Destinations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $isExperienceNavActive ? ' active' : '' ?>" href="<?= e(base_url('experiences')) ?>"<?= $isExperienceNavActive ? ' aria-current="page"' : '' ?> @click="closeMenu()">Experiences</a>
                    </li>
                    <?php if($activitiesNavPublished): ?><li class="nav-item">
                        <a class="nav-link<?= $isActivitiesNavActive ? ' active' : '' ?>" href="<?= e(base_url('activities')) ?>"<?= $isActivitiesNavActive ? ' aria-current="page"' : '' ?> @click="closeMenu()">Activities</a>
                    </li><?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $isToursNavActive ? ' active' : '' ?>" href="<?= e(base_url('tours')) ?>"<?= $isToursNavActive ? ' aria-current="page"' : '' ?> @click="closeMenu()">Tours</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $isItineraryNavActive ? ' active' : '' ?>" href="<?= e(base_url('itineraries')) ?>"<?= $isItineraryNavActive ? ' aria-current="page"' : '' ?> @click="closeMenu()">Tour Itineraries</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= route_is('/about-us') ? ' active' : '' ?>" href="<?= e(base_url('about-us')) ?>"<?= route_is('/about-us') ? ' aria-current="page"' : '' ?> @click="closeMenu()">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $isSeasonalNavActive || $isPlannerNavActive || $isBlogNavActive ? ' active' : '' ?>" href="<?= e(base_url('seasonal')) ?>"<?= $isSeasonalNavActive || $isPlannerNavActive || $isBlogNavActive ? ' aria-current="page"' : '' ?> @click="closeMenu()">Travel Guide</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= route_is('/reviews') ? ' active' : '' ?>" href="<?= e(base_url('reviews')) ?>"<?= route_is('/reviews') ? ' aria-current="page"' : '' ?> @click="closeMenu()">Reviews</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= route_is('/contact') ? ' active' : '' ?>" href="<?= e(base_url('contact')) ?>"<?= route_is('/contact') ? ' aria-current="page"' : '' ?> @click="closeMenu()">Contact</a>
                    </li>
                </ul>
                <div class="site-shell-actions" @click.stop>
                    <?php if (ict_optional_allowed('translation')): ?><div class="gtranslate_wrapper" data-no-track="1"></div><?php else: ?><a class="privacy-service-link" href="<?= e(ict_privacy_url('privacy_choices.php')) ?>" data-analytics-open data-no-track="1">Translation choices</a><?php endif; ?>
                    <a class="btn btn-primary site-shell-plan-btn" href="<?= e(base_url('contact')) ?>" @click="closeMenu()">Plan My Trip</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="site-nav-backdrop" id="siteNavBackdrop"></div>
    <?php if ($siteUnderConstructionNoticeEnabled): ?>
        <div class="site-under-construction-notice" role="status" aria-label="Site status notice">
            <div class="container site-under-construction-notice__inner">
                <span class="site-under-construction-notice__badge">Site under construction</span>
                <span class="site-under-construction-notice__message"><?= e($siteUnderConstructionNoticeMessage) ?></span>
            </div>
        </div>
    <?php endif; ?>
</header>

<?php if (str_contains(' ' . $bodyClass . ' ', ' page-itinerary ') || str_contains(' ' . $bodyClass . ' ', ' page-tour ') || str_contains(' ' . $bodyClass . ' ', ' page-day-tour ')): ?>
<div class="floating-plan-btn" id="floatingPlanBtn" x-data="{ open: false }">
  <a href="<?= e(base_url('contact') . (!empty($itinName) ? '?itinerary=' . urlencode($itinName) : '')) ?>"
     class="floating-plan-btn-inner"
     @mouseenter="open = true" @mouseleave="open = false"
     @touchstart="open = !open">
    <span class="floating-plan-icon">✈</span>
    <span class="floating-plan-text" x-show="open" x-transition.opacity.duration.200ms>
      Customize this Itinerary
    </span>
  </a>
</div>
<?php endif; ?>

<main id="main-content" tabindex="-1">
