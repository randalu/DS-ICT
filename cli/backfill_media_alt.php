<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/media_alt.php';
$apply=in_array('--apply',$argv,true);
if(!$apply && !in_array('--check',$argv,true)){fwrite(STDERR,"Use --check or --apply.\n");exit(2);}
$candidates=[];$issues=[];
$collect=static function(string $path,string $alt,string $source,int $assetId=0)use(&$candidates,&$issues):void{
    if(trim($alt)===''||trim($path)==='')return;
    try{$alt=media_alt_validate($alt);}catch(InvalidArgumentException){$issues[]=['source'=>$source,'reason'=>'invalid description'];return;}
    $asset=$assetId?fetch_one('SELECT id,file_path,alt_text,mime_type FROM media_assets WHERE id=:id',['id'=>$assetId]):media_alt_asset($path);
    if($asset && media_alt_path_key((string)$asset['file_path'])!==media_alt_path_key($path))$asset=null;
    if(!$asset){$issues[]=['source'=>$source,'reason'=>'missing or ambiguous asset match'];return;}
    $candidates[(int)$asset['id']]['descriptions'][$alt]=true;
};
foreach(['destinations','itineraries','tours','day_tours','activity_pages'] as $table){
    if(!db_table_exists($table)||!db_column_exists($table,'cover_alt')||!db_column_exists($table,'cover_image'))continue;
    $assetColumn=db_column_exists($table,'cover_media_asset_id')?'cover_media_asset_id':'0 AS cover_media_asset_id';
    foreach(fetch_all('SELECT id,cover_image,cover_alt,'.$assetColumn.' FROM '.$table)as $row){$collect((string)$row['cover_image'],(string)$row['cover_alt'],$table.':'.$row['id'],(int)$row['cover_media_asset_id']);}
}
foreach([['destination_photos','image_path','caption'],['day_tour_gallery','image_path','alt_text']]as[$table,$image,$alt]){
    if(!db_table_exists($table)||!db_column_exists($table,$alt))continue;
    foreach(fetch_all('SELECT id,'.$image.','.$alt.' FROM '.$table)as$row){$collect((string)$row[$image],(string)$row[$alt],$table.':'.$row['id']);}
}
if(db_table_exists('activity_example_images')){
    foreach(fetch_all('SELECT g.id,g.media_asset_id,g.alt_text,m.file_path FROM activity_example_images g JOIN media_assets m ON m.id=g.media_asset_id')as$row){$collect((string)$row['file_path'],(string)$row['alt_text'],'activity_entry:'.$row['id'],(int)$row['media_asset_id']);}
}
if(db_table_exists('media_placement_alts')){
    foreach(fetch_all('SELECT * FROM media_placement_alts')as$row){
        try{$target=image_manager_slot($row['content_type'],image_manager_record($row['content_type'],(int)$row['content_id']),$row['slot'],(int)$row['child_id']);if($target['path']===$row['image_path'])$collect($row['image_path'],$row['alt_text'],'placement:'.$row['content_type'].':'.$row['content_id'].':'.$row['slot']);}catch(InvalidArgumentException){/* Removed content has no active placement. */}
    }
}
if(db_table_exists('tours')){
    foreach(['coverage_json','program_json']as$field){
        if(!db_column_exists('tours',$field))continue;
        foreach(fetch_all('SELECT id,'.$field.' FROM tours')as$row){
            $walk=static function(mixed $value)use(&$walk,$collect,$row,$field):void{
                if(!is_array($value))return;
                if(!empty($value['image_alt'])){
                    $assetId=(int)($value['image_asset_id']??0);
                    $asset=$assetId?fetch_one('SELECT file_path FROM media_assets WHERE id=:id',['id'=>$assetId]):null;
                    $path=(string)($asset['file_path']??$value['image_path']??'');
                    if($path!=='')$collect($path,(string)$value['image_alt'],'tours:'.$row['id'].':'.$field,$assetId);
                }
                foreach($value as$item){if(is_array($item))$walk($item);}
            };
            $walk(json_decode((string)$row[$field],true));
        }
    }
}
$filled=0;$eligible=0;$preserved=0;
db()->beginTransaction();
try{
    foreach($candidates as$id=>$candidate){
        $asset=fetch_one('SELECT id,file_path,alt_text,mime_type FROM media_assets WHERE id=:id FOR UPDATE',['id'=>$id]);
        if(!$asset||trim((string)$asset['alt_text'])!==''){$preserved++;continue;}
        $descriptions=array_keys($candidate['descriptions']);
        if(count($descriptions)!==1){$issues[]=['asset_id'=>$id,'reason'=>'conflicting existing descriptions'];continue;}
        $eligible++;if($apply){media_alt_assign($id,$descriptions[0]);$filled++;}
    }
    $apply?db()->commit():db()->rollBack();
}catch(Throwable$e){if(db()->inTransaction())db()->rollBack();throw$e;}
echo json_encode(['mode'=>$apply?'apply':'check','eligible'=>$eligible,'filled'=>$filled,'preserved'=>$preserved,'issues'=>$issues],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
