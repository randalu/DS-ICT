<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();
$adminTitle = 'Privacy & Tracking Controls';
$adminPage = 'privacy_settings';
$privacySettings = ict_privacy_settings();
$privacyConfigured = $privacySettings['configured'];
$old = old_input('privacy_settings', null);
$privacyHasOldInput = is_array($old);
if (is_array($old)) {
    foreach (array_keys(ict_privacy_defaults()) as $key) { if (is_scalar($old[$key] ?? null)) { $privacySettings[$key] = $old[$key]; } }
}
require __DIR__ . '/_top.php';
require __DIR__ . '/_privacy_settings_form.php';
require __DIR__ . '/_bottom.php';
