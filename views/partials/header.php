<?php
declare(strict_types=1);

/**
 * Utility bar, header and opening <main> for the v2 public layout.
 *
 * No Bootstrap and no Alpine: the mobile drawer is a button + panel that
 * assets/js/site/nav.js enhances, and the desktop submenus open on hover or
 * focus-within so they stay keyboard reachable without JavaScript.
 *
 * @var \ICT\View\Page $page
 */

if ($page->recordPageView) {
    record_page_view();
}

$path = request_path();
$isOn = static function (string $prefix) use ($path): bool {
    $prefix = '/' . trim($prefix, '/');

    return $path === $prefix || str_starts_with($path, $prefix . '/');
};

$phone = function_exists('site_primary_phone') ? site_primary_phone() : '';
$email = function_exists('site_contact_email') ? site_contact_email() : '';
$whatsapp = function_exists('site_whatsapp_url') ? site_whatsapp_url() : '';
$construction = function_exists('site_under_construction_notice_enabled') && site_under_construction_notice_enabled();
$constructionMessage = function_exists('site_under_construction_notice_message') ? site_under_construction_notice_message() : '';

$primaryNav = [
    [
        'label' => 'Destinations',
        'url' => base_url('destinations'),
        'active' => $isOn('destinations'),
        'children' => [
            ['label' => 'All destinations', 'url' => base_url('destinations')],
            ['label' => 'Hidden gems', 'url' => base_url('destinations?filter=hidden-gems')],
        ],
    ],
    [
        'label' => 'Experiences',
        'url' => base_url('experiences'),
        'active' => $isOn('experiences') || $isOn('activities'),
        'children' => [
            ['label' => 'All experiences', 'url' => base_url('experiences')],
            ['label' => 'Activities', 'url' => base_url('activities')],
        ],
    ],
    [
        'label' => 'Journeys',
        'url' => base_url('itineraries'),
        'active' => $isOn('itineraries') || $isOn('tours') || $isOn('day-tours'),
        'children' => [
            ['label' => 'Signature itineraries', 'url' => base_url('itineraries')],
            ['label' => 'Private tours', 'url' => base_url('tours')],
            ['label' => 'Day tours', 'url' => base_url('day-tours')],
        ],
    ],
    [
        'label' => 'Travel guide',
        'url' => base_url('seasonal'),
        'active' => $isOn('seasonal') || $isOn('blog') || $isOn('from') || $isOn('faq'),
        'children' => [
            ['label' => 'When to visit', 'url' => base_url('seasonal')],
            ['label' => 'Journal', 'url' => base_url('blog')],
            ['label' => 'From abroad', 'url' => base_url('from')],
            ['label' => 'FAQ', 'url' => base_url('faq')],
        ],
    ],
    [
        'label' => 'About',
        'url' => base_url('about-us'),
        'active' => $isOn('about-us') || $isOn('why-choose-us') || $isOn('reviews'),
        'children' => [
            ['label' => 'Our story', 'url' => base_url('about-us')],
            ['label' => 'Why travel with us', 'url' => base_url('why-choose-us')],
            ['label' => 'Reviews', 'url' => base_url('reviews')],
        ],
    ],
];
?>
<a class="ic-skip" href="#main-content">Skip to main content</a>

<div class="ic-utility">
    <div class="ic-wrap ic-utility__inner">
        <div class="ic-utility__contact">
            <?php if ($phone !== ''): ?>
                <a class="ic-utility__link" href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6.5 3h3l1.5 4-2 1.5a12 12 0 0 0 6.5 6.5l1.5-2 4 1.5v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4.5 5.2 2 2 0 0 1 6.5 3Z"/></svg>
                    <span><?= e($phone) ?></span>
                </a>
            <?php endif; ?>
            <?php if ($email !== ''): ?>
                <a class="ic-utility__link" href="mailto:<?= e($email) ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    <span><?= e($email) ?></span>
                </a>
            <?php endif; ?>
        </div>
        <div class="ic-utility__meta">
            <?php if (ict_optional_allowed('translation')): ?>
                <div class="gtranslate_wrapper"></div>
            <?php else: ?>
                <a class="ic-utility__link" href="<?= e(ict_privacy_url('privacy_choices.php')) ?>" data-analytics-open data-no-track="1">Language &amp; privacy</a>
            <?php endif; ?>
            <span class="ic-utility__sep" aria-hidden="true"></span>
            <span class="ic-utility__note">Sri Lanka · private driver-guides</span>
        </div>
    </div>
