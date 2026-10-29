<?php
declare(strict_types=1);
if (!isset($confirmationPlanId, $confirmationAcceptance) || !is_array($confirmationAcceptance)) { http_response_code(404); exit; }
if ((int) ($confirmationAcceptance['id'] ?? 0) <= 0) {
    echo '<p class="small text-warning">Confirmation is unavailable without an archived acceptance record. Review the acceptance history before proceeding.</p>';
    return;
}
$confirmationOld = (int) old_input('plan_id', 0) === $confirmationPlanId ? old_input('confirmation_evidence', []) : [];
if (!is_array($confirmationOld)) { $confirmationOld = []; }
if ($confirmationOld && ((int) ($confirmationOld['acceptance_record_id'] ?? 0) !== (int) ($confirmationAcceptance['id'] ?? 0)
    || !is_string($confirmationOld['accepted_snapshot_hash'] ?? null)
    || !hash_equals((string) ($confirmationAcceptance['policy_snapshot_hash'] ?? ''), $confirmationOld['accepted_snapshot_hash']))) {
    $confirmationOld = []; // A newer acceptance needs fresh references and operator checks.
}
require_once __DIR__ . '/../includes/quotation_booking.php';
$quoteBinding = quote_booking_binding($confirmationPlanId);
$quoteDefault = '';
if ($quoteBinding) { $boundQuote = quote_get((int) $quoteBinding['revision_id']); $quoteDefault = $boundQuote['reference'] . ' R' . $boundQuote['revision'] . ' Option ' . $quoteBinding['option_key']; }
$confirmationValue = static fn(string $key, string $fallback = ''): string => is_string($confirmationOld[$key] ?? null) ? $confirmationOld[$key] : ($key === 'quote_reference' ? $quoteDefault : $fallback);
?>
<form method="post" action="<?= e(base_url('admin/customer_plan_booking_status_save.php')) ?>">
<?= csrf_field() ?>
<input type="hidden" name="plan_id" value="<?= $confirmationPlanId ?>">
<input type="hidden" name="confirmation_evidence[acceptance_record_id]" value="<?= (int) ($confirmationAcceptance['id'] ?? 0) ?>">
<input type="hidden" name="confirmation_evidence[accepted_snapshot_hash]" value="<?= e((string) ($confirmationAcceptance['policy_snapshot_hash'] ?? '')) ?>">
<p class="small text-secondary">Check the accepted quote, payment requirement, supplier availability and final services. Use reference IDs only; keep bank, passport and other sensitive records in private storage.</p>
<div class="row g-2 mb-2">
<div class="col-md-6"><label class="form-label small" for="quote_ref_<?= $confirmationPlanId ?>">Quote / final-service reference *</label><input class="form-control form-control-sm" id="quote_ref_<?= $confirmationPlanId ?>" name="confirmation_evidence[quote_reference]" maxlength="190" value="<?= e($confirmationValue('quote_reference')) ?>" required></div>
<div class="col-md-6"><label class="form-label small" for="payment_state_<?= $confirmationPlanId ?>">Payment requirement *</label><select class="form-select form-select-sm" id="payment_state_<?= $confirmationPlanId ?>" name="confirmation_evidence[payment_status]" required>
<?php foreach (['funds_received' => 'Required funds received', 'no_payment_due' => 'Quote explicitly states no payment due now'] as $value => $label): ?><option value="<?= $value ?>" <?= $confirmationValue('payment_status', 'funds_received') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
</select></div>
<div class="col-md-6"><label class="form-label small" for="payment_ref_<?= $confirmationPlanId ?>">Payment evidence reference *</label><input class="form-control form-control-sm" id="payment_ref_<?= $confirmationPlanId ?>" name="confirmation_evidence[payment_reference]" maxlength="190" value="<?= e($confirmationValue('payment_reference')) ?>" required><div class="form-text">Receipt / funds-verification reference, or the quote section explicitly stating no payment is due at confirmation.</div></div>
<div class="col-md-6"><label class="form-label small" for="supplier_ref_<?= $confirmationPlanId ?>">Supplier-check reference *</label><input class="form-control form-control-sm" id="supplier_ref_<?= $confirmationPlanId ?>" name="confirmation_evidence[supplier_reference]" maxlength="190" value="<?= e($confirmationValue('supplier_reference')) ?>" required><div class="form-text">Availability and service-check reference. Make no supplier commitment before written confirmation.</div></div>
</div>
<?php foreach (['payment_verified' => 'I verified received funds, or the referenced quote explicitly says no payment is due at confirmation.', 'supplier_verified' => 'I checked current supplier availability without making a pre-confirmation commitment.', 'final_services_reviewed' => 'I reviewed the final services against the referenced quote and accepted plan.'] as $key => $label): ?>
<label class="form-check small mb-2"><input class="form-check-input" type="checkbox" name="confirmation_evidence[<?= $key ?>]" value="1" <?= in_array($confirmationOld[$key] ?? null, ['1', 1], true) ? 'checked' : '' ?> required><span class="form-check-label"><?= e($label) ?></span></label>
<?php endforeach; ?>
<p class="small text-secondary">These are recorded operator checks. The website does not verify funds or supplier records automatically.</p>
<button class="btn btn-sm btn-success" name="action" value="confirm_booking">Issue Written Booking Confirmation</button>
</form>
