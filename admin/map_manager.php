<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/route_map_admin.php';
admin_require_auth();
$adminTitle = 'Map Manager';
$adminPage = 'map_manager';
$type = is_string(get_input('type', 'itinerary')) ? get_input('type', 'itinerary') : '';
$tab = get_input('tab', 'routes');
if (!in_array($tab, ['routes', 'locations', 'design'], true)) { $tab = 'routes'; }
try { $definition = route_map_type($type); } catch (InvalidArgumentException) { http_response_code(404); exit('Package collection not found.'); }
$id = max(0, (int) get_input('id', 0));
$record = null;
if ($id) { try { $record = route_map_record($type, $id); } catch (InvalidArgumentException) { http_response_code(404); exit('Package not found.'); } }
$contextQuery = '&type=' . $type . '&id=' . $id;
require __DIR__ . '/_top.php';
echo route_map_admin_assets();
?>
<p>Create consistent Sri Lanka maps from selected daily destinations. Arrows show travel order; they do not calculate road distances or driving times.</p>
<nav class="d-flex gap-2 flex-wrap mb-4" aria-label="Map Manager sections"><?php foreach (['routes' => 'Package maps', 'locations' => 'Map locations', 'design' => 'Default design'] as $key => $label): ?><a class="btn <?= $tab === $key ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= e(base_url('admin/map_manager.php?tab=' . $key . $contextQuery)) ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?></nav>
<?php if ($tab === 'routes'):
    $packages = db_table_exists($definition['table']) ? fetch_all('SELECT id,name FROM ' . $definition['table'] . ' ORDER BY name,id') : [];
?>
<form method="get" class="row g-3 mb-4"><div class="col-md-4"><label for="map-package-type" class="form-label">Collection</label><select class="form-select" name="type" id="map-package-type" data-map-collection><?php foreach (route_map_types() as $key => $item): ?><option value="<?= e($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= e($item['label']) ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label for="map-package" class="form-label">Package</label><select class="form-select" name="id" id="map-package"><option value="0">Choose a package</option><?php foreach ($packages as $package): ?><option value="<?= (int) $package['id'] ?>" <?= $id === (int) $package['id'] ? 'selected' : '' ?>><?= e($package['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-2 align-self-end"><button class="btn btn-primary" type="submit">Open map</button></div></form>
<?php if ($record): ?><h2 class="h4"><?= e($record['name']) ?></h2><p><a href="<?= e(route_map_editor_url($type, $id)) ?>">Open package daily-plan editor</a></p><?php route_map_admin_form($type, $record); else: ?>
<div style="max-width:340px"><?= route_map_card(['ready' => false]) ?></div><p>Select a package above to configure its route. Until then, its public page uses this default overview.</p>
<?php endif; elseif ($tab === 'locations'):
    $available = route_map_available();
    $locationId = max(0, (int) get_input('location_id', 0));
    $locations = route_map_locations();
    $location = $locations[$locationId] ?? ['name' => '', 'latitude' => '', 'longitude' => '', 'coordinate_source' => '', 'destination_id' => '', 'is_verified' => 0, 'is_active' => 1, 'revision' => 'missing'];
    if ($locationId && !isset($locations[$locationId])) { echo '<p class="alert alert-warning">Location not found. Choose a saved location below.</p>'; $locationId = 0; }
    if (get_input('map_reload', '') === '1') { unset($_SESSION['route_map_location_input']); }
    $old = $_SESSION['route_map_location_input'] ?? null;
    if (is_array($old) && (int) ($old['location_id'] ?? -1) === $locationId) { $location = $old + $location; }
    $destinations = db_table_exists('destinations') ? fetch_all('SELECT id,name,is_published FROM destinations ORDER BY name,id') : [];
    $suggestions = json_decode(file_get_contents(__DIR__ . '/../assets/maps/locations.json'), true, 16, JSON_THROW_ON_ERROR);
