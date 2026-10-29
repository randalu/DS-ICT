<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
$server = ['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'GET'];
$home = \NewSite\build_page(\NewSite\resolve_route($server, [], []), []);
$routes = [];
foreach (\NewSite\route_manifest([]) as $path => $entry) {
    $resolved = \NewSite\resolve_route(['REQUEST_URI' => $path], [], []);
    $page = \NewSite\build_page($resolved, []);
    $routes[] = ['path' => $path, 'family' => $page['family'], 'kind' => $page['page_kind'], 'status' => $page['status']];
}
echo json_encode(['mode' => $home['mode'], 'routes' => $routes], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
