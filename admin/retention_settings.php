<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();
require_once __DIR__ . '/../includes/retention_settings.php';
require_once __DIR__ . '/../includes/records_workbook.php';
$adminTitle = 'Data Retention & Records';
$adminPage = 'retention_settings';
$retentionState = ict_retention_state();
$retentionSettings = $retentionState['settings'];
$retentionReport = ict_analytics_retention_report();
$retentionWorkbookAvailable = $retentionState['configured'] && ict_records_workbook_available();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && ($_GET['review_current'] ?? null) === '1') {
    unset($_SESSION[OLD_INPUT_SESSION_KEY]['retention_settings'], $_SESSION[OLD_INPUT_SESSION_KEY]['retention_token']);
}
$old = old_input('retention_settings', null);
$retentionHasOldInput = is_array($old);
$retentionFormToken = $retentionHasOldInput ? old_input('retention_token', '') : $retentionState['token'];
$retentionFormToken = is_string($retentionFormToken) ? $retentionFormToken : '';
if ($retentionHasOldInput) {
    foreach ($retentionSettings['rules'] as $key => &$rule) {
        foreach (array_keys($rule) as $field) {
            if (is_scalar($old['rules'][$key][$field] ?? null)) { $rule[$field] = (string) $old['rules'][$key][$field]; }
        }
    }
    unset($rule);
    foreach (['hold_note', 'copies_note', 'storage_note'] as $key) {
        if (is_scalar($old[$key] ?? null)) { $retentionSettings[$key] = (string) $old[$key]; }
    }
}
require __DIR__ . '/_top.php';
require __DIR__ . '/_retention_settings_form.php';
require __DIR__ . '/_bottom.php';
