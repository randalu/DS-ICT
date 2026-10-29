<?php
declare(strict_types=1);

/**
 * Blog index — editorial grid with pagination.
 *
 * @var \ICT\View\Page $page
 */

use ICT\Support\Pagination;

$d = $page->data;
$window = $d['window'];
?>
<section class="ix-collection-head">
    <div class="ix-wrap">
        <nav class="ix-crumbs" aria-label="Breadcrumb">
            <a href="<?= e(base_url()) ?>">Home</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Blog</span>
        </nav>
        <h1 class="ix-h1">Sri Lanka travel journal</h1>
        <p>Practical writing on routes, timing and the places worth the detour.</p>
    </div>
</section>

<section class="ix-collection-body">
    <div class="ix-wrap">
        <?php if ($d['items'] === []): ?>
            <div class="ix-empty">
                <p>No articles published yet.</p>
            </div>
        <?php else: ?>
            <div class="ix-grid ix-grid--3 ix-rail-mobile" tabindex="0" role="region" aria-label="Articles">
                <?php foreach ($d['items'] as $index => $card): ?>
                    <article class="ix-card" data-ix-reveal data-delay="<?= min(240, 40 * $index) ?>">
                        <a class="ix-card__link" href="<?= e($card['url']) ?>">
                            <span class="ix-card__media">
                                <img src="<?= e($card['image']) ?>" alt="<?= e($card['alt']) ?>" loading="<?= $index < 3 ? 'eager' : 'lazy' ?>" decoding="async">
                            </span>
                            <span class="ix-card__body">
                                <?php if (($card['meta'][0] ?? '') !== ''): ?>
                                    <span class="ix-card__excerpt"><?= e((string) $card['meta'][0]) ?></span>
                                <?php endif; ?>
                                <span class="ix-card__title"><?= e($card['title']) ?></span>
                            </span>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($window['pages'] > 1): ?>
                <?php
                $currentPage = (int) $window['page'];
                $totalPages = (int) $window['pages'];
                ?>
                <nav class="ix-pagination" aria-label="Pagination">
                    <?php if ($window['has_prev']): ?>
                        <a class="ix-pagination__step" rel="prev" href="<?= e($window['prev_url']) ?>">Previous</a>
                    <?php else: ?>
                        <span class="ix-pagination__step is-disabled" aria-hidden="true">Previous</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === $currentPage): ?>
                            <span class="ix-pagination__page is-current" aria-current="page"><?= $i ?></span>
                        <?php elseif ($i === 1 || $i === $totalPages || abs($i - $currentPage) <= 1): ?>
                            <a class="ix-pagination__page" href="<?= e(Pagination::pageUrl($d['base_url'], $i)) ?>"><?= $i ?></a>
                        <?php elseif (abs($i - $currentPage) === 2): ?>
                            <span class="ix-pagination__gap" aria-hidden="true">…</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($window['has_next']): ?>
                        <a class="ix-pagination__step" rel="next" href="<?= e($window['next_url']) ?>">Next</a>
                    <?php else: ?>
                        <span class="ix-pagination__step is-disabled" aria-hidden="true">Next</span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
