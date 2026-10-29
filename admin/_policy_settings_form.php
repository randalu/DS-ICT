<?php
declare(strict_types=1);
if (!isset($policySettings) || !is_array($policySettings)) {
    http_response_code(404);
    exit;
}
?>
<section class="policy-settings">
<div class="alert alert-warning">
    <strong>Cancellation policy settings.</strong>
    Saving updates these settings and the existing unpublished cancellation page. Published policies and accepted versions are never changed here.
</div>
<div class="row g-3">
    <div class="col-xl-7">
        <div class="card card-soft"><div class="card-body">
            <h2 class="h5">Cancellation &amp; Refund Policy</h2>
            <p class="small text-secondary">Edit the schedule and notes, then review the preview. Charges use the total confirmed tour price. Deposits remain quote-specific. Later wording changes need approval before publication.</p>
            <form method="post" action="<?= e(base_url('admin/policy_settings_save.php')) ?>">
                <?= csrf_field() ?>
                <div class="table-responsive"><table class="table align-middle">
                    <thead><tr><th>Minimum days before start</th><th>Cancellation charge (%)</th></tr></thead><tbody>
                    <?php foreach ($policySettings['bands'] as $index => $band): ?>
                    <tr><td><label class="visually-hidden" for="days<?= $index ?>">Band <?= $index + 1 ?> minimum days</label><input id="days<?= $index ?>" class="form-control" type="number" min="0" max="3660" name="policy_settings[bands][<?= $index ?>][min_days]" value="<?= e((string) $band['min_days']) ?>" required></td>
                    <td><label class="visually-hidden" for="charge<?= $index ?>">Band <?= $index + 1 ?> charge percent</label><input id="charge<?= $index ?>" class="form-control" type="number" min="0" max="100" name="policy_settings[bands][<?= $index ?>][charge_percent]" value="<?= e((string) $band['charge_percent']) ?>" required></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                <p class="form-text">Thresholds decrease from top to bottom; ranges are generated without gaps. Last band starts at 0 days; its percentage is editable. Charges cannot decrease as departure approaches.</p>
                <label class="form-label" for="depositRule">Deposit treatment</label>
                <select class="form-select mb-3" id="depositRule" name="policy_settings[deposit_rule]">
                    <option value="included" <?= $policySettings['deposit_rule'] === 'included' ? 'selected' : '' ?>>Deposit is part-payment, included in the schedule charge</option>
                    <option value="non_refundable_if_disclosed" <?= $policySettings['deposit_rule'] === 'non_refundable_if_disclosed' ? 'selected' : '' ?>>Non-refundable only if disclosed; use the greater charge, without adding twice</option>
                </select>
                <label class="form-label" for="supplierRule">Non-recoverable supplier costs</label>
                <select class="form-select mb-3" id="supplierRule" name="policy_settings[supplier_rule]">
                    <option value="higher_documented_cost" <?= $policySettings['supplier_rule'] === 'higher_documented_cost' ? 'selected' : '' ?>>Higher documented, pre-disclosed cost can replace the schedule charge</option>
                    <option value="included" <?= $policySettings['supplier_rule'] === 'included' ? 'selected' : '' ?>>Included in the schedule charge; no additional deduction</option>
                </select>
                <p class="form-text">A higher supplier cost replaces the scheduled charge; it is never added on top. Disclose each item and its amount or calculation basis before acceptance and payment, retain evidence of net non-recoverable costs, and show the traveller an itemised calculation. The displayed refund percentages can be reduced by this exception, including the default 60+ day band.</p>
                <label class="form-label" for="refundDays">Refund processing target (business days)</label>
                <input id="refundDays" class="form-control mb-3" type="number" min="1" max="90" name="policy_settings[refund_business_days]" value="<?= e((string) $policySettings['refund_business_days']) ?>" required>
                <p class="form-text">Review the processing note alongside this number: identify the start of the clock, business-day definition and handling of undisputed refunds while supplier amounts remain unresolved. Payments before confirmation have separate treatment in the preview.</p>
                <?php foreach (['non_refundable_items' => 'Non-refundable items and disclosure', 'before_confirmation_text' => 'Payments before written confirmation', 'notice_text' => 'Cancellation notice and date calculation', 'changes_text' => 'Traveller-requested changes', 'no_show_text' => 'No-show and unused services', 'refund_text' => 'Refund method and processing', 'operator_text' => 'Operator cancellation and disruption', 'operator_contact_text' => 'Operator contact'] as $key => $label): ?>
                    <label class="form-label" for="<?= e($key) ?>"><?= e($label) ?></label>
                    <textarea id="<?= e($key) ?>" class="form-control mb-3" rows="3" maxlength="3000" name="policy_settings[<?= e($key) ?>]" required><?= e((string) $policySettings[$key]) ?></textarea>
                <?php endforeach; ?>
                <button class="btn btn-success" type="submit">Save Policy Settings</button>
                <a class="btn btn-outline-secondary" href="<?= e(base_url('admin/pages.php')) ?>">Review Static Pages</a>
            </form>
        </div></div>
    </div>
    <div class="col-xl-5"><div class="card card-soft"><div class="card-body">
        <h2 class="h5">Saved policy preview</h2>
        <p class="small text-secondary">The preview reflects saved values. Publication is separate; existing published policies and accepted versions stay unchanged.</p>
        <?= ict_cancellation_draft_html() ?>
        <hr><h3 class="h6">Reference examples</h3>
        <p class="small">Agency schedules differ. Island Culture Travels uses its own approved schedule and disclosed booking-specific supplier terms.</p>
        <a href="https://www.srilankaecotourism.com/terms-and-conditions/" target="_blank" rel="noopener">Sri Lanka Ecotourism</a> ·
        <a href="https://sunbirdtours.lk/booking-terms-conditions/" target="_blank" rel="noopener">Sunbird Lanka Tours</a>
    </div></div></div>
</div>
</section>
