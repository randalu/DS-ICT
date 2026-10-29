<?php
declare(strict_types=1);

/**
 * Local smoke checks for POST gates and route renders.
 *
 * Usage:
 *   php cli/smoke_post_gates.php
 *   php cli/smoke_post_gates.php --only=static
 *   php cli/smoke_post_gates.php --only=dynamic
 *   php cli/smoke_post_gates.php --list
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from CLI.\n");
    exit(1);
}

const SMOKE_ROOT = __DIR__ . '/..';
const SMOKE_MARKER = 'SMOKE_RESULT:';

$args = array_slice($argv, 1);
$childCase = '';
$only = 'all';
$list = false;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--case=')) {
        $childCase = substr($arg, 7);
        continue;
    }
    if (str_starts_with($arg, '--only=')) {
        $only = strtolower(trim(substr($arg, 7)));
        continue;
    }
    if ($arg === '--list') {
        $list = true;
    }
}

$cases = smoke_cases();

if ($childCase !== '') {
    smoke_run_child_case($childCase, $cases);
    exit(0);
}

if ($list) {
    foreach ($cases as $id => $case) {
        echo $id . ' | ' . $case['label'] . PHP_EOL;
    }
    exit(0);
}

if (!in_array($only, ['all', 'static', 'dynamic'], true)) {
    fwrite(STDERR, "Unknown --only value. Use all, static, or dynamic.\n");
    exit(1);
}

$results = [];
if ($only !== 'dynamic') {
    foreach (smoke_static_audits() as $result) {
        $results[] = $result;
        smoke_print_result($result);
    }
}

if ($only !== 'static') {
    foreach (array_keys($cases) as $caseId) {
        $result = smoke_run_case_process($caseId);
        $results[] = $result;
        smoke_print_result($result);
    }
}

$failures = array_values(array_filter($results, static fn(array $result): bool => $result['status'] === 'fail'));
$skips = array_values(array_filter($results, static fn(array $result): bool => $result['status'] === 'skip'));
$passes = array_values(array_filter($results, static fn(array $result): bool => $result['status'] === 'pass'));

echo PHP_EOL;
echo 'summary: pass=' . count($passes) . ' fail=' . count($failures) . ' skip=' . count($skips) . PHP_EOL;

exit($failures ? 1 : 0);

function smoke_cases(): array
{
    return [
        'map_manager_guest' => ['label'=>'Map Manager requires admin authentication','target'=>'admin/map_manager.php','method'=>'GET','setup'=>'render','expect_code'=>302,'expect_guest'=>true],
        'map_save_guest' => ['label'=>'Map saves require admin authentication','target'=>'admin/map_manager_save.php','method'=>'POST','setup'=>'render','expect_code'=>302,'expect_guest'=>true],
        'map_save_get' => ['label'=>'Map saves reject GET','target'=>'admin/map_manager_save.php','method'=>'GET','setup'=>'admin_render','expect_code'=>302],
        'map_save_csrf' => ['label'=>'Map saves require CSRF','target'=>'admin/map_manager_save.php','method'=>'POST','setup'=>'admin_invalid_csrf','expect_code'=>419],
        'map_preview_guest' => ['label'=>'Map previews require admin authentication','target'=>'admin/map_manager_api.php','method'=>'POST','setup'=>'render','expect_code'=>302,'expect_guest'=>true],
        'map_preview_get' => ['label'=>'Map previews reject GET','target'=>'admin/map_manager_api.php','method'=>'GET','setup'=>'admin_render','expect_code'=>405],
        'map_preview_csrf' => ['label'=>'Map previews require CSRF','target'=>'admin/map_manager_api.php','method'=>'POST','setup'=>'admin_invalid_csrf','expect_code'=>419],
        'tour_program_guest' => ['label'=>'Tour programme editor requires admin authentication','target'=>'admin/tour_program.php','method'=>'GET','setup'=>'render','expect_code'=>302,'expect_guest'=>true],
        'tour_program_save_get' => ['label'=>'Tour programme save rejects GET','target'=>'admin/tour_program_save.php','method'=>'GET','setup'=>'admin_render','expect_code'=>302],
        'tour_program_csrf' => ['label'=>'Tour programme rejects invalid CSRF','target'=>'admin/tour_program_save.php','method'=>'POST','setup'=>'admin_invalid_csrf','expect_code'=>419],
        'tour_reference_gone' => ['label'=>'Removed Tour reference page returns Gone','target'=>'n_day_itinerary.php','method'=>'GET','setup'=>'render','expect_code'=>410],
        'tour_guide_editor_gone' => ['label'=>'Removed Tour guide editor returns Gone','target'=>'admin/tour_guides.php','method'=>'GET','setup'=>'admin_render','expect_code'=>410],
        'privacy_save_get' => ['label' => 'visitor choice save rejects GET', 'target' => 'privacy_choices_save.php', 'method' => 'GET', 'setup' => 'render', 'expect_code' => 405],
        'privacy_save_csrf' => ['label' => 'visitor choice rejects invalid CSRF', 'target' => 'privacy_choices_save.php', 'method' => 'POST', 'setup' => 'track_invalid_csrf', 'expect_code' => 419],
        'privacy_save_accept' => ['label' => 'visitor acceptance respects available analytics categories', 'target' => 'privacy_choices_save.php', 'method' => 'POST', 'setup' => 'privacy_choice', 'choice_action' => 'accept', 'expect_code' => 200, 'expect_choice' => true, 'expect_available_analytics' => true],
        'privacy_save_reject' => ['label' => 'visitor rejection overrides posted grant', 'target' => 'privacy_choices_save.php', 'method' => 'POST', 'setup' => 'privacy_choice', 'choice_action' => 'reject', 'expect_code' => 200, 'expect_choice' => false],
        'privacy_save_reviews_only' => ['label' => 'visitor category choices save independently', 'target' => 'privacy_choices_save.php', 'method' => 'POST', 'setup' => 'privacy_choice', 'choice_action' => 'custom', 'choices' => ['analytics'=>'0','external_reviews'=>'1','translation'=>'0','chat'=>'0'], 'expect_code' => 200, 'expect_choice' => false, 'expect_categories' => ['external_reviews'=>true,'translation'=>false,'chat'=>false]],
        'privacy_save_bad_category' => ['label' => 'visitor category rejects non-boolean form values', 'target' => 'privacy_choices_save.php', 'method' => 'POST', 'setup' => 'privacy_choice', 'choice_action' => 'custom', 'choices' => ['analytics'=>'0','translation'=>'yes'], 'expect_code' => 422],
        'privacy_save_stale' => ['label' => 'visitor choice rejects stale notice', 'target' => 'privacy_choices_save.php', 'method' => 'POST', 'setup' => 'privacy_choice', 'choice_action' => 'stale', 'expect_code' => 422],
        'privacy_save_large' => ['label' => 'visitor choice rejects oversized body', 'target' => 'privacy_choices_save.php', 'method' => 'POST', 'setup' => 'privacy_choice', 'choice_action' => 'large', 'expect_code' => 413],
        'privacy_choices_render' => ['label' => 'visitor preference page renders', 'target' => 'privacy_choices.php', 'method' => 'GET', 'setup' => 'render', 'expect_render_min' => 1000, 'expect_contains' => 'Privacy choices'],
        'admin_privacy_csrf' => ['label' => 'admin analytics controls reject invalid CSRF', 'target' => 'admin/privacy_settings_save.php', 'method' => 'POST', 'setup' => 'admin_invalid_csrf', 'expect_code' => 419],
        'admin_privacy_guest' => ['label' => 'analytics settings require admin authentication', 'target' => 'admin/privacy_settings.php', 'method' => 'GET', 'setup' => 'render', 'expect_code' => 302, 'expect_guest' => true],
        'admin_seo_guest' => ['label' => 'SEO portal requires admin authentication', 'target' => 'admin/seo_manager.php', 'method' => 'GET', 'setup' => 'render', 'expect_code' => 302, 'expect_guest' => true],
        'admin_seo_api_guest' => ['label' => 'SEO API requires admin authentication', 'target' => 'admin/seo_manager_api.php', 'method' => 'GET', 'setup' => 'render', 'expect_code' => 401],
        'admin_seo_csrf' => ['label' => 'SEO writes reject invalid CSRF', 'target' => 'admin/seo_manager_api.php', 'method' => 'POST', 'setup' => 'admin_invalid_csrf', 'expect_code' => 419],
        'admin_privacy_save_guest' => ['label' => 'analytics settings writes require admin authentication', 'target' => 'admin/privacy_settings_save.php', 'method' => 'POST', 'setup' => 'render', 'expect_code' => 302, 'expect_guest' => true],
        'admin_privacy_render' => ['label' => 'authenticated privacy settings render', 'target' => 'admin/privacy_settings.php', 'method' => 'GET', 'setup' => 'admin_render', 'expect_render_min' => 1000, 'expect_contains' => 'Save Privacy Controls'],
        'admin_privacy_save' => ['label' => 'admin analytics settings save with rollback', 'target' => 'admin/privacy_settings_save.php', 'method' => 'POST', 'setup' => 'admin_privacy', 'privacy_mode' => 'valid', 'expect_code' => 302, 'expect_privacy_days' => 30],
        'admin_privacy_gtm_review' => ['label' => 'GTM switch requires operator review', 'target' => 'admin/privacy_settings_save.php', 'method' => 'POST', 'setup' => 'admin_privacy', 'privacy_mode' => 'gtm', 'expect_code' => 302, 'expect_privacy_days' => 180],
        'admin_privacy_invitation_review' => ['label' => 'invitation switch requires a separate operator review', 'target' => 'admin/privacy_settings_save.php', 'method' => 'POST', 'setup' => 'admin_privacy', 'privacy_mode' => 'invitations', 'expect_code' => 302, 'expect_privacy_days' => 180],
        'admin_widget_source' => ['label' => 'private widget preview renders source only', 'target' => 'admin/tripadvisor_widgets_preview.php', 'method' => 'GET', 'setup' => 'admin_render', 'expect_render_min' => 1000, 'expect_contains' => 'Live external previews are disabled'],
        'public_ai_off_get' => ['label' => 'public AI GET stays local and offers alternatives', 'target' => 'ai_search.php', 'method' => 'GET', 'setup' => 'public_ai_off', 'expect_render_min' => 1000, 'expect_contains' => 'AI search is currently unavailable'],
        'public_ai_csrf' => ['label' => 'public AI POST rejects invalid CSRF', 'target' => 'ai_search.php', 'method' => 'POST', 'setup' => 'public_ai_off', 'ai_mode' => 'csrf', 'expect_code' => 419],
        'public_ai_body' => ['label' => 'public AI body bound precedes CSRF', 'target' => 'ai_search.php', 'method' => 'POST', 'setup' => 'public_ai_off', 'ai_mode' => 'large', 'expect_code' => 413],
        'public_ai_method' => ['label' => 'public AI rejects unsafe methods', 'target' => 'ai_search.php', 'method' => 'PUT', 'setup' => 'public_ai_off', 'expect_code' => 405],
        'admin_privacy_ai_review' => ['label' => 'public AI requires separate operator acknowledgement', 'target' => 'admin/privacy_settings_save.php', 'method' => 'POST', 'setup' => 'admin_privacy', 'privacy_mode' => 'ai', 'expect_code' => 302, 'expect_privacy_days' => 180, 'expect_public_ai' => false],
        'admin_privacy_ai_ack' => ['label' => 'acknowledged public AI switch saves in rollback fixture', 'target' => 'admin/privacy_settings_save.php', 'method' => 'POST', 'setup' => 'admin_privacy', 'privacy_mode' => 'ai_ack', 'expect_code' => 302, 'expect_privacy_days' => 180, 'expect_public_ai' => true],
        'admin_blog_delete_csrf' => [
            'label' => 'admin blog delete rejects invalid CSRF',
            'target' => 'admin/blog_post_delete.php',
            'method' => 'POST',
            'setup' => 'admin_invalid_csrf',
            'expect_code' => 419,
        ],
        'admin_destination_delete_csrf' => [
            'label' => 'admin destination delete rejects invalid CSRF',
            'target' => 'admin/destination_delete.php',
            'method' => 'POST',
            'setup' => 'admin_invalid_csrf',
            'expect_code' => 419,
        ],
        'admin_site_settings_csrf' => [
            'label' => 'admin site settings rejects invalid CSRF',
            'target' => 'admin/site_settings_save.php',
            'method' => 'POST',
            'setup' => 'admin_invalid_csrf',
            'expect_code' => 419,
        ],
        'admin_booking_confirmation_csrf' => [
            'label' => 'booking confirmation rejects invalid CSRF', 'target' => 'admin/customer_plan_booking_status_save.php', 'method' => 'POST', 'setup' => 'admin_invalid_csrf', 'expect_code' => 419,
        ],
        'admin_booking_confirmation_funds' => [
            'label' => 'confirmation saves received-funds evidence and message atomically', 'target' => 'admin/customer_plan_booking_status_save.php', 'method' => 'POST', 'setup' => 'admin_confirmation_fixture', 'confirmation_mode' => 'funds_received', 'expect_code' => 302, 'expect_confirmation_status' => 'confirmed',
        ],
        'admin_booking_confirmation_no_due' => [
            'label' => 'confirmation records explicit no-payment-due quote term', 'target' => 'admin/customer_plan_booking_status_save.php', 'method' => 'POST', 'setup' => 'admin_confirmation_fixture', 'confirmation_mode' => 'no_payment_due', 'expect_code' => 302, 'expect_confirmation_status' => 'confirmed',
        ],
        'admin_booking_confirmation_missing' => [
            'label' => 'confirmation rejects missing payment reference with no writes', 'target' => 'admin/customer_plan_booking_status_save.php', 'method' => 'POST', 'setup' => 'admin_confirmation_fixture', 'confirmation_mode' => 'missing', 'expect_code' => 302, 'expect_confirmation_status' => 'accepted',
        ],
        'admin_booking_confirmation_stale' => [
            'label' => 'confirmation rejects stale acceptance record with no writes', 'target' => 'admin/customer_plan_booking_status_save.php', 'method' => 'POST', 'setup' => 'admin_confirmation_fixture', 'confirmation_mode' => 'stale', 'expect_code' => 302, 'expect_confirmation_status' => 'accepted',
        ],
        'admin_booking_request_acceptance' => [
            'label' => 'reviewed planning handler requests acceptance atomically', 'target' => 'admin/customer_plan_booking_status_save.php', 'method' => 'POST', 'setup' => 'booking_workflow_fixture', 'workflow_mode' => 'request', 'expect_code' => 302, 'expect_workflow_status' => 'awaiting_acceptance', 'expect_workflow_writes' => 1,
        ],
        'admin_booking_request_unreviewed' => [
            'label' => 'request handler rejects unreviewed plan', 'target' => 'admin/customer_plan_booking_status_save.php', 'method' => 'POST', 'setup' => 'booking_workflow_fixture', 'workflow_mode' => 'unreviewed', 'expect_code' => 302, 'expect_workflow_status' => 'planning', 'expect_workflow_writes' => 0,
        ],
        'customer_booking_acceptance' => [
            'label' => 'customer handler archives policies and accepts without confirming', 'target' => 'account_plan_policy_accept.php', 'method' => 'POST', 'setup' => 'booking_workflow_fixture', 'workflow_mode' => 'accept', 'expect_code' => 302, 'expect_workflow_status' => 'accepted', 'expect_workflow_writes' => 1,
        ],
        'customer_booking_acceptance_stale' => [
            'label' => 'customer handler rejects changed policy hash', 'target' => 'account_plan_policy_accept.php', 'method' => 'POST', 'setup' => 'booking_workflow_fixture', 'workflow_mode' => 'stale_policy', 'expect_code' => 302, 'expect_workflow_status' => 'awaiting_acceptance', 'expect_workflow_writes' => 0,
        ],
        'customer_booking_acceptance_other_owner' => [
            'label' => 'customer handler rejects another customer plan', 'target' => 'account_plan_policy_accept.php', 'method' => 'POST', 'setup' => 'booking_workflow_fixture', 'workflow_mode' => 'other_owner', 'expect_code' => 302, 'expect_workflow_status' => 'awaiting_acceptance', 'expect_workflow_writes' => 0,
        ],
        'customer_booking_acceptance_csrf' => [
            'label' => 'customer acceptance handler rejects invalid CSRF before writes', 'target' => 'account_plan_policy_accept.php', 'method' => 'POST', 'setup' => 'booking_workflow_fixture', 'workflow_mode' => 'invalid_csrf', 'expect_code' => 419, 'expect_workflow_status' => 'awaiting_acceptance', 'expect_workflow_writes' => 0,
        ],
        'admin_policy_settings_csrf' => [
            'label' => 'admin cancellation policy settings rejects invalid CSRF',
            'target' => 'admin/policy_settings_save.php',
            'method' => 'POST',
            'setup' => 'admin_invalid_csrf',
            'expect_code' => 419,
        ],
        'admin_policy_settings_render' => [
            'label' => 'authenticated cancellation settings page renders',
            'target' => 'admin/policy_settings.php', 'method' => 'GET', 'setup' => 'admin_render',
            'expect_render_min' => 1000, 'expect_contains' => 'Save Policy Settings',
        ],
        'admin_policy_settings_save' => [
            'label' => 'authenticated cancellation settings POST saves draft with rollback',
            'target' => 'admin/policy_settings_save.php', 'method' => 'POST', 'setup' => 'admin_policy_valid',
            'expect_code' => 302, 'expect_policy_charge' => 30,
        ],
        'admin_retention_settings_csrf' => [
            'label' => 'admin retention settings rejects invalid CSRF',
            'target' => 'admin/retention_settings_save.php', 'method' => 'POST', 'setup' => 'admin_invalid_csrf', 'expect_code' => 419,
        ],
        'admin_retention_settings_render' => [
            'label' => 'authenticated retention settings page renders',
            'target' => 'admin/retention_settings.php', 'method' => 'GET', 'setup' => 'admin_render',
            'expect_render_min' => 1000, 'expect_contains' => 'Save Retention Settings',
        ],
        'admin_retention_settings_save' => [
            'label' => 'authenticated retention settings POST saves draft with rollback',
            'target' => 'admin/retention_settings_save.php', 'method' => 'POST', 'setup' => 'admin_retention_valid',
            'expect_code' => 302, 'expect_retention_period' => 9,
        ],
        'admin_records_template_download' => [
            'label' => 'authenticated records template downloads a workbook',
            'target' => 'admin/records_template_download.php', 'method' => 'GET', 'setup' => 'admin_retention_workbook',
            'expect_workbook' => true,
        ],
        'admin_media_delete_csrf' => [
            'label' => 'admin media delete rejects invalid CSRF',
            'target' => 'admin/media_asset_delete.php',
            'method' => 'POST',
            'setup' => 'admin_invalid_csrf',
            'expect_code' => 419,
        ],
        'admin_social_publish_csrf' => [
            'label' => 'admin social publish rejects invalid CSRF',
            'target' => 'admin/social_post_publish.php',
            'method' => 'POST',
            'setup' => 'admin_invalid_csrf',
            'expect_code' => 419,
        ],
        'subscribe_csrf' => [
            'label' => 'newsletter subscribe rejects invalid CSRF',
            'target' => 'subscribe.php',
            'method' => 'POST',
            'setup' => 'subscribe_invalid_csrf',
            'expect_code' => 419,
        ],
        'track_event_invalid_csrf' => [
            'label' => 'analytics tracking rejects invalid CSRF',
            'target' => 'track-event.php',
            'method' => 'POST',
            'setup' => 'track_invalid_csrf',
            'expect_code' => 419,
        ],
        'track_event_valid_invalid_event' => [
            'label' => 'analytics tracking accepts valid CSRF and ignores invalid event key',
            'target' => 'track-event.php',
            'method' => 'POST',
            'setup' => 'track_valid_invalid_event',
            'expect_code' => 204,
        ],
        'destinations_save_route' => [
            'label' => 'destinations route session save returns 204 with rollback',
            'target' => 'destinations.php',
            'method' => 'POST',
            'setup' => 'route_save',
            'slug_table' => 'destinations',
            'slug_field' => 'dest_slugs',
            'action' => 'save_route',
            'expect_code' => 204,
            'rollback' => true,
        ],
        'experiences_save_wishlist' => [
            'label' => 'experiences wishlist session save returns 204 with rollback',
            'target' => 'experiences.php',
            'method' => 'POST',
            'setup' => 'route_save',
            'slug_table' => 'experiences',
            'slug_field' => 'exp_slugs',
            'action' => 'save_wishlist',
            'expect_code' => 204,
            'rollback' => true,
        ],
        'destinations_render' => [
            'label' => 'destinations page renders through bootstrap',
            'target' => 'destinations.php',
            'method' => 'GET',
            'setup' => 'render',
            'uri' => '/destinations',
            'expect_render_min' => 1000,
        ],
        'contact_render' => [
            'label' => 'contact page renders first-reply commitment',
            'target' => 'contact.php', 'method' => 'GET', 'setup' => 'render', 'uri' => '/contact',
            'expect_render_min' => 1000, 'expect_contains' => 'First reply within 24 hours; plan timing agreed separately',
        ],
        'experiences_render' => [
            'label' => 'experiences page renders through bootstrap',
            'target' => 'experiences.php',
            'method' => 'GET',
            'setup' => 'render',
            'uri' => '/experiences',
            'expect_render_min' => 1000,
        ],
        'itinerary_detail_render' => [
            'label' => 'itinerary detail renders with a published itinerary slug',
            'target' => 'itinerary.php',
            'method' => 'GET',
            'setup' => 'detail_slug',
            'slug_table' => 'itineraries',
            'uri_prefix' => '/itineraries/',
            'expect_render_min' => 1000,
        ],
        'experience_detail_render' => [
            'label' => 'experience detail renders with a published experience slug',
            'target' => 'experience.php',
            'method' => 'GET',
            'setup' => 'detail_slug',
            'slug_table' => 'experiences',
            'uri_prefix' => '/experiences/',
            'expect_render_min' => 1000,
        ],
        'tour_detail_render' => [
            'label' => 'tour detail renders with a published tour slug',
            'target' => 'tour.php',
            'method' => 'GET',
            'setup' => 'detail_slug',
            'slug_table' => 'tours',
            'uri_prefix' => '/tours/',
            'expect_render_min' => 1000,
        ],
        'theme_detail_render' => [
            'label' => 'theme page renders with default theme slug fallback',
            'target' => 'theme.php',
            'method' => 'GET',
            'setup' => 'fixed_get',
            'uri' => '/honeymoon-tours',
            'get' => ['slug' => 'honeymoon-tours'],
            'expect_render_min' => 1000,
        ],
        'sitemap_core' => [
            'label' => 'sitemap partial core renders XML',
            'target' => 'sitemap_partial.php',
            'method' => 'GET',
            'setup' => 'sitemap_type',
            'sitemap_type' => 'core',
            'expect_render_min' => 80,
            'expect_contains' => '<urlset',
        ],
        'sitemap_destinations' => [
            'label' => 'sitemap partial destinations renders XML',
            'target' => 'sitemap_partial.php',
            'method' => 'GET',
            'setup' => 'sitemap_type',
            'sitemap_type' => 'destinations',
            'expect_render_min' => 80,
            'expect_contains' => '<urlset',
        ],
        'sitemap_itineraries' => [
            'label' => 'sitemap partial itineraries renders XML',
            'target' => 'sitemap_partial.php',
            'method' => 'GET',
            'setup' => 'sitemap_type',
            'sitemap_type' => 'itineraries',
            'expect_render_min' => 80,
            'expect_contains' => '<urlset',
        ],
        'sitemap_tours' => [
            'label' => 'sitemap partial tours renders XML',
            'target' => 'sitemap_partial.php',
            'method' => 'GET',
            'setup' => 'sitemap_type',
            'sitemap_type' => 'tours',
            'expect_render_min' => 80,
            'expect_contains' => '<urlset',
        ],
        'sitemap_experiences' => [
            'label' => 'sitemap partial experiences renders XML',
            'target' => 'sitemap_partial.php',
            'method' => 'GET',
            'setup' => 'sitemap_type',
            'sitemap_type' => 'experiences',
            'expect_render_min' => 80,
            'expect_contains' => '<urlset',
        ],
        'sitemap_partners' => [
            'label' => 'sitemap partial partners renders XML',
            'target' => 'sitemap_partial.php',
            'method' => 'GET',
            'setup' => 'sitemap_type',
            'sitemap_type' => 'partners',
            'expect_render_min' => 80,
            'expect_contains' => '<urlset',
        ],
        'sitemap_countries' => [
            'label' => 'sitemap partial countries renders XML',
            'target' => 'sitemap_partial.php',
            'method' => 'GET',
            'setup' => 'sitemap_type',
            'sitemap_type' => 'countries',
            'expect_render_min' => 80,
            'expect_contains' => '<urlset',
        ],
        'sitemap_blog' => [
            'label' => 'sitemap partial blog renders XML',
            'target' => 'sitemap_partial.php',
            'method' => 'GET',
            'setup' => 'sitemap_type',
            'sitemap_type' => 'blog',
            'expect_render_min' => 80,
            'expect_contains' => '<urlset',
        ],
        'sitemap_themes' => [
            'label' => 'sitemap partial themes renders XML',
            'target' => 'sitemap_partial.php',
            'method' => 'GET',
            'setup' => 'sitemap_type',
            'sitemap_type' => 'themes',
            'expect_render_min' => 80,
            'expect_contains' => '<urlset',
        ],
        'sitemap_seasonal' => [
            'label' => 'sitemap partial seasonal renders XML',
            'target' => 'sitemap_partial.php',
            'method' => 'GET',
            'setup' => 'sitemap_type',
            'sitemap_type' => 'seasonal',
            'expect_render_min' => 80,
            'expect_contains' => '<urlset',
        ],
        'sitemap_n_day' => [
            'label' => 'retired duration sitemap returns Not Found',
            'target' => 'sitemap_partial.php',
            'method' => 'GET',
            'setup' => 'sitemap_type',
            'sitemap_type' => 'n-day',
            'expect_code' => 404,
        ],
        'sitemap_images' => [
            'label' => 'sitemap partial images renders XML',
            'target' => 'sitemap_partial.php',
            'method' => 'GET',
            'setup' => 'sitemap_type',
            'sitemap_type' => 'images',
            'expect_render_min' => 80,
            'expect_contains' => '<urlset',
        ],
    ];
}

function smoke_static_audits(): array
{
    return [
        smoke_audit_admin_write_handlers(),
        smoke_audit_post_forms_have_csrf(),
        smoke_audit_installer_csrf(),
        smoke_audit_subscribe_csrf(),
    ];
}

function smoke_audit_admin_write_handlers(): array
{
    $issues = [];
    foreach (glob(SMOKE_ROOT . '/admin/*.php') ?: [] as $file) {
        $relative = smoke_relative_path($file);
        if (str_starts_with(basename($file), '_')) {
            continue;
        }

        $text = (string) file_get_contents($file);
        $writes = preg_match(
            '/execute_query\s*\(|\bINSERT\s+INTO\b|\bUPDATE\s+[`a-z0-9_]+\s+SET\b|\bDELETE\s+FROM\b|db\(\)->exec|site_setting_set\s*\(|analytics_setting_set\s*\(/i',
            $text
        ) === 1;
        if (!$writes) {
            continue;
        }

        $hasAuth = preg_match('/admin_require_auth\s*\(/', $text) === 1;
        $hasPostGate = preg_match('/!is_post\s*\(|if\s*\(\s*is_post\s*\(\s*\)\s*\)|REQUEST_METHOD.*POST/s', $text) === 1;
        $hasGuard = preg_match('/guard_post_size\s*\(/', $text, $guardMatch, PREG_OFFSET_CAPTURE) === 1;
        $hasCsrf = preg_match('/require_csrf\s*\(/', $text, $csrfMatch, PREG_OFFSET_CAPTURE) === 1;

        if (!$hasAuth || !$hasPostGate || !$hasGuard || !$hasCsrf) {
            $issues[] = $relative . ' missing auth/post gate/guard/csrf coverage';
            continue;
        }

        if (($csrfMatch[0][1] ?? 0) < ($guardMatch[0][1] ?? 0)) {
            $issues[] = $relative . ' calls require_csrf before guard_post_size';
        }
    }

    return smoke_result(
        'static admin write handler gates',
        $issues ? 'fail' : 'pass',
        $issues ? implode('; ', $issues) : 'auth, POST handling, guard, and CSRF ordering found'
    );
}

function smoke_audit_post_forms_have_csrf(): array
{
    $issues = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(SMOKE_ROOT, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'php') {
            continue;
        }

        $path = $fileInfo->getPathname();
        $relative = smoke_relative_path($path);
        $normalized=str_replace('\\','/',$relative);
        // The separately tested preview denies all POST requests and is blocked by Apache.
        if (str_starts_with($normalized, 'assets/') || str_starts_with($normalized,'NEW-SITE/')) {
            continue;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            continue;
        }

        for ($index = 0, $count = count($lines); $index < $count; $index++) {
            if (!str_contains($lines[$index], '<form')) {
                continue;
            }

            $block = '';
            for ($offset = 0; $offset < 40 && ($index + $offset) < $count; $offset++) {
                $block .= $lines[$index + $offset] . "\n";
                if (stripos($lines[$index + $offset], '</form>') !== false) {
                    break;
                }
            }

            if (preg_match('/method\s*=\s*["\']post["\']/i', $block) !== 1) {
                continue;
            }

            $hasStandardCsrf = str_contains($block, 'csrf_field(');
            $hasInstallerCsrf = str_contains($relative, 'install.php')
                && str_contains($block, 'name="_token"')
                && str_contains($block, 'installer_csrf_token()');
            if (!$hasStandardCsrf && !$hasInstallerCsrf) {
                $issues[] = $relative . ':' . ($index + 1);
            }
        }
    }

    return smoke_result(
        'static POST forms include CSRF fields',
        $issues ? 'fail' : 'pass',
        $issues ? 'missing CSRF near forms: ' . implode(', ', $issues) : 'all scanned POST forms include CSRF fields'
    );
}

function smoke_audit_installer_csrf(): array
{
    $path = SMOKE_ROOT . '/install.php';
    $text = is_file($path) ? (string) file_get_contents($path) : '';
    $ok = str_contains($text, 'installer_csrf_token')
        && str_contains($text, 'installer_verify_csrf')
        && str_contains($text, 'name="_token"');

    return smoke_result(
        'static installer CSRF wiring',
        $ok ? 'pass' : 'fail',
        $ok ? 'installer token generation, verification, and form field found' : 'installer CSRF wiring is incomplete'
    );
}

function smoke_audit_subscribe_csrf(): array
{
    $widget = (string) @file_get_contents(SMOKE_ROOT . '/includes/blog_widgets.php');
    $subscribe = (string) @file_get_contents(SMOKE_ROOT . '/subscribe.php');
    $ok = str_contains($widget, "base_url('subscribe.php')")
        && str_contains($widget, 'csrf_field()')
        && str_contains($subscribe, 'guard_post_size()')
        && str_contains($subscribe, 'require_csrf()');

    return smoke_result(
        'static subscribe/blog widget CSRF wiring',
        $ok ? 'pass' : 'fail',
        $ok ? 'blog widget emits CSRF and subscribe.php requires it' : 'subscribe/blog widget CSRF wiring is incomplete'
    );
}

function smoke_run_case_process(string $caseId): array
{
    $cmd = [PHP_BINARY, __FILE__, '--case=' . $caseId];
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($cmd, $descriptorSpec, $pipes, SMOKE_ROOT);
    if (!is_resource($process)) {
        return smoke_result($caseId, 'fail', 'could not start child PHP process');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]) ?: '';
    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if (!preg_match('/' . preg_quote(SMOKE_MARKER, '/') . '(\{.*\})/s', $stdout, $match)) {
        return smoke_result($caseId, 'fail', 'missing child result marker; exit=' . $exitCode . '; stderr=' . trim($stderr));
    }

    $result = json_decode($match[1], true);
    if (!is_array($result)) {
        return smoke_result($caseId, 'fail', 'invalid child result JSON');
    }

    if ($exitCode !== 0 && ($result['status'] ?? '') !== 'skip') {
        $result['status'] = 'fail';
        $result['detail'] = trim(($result['detail'] ?? '') . ' child exit=' . $exitCode . ' stderr=' . trim($stderr));
    }

    return $result;
}

function smoke_run_child_case(string $caseId, array $cases): void
{
    $case = $cases[$caseId] ?? null;
    if (!is_array($case)) {
        echo SMOKE_MARKER . json_encode(smoke_result($caseId, 'fail', 'unknown case')) . PHP_EOL;
        return;
    }

    $result = smoke_result((string) $case['label'], 'fail', 'case did not complete');
    $startedBuffers = ob_get_level();
    ob_start();

    register_shutdown_function(static function () use (&$result, $startedBuffers, $case): void {
        $fatal = error_get_last();
        if (is_array($fatal) && in_array((int) ($fatal['type'] ?? 0), [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $result = smoke_result($result['name'] ?? 'child case', 'fail', 'fatal: ' . (string) ($fatal['message'] ?? 'unknown'));
        }

        if (($result['detail'] ?? '') === 'case did not complete' && isset($case['expect_code'])) {
            $code = smoke_http_code();
            $expectedCode = (int) $case['expect_code'];
            $result = smoke_result(
                (string) $case['label'],
                $code === $expectedCode ? 'pass' : 'fail',
                'code=' . $code . ' expected=' . $expectedCode
            );
        }

        if (isset($case['expect_policy_charge'])) {
            $saved = ict_cancellation_settings();
            $page = fetch_one('SELECT content, is_published FROM static_pages WHERE slug = :slug', ['slug' => 'cancellation-refund-policy']);
            $ok = smoke_http_code() === 302 && db()->inTransaction()
                && $saved['bands'][1]['charge_percent'] === $case['expect_policy_charge']
                && $page && !(bool) $page['is_published'] && $page['content'] === ict_cancellation_draft_html($saved);
            $result = smoke_result((string) $case['label'], $ok ? 'pass' : 'fail', 'settings/CMS draft saved=' . ($ok ? 'yes' : 'no'));
        }

        if (isset($case['expect_retention_period'])) {
            $saved = ict_retention_settings();
            $page = fetch_one('SELECT content, is_published FROM static_pages WHERE slug = :slug', ['slug' => 'privacy-policy']);
            $ok = smoke_http_code() === 302 && db()->inTransaction()
                && $saved['rules']['enquiries']['period'] === $case['expect_retention_period']
                && fetch_all('SELECT * FROM static_pages ORDER BY id') === ($GLOBALS['_smoke_retention_pages'] ?? null);
            $result = smoke_result((string) $case['label'], $ok ? 'pass' : 'fail', 'retention settings saved/policies preserved=' . ($ok ? 'yes' : 'no'));
        }
        if (array_key_exists('expect_choice', $case)) {
            $state = ict_privacy_state();
            $expected=!empty($case['expect_available_analytics'])?ict_privacy_available_categories(ict_privacy_settings())['analytics']:$case['expect_choice'];
            $ok = smoke_http_code() === 200 && $state['chosen'] && $state['analytics'] === $expected;
            foreach ($case['expect_categories'] ?? [] as $category => $expected) { $ok = $ok && $state[$category] === $expected; }
            $result = smoke_result((string) $case['label'], $ok ? 'pass' : 'fail', 'choice saved=' . ($ok ? 'yes' : 'no'));
        }
        if (!empty($case['expect_guest'])) {
            $ok = smoke_http_code() === 302 && !admin_check();
            $result = smoke_result((string) $case['label'], $ok ? 'pass' : 'fail', 'guest redirected=' . ($ok ? 'yes' : 'no'));
        }
        if (isset($case['expect_privacy_days'])) {
            $saved = ict_privacy_settings();
            $ok = smoke_http_code() === 302 && db()->inTransaction() && $saved['preference_days'] === $case['expect_privacy_days'] && !$saved['gtm_allowed'] && !$saved['review_invitations_allowed'] && $saved['public_ai_search_enabled'] === ($case['expect_public_ai'] ?? false);
            $result = smoke_result((string) $case['label'], $ok ? 'pass' : 'fail', 'settings boundary=' . ($ok ? 'yes' : 'no'));
        }
        if (!empty($case['expect_workbook']) && ($result['status']??'')!=='skip') {
            $body = (string) ob_get_contents();
            $ok = str_starts_with($body, "PK\x03\x04") && strlen($body) > 1000;
            $result = smoke_result((string) $case['label'], $ok ? 'pass' : 'fail', 'xlsx_bytes=' . strlen($body));
        }

        if (isset($case['expect_confirmation_status'])) {
            $plan = fetch_one('SELECT booking_status, contact_inquiry_id FROM customer_itinerary_plans WHERE id = :id', ['id' => $GLOBALS['_smoke_confirmation_plan_id'] ?? 0]);
            $rows = $plan ? fetch_all('SELECT note FROM contact_inquiry_activity WHERE inquiry_id = :id AND action_type = :action', ['id' => $plan['contact_inquiry_id'], 'action' => ICT_CONFIRMATION_EVIDENCE_ACTION]) : [];
            $messages = $plan ? (int) fetch_one('SELECT COUNT(*) AS total FROM contact_inquiry_messages WHERE inquiry_id = :id', ['id' => $plan['contact_inquiry_id']])['total'] : -1;
            $expectedWrites = $case['expect_confirmation_status'] === 'confirmed' ? 1 : 0;
            $ok = $plan && $plan['booking_status'] === $case['expect_confirmation_status'] && count($rows) === $expectedWrites && $messages === $expectedWrites && db()->inTransaction();
            if ($expectedWrites && $rows) { $evidence = json_decode($rows[0]['note'], true); $ok = $ok && ($evidence['payment_status'] ?? '') === $case['confirmation_mode']; }
            $result = smoke_result((string) $case['label'], $ok ? 'pass' : 'fail', 'status/evidence/message atomic=' . ($ok ? 'yes' : 'no'));
        }

        if (isset($case['expect_workflow_status'])) {
            $plan = fetch_one('SELECT booking_status, booking_confirmed_at, contact_inquiry_id FROM customer_itinerary_plans WHERE id = :id', ['id' => $GLOBALS['_smoke_confirmation_plan_id'] ?? 0]);
            $messages = $plan ? (int) fetch_one('SELECT COUNT(*) AS total FROM contact_inquiry_messages WHERE inquiry_id = :id', ['id' => $plan['contact_inquiry_id']])['total'] : -1;
            $activity = $plan ? (int) fetch_one('SELECT COUNT(*) AS total FROM contact_inquiry_activity WHERE inquiry_id = :id', ['id' => $plan['contact_inquiry_id']])['total'] : -1;
            $archives = (int) fetch_one('SELECT COUNT(*) AS total FROM customer_policy_acceptances WHERE plan_id = :id', ['id' => $GLOBALS['_smoke_confirmation_plan_id'] ?? 0])['total'];
            $expectedArchives = $case['expect_workflow_status'] === 'accepted' ? 1 : 0;
            $ok = smoke_http_code() === $case['expect_code'] && $plan && $plan['booking_status'] === $case['expect_workflow_status'] && $plan['booking_confirmed_at'] === null && $messages === $case['expect_workflow_writes'] && $activity === $case['expect_workflow_writes'] && $archives === $expectedArchives && db()->inTransaction();
            $result = smoke_result((string) $case['label'], $ok ? 'pass' : 'fail', 'status/archive/message/activity atomic=' . ($ok ? 'yes' : 'no'));
        }

        if (function_exists('db')) {
            try {
                if (db()->inTransaction()) {
                    db()->rollBack();
                    $result['detail'] = trim((string) ($result['detail'] ?? '') . ' rollback=yes');
                }
            } catch (Throwable) {
                // Ignore rollback checks during shutdown.
            }
        }

        while (ob_get_level() > $startedBuffers) {
            ob_end_clean();
        }

        echo SMOKE_MARKER . json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    });

    try {
        smoke_execute_case($case, $result);
    } catch (Throwable $e) {
        $result = smoke_result((string) ($case['label'] ?? $caseId), 'fail', get_class($e) . ': ' . $e->getMessage());
    }
}

function smoke_execute_case(array $case, array &$result): void
{
    require_once SMOKE_ROOT . '/includes/functions.php';

    $target = (string) $case['target'];
    $method = (string) ($case['method'] ?? 'GET');
    $uri = (string) ($case['uri'] ?? '/' . $target);

    $_GET = [];
    $_POST = [];
    $_REQUEST = [];
    $_COOKIE = $_COOKIE ?? [];
    $_FILES = [];
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/' . $target;
    $_SERVER['CONTENT_LENGTH'] = '0';
    $_SERVER['HTTP_HOST'] = 'localhost';

    $setup = (string) ($case['setup'] ?? '');
    if ($setup === 'public_ai_off') {
        ensure_site_settings_schema();
        // Warm shell DDL before the rollback fixture; never enable a real provider.
        $recordPageView = false; ob_start(); require SMOKE_ROOT . '/includes/header.php'; require SMOKE_ROOT . '/includes/footer.php'; ob_end_clean();
        db()->beginTransaction();
        $settings = array_intersect_key(ict_privacy_settings(), ict_privacy_defaults());
        $settings['public_ai_search_enabled'] = false;
        ict_privacy_save_settings($settings);
        $_GET = ['q' => 'Synthetic legacy query'];
        if (($case['ai_mode'] ?? '') === 'csrf') { $_POST = ['_token' => 'invalid']; }
        if (($case['ai_mode'] ?? '') === 'large') { $_SERVER['CONTENT_LENGTH'] = '16385'; }
    } elseif ($setup === 'privacy_choice') {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $mode = $case['choice_action'];
        $_POST = ['_token' => csrf_token(), 'choice_action' => $mode, 'analytics' => '1', 'purpose_revision' => ict_privacy_settings()['purpose_revision']];
        $_POST = array_replace($_POST, $case['choices'] ?? []);
        if ($mode === 'stale') { $_POST['purpose_revision'] = 0; }
        if ($mode === 'large') { $_SERVER['CONTENT_LENGTH'] = '4097'; }
    } elseif ($setup === 'admin_privacy') {
        smoke_setup_admin_or_skip($case, $result);
        ensure_site_settings_schema();
        db()->beginTransaction();
        execute_query('DELETE FROM site_settings WHERE setting_key = :key', ['key' => ICT_PRIVACY_SETTINGS_KEY]);
        ict_privacy_save_settings(ict_privacy_defaults());
        $settings = ict_privacy_defaults();
        if ($case['privacy_mode'] === 'valid') { $settings['preference_days'] = 30; }
        elseif ($case['privacy_mode'] === 'gtm') { $settings['gtm_allowed'] = true; }
        elseif ($case['privacy_mode'] === 'invitations') { $settings['review_invitations_allowed'] = true; }
        else { $settings['public_ai_search_enabled'] = true; }
        $_POST = ['_token' => csrf_token(), 'privacy_settings' => $settings];
        if ($case['privacy_mode'] === 'ai_ack') { $_POST['public_ai_review_ack'] = '1'; }
    } elseif ($setup === 'admin_invalid_csrf') {
        smoke_setup_admin_or_skip($case, $result);
        $_POST = ['_token' => 'invalid-token', 'id' => '0', 'action' => 'invalid'];
    } elseif ($setup === 'admin_render') {
        smoke_setup_admin_or_skip($case, $result);
    } elseif ($setup === 'admin_confirmation_fixture') {
        smoke_setup_admin_or_skip($case, $result);
        smoke_setup_confirmation_fixture((string) $case['confirmation_mode']);
    } elseif ($setup === 'booking_workflow_fixture') {
        smoke_setup_admin_or_skip($case, $result);
        smoke_setup_workflow_fixture((string) $case['workflow_mode']);
    } elseif ($setup === 'admin_policy_valid') {
        smoke_setup_admin_or_skip($case, $result);
        require_once SMOKE_ROOT . '/includes/cancellation_settings.php';
        ensure_site_settings_schema();
        $settings = ict_cancellation_defaults();
        $settings['bands'][1]['charge_percent'] = 30;
        $_POST = ['_token' => csrf_token(), 'policy_settings' => $settings];
        db()->beginTransaction();
    } elseif ($setup === 'admin_retention_workbook') {
        smoke_setup_admin_or_skip($case, $result);
        require_once SMOKE_ROOT . '/includes/records_workbook.php';
        if(!ict_records_workbook_available()){
            $result=smoke_result((string)$case['label'],'skip','private workbook spool or ZIP/XML support is not configured for this CLI identity');
            return;
        }
        ensure_site_settings_schema();
        db()->beginTransaction();
        execute_query('DELETE FROM site_settings WHERE setting_key=:key', ['key'=>ICT_RETENTION_KEY]);
        ict_retention_save(ict_retention_defaults(), 'missing');
    } elseif ($setup === 'admin_retention_valid') {
        smoke_setup_admin_or_skip($case, $result);
        require_once SMOKE_ROOT . '/includes/retention_settings.php';
        ensure_site_settings_schema();
        $settings = ict_retention_settings();
        $settings['rules']['enquiries']['period'] = 9;
        $GLOBALS['_smoke_retention_pages'] = fetch_all('SELECT * FROM static_pages ORDER BY id');
        $_POST = ['_token' => csrf_token(), 'retention_settings' => $settings, 'retention_token' => ict_retention_state()['token']];
        db()->beginTransaction();
    } elseif ($setup === 'subscribe_invalid_csrf') {
        $_POST = ['_token' => 'invalid-token', 'email' => 'visitor@example.test'];
        $_SERVER['HTTP_REFERER'] = base_url('blog');
    } elseif ($setup === 'track_invalid_csrf') {
        $_POST = ['_token' => 'invalid-token', 'event_type' => 'whatsapp_click'];
    } elseif ($setup === 'track_valid_invalid_event') {
        $_POST = ['_token' => csrf_token(), 'event_type' => 'bad event', 'target_url' => base_url('contact')];
    } elseif ($setup === 'route_save') {
        $token = bin2hex(random_bytes(32));
        $_COOKIE['ict_route_token'] = $token;
        $slug = smoke_first_published_slug((string) ($case['slug_table'] ?? ''));
        $_POST = [
            '_token' => csrf_token(),
            'token' => $token,
            'action' => (string) ($case['action'] ?? ''),
            (string) ($case['slug_field'] ?? 'slugs') => $slug,
        ];
        db()->beginTransaction();
    } elseif ($setup === 'render') {
        // No extra setup needed.
    } elseif ($setup === 'detail_slug') {
        $slug = smoke_first_published_slug((string) ($case['slug_table'] ?? ''));
        if ($slug === '') {
            $result = smoke_result((string) $case['label'], 'skip', 'no published slug in ' . (string) ($case['slug_table'] ?? 'table'));
            return;
        }
        $_GET = ['slug' => $slug];
        $uriPrefix = (string) ($case['uri_prefix'] ?? '/');
        $_SERVER['REQUEST_URI'] = rtrim($uriPrefix, '/') . '/' . rawurlencode($slug);
    } elseif ($setup === 'fixed_get') {
        $get = $case['get'] ?? [];
        $_GET = is_array($get) ? $get : [];
    } elseif ($setup === 'sitemap_type') {
        $type = (string) ($case['sitemap_type'] ?? '');
        $_GET = ['type' => $type];
        $_SERVER['REQUEST_URI'] = '/sitemap-' . $type . '.xml';
    } else {
        $result = smoke_result((string) $case['label'], 'fail', 'unknown setup: ' . $setup);
        return;
    }

    $_REQUEST = array_merge($_GET, $_POST);

    ob_start();
    include SMOKE_ROOT . '/' . $target;
    $body = (string) ob_get_clean();

    $code = smoke_http_code();
    $expectedCode = isset($case['expect_code']) ? (int) $case['expect_code'] : null;
    $expectedRenderMin = isset($case['expect_render_min']) ? (int) $case['expect_render_min'] : null;

    if ($expectedRenderMin !== null) {
        $length = strlen($body);
        $expectedContains = isset($case['expect_contains']) ? (string) $case['expect_contains'] : '';
        $containsOk = $expectedContains === '' || str_contains($body, $expectedContains);
        $lengthOk = $length >= $expectedRenderMin;
        $result = smoke_result(
            (string) $case['label'],
            ($lengthOk && $containsOk) ? 'pass' : 'fail',
            'render_bytes=' . $length
                . ($expectedContains !== '' ? ' contains=' . ($containsOk ? 'yes' : 'no') : '')
        );
        return;
    }

    $result = smoke_result(
        (string) $case['label'],
        $expectedCode === null || $code === $expectedCode ? 'pass' : 'fail',
        'code=' . $code . ($expectedCode !== null ? ' expected=' . $expectedCode : '')
    );
}

function smoke_setup_confirmation_fixture(string $mode): void
{
    require_once SMOKE_ROOT . '/includes/customer_auth.php';
    require_once SMOKE_ROOT . '/includes/booking_confirmation.php';
    ensure_customer_accounts_schema();
    ensure_site_settings_schema();
    if (!ict_confirmation_storage_ready()) { throw new RuntimeException('Confirmation fixture needs compatible existing storage.'); }
    db()->beginTransaction();
    foreach (ict_policy_required_keys() as $key) {
        $doc = ict_policy_documents()[$key];
        execute_query('UPDATE static_pages SET is_published = 1, title = :title, meta_title = :meta, meta_description = :description, content = :content WHERE slug = :slug', [
            'slug' => $doc['slug'], 'title' => 'Synthetic ' . $key, 'meta' => 'Synthetic policy', 'description' => 'Isolated policy fixture.', 'content' => '<p>Isolated synthetic policy content for rollback-only confirmation handler verification.</p>',
        ]);
    }
    $documents = ict_policy_published_documents();
    if (count($documents) !== 4) { throw new RuntimeException('Synthetic policy fixture is unavailable.'); }
    $snapshot = ict_policy_acceptance_snapshot($documents);
    $token = bin2hex(random_bytes(8));
    execute_query('INSERT INTO customer_users (google_sub, email, name) VALUES (:sub, :email, :name)', ['sub' => 'confirm-smoke-' . $token, 'email' => $token . '@example.invalid', 'name' => 'Synthetic confirmation smoke']);
    $customerId = (int) last_insert_id();
    execute_query("INSERT INTO contact_inquiries (name, message, customer_user_id) VALUES ('Synthetic confirmation smoke', 'Rollback-only fixture', :customer)", ['customer' => $customerId]);
    $inquiryId = (int) last_insert_id();
    execute_query("INSERT INTO customer_itinerary_plans (customer_user_id, contact_inquiry_id, plan_title, plan_status, booking_status, booking_terms_version, cancellation_policy_version, payment_policy_version, privacy_policy_version, policy_snapshot_hash, policy_snapshot_json, policy_accepted_at, booking_terms_accepted_at, cancellation_policy_accepted_at)
        VALUES (:customer, :inquiry, 'Synthetic confirmation smoke', 'confirmed', 'accepted', :terms, :cancel, :payment, :privacy, :hash, :json, '2026-10-12 12:00:00', '2026-10-12 12:00:00', '2026-10-12 12:00:00')", [
        'customer' => $customerId, 'inquiry' => $inquiryId, 'terms' => $documents['booking_terms']['version'], 'cancel' => $documents['cancellation_refund']['version'], 'payment' => $documents['payment_deposit']['version'], 'privacy' => $documents['privacy']['version'], 'hash' => $snapshot['hash'], 'json' => $snapshot['json'],
    ]);
    $planId = (int) last_insert_id();
    $append = static function () use ($planId): int {
        execute_query('INSERT INTO customer_policy_acceptances (plan_id, customer_user_id, booking_terms_version, booking_terms_accepted_at, cancellation_policy_version, cancellation_policy_accepted_at, payment_policy_version, privacy_policy_version, policy_snapshot_hash, policy_snapshot_json, accepted_at)
            SELECT id, customer_user_id, booking_terms_version, booking_terms_accepted_at, cancellation_policy_version, cancellation_policy_accepted_at, payment_policy_version, privacy_policy_version, policy_snapshot_hash, policy_snapshot_json, policy_accepted_at FROM customer_itinerary_plans WHERE id = :id', ['id' => $planId]);
        return (int) last_insert_id();
    };
    $evidence = ['quote_reference' => 'SYN-QUOTE-01', 'payment_status' => $mode === 'no_payment_due' ? 'no_payment_due' : 'funds_received', 'payment_reference' => $mode === 'no_payment_due' ? 'SYN-QUOTE-01 section 4: no payment due now' : 'SYN-RECEIPT-01', 'supplier_reference' => 'SYN-AVAILABILITY-01', 'payment_verified' => '1', 'supplier_verified' => '1', 'final_services_reviewed' => '1', 'accepted_snapshot_hash' => $snapshot['hash'], 'acceptance_record_id' => (string) $append()];
    if ($mode === 'missing') { unset($evidence['payment_reference']); }
    if ($mode === 'stale') { $append(); }
    $GLOBALS['_smoke_confirmation_plan_id'] = $planId;
    $_POST = ['_token' => csrf_token(), 'plan_id' => $planId, 'action' => 'confirm_booking', 'confirmation_evidence' => $evidence];
}

function smoke_setup_workflow_fixture(string $mode): void
{
    smoke_setup_confirmation_fixture('funds_received');
    $planId = (int) $GLOBALS['_smoke_confirmation_plan_id'];
    $plan = fetch_one('SELECT customer_user_id, policy_snapshot_hash FROM customer_itinerary_plans WHERE id = :id', ['id' => $planId]);
    execute_query('DELETE FROM customer_policy_acceptances WHERE plan_id = :id', ['id' => $planId]);
    $requesting = in_array($mode, ['request', 'unreviewed'], true);
    execute_query('UPDATE customer_itinerary_plans SET booking_status = :status, policy_snapshot_hash = NULL, policy_snapshot_json = NULL, policy_accepted_at = NULL, booking_terms_accepted_at = NULL, cancellation_policy_accepted_at = NULL WHERE id = :id', ['status' => $requesting ? 'planning' : 'awaiting_acceptance', 'id' => $planId]);
    if ($requesting) {
        $_POST = ['_token' => csrf_token(), 'plan_id' => $planId, 'action' => 'request_acceptance', 'final_plan_reviewed' => $mode === 'request' ? '1' : ''];
    } else {
        $_SESSION[CUSTOMER_SESSION_KEY] = (int) $plan['customer_user_id'];
        if ($mode === 'other_owner') {
            $token = bin2hex(random_bytes(8));
            execute_query('INSERT INTO customer_users (google_sub, email, name) VALUES (:sub, :email, :name)', ['sub' => 'workflow-other-' . $token, 'email' => $token . '@example.invalid', 'name' => 'Synthetic other owner']);
            $_SESSION[CUSTOMER_SESSION_KEY] = (int) last_insert_id();
        }
        $_POST = ['_token' => $mode === 'invalid_csrf' ? 'invalid' : csrf_token(), 'plan_id' => $planId, 'policy_snapshot_hash' => $mode === 'stale_policy' ? str_repeat('0', 64) : $plan['policy_snapshot_hash'], 'accept_booking_terms' => '1', 'accept_cancellation_policy' => '1', 'accept_payment_policy' => '1', 'accept_privacy_policy' => '1'];
    }
}

function smoke_setup_admin_or_skip(array $case, array &$result): void
{
    require_once SMOKE_ROOT . '/includes/auth.php';
    if (!db_table_exists('admins')) {
        $result = smoke_result((string) $case['label'], 'skip', 'admins table is not available');
        exit(0);
    }

    $admin = fetch_one('SELECT id, password_hash FROM admins WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
    if (!$admin) {
        $result = smoke_result((string) $case['label'], 'skip', 'no active admin row');
        exit(0);
    }

    $_SESSION[ADMIN_SESSION_KEY] = (int) $admin['id'];
    $_SESSION[ADMIN_CREDENTIAL_STAMP_KEY] = admin_credential_stamp((int) $admin['id'], (string) $admin['password_hash']);
}

function smoke_first_published_slug(string $table): string
{
    if ($table === '' || !db_table_exists($table) || !db_column_exists($table, 'slug')) {
        return '';
    }

    $where = db_column_exists($table, 'is_published') ? ' WHERE is_published = 1' : '';
    $row = fetch_one('SELECT slug FROM ' . $table . $where . ' ORDER BY id ASC LIMIT 1');

    return trim((string) ($row['slug'] ?? ''));
}

function smoke_http_code(): int
{
    $code = http_response_code();
    return is_int($code) && $code > 0 ? $code : 200;
}

function smoke_print_result(array $result): void
{
    $status = strtoupper((string) ($result['status'] ?? 'fail'));
    $name = (string) ($result['name'] ?? 'unnamed');
    $detail = trim((string) ($result['detail'] ?? ''));
    echo '[' . $status . '] ' . $name . ($detail !== '' ? ' - ' . $detail : '') . PHP_EOL;
}

function smoke_result(string $name, string $status, string $detail = ''): array
{
    return [
        'name' => $name,
        'status' => $status,
        'detail' => $detail,
    ];
}

function smoke_relative_path(string $path): string
{
    $root = rtrim(str_replace('\\', '/', realpath(SMOKE_ROOT) ?: SMOKE_ROOT), '/') . '/';
    $normalized = str_replace('\\', '/', $path);
    $real = realpath($path);
    if ($real !== false) {
        $normalized = str_replace('\\', '/', $real);
    }

    return str_starts_with($normalized, $root)
        ? substr($normalized, strlen($root))
        : $normalized;
}
