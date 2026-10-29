<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/seo_manager.php';
require_once __DIR__.'/../includes/media_alt.php';
require_once __DIR__.'/../includes/image_alt_context.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
function image_alt_reply(array $data,int $status=200): never { http_response_code($status);echo json_encode($data,JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE);exit; }
if (!admin_check()) { image_alt_reply(['error'=>'Sign in again before generating alt text.'],401); }
unset($_SESSION['_ict_seo_connection']);
try {
    admin_openai_reject_credentials($_GET);
    admin_openai_reject_credentials($_POST);
    if (is_get()) {
        $id=filter_var($_GET['asset_id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        if ($id===false) { throw new InvalidArgumentException('Invalid image identifier.'); }
        $asset=$id ? fetch_one('SELECT id,file_path,alt_text,mime_type FROM media_assets WHERE id=:id',['id'=>$id]) : null;
        if($id && !$asset) { throw new InvalidArgumentException('Image no longer exists.'); }
        $reference=image_alt_context($_GET,(int)$id);
        image_alt_reply(['configured'=>admin_openai_key()!=='','ready'=>db_table_exists('admin_ai_requests')&&db_table_exists('media_placement_alts'),'model'=>admin_openai_model('alt'),'context'=>$reference['references'],'context_revision'=>$reference['revision'],'asset'=>$asset?['id'=>(int)$asset['id'],'alt_text'=>$asset['alt_text'],'revision'=>media_alt_revision($asset)]:null]);
    }
    if (!is_post()) { header('Allow: GET, POST');image_alt_reply(['error'=>'Use POST.'],405); }
    guard_post_size(); require_csrf();
    foreach($_POST as $name=>$value) { if(!is_string($value) || in_array($name,['key','api_key','endpoint'],true)) { throw new InvalidArgumentException('Invalid image request. Credentials are configured on the server.'); } }
    $key=admin_openai_key();if($key==='') { throw new InvalidArgumentException('The server OpenAI key is not configured. Manual alt editing remains available.'); }
    $id=filter_var($_POST['asset_id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);if($id===false){throw new InvalidArgumentException('Invalid image identifier.');}
    $reference=image_alt_context($_POST,(int)$id);
    if(!hash_equals($reference['revision'],(string)($_POST['context_revision']??''))){throw new DomainException('The image reference context changed. Reselect or refresh the image before generating.');}
    $file=$_FILES['media']??null;
    if($file && $id){throw new InvalidArgumentException('Choose one image only.');}
    if($file){
        if(!is_string($file['tmp_name']??null) || ($file['error']??1)!==UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])){throw new InvalidArgumentException('Choose a valid uploaded image.');}
        $path=$file['tmp_name'];
    } elseif($id) {
        $media=image_manager_library_asset((int)$id,false);$path=media_alt_filesystem_path($media['path']);
    } else {
        $type=(string)($_POST['type']??'');$parent=image_manager_record($type,(int)($_POST['id']??0));
        $target=($_POST['page_media']??'')==='1'?page_media_alt_target($type,(int)$parent['id'],(string)($_POST['slot']??''),(int)($_POST['child_id']??0)):image_manager_slot($type,$parent,(string)($_POST['slot']??''),(int)($_POST['child_id']??0));
        if(!hash_equals($target['revision'],(string)($_POST['revision']??''))){throw new DomainException('This assignment changed. Reload before generating.');}
        $path=media_alt_filesystem_path((string)$target['path']);
    }
    if(!$path || !is_file($path) || filesize($path)>image_manager_limit(false)){throw new InvalidArgumentException('Image exceeds the supported upload limit or is missing.');}
    $mime=(string)(new finfo(FILEINFO_MIME_TYPE))->file($path);image_manager_validate_mime($mime,false);
    $size=@getimagesize($path);$memory=image_manager_ini_bytes((string)ini_get('memory_limit'));
    if(!$size || $size[0]*$size[1]>50000000 || ($memory>0 && memory_get_usage(true)+$size[0]*$size[1]*12+16777216>$memory)){throw new InvalidArgumentException('Resize this image before generating alt text.');}
    if(!function_exists('imagecreatefromstring')) { throw new RuntimeException('Image processing is unavailable.'); }
    $original=@imagecreatefromstring(file_get_contents($path));if(!$original){throw new InvalidArgumentException('Image cannot be decoded.');}
    if($mime==='image/jpeg' && function_exists('exif_read_data')){
        $oriented=gd_apply_exif_orientation($original,$path);
        if($oriented && $oriented!==$original){imagedestroy($original);$original=$oriented;}
        $size[0]=imagesx($original);$size[1]=imagesy($original);
    }
    $scale=min(1,1024/max($size[0],$size[1]));$copy=imagecreatetruecolor(max(1,(int)round($size[0]*$scale)),max(1,(int)round($size[1]*$scale)));
    $white=imagecolorallocate($copy,255,255,255);imagefill($copy,0,0,$white);
    imagecopyresampled($copy,$original,0,0,0,0,imagesx($copy),imagesy($copy),$size[0],$size[1]);
    ob_start();imagejpeg($copy,null,82);$bytes=ob_get_clean();imagedestroy($copy);imagedestroy($original);
    $fingerprint=hash_file('sha256',$path);$admin=(int)admin_id();$stamp=(string)$_SESSION[ADMIN_CREDENTIAL_STAMP_KEY];$version=admin_openai_version();
    $request=admin_openai_begin('alt',(string)($_POST['request_token']??''),$admin);$usage=[];
    session_write_close();
    try {
        $response=admin_openai_response($key,image_alt_payload($bytes,$reference['references']));
        $usage=$response['usage'];$result=json_decode($response['text'],true,8,JSON_THROW_ON_ERROR);
        if(!is_array($result)||array_keys($result)!==['alt_text']){throw new RuntimeException('OpenAI returned invalid alt text.');}
        $alt=media_alt_validate($result['alt_text']);seo_revalidate_session($admin,$stamp,$version);
        $latestReference=image_alt_context($_POST,(int)$id);
        if(!hash_equals($reference['revision'],$latestReference['revision'])){throw new DomainException('The assigned destination or entry changed during generation. Refresh and generate a new suggestion.');}
        if(!$file && (!is_file($path)||!hash_equals($fingerprint,hash_file('sha256',$path)))){throw new DomainException('This image changed during generation. Generate a new suggestion.');}
        if(isset($target)){
            $latest=($_POST['page_media']??'')==='1'?page_media_alt_target($type,(int)$parent['id'],(string)$_POST['slot'],(int)($_POST['child_id']??0)):image_manager_slot($type,image_manager_record($type,(int)$parent['id']),(string)$_POST['slot'],(int)($_POST['child_id']??0));
            if(!hash_equals($target['revision'],$latest['revision']))throw new DomainException('This assignment changed during generation. Reload before applying a suggestion.');
        }
        admin_openai_finish($request,'completed',$usage);
        image_alt_reply(['alt_text'=>$alt,'fingerprint'=>$fingerprint,'asset_id'=>(int)$id]);
    } catch(Throwable $e){admin_openai_finish($request,'failed_or_unknown',$usage);throw $e;}
} catch(DomainException $e){image_alt_reply(['error'=>$e->getMessage()],409);}
catch(InvalidArgumentException $e){image_alt_reply(['error'=>$e->getMessage()],422);}
catch(Throwable){image_alt_reply(['error'=>'OpenAI could not finish this suggestion. It may have been billed. No automatic retry was made; manual editing remains available.'],502);}
