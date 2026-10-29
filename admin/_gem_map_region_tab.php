<?php
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();

/**
 * admin/_gem_map_region_tab.php
 *
 * Partial — "Map & Region" panel for destination_form.php
 *
 * USAGE: paste the include below into destination_form.php, just before
 * the closing </form> tag or after the "Best Months" section.
 *
 *   <?php require __DIR__ . '/_gem_map_region_tab.php'; ?>
 *
 * Reads $record (the current destination row, may be []).
 * All fields use old_input() so values survive validation redirects.
 */

$_map_regions = [
    'cultural' => 'Cultural Triangle',
    'hill'     => 'Hill Country',
    'wildlife' => 'Wildlife',
    'south'    => 'South Coast',
    'east'     => 'East Coast',
];

$_map_r_options = ['5.0','5.5','6.0','6.5','7.0','7.5','8.0','8.5','9.0'];

// Current values (prefer old_input on validation fail, else DB record)
$_region_slug  = old_input('region_slug',  $record['region_slug']  ?? '');
$_region_label = old_input('region_label', $record['region_label'] ?? '');
$_emoji        = old_input('emoji',        $record['emoji']        ?? '');
$_stay_dur     = old_input('stay_duration',$record['stay_duration']?? '');
$_map_x        = old_input('map_x',        $record['map_x']        ?? '');
$_map_y        = old_input('map_y',        $record['map_y']        ?? '');
$_map_r        = old_input('map_r',        $record['map_r']        ?? '7.0');
$_map_initial  = old_input('map_initial',  $record['map_initial']  ?? '');
?>

<!-- ═══════════════════════════════════════════════════════════
     MAP & REGION PANEL  (admin/_gem_map_region_tab.php)
