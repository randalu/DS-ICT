<?php
declare(strict_types=1);

$adminPage = 'blog';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/blog_widgets.php';

admin_require_auth();

$adminTitle = 'Blog Settings';

$categories = fetch_all("SELECT id, name FROM blog_categories ORDER BY sort_order ASC, name ASC");
$allItineraries = fetch_all("SELECT id, name, days FROM itineraries WHERE is_published = 1 ORDER BY is_featured DESC, sort_order ASC, days ASC, name ASC");
$allDestinations = fetch_all("SELECT id, name FROM destinations WHERE is_published = 1 ORDER BY is_featured DESC, sort_order ASC, name ASC");
$allBlogPosts = fetch_all("SELECT id, title FROM blog_posts ORDER BY published_at DESC, id DESC LIMIT 200");

$widgetsConfig = widget_load_globals();

$widgetCatalogue = [
    'related_posts'        => 'Related Posts',
    'related_itineraries'  => 'Related Itineraries',
    'related_destinations' => 'Related Destinations',
    'cta'                  => 'Call To Action',
    'newsletter'           => 'Newsletter Signup',
    'ad'                   => 'Custom HTML / Ad',
];
$positionLabels = [
    'sidebar'        => 'Sidebar (right column)',
    'inline_after_p' => 'Inline (after Nth paragraph)',
    'below_post'     => 'Below post (full width)',
];
$visibilityLabels = [
    'all'         => 'All posts',
    'category_in' => 'Posts in selected categories',
    'post_in'     => 'Selected posts only',
];

$widgetsBootstrap = [
    'config'         => $widgetsConfig,
    'catalogue'      => $widgetCatalogue,
    'positions'      => $positionLabels,
    'visibility'     => $visibilityLabels,
    'categories'     => $categories,
    'posts'          => $allBlogPosts,
    'itineraries'    => $allItineraries,
    'destinations'   => $allDestinations,
];

require __DIR__ . '/_top.php';
?>

<?php require __DIR__.'/_blog_landing_form.php'; ?>
<form method="post" action="<?= e(base_url('admin/blog_settings_save.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="widgets_v2_json" id="widgets_v2_json" value="<?= e((string) json_encode($widgetsConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>">

    <div class="card card-soft">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Global Blog Widgets</h2>
                <div class="d-flex gap-2">
                    <select id="widgetTypePicker" class="form-select form-select-sm" style="max-width:240px;">
                        <option value="">Add widget…</option>
                        <?php foreach ($widgetCatalogue as $key => $label): ?>
                        <option value="<?= e($key) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline-success btn-sm" id="widgetAddBtn">Add</button>
                </div>
            </div>
            <p class="text-secondary small mb-3">These widgets appear on every blog post unless a per-post widget overrides the same id.</p>
            <div id="widgetList" class="d-grid gap-3"></div>
            <div id="widgetEmptyHint" class="text-secondary small mt-2">No global widgets configured.</div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success">Save Defaults</button>
                <a href="<?= e(base_url('admin/blog_posts.php')) ?>" class="btn btn-outline-secondary">Back to Posts</a>
            </div>
        </div>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
window.WidgetBuilderBootstrap = <?= json_encode($widgetsBootstrap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= e(base_url('admin/assets/widget_builder.js')) ?>?v=2"></script>

<?php require __DIR__ . '/_bottom.php'; ?>
