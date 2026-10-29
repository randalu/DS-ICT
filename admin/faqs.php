<?php
declare(strict_types=1);

$adminTitle = 'FAQ Manager';
$adminPage = 'faqs';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

ensure_faq_entries_table();

$page = max(1, (int) get_input('page', 1));
$perPage = 20;
$offset = paginate_offset($page, $perPage);
$search = trim((string) get_input('q', ''));

$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE question LIKE :search_question OR answer LIKE :search_answer";
    $params['search_question'] = '%' . $search . '%';
    $params['search_answer'] = '%' . $search . '%';
}

$total = (int) (fetch_one("SELECT COUNT(*) AS total FROM faq_entries {$where}", $params)['total'] ?? 0);
$items = fetch_all("
    SELECT id, question, sort_order, is_published, updated_at
    FROM faq_entries
    {$where}
    ORDER BY sort_order ASC, id ASC
    LIMIT {$perPage} OFFSET {$offset}
", $params);
$pages = max(1, (int) ceil($total / max(1, $perPage)));

require __DIR__ . '/_top.php';
?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <form class="d-flex gap-2" method="get">
                <input type="text" name="q" class="form-control" placeholder="Search FAQ..." value="<?= e($search) ?>">
                <button class="btn btn-outline-dark">Search</button>
            </form>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= e(base_url('admin/faq_page.php')) ?>" class="btn btn-outline-dark">Edit /faq Page</a>
                <a href="<?= e(base_url('admin/faq_form.php')) ?>" class="btn btn-success">Add FAQ</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Question</th>
                    <th>Published</th>
                    <th>Sort</th>
                    <th>Updated</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($items): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e((string) $item['question']) ?></td>
                            <td><?= (int) $item['is_published'] === 1 ? 'Yes' : 'No' ?></td>
                            <td><?= (int) $item['sort_order'] ?></td>
                            <td class="text-secondary small"><?= e((string) $item['updated_at']) ?></td>
                            <td class="text-end">
                                <a href="<?= e(base_url('admin/faq_form.php?id=' . (int) $item['id'])) ?>" class="btn btn-sm btn-outline-dark">Edit</a>
                                <form method="post" action="<?= e(base_url('admin/faq_delete.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this FAQ item?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary">No FAQ entries found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination mb-0">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item<?= $i === $page ? ' active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
