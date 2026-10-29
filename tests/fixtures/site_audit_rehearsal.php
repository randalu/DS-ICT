<?php
declare(strict_types=1);
// Explicit CLI-only, loopback-only disposable schema. Never connects to the application database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
putenv('DB_NAME=ict_audit_portal_rehearsal');
putenv('APP_URL=http://127.0.0.1:8146');
require_once __DIR__ . '/../../includes/seo_generation.php';
require_once __DIR__ . '/../../includes/site_audit_portal.php';
$schema = 'ict_audit_portal_rehearsal';
if (!in_array(DB_HOST,['127.0.0.1','localhost','::1'],true) || DB_NAME !== $schema) { throw new RuntimeException('Loopback synthetic database required.'); }
$server = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',DB_USERNAME,DB_PASSWORD,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$mode = $argv[1] ?? '';
if ($mode === 'setup') {
    if ($server->query("SHOW DATABASES LIKE '$schema'")->fetch()) { throw new RuntimeException('Disposable schema already exists; inspect it before reuse.'); }
    $server->exec("CREATE DATABASE `$schema` CHARACTER SET utf8mb4");
    foreach (['2026-09-01-site-audit.sql','2026-10-15-seo-manager.sql','2026-10-16-site-audit-portal.sql'] as $file) { db()->exec(file_get_contents(__DIR__ . '/../../database/migrations/' . $file)); }
    db()->exec('CREATE TABLE admins(id INT PRIMARY KEY,name VARCHAR(100),email VARCHAR(100),username VARCHAR(100),is_active INT,last_login_at DATETIME NULL,password_hash VARCHAR(255)) ENGINE=InnoDB');
    execute_query("INSERT INTO admins VALUES(101,'Audit Fixture','fixture@example.invalid','audit_fixture',1,NULL,:hash)",['hash'=>password_hash(bin2hex(random_bytes(24)),PASSWORD_DEFAULT)]);
    foreach (audit_content_types() as [$table,$prefix,$editor,$title]) {
        db()->exec("CREATE TABLE `$table`(id INT PRIMARY KEY,slug VARCHAR(180),is_published INT,`$title` VARCHAR(255),hero_image VARCHAR(255) NULL,hero_media_type VARCHAR(20) NULL,hero_video_poster VARCHAR(255) NULL,og_image VARCHAR(255) NULL,meta_title VARCHAR(255) NULL,meta_description VARCHAR(320) NULL,excerpt TEXT NULL,intro LONGTEXT NULL,content LONGTEXT NULL,faq_json LONGTEXT NULL) ENGINE=InnoDB");
    }
    execute_query("INSERT INTO destinations(id,slug,is_published,name,hero_image,meta_title,meta_description,excerpt,intro) VALUES(101,'audit-fixture',1,'Synthetic travel page','','Original SEO title','Original description','Original excerpt','A public synthetic introduction.')");
    execute_query("INSERT INTO destinations(id,slug,is_published,name) VALUES(102,'unpublished-fixture',0,'Unpublished fixture')");
    db()->exec('CREATE TABLE destination_photos(id INT PRIMARY KEY,destination_id INT,image_path VARCHAR(255)) ENGINE=InnoDB');
    execute_query("INSERT INTO destination_photos VALUES(201,101,''),(202,101,'assets/images/placeholders/placeholder-destination.svg')");
    $record=seo_present('destination',101);
    execute_query('INSERT INTO seo_suggestions(id,admin_id,content_type,content_id,field_name,source_revision,policy_version,model,suggestion_json,created_at) VALUES(501,101,\'destination\',101,\'meta_title\',:revision,:policy,:model,:data,NOW())',
        ['revision'=>$record['revision'],'policy'=>SEO_POLICY_VERSION,'model'=>SEO_MODEL,'data'=>audit_json(['replacement'=>'Reviewed travel title'])]);
    echo "Synthetic schema created; no existing application records read or changed.\n"; exit;
}
if ($mode === 'seed-preview') {
    $record=seo_present('destination',101);
    execute_query('INSERT INTO seo_suggestions(id,admin_id,content_type,content_id,field_name,source_revision,policy_version,model,suggestion_json,created_at) VALUES(501,101,\'destination\',101,\'meta_title\',:revision,:policy,:model,:data,NOW()) ON DUPLICATE KEY UPDATE source_revision=VALUES(source_revision)',
        ['revision'=>$record['revision'],'policy'=>SEO_POLICY_VERSION,'model'=>SEO_MODEL,'data'=>audit_json(['replacement'=>'Reviewed travel title'])]);
    echo "Synthetic preview seeded.\n";exit;
}
if ($mode === 'runtime-check') {
    $modules = (string) getenv('AUDIT_TEST_MODULES');
    if (!is_file($modules . '/playwright/package.json')) { throw new RuntimeException('Explicit test modules required'); }
    db()->exec('CREATE TABLE IF NOT EXISTS site_settings(setting_key VARCHAR(120) PRIMARY KEY,setting_value LONGTEXT NULL,updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB');
    db()->beginTransaction();
    $oldModules=getenv('SITE_AUDIT_NODE_MODULES');$oldNode=getenv('NODE_PATH');$oldBrowsers=getenv('PLAYWRIGHT_BROWSERS_PATH');
    try {
        execute_query("INSERT INTO site_settings(setting_key,setting_value) VALUES('site_audit_node_modules',:modules) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)",['modules'=>$modules]);
        execute_query("INSERT INTO site_settings(setting_key,setting_value) VALUES('site_audit_browsers_path',:path) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)",['path'=>sys_get_temp_dir()]);
        putenv('SITE_AUDIT_NODE_MODULES');putenv('NODE_PATH');putenv('PLAYWRIGHT_BROWSERS_PATH');
        site_audit_prepare_node_runtime();
        if (!db()->inTransaction()) {throw new RuntimeException('Runtime lookup must not install schema/defaults');}
        if (getenv('PLAYWRIGHT_BROWSERS_PATH')!==realpath(sys_get_temp_dir())) {throw new RuntimeException('Browser cache binding failed');}
        if (getenv('NODE_PATH')!==realpath($modules)) { throw new RuntimeException('Database runtime binding failed'); }
        putenv('SITE_AUDIT_NODE_MODULES=/definitely-unavailable-audit-runtime');
        $rejected=false;try {site_audit_prepare_node_runtime();}catch(RuntimeException){$rejected=true;}
        if (!$rejected) {throw new RuntimeException('Environment runtime override was ignored');}
        echo "Database runtime binding and explicit environment precedence passed.\n";
    } finally {
        db()->rollBack();
        $oldModules===false?putenv('SITE_AUDIT_NODE_MODULES'):putenv('SITE_AUDIT_NODE_MODULES='.$oldModules);
        $oldNode===false?putenv('NODE_PATH'):putenv('NODE_PATH='.$oldNode);
        $oldBrowsers===false?putenv('PLAYWRIGHT_BROWSERS_PATH'):putenv('PLAYWRIGHT_BROWSERS_PATH='.$oldBrowsers);
    }
    exit;
}
if ($mode === 'accuracy-check') {
    if (!db()->query("SHOW COLUMNS FROM blog_posts LIKE 'published_at'")->fetch()) { db()->exec('ALTER TABLE blog_posts ADD published_at DATETIME NULL'); }
    db()->beginTransaction();
    try {
        foreach ([301=>'DATE_ADD(NOW(), INTERVAL 1 DAY)',302=>'DATE_SUB(NOW(), INTERVAL 1 DAY)',303=>'NULL'] as $id=>$date) {
            db()->exec("INSERT INTO blog_posts(id,slug,is_published,title,published_at) VALUES($id,'accuracy-$id',1,'Synthetic publication',$date)");
        }
        $inventory=audit_inventory();$urls=array_column($inventory['items'],'url');
        if(in_array(base_url('blog/accuracy-301'),$urls,true) || !in_array(base_url('blog/accuracy-301'),$inventory['unpublished'],true)
            || !in_array(base_url('blog/accuracy-302'),$urls,true) || !in_array(base_url('blog/accuracy-303'),$urls,true)) { throw new RuntimeException('Publication eligibility regression'); }
        echo "Scheduled, past and undated publication eligibility passed.\n";
    } finally { db()->rollBack(); }
    exit;
}
if ($mode==='hide-evidence') {db()->exec('RENAME TABLE site_audit_evidence TO site_audit_evidence_held');echo "Synthetic evidence table hidden.\n";exit;}
if ($mode==='restore-evidence') {db()->exec('RENAME TABLE site_audit_evidence_held TO site_audit_evidence');echo "Synthetic evidence table restored.\n";exit;}
if ($mode === 'cleanup') {
    $runs = db()->query('SELECT id FROM site_audit_runs')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($runs as $id) foreach (['','.lock','.offset','.inventory.json'] as $suffix) { $path=site_audit_log_path((int)$id).$suffix;if(is_file($path))unlink($path); }
    // Clean only pre-namespace artifacts whose content proves this exact fixture origin.
    foreach($runs as $id){$legacy=sys_get_temp_dir().DIRECTORY_SEPARATOR.'ict_site_audit_'.(int)$id.'.jsonl';if(is_file($legacy)&&str_contains(file_get_contents($legacy),'http://127.0.0.1:8146')){foreach(['','.lock','.offset','.inventory.json'] as $suffix){if(is_file($legacy.$suffix))unlink($legacy.$suffix);}}}
    @rmdir(site_audit_private_directory());
    $server->exec("DROP DATABASE `$schema`"); echo "Only the named synthetic schema and its worker artifacts removed.\n";exit;
}
if ($mode !== 'check') { throw new InvalidArgumentException('Use setup, check or cleanup.'); }
$checks=[];
$check=static function(bool $ok,string $label) use(&$checks): void { if(!$ok)throw new RuntimeException($label);$checks[]=$label; };
$reject=static function(callable $call,string $label) use($check): void {try{$call();}catch(DomainException|InvalidArgumentException|RuntimeException|JsonException){$check(true,$label);return;}$check(false,$label);};
$check(audit_portal_ready(),'Additive migration ready');
db()->exec(file_get_contents(__DIR__.'/../../database/migrations/2026-10-16-site-audit-portal.sql'));
$check(audit_portal_ready(),'Migration reruns without data loss');
$inventory=audit_inventory();$check(count($inventory['items'])===1 && count($inventory['unpublished'])===1,'Published-only inventory with unpublished route detection');
$run=(int)fetch_one("SELECT id FROM site_audit_runs WHERE status='done' ORDER BY id LIMIT 1")['id'];
$rows=audit_issue_rows($run);$types=array_column($rows,'type');
foreach(['missing_media_assignment','placeholder_image','broken_internal_link','missing_anchor','malformed_jsonld','mobile_overflow'] as $type)$check(in_array($type,$types,true),'Crawler/import evidence: '.$type);
$check(count(array_filter($rows,static fn($row)=>$row['type']==='resource_failure' && str_contains($row['target_url'],'missing-variant.svg') && $row['certainty']==='confirmed'))>0,'Missing responsive variant confirmed from HTTP evidence');
$check(count(array_filter($rows,static fn($row)=>$row['certainty']==='inconclusive' && str_contains($row['detail'],'429')))>0,'429 never becomes confirmed broken link');
$check(count(fetch_all('SELECT * FROM site_audit_observations WHERE run_id=:id',['id'=>$run]))>0,'Page/resource observations imported');
$before=(int)fetch_one('SELECT COUNT(*) n FROM site_audit_issues')['n'];site_audit_poll_result($run);site_audit_poll_result($run);
$check((int)fetch_one('SELECT COUNT(*) n FROM site_audit_issues')['n']===$before,'Repeated polls do not duplicate findings');
$first=$rows[0];audit_triage($first['fingerprint'],'ignored','Synthetic known exception',101);
$check(audit_issue_rows($run)[0]['triage_state']==='ignored','Ignored reason persists');
$reject(fn()=>audit_triage($first['fingerprint'],'ignored','',101),'Ignored state requires a reason');
$reject(fn()=>audit_triage(str_repeat('f',64),'open','',101),'Unknown fingerprint rejected');
$reject(fn()=>audit_ai_context($run,[99999999]),'Foreign issue ID rejected');
$reject(fn()=>audit_ai_context($run,range(1,21)),'More than 20 issues rejected');
$ids=[(int)$first['id']];$preview=audit_ai_context($run,$ids);
$hash=fetch_one('SELECT password_hash FROM admins WHERE id=101')['password_hash'];
$_SESSION[ADMIN_SESSION_KEY]=101;$_SESSION[ADMIN_CREDENTIAL_STAMP_KEY]=admin_credential_stamp(101,$hash);
$connect=static function(): void {$_SESSION['_ict_seo_connection']=['key'=>'synthetic-not-a-real-key','admin'=>101,'stamp'=>$_SESSION[ADMIN_CREDENTIAL_STAMP_KEY],'touched'=>time(),'nonce'=>bin2hex(random_bytes(16))];session_write_close();session_start();};
$connect();
$transport=static function(array $payload) use($ids,$check): array {
    $check($payload['store']===false,'OpenAI response storage disabled');$check($payload['model']===SEO_MODEL,'Existing configured model reused');
    $check(str_contains($payload['instructions'],'untrusted DATA'),'Evidence treated as untrusted data');
    return ['status'=>'completed','output'=>[['content'=>[['type'=>'output_text','text'=>audit_json(['recommendations'=>[['issue_id'=>$ids[0],'priority'=>'high','explanation'=>'Synthetic review','action'=>'Review the observed issue','dependencies'=>[],'verification'=>['Run another audit']]]])]]]],'usage'=>['input_tokens'=>10,'output_tokens'=>5]];
};
$result=audit_run_review($run,$ids,$preview['hash'],'synthetic-review-request-0001',$transport);
$check(count($result['result']['recommendations'])===1,'Mocked paid pipeline persists review');
$check((int)fetch_one('SELECT COUNT(*) n FROM site_audit_reviews')['n']>=1,'Review saved with model/hash');
$check((int)fetch_one("SELECT input_tokens FROM seo_requests WHERE request_token='synthetic-review-request-0001'")['input_tokens']===10,'Shared usage records token counts');
$reject(fn()=>audit_run_review($run,$ids,$preview['hash'],'synthetic-review-request-0001',$transport),'Request replay rejected');
$reject(fn()=>audit_run_review($run,$ids,str_repeat('0',64),'synthetic-bad-context-0002',$transport),'Changed preview rejected before paid call');
$oldLimit=getenv('SEO_GENERATION_DAILY_LIMIT');putenv('SEO_GENERATION_DAILY_LIMIT=0');
$reject(fn()=>audit_run_review($run,$ids,$preview['hash'],'synthetic-quota-request-0003',$transport),'Shared daily quota enforced');
putenv('SEO_GENERATION_DAILY_LIMIT'.($oldLimit===false?'':'='.$oldLimit));
$reviews=(int)fetch_one('SELECT COUNT(*) n FROM site_audit_reviews')['n'];
$reject(fn()=>audit_run_review($run,$ids,$preview['hash'],'synthetic-malformed-0004',static fn()=>['status'=>'completed','output'=>[['content'=>[['type'=>'output_text','text'=>'{}']]]]]),'Malformed output rejected');
$check((int)fetch_one('SELECT COUNT(*) n FROM site_audit_reviews')['n']===$reviews,'Malformed provider result preserves completed reviews');
$reject(fn()=>audit_run_review($run,$ids,$preview['hash'],'synthetic-disconnected-0005',static function() use($transport) {session_start();unset($_SESSION['_ict_seo_connection']);session_write_close();return $transport(['store'=>false,'model'=>SEO_MODEL,'instructions'=>'untrusted DATA']);}),'Disconnect during provider call rejects result');
$check((int)fetch_one('SELECT COUNT(*) n FROM site_audit_reviews')['n']===$reviews,'Disconnect cannot persist a result');
$connect();
$record=seo_present('destination',101);execute_query("UPDATE destinations SET meta_title='Concurrent edit' WHERE id=101");
$reject(fn()=>seo_save('destination',101,$record['revision'],['meta_title'=>'Stale replacement'],null,101),'Stale preview cannot overwrite concurrent edit');
$check(fetch_one('SELECT meta_title FROM destinations WHERE id=101')['meta_title']==='Concurrent edit','Concurrent title preserved');
$current=seo_present('destination',101);seo_save('destination',101,$current['revision'],['meta_title'=>'Reviewed synthetic title'],null,101);
$check((int)fetch_one('SELECT COUNT(*) n FROM seo_revisions')['n']>=1,'Selective save retains revision history');
execute_query("INSERT INTO site_audit_runs(started_at,status) VALUES(NOW(),'done')");$second=(int)db()->lastInsertId();
execute_query('INSERT INTO site_audit_snapshots(run_id,profile,base_url,inventory_json,coverage_json) VALUES(:id,:profile,:base,:inventory,:coverage)',['id'=>$second,'profile'=>audit_snapshot($run)['profile'],'base'=>APP_URL,'inventory'=>audit_json($inventory),'coverage'=>'{"complete":false}']);
audit_import_events($second,[['observation'=>['kind'=>'page','page_url'=>$first['page_url'],'status'=>200,'complete'=>true,'passed_checks'=>[$first['type']]]]]);
$check(count(audit_compare($second)['resolved'])===0,'Partial run cannot resolve absent findings');
execute_query('UPDATE site_audit_snapshots SET coverage_json=\'{"complete":true}\' WHERE run_id=:id',['id'=>$second]);
$comparison=audit_compare($second);$check(count($comparison['resolved'])>=1,'Successful compatible rule recheck can resolve a finding');
$check(count($comparison['unverified'])>=1,'Other unchecked rules remain unverified');
$check(count($inventory['items'][0]['child_media']??[])===2,'Published child media assignments are inventoried');
execute_query("INSERT INTO site_audit_runs(started_at,status) VALUES(DATE_SUB(NOW(),INTERVAL 2 HOUR),'running')");$stale=(int)db()->lastInsertId();
$check(site_audit_poll_result($stale)['status']==='failed','Polling recovers an abandoned worker without a fresh launch');
$check((int)fetch_one('SELECT is_published FROM destinations WHERE id=102')['is_published']===0,'Draft never published');
file_put_contents(__DIR__.'/../../output/site-audit-integration.json',audit_json(['passed'=>count($checks),'checks'=>$checks]));
echo count($checks)." integration checks passed; all OpenAI responses mocked.\n";
