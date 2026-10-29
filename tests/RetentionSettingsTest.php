<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/records_workbook.php';

final class RetentionSettingsTest extends TestCase
{
    private static string $spool;
    private static string|false $previousSpool;

    public static function setUpBeforeClass(): void
    {
        self::$previousSpool = getenv('ICT_RECORDS_TMP_DIR');
        self::$spool = sys_get_temp_dir().'/ict-retention-test-'.bin2hex(random_bytes(8));
        if (!mkdir(self::$spool, 0700)) { throw new RuntimeException('Could not prepare private test spool.'); }
        if (PHP_OS_FAMILY === 'Windows') {
            $script = '$ErrorActionPreference="Stop"; $path=[Text.Encoding]::UTF8.GetString([Convert]::FromBase64String("'.base64_encode(self::$spool).'")); $acl=[IO.Directory]::GetAccessControl($path); $acl.SetAccessRuleProtection($true,$false); $current=[Security.Principal.WindowsIdentity]::GetCurrent().User; foreach($sid in @($current,[Security.Principal.SecurityIdentifier]::new("S-1-5-18"),[Security.Principal.SecurityIdentifier]::new("S-1-5-32-544"))) { $rule=[Security.AccessControl.FileSystemAccessRule]::new($sid,"FullControl","ContainerInherit,ObjectInherit","None","Allow"); $acl.AddAccessRule($rule) }; [IO.Directory]::SetAccessControl($path,$acl)';
            $process = proc_open(['powershell.exe','-NoProfile','-NonInteractive','-EncodedCommand',base64_encode(mb_convert_encoding($script,'UTF-16LE','UTF-8'))], [1=>['pipe','w'],2=>['pipe','w']], $pipes, null, null, ['bypass_shell'=>true]);
            if (!is_resource($process)) { rmdir(self::$spool); throw new RuntimeException('Could not secure test spool.'); }
            $output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
            if (proc_close($process)!==0) { rmdir(self::$spool); throw new RuntimeException('Could not secure test spool: '.$output); }
        }
        putenv('ICT_RECORDS_TMP_DIR='.self::$spool);
    }

    public static function tearDownAfterClass(): void
    {
        self::$previousSpool === false ? putenv('ICT_RECORDS_TMP_DIR') : putenv('ICT_RECORDS_TMP_DIR='.self::$previousSpool);
        if (!rmdir(self::$spool)) { throw new RuntimeException('Test workbook spool cleanup failed.'); }
    }

