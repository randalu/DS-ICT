<?php
declare(strict_types=1);
require_once __DIR__ . '/quotations.php';

function quote_booking_days(int $planId): array
{
    return fetch_all('SELECT day_number,day_end,title,summary,overnight_location,travel_time,image_path,sort_order FROM customer_itinerary_days WHERE plan_id=:id ORDER BY sort_order,id', ['id' => $planId]);
}
function quote_plan_hash(int $planId): string
{
    $plan = fetch_one('SELECT customer_user_id,plan_title,travel_dates,traveler_summary,trip_support_type FROM customer_itinerary_plans WHERE id=:id', ['id' => $planId]);
    return hash('sha256', quote_json(['plan' => $plan, 'days' => quote_booking_days($planId)]));
}
function quote_booking_binding(int $planId, bool $lock = false): ?array
{
    if (!db_table_exists('quotation_bookings')) { return null; }
    return fetch_one('SELECT * FROM quotation_bookings WHERE plan_id=:id' . ($lock ? ' FOR UPDATE' : ''), ['id' => $planId]);
}
function quote_assert_binding(int $planId, ?int $acceptanceId = null, bool $guest = false): ?array
{
    $binding = quote_booking_binding($planId, true); if (!$binding) { return null; }
    $row = quote_get((int) $binding['revision_id'], true);
    $latest = fetch_one("SELECT id FROM quotation_revisions WHERE quotation_id=:id AND state='issued' ORDER BY revision DESC LIMIT 1 FOR UPDATE", ['id' => $binding['quotation_id']]);
    if ($row['state'] !== 'issued' || $row['withdrawn_at'] !== null || (int) ($latest['id'] ?? 0) !== (int) $row['id'] || !hash_equals($binding['services_sha256'], quote_plan_hash($planId))) { throw new RuntimeException('The agreed quotation or itinerary changed. Review conversion and obtain fresh acceptance.'); }
    if ($acceptanceId !== null) {
        $table = $guest ? 'quotation_guest_acceptances' : 'quotation_customer_acceptances'; $column = $guest ? 'id' : 'acceptance_id';
        $acceptance = fetch_one("SELECT * FROM {$table} WHERE {$column}=:id AND plan_id=:plan FOR UPDATE", ['id' => $acceptanceId, 'plan' => $planId]);
        if (!$acceptance || (int) $acceptance['revision_id'] !== (int) $binding['revision_id'] || !hash_equals($binding['services_sha256'], $acceptance['services_sha256'])) { throw new RuntimeException('Fresh acceptance of the selected quotation is required.'); }
    }
    return $binding;
}
function quote_bind_customer_acceptance(int $planId, int $acceptanceId): void
{
    $binding = quote_assert_binding($planId); if (!$binding) { return; }
    execute_query('INSERT INTO quotation_customer_acceptances (acceptance_id,plan_id,revision_id,services_sha256) VALUES (:id,:plan,:revision,:hash)', ['id' => $acceptanceId, 'plan' => $planId, 'revision' => $binding['revision_id'], 'hash' => $binding['services_sha256']]);
}
function quote_customer_token(int $planId): string
{
    $binding = quote_booking_binding($planId);
    return $binding ? hash('sha256', $binding['revision_id'] . ':' . $binding['option_key'] . ':' . $binding['services_sha256']) : '';
}
function quote_check_customer_token(int $planId, array $input): void
{
    $binding = quote_booking_binding($planId); if (!$binding) { return; }
    quote_assert_binding($planId);
    $quote = quote_get((int) $binding['revision_id']);
    if ($quote['document']['valid_until'] !== '' && $quote['document']['valid_until'] < substr(quote_now(), 0, 10)) { throw new RuntimeException('This quotation expired. Request a reviewed quotation before accepting.'); }
    if (!is_string($input['quotation_token'] ?? null) || !hash_equals(quote_customer_token($planId), $input['quotation_token'])) { throw new RuntimeException('The quotation changed. Reload and review the current services and price.'); }
}
function quote_convert(int $id, string $key, int $admin, int $customerId = 0, bool $replaceReviewed = false): int
{
    if (!in_array($key, ['A', 'B'], true) || !ict_confirmation_storage_ready()) { throw new InvalidArgumentException('Booking storage or selected option is unavailable.'); }
    return ict_confirmation_atomic(static function () use ($id, $key, $admin, $customerId, $replaceReviewed): int {
        $row = quote_get($id, true); $d = $row['document'];
        fetch_one('SELECT id FROM quotations WHERE id=:id FOR UPDATE', ['id' => $row['quotation_id']]);
        $latest = fetch_one("SELECT id FROM quotation_revisions WHERE quotation_id=:id AND state='issued' ORDER BY revision DESC LIMIT 1 FOR UPDATE", ['id' => $row['quotation_id']]);
        if ($row['state'] !== 'issued' || $row['withdrawn_at'] !== null || (int) ($latest['id'] ?? 0) !== $id || !$d['options'][$key]['enabled']) { throw new InvalidArgumentException('Select the latest issued quotation and an enabled option.'); }
        if ($d['valid_until'] !== '' && $d['valid_until'] < substr(quote_now(), 0, 10)) { throw new InvalidArgumentException('This quotation has expired. Issue a reviewed revision.'); }
        if ($customerId && !fetch_one("SELECT id FROM customer_users WHERE id=:id AND status='active'", ['id' => $customerId])) { throw new InvalidArgumentException('Active customer account not found.'); }
        $previous = fetch_one('SELECT * FROM quotation_bookings WHERE quotation_id=:id FOR UPDATE', ['id' => $row['quotation_id']]);
        $plan = $previous ? fetch_one('SELECT * FROM customer_itinerary_plans WHERE id=:id FOR UPDATE', ['id' => $previous['plan_id']]) : null;
        if ($previous && (int) $previous['revision_id'] === $id && $previous['option_key'] === $key && hash_equals($previous['services_sha256'], quote_plan_hash((int) $previous['plan_id']))) { return (int) $previous['plan_id']; }
        if ($plan && (!$replaceReviewed || $plan['booking_status'] === 'confirmed' || (int) ($plan['customer_user_id'] ?? 0) !== $customerId)) { throw new DomainException('Existing booking requires an explicit reviewed replacement; confirmed bookings and account ownership cannot be replaced.'); }
        if ($plan && fetch_one("SELECT id FROM customer_itinerary_change_requests WHERE plan_id=:id AND request_status='pending'", ['id' => $plan['id']])) { throw new DomainException('Resolve pending itinerary changes before replacing the plan.'); }
        $inquiry = $plan ? (int) $plan['contact_inquiry_id'] : (int) $d['inquiry_id'];
        if ($inquiry) {
            $lead = fetch_one('SELECT * FROM contact_inquiries WHERE id=:id FOR UPDATE', ['id' => $inquiry]);
            if (!$lead || (int) ($lead['customer_user_id'] ?? 0) !== $customerId) { throw new InvalidArgumentException('Inquiry ownership differs from the selected customer or guest.'); }
            $existing = fetch_one('SELECT id FROM customer_itinerary_plans WHERE contact_inquiry_id=:id FOR UPDATE', ['id' => $inquiry]);
            if ($existing && (!$plan || (int) $existing['id'] !== (int) $plan['id'])) { throw new DomainException('This inquiry already has a plan. Choose an unlinked quotation or another inquiry.'); }
        } else {
            execute_query('INSERT INTO contact_inquiries (name,email,phone,country,message,customer_user_id,source_page) VALUES (:name,:email,:phone,:country,:message,:customer,:source)', ['name' => $d['customer']['name'], 'email' => $d['customer']['email'] ?: null, 'phone' => $d['customer']['phone'], 'country' => $d['customer']['country'], 'message' => 'Quotation ' . $row['reference'] . ' R' . $row['revision'] . ' Option ' . $key, 'customer' => $customerId ?: null, 'source' => 'admin/quotations.php']);
            $inquiry = (int) last_insert_id();
        }
        execute_query('UPDATE contact_inquiries SET admin_guest_only=:guest WHERE id=:id', ['guest' => $customerId ? 0 : 1, 'id' => $inquiry]);
        $summary = $d['customer']['adults'] . ' adults, ' . $d['customer']['children'] . ' children';
        $params = ['customer' => $customerId ?: null, 'inquiry' => $inquiry, 'title' => $d['title'], 'dates' => str_replace('T', ' ', $d['arrival']) . ' to ' . str_replace('T', ' ', $d['departure']), 'travelers' => $summary];
        if ($plan) {
            $planId = (int) $plan['id'];
            execute_query("UPDATE customer_itinerary_plans SET plan_title=:title,travel_dates=:dates,traveler_summary=:travelers,booking_status='planning',plan_status='planning',policy_snapshot_json=NULL,policy_snapshot_hash=NULL,policy_accepted_at=NULL,booking_terms_version=NULL,cancellation_policy_version=NULL,payment_policy_version=NULL,privacy_policy_version=NULL,booking_terms_accepted_at=NULL,cancellation_policy_accepted_at=NULL,last_admin_update_at=NOW() WHERE id=:id", ['title' => $params['title'], 'dates' => $params['dates'], 'travelers' => $summary, 'id' => $planId]);
            execute_query('DELETE FROM customer_itinerary_days WHERE plan_id=:id', ['id' => $planId]);
        } else {
            execute_query("INSERT INTO customer_itinerary_plans (customer_user_id,contact_inquiry_id,plan_title,travel_dates,traveler_summary,plan_status,booking_status) VALUES (:customer,:inquiry,:title,:dates,:travelers,'planning','planning')", $params); $planId = (int) last_insert_id();
        }
        foreach ($d['days'] as $index => $day) {
            $summaryText = $day['plan'] . ($day['date'] !== '' ? "\nDate: " . $day['date'] : '') . ($day['distance'] !== '' ? "\nApproximate distance: " . $day['distance'] : '') . "\n\nExperiences:\n";
            foreach ($day['activities'] as $a) { $summaryText .= $a['name'] . ' - ' . quote_status_label($a['status'][$key]) . ($a['description'] !== '' ? ': ' . $a['description'] : '') . "\n"; }
            if ($key === 'A') { foreach ($d['hotels'] as $h) { if ($h['day'] >= $day['start'] && $h['day'] <= $day['end']) { $summaryText .= "\nHotel: " . $h['name'] . ($h['alternative'] ? ' or ' . $h['alternative'] : '') . '; ' . $h['nights'] . ' nights; ' . $h['room'] . '; ' . $h['occupancy'] . '; ' . $h['meals']; } } }
            execute_query('INSERT INTO customer_itinerary_days (plan_id,day_number,day_end,title,summary,overnight_location,travel_time,image_path,sort_order) VALUES (:plan,:start,:end,:title,:summary,:overnight,:time,:image,:sort)', ['plan' => $planId, 'start' => $day['start'], 'end' => $day['end'], 'title' => $day['title'], 'summary' => $summaryText, 'overnight' => $day['overnight'], 'time' => $day['travel_time'], 'image' => $day['image'] ?: null, 'sort' => $index]);
        }
        $hash = quote_plan_hash($planId);
        execute_query('INSERT INTO quotation_bookings (quotation_id,revision_id,option_key,plan_id,services_sha256) VALUES (:qid,:rid,:opt,:plan,:hash) ON DUPLICATE KEY UPDATE revision_id=VALUES(revision_id),option_key=VALUES(option_key),services_sha256=VALUES(services_sha256)', ['qid' => $row['quotation_id'], 'rid' => $id, 'opt' => $key, 'plan' => $planId, 'hash' => $hash]);
        execute_query('UPDATE quotations SET inquiry_id=:inquiry WHERE id=:id', ['inquiry' => $inquiry, 'id' => $row['quotation_id']]);
        execute_query('INSERT INTO contact_inquiry_activity (inquiry_id,action_type,note,admin_user_id) VALUES (:inquiry,:action,:note,:admin)', ['inquiry' => $inquiry, 'action' => 'quotation_converted', 'note' => $row['reference'] . ' R' . $row['revision'] . ' Option ' . $key . ' ' . $d['currency'] . ' ' . quote_money(quote_option_total($d, $key)), 'admin' => $admin]);
        quote_event($id, $admin, 'booking_converted', ['plan_id' => $planId, 'option' => $key, 'replaced' => (bool) $plan]); return $planId;
    });
}
function quote_guest_accept(int $planId, array $input, int $admin): int
{
    $name = quote_text($input['customer_name'] ?? '', 190); $reference = quote_text($input['evidence_reference'] ?? '', 190); $channel = $input['channel'] ?? '';
    $at = quote_text($input['accepted_at'] ?? '', 16);
    if ($name === '' || $reference === '' || !in_array($channel, ['whatsapp', 'email'], true) || !quote_date($at, true) || str_replace('T', ' ', $at) > quote_now() || ($input['all_policies'] ?? '') !== '1') { throw new InvalidArgumentException('Record customer identity, channel, evidence reference, past acceptance time and agreement to all four policies.'); }
    return ict_confirmation_atomic(static function () use ($planId, $input, $admin, $name, $reference, $channel, $at): int {
        $plan = fetch_one('SELECT * FROM customer_itinerary_plans WHERE id=:id FOR UPDATE', ['id' => $planId]);
        if (!$plan || $plan['customer_user_id'] !== null || !in_array($plan['booking_status'], ['planning', 'awaiting_acceptance', 'accepted'], true)) { throw new DomainException('This plan is not an unconfirmed guest booking.'); }
        $binding = quote_assert_binding($planId); if (!$binding) { throw new DomainException('Guest acceptance requires a quotation booking.'); }
        $documents = ict_policy_published_documents(true);
        if (count($documents) !== count(ict_policy_required_keys())) { throw new RuntimeException('All required policies must be available before acceptance.'); }
        $snapshot = ict_policy_acceptance_snapshot($documents);
        if (!is_string($input['policy_snapshot_hash'] ?? null) || !hash_equals($snapshot['hash'], $input['policy_snapshot_hash']) || (int) ($input['revision_id'] ?? 0) !== (int) $binding['revision_id'] || ($input['option_key'] ?? '') !== $binding['option_key']) { throw new DomainException('Quote or policies changed. Reload and verify fresh evidence.'); }
        $quote = quote_get((int) $binding['revision_id']);
        if ($quote['document']['valid_until'] !== '' && substr($at, 0, 10) > $quote['document']['valid_until']) { throw new InvalidArgumentException('Acceptance occurred after the quotation validity date. Issue a reviewed quotation.'); }
        if (str_replace('T', ' ', $at) < substr($quote['issued_at'], 0, 16)) { throw new InvalidArgumentException('Acceptance must follow issue of this quotation revision.'); }
        execute_query('INSERT INTO quotation_guest_acceptances (plan_id,revision_id,option_key,customer_name,channel,evidence_reference,accepted_at,policy_snapshot_json,policy_snapshot_hash,services_sha256,admin_id) VALUES (:plan,:revision,:option,:name,:channel,:evidence,:at,:json,:hash,:services,:admin)', ['plan' => $planId, 'revision' => $binding['revision_id'], 'option' => $binding['option_key'], 'name' => $name, 'channel' => $channel, 'evidence' => $reference, 'at' => str_replace('T', ' ', $at) . ':00', 'json' => $snapshot['json'], 'hash' => $snapshot['hash'], 'services' => $binding['services_sha256'], 'admin' => $admin]);
        $id = (int) last_insert_id();
        execute_query("UPDATE customer_itinerary_plans SET booking_status='accepted',policy_snapshot_json=:json,policy_snapshot_hash=:hash,policy_accepted_at=:at,booking_terms_accepted_at=:terms_at,cancellation_policy_accepted_at=:cancel_at,booking_terms_version=:terms,cancellation_policy_version=:cancel,payment_policy_version=:payment,privacy_policy_version=:privacy,last_admin_update_at=NOW() WHERE id=:id", ['json' => $snapshot['json'], 'hash' => $snapshot['hash'], 'at' => str_replace('T', ' ', $at) . ':00', 'terms_at' => str_replace('T', ' ', $at) . ':00', 'cancel_at' => str_replace('T', ' ', $at) . ':00', 'terms' => $documents['booking_terms']['version'], 'cancel' => $documents['cancellation_refund']['version'], 'payment' => $documents['payment_deposit']['version'], 'privacy' => $documents['privacy']['version'], 'id' => $planId]);
        quote_event((int) $binding['revision_id'], $admin, 'guest_acceptance_recorded', ['acceptance_id' => $id, 'channel' => $channel]); return $id;
    });
}
