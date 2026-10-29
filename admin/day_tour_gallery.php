<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/package_gallery.php';
admin_require_auth();
$id = (int) get_input('id', 0);
try { $record = image_manager_record('day_tour', $id); }
catch (InvalidArgumentException $e) { flash_set('error', $e->getMessage()); redirect('admin/day_tours.php'); }
$adminTitle = 'Day Tour Gallery'; $adminPage = 'day_tours';
if (is_post()) {
    guard_post_size(); require_csrf(); old_input_set($_POST);
    try { package_gallery_save($id, $_POST, $_FILES); old_input_clear(); flash_set('success', 'Gallery saved.'); }
    catch (InvalidArgumentException | DomainException $e) { flash_set('error', $e->getMessage()); }
    catch (Throwable $e) { error_log('Day tour gallery: ' . $e->getMessage()); flash_set('error', 'Gallery could not be saved.'); }
    redirect('admin/day_tour_gallery.php?id=' . $id . '&edit_id=' . (int) post_input('photo_id', 0));
}
$photos = image_manager_children('day_tour', $id); $editId = (int) get_input('edit_id', 0); $photo = [];
foreach ($photos as $row) { if ((int) $row['id'] === $editId) { $photo = $row; } }
require __DIR__ . '/_top.php';
?>
<h1 class="h3"><?= e($record['name']) ?> · Gallery</h1>
<p><a href="<?= e(base_url('admin/day_tour_form.php?id=' . $id)) ?>">Full day tour editor</a> · <a href="<?= e(base_url('admin/image_manager.php?type=day_tour&id=' . $id)) ?>">Image Manager</a></p>
<form class="card card-body mb-4" method="post" enctype="multipart/form-data"><?= csrf_field() ?>
<input type="hidden" name="photo_id" value="<?= (int) ($photo['id'] ?? 0) ?>"><input type="hidden" name="revision" value="<?= e(package_gallery_revision($id)) ?>">
<h2 class="h5"><?= $photo ? 'Edit photo' : 'Add photo' ?></h2>
<div data-media-picker data-field="gallery_image" data-allow-video="0"><label class="form-label">Upload photo<input class="form-control" type="file" name="gallery_image" accept="image/jpeg,image/png,image/webp,image/avif,image/gif"></label><input type="hidden" name="gallery_image_media_asset_id" value="" data-media-asset-id><button type="button" class="btn btn-outline-dark my-2" data-media-open>Choose from Media Library</button><span data-media-selected></span></div>
<label class="form-label">Image description<input name="alt_text" class="form-control" maxlength="255" required value="<?= e((string) old_input('alt_text', $photo['alt_text'] ?? '')) ?>"></label>
<label class="form-label">Display order<input name="sort_order" class="form-control" type="number" value="<?= e((string) old_input('sort_order', $photo['sort_order'] ?? count($photos))) ?>"></label>
<button class="btn btn-success" name="action" value="save">Save photo</button></form>
<div class="row g-3"><?php foreach ($photos as $row): ?><article class="col-sm-6 col-lg-4"><div class="card card-body h-100"><img class="img-fluid" src="<?= e(stored_media_url($row['image_path'])) ?>" alt="<?= e($row['alt_text']) ?>"><p><?= e($row['alt_text']) ?> · Order <?= (int) $row['sort_order'] ?></p><a href="<?= e(base_url('admin/day_tour_gallery.php?id=' . $id . '&edit_id=' . (int) $row['id'])) ?>">Edit</a><form method="post" onsubmit="return confirm('Remove this gallery entry? The photo stays in the library.');"><?= csrf_field() ?><input type="hidden" name="photo_id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="revision" value="<?= e(package_gallery_revision($id)) ?>"><button class="btn btn-outline-danger btn-sm" name="action" value="remove">Remove entry</button></form></div></article><?php endforeach; ?></div>
<?php require __DIR__ . '/_bottom.php'; ?>
