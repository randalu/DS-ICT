<?php
declare(strict_types=1);

/**
 * Utility bar, sticky header with search, and the Alpine drawer.
 *
 * @var \ICT\View\Page $page
 */

if ($page->recordPageView) {
    record_page_view();
}

$basePath = rtrim((string) parse_url(base_url(), PHP_URL_PATH), '/');
$current = request_path();
if ($basePath !== '' && $basePath !== '/' && str_starts_with($current, $basePath)) {
    $current = substr($current, strlen($basePath)) ?: '/';
}
$current = '/' . trim($current, '/');

$isOn = static function (string $prefix) use ($current): bool {
    $prefix = '/' . trim($prefix, '/');

    return $current === $prefix || str_starts_with($current, $prefix . '/');
};

$nav = [
    ['label' => 'Destinations', 'url' => base_url('destinations'), 'on' => $isOn('destinations')],
    ['label' => 'Experiences', 'url' => base_url('experiences'), 'on' => $isOn('experiences')],
    ['label' => 'Activities', 'url' => base_url('activities'), 'on' => $isOn('activities')],
    ['label' => 'Journeys', 'url' => base_url('itineraries'), 'on' => $isOn('itineraries') || $isOn('tours') || $isOn('day-tours')],
    ['label' => 'Blog', 'url' => base_url('blog'), 'on' => $isOn('blog')],
    ['label' => 'Guide', 'url' => base_url('seasonal'), 'on' => $isOn('seasonal') || $isOn('from')],
    ['label' => 'About', 'url' => base_url('about-us'), 'on' => $isOn('about-us') || $isOn('why-choose-us') || $isOn('reviews')],
];

$phone = function_exists('site_primary_phone') ? site_primary_phone() : '';
$email = function_exists('site_contact_email') ? site_contact_email() : '';
$whatsapp = function_exists('site_whatsapp_url') ? site_whatsapp_url() : '';
$construction = function_exists('site_under_construction_notice_enabled') && site_under_construction_notice_enabled();
$constructionMessage = function_exists('site_under_construction_notice_message') ? site_under_construction_notice_message() : '';
?>
<a class="ix-skip" href="#main">Skip to content</a>

<div class="ix-utility">
    <div class="ix-wrap ix-utility__inner">
        <div class="ix-utility__side">
            <?php if ($phone !== ''): ?>
                <a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a>
            <?php endif; ?>
            <?php if ($whatsapp !== ''): ?>
                <a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a>
            <?php endif; ?>
        </div>
        <div class="ix-utility__side">
            <?php if (ict_optional_allowed('translation')): ?>
                <div class="gtranslate_wrapper"></div>
            <?php endif; ?>
            <a href="<?= e(base_url('account')) ?>">Client login</a>
        </div>
    </div>
</div>

<header class="ix-header" data-ix-header>
    <div class="ix-wrap ix-header__inner">
        <a class="ix-brand" href="<?= e(base_url()) ?>" aria-label="<?= e(APP_NAME) ?> home">
            <img class="ix-brand__mark" src="<?= e(base_url('assets/images/island_culture_travels_logo.png')) ?>" alt="" width="34" height="34" decoding="async">
            <span>
                <span class="ix-brand__name">Island Culture</span>
                <span class="ix-brand__sub">Private Sri Lanka</span>
            </span>
        </a>

        <nav class="ix-nav" aria-label="Primary">
            <?php foreach ($nav as $item): ?>
                <a href="<?= e($item['url']) ?>"<?= $item['on'] ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <form class="ix-search" method="get" action="<?= e(base_url('destinations')) ?>" role="search">
            <svg class="ix-search__icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input class="ix-search__input" type="search" name="q" placeholder="Search destinations" aria-label="Search destinations">
        </form>

        <div class="ix-header__actions">
            <button class="ix-icon-btn" type="button" data-ix-theme-toggle aria-pressed="false" aria-label="Switch to dark mode">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
            </button>
            <a class="ix-btn ix-btn--sm" href="<?= e(base_url('contact')) ?>">Plan trip</a>
            <button class="ix-icon-btn ix-burger" type="button"
                    @click="menu = !menu"
                    :aria-expanded="menu ? 'true' : 'false'"
                    aria-controls="ix-drawer"
                    aria-label="Open menu">
                <span class="ix-burger__bars" aria-hidden="true"><span></span><span></span></span>
            </button>
        </div>
    </div>

    <?php if ($construction): ?>
        <div class="ix-notice-construction" role="status">
            <div class="ix-wrap">
                <strong>Site under construction</strong>
                <span><?= e($constructionMessage) ?></span>
            </div>
        </div>
    <?php endif; ?>
</header>

<div class="ix-drawer" id="ix-drawer" x-show="menu" x-cloak x-transition.opacity.duration.200ms
     @keydown.escape.window="menu = false">
    <nav class="ix-drawer__list" aria-label="Mobile">
        <?php foreach ($nav as $index => $item): ?>
            <a href="<?= e($item['url']) ?>" @click="menu = false"<?= $item['on'] ? ' aria-current="page"' : '' ?>>
                <span><?= sprintf('%02d', $index + 1) ?></span><?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="ix-drawer__actions">
        <a class="ix-btn" href="<?= e(base_url('contact')) ?>" @click="menu = false">Plan my trip</a>
        <?php if ($whatsapp !== ''): ?>
            <a class="ix-btn ix-btn--ghost" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a>
        <?php endif; ?>
    </div>
</div>

<main id="main" tabindex="-1">
