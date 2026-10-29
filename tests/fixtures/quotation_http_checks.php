<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DB_NAME')!=='ict_quotation_rehearsal_20261023'){exit(2);}
$base='http://127.0.0.1:8153';$cookies=[];$checks=[];
$request=static function(string $path,?array $body=null,bool $authenticated=true)use($base,&$cookies):array{
    $handle=curl_init($base.$path);$headers=['Accept: application/json'];if($body!==null){$headers[]='Content-Type: application/json';curl_setopt($handle,CURLOPT_POST,true);curl_setopt($handle,CURLOPT_POSTFIELDS,json_encode($body,JSON_THROW_ON_ERROR));}
    if($authenticated&&$cookies){$headers[]='Cookie: '.implode('; ',$cookies);}
    curl_setopt_array($handle,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>20,CURLOPT_HEADERFUNCTION=>static function($handle,$line)use(&$cookies){if(preg_match('/^Set-Cookie:\s*([^;]+)/i',$line,$m)){$parts=explode('=',$m[1],2);$cookies[$parts[0]]=$m[1];}return strlen($line);}]);
    $text=curl_exec($handle);$status=curl_getinfo($handle,CURLINFO_RESPONSE_CODE);curl_close($handle);return [$status,$text];
};
$assert=static function(bool $ok,string $label)use(&$checks):void{if(!$ok){throw new RuntimeException($label);}$checks[]=$label;};
[$code]=$request('/admin/quotation_api.php?action=lookup&kind=activity',null,false);$assert($code===401,'Unauthenticated lookup rejected');
[$code]=$request('/admin/quotation_edit.php?id=1',null,false);$assert($code===302,'Unauthenticated editor redirected');
[$code]=$request('/admin/quotation_pdf.php',null,false);$assert($code===302,'Unauthenticated PDF rejected');
$request('/__quotation_fixture');[$code,$html]=$request('/admin/quotations.php');$assert($code===200,'Authenticated listing renders');
preg_match('~<script type="application/json" id="quote-config">(.*?)</script>~s',(string)$html,$match);$config=json_decode($match[1]??'',true,32,JSON_THROW_ON_ERROR);$token=$config['csrf'];
[$code]=$request('/admin/quotation_api.php',['action'=>'create','csrf_token'=>'invalid']);$assert($code===419,'Missing or invalid CSRF rejected');
[$code]=$request('/admin/quotation_api.php');$assert($code===405,'GET cannot mutate quotations');
[$code]=$request('/admin/quotation_pdf.php');$assert($code===405,'PDF export requires POST');
[$code,$result]=$request('/admin/quotation_api.php',['action'=>'create','csrf_token'=>$token]);$data=json_decode((string)$result,true);$assert($code===200&&($data['id']??0)>0,'Authenticated draft creation succeeds');$id=$data['id'];
[$code,$html]=$request('/admin/quotation_edit.php?id='.$id);$assert($code===200,'Created editor renders');preg_match('~<script type="application/json" id="quote-config">(.*?)</script>~s',(string)$html,$match);$editor=json_decode($match[1],true,64,JSON_THROW_ON_ERROR);
$payload=['action'=>'save','csrf_token'=>$token,'id'=>$id,'version'=>1,'step'=>2,'document'=>$editor['document']];[$code]=$request('/admin/quotation_api.php',$payload);$assert($code===200,'Incomplete draft can save');[$code]=$request('/admin/quotation_api.php',$payload);$assert($code===409,'Stale concurrent draft write rejected');
[$code]=$request('/admin/quotation_api.php',['action'=>'issue','csrf_token'=>$token,'id'=>$id,'version'=>2]);$assert($code===422,'Incomplete draft cannot issue');
[$code]=$request('/admin/quotation_api.php?action=lookup&kind=activity&q=Scenic');$assert($code===200,'Experience suggestions load');
[$code]=$request('/database/migrations/2026-10-23-quotation-builder.sql');$assert($code===404,'Fixture does not expose migration or private paths');
echo json_encode(['checks'=>$checks,'passed'=>count($checks)],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR),"\n";
