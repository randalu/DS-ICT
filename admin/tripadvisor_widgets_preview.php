<?php
declare(strict_types=1);

$adminTitle = 'TripAdvisor Widgets Preview';
$adminPage = 'tripadvisor_widgets';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
require_once __DIR__ . '/../includes/review_widget_snippets.php';

$tripadvisorWidgets = tripadvisor_widget_preview_snippets();
$preservedWidgets = review_widget_preserved_snippets();

require __DIR__ . '/_top.php';
?>

<div class="card card-soft mb-4">
    <div class="card-body">
        <h2 class="h5 mb-2">TripAdvisor Widget Preview</h2>
        <p class="text-secondary mb-0">
            Preserved widget source for review. Live external previews are disabled on private admin pages;
            provider/account acceptance requires a separate isolated review.
        </p>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($tripadvisorWidgets as $widget): ?>
        <div class="col-xl-6">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h2 class="h5 mb-1"><?= e((string) $widget['label']) ?></h2>
                            <p class="text-secondary small mb-0"><?= e((string) $widget['recommendation']) ?></p>
                        </div>
                        <span class="badge text-bg-secondary">Source</span>
                    </div>
                    <div class="border rounded-3 p-3 bg-white" style="min-height:120px; overflow:auto;">
                        <pre class="small mb-0" style="white-space:pre-wrap;"><?= e((string) $widget['html']) ?></pre>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card card-soft mt-4">
    <div class="card-body">
        <h2 class="h5 mb-2">Preserved Snippet Sources</h2>
        <p class="text-secondary small mb-3">Source inspection only. Public review widgets follow visitor choices and operator controls; dormant providers stay disabled.</p>
        <div class="row g-3">
            <?php foreach ($preservedWidgets as $group => $widgets): ?>
                <?php foreach ($widgets as $widget): ?>
                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3 bg-light h-100">
                            <h3 class="h6 mb-1"><?= e((string) $widget['label']) ?></h3>
                            <p class="small text-secondary mb-2"><?= e((string) $widget['notes']) ?></p>
                            <pre class="small mb-0" style="white-space:pre-wrap;"><?= e((string) $widget['html']) ?></pre>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
