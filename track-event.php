<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 65536) {
    http_response_code(413);
    exit;
}

$payload = null;
$raw = file_get_contents('php://input', false, null, 0, 65536);
if (is_string($raw) && trim($raw) !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

if (!is_array($payload)) {
    $payload = $_POST;
}

$token = $payload['_token'] ?? null;
if (!csrf_verify(is_string($token) ? $token : null)) {
    http_response_code(419);
    exit;
}

$eventType = strtolower(trim((string) ($payload['event_type'] ?? '')));
if (in_array($eventType, ict_analytics_click_keys(), true)) {
    track_analytics_event($eventType);
}

http_response_code(204);
