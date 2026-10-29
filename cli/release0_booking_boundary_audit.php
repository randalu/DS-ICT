<?php
declare(strict_types=1);

/** Technical policy/booking gate only; not certification of the full Release 0 evidence checklist. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/policy_content.php';
require_once __DIR__ . '/../includes/policy_acceptance.php';

$format = 'text';
$strict = false;
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--strict') { $strict = true; continue; }
    if (str_starts_with($argument, '--format=')) {
        $format = strtolower(trim(substr($argument, 9)));
        if (in_array($format, ['text', 'json'], true)) { continue; }
    }
    fwrite(STDERR, "Usage: php cli/release0_booking_boundary_audit.php [--format=text|json] [--strict]\n");
    exit(2);
}

$acceptanceFields = [
    'booking_status', 'booking_terms_version', 'booking_terms_accepted_at',
    'cancellation_policy_version', 'cancellation_policy_accepted_at',
    'payment_policy_version', 'privacy_policy_version', 'marketing_consent',
    'marketing_consent_at', 'customer_ip', 'customer_user_agent',
    'policy_snapshot_hash', 'policy_snapshot_json', 'policy_accepted_at',
    'booking_confirmed_at', 'booking_confirmed_by_admin_user_id',
];
$inspectTable = static function (string $table, ?string $statusColumn) use ($acceptanceFields): array {
    if (!db_table_exists($table)) {
        return ['exists' => false, 'status_column' => null, 'status_counts' => [],
            'row_count' => null, 'acceptance_fields_present' => []];
    }
    $statusCandidates = $statusColumn === 'inquiry_status' ? ['inquiry_status', 'status'] : [$statusColumn];
    $resolvedStatusColumn = null;
    foreach ($statusCandidates as $candidate) {
        if ($candidate !== null && db_column_exists($table, $candidate)) {
            $resolvedStatusColumn = $candidate;
            break;
        }
    }
    $statusCounts = [];
    if ($resolvedStatusColumn !== null) {
        foreach (fetch_all("SELECT `{$resolvedStatusColumn}` AS status, COUNT(*) AS total FROM `{$table}` GROUP BY `{$resolvedStatusColumn}` ORDER BY `{$resolvedStatusColumn}`") as $row) {
            $status = trim((string) ($row['status'] ?? ''));
            if ($status !== '') { $statusCounts[$status] = (int) ($row['total'] ?? 0); }
        }
    }
    return ['exists' => true, 'status_column' => $resolvedStatusColumn,
        'status_counts' => $statusCounts,
        'row_count' => (int) (fetch_one("SELECT COUNT(*) AS total FROM `{$table}`")['total'] ?? 0),
        'acceptance_fields_present' => array_values(array_filter($acceptanceFields,
            static fn(string $field): bool => db_column_exists($table, $field)))];
};

$contact = $inspectTable('contact_inquiries', 'inquiry_status');
$plans = $inspectTable('customer_itinerary_plans', 'plan_status');
$bookingStatusCounts = [];
if ($plans['exists'] && db_column_exists('customer_itinerary_plans', 'booking_status')) {
    foreach (fetch_all('SELECT booking_status AS status, COUNT(*) AS total FROM customer_itinerary_plans GROUP BY booking_status ORDER BY booking_status') as $row) {
        $status = trim((string) ($row['status'] ?? ''));
        if ($status !== '') { $bookingStatusCounts[$status] = (int) ($row['total'] ?? 0); }
    }
}
$acceptanceHistoryCount = db_table_exists('customer_policy_acceptances')
    ? (int) (fetch_one('SELECT COUNT(*) AS total FROM customer_policy_acceptances')['total'] ?? 0) : 0;
$hasContactPlanLink = $contact['exists'] && $plans['exists'] && db_column_exists('customer_itinerary_plans', 'contact_inquiry_id');
$contactWonCount = (int) ($contact['status_counts']['won'] ?? 0);
$planConfirmedCount = (int) ($plans['status_counts']['confirmed'] ?? 0);
$allAcceptanceFields = array_values(array_unique(array_merge($contact['acceptance_fields_present'], $plans['acceptance_fields_present'])));
$policyDocuments = ict_policy_documents();
$policyPageState = [];
if (db_table_exists('static_pages')) {
    foreach ($policyDocuments as $key => $document) {
        $page = fetch_one('SELECT id, is_published FROM static_pages WHERE slug = :slug LIMIT 1', ['slug' => $document['slug']]);
        $policyPageState[$key] = ['slug' => $document['slug'], 'exists' => $page !== null,
            'is_published' => (int) ($page['is_published'] ?? 0) === 1];
    }
}
$requiredPolicyPageState = array_intersect_key($policyPageState, array_flip(ict_policy_required_keys()));
$publishedRequiredPolicyCount = count(array_filter($requiredPolicyPageState, static fn(array $page): bool => $page['exists'] && $page['is_published']));
$policyActivationReady = count(ict_policy_published_documents()) === count(ict_policy_required_keys());
$report = [
    'gate' => 'release0-booking-boundary',
    'gate_status' => $policyActivationReady ? 'ready' : 'pending_policy_review',
    'scope' => 'Technical booking-policy activation only; independent business, legal and operational evidence remains separate.',
    'decision' => 'provisionally_approved_for_implementation',
    'decision_source' => 'business_owner_instruction_2026-09-26',
    'business_identity' => ict_policy_business_identity(),
    'recommended_candidate' => 'customer_itinerary_plans',
    'candidate_requires_business_confirmation' => false,
    'booking_boundary_approved' => true,
    'policy_review_gate_complete' => $policyActivationReady,
    'proposed_confirmation_transition' => [
        'admin_reviewed_final_itinerary' => 'booking_status=awaiting_acceptance',
        'customer_accepted_published_versions' => 'booking_status=accepted',
        'operator_issued_written_confirmation' => 'booking_status=confirmed',
    ],
    'contact_inquiries' => $contact,
    'customer_itinerary_plans' => $plans,
    'signals' => [
        'contact_won_count' => $contactWonCount,
        'plan_review_confirmed_count' => $planConfirmedCount,
        'booking_status_counts' => $bookingStatusCounts,
        'policy_acceptance_history_count' => $acceptanceHistoryCount,
        'customer_plan_links_to_inquiry' => $hasContactPlanLink,
        'acceptance_fields_present_anywhere' => $allAcceptanceFields,
    ],
    'policy_pages' => $policyPageState,
    'blocking_reasons' => !$policyActivationReady ? ['Required policy pages remain unpublished or contain unresolved draft/placeholder content; acceptance remains disabled until reviewed and published.'] : [],
    'evidence_required_before_release' => [
        ...(!$policyActivationReady ? ['Review and publish the booking, cancellation, payment, and privacy policy pages in the CMS.'] : []),
        'Verify the actual supplier, payment, privacy-tool, and retention details represented by the drafts.',
        'Confirm current legal wording and any applicable Sri Lanka consumer or data-protection requirements before publication.',
    ],
];
if ($format === 'json') {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    echo '[' . ($policyActivationReady ? 'READY' : 'PENDING REVIEW') . '] Release 0 provisional booking workflow.' . PHP_EOL;
    echo 'Candidate workflow: ' . $report['recommended_candidate'] . PHP_EOL;
    echo 'contact_inquiries rows: ' . (string) ($contact['row_count'] ?? 'unavailable') . '; won: ' . $contactWonCount . PHP_EOL;
    echo 'customer_itinerary_plans rows: ' . (string) ($plans['row_count'] ?? 'unavailable') . '; confirmed: ' . $planConfirmedCount . PHP_EOL;
    echo 'Acceptance fields present: ' . ($allAcceptanceFields ? implode(', ', $allAcceptanceFields) : 'none') . PHP_EOL;
    echo 'Policy drafts present: ' . (string) count(array_filter($policyPageState, static fn(array $page): bool => $page['exists'] && !$page['is_published'])) . '/' . count($policyDocuments) . PHP_EOL;
    echo 'Booking boundary: admin review -> customer policy acceptance -> operator written confirmation.' . PHP_EOL;
    echo 'Published required policies: ' . $publishedRequiredPolicyCount . '/' . count(ict_policy_required_keys()) . PHP_EOL;
    echo $report['scope'] . PHP_EOL;
}
exit($strict && (!$report['booking_boundary_approved'] || !$policyActivationReady) ? 1 : 0);
