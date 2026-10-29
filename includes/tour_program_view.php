<?php
declare(strict_types=1);
$program=tour_program_decode($tour['program_json']??null);
$programImageIds=[];foreach(array_merge($program['days'],$program['hotels']) as $row){if(!empty($row['image_asset_id'])){$programImageIds[]=(int)$row['image_asset_id'];}}
$programImages=[];
if($programImageIds&&db_table_exists('media_assets')){
    $ids=array_values(array_unique($programImageIds));$marks=implode(',',array_fill(0,count($ids),'?'));
    foreach(fetch_all('SELECT id,file_path,mime_type FROM media_assets WHERE id IN ('.$marks.')',$ids) as $asset){if(str_starts_with($asset['mime_type'],'image/')){$programImages[(int)$asset['id']]=stored_media_url($asset['file_path']);}}
}
$programPhoto=static function(array $row)use($programImages):string{return $programImages[(int)($row['image_asset_id']??0)]??'';};
?>
<?php if($program['route']!==''): ?><section class="journey-section" id="tour-route"><span class="section-eyebrow">Your route</span><h2>The journey at a glance</h2><p class="journey-route-plan"><?= nl2br(e($program['route'])) ?></p></section><?php endif; ?>
<?php if($program['days']): ?><section class="journey-section" id="tour-days"><span class="section-eyebrow">A private plan, shaped around you</span><h2>Daily plan and highlights</h2><p>Adjust the stops, nights and experiences with us before confirming your private tour.</p><div class="tour-day-plan">
<?php foreach($program['days'] as $day):$photo=$programPhoto($day);$dayLabel=$day['day_start']===$day['day_end']?'Day '.$day['day_start']:'Days '.$day['day_start'].'–'.$day['day_end']; ?>
<article class="tour-day-card"><div class="tour-day-heading"><span class="journey-coverage-status"><?= e($dayLabel) ?></span><h3><?= e($day['title']) ?></h3><p class="journey-location"><?= e($day['location']) ?></p></div>
<?php if($photo!==''): ?><?= responsive_image(['src'=>$photo,'alt'=>$day['image_alt'],'widths'=>[400,800],'sizes'=>'(min-width:992px) 700px,100vw','width'=>800,'height'=>500,'loading'=>'lazy','class'=>'tour-program-photo']) ?><?php endif; ?>
<p><?= nl2br(e($day['summary'])) ?></p><?php if($day['highlights']): ?><h4>Day highlights</h4><ul><?php foreach($day['highlights'] as $highlight): ?><li><?= e($highlight) ?></li><?php endforeach; ?></ul><?php endif; ?>
<dl class="tour-day-facts"><?php if($day['overnight']!==''): ?><div><dt>Overnight</dt><dd><?= e($day['overnight']) ?></dd></div><?php endif; ?><?php if($day['travel_time']!==''): ?><div><dt>Travel plan</dt><dd><?= e($day['travel_time']) ?></dd></div><?php endif; ?></dl></article>
<?php endforeach; ?></div></section><?php endif; ?>
<?php if($program['hotels']): ?><section class="journey-section" id="tour-hotels"><span class="section-eyebrow">Places to stay</span><h2>Hotels and accommodation</h2><p>These are proposed stays or accommodation areas. The hotel, room, meals and included nights are confirmed in your written quote.</p><div class="tour-hotel-grid">
<?php foreach($program['hotels'] as $hotel):$photo=$programPhoto($hotel); ?>
<article class="tour-hotel-card"><?php if($photo!==''): ?><?= responsive_image(['src'=>$photo,'alt'=>$hotel['image_alt'],'widths'=>[400,800],'sizes'=>'(min-width:992px) 350px,100vw','width'=>800,'height'=>500,'loading'=>'lazy','class'=>'tour-program-photo']) ?><?php endif; ?><div class="tour-hotel-copy"><span class="journey-coverage-status"><?= $hotel['status']==='optional'?'Optional stay':'Proposed stay' ?></span><h3><?= e($hotel['name']) ?></h3><p class="journey-location"><?= e($hotel['location']) ?></p><p><?= nl2br(e($hotel['description'])) ?></p><dl>
<?php foreach(['category'=>'Style','nights'=>'Nights','room_type'=>'Room','board_basis'=>'Meals'] as $field=>$label):if(($hotel[$field]??'')===''||($field==='nights'&&(int)$hotel[$field]===0)){continue;} ?><div><dt><?= $label ?></dt><dd><?= e((string)$hotel[$field]) ?></dd></div><?php endforeach; ?></dl></div></article>
<?php endforeach; ?></div></section><?php endif; ?>
<?php if($program['sections']): ?><section class="journey-section" id="tour-planning"><span class="section-eyebrow">The practical details</span><h2>Plan the details of your Tour</h2><?php foreach($program['sections'] as $section): ?><article class="tour-planning-section"><h3><?= e($section['title']) ?></h3><div class="content-prose"><?= render_public_content(preg_replace('~<(/?)h[1-3](\b[^>]*)>~i','<$1h4$2>',$section['body'])??$section['body']) ?></div></article><?php endforeach; ?></section><?php endif; ?>
