<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/site_audit_portal.php';
require_once __DIR__ . '/../includes/admin_openai.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function audit_reply(array $data, int $status = 200): never { http_response_code($status); echo audit_json($data); exit; }
if (!admin_check()) { audit_reply(['error' => 'Your session expired. Sign in again.'],401); }
try {
    admin_openai_reject_credentials($_GET);
    admin_openai_reject_credentials($_POST);
    foreach (array_merge($_GET,$_POST) as $value) { if (!is_string($value)) { throw new InvalidArgumentException('Invalid request values.'); } }
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    if (!audit_portal_ready()) { audit_reply(['error' => 'Apply the reviewed site-audit portal/progress migrations. No schema is created by this page.'],503); }
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'history') {
            audit_reply(['runs' => fetch_all('SELECT r.*,s.profile,s.coverage_json FROM site_audit_runs r LEFT JOIN site_audit_snapshots s ON s.run_id=r.id ORDER BY r.id DESC LIMIT 100')]);
        }
        $runId = max(0, (int) ($_GET['run_id'] ?? 0));
        if ($action === 'report' || $action === 'export') {
            $run = fetch_one('SELECT * FROM site_audit_runs WHERE id=:run', ['run' => $runId]);
            if (!$run) { audit_reply(['error' => 'Run not found.'],404); }
            $snapshot = audit_snapshot($runId); $issues = audit_issue_rows($runId);
            if ($action === 'export') {
                header('Content-Disposition: attachment; filename="site-audit-' . $runId . (($_GET['format'] ?? '') === 'csv' ? '.csv' : '.json') . '"');
                if (($_GET['format'] ?? '') === 'csv') {
                    header('Content-Type: text/csv; charset=utf-8'); $out = fopen('php://output','w');
                    $columns = ['id','severity','type','certainty','page_url','target_url','source_location','detail','triage_state','reason','observed_at'];
                    fputcsv($out,$columns,',','"','');
                    foreach ($issues as $row) { fputcsv($out,array_map(static function($key) use($row) { $value = (string) ($row[$key] ?? ''); return preg_match('/^[\s]*[=+@-]/u',$value) ? "'" . $value : $value; },$columns),',','"',''); } fclose($out); exit;
                }
                audit_reply(['run' => $run,'coverage' => $snapshot['coverage'],'issues' => $issues,'comparison' => audit_compare($runId)]);
            }
            $severity = $_GET['severity'] ?? ''; $certainty = $_GET['certainty'] ?? ''; $state = $_GET['state'] ?? ''; $query = mb_strtolower(mb_substr($_GET['q'] ?? '',0,100));
            $filtered = array_values(array_filter($issues,static fn($row) => (!$severity || $row['severity'] === $severity) && (!$certainty || $row['certainty'] === $certainty) && (!$state || $row['triage_state'] === $state) && (!$query || str_contains(mb_strtolower($row['page_url'] . ' ' . $row['type'] . ' ' . $row['detail'] . ' ' . ($row['target_url'] ?? '')),$query))));
            $groups = audit_issue_groups($issues);
            $page = max(1,min(100000,(int) ($_GET['page'] ?? 1))); $counts = array_count_values(array_column($issues,'severity'));
            $observationPage = max(1,min(100000,(int) ($_GET['observation_page'] ?? 1))); $offset = ($observationPage - 1) * 100;
            $observations = fetch_all('SELECT kind,page_url,data_json,observed_at FROM site_audit_observations WHERE run_id=:run ORDER BY id LIMIT 101 OFFSET ' . $offset,['run' => $runId]);
            $moreObservations = count($observations)>100; $observations=array_slice($observations,0,100);
            $reviews = fetch_all('SELECT id,model,result_json,created_at FROM site_audit_reviews WHERE run_id=:run ORDER BY id DESC LIMIT 20',['run' => $runId]);
            audit_reply(['run' => $run, 'coverage' => $snapshot['coverage'], 'inventory_notices' => $snapshot['inventory']['notices'], 'counts' => $counts,
                'affected_pages' => count(array_unique(array_column($issues,'page_url'))), 'groups'=>array_slice($groups,0,30), 'group_count'=>count($groups), 'issues' => array_slice($filtered,($page-1)*100,100), 'filtered_count' => count($filtered), 'page' => $page,
                'observations' => $observations, 'more_observations' => $moreObservations, 'reviews' => $reviews,'comparison' => audit_compare($runId)]);
        }
        throw new InvalidArgumentException('Unknown read action.');
    }
    if (!is_post()) { header('Allow: GET, POST'); audit_reply(['error' => 'Use POST for changes.'],405); }
    guard_post_size();
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 50000) { audit_reply(['error' => 'Request is too large.'],413); }
    if (!csrf_verify($_POST['_token'] ?? null)) { audit_reply(['error' => 'Invalid security token. Reload first.'],419); }
    $input = json_decode($_POST['payload'] ?? '{}',true,16,JSON_THROW_ON_ERROR);
    if (!is_array($input) || ($input && array_is_list($input))) { throw new InvalidArgumentException('Invalid payload.'); }
    admin_openai_reject_credentials($input);
    foreach (['fingerprint','state','reason','context_hash','request_token'] as $field) {
        if (isset($input[$field]) && !is_string($input[$field])) { throw new InvalidArgumentException('Invalid text fields.'); }
    }
    if (isset($input['run_id']) && (!is_int($input['run_id']) || $input['run_id']<=0)) { throw new InvalidArgumentException('Invalid run identifier.'); }
    if ($action === 'triage') {
        audit_triage((string) ($input['fingerprint'] ?? ''),(string) ($input['state'] ?? ''),(string) ($input['reason'] ?? ''),(int) admin_id()); audit_reply(['saved' => true]);
    }
    if ($action === 'preflight') { audit_reply(audit_preflight()); }
    if (in_array($action,['context','review'],true)) {
        $runId = (int) ($input['run_id'] ?? 0); $ids = $input['ids'] ?? [];
        if (!is_array($ids) || !array_is_list($ids)) { throw new InvalidArgumentException('Invalid finding selection.'); }
        $run = fetch_one('SELECT status FROM site_audit_runs WHERE id=:run',['run'=>$runId]);
        if (!$run || $run['status'] !== 'done') { throw new InvalidArgumentException('Wait for the technical run to finish before AI review.'); }
        $preview = audit_ai_context($runId,$ids);
        if ($action === 'context') { audit_reply($preview); }
        if (!is_string($input['context_hash'] ?? null) || !hash_equals($preview['hash'],$input['context_hash'])) { throw new DomainException('The selected evidence changed. Preview it again.'); }
        audit_reply(audit_run_review($runId,$ids,$preview['hash'],(string) ($input['request_token'] ?? '')));
    }
    throw new InvalidArgumentException('Unknown action.');
} catch (DomainException $e) { audit_reply(['error'=>$e->getMessage()],409); }
catch (InvalidArgumentException|JsonException|TypeError $e) { audit_reply(['error'=>$e instanceof TypeError ? 'Invalid request values.' : $e->getMessage()],422); }
catch (RuntimeException $e) { audit_reply(['error'=>preg_match('/^(OpenAI|The model|Connect|Daily)/',$e->getMessage()) ? $e->getMessage() : 'The operation could not finish. Technical findings and page content were preserved.'],502); }
catch (Throwable) { audit_reply(['error'=>'Audit data is temporarily unavailable. No automatic paid retry was made.'],503); }
