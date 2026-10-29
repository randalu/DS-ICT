<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quotation_pdf.php';
require_once __DIR__ . '/../includes/quotation_booking.php';
header('Cache-Control: no-store'); header('X-Robots-Tag: noindex, nofollow'); header('Content-Type: application/json; charset=utf-8');
if (!admin_check()) { http_response_code(401); echo quote_json(['error' => 'Your admin session expired. Sign in again; your entries are retained.']); exit; }
admin_require_auth();
if (!quote_ready()) { http_response_code(503); echo quote_json(['error' => 'Quotation migration has not been installed.']); exit; }
$admin = (int) admin_id();
try {
    $action = (string) ($_GET['action'] ?? '');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && $action === 'lookup') {
        $kind = (string) ($_GET['kind'] ?? 'activity'); $search = quote_text((string) ($_GET['q'] ?? ''), 190); $items = [];
        if (in_array($kind, ['activity', 'day', 'hotel', 'defaults'], true)) {
            foreach (fetch_all('SELECT id,name,payload_json,lock_version FROM quotation_library WHERE kind=:kind AND name LIKE :q ORDER BY name LIMIT 20', ['kind' => $kind, 'q' => '%' . $search . '%']) as $item) { $items[] = ['source' => 'library', 'id' => (int) $item['id'], 'name' => $item['name'], 'version' => (int) $item['lock_version'], 'payload' => json_decode($item['payload_json'], true, 64, JSON_THROW_ON_ERROR)]; }
        }
        $table = match ($kind) { 'destination' => 'destinations', 'activity' => 'experiences', 'media' => 'media_assets', default => null };
        if ($table && db_table_exists($table)) {
            $column = $kind === 'destination' ? 'name' : ($kind === 'media' ? 'file_name' : 'title');
            $lookupId = quote_int($_GET['id'] ?? 0, 0, PHP_INT_MAX);
            $predicate = $lookupId ? 'id=:id' : $column . ' LIKE :q'; $lookupParams = $lookupId ? ['id' => $lookupId] : ['q' => '%' . $search . '%'];
            foreach (fetch_all("SELECT * FROM {$table} WHERE {$predicate} ORDER BY id DESC LIMIT 20", $lookupParams) as $item) {
                $path = $kind === 'media' ? (string) $item['file_path'] : content_image_path($item);
                if ($kind === 'media' && !preg_match('/\.(jpe?g|png|webp)$/i', $path)) { continue; }
                $items[] = ['source' => $kind === 'activity' ? 'website' : $kind, 'id' => (int) $item['id'], 'name' => $item[$column], 'version' => 0, 'payload' => ['name' => $item[$column], 'description' => strip_tags((string) ($item['excerpt'] ?? '')), 'image' => $path, 'status' => ['A' => 'included', 'B' => 'included']]];
            }
        }
        echo quote_json(['items' => $items]); exit;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); header('Allow: POST, GET'); echo quote_json(['error' => 'Use a supported request method.']); exit; }
    guard_post_size();
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 1000000) { throw new InvalidArgumentException('Quotation request is too large.'); }
    $data = json_decode((string) file_get_contents('php://input'), true, 64, JSON_THROW_ON_ERROR);
    if (!is_array($data)) { throw new InvalidArgumentException('Invalid request.'); }
    $_POST['_token'] = $data['csrf_token'] ?? ''; require_csrf();
    $action = $data['action'] ?? ''; $id = quote_int($data['id'] ?? 0, 0, PHP_INT_MAX);
    $result = match ($action) {
        'create' => ['id' => quote_create_from_inquiry($admin, quote_int($data['inquiry_id'] ?? 0, 0, PHP_INT_MAX))],
        'save' => quote_save($id, quote_int($data['version'] ?? 0, 1, PHP_INT_MAX), quote_int($data['step'] ?? 1, 1, 5), $data['document'] ?? [], $admin),
        'issue' => quote_issue($id, quote_int($data['version'] ?? 0, 1, PHP_INT_MAX), $admin),
        'duplicate' => ['id' => quote_create($admin, array_replace(quote_get($id)['document'], ['inquiry_id' => 0]))],
        'revise' => ['id' => quote_revise($id, $admin)],
        'library_save' => ['id' => quote_library_save((string) ($data['kind'] ?? ''), (string) ($data['name'] ?? ''), $data['payload'] ?? [], quote_int($data['version'] ?? 0, 0, PHP_INT_MAX))],
        'convert' => ['plan_id' => quote_convert($id, (string) ($data['option'] ?? ''), $admin, quote_int($data['customer_id'] ?? 0, 0, PHP_INT_MAX), ($data['replace_reviewed'] ?? false) === true)],
        'guest_accept' => ['acceptance_id' => quote_guest_accept(quote_int($data['plan_id'] ?? 0, 1, PHP_INT_MAX), $data['evidence'] ?? [], $admin)],
        'confirm' => ['evidence_id' => ict_confirm_booking(quote_int($data['plan_id'] ?? 0, 1, PHP_INT_MAX), $admin, $data['evidence'] ?? [])],
        'withdraw' => ict_confirmation_atomic(static function () use ($id, $data, $admin): array {
            $row = quote_get($id, true);
            if ($row['state'] !== 'issued' || (int) $row['lock_version'] !== (int) ($data['version'] ?? 0)) { throw new DomainException('Reload the current issued revision.'); }
            execute_query("UPDATE quotation_revisions SET state='withdrawn',withdrawn_at=:now,lock_version=lock_version+1 WHERE id=:id", ['now' => quote_now(), 'id' => $id]); quote_event($id, $admin, 'withdrawn'); return ['id' => $id];
        }),
        'share_attempt' => (static function () use ($id, $admin): array { quote_get($id); quote_event($id, $admin, 'sharing_attempt'); return ['recorded' => true]; })(),
        default => throw new InvalidArgumentException('Unknown quotation action.'),
    };
    echo quote_json($result);
} catch (DomainException $e) { http_response_code(409); echo quote_json(['error' => $e->getMessage()]); }
catch (InvalidArgumentException | JsonException $e) { http_response_code(422); echo quote_json(['error' => $e->getMessage()]); }
catch (Throwable $e) { http_response_code(500); error_log('Quotation operation failed: ' . get_class($e)); echo quote_json(['error' => 'The operation could not be completed. Your draft entries are retained. Check migration, storage and PDF dependencies.']); }
