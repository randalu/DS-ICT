<?php
declare(strict_types=1);

$adminTitle = 'Site Settings';
$adminPage = 'site_settings';

require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();

ensure_site_settings_schema();

$primaryPhone = trim((string) site_setting_get('contact_phone_primary', SITE_PHONE));
$secondaryPhone = trim((string) site_setting_get('contact_phone_secondary', SITE_PHONE_SECONDARY));
$contactEmail = trim((string) site_setting_get('contact_email', SITE_EMAIL));
$whatsappNumber = trim((string) site_setting_get('contact_whatsapp_number', WHATSAPP_NUMBER));
$whatsappDefaultText = trim((string) site_setting_get('contact_whatsapp_default_text', DEFAULT_WHATSAPP_TEXT));
$noticeEnabled = site_under_construction_notice_enabled();
$noticeMessage = site_under_construction_notice_message();

$footerBottomText = footer_bottom_links_text();
$footerServiceText = footer_service_links_text();

require_once __DIR__ . '/../includes/recaptcha.php';
$recaptchaEnabled = site_setting_get('recaptcha_enabled', RECAPTCHA_ENABLED ? '1' : '0');
$recaptchaSiteKey = site_setting_get('recaptcha_site_key', '');
$recaptchaSecretKey = site_setting_get('recaptcha_secret_key', '');
$indexnowEnabled = site_setting_get('indexnow_enabled', INDEXNOW_ENABLED ? '1' : '0');
$indexnowKey = INDEXNOW_KEY;
$cspEnforceNote = site_setting_get('csp_enforce', '1');

$oldPrimaryPhone = old_input('contact_phone_primary', '__ICT_NO_OLD__');
if ($oldPrimaryPhone !== '__ICT_NO_OLD__') {
    $primaryPhone = trim((string) $oldPrimaryPhone);
}

$oldSecondaryPhone = old_input('contact_phone_secondary', '__ICT_NO_OLD__');
if ($oldSecondaryPhone !== '__ICT_NO_OLD__') {
    $secondaryPhone = trim((string) $oldSecondaryPhone);
}

$oldContactEmail = old_input('contact_email', '__ICT_NO_OLD__');
if ($oldContactEmail !== '__ICT_NO_OLD__') {
    $contactEmail = trim((string) $oldContactEmail);
}

$oldWhatsappNumber = old_input('contact_whatsapp_number', '__ICT_NO_OLD__');
if ($oldWhatsappNumber !== '__ICT_NO_OLD__') {
    $whatsappNumber = trim((string) $oldWhatsappNumber);
}

$oldWhatsappText = old_input('contact_whatsapp_default_text', '__ICT_NO_OLD__');
if ($oldWhatsappText !== '__ICT_NO_OLD__') {
    $whatsappDefaultText = trim((string) $oldWhatsappText);
}

$oldNoticeEnabled = old_input('site_under_construction_notice_enabled', '__ICT_NO_OLD__');
if ($oldNoticeEnabled !== '__ICT_NO_OLD__') {
    $noticeEnabled = (string) $oldNoticeEnabled === '1';
}

$oldNoticeMessage = old_input('site_under_construction_notice_message', '__ICT_NO_OLD__');
if ($oldNoticeMessage !== '__ICT_NO_OLD__') {
    $noticeMessage = trim((string) $oldNoticeMessage);
}

$oldRecaptchaEnabled = old_input('recaptcha_enabled', '__ICT_NO_OLD__');
if ($oldRecaptchaEnabled !== '__ICT_NO_OLD__') {
    $recaptchaEnabled = (string) $oldRecaptchaEnabled === '1' ? '1' : '0';
}

$oldRecaptchaSiteKey = old_input('recaptcha_site_key', '__ICT_NO_OLD__');
if ($oldRecaptchaSiteKey !== '__ICT_NO_OLD__') {
    $recaptchaSiteKey = trim((string) $oldRecaptchaSiteKey);
}

$oldRecaptchaSecretKey = old_input('recaptcha_secret_key', '__ICT_NO_OLD__');
if ($oldRecaptchaSecretKey !== '__ICT_NO_OLD__') {
    $recaptchaSecretKey = trim((string) $oldRecaptchaSecretKey);
}

$oldIndexnowEnabled = old_input('indexnow_enabled', '__ICT_NO_OLD__');
if ($oldIndexnowEnabled !== '__ICT_NO_OLD__') {
    $indexnowEnabled = (string) $oldIndexnowEnabled === '1' ? '1' : '0';
}

