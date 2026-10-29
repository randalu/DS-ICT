<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/page_media_admin.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Robots-Tag: noindex,nofollow');
function page_media_reply(array $data,int $status=200): never {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if(!admin_check())page_media_reply(['error'=>'Sign in again before managing images.'],401);
try {
    if(!is_get()&&!is_post()){header('Allow: GET, POST');page_media_reply(['error'=>'Method not allowed.'],405);}
    if(is_post()){
        require_once __DIR__.'/../includes/image_manager.php';$limit=image_manager_ini_bytes((string)ini_get('post_max_size'));
        if($limit>0 && (int)($_SERVER['CONTENT_LENGTH']??0)>$limit)page_media_reply(['error'=>'Upload exceeds server request limit.'],413);
        if(!csrf_verify(is_string($_POST['_token']??null)?$_POST['_token']:null))page_media_reply(['error'=>'Invalid or expired security token. Reload before saving.'],419);
        foreach($_POST as $value)if(!is_string($value))throw new InvalidArgumentException('Invalid request fields.');
        if(array_diff(array_keys($_FILES),['media']))throw new InvalidArgumentException('Submit one file only.');
        if(isset($_FILES['media']))foreach(['name','tmp_name','error','size'] as $key)if(!isset($_FILES['media'][$key])||!is_scalar($_FILES['media'][$key]))throw new InvalidArgumentException('Invalid upload.');
        page_media_save($_POST,$_FILES['media']??null);
    }
    $input=is_post()?$_POST:$_GET;$type=(string)($input['type']??'');$id=(int)($input['id']??0);page_media_record($type,$id);
    ob_start();page_media_manager($type,$id);$html=ob_get_clean();page_media_reply(['html'=>$html,'ok'=>true]);
}catch(DomainException $e){page_media_reply(['error'=>$e->getMessage()],409);}catch(InvalidArgumentException|JsonException $e){page_media_reply(['error'=>$e->getMessage()],422);}catch(Throwable $e){error_log('Page media: '.$e->getMessage());page_media_reply(['error'=>'Could not save media. Refresh and compare before retrying.'],500);}
