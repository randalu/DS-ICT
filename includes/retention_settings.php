<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
const ICT_RETENTION_KEY = 'data_retention_draft_json';
const ICT_RECORDS_TEMPLATE = __DIR__ . '/../references/templates/Island-Culture-Travels-Records-Template.xlsx';
final class IctRetentionStorageUnavailable extends RuntimeException {}

function ict_retention_categories(): array
{
    return [
        'enquiries' => ['label' => 'Unsuccessful enquiries', 'period' => 6, 'unit' => 'months', 'trigger' => 'Enquiry closed or last substantive reply, whichever is later', 'basis' => 'Short enquiry follow-up window; DPA six-month enquiry example', 'source' => 'https://dpa.gov.lk/notice.php'],
        'bookings' => ['label' => 'Core booking records', 'period' => 60, 'unit' => 'months', 'trigger' => 'Later of trip end, booking closure and final settlement', 'basis' => 'Core contract/accounting evidence; five-year starting point, extend for obligations', 'source' => 'https://www.ird.gov.lk/en/publications/Acts_Income%20Tax_2017/IR_Act_No_24_2017_E.pdf'],
        'payments' => ['label' => 'Payments and refunds', 'period' => 60, 'unit' => 'months', 'trigger' => 'Transaction date or final settlement, whichever is later', 'basis' => 'Tax/source records; section 120(6), with longer assessment/proceeding holds', 'source' => 'https://www.ird.gov.lk/en/publications/Acts_Income%20Tax_2017/IR_Act_No_24_2017_E.pdf'],
        'acceptances' => ['label' => 'Policy acceptance evidence', 'period' => 60, 'unit' => 'months', 'trigger' => 'Booking closure or final settlement, whichever is later', 'basis' => 'Proposed alignment with core booking evidence; not a statutory universal period', 'source' => 'Business review default'],
        'trip_details' => ['label' => 'Extra trip logistics', 'period' => 3, 'unit' => 'months', 'trigger' => 'Trip end, once issues are closed', 'basis' => 'Proposed minimisation of unnecessary operational copies; preserve core evidence', 'source' => 'Business review default'],
        'privacy_requests' => ['label' => 'Privacy request records', 'period' => 24, 'unit' => 'months', 'trigger' => 'Request resolved and closed', 'basis' => 'Proposed request-handling evidence window; extend for unresolved issues', 'source' => 'Business review default'],
        'inactive_accounts' => ['label' => 'Inactive account profiles', 'period' => 24, 'unit' => 'months', 'trigger' => 'Last meaningful account activity; no open booking or dispute', 'basis' => 'Proposed profile review; linked financial/acceptance records follow their own rules', 'source' => 'Business review default'],
        'security_logs' => ['label' => 'Security and IP logs', 'period' => 90, 'unit' => 'days', 'trigger' => 'Log creation date', 'basis' => 'Short technical-log review window; DPA three-month IP example', 'source' => 'https://dpa.gov.lk/notice.php'],
        'analytics' => ['label' => 'First-party analytics', 'period' => 365, 'unit' => 'days', 'trigger' => 'Event date', 'basis' => 'Owner-approved review period; verify the runtime match. Disposal remains paused.', 'source' => 'Existing Admin Analytics Settings'],
        'backups' => ['label' => 'Rolling backup copies', 'period' => 90, 'unit' => 'days', 'trigger' => 'Snapshot creation date', 'basis' => 'Proposed rolling-copy review; approved archives and recovery evidence need separate rules', 'source' => 'Business review default'],
    ];
}

function ict_retention_defaults(): array
{
    $rules = [];
    foreach (ict_retention_categories() as $key => $category) {
        $rules[$key] = array_intersect_key($category, array_flip(['period', 'unit', 'trigger']));
    }
    return ['rules' => $rules,
        'hold_note' => 'Internal procedure: Imran reviews retention monthly, with Antony as backup, and records each keep or disposal decision. Suspend disposal for an open booking, dispute, refund, insurance claim, tax assessment, legal requirement or other documented continuing obligation. Record the reason, owner and next review; release the hold only after review. Actual operation and applicable obligations require verification before publication.',
        'copies_note' => 'Email and spreadsheet copies follow the same record category and start date as the original. Where categories overlap, use the later justified review date. Remove unnecessary extra copies; do not shorten financial or acceptance evidence by classifying it as ordinary correspondence.',
        'storage_note' => 'Keep Excel working copies outside public web paths and Git, with access limited to authorised record handlers. The owner will arrange sharing separately; the sharing method, effective access and backup/recovery arrangements require verification before real records are used. Record reference IDs and copy locations instead of duplicating passport, health, bank or other unnecessary personal details. Imran is primary; Antony is backup.',
    ];
}

