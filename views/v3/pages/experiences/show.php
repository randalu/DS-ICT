<?php
declare(strict_types=1);

/**
 * Experience detail — magazine spread: oversized opener, essay, tips, related.
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
            <a href="<?= e(base_url('experiences')) ?>">Experiences</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= e($d['title']) ?></span>
        </nav>

        <div class="ix-detail-hero__grid">
            <div>
                <p class="ix-eyebrow">Experience</p>
                <h1 class="ix-h1"><?= e($d['title']) ?></h1>
                <?php if ($d['excerpt'] !== ''): ?><p class="ix-lead"><?= e($d['excerpt']) ?></p><?php endif; ?>

                <div class="ix-detail-hero__facts">
                    <?php if ($d['best_time'] !== ''): ?><span class="ix-chip"><?= e($d['best_time']) ?></span><?php endif; ?>
                    <span class="ix-chip">Fits any private route</span>
                </div>

                <div class="ix-detail-hero__actions">
                    <a class="ix-btn" href="<?= e($d['cta_url']) ?>">Add to my trip</a>
                    <a class="ix-btn ix-btn--ghost" href="<?= e($d['itineraries_url']) ?>">Journeys with this</a>
                </div>
            </div>

            <div class="ix-detail-hero__media">
                <img src="<?= e($d['hero']) ?>" alt="<?= e($d['title']) ?>" fetchpriority="high" decoding="async" width="1200" height="900">
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

            <?php if (trim(strip_tags($d['where'])) !== ''): ?>
                <section style="margin-top:2rem">
                    <h2 class="ix-h3">Where to do it</h2>
                    <div class="ix-prose"><?= $d['where'] ?></div>
                </section>
            <?php endif; ?>

            <?php if ($d['tips'] !== []): ?>
                <section style="margin-top:2rem">
                    <h2 class="ix-h3">Good to know</h2>
                    <ul class="ix-tips">
                        <?php foreach ($d['tips'] as $tip): ?>
                            <li><?= e($tip) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </div>

        <aside class="ix-detail-aside">
            <div class="ix-aside-card">
                <h2 class="ix-h4">At a glance</h2>
                <dl class="ix-facts">
                    <?php if ($d['best_time'] !== ''): ?><div><dt>Best time</dt><dd><?= e($d['best_time']) ?></dd></div><?php endif; ?>
                    <div><dt>Style</dt><dd>Private, guided</dd></div>
                    <div><dt>Customisable</dt><dd>Yes</dd></div>
                </dl>
                <a class="ix-btn" style="width:100%;margin-top:1rem" href="<?= e($d['cta_url']) ?>">Plan around this</a>
            </div>
        </aside>
    </div>
</section>

<?php if ($d['related'] !== []): ?>
<section class="ix-section ix-section--surface">
    <div class="ix-wrap">
        <div class="ix-section__head">
            <h2 class="ix-h2">More experiences</h2>
            <a class="ix-link" href="<?= e(base_url('experiences')) ?>">All experiences <span class="ix-link__arrow" aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="ix-grid ix-grid--3 ix-rail-mobile" tabindex="0" role="region" aria-label="Related experiences">
            <?php foreach ($d['related'] as $card): ?>
                <article class="ix-card">
                    <a class="ix-card__link" href="<?= e($card['url']) ?>">
                        <span class="ix-card__media">
                            <img src="<?= e($card['image']) ?>" alt="<?= e($card['alt']) ?>" loading="lazy" decoding="async">
                        </span>
                        <span class="ix-card__body">
                            <span class="ix-card__title"><?= e($card['title']) ?></span>
                        </span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
