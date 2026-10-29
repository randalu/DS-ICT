<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
if (!admin_check()) { redirect('admin/login.php?return=quotations'); }
require_once __DIR__ . '/../includes/quotations.php';
if (!quote_ready()) { redirect('admin/quotations.php'); }
try { $row = quote_get((int) get_input('id', 0)); } catch (InvalidArgumentException) { http_response_code(404); exit('Quotation not found.'); }
$adminTitle = $row['reference']; $adminPage = 'quotations';
$revisions = fetch_all('SELECT id,revision,state FROM quotation_revisions WHERE quotation_id=:id ORDER BY revision DESC', ['id' => $row['quotation_id']]);
$history = fetch_all('SELECT action,created_at,admin_id FROM quotation_events WHERE revision_id=:id ORDER BY id DESC LIMIT 30', ['id' => $row['id']]);
require __DIR__ . '/_top.php'; ?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/quotations.css')) ?>">
<section class="quote-shell" id="quote-editor"><a href="<?= e(base_url('admin/quotations.php')) ?>">← All quotations</a>
<div class="quote-heading"><div><p class="quote-eyebrow"><?= e($row['reference']) ?> · REVISION <?= (int) $row['revision'] ?></p><h1><?= $row['state'] === 'draft' ? 'Build their journey' : e(ucfirst($row['state'])) . ' quotation' ?></h1><span id="save-state" role="status">Saved</span></div>
<div class="quote-actions"><button class="btn btn-outline-dark" data-command="duplicate">Duplicate</button><?php if ($row['state'] !== 'draft'): ?><button class="btn btn-success" data-command="revise">Create editable revision</button><?php endif; ?></div></div>
<div id="quote-message" role="alert" tabindex="-1"></div><div id="quote-recovery" hidden class="alert alert-warning">Unsaved entries from this tab are available. <button type="button" class="btn btn-sm btn-outline-dark" id="restore-draft">Restore unsaved edits</button> <button type="button" class="btn btn-sm btn-link" id="discard-recovery">Discard recovery copy</button></div>
<nav class="quote-steps" aria-label="Quotation steps"><?php foreach (['Customer & trip','Days & experiences','Hotels & prices','Inclusions & wording','Review & issue'] as $index => $label): ?><button type="button" data-step="<?= $index + 1 ?>"><span><?= $index + 1 ?></span><?= e($label) ?></button><?php endforeach; ?></nav>
<form id="quote-form" novalidate><fieldset id="quote-fields" <?= $row['state'] !== 'draft' ? 'disabled' : '' ?>><div id="quote-step-body"></div></fieldset></form>
<div class="quote-nav"><button type="button" class="btn btn-outline-dark" id="quote-back">← Back</button><button type="button" class="btn btn-outline-dark" id="quote-save" <?= $row['state'] !== 'draft' ? 'hidden' : '' ?>>Save draft</button><button type="button" class="btn btn-success" id="quote-next">Next →</button></div>
<section id="quote-review-actions" hidden class="quote-review-actions"><button class="btn btn-outline-dark" id="preview-pdf">Preview PDF</button>
<?php if ($row['state'] === 'draft'): ?><button class="btn btn-success" id="issue-quote">Issue quotation</button><?php else: ?><button class="btn btn-success" id="share-pdf">Share PDF</button><button class="btn btn-outline-dark" id="download-pdf">Download PDF</button><button class="btn btn-outline-dark" id="copy-message">Copy message</button><a class="btn btn-outline-success" id="open-whatsapp" target="_blank" rel="noopener">Open WhatsApp</a><?php if ($row['state'] === 'issued'): ?><a class="btn btn-dark" href="<?= e(base_url('admin/quotation_booking.php?id=' . $row['id'])) ?>">Review booking conversion</a><button class="btn btn-outline-danger" data-command="withdraw">Withdraw revision</button><?php endif; ?><?php endif; ?>
</section><div id="pdf-panel" hidden><h2>PDF preview</h2><a id="pdf-open" target="_blank" rel="noopener">Open PDF in a new tab</a><iframe id="pdf-frame" title="Quotation PDF preview"></iframe></div>
<details class="mt-4"><summary>Revisions and recent activity</summary><div class="d-flex flex-wrap gap-3 my-2"><?php foreach ($revisions as $revision): ?><a href="?id=<?= (int) $revision['id'] ?>">R<?= (int) $revision['revision'] ?> · <?= e($revision['state']) ?></a><?php endforeach; ?></div><ul><?php foreach ($history as $event): ?><li><?= e($event['created_at']) ?> · <?= e(str_replace('_', ' ', $event['action'])) ?> · Admin <?= (int) $event['admin_id'] ?></li><?php endforeach; ?></ul></details>
</section>
<dialog id="quote-picker"><div class="quote-picker-heading"><h2 id="picker-title">Choose reusable content</h2><button type="button" class="btn btn-outline-dark" id="picker-close">Close</button></div><label for="picker-search">Search</label><input id="picker-search" class="form-control" type="search" autocomplete="off"><div id="picker-results" aria-live="polite"></div></dialog>
<script type="application/json" id="quote-config"><?= json_encode(['csrf' => csrf_token(), 'api' => base_url('admin/quotation_api.php'), 'pdf' => base_url('admin/quotation_pdf.php'), 'edit' => base_url('admin/quotation_edit.php'), 'mediaApi' => base_url('admin/media_picker_api.php'), 'base' => rtrim(APP_URL, '/') . '/', 'admin' => admin_id(), 'id' => (int) $row['id'], 'version' => (int) $row['lock_version'], 'step' => (int) $row['step'], 'state' => $row['state'], 'reference' => $row['reference'], 'revision' => (int) $row['revision'], 'document' => $row['document']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= e(base_url('assets/js/quotation-common.js')) ?>" defer></script><script src="<?= e(base_url('assets/js/quotation-editor.js')) ?>" defer></script>
<?php require __DIR__ . '/_bottom.php'; ?>