$oldCspEnforce = old_input('csp_enforce', '__ICT_NO_OLD__');
if ($oldCspEnforce !== '__ICT_NO_OLD__') {
    $cspEnforceNote = (string) $oldCspEnforce === '1' ? '1' : '0';
}

$oldFooterBottom = old_input('footer_bottom_links_text', '__ICT_NO_OLD__');
if ($oldFooterBottom !== '__ICT_NO_OLD__') {
    $footerBottomText = (string) $oldFooterBottom;
}

$oldFooterService = old_input('footer_service_links_text', '__ICT_NO_OLD__');
if ($oldFooterService !== '__ICT_NO_OLD__') {
    $footerServiceText = (string) $oldFooterService;
}

require __DIR__ . '/_top.php';
?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-2">Contact Details</h2>
                <p class="text-secondary small mb-3">These values are used sitewide in footer, contact actions, and WhatsApp buttons.</p>

                <form method="post" action="<?= e(base_url('admin/site_settings_save.php')) ?>" data-ai-context="site_settings" data-ai-title="Site Settings">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Main Phone *</label>
                        <input type="text" name="contact_phone_primary" class="form-control" value="<?= e($primaryPhone) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Secondary Phone</label>
                        <input type="text" name="contact_phone_secondary" class="form-control" value="<?= e($secondaryPhone) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contact Email *</label>
                        <input type="email" name="contact_email" class="form-control" value="<?= e($contactEmail) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">WhatsApp Chat Number *</label>
                        <input type="text" name="contact_whatsapp_number" class="form-control" value="<?= e($whatsappNumber) ?>" required>
                        <div class="form-text">Use international style, e.g. <code>+94 76 919 2427</code>.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Default WhatsApp Message</label>
                        <textarea name="contact_whatsapp_default_text" rows="3" class="form-control" data-ai-field="contact_whatsapp_default_text" data-ai-label="Default WhatsApp Message" data-ai-context="Default pre-filled WhatsApp message sent when a visitor clicks the WhatsApp button. Should be friendly, welcoming, and invite the visitor to share their travel dates and interests. Keep it under 100 words."><?= e($whatsappDefaultText) ?></textarea>
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <h2 class="h5 mb-2">reCAPTCHA</h2>
                        <p class="text-secondary small mb-3">Google reCAPTCHA protects contact and subscribe forms from spam. <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener">Get your keys</a>.</p>
                        <input type="hidden" name="recaptcha_enabled" value="0">
                        <div class="form-check form-switch mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="recaptchaEnabled"
                                name="recaptcha_enabled"
                                value="1"
                                <?= $recaptchaEnabled === '1' ? 'checked' : '' ?>
                            >
                            <label class="form-check-label fw-semibold" for="recaptchaEnabled">Enable reCAPTCHA on forms</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Site Key</label>
                            <input type="text" name="recaptcha_site_key" class="form-control font-monospace" value="<?= e($recaptchaSiteKey) ?>" placeholder="6Lxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                            <div class="form-text">Leave blank to use the value from <code>.env</code> (<?= e(RECAPTCHA_SITE_KEY !== '' ? 'set' : 'not set') ?>).</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Secret Key</label>
                            <input type="password" name="recaptcha_secret_key" class="form-control font-monospace" value="<?= e($recaptchaSecretKey) ?>" placeholder="6Lxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" autocomplete="off">
                            <div class="form-text">Leave blank to use the value from <code>.env</code> (<?= e(RECAPTCHA_SECRET_KEY !== '' ? 'set' : 'not set') ?>). Saved value is hidden for security.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <h2 class="h5 mb-2">Search &amp; Security</h2>
                        <p class="text-secondary small mb-3">IndexNow submits updated URLs to Bing/Yandex. CSP is currently <strong>enforcing</strong> via <code>.htaccess</code> with reporting to <code>/csp-report.php</code>.</p>
                        <input type="hidden" name="indexnow_enabled" value="0">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="indexnowEnabled" name="indexnow_enabled" value="1" <?= $indexnowEnabled === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="indexnowEnabled">Enable IndexNow submissions</label>
                        </div>
                        <div class="form-text mb-3">Key: <code><?= e($indexnowKey !== '' ? $indexnowKey : 'not set') ?></code> — configure <code>INDEXNOW_KEY</code> in <code>.env</code> and publish <code><?= e($indexnowKey !== '' ? base_url($indexnowKey . '.txt') : base_url('{key}.txt')) ?></code>.</div>
                        <input type="hidden" name="csp_enforce" value="0">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="cspEnforce" name="csp_enforce" value="1" <?= $cspEnforceNote === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="cspEnforce">CSP enforcing (uncheck to use Report-Only)</label>
                        </div>
                        <div class="form-text">Toggling Report-Only requires updating <code>.htaccess</code> <code>Content-Security-Policy</code> → <code>Content-Security-Policy-Report-Only</code> and redeploying. This setting records intent for the next deploy.</div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <h2 class="h5 mb-2">Footer Bottom Links</h2>
                        <p class="text-secondary small mb-2">Links shown in the footer bottom bar (separated by pipes). One per line: <code>url | Label</code>.</p>
                        <textarea name="footer_bottom_links_text" rows="5" class="form-control font-monospace" placeholder="faq | FAQ"><?= e($footerBottomText) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <h2 class="h5 mb-2">Footer Service Extras</h2>
                        <p class="text-secondary small mb-2">Extra links in the "Itineraries &amp; Services" column. One per line: <code>url | Label</code>.</p>
                        <textarea name="footer_service_links_text" rows="4" class="form-control font-monospace" placeholder="seasonal | When to Visit"><?= e($footerServiceText) ?></textarea>
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <h2 class="h5 mb-2">Public Launch Notice</h2>
                        <p class="text-secondary small mb-3">Show a sitewide notice while final images and revisions are being completed. Visitors can still browse every page.</p>
                        <input type="hidden" name="site_under_construction_notice_enabled" value="0">
                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="siteUnderConstructionNotice"
                                name="site_under_construction_notice_enabled"
                                value="1"
                                <?= $noticeEnabled ? 'checked' : '' ?>
                            >
                            <label class="form-check-label fw-semibold" for="siteUnderConstructionNotice">Show under-construction notice on public pages</label>
                        </div>
                        <div class="form-text">This is only a visible banner. It does not block the site, hide pages, or add noindex tags.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Notice Message</label>
                        <textarea name="site_under_construction_notice_message" rows="3" maxlength="280" class="form-control" data-ai-field="site_under_construction_notice_message" data-ai-label="Site Notice Message" data-ai-context="Short site notice or announcement message shown to visitors. Should be clear, polite, and informative. Maximum 2 sentences."><?= e($noticeMessage) ?></textarea>
                        <div class="form-text">Keep it short and factual. Maximum 280 characters.</div>
                    </div>

                    <button type="submit" class="btn btn-success">Save Site Settings</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card card-soft mb-3"><div class="card-body">
            <h2 class="h6">Cancellation &amp; Refund Policy</h2>
            <p class="small text-secondary">Edit cancellation bands, deposit treatment, supplier costs and refund processing notes.</p>
            <a class="btn btn-outline-success" href="<?= e(base_url('admin/policy_settings.php')) ?>">Open Policy Settings</a>
        </div></div>
        <div class="card card-soft mb-3"><div class="card-body">
            <h2 class="h6">Data Retention &amp; Records</h2>
            <p class="small text-secondary">Edit retention review periods, hold/copy rules and download the recordkeeping workbook.</p>
            <a class="btn btn-outline-success" href="<?= e(base_url('admin/retention_settings.php')) ?>">Open Retention Settings</a>
        </div></div>
        <div class="card card-soft mb-3"><div class="card-body">
            <h2 class="h6">Privacy &amp; Tracking Controls</h2>
            <p class="small text-secondary">Review optional-service choices, public AI controls and the paused analytics retention hold.</p>
            <a class="btn btn-outline-success" href="<?= e(base_url('admin/privacy_settings.php')) ?>">Open Privacy Controls</a>
        </div></div>
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h6 mb-2">Current WhatsApp Link</h2>
                <p class="small text-secondary mb-2">Preview generated chat URL:</p>
                <div class="small text-break"><?= e(site_whatsapp_url($whatsappDefaultText)) ?></div>

                <hr class="my-4">

                <h2 class="h6 mb-2">Launch Notice</h2>
                <div class="mb-2">
                    <span class="badge <?= $noticeEnabled ? 'text-bg-warning' : 'text-bg-success' ?>">
                        <?= $noticeEnabled ? 'Visible on public site' : 'Site marked live' ?>
                    </span>
                </div>
                <p class="small text-secondary mb-0"><?= e($noticeEnabled ? $noticeMessage : 'The public under-construction notice is disabled.') ?></p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
