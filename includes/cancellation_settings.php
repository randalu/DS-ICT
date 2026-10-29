<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

const ICT_CANCELLATION_DRAFT_KEY = 'cancellation_policy_draft_json';

function ict_cancellation_defaults(): array
{
    return [
        'bands' => [
            ['min_days' => 60, 'charge_percent' => 0],
            ['min_days' => 30, 'charge_percent' => 25],
            ['min_days' => 14, 'charge_percent' => 50],
            ['min_days' => 8, 'charge_percent' => 75],
            ['min_days' => 0, 'charge_percent' => 100],
        ],
        'deposit_rule' => 'included',
        'supplier_rule' => 'higher_documented_cost',
        'refund_business_days' => 14,
        'non_refundable_items' => 'List each non-refundable supplier item and its amount or calculation basis in the written quote before policy acceptance and payment. Examples may include issued tickets, permits or special-rate accommodation, only where the actual supplier terms support them. Retain only documented costs actually committed and not recoverable, net of supplier refunds, credits available for this booking or other recovery. Provide an itemised calculation and supporting supplier terms; do not include estimated future costs or count an item twice.',
        'before_confirmation_text' => 'Payment alone does not confirm a booking. Check supplier availability before operator-issued written confirmation, but make no supplier commitments before issuing that confirmation. If supplier checks fail, the operator cannot confirm the trip, or the traveller withdraws before written confirmation, return the payments received; do not apply the confirmed-trip cancellation schedule or deduct supplier commitment costs. Give the traveller a written acknowledgement and refund calculation. Any bank or currency-conversion fee treatment must already have been disclosed in the written quote. Applicable rights still apply.',
        'notice_text' => 'Send cancellation notice in writing to hello@islandculturetravels.com or WhatsApp +94 76 919 2427. Count calendar days from the date the notice is received in Sri Lanka time to the confirmed trip start date; the start date itself is excluded. Notice received on the start date counts as 0 days. Acknowledge receipt and state the date and band used in the written calculation.',
        'changes_text' => 'Request date, route, accommodation or traveller changes in writing. Availability, revised prices and any supplier charges must be explained and accepted before an amendment is confirmed. The written amendment must identify the trip start date used for any later cancellation calculation and the treatment of existing supplier commitments. A request alone does not change the confirmed start date. Partial cancellations require an itemised calculation for the affected services, rather than automatically applying a whole-trip charge.',
        'no_show_text' => 'For a no-show at the start of the entire trip, use the final cancellation-charge band. After a trip has started, early departure or voluntarily unused services are assessed against the affected unused portion and its disclosed supplier terms; do not apply another whole-trip charge or count delivered services twice. Any proposed retention for unused services must be itemised. Services the operator fails to provide are assessed under operator cancellation, separately from voluntary non-use and subject to applicable rights.',
        'refund_text' => 'Provide a written refund assessment and calculation within 5 business days of receiving the cancellation request. Count business days from the next business day after receipt. If a supplier amount remains unresolved, identify that amount, provide the undisputed calculation and keep the traveller updated. This assessment deadline is separate from the refund-processing target: the processing clock starts on the next business day after written confirmation of the refund amount and receipt of the payment details needed to return it. Business days mean Monday to Friday, excluding Sri Lankan public holidays. Give the traveller the expected processing date. Supplier reconciliation must not delay an agreed, undisputed refund portion. Use the original payment channel where feasible, or agree a documented alternative with the traveller. Each quote states the conversion-rate basis and refund currency before acceptance and payment; fee allocation also follows the written quote. Bank processing time may be additional. Applicable mandatory deadlines still apply.',
        'operator_text' => 'If Island Culture Travels cancels the entire trip for operational reasons before it starts, offer an alternative only with the traveller\'s agreement or refund all payments received for that trip. During a trip, account separately for services delivered and refund payments for services not provided. Do not apply traveller-cancellation bands or their supplier-cost exception to an operator cancellation. Disruption and force majeure require separate review of the agreed terms, recoverable costs and applicable rights; do not automatically classify them as traveller cancellation.',
        'operator_contact_text' => "Imran Mihiran, trading as Island Culture Travels\n163/3 Niwasa Udyana Mawatha, Raddolugama 11390 (Katunayake)\nBooking confirmations, refunds, complaints and trip emergencies: Imran (primary); Antony (backup).\nEmail: hello@islandculturetravels.com\nWhatsApp: +94 76 919 2427\nCalls: +94 77 949 2427",
    ];
}

