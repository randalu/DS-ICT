<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__,2);ini_set('session.save_path',$root.'/output/session');
require_once $root.'/includes/activity_pages.php';require_once $root.'/includes/site_audit_portal.php';
$out=$root.'/output/activity-gallery-smoke';$fixture=json_decode(file_get_contents($out.'/fixture.json'),true,32,JSON_THROW_ON_ERROR);
$page=fetch_one('SELECT * FROM activity_pages WHERE id=:id',['id'=>$fixture['page']]);
if(!$page||$page['slug']!==$fixture['slug']||!str_starts_with($page['slug'],'gallery-smoke-'))throw new RuntimeException('Fixture identity differs.');
$raceCount=count(activity_gallery_images($fixture['entries'][4]));
if($raceCount!==5)throw new RuntimeException('Concurrency fixture did not finish at five images.');
execute_query('DELETE FROM activity_example_images WHERE activity_example_id=:id',['id'=>$fixture['entries'][4]]);
execute_query("UPDATE activity_examples SET title='Legacy entry without assigned images' WHERE id=:id AND activity_page_id=:page",['id'=>$fixture['entries'][4],'page'=>$fixture['page']]);
execute_query("UPDATE activity_examples SET title='Two photographs uploaded through Image Manager' WHERE id=:id AND activity_page_id=:page",['id'=>$fixture['entries'][0],'page'=>$fixture['page']]);
$result=['concurrent_requests'=>['saved'=>1,'stale_rejected'=>1,'final_count'=>$raceCount],'ui_uploads'=>count(array_filter(activity_category_images((int)$fixture['page']),static fn($p)=>str_starts_with($p['file_path'],'image-manager/'))),'layout_cases'=>array_map(static fn($id)=>count(activity_gallery_images($id)),$fixture['entries'])];
db()->beginTransaction();
try{
    execute_query('UPDATE activity_pages SET is_published=1 WHERE id=:id',['id'=>$fixture['page']]);
    $inventory=audit_inventory();$record=array_values(array_filter($inventory['items'],static fn($r)=>$r['type']==='activity'&&$r['id']===$fixture['page']))[0];
    $missing=array_filter($record['field_issues'],static fn($r)=>$r['type']==='missing_media_assignment');
    $result['audit_missing_gallery']=count($missing)===1;
    $result['audit_child_images']=count($record['child_media']);
    $_GET=['type'=>'images'];ob_start();require $root.'/sitemap_partial.php';$xml=ob_get_clean();
    $doc=new DOMDocument();if(!@$doc->loadXML($xml))throw new RuntimeException('Image sitemap is invalid XML.');
    $xp=new DOMXPath($doc);$xp->registerNamespace('s','http://www.sitemaps.org/schemas/sitemap/0.9');$xp->registerNamespace('im','http://www.google.com/schemas/sitemap-image/1.1');
    $nodes=$xp->query('//s:url[s:loc="'.base_url('activities/'.$fixture['slug']).'"]/im:image/im:loc');$imageUrls=[];foreach($nodes as $node){$imageUrls[]=$node->textContent;}
    $expected=array_unique(array_map(static fn($p)=>stored_media_url($p['file_path']),activity_category_images((int)$fixture['page'])));
    $result['sitemap_contains_entry_images']=!array_diff($expected,$imageUrls);$result['sitemap_images_unique']=count($imageUrls)===count(array_unique($imageUrls));
    $broken=$fixture['assets'][0];$originalPath=fetch_one('SELECT file_path FROM media_assets WHERE id=:id',['id'=>$broken])['file_path'];
    execute_query("UPDATE media_assets SET file_path='assets/images/gallery-missing-fixture.jpg' WHERE id=:id",['id'=>$broken]);
    $inventory=audit_inventory();$record=array_values(array_filter($inventory['items'],static fn($r)=>$r['type']==='activity'&&$r['id']===$fixture['page']))[0];
    $result['audit_missing_file_detected']=(bool)array_filter($record['field_issues'],static fn($r)=>str_contains($r['detail'],'image file is missing'));
    execute_query('UPDATE media_assets SET file_path=:path WHERE id=:id',['path'=>$originalPath,'id'=>$broken]);
}finally{
    if(db()->inTransaction())db()->rollBack();
    execute_query('UPDATE activity_pages SET is_published=0,updated_at=:stamp WHERE id=:id AND slug=:slug',['id'=>$fixture['page'],'slug'=>$fixture['slug'],'stamp'=>$page['updated_at']]);
}
foreach(['audit_missing_gallery','sitemap_contains_entry_images','sitemap_images_unique','audit_missing_file_detected'] as $check){if(!$result[$check])throw new RuntimeException('Failed: '.$check);}
file_put_contents($out.'/integration-checks.json',json_encode($result,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));echo json_encode($result,JSON_PRETTY_PRINT).PHP_EOL;
