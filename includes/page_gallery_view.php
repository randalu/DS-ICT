<?php
declare(strict_types=1);
require_once __DIR__.'/page_media.php';
require_once __DIR__.'/responsive_image.php';
function page_gallery_public(array $photos,string $title): void {
    $photos=array_values(array_filter($photos,static fn($p)=>trim((string)($p['image_path']??''))!==''));if(!$photos)return;
    $count=count($photos);
    ?>
    <section class="page-gallery-shell" aria-label="<?= e($title.' photographs') ?>" data-page-gallery data-lightbox-ignore>
      <div class="page-gallery-grid" data-count="<?= min(5,$count) ?>">
      <?php foreach($photos as $i=>$photo): ?>
        <a class="page-gallery-tile<?= $i>=5?' page-gallery-extra':'' ?>" href="<?= e(stored_media_url($photo['image_path'])) ?>" data-pg-photo aria-label="<?= e('Open photograph '.($i+1).' of '.$count.': '.$photo['alt_text']) ?>">
        <?= responsive_image(['src'=>$photo['image_path'],'alt'=>$photo['alt_text'],'widths'=>[400,800,1280],'sizes'=>$i===0?'(min-width: 1024px) 50vw, 100vw':'(min-width: 1024px) 25vw, 100vw','width'=>1200,'height'=>800,'loading'=>$i===0?'eager':'lazy','fetchpriority'=>$i===0?'high':'auto']) ?>
        <?php if($i===min(4,$count-1)): ?><span class="page-gallery-all">View all <?= $count ?> photos</span><?php endif; ?>
        </a>
      <?php endforeach; ?>
      </div>
      <div class="page-gallery-mobile" hidden><button type="button" data-pg-prev aria-label="Previous photograph">&#8249;</button><span data-pg-counter aria-live="polite">1 / <?= $count ?></span><button type="button" data-pg-next aria-label="Next photograph">&#8250;</button><button type="button" data-pg-all>View all <?= $count ?> photos</button></div>
      <noscript><p>All gallery photographs:</p><ul><?php foreach($photos as $photo): ?><li><a href="<?= e(stored_media_url($photo['image_path'])) ?>"><?= e($photo['alt_text']?:'Open photograph') ?></a></li><?php endforeach; ?></ul></noscript>
      <dialog class="page-gallery-dialog" aria-label="<?= e($title.' image viewer') ?>"><div class="page-gallery-toolbar"><span data-pg-view-counter aria-live="polite"></span><button type="button" data-pg-close autofocus>Close</button></div><div class="page-gallery-view-stage"><button type="button" data-pg-view-prev aria-label="Previous photograph">&#8249;</button><img data-pg-view-image alt=""><button type="button" data-pg-view-next aria-label="Next photograph">&#8250;</button></div><p data-pg-description></p></dialog>
    </section>
    <?php
}
function page_gallery_assets(): void {static $done=false;if($done)return;$done=true;?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/page-gallery.css')) ?>?v=1"><script defer src="<?= e(base_url('assets/js/page-gallery.js')) ?>?v=1"></script>
<?php }

function page_gallery_video(array $record): void {
    $hero=content_hero_media($record);if($hero['type']!=='video'||$hero['url']==='')return;
    ?><details class="page-gallery-video"><summary>Watch page video</summary><video controls playsinline preload="none" src="<?= e($hero['url']) ?>" <?= $hero['poster_url']!==''?'poster="'.e($hero['poster_url']).'"':'' ?>></video></details><?php
}
