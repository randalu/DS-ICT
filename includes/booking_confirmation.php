<?php
declare(strict_types=1);
require_once __DIR__ . '/policy_acceptance.php';

const ICT_CONFIRMATION_EVIDENCE_ACTION = 'booking_confirmation_evidence';

function ict_confirmation_validate(array $input): array
{
    $result = [];
    foreach (['quote_reference', 'payment_reference', 'supplier_reference'] as $field) {
        $value = $input[$field] ?? null;
        if (!is_string($value) || trim($value) === '' || !mb_check_encoding($value, 'UTF-8')
            || mb_strlen($value) > 190 || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw new InvalidArgumentException('Enter each quote, payment and supplier reference (up to 190 characters; reference IDs only).');
        }
        $result[$field] = trim($value);
    }
    $status = $input['payment_status'] ?? null;
    if (!in_array($status, ['funds_received', 'no_payment_due'], true)) {
        throw new InvalidArgumentException('Select received funds or an explicit quote term stating no payment is due at confirmation.');
    }
    $result['payment_status'] = $status;
    foreach (['payment_verified', 'supplier_verified', 'final_services_reviewed'] as $field) {
        if (!in_array($input[$field] ?? null, ['1', 1], true)) {
            throw new InvalidArgumentException('Verify the quote payment requirement, supplier availability and final services before confirmation.');
        }
        $result[$field] = true;
    }
    $hash = $input['accepted_snapshot_hash'] ?? null;
    $acceptanceId = filter_var($input['acceptance_record_id'] ?? null, FILTER_VALIDATE_INT);
    if (!is_string($hash) || !preg_match('/^[a-f0-9]{64}$/', $hash) || $acceptanceId === false || $acceptanceId <= 0) {
        throw new InvalidArgumentException('Reload the plan to bind confirmation to the current acceptance record.');
    }
    $result['accepted_snapshot_hash'] = $hash;
    $result['acceptance_record_id'] = $acceptanceId;
    return $result;
}

/** No DDL: evidence and notification storage must already support atomic writes. */
function ict_confirmation_storage_ready(): bool
{
    $tables = [
        'contact_inquiry_activity' => ['id', 'inquiry_id', 'action_type', 'old_value', 'new_value', 'note', 'admin_user_id', 'created_at'],
        'contact_inquiry_messages' => ['id', 'inquiry_id', 'admin_user_id', 'sender_type', 'message_body', 'is_visible_to_customer'],
        'customer_policy_acceptances' => ['id', 'plan_id', 'customer_user_id', 'policy_snapshot_hash', 'policy_snapshot_json', 'accepted_at'],
        'customer_itinerary_plans' => ['id', 'customer_user_id', 'contact_inquiry_id', 'booking_status', 'booking_confirmed_at', 'booking_confirmed_by_admin_user_id', 'last_admin_update_at', 'policy_snapshot_hash', 'policy_snapshot_json', 'policy_accepted_at'],
        'customer_itinerary_change_requests' => ['id', 'plan_id', 'request_status'],
        'contact_inquiries' => ['id'],
        'static_pages' => ['id', 'slug', 'title', 'content', 'meta_title', 'meta_description', 'updated_at', 'is_published'],
    ];
    foreach ($tables as $table => $columns) {
        if (!db_table_exists($table)) { return false; }
        foreach ($columns as $column) { if (!db_column_exists($table, $column)) { return false; } }
        $engine = fetch_one('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :name', ['name' => $table]);
        if (strtoupper((string) ($engine['ENGINE'] ?? '')) !== 'INNODB') { return false; }
    }
    return true;
}

/** Keep caller transactions intact, while rolling back this operation on every failure. */
function ict_confirmation_atomic(callable $operation): mixed
{
    static $sequence = 0;
    $pdo = db();
    $owns = !$pdo->inTransaction();
    $savepoint = 'ict_confirmation_' . ++$sequence;
    if ($owns) { $pdo->beginTransaction(); } else { $pdo->exec('SAVEPOINT ' . $savepoint); }
    try {
        $result = $operation();
        if ($owns) { $pdo->commit(); } else { $pdo->exec('RELEASE SAVEPOINT ' . $savepoint); }
        return $result;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            if ($owns) { $pdo->rollBack(); }
            else { $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint); $pdo->exec('RELEASE SAVEPOINT ' . $savepoint); }
        }
        throw $exception;
    }
}

