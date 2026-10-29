<?php
declare(strict_types=1);
// Local fixture router is inaccessible unless explicitly started for its named synthetic schema.
if (PHP_SAPI!=='cli-server' || getenv('DB_NAME')!=='ict_audit_portal_rehearsal' || !in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)) {http_response_code(404);exit;}
$root=dirname(__DIR__,2);$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if ($path==='/accuracy-fixture') {
    header('Content-Type: text/html;charset=utf-8');
    echo '<!doctype html><html><head><title>Audit accuracy fixture</title><style>.d-none{display:none}.invisible{visibility:hidden}</style></head><body><main><h1>Visibility checks</h1><input id="visible-unlabelled"><label for="labelled">Labelled input</label><input id="labelled"><input class="d-none" id="honeypot" aria-hidden="true" tabindex="-1"><div hidden><input id="hidden-parent"></div><div class="invisible"><input id="invisible-parent"></div><input type="hidden" id="hidden-type"><script type="application/ld+json">{"@type":"Article","headline":"Fixture","mainEntityOfPage":{"@type":"WebPage","@id":"http://127.0.0.1:8146/accuracy-fixture"}}</script></main></body></html>';return;
}
if ($path==='/__audit_fixture') {
    require_once $root.'/includes/auth.php';
    $row=fetch_one('SELECT id,password_hash FROM admins WHERE id=101');session_regenerate_id(true);
    $_SESSION[ADMIN_SESSION_KEY]=101;$_SESSION[ADMIN_CREDENTIAL_STAMP_KEY]=admin_credential_stamp(101,$row['password_hash']);
    echo 'Synthetic session ready';return;
}
if (preg_match('~^/admin/(site_audit(?:_api|_run|_results)?|seo_manager(?:_api)?)\.php$~',$path) || preg_match('~^/assets/(css|js)/[a-z0-9-]+\.(css|js)$~',$path)) {return false;}
if ($path==='/robots.txt') {header('Content-Type:text/plain');echo "User-agent: *\nDisallow: /admin\nDisallow: /account\nDisallow: /blocked\n";return;}
if ($path==='/sitemap_index.php') {header('Content-Type:application/xml');echo '<urlset><url><loc>http://127.0.0.1:8146/</loc></url><url><loc>http://127.0.0.1:8146/destinations/audit-fixture</loc></url></urlset>';return;}
if ($path==='/assets/images/placeholders/placeholder-destination.svg' || $path==='/good.svg') {header('Content-Type:image/svg+xml');echo '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200"><rect width="400" height="200" fill="#46776a"/></svg>';return;}
if ($path==='/blocked' || $path==='/limited') {http_response_code(429);echo 'Rate limited';return;}
if ($path==='/redirect') {header('Location:/destinations/audit-fixture');return;}
if (!in_array($path,['/','/destinations/audit-fixture','/discovered','/soft404'],true)) {http_response_code(404);echo 'Not found';return;}
header('Content-Type:text/html;charset=utf-8');
?><!doctype html><html><head><title><?= $path==='/soft404'?'Page not found':'Synthetic private travel' ?></title><meta name="description" content="A complete synthetic description for private travel audit test pages."><link rel="canonical" href="http://127.0.0.1:8146<?= htmlspecialchars($path) ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><meta property="og:image" content="/good.svg"><script type="application/ld+json">{broken json</script></head>
<body><main><h1>Synthetic private travel</h1><p>This is a public fixture describing a private trip. It contains enough visible text to exercise page discovery, image reconciliation and metadata checks without any customer data.</p><img src="/assets/images/placeholders/placeholder-destination.svg" width="400" height="200"><img src="/good.svg" srcset="/good.svg 1x, /missing-variant.svg 2x" loading="lazy" alt="Synthetic green rectangle"><div style="width:600px">Overflow fixture</div><a href="/destinations/audit-fixture#absent">Trip details</a><a href="/missing">Missing route</a><a href="/limited">Temporarily limited</a><a href="/blocked">Robots excluded</a><a href="/discovered">Discovered public route</a><a href="/soft404">Soft error</a><a href="/destinations/unpublished-fixture">Unpublished link</a><a href="/admin/danger.php">Excluded admin route</a><a href="/account.php">Excluded account</a><a href="/redirect">Redirect</a></main></body></html>
