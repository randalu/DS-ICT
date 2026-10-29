<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../cli/migrate_page_media_release.php';

final class PageMediaMigrationPlanTest extends TestCase
{
    private string $directory;
    private string $historical;
    private string $release;
    private array $additional=[];

    protected function setUp(): void
    {
        $this->directory=__DIR__.'/../output/page-media-ledger-'.bin2hex(random_bytes(8));
        mkdir($this->directory,0700,true);
        $this->historical=$this->directory.'/2026-04-19-cta-faq-review-month.sql';
        $this->release=$this->directory.'/2026-10-28-page-media-galleries.sql';
        file_put_contents($this->historical,"SELECT 1;\n");
        file_put_contents($this->release,"SELECT 2;\n");
    }

    protected function tearDown(): void
    {
        foreach($this->additional as $file){unlink($file);}
        unlink($this->historical);unlink($this->release);rmdir($this->directory);
    }

    private function plan(array $ledger): array
    {
        return release_migration_plan($ledger,[$this->historical,$this->release],[basename($this->release)]);
    }

    public function testHistoricalStatusesAreReportedWithoutReplayingOrChangingHistory(): void
    {
        foreach(['skipped','baselined','failed','running','',null] as $status){
            $ledger=[basename($this->historical)=>['status'=>$status,'checksum'=>hash_file('sha256',$this->historical)],basename($this->release)=>['status'=>'applied','checksum'=>hash_file('sha256',$this->release)]];
            $before=$ledger;$result=$this->plan($ledger);
            $this->assertSame([],$result['pending']);
            $this->assertSame([basename($this->historical)],$result['historicalStatuses']);
            $this->assertSame($before,$ledger);
        }
    }

    public function testNonAppliedReleaseStatusesStillStopThePlan(): void
    {
        foreach(['skipped','baselined','failed','running','',null] as $status){
            try{
                $this->plan([basename($this->historical)=>['status'=>'applied'],basename($this->release)=>['status'=>$status]]);
                $this->fail('A non-applied release migration was accepted.');
            }catch(RuntimeException $error){$this->assertStringContainsString('Non-applied release ledger status: '.basename($this->release),$error->getMessage());}
        }
    }

    public function testOnlyAllowlistedMissingMigrationsBecomePending(): void
    {
        $result=$this->plan([basename($this->historical)=>['status'=>'baselined']]);
        $this->assertSame([basename($this->release)=>$this->release],$result['pending']);
    }

    public function testAnUnreviewedMissingMigrationStillStopsThePlan(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unreviewed pending migration: '.basename($this->historical));
        $this->plan([]);
    }

    public function testReleaseChecksumMismatchStillStopsThePlan(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Release checksum mismatch: '.basename($this->release));
        $this->plan([basename($this->historical)=>[],basename($this->release)=>['status'=>'applied','checksum'=>str_repeat('0',64)]]);
    }

    public function testHistoricalChecksumDriftIsDiagnosticAndDoesNotBecomePending(): void
    {
        $result=$this->plan([basename($this->historical)=>['status'=>'baselined','checksum'=>str_repeat('0',64)],basename($this->release)=>['status'=>'applied']]);
        $this->assertSame(1,$result['historicalDrift']);
        $this->assertSame([],$result['pending']);
    }

    public function testVersionOnlyLegacyHistoryDoesNotRequireAStatusColumn(): void
    {
        $result=$this->plan([basename($this->historical)=>[],basename($this->release)=>[]]);
        $this->assertSame([],$result['pending']);
        $this->assertSame([],$result['historicalStatuses']);
    }

    public function testCanonicalChecksumsWorkWithWindowsLineEndings(): void
    {
        $checksum=hash_file('sha256',$this->release);
        file_put_contents($this->release,"SELECT 2;\r\n");
        $result=$this->plan([basename($this->historical)=>[],basename($this->release)=>['status'=>'applied','checksum'=>$checksum]]);
        $this->assertSame([],$result['pending']);
        $this->assertSame(0,$result['historicalDrift']);
    }

    private function fixture(string $name,string $sql): string
    {
        $file=$this->directory.'/'.$name;
        file_put_contents($file,$sql);$this->additional[]=$file;
        return $file;
    }

    public function testAllUnreviewedPendingMigrationsAreReportedTogether(): void
    {
        $other=$this->fixture('2026-06-19-unreviewed-content.sql',"SELECT 3;\n");
        try{
            release_migration_plan([],[$this->release,$other,$this->historical],[basename($this->release)]);
            $this->fail('Unknown historical migrations were accepted.');
        }catch(PageMediaPlanBlocked $error){
            $this->assertSame([
                'Unreviewed pending migration: '.basename($this->historical),
                'Unreviewed pending migration: '.basename($other),
            ],$error->reasons);
            $this->assertStringNotContainsString($this->directory,$error->getMessage());
        }
    }

