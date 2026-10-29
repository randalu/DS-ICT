<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

\NewSite\enforce_request();
$path = $_GET['path'] ?? null;
$image = is_string($path) ? \NewSite\source_image($path) : null;
if ($image === null) {
    \NewSite\stop_response(404, 'Preview image not found.');
}
header('Content-Type: ' . $image['type']);
header('Content-Length: ' . (string) filesize($image['file']));
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'HEAD') {
    readfile($image['file']);
}
