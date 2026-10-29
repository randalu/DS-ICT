<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/page_media_bootstrap.php';require_once __DIR__.'/../../includes/page_media.php';require_once __DIR__.'/../../includes/media_alt.php';
$out=__DIR__.'/../../output/page-gallery-20261028';$jar=$out.'/synthetic-cookies.txt';$base=APP_URL;$checks=[];
function pm_http(string $path,string $method='GET',?array $fields=null,bool $cookies=true):array {global $jar,$base;$curl=curl_init($base.$path);$options=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>60,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_FOLLOWLOCATION=>false];if($cookies){$options[CURLOPT_COOKIEFILE]=$jar;$options[CURLOPT_COOKIEJAR]=$jar;}if($fields!==null)$options[CURLOPT_POSTFIELDS]=$fields;curl_setopt_array($curl,$options);$body=curl_exec($curl);$code=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_error($curl);curl_close($curl);if($body===false)throw new RuntimeException($error);return [$code,$body];}
function pm_check(bool $ok,string $label):void {global $checks;if(!$ok)throw new RuntimeException('FAIL: '.$label);$checks[]=$label;}
function pm_state():array {[$code,$body]=pm_http('/admin/page_media_api.php?type=blog&id=101');pm_check($code===200,'Authenticated media state loads');$data=json_decode($body,true,32,JSON_THROW_ON_ERROR);$dom=new DOMDocument();@$dom->loadHTML($data['html']);$xp=new DOMXPath($dom);$root=$xp->query('//*[@data-page-media]')->item(0);if(!$root instanceof DOMElement)throw new RuntimeException('Media panel was not rendered.');return ['revision'=>$root->getAttribute('data-revision'),'_token'=>$root->getAttribute('data-token'),'type'=>'blog','id'=>'101'];}
$uploaded=null;
try{
    [$code]=pm_http('/admin/page_media_api.php?type=blog&id=101','GET',null,false);pm_check($code===401,'Guest rejected with 401');
    [$code]=pm_http('/admin/image_alt_api.php','GET',null,false);pm_check($code===401,'Image alt generation rejects guests');
    [$code]=pm_http('/__fixture-admin');pm_check($code===302,'Synthetic admin session established');$state=pm_state();
    [$code]=pm_http('/admin/page_media_api.php','PUT');pm_check($code===405,'Unsafe method rejected with 405');
    [$code]=pm_http('/admin/page_media_api.php','POST',array_replace($state,['_token'=>'invalid','action'=>'add']));pm_check($code===419,'Invalid CSRF rejected with 419');
    $photo=page_gallery_rows('blog',101)[0];$input=$state+['action'=>'description','child_id'=>(string)$photo['id'],'description'=>'Updated through authenticated HTTP '.bin2hex(random_bytes(4))];
    [$code]=pm_http('/admin/page_media_api.php','POST',$input);pm_check($code===200,'Description saved through authenticated HTTP');
    [$code]=pm_http('/admin/page_media_api.php','POST',$input);pm_check($code===409,'Stale HTTP save rejected with 409');
    $input=pm_state()+['action'=>'description','child_id'=>'999999','description'=>'Foreign photo'];[$code]=pm_http('/admin/page_media_api.php','POST',$input);pm_check($code===422,'Foreign photograph rejected with 422');
    $before=page_gallery_rows('blog',101);$input=pm_state()+['action'=>'add','source'=>'upload','asset_id'=>'0','description'=>'An uploaded landscape for synthetic verification','media'=>new CURLFile(__DIR__.'/../../assets/images/island_culture_travels_logo.png','image/png','fixture-logo.png')];
    [$code,$body]=pm_http('/admin/page_media_api.php','POST',$input);pm_check($code===200,'Real multipart upload succeeds');
    $after=page_gallery_rows('blog',101);$new=array_values(array_filter($after,static fn($p)=>!in_array($p['id'],array_column($before,'id'))));pm_check(count($new)===1,'Upload adds exactly one gallery assignment');$uploaded=$new[0];
    pm_check(page_media_file_exists($uploaded['image_path']),'Uploaded original exists');
    $input=pm_state()+['action'=>'remove','child_id'=>(string)$uploaded['id']];[$code]=pm_http('/admin/page_media_api.php','POST',$input);pm_check($code===200&&page_media_file_exists($uploaded['image_path']),'Removing assignment retains uploaded file');
    [$code,$xml]=pm_http('/sitemap-images.xml');$dom=new DOMDocument();pm_check($code===200&&$dom->loadXML($xml),'Image sitemap remains valid XML');$xp=new DOMXPath($dom);$xp->registerNamespace('s','http://www.sitemaps.org/schemas/sitemap/0.9');$xp->registerNamespace('i','http://www.google.com/schemas/sitemap-image/1.1');pm_check($xp->query('//i:image')->length>=40,'Gallery photographs included in image sitemap');
    foreach(page_media_types() as $type=>[$table,$title,$editor]){
        $record=page_media_record($type,$type==='blog_landing'?1:101);$route=parse_url(page_media_public_url($type,$record),PHP_URL_PATH);[$code,$html]=pm_http($route);
        $dom=new DOMDocument();@$dom->loadHTML($html);$xp=new DOMXPath($dom);$gallery=$xp->query('//*[@data-page-gallery]')->item(0);
        pm_check($code===200 && $gallery!==null && $xp->query('//h1')->length===1,$type.' public route retains one heading and a gallery');
        $marker=strpos($html,'data-page-gallery');$heroMarker=match($type){'destination'=>'class="rd-hero"','itinerary'=>'class="itin-detail-hero"','tour'=>'class="journey-hero"','day_tour'=>'class="dt-detail-hero"','experience'=>'class="exp-detail-hero"','activity'=>'class="activities-hero activities-hero-detail"','page'=>'class="py-5 bg-light border-bottom"',default=>'class="page-hero-banner'};
        pm_check(strpos($html,$heroMarker)!==false && strpos($html,$heroMarker)<$marker,$type.' retains hero before the lower-page gallery');
        [$adminCode,$adminHtml]=pm_http('/admin/'.$editor.'?id='.($type==='blog_landing'?1:101));pm_check($adminCode===200 && str_contains($adminHtml,'data-page-media'),$type.' full editor exposes shared media controls');
        pm_check(str_contains($adminHtml,'data-alt-generate') && str_contains($adminHtml,'data-alt-apply'),$type.' editor retains Generate alt and Apply suggestion controls');
        $asset=(int)(media_alt_asset((string)$record['hero_image'])['id']??0);
        [$contextCode,$contextBody]=pm_http('/admin/image_alt_api.php?'.http_build_query(['page_media'=>'1','type'=>$type,'id'=>$record['id'],'slot'=>'hero_image','child_id'=>0,'asset_id'=>$asset]));
        $context=json_decode($contextBody,true,32,JSON_THROW_ON_ERROR);
        pm_check($contextCode===200 && ($context['context']['country']??'')==='Sri Lanka' && ($context['context']['page']??'')===$record[$title],$type.' AI references use saved page and Sri Lanka context');
    }
    file_put_contents($out.'/http-checks.json',json_encode($checks,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));echo count($checks)." authenticated HTTP checks passed.\n";
}finally{
    if($uploaded){execute_query('DELETE FROM page_gallery_images WHERE media_asset_id=:id',['id'=>$uploaded['media_asset_id']]);execute_query('DELETE FROM media_assets WHERE id=:id',['id'=>$uploaded['media_asset_id']]);delete_uploaded_media($uploaded['image_path']);}
    if(is_file($jar))unlink($jar);
}
