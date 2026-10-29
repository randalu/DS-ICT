<?php
declare(strict_types=1);

/**
 * Filter bar for the discovery indexes.
 *
 * A plain GET form: it works with JavaScript disabled, submits to the index
 * itself, and keeps the canonical/noindex behaviour in the controller (filters
 * live in the query string, never in the path).
 *
 * @var array<string,string> $filters
 * @var array<string,list<array{value:string,label:string}>> $facets
 * @var string $base_url
 * @var array<string,string> $query
 * @var list<array{name:string,label:string,type:string,options:list<array{value:string,label:string}>}> $controls
 * @var int $total
 */

$hasActive = array_filter($query, static fn($value): bool => trim((string) $value) !== '') !== [];
?>
<form class="ic-filters" method="get" action="<?= e($base_url) ?>" data-ic-filters>
    <div class="ic-filters__row">
        <?php foreach ($controls as $control): ?>
            <?php if ($control['type'] === 'search'): ?>
                <label class="ic-field ic-field--search">
                    <span class="ic-field__label"><?= e($control['label']) ?></span>
                    <input class="ic-field__control"
                           type="search"
                           name="<?= e($control['name']) ?>"
                           value="<?= e((string) ($filters[$control['name']] ?? '')) ?>"
                           placeholder="Search by name">
                </label>
            <?php else: ?>
                <label class="ic-field">
                    <span class="ic-field__label"><?= e($control['label']) ?></span>
                    <select class="ic-field__control" name="<?= e($control['name']) ?>">
                        <option value="">All</option>
                        <?php foreach ($control['options'] as $option): ?>
                            <option value="<?= e($option['value']) ?>"
                                <?= ((string) ($filters[$control['name']] ?? '') === $option['value']) ? 'selected' : '' ?>>
                                <?= e($option['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="ic-filters__actions">
            <button class="ic-btn ic-btn--lagoon ic-btn--sm" type="submit">Apply</button>
            <?php if ($hasActive): ?>
                <a class="ic-filters__clear" href="<?= e($base_url) ?>">Clear filters</a>
            <?php endif; ?>
        </div>
    </div>

    <p class="ic-filters__count" aria-live="polite">
        <?= $total === 1 ? '1 result' : (int) $total . ' results' ?><?php if ($hasActive): ?> for this filter<?php endif; ?>
    </p>
</form>
