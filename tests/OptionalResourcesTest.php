<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/review_widget_snippets.php';

final class OptionalResourcesTest extends TestCase
{
    private array $server;
    private array $cookies;
    protected function setUp(): void
    {
        ensure_site_settings_schema();
        $this->server = $_SERVER; $this->cookies = $_COOKIE;
        $_SERVER['REQUEST_URI'] = '/reviews'; $_SERVER['SCRIPT_NAME'] = '/reviews.php'; $_SERVER['HTTP_HOST'] = 'localhost';
        $_COOKIE = [];
        // Warm legacy header/footer guards before creating the rollback-only fixture.
        $this->renderPublicShell();
        db()->beginTransaction();
        execute_query('DELETE FROM site_settings WHERE setting_key=:key', ['key'=>ICT_PRIVACY_SETTINGS_KEY]);
        ict_privacy_save_settings(ict_privacy_defaults());
    }
    protected function tearDown(): void
    {
        $this->assertTrue(db()->inTransaction(), 'No schema DDL may commit this fixture.');
        db()->rollBack(); $_SERVER=$this->server; $_COOKIE=$this->cookies;
    }
    private function grant(array $choice): void { $_COOKIE[ICT_PRIVACY_COOKIE]=ict_privacy_cookie_value($choice,ict_privacy_settings()); }
    private function renderPublicShell(): string
    {
        $recordPageView=false; $seoTitle='Synthetic C2 shell';
        ob_start(); require __DIR__.'/../includes/header.php'; require __DIR__.'/../includes/footer.php'; return (string)ob_get_clean();
    }
    public function testLegacySettingsAndChoicesDoNotGrantNewPurposes(): void
    {
        $legacy=['schema_version'=>1,'purpose_revision'=>7,'preference_days'=>180,'first_party_enabled'=>true,'gtm_allowed'=>false];
        execute_query('UPDATE site_settings SET setting_value=:value WHERE setting_key=:key',['value'=>json_encode($legacy),'key'=>ICT_PRIVACY_SETTINGS_KEY]);
        $settings=ict_privacy_settings();
        $this->assertTrue($settings['configured']); $this->assertSame(7,$settings['purpose_revision']);
        $this->assertFalse($settings['external_reviews_allowed']); $this->assertFalse($settings['translation_allowed']);
        $now=time(); $cookie=json_encode(['v'=>1,'r'=>7,'a'=>true,'t'=>$now,'x'=>$now+86400,'n'=>str_repeat('a',32)]);
        $this->assertFalse(ict_privacy_parse_cookie($cookie,$settings)['chosen']);
        try { ict_privacy_save_settings(array_replace(ict_privacy_defaults(),['purpose_revision'=>6])); $this->fail('Legacy revision decreased'); }
        catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
    }
    public function testCategoriesAreIndependentAndUnavailableServicesCannotBeGranted(): void
    {
        $this->grant(['external_reviews'=>true]);
        $this->assertTrue(ict_optional_allowed('external_reviews')); $this->assertFalse(ict_optional_allowed('translation'));
        $this->assertFalse(ict_optional_allowed('chat')); $this->assertTrue(ict_analytics_allowed());
        $this->assertFalse(ict_analytics_allowed('gtm'));
        $this->grant(['translation'=>true]); $this->assertTrue(ict_optional_allowed('translation')); $this->assertFalse(ict_optional_allowed('external_reviews'));
        $choices=ict_privacy_validate_choices(['purpose_revision'=>'1','choice_action'=>'custom','analytics'=>'0','external_reviews'=>'1','translation'=>'0','chat'=>'1'],ict_privacy_settings());
        $this->assertTrue($choices['external_reviews']); $this->assertFalse($choices['analytics']); $this->assertFalse($choices['chat']);
        $this->assertFalse(ict_privacy_settings()['review_invitations_allowed']);
    }
    public function testUnchosenExpiredRevisionAndOperatorOffExcludeEveryOptionalPath(): void
    {
        foreach (['external_reviews','translation','chat'] as $category) { $this->assertFalse(ict_optional_allowed($category)); }
        $this->grant(['external_reviews'=>true,'translation'=>true,'chat'=>true]);
        ict_privacy_save_settings(array_replace(ict_privacy_defaults(),['external_reviews_allowed'=>false,'translation_allowed'=>false]));
        $this->assertFalse(ict_optional_allowed('external_reviews')); $this->assertFalse(ict_optional_allowed('translation'));
        ict_privacy_save_settings(array_replace(ict_privacy_defaults(),['purpose_revision'=>2]));
        $this->assertFalse(ict_optional_allowed('external_reviews'));
        $cookie=ict_privacy_cookie_value(['external_reviews'=>true],ict_privacy_settings(),time()-181*86400);
        $_COOKIE[ICT_PRIVACY_COOKIE]=$cookie; $this->assertFalse(ict_optional_allowed('external_reviews'));
    }
    public function testPrivateContextsSuppressSharedOptionalResourcesEvenWithGrants(): void
    {
        $this->grant(['analytics'=>true,'external_reviews'=>true,'translation'=>true,'chat'=>true]);
        foreach (['/account','/account.php','/admin/privacy_settings.php','/account_google_callback.php?code=synthetic','/ai-search?q=synthetic','/privacy_choices.php'] as $path) {
            $_SERVER['REQUEST_URI']=$path;
            $html=$this->renderPublicShell();
            $this->assertStringNotContainsString('data-optional-category=',$html);
            foreach (['fonts.googleapis.com','cdn.jsdelivr.net','widget.trustpilot.com','cdn.gtranslate.net','jscache.com','static.tacdn.com','embed.tawk.to'] as $host) { $this->assertStringNotContainsString($host,$html); }
        }
    }
    public function testReviewMarkupIsInertAndPlainLinksWorkWithoutGrant(): void
    {
        $html=$this->renderPublicShell();
        $this->assertStringContainsString('Read reviews on TripAdvisor',$html);
        $this->assertStringContainsString('Read reviews on Trustpilot',$html);
        $this->assertStringNotContainsString('data-optional-category=',$html);
        $this->grant(['external_reviews'=>true]); $html=$this->renderPublicShell();
        $this->assertStringContainsString('data-optional-id="trustpilot-loader"',$html);
        $this->assertStringContainsString('data-optional-id="tripadvisor-footer"',$html);
        $this->assertStringNotContainsString('data-optional-id="gtranslate"',$html);
        $this->assertStringNotContainsString('invitejs.trustpilot.com',$html);
        $this->assertStringNotContainsString('preconnect',$html);
        // PHP 8.3's HTML4 parser does not model template contents; browser tests
        // verify inertness. Inspect the active shell independently here.
        $activeHtml=preg_replace('~<template\b[^>]*>.*?</template>~s','',$html);
        $doc=new DOMDocument(); @$doc->loadHTML($activeHtml); $xpath=new DOMXPath($doc);
        foreach ($xpath->query('//script[starts-with(@src,"http")]') as $script) {
            $this->assertStringStartsWith(rtrim(APP_URL,'/').'/assets/', $script->getAttribute('src'));
        }
        $this->assertSame(0,$xpath->query('//img[starts-with(@src,"https://static.tacdn.com") and not(ancestor::template)]')->length);
    }
    public function testAvatarOnlyPermitsLocalAssetPaths(): void
    {
        foreach (['https://lh3.googleusercontent.com/synthetic','//other.example/avatar.png','javascript:bad','assets/uploads/../private.png','/admin/avatar.php','data:image/png;base64,synthetic'] as $url) { $this->assertSame('',ict_privacy_local_avatar_url($url)); }
        $this->assertStringEndsWith('/assets/images/avatar.png',ict_privacy_local_avatar_url('assets/images/avatar.png?private=omit'));
    }
    public function testPublicContextsPermitCoarseAnalyticsButNeverDraftOrPrivatePages(): void
    {
        $this->grant(['analytics'=>true,'external_reviews'=>true]);
        foreach (['/country.php?slug=synthetic','/from/synthetic','/plan','/compare','/7-day-itinerary','/family-tours','/sri-lanka-in-july','/business-partners/apply'] as $path) {
            $_SERVER['REQUEST_URI']=$path; $this->assertTrue(ict_optional_allowed('external_reviews'),$path);
        }
        $_SERVER['REQUEST_URI']='/plan'; $this->assertTrue(ict_analytics_allowed());
        $slug='c2-static-'.bin2hex(random_bytes(4));
        execute_query('INSERT INTO static_pages (slug,title,content,is_published) VALUES (:slug,:title,:content,0)',['slug'=>$slug,'title'=>'Synthetic C2 static','content'=>'Synthetic fixture only']);
        $_SERVER['REQUEST_URI']='/'.$slug; $this->assertFalse(ict_optional_allowed('external_reviews'));
        execute_query('UPDATE static_pages SET is_published=1 WHERE slug=:slug',['slug'=>$slug]);
        $this->assertTrue(ict_optional_allowed('external_reviews')); $this->assertTrue(ict_analytics_allowed());
        foreach (['/account','/admin','/ai-search','/privacy_choices.php'] as $path) {
            $_SERVER['REQUEST_URI']=$path; $this->assertFalse(ict_analytics_allowed());
        }
    }
    public function testPinnedAssetsHaveHashesLicensesAndLocalFontUrls(): void
    {
        $root=dirname(__DIR__); $manifest=json_decode(file_get_contents($root.'/assets/vendor/manifest.json'),true,512,JSON_THROW_ON_ERROR);
        $this->assertGreaterThan(20,count($manifest['assets']));
        foreach ($manifest['assets'] as $asset) { $this->assertSame($asset['sha256'],hash_file('sha256',$root.'/'.$asset['path']),$asset['path']); }
        foreach (['bootstrap-5.3.3/LICENSE','alpinejs-3.14.8/LICENSE.md','quill-2.0.2/LICENSE','mdi-font-7.4.47/LICENSE'] as $license) { $this->assertFileExists($root.'/assets/vendor/'.$license); }
        $css=file_get_contents($root.'/assets/fonts/brand.css'); $this->assertStringNotContainsString('https://',$css);
        preg_match_all('~url\(([^)]+)\)~',$css,$matches); foreach ($matches[1] as $path) { $this->assertFileExists($root.'/assets/fonts/'.$path); }
    }
}
