<?php
declare(strict_types=1);
require_once __DIR__.'/functions.php';

/** Shared page identities. Types remain distinct even when numeric IDs overlap. */
function page_media_types(): array {
    return ['destination'=>['destinations','name','destination_form.php','destinations/'], 'itinerary'=>['itineraries','name','itinerary_form.php','itineraries/'], 'tour'=>['tours','name','tour_form.php','tours/'], 'day_tour'=>['day_tours','name','day_tour_form.php','day-tours/'], 'experience'=>['experiences','title','experience_form.php','experiences/'], 'activity'=>['activity_pages','name','activity_form.php','activities/'], 'blog'=>['blog_posts','title','blog_post_form.php','blog/'], 'page'=>['static_pages','title','page_form.php',''], 'blog_landing'=>['blog_landing','title','blog_settings.php','blog']];
}
function page_media_definition(string $type): array { return page_media_types()[$type] ?? throw new InvalidArgumentException('Unsupported page type.'); }
function page_media_record(string $type,int $id,bool $lock=false): array {
    [$table]=page_media_definition($type);
    if($id<1 || ($type==='blog_landing' && $id!==1) || !db_table_exists($table)) throw new InvalidArgumentException('Page is unavailable. Review the page media migration.');
    $suffix=$lock && db()->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';
    return fetch_one("SELECT * FROM $table WHERE id=:id".$suffix,['id'=>$id]) ?? throw new InvalidArgumentException('Page not found.');
}
function page_gallery_storage(string $type): array {
    page_media_definition($type);
    return match($type) {
        'destination'=>['destination_photos','destination_id','image_path','caption'],
        'experience'=>['experience_gallery','experience_id','image_path','alt_text'],
        'day_tour'=>['day_tour_gallery','day_tour_id','image_path','alt_text'],
        default=>['page_gallery_images','content_id','media_asset_id','alt_text']
    };
}
function page_gallery_available(string $type): bool {
    [$table,$owner,$image,$alt]=page_gallery_storage($type);
    foreach([$owner,$image,$alt,'sort_order'] as $column) if(!db_column_exists($table,$column)) return false;
    return $table!=='page_gallery_images' || db_table_exists('media_assets');
}
function page_gallery_rows(string $type,int $id): array {
    if(!page_gallery_available($type)) return [];
    [$table,$owner,$image,$alt]=page_gallery_storage($type);
    if($table==='page_gallery_images') return fetch_all('SELECT g.id,g.media_asset_id,g.alt_text,g.sort_order,m.file_path AS image_path FROM page_gallery_images g JOIN media_assets m ON m.id=g.media_asset_id WHERE g.content_type=:type AND g.content_id=:id ORDER BY g.sort_order,g.id',['type'=>$type,'id'=>$id]);
    return fetch_all("SELECT id,$image AS image_path,$alt AS alt_text,sort_order FROM $table WHERE $owner=:id ORDER BY sort_order,id",['id'=>$id]);
}
function page_media_revision(string $type,array $record): string {
    return hash_hmac('sha256',json_encode([$type,$record,page_gallery_rows($type,(int)$record['id'])],JSON_THROW_ON_ERROR),csrf_token());
}
/** Read-only AI binding; gallery IDs must belong to the saved page. */
function page_media_alt_target(string $type,int $id,string $slot,int $child=0): array {
    $record=page_media_record($type,$id);$path='';
    if($slot==='gallery'){
        if($child){foreach(page_gallery_rows($type,$id) as $photo)if((int)$photo['id']===$child)$path=(string)$photo['image_path'];
            if($path==='')throw new InvalidArgumentException('Photo does not belong to this page.');}
    }else{
        page_media_alt_field($slot);[$table]=page_media_definition($type);
        if(!db_column_exists($table,$slot))throw new InvalidArgumentException('Image field unavailable.');
        $path=(string)($record[$slot]??'');
    }
    return ['path'=>$path,'revision'=>page_media_revision($type,$record),'row'=>$record];
}
function page_media_transaction(callable $work): mixed {
    $pdo=db();$nested=$pdo->inTransaction();$nested?$pdo->exec('SAVEPOINT page_media'):$pdo->beginTransaction();
    try {$result=$work();$nested?$pdo->exec('RELEASE SAVEPOINT page_media'):$pdo->commit();return $result;}
    catch(Throwable $e){if($pdo->inTransaction()){$nested?$pdo->exec('ROLLBACK TO SAVEPOINT page_media'):$pdo->rollBack();}throw $e;}
}
function page_media_description(mixed $value): string {
    if(!is_string($value) || !mb_check_encoding($value,'UTF-8') || str_contains($value,"\0") || mb_strlen(trim($value))>255 || strip_tags($value)!==$value) throw new InvalidArgumentException('Use a plain text image description of up to 255 characters.');
    $value=trim($value);if($value==='')throw new InvalidArgumentException('Describe what the photograph shows.');return $value;
}
function page_media_save(array $input,?array $file=null): void {
    require_once __DIR__.'/image_manager.php';
    foreach(['id','child_id','asset_id'] as $key)if(isset($input[$key]) && (string)$input[$key]!=='' && !preg_match('/\A(?:0|[1-9][0-9]*)\z/',(string)$input[$key]))throw new InvalidArgumentException('Invalid media identifier.');
    $type=(string)($input['type']??'');$id=(int)($input['id']??0);[$parentTable]=page_media_definition($type);
    $action=(string)($input['action']??'');$created=null;
    if(!in_array($action,['add','replace','description','remove','order','slot'],true))throw new InvalidArgumentException('Unknown media action.');
    [$table,$owner,$image,$alt]=page_gallery_storage($type);
    if($action!=='slot' && !page_gallery_available($type))throw new InvalidArgumentException('Gallery schema is unavailable. Review the migration.');
    if(db()->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql') foreach(array_unique([$parentTable,'media_assets',...($action==='slot'?[]:[$table])]) as $name){
        $engine=fetch_one('SHOW TABLE STATUS WHERE Name=:name',['name'=>$name]);if(strcasecmp((string)($engine['Engine']??''),'InnoDB')!==0)throw new InvalidArgumentException('Media changes require transactional tables.');
    }
    try {page_media_transaction(function()use($input,$file,$type,$id,$action,$parentTable,$table,$owner,$image,$alt,&$created){
        $record=page_media_record($type,$id,true);
        if(!hash_equals(page_media_revision($type,$record),(string)($input['revision']??'')))throw new DomainException('This page or gallery changed. Refresh and compare before saving.');
        $rows=page_gallery_rows($type,$id);$child=(int)($input['child_id']??0);$photo=null;foreach($rows as $row)if((int)$row['id']===$child)$photo=$row;
        $where="$owner=:owner"; $params=['owner'=>$id];if($table==='page_gallery_images'){$where.=' AND content_type=:type';$params['type']=$type;}
        if($action==='order'){
            $order=json_decode((string)($input['order']??''),true,32,JSON_THROW_ON_ERROR);$ids=array_map('intval',array_column($rows,'id'));$sorted=$ids;sort($sorted);
            if(!is_array($order)||!array_is_list($order)||count(array_filter($order,'is_int'))!==count($order))throw new InvalidArgumentException('Invalid gallery order.');
            $given=$order;sort($given);if($given!==$sorted)throw new DomainException('Gallery order must contain every current image once.');
            foreach($order as $i=>$pid)execute_query("UPDATE $table SET sort_order=:sort WHERE id=:photo AND $where",$params+['sort'=>$i,'photo'=>$pid]);return;
        }
        if(in_array($action,['replace','description','remove'],true)&&!$photo)throw new InvalidArgumentException('Photo does not belong to this page.');
        if($action==='remove'){execute_query("DELETE FROM $table WHERE id=:photo AND $where",$params+['photo'=>$child]);return;}
        $slot=(string)($input['slot']??'');
        if($action==='slot' && !in_array($slot,['hero_image','cover_image','og_image','hero_video_poster'],true))throw new InvalidArgumentException('Invalid image slot.');
        if($action==='slot' && !db_column_exists($parentTable,$slot))throw new InvalidArgumentException('Media field is unavailable. Review the migration.');
        $source=(string)($input['source']??'');$hasFile=$file && ($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
        if($action==='description' || ($action==='slot' && $source==='description')){
            if($hasFile || !empty($input['asset_id']))throw new InvalidArgumentException('Description changes do not accept replacement media.');
            $description=page_media_description($input['description']??'');
            page_media_save_asset_alt((string)($action==='description'?$photo['image_path']:($record[$slot]??'')),$description,$input);
            if($action==='description')execute_query("UPDATE $table SET $alt=:alt WHERE id=:photo AND $where",$params+['alt'=>$description,'photo'=>$child]);
            else {$field=page_media_alt_field($slot);if(!db_column_exists($parentTable,$field))throw new InvalidArgumentException('Description field unavailable.');execute_query("UPDATE $parentTable SET $field=:alt WHERE id=:id",['alt'=>$description,'id'=>$id]);}return;
        }
        if($action==='slot' && $source==='remove'){
            $updates=[$slot.'=NULL'];$altField=page_media_alt_field($slot);if(db_column_exists($parentTable,$altField))$updates[]="$altField=NULL";
            if($slot==='cover_image'&&db_column_exists($parentTable,'cover_media_asset_id'))$updates[]='cover_media_asset_id=NULL';
            if($slot==='hero_image')foreach(['hero_media_asset_id=NULL','hero_video_poster=NULL',"hero_media_type='image'"] as $set)if(db_column_exists($parentTable,explode('=',$set)[0]))$updates[]=$set;
            execute_query("UPDATE $parentTable SET ".implode(',',$updates).' WHERE id=:id',['id'=>$id]);return;
        }
        $description=page_media_description($input['description']??'');
        $allowVideo=$action==='slot'&&$slot==='hero_image'&&db_column_exists($parentTable,'hero_media_type');
        if($action==='slot'&&$slot==='hero_video_poster'&&($record['hero_media_type']??'')!=='video')throw new InvalidArgumentException('Choose a video hero before its poster.');
        $asset=(int)($input['asset_id']??0);
        if($source==='upload'&&$hasFile&&$asset===0)$media=image_manager_upload($file,$allowVideo,$created);
        elseif($source==='library'&&!$hasFile&&$asset>0)$media=image_manager_library_asset($asset,$allowVideo);
        else throw new InvalidArgumentException('Choose exactly one upload or library image.');
        require_once __DIR__.'/media_alt.php';
        if($media['media_type']==='image')media_alt_assign((int)$media['asset_id'],$description,$input);
        if($action==='slot'){
            $values=[$slot=>$media['path']];$altField=page_media_alt_field($slot);if(db_column_exists($parentTable,$altField))$values[$altField]=$description;
            if($slot==='hero_image')foreach(['hero_media_type'=>$media['media_type'],'hero_media_asset_id'=>$media['asset_id'],'hero_video_poster'=>null] as $key=>$value)if(db_column_exists($parentTable,$key))$values[$key]=$value;
            if($slot==='cover_image'&&db_column_exists($parentTable,'cover_media_asset_id'))$values['cover_media_asset_id']=$media['asset_id'];
            $sets=[];foreach($values as $key=>$value)$sets[]="$key=:$key";execute_query("UPDATE $parentTable SET ".implode(',',$sets).' WHERE id=:id',$values+['id'=>$id]);return;
        }
        if($type==='destination' && db_column_exists('media_assets','destination_id')){
            $suffix=db()->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';
            $assetRow=fetch_one('SELECT destination_id FROM media_assets WHERE id=:id'.$suffix,['id'=>$media['asset_id']]);
            if(!empty($assetRow['destination_id']) && (int)$assetRow['destination_id']!==$id)throw new InvalidArgumentException('Choose a photograph assigned to this destination or an unassigned library photograph.');
            execute_query('UPDATE media_assets SET destination_id=:destination WHERE id=:id',['destination'=>$id,'id'=>$media['asset_id']]);
        }
        $value=$table==='page_gallery_images'?$media['asset_id']:$media['path'];
        if($action==='replace')execute_query("UPDATE $table SET $image=:image,$alt=:alt WHERE id=:photo AND $where",$params+['image'=>$value,'alt'=>$description,'photo'=>$child]);
        else {
            if(count($rows)>=200)throw new InvalidArgumentException('A page gallery supports up to 200 photos.');
            $columns=[$owner,$image,$alt,'sort_order'];$values=['owner'=>$id,'image'=>$value,'alt'=>$description,'sort'=>($rows?max(array_column($rows,'sort_order'))+1:0)];$holders=[':owner',':image',':alt',':sort'];
            if($table==='page_gallery_images'){$columns[]='content_type';$holders[]=':type';$values['type']=$type;}
            execute_query("INSERT INTO $table (".implode(',',$columns).') VALUES ('.implode(',',$holders).')',$values);
        }
    });}catch(Throwable $e){if($created)delete_uploaded_media($created);throw $e;}
}
function page_media_save_asset_alt(string $path,string $description,array $input): void {
    require_once __DIR__.'/media_alt.php';
    if($path==='')return;
    $asset=media_alt_asset($path,true);
    if($asset){media_alt_assign((int)$asset['id'],$description,$input);return;}
    if(($input['update_asset_alt']??'')==='1')throw new DomainException('Register this image in Media Library and refresh before updating its saved description.');
}
function page_media_alt_field(string $slot): string {return match($slot){'hero_image'=>'hero_alt','cover_image'=>'cover_alt','og_image'=>'og_alt','hero_video_poster'=>'hero_poster_alt',default=>throw new InvalidArgumentException('Invalid slot.')};}
function page_layout(array $record): string {
    if(in_array($record['layout_style']??'',['document','travel'],true))return $record['layout_style'];
    require_once __DIR__.'/policy_content.php';
    return in_array($record['slug']??'',array_column(ict_policy_documents(),'slug'),true)?'document':'travel';
}
function blog_landing_record(): array {
    $defaults=['id'=>1,'title'=>'Sri Lanka Travel Blog','slug'=>'','intro'=>'Tips, guides, and stories from the island.','meta_title'=>'Travel Blog | Sri Lanka Travel Tips & Guides | '.APP_NAME,'meta_description'=>'Sri Lanka travel tips, destination guides, itinerary ideas, and cultural insights from the Island Culture Travels team.','hero_image'=>null,'cover_image'=>null,'og_image'=>null,'cta_title'=>'','cta_text'=>'','is_published'=>1];
    return db_table_exists('blog_landing')?(fetch_one('SELECT * FROM blog_landing WHERE id=1')?:$defaults):$defaults;
}
function page_gallery_schema(array $rows): array {
    return array_map(static fn($p)=>['@type'=>'ImageObject','contentUrl'=>stored_media_url($p['image_path']),'description'=>$p['alt_text']],$rows);
}

function page_media_file_exists(string $path): bool {
    $local=uploaded_media_filesystem_path($path);if($local && is_file($local))return true;
    if(str_starts_with($path,'assets/')){$base=realpath(__DIR__.'/../assets');$file=realpath(__DIR__.'/../'.$path);return $base&&$file&&str_starts_with($file,$base.DIRECTORY_SEPARATOR)&&is_file($file);}
    return false;
}

function page_media_public_url(string $type,array $record): string {
    if($type==='blog_landing')return base_url('blog');
    if($type==='page')return static_page_public_url((string)$record['slug'],(string)($record['canonical_url']??''));
    if($type==='activity'&&!empty($record['is_hub']))return base_url('activities');
    return base_url(page_media_definition($type)[3].$record['slug']);
}
/** Public rows only; scheduled posts stay excluded from lint and sitemaps. */
function page_media_published(string $type): array {
    [$table]=page_media_definition($type);
    if(!db_column_exists($table,'is_published'))return [];
    $where='is_published=1';if($type==='blog'&&db_column_exists($table,'published_at'))$where.=' AND (published_at IS NULL OR published_at<=CURRENT_TIMESTAMP)';
    return fetch_all("SELECT * FROM $table WHERE $where ORDER BY id");
}
