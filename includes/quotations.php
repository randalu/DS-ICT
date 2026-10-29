<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/media.php';
require_once __DIR__ . '/booking_confirmation.php';

const ICT_QUOTE_STATUSES = ['included', 'optional', 'not_included'];

function quote_json(mixed $value): string { return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
function quote_now(): string { return (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d H:i:s'); }
function quote_text(mixed $value, int $max = 500): string
{
    if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || mb_strlen($value) > $max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) {
        throw new InvalidArgumentException('Invalid text or text is too long (maximum ' . $max . ' characters).');
    }
    return trim($value);
}
function quote_int(mixed $value, int $min = 0, int $max = 999): int
{
    $v = filter_var($value, FILTER_VALIDATE_INT);
    if ($v === false || $v < $min || $v > $max) { throw new InvalidArgumentException('Enter a whole number between ' . $min . ' and ' . $max . '.'); }
    return $v;
}
function quote_cents(mixed $value): int
{
    if (!is_string($value) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $value)) { throw new InvalidArgumentException('Enter a positive amount with up to two decimal places.'); }
    [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
    return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
}
function quote_money(int $cents): string { return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT); }
function quote_statuses(mixed $value): array
{
    if (!is_array($value)) { throw new InvalidArgumentException('Invalid inclusion choices.'); }
    $out = [];
    foreach (['A', 'B'] as $key) {
        if (!in_array($value[$key] ?? null, ICT_QUOTE_STATUSES, true)) { throw new InvalidArgumentException('Choose an inclusion status for each option.'); }
        $out[$key] = $value[$key];
    }
    return $out;
}
function quote_list(mixed $value, int $max): array
{
    if (!is_array($value) || !array_is_list($value) || count($value) > $max) { throw new InvalidArgumentException('Invalid list or too many items.'); }
    foreach ($value as $item) { if (!is_array($item)) { throw new InvalidArgumentException('Invalid list item.'); } }
    return $value;
}
function quote_default_inclusions(): array
{
    $labels = ['Arrival welcome with name board and flower garland', 'Mineral water throughout the tour', 'Comfortable air-conditioned vehicle',
        'Half-day Yala safari', 'Nanu Oya-Ella train ticket', 'Turtle hatchery', 'Tea factory', 'English-speaking professional chauffeur',
        'Itinerary planning', 'Complimentary onboard Wi-Fi', 'Fuel', 'Passenger insurance', 'Highway tolls and parking', 'Government taxes and VAT', 'Driver accommodation and meals'];
    return array_map(static fn($name, $index) => ['name' => $name, 'status' => ['A' => $index >= 3 && $index <= 6 ? 'not_included' : 'included', 'B' => $index >= 3 && $index <= 6 ? 'not_included' : 'included']], $labels, array_keys($labels));
}
function quote_defaults(): array
{
    $master = db_table_exists('quotation_library') ? fetch_one("SELECT payload_json FROM quotation_library WHERE kind = 'defaults' AND name = 'master'") : null;
    $wording = $master ? json_decode($master['payload_json'], true, 32, JSON_THROW_ON_ERROR) : [];
    return ['customer' => ['name' => '', 'phone' => '', 'email' => '', 'country' => '', 'adults' => 2, 'children' => 0, 'ages' => ''],
        'title' => 'Your private Sri Lanka journey', 'arrival' => '', 'departure' => '', 'vehicle' => 'Comfortable air-conditioned van', 'inquiry_id' => 0,
        'cover_image' => '', 'intro' => $wording['intro'] ?? 'Thank you for considering Island Culture Travels. Your proposed private journey is outlined below.',
        'footer' => $wording['footer'] ?? "Thank you\nIsland Culture Travels\nImran Mihiran", 'valid_until' => '', 'payment_notes' => '', 'exclusions' => '',
        'inclusions' => $wording['inclusions'] ?? quote_default_inclusions(), 'days' => [], 'hotels' => [],
        'currency' => 'USD', 'options' => [
            'A' => ['enabled' => true, 'label' => 'Transportation, activities and hotels', 'basis' => 'group', 'amount' => '', 'adult_rate' => '', 'child_rate' => '', 'deposit' => ''],
            'B' => ['enabled' => true, 'label' => 'Transportation and activities', 'basis' => 'group', 'amount' => '', 'adult_rate' => '', 'child_rate' => '', 'deposit' => '']]];
}
function quote_date(string $value, bool $time = false): bool
{
    $format = $time ? 'Y-m-d\TH:i' : 'Y-m-d';
    $date = DateTimeImmutable::createFromFormat('!' . $format, $value, new DateTimeZone('Asia/Colombo'));
    return $date !== false && $date->format($format) === $value;
}
/** Whitelisted, bounded JSON aggregate; no HTML or executable content is accepted. */
function quote_document(array $input): array
{
    $out = [];
    foreach (['title' => 255, 'arrival' => 16, 'departure' => 16, 'vehicle' => 190, 'intro' => 6000, 'footer' => 3000, 'valid_until' => 10, 'payment_notes' => 3000, 'exclusions' => 6000, 'cover_image' => 255] as $key => $limit) { $out[$key] = quote_text($input[$key] ?? '', $limit); }
    foreach (['arrival', 'departure', 'valid_until'] as $key) { if ($out[$key] !== '' && !quote_date($out[$key], $key !== 'valid_until')) { throw new InvalidArgumentException('Enter a valid ' . $key . ' date.'); } }
    if (!is_array($input['customer'] ?? null)) { throw new InvalidArgumentException('Customer details are required.'); }
    $c = $input['customer']; $out['customer'] = [];
    foreach (['name' => 150, 'phone' => 50, 'email' => 190, 'country' => 120, 'ages' => 190] as $k => $limit) { $out['customer'][$k] = quote_text($c[$k] ?? '', $limit); }
    if ($out['customer']['email'] !== '' && !filter_var($out['customer']['email'], FILTER_VALIDATE_EMAIL)) { throw new InvalidArgumentException('Enter a valid email address.'); }
    foreach (['adults', 'children'] as $k) { $out['customer'][$k] = quote_int($c[$k] ?? 0, 0, 99); }
    $out['inquiry_id'] = quote_int($input['inquiry_id'] ?? 0, 0, PHP_INT_MAX);
    $out['currency'] = quote_text($input['currency'] ?? 'USD', 3);
    if (!preg_match('/^[A-Z]{3}$/', $out['currency'])) { throw new InvalidArgumentException('Use a three-letter currency code.'); }
    foreach (['A', 'B'] as $k) {
        $o = $input['options'][$k] ?? []; if (!is_array($o)) { throw new InvalidArgumentException('Invalid price option.'); }
        $out['options'][$k] = ['enabled' => ($o['enabled'] ?? false) === true, 'label' => quote_text($o['label'] ?? '', 190), 'basis' => $o['basis'] ?? 'group'];
        if (!in_array($out['options'][$k]['basis'], ['group', 'person'], true)) { throw new InvalidArgumentException('Choose group or per-person pricing.'); }
        foreach (['amount', 'adult_rate', 'child_rate', 'deposit'] as $field) { $v = quote_text($o[$field] ?? '', 12); if ($v !== '') { quote_cents($v); } $out['options'][$k][$field] = $v; }
    }
    $out['inclusions'] = [];
    foreach (quote_list($input['inclusions'] ?? [], 60) as $item) { $out['inclusions'][] = ['name' => quote_text($item['name'] ?? '', 500), 'status' => quote_statuses($item['status'] ?? [])]; }
    $out['days'] = [];
    foreach (quote_list($input['days'] ?? [], 90) as $day) {
        $d = ['start' => quote_int($day['start'] ?? 1), 'end' => quote_int($day['end'] ?? $day['start'] ?? 1), 'destination_id' => quote_int($day['destination_id'] ?? 0, 0, PHP_INT_MAX), 'image_manual' => ($day['image_manual'] ?? false) === true];
        if ($d['end'] < $d['start']) { throw new InvalidArgumentException('A day range must end on or after its start.'); }
        foreach (['date' => 10, 'title' => 255, 'plan' => 12000, 'overnight' => 180, 'distance' => 100, 'travel_time' => 100, 'image' => 255] as $key => $limit) { $d[$key] = quote_text($day[$key] ?? '', $limit); }
        if ($d['date'] !== '' && !quote_date($d['date'])) { throw new InvalidArgumentException('Invalid day date.'); }
        $d['activities'] = [];
        foreach (quote_list($day['activities'] ?? [], 40) as $activity) { $d['activities'][] = ['name' => quote_text($activity['name'] ?? '', 190), 'description' => quote_text($activity['description'] ?? '', 3000), 'image' => quote_text($activity['image'] ?? '', 255), 'status' => quote_statuses($activity['status'] ?? [])]; }
        $out['days'][] = $d;
    }
    $out['hotels'] = [];
    foreach (quote_list($input['hotels'] ?? [], 90) as $hotel) {
        $h = ['nights' => quote_int($hotel['nights'] ?? 1, 1, 90), 'day' => quote_int($hotel['day'] ?? 1)];
        foreach (['name', 'alternative', 'location', 'star', 'room', 'occupancy', 'meals'] as $key) { $h[$key] = quote_text($hotel[$key] ?? '', 190); }
        $out['hotels'][] = $h;
    }
    return $out;
}
function quote_option_total(array $doc, string $key): int
{
    $o = $doc['options'][$key];
    return $o['basis'] === 'group' ? quote_cents($o['amount']) : quote_cents($o['adult_rate']) * $doc['customer']['adults'] + ($doc['customer']['children'] ? quote_cents($o['child_rate']) * $doc['customer']['children'] : 0);
}
function quote_validate_issue(array $doc): void
{
    if ($doc['customer']['name'] === '' || $doc['customer']['phone'] === '' || $doc['title'] === '' || $doc['vehicle'] === '' || $doc['arrival'] === '' || $doc['departure'] === '' || !$doc['days'] || $doc['customer']['adults'] < 1) { throw new InvalidArgumentException('Complete the customer name, phone, adult count, title, vehicle, arrival, departure and day plans.'); }
    if ($doc['departure'] < $doc['arrival']) { throw new InvalidArgumentException('Departure must follow arrival.'); }
    if ($doc['valid_until'] !== '' && $doc['valid_until'] < substr(quote_now(), 0, 10)) { throw new InvalidArgumentException('Validity date has already passed.'); }
    if ($doc['customer']['children'] && $doc['customer']['ages'] === '') { throw new InvalidArgumentException('Specify the children\'s ages.'); }
    $active = 0;
    foreach ($doc['options'] as $key => $o) {
        if (!$o['enabled']) { continue; } ++$active;
        if ($o['label'] === '' || quote_option_total($doc, $key) <= 0) { throw new InvalidArgumentException('Complete the active option prices and labels.'); }
        if ($o['deposit'] !== '' && quote_cents($o['deposit']) > quote_option_total($doc, $key)) { throw new InvalidArgumentException('Deposit cannot exceed the group total.'); }
    }
    if (!$active) { throw new InvalidArgumentException('Enable at least one price option.'); }
    $ranges = [];
    foreach ($doc['days'] as $d) {
        if ($d['title'] === '' || $d['plan'] === '') { throw new InvalidArgumentException('Complete the title and travel plan for every day.'); }
        foreach ($ranges as [$start, $end]) { if ($d['start'] <= $end && $d['end'] >= $start) { throw new InvalidArgumentException('Day numbers overlap. Review the day ranges.'); } }
        $ranges[] = [$d['start'], $d['end']];
        if ($d['date'] !== '' && ($d['date'] < substr($doc['arrival'], 0, 10) || $d['date'] > substr($doc['departure'], 0, 10))) { throw new InvalidArgumentException('A day date falls outside the trip dates.'); }
        foreach ($d['activities'] as $a) { if ($a['name'] === '') { throw new InvalidArgumentException('Name every activity.'); } }
    }
    if ($doc['options']['A']['enabled']) {
        if (!$doc['hotels']) { throw new InvalidArgumentException('Add hotel arrangements for the hotel-inclusive option.'); }
        foreach ($doc['hotels'] as $h) { foreach (['name', 'location', 'star', 'room', 'occupancy', 'meals'] as $k) { if ($h[$k] === '') { throw new InvalidArgumentException('Complete hotel location, category, room, occupancy and specific meals.'); } } }
    }
    foreach ($doc['inclusions'] as $i) {
        if ($i['name'] === '') { throw new InvalidArgumentException('Name every inclusion.'); }
        foreach ($doc['days'] as $d) { foreach ($d['activities'] as $a) {
            if (mb_strtolower($a['name']) === mb_strtolower($i['name']) && $a['status'] !== $i['status']) { throw new InvalidArgumentException('The activity and inclusion statuses conflict for ' . $i['name'] . '.'); }
        } }
    }
}
function quote_ready(): bool
{
    foreach (['quotations', 'quotation_revisions', 'quotation_library', 'quotation_events', 'quotation_bookings', 'quotation_guest_acceptances', 'quotation_customer_acceptances'] as $table) { if (!db_table_exists($table)) { return false; } }
    if (!db_column_exists('contact_inquiries', 'admin_guest_only')) { return false; }
    $column = fetch_one("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='customer_itinerary_plans' AND COLUMN_NAME='customer_user_id'");
    return ($column['IS_NULLABLE'] ?? '') === 'YES';
}
function quote_event(int $revisionId, int $adminId, string $action, array $details = []): void
{
    execute_query('INSERT INTO quotation_events (revision_id,admin_id,action,details_json) VALUES (:revision,:admin,:action,:details)', ['revision' => $revisionId, 'admin' => $adminId, 'action' => $action, 'details' => quote_json($details)]);
}
function quote_get(int $revisionId, bool $lock = false): array
{
    $row = fetch_one('SELECT r.*, q.reference, q.inquiry_id FROM quotation_revisions r JOIN quotations q ON q.id=r.quotation_id WHERE r.id=:id' . ($lock ? ' FOR UPDATE' : ''), ['id' => $revisionId]);
    if (!$row) { throw new InvalidArgumentException('Quotation revision not found.'); }
    $row['document'] = json_decode($row['document_json'], true, 64, JSON_THROW_ON_ERROR);
    return $row;
}
function quote_create(int $adminId, ?array $document = null): int
{
    return ict_confirmation_atomic(static function () use ($adminId, $document): int {
        execute_query('INSERT INTO quotations (created_by) VALUES (:admin)', ['admin' => $adminId]); $qid = (int) last_insert_id();
        $ref = 'ICT-Q-' . substr(quote_now(), 0, 4) . '-' . str_pad((string) $qid, 6, '0', STR_PAD_LEFT);
        execute_query('UPDATE quotations SET reference=:ref WHERE id=:id', ['ref' => $ref, 'id' => $qid]);
        execute_query('INSERT INTO quotation_revisions (quotation_id,revision,document_json) VALUES (:id,1,:doc)', ['id' => $qid, 'doc' => quote_json($document ?? quote_defaults())]);
        $id = (int) last_insert_id(); quote_event($id, $adminId, 'created'); return $id;
    });
}
function quote_create_from_inquiry(int $adminId, int $inquiryId): int
{
    if (!$inquiryId) { return quote_create($adminId); }
    $lead = fetch_one('SELECT * FROM contact_inquiries WHERE id=:id', ['id' => $inquiryId]);
    if (!$lead) { throw new InvalidArgumentException('Inquiry not found.'); }
    $doc = quote_defaults(); $doc['inquiry_id'] = $inquiryId;
    foreach (['name','email','phone','country'] as $key) { $doc['customer'][$key] = (string) ($lead[$key] ?? ''); }
    return quote_create($adminId, quote_document($doc));
}
function quote_save(int $id, int $version, int $step, array $input, int $adminId): array
{
    $doc = quote_document($input);
    return ict_confirmation_atomic(static function () use ($id, $version, $step, $doc, $adminId): array {
        $row = quote_get($id, true);
        if ($row['state'] !== 'draft' || (int) $row['lock_version'] !== $version) { throw new DomainException('This draft changed in another tab or was issued. Your entries are retained; reload before overwriting.'); }
        if ($doc['inquiry_id'] && !fetch_one('SELECT id FROM contact_inquiries WHERE id=:id', ['id' => $doc['inquiry_id']])) { throw new InvalidArgumentException('Linked inquiry not found.'); }
        execute_query('UPDATE quotation_revisions SET document_json=:doc,step=:step,lock_version=lock_version+1 WHERE id=:id', ['doc' => quote_json($doc), 'step' => quote_int($step, 1, 5), 'id' => $id]);
        execute_query('UPDATE quotations SET inquiry_id=:inquiry WHERE id=:id', ['inquiry' => $doc['inquiry_id'] ?: null, 'id' => $row['quotation_id']]);
        quote_event($id, $adminId, 'draft_saved', ['version' => $version + 1]); return ['id' => $id, 'version' => $version + 1];
    });
}
function quote_revise(int $id, int $adminId): int
{
    return ict_confirmation_atomic(static function () use ($id, $adminId): int {
        $row = quote_get($id, true);
        fetch_one('SELECT id FROM quotations WHERE id=:id FOR UPDATE', ['id' => $row['quotation_id']]);
        $latest = fetch_one('SELECT id,revision,state FROM quotation_revisions WHERE quotation_id=:qid ORDER BY revision DESC LIMIT 1 FOR UPDATE', ['qid' => $row['quotation_id']]);
        if ($latest['state'] === 'draft') { return (int) $latest['id']; }
        execute_query('INSERT INTO quotation_revisions (quotation_id,revision,document_json) VALUES (:qid,:rev,:doc)', ['qid' => $row['quotation_id'], 'rev' => (int) $latest['revision'] + 1, 'doc' => $row['document_json']]);
        $next = (int) last_insert_id(); quote_event($next, $adminId, 'revision_created', ['source' => $id]); return $next;
    });
}
function quote_library_save(string $kind, string $name, array $payload, int $version): int
{
    if (!in_array($kind, ['activity', 'day', 'hotel', 'defaults'], true)) { throw new InvalidArgumentException('Invalid reusable content type.'); }
    $name = quote_text($name, 190); if ($name === '') { throw new InvalidArgumentException('Enter a reusable name.'); }
    $base = quote_defaults();
    if ($kind === 'activity') { $base['days'] = [['activities' => [$payload]]]; $clean = quote_document($base)['days'][0]['activities'][0]; }
    elseif ($kind === 'day') { $base['days'] = [$payload]; $clean = quote_document($base)['days'][0]; }
    elseif ($kind === 'hotel') { $base['hotels'] = [$payload]; $clean = quote_document($base)['hotels'][0]; }
    else { $name = 'master'; $base = array_replace($base, $payload); $d = quote_document($base); $clean = array_intersect_key($d, array_flip(['intro', 'footer', 'inclusions'])); }
    return ict_confirmation_atomic(static function () use ($kind, $name, $clean, $version): int {
        $row = fetch_one('SELECT id,lock_version FROM quotation_library WHERE kind=:kind AND name=:name FOR UPDATE', ['kind' => $kind, 'name' => $name]);
        if ($row) {
            if ((int) $row['lock_version'] !== $version) { throw new DomainException('Reusable content already exists or changed. Select its latest version before replacing it.'); }
            execute_query('UPDATE quotation_library SET payload_json=:doc,lock_version=lock_version+1 WHERE id=:id', ['doc' => quote_json($clean), 'id' => $row['id']]); return (int) $row['id'];
        }
        if ($version !== 0) { throw new DomainException('Reusable content changed. Reload.'); }
        execute_query('INSERT INTO quotation_library (kind,name,payload_json) VALUES (:kind,:name,:doc)', ['kind' => $kind, 'name' => $name, 'doc' => quote_json($clean)]); return (int) last_insert_id();
    });
}
