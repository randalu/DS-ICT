<?php
declare(strict_types=1);
$seasonField = $packageType === 'itinerary' ? 'best_months_json' : 'best_time_json';
if (db_column_exists(package_tables()[$packageType], $seasonField)):
?>
<details class="mb-3"><summary>Structured season details</summary>
<p class="small text-secondary">Existing ratings and additional keys are preserved. Check below to replace the complete JSON, including any per-month ratings.</p>
<label><input type="checkbox" name="<?= e($seasonField) ?>_override" value="1" <?= old_input($seasonField . '_override') === '1' ? 'checked' : '' ?>> Apply structured JSON instead of the month selector</label>
<textarea class="form-control" rows="5" name="<?= e($seasonField) ?>_raw"><?= e((string) old_input($seasonField . '_raw', $record[$seasonField] ?? '')) ?></textarea>
</details><?php endif; ?>
