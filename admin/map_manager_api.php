<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/route_map_admin.php';
admin_require_auth();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (!is_post()) { http_response_code(405); header('Allow: POST'); echo '{"error":"Use POST for map previews."}'; exit; }
guard_post_size(); require_csrf();
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 150000) { http_response_code(413); echo '{"error":"Map form is too large."}'; exit; }
try {
    $type = route_map_text(post_input('type', ''), 20);
    $id = route_map_integer(post_input('id', ''), 1, PHP_INT_MAX);
    $record = route_map_record($type, $id);
    if (!route_map_available()) { throw new InvalidArgumentException('Apply the Route Maps migration before previewing routes.'); }
    $schedule = route_map_schedule($type, $record);
    $locations = route_map_locations();
    $days = route_map_normalize_days(post_input('map_days'), $schedule, $locations, false);
    $html = route_map_card(['ready' => false, 'document' => ['days' => $days], 'locations' => $locations], null, true);
    echo json_encode(['html' => $html], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException | JsonException $error) {
    http_response_code(422); echo json_encode(['error' => $error->getMessage()]);
} catch (Throwable) {
    http_response_code(503); echo '{"error":"Map preview is unavailable. Your entries are preserved."}';
}