function ict_retention_validate(array $input): array
{
    $keys = ['rules', 'hold_note', 'copies_note', 'storage_note'];
    if (count($input) !== count($keys) || array_diff(array_keys($input), $keys)) {
        throw new InvalidArgumentException('Use the listed retention fields only.');
    }
    $rules = $input['rules'] ?? null;
    if (!is_array($rules) || array_diff(array_keys($rules), array_keys(ict_retention_categories()))) {
        throw new InvalidArgumentException('Use the listed record categories.');
    }
    $validated = ['rules' => []];
    foreach (ict_retention_categories() as $key => $category) {
        $row = $rules[$key] ?? null;
        if (!is_array($row) || count($row) !== 3 || array_diff(array_keys($row), ['period', 'unit', 'trigger'])) { throw new InvalidArgumentException('Complete every retention category using the listed fields.'); }
        if (!is_int($row['period'] ?? null) && !is_string($row['period'] ?? null)) { throw new InvalidArgumentException('Use a whole-number period.'); }
        $period = filter_var($row['period'] ?? null, FILTER_VALIDATE_INT);
        $unit = $row['unit'] ?? null;
        if ($period === false || $period < 1 || !in_array($unit, ['days', 'months'], true)
            || $period > ($unit === 'months' ? 1200 : 36500)) {
            throw new InvalidArgumentException('Enter a positive whole period (up to 1,200 months or 36,500 days).');
        }
        $trigger = $row['trigger'] ?? null;
        if (!is_string($trigger) || !mb_check_encoding($trigger, 'UTF-8') || preg_match('/[\x00-\x1F\x7F]/', $trigger) || trim($trigger) === '' || mb_strlen($trigger) > 500) {
            throw new InvalidArgumentException('Enter a start-date rule of up to 500 characters for every category.');
        }
        $validated['rules'][$key] = ['period' => $period, 'unit' => $unit, 'trigger' => trim($trigger)];
    }
    foreach (['hold_note', 'copies_note', 'storage_note'] as $key) {
        $value = $input[$key] ?? null;
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) || trim($value) === '' || mb_strlen($value) > 3000) {
            throw new InvalidArgumentException('Complete the hold, copy and storage notes (up to 3,000 characters each).');
        }
        $validated[$key] = trim($value);
    }
    return $validated;
}

/** Stored documents use integer periods; form strings are normalized only on save. */
function ict_retention_decode(string $json): array
{
    $input = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($input)) { throw new InvalidArgumentException('Invalid saved retention document.'); }
    $settings = ict_retention_validate($input);
    foreach ($input['rules'] as $rule) {
        if (!is_int($rule['period'])) { throw new InvalidArgumentException('Invalid saved retention period.'); }
    }
    return $settings;
}

/** Read-only state. Defaults are an unsaved preview, never evidence of a saved rule. */
function ict_retention_state(): array
{
    $state = ['settings' => ict_retention_defaults(), 'configured' => false, 'invalid' => false, 'available' => false, 'token' => 'missing'];
    try {
        if (!db_table_exists('site_settings')) { return $state; }
        $state['available'] = true;
        $row = fetch_one('SELECT setting_value FROM site_settings WHERE setting_key = :key', ['key' => ICT_RETENTION_KEY]);
    } catch (Throwable) { $state['available'] = false; return $state; }
    if ($row) {
        $state['token'] = hash('sha256', (string) $row['setting_value']);
        try {
            $state['settings'] = ict_retention_decode((string) $row['setting_value']);
            $state['configured'] = true;
        } catch (Throwable) { $state['invalid'] = true; }
    }
    return $state;
}

function ict_retention_settings(): array
{
    return ict_retention_state()['settings'];
}

function ict_retention_draft_html(?array $settings = null): string
{
    $settings = ict_retention_validate($settings ?? ict_retention_settings());
    $html = '<!-- ICT_RETENTION_START --><p><strong>Working draft for review.</strong> Proposed retention review periods are editable in Admin Settings. They are researched starting values, not verified industry-wide practice or automatic deletion instructions. Applicable obligations and actual storage/deletion procedures require final review.</p><ul>';
    foreach (ict_retention_categories() as $key => $category) {
        $rule = $settings['rules'][$key];
        $html .= '<li><strong>' . e($category['label']) . ': ' . $rule['period'] . ' ' . e($rule['unit']) . '.</strong> Start counting from: ' . e($rule['trigger']) . '.</li>';
    }
    $notes = [];
    foreach (['hold_note', 'copies_note', 'storage_note'] as $key) { $notes[] = '<p>' . e($settings[$key]) . '</p>'; }
    return $html . '</ul>' . implode('', $notes) . '<p>Review dates flag records for a human decision; this feature does not delete website, email, spreadsheet or backup records. Analytics disposal remains paused; matching the runtime period does not authorize disposal. Verify that effective retention matches approved policy before publication.</p><!-- ICT_RETENTION_END -->';
}

