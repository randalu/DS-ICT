<?php
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();

/**
 * admin/_gem_itinerary_fields.php
 *
 * Traveller types + start/end point fields for itinerary_form.php.
 *
 * USAGE: add into itinerary_form.php, after the Category dropdown:
 *
 *   <?php require __DIR__ . '/_gem_itinerary_fields.php'; ?>
 */

$_itin_traveller_options = [
    'couple'    => ['label' => 'Couple',    'icon' => '👫'],
    'family'    => ['label' => 'Family',    'icon' => '👨‍👩‍👧'],
    'honeymoon' => ['label' => 'Honeymoon', 'icon' => '💑'],
    'solo'      => ['label' => 'Solo',      'icon' => '🧳'],
    'group'     => ['label' => 'Group',     'icon' => '👥'],
];

$_itin_start_options = [
    '' => '— Any —',
    'Colombo Airport'  => 'Colombo Airport (BIA)',
    'Colombo'          => 'Colombo City',
    'Negombo'          => 'Negombo',
    'Galle'            => 'Galle',
    'Kandy'            => 'Kandy',
    'Sigiriya'         => 'Sigiriya / Habarana',
    'Trincomalee'      => 'Trincomalee',
];

// Decode traveller_types CSV
$_saved_types_raw = old_input('traveller_types', $record['traveller_types'] ?? '');
$_saved_types = array_filter(array_map('trim', explode(',', (string)$_saved_types_raw)));

$_saved_start = old_input('start_point', $record['start_point'] ?? '');
$_saved_end   = old_input('end_point',   $record['end_point']   ?? '');
foreach ([$_saved_start, $_saved_end] as $_saved_point) {
    if (is_string($_saved_point) && $_saved_point !== '' && !isset($_itin_start_options[$_saved_point])) { $_itin_start_options[$_saved_point] = $_saved_point; }
}
foreach ($_saved_types as $_saved_type) {
    if (!isset($_itin_traveller_options[$_saved_type])) { $_itin_traveller_options[$_saved_type] = ['label' => $_saved_type, 'icon' => '']; }
}
?>

<!-- ═══════════════════════════════════════════════════════════
     ITINERARY FILTER FIELDS  (admin/_gem_itinerary_fields.php)
════════════════════════════════════════════════════════════ -->
<div class="card mb-4">
  <div class="card-header d-flex align-items-center gap-2">
    <span>🎯</span>
    <strong>Traveller Type &amp; Route Endpoints</strong>
    <small class="text-muted ms-1">— powers the listing page filter bar</small>
  </div>
  <div class="card-body">

    <!-- Traveller types -->
    <div class="mb-4">
      <label class="form-label fw-semibold">Traveller Types</label>
      <div class="form-text mb-2">
        Select all that apply. These populate the filter chips on the itineraries listing page.
      </div>
      <div class="d-flex gap-2 flex-wrap" id="gemTravellerPicker">
        <?php foreach ($_itin_traveller_options as $val => $opt): ?>
        <label class="gem-type-chip"
               data-val="<?= e($val) ?>"
               style="cursor:pointer; display:inline-flex; align-items:center; gap:.4rem;
                      padding:.4rem 1rem; border-radius:999px; font-size:.8rem; font-weight:600;
                      border: 2px solid <?= in_array($val, $_saved_types, true) ? 'var(--bs-success, #2d6a41)' : 'rgba(0,0,0,.15)' ?>;
                      background: <?= in_array($val, $_saved_types, true) ? 'rgba(45,106,65,.10)' : 'transparent' ?>;
                      color: <?= in_array($val, $_saved_types, true) ? '#1a4f2e' : '#555' ?>;
                      transition: all 150ms; user-select:none;">
          <input type="checkbox"
                 name="traveller_types_raw[]"
                 value="<?= e($val) ?>"
                 <?= in_array($val, $_saved_types, true) ? 'checked' : '' ?>
                 class="form-check-input m-0">
          <?= $opt['icon'] ?> <?= e($opt['label']) ?>
        </label>
        <?php endforeach; ?>
      </div>
      <!-- Hidden field that collects CSV for the save handler -->
      <input type="hidden" name="traveller_types" id="gemTravellerTypes"
             value="<?= e(implode(',', $_saved_types)) ?>">
      <input type="hidden" name="traveller_types_present" value="1">
    </div>

    <!-- Start / End point -->
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold" for="gem_start_point">
          Starting Point
        </label>
        <input class="form-control" id="gem_start_point" name="start_point" list="itinerary-route-points" maxlength="255" value="<?= e((string) $_saved_start) ?>" placeholder="Choose or enter a starting point">
        <div class="form-text">Where does this itinerary begin?</div>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold" for="gem_end_point">
          Ending Point
        </label>
        <input class="form-control" id="gem_end_point" name="end_point" list="itinerary-route-points" maxlength="255" value="<?= e((string) $_saved_end) ?>" placeholder="Choose or enter an ending point">
        <div class="form-text">Where does it finish?</div>
      </div>
    </div>
    <datalist id="itinerary-route-points"><?php foreach ($_itin_start_options as $val => $label): if ($val === '') { continue; } ?><option value="<?= e($val) ?>"><?= e($label) ?></option><?php endforeach; ?></datalist>

  </div><!-- /card-body -->
</div><!-- /card -->

<script>
(function() {
    const chips    = document.querySelectorAll('#gemTravellerPicker .gem-type-chip');
    const hiddenFld= document.getElementById('gemTravellerTypes');

    function buildCSV() {
        const selected = [];
        chips.forEach(chip => {
            const cb = chip.querySelector('input[type=checkbox]');
            if (cb && cb.checked) selected.push(cb.value);
        });
        if (hiddenFld) hiddenFld.value = selected.join(',');
    }

    chips.forEach(chip => {
        const cb = chip.querySelector('input[type=checkbox]');
        if (!cb) return;
        cb.addEventListener('change', function() {
            const on = cb.checked;
            chip.style.border     = `2px solid ${on ? '#2d6a41' : 'rgba(0,0,0,.15)'}`;
            chip.style.background = on ? 'rgba(45,106,65,.10)' : 'transparent';
            chip.style.color      = on ? '#1a4f2e' : '#555';
            buildCSV();
        });
    });
})();
</script>
