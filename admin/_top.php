<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$adminUser = admin_user();
$adminTitle = $adminTitle ?? 'Admin';
$adminPage = $adminPage ?? '';
$adminSignedInName = trim((string) ($adminUser['name'] ?? $adminUser['username'] ?? 'Admin'));

$adminNavGroups = [
    [
        'label' => 'Overview',
        'items' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => base_url('admin/dashboard.php'), 'icon' => 'mdi-grid-large'],
            ['key' => 'webstats', 'label' => 'Web Stats', 'href' => base_url('admin/webstats.php'), 'icon' => 'mdi-chart-line'],
            ['key' => 'ai_search_audit', 'label' => 'AI/Search Audit', 'href' => base_url('admin/ai_search_audit.php'), 'icon' => 'mdi-magnify-scan'],
            ['key' => 'site_audit', 'label' => 'Site Audit', 'href' => base_url('admin/site_audit.php'), 'icon' => 'mdi-shield-search'],
            ['key' => 'inquiries', 'label' => 'Inquiries', 'href' => base_url('admin/inquiries.php'), 'icon' => 'mdi-email-outline'],
            ['key' => 'quotations', 'label' => 'Quotations', 'href' => base_url('admin/quotations.php'), 'icon' => 'mdi-file-document-outline'],
        ],
    ],
    [
        'label' => 'Leads & Partners',
        'items' => [
            ['key' => 'business_partners', 'label' => 'Business Partners', 'href' => base_url('admin/business_partners.php'), 'icon' => 'mdi-handshake-outline'],
            ['key' => 'reviews', 'label' => 'Reviews', 'href' => base_url('admin/reviews.php'), 'icon' => 'mdi-star-outline'],
        ],
    ],
    [
        'label' => 'Travel Content',
        'items' => [
            ['key' => 'destinations', 'label' => 'Destinations', 'href' => base_url('admin/destinations.php'), 'icon' => 'mdi-map-marker-outline'],
            ['key' => 'experiences', 'label' => 'Experiences', 'href' => base_url('admin/experiences.php'), 'icon' => 'mdi-compass-outline'],
            ['key' => 'activities', 'label' => 'Activities', 'href' => base_url('admin/activities.php'), 'icon' => 'mdi-hiking'],
            ['key' => 'tours', 'label' => 'Tour Packages', 'href' => base_url('admin/tours.php'), 'icon' => 'mdi-car-estate'],
            ['key' => 'day_tours', 'label' => 'Day Tour Packages', 'href' => base_url('admin/day_tours.php'), 'icon' => 'mdi-car-clock'],
            ['key' => 'itineraries', 'label' => 'Itineraries', 'href' => base_url('admin/itineraries.php'), 'icon' => 'mdi-map-outline'],
            ['key' => 'itinerary_categories', 'label' => 'Itinerary Categories', 'href' => base_url('admin/itinerary_categories.php'), 'icon' => 'mdi-shape-outline'],
            ['key' => 'countries', 'label' => 'Country Pages', 'href' => base_url('admin/countries.php'), 'icon' => 'mdi-earth'],
            ['key' => 'seasonal_guides', 'label' => 'Seasonal Guides', 'href' => base_url('admin/seasonal_guides.php'), 'icon' => 'mdi-weather-partly-cloudy'],
            ['key' => 'pages', 'label' => 'Static Pages', 'href' => base_url('admin/pages.php'), 'icon' => 'mdi-file-document-outline'],
            ['key' => 'faqs', 'label' => 'FAQ Manager', 'href' => base_url('admin/faqs.php'), 'icon' => 'mdi-frequently-asked-questions'],
            ['key' => 'services', 'label' => 'Services', 'href' => base_url('admin/services.php'), 'icon' => 'mdi-briefcase-outline'],
        ],
    ],
    [
        'label' => 'Media & Social',
        'items' => [
            ['key' => 'image_manager', 'label' => 'Image Manager', 'href' => base_url('admin/image_manager.php'), 'icon' => 'mdi-image-edit-outline'],
            ['key' => 'map_manager', 'label' => 'Map Manager', 'href' => base_url('admin/map_manager.php'), 'icon' => 'mdi-map-marker-path'],
            ['key' => 'seo_manager', 'label' => 'SEO Manager', 'href' => base_url('admin/seo_manager.php'), 'icon' => 'mdi-text-search'],
            ['key' => 'media_library', 'label' => 'Media Library', 'href' => base_url('admin/media_library.php'), 'icon' => 'mdi-folder-image'],
            ['key' => 'social_links', 'label' => 'Social Links', 'href' => base_url('admin/social_links.php'), 'icon' => 'mdi-link-variant'],
            ['key' => 'social_channels', 'label' => 'Social Channels', 'href' => base_url('admin/social_channels.php'), 'icon' => 'mdi-account-group-outline'],
            ['key' => 'social_posts', 'label' => 'Social Drafts', 'href' => base_url('admin/social_posts.php'), 'icon' => 'mdi-post-outline'],
            ['key' => 'blog', 'label' => 'Blog', 'href' => base_url('admin/blog_posts.php'), 'icon' => 'mdi-notebook-outline'],
            ['key' => 'blog_settings', 'label' => 'Blog Settings', 'href' => base_url('admin/blog_settings.php'), 'icon' => 'mdi-tune'],
            ['key' => 'tripadvisor_widgets', 'label' => 'TripAdvisor Widgets', 'href' => base_url('admin/tripadvisor_widgets_preview.php'), 'icon' => 'mdi-forum-outline'],
        ],
    ],
    [
        'label' => 'System & Settings',
        'items' => [
            ['key' => 'homepage_manager', 'label' => 'Homepage Manager', 'href' => base_url('admin/homepage_manager.php'), 'icon' => 'mdi-home-edit-outline'],
            ['key' => 'site_settings', 'label' => 'Site Settings', 'href' => base_url('admin/site_settings.php'), 'icon' => 'mdi-cog-outline'],
            ['key' => 'policy_settings', 'label' => 'Cancellation & Refund', 'href' => base_url('admin/policy_settings.php'), 'icon' => 'mdi-file-document-outline'],
            ['key' => 'retention_settings', 'label' => 'Data Retention & Records', 'href' => base_url('admin/retention_settings.php'), 'icon' => 'mdi-calendar-clock'],
            ['key' => 'privacy_settings', 'label' => 'Privacy & Tracking Controls', 'href' => base_url('admin/privacy_settings.php'), 'icon' => 'mdi-shield-check-outline'],
            ['key' => 'analytics_retention', 'label' => 'Analytics Retention Hold', 'href' => base_url('admin/analytics_retention.php'), 'icon' => 'mdi-calendar-clock'],
            ['key' => 'analytics_settings', 'label' => 'Analytics Settings', 'href' => base_url('admin/analytics_settings.php'), 'icon' => 'mdi-google-analytics'],
            ['key' => 'trip_planner_settings', 'label' => 'Trip Planner', 'href' => base_url('admin/trip_planner_settings.php'), 'icon' => 'mdi-calendar-edit'],
            ['key' => 'weather_api', 'label' => 'Weather API / Data', 'href' => base_url('admin/weather_api.php'), 'icon' => 'mdi-weather-cloudy'],
            ['key' => 'ai_settings', 'label' => 'AI Settings', 'href' => base_url('admin/ai_settings.php'), 'icon' => 'mdi-auto-fix'],
        ],
    ],
];

