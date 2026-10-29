<?php
declare(strict_types=1);

/**
 * Daylight (v3.1) home — app-shell layout, deliberately low on copy.
 *
 * @var \ICT\View\Page $page
 */

$d = $page->data;
$hero = $d['hero'];
?>
<section class="ix-hero">
    <div class="ix-wrap">
        <div class="ix-hero__inner">
            <div>
                <p class="ix-eyebrow"><?= e($hero['eyebrow']) ?></p>
                <h1 class="ix-hero__title"><?= e($hero['title']) ?><?php if ($hero['title_em'] !== ''): ?> <em><?= e($hero['title_em']) ?></em><?php endif; ?></h1>
                <p class="ix-hero__sub"><?= e($hero['sub']) ?></p>
                <div class="ix-hero__actions">
                    <a class="ix-btn" href="<?= e($hero['primary_url']) ?>"><?= e($hero['primary_label']) ?></a>
                    <a class="ix-btn ix-btn--ghost" href="<?= e($hero['secondary_url']) ?>"><?= e($hero['secondary_label']) ?></a>
                </div>
            </div>

            <div class="ix-hero__media">
                <?php if ($hero['video'] !== ''): ?>
                    <video autoplay muted loop playsinline preload="metadata" poster="<?= e($hero['image']) ?>" aria-hidden="true" tabindex="-1">
                        <source src="<?= e($hero['video']) ?>" type="video/mp4">
                    </video>
                <?php else: ?>
                    <img src="<?= e($hero['image']) ?>" alt="" fetchpriority="high" decoding="async" width="1200" height="900">
                <?php endif; ?>
                <span class="ix-hero__badge"><strong data-ix-count="<?= (int) $d['stats'][0]['count'] ?>"><?= (int) $d['stats'][0]['count'] ?></strong> destinations mapped</span>
            </div>
        </div>

        <div class="ix-stats">
            <?php foreach ($d['stats'] as $stat): ?>
                <div class="ix-stats__item">
                    <span class="ix-stats__value" data-ix-count="<?= (int) $stat['count'] ?>"><?= (int) $stat['count'] ?></span>
                    <span class="ix-stats__label"><?= e($stat['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($d['journeys'] !== []): ?>
<section class="ix-section ix-section--surface">
    <div class="ix-wrap">
        <div class="ix-section__head">
            <h2 class="ix-h2">Signature journeys</h2>
            <a class="ix-link" href="<?= e(base_url('itineraries')) ?>">All itineraries <span class="ix-link__arrow" aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="ix-grid ix-grid--3 ix-rail-mobile" tabindex="0" role="region" aria-label="Signature journeys">
            <?php foreach ($d['journeys'] as $index => $journey): ?>
                <article class="ix-card" data-ix-reveal data-delay="<?= 60 * $index ?>">
                    <a class="ix-card__link" href="<?= e($journey['url']) ?>">
                        <span class="ix-card__media">
                            <img src="<?= e($journey['image']) ?>" alt="<?= e($journey['alt']) ?>" loading="lazy" decoding="async">
                            <?php if ($journey['days'] > 0): ?><span class="ix-card__badge"><?= (int) $journey['days'] ?> days</span><?php endif; ?>
                        </span>
                        <span class="ix-card__body">
                            <span class="ix-card__title"><?= e($journey['title']) ?></span>
                            <span class="ix-card__facts">
                                <span class="ix-chip"><?= e($journey['kicker']) ?></span>
                                <?php if ($journey['price'] !== ''): ?><span class="ix-card__price">From <?= e($journey['price']) ?></span><?php endif; ?>
                            </span>
                        </span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($d['matcher'] !== []): ?>
<section class="ix-section">
    <div class="ix-wrap">
        <div class="ix-section__head">
            <div>
                <h2 class="ix-h2">How much island fits your dates?</h2>
            </div>
        </div>

        <div data-island="journey-matcher"
             data-props="<?= e(json_encode(['items' => $d['matcher']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>">
            <div class="ix-matcher">
                <div class="ix-matcher__grid ix-rail-mobile" tabindex="0" role="region" aria-label="Journeys by length">
                    <?php foreach ($d['matcherFallback'] as $item): ?>
                        <article class="ix-card">
                            <a class="ix-card__link" href="<?= e($item['url']) ?>">
                                <span class="ix-card__media">
                                    <img src="<?= e($item['image']) ?>" alt="<?= e($item['alt']) ?>" loading="lazy" decoding="async">
                                    <span class="ix-card__badge"><?= (int) $item['days'] ?> days</span>
                                </span>
                                <span class="ix-card__body">
                                    <span class="ix-card__title"><?= e($item['title']) ?></span>
                                    <span class="ix-card__facts">
                                        <?php if ($item['price'] !== ''): ?><span class="ix-card__price">From <?= e($item['price']) ?></span><?php endif; ?>
                                    </span>
                                </span>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="ix-meta" style="margin-top:.9rem">
                    <a class="ix-link" href="<?= e(base_url('itineraries')) ?>">See all <?= count($d['matcher']) ?> itineraries <span class="ix-link__arrow" aria-hidden="true">&rarr;</span></a>
                </p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($d['destinations'] !== []): ?>
<section class="ix-section ix-section--surface">
    <div class="ix-wrap">
        <div class="ix-section__head">
            <h2 class="ix-h2">Where you could go</h2>
            <a class="ix-link" href="<?= e(base_url('destinations')) ?>">All destinations <span class="ix-link__arrow" aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="ix-strip ix-rail-mobile" tabindex="0" role="region" aria-label="Destinations">
            <?php foreach ($d['destinations'] as $destination): ?>
                <a class="ix-strip__item" href="<?= e($destination['url']) ?>">
                    <img src="<?= e($destination['image']) ?>" alt="<?= e($destination['alt']) ?>" loading="lazy" decoding="async">
                    <span><?= e($destination['title']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($d['experiences'] !== []): ?>
<section class="ix-section">
    <div class="ix-wrap">
        <div class="ix-section__head">
            <h2 class="ix-h2">What you can do</h2>
            <a class="ix-link" href="<?= e(base_url('experiences')) ?>">All experiences <span class="ix-link__arrow" aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="ix-exp-list">
            <?php foreach ($d['experiences'] as $experience): ?>
                <a class="ix-exp-item" href="<?= e($experience['url']) ?>">
                    <span class="ix-exp-item__thumb"><img src="<?= e($experience['image']) ?>" alt="" loading="lazy" decoding="async"></span>
                    <span>
                        <span class="ix-exp-item__title"><?= e($experience['title']) ?></span>
                        <span class="ix-exp-item__meta"><?= e($experience['kicker']) ?></span>
                    </span>
                    <span class="ix-exp-item__arrow" aria-hidden="true">&rarr;</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="ix-section ix-section--surface">
    <div class="ix-wrap">
        <div class="ix-section__head">
            <h2 class="ix-h2">How it works</h2>
        </div>

        <div class="ix-rows">
            <?php foreach ($d['steps'] as $step): ?>
                <div class="ix-row">
                    <span class="ix-row__num"><?= e($step['num']) ?></span>
                    <div>
                        <h3><?= e($step['title']) ?></h3>
                        <p><?= e($step['text']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($d['articles'] !== []): ?>
<section class="ix-section">
    <div class="ix-wrap">
        <div class="ix-section__head">
            <h2 class="ix-h2">Journal</h2>
            <a class="ix-link" href="<?= e(base_url('blog')) ?>">All articles <span class="ix-link__arrow" aria-hidden="true">&rarr;</span></a>
        </div>

        <div class="ix-journal-list">
            <?php foreach ($d['articles'] as $article): ?>
                <a class="ix-journal-row" href="<?= e($article['url']) ?>">
                    <span class="ix-journal-row__thumb">
                        <img src="<?= e($article['image']) ?>" alt="" loading="lazy" decoding="async">
                    </span>
                    <h3><?= e($article['title']) ?></h3>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="ix-cta">
    <div class="ix-wrap ix-cta__inner">
        <div>
            <h2 class="ix-h2"><?= e($d['cta']['title']) ?></h2>
            <p><?= e($d['cta']['body']) ?></p>
        </div>
        <div class="ix-cta__actions">
            <a class="ix-btn ix-btn--on-night" href="<?= e($d['cta']['url']) ?>"><?= e($d['cta']['label']) ?></a>
            <?php if ($d['whatsapp'] !== ''): ?>
                <a class="ix-btn ix-btn--night" href="<?= e($d['whatsapp']) ?>" target="_blank" rel="noopener">WhatsApp</a>
            <?php endif; ?>
        </div>
    </div>
</section>
