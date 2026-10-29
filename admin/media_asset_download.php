<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
ensure_social_marketing_schema();

$id = (int) get_input('id', 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Media asset not found.');
}

$record = fetch_one('SELECT file_path, file_name, mime_type FROM media_assets WHERE id = :id LIMIT 1', ['id' => $id]);
if (!$record) {
    http_response_code(404);
    exit('Media asset not found.');
}

$filePath = trim((string) ($record['file_path'] ?? ''));
$absolutePath = uploaded_media_filesystem_path($filePath);
if ($absolutePath === null || !is_file($absolutePath)) {
    http_response_code(404);
    exit('File missing on disk.');
}

$fileName = trim((string) ($record['file_name'] ?? basename($absolutePath)));
if ($fileName === '') {
    $fileName = basename($absolutePath);
}
$fileName = basename(str_replace('\\', '/', $fileName));
$fileName = preg_replace('/[\x00-\x1F\x7F"]+/', '', $fileName) ?: basename($absolutePath);

$mimeType = trim((string) ($record['mime_type'] ?? 'application/octet-stream'));
if ($mimeType === '') {
    $mimeType = 'application/octet-stream';
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . (string) filesize($absolutePath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');
readfile($absolutePath);
exit;
