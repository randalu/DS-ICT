<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/functions.php';

final class PrivacyControlsTest extends TestCase
{
    private array $server;
    private array $cookies;
    private array $session;

    protected function setUp(): void
    {
        ensure_site_settings_schema();
        ensure_analytics_events_table();
        ensure_analytics_configuration_schema();
        $this->server = $_SERVER; $this->cookies = $_COOKIE; $this->session = $_SESSION;
        $_SERVER['REQUEST_URI'] = '/contact'; $_SERVER['SCRIPT_NAME'] = '/contact.php';
        $_SERVER['HTTP_HOST'] = 'localhost'; unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_COOKIE = []; unset($_SESSION['google_tag_events']);
        db()->beginTransaction();
        execute_query('DELETE FROM site_settings WHERE setting_key = :key', ['key' => ICT_PRIVACY_SETTINGS_KEY]); // Fixture row only; restored by parent rollback.
        ict_privacy_save_settings(ict_privacy_defaults());
    }

    protected function tearDown(): void
    {
        if (db()->inTransaction()) { db()->rollBack(); }
        $_SERVER = $this->server; $_COOKIE = $this->cookies; $_SESSION = $this->session;
    }

    private function grant(): void { $_COOKIE[ICT_PRIVACY_COOKIE] = ict_privacy_cookie_value(true, ict_privacy_settings()); }
    private function countRows(string $table): int { return (int) fetch_one('SELECT COUNT(*) AS total FROM ' . $table)['total']; }

    public function testMissingOptionalSettingsKeepGtmOffAndFirstPartyActive(): void
    {
        $this->assertSame(180, ict_privacy_defaults()['preference_days']);
        $this->assertFalse(ict_privacy_defaults()['gtm_allowed']);
        $this->grant();
        $this->assertTrue(ict_analytics_allowed());
        execute_query('DELETE FROM site_settings WHERE setting_key = :key', ['key' => ICT_PRIVACY_SETTINGS_KEY]);
        $this->assertTrue(ict_analytics_allowed());
        execute_query('INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :json)', ['key' => ICT_PRIVACY_SETTINGS_KEY, 'json' => '{malformed']);
        $this->assertFalse(ict_analytics_allowed('gtm'));
        $this->assertTrue(ict_analytics_allowed());
    }

    public function testStrictCookieExpiryRevisionFieldsAndFreshChoiceBinding(): void
    {
        $settings = ict_privacy_defaults(); $now = 1800000000;
        $encoded = ict_privacy_cookie_value(true, $settings, $now);
        $state = ict_privacy_parse_cookie($encoded, $settings, $now);
        $this->assertTrue($state['analytics']);
        $this->assertSame($now + 180 * 86400, $state['expires_at']);
        $this->assertNotSame($encoded, ict_privacy_cookie_value(true, $settings, $now), 'Same-second choices must not share queue bindings.');
        $original = json_decode($encoded, true);
        foreach ([['r' => 2], ['a' => 'true'], ['t' => $now + 1], ['x' => $now], ['x' => $now + 181 * 86400], ['n' => 'bad'], ['tracking_id' => 'extra']] as $change) {
            $this->assertFalse(ict_privacy_parse_cookie(json_encode(array_replace($original, $change)), $settings, $now)['analytics']);
        }
        $this->assertFalse(ict_privacy_parse_cookie($encoded, array_replace($settings, ['preference_days' => 30]), $now)['analytics']);
        $this->assertFalse(ict_privacy_parse_cookie(str_repeat('x', 513), $settings, $now)['chosen']);
        $this->assertFalse(ict_privacy_parse_cookie(null, $settings, $now)['chosen']);
        $this->assertFalse(ict_privacy_parse_cookie(ict_privacy_cookie_value(false, $settings, $now), $settings, $now)['analytics']);
    }

