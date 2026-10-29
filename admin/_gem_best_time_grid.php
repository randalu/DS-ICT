<?php
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();

/**
 * admin/_gem_best_time_grid.php
 *
 * Reusable 12-month best-time rating grid.
 * Used by BOTH destination_form.php AND itinerary_form.php.
 *
 * USAGE in destination_form.php — replace the existing "Best Months"
 * checkbox section with:
 *
 *   <?php
 *   $_gem_bt_field   = 'best_time_json';          // DB column name
 *   $_gem_bt_record  = $record;                    // current DB row
 *   $_gem_bt_context = 'destination';              // label context
 *   require __DIR__ . '/_gem_best_time_grid.php';
 *   ?>
 *
 * USAGE in itinerary_form.php:
 *
 *   <?php
 *   $_gem_bt_field   = 'best_months_json';
 *   $_gem_bt_record  = $record;
 *   $_gem_bt_context = 'itinerary';
 *   require __DIR__ . '/_gem_best_time_grid.php';
 *   ?>
 *
 * Output fields posted:
 *   best_time_ratings[Jan] = great|okay|avoid|''
 *   best_time_note         = free-text note
 * The _save.php handlers (below) convert these to JSON.
 */

$_gem_bt_field   ??= 'best_time_json';
$_gem_bt_record  ??= [];
$_gem_bt_context ??= 'destination';

// Decode existing JSON to pre-select ratings
$_gem_bt_current = [];
$_gem_bt_note    = old_input('best_time_note', '');
$_gem_bt_raw     = $_gem_bt_record[$_gem_bt_field] ?? '';
if ($_gem_bt_raw !== '' && $_gem_bt_raw !== null) {
    $_gem_bt_decoded = json_decode((string)$_gem_bt_raw, true);
    if (is_array($_gem_bt_decoded)) {
        // New format: { "months": [{"month":"Jan","rating":"great"}, ...], "note":"..." }
        if (isset($_gem_bt_decoded['months']) && is_array($_gem_bt_decoded['months'])) {
            foreach ($_gem_bt_decoded['months'] as $_m) {
                if (isset($_m['month'], $_m['rating'])) {
                    $_gem_bt_current[$_m['month']] = $_m['rating'];
                }
            }
            if ($_gem_bt_note === '' && isset($_gem_bt_decoded['note'])) {
                $_gem_bt_note = (string)$_gem_bt_decoded['note'];
            }
        }
        // Legacy format: { "months": ["jan","feb",...], "note":"..." }
        elseif (isset($_gem_bt_decoded['months']) && is_array($_gem_bt_decoded['months'])) {
            foreach ($_gem_bt_decoded['months'] as $_m) {
                $_gem_bt_current[ucfirst(strtolower((string)$_m))] = 'great';
            }
        }
    }
}

// Allow old_input to override on validation fail
if (is_array(old_input('best_time_ratings', null))) {
    $_gem_bt_current = array_map('strval', old_input('best_time_ratings', []));
}
if (old_input('best_time_note', null) !== null) {
    $_gem_bt_note = (string)old_input('best_time_note', '');
}

$_gem_months = [
    'Jan','Feb','Mar','Apr','May','Jun',
    'Jul','Aug','Sep','Oct','Nov','Dec',
];

$_gem_ratings = [
    'great' => ['label' => 'Great',  'color' => '#2d6a41', 'bg' => 'rgba(45,106,65,.12)', 'icon' => '✓'],
    'okay'  => ['label' => 'Okay',   'color' => '#8f7a28', 'bg' => 'rgba(143,122,40,.12)', 'icon' => '~'],
    'avoid' => ['label' => 'Avoid',  'color' => '#9a3a2a', 'bg' => 'rgba(154,58,42,.10)', 'icon' => '✕'],
];
?>

<!-- ═══════════════════════════════════════════════════════════
     BEST TIME GRID  (admin/_gem_best_time_grid.php)
