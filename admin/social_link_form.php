<?php
declare(strict_types=1);

$adminPage = 'social_links';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$id = (int) get_input('id', 0);
if ($id <= 0) {
    flash_set('error', 'Social link slot not found.');
    redirect('admin/social_links.php');
}

$record = fetch_one("SELECT * FROM social_links WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
if (!$record) {
    flash_set('error', 'Social link slot not found.');
    redirect('admin/social_links.php');
}

$adminTitle = 'Edit Social Link';

require __DIR__ . '/_top.php';
?>

<form method="post" action="<?= e(base_url('admin/social_link_save.php')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Social Link Details</h2>

                    <div class="mb-3">
                        <label class="form-label">Slot</label>
                        <input type="text" class="form-control" value="<?= e(ucwords(str_replace('_', ' ', (string) $record['slot_key']))) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Label *</label>
                        <input type="text" name="label" class="form-control"
                               value="<?= e((string) admin_form_value($record, 'label')) ?>" maxlength="100" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <input type="url" name="url" class="form-control"
                               placeholder="https://example.com/profile"
                               value="<?= e((string) admin_form_value($record, 'url')) ?>" maxlength="255">
                        <div class="form-text">Leave empty to keep this slot hidden on the footer.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Icon Text</label>
                        <input type="text" name="icon_text" class="form-control"
                               value="<?= e((string) admin_form_value($record, 'icon_text')) ?>" maxlength="20">
                        <div class="form-text">Examples: `f`, `in`, `yt`, `★`. Used when no image icon is uploaded.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Icon Image (optional)</label>
                        <input type="file" name="icon_image" class="form-control" accept="image/*">
                        <?php if (!empty($record['icon_image'])): ?>
                            <div class="mt-2 d-flex align-items-center gap-2">
                                <img src="<?= e(stored_media_url((string) $record['icon_image'])) ?>" alt="<?= e((string) $record['label']) ?>" style="width:30px;height:30px;object-fit:contain;">
                                <span class="small text-secondary"><?= e((string) $record['icon_image']) ?></span>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_icon_image" id="remove_icon_image" value="1">
                                <label class="form-check-label" for="remove_icon_image">Remove current icon image</label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Settings</h2>
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control"
                               value="<?= (int) admin_form_value($record, 'sort_order', 0) ?>">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                            <?= admin_checkbox_value($record, 'is_active', true) ?>>
                        <label class="form-check-label" for="is_active">Active (visible on site)</label>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success">Save Social Link</button>
                <a href="<?= e(base_url('admin/social_links.php')) ?>" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/_bottom.php'; ?>