    public function testChoiceValidationNeverTreatsAnArbitraryPostedFlagAsConsent(): void
    {
        $settings = array_replace(ict_privacy_defaults(), ['gtm_allowed'=>true]);
        $this->assertTrue(ict_privacy_validate_choice(['purpose_revision' => '1', 'choice_action' => 'accept'], $settings));
        $this->assertFalse(ict_privacy_validate_choice(['purpose_revision' => '1', 'choice_action' => 'reject', 'analytics' => '1'], $settings));
        $this->assertTrue(ict_privacy_validate_choice(['purpose_revision' => '1', 'choice_action' => 'custom', 'analytics' => '1'], $settings));
        foreach ([['choice_action' => 'accept'], ['purpose_revision' => '2', 'choice_action' => 'accept'], ['purpose_revision' => '1', 'choice_action' => 'custom', 'analytics' => 'yes'], ['purpose_revision' => '1', 'choice_action' => 'unknown']] as $bad) {
            try { ict_privacy_validate_choice($bad, $settings); $this->fail('Invalid choice accepted.'); } catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
        $this->assertFalse(ict_analytics_allowed('gtm'), 'POST flags do not mint optional-service permission.');
        $this->assertTrue(ict_analytics_allowed());
    }

    public function testSettingsValidatePreserveCallerTransactionAndPreventRevisionRollback(): void
    {
        $settings = ict_privacy_defaults();
        foreach ([['preference_days' => 0], ['preference_days' => 366], ['purpose_revision' => []], ['gtm_allowed' => 'yes'], ['unexpected' => 'x']] as $change) {
            try { ict_privacy_save_settings(array_replace($settings, $change)); $this->fail('Invalid settings saved.'); } catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
        ict_privacy_save_settings(array_replace($settings, ['purpose_revision' => 2]));
        try { ict_privacy_save_settings($settings); $this->fail('Revision decreased.'); } catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        $this->assertSame(2, ict_privacy_settings()['purpose_revision']);
        $this->assertTrue(db()->inTransaction());
    }

    public function testPublicContextsAndSameOriginReferrerExcludePrivateEndpoints(): void
    {
        $this->grant();
        foreach (['/', '/contact.php', '/destinations/sigiriya', '/tours/private-driver', '/reviews', '/itineraries'] as $path) { $this->assertNotSame('', ict_analytics_page_group($path)); }
        foreach (['/admin', '/account', '/account/plans/123', '/account_google_callback.php?code=private', '/ai-search?q=private', '/privacy_choices.php', '/%61ccount', '/contact/../account'] as $path) {
            $_SERVER['REQUEST_URI'] = $path; $this->assertFalse(ict_analytics_allowed());
        }
        $_SERVER['SCRIPT_NAME'] = '/track-event.php'; $_SERVER['REQUEST_URI'] = '/track-event.php';
        foreach (['https://evil.example/contact', 'http://localhost:9000/contact', 'http://localhost/account/plans/123', ''] as $ref) { $_SERVER['HTTP_REFERER'] = $ref; $this->assertFalse(ict_analytics_allowed()); }
        $_SERVER['HTTP_REFERER'] = 'http://localhost/contact?email=private@example.invalid';
        $this->assertTrue(ict_analytics_allowed()); $this->assertSame('contact', ict_analytics_request_group());
    }

    public function testOptionalRefusalAndDisabledOptionalSwitchDoNotSuppressFirstParty(): void
    {
        $events = $this->countRows('analytics_events'); $pages = $this->countRows('page_views');
        $_SERVER['REQUEST_METHOD']='GET';
        track_analytics_event('phone_click'); record_page_view(); run_analytics_retention_cleanup();
        $this->assertSame($events+1, $this->countRows('analytics_events')); $this->assertSame($pages+1, $this->countRows('page_views'));
        $this->assertFalse(ict_analytics_allowed('gtm'));
        ict_privacy_save_settings(array_replace(ict_privacy_defaults(), ['first_party_enabled' => false]));
        track_analytics_event('phone_click'); record_page_view();
        $this->assertSame($events+2, $this->countRows('analytics_events')); $this->assertSame($pages+2, $this->countRows('page_views'));
        $this->assertTrue(ict_analytics_disposal_paused());
    }

    public function testGrantedEventsAndPageViewsDropUnnecessaryIdentifiers(): void
    {
        $this->grant();
        $_SERVER['HTTP_USER_AGENT'] = 'Synthetic private agent'; $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
        $_SERVER['HTTP_REFERER'] = 'http://localhost/account/plans/123?private=1';
        track_analytics_event('phone_click', ['target_url' => 'mailto:private@example.invalid?body=private', 'link_text' => 'Private name', 'plan_id' => 123]);
        $id = (int) last_insert_id();
        $row = fetch_one('SELECT * FROM analytics_events WHERE id = :id', ['id' => $id]);
        $this->assertSame('/contact', $row['path']); $this->assertSame('phone', $row['target_group']);
        foreach (['target_url', 'ip_address', 'user_agent', 'referrer'] as $field) { $this->assertSame('', $row[$field]); }
        $this->assertNull($row['meta_json']);
        record_page_view(); $pageId = (int) last_insert_id();
        $page = fetch_one('SELECT * FROM page_views WHERE id = :id', ['id' => $pageId]);
        foreach (['ip_address', 'user_agent'] as $field) { $this->assertSame('', $page[$field]); }
        $this->assertSame('/contact', $page['path']); $this->assertTrue(db()->inTransaction());
    }

    public function testGtmOffOverridesEnvironmentAndPrivateContext(): void
    {
        $old = getenv('GTM_CONTAINER_ID'); putenv('GTM_CONTAINER_ID=GTM-SYN123');
        try {
            $this->grant(); ob_start(); render_google_tag_manager_head(); render_google_tag_manager_body(); $off = ob_get_clean();
            $this->assertSame('', $off);
            ict_privacy_save_settings(array_replace(ict_privacy_defaults(), ['gtm_allowed' => true]));
            ob_start(); render_google_tag_manager_head(); render_google_tag_manager_body(); $on = ob_get_clean();
            $this->assertStringContainsString('gtm.js', $on); $this->assertStringContainsString('ad_storage:', $on); $this->assertStringContainsString("'denied'", $on); $this->assertStringNotContainsString('ns.html', $on);
            $_SERVER['REQUEST_URI'] = '/account/plans/123';
            ob_start(); render_google_tag_manager_head(); render_google_tag_manager_body(); $private = ob_get_clean();
            $this->assertSame('', $private);
        } finally { $old === false ? putenv('GTM_CONTAINER_ID') : putenv('GTM_CONTAINER_ID=' . $old); }
    }

    public function testQueueBindsToCurrentChoiceAndDropsLegacyAndPreConsentEntries(): void
    {
        ict_privacy_save_settings(array_replace(ict_privacy_defaults(), ['gtm_allowed' => true]));
        google_tag_event_queue('generate_lead', ['plan_id' => 123]); $this->assertArrayNotHasKey('google_tag_events', $_SESSION);
        $this->grant(); google_tag_event_queue('generate_lead', ['lead_type' => 'tour_inquiry', 'plan_id' => 123, 'email' => 'private@example.invalid']);
        $this->assertCount(1, $_SESSION['google_tag_events']);
        $this->assertArrayNotHasKey('plan_id', $_SESSION['google_tag_events'][0]['payload']);
        $this->grant(); // A new same-second choice must invalidate old queued measurement.
        ob_start(); render_google_tag_queued_events(); $stale = ob_get_clean(); $this->assertSame('', $stale);
        $_SESSION['google_tag_events'] = [['event' => 'generate_lead', 'email' => 'private@example.invalid']];
        ob_start(); render_google_tag_queued_events(); $legacy = ob_get_clean(); $this->assertSame('', $legacy);
        google_tag_event_queue('generate_lead', ['lead_type' => 'tour_inquiry']);
        ob_start(); render_google_tag_queued_events(); $valid = ob_get_clean();
        $this->assertStringContainsString('generate_lead', $valid); $this->assertStringNotContainsString('choice_token', $valid);
        $this->assertArrayNotHasKey('google_tag_events', $_SESSION);
    }

    public function testReturnPathsNeverRedirectToAnotherOriginOrCarryQuerySecrets(): void
    {
        foreach (['https://evil.example/contact', '//evil.example/contact', '/contact/../account', '/contact\\evil', "contact\nlocation"] as $path) { $this->assertSame('', ict_privacy_local_return($path)); }
        $this->assertSame('contact', ict_privacy_local_return('/contact?email=private@example.invalid'));
        $this->assertSame('privacy_choices.php', ict_privacy_local_return('privacy_choices.php'));
    }
}
