<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function activity_gallery_available(): bool
{
    return db_table_exists('activity_example_images') && db_table_exists('media_assets');
}

function activity_gallery_images(int $entryId): array
{
    if (!activity_gallery_available()) { return []; }
    return fetch_all('SELECT g.*, m.file_path, m.mime_type FROM activity_example_images g
        LEFT JOIN media_assets m ON m.id=g.media_asset_id WHERE g.activity_example_id=:id ORDER BY g.sort_order,g.id', ['id'=>$entryId]);
}

function activity_category_images(int $pageId): array
{
    if (!activity_gallery_available()) { return []; }
    return fetch_all('SELECT g.*,m.file_path,m.mime_type,e.activity_page_id FROM activity_example_images g
        JOIN activity_examples e ON e.id=g.activity_example_id LEFT JOIN media_assets m ON m.id=g.media_asset_id
        WHERE e.activity_page_id=:id ORDER BY e.sort_order,e.id,g.sort_order,g.id', ['id'=>$pageId]);
}

function activity_gallery_revision(int $entryId): string
{
    $entry = fetch_one('SELECT * FROM activity_examples WHERE id=:id', ['id'=>$entryId]);
    return hash('sha256', json_encode([$entry, activity_gallery_images($entryId)], JSON_THROW_ON_ERROR));
}

function activity_content_revision(int $pageId): string
{
    if ($pageId < 1) { return ''; }
    return hash('sha256', json_encode([
        fetch_one('SELECT * FROM activity_pages WHERE id=:id', ['id'=>$pageId]),
        fetch_all('SELECT * FROM activity_examples WHERE activity_page_id=:id ORDER BY sort_order,id', ['id'=>$pageId]),
        activity_category_images($pageId),
    ], JSON_THROW_ON_ERROR));
}

function activity_gallery_entry(int $pageId, int $entryId): array
{
    return fetch_one('SELECT * FROM activity_examples WHERE id=:entry AND activity_page_id=:page', ['entry'=>$entryId,'page'=>$pageId])
        ?? throw new InvalidArgumentException('This activity entry does not belong to the selected category.');
}

function activity_gallery_alt(mixed $value): string
{
    if (!is_string($value)) { throw new InvalidArgumentException('Enter a plain text image description.'); }
    $alt=trim($value);
    if ($alt==='' || mb_strlen($alt)>255 || strip_tags($alt)!==$alt || str_contains($alt,"\0")) {
        throw new InvalidArgumentException('Image alt text is required: up to 255 plain text characters.');
    }
    return $alt;
}

function activity_gallery_id(mixed $value,bool $allowZero=false): int
{
    if($allowZero && ($value===''||$value===null))return 0;
    $id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>$allowZero?0:1]]);
    if($id===false)throw new InvalidArgumentException('Invalid activity gallery identifier.');
    return $id;
}

