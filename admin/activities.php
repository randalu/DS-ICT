<?php
declare(strict_types=1);

$adminTitle = 'Activities';
$adminPage = 'activities';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity_pages.php';
admin_require_auth();

$search = trim((string) get_input('q', ''));
$items = [];
$schemaReady = db_table_exists('activity_pages');
$missingByPage=[];
if(activity_gallery_available()){
    foreach(fetch_all('SELECT e.activity_page_id,COUNT(*) AS missing FROM activity_examples e WHERE NOT EXISTS(SELECT 1 FROM activity_example_images g WHERE g.activity_example_id=e.id) GROUP BY e.activity_page_id') as $missing){$missingByPage[(int)$missing['activity_page_id']]=(int)$missing['missing'];}
}
if ($schemaReady) {
    $where = '';
    $params = [];
    if ($search !== '') {
        $where = 'WHERE name LIKE :name OR slug LIKE :slug';
        $params = ['name' => '%' . $search . '%', 'slug' => '%' . $search . '%'];
    }
    $items = fetch_all("SELECT id,name,slug,is_hub,is_published,sort_order,updated_at FROM activity_pages {$where} ORDER BY is_hub DESC,sort_order,name", $params);
}
require __DIR__ . '/_top.php';
?>
<div class="card card-soft"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <form class="d-flex gap-2" method="get"><input class="form-control" name="q" value="<?= e($search) ?>" placeholder="Search Activities"><button class="btn btn-outline-dark">Search</button></form>
        <?php if ($schemaReady): ?><a class="btn btn-success" href="<?= e(base_url('admin/activity_form.php')) ?>">Add category</a><?php endif; ?>
    </div>
    <?php if (!$schemaReady): ?>
        <div class="alert alert-warning mb-0">Activities are not installed in this database yet. Review and apply <code>database/migrations/2026-10-25-activities.sql</code>, then run the seed script.</div>
    <?php else: ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Page</th><th>Slug</th><th>Type</th><th>Published</th><th>Updated</th><th class="text-end">Actions</th></tr></thead><tbody>
        <?php foreach ($items as $item): ?>
            <tr><td><?= e($item['name']) ?><?php if(!empty($missingByPage[(int)$item['id']])): ?><small class="d-block text-warning"><?= $missingByPage[(int)$item['id']] ?> entries need images</small><?php endif; ?></td><td><code><?= e($item['slug']) ?></code></td><td><?= (int)$item['is_hub'] ? 'Hub' : 'Category' ?></td><td><?= (int)$item['is_published'] ? 'Yes' : 'Draft' ?></td><td><?= e((string)$item['updated_at']) ?></td><td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-dark" href="<?= e(base_url('admin/activity_form.php?id='.(int)$item['id'])) ?>">Edit</a>
                <a class="btn btn-sm btn-outline-dark" href="<?= e(base_url('admin/image_manager.php?type=activity&id='.(int)$item['id'])) ?>">Images</a>
                <a class="btn btn-sm btn-outline-dark" href="<?= e(base_url('admin/seo_manager.php?type=activity&id='.(int)$item['id'])) ?>">SEO</a>
            </td></tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="6" class="text-center text-secondary">No activity pages found. Run the seed script after applying the migration.</td></tr><?php endif; ?>
        </tbody></table></div>
    <?php endif; ?>
</div></div>
<?php require __DIR__ . '/_bottom.php'; ?>