/** Validate the real form/save contract, including every day and charge boundary. */
function ict_cancellation_validate(array $input): array
{
    $result = [];
    $bands = $input['bands'] ?? null;
    if (!is_array($bands) || count($bands) !== 5) {
        throw new InvalidArgumentException('Enter all five cancellation bands.');
    }
    $previousDays = 3661;
    $previousCharge = -1;
    foreach (array_values($bands) as $index => $band) {
        if (!is_array($band)) {
            throw new InvalidArgumentException('Invalid cancellation band.');
        }
        $days = filter_var($band['min_days'] ?? null, FILTER_VALIDATE_INT);
        $charge = filter_var($band['charge_percent'] ?? null, FILTER_VALIDATE_INT);
        if ($days === false || $days < 0 || $days > 3660 || $days >= $previousDays
            || $charge === false || $charge < 0 || $charge > 100 || $charge < $previousCharge
            || ($index === 4 && $days !== 0)) {
            throw new InvalidArgumentException('Days must decrease without duplicates, charges must increase or stay equal (0–100%), and the final band must start at 0 days.');
        }
        $result['bands'][] = ['min_days' => $days, 'charge_percent' => $charge];
        $previousDays = $days;
        $previousCharge = $charge;
    }
    foreach (['deposit_rule' => ['included', 'non_refundable_if_disclosed'], 'supplier_rule' => ['included', 'higher_documented_cost']] as $key => $allowed) {
        if (!is_string($input[$key] ?? null) || !in_array($input[$key], $allowed, true)) {
            throw new InvalidArgumentException('Choose a valid deposit and supplier-cost treatment.');
        }
        $result[$key] = $input[$key];
    }
    $days = filter_var($input['refund_business_days'] ?? null, FILTER_VALIDATE_INT);
    if ($days === false || $days < 1 || $days > 90) {
        throw new InvalidArgumentException('Refund processing target must be between 1 and 90 business days.');
    }
    $result['refund_business_days'] = $days;
    foreach (['non_refundable_items', 'before_confirmation_text', 'notice_text', 'changes_text', 'no_show_text', 'refund_text', 'operator_text', 'operator_contact_text'] as $key) {
        // Existing saved drafts retain their customised fields when this new note is absent.
        $value = $key === 'before_confirmation_text' && !array_key_exists($key, $input)
            ? ict_cancellation_defaults()[$key] : ($input[$key] ?? null);
        if (!is_string($value) || trim($value) === '' || mb_strlen($value) > 3000) {
            throw new InvalidArgumentException('Complete every policy note using no more than 3,000 characters each.');
        }
        $result[$key] = trim($value);
    }
    return $result;
}

function ict_cancellation_settings(): array
{
    try {
        // Policy reads must never invoke a runtime schema initializer inside booking transactions.
        $row = db_table_exists('site_settings')
            ? fetch_one('SELECT setting_value FROM site_settings WHERE setting_key = :key', ['key' => ICT_CANCELLATION_DRAFT_KEY])
            : null;
        $json = (string) ($row['setting_value'] ?? '');
        if ($json !== '') {
            $input = json_decode((string) $json, true, 16, JSON_THROW_ON_ERROR);
            if (is_array($input)) {
                return ict_cancellation_validate($input);
            }
        }
    } catch (Throwable) {
        // Older installs and malformed stored settings retain a safe, labelled draft.
    }
    return ict_cancellation_defaults();
}

function ict_cancellation_band_label(array $bands, int $index): string
{
    $min = (int) $bands[$index]['min_days'];
    return $index === 0 ? $min . '+ days' : $min . '–' . ((int) $bands[$index - 1]['min_days'] - 1) . ' days';
}