</div>

<header class="ic-header" data-ic-header>
    <div class="ic-wrap ic-header__inner">
        <a class="ic-brand" href="<?= e(base_url()) ?>" aria-label="<?= e(APP_NAME) ?> home">
            <img
                class="ic-brand__mark"
                src="<?= e(base_url('assets/images/island_culture_travels_logo.png')) ?>"
                alt=""
                width="44"
                height="44"
                decoding="async"
            >
            <span class="ic-brand__text">
                <span class="ic-brand__name">Island Culture</span>
                <span class="ic-brand__sub">Private Sri Lanka travel</span>
            </span>
        </a>

        <nav class="ic-nav" aria-label="Primary">
            <ul class="ic-nav__list">
                <?php foreach ($primaryNav as $item): ?>
                    <li class="ic-nav__item<?= $item['children'] ? ' has-menu' : '' ?>">
                        <a class="ic-nav__link<?= $item['active'] ? ' is-active' : '' ?>"
                           href="<?= e($item['url']) ?>"
                           <?= $item['active'] ? 'aria-current="page"' : '' ?>>
                            <?= e($item['label']) ?>
                        </a>
                        <?php if ($item['children']): ?>
                            <div class="ic-nav__menu">
                                <ul>
                                    <?php foreach ($item['children'] as $child): ?>
                                        <li><a href="<?= e($child['url']) ?>"><?= e($child['label']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="ic-header__actions">
            <a class="ic-btn ic-btn--sun ic-btn--sm" href="<?= e(base_url('contact')) ?>">Plan my trip</a>
            <button class="ic-navtoggle" type="button"
                    data-ic-nav-toggle
                    aria-expanded="false"
                    aria-controls="ic-drawer">
                <span class="ic-navtoggle__bars" aria-hidden="true"><span></span><span></span></span>
                <span class="ic-navtoggle__label">Menu</span>
            </button>
        </div>
    </div>

    <?php if ($construction): ?>
        <div class="ic-notice" role="status">
            <div class="ic-wrap ic-notice__inner">
                <strong>Site under construction</strong>
                <span><?= e($constructionMessage) ?></span>
            </div>
        </div>
    <?php endif; ?>
</header>

<div class="ic-drawer" id="ic-drawer" data-ic-drawer hidden>
    <nav class="ic-drawer__inner" aria-label="Mobile">
        <ul class="ic-drawer__list">
            <?php foreach ($primaryNav as $item): ?>
                <li>
                    <a class="ic-drawer__link<?= $item['active'] ? ' is-active' : '' ?>" href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a>
                    <?php if ($item['children']): ?>
                        <ul class="ic-drawer__sublist">
                            <?php foreach ($item['children'] as $child): ?>
                                <li><a href="<?= e($child['url']) ?>"><?= e($child['label']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="ic-drawer__actions">
            <a class="ic-btn ic-btn--sun" href="<?= e(base_url('contact')) ?>">Plan my trip</a>
            <?php if ($whatsapp !== ''): ?>
                <a class="ic-btn ic-btn--outline" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a>
            <?php endif; ?>
            <?php if ($phone !== ''): ?>
                <a class="ic-drawer__phone" href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a>
            <?php endif; ?>
        </div>
    </nav>
</div>

<main id="main-content" tabindex="-1">
