<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/package_fields.php';
admin_require_auth();
header('Cache-Control: no-store'); header('X-Robots-Tag: noindex, nofollow');
$adminTitle = 'Package field review'; $adminPage = 'site_audit';
$type = (string) get_input('type', 'itinerary'); $id = (int) get_input('id', 0);
$inventory = null; $error = '';
try { if ($id > 0) { $inventory = package_field_inventory($type, $id); } }
catch (InvalidArgumentException $e) { $error = $e->getMessage(); }
require __DIR__ . '/_top.php';
?>
<h1 class="h3">Package fields and editor coverage</h1>
<p>Review every stored package field, child section and relationship. Use the full editor for locations, prices, schedules, links and publication; SEO Manager for selected wording; Image Manager for photos and image descriptions. System IDs and timestamps are read-only.</p>
<?php if ($error): ?><p role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if (!$inventory): ?>
<?php foreach (package_tables() as $key => $table): if (!db_table_exists($table)) { continue; } ?>
<h2 class="h5"><?= e(ucwords(str_replace('_', ' ', $key))) ?></h2><ul>
<?php foreach (fetch_all("SELECT id,name,is_published FROM $table ORDER BY name LIMIT 10000") as $row): ?><li><a href="<?= e(base_url('admin/package_fields.php?type=' . $key . '&id=' . (int) $row['id'])) ?>"><?= e($row['name']) ?></a> · <?= $row['is_published'] ? 'Published' : 'Draft' ?></li><?php endforeach; ?></ul>
<?php endforeach; ?>
<?php else: ?>
<h2 class="h4"><?= e($inventory['record']['name']) ?></h2><nav class="d-flex gap-3 flex-wrap mb-3" aria-label="Package editors">
<?php foreach (package_editor_links($type, $id) as $label => $url): ?><a href="<?= e($url) ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
<?php foreach ($inventory as $group => $values): ?><details open class="card mb-3"><summary class="card-header"><?= e(ucwords($group)) ?></summary><div class="card-body">
<?php if (!$values): ?><p>No entries.</p><?php else: ?>
<div class="table-responsive"><table class="table"><thead><tr><th>Field / section</th><th>Stored value</th></tr></thead><tbody>
<?php foreach ($values as $field => $value): ?><tr><th scope="row"><?= e((string) $field) ?></th><td><pre style="white-space:pre-wrap;overflow-wrap:anywhere;min-width:0;max-width:70ch"><?= e(is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ($value === null ? '(not set)' : (string) $value)) ?></pre></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></div></details><?php endforeach; ?>
<?php endif; require __DIR__ . '/_bottom.php'; ?>
