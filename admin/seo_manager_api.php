<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/seo_generation.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function seo_reply(array $data, int $status = 200): never
{
    http_response_code($status); echo seo_json($data); exit;
}
if (!admin_check()) { unset($_SESSION['_ict_seo_connection']); seo_reply(['error' => 'Your session expired. Sign in again.', 'login' => base_url('admin/login.php?return=seo_manager')], 401); }
admin_require_auth();
try {
    admin_openai_reject_credentials($_GET);
    admin_openai_reject_credentials($_POST);
    foreach ($_GET as $value) { if (!is_string($value)) { throw new InvalidArgumentException('Invalid query fields.'); } }
    if (isset($_POST['action']) && !is_string($_POST['action'])) { throw new InvalidArgumentException('Invalid action.'); }
    $action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'list') { seo_reply(seo_list($_GET)); }
        seo_require_ready();
        if ($action === 'load') { seo_reply(['record' => seo_present((string) ($_GET['type'] ?? ''), (int) ($_GET['id'] ?? 0))]); }
        if ($action === 'status') { seo_reply(['usage' => seo_usage((int) admin_id()), 'guidance' => seo_guidance(), 'policy' => seo_core_rules(), 'policy_version' => SEO_POLICY_VERSION,
            'brief' => seo_latest_brief('guidance', '', 0, 'International')]); }
        throw new InvalidArgumentException('Unknown read action.');
    }
    if (!is_post()) { header('Allow: GET, POST'); seo_reply(['error' => 'Use POST for changes.'], 405); }
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 250000) { seo_reply(['error' => 'Request is too large.'], 413); }
    guard_post_size();
    if (!csrf_verify(is_string($_POST['_token'] ?? null) ? $_POST['_token'] : null)) { seo_reply(['error' => 'Invalid or expired security token. Reload first.'], 419); }
    require_csrf();
    foreach ($_POST as $value) { if (!is_string($value)) { throw new InvalidArgumentException('Invalid request fields.'); } }
    seo_require_ready();
    $input = isset($_POST['payload']) ? json_decode($_POST['payload'], true, 32, JSON_THROW_ON_ERROR) : [];
    if (!is_array($input) || array_is_list($input) && $input !== []) { throw new InvalidArgumentException('Invalid request payload.'); }
    admin_openai_reject_credentials($input);
    $admin = (int) admin_id();
    if (in_array($action,['connect','disconnect'],true) || isset($_POST['key']) || isset($input['key'])) { throw new InvalidArgumentException('OpenAI credentials are configured on the server only.'); }
    foreach (['type','field','selection','existing','market','kind','request_token'] as $key) {
        if (isset($input[$key]) && !is_string($input[$key])) { throw new InvalidArgumentException('Invalid text input.'); }
    }
    foreach (['id','revision_id','revision'] as $key) {
        if (isset($input[$key]) && !is_scalar($input[$key])) { throw new InvalidArgumentException('Invalid identifier.'); }
    }
    if ($action === 'save') { seo_reply(['record' => seo_save((string) ($input['type'] ?? ''), (int) ($input['id'] ?? 0), (string) ($input['revision'] ?? ''), (array) ($input['values'] ?? []), isset($input['plan']) ? (array) $input['plan'] : null, $admin, (array) ($input['suggestions'] ?? []))]); }
    if ($action === 'restore') { seo_reply(['record' => seo_restore((string) ($input['type'] ?? ''), (int) ($input['id'] ?? 0), (string) ($input['revision'] ?? ''), (int) ($input['revision_id'] ?? 0), $admin)]); }
    if ($action === 'guidance') {
        $values = seo_guidance_validate((array) ($input['values'] ?? []));
        $name = 'ict-seo-guidance-' . substr(hash('sha256', DB_NAME), 0, 32);
        if ((int) fetch_one('SELECT GET_LOCK(:name,0) AS acquired', ['name' => $name])['acquired'] !== 1) { throw new DomainException('Guidance is being edited. Retry shortly.'); }
        try {
            if ((int) ($input['revision'] ?? -1) !== seo_guidance()['id']) { throw new DomainException('Guidance changed. Reload before saving.'); }
            execute_query('INSERT INTO seo_guidance(admin_id,guidance_json,created_at) VALUES(:admin,:guidance,NOW())', ['admin' => $admin, 'guidance' => seo_json($values)]);
        } finally { execute_query('DO RELEASE_LOCK(:name)', ['name' => $name]); }
        seo_reply(['guidance' => seo_guidance()]);
    }
    if (in_array($action, ['generate','research','test'], true)) {
        $kind = $action === 'research' ? 'research' : 'generation';
        $result = seo_paid_request($kind, (string) ($input['request_token'] ?? ''), function ($call, $revalidate) use ($action, $input, $admin): array {
            if ($action === 'test') { $call(['input' => 'Reply OK.']); $revalidate(); return ['notice' => 'OpenAI connection test completed using ' . admin_openai_model('seo') . '.']; }
            return $action === 'generate' ? seo_generate($input, $admin, $call, $revalidate) : seo_research($input, $admin, $call, $revalidate);
        });
        seo_reply(['result' => $result, 'usage' => seo_usage($admin)]);
    }
    throw new InvalidArgumentException('Unknown action.');
} catch (DomainException $e) { seo_reply(['error' => $e->getMessage()], 409); }
catch (InvalidArgumentException|JsonException|TypeError $e) { seo_reply(['error' => $e instanceof TypeError ? 'Invalid request values.' : $e->getMessage()], 422); }
catch (RuntimeException $e) { seo_reply(['error' => str_starts_with($e->getMessage(), 'OpenAI') || str_starts_with($e->getMessage(), 'The model') || str_starts_with($e->getMessage(), 'Research') ? $e->getMessage() : 'The operation could not finish. Current page content was preserved.'], 502); }
catch (Throwable) { seo_reply(['error' => 'The operation failed. Reload and review before retrying.'], 500); }
