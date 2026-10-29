<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();
require_once __DIR__ . '/../includes/cancellation_settings.php';
$adminTitle = 'Cancellation & Refund Settings';
$adminPage = 'policy_settings';
$policySettings = ict_cancellation_settings();
$oldSettings = old_input('policy_settings', null);
if (is_array($oldSettings)) {
    // Show only scalar fields; malicious nested values must not reach HTML inputs.
    foreach ($policySettings as $key => $value) {
        if ($key !== 'bands' && is_scalar($oldSettings[$key] ?? null)) {
            $policySettings[$key] = (string) $oldSettings[$key];
        }
    }
    foreach ($policySettings['bands'] as $index => &$band) {
        foreach (['min_days', 'charge_percent'] as $key) {
            if (is_scalar($oldSettings['bands'][$index][$key] ?? null)) {
                $band[$key] = (string) $oldSettings['bands'][$index][$key];
            }
        }
    }
    unset($band);
}
require __DIR__ . '/_top.php';
require __DIR__ . '/_policy_settings_form.php';
require __DIR__ . '/_bottom.php';
