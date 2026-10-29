<?php
declare(strict_types=1);

$adminPage = 'destinations';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

$destinationId = (int) get_input('destination_id', 0);

if (!$destinationId) {
    flash_set('error', 'No destination specified.');
    redirect('admin/destinations.php');
}

if (!db_table_exists('destinations')) {
    flash_set('error', 'Destination records are not available in this database.');
    redirect('admin/dashboard.php');
}

if (!db_table_exists('destination_photos')) {
    flash_set('error', 'Destination photo gallery is not available in this database.');
    redirect('admin/destination_form.php?id=' . $destinationId);
}

$destination = fetch_one(
    "SELECT id, name, slug, is_published FROM destinations WHERE id = :id LIMIT 1",
    ['id' => $destinationId]
);
if (!$destination) {
    flash_set('error', 'Destination not found.');
    redirect('admin/destinations.php');
}

if (is_post()) {
    guard_post_size();
}

// Handle delete
if (is_post() && post_input('action') === 'delete') {
    require_csrf();
    $photoId = (int) post_input('photo_id', 0);
    if ($photoId) {
        $photo = fetch_one("SELECT image_path FROM destination_photos WHERE id = :id AND destination_id = :dest", ['id' => $photoId, 'dest' => $destinationId]);
        if ($photo) {
            $photoPath = trim((string) ($photo['image_path'] ?? ''));
            try {
                execute_query("DELETE FROM destination_photos WHERE id = :id AND destination_id = :dest", ['id' => $photoId, 'dest' => $destinationId]);
                if ($photoPath !== '') {
                    delete_uploaded_media($photoPath);
                }

                if ((int) ($destination['is_published'] ?? 0) === 1 && !empty($destination['slug'])) {
                    indexnow_submit_urls([
                        base_url('destinations/' . $destination['slug']),
                        base_url('destinations'),
                        base_url('sitemap.xml'),
                    ]);
                }

                flash_set('success', 'Photo deleted.');
            } catch (Throwable) {
                flash_set('error', 'Photo could not be deleted.');
            }
        } else {
            flash_set('error', 'Photo not found.');
        }
    }
    redirect('admin/destination_photos.php?destination_id=' . $destinationId);
}

// Handle metadata update
if (is_post() && post_input('action') === 'update') {
    require_csrf();
    $photoId = (int) post_input('photo_id', 0);
    $caption = trim((string) post_input('caption', ''));
    $sortOrder = (int) post_input('sort_order', 0);

    if (!$photoId) {
        flash_set('error', 'Photo not found.');
        redirect('admin/destination_photos.php?destination_id=' . $destinationId);
    }

    if ($caption === '') {
        flash_set('error', 'Caption is required because it is used as image alt text.');
        redirect('admin/destination_photos.php?destination_id=' . $destinationId);
    }

    if (mb_strlen($caption) > 300) {
        flash_set('error', 'Caption must be 300 characters or fewer.');
        redirect('admin/destination_photos.php?destination_id=' . $destinationId);
    }

    $photo = fetch_one(
        "SELECT id FROM destination_photos WHERE id = :id AND destination_id = :dest LIMIT 1",
        ['id' => $photoId, 'dest' => $destinationId]
    );
    if (!$photo) {
        flash_set('error', 'Photo not found.');
        redirect('admin/destination_photos.php?destination_id=' . $destinationId);
    }

    execute_query(
        "UPDATE destination_photos
            SET caption = :caption, sort_order = :sort_order
          WHERE id = :id AND destination_id = :dest",
        [
            'caption' => $caption,
            'sort_order' => $sortOrder,
            'id' => $photoId,
            'dest' => $destinationId,
        ]
    );

    if ((int) ($destination['is_published'] ?? 0) === 1 && !empty($destination['slug'])) {
        indexnow_submit_urls([
            base_url('destinations/' . $destination['slug']),
            base_url('destinations'),
            base_url('sitemap.xml'),
        ]);
    }

    flash_set('success', 'Photo details updated.');
    redirect('admin/destination_photos.php?destination_id=' . $destinationId);
}

