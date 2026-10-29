<?php
declare(strict_types=1);

$adminTitle = 'Itinerary Categories';
$adminPage = 'itinerary_categories';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

if (!db_table_exists('itinerary_categories')) {
    require __DIR__ . '/_top.php';
    ?>
    <div class="alert alert-warning">
        Itinerary category schema is missing. Run
        <code>database/migrations/2026-04-21-itinerary-categories-and-day-ranges.sql</code>
        in your database first.
    </div>
    <?php
    require __DIR__ . '/_bottom.php';
    return;
}

$hasCategoryPublishedFlag = db_column_exists('itinerary_categories', 'is_published');
$categoryPublishedSelect = $hasCategoryPublishedFlag ? 'is_published' : '1 AS is_published';

if (is_post()) {
    guard_post_size();
    require_csrf();

    $action = (string) post_input('action', 'save');

    if ($action === 'delete') {
        $deleteId = (int) post_input('id', 0);
        if ($deleteId > 0) {
            $deletedRecord = fetch_one(
                "SELECT slug, {$categoryPublishedSelect} FROM itinerary_categories WHERE id = :id LIMIT 1",
                ['id' => $deleteId]
            );

            if (db_column_exists('itineraries', 'category_id')) {
                execute_query("UPDATE itineraries SET category_id = NULL WHERE category_id = :id", ['id' => $deleteId]);
            }
            execute_query("DELETE FROM itinerary_categories WHERE id = :id", ['id' => $deleteId]);

            $indexNowUrls = [
                base_url('experiences'),
                base_url('sitemap.xml'),
            ];

            $wasPublished = !$hasCategoryPublishedFlag || (int) ($deletedRecord['is_published'] ?? 1) === 1;
            if ($deletedRecord && $wasPublished && !empty($deletedRecord['slug'])) {
                $indexNowUrls[] = base_url('experiences/' . $deletedRecord['slug']);
                $indexNowUrls[] = base_url('tours/' . $deletedRecord['slug']);
            }

            indexnow_submit_urls($indexNowUrls);

            flash_set('success', 'Category deleted.');
        }
        redirect('admin/itinerary_categories.php');
    }

    $id = (int) post_input('id', 0);
    $name = trim((string) post_input('name', ''));
    $slugRaw = trim((string) post_input('slug', ''));
    $description = trim((string) post_input('description', ''));
    $metaTitle = trim((string) post_input('meta_title', ''));
    $metaDescription = trim((string) post_input('meta_description', ''));
    $canonicalUrl = trim((string) post_input('canonical_url', ''));
    $sortOrder = (int) post_input('sort_order', 0);
    $isPublished = post_input('is_published') ? 1 : 0;

    $slug = $slugRaw !== '' ? slugify($slugRaw) : slugify($name);

    $errors = [];

    if ($name === '') {
        $errors[] = 'Category name is required.';
    }

    if ($slug === '') {
        $errors[] = 'Category slug is required.';
    }

    $duplicate = fetch_one(
        "SELECT id FROM itinerary_categories WHERE slug = :slug AND id != :id LIMIT 1",
        ['slug' => $slug, 'id' => $id]
    );

    if ($duplicate) {
        $errors[] = 'Category slug already exists.';
    }

    $record = [];
    if ($id > 0) {
        $record = fetch_one("SELECT * FROM itinerary_categories WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
        if (!$record) {
            $errors[] = 'Category not found.';
        }
    }

    if ($metaTitle === '') {
        $metaTitle = $name . ' Sri Lanka Experiences | ' . APP_NAME;
    }

    if ($metaDescription === '') {
        $metaDescription = 'Explore ' . $name . ' experiences in Sri Lanka and related private itinerary ideas.';
    }

    if ($canonicalUrl === '') {
        $canonicalUrl = base_url('experiences/' . $slug);
    }

    if ($errors) {
        flash_set('error', implode(' ', $errors));
        redirect($id > 0 ? 'admin/itinerary_categories.php?edit=' . $id : 'admin/itinerary_categories.php');
    }

    try {
        db()->beginTransaction();

        $heroImage = $record['hero_image'] ?? null;
        $ogImage = $record['og_image'] ?? null;

        if (!empty($_FILES['hero_image']['name'])) {
            $heroImage = upload_image($_FILES['hero_image'], 'itinerary-categories');
        }

        if (!empty($_FILES['og_image']['name'])) {
            $ogImage = upload_image($_FILES['og_image'], 'itinerary-categories');
        }

        $columnValueMap = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description !== '' ? $description : null,
            'hero_image' => $heroImage,
            'og_image' => $ogImage,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'canonical_url' => $canonicalUrl,
            'sort_order' => $sortOrder,
            'is_published' => $isPublished,
        ];

        $writeData = [];
        foreach ($columnValueMap as $column => $value) {
            if (db_column_exists('itinerary_categories', $column)) {
                $writeData[$column] = $value;
            }
        }

        if ($id > 0) {
            $setParts = [];
            foreach (array_keys($writeData) as $column) {
                $setParts[] = "{$column} = :{$column}";
            }

            execute_query(
                'UPDATE itinerary_categories SET ' . implode(', ', $setParts) . ' WHERE id = :id',
                array_merge($writeData, ['id' => $id])
            );
        } else {
            $columns = array_keys($writeData);
            $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

            execute_query(
                'INSERT INTO itinerary_categories (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')',
                $writeData
            );

            $id = (int) last_insert_id();
        }

        db()->commit();

        $oldSlug = trim((string) ($record['slug'] ?? ''));
        $wasPublished = !$hasCategoryPublishedFlag || (int) ($record['is_published'] ?? 1) === 1;
        $isPublishedNow = !$hasCategoryPublishedFlag || $isPublished === 1;

        $indexNowUrls = [
            base_url('experiences'),
            base_url('sitemap.xml'),
        ];

        if ($isPublishedNow) {
            $indexNowUrls[] = base_url('experiences/' . $slug);
            $indexNowUrls[] = base_url('tours/' . $slug);
        }

        if ($id > 0 && $wasPublished && $oldSlug !== '' && ($oldSlug !== $slug || !$isPublishedNow)) {
            $indexNowUrls[] = base_url('experiences/' . $oldSlug);
            $indexNowUrls[] = base_url('tours/' . $oldSlug);
        }

        indexnow_submit_urls($indexNowUrls);

        flash_set('success', 'Category saved successfully.');
        redirect('admin/itinerary_categories.php?edit=' . $id);
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash_set('error', 'Failed to save category.');
        redirect($id > 0 ? 'admin/itinerary_categories.php?edit=' . $id : 'admin/itinerary_categories.php');
    }
}

