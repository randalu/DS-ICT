<?php
declare(strict_types=1);

$adminPage = 'faqs';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

ensure_faq_entries_table();

$id = (int) get_input('id', 0);
$record = [];

if ($id > 0) {
    $record = fetch_one("SELECT * FROM faq_entries WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'FAQ item not found.');
        redirect('admin/faqs.php');
    }
}

$adminTitle = $id > 0 ? 'Edit FAQ Item' : 'Add FAQ Item';

require __DIR__ . '/_top.php';
?>

<form method="post" action="<?= e(base_url('admin/faq_save.php')) ?>" data-ai-context="faq" data-ai-title="<?= e($adminTitle) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">FAQ Content</h2>

                    <div class="mb-3">
                        <label class="form-label">Question *</label>
                        <input type="text" name="question" class="form-control" value="<?= e((string) admin_form_value($record, 'question')) ?>" required data-ai-field="question">
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Answer *</label>
                        <textarea name="answer" rows="10" class="form-control quill-editor" required><?= e((string) admin_form_value($record, 'answer')) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Publish</h2>

                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= e((string) admin_form_value($record, 'sort_order', 0)) ?>">
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" <?= admin_checkbox_value($record, 'is_published', 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_published">Published</label>
                    </div>

                    <button type="submit" class="btn btn-success w-100"><?= $id > 0 ? 'Update FAQ' : 'Create FAQ' ?></button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/_bottom.php'; ?>
