<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/functions.php';

final class WebStatsCountingTest extends TestCase
{
    private array $server;
    private array $cookies;
    private array $session;
    private int $startId;
    protected function setUp(): void
    {
        if (!str_starts_with(DB_NAME,'ict_stats_all_20261023_') || !ict_webstats_has_metadata_columns()) {
            $this->markTestSkipped('Requires the disposable migrated statistics fixture.');
        }
        $this->server=$_SERVER;$this->cookies=$_COOKIE;$this->session=$_SESSION;
        $_SERVER['REQUEST_URI']='/contact';$_SERVER['SCRIPT_NAME']='/contact.php';$_SERVER['REQUEST_METHOD']='GET';
        $_SERVER['HTTP_HOST']='127.0.0.1:8133';$_SERVER['REMOTE_ADDR']='192.0.2.77';
        $_SERVER['HTTP_USER_AGENT']='Mozilla/5.0 (Windows NT 10.0) Chrome/142.0 Safari/537.36 private-details';
        $_SERVER['HTTP_REFERER']='https://example.invalid/private?email=private@example.invalid';$_SERVER['HTTP_CF_IPCOUNTRY']='LK';
        $_COOKIE=[];unset($_SESSION['ict_analytics_visit'],$_SESSION['ict_analytics_browser']);
        db()->beginTransaction();ict_privacy_save_settings(array_replace(ict_privacy_defaults(),['purpose_revision'=>3]));
        $this->startId=(int)fetch_one('SELECT COALESCE(MAX(id),0) AS id FROM page_views')['id'];
    }
    protected function tearDown(): void
    {
        if(isset($this->server)){
            if(db()->inTransaction()){db()->rollBack();}
            $_SERVER=$this->server;$_COOKIE=$this->cookies;$_SESSION=$this->session;
        }
    }
    private function counts(): array
    {
        $v=ict_webstats_visitor_sql();$s=ict_webstats_visit_sql();
        return fetch_one("SELECT COUNT(*) AS views,COUNT(DISTINCT {$v}) AS visitors,COUNT(DISTINCT {$s}) AS visits FROM page_views WHERE id>:id",['id'=>$this->startId]);
    }
    private function latest(): array{return fetch_one('SELECT * FROM page_views ORDER BY id DESC LIMIT 1');}
    public function testEveryConsentStateAndOptionalKillSwitchStillRecordsViewsAndClicks(): void
    {
        $settings=ict_privacy_settings();$events=(int)fetch_one('SELECT COUNT(*) AS n FROM analytics_events')['n'];
        $cookies=[null,ict_privacy_cookie_value(false,$settings),ict_privacy_cookie_value(true,$settings,time()-181*86400),ict_privacy_cookie_value(true,$settings),'{malformed'];
        foreach($cookies as $cookie){
            $_COOKIE=$cookie===null?[]:[ICT_PRIVACY_COOKIE=>$cookie];record_page_view();track_analytics_event('phone_click');
            $this->assertTrue(ict_analytics_allowed());$this->assertFalse(ict_analytics_allowed('gtm'));
        }
        ict_privacy_save_settings(array_replace(ict_privacy_defaults(),['purpose_revision'=>3,'first_party_enabled'=>false]));
        record_page_view();track_analytics_event('phone_click');
        $this->assertSame(['views'=>6,'visitors'=>1,'visits'=>1],$this->counts());
        $this->assertSame($events+6,(int)fetch_one('SELECT COUNT(*) AS n FROM analytics_events')['n']);
        execute_query('UPDATE site_settings SET setting_value=:bad WHERE setting_key=:key',['bad'=>'{malformed','key'=>ICT_PRIVACY_SETTINGS_KEY]);
        record_page_view();$this->assertSame(['views'=>7,'visitors'=>1,'visits'=>1],$this->counts());
        $this->assertFalse(ict_analytics_allowed('gtm'));
    }
    public function testRepeatPagesNewBrowserAndThirtyMinuteVisitBoundary(): void
    {
        record_page_view();$first=$this->latest();record_page_view();
        $this->assertSame(['views'=>2,'visitors'=>1,'visits'=>1],$this->counts());
        $_SESSION['ict_analytics_visit']['last_seen']=time()-1800;record_page_view();
        $this->assertSame($first['visitor_key'],$this->latest()['visitor_key']);
        $this->assertNotSame($first['visit_key'],$this->latest()['visit_key']);
        unset($_SESSION['ict_analytics_visit'],$_SESSION['ict_analytics_browser']);record_page_view();
        $this->assertSame(['views'=>4,'visitors'=>2,'visits'=>3],$this->counts());
    }
    public function testCompleteBoundedDimensionsAndPublicPathNeverStoreRawDetails(): void
    {
        $_SERVER['REQUEST_URI']='/destinations/synthetic-place?email=private@example.invalid';record_page_view();$row=$this->latest();
        $this->assertSame('/destinations/synthetic-place',$row['path']);$this->assertSame('LK',$row['country']);
        $this->assertSame('https://example.invalid',$row['referrer']);
        $this->assertSame('Desktop',$row['device_type']);$this->assertSame('Chrome',$row['browser_name']);$this->assertSame('Windows',$row['os_name']);
        foreach(['ip_address','user_agent'] as $field){$this->assertSame('',$row[$field]);}
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/',$row['visitor_key']);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{32}\z/',$row['visit_key']);
        unset($_SERVER['HTTP_CF_IPCOUNTRY']);$_SERVER['HTTP_USER_AGENT']='';$_SERVER['HTTP_REFERER']='javascript:private';record_page_view();
        $this->assertSame('',$this->latest()['country']);$this->assertSame('Unknown',$this->latest()['device_type']);$this->assertSame('',$this->latest()['referrer']);
        $this->assertTrue(db()->inTransaction());
    }
    public function testPrivateErrorHeadPostAndUnknownPathsAreExcluded(): void
    {
        foreach(['/admin','/account','/ai-search','/privacy_choices.php','/wp-login.php','/contact/../account','/stats-unpublished-fixture'] as $path){$_SERVER['REQUEST_URI']=$path;record_page_view();$this->assertFalse(ict_analytics_allowed());}
        $_SERVER['REQUEST_URI']='/contact';foreach(['HEAD','POST'] as $method){$_SERVER['REQUEST_METHOD']=$method;record_page_view();}
        $_SERVER['REQUEST_METHOD']='GET';$old=http_response_code();try{http_response_code(404);record_page_view();}finally{http_response_code($old?:200);}
        $this->assertSame(['views'=>0,'visitors'=>0,'visits'=>0],$this->counts());
    }
    public function testAnonymousHistoryIsNotInventedAndMixedLegacyDistinctCountsRemainCorrect(): void
    {
        execute_query("INSERT INTO page_views(path,ip_address,user_agent) VALUES ('/legacy','192.0.2.1','Legacy'),('/legacy','192.0.2.1','Legacy'),('/aggregate','','')");
        record_page_view();$this->assertSame(['views'=>4,'visitors'=>2,'visits'=>2],$this->counts());
        $s=ict_webstats_visit_sql();$this->assertCount(2,fetch_all("SELECT MIN(id),MAX(id) FROM page_views WHERE id>:id AND ({$s}) IS NOT NULL GROUP BY {$s}",['id'=>$this->startId]));
    }
    public function testClickContextUsesSameOriginPublicPageAndDropsQueries(): void
    {
        $_SERVER['SCRIPT_NAME']='/track-event.php';$_SERVER['REQUEST_URI']='/track-event.php';
        $_SERVER['HTTP_REFERER']='http://127.0.0.1:8133/destinations/synthetic-place?private=1';
        $this->assertTrue(ict_analytics_allowed());track_analytics_event('phone_click');
        $row=fetch_one('SELECT * FROM analytics_events ORDER BY id DESC LIMIT 1');
        $this->assertSame('/destinations/synthetic-place',$row['path']);$this->assertSame('phone',$row['target_group']);
        $_SERVER['HTTP_REFERER']='https://evil.invalid/contact';$this->assertFalse(ict_analytics_allowed());
    }
    public function testDeviceBrowserAndOsClassification(): void
    {
        foreach([
            ['Mozilla Windows Edg/142.0','Desktop','Edge','Windows'],['Mozilla iPhone CriOS/142.0 Safari/0','Mobile','Chrome','iOS'],
            ['Mozilla iPad Safari/0','Tablet','Safari','iOS'],['Mozilla Android Firefox/0','Mobile','Firefox','Android'],
            ['Googlebot/0','Bots','Bot','Other'],['','Unknown','Unknown','Unknown']
        ] as [$ua,$device,$browser,$os]){$this->assertSame(['device_type'=>$device,'browser_name'=>$browser,'os_name'=>$os],ict_webstats_metadata($ua));}
    }
}