$adminGroupKey = '';
foreach ($adminNavGroups as $group) {
    $groupKey = strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $group['label']) ?: 'group');
    foreach ($group['items'] as $item) {
        if ((string) ($item['key'] ?? '') === $adminPage) {
            $adminGroupKey = $groupKey;
            break 2;
        }
    }
}

$renderAdminNav = static function (array $groups, string $activePage, string $activeGroupKey): void {
    foreach ($groups as $group) {
        $groupLabel = (string) ($group['label'] ?? 'Section');
        $groupKey = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $groupLabel) ?: 'group');
        $groupId = 'admin-nav-' . $groupKey;
        $isOpen = $groupKey === $activeGroupKey;
        ?>
        <li class="nav-item nav-category"><?= e($groupLabel) ?></li>
        <li class="nav-item">
            <a class="nav-link<?= $isOpen ? '' : ' collapsed' ?>" data-bs-toggle="collapse" href="#<?= e($groupId) ?>" aria-expanded="<?= $isOpen ? 'true' : 'false' ?>" aria-controls="<?= e($groupId) ?>">
                <i class="menu-icon mdi mdi-folder-outline"></i>
                <span class="menu-title"><?= e($groupLabel) ?></span>
                <i class="menu-arrow"></i>
            </a>
            <div class="collapse<?= $isOpen ? ' show' : '' ?>" id="<?= e($groupId) ?>">
                <ul class="nav flex-column sub-menu">
                    <?php foreach (($group['items'] ?? []) as $item): ?>
                        <?php $isActive = (string) ($item['key'] ?? '') === $activePage; ?>
                        <li class="nav-item">
                            <a class="nav-link<?= $isActive ? ' active' : '' ?>" href="<?= e((string) ($item['href'] ?? '#')) ?>">
                                <?php if (!empty($item['icon'])): ?>
                                    <i class="mdi <?= e((string) $item['icon']) ?> me-2"></i>
                                <?php endif; ?>
                                <?= e((string) ($item['label'] ?? 'Link')) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </li>
        <?php
    }
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= e($adminTitle) ?> | <?= e(APP_NAME) ?> Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="<?= e(ict_privacy_url('assets/vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css')) ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(ict_privacy_url('assets/vendor/mdi-font-7.4.47/css/materialdesignicons.min.css')) ?>">
    <link href="<?= e(ict_privacy_url('assets/vendor/quill-2.0.2/dist/quill.snow.css')) ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/typography.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/typography.css')) ?>">
    <script defer src="<?= e(ict_privacy_url('assets/vendor/alpinejs-3.14.8/dist/cdn.min.js')) ?>"></script>
