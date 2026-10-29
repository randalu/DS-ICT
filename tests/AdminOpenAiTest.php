<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../includes/seo_manager.php';
require_once __DIR__.'/../includes/media_alt.php';

final class AdminOpenAiTest extends TestCase
{
    public function testServerConfigurationReplacesLegacySessionKeysAndSeparatesModels():void
    {
        $names=['OPENAI_ADMIN_API_KEY','OPENAI_ALT_MODEL','OPENAI_SEO_MODEL','OPENAI_AUDIT_MODEL'];$before=[];
        foreach($names as$name)$before[$name]=getenv($name);
        $session=$_SESSION;
        try{
            putenv('OPENAI_ADMIN_API_KEY=synthetic-local-only-key');putenv('OPENAI_ALT_MODEL=vision-fixture');putenv('OPENAI_SEO_MODEL=seo-fixture');putenv('OPENAI_AUDIT_MODEL=audit-fixture');
            $_SESSION['_ict_seo_connection']=['key'=>'legacy-secret'];
            $this->assertSame('synthetic-local-only-key',seo_connection()['key']);$this->assertArrayNotHasKey('_ict_seo_connection',$_SESSION);
            $this->assertSame('vision-fixture',admin_openai_model('alt'));$this->assertSame('audit-fixture',admin_openai_model('audit'));
            $version=admin_openai_version();putenv('OPENAI_ADMIN_API_KEY=rotated-synthetic-local-key');$this->assertNotSame($version,admin_openai_version());
            $response=seo_openai_response('synthetic',['model'=>'browser-choice'],static function(array$payload):array{
                self::assertSame('seo-fixture',$payload['model']);self::assertFalse($payload['store']);
                return ['status'=>'completed','output'=>[['content'=>[['type'=>'output_text','text'=>'A photograph description']]]]];
            });$this->assertSame('A photograph description',$response['text']);
            putenv('OPENAI_ADMIN_API_KEY');$this->assertNull(seo_connection());
        }finally{foreach($before as$name=>$value){putenv($value===false?$name:$name.'='.$value);}$_SESSION=$session;}
    }
    public function testAltDefaultsPreservePlacementSpecificDescriptionsAndRejectStaleReplacement():void
    {
        if(!db_table_exists('media_asset_alt_revisions'))$this->markTestSkipped('Shared OpenAI migration required.');
        db()->beginTransaction();
        try{
            execute_query("INSERT INTO media_assets(file_path,file_name,mime_type,file_size,alt_text) VALUES(:path,'Alt fixture','image/jpeg',1,'')",['path'=>'alt-test-'.bin2hex(random_bytes(8)).'.jpg']);$id=(int)db()->lastInsertId();
            media_alt_assign($id,'A green hillside');$asset=fetch_one('SELECT * FROM media_assets WHERE id=:id',['id'=>$id]);$revision=media_alt_revision($asset);
            media_alt_assign($id,'A placement specific hillside');$this->assertSame('A green hillside',fetch_one('SELECT alt_text FROM media_assets WHERE id=:id',['id'=>$id])['alt_text']);
            media_alt_assign($id,'A hillside covered in tea',['update_asset_alt'=>'1','asset_alt_revision'=>$revision]);
            $this->assertSame('A hillside covered in tea',fetch_one('SELECT alt_text FROM media_assets WHERE id=:id',['id'=>$id])['alt_text']);
            try{media_alt_assign($id,'Stale wording',['update_asset_alt'=>'1','asset_alt_revision'=>$revision]);$this->fail('Stale replacement accepted');}catch(DomainException){$this->addToAssertionCount(1);}
            foreach(['<b>Photo</b>',str_repeat('a',256),"bad\ntext",'']as$bad){try{media_alt_validate($bad);$this->fail('Invalid alt accepted');}catch(InvalidArgumentException){$this->addToAssertionCount(1);}}
            $this->assertSame('nested/photo.jpg',media_alt_path_key('/assets/uploads/nested/photo.jpg'));
            execute_query("INSERT INTO media_assets(file_path,file_name,mime_type,file_size) VALUES(:path,'Alias fixture','image/jpeg',1)",['path'=>'uploads/'.$asset['file_path']]);
            $this->assertNull(media_alt_asset($asset['file_path']),'Ambiguous aliases must not be merged.');
        }finally{if(db()->inTransaction())db()->rollBack();}
    }
    public function testRequestReservationsEnforceDuplicateConcurrentAndQuotaBoundaries():void
    {
        if(!db_table_exists('admin_ai_requests'))$this->markTestSkipped('Shared AI migration required.');
        db()->beginTransaction();$admin=987654321;
        try{
            $token=bin2hex(random_bytes(16));$id=admin_openai_begin('alt',$token,$admin);
            try{admin_openai_begin('alt',bin2hex(random_bytes(16)),$admin);$this->fail('Concurrent request accepted');}catch(DomainException){$this->addToAssertionCount(1);}
            admin_openai_finish($id,'completed',['input'=>12,'output'=>5]);
            try{admin_openai_begin('alt',$token,$admin);$this->fail('Duplicate request accepted');}catch(DomainException){$this->addToAssertionCount(1);}
            $hourly=admin_ai_request_limit('alt','hour');$daily=admin_ai_request_limit('alt');
            for($i=1;$i<$hourly;$i++){execute_query("INSERT INTO admin_ai_requests(admin_id,request_token,feature,model,status,created_at) VALUES(:admin,:token,'alt','fixture','failed_or_unknown',NOW())",['admin'=>$admin,'token'=>bin2hex(random_bytes(16))]);}
            try{admin_openai_begin('alt',bin2hex(random_bytes(16)),$admin);$this->fail('Hourly limit ignored');}catch(InvalidArgumentException){$this->addToAssertionCount(1);}
            $this->assertSame((string)$hourly,(string)admin_openai_usage($admin)[0]['requests']);
            for($i=0;$i<$daily;$i++){execute_query("INSERT INTO admin_ai_requests(admin_id,request_token,feature,model,status,created_at) VALUES(:admin,:token,'alt','fixture','failed_or_unknown',NOW())",['admin'=>$admin+1,'token'=>bin2hex(random_bytes(16))]);}
            try{admin_openai_begin('alt',bin2hex(random_bytes(16)),$admin+2);$this->fail('Site daily limit ignored');}catch(InvalidArgumentException){$this->addToAssertionCount(1);}
        }finally{if(db()->inTransaction())db()->rollBack();}
    }
    public function testPaidSeoAndAuditUseSharedCredentialAndRevalidateLogin():void
    {
        if(!db_table_exists('admin_ai_requests')||!seo_ready())$this->markTestSkipped('Admin AI and SEO migrations required.');
        $before=$_SESSION;$key=getenv('OPENAI_ADMIN_API_KEY');$model=getenv('OPENAI_AUDIT_MODEL');
        db()->beginTransaction();
        try{
            putenv('OPENAI_ADMIN_API_KEY=synthetic-no-network-key');putenv('OPENAI_AUDIT_MODEL=audit-fixture');
            $suffix=bin2hex(random_bytes(8));$hash=password_hash($suffix,PASSWORD_DEFAULT);
            execute_query("INSERT INTO admins(name,email,username,password_hash,is_active) VALUES('AI fixture',:email,:username,:hash,1)",['email'=>$suffix.'@example.test','username'=>$suffix,'hash'=>$hash]);$admin=(int)db()->lastInsertId();
            $_SESSION[ADMIN_SESSION_KEY]=$admin;$_SESSION[ADMIN_CREDENTIAL_STAMP_KEY]=admin_credential_stamp($admin,$hash);
            $mock=static function(array$payload):array{self::assertSame('audit-fixture',$payload['model']);return ['status'=>'completed','output'=>[['content'=>[['type'=>'output_text','text'=>'Review fixture']]]],'usage'=>['input_tokens'=>9,'output_tokens'=>4]];};
            $result=seo_paid_request('generation',bin2hex(random_bytes(16)),static fn($call)=>$call(['input'=>'Synthetic audit']),$mock,'audit');
            $this->assertSame('Review fixture',$result['text']);$this->assertSame('audit',admin_openai_usage($admin)[0]['feature']);
            $this->assertSame(1,(int)fetch_one('SELECT COUNT(*) AS n FROM seo_requests WHERE admin_id=:admin',['admin'=>$admin])['n']);
            $revoked=static function(array$payload)use($mock):array{if(session_status()!==PHP_SESSION_ACTIVE)session_start();unset($_SESSION[ADMIN_SESSION_KEY]);session_write_close();return $mock($payload);};
            try{seo_paid_request('generation',bin2hex(random_bytes(16)),static fn($call)=>$call(['input'=>'Synthetic audit']),$revoked,'audit');$this->fail('Revoked login accepted');}catch(DomainException){$this->addToAssertionCount(1);}
            $this->assertSame(1,(int)fetch_one("SELECT COUNT(*) AS n FROM admin_ai_requests WHERE admin_id=:admin AND status='failed_or_unknown'",['admin'=>$admin])['n']);
        }finally{if(db()->inTransaction())db()->rollBack();if(session_status()!==PHP_SESSION_ACTIVE)session_start();$_SESSION=$before;putenv($key===false?'OPENAI_ADMIN_API_KEY':'OPENAI_ADMIN_API_KEY='.$key);putenv($model===false?'OPENAI_AUDIT_MODEL':'OPENAI_AUDIT_MODEL='.$model);}
    }
}
