<?php
declare(strict_types=1);

function release1_analytics_extract_queued_events(array $sources): array
{
    $events = [];
    foreach ($sources as $sourceName => $sourceText) {
        if (!is_string($sourceText)) {
            continue;
        }

        if (preg_match_all(
            '/google_tag_event_queue\s*\(\s*([\'\"])([A-Za-z][A-Za-z0-9_]*)\1/s',
            $sourceText,
            $matches
        )) {
            foreach ($matches[2] as $eventName) {
                $events[$eventName][] = (string) $sourceName;
            }
        }

        if (preg_match_all(
            '/google_tag_event_queue\s*\(\s*\$[A-Za-z_][A-Za-z0-9_]*\s*\?\s*[\'\"]([A-Za-z][A-Za-z0-9_]*)[\'\"]\s*:\s*[\'\"]([A-Za-z][A-Za-z0-9_]*)[\'\"]/s',
            $sourceText,
            $conditionalMatches
        )) {
            foreach ([$conditionalMatches[1], $conditionalMatches[2]] as $conditionalEvents) {
                foreach ($conditionalEvents as $eventName) {
                    $events[$eventName][] = (string) $sourceName;
                }
            }
        }
    }

    foreach ($events as $eventName => $sourceNames) {
        $events[$eventName] = array_values(array_unique($sourceNames));
        sort($events[$eventName]);
    }
    ksort($events);

    return $events;
}

function release1_analytics_extract_default_click_events(string $sourceText): array
{
    if (!preg_match('/\$defaultEvents\s*=\s*\[(.*?)^\h*\];/ms', $sourceText, $blockMatch)) {
        return [];
    }

    if (!preg_match_all('/^\s*\[\s*[\'\"]([a-z0-9_]+)[\'\"]\s*,/m', $blockMatch[1], $matches)) {
        return [];
    }

    return array_values(array_unique($matches[1]));
}

function release1_analytics_extract_data_track_events(array $sources): array
{
    $counts = [];
    foreach ($sources as $sourceText) {
        if (!is_string($sourceText)) {
            continue;
        }

        if (preg_match_all('/\bdata-track-event\s*=\s*([\'\"])([a-z0-9_]+)\1/i', $sourceText, $matches)) {
            foreach ($matches[2] as $eventName) {
                $eventName = strtolower($eventName);
                $counts[$eventName] = ($counts[$eventName] ?? 0) + 1;
            }
        }
    }

    ksort($counts);
    return $counts;
}

function release1_analytics_resolve_gtm_settings(array $settings, string $environmentId): array
{
    $environmentId = trim($environmentId);
    $databaseId = trim((string) ($settings['google_tag_manager_id'] ?? 'GTM-56SFZG95'));
    $enabledSetting = (string) ($settings['google_tag_manager_enabled'] ?? '1');
    $containerId = strtoupper($environmentId !== '' ? $environmentId : $databaseId);
    $enabledBySource = $environmentId !== '' || $enabledSetting === '1';
    $validId = preg_match('/^GTM-[A-Z0-9]+$/', $containerId) === 1;
    $resolvedEnabled = $enabledBySource && $validId;

    return [
        'cli_environment_override_present' => $environmentId !== '',
        'database_setting_enabled' => $enabledSetting === '1',
        'database_setting_container_id' => $databaseId,
        'cli_resolved_config_source' => $environmentId !== '' ? 'cli_environment' : 'database_setting',
        'cli_resolved_enabled' => $resolvedEnabled,
        'cli_resolved_container_id' => $resolvedEnabled ? $containerId : '',
        'web_server_environment_verified' => false,
    ];
}

function release1_analytics_static_audit(array $sources): array
{
    $header = (string) ($sources['includes/header.php'] ?? '');
    $footer = (string) ($sources['includes/footer.php'] ?? '');
    $schemaHelpers = (string) ($sources['includes/schema_helpers.php'] ?? '');
    $eventQueueSources = [
        'contact.php' => (string) ($sources['contact.php'] ?? ''),
        'account_customize_itinerary.php' => (string) ($sources['account_customize_itinerary.php'] ?? ''),
        'account_plan_request_save.php' => (string) ($sources['account_plan_request_save.php'] ?? ''),
        'account_inquiry_message_save.php' => (string) ($sources['account_inquiry_message_save.php'] ?? ''),
    ];

    $queuedEvents = release1_analytics_extract_queued_events($eventQueueSources);
    $clickEventKeys = release1_analytics_extract_default_click_events($schemaHelpers);
    $dataTrackEvents = release1_analytics_extract_data_track_events([
        (string) ($sources['includes/sticky_cta.php'] ?? ''),
        (string) ($sources['includes/cta.php'] ?? ''),
        (string) ($sources['contact.php'] ?? ''),
    ]);

    $directGtagCalls = 0;
    $dataLayerPushes = 0;
    foreach ($sources as $sourceText) {
        if (!is_string($sourceText)) {
            continue;
        }
        $directGtagCalls += preg_match_all('/\bgtag\s*\(/', $sourceText);
        $dataLayerPushes += preg_match_all('/(?:window\.)?dataLayer\.push\s*\(/', $sourceText);
    }

    $unregisteredClickEvents = array_values(array_diff(array_keys($dataTrackEvents), $clickEventKeys));
    $clickClassifications = [
        'whatsapp_click',
        'phone_click',
        'email_click',
        'contact_cta_click',
        'sticky_cta_click',
        'external_link_click',
    ];
    $missingClickRegistryEvents = array_values(array_diff($clickClassifications, $clickEventKeys));

    return [
        'gtm_head_render_calls' => preg_match_all('/render_google_tag_manager_head\s*\(\s*\)\s*;/', $header),
        'gtm_body_render_calls' => preg_match_all('/render_google_tag_manager_body\s*\(\s*\)\s*;/', $header),
        'queued_event_flush_calls' => preg_match_all('/render_google_tag_queued_events\s*\(\s*\)\s*;/', $footer),
        'document_click_listener_count' => preg_match_all('/document\.addEventListener\s*\(\s*[\'\"]click[\'\"]/', $footer),
        'click_data_layer_push_calls' => preg_match_all('/window\.dataLayer\.push\s*\(/', $footer),
        'first_party_send_beacon_calls' => preg_match_all('/navigator\.sendBeacon\s*\(/', $footer),
        'send_beacon_boolean_result_checked' => preg_match('/if\s*\(\s*navigator\.sendBeacon\s*\([^;]+\)\s*\)\s*\{\s*return;?\s*\}/s', $footer) === 1,
        'first_party_fetch_fallback_calls' => preg_match_all('/fetch\s*\(\s*endpoint\s*,/', $footer),
        'queued_event_renderer_unsets_session_queue' => str_contains($schemaHelpers, 'unset($_SESSION[\'google_tag_events\'])'),
        'data_layer_push_calls_in_scanned_sources' => $dataLayerPushes,
        'direct_gtag_calls_in_scanned_sources' => $directGtagCalls,
        'default_internal_click_event_keys' => $clickEventKeys,
        'custom_data_track_event_counts' => $dataTrackEvents,
        'unregistered_custom_click_events' => $unregisteredClickEvents,
        'missing_internal_click_event_definitions' => $missingClickRegistryEvents,
        'queued_gtm_events_by_source' => $queuedEvents,
        'queued_business_events_separate_from_internal_click_registry' => array_values(array_diff(array_keys($queuedEvents), $clickEventKeys)),
        'click_dual_transport_is_intentional' => true,
        'external_gtm_tag_duplication_verifiable_locally' => false,
    ];
}