</head>
<body class="admin-area">
<div class="container-scroller">
    <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
        <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
            <div class="me-3">
                <button class="navbar-toggler navbar-toggler align-self-center d-none d-lg-flex" type="button" data-admin-toggle="sidebar" aria-label="Toggle sidebar">
                    <span class="mdi mdi-menu"></span>
                </button>
            </div>
            <div>
                <a class="navbar-brand brand-logo" href="<?= e(base_url('admin/dashboard.php')) ?>">
                    <span class="brand-logo-text"><?= e(APP_NAME) ?> Admin</span>
                </a>
                <a class="navbar-brand brand-logo-mini" href="<?= e(base_url('admin/dashboard.php')) ?>">
                    <span class="brand-logo-text">ICT</span>
                </a>
            </div>
        </div>
        <div class="navbar-menu-wrapper d-flex align-items-top">
            <ul class="navbar-nav">
                <li class="nav-item d-none d-lg-flex align-items-center me-3">
                    <span class="admin-signed-in-badge">
                        <i class="mdi mdi-account-circle-outline me-1"></i>
                        <?= e($adminSignedInName) ?> &mdash; <?= e($adminTitle) ?>
                    </span>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item d-none d-lg-block">
                    <a class="nav-link admin-top-link" href="<?= e(base_url()) ?>" target="_blank" rel="noopener">
                        <i class="mdi mdi-open-in-new"></i> View Site
                    </a>
                </li>
                <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                    <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="btn btn-light btn-sm rounded-pill px-3"><?= e($adminSignedInName) ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                        <div class="dropdown-header text-center">
                            <p class="mb-1 mt-2 font-weight-semibold"><?= e($adminSignedInName) ?></p>
                            <p class="fw-light text-muted mb-0"><?= e((string) ($adminUser['username'] ?? 'Administrator')) ?></p>
                        </div>
                        <a class="dropdown-item" href="<?= e(base_url()) ?>" target="_blank" rel="noopener"><i class="dropdown-item-icon mdi mdi-open-in-new text-primary me-2"></i>Open Public Site</a>
                        <a class="dropdown-item" href="<?= e(base_url('admin/logout.php')) ?>"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out</a>
                    </div>
                </li>
            </ul>
            <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-admin-toggle="sidebar-mobile" aria-label="Open sidebar">
                <span class="mdi mdi-menu"></span>
            </button>
        </div>
    </nav>

    <div class="container-fluid page-body-wrapper">
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
            <ul class="nav">
                <?php $renderAdminNav($adminNavGroups, $adminPage, $adminGroupKey); ?>
                <li class="nav-item nav-category">Utilities</li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(base_url()) ?>" target="_blank" rel="noopener">
                        <i class="menu-icon mdi mdi-open-in-new"></i>
                        <span class="menu-title">View Public Site</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(base_url('admin/logout.php')) ?>">
                        <i class="menu-icon mdi mdi-logout"></i>
                        <span class="menu-title">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="main-panel">
            <div class="content-wrapper">
                <div class="admin-page-head">
                    <div>
                        <h1><?= e($adminTitle) ?></h1>
                        <p>Island Culture Travels administration panel</p>
                    </div>
                    <div class="admin-utility-links">
                        <a href="<?= e(base_url('admin/dashboard.php')) ?>"><i class="mdi mdi-view-dashboard-outline"></i> Dashboard</a>
                        <a href="<?= e(base_url('admin/inquiries.php')) ?>"><i class="mdi mdi-email-fast-outline"></i> Inquiries</a>
                        <a href="<?= e(base_url('admin/media_library.php')) ?>"><i class="mdi mdi-folder-multiple-image"></i> Media</a>
                    </div>
                </div>

                <div class="admin-flash-stack">
                    <?php if ($msg = flash_get('success')): ?>
                        <div class="alert alert-success"><?= e($msg) ?></div>
                    <?php endif; ?>

                    <?php if ($msg = flash_get('error')): ?>
                        <div class="alert alert-danger"><?= e($msg) ?></div>
                    <?php endif; ?>
                </div>
