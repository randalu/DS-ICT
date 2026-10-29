<?php
declare(strict_types=1);

$adminPage = 'pages';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/trust_pages.php';

admin_require_auth();

$adminTitle = 'Static Pages';

if (!db_table_exists('static_pages')) {
    require __DIR__ . '/_top.php';
    ?>
    <div class="alert alert-warning">Static page management is not available because the <code>static_pages</code> table is missing.</div>
    <?php
    require __DIR__ . '/_bottom.php';
    return;
}

$id = (int) get_input('id', 0);
$record = [];

if ($id > 0) {
    $record = fetch_one("SELECT * FROM static_pages WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Static page not found.');
        redirect('admin/pages.php');
    }
}

if ($id === 0) {
    $template = get_input('trust_template', '');
    if (is_string($template) && $template !== '') {
        $drafts = trust_page_drafts();
        if (!isset($drafts[$template])) { http_response_code(404); exit('Unknown trust-page template.'); }
        $existingDraft = fetch_one('SELECT id FROM static_pages WHERE slug=? LIMIT 1', [$template]);
        if ($existingDraft) { redirect('admin/page_form.php?id=' . (int)$existingDraft['id']); }
        $record = $drafts[$template];
    }
}
$isPolicyDraft = in_array((string)($record['slug'] ?? ''), array_merge(array_keys(published_static_page_trust_routes()), ['privacy-policy']), true);
$adminTitle = $id > 0 ? 'Edit Static Page' : 'Add Static Page';

require __DIR__ . '/_top.php';
require_once __DIR__ . '/_media_picker.php';
?>

<form method="post" action="<?= e(base_url('admin/page_save.php')) ?>" enctype="multipart/form-data" data-ai-context="page" data-ai-title="<?= e($adminTitle) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Content</h2>

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" value="<?= e((string) admin_form_value($record, 'title')) ?>" required data-ai-field="title" data-ai-label="Page Title">
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
                            data-canonical-prefix=""
                        >
                        <div class="form-text">Auto-generates live from title. Edit manually to override.</div>
                    </div>

                    <div class="mb-0">
                        <?php require_once __DIR__.'/../includes/page_media.php'; ?>
                        <label class="form-label">Layout</label><select name="layout_style" class="form-select mb-3" <?= !db_column_exists('static_pages','layout_style')?'disabled':'' ?>><option value="document" <?= admin_form_value($record,'layout_style',page_layout($record))==='document'?'selected':'' ?>>Readable document</option><option value="travel" <?= admin_form_value($record,'layout_style',page_layout($record))==='travel'?'selected':'' ?>>Travel / service</option></select>
                        <label class="form-label">Introduction</label><textarea class="form-control mb-3" rows="4" name="intro" <?= !db_column_exists('static_pages','intro')?'disabled':'' ?>><?= e((string)admin_form_value($record,'intro')) ?></textarea>
                        <label class="form-label">Content</label>
                        <textarea name="content" rows="14" class="form-control quill-editor"><?= e((string) admin_form_value($record, 'content')) ?></textarea>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label class="form-label">CTA Title</label>
                        <input type="text" name="cta_title" class="form-control" value="<?= e((string) admin_form_value($record, 'cta_title')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">CTA Text</label>
                        <textarea name="cta_text" rows="4" class="form-control quill-editor"><?= e((string) admin_form_value($record, 'cta_text')) ?></textarea>
                    </div>

                    <?php require __DIR__ . '/_faq_editor.php'; ?>
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
        </div>

        <div class="col-lg-4">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Publish</h2>

                    <?php if ($isPolicyDraft): ?>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="trust_owner_approved" value="1" id="trust_owner_approved"><label class="form-check-label" for="trust_owner_approved">The business owner approves this exact wording and its factual claims for publication.</label></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= e((string) admin_form_value($record, 'sort_order', 0)) ?>">
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" <?= admin_checkbox_value($record, 'is_published') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_published">Published</label>
                    </div>

                    <button type="submit" class="btn btn-success w-100"><?= $id > 0 ? 'Update Page' : 'Create Page' ?></button>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Hero Media</h2>
                    <?php admin_media_picker('hero_image', 'Hero Image / Video', $record, ['allow_video' => true]); ?>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__.'/../includes/page_media_admin.php';page_media_admin_assets();page_media_manager('page',(int)$id); ?>
<?php require __DIR__ . '/_bottom.php'; ?>