function ict_cancellation_draft_html(?array $settings = null): string
{
    $settings = ict_cancellation_validate($settings ?? ict_cancellation_settings());
    $html = '<p>Effective version: 14 October 2026. This policy applies with your written quote and booking confirmation. Booking-specific supplier exceptions must be disclosed and agreed before acceptance and payment. Rights that cannot lawfully be excluded remain applicable.</p>';
    $html .= '<h2>1. Written cancellation notice</h2><p>' . e($settings['notice_text']) . '</p>';
    $html .= '<h2>2. Standard traveller-cancellation schedule</h2><p>This schedule applies to cancellation of an entire trip after operator-issued written confirmation and before the trip starts. Charges below are percentages of the total confirmed tour price, not percentages of a deposit.</p>';
    $html .= $settings['supplier_rule'] === 'higher_documented_cost'
        ? '<p><strong>Supplier-cost exception: the schedule is not a guaranteed refund.</strong> Higher documented non-recoverable supplier costs can replace the scheduled charge only where the items and amounts or calculation basis were disclosed before policy acceptance and payment and agreed by the traveller. This may reduce the refund, including for notice of 60 days or more under the default bands. Costs replace the charge; they are not added to it.</p>'
        : '<p>Supplier costs are included within the scheduled charge. The schedule shows the refund for a fully paid booking before any separately agreed non-refundable-deposit treatment.</p>';
    // The public CMS sanitizer supports lists, but strips table tags and their cell separators.
    $html .= '<ul>';
    foreach ($settings['bands'] as $index => $band) {
        $html .= '<li><strong>' . e(ict_cancellation_band_label($settings['bands'], $index)) . ' before trip start:</strong> cancellation charge ' . $band['charge_percent'] . '% of the total confirmed tour price; indicative refund if fully paid ' . (100 - $band['charge_percent']) . '%.</li>';
    }
    $html .= '</ul><p>Refunds cannot exceed payments actually received. For a partly paid booking, subtract the applicable cancellation charge from payments received, with a minimum refund of zero. The applicable charge is the scheduled charge or, where the agreed exceptions below apply, the greater qualifying amount. This policy does not automatically collect an unpaid balance.</p>';
    $html .= '<h2>3. Deposits and non-refundable items</h2><p>';
    $html .= $settings['deposit_rule'] === 'included'
        ? 'A deposit is part-payment and is included in the cancellation calculation; it is not automatically an additional non-refundable charge.'
        : 'A deposit is non-refundable only where that treatment was disclosed and agreed before payment. Retain the greater of the schedule charge and the agreed non-refundable deposit, not both added together.';
    $html .= '</p><p>' . e($settings['non_refundable_items']) . '</p><p>';
    $html .= $settings['supplier_rule'] === 'included'
        ? 'Supplier costs are included in the schedule charge and are not added again.'
        : 'Use the greater of the scheduled charge and qualifying documented supplier costs, capped at the total confirmed tour price. Any agreed non-refundable deposit is part of that comparison, not another fee. Do not add the same costs again. Provide the traveller with an itemised calculation of the scheduled charge, net supplier costs, applicable charge, payments received and refund.';
    $html .= '</p><h3>Calculation examples using the current settings</h3>';
    if ($settings['supplier_rule'] === 'higher_documented_cost') {
        $scheduledCharge = 20 * $settings['bands'][1]['charge_percent']; // Illustrative USD 2,000 tour.
        $applicableCharge = max($scheduledCharge, 800);
        $html .= '<p>Example: a fully paid USD 2,000 trip cancelled ' . $settings['bands'][1]['min_days'] . ' days before its start has a scheduled charge of USD ' . $scheduledCharge . '. If qualifying, pre-agreed net supplier costs are USD 800, the applicable charge is USD ' . $applicableCharge . ' and the refund is USD ' . (2000 - $applicableCharge) . ', assuming no higher agreed non-refundable deposit. The two charges are not added together.</p>';
    }
    $partialCharge = 20 * $settings['bands'][2]['charge_percent'];
    if ($settings['deposit_rule'] === 'non_refundable_if_disclosed') {
        $partialCharge = max($partialCharge, 400);
    }
    $html .= '<p>Example: for a USD 2,000 trip with only USD 400 paid, cancellation ' . $settings['bands'][2]['min_days'] . ' days before its start gives an applicable charge of USD ' . $partialCharge . ' and a refund of USD ' . max(0, 400 - $partialCharge) . ', assuming no higher qualifying supplier costs. A percentage charge on the tour price is not the same as a percentage refund of the deposit.</p>';
    $html .= '<h2>4. Payments before written confirmation</h2><p>' . e($settings['before_confirmation_text']) . '</p>';
    $html .= '<h2>5. Changes</h2><p>' . e($settings['changes_text']) . '</p><h2>6. No-show and unused services</h2><p>' . e($settings['no_show_text']) . '</p>';
    $html .= '<h2>7. Refund processing</h2><p>Processing target: ' . $settings['refund_business_days'] . ' business days.</p><p>' . e($settings['refund_text']) . '</p>';
    return $html . '<h2>8. Operator cancellation and disruption</h2><p>' . e($settings['operator_text']) . '</p><h2>9. Operator contact</h2><p>' . nl2br(e($settings['operator_contact_text'])) . '</p>';
}

/** One atomic draft-settings/CMS update; never overwrite a published policy. */
function ict_cancellation_save(array $input): bool
{
    $settings = ict_cancellation_validate($input);
    ensure_site_settings_schema(); // Runtime DDL must finish before opening the transaction.
    $hasDraftTable = db_table_exists('static_pages') && db_column_exists('static_pages', 'is_published');
    $pdo = db();
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        execute_query('INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [
            'key' => ICT_CANCELLATION_DRAFT_KEY, 'value' => json_encode($settings, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
        $updated = false;
        if ($hasDraftTable) {
            $page = fetch_one('SELECT id, is_published FROM static_pages WHERE slug = :slug LIMIT 1 FOR UPDATE', ['slug' => 'cancellation-refund-policy']);
            if ($page && !(bool) $page['is_published']) {
                execute_query('UPDATE static_pages SET content = :content, updated_at = NOW() WHERE id = :id AND is_published = 0', [
                    'content' => ict_cancellation_draft_html($settings), 'id' => $page['id'],
                ]);
                $updated = true;
            }
        }
        if ($ownsTransaction) {
            $pdo->commit();
        }
        unset($GLOBALS['_site_settings_cache']);
        return $updated;
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        unset($GLOBALS['_site_settings_cache']);
        throw $exception;
    }
}
