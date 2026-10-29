<?php
declare(strict_types=1);
$packageTable = package_tables()[$packageType];
$coverReady = db_column_exists($packageTable, 'cover_image') && db_column_exists($packageTable, 'cover_alt') && db_column_exists($packageTable, 'cover_media_asset_id');
?>
<section class="card card-soft mb-4" id="package-cover"><div class="card-body">
<h2 class="h5">Cover / Thumbnail Image</h2>
<p class="small text-secondary">Used on homepage cards, listings and related-package cards. The detail-page hero and social-sharing image remain independent.</p>
<?php if (!$coverReady): ?><p class="alert alert-info">The package media migration is required to enable a separate cover image.</p>
<?php else: ?>
<input type="hidden" name="cover_present" value="1">
<?php if (!empty($record['cover_image'])): ?><img class="img-fluid rounded mb-3" style="max-height:180px" src="<?= e(stored_media_url($record['cover_image'])) ?>" alt="<?= e(package_card_alt($record)) ?>"><?php else: ?><p class="small">No separate cover selected. Existing photography is used as a fallback.</p><?php endif; ?>
<div data-media-picker data-field="cover_image" data-allow-video="0">
<label class="form-label">Upload cover<input type="file" class="form-control" name="cover_image" accept="image/jpeg,image/png,image/webp,image/avif,image/gif"></label>
<input type="hidden" name="cover_image_media_asset_id" value="" data-media-asset-id>
<button type="button" class="btn btn-outline-dark btn-sm my-2" data-media-open>Choose cover from Media Library</button>
<span class="small" data-media-selected>No replacement selected</span>
</div>
<label class="form-label w-100">Cover alt text<input class="form-control" name="cover_alt" maxlength="255" value="<?= e((string) admin_form_value($record, 'cover_alt')) ?>" placeholder="Describe the photo"></label>
<?php if (!empty($record['cover_image'])): ?><label class="form-check-label"><input type="checkbox" name="cover_clear" value="1"> Clear this cover (keeps the file in the library)</label><?php endif; ?>
<?php endif; ?>
<?php if (!empty($id)): ?><p class="mt-2 mb-0"><a href="<?= e(base_url('admin/image_manager.php?type=' . $packageType . '&id=' . $id)) ?>">Manage all package images</a></p><?php endif; ?>
</div></section>
