<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../includes/admin_ai_limits.php';
final class AdminAiLimitsTest extends TestCase {
    private array $saved=[];
    protected function setUp(): void {foreach(['SEO_GENERATION_DAILY_LIMIT','SEO_RESEARCH_DAILY_LIMIT','OPENAI_ALT_HOURLY_LIMIT','OPENAI_ALT_DAILY_LIMIT'] as $key){$this->saved[$key]=getenv($key);putenv($key);}}
    protected function tearDown(): void {foreach($this->saved as $key=>$value)putenv($value===false?$key:$key.'='.$value);}
    public function testDefaultQuotasAreTenfold():void {$this->assertSame(500,admin_ai_request_limit('generation'));$this->assertSame(100,admin_ai_request_limit('research'));$this->assertSame(200,admin_ai_request_limit('alt','hour'));$this->assertSame(1000,admin_ai_request_limit('alt','day'));}
    public function testOverridesAreScaledAndZeroStillDisables():void {foreach(['SEO_GENERATION_DAILY_LIMIT'=>['generation','day'],'SEO_RESEARCH_DAILY_LIMIT'=>['research','day'],'OPENAI_ALT_HOURLY_LIMIT'=>['alt','hour'],'OPENAI_ALT_DAILY_LIMIT'=>['alt','day']] as $key=>[$feature,$period]){putenv($key.'=37');$this->assertSame(370,admin_ai_request_limit($feature,$period));putenv($key.'=0');$this->assertSame(0,admin_ai_request_limit($feature,$period));}}
    public function testMalformedValuesUseDefaultsAndCapsScaleSafely():void {putenv('SEO_GENERATION_DAILY_LIMIT=invalid');$this->assertSame(500,admin_ai_request_limit('generation'));putenv('SEO_GENERATION_DAILY_LIMIT=-1');$this->assertSame(500,admin_ai_request_limit('generation'));putenv('SEO_GENERATION_DAILY_LIMIT=999999999999999999999999999999999');$this->assertSame(100000,admin_ai_request_limit('generation'));}
    public function testUnknownQuotaIsRejected():void {$this->expectException(InvalidArgumentException::class);admin_ai_request_limit('unknown');}
}
