<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/site_settings.php');
}

guard_post_size();
require_csrf();
ensure_site_settings_schema();

$primaryPhone = trim((string) post_input('contact_phone_primary', ''));
$secondaryPhone = trim((string) post_input('contact_phone_secondary', ''));
$contactEmail = trim((string) post_input('contact_email', ''));
$whatsappNumber = trim((string) post_input('contact_whatsapp_number', ''));
$whatsappDefaultText = trim((string) post_input('contact_whatsapp_default_text', ''));
$noticeEnabled = post_input('site_under_construction_notice_enabled', '0') === '1' ? '1' : '0';
$noticeMessage = trim((string) post_input('site_under_construction_notice_message', ''));

old_input_set($_POST);

$errors = [];

if ($primaryPhone === '') {
    $errors[] = 'Main phone is required.';
}

if ($contactEmail === '' || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid contact email is required.';
}

if ($whatsappNumber === '') {
    $errors[] = 'WhatsApp chat number is required.';
}

if ($whatsappDefaultText === '') {
    $whatsappDefaultText = DEFAULT_WHATSAPP_TEXT;
}

if ($noticeMessage === '') {
    $noticeMessage = site_under_construction_default_message();
}

if (mb_strlen($noticeMessage) > 280) {
    $errors[] = 'Under-construction notice must be 280 characters or fewer.';
}

$recaptchaEnabled = post_input('recaptcha_enabled', '0') === '1' ? '1' : '0';
$recaptchaSiteKey = trim((string) post_input('recaptcha_site_key', ''));
$recaptchaSecretKey = trim((string) post_input('recaptcha_secret_key', ''));
$indexnowEnabled = post_input('indexnow_enabled', '0') === '1' ? '1' : '0';
$cspEnforce = post_input('csp_enforce', '0') === '1' ? '1' : '0';

$footerBottomText = (string) post_input('footer_bottom_links_text', '');
$footerServiceText = (string) post_input('footer_service_links_text', '');

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect('admin/site_settings.php');
}

site_setting_set('contact_phone_primary', $primaryPhone);
site_setting_set('contact_phone_secondary', $secondaryPhone);
site_setting_set('contact_email', $contactEmail);
site_setting_set('contact_whatsapp_number', $whatsappNumber);
site_setting_set('contact_whatsapp_default_text', $whatsappDefaultText);
site_setting_set('site_under_construction_notice_enabled', $noticeEnabled);
site_setting_set('site_under_construction_notice_message', $noticeMessage);
site_setting_set('recaptcha_enabled', $recaptchaEnabled);
site_setting_set('recaptcha_site_key', $recaptchaSiteKey);
site_setting_set('recaptcha_secret_key', $recaptchaSecretKey);
site_setting_set('indexnow_enabled', $indexnowEnabled);
site_setting_set('csp_enforce', $cspEnforce);

$footerBottomLinks = parse_footer_links_text($footerBottomText);
$footerServiceLinks = parse_footer_links_text($footerServiceText);
site_setting_set('footer_bottom_links', json_encode($footerBottomLinks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]');
site_setting_set('footer_service_links', json_encode($footerServiceLinks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]');

old_input_clear();
flash_set('success', 'Site settings updated.');
redirect('admin/site_settings.php');
