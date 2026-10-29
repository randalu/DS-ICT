<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli-server'||!in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)){http_response_code(404);exit;}
$root=dirname(__DIR__,2);$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
header('X-Robots-Tag: noindex, nofollow');
if(str_starts_with((string)$path,'/assets/')&&!str_contains((string)$path,'..')){
    $assetRoot=realpath($root.'/assets');$candidate=realpath($root.$path);
    if($candidate&&str_starts_with($candidate,$assetRoot.DIRECTORY_SEPARATOR)&&is_file($candidate)&&!preg_match('/\.(php|phtml|phar)$/i',$candidate)){return false;}
    http_response_code(404);exit;
}
$rehearsalDir=getenv('ACTIVITY_GALLERY_REHEARSAL_DIR')?:$root.'/output/activity-gallery-smoke';
$manifest=$rehearsalDir.'/fixture.json';
if(!is_file($manifest)){http_response_code(404);exit;}
$fixture=json_decode(file_get_contents($manifest),true,32,JSON_THROW_ON_ERROR);
if($path!=='/activities/'.$fixture['slug']){http_response_code(404);exit;}
require_once $root.'/includes/activity_pages.php';
$page=fetch_one('SELECT id,slug,updated_at FROM activity_pages WHERE id=:id',['id'=>$fixture['page']]);
if(!$page||$page['slug']!==$fixture['slug']||!str_starts_with($page['slug'],'gallery-smoke-')){http_response_code(404);exit;}
db()->beginTransaction();$recordPageView=false;
try{
    execute_query('UPDATE activity_pages SET is_published=1 WHERE id=:id',['id'=>$fixture['page']]);
    $_GET['slug']=$fixture['slug'];ob_start();require $root.'/activity.php';$html=ob_get_clean();
    if(($_GET['nojs']??'')==='1'){$html=preg_replace('~<script\b[^>]*>.*?</script>~is','',$html);}
    if(isset($_GET['focus_entry']) && in_array((int)$_GET['focus_entry'],$fixture['entries'],true)){
        // Focused visual evidence retains the production entry markup and stylesheet.
        // The unfiltered route remains the acceptance check for the complete page.
        $doc=new DOMDocument();$previous=libxml_use_internal_errors(true);$doc->loadHTML($html);libxml_clear_errors();libxml_use_internal_errors($previous);
        $xpath=new DOMXPath($doc);
        foreach($xpath->query('//li[starts-with(@id,"activity-entry-")]') as $node){if($node->getAttribute('id')!=='activity-entry-'.(int)$_GET['focus_entry']){$node->parentNode->removeChild($node);}}
        foreach($xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," activities-hero ")]') as $node){$node->parentNode->removeChild($node);}
        foreach(iterator_to_array($doc->getElementsByTagName('body')->item(0)->childNodes) as $node){if($node instanceof DOMElement&&!in_array(strtolower($node->tagName),['main','script','style'],true)){$node->parentNode->removeChild($node);}}
        $html=$doc->saveHTML();
    }
    echo $html;
}finally{
    if(db()->inTransaction())db()->rollBack();
    // Legacy header/footer schema guards can implicitly commit MySQL transactions.
    // Restore only this explicitly identified synthetic page, even in that case.
    execute_query('UPDATE activity_pages SET is_published=0,updated_at=:stamp WHERE id=:id AND slug=:slug',['stamp'=>$page['updated_at'],'id'=>$fixture['page'],'slug'=>$fixture['slug']]);
}
