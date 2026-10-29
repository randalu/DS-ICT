<?php
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();

/**
 * admin/_gem_day_highlights.php
 *
 * Tag-style highlights input for itinerary_days.php.
 * Replaces the day's free-text summary with structured highlight chips.
 *
 * USAGE: add to the day form in itinerary_days.php, after Travel Time:
 *
 *   <?php
 *   $_gem_dh_record = $editRecord;   // current day row (may be [])
 *   require __DIR__ . '/_gem_day_highlights.php';
 *   ?>
 *
 * Posts: highlights_json_raw = JSON string array of tags
 */

$_gem_dh_record ??= [];

// Decode existing highlights_json
$_gem_dh_tags = [];
$_gem_dh_raw  = $_gem_dh_record['highlights_json'] ?? '';
if ($_gem_dh_raw !== '' && $_gem_dh_raw !== null) {
    $_gem_dh_decoded = json_decode((string)$_gem_dh_raw, true);
    if (is_array($_gem_dh_decoded)) {
        $_gem_dh_tags = array_values(array_filter(array_map('strval', $_gem_dh_decoded)));
    }
}

// old_input override
$_gem_dh_old = old_input('highlights_json_raw', null);
if ($_gem_dh_old !== null) {
    $_gem_dh_decoded2 = json_decode((string)$_gem_dh_old, true);
    if (is_array($_gem_dh_decoded2)) {
        $_gem_dh_tags = array_values(array_filter(array_map('strval', $_gem_dh_decoded2)));
    }
}

$_gem_dh_suggestions = [
    '🏛 Heritage site', '🌿 Nature walk', '🚗 Scenic drive', '🍛 Local lunch',
    '🐘 Wildlife', '⛵ Boat trip', '🧘 Rest day', '🏖 Beach time',
    '🌅 Sunrise hike', '🎭 Cultural show', '☕ Tea estate', '📸 Photography stop',
];
?>

<!-- ═══════════════════════════════════════════════════════════
     DAY HIGHLIGHTS TAG INPUT  (admin/_gem_day_highlights.php)
════════════════════════════════════════════════════════════ -->
<div class="mb-3">
  <label class="form-label fw-semibold">
    Day Highlights
    <small class="text-muted fw-normal">— short chips shown in the timeline expand panel</small>
  </label>

  <!-- Tag display area -->
  <div id="gemDhTagArea"
       style="min-height:44px; border:1px solid #dee2e6; border-radius:.5rem;
              padding:.4rem .5rem; background:#fff; display:flex; flex-wrap:wrap;
              gap:.35rem; align-items:center; cursor:text;"
       onclick="document.getElementById('gemDhInput').focus()">
    <?php foreach ($_gem_dh_tags as $tag): ?>
    <span class="gem-dh-tag"
          style="display:inline-flex; align-items:center; gap:.3rem;
                 background:rgba(45,106,65,.1); border:1px solid rgba(45,106,65,.25);
                 color:#1a4f2e; font-size:.75rem; font-weight:600;
                 padding:.2rem .55rem; border-radius:999px;">
      <?= e($tag) ?>
      <button type="button" class="gem-dh-remove"
              data-tag="<?= e($tag) ?>"
              style="background:none; border:none; cursor:pointer;
                     color:#888; font-size:.75rem; padding:0; line-height:1;">✕</button>
    </span>
    <?php endforeach; ?>
    <input type="text" id="gemDhInput"
           placeholder="<?= empty($_gem_dh_tags) ? 'Type a highlight and press Enter…' : 'Add another…' ?>"
           style="border:none; outline:none; font-size:.82rem; flex:1; min-width:160px;
                  background:transparent; padding:.1rem .2rem; font-family:inherit;">
  </div>

  <!-- Suggestion chips -->
  <div class="mt-1 d-flex flex-wrap gap-1">
    <span style="font-size:.65rem; color:#888; align-self:center; margin-right:.2rem;">Quick add:</span>
    <?php foreach ($_gem_dh_suggestions as $sug): ?>
    <button type="button" class="gem-dh-suggestion btn btn-outline-secondary btn-sm py-0 px-2"
            data-val="<?= e($sug) ?>"
            style="font-size:.65rem; border-radius:999px; line-height:1.8;">
      <?= e($sug) ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Hidden JSON field -->
  <input type="hidden" name="highlights_json_raw" id="gemDhHidden"
         value="<?= e(json_encode($_gem_dh_tags, JSON_UNESCAPED_UNICODE)) ?>">

  <div class="form-text">
    Each highlight becomes a small chip in the day timeline. Keep them short (2–4 words).
    Press <kbd>Enter</kbd> or <kbd>,</kbd> to add.
  </div>
</div>

<script>
(function() {
    const tagArea    = document.getElementById('gemDhTagArea');
    const inputEl    = document.getElementById('gemDhInput');
    const hiddenEl   = document.getElementById('gemDhHidden');
    const suggestions= document.querySelectorAll('.gem-dh-suggestion');

    let tags = <?= json_encode($_gem_dh_tags, JSON_UNESCAPED_UNICODE) ?>;

    function renderTags() {
        // Remove existing tag spans (keep the input)
        tagArea.querySelectorAll('.gem-dh-tag').forEach(t => t.remove());
        tags.forEach(tag => {
            const span = document.createElement('span');
            span.className = 'gem-dh-tag';
            span.style.cssText = 'display:inline-flex;align-items:center;gap:.3rem;' +
                'background:rgba(45,106,65,.1);border:1px solid rgba(45,106,65,.25);' +
                'color:#1a4f2e;font-size:.75rem;font-weight:600;' +
                'padding:.2rem .55rem;border-radius:999px;';
            span.textContent = tag + ' ';
            const rmBtn = document.createElement('button');
            rmBtn.type = 'button';
            rmBtn.className = 'gem-dh-remove';
            rmBtn.dataset.tag = tag;
            rmBtn.style.cssText = 'background:none;border:none;cursor:pointer;color:#888;font-size:.75rem;padding:0;line-height:1;';
            rmBtn.textContent = '✕';
            rmBtn.addEventListener('click', () => removeTag(tag));
            span.appendChild(rmBtn);
            tagArea.insertBefore(span, inputEl);
        });
        hiddenEl.value = JSON.stringify(tags);
        inputEl.placeholder = tags.length === 0 ? 'Type a highlight and press Enter…' : 'Add another…';
    }

    function addTag(val) {
        val = val.trim().replace(/,+$/, '').trim();
        if (!val || tags.includes(val) || tags.length >= 8) return;
        tags.push(val);
        renderTags();
    }

    function removeTag(val) {
        tags = tags.filter(t => t !== val);
        renderTags();
    }

    inputEl.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addTag(this.value);
            this.value = '';
        }
        if (e.key === 'Backspace' && this.value === '' && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });

    inputEl.addEventListener('blur', function() {
        if (this.value.trim()) { addTag(this.value); this.value = ''; }
    });

    suggestions.forEach(btn => {
        btn.addEventListener('click', function() {
            addTag(this.dataset.val);
        });
    });
})();
</script>
