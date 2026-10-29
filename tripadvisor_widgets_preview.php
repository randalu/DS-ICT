<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/review_widget_snippets.php';

$tripadvisorWidgets = tripadvisor_widget_preview_snippets();

$seoTitle = 'TripAdvisor Widgets Preview | ' . APP_NAME;
$seoDescription = 'Temporary TripAdvisor widget preview page for visual review.';
$seoCanonical = base_url('tripadvisor_widgets_preview.php');
$seoRobots = 'noindex,nofollow,noarchive';
$seoImage = DEFAULT_OG_IMAGE;
$seoType = 'website';
$bodyClass = 'page-tripadvisor-widget-preview';
$disableTripadvisorFooterWidget = true;

require __DIR__ . '/includes/header.php';
?>

<section class="py-5 border-bottom" style="background: var(--surface);">
    <div class="container">
        <span class="section-kicker">Temporary Preview</span>
        <h1 class="section-heading mb-3">TripAdvisor Widgets Preview</h1>
        <p class="text-secondary mb-0">
            This temporary public page is only for comparing the supplied TripAdvisor widgets. It is not linked from navigation and is marked noindex.
        </p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($tripadvisorWidgets as $widget): ?>
                <div class="col-xl-6">
                    <div class="detail-block-soft h-100">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h2 class="h5 mb-1"><?= e((string) $widget['label']) ?></h2>
                                <p class="text-secondary small mb-0"><?= e((string) $widget['recommendation']) ?></p>
                            </div>
                            <span class="badge-soft">Preview</span>
                        </div>
                        <div class="border rounded-4 p-3 bg-white" style="min-height:120px; overflow:auto;">
                            <?= (string) $widget['html'] ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
