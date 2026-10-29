<?php
declare(strict_types=1);

/**
 * Expected:
 * $breadcrumbs = [
 *   ['name' => 'Home', 'url' => base_url()],
 *   ['name' => 'Destinations', 'url' => base_url('destinations')],
 *   ['name' => 'Sigiriya']
 * ];
 */

if (!isset($breadcrumbs) || !is_array($breadcrumbs) || !$breadcrumbs) {
    return;
}

$breadcrumbCount = count($breadcrumbs);
$truncateThreshold = 3;
$shouldTruncate = $breadcrumbCount > $truncateThreshold;
?>
<nav aria-label="Breadcrumb" class="mb-3" x-data="breadcrumbNav()">
    <ol class="breadcrumb small mb-0 breadcrumb-truncate-wrap">
        <?php if ($shouldTruncate): ?>
            <li class="breadcrumb-item">
                <?php if (!empty($breadcrumbs[0]['url'])): ?>
                    <a href="<?= e($breadcrumbs[0]['url']) ?>"><?= e($breadcrumbs[0]['name'] ?? 'Home') ?></a>
                <?php else: ?>
                    <?= e($breadcrumbs[0]['name'] ?? 'Home') ?>
                <?php endif; ?>
            </li>
            <li class="breadcrumb-item breadcrumb-ellipsis-cell" x-show="!expanded">
                <span class="breadcrumb-ellipsis"
                      @click="expanded = true"
                      aria-label="Show full breadcrumb path"
                      role="button"
                      tabindex="0"
                      @keydown.enter="expanded = true"
                      @keydown.space.prevent="expanded = true">…</span>
            </li>
            <li class="breadcrumb-item breadcrumb-expanded-cell" x-show="expanded"
                x-transition:enter="bc-enter"
                x-transition:enter-start="bc-enter-start"
                x-transition:enter-end="bc-enter-end"
                x-transition:leave="bc-leave"
                x-transition:leave-start="bc-leave-start"
                x-transition:leave-end="bc-leave-end">
                <span class="breadcrumb-ellipsis breadcrumb-ellipsis--collapse"
                      @click="expanded = false"
                      aria-label="Collapse breadcrumb"
                      role="button"
                      tabindex="0"
                      @keydown.enter="expanded = false"
                      @keydown.space.prevent="expanded = false">…</span>
            </li>
            <?php for ($i = 1; $i < $breadcrumbCount - 1; $i++): ?>
                <?php $crumb = $breadcrumbs[$i]; ?>
                <li class="breadcrumb-item breadcrumb-expanded-cell" x-show="expanded"
                    x-transition:enter="bc-enter"
                    x-transition:enter-start="bc-enter-start"
                    x-transition:enter-end="bc-enter-end"
                    x-transition:leave="bc-leave"
                    x-transition:leave-start="bc-leave-start"
                    x-transition:leave-end="bc-leave-end">
                    <?php if (!empty($crumb['url'])): ?>
                        <a href="<?= e($crumb['url']) ?>"><?= e($crumb['name'] ?? '') ?></a>
                    <?php else: ?>
                        <?= e($crumb['name'] ?? '') ?>
                    <?php endif; ?>
                </li>
            <?php endfor; ?>
            <li class="breadcrumb-item active" aria-current="page">
                <?= e($breadcrumbs[$breadcrumbCount - 1]['name'] ?? '') ?>
            </li>
        <?php else: ?>
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php $isLast = ($index === array_key_last($breadcrumbs)); ?>
                <li class="breadcrumb-item<?= $isLast ? ' active' : '' ?>"<?= $isLast ? ' aria-current="page"' : '' ?>>
                    <?php if (!$isLast && !empty($crumb['url'])): ?>
                        <a href="<?= e($crumb['url']) ?>"><?= e($crumb['name'] ?? '') ?></a>
                    <?php else: ?>
                        <?= e($crumb['name'] ?? '') ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ol>
</nav>
