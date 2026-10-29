<?php
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();

/**
 * admin/_gem_day_arc_editor.php
 *
 * Day-in-the-life arc editor for experience_form.php.
 *
 * USAGE: add inside experience_form.php, after the "Intro" textarea:
 *
 *   <?php require __DIR__ . '/_gem_day_arc_editor.php'; ?>
 *
 * Reads $record (current experience row).
 * Posts: day_arc[0..3][time_of_day|icon|heading|body|image_path]
 *        day_arc_delete_image[0..3] = 1  (to clear an image)
 *        day_arc_images[0..3]            (file upload per segment)
 */

$_arc_segments_default = [
    ['time_of_day' => 'Morning',   'icon' => '🌅', 'heading' => '', 'body' => '', 'image_path' => ''],
    ['time_of_day' => 'Midday',    'icon' => '☀️',  'heading' => '', 'body' => '', 'image_path' => ''],
    ['time_of_day' => 'Afternoon', 'icon' => '🌿', 'heading' => '', 'body' => '', 'image_path' => ''],
    ['time_of_day' => 'Evening',   'icon' => '🌙', 'heading' => '', 'body' => '', 'image_path' => ''],
];

// Decode existing day_arc_json
$_arc_segments = $_arc_segments_default;
$_arc_raw = $record['day_arc_json'] ?? '';
if ($_arc_raw !== '' && $_arc_raw !== null) {
    $_arc_decoded = json_decode((string)$_arc_raw, true);
    if (is_array($_arc_decoded) && count($_arc_decoded) > 0) {
        foreach ($_arc_decoded as $i => $seg) {
            if (isset($_arc_segments[$i])) {
                $_arc_segments[$i] = array_merge($_arc_segments[$i], array_intersect_key(
                    $seg,
                    array_flip(['time_of_day','icon','heading','body','image_path'])
                ));
            }
        }
    }
}

// old_input override on validation fail
$_arc_old = old_input('day_arc', null);
if (is_array($_arc_old)) {
    foreach ($_arc_old as $i => $seg) {
        if (isset($_arc_segments[$i]) && is_array($seg)) {
            $_arc_segments[$i] = array_merge($_arc_segments[$i], array_intersect_key(
                array_map('strval', $seg),
                array_flip(['time_of_day','icon','heading','body'])
            ));
        }
    }
}

$_arc_bg_colors = [
    'linear-gradient(135deg,#2d6a41,#8f7a28)',
    'linear-gradient(135deg,#1a3a6a,#2a6aaa)',
    'linear-gradient(135deg,#6a3a1a,#aa7a2a)',
    'linear-gradient(135deg,#1a1a3a,#2a2a5a)',
];
?>

<!-- ═══════════════════════════════════════════════════════════
     DAY ARC EDITOR  (admin/_gem_day_arc_editor.php)
════════════════════════════════════════════════════════════ -->
<div class="card mb-4">
  <div class="card-header d-flex align-items-center gap-2">
    <span>🕐</span>
    <strong>Day-in-the-Life Arc</strong>
    <small class="text-muted ms-1">— 4 time-of-day segments shown on the experience detail page</small>
    <span class="badge bg-secondary ms-auto" style="font-size:.7rem;">GEM redesign</span>
  </div>
  <div class="card-body p-0">

    <?php foreach ($_arc_segments as $i => $seg):
        $imgUrl = !empty($seg['image_path']) ? stored_media_url((string)$seg['image_path']) : '';
    ?>
    <div class="gem-arc-segment border-bottom p-3 <?= $i === 0 ? '' : '' ?>">

      <div class="row g-3 align-items-start">

        <!-- Colour preview swatch -->
        <div class="col-auto d-none d-md-block">
          <div style="width:52px; height:52px; border-radius:.75rem; margin-top:1.75rem;
                      background:<?= $_arc_bg_colors[$i] ?>; flex-shrink:0;
                      display:flex; align-items:center; justify-content:center;
                      font-size:1.4rem;">
            <?= e($seg['icon']) ?>
          </div>
        </div>

        <!-- Time of day (fixed, readonly) -->
        <div class="col-6 col-md-2">
          <label class="form-label fw-semibold" style="font-size:.8rem;">Time of Day</label>
          <input type="text" class="form-control form-control-sm"
                 name="day_arc[<?= $i ?>][time_of_day]"
                 value="<?= e($seg['time_of_day']) ?>"
                 maxlength="40" readonly
                 style="background:#f8f9fa; font-weight:600;">
        </div>

        <!-- Icon emoji -->
        <div class="col-3 col-md-1">
          <label class="form-label fw-semibold" style="font-size:.8rem;">Icon</label>
          <input type="text" class="form-control form-control-sm"
                 name="day_arc[<?= $i ?>][icon]"
                 value="<?= e($seg['icon']) ?>"
                 maxlength="8"
                 style="font-size:1.2rem; text-align:center;">
        </div>

        <!-- Heading -->
        <div class="col-12 col-md-4">
          <label class="form-label fw-semibold" style="font-size:.8rem;">Heading</label>
          <input type="text" class="form-control form-control-sm"
                 name="day_arc[<?= $i ?>][heading]"
                 value="<?= e($seg['heading']) ?>"
                 maxlength="120"
                 placeholder="e.g. Early Drive from Sigiriya">
        </div>

        <!-- Image -->
        <div class="col-12 col-md-3">
          <label class="form-label fw-semibold" style="font-size:.8rem;">Image <small class="text-muted fw-normal">(optional)</small></label>
          <?php if ($imgUrl): ?>
          <div class="mb-1 position-relative" style="width:100%; height:70px; border-radius:.5rem; overflow:hidden;">
            <img src="<?= e($imgUrl) ?>" alt=""
                 style="width:100%; height:100%; object-fit:cover;">
            <label style="position:absolute; top:.3rem; right:.3rem; cursor:pointer;
                          background:rgba(0,0,0,.5); border-radius:50%; width:22px; height:22px;
                          display:flex; align-items:center; justify-content:center; color:#fff; font-size:.7rem;">
              <input type="checkbox" name="day_arc_delete_image[<?= $i ?>]" value="1" style="display:none;">
              ✕
            </label>
            <input type="hidden" name="day_arc[<?= $i ?>][image_path]" value="<?= e($seg['image_path']) ?>">
          </div>
          <?php else: ?>
          <input type="file" class="form-control form-control-sm"
                 name="day_arc_images[<?= $i ?>]"
                 accept="image/jpeg,image/png,image/webp">
          <input type="hidden" name="day_arc[<?= $i ?>][image_path]" value="">
          <?php endif; ?>
        </div>

      </div><!-- /row -->

      <!-- Body text -->
      <div class="mt-2">
        <label class="form-label fw-semibold" style="font-size:.8rem;">
          Body — what this part of the day looks like
        </label>
        <textarea class="form-control form-control-sm" rows="2"
                  name="day_arc[<?= $i ?>][body]"
                  placeholder="Describe what the visitor would do, see, eat, or feel during this time of day…"><?= e($seg['body']) ?></textarea>
      </div>

    </div><!-- /gem-arc-segment -->
    <?php endforeach; ?>

  </div><!-- /card-body -->
</div><!-- /card -->
