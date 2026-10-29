<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();
if (!is_post()) { http_response_code(405); header('Allow: POST'); exit; }
guard_post_size(); require_csrf();
require_once __DIR__ . '/../includes/quotation_pdf.php';
try {
    $row = quote_get((int) post_input('id', 0));
    if ($row['state'] === 'draft') { $bytes = quote_render_pdf($row, quote_assets($row['document'])); }
    else { $bytes = $row['pdf_bytes']; if (!is_string($bytes) || !hash_equals((string) $row['pdf_sha256'], hash('sha256', $bytes))) { throw new RuntimeException('Archived PDF verification failed.'); } }
    quote_event((int) $row['id'], (int) admin_id(), 'exported', ['state' => $row['state']]);
    header('Content-Type: application/pdf'); header('Cache-Control: private, no-store'); header('X-Content-Type-Options: nosniff'); header('X-Robots-Tag: noindex, nofollow');
    header('Content-Disposition: inline; filename="' . $row['reference'] . '-R' . $row['revision'] . '.pdf"'); echo $bytes;
} catch (Throwable) { http_response_code(422); echo 'Could not prepare this PDF. Review the quotation images and PDF storage.'; }