    public function testInvalidRulesAndTextAreRejected(): void
    {
        foreach (['missing','zero','unit','nested','unknown','extra_top','extra_row','bool','utf8','control'] as $case) {
            $input = ict_retention_defaults();
            match ($case) {
                'missing' => $input['rules']['payments'] = null,
                'zero' => $input['rules']['enquiries']['period'] = 0,
                'unit' => $input['rules']['enquiries']['unit'] = 'forever',
                'nested' => $input['hold_note'] = [],
                'unknown' => $input['rules']['invented'] = [],
                'extra_top' => $input['extra'] = true,
                'extra_row' => $input['rules']['enquiries']['extra'] = true,
                'bool' => $input['rules']['enquiries']['period'] = true,
                'utf8' => $input['hold_note'] = "\xFF",
                'control' => $input['rules']['analytics']['trigger'] = "Event\x00date",
            };
            try { ict_retention_validate($input); $this->fail('Invalid input accepted: '.$case); }
            catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
        $input = ict_retention_defaults(); $input['hold_note'] = '<script>unsafe</script>';
        $html = ict_retention_draft_html($input);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('Working draft for review', $html);
        $this->assertSame(10, substr_count($html, '<li>'));
        $this->assertStringContainsString('Unsuccessful enquiries: 6 months.', render_public_content($html));
    }

    public function testMissingInvalidAndConfiguredStatesAreDistinctAndInvalidCannotSave(): void
    {
        db()->beginTransaction();
        try {
            execute_query('DELETE FROM site_settings WHERE setting_key=:key', ['key'=>ICT_RETENTION_KEY]);
            $state=ict_retention_state();$this->assertFalse($state['configured']);$this->assertFalse($state['invalid']);$this->assertSame('missing',$state['token']);
            ict_retention_save(ict_retention_defaults(),$state['token']);
            $this->assertTrue(ict_retention_state()['configured']);
            execute_query('UPDATE site_settings SET setting_value=:raw WHERE setting_key=:key',['raw'=>'{broken','key'=>ICT_RETENTION_KEY]);
            $state=ict_retention_state();$this->assertTrue($state['invalid']);$this->assertFalse($state['configured']);
            try { ict_retention_save(ict_retention_defaults(),$state['token']);$this->fail('Invalid saved document replaced'); }
            catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
            $this->assertSame('{broken',fetch_one('SELECT setting_value FROM site_settings WHERE setting_key=:key',['key'=>ICT_RETENTION_KEY])['setting_value']);
            $this->assertTrue(db()->inTransaction());
        } finally { db()->rollBack(); }
    }

    public function testSettingsOnlySavePreservesAllPoliciesRuntimePrivacyAndHoldAndRejectsStaleInput(): void
    {
        db()->beginTransaction();
        try {
            execute_query('DELETE FROM site_settings WHERE setting_key=:key',['key'=>ICT_RETENTION_KEY]);
            $baseline=$this->protectedState();
            $input=ict_retention_defaults();$input['rules']['enquiries']['period']=9;
            ict_retention_save($input,'missing');
            $this->assertSame($baseline,$this->protectedState());
            $this->assertSame($input,ict_retention_state()['settings']);
            try { ict_retention_save(ict_retention_defaults(),'missing');$this->fail('Stale first-save token accepted'); }
            catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
            $token=ict_retention_state()['token'];
            $input['rules']['enquiries']['period']=12;
            ict_retention_save($input,$token);
            try { ict_retention_save(ict_retention_defaults(),$token);$this->fail('Stale overwrite accepted'); }
            catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
            $this->assertSame($input,ict_retention_settings());
            $this->assertSame($baseline,$this->protectedState());
            $this->assertTrue(db()->inTransaction());
        } finally { db()->rollBack(); }
    }

    public function testUnavailableSchemaFailsWithoutCreation(): void
    {
        $source='putenv("DB_NAME=ict_retention_schema_unavailable_test");require '.var_export(__DIR__.'/../includes/retention_settings.php',true).';$s=ict_retention_state();if($s["available"]){exit(9);}try{ict_retention_save(ict_retention_defaults(),"missing");exit(8);}catch(RuntimeException $e){echo "schema-unavailable-blocked";}';
        $process=proc_open([PHP_BINARY,'-d','session.save_path='.session_save_path(),'-r',$source],[1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
        $this->assertIsResource($process);
        $output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($process);
        $this->assertSame(0,$code,$output);
        $this->assertStringContainsString('schema-unavailable-blocked',$output);
        $source=file_get_contents(__DIR__.'/../includes/retention_settings.php');
        $this->assertStringNotContainsString('ensure_',$source);
        $this->assertStringNotContainsString('UPDATE static_pages',$source);
    }

    private function protectedState(): array
    {
        return [fetch_all('SELECT setting_key,setting_value FROM site_settings WHERE setting_key<>:key ORDER BY setting_key',['key'=>ICT_RETENTION_KEY]),
            fetch_all('SELECT * FROM analytics_settings ORDER BY setting_key'),fetch_all('SELECT * FROM static_pages ORDER BY id')];
    }

    public function testWorkbookSnapshotEscapesTextPreservesFormulasAndPristineTemplate(): void
    {
        $before=hash_file('sha256',ICT_RECORDS_TEMPLATE);
        $input=ict_retention_defaults();$input['rules']['enquiries']['period']=9;$input['rules']['enquiries']['trigger']='=Text with & and < characters';
        $path=ict_records_workbook_snapshot($input);
        try {
            $this->assertSame(ict_records_temp_directory(),realpath(dirname($path)));
            $zip=new ZipArchive();$this->assertTrue($zip->open($path)===true);
            $doc=new DOMDocument();$this->assertTrue($doc->loadXML($zip->getFromName('xl/worksheets/sheet2.xml')));
            $cells=[];foreach($doc->getElementsByTagName('c') as $cell){$cells[$cell->getAttribute('r')]=$cell;}
            $this->assertSame('9',$cells['C6']->textContent);
            $this->assertSame($input['rules']['enquiries']['trigger'],$cells['E6']->textContent);
            $this->assertSame('inlineStr',$cells['E6']->getAttribute('t'));
            $this->assertSame(0,$cells['E6']->getElementsByTagName('f')->length);
            $this->assertStringContainsString('fullCalcOnLoad="1"',$zip->getFromName('xl/workbook.xml'));
            $pristine=new ZipArchive();$pristine->open(ICT_RECORDS_TEMPLATE);
            foreach(['xl/worksheets/sheet1.xml','xl/worksheets/sheet2.xml'] as $sheet){
                $original=new DOMDocument();$original->loadXML($pristine->getFromName($sheet));
                $generated=new DOMDocument();$generated->loadXML($zip->getFromName($sheet));
                $formulas=static fn(DOMDocument $xml):array=>array_map(static fn(DOMNode $node):string=>$node->textContent,iterator_to_array($xml->getElementsByTagName('f')));
                $this->assertSame($formulas($original),$formulas($generated));
            }
            $pristine->close();$zip->close();
        } finally { unlink($path); }
        $this->assertSame($before,hash_file('sha256',ICT_RECORDS_TEMPLATE));
    }

    public function testPublicOrMissingSpoolIsRejectedAndFailedWorkbookLeavesNoTemporaryFile(): void
    {
        $previous=getenv('ICT_RECORDS_TMP_DIR');
        try {
            // A broadly accessible system temp directory is not private merely because it is outside webroot.
            if (PHP_OS_FAMILY === 'Windows') {
                $inherited = self::$spool.'/inherited'; mkdir($inherited, 0700);
                try { $this->assertFalse(ict_records_spool_private($inherited)); } finally { rmdir($inherited); }
            }
            foreach([dirname(__DIR__).'/output',dirname(__DIR__).'/output/does-not-exist-spool'] as $directory){
                putenv('ICT_RECORDS_TMP_DIR='.$directory);
                $this->assertFalse(ict_records_workbook_available());
                try{ict_records_workbook_snapshot(ict_retention_defaults());$this->fail('Unsafe spool accepted');}catch(RuntimeException){$this->addToAssertionCount(1);}
            }
            $previous===false?putenv('ICT_RECORDS_TMP_DIR'):putenv('ICT_RECORDS_TMP_DIR='.$previous);
            $directory=ict_records_temp_directory();$before=glob($directory.'/ict-records-*');
            $broken=tempnam($directory,'ict-test-template-');file_put_contents($broken,'Not a ZIP workbook');
            try{ict_records_workbook_snapshot(ict_retention_defaults(),$broken);$this->fail('Invalid workbook accepted');}catch(RuntimeException){$this->addToAssertionCount(1);}finally{unlink($broken);}
            $this->assertSame($before,glob($directory.'/ict-records-*'));
        }finally{$previous===false?putenv('ICT_RECORDS_TMP_DIR'):putenv('ICT_RECORDS_TMP_DIR='.$previous);}
    }
}
