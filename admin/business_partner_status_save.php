<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();
ensure_business_partners_schema();

if (!is_post()) {
    redirect('admin/business_partners.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);
$status = trim((string) post_input('status', ''));
$returnTo = trim((string) post_input('return_to', ''));
$statuses = business_partner_statuses();
$redirectTarget = 'admin/business_partners.php';
$returnTo = str_replace(["\r", "\n"], '', $returnTo);
if ($returnTo !== '' && preg_match('#^/?admin/business_partners\.php(?:\?.*)?$#', $returnTo)) {
    $redirectTarget = ltrim($returnTo, '/');
}

if ($id <= 0 || !isset($statuses[$status])) {
    flash_set('error', 'Invalid partner status update.');
    redirect($redirectTarget);
}

$partner = fetch_one('SELECT id, slug, status, published_at FROM business_partners WHERE id = :id LIMIT 1', ['id' => $id]);
if (!$partner) {
    flash_set('error', 'Business partner not found.');
    redirect($redirectTarget);
}

$wasPublished = (string) ($partner['status'] ?? '') === 'published';
$willPublish = $status === 'published';
$publishedAtSql = $willPublish && empty($partner['published_at']) ? ', published_at = CURRENT_TIMESTAMP' : '';

try {
    execute_query(
        "UPDATE business_partners SET status = :status{$publishedAtSql} WHERE id = :id",
        [
            'status' => $status,
            'id' => $id,
        ]
    );
} catch (Throwable $e) {
    flash_set('error', 'Failed to update partner status.');
    redirect($redirectTarget);
}

$urls = [
    base_url('business-partners'),
    base_url('business-partners/hotels-accommodations'),
    base_url('sitemap.xml'),
];

$slug = trim((string) ($partner['slug'] ?? ''));
if ($slug !== '' && ($wasPublished || $willPublish)) {
    $urls[] = base_url('business-partners/partner/' . $slug);
}
indexnow_submit_urls($urls);

flash_set('success', 'Partner status changed to ' . ($statuses[$status] ?? $status) . '.');
redirect($redirectTarget);