/** Customer-facing approved schedule, separate from internal operating-review notes. */
function ict_retention_policy_html(?array $settings = null): string
{
    $settings = ict_retention_validate($settings ?? ict_retention_settings());
    $html = '<!-- ICT_RETENTION_START --><p>We review records using the category and start-date rules below. These are review points for deciding whether records remain necessary, rather than automatic deletion dates.</p><ul>';
    foreach (ict_retention_categories() as $key => $category) {
        $rule = $settings['rules'][$key];
        $html .= '<li><strong>' . e($category['label']) . ': ' . $rule['period'] . ' ' . e($rule['unit']) . '.</strong> Start counting from: ' . e($rule['trigger']) . '.</li>';
    }
    return $html . '</ul><p>Imran reviews retention monthly, with Antony as backup. We may retain records longer for an open booking, refund, dispute, claim, tax assessment or another continuing legal or business obligation. We record the reason and next review for a hold. Email and spreadsheet copies follow the relevant record category; overlapping categories use the later justified review date.</p><p>Analytics disposal is paused while operating and recovery checks are completed. A review date or matching analytics period does not release a hold or authorise deletion. Once a record is no longer needed and no hold applies, its disposal must cover relevant working copies as well as the website. Backup copies follow their own review cycle.</p><!-- ICT_RETENTION_END -->';
}

/** Settings-only write: no DDL, policy synchronization, hold change or disposal. */
function ict_retention_save(array $input, string $expectedToken): void
{
    $settings = ict_retention_validate($input);
    if ($expectedToken !== 'missing' && !preg_match('/\A[a-f0-9]{64}\z/', $expectedToken)) { throw new InvalidArgumentException('Reload the retention settings before saving.'); }
    if (!db_table_exists('site_settings')) { throw new IctRetentionStorageUnavailable('Existing settings table unavailable; no schema changes were made.'); }
    $engine = fetch_one('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :name', ['name' => 'site_settings']);
    if (strtoupper((string) ($engine['ENGINE'] ?? '')) !== 'INNODB') { throw new IctRetentionStorageUnavailable('Transactional settings storage is required; no schema changes were made.'); }
    $pdo = db();
    $owns = !$pdo->inTransaction();
    if ($owns) { $pdo->beginTransaction(); } else { $pdo->exec('SAVEPOINT ict_retention_save'); }
    try {
        $row = fetch_one('SELECT setting_value FROM site_settings WHERE setting_key = :key FOR UPDATE', ['key' => ICT_RETENTION_KEY]);
        $token = $row ? hash('sha256', (string) $row['setting_value']) : 'missing';
        if ($row) {
            try { ict_retention_decode((string) $row['setting_value']); }
            catch (Throwable) { throw new InvalidArgumentException('Stored retention settings are invalid. Review and repair them separately before replacing them.'); }
        }
        if (!hash_equals($token, $expectedToken)) { throw new InvalidArgumentException('Retention settings changed. Reload and review before saving.'); }
        $parameters = ['key' => ICT_RETENTION_KEY, 'value' => json_encode($settings, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)];
        if ($row) {
            execute_query('UPDATE site_settings SET setting_value = :value WHERE setting_key = :key', $parameters);
        } else {
            // A unique-key collision must never turn a concurrent first save into an overwrite.
            try { execute_query('INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)', $parameters); }
            catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) === 1062) { throw new InvalidArgumentException('Retention settings changed. Reload and review before saving.', 0, $exception); }
                throw $exception;
            }
        }
        if ($owns) { $pdo->commit(); } else { $pdo->exec('RELEASE SAVEPOINT ict_retention_save'); }
        unset($GLOBALS['_site_settings_cache']);
    } catch (Throwable $exception) {
        if ($owns && $pdo->inTransaction()) { $pdo->rollBack(); }
        elseif (!$owns && $pdo->inTransaction()) { $pdo->exec('ROLLBACK TO SAVEPOINT ict_retention_save'); $pdo->exec('RELEASE SAVEPOINT ict_retention_save'); }
        throw $exception;
    }
}
