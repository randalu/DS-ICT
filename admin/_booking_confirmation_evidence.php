<?php
declare(strict_types=1);
if (!isset($confirmationDetails) || !is_array($confirmationDetails) || ($confirmationDetails['schema_version'] ?? null) !== 1) {
    echo '<p class="text-warning">Confirmation evidence could not be read. Review the activity record.</p>';
    return;
}
$displayEvidence = static fn(string $key): string => is_scalar($confirmationDetails[$key] ?? null) ? (string) $confirmationDetails[$key] : '';
?>
<dl class="row small mb-1 mt-2">
<?php foreach (['quote_reference' => 'Quote / services', 'payment_reference' => 'Payment evidence', 'supplier_reference' => 'Supplier check'] as $key => $label): ?>
<dt class="col-sm-4"><?= e($label) ?></dt><dd class="col-sm-8 text-break"><?= e($displayEvidence($key)) ?></dd>
<?php endforeach; ?>
<dt class="col-sm-4">Payment basis</dt><dd class="col-sm-8"><?= match ($confirmationDetails['payment_status'] ?? '') { 'no_payment_due' => 'Quote explicitly states no payment due at confirmation', 'funds_received' => 'Required funds received', default => 'Unknown payment basis; review the record' } ?></dd>
</dl>
<details class="small"><summary>Accepted-plan evidence binding</summary><p class="mb-1">Plan <?= (int) ($confirmationDetails['plan_id'] ?? 0) ?>; acceptance <?= (int) ($confirmationDetails['acceptance_record_id'] ?? 0) ?>; operator <?= (int) ($confirmationDetails['operator_admin_id'] ?? 0) ?>; recorded <?= e($displayEvidence('recorded_at')) ?>.</p><p class="text-break mb-1">Policy snapshot: <?= e($displayEvidence('accepted_snapshot_hash')) ?></p></details>