    public function testUnknownPendingDoesNotHideFailedReleaseAndChecksumGates(): void
    {
        try{
            $this->plan([basename($this->release)=>['status'=>'failed','checksum'=>str_repeat('0',64)]]);
            $this->fail('A failed/checksum-mismatched release was accepted.');
        }catch(PageMediaPlanBlocked $error){
            $this->assertCount(3,$error->reasons);
            $this->assertStringContainsString('Unreviewed pending migration: '.basename($this->historical),$error->getMessage());
            $this->assertStringContainsString('Non-applied release ledger status: '.basename($this->release),$error->getMessage());
            $this->assertStringContainsString('Release checksum mismatch: '.basename($this->release),$error->getMessage());
        }
    }

    public function testMissingReleaseFileIsNotSilentlySkipped(): void
    {
        $this->expectException(PageMediaPlanBlocked::class);
        $this->expectExceptionMessage('Missing release migration file: '.basename($this->release));
        release_migration_plan([basename($this->historical)=>[]],[$this->historical],[basename($this->release)]);
    }

    public function testReviewedPrerequisitesAreExactAdditiveReleaseDependencies(): void
    {
        $names=[
            '2026-09-01-site-audit.sql','2026-09-12-site-audit-progress.sql',
            '2026-10-15-seo-manager.sql','2026-10-16-site-audit-portal.sql',
            '2026-10-25-activities.sql','2026-10-25-activity-entry-galleries.sql',
            '2026-10-25-shared-admin-openai.sql','2026-10-25-image-placement-alt.sql',
        ];
        $this->assertSame($names,array_keys(release_reviewed_prerequisites()));
        $files=array_map(static fn(string $name):string=>__DIR__.'/../database/migrations/'.$name,$names);
        $plan=release_migration_plan([],$files,$names);
        $this->assertCount(8,$plan['pending']);
        $contracts=[];
        foreach($files as $file){
            $sql=file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/\b(?:INSERT|UPDATE|DELETE|DROP|TRUNCATE|REPLACE)\s+(?:INTO|FROM|TABLE)\b/i',$sql);
            $contracts+=release_created_contracts($sql);
        }
        $this->assertCount(19,$contracts);
        $this->assertSame(['admin_id','request_token'],$contracts['admin_ai_requests']['unique']['admin_ai_once']);
        $this->assertSame(['admin_id','request_token'],$contracts['seo_requests']['unique']['seo_request_once']);
        $this->assertSame(['content_type','content_id','slot','child_id'],$contracts['media_placement_alts']['unique']['PRIMARY']);
        $this->assertSame('bigint unsigned',$contracts['activity_pages']['columns']['id']['COLUMN_TYPE']);
        $this->assertSame("enum('running','done','failed')",$contracts['site_audit_runs']['columns']['status']['COLUMN_TYPE']);
        $this->assertCount(17,release_migration_names());
        $this->assertSame([],array_diff($names,release_migration_names()));
        $this->assertNotContains('2026-06-19-placeholder-media-assignments.sql',release_migration_names());
    }

    public function testAReviewedNameCannotAuthorizeDifferentSql(): void
    {
        $name='2026-10-25-shared-admin-openai.sql';
        $file=$this->fixture($name,file_get_contents(__DIR__.'/../database/migrations/'.$name)."\nDELETE FROM media_assets;\n");
        $this->expectException(PageMediaPlanBlocked::class);
        $this->expectExceptionMessage('Reviewed prerequisite SQL changed: '.$name);
        release_migration_plan([],[$file],[$name]);
    }

    public function testAuditProgressGuardsRecognizeCompatibleExistingColumns(): void
    {
        $columns=release_audit_progress_columns();
        $columns['total_pages']['COLUMN_TYPE']='int(10) unsigned';
        $columns['log_offset']['COLUMN_TYPE']='bigint(20) unsigned';
        $columns['log_offset']['COLUMN_DEFAULT']=0;
        $columns['triggered_by']['COLUMN_DEFAULT']="'admin'";
        $this->assertSame([],release_audit_progress_plan($columns));
        unset($columns['log_offset']);
        $this->assertSame(['ALTER TABLE site_audit_runs ADD COLUMN log_offset BIGINT UNSIGNED NOT NULL DEFAULT 0'],release_audit_progress_plan($columns));
    }