$editId = (int) get_input('edit', 0);
$editRecord = [];
if ($editId > 0) {
    $editRecord = fetch_one("SELECT * FROM itinerary_categories WHERE id = :id LIMIT 1", ['id' => $editId]) ?: [];
}

$canCountByCategory = db_column_exists('itineraries', 'category_id');
$categoryCounts = $canCountByCategory
    ? fetch_all("SELECT category_id, COUNT(*) AS total FROM itineraries GROUP BY category_id")
    : [];

$countMap = [];
foreach ($categoryCounts as $row) {
    $countMap[(int) ($row['category_id'] ?? 0)] = (int) ($row['total'] ?? 0);
}

$categories = fetch_all("SELECT * FROM itinerary_categories ORDER BY sort_order ASC, name ASC");

require __DIR__ . '/_top.php';
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= $editId > 0 ? 'Edit Category' : 'Add Category' ?></h2>

                <form method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int) ($editRecord['id'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= e((string) ($editRecord['name'] ?? '')) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="<?= e((string) ($editRecord['slug'] ?? '')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="4" class="form-control"><?= e((string) ($editRecord['description'] ?? '')) ?></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="<?= e((string) ($editRecord['sort_order'] ?? '0')) ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_published" id="is_published" value="1" <?= ((int) ($editRecord['is_published'] ?? 1) === 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_published">Published</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Hero Image</label>
                        <input type="file" name="hero_image" class="form-control" accept="image/*">
                        <?php if (!empty($editRecord['hero_image'])): ?>
                            <div class="small mt-2 text-secondary"><?= e((string) $editRecord['hero_image']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">OG Image</label>
                        <input type="file" name="og_image" class="form-control" accept="image/*">
                        <?php if (!empty($editRecord['og_image'])): ?>
                            <div class="small mt-2 text-secondary"><?= e((string) $editRecord['og_image']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" value="<?= e((string) ($editRecord['meta_title'] ?? '')) ?>" placeholder="Auto-generated if blank">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" rows="3" class="form-control" placeholder="Auto-generated if blank"><?= e((string) ($editRecord['meta_description'] ?? '')) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Canonical URL</label>
                        <input type="text" name="canonical_url" class="form-control" value="<?= e((string) ($editRecord['canonical_url'] ?? '')) ?>" placeholder="Auto-generated if blank">
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-success" type="submit"><?= $editId > 0 ? 'Update Category' : 'Create Category' ?></button>
                        <?php if ($editId > 0): ?>
                            <a href="<?= e(base_url('admin/itinerary_categories.php')) ?>" class="btn btn-outline-dark">New Category</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-3">Categories</h2>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Itineraries</th>
                            <th>Published</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($categories): ?>
                            <?php foreach ($categories as $category): ?>
                                <?php
                                $categoryId = (int) $category['id'];
                                $assignedCount = (int) ($countMap[$categoryId] ?? 0);
                                ?>
                                <tr>
                                    <td><?= e($category['name']) ?></td>
                                    <td><code><?= e($category['slug']) ?></code></td>
                                    <td><?= $assignedCount ?></td>
                                    <td><?= (int) ($category['is_published'] ?? 1) ? 'Yes' : 'No' ?></td>
                                    <td class="text-end">
                                        <a href="<?= e(base_url('admin/itinerary_categories.php?edit=' . $categoryId)) ?>" class="btn btn-sm btn-outline-dark">Edit</a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $categoryId ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-secondary">No categories yet.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
