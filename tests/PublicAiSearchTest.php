<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../includes/public_ai_search.php';

final class PublicAiSearchTest extends TestCase
{
    private array $session;
    private array $server;
    private int $calls = 0;
    private int $reservations = 0;
    protected function setUp(): void
    {
        ensure_site_settings_schema();
        $this->session=$_SESSION; $this->server=$_SERVER;
        $_SERVER['REMOTE_ADDR']='192.0.2.213'; $_SERVER['REQUEST_METHOD']='POST';
        db()->beginTransaction();
        execute_query('DELETE FROM site_settings WHERE setting_key=:key',['key'=>ICT_PRIVACY_SETTINGS_KEY]);
        ict_privacy_save_settings(array_replace(ict_privacy_defaults(),['public_ai_search_enabled'=>true]));
        unset($_SESSION['_ict_public_ai_limit']); csrf_token();
    }
    protected function tearDown(): void
    {
        $this->assertTrue(db()->inTransaction(),'No fixture DDL/implicit commit');
        db()->rollBack(); $_SESSION=$this->session; $_SERVER=$this->server;
    }
    private function body(): array { return ['_token'=>csrf_token(),'ai_notice'=>ICT_PUBLIC_AI_NOTICE,'search_action'=>'search_with_ai','q'=>'Elephants and tea']; }
    private function dependencies(array $replace=[]): array
    {
        return array_replace(['available'=>static fn()=>true,'reserve_ip'=>function(){ $this->reservations++; return 'ok'; },
            'search'=>function($query){ $this->calls++; return [['title'=>'Synthetic tea trip','url'=>'itineraries','excerpt'=>'Synthetic result','score'=>.8]]; }],$replace);
    }
    private function request(array $post=[],array $deps=[],string $method='POST',array $get=[]): array
    { return ict_public_ai_request(['REQUEST_METHOD'=>$method],$get,$post,$_SESSION,$this->dependencies($deps)); }
    public function testOperatorOffLegacyAndMalformedSettingsNeverCallTransport(): void
    {
        $this->assertFalse(ict_privacy_defaults()['public_ai_search_enabled']);
        $legacy=ict_privacy_defaults(); unset($legacy['public_ai_search_enabled']); $legacy['schema_version']=2;
        foreach([json_encode($legacy),json_encode(array_replace(ict_privacy_defaults(),['public_ai_search_enabled'=>false])),'{malformed'] as $raw) {
            execute_query('UPDATE site_settings SET setting_value=:raw WHERE setting_key=:key',['raw'=>$raw,'key'=>ICT_PRIVACY_SETTINGS_KEY]);
            $state=$this->request($this->body()); $this->assertFalse($state['enabled']); $this->assertFalse($state['searched']);
        }
        $this->assertSame(0,$this->calls); $this->assertSame(0,$this->reservations);
        $normal=ict_privacy_decode_settings(json_encode($legacy));
        $this->assertSame(3,$normal['schema_version']); $this->assertFalse($normal['public_ai_search_enabled']);
        $cookie=ict_privacy_cookie_value(['analytics'=>true],$normal); $this->assertTrue(ict_privacy_parse_cookie($cookie,$normal)['analytics']);
        $legacy['public_ai_search_enabled']=true;
        try { ict_privacy_decode_settings(json_encode($legacy)); $this->fail('Legacy extra flag accepted'); } catch(InvalidArgumentException){$this->addToAssertionCount(1);}
    }
    public function testGetOnlyPrefillsAndNoKeyNeverCalls(): void
    {
        $get=$this->request([],[],'GET',['q'=>str_repeat('旅',1001)]);
        $this->assertSame(1000,mb_strlen($get['query'])); $this->assertTrue($get['enabled']);
        $this->assertSame('',$this->request([],[],'GET',['q'=>['bad']])['query']);
        $this->assertFalse($this->request($this->body(),['available'=>static fn()=>false])['enabled']);
        execute_query('DELETE FROM site_settings WHERE setting_key=:key',['key'=>'ai_api_key']);
        $this->assertFalse(ict_public_ai_available());
        $this->assertSame(0,$this->calls); $this->assertSame(0,$this->reservations);
    }
    public function testUnsafeMethodActionNoticeCsrfAndInputNeverReserveOrSend(): void
    {
        $this->assertSame(405,$this->request([],[],'PUT')['status']);
        foreach([['_token'=>'bad'],['_token'=>['bad']],['search_action'=>'accept'],['ai_notice'=>'old'],['q'=>[]],['q'=>''],['q'=>str_repeat('a',1001)],['q'=>"bad\0text"],['q'=>"\xC3\x28"]] as $change) {
            $state=$this->request(array_replace($this->body(),$change));
            $this->assertContains($state['status'],[419,422]); $this->assertFalse($state['searched']);
        }
        $large=ict_public_ai_request(['REQUEST_METHOD'=>'POST','CONTENT_LENGTH'=>16385],[],$this->body(),$_SESSION,$this->dependencies());
        $this->assertSame(413,$large['status']); $this->assertSame(0,$this->calls); $this->assertSame(0,$this->reservations);
    }
    public function testSuccessAndSessionQuotaExpiryFailClosedIpAndProviderFailure(): void
    {
        for($i=0;$i<5;$i++) { $this->assertTrue($this->request($this->body())['searched']); }
        $this->assertSame(429,$this->request($this->body())['status']); $this->assertSame(5,$this->calls);
        $_SESSION['_ict_public_ai_limit']['start']=time()-900;
        $this->assertTrue($this->request($this->body())['searched']);
        unset($_SESSION['_ict_public_ai_limit']);
        $this->assertSame(429,$this->request($this->body(),['reserve_ip'=>static fn()=>'limited'])['status']);
        $this->assertSame(503,$this->request($this->body(),['reserve_ip'=>static fn()=>'unavailable'])['status']);
        $this->assertArrayNotHasKey('_ict_public_ai_limit',$_SESSION);
        $failed=$this->request($this->body(),['search'=>static function(){throw new RuntimeException('PRIVATE PROVIDER DETAIL');}]);
        $this->assertSame(503,$failed['status']); $this->assertStringNotContainsString('PRIVATE',$failed['error']);
        $this->assertSame(1,$_SESSION['_ict_public_ai_limit']['count']);
        $this->assertSame([], $this->request($this->body(),['search'=>static fn()=>[]])['results']);
        $this->assertSame(503,$this->request($this->body(),['search'=>static fn()=>null])['status']);
    }
    public function testExistingIpTableReservesWithoutCleanupAndSurvivesSessionReset(): void
    {
        $this->assertTrue(db_table_exists('form_submission_limits'));
        $before=(int)fetch_one('SELECT COUNT(*) AS total FROM form_submission_limits')['total'];
        $ip='198.51.100.213';
        for($i=0;$i<5;$i++){ $this->assertSame('ok',ict_public_ai_reserve_ip($ip,time())); }
        $this->assertSame('limited',ict_public_ai_reserve_ip($ip,time()));
        $this->assertSame('unavailable',ict_public_ai_reserve_ip('',time()));
        $this->assertSame($before+5,(int)fetch_one('SELECT COUNT(*) AS total FROM form_submission_limits')['total']);
        $rows=fetch_all("SELECT ip_address FROM form_submission_limits WHERE form_key='public_ai_search' AND ip_address=:bucket",['bucket'=>'ai:'.substr(hash('sha256',inet_pton($ip)),0,40)]);
        $this->assertCount(5,$rows); $this->assertStringNotContainsString($ip,$rows[0]['ip_address']);
        execute_query("UPDATE form_submission_limits SET created_at=DATE_SUB(NOW(),INTERVAL 901 SECOND) WHERE form_key='public_ai_search' AND ip_address=:bucket",['bucket'=>$rows[0]['ip_address']]);
        $this->assertSame('ok',ict_public_ai_reserve_ip($ip,time()));
    }
    public function testResponseValidationEscapingAndSafeLocalAlternatives(): void
    {
        foreach(['bad','{}',json_encode(['data'=>[['embedding'=>['bad']]]]),json_encode(['data'=>[['embedding'=>[]]]]),json_encode(['data'=>[['embedding'=>array_fill(0,8193,1)]]]),str_repeat('x',262145)] as $raw){ $this->assertNull(ict_public_ai_decode_embedding($raw)); }
        $this->assertSame([1,.5],ict_public_ai_decode_embedding('{"data":[{"embedding":[1,0.5]}]}'));
        foreach(['https://example.test','//example.test','javascript:alert(1)','../account','tour?private=1',[]] as $bad){$this->assertSame('',ict_public_ai_result_url($bad));}
        $this->assertSame(base_url('itineraries'),ict_public_ai_result_url('/itineraries'));
        $publicAiState=$this->request(array_replace($this->body(),['q'=>'<script>secret</script>']));
        ob_start(); require __DIR__.'/../includes/public_ai_search_view.php'; $html=ob_get_clean();
        $this->assertStringNotContainsString('<script>secret</script>',$html); $this->assertStringContainsString('&lt;script&gt;secret&lt;/script&gt;',$html);
        $this->assertStringContainsString('method="POST"',$html); $this->assertStringContainsString('name="ai_notice"',$html);
        $this->assertStringContainsString('OpenRouter and its selected model provider',$html); $this->assertStringContainsString('Explore without AI',$html);
        $this->assertStringNotContainsString('Relevance:',$html);
    }
    public function testContendedIpLockFailsClosedWithoutAddingRows(): void
    {
        $ip='203.0.113.213'; $bucket='ai:'.substr(hash('sha256',inet_pton($ip)),0,40);
        $lock='ict-ai:'.substr(hash('sha256',$bucket),0,40);
        $peer=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',DB_HOST,DB_PORT,DB_NAME,DB_CHARSET),DB_USERNAME,DB_PASSWORD,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $hold=$peer->prepare('SELECT GET_LOCK(:name,0)'); $hold->execute(['name'=>$lock]); $this->assertSame(1,(int)$hold->fetchColumn());
        $before=(int)fetch_one('SELECT COUNT(*) AS total FROM form_submission_limits')['total'];
        try {
            $this->assertSame('unavailable',ict_public_ai_reserve_ip($ip,time()));
            $this->assertSame($before,(int)fetch_one('SELECT COUNT(*) AS total FROM form_submission_limits')['total']);
        } finally { $release=$peer->prepare('SELECT RELEASE_LOCK(:name)'); $release->execute(['name'=>$lock]); }
        $this->assertSame('ok',ict_public_ai_reserve_ip($ip,time()));
    }
    public function testIndexTransportSeamOnlyUsesMockedEmbeddingAndSettingsShapeIsStrict(): void
    {
        $calls=0; $results=ai_search_query('Synthetic query',2,static function($text)use(&$calls){$calls++; return [1,0];});
        $this->assertSame(1,$calls); $this->assertLessThanOrEqual(2,count($results));
        foreach([['public_ai_search_enabled'=>'yes'],['schema_version'=>2],['unexpected'=>true]] as $bad){
            try { ict_privacy_validate_settings(array_replace(ict_privacy_defaults(),$bad)); $this->fail('Invalid settings accepted'); }
            catch(InvalidArgumentException){$this->addToAssertionCount(1);}
        }
        $source=file_get_contents(__DIR__.'/../includes/public_ai_search.php');
        $this->assertStringNotContainsString('DELETE FROM',$source); $this->assertStringNotContainsString('ensure_contact',$source);
        $this->assertStringContainsString('CURLOPT_TIMEOUT => 20',$source); $this->assertStringContainsString('CURLOPT_CONNECTTIMEOUT => 5',$source);
        $this->assertStringContainsString('CURLOPT_FOLLOWLOCATION => false',$source);
    }
}