/** Parent lock also serializes entry reconciliation and all Image Manager saves. */
function activity_gallery_save(array $input, ?array $file=null): void
{
    require_once __DIR__ . '/image_manager.php';
    require_once __DIR__.'/media_alt.php';
    if (!activity_gallery_available()) { throw new InvalidArgumentException('Apply the activity entry galleries migration first.'); }
    if (($input['type']??'')!=='activity') { throw new InvalidArgumentException('Choose an activity category.'); }
    $pageId=activity_gallery_id($input['id']??0); $entryId=activity_gallery_id($input['example_id']??0); $photoId=activity_gallery_id($input['photo_id']??0,true);
    $action=(string)($input['action']??'');
    if (!in_array($action,['activity_gallery_add','activity_gallery_replace','activity_gallery_alt','activity_gallery_order','activity_gallery_remove'],true)) {
        throw new InvalidArgumentException('Unsupported activity gallery action.');
    }
    if(in_array($action,['activity_gallery_add','activity_gallery_order'],true) && $photoId!==0){throw new InvalidArgumentException('Do not submit an existing photo ID for this action.');}
    $pdo=db(); $nested=$pdo->inTransaction(); $createdPath=null;
    $nested ? $pdo->exec('SAVEPOINT activity_gallery_save') : $pdo->beginTransaction();
    try {
        $parent=image_manager_record('activity',$pageId,true);
        if (!empty($parent['is_hub'])) { throw new InvalidArgumentException('The hub has page images only. Choose a category entry.'); }
        activity_gallery_entry($pageId,$entryId);
        if (!is_string($input['revision']??null) || !hash_equals(activity_gallery_revision($entryId),$input['revision'])) {
            throw new DomainException('This entry or its gallery changed. Refresh and compare before saving.');
        }
        $photos=activity_gallery_images($entryId);
        $photo=null; foreach($photos as $row){if((int)$row['id']===$photoId){$photo=$row;}}
        if ($action!=='activity_gallery_add' && $action!=='activity_gallery_order' && !$photo) { throw new InvalidArgumentException('Choose an image belonging to this entry.'); }
        $hasFile=$file && ($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
        $assetId=activity_gallery_id($input['asset_id']??0,true);
        if (!in_array($action,['activity_gallery_add','activity_gallery_replace'],true) && ($hasFile || $assetId)) {
            throw new InvalidArgumentException('Metadata actions do not accept replacement media.');
        }
        if ($action==='activity_gallery_order') {
            try {$order=json_decode((string)($input['order']??''),true,16,JSON_THROW_ON_ERROR);}catch(JsonException){throw new InvalidArgumentException('Invalid image order.');}
            $ids=array_map(static fn($r)=>(int)$r['id'],$photos);
            if (!is_array($order) || !array_is_list($order) || count($order)!==count($ids) || array_filter($order,static fn($v)=>!is_int($v))) { throw new InvalidArgumentException('Order must contain each image ID once.'); }
            $sorted=$order;sort($sorted);$expected=$ids;sort($expected);
            if($sorted!==$expected){throw new InvalidArgumentException('Order must contain each image ID once.');}
            foreach($order as $index=>$imageId){execute_query('UPDATE activity_example_images SET sort_order=:sort WHERE id=:id AND activity_example_id=:entry',['sort'=>$index,'id'=>$imageId,'entry'=>$entryId]);}
        } elseif ($action==='activity_gallery_remove') {
            if (!empty($parent['is_published']) && count($photos)<=1) { throw new InvalidArgumentException('A published entry needs at least one image. Make its category a draft before removing the final image.'); }
            if(!empty($parent['is_published']) && !array_filter($photos,static fn($p)=>(int)$p['id']!==$photoId && activity_gallery_file_exists((string)$p['file_path']))){throw new InvalidArgumentException('A published entry must retain at least one available image. Replace the missing photographs first.');}
            execute_query('DELETE FROM activity_example_images WHERE id=:id AND activity_example_id=:entry',['id'=>$photoId,'entry'=>$entryId]);
        } elseif ($action==='activity_gallery_alt') {
            $alt=activity_gallery_alt($input['alt_text']??'');
            media_alt_assign((int)$photo['media_asset_id'],$alt,$input);
            execute_query('UPDATE activity_example_images SET alt_text=:alt WHERE id=:id AND activity_example_id=:entry',['alt'=>$alt,'id'=>$photoId,'entry'=>$entryId]);
        } else {
            $alt=activity_gallery_alt($input['alt_text']??'');
            if($action==='activity_gallery_add' && count($photos)>=5){throw new InvalidArgumentException('Each entry can have at most five images. Replace or remove one first.');}
            $source=(string)($input['source']??'');
            if(!(($source==='upload' && $hasFile && !$assetId)||($source==='library' && !$hasFile && $assetId>0))){throw new InvalidArgumentException('Choose one upload or one Media Library image.');}
            $media=$source==='upload'?image_manager_upload($file,false,$createdPath):image_manager_library_asset($assetId,false);
            media_alt_assign((int)$media['asset_id'],$alt,$input);
            if($source==='upload' && db_column_exists('media_assets','alt_text')){
                execute_query('UPDATE media_assets SET alt_text=:alt WHERE id=:id',['alt'=>$alt,'id'=>$media['asset_id']]);
            }
            foreach($photos as $existing){if((int)$existing['id']!==$photoId && ((int)$existing['media_asset_id']===$media['asset_id'] || $existing['file_path']===$media['path'])){throw new InvalidArgumentException('This image is already assigned to the entry.');}}
            if($action==='activity_gallery_replace'){
                execute_query('UPDATE activity_example_images SET media_asset_id=:asset,alt_text=:alt WHERE id=:id AND activity_example_id=:entry',['asset'=>$media['asset_id'],'alt'=>$alt,'id'=>$photoId,'entry'=>$entryId]);
            }else{
                $sort=$photos?max(array_column($photos,'sort_order'))+1:0;
                execute_query('INSERT INTO activity_example_images(activity_example_id,media_asset_id,alt_text,sort_order) VALUES(:entry,:asset,:alt,:sort)',['entry'=>$entryId,'asset'=>$media['asset_id'],'alt'=>$alt,'sort'=>$sort]);
            }
        }
        execute_query('UPDATE activity_pages SET updated_at=CURRENT_TIMESTAMP WHERE id=:id',['id'=>$pageId]);
        $nested ? $pdo->exec('RELEASE SAVEPOINT activity_gallery_save') : $pdo->commit();
    } catch(Throwable $error) {
        if($pdo->inTransaction()){$nested?$pdo->exec('ROLLBACK TO SAVEPOINT activity_gallery_save'):$pdo->rollBack();}
        if($createdPath){delete_uploaded_media($createdPath);}
        throw $error;
    }
}

function activity_gallery_reference_count(int $assetId, string $path=''): int
{
    $count=0;
    if(activity_gallery_available()){$count+=(int)(fetch_one('SELECT COUNT(*) AS total FROM activity_example_images WHERE media_asset_id=:id',['id'=>$assetId])['total']??0);}
    if(db_table_exists('activity_pages')){
        $count+=(int)(fetch_one('SELECT COUNT(*) AS total FROM activity_pages WHERE cover_media_asset_id=:id OR (:path<>\'\' AND (hero_image=:hero OR cover_image=:cover OR og_image=:og))',['id'=>$assetId,'path'=>$path,'hero'=>$path,'cover'=>$path,'og'=>$path])['total']??0);
    }
    return $count;
}

function activity_gallery_file_exists(string $path): bool
{
    $absolute=uploaded_media_filesystem_path($path);
    if($absolute && is_file($absolute))return true;
    if(!str_starts_with($path,'assets/'))return false;
    $root=realpath(__DIR__.'/../assets');$candidate=realpath(__DIR__.'/../'.$path);
    return $root && $candidate && str_starts_with($candidate,$root.DIRECTORY_SEPARATOR) && is_file($candidate);
}
