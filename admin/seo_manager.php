<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/seo_manager.php';
if (!admin_check()) { redirect('admin/login.php?return=seo_manager'); }
header('Cache-Control: no-store');
$ready = seo_ready();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>SEO Manager | <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(base_url('assets/css/seo-manager.css')) ?>">
<link rel="stylesheet" href="<?= e(base_url('assets/css/typography.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/typography.css')) ?>"></head>
<body class="seo-page"><main id="seo-manager" data-ready="<?= $ready ? '1' : '0' ?>" data-api="<?= e(base_url('admin/seo_manager_api.php')) ?>" data-csrf="<?= e(csrf_token()) ?>">
<header class="seo-header"><div><a href="<?= e(base_url('admin/dashboard.php')) ?>">← Admin</a><h1>SEO Manager</h1><p>Useful content. Clear search intent. Changes you control.</p></div><span class="seo-badge"><?= e(admin_openai_model('seo')) ?></span></header>
<?php if (!$ready): ?><p class="seo-notice">The SEO workspace migration has not been applied. Content lists are available; editing and AI actions will unlock after the owner applies the reviewed migration. No schema is created here.</p><?php endif; ?>
<p id="seo-message" class="seo-notice" role="status" aria-live="polite" hidden></p>
<nav class="seo-tabs" aria-label="SEO workspace"><button type="button" data-tab="content" aria-current="page">Content</button><button type="button" data-tab="research">Research</button><button type="button" data-tab="principles">Principles</button><button type="button" data-tab="connection">Connection &amp; Usage</button></nav>
<section data-panel="content"><h2 class="seo-sr">Content</h2><div class="seo-layout"><aside class="seo-card">
<form id="seo-filter"><label>Content type<select name="type"><?php foreach (seo_types() as $type => $def): ?><option value="<?= e($type) ?>"><?= e($def['label']) ?></option><?php endforeach; ?></select></label>
<label>Search<input name="q" type="search" maxlength="100" placeholder="Search titles"></label>
<div class="seo-grid"><label>Status<select name="published"><option value="">All</option><option value="1">Published</option><option value="0">Draft</option></select></label>
<label>SEO review<select name="review"><option value="">Any date</option><option value="never">Never reviewed</option><option value="old">Over 30 days</option></select></label></div>
<label class="seo-check"><input type="checkbox" name="missing" value="1"> Missing metadata</label><button>Find content</button></form>
<div id="seo-list" aria-live="polite"></div><div class="seo-actions"><button type="button" id="seo-prev">Previous</button><span id="seo-page-number"></span><button type="button" id="seo-next">Next</button></div></aside>
<section class="seo-card" id="seo-editor"><h2>Select a page</h2><p>Choose a destination, itinerary, package, activity page or blog post to review its SEO.</p></section></div></section>
<section data-panel="research" class="seo-card" hidden><h2>Research</h2><p>Research runs only when requested. Sources and dates stay visible. Keyword targets are suggestions; demand and performance are unmeasured.</p>
<div class="seo-actions"><button type="button" id="seo-platform-research">Refresh SEO guidance</button><button type="button" id="seo-page-research">Research selected page’s keywords</button></div>
<p>Each new research brief uses web search followed by structured extraction. A paid request may be billed even if your browser disconnects. No automatic retries.</p><div id="seo-research-output"></div></section>
<section data-panel="principles" class="seo-card" hidden><h2>Principles</h2><p>Core policy <?= e(SEO_POLICY_VERSION) ?> applies to every generation. Editorial guidance cannot override it.</p><details><summary>Read mandatory core rules</summary><pre class="seo-wrap"><?= e(seo_core_rules()) ?></pre></details>
<form id="seo-guidance"><h3>Editorial guidance</h3><?php foreach (['tone' => 'Tone', 'audience' => 'Audience', 'terminology' => 'Preferred terminology', 'verified_facts' => 'Verified business facts', 'prohibited_claims' => 'Additional prohibited claims'] as $key => $label): ?><label><?= e($label) ?><textarea name="<?= e($key) ?>" rows="3" maxlength="3000"></textarea></label><?php endforeach; ?>
<button type="button" id="seo-guidance-preview">Preview guidance</button><pre id="seo-guidance-preview-text" class="seo-wrap"></pre><button>Save new guidance version</button></form></section>
<section data-panel="connection" class="seo-card" hidden><h2>Connection &amp; Usage</h2><p id="seo-connection-status">Checking server configuration...</p>
<p>One server-configured OpenAI key serves Image Manager, SEO Manager and Site Audit. The key is never accepted from or returned to the browser. Manual editing remains available without it.</p>
<div class="seo-actions"><button type="button" id="seo-test">Run paid connection test</button><button type="button" id="seo-refresh-status">Refresh status</button></div>
<p>Only public page context and editorial guidance are sent to OpenAI. Do not include private customer information. Provider retention rules still apply when response storage is disabled.</p><pre id="seo-usage" class="seo-wrap"></pre></section>
</main><script src="<?= e(base_url('assets/js/seo-ai.js')) ?>" defer></script><script src="<?= e(base_url('assets/js/seo-manager.js')) ?>?v=<?= (int) filemtime(__DIR__ . '/../assets/js/seo-manager.js') ?>" defer></script></body></html>
