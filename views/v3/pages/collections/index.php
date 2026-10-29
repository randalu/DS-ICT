<?php
declare(strict_types=1);

/**
 * Daylight (v3.1) collection index — shared by all six discovery routes.
 *
 * Server-rendered GET form and anchor pagination, so filters, canonicals and
 * keyboard use are identical to before; only the shell and card style change.
 *
 * @var \ICT\View\Page $page
 */

use ICT\Support\Pagination;

$data = $page->data;
$family = (string) $data['family'];
$facets = $data['facets'] ?? [];
$query = $data['query'] ?? [];
$base = (string) $data['base_url'];
$window = $data['window'];

$controls = [
    ['name' => 'q', 'label' => 'Search', 'options' => []],
];

if ($family === 'destinations') {
    $controls[] = ['name' => 'region', 'label' => 'Region', 'options' => $facets['region'] ?? []];
}
if ($family === 'experiences') {
    $controls[] = ['name' => 'category', 'label' => 'Category', 'options' => $facets['category'] ?? []];
}
if ($family === 'itineraries') {
    $controls[] = ['name' => 'days', 'label' => 'Length', 'options' => [
        ['value' => '1-7', 'label' => 'Up to 7 days'],
        ['value' => '8-12', 'label' => '8–12 days'],
        ['value' => '13+', 'label' => '13+ days'],
    ]];
}
if ($family === 'day-tours') {
    $controls[] = ['name' => 'difficulty', 'label' => 'Difficulty', 'options' => $facets['difficulty'] ?? []];
    $controls[] = ['name' => 'price', 'label' => 'Price', 'options' => [
        ['value' => 'under-100', 'label' => 'Under $100'],
        ['value' => '100-200', 'label' => '$100–$200'],
        ['value' => '200+', 'label' => '$200+'],
    ]];
}

$hasActive = $query !== [];
?>
<section class="ix-collection-head">
    <div class="ix-wrap">
        <nav class="ix-crumbs" aria-label="Breadcrumb">
            <a href="<?= e(base_url()) ?>">Home</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= e($data['heading']) ?></span>
        </nav>
        <div class="ix-collection-head__inner">
            <div>
                <h1 class="ix-h1"><?= e($data['heading']) ?></h1>
                <p><?= e($data['intro']) ?></p>
            </div>
        </div>
    </div>
</section>

<div class="ix-subnav">
    <div class="ix-wrap ix-subnav__inner">
        <form class="ix-filterbar" method="get" action="<?= e($base) ?>" data-ic-filters>
            <?php foreach ($controls as $control): ?>
                <?php if ($control['options'] === [] && $control['name'] === 'q'): ?>
                    <label class="ix-field">
                        <span class="ix-field__label"><?= e($control['label']) ?></span>
                        <input class="ix-input" type="search" name="q" value="<?= e((string) ($data['filters']['q'] ?? '')) ?>" placeholder="Name or keyword">
                    </label>
                <?php else: ?>
                    <label class="ix-field">
                        <span class="ix-field__label"><?= e($control['label']) ?></span>
                        <select class="ix-select" name="<?= e($control['name']) ?>">
                            <option value="">All</option>
                            <?php foreach ($control['options'] as $option): ?>
                                <option value="<?= e($option['value']) ?>"<?= ((string) ($data['filters'][$control['name']] ?? '') === $option['value']) ? ' selected' : '' ?>>
                                    <?= e($option['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>

            <button class="ix-btn ix-btn--sm" type="submit" style="margin-bottom:.1rem">Apply</button>
            <span class="ix-filterbar__count" aria-live="polite"><?= (int) $data['total'] ?> results</span>
            <?php if ($hasActive): ?>
                <a class="ix-filterbar__clear" href="<?= e($base) ?>">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<section class="ix-collection-body">
    <div class="ix-wrap">
        <?php if ($data['items'] === []): ?>
            <div class="ix-empty">
                <p><?= e($data['empty']) ?></p>
                <a class="ix-btn ix-btn--sm" href="<?= e($base) ?>">Show everything</a>
            </div>
        <?php else: ?>
            <div class="ix-grid ix-grid--3">
                <?php foreach ($data['items'] as $index => $item): ?>
                    <article class="ix-card" data-ix-reveal data-delay="<?= min(240, 40 * $index) ?>">
                        <a class="ix-card__link" href="<?= e($item['url']) ?>">
                            <span class="ix-card__media">
                                <img src="<?= e($item['image']) ?>" alt="<?= e($item['alt']) ?>" loading="<?= $index < 3 ? 'eager' : 'lazy' ?>" decoding="async">
                                <?php if (($item['days'] ?? 0) > 0): ?>
                                    <span class="ix-card__badge"><?= (int) $item['days'] ?> days</span>
                                <?php elseif (($item['meta'][0] ?? '') !== ''): ?>
                                    <span class="ix-card__badge"><?= e((string) $item['meta'][0]) ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="ix-card__body">
                                <span class="ix-card__title"><?= e($item['title']) ?></span>
                                <?php if (($item['kicker'] ?? '') !== ''): ?>
                                    <span class="ix-card__excerpt"><?= e($item['kicker']) ?></span>
                                <?php endif; ?>
                                <span class="ix-card__facts">
                                    <?php if (($item['price'] ?? '') !== ''): ?>
                                        <span class="ix-card__price">From <?= e($item['price']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($window['pages'] > 1): ?>
                <?php
                $currentPage = (int) $window['page'];
                $totalPages = (int) $window['pages'];
                $numbers = [];
                for ($i = 1; $i <= $totalPages; $i++) {
                    if ($i === 1 || $i === $totalPages || abs($i - $currentPage) <= 1) {
                        $numbers[] = $i;
                    }
                }
                ?>
                <nav class="ix-pagination" aria-label="Pagination">
                    <?php if ($window['has_prev']): ?>
                        <a class="ix-pagination__step" rel="prev" href="<?= e($window['prev_url']) ?>">Previous</a>
                    <?php else: ?>
                        <span class="ix-pagination__step is-disabled" aria-hidden="true">Previous</span>
                    <?php endif; ?>

                    <?php $lastNumber = 0; ?>
                    <?php foreach ($numbers as $number): ?>
                        <?php if ($lastNumber !== 0 && $number - $lastNumber > 1): ?>
                            <span class="ix-pagination__gap" aria-hidden="true">…</span>
                        <?php endif; ?>
                        <?php if ($number === $currentPage): ?>
                            <span class="ix-pagination__page is-current" aria-current="page"><?= $number ?></span>
                        <?php else: ?>
                            <a class="ix-pagination__page" href="<?= e(Pagination::pageUrl($base, $number, $query)) ?>"><?= $number ?></a>
                        <?php endif; ?>
                        <?php $lastNumber = $number; ?>
                    <?php endforeach; ?>

                    <?php if ($window['has_next']): ?>
                        <a class="ix-pagination__step" rel="next" href="<?= e($window['next_url']) ?>">Next</a>
                    <?php else: ?>
                        <span class="ix-pagination__step is-disabled" aria-hidden="true">Next</span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($data['themes'])): ?>
            <section style="margin-top:2.5rem">
                <h2 class="ix-h3">Browse by theme</h2>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.75rem">
                    <?php foreach ($data['themes'] as $theme): ?>
                        <a class="related-link-card ix-chip" href="<?= e($theme['url']) ?>" style="min-height:36px;padding:0 .8rem"><?= e($theme['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>
