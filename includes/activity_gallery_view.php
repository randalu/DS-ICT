<?php
declare(strict_types=1);
require_once __DIR__ . '/activity_gallery.php';
require_once __DIR__.'/media_alt.php';

function activity_gallery_manager_state(int $entryId): void
{
    $photos=activity_gallery_images($entryId);
    ?>
    <div data-ag-state data-revision="<?= e(activity_gallery_revision($entryId)) ?>" data-count="<?= count($photos) ?>">
    <p><strong><?= count($photos) ?> of 5 images</strong> · First image is the lead photo.</p>
    <?php if(!$photos): ?><p class="im-notice">No entry images yet. Existing public text remains visible; add at least one image before the next published content save.</p><?php endif; ?>
    <div class="im-grid ag-manager-photos">
    <?php foreach($photos as $index=>$photo): ?>
        <form class="im-card" data-ag-form data-photo="<?= (int)$photo['id'] ?>">
            <h4>Image <?= $index+1 ?><?= $index===0?' · Lead':'' ?></h4>
            <img class="ag-manager-preview" src="<?= e(stored_media_url((string)$photo['file_path'])) ?>" alt="<?= e($photo['alt_text']) ?>" loading="lazy">
            <input type="hidden" name="asset_id" value=""><input type="hidden" name="source" value="">
            <label>Alt text<input name="alt_text" required maxlength="255" value="<?= e($photo['alt_text']) ?>"></label>
            <?php media_alt_controls((int)$photo['media_asset_id']); ?>
            <label>Replace from device<input type="file" name="media" accept="image/jpeg,image/png,image/webp,image/avif,image/gif"></label>
            <p data-ag-selection></p>
            <div class="im-actions"><button type="button" data-ag-library>Choose replacement from Media Library</button><button type="submit">Save image / alt text</button><button type="button" data-ag-cancel>Cancel edits</button></div>
            <div class="im-actions ag-manager-actions"><button type="button" data-ag-move="up" <?= $index===0?'disabled':'' ?>>Move up</button><button type="button" data-ag-move="down" <?= $index===count($photos)-1?'disabled':'' ?>>Move down</button><button type="button" data-ag-remove>Remove assignment</button></div>
        </form>
    <?php endforeach; ?>
    </div>
    <?php if(count($photos)<5): ?>
    <form class="im-card ag-manager-add" data-ag-form data-photo="0">
        <h4>Add entry images</h4><p>Upload up to <?= 5-count($photos) ?> photographs or choose one existing image. Each needs its own description.</p>
        <input type="hidden" name="asset_id" value=""><input type="hidden" name="source" value="">
        <label>Upload photographs<input type="file" name="media" multiple accept="image/jpeg,image/png,image/webp,image/avif,image/gif"></label>
        <div data-ag-queue></div><p data-ag-selection></p>
        <label data-ag-alt-label>Image alt text<input name="alt_text" maxlength="255" required></label>
        <?php media_alt_controls(); ?>
        <div class="im-actions"><button type="button" data-ag-library>Choose from Media Library</button><button type="submit">Add selected images</button><button type="button" data-ag-cancel>Cancel selection</button></div>
    </form>
    <?php endif; ?></div>
    <?php
}

function activity_gallery_manager(array $page): void
{
    if(!empty($page['is_hub']))return;
    ?>
    <section class="ag-manager" data-ag-manager data-category="<?= (int)$page['id'] ?>" data-token="<?= e(csrf_token()) ?>">
    <h2>Entry galleries</h2><p>Choose 1–5 relevant photographs for each entry. Saved changes on a published category appear immediately. Page images remain separate above.</p>
    <?php if(!activity_gallery_available()): ?><p class="im-notice">Entry galleries are unavailable until the activity entry galleries migration is applied. Existing content remains visible.</p>
    <?php else: foreach(activity_examples((int)$page['id']) as $entry): ?>
    <section id="entry-<?= (int)$entry['id'] ?>" class="ag-manager-entry" data-ag-entry="<?= (int)$entry['id'] ?>">
    <h3><?= e($entry['title']) ?></h3><p><?= e(implode(' · ',activity_json_list($entry['destinations_json']))) ?></p>
    <button type="button" data-ag-refresh>Refresh saved gallery</button>
    <p data-ag-status role="status" aria-live="polite"></p>
    <ol data-ag-upload-report aria-live="polite"></ol>
    <div data-ag-current><?php activity_gallery_manager_state((int)$entry['id']); ?></div>
    </section>
    <?php endforeach; endif; ?>
    <dialog data-ag-library-dialog aria-labelledby="ag-library-heading"><div class="im-dialog-header"><h2 id="ag-library-heading">Choose an entry image</h2><button type="button" data-ag-library-close>Close</button></div>
    <form data-ag-library-search class="im-search"><label>Search filenames<input type="search" name="q" maxlength="120"></label><button>Search</button></form>
    <p data-ag-library-status role="status"></p><div class="im-library-grid" data-ag-library-items></div><div class="im-pagination"><button type="button" data-ag-library-prev>Previous</button><span data-ag-library-page></span><button type="button" data-ag-library-next>Next</button></div>
    </dialog></section>
    <?php
}

function activity_gallery_public(array $photos,string $title): void
{
    $photos=array_values(array_filter($photos,static fn($p)=>!empty($p['file_path']) && str_starts_with((string)($p['mime_type']??''),'image/') && activity_gallery_file_exists((string)$p['file_path'])));
    if(!$photos)return;
    require_once __DIR__.'/responsive_image.php';
    ?>
    <div class="activity-entry-gallery" data-activity-gallery data-lightbox-ignore aria-label="<?= e($title.' photographs') ?>">
    <div class="activity-entry-stage">
        <?php foreach($photos as $index=>$photo): ?>
        <a href="<?= e(stored_media_url($photo['file_path'])) ?>" data-activity-slide <?= $index?'hidden':'' ?> aria-label="<?= e('Open photograph '.($index+1).' of '.count($photos).': '.$photo['alt_text']) ?>">
            <?= responsive_image(['src'=>$photo['file_path'],'alt'=>$photo['alt_text'],'widths'=>[400,800,1200],'sizes'=>'(min-width: 1200px) 560px, (min-width: 768px) 50vw, 100vw','width'=>900,'height'=>600,'loading'=>'lazy']) ?>
        </a><?php endforeach; ?>
        <?php if(count($photos)>1): ?><span class="activity-entry-counter" data-activity-counter aria-live="polite">1 / <?= count($photos) ?></span><?php endif; ?>
    </div>
    <?php if(count($photos)>1): ?><div class="activity-entry-thumbnails" aria-label="Choose photograph">
        <?php foreach($photos as $index=>$photo): ?><a href="<?= e(stored_media_url($photo['file_path'])) ?>" data-activity-thumb="<?= $index ?>" <?= !$index?'aria-current="true"':'' ?> aria-label="<?= e('Photograph '.($index+1).': '.$photo['alt_text']) ?>"><?= responsive_image(['src'=>$photo['file_path'],'alt'=>'','widths'=>[400],'sizes'=>'100px','width'=>120,'height'=>90,'loading'=>'lazy']) ?></a><?php endforeach; ?>
    </div><?php endif; ?></div>
    <?php
}
