<?php
declare(strict_types=1);

use function NewSite\e;
use function NewSite\icon;
use function NewSite\url;
use function NewSite\View\image;

/** @var array $page */
$notFound = $page['page_kind'] === 'not-found';
?>
<section class="milestone-page container section-space">
    <div class="milestone-page__copy"><p class="eyebrow"><?= $notFound ? 'A little off the path' : 'An unavailable preview view' ?></p><h1><?= $notFound ? 'This path<br>ends here.' : 'This view<br>is unavailable.' ?></h1><p class="lead"><?= $notFound ? 'This preview does not have a page at that address.' : e((string) $page['title']) ?></p><p><?= $notFound ? 'Return to the homepage to explore the design. Unknown pages are not replaced with invented catalogue records.' : 'The expected view cannot be selected in this local preview. No request has been forwarded to the original site, and its backend remains unchanged.' ?></p><a class="button button--dark" href="<?= e(url('/')) ?>">Explore the homepage <?= icon('arrow-right') ?></a><?php if (!$notFound): ?><p class="milestone-caption">No original login, inquiry, consent or booking action runs here.</p><?php endif; ?></div>
    <div class="milestone-page__image"><?php image('assets/images/country-market/nine-arch-bridge.jpg', 'Train crossing the Nine Arch Bridge surrounded by Sri Lankan hill-country forest', '', '(max-width: 800px) 90vw, 46vw', true); ?><span>The journey continues, one considered step at a time.</span></div>
</section>
<section class="milestone-status container"><h2>A separate space to explore</h2><ol><li><strong>Frontend layouts</strong><span>Core and linked page-family previews</span></li><li><strong>Real source content</strong><span>Verified SELECT-only connection required</span></li><li><strong>Live actions</strong><span>Not enabled in this preview</span></li></ol></section>
