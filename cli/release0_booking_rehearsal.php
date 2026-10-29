<?php
declare(strict_types=1);

/** Local, rollback-only service rehearsal. Never calls mail, WhatsApp, banks or suppliers. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/booking_workflow.php';
if (APP_ENV === 'production') { fwrite(STDERR, "Use a disposable local database, never production.\n"); exit(2); }
if (db()->inTransaction() || !ict_confirmation_storage_ready()) {
    fwrite(STDERR, "Existing transactional booking storage is required; this rehearsal runs no schema initializers.\n");
    exit(1);
}

function rehearsal_baseline(): array
{
    $counts = [];
    foreach (['customer_users', 'contact_inquiries', 'customer_itinerary_plans', 'customer_policy_acceptances', 'contact_inquiry_activity', 'contact_inquiry_messages', 'customer_itinerary_change_requests'] as $table) {
        $counts[$table] = (int) fetch_one('SELECT COUNT(*) AS total FROM ' . $table)['total'];
    }
    $policies = [];
    foreach (ict_policy_documents() as $key => $doc) {
        $policies[$key] = fetch_one('SELECT * FROM static_pages WHERE slug = :slug', ['slug' => $doc['slug']]);
    }
    return ['row_counts' => $counts, 'policy_rows_sha256' => hash('sha256', json_encode($policies, JSON_THROW_ON_ERROR)), 'acceptance_enabled' => ict_policy_acceptance_available()];
}

$before = rehearsal_baseline();
$checks = [];
$cases = [];
$failure = null;
$assert = static function (bool $condition, string $label) use (&$checks): void {
    if (!$condition) { throw new RuntimeException($label); }
    $checks[] = $label;
};
$admin = fetch_one('SELECT id FROM admins WHERE is_active = 1 LIMIT 1');
if (!$admin) { fwrite(STDERR, "An existing active local administrator is required.\n"); exit(1); }
db()->beginTransaction();
try {
    foreach (ict_policy_required_keys() as $key) {
        $doc = ict_policy_documents()[$key];
        execute_query('UPDATE static_pages SET is_published = 1, title = :title, meta_title = :meta, meta_description = :description, content = :content WHERE slug = :slug', ['slug' => $doc['slug'], 'title' => 'Synthetic ' . $key, 'meta' => 'Synthetic policy', 'description' => 'Rollback-only rehearsal', 'content' => '<p>Synthetic reviewed policy text for isolated service testing only. No publication approval or actual booking terms are represented.</p>']);
    }
    $snapshot = ict_policy_acceptance_snapshot(ict_policy_published_documents());
    $assert(count(ict_policy_published_documents()) === 4, 'Four synthetic policy versions available only inside the transaction');
    foreach (['funds_received', 'no_payment_due'] as $index => $basis) {
        $caseRef = 'SYN-ICT-20261013-' . sprintf('%04d', $index + 1);
        $token = bin2hex(random_bytes(8));
        execute_query('INSERT INTO customer_users (google_sub, email, name) VALUES (:sub, :email, :name)', ['sub' => 'workflow-' . $token, 'email' => $token . '@example.invalid', 'name' => 'Synthetic workflow rehearsal']);
        $customerId = (int) last_insert_id();
        execute_query('INSERT INTO contact_inquiries (name, message, customer_user_id) VALUES (:name, :message, :customer)', ['name' => 'Synthetic workflow rehearsal', 'message' => 'Rollback-only ' . $caseRef, 'customer' => $customerId]);
        $inquiryId = (int) last_insert_id();
        execute_query("INSERT INTO customer_itinerary_plans (customer_user_id, contact_inquiry_id, plan_title, plan_status, booking_status) VALUES (:customer, :inquiry, :title, 'draft', 'planning')", ['customer' => $customerId, 'inquiry' => $inquiryId, 'title' => $caseRef]);
        $planId = (int) last_insert_id();
        ict_request_booking_acceptance($planId, (int) $admin['id'], true);
        $assert(fetch_one('SELECT booking_status FROM customer_itinerary_plans WHERE id = :id', ['id' => $planId])['booking_status'] === 'awaiting_acceptance', $caseRef . ': reviewed plan requests acceptance');
        $input = array_fill_keys(['accept_booking_terms', 'accept_cancellation_policy', 'accept_payment_policy', 'accept_privacy_policy'], '1');
        $input['policy_snapshot_hash'] = $snapshot['hash'];
        $acceptanceId = ict_accept_booking_policies($planId, $customerId, $input, null, 'Synthetic rollback-only rehearsal');
        $accepted = fetch_one('SELECT * FROM customer_itinerary_plans WHERE id = :id', ['id' => $planId]);
        $archive = fetch_one('SELECT * FROM customer_policy_acceptances WHERE id = :id', ['id' => $acceptanceId]);
        $assert($accepted['booking_status'] === 'accepted' && $accepted['booking_confirmed_at'] === null, $caseRef . ': acceptance does not confirm booking');
        $assert($archive['policy_snapshot_json'] === $accepted['policy_snapshot_json'] && $archive['policy_snapshot_hash'] === $snapshot['hash'], $caseRef . ': exact reviewed policies archived');
        $assert((int) $accepted['marketing_consent'] === 0, $caseRef . ': optional marketing stays unselected');
        $evidenceInput = ['quote_reference' => $caseRef . '-Q01', 'payment_status' => $basis, 'payment_reference' => $basis === 'funds_received' ? $caseRef . '-PAY01' : $caseRef . '-Q01 section 4: no payment due at confirmation', 'supplier_reference' => $caseRef . '-SUP01', 'payment_verified' => '1', 'supplier_verified' => '1', 'final_services_reviewed' => '1', 'accepted_snapshot_hash' => $snapshot['hash'], 'acceptance_record_id' => (string) $acceptanceId];
        $evidenceId = ict_confirm_booking($planId, (int) $admin['id'], $evidenceInput);
        $saved = json_decode(fetch_one('SELECT note FROM contact_inquiry_activity WHERE id = :id', ['id' => $evidenceId])['note'], true, 16, JSON_THROW_ON_ERROR);
        $confirmed = fetch_one('SELECT booking_status, booking_confirmed_at FROM customer_itinerary_plans WHERE id = :id', ['id' => $planId]);
        $assert($confirmed['booking_status'] === 'confirmed' && $confirmed['booking_confirmed_at'] !== null, $caseRef . ': operator confirmation completes the transition');
        $assert($saved['acceptance_record_id'] === $acceptanceId && $saved['payment_status'] === $basis && $saved['quote_reference'] === $evidenceInput['quote_reference'] && $saved['payment_reference'] === $evidenceInput['payment_reference'] && $saved['supplier_reference'] === $evidenceInput['supplier_reference'], $caseRef . ': references and payment basis bound to archive');
        $assert((int) fetch_one('SELECT COUNT(*) AS total FROM contact_inquiry_messages WHERE inquiry_id = :id AND is_visible_to_customer = 1', ['id' => $inquiryId])['total'] === 3, $caseRef . ': request, acceptance and confirmation workspace messages saved');
        $assert(db()->inTransaction(), $caseRef . ': parent transaction still active after all three operations');
        $cases[] = ['case_reference' => $caseRef, 'payment_basis' => $basis, 'transitions' => ['planning', 'awaiting_acceptance', 'accepted', 'confirmed'], 'synthetic_evidence' => $saved, 'ids_are_rolled_back' => true];
    }
} catch (Throwable $exception) {
    $failure = get_class($exception) . ': ' . $exception->getMessage();
} finally {
    if (db()->inTransaction()) { db()->rollBack(); }
}
$after = rehearsal_baseline();
$restored = $before === $after;
$report = ['checked_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format(DATE_ATOM), 'status' => $failure === null && $restored ? 'passed_local_rollback_only' : 'failed', 'scope' => 'Shared production services used by the handlers; inquiry/plan seeded synthetically. Not browser, payment, supplier or email delivery acceptance.', 'checks_passed' => count($checks), 'checks' => $checks, 'cases' => $cases, 'before' => $before, 'after' => $after, 'row_counts_and_complete_policy_rows_restored' => $restored, 'external_notifications_sent' => false, 'real_booking_confirmed' => false, 'schema_initializers_run' => false, 'failure' => $failure];
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
exit($report['status'] === 'passed_local_rollback_only' ? 0 : 1);