    public function testAuditProgressRejectsTypeNullabilityAndDefaultDriftInsteadOfOverwritingIt(): void
    {
        foreach([
            ['total_pages','COLUMN_TYPE','int'],['log_offset','COLUMN_TYPE','int unsigned'],
            ['triggered_by','COLUMN_TYPE','varchar(99)'],['triggered_by','IS_NULLABLE','YES'],
            ['total_pages','COLUMN_DEFAULT',null],['log_offset','COLUMN_DEFAULT','1'],
            ['triggered_by','COLUMN_DEFAULT','other'],
        ] as [$column,$field,$value]){
            $columns=release_audit_progress_columns();$columns[$column][$field]=$value;
            try{release_audit_progress_plan($columns);$this->fail('Incompatible audit progress schema accepted.');}
            catch(RuntimeException $error){$this->assertStringContainsString('site_audit_runs.'.$column,$error->getMessage());}
        }
    }

    public function testPartialAuditSchemaAddsOnlyMissingColumnsAndChecksPostconditions(): void
    {
        $columns=['triggered_by'=>release_audit_progress_columns()['triggered_by']];$executed=[];
        release_apply_audit_progress(
            static function()use(&$columns):array{return $columns;},
            static function(string $sql)use(&$columns,&$executed):void{
                $executed[]=$sql;preg_match('/ADD COLUMN ([a-z_]+)/',$sql,$match);
                $columns[$match[1]]=release_audit_progress_columns()[$match[1]];
            }
        );
        $this->assertCount(2,$executed);
        $this->assertStringContainsString('ADD COLUMN total_pages',$executed[0]);
        $this->assertStringContainsString('ADD COLUMN log_offset',$executed[1]);
        $this->assertStringNotContainsString('IF NOT EXISTS',implode("\n",$executed));
        $this->assertSame([],release_audit_progress_plan($columns));
        $executed=[];
        release_apply_audit_progress(static fn():array=>$columns,static function(string $sql)use(&$executed):void{$executed[]=$sql;});
        $this->assertSame([],$executed,'Existing compatible columns must not be rewritten.');
    }

    public function testAuditGuardValidatesAllExistingColumnsBeforeAnyAlter(): void
    {
        $columns=['triggered_by'=>['COLUMN_TYPE'=>'text','IS_NULLABLE'=>'NO','COLUMN_DEFAULT'=>'admin']];$executed=[];
        try{
            release_apply_audit_progress(static fn():array=>$columns,static function(string $sql)use(&$executed):void{$executed[]=$sql;});
            $this->fail('Incompatible existing column accepted.');
        }catch(RuntimeException){$this->assertSame([],$executed);}
    }

    public function testAuditGuardRejectsMissingPostconditions(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Audit progress columns missing after ALTER.');
        release_apply_audit_progress(static fn():array=>[],static function(string $sql):void{});
    }

    public function testBackupLiteralsKeepJsonTextAndBinaryBytesDistinct(): void
    {
        $this->assertSame('NULL',release_backup_literal(null,true));
        $this->assertSame("''",release_backup_literal(''));
        $this->assertSame('CAST(0x00015cff AS BINARY)',release_backup_literal("\x00\x01\\\xff"));
        $json='{"quote":"a\\\"b","unicode":"山"}';
        $this->assertSame('CONVERT(0x'.bin2hex($json).' USING utf8mb4)',release_backup_literal($json,true));
    }

    public function testTriggerBackupRetainsOriginalModeCharsetAndDelimiterProtectedBody(): void
    {
        $ddl='CREATE DEFINER=`owner`@`localhost` TRIGGER `example` BEFORE INSERT ON `sample` FOR EACH ROW BEGIN SET NEW.id=NEW.id; END';
        $sql=release_backup_trigger(['SQL Original Statement'=>$ddl,'sql_mode'=>'ANSI_QUOTES','character_set_client'=>'utf8mb4','collation_connection'=>'utf8mb4_unicode_ci']);
        foreach(['sql_mode'=>'ANSI_QUOTES','character_set_client'=>'utf8mb4','collation_connection'=>'utf8mb4_unicode_ci'] as $setting=>$value){
            $this->assertStringContainsString('SET SESSION '.$setting.'=CONVERT(0x'.bin2hex($value).' USING utf8mb4);',$sql);
        }
        $this->assertMatchesRegularExpression('/DELIMITER (ict_end_[a-f0-9]{24})\n'.preg_quote($ddl,'/').'\1\nDELIMITER ;\n/',$sql);
    }

    public function testPullHelperKeepsExactBaselineAndNoOverwriteGatesBeforePull(): void
    {
        $source=file_get_contents(__DIR__.'/../cli/pull_page_media_release.sh');
        foreach([
            '"$current" == "$base" || "$current" == "$target"',
            '"$(git rev-parse FETCH_HEAD)" == "$target"',
            'cmp -s "$packet/allowlist.txt" "$packet/changed.txt"',
            'git ls-files --others -- "${changed[@]}"',
            'git -c merge.autoStash=false -c rebase.autoStash=false pull --ff-only --no-rebase origin "$target"',
            'extension_loaded("pdo_mysql")','extension_loaded("zlib")','function_exists("proc_open")',
            'set +x',
        ] as $guard){$this->assertStringContainsString($guard,$source);}
        $this->assertLessThan(strpos($source,'pull --ff-only'),strpos($source,'"$packet/relay.php"'));
        $this->assertStringNotContainsString('git reset',$source);
        $this->assertStringNotContainsString('--force',$source);
        $this->assertStringNotContainsString('cp .env',$source);
    }