════════════════════════════════════════════════════════════ -->
<div class="card mb-4">
  <div class="card-header d-flex align-items-center gap-2">
    <span>🗺</span>
    <strong>Map &amp; Region</strong>
    <span class="badge bg-secondary ms-auto" style="font-size:.7rem;">GEM redesign</span>
  </div>
  <div class="card-body">

    <div class="row g-3">

      <!-- Region slug + label -->
      <div class="col-md-4">
        <label class="form-label fw-semibold" for="gem_region_slug">
          Region <span class="text-danger">*</span>
        </label>
        <select class="form-select" id="gem_region_slug" name="region_slug"
                onchange="gemAutoLabel(this)">
          <option value="">— None —</option>
          <?php foreach ($_map_regions as $slug => $label): ?>
          <option value="<?= e($slug) ?>"
            <?= $_region_slug === $slug ? 'selected' : '' ?>>
            <?= e($label) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Drives the map filter pill and zone highlight.</div>
      </div>

      <div class="col-md-4">
        <label class="form-label fw-semibold" for="gem_region_label">Region Label</label>
        <input type="text" class="form-control" id="gem_region_label"
               name="region_label" value="<?= e($_region_label) ?>"
               placeholder="e.g. Cultural Triangle"
               maxlength="120">
        <div class="form-text">Auto-filled when you pick a region above. Override if needed.</div>
      </div>

      <!-- Emoji -->
      <div class="col-md-2">
        <label class="form-label fw-semibold" for="gem_emoji">Emoji</label>
        <input type="text" class="form-control" id="gem_emoji"
               name="emoji" value="<?= e($_emoji) ?>"
               placeholder="🏔" maxlength="8"
               style="font-size:1.4rem; text-align:center;">
        <div class="form-text">List thumbnail fallback.</div>
      </div>

      <!-- Stay duration -->
      <div class="col-md-2">
        <label class="form-label fw-semibold" for="gem_stay_duration">Stay Duration</label>
        <input type="text" class="form-control" id="gem_stay_duration"
               name="stay_duration" value="<?= e($_stay_dur) ?>"
               placeholder="e.g. Half day" maxlength="80">
        <div class="form-text">Shown on cards &amp; map hover.</div>
      </div>

    </div><!-- /row 1 -->

    <hr class="my-3">

    <!-- Map pin coordinates -->
    <p class="mb-2 fw-semibold text-muted" style="font-size:.8rem; text-transform:uppercase; letter-spacing:.06em;">
      SVG Map Pin — viewBox 0 0 220 345
    </p>

    <div class="row g-3 align-items-end">

      <div class="col-6 col-md-2">
        <label class="form-label" for="gem_map_x">Pin X</label>
        <input type="number" class="form-control" id="gem_map_x"
               name="map_x" value="<?= e($_map_x) ?>"
               step="0.5" min="0" max="220" placeholder="e.g. 118">
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label" for="gem_map_y">Pin Y</label>
        <input type="number" class="form-control" id="gem_map_y"
               name="map_y" value="<?= e($_map_y) ?>"
               step="0.5" min="0" max="345" placeholder="e.g. 108">
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label" for="gem_map_r">Pin Radius</label>
        <select class="form-select" id="gem_map_r" name="map_r">
          <?php foreach ($_map_r_options as $r): ?>
          <option value="<?= e($r) ?>" <?= (string)$_map_r === $r ? 'selected' : '' ?>>
            <?= e($r) ?> <?= $r === '7.0' ? '(default)' : '' ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label" for="gem_map_initial">Pin Initial</label>
        <input type="text" class="form-control" id="gem_map_initial"
               name="map_initial" value="<?= e($_map_initial) ?>"
               maxlength="2" placeholder="e.g. S"
               style="text-align:center; font-weight:700; font-size:1.1rem;">
        <div class="form-text">1–2 letters inside the pin circle.</div>
      </div>

      <!-- Live mini map preview -->
      <div class="col-12 col-md-4">
        <label class="form-label">Pin Preview</label>
        <div style="background:#f7f3ea; border:1px solid rgba(12,35,22,.1);
                    border-radius:.75rem; padding:.75rem; text-align:center;">
          <svg id="gemPinPreview" viewBox="0 0 44 44" width="44" height="44"
               xmlns="http://www.w3.org/2000/svg">
            <circle id="gemPreviewCircle" cx="22" cy="22" r="18"
                    fill="#2d6a41" stroke="#fff" stroke-width="3"/>
            <text id="gemPreviewText" x="22" y="27"
                  font-family="system-ui" font-size="14" font-weight="800"
                  fill="#fff" text-anchor="middle"></text>
          </svg>
          <div style="font-size:.65rem; color:#5a6b5e; margin-top:.25rem;"
               id="gemPinCoords">x=— y=—</div>
        </div>
      </div>

    </div><!-- /row 2 -->

  </div><!-- /card-body -->
</div><!-- /card -->

<script>
// Auto-fill region label when region slug is selected
const _regionLabels = <?= json_encode($_map_regions, JSON_UNESCAPED_UNICODE) ?>;
function gemAutoLabel(select) {
    const labelInput = document.getElementById('gem_region_label');
    if (labelInput && select.value && _regionLabels[select.value]) {
        labelInput.value = _regionLabels[select.value];
    }
}

// Live pin preview
(function() {
    const initialInput = document.getElementById('gem_map_initial');
    const xInput       = document.getElementById('gem_map_x');
    const yInput       = document.getElementById('gem_map_y');
    const previewText  = document.getElementById('gemPreviewText');
    const coordLabel   = document.getElementById('gemPinCoords');

    function updatePreview() {
        if (previewText) previewText.textContent = (initialInput?.value || '?').toUpperCase();
        if (coordLabel && xInput && yInput) {
            coordLabel.textContent = `x=${xInput.value || '—'} y=${yInput.value || '—'}`;
        }
    }

    [initialInput, xInput, yInput].forEach(el => {
        if (el) el.addEventListener('input', updatePreview);
    });
    updatePreview();
})();
</script>
