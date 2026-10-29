<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image_manager.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
function image_manager_reply(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
if (!admin_check()) { image_manager_reply(['error' => 'Your session expired. Sign in again, then select your replacement.', 'login' => base_url('admin/login.php?return=image_manager')], 401); }
try {
    if (($_GET['action']??'')==='activity_gallery') {
        if(!is_get()){image_manager_reply(['error'=>'Method not allowed.'],405);}
        require_once __DIR__.'/../includes/activity_pages.php';
        require_once __DIR__.'/../includes/activity_gallery_view.php';
        $parent=image_manager_record('activity',(int)get_input('id',0));
        $entryId=(int)get_input('example_id',0);
        activity_gallery_entry((int)$parent['id'],$entryId);
        if(!activity_gallery_available()){throw new InvalidArgumentException('Entry gallery schema is unavailable.');}
        ob_start();activity_gallery_manager_state($entryId);$html=ob_get_clean();
        image_manager_reply(['html'=>$html]);
    }
    if (($_GET['action'] ?? '') === 'gallery') {
        if (!is_get()) { image_manager_reply(['error' => 'Method not allowed.'], 405); }
        image_manager_gallery_require();
        require_once __DIR__ . '/../includes/image_manager_view.php';
        $parent = image_manager_record('destination', (int) get_input('id', 0));
        ob_start(); image_manager_gallery_view($parent); $html = ob_get_clean();
        image_manager_reply(['html' => $html]);
    }
    if (($_GET['action'] ?? '') === 'library') {
        if (!is_get()) { image_manager_reply(['error' => 'Method not allowed.'], 405); }
        if (!db_table_exists('media_assets')) { image_manager_reply(['error' => 'Media library is unavailable.'], 503); }
        $q = mb_substr(trim((string) get_input('q', '')), 0, 120);
        $page = max(1, min(100000, (int) get_input('page', 1)));
        $video = get_input('video', '') === '1';
        $mimes = array_keys(media_allowed_mime_types($video));
        $quoted = implode(',', array_map(static fn(string $mime): string => db()->quote($mime), $mimes));
        $where = "mime_type IN ({$quoted})";
        $params = [];
        $destination = (int) get_input('destination_id', 0);
        if ($destination > 0) {
            if (!db_column_exists('media_assets', 'destination_id')) { throw new InvalidArgumentException('Destination categories are unavailable.'); }
            image_manager_record('destination', $destination);
            $where .= get_input('include_unassigned', '') === '1'
                ? ' AND (destination_id = :destination OR destination_id IS NULL OR destination_id = 0)'
                : ' AND destination_id = :destination';
            $params['destination'] = $destination;
        }
        if ($q !== '') { $where .= ' AND file_name LIKE :q'; $params['q'] = '%' . $q . '%'; }
        $offset = ($page - 1) * 24;
        $altColumn = db_column_exists('media_assets', 'alt_text') ? 'alt_text' : 'NULL AS alt_text';
        $rows = fetch_all("SELECT id, file_path, file_name, mime_type, {$altColumn} FROM media_assets WHERE {$where} ORDER BY id DESC LIMIT 25 OFFSET {$offset}", $params);
        $more = count($rows) > 24;
        $items = array_map(static fn(array $row): array => ['id' => (int) $row['id'], 'name' => $row['file_name'], 'caption' => (string) ($row['alt_text'] ?? ''),
            'url' => stored_media_url($row['file_path']), 'media_type' => str_starts_with($row['mime_type'], 'video/') ? 'video' : 'image'], array_slice($rows, 0, 24));
        image_manager_reply(['items' => $items, 'more' => $more, 'page' => $page]);
    }
    if (!is_post()) { header('Allow: POST'); image_manager_reply(['error' => 'Use POST to save images.'], 405); }
    $postLimit = image_manager_ini_bytes((string) ini_get('post_max_size'));
    if ($postLimit > 0 && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $postLimit) { image_manager_reply(['error' => 'Upload exceeds the server request limit. Choose a smaller file.'], 413); }
    if (!csrf_verify(is_string($_POST['_token'] ?? null) ? $_POST['_token'] : null)) { image_manager_reply(['error' => 'Invalid or expired security token. Reload before saving.'], 419); }
    foreach ($_POST as $value) { if (!is_string($value)) { throw new InvalidArgumentException('Invalid request fields.'); } }
    if (array_diff(array_keys($_FILES), ['media'])) { throw new InvalidArgumentException('Submit one file only.'); }
    if (isset($_FILES['media'])) {
        foreach (['name', 'tmp_name', 'error', 'size'] as $key) {
            if (!isset($_FILES['media'][$key]) || !is_scalar($_FILES['media'][$key])) { throw new InvalidArgumentException('Submit one file only.'); }
        }
    }
    if(str_starts_with((string)($_POST['action']??''),'activity_gallery_')){
        require_once __DIR__.'/../includes/activity_gallery_view.php';
        activity_gallery_save($_POST,$_FILES['media']??null);
        ob_start();activity_gallery_manager_state((int)$_POST['example_id']);$html=ob_get_clean();
        image_manager_reply(['html'=>$html,'ok'=>true]);
    }
    if (($_POST['action'] ?? '') === 'library_upload') {
        if (!empty($_POST['asset_id'])) { throw new InvalidArgumentException('Choose one upload only.'); }
        image_manager_reply(['uploaded' => image_manager_destination_upload($_POST, $_FILES['media'] ?? null)]);
    }
    if (str_starts_with((string) ($_POST['action'] ?? ''), 'gallery_')) {
        if ($_FILES) { throw new InvalidArgumentException('Gallery metadata actions do not accept files.'); }
        image_manager_gallery_manage($_POST);
        image_manager_reply(['ok' => true]);
    }
    $result = image_manager_save($_POST, $_FILES['media'] ?? null);
    image_manager_reply(['saved' => $result]);
} catch (DomainException $error) {
    $latest = null;
    try { $latest = image_manager_present((string) ($_POST['type'] ?? ''), image_manager_record((string) ($_POST['type'] ?? ''), (int) ($_POST['id'] ?? 0)), (string) ($_POST['slot'] ?? ''), (int) ($_POST['child_id'] ?? 0)); } catch (Throwable) {}
    image_manager_reply(['error' => $error->getMessage(), 'latest' => $latest], 409);
} catch (InvalidArgumentException $error) {
    image_manager_reply(['error' => $error->getMessage()], 422);
} catch (Throwable $error) {
    error_log('Image manager: ' . $error->getMessage());
    image_manager_reply(['error' => 'The image could not be saved. Your previous image is unchanged. Please retry.'], 500);
}
