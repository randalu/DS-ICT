<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

$rawIds = trim((string) get_input('ids', ''));
$idList = [];
if ($rawIds !== '') {
    foreach (preg_split('/\\s*,\\s*/', $rawIds) as $part) {
        $id = (int) trim((string) $part);
        if ($id > 0) {
            $idList[] = $id;
        }
    }
}
$idList = array_values(array_unique($idList));
$idList = array_slice($idList, 0, 4);

$itineraries = [];
if ($idList) {
    $ph = [];
    $params = [];
    foreach ($idList as $idx => $id) {
        $key = 'id' . $idx;
        $ph[] = ':' . $key;
        $params[$key] = $id;
    }

    $rows = fetch_all(
        "SELECT
            id, name, slug, days, excerpt, highlights, route_summary, best_for,
            hero_image, og_image, meta_description,
            " . pricing_select_fragment('itineraries') . "
         FROM itineraries
         WHERE is_published = 1
           AND id IN (" . implode(',', $ph) . ")",
        $params
    );

    $byId = [];
    foreach ($rows as $row) {
        $byId[(int) $row['id']] = $row;
    }
    foreach ($idList as $id) {
        if (isset($byId[$id])) {
            $itineraries[] = $byId[$id];
        }
    }
}

$seoTitle = 'Compare Sri Lanka Itineraries | ' . APP_NAME;
$seoDescription = 'Compare private Sri Lanka itinerary options side-by-side to choose the right route, pace, and travel plan.';
$seoCanonical = base_url('compare');
$seoImage = DEFAULT_OG_IMAGE;
$seoType = 'website';
$seoRobots = 'noindex,follow,max-image-preview:large';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Compare', 'url' => $seoCanonical],
];

$schemaItems = [];
foreach ($itineraries as $i) {
    $schemaItems[] = [
        'url' => base_url('itineraries/' . (string) ($i['slug'] ?? '')),
        'name' => (string) ($i['name'] ?? ''),
        'image' => package_card_image($i, 'itinerary'),
    ];
}

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
];
if ($schemaItems) {
    $schemaGraph[] = schema_item_list('Compared Itineraries', $schemaItems);
}

$bodyClass = 'page-compare';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media page-hero-compare">
    <?= responsive_image([
        'src' => DEFAULT_OG_IMAGE,
        'alt' => 'Compare Sri Lanka Itineraries',
        'widths' => [800, 1280, 1920],
        'sizes' => '100vw',
        'width' => 1920,
        'height' => 1080,
        'loading' => 'eager',
        'fetchpriority' => 'high',
        'class' => 'page-hero-bg-img',
        'picture_class' => 'page-hero-media',
    ]) ?>
    <div class="container">
        <div class="hero-content">
            <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
            <span class="section-kicker" style="color:var(--accent-light);">Compare</span>
            <h1 class="hero-title">Compare <em>Itineraries</em></h1>
            <div class="hero-summary-box">
                <p class="lead mb-0">Compare up to 4 private Sri Lanka itinerary options side-by-side.</p>
            </div>
            <div class="hero-actions mt-3 d-flex gap-2 flex-wrap">
                <?php if ($idList): ?>
                    <a class="btn btn-primary" href="<?= e(base_url('contact?from_compare=' . implode(',', array_map('intval', $idList)))) ?>">Inquire About These</a>
                <?php endif; ?>
                <button type="button" class="btn btn-ghost" data-copy-url>Share</button>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if (!$idList): ?>
            <div class="alert alert-light border">
                Add itinerary IDs in the URL, e.g. <code><?= e(base_url('compare?ids=12,18,24')) ?></code>
            </div>
        <?php elseif (!$itineraries): ?>
            <div class="alert alert-warning">
                No published itineraries matched your selection.
            </div>
        <?php else: ?>
            <div class="table-responsive" style="border:1px solid var(--line);border-radius:var(--radius-lg);background:var(--surface);box-shadow:var(--shadow-sm);overflow:hidden;">
                <table class="table align-middle mb-0" style="--bs-table-bg:var(--surface);border-color:var(--line);">
                    <thead style="background:var(--surface-alt);">
                        <tr>
                            <th style="min-width:180px;">Field</th>
                            <?php foreach ($itineraries as $itinerary): ?>
                                <th style="min-width:280px;">
                                    <div class="fw-bold"><?= e((string) ($itinerary['name'] ?? '')) ?></div>
                                    <div class="small text-secondary"><?= (int) ($itinerary['days'] ?? 0) ?> days</div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold">Hero</td>
                            <?php foreach ($itineraries as $itinerary): ?>
                                <?php
                                $src = package_card_image($itinerary, 'itinerary');
                                $imgSrc = $src !== '' ? $src : DEFAULT_OG_IMAGE;
                                ?>
                                <td>
                                    <a href="<?= e(base_url('itineraries/' . (string) $itinerary['slug'])) ?>" class="text-decoration-none">
                                        <?= responsive_image([
                                            'src' => $imgSrc,
                                            'alt' => (string) ($itinerary['name'] ?? ''),
                                            'class' => 'img-fluid rounded-3 border',
                                            'sizes' => '(max-width: 992px) 80vw, 320px',
                                            'width' => 1200,
                                            'height' => 800,
                                            'loading' => 'lazy',
                                        ]) ?>
                                    </a>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Price</td>
                            <?php foreach ($itineraries as $itinerary): ?>
                                <?php $price = format_price_range($itinerary, true); ?>
                                <td><?= e($price !== '' ? $price : 'Request quote') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Summary</td>
                            <?php foreach ($itineraries as $itinerary): ?>
                                <?php
                                $summary = (string) ($itinerary['excerpt'] ?: $itinerary['meta_description'] ?: '');
                                $summary = excerpt_words($summary, 24);
                                ?>
                                <td class="text-secondary"><?= e($summary) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Highlights</td>
                            <?php foreach ($itineraries as $itinerary): ?>
                                <?php
                                $raw = (string) ($itinerary['highlights'] ?? '');
                                if ($raw === '') {
                                    $raw = (string) ($itinerary['route_summary'] ?? '');
                                }
                                $plain = trim(preg_replace('/\\s+/', ' ', strip_tags($raw)) ?? '');
                                $plain = str_replace(['•', 'Â·'], "\n", $plain);
                                $parts = array_values(array_filter(array_map('trim', preg_split('/\\r?\\n+/', $plain) ?: []), static fn(string $v): bool => $v !== ''));
                                $parts = array_slice($parts, 0, 3);
                                ?>
                                <td>
                                    <?php if ($parts): ?>
                                        <ul class="mb-0 small text-secondary">
                                            <?php foreach ($parts as $p): ?>
                                                <li><?= e($p) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-secondary small">Highlights will be updated soon.</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Actions</td>
                            <?php foreach ($itineraries as $itinerary): ?>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-outline-dark btn-sm" href="<?= e(base_url('itineraries/' . (string) $itinerary['slug'])) ?>">View</a>
                                        <a class="btn btn-primary btn-sm" href="<?= e(base_url('contact?itinerary_id=' . (int) ($itinerary['id'] ?? 0))) ?>">Inquire</a>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    var btn = document.querySelector('[data-copy-url]');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var url = window.location.href;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function () {
                btn.textContent = 'Copied';
                setTimeout(function () { btn.textContent = 'Share'; }, 1200);
            }).catch(function () {});
        } else {
            prompt('Copy this link:', url);
        }
    });
})();
</script>

<?php
schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