?>
<h2 class="h4">Reusable map locations</h2><p>Link a destination record, or save an airport/pickup place without a destination page. Coordinates must be reviewed and verified before a route can be displayed. Editing a referenced location makes its maps require review.</p>
<?php if (!$available): ?><p class="alert alert-warning">Apply the Route Maps migration before adding locations.</p><?php else: ?>
<?php if ($old): ?><p class="alert alert-info">Entered values retained after an unsuccessful save. <a href="<?= e(base_url('admin/map_manager.php?tab=locations&location_id=' . $locationId . '&map_reload=1' . $contextQuery)) ?>">Load saved location</a></p><?php endif; ?>
<form method="post" class="card card-soft route-map-location-form mb-4" action="<?= e(base_url('admin/map_manager_save.php')) ?>" data-map-location-form><div class="card-body">
<h3 class="h5"><?= $locationId ? 'Edit saved location' : 'Add location' ?></h3>
<?= csrf_field() ?><input type="hidden" name="action" value="location"><input type="hidden" name="type" value="<?= e($type) ?>"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="location_id" value="<?= $locationId ?>"><input type="hidden" name="revision" value="<?= e((string) $location['revision']) ?>">
<?php if (!$locationId): ?><div class="mb-3"><label class="form-label" for="map-suggestion">Start with a suggested place</label><select id="map-suggestion" class="form-select" data-map-suggestion><option value="">Enter coordinates manually</option><?php foreach ($suggestions['places'] as $index => $place): ?><option value="<?= $index ?>"><?= e($place['name']) ?></option><?php endforeach; ?></select><div class="form-text">GeoNames city/airport reference points. Review before verifying; nothing is saved automatically.</div></div><script type="application/json" data-map-suggestions><?= json_encode($suggestions['places'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script><?php endif; ?>
<div class="mb-3"><label class="form-label" for="map-location-name">Map label *</label><input class="form-control" id="map-location-name" name="name" maxlength="150" value="<?= e((string) $location['name']) ?>" required></div>
<div class="row g-3 mb-3"><?php foreach (['latitude' => 'Latitude', 'longitude' => 'Longitude'] as $field => $label): ?><div class="col-sm-6"><label for="map-<?= $field ?>" class="form-label"><?= $label ?> *</label><input class="form-control" id="map-<?= $field ?>" name="<?= $field ?>" type="number" inputmode="decimal" step="0.000001" min="<?= $field === 'latitude' ? '5.8' : '79.4' ?>" max="<?= $field === 'latitude' ? '10' : '82' ?>" value="<?= e((string) $location[$field]) ?>" required></div><?php endforeach; ?></div>
<div class="mb-3"><label class="form-label" for="map-destination">Linked destination record (optional)</label><select id="map-destination" class="form-select" name="destination_id"><option value="">Standalone saved place</option><?php foreach ($destinations as $destination): ?><option value="<?= (int) $destination['id'] ?>" <?= (int) $location['destination_id'] === (int) $destination['id'] ? 'selected' : '' ?>><?= e($destination['name']) ?><?= !(int) $destination['is_published'] ? ' · draft' : '' ?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label" for="map-coordinate-source">Coordinate source / verification note *</label><input id="map-coordinate-source" class="form-control" name="coordinate_source" maxlength="500" value="<?= e((string) $location['coordinate_source']) ?>" required></div>
<label class="form-check mb-3"><input type="checkbox" class="form-check-input" name="is_verified" value="1" <?= (int) $location['is_verified'] ? 'checked' : '' ?>><span class="form-check-label">I checked these coordinates and the map label.</span></label>
<label class="form-check mb-3"><input type="checkbox" class="form-check-input" name="is_active" value="1" <?= (int) $location['is_active'] ? 'checked' : '' ?>><span class="form-check-label">Available for route maps</span></label>
<button class="btn btn-success" type="submit">Save map location</button> <a href="<?= e(base_url('admin/map_manager.php?tab=locations' . $contextQuery)) ?>" class="btn btn-outline-dark">New location</a>
</div></form>
<?php endif; ?>
<div class="route-map-library-list"><?php foreach ($locations as $place): ?><article class="route-map-library-item"><h3 class="h6"><?= e($place['name']) ?></h3><p class="small mb-1"><?= e($place['latitude'] . ', ' . $place['longitude']) ?> · <?= (int) $place['is_active'] && (int) $place['is_verified'] ? 'Verified' : 'Unavailable for public routes' ?></p><a href="<?= e(base_url('admin/map_manager.php?tab=locations&location_id=' . (int) $place['id'] . $contextQuery)) ?>">Edit location</a></article><?php endforeach; ?></div>
<?php else:
    $designState = route_map_design_state();
    if (get_input('map_reload', '') === '1') { unset($_SESSION['route_map_design_input']); }
    $oldDesign = $_SESSION['route_map_design_input'] ?? null;
    $design = is_array($oldDesign) ? $oldDesign + $designState['design'] : $designState['design'];
?>
<h2 class="h4">Default site design</h2><p>This shared design applies to every package. The stored Sri Lanka overview is used before a package route is configured.</p>
<?php if (!$designState['saved']): ?><p class="alert alert-info">The built-in design is stored with the application. Save below to store site-specific settings.</p><?php endif; ?>
<?php if ($oldDesign): ?><p class="alert alert-info">Your design entries were retained. <a href="<?= e(base_url('admin/map_manager.php?tab=design&map_reload=1' . $contextQuery)) ?>">Load saved design</a></p><?php endif; ?>
<div class="route-map-editor-grid"><form method="post" action="<?= e(base_url('admin/map_manager_save.php')) ?>" class="route-map-location-form" data-map-design-form>
<?= csrf_field() ?><input type="hidden" name="action" value="design"><input type="hidden" name="type" value="<?= e($type) ?>"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="revision" value="<?= e((string) ($oldDesign['revision'] ?? $designState['revision'])) ?>">
<?php foreach (['title' => ['Route map title', 90], 'brand' => ['Site / brand label', 100]] as $field => [$label, $limit]): ?><div class="mb-3"><label class="form-label" for="map-design-<?= $field ?>"><?= $label ?></label><input id="map-design-<?= $field ?>" class="form-control" name="<?= $field ?>" maxlength="<?= $limit ?>" value="<?= e((string) $design[$field]) ?>" required></div><?php endforeach; ?>
<div class="mb-3"><label class="form-label" for="map-route-color">Arrow and marker-outline colour</label><input type="color" id="map-route-color" name="route_color" class="form-control form-control-color" value="<?= e((string) $design['route_color']) ?>"><p class="form-text">Choose a dark colour for contrast with the fixed light map background.</p></div>
<button class="btn btn-success" type="submit" <?= !$designState['available'] ? 'disabled' : '' ?>>Save default map design</button></form><div style="max-width:340px"><?= route_map_card(['ready' => false], $designState['design']) ?></div></div>
<?php endif; require __DIR__ . '/_bottom.php'; ?>