// Handle upload
if (is_post() && post_input('action') === 'upload') {
    require_csrf();
    $caption = trim((string) post_input('caption', ''));
    $sortOrder = (int) post_input('sort_order', 0);

    if ($caption === '') {
        flash_set('error', 'Caption is required because it is used as image alt text.');
        redirect('admin/destination_photos.php?destination_id=' . $destinationId);
    }

    if (mb_strlen($caption) > 300) {
        flash_set('error', 'Caption must be 300 characters or fewer.');
        redirect('admin/destination_photos.php?destination_id=' . $destinationId);
    }

    if (!empty($_FILES['photo']['name'])) {
        try {
            $path = upload_image($_FILES['photo'], 'destination_photos/' . $destinationId);
            execute_query(
                "INSERT INTO destination_photos (destination_id, image_path, caption, sort_order) VALUES (:dest, :path, :caption, :sort)",
                ['dest' => $destinationId, 'path' => $path, 'caption' => $caption, 'sort' => $sortOrder]
            );

            if ((int) ($destination['is_published'] ?? 0) === 1 && !empty($destination['slug'])) {
                indexnow_submit_urls([
                    base_url('destinations/' . $destination['slug']),
                    base_url('destinations'),
                    base_url('sitemap.xml'),
                ]);
            }

            flash_set('success', 'Photo uploaded.');
        } catch (Throwable $e) {
            flash_set('error', 'Upload failed.');
        }
    } else {
        flash_set('error', 'No file selected.');
    }
    redirect('admin/destination_photos.php?destination_id=' . $destinationId);
}

if (is_post()) {
    require_csrf();
    flash_set('error', 'Unknown photo action.');
    redirect('admin/destination_photos.php?destination_id=' . $destinationId);
}

$photos = fetch_all(
    "SELECT * FROM destination_photos WHERE destination_id = :dest ORDER BY sort_order ASC, id ASC",
    ['dest' => $destinationId]
);

$adminTitle = 'Photos: ' . $destination['name'];

require __DIR__ . '/_top.php';
?>

<p><a href="<?= e(base_url('admin/destination_form.php?id=' . $destinationId)) ?>" class="btn btn-sm btn-outline-secondary mb-3">← Back to Destination</a></p>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-3">Upload Photo</h2>
                <form method="post" action="<?= e(base_url('admin/destination_photos.php?destination_id=' . $destinationId)) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload">
                    <div class="mb-3">
                        <label class="form-label">Image *</label>
                        <input type="file" name="photo" class="form-control" accept="image/*" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Caption / Alt Text *</label>
                        <input type="text" name="caption" class="form-control" placeholder="Describe the photo for visitors and search engines" maxlength="300" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= count($photos) ?>">
                    </div>
                    <button class="btn btn-success w-100">Upload</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-3">Gallery (<?= count($photos) ?> photos)</h2>
                <?php if (!$photos): ?>
                    <p class="text-secondary">No photos yet. Upload the first one.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($photos as $photo): ?>
                        <div class="col-6 col-lg-4">
                            <div class="border rounded overflow-hidden" style="position:relative;">
                                <img src="<?= e(UPLOAD_BASE_URL . '/' . ltrim((string)$photo['image_path'], '/')) ?>"
                                     class="img-fluid w-100" style="height:140px;object-fit:cover;"
                                     alt="<?= e((string)($photo['caption'] ?? '')) ?>">
                                <form method="post" action="<?= e(base_url('admin/destination_photos.php?destination_id=' . $destinationId)) ?>" class="p-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>">
                                    <label class="form-label small mb-1">Caption / Alt Text</label>
                                    <input type="text" name="caption" class="form-control form-control-sm mb-2" value="<?= e((string)($photo['caption'] ?? '')) ?>" maxlength="300" required>
                                    <label class="form-label small mb-1">Sort Order</label>
                                    <input type="number" name="sort_order" class="form-control form-control-sm mb-2" value="<?= (int)($photo['sort_order'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">Save Details</button>
                                </form>
                                <form method="post" action="<?= e(base_url('admin/destination_photos.php?destination_id=' . $destinationId)) ?>"
                                      onsubmit="return confirm('Delete photo?')" class="px-2 pb-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger w-100">Delete</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
