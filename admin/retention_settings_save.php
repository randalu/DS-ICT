<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();
if (!is_post()) { redirect('admin/retention_settings.php'); }
guard_post_size();
require_csrf();
require_once __DIR__ . '/../includes/retention_settings.php';
$input = post_input('retention_settings', []);
$token = post_input('retention_token', null);
old_input_set(['retention_settings' => $input, 'retention_token' => is_string($token) && strlen($token) <= 64 ? $token : '']);
try {
    if (!is_array($input)) { throw new InvalidArgumentException('Invalid retention settings.'); }
    if (!is_string($token)) { throw new InvalidArgumentException('Reload the retention settings before saving.'); }
    ict_retention_save($input, $token);
    old_input_clear();
    flash_set('success', 'Retention settings saved. Policy text, analytics runtime and the paused hold are unchanged. Download a fresh blank workbook snapshot if needed.');
} catch (InvalidArgumentException $exception) { flash_set('error', $exception->getMessage()); }
catch (IctRetentionStorageUnavailable $exception) { flash_set('error', $exception->getMessage()); }
catch (Throwable) { flash_set('error', 'Could not save retention settings. Please try again.'); }
redirect('admin/retention_settings.php');
