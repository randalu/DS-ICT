<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();

function admin_media_picker(string $field, string $label, array $record = [], array $options = []): void
{
    $allowVideo = !empty($options['allow_video']);
    $path = trim((string) ($record[$field] ?? ''));
    $assetId = (int) ($record[$field . '_media_asset_id'] ?? 0);
    $mediaType = trim((string) ($record['hero_media_type'] ?? 'image'));
    $poster = trim((string) ($record['hero_video_poster'] ?? ''));
    $accept = $allowVideo ? 'image/*,video/mp4,video/webm,video/quicktime' : 'image/*';
    $previewUrl = $path !== '' ? stored_media_url($path) : '';
    ?>
    <div class="admin-media-picker mb-3" data-media-picker data-field="<?= e($field) ?>" data-allow-video="<?= $allowVideo ? '1' : '0' ?>">
        <label class="form-label"><?= e($label) ?></label>
        <?php if ($previewUrl !== ''): ?>
            <div class="admin-media-preview mb-2">
                <?php if ($mediaType === 'video'): ?>
                    <video src="<?= e($previewUrl) ?>" poster="<?= e($poster !== '' ? stored_media_url($poster) : '') ?>" muted controls preload="metadata"></video>
                <?php else: ?>
                    <img src="<?= e($previewUrl) ?>" alt="">
                <?php endif; ?>
            </div>
            <div class="form-text mb-2">Current: <code><?= e($path) ?></code></div>
        <?php endif; ?>

        <input type="hidden" name="<?= e($field) ?>_media_asset_id" value="<?= $assetId > 0 ? $assetId : '' ?>" data-media-asset-id>

        <div class="admin-media-tabs">
            <label class="admin-media-tab">
                <span>Upload</span>
                <input type="file" name="<?= e($field) ?>" class="form-control" accept="<?= e($accept) ?>">
            </label>
            <label class="admin-media-tab">
                <span>Choose from Media Library</span>
                <button type="button" class="btn btn-outline-dark btn-sm" data-media-open>Choose media</button>
                <span class="small text-secondary" data-media-selected><?= $assetId > 0 ? 'Selected media ID ' . $assetId : 'No media selected' ?></span>
            </label>
            <label class="admin-media-tab">
                <span>Import from URL</span>
                <input type="url" name="<?= e($field) ?>_url" class="form-control" placeholder="https://example.com/image.jpg">
            </label>
        </div>

        <?php if ($allowVideo): ?>
            <input type="hidden" name="<?= e($field) ?>_current_media_type" value="<?= e($mediaType) ?>">
            <div class="mt-2">
                <label class="form-label small mb-1">Video poster image</label>
                <input type="file" name="<?= e($field) ?>_poster" class="form-control" accept="image/*">
                <?php if ($poster !== ''): ?>
                    <div class="form-text">Current poster: <code><?= e($poster) ?></code></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
