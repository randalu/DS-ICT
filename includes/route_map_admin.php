<?php
declare(strict_types=1);
require_once __DIR__ . '/route_map_store.php';
require_once __DIR__ . '/route_map_view.php';

function route_map_admin_assets(): string
{
    static $loaded = false;
    if ($loaded) { return ''; }
    $loaded = true;
    return '<link rel="stylesheet" href="' . e(base_url('assets/css/route-map.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/route-map.css')) . '">'
        . route_map_assets() . '<script defer src="' . e(base_url('assets/js/route-map-admin.js') . '?v=' . filemtime(__DIR__ . '/../assets/js/route-map-admin.js')) . '"></script>';
}

function route_map_return_path(string $type, int $id, string $return): string
{
    $definition = route_map_type($type);
    if ($return === $definition['editor']) { return 'admin/' . $return . '?' . $definition['id_key'] . '=' . $id . '#route-map-editor'; }
    return 'admin/map_manager.php?type=' . $type . '&id=' . $id . '#route-map-editor';
}

function route_map_admin_form(string $type, array $record, string $return = 'map_manager.php'): void
{
    $id = (int) $record['id'];
    $state = route_map_state($type, $record);
    $scope = $type . ':' . $id;
    if (get_input('map_reload', '') === '1') { unset($_SESSION['route_map_input'][$scope]); }
    $old = $_SESSION['route_map_input'][$scope] ?? null;
    $selected = [];
    foreach ($state['document']['days'] ?? [] as $day) { $selected[$day['day']] = $day['stops']; }
    if (is_array($old) && is_array($old['map_days'] ?? null)) { $selected = $old['map_days']; }
    $revision = is_array($old) ? ($old['revision'] ?? '') : $state['revision'];
    $sourceRevision = is_array($old) ? ($old['source_revision'] ?? '') : $state['schedule']['revision'];
    $libraryRevision = is_array($old) ? ($old['library_revision'] ?? '') : route_map_library_revision($state['locations']);
    $status = is_array($old) ? ($old['status'] ?? 'draft') : ($state['document']['status'] ?? 'draft');
    $reloadPath = str_replace('#route-map-editor', '&map_reload=1#route-map-editor', route_map_return_path($type, $id, $return));
    echo route_map_admin_assets();
    ?>
    <section class="route-map-editor card card-soft" id="route-map-editor"><div class="card-body">
        <div class="d-flex gap-2 align-items-start justify-content-between flex-wrap mb-3"><div><h2 class="h4">Journey map</h2><p class="text-secondary mb-1">Choose destinations for the saved daily plan. Map saves are separate from package text and photos.</p></div><a class="btn btn-outline-dark" href="<?= e(base_url('admin/map_manager.php?type=' . $type . '&id=' . $id)) ?>">Open Map Manager</a></div>
        <?php if ($state['message'] !== ''): ?><p class="alert alert-warning" role="status"><?= e($state['message']) ?></p><?php endif; ?>
        <?php if ($old): ?><p class="alert alert-info">Your map entries were retained after an unsuccessful save. Compare them with the saved map before retrying. <a href="<?= e(base_url($reloadPath)) ?>">Load saved map</a></p><?php endif; ?>
        <?php if (!$state['available']): ?><?= route_map_card($state) ?><?php else: ?>
        <div class="route-map-editor-grid">
        <form method="post" action="<?= e(base_url('admin/map_manager_save.php')) ?>" data-route-map-form data-preview-url="<?= e(base_url('admin/map_manager_api.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="route"><input type="hidden" name="type" value="<?= e($type) ?>"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="return_to" value="<?= e($return) ?>">
            <input type="hidden" name="revision" value="<?= e(is_scalar($revision) ? (string) $revision : '') ?>"><input type="hidden" name="source_revision" value="<?= e(is_scalar($sourceRevision) ? (string) $sourceRevision : '') ?>"><input type="hidden" name="library_revision" value="<?= e(is_scalar($libraryRevision) ? (string) $libraryRevision : '') ?>">
            <?php if (!$state['locations']): ?><p class="alert alert-info">Add and verify locations in <a href="<?= e(base_url('admin/map_manager.php?tab=locations&type=' . $type . '&id=' . $id)) ?>">Map Locations</a> first. No locations are created automatically.</p><?php endif; ?>
            <?php if (!$state['schedule']['days']): ?><p class="alert alert-info">Save the written daily plan first, then select its destinations here. <a href="<?= e(route_map_editor_url($type, $id)) ?>">Open daily-plan editor</a></p><?php endif; ?>
            <?php foreach (array_merge([['day' => 0, 'title' => 'Optional arrival / starting point', 'written' => true]], $state['schedule']['days']) as $day):
                $dayNumber = (int) $day['day'];
                $stops = $selected[$dayNumber] ?? [];
                if (!is_array($stops) || count($stops) > 8) { $stops = []; }
                if (!$stops) { $stops = ['']; }
            ?>
            <fieldset data-map-day="<?= $dayNumber ?>"><legend>Day <?= $dayNumber ?><?= $dayNumber === 0 ? ' · Arrival' : '' ?></legend>
                <p class="route-map-day-title"><?= e($day['title']) ?><?= !$day['written'] ? ' — save this written day plan before displaying its route.' : '' ?></p>
                <div data-map-stops>
                <?php foreach ($stops as $index => $stop): $value = is_scalar($stop) ? (string) $stop : ''; ?>
                    <div class="route-map-stop-row"><select class="form-select" name="map_days[<?= $dayNumber ?>][]" aria-label="Day <?= $dayNumber ?> stop <?= $index + 1 ?>">
                        <option value="">Choose destination or saved place</option>
                        <?php if ($value !== '' && !isset($state['locations'][(int) $value])): ?><option value="<?= e($value) ?>" selected>Unavailable selection — choose a saved place</option><?php endif; ?>
                        <?php foreach ($state['locations'] as $location): ?><option value="<?= (int) $location['id'] ?>" <?= $value === (string) $location['id'] ? 'selected' : '' ?>><?= e($location['name']) ?><?= !(int) $location['is_active'] ? ' · inactive' : (!(int) $location['is_verified'] ? ' · needs verification' : '') ?></option><?php endforeach; ?>
                    </select><span class="route-map-stop-tools" hidden><button type="button" class="btn btn-outline-secondary" data-map-up aria-label="Move stop earlier">↑</button><button type="button" class="btn btn-outline-secondary" data-map-down aria-label="Move stop later">↓</button><button type="button" class="btn btn-outline-danger" data-map-remove aria-label="Remove stop">×</button></span></div>
                <?php endforeach; ?>
                </div><button type="button" class="btn btn-sm btn-outline-dark" data-map-add hidden>Add stop</button>
            </fieldset>
            <?php endforeach; ?>
            <div class="mb-3"><label class="form-label" for="route-map-status">Public map</label><select class="form-select" id="route-map-status" name="status"><option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft — display Sri Lanka overview</option><option value="ready" <?= $status === 'ready' ? 'selected' : '' ?>>Display this route on the package page</option></select></div>
            <?php if ($state['review']): ?><label class="form-check mb-3"><input type="checkbox" class="form-check-input" name="confirm_review" value="1"><span class="form-check-label">I reviewed the changed daily plan and locations and confirmed these map stops.</span></label><?php endif; ?>
            <input type="hidden" name="map_form_complete" value="1">
            <button class="btn btn-success" type="submit">Save journey map</button> <a class="btn btn-outline-dark" href="<?= e(base_url($reloadPath)) ?>">Load saved map</a>
            <p class="mt-3 text-secondary" role="status" data-map-status>Choose stops to preview the route. Changes are saved only with Save journey map.</p>
            <noscript><p>Existing stop selectors and saving work without JavaScript. Enable it to add or reorder multiple stops and preview changes.</p></noscript>
        </form>
        <div class="route-map-preview" data-map-preview><?= route_map_card($state) ?></div>
        </div><?php endif; ?>
    </div></section>
    <?php
}
