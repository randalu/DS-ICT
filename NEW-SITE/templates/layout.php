<?php
declare(strict_types=1);

use function NewSite\e;
use function NewSite\icon;
use function NewSite\media;
use function NewSite\url;

require_once __DIR__ . '/components.php';
/** @var array $page */
$isHome = $page['page_kind'] === 'home';
$heroImage = $page['hero']['image'] ?? $page['record']['image'] ?? '';
$hasBleedHero = in_array($page['page_kind'], ['collection', 'detail', 'article'], true)
    && is_string($heroImage) && $heroImage !== ''
    && $page['mode'] !== 'unavailable';
$bodyClasses = array_values(array_filter([$isHome ? 'page-home' : null, $hasBleedHero ? 'has-bleed-hero' : null]));
$sourceLabel = match ($page['mode']) {
    'database' => 'Read-only content',
    'fixture' => 'Sample content',
    default => 'Content unavailable',
};
$navigation = [
    'Destinations' => '/destinations',
    'Tours & itineraries' => '/tours',
    'Experiences' => '/experiences',
    'Travel guide' => '/blog',
    'About us' => '/about-us',
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="<?= e((string) $page['description']) ?>">
    <meta name="theme-color" content="#123F49">
    <title><?= e((string) $page['title']) ?></title>
    <link rel="canonical" href="<?= e((string) $page['canonical']) ?>">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="preload" href="/assets/fonts/fraunces-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/manrope-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/assets/site.css">
    <?php if (!$isHome): ?><link rel="stylesheet" href="/assets/pages.css"><link rel="stylesheet" href="/assets/support.css"><?php endif; ?>
    <script src="/assets/site.js" defer></script>
    <?php if (!$isHome): ?><script src="/assets/forms.js" defer></script><?php endif; ?>
</head>
<body<?= $bodyClasses !== [] ? ' class="' . e(implode(' ', $bodyClasses)) . '"' : '' ?> data-content-mode="<?= e((string) $page['mode']) ?>">
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="preview-bar">
        <div class="preview-bar__inner">
            <span><span class="status-dot" aria-hidden="true"></span> Local design preview</span>
            <a href="#preview-note" data-preview-open><?= e($sourceLabel) ?> <span class="preview-bar__extra">&middot; Nothing is submitted</span> <?= icon('arrow-up-right') ?></a>
        </div>
    </div>
    <header class="site-header">
        <div class="site-header__inner container">
            <a class="brand" href="<?= e(url('/')) ?>" aria-label="Island Culture Travels homepage">
                <img src="<?= e(media('assets/images/island_culture_travels_logo.png')) ?>" width="200" height="92" alt="Island Culture Travels" decoding="async">
            </a>
            <nav class="desktop-nav" aria-label="Main navigation">
                <?php foreach ($navigation as $label => $target): ?><a href="<?= e(url($target)) ?>"<?= $page['route'] === $target ? ' aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?>
            </nav>
            <a class="header-cta" href="<?= e(url('/contact')) ?>">Plan your trip <?= icon('arrow-up-right') ?></a>
            <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="mobile-menu"><span>Menu</span><?= icon('menu') ?></button>
        </div>
        <nav class="mobile-menu" id="mobile-menu" aria-label="Mobile navigation">
            <?php foreach ($navigation as $label => $target): ?><a href="<?= e(url($target)) ?>"><?= e($label) ?> <?= icon('arrow-up-right') ?></a><?php endforeach; ?>
            <a href="<?= e(url('/itineraries')) ?>">Browse itineraries <?= icon('arrow-up-right') ?></a>
            <a href="<?= e(url('/day-tours')) ?>">Day tours <?= icon('arrow-up-right') ?></a>
            <a href="<?= e(url('/contact')) ?>">Plan your trip <?= icon('arrow-up-right') ?></a>
        </nav>
    </header>
    <main id="main" tabindex="-1">
        <?php
        $views = ['home' => 'home.php', 'collection' => 'collection.php', 'detail' => 'detail.php', 'article' => 'article.php', 'support' => 'support.php', 'account' => 'account.php', 'not-found' => 'milestone.php'];
        require __DIR__ . '/' . ($views[$page['page_kind']] ?? 'milestone.php');
        ?>
    </main>
    <footer class="site-footer">
        <div class="container footer-main">
            <div class="footer-brand">
                <a class="brand" href="<?= e(url('/')) ?>"><img src="<?= e(media('assets/images/island_culture_travels_logo.png')) ?>" width="200" height="92" alt="Island Culture Travels homepage" loading="lazy"></a>
                <p>Private journeys.<br>Personal perspectives.<br>Your Sri Lanka.</p>
            </div>
            <div class="footer-column"><h2>Find your way</h2><a href="<?= e(url('/destinations')) ?>">Destinations</a><a href="<?= e(url('/itineraries')) ?>">Suggested itineraries</a><a href="<?= e(url('/tours')) ?>">Tours & travel styles</a><a href="<?= e(url('/day-tours')) ?>">Day tours</a><a href="<?= e(url('/experiences')) ?>">Experiences</a></div>
            <div class="footer-column"><h2>A little guidance</h2><a href="<?= e(url('/blog')) ?>">Travel journal</a><a href="<?= e(url('/seasonal')) ?>">When to visit</a><a href="<?= e(url('/from')) ?>">Planning from abroad</a><a href="<?= e(url('/faq')) ?>">Your questions</a><a href="<?= e(url('/about-us')) ?>">Our story</a><a href="<?= e(url('/reviews')) ?>">Traveller reviews</a></div>
            <div class="footer-column"><h2>Make it personal</h2><a href="<?= e(url('/contact')) ?>">Plan your trip</a><a href="<?= e(url('/private-driver-sri-lanka')) ?>">Private chauffeur travel</a><a href="<?= e(url('/airport-transfers')) ?>">Airport transfers</a><a href="<?= e(url('/account/login')) ?>">Your account</a><a href="<?= e(url('/privacy_choices.php')) ?>">Privacy choices</a><?php if ($page['mode'] === 'fixture'): ?><a href="<?= e(url('/booking-terms-and-conditions')) ?>">Policy layout previews</a><?php endif; ?><?php if (isset($page['route_manifest']['/business-partners'])): ?><a href="<?= e(url('/business-partners')) ?>">Partner layout previews</a><?php endif; ?></div>
        </div>
        <div class="container footer-bottom"><p>&copy; <?= date('Y') ?> Island Culture Travels</p><a href="#preview-note" data-preview-open>About this design preview <?= icon('arrow-up-right') ?></a><a href="#main">Back to top <?= icon('arrow-up-right') ?></a></div>
        <div class="container preview-note" id="preview-note"><span class="status-dot" aria-hidden="true"></span><p><strong>Isolated, read-only preview.</strong> <?= e((string) $page['source_message']) ?> Customer accounts, inquiries and optional services never call the original site's write handlers. Demo actions are temporary and are not saved or sent.</p></div>
    </footer>
    <dialog class="preview-dialog" id="preview-dialog" aria-labelledby="preview-dialog-title">
        <button type="button" class="dialog-close" data-preview-close aria-label="Close preview information"><?= icon('close') ?></button>
        <p class="eyebrow">A separate space to explore</p><h2 id="preview-dialog-title">A new look.<br>The original site, untouched.</h2>
        <p><?= e((string) $page['source_message']) ?></p>
        <ul><li>No inquiries, payments or account changes are submitted.</li><li>No customer sessions, analytics, AI or external widgets run here.</li><li>Sample journeys are layout illustrations, not published offers.</li><li>Only verified read-only credentials can supply CMS content; unavailable data is never silently replaced with samples.</li></ul>
        <button class="button button--dark" type="button" data-preview-close>Continue exploring <?= icon('arrow-right') ?></button>
    </dialog>
</body>
</html>
