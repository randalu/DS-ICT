<?php
declare(strict_types=1);
require_once __DIR__.'/media_alt.php';
function image_manager_card(string $type, array $record, array $card): void
{
    $slot = $card['slot'];
    $poster = $slot === 'hero_video_poster';
    $inactive = $poster && ($record['hero_media_type'] ?? '') !== 'video';
    $unavailable = !db_table_exists('media_assets');
    $state = $inactive ? ['revision' => '', 'url' => '', 'media_type' => 'image', 'caption' => '']
        : image_manager_present($type, $record, $slot, $card['child_id']);
    $allowsVideo = $slot === 'hero_image' && in_array($type, ['destination', 'itinerary'], true) && db_column_exists(image_manager_type($type)['table'], 'hero_media_type');
    ?>
    <form class="im-card" data-card data-slot="<?= e($slot) ?>" data-video="<?= $allowsVideo ? '1' : '0' ?>" data-inactive="<?= $inactive ? '1' : '0' ?>" method="post" enctype="multipart/form-data" action="<?= e(base_url('admin/image_manager_api.php')) ?>">
        <h2><?= e($card['label']) ?></h2>
        <div data-current class="im-preview">
            <?php if ($state['url'] !== ''): ?>
                <?php if ($state['media_type'] === 'video'): ?><video controls preload="none" src="<?= e($state['url']) ?>" poster="<?= e($state['poster_url'] ?? '') ?>"></video>
                <?php else: ?><img loading="lazy" src="<?= e($state['url']) ?>" alt="<?= e($state['caption'] ?: $card['label']) ?>"><?php endif; ?>
            <?php else: ?><span><?= $inactive ? 'Choose and save a video hero to enable its poster.' : 'No image assigned' ?></span><?php endif; ?>
        </div>
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="<?= e($type) ?>"><input type="hidden" name="id" value="<?= (int) $record['id'] ?>">
        <input type="hidden" name="slot" value="<?= e($slot) ?>"><input type="hidden" name="child_id" value="<?= $card['child_id'] ?>">
        <input type="hidden" name="revision" value="<?= e($state['revision']) ?>"><input type="hidden" name="source" value=""><input type="hidden" name="asset_id" value="">
        <fieldset <?= $inactive || $unavailable ? 'disabled' : '' ?>>
            <?php if ($slot === 'gallery_add'): ?><input type="file" name="media" hidden><?php else: ?>
            <label class="im-upload">Upload from device<input aria-label="<?= e($card['label']) ?> upload" type="file" name="media" accept="image/jpeg,image/png,image/webp,image/avif,image/gif<?= $allowsVideo ? ',video/mp4,video/webm,video/quicktime' : '' ?>"></label>
            <?php endif; ?>
            <button type="button" data-library>Choose from Media Library</button>
            <div data-pending class="im-pending" hidden></div>
            <label>Image alt text<input name="caption" value="<?= e($state['caption']) ?>" maxlength="<?= in_array($slot, ['gallery', 'gallery_add'], true) ? 300 : 255 ?>" placeholder="Describe what the photo shows"></label>
            <?php media_alt_controls((int)($state['asset_id']??0)); ?>
            <div class="im-actions"><button type="submit" data-save disabled><?= $slot === 'gallery_add' ? 'Add to public gallery' : 'Save this image' ?></button><button type="button" data-cancel disabled>Cancel</button></div>
        </fieldset>
        <progress max="100" value="0" hidden aria-label="Upload progress"></progress>
        <p data-status role="status" aria-live="polite"></p>
    </form>
    <?php
}

function image_manager_gallery_view(array $record): void
{
    $id = (int) $record['id'];
    $photos = image_manager_children('destination', $id);
    ?>
    <div data-gallery-state data-revision="<?= e(image_manager_gallery_revision($id)) ?>">
    <p data-gallery-status role="status" aria-live="polite"></p>
    <button type="button" data-refresh-gallery>Refresh gallery</button>
    <button type="button" data-save-order disabled>Save order</button>
    <div class="im-grid" data-gallery-photos>
    <?php foreach ($photos as $photo): ?>
    <div data-gallery-photo="<?= (int) $photo['id'] ?>">
    <?php image_manager_card('destination', $record, ['slot' => 'gallery', 'label' => 'Gallery photo', 'child_id' => (int) $photo['id']]); ?>
    <div class="im-actions im-gallery-actions">
    <button type="button" data-caption-save>Save caption</button>
    <button type="button" data-move="up" aria-label="Move photo up">Move up</button>
    <button type="button" data-move="down" aria-label="Move photo down">Move down</button>
    <button type="button" data-remove-photo>Remove from gallery</button>
    </div></div>
    <?php endforeach; ?>
    </div>
    <?php if (!$photos): ?><p>No gallery photos yet. Add photos below.</p><?php endif; ?>
    <?php image_manager_card('destination', $record, ['slot' => 'gallery_add', 'label' => 'Select a photo for the public gallery', 'child_id' => 0]); ?>
    </div>
    <?php
}
