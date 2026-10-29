<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../includes/analytics_retention_review.php';

final class AnalyticsRetentionReviewTest extends TestCase
{
    private int $reviewer;
    protected function setUp(): void
    {
        ensure_analytics_events_table();ensure_analytics_configuration_schema();ensure_site_settings_schema();
        $this->reviewer=(int)fetch_one('SELECT id FROM admins WHERE is_active=1 ORDER BY id LIMIT 1')['id'];
        db()->beginTransaction();
        execute_query('DELETE FROM analytics_settings WHERE setting_key=:key',['key'=>ICT_ANALYTICS_HOLD_KEY]);
        execute_query('UPDATE analytics_settings SET setting_value=:days WHERE setting_key=:key',['days'=>'365','key'=>'events_retention_days']);
        $this->manual(['period'=>365,'unit'=>'days','trigger'=>'Event date']);
    }
    protected function tearDown(): void{$this->assertTrue(db()->inTransaction());db()->rollBack();}
    private function input(): array{return ['revision'=>0,'reason'=>'Synthetic monthly review','reference'=>'C4-TEST','next_review_date'=>date('Y-m-d',strtotime('+1 month'))];}
    private function manual(array $rule): void{$settings=ict_retention_defaults();$settings['rules']['analytics']=$rule;execute_query('INSERT INTO site_settings(setting_key,setting_value) VALUES(:key,:value) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',['key'=>'data_retention_draft_json','value'=>json_encode($settings)]);}
    public function testPartialOrMalformedManualDocumentsNeverReconcile(): void
    {
        foreach ([json_encode(['rules'=>['analytics'=>['period'=>365,'unit'=>'days','trigger'=>'Event date']]]), '{bad', json_encode(array_replace(ict_retention_defaults(), ['unexpected'=>true]))] as $raw) {
            execute_query('UPDATE site_settings SET setting_value=:value WHERE setting_key=:key', ['value'=>$raw,'key'=>ICT_RETENTION_KEY]);
            $report=ict_analytics_retention_report();
            $this->assertFalse($report['manual_rule_configured']);$this->assertFalse($report['periods_reconciled']);$this->assertTrue($report['disposal_paused']);$this->assertFalse($report['apply_available']);
        }
    }
    public function testMissingInvalidAndTamperedUnpausedHoldsAlwaysPause(): void
    {
        $this->assertFalse(ict_analytics_hold_state()['configured']);$this->assertTrue(ict_analytics_disposal_paused());
        foreach(['{bad',json_encode(['paused'=>false]),json_encode(array_replace(ict_analytics_hold_defaults(),['revision'=>1,'paused'=>false,'reviewed_by'=>$this->reviewer,'reviewed_at'=>gmdate('Y-m-d\TH:i:s\Z')]))] as $raw){
            execute_query('INSERT INTO analytics_settings(setting_key,setting_value) VALUES(:key,:value) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',['key'=>ICT_ANALYTICS_HOLD_KEY,'value'=>$raw]);
            $hold=ict_analytics_hold_state();$this->assertFalse($hold['configured']);$this->assertTrue($hold['invalid']);
            $report=ict_analytics_retention_report();$this->assertTrue($report['disposal_paused']);$this->assertFalse($report['apply_available']);
        }
    }
    public function testAuthenticatedSaveRecordsServerIdentityAndRejectsStaleReview(): void
    {
        ict_analytics_hold_save($this->input(),$this->reviewer);$hold=ict_analytics_hold_state();
        $this->assertTrue($hold['configured']);$this->assertTrue($hold['paused']);$this->assertSame($this->reviewer,$hold['reviewed_by']);$this->assertSame(1,$hold['revision']);
        try{ict_analytics_hold_save($this->input(),$this->reviewer);$this->fail('Stale overwrite accepted');}catch(InvalidArgumentException){$this->addToAssertionCount(1);}
        $this->assertSame($hold,ict_analytics_hold_state());
        ict_analytics_hold_save(array_replace($this->input(),['revision'=>1]),$this->reviewer);$this->assertSame(2,ict_analytics_hold_state()['revision']);
    }
    public function testInvalidFieldsIdentityAndDatesNeverSave(): void
    {
        foreach([['paused'=>false],['reviewed_by'=>999],['reason'=>''],['reason'=>[]],['reference'=>str_repeat('x',121)],['next_review_date'=>'2026-02-30'],['revision'=>-1]] as $change){
            try{ict_analytics_hold_save(array_replace($this->input(),$change),$this->reviewer);$this->fail('Invalid field accepted');}catch(InvalidArgumentException){$this->addToAssertionCount(1);}
        }
        try{ict_analytics_hold_save($this->input(),0);$this->fail('Anonymous review accepted');}catch(InvalidArgumentException){$this->addToAssertionCount(1);}
        $this->assertFalse(ict_analytics_hold_state()['configured']);
    }
    public function testOverdueDateDoesNotReleaseHoldOrDeleteRecords(): void
    {
        ict_analytics_hold_save(array_replace($this->input(),['next_review_date'=>'2000-01-01']),$this->reviewer);
        $before=(int)fetch_one('SELECT COUNT(*) AS total FROM analytics_events')['total'];
        $report=ict_analytics_retention_report();$this->assertTrue($report['review_overdue']);$this->assertTrue($report['disposal_paused']);
        run_analytics_retention_cleanup();$this->assertSame($before,(int)fetch_one('SELECT COUNT(*) AS total FROM analytics_events')['total']);
    }
    public function testDryRunStrictCutoffCountsOnlySyntheticOldRowsWithoutWrites(): void
    {
        $now=new DateTimeImmutable('2001-01-02 00:00:00');$cutoff=$now->modify('-365 days')->format('Y-m-d H:i:s');
        $baseline=ict_analytics_retention_report($now);$this->assertSame(0,$baseline['candidate_count'],'Controlled window must exclude baseline rows');
        foreach(['1999-01-01 00:00:00',$cutoff,'2001-01-01 00:00:00'] as $created){execute_query("INSERT INTO analytics_events(event_type,path,target_url,target_group,ip_address,user_agent,referrer,created_at) VALUES('external_link_click','c4-synthetic','','external','','','',:created)",['created'=>$created]);}
        $before=(int)fetch_one('SELECT COUNT(*) AS total FROM analytics_events')['total'];$settings=(int)fetch_one('SELECT COUNT(*) AS total FROM analytics_settings')['total'];
        $report=ict_analytics_retention_report($now);$this->assertSame(1,$report['candidate_count']);$this->assertSame($cutoff,$report['cutoff']);$this->assertTrue($report['periods_reconciled']);
        $this->assertSame($before,(int)fetch_one('SELECT COUNT(*) AS total FROM analytics_events')['total']);$this->assertSame($settings,(int)fetch_one('SELECT COUNT(*) AS total FROM analytics_settings')['total']);
    }
    public function testMonthsCustomTriggerAndDifferentPeriodsAreVisibleNotAutoConverted(): void
    {
        foreach([['period'=>12,'unit'=>'months','trigger'=>'Event date'],['period'=>180,'unit'=>'days','trigger'=>'Event date'],['period'=>365,'unit'=>'days','trigger'=>'Last activity']] as $rule){
            $this->manual($rule);$report=ict_analytics_retention_report();$this->assertFalse($report['periods_reconciled']);$this->assertSame($rule,$report['manual_rule']);$this->assertTrue($report['disposal_paused']);
        }
        execute_query('DELETE FROM site_settings WHERE setting_key=:key',['key'=>'data_retention_draft_json']);
        $this->assertFalse(ict_analytics_retention_report()['manual_rule_configured']);
        execute_query('UPDATE analytics_settings SET setting_value=:days WHERE setting_key=:key',['days'=>'invalid','key'=>'events_retention_days']);
        $report=ict_analytics_retention_report();$this->assertNull($report['candidate_count']);$this->assertNull($report['runtime_days']);
    }
    public function testCommandRejectsApplyBeforeConnectingAndSourceContainsNoDelete(): void
    {
        $path=__DIR__.'/../cli/analytics_retention_review.php';$command=escapeshellarg(PHP_BINARY).' '.escapeshellarg($path).' --apply 2>&1';
        exec($command,$output,$code);$this->assertSame(2,$code);$this->assertStringContainsString('No apply operation',implode("\n",$output));
        $source=file_get_contents(__DIR__.'/../includes/analytics_retention_review.php');$this->assertStringNotContainsString('DELETE FROM',$source);$this->assertStringNotContainsString('ensure_',$source);
    }
}
