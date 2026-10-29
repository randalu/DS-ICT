<?php
declare(strict_types=1);

/**
 * Shared index template for the six discovery families.
 *
 * The controller supplies copy, cards, facets and the pagination window; this
 * file only arranges them. Adding a family means adding a registry entry, not
 * another near-identical template.
 *
 * @var \ICT\View\Page $page
 */

$data = $page->data;
$family = (string) $data['family'];
$facets = $data['facets'];
$total = (int) $data['total'];

$controls = [
    ['name' => 'q', 'label' => 'Search', 'type' => 'search', 'options' => []],
];

if ($family === 'destinations') {
    $controls[] = ['name' => 'region', 'label' => 'Region', 'type' => 'select', 'options' => $facets['region'] ?? []];
}

if ($family === 'experiences') {
    $controls[] = ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => $facets['category'] ?? []];
}

if ($family === 'itineraries') {
    $controls[] = ['name' => 'days', 'label' => 'Trip length', 'type' => 'select', 'options' => [
        ['value' => '1-7', 'label' => 'Up to 7 days'],
        ['value' => '8-12', 'label' => '8–12 days'],
        ['value' => '13+', 'label' => '13 days or more'],
    ]];
}

if ($family === 'day-tours') {
    $controls[] = ['name' => 'difficulty', 'label' => 'Difficulty', 'type' => 'select', 'options' => $facets['difficulty'] ?? []];
    $controls[] = ['name' => 'price', 'label' => 'Price', 'type' => 'select', 'options' => [
        ['value' => 'under-100', 'label' => 'Under $100'],
        ['value' => '100-200', 'label' => '$100 – $200'],
        ['value' => '200+', 'label' => '$200 and above'],
    ]];
}
?>
<section class="ic-collection-hero">
    <div class="ic-wrap">
        <nav class="ic-crumbs" aria-label="Breadcrumb">
            <a href="<?= e(base_url()) ?>">Home</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page"><?= e($data['heading']) ?></span>
        </nav>

        <p class="ic-eyebrow"><?= e($data['eyebrow']) ?></p>
        <h1 class="ic-collection-hero__title"><?= e($data['heading']) ?></h1>
        <p class="ic-collection-hero__intro"><?= e($data['intro']) ?></p>
    </div>
</section>

<section class="ic-section ic-section--tight">
    <div class="ic-wrap">
        <?php
        $filters = $data['filters'];
        $facets = $data['facets'];
        $base_url = $data['base_url'];
        $query = $data['query'];
        $total = (int) $data['total'];
        require APP_ROOT . '/views/partials/filter-bar.php';
        ?>

        <?php if ($data['items'] === []): ?>
            <div class="ic-empty">
                <p class="ic-empty__title">Nothing here yet</p>
                <p><?= e($data['empty']) ?></p>
                <a class="ic-btn ic-btn--outline" href="<?= e($data['base_url']) ?>">Show everything</a>
            </div>
        <?php else: ?>
            <ul class="ic-grid ic-grid--3 ic-collection-grid">
                <?php foreach ($data['items'] as $index => $item): ?>
                    <li>
                        <?php
                        $card = $item;
                        $variant = $family;
                        $eager = $index < 3;
                        require APP_ROOT . '/views/partials/card.php';
                        ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php
            $window = $data['window'];
            require APP_ROOT . '/views/partials/pagination.php';
            ?>
        <?php endif; ?>

        <?php if (!empty($data['themes'])): ?>
            <section class="ic-themes" aria-labelledby="ic-themes-title">
                <h2 class="ic-h3" id="ic-themes-title">Browse by theme</h2>
                <p class="ic-themes__intro">Prefer to start from the kind of trip rather than the length of it.</p>
                <ul class="ic-themes__list">
                    <?php foreach ($data['themes'] as $theme): ?>
                        <li>
                            <a class="related-link-card ic-theme-link" href="<?= e($theme['url']) ?>">
                                <span class="ic-theme-link__label"><?= e($theme['label']) ?></span>
                                <span class="ic-theme-link__meta"><?= (int) $theme['count'] ?> <?= $theme['count'] === 1 ? 'journey' : 'journeys' ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </div>
</section>
