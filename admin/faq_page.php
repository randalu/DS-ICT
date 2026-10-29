<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$page = ensure_faq_static_page_record();
$pageId = (int) ($page['id'] ?? 0);

if ($pageId <= 0) {
    flash_set('error', 'Could not initialize FAQ page record.');
    redirect('admin/faqs.php');
}

redirect('admin/page_form.php?id=' . $pageId);
