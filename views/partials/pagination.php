<?php
declare(strict_types=1);

/**
 * Pagination control for listing pages.
 *
 * Links keep the active filters (see Pagination::pageUrl) and are plain anchors,
 * so keyboard and no-JavaScript operation are identical.
 *
 * Local names are deliberately not $page/$pages: this partial is included from
 * the layout scope, where $page is the Page model.
 *
 * @var array{page:int,pages:int,total:int,per_page:int,has_prev:bool,has_next:bool,prev_url:string,next_url:string} $window
 * @var string $base_url
 * @var array<string,string> $query
 */

use ICT\Support\Pagination;

if ($window['pages'] <= 1) {
    return;
}

$currentPage = $window['page'];
$totalPages = $window['pages'];

// Show first, last, current +/- 2, with ellipses between the runs.
$numbers = [];
for ($i = 1; $i <= $totalPages; $i++) {
    if ($i === 1 || $i === $totalPages || abs($i - $currentPage) <= 2) {
        $numbers[] = $i;
    }
}
?>
<nav class="ic-pagination" aria-label="Pagination">
    <?php if ($window['has_prev']): ?>
        <a class="ic-pagination__step" rel="prev" href="<?= e($window['prev_url']) ?>">&larr; Previous</a>
    <?php else: ?>
        <span class="ic-pagination__step is-disabled" aria-hidden="true">&larr; Previous</span>
    <?php endif; ?>

    <ol class="ic-pagination__pages">
        <?php $last = 0; ?>
        <?php foreach ($numbers as $number): ?>
            <?php if ($last !== 0 && $number - $last > 1): ?>
                <li class="ic-pagination__gap" aria-hidden="true">…</li>
            <?php endif; ?>
            <li>
                <?php if ($number === $currentPage): ?>
                    <span class="ic-pagination__page is-current" aria-current="page"><?= $number ?></span>
                <?php else: ?>
                    <a class="ic-pagination__page" href="<?= e(Pagination::pageUrl($base_url, $number, $query)) ?>"><?= $number ?></a>
                <?php endif; ?>
            </li>
            <?php $last = $number; ?>
        <?php endforeach; ?>
    </ol>

    <?php if ($window['has_next']): ?>
        <a class="ic-pagination__step" rel="next" href="<?= e($window['next_url']) ?>">Next &rarr;</a>
    <?php else: ?>
        <span class="ic-pagination__step is-disabled" aria-hidden="true">Next &rarr;</span>
    <?php endif; ?>
</nav>
