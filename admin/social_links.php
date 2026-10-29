<?php
declare(strict_types=1);

$adminPage = 'social_links';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$adminTitle = 'Social Links';

$items = fetch_all("
    SELECT id, slot_key, label, url, icon_text, icon_image, sort_order, is_active, is_custom
    FROM social_links
    ORDER BY sort_order ASC, id ASC
");

$formatSlot = static function (string $slotKey): string {
    return ucwords(str_replace('_', ' ', trim($slotKey)));
};

require __DIR__ . '/_top.php';
?>

<div class="card card-soft mb-3">
    <div class="card-body">
        <div class="small text-secondary">
            Configure footer social buttons here. You have built-in platforms plus 5 custom slots.
            For custom slots, you can upload a custom icon image.
        </div>
    </div>
</div>

<div class="card card-soft">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Slot</th>
                    <th>Preview</th>
                    <th>Label</th>
                    <th>URL</th>
                    <th>Sort</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="7" class="text-secondary py-4 text-center">No social link slots found.</td></tr>
                <?php endif; ?>
                <?php foreach ($items as $item): ?>
                    <?php
                    $iconText = trim((string) ($item['icon_text'] ?? ''));
                    $iconImage = trim((string) ($item['icon_image'] ?? ''));
                    ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($formatSlot((string) $item['slot_key'])) ?></div>
                            <?php if ((int) $item['is_custom'] === 1): ?>
                                <div class="small text-secondary">Custom slot</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($iconImage !== ''): ?>
                                <img src="<?= e(stored_media_url($iconImage)) ?>" alt="<?= e((string) $item['label']) ?>" style="width:24px;height:24px;object-fit:contain;">
                            <?php else: ?>
                                <span class="badge bg-dark"><?= e($iconText !== '' ? $iconText : strtoupper(substr((string) $item['label'], 0, 1))) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e((string) $item['label']) ?></td>
                        <td>
                            <?php if (!empty($item['url'])): ?>
                                <a href="<?= e((string) $item['url']) ?>" target="_blank" rel="noopener"><?= e((string) $item['url']) ?></a>
                            <?php else: ?>
                                <span class="text-secondary small">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $item['sort_order'] ?></td>
                        <td><?= (int) $item['is_active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('admin/social_link_form.php?id=' . (int) $item['id'])) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
