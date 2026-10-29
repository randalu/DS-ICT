<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { http_response_code(405); header('Allow: GET'); exit; }
require_once __DIR__ . '/../includes/records_workbook.php';
try {
    $state = ict_retention_state();
    if (!$state['configured']) { throw new RuntimeException('Valid saved retention settings required.'); }
    $path = ict_records_workbook_snapshot($state['settings']);
}
catch (Throwable) { flash_set('error', 'Workbook download unavailable. Check the private template and ZIP/XML support.'); redirect('admin/retention_settings.php'); }
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Island-Culture-Travels-Records-Template.xlsx"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
try { readfile($path); } finally { unlink($path); }
exit;
