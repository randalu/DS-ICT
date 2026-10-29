<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/analytics_audit.php';

final class AnalyticsAuditTest extends TestCase
{
    public function testQueuedEventExtractorFindsLiteralAndConditionalEvents(): void
    {
        $events = release1_analytics_extract_queued_events([
            'contact.php' => "google_tag_event_queue('generate_lead', []); google_tag_event_queue('itinerary_plan_started', []);",
            'customizer.php' => "google_tag_event_queue(\$existing ? 'itinerary_plan_opened' : 'itinerary_plan_started', []);",
        ]);

        $this->assertSame(['contact.php', 'customizer.php'], $events['itinerary_plan_started']);
        $this->assertSame(['customizer.php'], $events['itinerary_plan_opened']);
        $this->assertSame(['contact.php'], $events['generate_lead']);
    }

    public function testClickAttributeRegistryAndStaticTransportCountsAreAudited(): void
    {
        $schemaHelpers = <<<'PHP'
$defaultEvents = [
    ['whatsapp_click', 'WhatsApp Click', 'Description', 1],
    ['sticky_cta_click', 'Sticky CTA Click', 'Description', 2],
];
PHP;
        $sources = [
            'includes/header.php' => '<?php render_google_tag_manager_head(); render_google_tag_manager_body();',
            'includes/footer.php' => <<<'JS'
render_google_tag_queued_events();
document.addEventListener('click', function () {
    window.dataLayer.push({event: 'whatsapp_click'});
    if (navigator.sendBeacon(endpoint, payload)) {
        return;
    }
    fetch(endpoint, {method: 'POST'});
});
JS,
            'includes/schema_helpers.php' => $schemaHelpers . "\nunset(\$_SESSION['google_tag_events']);",
            'includes/sticky_cta.php' => '<a data-track-event="sticky_cta_click"></a><a data-track-event="sticky_cta_click"></a>',
            'includes/cta.php' => '<a data-track-event="whatsapp_click"></a>',
        ];

        $audit = release1_analytics_static_audit($sources);

        $this->assertSame(1, $audit['gtm_head_render_calls']);
        $this->assertSame(1, $audit['gtm_body_render_calls']);
        $this->assertSame(1, $audit['queued_event_flush_calls']);
        $this->assertSame(1, $audit['document_click_listener_count']);
        $this->assertSame(1, $audit['first_party_send_beacon_calls']);
        $this->assertTrue($audit['send_beacon_boolean_result_checked']);
        $this->assertSame(1, $audit['first_party_fetch_fallback_calls']);
        $this->assertSame(['sticky_cta_click' => 2, 'whatsapp_click' => 1], $audit['custom_data_track_event_counts']);
        $this->assertSame([], $audit['unregistered_custom_click_events']);
        $this->assertTrue($audit['queued_event_renderer_unsets_session_queue']);
    }

    public function testGtmResolutionIsClearlyScopedToCliEnvironment(): void
    {
        $databaseOnly = release1_analytics_resolve_gtm_settings([
            'google_tag_manager_enabled' => '1',
            'google_tag_manager_id' => 'GTM-ABC123',
        ], '');
        $environmentOverride = release1_analytics_resolve_gtm_settings([
            'google_tag_manager_enabled' => '0',
            'google_tag_manager_id' => 'GTM-ABC123',
        ], 'GTM-ENV456');
        $invalidOverride = release1_analytics_resolve_gtm_settings([
            'google_tag_manager_enabled' => '1',
            'google_tag_manager_id' => 'GTM-ABC123',
        ], 'invalid-id');

        $this->assertSame('GTM-ABC123', $databaseOnly['cli_resolved_container_id']);
        $this->assertSame('GTM-ENV456', $environmentOverride['cli_resolved_container_id']);
        $this->assertFalse($invalidOverride['cli_resolved_enabled']);
        $this->assertSame('', $invalidOverride['cli_resolved_container_id']);
        $this->assertFalse($databaseOnly['web_server_environment_verified']);
    }
}
