<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/business_partners.php');
}

guard_post_size();
require_csrf();
ensure_site_settings_schema();

$enabled = post_input('business_partners_public_enabled') ? '1' : '0';
site_setting_set('business_partners_public_enabled', $enabled);

indexnow_submit_urls([
    base_url('business-partners'),
    base_url('business-partners/hotels-accommodations'),
    base_url('sitemap.xml'),
]);

flash_set(
    'success',
    $enabled === '1'
        ? 'Public Business Partners area enabled.'
        : 'Public Business Partners area disabled. Existing applications and partner records were not changed.'
);

redirect('admin/business_partners.php');
