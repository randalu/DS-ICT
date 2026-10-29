<?php
declare(strict_types=1);
require_once __DIR__.'/image_manager.php';

function media_alt_validate(mixed $value, int $maximum = 255): string
{
    if (!is_string($value)) { throw new InvalidArgumentException('Alt text must be plain text.'); }
    $value=trim($value);
    if ($value==='' || !mb_check_encoding($value, 'UTF-8') || mb_strlen($value)>$maximum || strip_tags($value)!==$value || preg_match('/[\x00-\x1f\x7f]/u',$value)) { throw new InvalidArgumentException('Describe the image using 1-' . $maximum . ' plain text characters.'); }
    return $value;
}
function media_alt_placement(string $type,int $id,string $slot,int $child,string $path): ?array
{
    if(!db_table_exists('media_placement_alts'))return null;
    return fetch_one('SELECT image_path,alt_text,updated_at FROM media_placement_alts WHERE content_type=:type AND content_id=:id AND slot=:slot AND child_id=:child AND image_path=:path',['type'=>$type,'id'=>$id,'slot'=>$slot,'child'=>$child,'path'=>$path]);
}
function media_alt_save_placement(string $type,int $id,string $slot,int $child,string $path,string $alt): void
{
    if(!in_array($slot,['hero_image','og_image','hero_video_poster','day'],true)||!db_table_exists('media_placement_alts'))return;
    if($alt===''){
        execute_query('DELETE FROM media_placement_alts WHERE content_type=:type AND content_id=:id AND slot=:slot AND child_id=:child',['type'=>$type,'id'=>$id,'slot'=>$slot,'child'=>$child]);return;
    }
    execute_query('INSERT INTO media_placement_alts(content_type,content_id,slot,child_id,image_path,alt_text,updated_at) VALUES(:type,:id,:slot,:child,:path,:alt,NOW()) ON DUPLICATE KEY UPDATE image_path=VALUES(image_path),alt_text=VALUES(alt_text),updated_at=NOW()',['type'=>$type,'id'=>$id,'slot'=>$slot,'child'=>$child,'path'=>$path,'alt'=>media_alt_validate($alt)]);
}
function media_alt_path_key(string $path): string
{
    $path=ltrim(str_replace('\\','/',trim($path)),'/');
    foreach (['assets/uploads/','uploads/'] as $prefix) { if(str_starts_with($path,$prefix)){return substr($path,strlen($prefix));} }
    return $path;
}
function media_alt_asset(string $path,bool $lock=false): ?array
{
    if ($path==='' || !db_table_exists('media_assets')) { return null; }
    $key=media_alt_path_key($path);
    $rows=fetch_all('SELECT id,file_path,alt_text,mime_type FROM media_assets WHERE file_path IN (:plain,:uploads,:assets)'.($lock?' FOR UPDATE':''),['plain'=>$key,'uploads'=>'uploads/'.$key,'assets'=>'assets/uploads/'.$key]);
    return count($rows)===1 ? $rows[0] : null;
}
function media_alt_target_asset(array $parent,array $target,string $slot): ?array
{
    if (!db_table_exists('media_assets')) { return null; }
    $id=(int)($target['row']['_image_asset_id']??($slot==='cover_image'?($parent['cover_media_asset_id']??0):($slot==='hero_image'?($parent['hero_media_asset_id']??0):0)));
    if($id){
        $asset=fetch_one('SELECT id,file_path,alt_text,mime_type FROM media_assets WHERE id=:id',['id'=>$id]);
        if($asset && media_alt_path_key((string)$asset['file_path'])===media_alt_path_key($target['path']))return $asset;
    }
    return media_alt_asset($target['path']);
}
function media_alt_filesystem_path(string $path): ?string
{
    $absolute=uploaded_media_filesystem_path($path);
    if($absolute && is_file($absolute))return $absolute;
    if(!str_starts_with($path,'assets/'))return null;
    $root=realpath(__DIR__.'/../assets');$candidate=realpath(__DIR__.'/../'.$path);
    return $root && $candidate && str_starts_with($candidate,$root.DIRECTORY_SEPARATOR) && is_file($candidate)?$candidate:null;
}
/** Register an explicitly saved legacy image; never modify its bytes. */
function media_alt_ensure_asset(string $path): int
{
    $asset=media_alt_asset($path,true);if($asset)return (int)$asset['id'];
    $key=media_alt_path_key($path);
    $aliases=fetch_one('SELECT COUNT(*) AS n FROM media_assets WHERE file_path IN (:plain,:uploads,:assets)',['plain'=>$key,'uploads'=>'uploads/'.$key,'assets'=>'assets/uploads/'.$key]);
    if((int)($aliases['n']??0)>0)throw new DomainException('This image has ambiguous Media Library records. Resolve those records before saving its default.');
    $absolute=media_alt_filesystem_path($path);if(!$absolute)throw new InvalidArgumentException('The image file is missing. Choose an available image first.');
    $mime=(string)(new finfo(FILEINFO_MIME_TYPE))->file($absolute);image_manager_validate_mime($mime,false);
    execute_query('INSERT INTO media_assets(file_path,file_name,mime_type,file_size,uploaded_by) VALUES(:path,:name,:mime,:size,:admin)',['path'=>$path,'name'=>basename($path),'mime'=>$mime,'size'=>filesize($absolute),'admin'=>admin_id()]);
    return (int)db()->lastInsertId();
}
function media_alt_revision(array $asset): string
{
    $version=db_table_exists('media_asset_alt_revisions') ? (int)(fetch_one('SELECT version FROM media_asset_alt_revisions WHERE media_asset_id=:id',['id'=>$asset['id']])['version']??0) : 0;
    return hash('sha256',json_encode([(int)$asset['id'],(string)$asset['file_path'],(string)($asset['alt_text']??''),$version],JSON_THROW_ON_ERROR));
}
/** Caller owns its content transaction. The asset lock serializes default changes. */
function media_alt_assign(int $id,string $alt,array $input=[]): void
{
    if (!$id || trim($alt)==='') { return; }
    $alt=media_alt_validate($alt);
    $asset=fetch_one('SELECT id,file_path,alt_text,mime_type FROM media_assets WHERE id=:id FOR UPDATE',['id'=>$id]);
    if (!$asset) { throw new DomainException('The selected image no longer exists.'); }
    $explicit=($input['update_asset_alt']??'')==='1';
    if ($explicit && (!is_string($input['asset_alt_revision']??null) || !hash_equals(media_alt_revision($asset),$input['asset_alt_revision']))) { throw new DomainException('The saved image description changed. Reload the image before updating its default.'); }
    if ((string)($asset['alt_text']??'')!=='' && !$explicit) { return; }
    if ((string)($asset['alt_text']??'')===$alt) { return; }
    execute_query('UPDATE media_assets SET alt_text=:alt WHERE id=:id',['alt'=>$alt,'id'=>$id]);
    if(db_table_exists('media_asset_alt_revisions')) { execute_query('INSERT INTO media_asset_alt_revisions(media_asset_id,version,updated_at) VALUES(:id,1,NOW()) ON DUPLICATE KEY UPDATE version=version+1,updated_at=NOW()',['id'=>$id]); }
}
function media_alt_controls(int $assetId=0): void
{
    ?>
    <div data-image-alt data-saved-asset="<?= $assetId ?>">
    <button type="button" data-alt-generate>Generate alt text</button>
    <p data-alt-status role="status" aria-live="polite"></p>
    <p data-alt-context></p>
    <div data-alt-preview hidden><label>Suggested description<textarea data-alt-suggestion maxlength="255" rows="3"></textarea></label><button type="button" data-alt-apply>Apply suggestion</button><button type="button" data-alt-dismiss>Dismiss</button></div>
    <input type="hidden" name="asset_alt_revision" value="">
    <input type="hidden" name="alt_context_revision" value="">
    <label><input type="checkbox" name="update_asset_alt" value="1"> Update saved image alt</label>
    <small>Empty saved descriptions are filled on save. Replacing an existing default requires this option; other page descriptions stay unchanged.</small>
    </div>
    <?php
}
