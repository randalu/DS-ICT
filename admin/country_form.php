<?php
declare(strict_types=1);

$adminPage = 'countries';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$adminTitle = 'Country Pages';

if (!db_table_exists('country_pages')) {
    require __DIR__ . '/_top.php';
    ?>
    <div class="alert alert-warning">Country page management is not available because the <code>country_pages</code> table is missing.</div>
    <?php
    require __DIR__ . '/_bottom.php';
    return;
}

$id = (int) get_input('id', 0);
$record = [];

if ($id > 0) {
    $record = fetch_one("SELECT * FROM country_pages WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Country page not found.');
        redirect('admin/countries.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Country Page' : 'Add Country Page';

require __DIR__ . '/_top.php';
?>

<form method="post" action="<?= e(base_url('admin/country_save.php')) ?>" enctype="multipart/form-data" data-ai-context="country" data-ai-title="<?= e($adminTitle) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Content</h2>

                    <div class="mb-3">
                        <label class="form-label">Country Name *</label>
                        <input type="text" name="country_name" class="form-control" value="<?= e((string) admin_form_value($record, 'country_name')) ?>" required data-ai-field="country_name" data-ai-label="Country Name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="<?= e((string) admin_form_value($record, 'slug')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Excerpt</label>
                        <textarea name="excerpt" rows="3" class="form-control" data-ai-field="excerpt"><?= e((string) admin_form_value($record, 'excerpt')) ?></textarea>
                    </div>

                    <?php
                    $textareas = [
                        'intro' => 'Introduction',
                        'why_sri_lanka' => 'Why Sri Lanka Suits This Market',
                        'recommended_itinerary' => 'Recommended Itinerary',
                        'travel_info' => 'Travel Planning Notes',
                        'best_time' => 'Best Time to Visit',
                        'cta_text' => 'CTA Text',
                    ];
                    foreach ($textareas as $field => $label):
                    ?>
                        <div class="mb-3">
                            <label class="form-label"><?= e($label) ?></label>
                            <textarea name="<?= e($field) ?>" rows="5" class="form-control quill-editor"><?= e((string) admin_form_value($record, $field)) ?></textarea>
                        </div>
                    <?php endforeach; ?>

                    <div class="mb-3">
                        <label class="form-label">CTA Title</label>
                        <input type="text" name="cta_title" class="form-control" value="<?= e((string) admin_form_value($record, 'cta_title')) ?>">
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
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Publish</h2>

                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= e((string) admin_form_value($record, 'sort_order', 0)) ?>">
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" <?= admin_checkbox_value($record, 'is_published') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_published">Published</label>
                    </div>

                    <button type="submit" class="btn btn-success w-100"><?= $id > 0 ? 'Update Country Page' : 'Create Country Page' ?></button>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Images</h2>

                    <div class="mb-3">
                        <label class="form-label">Hero Image</label>
                        <input type="file" name="hero_image" class="form-control" accept="image/*">
                        <?php if (!empty($record['hero_image'])): ?>
                            <div class="small mt-2 text-secondary"><?= e((string) $record['hero_image']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">OG Image</label>
                        <input type="file" name="og_image" class="form-control" accept="image/*">
                        <?php if (!empty($record['og_image'])): ?>
                            <div class="small mt-2 text-secondary"><?= e((string) $record['og_image']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/_bottom.php'; ?>
