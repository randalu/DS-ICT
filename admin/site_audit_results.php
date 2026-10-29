<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/site_audit.php';

admin_require_auth();

header('Content-Type: application/json; charset=utf-8');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

guard_post_size();
require_csrf();

$runId = (int) post_input('run_id', 0);
if ($runId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'run_id required']);
    exit;
}

try {
    echo json_encode(site_audit_poll_result($runId));
} catch (OutOfBoundsException) {
    http_response_code(404);
    echo json_encode(['error' => 'Run not found']);
} catch (InvalidArgumentException) {
    http_response_code(400);
    echo json_encode(['error' => 'run_id required']);
} catch (Throwable) {
    http_response_code(503);
    echo json_encode(['error' => 'Audit results are temporarily unavailable.']);
}
