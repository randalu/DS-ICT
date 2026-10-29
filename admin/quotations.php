<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
if (!admin_check()) { redirect('admin/login.php?return=quotations'); }
require_once __DIR__ . '/../includes/quotations.php';
$adminTitle = 'Quotations'; $adminPage = 'quotations';
$search = trim((string) get_input('q', '')); $page = max(1, (int) get_input('page', 1)); $rows = []; $total = 0;
if (quote_ready()) {
    $where = '1=1'; $params = [];
    if ($search !== '') {
        $columns = ['q.reference','r.state',"JSON_UNQUOTE(JSON_EXTRACT(r.document_json,'$.customer.name'))","JSON_UNQUOTE(JSON_EXTRACT(r.document_json,'$.customer.phone'))","JSON_UNQUOTE(JSON_EXTRACT(r.document_json,'$.arrival'))","JSON_UNQUOTE(JSON_EXTRACT(r.document_json,'$.departure'))",'CAST(q.inquiry_id AS CHAR)'];
        $terms = []; foreach ($columns as $i => $column) { $terms[] = $column . ' LIKE :s' . $i; $params['s' . $i] = '%' . mb_substr($search, 0, 190) . '%'; } $where = '(' . implode(' OR ', $terms) . ')';
    }
    $join = ' FROM quotations q JOIN quotation_revisions r ON r.quotation_id=q.id AND r.revision=(SELECT MAX(r2.revision) FROM quotation_revisions r2 WHERE r2.quotation_id=q.id) WHERE ' . $where;
    $total = (int) fetch_one('SELECT COUNT(*) AS total' . $join, $params)['total']; $offset = ($page - 1) * 25;
    $rows = fetch_all('SELECT q.reference,q.inquiry_id,r.id,r.revision,r.state,r.updated_at,r.document_json' . $join . ' ORDER BY r.updated_at DESC,r.id DESC LIMIT 25 OFFSET ' . $offset, $params);
}
require __DIR__ . '/_top.php'; ?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/quotations.css')) ?>">
<section class="quote-shell"><div class="quote-heading"><div><p class="quote-eyebrow">ISLAND CULTURE TRAVELS</p><h1>Quotations</h1><p>Shape a journey. Present the options. Keep every agreement together.</p></div>
<div><a class="btn btn-outline-dark" href="<?= e(base_url('admin/quotation_library.php')) ?>">Reusable content & defaults</a> <button class="btn btn-success" id="quote-create" <?= quote_ready() ? '' : 'disabled' ?>>+ Create quotation</button></div></div>
<div id="quote-message" role="status"></div>
<?php if (!quote_ready()): ?><div class="alert alert-warning">Quotation storage is not installed. Apply the reviewed 2026-10-23 quotation migration before creating quotes. No database changes run from this page.</div><?php else: ?>
<form class="quote-search" method="get"><label for="quote-search">Find a quotation</label><div class="d-flex gap-2"><input id="quote-search" class="form-control" name="q" value="<?= e($search) ?>" placeholder="Number, customer, phone, date, status or inquiry"><button class="btn btn-dark">Search</button></div></form>
<div class="quote-records"><?php foreach ($rows as $row): $d = json_decode($row['document_json'], true); ?><a class="quote-record" href="<?= e(base_url('admin/quotation_edit.php?id=' . $row['id'])) ?>"><div><span class="quote-eyebrow"><?= e($row['reference']) ?> · R<?= (int) $row['revision'] ?></span><h2><?= e($d['title']) ?></h2><p><?= e($d['customer']['name'] ?: 'Customer details to complete') ?> · <?= e($d['customer']['phone']) ?></p></div><div><span class="quote-badge"><?= e(ucfirst($row['state'])) ?></span><p><?= e(substr($d['arrival'], 0, 10)) ?> → <?= e(substr($d['departure'], 0, 10)) ?></p><small>Inquiry <?= (int) $row['inquiry_id'] ?: '—' ?></small></div></a><?php endforeach; ?><?php if (!$rows): ?><div class="quote-empty"><h2>Your next journey starts here</h2><p>Create a quotation, add the days, and compare hotel-inclusive and transport-only options.</p></div><?php endif; ?></div>
<nav class="d-flex gap-3 mt-3" aria-label="Quotation pages"><?php if ($page > 1): ?><a href="?q=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">Previous</a><?php endif; ?><span><?= $total ?> quotation(s) · Page <?= $page ?></span><?php if ($page * 25 < $total): ?><a href="?q=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">Next</a><?php endif; ?></nav>
<?php endif; ?></section>
<script type="application/json" id="quote-config"><?= json_encode(['csrf' => csrf_token(), 'api' => base_url('admin/quotation_api.php'), 'edit' => base_url('admin/quotation_edit.php'), 'initialInquiry' => max(0, (int) get_input('inquiry_id', 0))], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= e(base_url('assets/js/quotation-common.js')) ?>" defer></script>
<?php require __DIR__ . '/_bottom.php'; ?>
