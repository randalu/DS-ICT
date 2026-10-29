<?php
declare(strict_types=1);

$adminPage = 'blog';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
require_once __DIR__ . '/../includes/blog_widgets.php';

$id = (int) get_input('id', 0);
$record = [];

if ($id > 0) {
    $record = fetch_one("SELECT * FROM blog_posts WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Post not found.');
        redirect('admin/blog_posts.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Post' : 'New Post';
$categories = fetch_all("SELECT id, name FROM blog_categories ORDER BY sort_order ASC, name ASC");
$allItineraries = fetch_all("SELECT id, name, days FROM itineraries WHERE is_published = 1 ORDER BY is_featured DESC, sort_order ASC, days ASC, name ASC");
$allDestinations = fetch_all("SELECT id, name FROM destinations WHERE is_published = 1 ORDER BY is_featured DESC, sort_order ASC, name ASC");
$allBlogPosts = fetch_all("SELECT id, title FROM blog_posts ORDER BY published_at DESC, id DESC LIMIT 200");

$widgetsConfig = validate_widget_config((string) admin_form_value($record, 'sidebar_widgets_json', ''));

$oldWidgetsJson = old_input('widgets_v2_json', null);
if (is_string($oldWidgetsJson) && $oldWidgetsJson !== '') {
    $decoded = json_decode($oldWidgetsJson, true);
    if (is_array($decoded)) {
        $widgetsConfig = validate_widget_config($decoded);
    }
}

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

$publishedAt = trim((string) admin_form_value($record, 'published_at', ''));
$publishedAtTimestamp = $publishedAt !== '' ? strtotime($publishedAt) : false;
$publishedAtValue = $publishedAtTimestamp
    ? date('Y-m-d\TH:i', $publishedAtTimestamp)
    : date('Y-m-d\TH:i');

require __DIR__ . '/_top.php';
require_once __DIR__ . '/_media_picker.php';
?>

<form method="post" action="<?= e(base_url('admin/blog_post_save.php')) ?>" enctype="multipart/form-data" data-ai-context="blog" data-ai-title="<?= e($adminTitle) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="widgets_v2_json" id="widgets_v2_json" value="<?= e((string) json_encode($widgetsConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" value="<?= e((string) admin_form_value($record, 'title')) ?>" required data-ai-field="title" data-ai-label="Blog Title">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input
                            type="text"
                            name="slug"
                            class="form-control"
                            value="<?= e((string) admin_form_value($record, 'slug')) ?>"
                            data-live-slug="1"
                            data-slug-source="title"
                            data-canonical-input="canonical_url"
                            data-canonical-prefix="blog"
                        >
                        <div class="form-text">Auto-generates live from title. Edit manually to override.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Excerpt</label>
                        <textarea name="excerpt" rows="3" class="form-control" data-ai-field="excerpt"><?= e((string) admin_form_value($record, 'excerpt')) ?></textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Content</label>
                        <textarea name="content" rows="18" class="form-control quill-editor"><?= (string) admin_form_value($record, 'content') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">SEO</h2>
                    <div class="mb-3">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" value="<?= e((string) admin_form_value($record, 'meta_title')) ?>" data-ai-field="meta_title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" rows="3" class="form-control" data-ai-field="meta_description"><?= e((string) admin_form_value($record, 'meta_description')) ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Canonical URL</label>
                        <input type="text" name="canonical_url" class="form-control" value="<?= e((string) admin_form_value($record, 'canonical_url')) ?>">
                        <div class="form-text">Auto-builds from slug. Clear this field to re-enable auto mode.</div>
                    </div>
                </div>
            </div>

            <div class="card card-soft mt-4" id="widgetBuilderCard">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Widgets</h2>
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
                    <p class="text-secondary small mb-3">Drag-reorder widgets. Per-post widgets override identically-positioned global defaults.
                        Configure global defaults in <a href="<?= e(base_url('admin/blog_settings.php')) ?>">Blog Settings</a>.</p>
                    <div id="widgetList" class="d-grid gap-3"></div>
                    <div id="widgetEmptyHint" class="text-secondary small mt-2">No widgets configured for this post — global defaults will be used.</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Publish</h2>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">— None —</option>
                            <?php
                            $currentCat = (int) admin_form_value($record, 'category_id', 0);
                            foreach ($categories as $cat):
                            ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= $currentCat === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Author</label>
                        <input type="text" name="author" class="form-control" value="<?= e((string) admin_form_value($record, 'author', 'Island Culture Travels')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Publish Date</label>
                        <input type="datetime-local" name="published_at" class="form-control"
                               value="<?= e($publishedAtValue) ?>">
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1"
                               <?= admin_checkbox_value($record, 'is_featured') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured">Featured</label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_published" id="is_published" value="1"
                               <?= admin_checkbox_value($record, 'is_published') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_published">Published</label>
                    </div>

                    <button type="submit" class="btn btn-success w-100"><?= $id > 0 ? 'Update Post' : 'Create Post' ?></button>
                    <a href="<?= e(base_url('admin/blog_posts.php')) ?>" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Images</h2>
                    <div class="mb-3">
                        <?php admin_media_picker('hero_image', 'Hero Image / Video', $record, ['allow_video' => true]); ?>
                    </div>
                    <div class="mb-0">
                        <?php admin_media_picker('og_image', 'OG Image', $record); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
window.WidgetBuilderBootstrap = <?= json_encode($widgetsBootstrap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= e(base_url('admin/assets/widget_builder.js')) ?>?v=2"></script>

<?php require_once __DIR__.'/../includes/page_media_admin.php';page_media_admin_assets();page_media_manager('blog',(int)$id); ?>
<?php require __DIR__ . '/_bottom.php'; ?>
