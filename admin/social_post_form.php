<?php
declare(strict_types=1);

$adminPage = 'social_posts';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

ensure_social_marketing_schema();

$id = (int) get_input('id', 0);
$record = [];

if ($id > 0) {
    $record = fetch_one('SELECT * FROM social_media_posts WHERE id = :id LIMIT 1', ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Social draft not found.');
        redirect('admin/social_posts.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Social Draft' : 'New Social Draft';

$hasDestinations = db_table_exists('destinations') && db_column_exists('destinations', 'id');
$hasItineraries = db_table_exists('itineraries') && db_column_exists('itineraries', 'id');
$hasTours = db_table_exists('tours') && db_column_exists('tours', 'id');
$hasDestinationName = $hasDestinations && db_column_exists('destinations', 'name');
$hasItineraryName = $hasItineraries && db_column_exists('itineraries', 'name');
$hasTourName = $hasTours && db_column_exists('tours', 'name');

$destinationWhere = $hasDestinations && db_column_exists('destinations', 'is_published') ? 'WHERE is_published = 1' : '';
$itineraryWhere = $hasItineraries && db_column_exists('itineraries', 'is_published') ? 'WHERE is_published = 1' : '';
$tourWhere = $hasTours && db_column_exists('tours', 'is_published') ? 'WHERE is_published = 1' : '';
$destinations = $hasDestinationName ? fetch_all("SELECT id, name FROM destinations {$destinationWhere} ORDER BY name ASC") : [];
$itineraries = $hasItineraryName ? fetch_all("SELECT id, name FROM itineraries {$itineraryWhere} ORDER BY name ASC") : [];
$tours = $hasTourName ? fetch_all("SELECT id, name FROM tours {$tourWhere} ORDER BY name ASC") : [];
$mediaAssets = fetch_all('SELECT id, file_path, file_name, mime_type, alt_text FROM media_assets ORDER BY id DESC LIMIT 200');

$platformOptions = social_media_platform_options();
$statusOptions = ['draft', 'ready', 'scheduled', 'published', 'archived'];

$selectedPlatforms = decode_json_array((string) ($record['platform_targets_json'] ?? '[]'));
$selectedAssetIds = [];
if ($id > 0) {
    $selectedAssetIds = array_map('intval', array_column(fetch_all('SELECT asset_id FROM social_media_post_assets WHERE post_id = :post_id ORDER BY sort_order ASC, id ASC', ['post_id' => $id]), 'asset_id'));
}

$oldPlatforms = old_input('platform_targets', null);
if (is_array($oldPlatforms)) {
    $selectedPlatforms = array_values(array_map(static fn($v): string => strtolower(trim((string) $v)), $oldPlatforms));
}

$oldAssetIds = old_input('asset_ids', null);
if (is_array($oldAssetIds)) {
    $selectedAssetIds = array_map('intval', $oldAssetIds);
}

require __DIR__ . '/_top.php';
?>

<form method="post" action="<?= e(base_url('admin/social_post_save.php')) ?>" data-ai-context="social_post" data-ai-title="<?= e($adminTitle) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card card-soft mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-3">Post Content</h2>
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" maxlength="255" required value="<?= e((string) admin_form_value($record, 'title')) ?>" data-ai-field="title" data-ai-label="Post Title" data-ai-context="Internal title for this social media post draft. Not shown publicly. Should describe the post content clearly for the admin. Keep it short and descriptive.">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="7" class="form-control" data-ai-field="description" data-ai-label="Post Caption / Body" data-ai-context="The main caption or body text for this social media post about Sri Lanka travel. Write for Instagram or Facebook. Use vivid, evocative language. Include a call to action. Use 2-4 short paragraphs. Add 3-5 relevant emojis naturally throughout. Do not use hashtags in the body — those go in the hashtags field."><?= e((string) admin_form_value($record, 'description')) ?></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Reference Destination</label>
                            <?php $selectedDestination = (int) admin_form_value($record, 'reference_destination_id', 0); ?>
                            <select name="reference_destination_id" class="form-select">
                                <option value="">Select destination</option>
                                <?php foreach ($destinations as $destination): ?>
                                    <option value="<?= (int) ($destination['id'] ?? 0) ?>" <?= $selectedDestination === (int) ($destination['id'] ?? 0) ? 'selected' : '' ?>><?= e((string) ($destination['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reference Itinerary</label>
                            <?php $selectedItinerary = (int) admin_form_value($record, 'reference_itinerary_id', 0); ?>
                            <select name="reference_itinerary_id" class="form-select">
                                <option value="">Select itinerary</option>
                                <?php foreach ($itineraries as $itinerary): ?>
                                    <option value="<?= (int) ($itinerary['id'] ?? 0) ?>" <?= $selectedItinerary === (int) ($itinerary['id'] ?? 0) ? 'selected' : '' ?>><?= e((string) ($itinerary['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reference Tour/Experience</label>
                            <?php $selectedTour = (int) admin_form_value($record, 'reference_tour_id', 0); ?>
                            <select name="reference_tour_id" class="form-select">
                                <option value="">Select tour</option>
                                <?php foreach ($tours as $tour): ?>
                                    <option value="<?= (int) ($tour['id'] ?? 0) ?>" <?= $selectedTour === (int) ($tour['id'] ?? 0) ? 'selected' : '' ?>><?= e((string) ($tour['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Custom Experience Name</label>
                        <input type="text" name="experience_name" class="form-control" maxlength="180" value="<?= e((string) admin_form_value($record, 'experience_name')) ?>" placeholder="Optional custom reference">
                    </div>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Attach Media</h2>
                    <p class="small text-secondary">Select media from the library. Use Media Library page to upload and set alt/SEO metadata first.</p>
                    <div class="row g-2" style="max-height:420px;overflow:auto;">
                        <?php foreach ($mediaAssets as $asset): ?>
                            <?php
                            $assetId = (int) ($asset['id'] ?? 0);
                            $assetUrl = stored_media_url((string) ($asset['file_path'] ?? ''));
                            $isSelected = in_array($assetId, $selectedAssetIds, true);
                            $mimeType = strtolower((string) ($asset['mime_type'] ?? ''));
                            ?>
                            <div class="col-md-6 col-xl-4">
                                <label class="border rounded p-2 d-block h-100 <?= $isSelected ? 'border-success' : '' ?>">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="asset_ids[]" value="<?= $assetId ?>" <?= $isSelected ? 'checked' : '' ?>>
                                        <span class="small fw-semibold ms-1">#<?= $assetId ?> <?= e((string) ($asset['file_name'] ?? '')) ?></span>
                                    </div>
                                    <?php if (str_starts_with($mimeType, 'image/')): ?>
                                        <img src="<?= e($assetUrl) ?>" alt="<?= e((string) ($asset['alt_text'] ?? '')) ?>" class="img-fluid rounded" style="max-height:90px;object-fit:cover;">
                                    <?php elseif (str_starts_with($mimeType, 'video/')): ?>
                                        <video src="<?= e($assetUrl) ?>" class="img-fluid rounded" style="max-height:90px;" muted preload="metadata"></video>
                                    <?php else: ?>
                                        <div class="small text-secondary">File preview unavailable.</div>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-soft mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-3">Publishing</h2>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <?php $selectedStatus = (string) admin_form_value($record, 'status', 'draft'); ?>
                        <select name="status" class="form-select">
                            <?php foreach ($statusOptions as $status): ?>
                                <option value="<?= e($status) ?>" <?= $selectedStatus === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Platform Targets</label>
                        <?php foreach ($platformOptions as $platformKey => $platformLabel): ?>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="platform_targets[]" value="<?= e($platformKey) ?>" id="platform_<?= e($platformKey) ?>" <?= in_array($platformKey, $selectedPlatforms, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="platform_<?= e($platformKey) ?>"><?= e($platformLabel) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Hashtags</label>
                        <input type="text" name="hashtags" class="form-control" maxlength="255" value="<?= e((string) admin_form_value($record, 'hashtags')) ?>" placeholder="#srilanka #privatetour" data-ai-field="hashtags" data-ai-label="Hashtags" data-ai-context="Hashtags for this Sri Lanka travel social media post. Format: space-separated, each starting with #. Include a mix of broad (#srilanka #travel) and niche (#srilankaprivatetours #islandculture). 8-12 hashtags total. No commas.">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Scheduled At</label>
                        <?php
                        $scheduledValue = '';
                        $scheduledRaw = (string) admin_form_value($record, 'scheduled_at', '');
                        if ($scheduledRaw !== '') {
                            $scheduledTs = strtotime($scheduledRaw);
                            if ($scheduledTs !== false) {
                                $scheduledValue = date('Y-m-d\TH:i', $scheduledTs);
                            }
                        }
                        ?>
                        <input type="datetime-local" name="scheduled_at" class="form-control" value="<?= e($scheduledValue) ?>">
                    </div>

                    <button type="submit" class="btn btn-success w-100"><?= $id > 0 ? 'Update Draft' : 'Create Draft' ?></button>
                    <a href="<?= e(base_url('admin/social_posts.php')) ?>" class="btn btn-outline-dark w-100 mt-2">Back to Drafts</a>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h6 mb-2">Direct Posting Notes</h2>
                    <p class="small text-secondary mb-0">Blog publishing can be triggered directly from Drafts. Facebook/Instagram/YouTube/Pinterest currently use queued manual export logs unless API credentials are connected in Social Channels.</p>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/_bottom.php'; ?>
