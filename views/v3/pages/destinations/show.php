<?php
declare(strict_types=1);

/**
 * Destination detail — editorial guide with a sticky facts rail.
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
            <a href="<?= e(base_url('destinations')) ?>">Destinations</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= e($d['name']) ?></span>
        </nav>

        <div class="ix-detail-hero__grid">
            <div>
                <?php if ($d['region'] !== ''): ?><p class="ix-eyebrow"><?= e($d['region']) ?></p><?php endif; ?>
                <h1 class="ix-h1"><?= e($d['name']) ?></h1>
                <?php if ($d['excerpt'] !== ''): ?><p class="ix-lead"><?= e($d['excerpt']) ?></p><?php endif; ?>

                <div class="ix-detail-hero__facts">
                    <?php if ($d['stay'] !== ''): ?><span class="ix-chip"><?= e($d['stay']) ?></span><?php endif; ?>
                    <?php if ($d['type'] !== ''): ?><span class="ix-chip"><?= e($d['type']) ?></span><?php endif; ?>
                    <?php if ($d['best_known_for'] !== ''): ?><span class="ix-chip ix-chip--coral"><?= e($d['best_known_for']) ?></span><?php endif; ?>
                </div>

                <div class="ix-detail-hero__actions">
                    <a class="ix-btn" href="<?= e($d['cta_url']) ?>">Plan this stop</a>
                    <a class="ix-btn ix-btn--ghost" href="<?= e($d['itineraries_url']) ?>">Journeys through here</a>
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

            <?php if (trim(strip_tags($d['why_visit'])) !== ''): ?>
                <section>
                    <h2 class="ix-h3">Why go</h2>
                    <div class="ix-prose"><?= $d['why_visit'] ?></div>
                </section>
            <?php endif; ?>

            <?php if (trim(strip_tags($d['things_to_do'])) !== ''): ?>
                <section>
                    <h2 class="ix-h3">Things to do</h2>
                    <div class="ix-prose"><?= $d['things_to_do'] ?></div>
                </section>
            <?php endif; ?>

            <?php if (trim(strip_tags($d['best_time'])) !== ''): ?>
                <section>
                    <h2 class="ix-h3">When to go</h2>
                    <div class="ix-prose"><?= $d['best_time'] ?></div>
                </section>
            <?php endif; ?>

            <?php if (trim(strip_tags($d['getting_there'])) !== ''): ?>
                <section>
                    <h2 class="ix-h3">Getting there</h2>
                    <div class="ix-prose"><?= $d['getting_there'] ?></div>
                </section>
            <?php endif; ?>

            <?php if ($d['faq'] !== []): ?>
                <section style="margin-top:2.5rem">
                    <h2 class="ix-h3">Questions travelers ask</h2>
                    <div class="ix-faq">
                        <?php foreach ($d['faq'] as $item): ?>
                            <details class="ix-faq__item">
                                <summary><?= e($item['q']) ?></summary>
                                <div class="ix-prose ix-prose--sm"><p><?= e($item['a']) ?></p></div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <aside class="ix-detail-aside">
            <div class="ix-aside-card">
                <h2 class="ix-h4">At a glance</h2>
                <dl class="ix-facts">
                    <?php if ($d['region'] !== ''): ?><div><dt>Region</dt><dd><?= e($d['region']) ?></dd></div><?php endif; ?>
                    <?php if ($d['stay'] !== ''): ?><div><dt>Suggested stay</dt><dd><?= e($d['stay']) ?></dd></div><?php endif; ?>
                    <?php if ($d['type'] !== ''): ?><div><dt>Type</dt><dd><?= e($d['type']) ?></dd></div><?php endif; ?>
                    <div><dt>Access</dt><dd>Private vehicle</dd></div>
                </dl>
                <a class="ix-btn" style="width:100%;margin-top:1rem" href="<?= e($d['cta_url']) ?>">Plan this stop</a>
            </div>

            <?php if (trim(strip_tags($d['where_to_stay'])) !== ''): ?>
                <div class="ix-aside-card">
                    <h2 class="ix-h4">Where to stay</h2>
                    <div class="ix-prose ix-prose--sm"><?= $d['where_to_stay'] ?></div>
                </div>
            <?php endif; ?>

            <?php if (trim(strip_tags($d['travel_tips'])) !== ''): ?>
                <div class="ix-aside-card">
                    <h2 class="ix-h4">Tips</h2>
                    <div class="ix-prose ix-prose--sm"><?= $d['travel_tips'] ?></div>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</section>

<?php if ($d['related'] !== []): ?>
<section class="ix-section ix-section--surface">
    <div class="ix-wrap">
        <div class="ix-section__head">
            <h2 class="ix-h2">Nearby</h2>
            <a class="ix-link" href="<?= e(base_url('destinations')) ?>">All destinations <span class="ix-link__arrow" aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="ix-grid ix-grid--3 ix-rail-mobile" tabindex="0" role="region" aria-label="Nearby destinations">
            <?php foreach ($d['related'] as $card): ?>
                <article class="ix-card">
                    <a class="ix-card__link" href="<?= e($card['url']) ?>">
                        <span class="ix-card__media">
                            <img src="<?= e($card['image']) ?>" alt="<?= e($card['alt']) ?>" loading="lazy" decoding="async">
                        </span>
                        <span class="ix-card__body">
                            <span class="ix-card__title"><?= e($card['title']) ?></span>
                            <?php if (($card['kicker'] ?? '') !== ''): ?>
                                <span class="ix-card__excerpt"><?= e($card['kicker']) ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
