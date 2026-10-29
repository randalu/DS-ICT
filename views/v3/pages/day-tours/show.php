<?php
declare(strict_types=1);

/**
 * Day tour detail — compact hero, highlights, meeting point, FAQ.
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
            <a href="<?= e(base_url('day-tours')) ?>">Day tours</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= e($d['name']) ?></span>
        </nav>

        <div class="ix-detail-hero__grid">
            <div>
                <p class="ix-eyebrow">Day tour</p>
                <h1 class="ix-h1"><?= e($d['name']) ?></h1>
                <?php if ($d['excerpt'] !== ''): ?><p class="ix-lead"><?= e($d['excerpt']) ?></p><?php endif; ?>

                <div class="ix-detail-hero__facts">
                    <?php if ($d['duration'] !== ''): ?><span class="ix-chip"><?= e($d['duration']) ?></span><?php endif; ?>
                    <?php if ($d['group'] > 0): ?><span class="ix-chip">Up to <?= (int) $d['group'] ?></span><?php endif; ?>
                    <?php if ($d['difficulty'] !== ''): ?><span class="ix-chip"><?= e(ucfirst($d['difficulty'])) ?></span><?php endif; ?>
                    <?php if ($d['price'] !== ''): ?><span class="ix-chip ix-chip--coral">From <?= e($d['price']) ?></span><?php endif; ?>
                </div>

                <div class="ix-detail-hero__actions">
                    <a class="ix-btn" href="<?= e($d['cta_url']) ?>">Check availability</a>
                    <a class="ix-btn ix-btn--ghost" href="<?= e(base_url('day-tours')) ?>">All day tours</a>
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

            <?php if (trim(strip_tags($d['highlights'])) !== ''): ?>
                <section style="margin-top:2rem">
                    <h2 class="ix-h3">Highlights</h2>
                    <div class="ix-prose"><?= $d['highlights'] ?></div>
                </section>
            <?php endif; ?>

            <?php if (trim(strip_tags($d['itinerary'])) !== ''): ?>
                <section style="margin-top:2rem">
                    <h2 class="ix-h3">The day, step by step</h2>
                    <div class="ix-prose"><?= $d['itinerary'] ?></div>
                </section>
            <?php endif; ?>

            <?php if (trim(strip_tags($d['includes'])) !== '' || trim(strip_tags($d['excludes'])) !== ''): ?>
                <div style="margin-top:2rem;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.25rem">
                    <?php if (trim(strip_tags($d['includes'])) !== ''): ?>
                        <section class="ix-aside-card">
                            <h2 class="ix-h4">Included</h2>
                            <div class="ix-prose ix-prose--sm"><?= $d['includes'] ?></div>
                        </section>
                    <?php endif; ?>
                    <?php if (trim(strip_tags($d['excludes'])) !== ''): ?>
                        <section class="ix-aside-card">
                            <h2 class="ix-h4">Not included</h2>
                            <div class="ix-prose ix-prose--sm"><?= $d['excludes'] ?></div>
                        </section>
                    <?php endif; ?>
                </div>
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
                <h2 class="ix-h4">The details</h2>
                <dl class="ix-facts">
                    <?php if ($d['duration'] !== ''): ?><div><dt>Duration</dt><dd><?= e($d['duration']) ?></dd></div><?php endif; ?>
                    <?php if ($d['start_time'] !== ''): ?><div><dt>Start</dt><dd><?= e($d['start_time']) ?></dd></div><?php endif; ?>
                    <?php if ($d['group'] > 0): ?><div><dt>Group</dt><dd>Up to <?= (int) $d['group'] ?></dd></div><?php endif; ?>
                    <?php if ($d['vehicle'] !== ''): ?><div><dt>Vehicle</dt><dd><?= e($d['vehicle']) ?></dd></div><?php endif; ?>
                    <?php if ($d['price'] !== ''): ?><div><dt>From</dt><dd><?= e($d['price']) ?><?= $d['price_unit'] !== '' ? ' <span>' . e($d['price_unit']) . '</span>' : '' ?></dd></div><?php endif; ?>
                </dl>
                <a class="ix-btn" style="width:100%;margin-top:1rem" href="<?= e($d['cta_url']) ?>">Check availability</a>
            </div>

            <?php if ($d['meeting_point'] !== ''): ?>
                <div class="ix-aside-card">
                    <h2 class="ix-h4">Meeting point</h2>
                    <p class="ix-prose ix-prose--sm" style="margin-top:.5rem"><?= e($d['meeting_point']) ?></p>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</section>

<?php if ($d['related'] !== []): ?>
<section class="ix-section ix-section--surface">
    <div class="ix-wrap">
        <div class="ix-section__head">
            <h2 class="ix-h2">Other day tours</h2>
            <a class="ix-link" href="<?= e(base_url('day-tours')) ?>">All day tours <span class="ix-link__arrow" aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="ix-grid ix-grid--3 ix-rail-mobile" tabindex="0" role="region" aria-label="Related day tours">
            <?php foreach ($d['related'] as $card): ?>
                <article class="ix-card">
                    <a class="ix-card__link" href="<?= e($card['url']) ?>">
                        <span class="ix-card__media">
                            <img src="<?= e($card['image']) ?>" alt="<?= e($card['alt']) ?>" loading="lazy" decoding="async">
                            <?php if (($card['meta'][0] ?? '') !== ''): ?><span class="ix-card__badge"><?= e((string) $card['meta'][0]) ?></span><?php endif; ?>
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
