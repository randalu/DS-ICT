<?php
declare(strict_types=1);

$adminPage = 'services';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$adminTitle = 'Services';

$items = fetch_all("SELECT * FROM services ORDER BY sort_order ASC, id ASC");

require __DIR__ . '/_top.php';
?>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= e(base_url('admin/service_form.php')) ?>" class="btn btn-success btn-sm">+ Add Service</a>
</div>

<div class="card card-soft">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Order</th>
                    <th>Icon</th>
                    <th>Title</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="5" class="text-secondary py-4 text-center">No services yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= (int) $item['sort_order'] ?></td>
                    <td style="font-size:1.4rem;"><?= e($item['icon']) ?></td>
                    <td><?= e($item['title']) ?></td>
                    <td><?= $item['is_active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                    <td class="text-end">
                        <a href="<?= e(base_url('admin/service_form.php?id=' . $item['id'])) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                        <form method="post" action="<?= e(base_url('admin/service_delete.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this service?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
