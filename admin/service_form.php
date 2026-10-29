<?php
declare(strict_types=1);

$adminPage = 'services';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$id = (int) get_input('id', 0);
$record = [];

if ($id > 0) {
    $record = fetch_one("SELECT * FROM services WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Service not found.');
        redirect('admin/services.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Service' : 'Add Service';

require __DIR__ . '/_top.php';
?>

<form method="post" action="<?= e(base_url('admin/service_save.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Service Details</h2>

                    <div class="mb-3">
                        <label class="form-label">Icon (emoji) *</label>
                        <input type="text" name="icon" class="form-control" style="font-size:1.5rem;width:80px;"
                               value="<?= e((string) admin_form_value($record, 'icon')) ?>" maxlength="20" required>
                        <div class="form-text">Paste or type an emoji, e.g. 🚗 🏨 🧭 📅 ✈️ 💬</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control"
                               value="<?= e((string) admin_form_value($record, 'title')) ?>" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control"><?= e((string) admin_form_value($record, 'description')) ?></textarea>
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
                <button type="submit" class="btn btn-success">Save Service</button>
                <a href="<?= e(base_url('admin/services.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/_bottom.php'; ?>