    public function testHistoricalProgressChecksumCanBeVerifiedWithoutChangingTheLedger(): void
    {
        $name='2026-09-12-site-audit-progress.sql';
        $file=__DIR__.'/../database/migrations/'.$name;
        $ledger=[$name=>['status'=>'applied','checksum'=>str_repeat('0',64)]];$before=$ledger;
        $plan=release_migration_plan($ledger,[$file],[$name],static fn():array=>release_audit_progress_columns());
        $this->assertSame([],$plan['pending']);
        $this->assertSame([$name],$plan['verifiedProgressHistory']);
        $this->assertSame(1,$plan['historicalDrift']);
        $this->assertSame($before,$ledger);
    }

    public function testHistoricalProgressChecksumRequiresAllThreeCompatibleColumns(): void
    {
        $name='2026-09-12-site-audit-progress.sql';$file=__DIR__.'/../database/migrations/'.$name;
        $variants=[[],true];
        foreach(array_keys(release_audit_progress_columns()) as $column){$columns=release_audit_progress_columns();unset($columns[$column]);$variants[]=$columns;}
        foreach([['total_pages','COLUMN_TYPE','int'],['log_offset','IS_NULLABLE','YES'],['triggered_by','COLUMN_DEFAULT','other'],['total_pages','EXTRA','VIRTUAL GENERATED']] as [$column,$field,$value]){
            $columns=release_audit_progress_columns();$columns[$column][$field]=$value;$variants[]=$columns;
        }
        foreach($variants as $columns){
            try{release_migration_plan([$name=>['status'=>'applied','checksum'=>str_repeat('0',64)]],[$file],[$name],static fn()=>$columns);$this->fail('Incomplete or incompatible schema accepted.');}
            catch(PageMediaPlanBlocked $error){$this->assertStringContainsString('Release checksum mismatch: '.$name,$error->getMessage());}
        }
    }

    public function testHistoricalProgressChecksumCannotBeAcceptedWithoutAnInspector(): void
    {
        $name='2026-09-12-site-audit-progress.sql';
        $this->expectException(PageMediaPlanBlocked::class);
        release_migration_plan([$name=>['status'=>'applied','checksum'=>str_repeat('0',64)]],[__DIR__.'/../database/migrations/'.$name],[$name]);
    }

    public function testHistoricalProgressCompatibilityNeverApprovesFailedOrUnknownStatus(): void
    {
        $name='2026-09-12-site-audit-progress.sql';$file=__DIR__.'/../database/migrations/'.$name;
        foreach(['failed','skipped','baselined','',null] as $status){
            $inspections=0;
            try{release_migration_plan([$name=>['status'=>$status,'checksum'=>str_repeat('0',64)]],[$file],[$name],static function()use(&$inspections):array{$inspections++;return release_audit_progress_columns();});$this->fail('Unapproved status accepted.');}
            catch(PageMediaPlanBlocked){$this->assertSame(0,$inspections);}
        }
    }

    public function testChangedProgressSqlStillFailsEvenWithCompatibleSchema(): void
    {
        $name='2026-09-12-site-audit-progress.sql';
        $file=$this->fixture($name,file_get_contents(__DIR__.'/../database/migrations/'.$name)."\nDELETE FROM site_audit_runs;\n");
        $this->expectException(PageMediaPlanBlocked::class);
        $this->expectExceptionMessage('Reviewed prerequisite SQL changed: '.$name);
        release_migration_plan([$name=>['status'=>'applied','checksum'=>str_repeat('0',64)]],[$file],[$name],static fn():array=>release_audit_progress_columns());
    }

    public function testProgressCompatibilityDoesNotRelaxOtherReleaseChecksums(): void
    {
        foreach(['2026-10-25-shared-admin-openai.sql','2026-10-28-page-media-galleries.sql'] as $name){
            try{release_migration_plan([$name=>['status'=>'applied','checksum'=>str_repeat('0',64)]],[__DIR__.'/../database/migrations/'.$name],[$name],static fn():array=>release_audit_progress_columns());$this->fail('Other release checksum bypassed.');}
            catch(PageMediaPlanBlocked $error){$this->assertStringContainsString('Release checksum mismatch: '.$name,$error->getMessage());}
        }
    }
}
