<?php
declare(strict_types=1);

/**
 * Tour detail — editorial body with a sticky quote card.
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
            <a href="<?= e(base_url('tours')) ?>">Tours</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= e($d['name']) ?></span>
        </nav>

        <div class="ix-detail-hero__grid">
            <div>
                <p class="ix-eyebrow">Private tour</p>
                <h1 class="ix-h1"><?= e($d['name']) ?></h1>
                <?php if ($d['excerpt'] !== ''): ?><p class="ix-lead"><?= e($d['excerpt']) ?></p><?php endif; ?>

                <div class="ix-detail-hero__facts">
                    <span class="ix-chip">Chauffeur-guided</span>
                    <span class="ix-chip">Flexible dates</span>
                    <?php if ($d['price'] !== ''): ?>
                        <span class="ix-chip ix-chip--coral">From <?= e($d['price']) ?><?= $d['price_unit'] !== '' ? ' · ' . e($d['price_unit']) : '' ?></span>
                    <?php endif; ?>
                </div>

                <div class="ix-detail-hero__actions">
                    <a class="ix-btn" href="<?= e($d['cta_url']) ?>">Request a quote</a>
                    <a class="ix-btn ix-btn--ghost" href="<?= e(base_url('tours')) ?>">All tours</a>
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

            <?php if ($d['programme'] !== []): ?>
                <h2 class="ix-h2" style="margin-top:2.5rem">Programme</h2>
                <div class="ix-faq">
                    <?php foreach ($d['programme'] as $day): ?>
                        <details class="ix-faq__item">
                            <summary><?= e($day['label']) ?><?= $day['title'] !== '' ? ' — ' . e($day['title']) : '' ?></summary>
                            <?php if ($day['body'] !== ''): ?>
                                <div class="ix-prose ix-prose--sm"><p><?= e($day['body']) ?></p></div>
                            <?php endif; ?>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (trim(strip_tags($d['includes'])) !== '' || trim(strip_tags($d['excludes'])) !== ''): ?>
                <div class="ix-grid ix-grid--2" style="margin-top:2.5rem;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.25rem">
                    <?php if (trim(strip_tags($d['includes'])) !== ''): ?>
                        <section class="ix-aside-card">
                            <h2 class="ix-h4">What is included</h2>
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
                <h2 class="ix-h4">Request a quote</h2>
                <dl class="ix-facts">
                    <?php if ($d['price'] !== ''): ?><div><dt>From</dt><dd><?= e($d['price']) ?><?= $d['price_unit'] !== '' ? ' <span>' . e($d['price_unit']) . '</span>' : '' ?></dd></div><?php endif; ?>
                    <div><dt>Guide</dt><dd>Private chauffeur</dd></div>
                    <div><dt>Dates</dt><dd>Your choice</dd></div>
                    <div><dt>Route</dt><dd>Adjustable</dd></div>
                </dl>
                <a class="ix-btn" style="width:100%;margin-top:1rem" href="<?= e($d['cta_url']) ?>">Get a price</a>
                <?php if (trim(strip_tags($d['pricing_notes'])) !== ''): ?>
                    <div class="ix-prose ix-prose--sm" style="margin-top:.9rem"><?= $d['pricing_notes'] ?></div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>

<?php if ($d['related'] !== []): ?>
<section class="ix-section ix-section--surface">
    <div class="ix-wrap">
        <div class="ix-section__head">
            <h2 class="ix-h2">Other tours</h2>
            <a class="ix-link" href="<?= e(base_url('tours')) ?>">All tours <span class="ix-link__arrow" aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="ix-grid ix-grid--3 ix-rail-mobile" tabindex="0" role="region" aria-label="Related tours">
            <?php foreach ($d['related'] as $card): ?>
                <article class="ix-card">
                    <a class="ix-card__link" href="<?= e($card['url']) ?>">
                        <span class="ix-card__media">
                            <img src="<?= e($card['image']) ?>" alt="<?= e($card['alt']) ?>" loading="lazy" decoding="async">
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
