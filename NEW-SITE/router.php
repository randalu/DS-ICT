<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

\NewSite\enforce_request();
$path = \NewSite\request_path($_SERVER);
if ($path === null) {
    \NewSite\stop_response(400, 'Invalid preview path.');
}
if ($path === '/media.php') {
    require __DIR__ . '/public/media.php';
    exit;
}
if (str_starts_with($path, '/assets/')) {
    $asset = \NewSite\public_asset($path);
    if ($asset === null) {
        \NewSite\stop_response(404, 'Preview asset not found.');
    }
    header('Content-Type: ' . $asset['type']);
    header('Content-Length: ' . (string) filesize($asset['file']));
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'HEAD') {
        readfile($asset['file']);
    }
    exit;
}
// Never return false: the development server must not serve arbitrary files.
if ($path !== '/index.php' && !isset(\NewSite\route_manifest()[$path]) && preg_match('~(?:\.[a-z0-9]+|/(?:app|fixtures|templates|tests|output)(?:/|$))~', $path)) {
    \NewSite\stop_response(404, 'Preview resource not found.');
}
require __DIR__ . '/public/index.php';
