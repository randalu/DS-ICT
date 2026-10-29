<?php
declare(strict_types=1);
require_once __DIR__.'/page_media.php';
require_once __DIR__.'/media_alt.php';
function page_media_ai_controls(string $type,int $id,string $slot,int $child,string $path,string $revision): void {
    $asset=media_alt_asset($path);
    echo csrf_field();
    foreach(['page_media'=>'1','type'=>$type,'id'=>(string)$id,'slot'=>$slot,'child_id'=>(string)$child,'revision'=>$revision] as $key=>$value)echo '<input type="hidden" name="'.e($key).'" value="'.e($value).'">';
    media_alt_controls((int)($asset['id']??0));
}

function page_media_manager(string $type,int $id): void {
    if($id<1){echo '<p>Save this page first to manage its images and gallery.</p>';return;}
    try{$record=page_media_record($type,$id);}catch(InvalidArgumentException $e){echo '<p role="status">'.e($e->getMessage()).'</p>';return;}
    [$table]=page_media_definition($type);$revision=page_media_revision($type,$record);
    ?>
    <section class="pm-manager card card-soft my-4" data-page-media data-type="<?= e($type) ?>" data-id="<?= $id ?>" data-revision="<?= e($revision) ?>" data-token="<?= e(csrf_token()) ?>" data-api="<?= e(base_url('admin/page_media_api.php')) ?>" data-library-api="<?= e(base_url('admin/image_manager_api.php')) ?>">
    <div class="card-body"><h2>Page images and gallery</h2><p>Save each image separately. Gallery removal keeps the original Media Library file. Descriptions apply to this page assignment.</p><button type="button" data-pm-refresh>Refresh saved images</button><p data-pm-status role="status" aria-live="polite"></p>
    <div class="pm-grid">
    <?php foreach(['hero_image'=>'Hero','cover_image'=>'Thumbnail / cover','og_image'=>'OG / social','hero_video_poster'=>'Hero video poster'] as $slot=>$label): if(!db_column_exists($table,$slot))continue;if($slot==='hero_video_poster'&&($record['hero_media_type']??'')!=='video')continue; $description=(string)($record[page_media_alt_field($slot)]??'');$video=$slot==='hero_image'&&($record['hero_media_type']??'')==='video'; ?>
    <form class="pm-card" data-pm-form data-slot="<?= e($slot) ?>"><h3><?= e($label) ?></h3>
    <?php if(!empty($record[$slot])): if($video): ?><video controls preload="none" src="<?= e(stored_media_url($record[$slot])) ?>"></video><?php else: ?><img loading="lazy" src="<?= e(stored_media_url($record[$slot])) ?>" alt="<?= e($description) ?>"><?php endif; endif; ?>
    <input type="hidden" name="action" value="slot"><input type="hidden" name="asset_id"><input type="hidden" name="source" value="description">
    <label>Upload <input type="file" name="media" accept="image/jpeg,image/png,image/webp,image/avif,image/gif<?= $slot==='hero_image'&&db_column_exists($table,'hero_media_type')?',video/mp4,video/webm,video/quicktime':'' ?>"></label><button type="button" data-pm-library>Choose from Media Library</button><span data-pm-selection></span>
    <label>Image description<input name="description" maxlength="255" value="<?= e($description) ?>" required></label><?php if($video){echo '<input type="hidden" name="slot" value="'.e($slot).'">';}else page_media_ai_controls($type,$id,$slot,0,(string)($record[$slot]??''),$revision); ?><button>Save image / description</button><?php if(!empty($record[$slot])): ?><button type="button" data-pm-remove-slot>Remove assignment</button><?php endif; ?></form>
    <?php endforeach; ?></div>
    <h3>Page gallery</h3>
    <?php if(!page_gallery_available($type)): ?><p>Gallery controls require the reviewed page media migration. No schema changes are made here.</p>
    <?php else: $photos=page_gallery_rows($type,$id);if(!$photos): ?><p>No gallery photos assigned. Select relevant photographs manually.</p><?php endif; ?>
    <div class="pm-grid" data-pm-photos><?php foreach($photos as $photo): ?>
    <form class="pm-card" data-pm-form data-photo="<?= (int)$photo['id'] ?>"><img loading="lazy" src="<?= e(stored_media_url($photo['image_path'])) ?>" alt="<?= e($photo['alt_text']) ?>">
    <input type="hidden" name="action" value="description"><input type="hidden" name="asset_id"><input type="hidden" name="source" value="description">
    <label>Upload replacement<input type="file" name="media" accept="image/jpeg,image/png,image/webp,image/avif,image/gif"></label><button type="button" data-pm-library>Choose replacement</button><span data-pm-selection></span><label>Image description<input name="description" maxlength="255" required value="<?= e($photo['alt_text']) ?>"></label><?php page_media_ai_controls($type,$id,'gallery',(int)$photo['id'],$photo['image_path'],$revision); ?><button>Save photo / description</button><button type="button" data-pm-move="up">Move up</button><button type="button" data-pm-move="down">Move down</button><button type="button" data-pm-remove>Remove from gallery</button></form>
    <?php endforeach; ?></div>
    <form class="pm-card" data-pm-form data-add><h3>Add gallery photo</h3><input type="hidden" name="action" value="add"><input type="hidden" name="asset_id"><input type="hidden" name="source" value="upload"><label>Upload photo<input type="file" name="media" accept="image/jpeg,image/png,image/webp,image/avif,image/gif"></label><button type="button" data-pm-library>Choose from Media Library</button><span data-pm-selection></span><label>Image description<input name="description" maxlength="255" required></label><?php page_media_ai_controls($type,$id,'gallery',0,'',$revision); ?><button>Add to gallery</button></form>
    <?php endif; ?>
    <dialog data-pm-dialog aria-label="Choose Media Library image"><button type="button" data-pm-close>Close</button><form data-pm-search><label>Search filenames<input name="q" maxlength="120" type="search"></label><button>Search</button></form><p data-pm-library-status role="status"></p><div class="pm-grid" data-pm-items></div><button type="button" data-pm-prev>Previous</button><button type="button" data-pm-next>Next</button></dialog></div></section>
    <?php
}
function page_media_admin_assets(): void {static $done=false;if($done)return;$done=true;?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/page-media-admin.css')) ?>?v=<?= filemtime(__DIR__.'/../assets/css/page-media-admin.css') ?>"><script defer src="<?= e(base_url('assets/js/page-media-admin.js')) ?>?v=<?= filemtime(__DIR__.'/../assets/js/page-media-admin.js') ?>"></script><script defer src="<?= e(base_url('assets/js/image-alt.js')) ?>?v=<?= filemtime(__DIR__.'/../assets/js/image-alt.js') ?>"></script>
<?php }