/** Operator-reported evidence only; this function never settles funds or reserves suppliers. */
function ict_confirm_booking(int $planId, int $adminId, array $input): int
{
    $validated = ict_confirmation_validate($input);
    if ($planId <= 0 || $adminId <= 0) { throw new InvalidArgumentException('Invalid plan or operator.'); }
    if (!ict_confirmation_storage_ready()) { throw new RuntimeException('Transactional confirmation evidence storage is unavailable.'); }
    return ict_confirmation_atomic(static function () use ($planId, $adminId, $validated): int {
        $plan = fetch_one('SELECT * FROM customer_itinerary_plans WHERE id = :id LIMIT 1 FOR UPDATE', ['id' => $planId]);
        require_once __DIR__ . '/quotation_booking.php';
        $guest = $plan && $plan['customer_user_id'] === null;
        $binding = quote_assert_binding($planId, $validated['acceptance_record_id'], $guest);
        if ($guest && !$binding) { throw new RuntimeException('Guest confirmation requires a quotation and recorded acceptance.'); }
        if (!$plan || $plan['booking_status'] !== 'accepted' || !ict_policy_plan_acceptance_state($plan, ict_policy_published_documents(true))['ready_to_confirm']) {
            throw new RuntimeException('Confirmation requires current policy acceptance and an accepted plan.');
        }
        if (fetch_all("SELECT id FROM customer_itinerary_change_requests WHERE plan_id = :id AND request_status = 'pending' FOR UPDATE", ['id' => $planId])) {
            throw new RuntimeException('Resolve pending itinerary changes before confirmation.');
        }
        $acceptance = $guest
            ? fetch_one('SELECT id, NULL AS customer_user_id, policy_snapshot_hash, policy_snapshot_json, accepted_at FROM quotation_guest_acceptances WHERE plan_id = :id ORDER BY id DESC LIMIT 1 FOR UPDATE', ['id' => $planId])
            : fetch_one('SELECT id, customer_user_id, policy_snapshot_hash, policy_snapshot_json, accepted_at FROM customer_policy_acceptances WHERE plan_id = :id ORDER BY id DESC LIMIT 1 FOR UPDATE', ['id' => $planId]);
        if (!$acceptance || (int) $acceptance['id'] !== $validated['acceptance_record_id']
            || (int) $acceptance['customer_user_id'] !== (int) $plan['customer_user_id']
            || !hash_equals((string) $plan['policy_snapshot_hash'], $validated['accepted_snapshot_hash'])
            || !hash_equals((string) $plan['policy_snapshot_hash'], (string) $acceptance['policy_snapshot_hash'])
            || $acceptance['policy_snapshot_json'] !== $plan['policy_snapshot_json']
            || $acceptance['accepted_at'] !== $plan['policy_accepted_at']) {
            throw new RuntimeException('Acceptance changed. Reload the plan and recheck the quote, payment and suppliers.');
        }
        $inquiryId = (int) $plan['contact_inquiry_id'];
        if ($inquiryId <= 0 || !fetch_one('SELECT id FROM contact_inquiries WHERE id = :id FOR UPDATE', ['id' => $inquiryId])) {
            throw new RuntimeException('The linked inquiry is unavailable.');
        }
        $evidence = ['schema_version' => 1, 'plan_id' => $planId, 'inquiry_id' => $inquiryId,
            'operator_admin_id' => $adminId, 'recorded_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format(DATE_ATOM),
            'basis' => 'Operator-reported checks; source references require manual verification'] + $validated;
        $note = json_encode($evidence, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $written = execute_query('INSERT INTO contact_inquiry_activity (inquiry_id, action_type, old_value, new_value, note, admin_user_id) VALUES (:inquiry, :action, :old, :new, :note, :admin)', [
            'inquiry' => $inquiryId, 'action' => ICT_CONFIRMATION_EVIDENCE_ACTION, 'old' => 'accepted', 'new' => 'confirmed', 'note' => $note, 'admin' => $adminId,
        ]);
        $evidenceId = (int) last_insert_id();
        if (!$written || $evidenceId <= 0 || (int) db()->query('SELECT ROW_COUNT()')->fetchColumn() !== 1) { throw new RuntimeException('Confirmation evidence was not saved.'); }
        $stored = fetch_one('SELECT inquiry_id, action_type, note, admin_user_id FROM contact_inquiry_activity WHERE id = :id', ['id' => $evidenceId]);
        if (!$stored || $stored['note'] !== $note || $stored['action_type'] !== ICT_CONFIRMATION_EVIDENCE_ACTION
            || (int) $stored['inquiry_id'] !== $inquiryId || (int) $stored['admin_user_id'] !== $adminId) {
            throw new RuntimeException('Confirmation evidence could not be verified after saving.');
        }
        $updated = execute_query("UPDATE customer_itinerary_plans SET booking_status = 'confirmed', booking_confirmed_at = NOW(), booking_confirmed_by_admin_user_id = :admin, last_admin_update_at = NOW() WHERE id = :id AND booking_status = 'accepted'", ['admin' => $adminId, 'id' => $planId]);
        if (!$updated || (int) db()->query('SELECT ROW_COUNT()')->fetchColumn() !== 1) { throw new RuntimeException('Booking status changed in another request.'); }
        $message = 'Island Culture Travels has issued written confirmation for your booking. Refer to the confirmed itinerary and payment instructions for the agreed service details.';
        $sent = execute_query("INSERT INTO contact_inquiry_messages (inquiry_id, admin_user_id, sender_type, message_body, is_visible_to_customer) VALUES (:inquiry, :admin, 'admin', :message, 1)", ['inquiry' => $inquiryId, 'admin' => $adminId, 'message' => $message]);
        $messageId = (int) last_insert_id();
        if (!$sent || $messageId <= 0 || (int) db()->query('SELECT ROW_COUNT()')->fetchColumn() !== 1) { throw new RuntimeException('Written confirmation was not saved.'); }
        $storedMessage = fetch_one('SELECT message_body, is_visible_to_customer FROM contact_inquiry_messages WHERE id = :id', ['id' => $messageId]);
        if (!$storedMessage || $storedMessage['message_body'] !== $message || (int) $storedMessage['is_visible_to_customer'] !== 1) {
            throw new RuntimeException('Written confirmation could not be verified after saving.');
        }
        return $evidenceId;
    });
}
