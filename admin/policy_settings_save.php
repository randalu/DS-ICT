<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();
if (!is_post()) {
    redirect('admin/policy_settings.php');
}
guard_post_size();
require_csrf();
require_once __DIR__ . '/../includes/cancellation_settings.php';
$input = post_input('policy_settings', []);
old_input_set(['policy_settings' => $input]);
try {
    if (!is_array($input)) {
        throw new InvalidArgumentException('Invalid policy settings.');
    }
    $draftUpdated = ict_cancellation_save($input);
    old_input_clear();
    flash_set('success', $draftUpdated
        ? 'Policy settings saved and unpublished cancellation page updated. Publication remains separate.'
        : 'Policy settings saved. No unpublished cancellation page was updated; published content stays unchanged.');
} catch (InvalidArgumentException $exception) {
    flash_set('error', $exception->getMessage());
} catch (Throwable) {
    flash_set('error', 'Could not save the policy settings. Please try again.');
}
redirect('admin/policy_settings.php');
