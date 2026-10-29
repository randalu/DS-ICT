<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
// Receive secret on stdin only. Do not accept command arguments or echo it.
$key=trim(stream_get_contents(STDIN,1024));
if(!preg_match('/^[\x21-\x7e]{20,512}$/D',$key)){fwrite(STDERR,"Invalid key input. Nothing changed.\n");exit(1);}
$file=dirname(__DIR__).'/.env';if(!is_file($file)||is_link($file)){throw new RuntimeException('An existing regular environment file is required.');}
$handle=fopen($file,'r+');if(!$handle||!flock($handle,LOCK_EX)){throw new RuntimeException('Environment file is not writable.');}
try{
    $contents=stream_get_contents($handle);$count=0;
    $updated=preg_replace_callback('/^\s*OPENAI_ADMIN_API_KEY\s*=.*$/m',static function()use($key,&$count){$count++;return 'OPENAI_ADMIN_API_KEY='.$key;},$contents);
    if($count>1)throw new RuntimeException('Duplicate key settings; review server configuration privately.');
    if(!$count)$updated=rtrim($contents)."\nOPENAI_ADMIN_API_KEY=".$key."\n";
    rewind($handle);if(fwrite($handle,$updated)!==strlen($updated))throw new RuntimeException('Environment update failed.');ftruncate($handle,strlen($updated));fflush($handle);
}finally{flock($handle,LOCK_UN);fclose($handle);}
echo "Server key configured. No key was printed. Restart PHP workers if the key is injected by the hosting environment.\n";
