<?php
declare(strict_types=1);
if (!isset($retentionSettings) || !is_array($retentionSettings)) { http_response_code(404); exit; }
?>
<div class="alert alert-warning"><strong>Retention review periods.</strong> Save controls here, then download the workbook with current settings. Review dates require a human decision; no new deletion job is enabled. Published privacy text and accepted versions are unchanged.</div>
<?php if ($retentionState['invalid']): ?>
<div class="alert alert-danger" role="status">Saved retention settings could not be verified. The values below are default previews, not saved rules. Saving and downloads are blocked; review the stored document separately.</div>
<?php elseif (!$retentionState['available']): ?>
<div class="alert alert-danger" role="status">The existing settings table is unavailable. Default previews are unsaved. No schema changes will be made; saving and downloads are blocked.</div>
<?php elseif ($retentionState['configured']): ?>
<div class="alert alert-info" role="status">A valid saved retention configuration is in use. Form changes take effect only after a successful save.</div>
<?php else: ?>
<div class="alert alert-info" role="status">No saved retention configuration exists. The owner-approved defaults below are unsaved previews and do not establish a saved analytics rule.</div>
<?php endif; ?>
<?php if ($retentionHasOldInput): ?><div class="alert alert-warning" role="status">The form shows unsaved values from your previous submission. They are not the configuration in use. Review them before saving. <a href="<?= e(base_url('admin/retention_settings.php?review_current=1')) ?>">Discard unsaved values and reload saved rules</a>.</div><?php endif; ?>
<div class="card card-soft mb-3"><div class="card-body">
<h2 class="h5">Retention settings and active hold</h2>
<dl class="row mb-0">
<dt class="col-sm-4">Runtime event cutoff</dt><dd class="col-sm-8"><?= $retentionReport['runtime_days'] !== null ? e($retentionReport['runtime_days'] . ' days') : 'Unavailable' ?></dd>
<dt class="col-sm-4">Saved analytics rule</dt><dd class="col-sm-8"><?= $retentionReport['manual_rule'] ? e($retentionReport['manual_rule']['period'] . ' ' . $retentionReport['manual_rule']['unit'] . '; ' . $retentionReport['manual_rule']['trigger']) : 'Not configured/verified' ?></dd>
<dt class="col-sm-4">Period and trigger match</dt><dd class="col-sm-8"><?= $retentionReport['periods_reconciled'] ? 'Yes; this does not authorize disposal' : 'No; review required' ?></dd>
<dt class="col-sm-4">Disposal</dt><dd class="col-sm-8">Paused; no apply operation exists. <a href="<?= e(base_url('admin/analytics_retention.php')) ?>">Review the hold and dry run</a>.</dd>
<dt class="col-sm-4">Recorded hold review</dt><dd class="col-sm-8"><?= $retentionReport['hold']['configured'] ? e('Revision ' . $retentionReport['hold']['revision'] . '; Admin#' . $retentionReport['hold']['reviewed_by'] . '; ' . $retentionReport['hold']['reference'] . '; next manual review ' . $retentionReport['hold']['next_review_date']) : 'Not verified; disposal remains paused' ?></dd>
</dl></div></div>
<div class="card card-soft mb-3"><div class="card-body">
<h2 class="h5">Recordkeeping workbook</h2>
<p>Two sheets: a record-reference register with calculated review dates, and the current retention rules. The template is empty; no customer data is exported. Save filled copies privately. Existing workbooks are snapshots: after changing settings, copy the refreshed Retention cells into the existing Retention sheet, preserving its name and your Records sheet. Apply the start-date rule when entering dates; review existing dates when that rule changes.</p>
<?php if ($retentionWorkbookAvailable): ?><a class="btn btn-outline-success" href="<?= e(base_url('admin/records_template_download.php')) ?>">Download Current Workbook Template</a><?php else: ?><p class="small text-secondary">Download unavailable until valid retention settings are saved and an existing private workbook spool outside public paths is verified. Server permissions and recovery setup require separate review.</p><?php endif; ?>
</div></div>
<form method="post" action="<?= e(base_url('admin/retention_settings_save.php')) ?>">
<?= csrf_field() ?>
<input type="hidden" name="retention_token" value="<?= e($retentionFormToken) ?>">
<fieldset <?= $retentionState['invalid'] || !$retentionState['available'] ? 'disabled' : '' ?>>
<div class="row g-3">
<?php foreach (ict_retention_categories() as $key => $category): $rule = $retentionSettings['rules'][$key]; ?>
<div class="col-md-6"><div class="card card-soft h-100"><div class="card-body">
<h2 class="h6"><?= e($category['label']) ?></h2>
<div class="row g-2 mb-2"><div class="col-6"><label class="form-label" for="period_<?= e($key) ?>">Period</label><input class="form-control" id="period_<?= e($key) ?>" type="number" min="1" max="36500" name="retention_settings[rules][<?= e($key) ?>][period]" value="<?= e((string) $rule['period']) ?>" required></div>
<div class="col-6"><label class="form-label" for="unit_<?= e($key) ?>">Unit</label><select class="form-select" id="unit_<?= e($key) ?>" name="retention_settings[rules][<?= e($key) ?>][unit]" required><?php if (!in_array($rule['unit'], ['months', 'days'], true)): ?><option value="" selected>Invalid submitted unit; select a unit</option><?php endif; ?><?php foreach (['months', 'days'] as $unit): ?><option value="<?= $unit ?>" <?= $rule['unit'] === $unit ? 'selected' : '' ?>><?= ucfirst($unit) ?></option><?php endforeach; ?></select></div></div>
<label class="form-label" for="trigger_<?= e($key) ?>">Start counting from</label><textarea class="form-control" rows="2" maxlength="500" id="trigger_<?= e($key) ?>" name="retention_settings[rules][<?= e($key) ?>][trigger]" required><?= e($rule['trigger']) ?></textarea>
<p class="small text-secondary mt-2 mb-0"><?= e($category['basis']) ?></p>
<?php if (str_starts_with($category['source'], 'https://')): ?><a class="small" href="<?= e($category['source']) ?>" target="_blank" rel="noopener">Research reference</a><?php endif; ?>
</div></div></div>
<?php endforeach; ?>
</div>
<div class="card card-soft mt-3"><div class="card-body">
<?php foreach (['hold_note' => 'Holds and continuing obligations', 'copies_note' => 'Email and spreadsheet copies', 'storage_note' => 'Private storage and working copies'] as $key => $label): ?>
<label class="form-label" for="<?= e($key) ?>"><?= e($label) ?></label><textarea class="form-control mb-3" id="<?= e($key) ?>" rows="3" maxlength="3000" name="retention_settings[<?= e($key) ?>]" required><?= e($retentionSettings[$key]) ?></textarea>
<?php endforeach; ?>
<p class="form-text">These are internal review periods, not automatic deletion instructions or verified agency-wide practice. Saving changes only this retention configuration; policy text, analytics runtime, the paused hold and external providers are unchanged. Continuing obligations can require longer retention.</p>
<button class="btn btn-success" type="submit">Save Retention Settings</button>
</div></div></fieldset></form>
