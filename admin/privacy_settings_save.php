<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();
if (!is_post()) { redirect('admin/privacy_settings.php'); }
guard_post_size();
require_csrf();
$input = post_input('privacy_settings', []);
old_input_set(['privacy_settings' => $input]);
try {
    if (!is_array($input)) { throw new InvalidArgumentException('Invalid privacy controls.'); }
    $settings = ict_privacy_validate_settings($input);
    if ($settings['gtm_allowed'] && post_input('gtm_review_ack') !== '1') { throw new InvalidArgumentException('Acknowledge the published-container review before allowing GTM.'); }
    if ($settings['review_invitations_allowed'] && post_input('review_invitation_ack') !== '1') { throw new InvalidArgumentException('Acknowledge the invitation purpose/account/privacy review before allowing invitations.'); }
    if ($settings['public_ai_search_enabled'] && post_input('public_ai_review_ack') !== '1') { throw new InvalidArgumentException('Acknowledge the provider/account/privacy review before enabling public AI search.'); }
    ict_privacy_save_settings($input);
    old_input_clear();
    flash_set('success', 'Privacy controls saved. Visitors must still make their own choice. Disposal remains paused.');
} catch (InvalidArgumentException $exception) { flash_set('error', $exception->getMessage()); }
catch (Throwable) { flash_set('error', 'Could not save privacy controls. Please try again.'); }
redirect('admin/privacy_settings.php');
