<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

\NewSite\enforce_request();
$page = \NewSite\build_page(\NewSite\resolve_route($_SERVER, $_GET));
$choice = $_GET['journey'] ?? '0';
if (is_string($choice) && preg_match('/\A[0-9]{1,2}\z/D', $choice) && (int) $choice < count($page['journeys'])) {
    $page['active_journey'] = (int) $choice;
}
http_response_code($page['status']);
header('Content-Type: text/html; charset=UTF-8');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD') {
    exit;
}
$layout = dirname(__DIR__) . '/templates/layout.php';
if (!is_file($layout)) {
    \NewSite\stop_response(503, 'Preview layout is being prepared.');
}
try {
    require $layout;
} catch (\Throwable) {
    \NewSite\stop_response(503, 'Preview rendering unavailable.');
}
