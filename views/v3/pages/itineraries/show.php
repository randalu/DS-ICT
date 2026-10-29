<?php
declare(strict_types=1);

/**
 * Itinerary detail — route timeline layout.
 *
 * @var \ICT\View\Page $page
 */

$d = $page->data;
?>
<section class="ix-detail-hero">
    <div class="ix-wrap">
        <nav class="ix-crumbs" aria-label="Breadcrumb">
            <a href="<?= e(base_url()) ?>">Home</a>
            <span aria-hidden="true">/</span>
            <a href="<?= e(base_url('itineraries')) ?>">Itineraries</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= e($d['name']) ?></span>
        </nav>

        <div class="ix-detail-hero__grid">
            <div>
                <p class="ix-eyebrow">Private itinerary</p>
                <h1 class="ix-h1"><?= e($d['name']) ?></h1>
                <?php if ($d['excerpt'] !== ''): ?>
                    <p class="ix-lead"><?= e($d['excerpt']) ?></p>
                <?php endif; ?>

                <div class="ix-detail-hero__facts">
                    <?php if ($d['days'] > 0): ?>
                        <span class="ix-chip"><?= (int) $d['days'] ?> days</span>
                    <?php endif; ?>
                    <?php if ($d['price'] !== ''): ?>
                        <span class="ix-chip ix-chip--coral">From <?= e($d['price']) ?><?= $d['price_unit'] !== '' ? ' · ' . e($d['price_unit']) : '' ?></span>
                    <?php endif; ?>
                    <span class="ix-chip">Driver-guide included</span>
                </div>

                <div class="ix-detail-hero__actions">
                    <a class="ix-btn" href="<?= e($d['cta_url']) ?>">Customise this trip</a>
                    <a class="ix-btn ix-btn--ghost" href="<?= e(base_url('itineraries')) ?>">All itineraries</a>
                </div>
            </div>

            <div class="ix-detail-hero__media">
                <img src="<?= e($d['hero']) ?>" alt="<?= e($d['name']) ?>" fetchpriority="high" decoding="async" width="1200" height="900">
            </div>
        </div>
    </div>
</section>

<section class="ix-section ix-section--tight">
    <div class="ix-wrap ix-detail-body">
        <div class="ix-detail-body__main">
            <?php if (trim(strip_tags($d['intro'])) !== ''): ?>
                <div class="ix-prose"><?= $d['intro'] ?></div>
            <?php endif; ?>

            <?php if (trim(strip_tags($d['route_summary'])) !== ''): ?>
                <div class="ix-route-summary">
                    <h2 class="ix-h3">Route</h2>
                    <div class="ix-prose"><?= $d['route_summary'] ?></div>
                </div>
            <?php endif; ?>

            <?php if ($d['timeline'] !== []): ?>
                <h2 class="ix-h2" style="margin-top:2.5rem">Day by day</h2>
                <ol class="ix-timeline">
                    <?php foreach ($d['timeline'] as $day): ?>
                        <li class="ix-timeline__day">
                            <div class="ix-timeline__marker" aria-hidden="true"></div>
                            <div class="ix-timeline__body">
                                <span class="ix-timeline__label"><?= e($day['label']) ?></span>
                                <h3 class="ix-h4"><?= e($day['title']) ?></h3>
                                <?php if ($day['summary'] !== ''): ?>
                                    <p><?= e($day['summary']) ?></p>
                                <?php endif; ?>
                                <div class="ix-timeline__meta">
                                    <?php if ($day['stay'] !== ''): ?><span class="ix-chip">Stay: <?= e($day['stay']) ?></span><?php endif; ?>
                                    <?php if ($day['travel'] !== ''): ?><span class="ix-chip"><?= e($day['travel']) ?></span><?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>

            <?php if (trim(strip_tags($d['travel_notes'])) !== ''): ?>
                <section style="margin-top:2.5rem">
                    <h2 class="ix-h3">Good to know</h2>
                    <div class="ix-prose"><?= $d['travel_notes'] ?></div>
                </section>
            <?php endif; ?>
        </div>

        <aside class="ix-detail-aside">
            <div class="ix-aside-card">
                <h2 class="ix-h4">At a glance</h2>
                <dl class="ix-facts">
                    <?php if ($d['days'] > 0): ?><div><dt>Length</dt><dd><?= (int) $d['days'] ?> days</dd></div><?php endif; ?>
                    <?php if ($d['price'] !== ''): ?><div><dt>From</dt><dd><?= e($d['price']) ?><?= $d['price_unit'] !== '' ? ' <span>' . e($d['price_unit']) . '</span>' : '' ?></dd></div><?php endif; ?>
                    <div><dt>Style</dt><dd>Private, driver-guided</dd></div>
                    <div><dt>Customisable</dt><dd>Yes</dd></div>
                </dl>
                <a class="ix-btn" style="width:100%;margin-top:1rem" href="<?= e($d['cta_url']) ?>">Plan this route</a>
            </div>

            <?php if (trim(strip_tags($d['best_for'])) !== ''): ?>
                <div class="ix-aside-card">
                    <h2 class="ix-h4">Best for</h2>
                    <div class="ix-prose ix-prose--sm"><?= $d['best_for'] ?></div>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</section>

<?php if ($d['related'] !== []): ?>
<section class="ix-section ix-section--surface">
    <div class="ix-wrap">
        <div class="ix-section__head">
            <h2 class="ix-h2">Other journeys</h2>
            <a class="ix-link" href="<?= e(base_url('itineraries')) ?>">All itineraries <span class="ix-link__arrow" aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="ix-grid ix-grid--3 ix-rail-mobile" tabindex="0" role="region" aria-label="Related itineraries">
            <?php foreach ($d['related'] as $card): ?>
                <article class="ix-card">
                    <a class="ix-card__link" href="<?= e($card['url']) ?>">
                        <span class="ix-card__media">
                            <img src="<?= e($card['image']) ?>" alt="<?= e($card['alt']) ?>" loading="lazy" decoding="async">
                            <?php if (($card['days'] ?? 0) > 0): ?><span class="ix-card__badge"><?= (int) $card['days'] ?> days</span><?php endif; ?>
                        </span>
                        <span class="ix-card__body">
                            <span class="ix-card__title"><?= e($card['title']) ?></span>
                            <span class="ix-card__facts">
                                <?php if (($card['price'] ?? '') !== ''): ?><span class="ix-card__price">From <?= e($card['price']) ?></span><?php endif; ?>
                            </span>
                        </span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
