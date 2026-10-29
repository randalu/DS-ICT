<?php
declare(strict_types=1);
// Explicit CLI-only disposable schema rehearsal. No real content is copied.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('session.save_path', __DIR__ . '/../../output/session');
require_once __DIR__ . '/../../includes/seo_generation.php';
$out = __DIR__ . '/../../output/seo-manager';
if (!is_dir($out)) { mkdir($out, 0777, true); }
$manifest = $out . '/fixture.json';
$name = 'ict_seo_test_' . bin2hex(random_bytes(6));
$checks = [];
$check = static function (bool $valid, string $label) use (&$checks): void { if (!$valid) { throw new RuntimeException('FAIL: ' . $label); } $checks[] = $label; };
$reject = static function (callable $operation, string $label) use ($check): void {
    try { $operation(); } catch (Throwable) { $check(true, $label); return; } $check(false, $label);
};
if (($argv[1] ?? '') === '--drop') {
    $name = (string) (json_decode(file_get_contents($manifest), true)['database'] ?? '');
    if (!preg_match('/^ict_seo_test_[a-f0-9]{12}$/D', $name)) { throw new RuntimeException('Unexpected disposable schema.'); }
    db()->exec('DROP DATABASE `' . $name . '`'); unlink($manifest); echo "Disposable SEO schema removed.\n"; exit;
}
$keep = ($argv[1] ?? '') === '--keep';
db()->exec('CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    foreach (db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        if (str_starts_with($table, 'seo_')) { continue; }
        if (!preg_match('/^[a-zA-Z0-9_]+$/D', $table)) { throw new RuntimeException('Unexpected table name'); }
        db()->exec('CREATE TABLE `' . $name . '`.`' . $table . '` LIKE `' . DB_NAME . '`.`' . $table . '`');
    }
    db()->exec('USE `' . $name . '`');
    $before = (int) db()->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn();
    $check(!seo_ready(), 'Missing migration fails closed');
    $check(seo_list(['type'=>'destination'])['items'] === [], 'List available without migration');
    $check($before === (int) db()->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn(), 'Read requests create no tables');
    db()->exec(file_get_contents(__DIR__ . '/../../database/migrations/2026-10-15-seo-manager.sql'));
    foreach (['seo_plans','seo_guidance','seo_briefs','seo_suggestions','seo_revisions','seo_requests'] as $table) db_table_exists($table,true);
    $check(seo_ready(), 'Additive migration applied in disposable schema');
    execute_query("INSERT INTO admins(id,name,email,username,password_hash,is_active) VALUES(1,'SEO fixture','seo@example.test','seo-fixture',:hash,1)", ['hash'=>password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)]);
    $admin = fetch_one('SELECT * FROM admins WHERE id=1');
    $_SESSION[ADMIN_SESSION_KEY] = 1; $_SESSION[ADMIN_CREDENTIAL_STAMP_KEY] = admin_credential_stamp(1,$admin['password_hash']);
    foreach (seo_types() as $type => $def) {
        $field = $def['title'];
        execute_query('INSERT INTO ' . $def['table'] . "(id,{$field},slug,excerpt,is_published) VALUES(101,:title,:slug,'Explore private travel in Sri Lanka',1)", ['title'=>'Sri Lanka ' . $type . ' fixture','slug'=>'seo-fixture-' . $type]);
        if ($type === 'blog') execute_query('UPDATE blog_posts SET content=:body WHERE id=101', ['body'=>'<p>Original unique passage.</p><img src="preserved.jpg"><p>Keep this ending.</p>']);
        else execute_query('UPDATE ' . $def['table'] . " SET intro='<p>Original introduction.</p>',faq_json='[]' WHERE id=101");
        $record = seo_record($type,101); $present = seo_present($type,101);
        $saved = seo_save($type,101,$present['revision'],['meta_title'=>'Private Sri Lanka journeys'],null,1);
        $after = seo_record($type,101);
        $check($after['meta_title']==='Private Sri Lanka journeys', $type . ' selected field saves');
        unset($record['meta_title'],$after['meta_title'],$record['updated_at'],$after['updated_at']);
        $check($record===$after, $type . ' unselected fields and publishing preserved');
        $reject(fn()=>seo_save($type,101,$present['revision'],['meta_title'=>'stale'],null,1),$type.' stale/replayed save rejected');
        $restored = seo_restore($type,101,$saved['revision'],(int)$saved['revisions'][0]['id'],1);
        $check(seo_record($type,101)['meta_title']===null,$type.' NULL metadata restored exactly');
        $reject(fn()=>seo_save($type,101,$restored['revision'],['slug'=>'changed'],null,1),$type.' protected slug rejected');
        if ($type !== 'blog') {
            $legacyFaq='[{"q":"How can I plan a private visit?","a":"Discuss your route with our team."}]';
            seo_save($type,101,$restored['revision'],['faq_json'=>$legacyFaq],null,1);
            $check(seo_record($type,101)['faq_json']===$legacyFaq,$type.' legacy q/a FAQs save without field loss');
        }
    }
    $present = seo_present('destination',101);
    $reject(fn()=>seo_restore('tour',101,seo_present('tour',101)['revision'],(int)$present['revisions'][0]['id'],1),'Forged cross-record restore rejected');
    db()->exec("CREATE TRIGGER seo_fixture_fail BEFORE INSERT ON seo_revisions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Synthetic audit failure'");
    $before = seo_record('destination',101);
    $reject(fn()=>seo_save('destination',101,$present['revision'],['meta_title'=>'Must roll back'],null,1),'Audit insert failure rejects save');
    $check($before===seo_record('destination',101),'Audit failure rolls back content and timestamp');
    db()->exec('DROP TRIGGER seo_fixture_fail');
    $blog = seo_present('blog',101);
    $body = seo_save('blog',101,$blog['revision'],['content'=>['selection'=>'Original unique passage.','replacement'=>'Improved private travel passage.']],null,1);
    $check(str_contains(seo_record('blog',101)['content'],'<img src="preserved.jpg"><p>Keep this ending.</p>'),'Selected blog passage preserves media and unselected body');
    seo_restore('blog',101,$body['revision'],(int)$body['revisions'][0]['id'],1);
    $check(seo_record('blog',101)['content']===$blog['fields']['content']['value'],'Blog body restore is byte-exact');
    $generation = ['replacement'=>'Private Sri Lanka travel','explanation'=>'Clear page intent','intent'=>'Plan private travel','keywords'=>['Sri Lanka'],'warnings'=>[]];
    $mock = static fn(array $payload): array => ['status'=>'completed','output'=>[['content'=>[['type'=>'output_text','text'=>json_encode($generation)]]]],'usage'=>['input_tokens'=>20,'output_tokens'=>10]];
    $call = static fn(array $payload): array => seo_openai_response('synthetic-no-network',$payload,$mock);
    foreach (seo_types() as $type => $def) {
        $present = seo_present($type,101); $before = seo_record($type,101);
        $result = seo_generate(['type'=>$type,'id'=>101,'field'=>'meta_title','revision'=>$present['revision']],1,$call,static fn()=>null);
        $check($before===seo_record($type,101),$type.' generation does not change content');
        $check($result['policy_version']===SEO_POLICY_VERSION && $result['suggestion_id']>0,$type.' suggestion records policy and model');
        seo_save($type,101,$present['revision'],['meta_title'=>$result['replacement']],null,1,['meta_title'=>$result['suggestion_id']]);
    }
    $researchCalls = 0;
    $researchCall = static function(array $payload) use (&$researchCalls): array {
        $researchCalls++;
        return ['text'=>isset($payload['tools']) ? 'Official evidence: useful content.' : json_encode(['findings'=>['Helpful factual content'],'uncertainties'=>['No measured keyword demand'],'actions'=>['Review existing metadata'],'primary_phrase'=>'Sri Lanka private travel','supporting_phrases'=>['Private Sri Lanka itinerary'],'questions'=>['How to plan?'],'intent'=>'Plan private travel']),
            'sources'=>[['url'=>'https://developers.google.com/search/docs/appearance/ai-features','title'=>'Google AI features']], 'usage'=>['input'=>10,'output'=>10]];
    };
    $brief = seo_research(['kind'=>'keywords','type'=>'destination','id'=>101],1,$researchCall,static fn()=>null);
    $check($researchCalls===2 && count($brief['data']['sources'])===1,'Research uses cited discovery then structured extraction');
    seo_research(['kind'=>'keywords','type'=>'destination','id'=>101],1,$researchCall,static fn()=>null);
    $check($researchCalls===2,'Fresh brief reused without model call');
    $reject(fn()=>seo_research(['kind'=>'keywords','type'=>'destination','id'=>101,'refresh'=>true],1,static fn()=>throw new RuntimeException('Synthetic timeout'),static fn()=>null),'Research failure reported');
    $check(seo_latest_brief('keywords','destination',101,'International')['id']===$brief['id'],'Research failure preserves last brief');
    $_SESSION['_ict_seo_connection'] = ['key'=>'synthetic-no-network','admin'=>1,'stamp'=>$_SESSION[ADMIN_CREDENTIAL_STAMP_KEY],'touched'=>time(),'nonce'=>'test-nonce'];
    $work = static function(callable $call, callable $revalidate): array { $result=$call(['input'=>'Synthetic test']); $revalidate(); return $result; };
    $requestToken = bin2hex(random_bytes(16));
    seo_paid_request('generation',$requestToken,$work,$mock);
    $reject(fn()=>seo_paid_request('generation',$requestToken,$work,$mock),'Duplicate paid request rejected without resend');
    $check(!str_contains(seo_json(seo_usage(1)),'synthetic-no-network'),'Usage never exposes key');
    $check((int)fetch_one('SELECT input_tokens FROM seo_requests LIMIT 1')['input_tokens']===20,'Token usage recorded');
    $lock = 'ict-seo-' . substr(hash('sha256', DB_NAME . ':1'),0,40);
    $other = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.$name,DB_USERNAME,DB_PASSWORD,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $statement = $other->prepare('SELECT GET_LOCK(:name,0)'); $statement->execute(['name'=>$lock]);
    try { $reject(fn()=>seo_paid_request('generation',bin2hex(random_bytes(16)),$work,$mock),'Concurrent admin request rejected by independent connection lock'); }
    finally { $statement=$other->prepare('DO RELEASE_LOCK(:name)'); $statement->execute(['name'=>$lock]); }
    putenv('SEO_GENERATION_DAILY_LIMIT=1');
    $reject(fn()=>seo_paid_request('generation',bin2hex(random_bytes(16)),$work,$mock),'Daily request cap enforced');
    putenv('SEO_GENERATION_DAILY_LIMIT');
    $reject(fn()=>seo_paid_request('generation',bin2hex(random_bytes(16)),static function($call,$revalidate) {
        $call(['input'=>'Synthetic request']); session_start(); unset($_SESSION['_ict_seo_connection']); session_write_close(); $revalidate(); return [];
    },$mock),'Disconnect during request invalidates its result');
    $check(!isset($_SESSION['_ict_seo_connection']),'Failed revalidation leaves connection cleared');
    $_SESSION['_ict_seo_connection']=['key'=>'synthetic-no-network','admin'=>1,'stamp'=>$_SESSION[ADMIN_CREDENTIAL_STAMP_KEY],'touched'=>time(),'nonce'=>'test-nonce'];
    $beforeRecord=seo_record('tour',101);
    $reject(fn()=>seo_paid_request('generation',bin2hex(random_bytes(16)),$work,static fn()=>throw new RuntimeException('OpenAI rejected this key or model access.')),'Provider authentication failure is not retried');
    $check($beforeRecord===seo_record('tour',101),'Provider failure preserves public content');
    $check((int)fetch_one("SELECT COUNT(*) AS n FROM seo_requests WHERE status='failed_or_unknown'")['n']===2,'Failed and uncertain requests retained in usage ledger');
    $check(seo_list(['type'=>'blog','missing'=>'1'])['items']!==[], 'Missing metadata filter works');
    $check(seo_list(['type'=>'destination','published'=>'0'])['items']===[], 'Publication filter works');
    // Optional older-schema fields disappear; no runtime schema installation.
    db()->exec('ALTER TABLE destinations DROP COLUMN faq_json'); db_column_exists('destinations','faq_json',true);
    $check(!isset(seo_present('destination',101)['fields']['faq_json']),'Missing optional FAQ field is omitted');
    file_put_contents($out.'/rehearsal-results.json',json_encode($checks,JSON_PRETTY_PRINT));
    if ($keep) { file_put_contents($manifest,json_encode(['database'=>$name])); }
    echo count($checks)." disposable integration checks passed.\n";
} finally {
    unset($_SESSION['_ict_seo_connection']);
    if (!$keep) { db()->exec('USE `' . DB_NAME . '`'); db()->exec('DROP DATABASE `' . $name . '`'); }
}