════════════════════════════════════════════════════════════ -->
<div class="card mb-4">
  <div class="card-header d-flex align-items-center gap-2">
    <span>📅</span>
    <strong>Best Time — Monthly Rating Grid</strong>
    <span class="badge bg-secondary ms-auto" style="font-size:.7rem;">GEM redesign</span>
  </div>
  <div class="card-body">

    <p class="text-muted mb-3" style="font-size:.82rem;">
      Set a rating for each month. This powers the 12-month colour strip on the
      <?= $_gem_bt_context === 'itinerary' ? 'itinerary detail' : 'destination detail' ?> page.
      Leave a month blank to hide it from the strip.
    </p>

    <!-- Legend -->
    <div class="d-flex gap-3 mb-3 flex-wrap">
      <?php foreach ($_gem_ratings as $val => $cfg): ?>
      <div class="d-flex align-items-center gap-1" style="font-size:.78rem;">
        <span style="display:inline-block; width:14px; height:14px; border-radius:3px;
                     background:<?= $cfg['color'] ?>; opacity:.85;"></span>
        <span><?= $cfg['label'] ?></span>
      </div>
      <?php endforeach; ?>
      <div class="d-flex align-items-center gap-1" style="font-size:.78rem;">
        <span style="display:inline-block; width:14px; height:14px; border-radius:3px;
                     background:#e0ddd8;"></span>
        <span>Not set</span>
      </div>
    </div>

    <!-- Month grid -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:.65rem;"
         id="gemBtGrid">
      <?php foreach ($_gem_months as $month):
          $current = $_gem_bt_current[$month] ?? '';
      ?>
      <div class="gem-bt-month" data-month="<?= $month ?>">
        <div class="fw-semibold mb-1" style="font-size:.75rem; letter-spacing:.05em;">
          <?= $month ?>
        </div>
        <div class="d-flex gap-1">
          <?php foreach ($_gem_ratings as $val => $cfg): ?>
          <label class="gem-bt-option flex-fill text-center"
                 data-val="<?= $val ?>"
                 style="cursor:pointer; border-radius:.5rem; padding:.3rem .1rem;
                        font-size:.65rem; font-weight:700; transition:all 150ms;
                        border: 2px solid <?= $current === $val ? $cfg['color'] : 'transparent' ?>;
                        background: <?= $current === $val ? $cfg['bg'] : 'rgba(0,0,0,.04)' ?>;
                        color: <?= $current === $val ? $cfg['color'] : '#888' ?>;">
            <input type="radio"
                   name="best_time_ratings[<?= $month ?>]"
                   value="<?= $val ?>"
                   <?= $current === $val ? 'checked' : '' ?>
                   style="display:none;">
            <?= $cfg['icon'] ?><br><?= $cfg['label'] ?>
          </label>
          <?php endforeach; ?>
          <!-- Clear button -->
          <label class="gem-bt-option text-center"
                 data-val=""
                 style="cursor:pointer; border-radius:.5rem; padding:.3rem .2rem;
                        font-size:.65rem; font-weight:700; transition:all 150ms;
                        border: 2px solid <?= $current === '' ? '#ccc' : 'transparent' ?>;
                        background: rgba(0,0,0,.03); color:#aaa; min-width:28px;">
            <input type="radio"
                   name="best_time_ratings[<?= $month ?>]"
                   value=""
                   <?= $current === '' ? 'checked' : '' ?>
                   style="display:none;">
            ×
          </label>
        </div>
      </div>
      <?php endforeach; ?>
    </div><!-- /month grid -->

    <!-- Live preview strip -->
    <div class="mt-3">
      <div style="font-size:.7rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase;
                  color:#5a6b5e; margin-bottom:.4rem;">Preview strip (as visitors see it)</div>
      <div id="gemBtPreviewStrip"
           style="display:grid; grid-template-columns:repeat(12,1fr); border-radius:.5rem;
                  overflow:hidden; border:1px solid rgba(12,35,22,.1); height:36px;">
        <?php foreach ($_gem_months as $month):
            $r = $_gem_bt_current[$month] ?? '';
            $bg = $r === 'great' ? '#2d6a41' : ($r === 'okay' ? '#8f7a28' : ($r === 'avoid' ? '#9a3a2a' : '#e0ddd8'));
            $opacity = $r === '' ? '.4' : '.85';
        ?>
        <div class="gem-strip-cell" data-month="<?= $month ?>"
             title="<?= $month ?>"
             style="background:<?= $bg ?>; opacity:<?= $opacity ?>;
                    display:flex; align-items:center; justify-content:center;
                    font-size:.55rem; font-weight:700;
                    color:<?= $r ? '#fff' : '#999' ?>; transition:background 200ms;">
          <?= $month ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Optional free-text note -->
    <div class="mt-3">
      <label class="form-label fw-semibold" for="gem_best_time_note">
        Best Time Note <small class="text-muted fw-normal">(optional)</small>
      </label>
      <input type="text" class="form-control" id="gem_best_time_note"
             name="best_time_note"
             value="<?= e($_gem_bt_note) ?>"
             maxlength="255"
             placeholder="e.g. Nov–Apr for south & west coasts; Apr–Sep for east coast">
      <div class="form-text">Short note shown below the monthly strip on the public page.</div>
    </div>

  </div><!-- /card-body -->
</div><!-- /card -->

<script>
(function() {
    const ratingColors = {
        great: { bg: '#2d6a41', opacity: '.85' },
        okay:  { bg: '#8f7a28', opacity: '.85' },
        avoid: { bg: '#9a3a2a', opacity: '.85' },
        '':    { bg: '#e0ddd8', opacity: '.45' },
    };
    const ratingBgMap = {
        great: 'rgba(45,106,65,.12)',
        okay:  'rgba(143,122,40,.12)',
        avoid: 'rgba(154,58,42,.10)',
        '':    'rgba(0,0,0,.03)',
    };
    const ratingColorMap = {
        great: '#2d6a41',
        okay:  '#8f7a28',
        avoid: '#9a3a2a',
        '':    '#aaa',
    };
    const borderColorMap = {
        great: '#2d6a41',
        okay:  '#8f7a28',
        avoid: '#9a3a2a',
        '':    '#ccc',
    };

    document.querySelectorAll('#gemBtGrid .gem-bt-month').forEach(monthEl => {
        const month = monthEl.dataset.month;
        const options = monthEl.querySelectorAll('.gem-bt-option');

        options.forEach(opt => {
            opt.addEventListener('click', function() {
                const val = this.dataset.val;
                // Style all options in this month
                options.forEach(o => {
                    const v = o.dataset.val;
                    const isSelected = v === val;
                    o.style.border        = `2px solid ${isSelected ? borderColorMap[v] : 'transparent'}`;
                    o.style.background    = isSelected ? ratingBgMap[v] : 'rgba(0,0,0,.04)';
                    o.style.color         = isSelected ? ratingColorMap[v] : '#888';
                });
                // Check the hidden radio
                const radio = this.querySelector('input[type=radio]');
                if (radio) radio.checked = true;
                // Update preview strip
                updateStrip(month, val);
            });
        });
    });

    function updateStrip(month, rating) {
        const cell = document.querySelector(`#gemBtPreviewStrip .gem-strip-cell[data-month="${month}"]`);
        if (!cell) return;
        const conf = ratingColors[rating] || ratingColors[''];
        cell.style.background = conf.bg;
        cell.style.opacity    = conf.opacity;
        cell.style.color      = rating ? '#fff' : '#999';
    }
})();
</script>
