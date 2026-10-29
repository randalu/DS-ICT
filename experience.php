<?php
/**
 * experience.php — GEM redesign
 * Day-in-the-life arc, gallery, linked destinations,
 * related itineraries, inline enquiry prompt.
 */

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/responsive_image.php';

ensure_experiences_schema();

// ── Column guards ─────────────────────────────────────────────────────────────
$_hasDayArc    = db_column_exists('experiences', 'day_arc_json');
$_hasCategoryId= db_column_exists('experiences', 'category_id');
$_hasCatTable  = db_table_exists('experience_categories');
$_hasItinCount = db_column_exists('experiences', 'itinerary_count');

// ── Load experience ───────────────────────────────────────────────────────────
$slug = trim((string) get_input('slug', ''));
$id   = (int) get_input('id', 0);

if (!db_table_exists('experiences')) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$publishedWhere = db_column_exists('experiences', 'is_published') ? ' AND is_published = 1' : '';

$experience = null;
if ($slug !== '') {
    $experience = fetch_one(
        "SELECT * FROM experiences WHERE slug = :slug {$publishedWhere} LIMIT 1",
        ['slug' => $slug]
    );
}
if (!$experience && $id > 0) {
    $experience = fetch_one(
        "SELECT * FROM experiences WHERE id = :id {$publishedWhere} LIMIT 1",
        ['id' => $id]
    );
}
if (!$experience) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// ── Category ──────────────────────────────────────────────────────────────────
$category = null;
if ($_hasCategoryId && !empty($experience['category_id']) && $_hasCatTable) {
    $_emojiSel = db_column_exists('experience_categories', 'emoji') ? ', emoji' : ", '🌿' AS emoji";
    $category = fetch_one(
        "SELECT id, name, slug {$_emojiSel} FROM experience_categories WHERE id = :id LIMIT 1",
        ['id' => $experience['category_id']]
    );
}

// ── Gallery ───────────────────────────────────────────────────────────────────
$gallery = [];
if (db_table_exists('experience_gallery')) {
    $gallery = fetch_all(
        "SELECT image_path, alt_text FROM experience_gallery
          WHERE experience_id = :id ORDER BY sort_order ASC, id ASC",
        ['id' => $experience['id']]
    );
}

// ── Related destinations ──────────────────────────────────────────────────────
$relatedDestinations = [];
if (db_table_exists('experience_destination') && db_table_exists('destinations')) {
    $relatedDestinations = fetch_all(
        "SELECT d.id, d.name, d.slug, d.excerpt, d.cover_image, d.hero_image,
                " . (db_column_exists('destinations', 'emoji')        ? "d.emoji,"        : "'📍' AS emoji,") . "
                " . (db_column_exists('destinations', 'stay_duration')? "d.stay_duration" : "'' AS stay_duration") . "
         FROM experience_destination ed
         INNER JOIN destinations d ON d.id = ed.destination_id
         WHERE ed.experience_id = :id AND d.is_published = 1
         ORDER BY d.sort_order ASC, d.name ASC
         LIMIT 6",
        ['id' => $experience['id']]
    );
}

// ── Related itineraries ───────────────────────────────────────────────────────
$relatedItineraries = [];

// Use fetch_all directly — works even without the helper
if (db_table_exists('experience_itinerary') && db_table_exists('itineraries')) {
    $relatedItineraries = fetch_all(
        "SELECT i.id, i.name, i.slug, i.days, i.excerpt, i.hero_image, i.og_image
         FROM experience_itinerary ei
         INNER JOIN itineraries i ON i.id = ei.itinerary_id
         WHERE ei.experience_id = :id AND i.is_published = 1
         ORDER BY i.is_featured DESC, i.sort_order ASC, i.days ASC
         LIMIT 6",
        ['id' => $experience['id']]
    );
}

// ── Related experiences ───────────────────────────────────────────────────────
$relatedExperiences = [];
if (db_table_exists('experience_related')) {
    $relatedExperiences = fetch_all(
        "SELECT e.id, e.title, e.slug, e.excerpt, e.hero_image, e.og_image, " . content_image_select_fragment('experiences','e') . "
         FROM experience_related er
         INNER JOIN experiences e ON e.id = er.related_experience_id
         WHERE er.experience_id = :id AND e.is_published = 1
         ORDER BY e.sort_order ASC
         LIMIT 4",
        ['id' => $experience['id']]
    );
}

// ── Day arc ───────────────────────────────────────────────────────────────────
$dayArc = [];
if ($_hasDayArc && !empty($experience['day_arc_json'])) {
    $decoded = json_decode((string)$experience['day_arc_json'], true);
    if (is_array($decoded)) {
        $dayArc = array_values(array_filter($decoded, fn($s) =>
            is_array($s) && (!empty($s['heading']) || !empty($s['body']))
        ));
    }
}

// ── Tips ──────────────────────────────────────────────────────────────────────
$tips = [];
if (!empty($experience['tips_json'])) {
    $decodedTips = json_decode((string)$experience['tips_json'], true);
    if (is_array($decodedTips)) {
        $tips = array_values(array_filter(array_map('strval', $decodedTips)));
    }
}

// ── Content ───────────────────────────────────────────────────────────────────
$expTitle   = trim((string)($experience['title'] ?? ''));
$expExcerpt = clean_public_text($experience['excerpt']           ?? '');
$expIntro   = clean_public_text($experience['intro']             ?? '');
$expWhere   = clean_public_text($experience['where_to_experience']?? '');
$expBest    = clean_public_text($experience['best_time']         ?? '');

// ── Hero ──────────────────────────────────────────────────────────────────────
$heroMedia = content_hero_media($experience);
$pageImage = og_image_url(array_replace($experience, ['hero_image'=>content_image_path($experience, ['hero_image','cover_image'])]));
$heroBg    = $heroMedia['type'] === 'video'
    ? ($heroMedia['poster_url'] ?: $pageImage)
    : ($heroMedia['url'] ?: $pageImage);
$heroBackground = is_placeholder_media_url($heroBg) ? '' : $heroBg;

// ── SEO ───────────────────────────────────────────────────────────────────────
$pageUrl        = !empty($experience['canonical_url'])
    ? $experience['canonical_url']
    : base_url('experiences/' . $experience['slug']);
$seoTitle       = $experience['meta_title']
    ?: ($expTitle . ' in Sri Lanka | ' . APP_NAME);
$seoDescription = $experience['meta_description']
    ?: ($expExcerpt ?: 'Discover the experience of ' . $expTitle . ' in Sri Lanka with Island Culture Travels.');
$seoCanonical   = $pageUrl;
$seoImage       = $pageImage;
$seoImageWidth  = 1200;
$seoImageHeight = 675;
$seoType        = 'website';

$breadcrumbs = [
    ['name' => 'Home',        'url' => base_url()],
    ['name' => 'Experiences', 'url' => base_url('experiences')],
    ['name' => $expTitle,     'url' => $pageUrl],
];

$schemaItems = [];
foreach ($relatedItineraries as $it) {
    $schemaItems[] = [
        'url'   => base_url('itineraries/' . $it['slug']),
        'name'  => (string)$it['name'],
        'image' => (package_card_image($it, 'itinerary') ?: DEFAULT_OG_IMAGE),
    ];
}
$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_experience($experience, $pageUrl),
    [
        '@type'       => 'WebPage',
        'name'        => $expTitle . ' in Sri Lanka',
        'url'         => $pageUrl,
        'description' => $seoDescription,
        'isPartOf'    => ['@id' => base_url() . '#website'],
        'about'       => ['@type' => 'Thing', 'name' => $expTitle],
    ],
];
if ($schemaItems) {
    $schemaGraph[] = schema_item_list($expTitle . ' Related Itineraries', $schemaItems);
}

$bodyClass = 'page-experience';
require_once __DIR__.'/includes/page_gallery_view.php';
$pageGallery=page_gallery_rows('experience',(int)$experience['id']);
$seoImageAlt=(string)($experience['og_alt']??'');
$extraStylesheets[]='assets/css/page-gallery.css';
if(!empty($experience['og_image']))$seoImage=stored_media_url($experience['og_image']);
if($pageGallery)$schemaGraph[]=['@type'=>'ImageGallery','name'=>$expTitle.' photographs','image'=>page_gallery_schema($pageGallery)];
require __DIR__ . '/includes/header.php';

// ── Day arc visual config ─────────────────────────────────────────────────────
$_arcBgs = [
    'linear-gradient(135deg,#2d6a41,#8f7a28)',
    'linear-gradient(135deg,#1a3a6a,#2a6aaa)',
    'linear-gradient(135deg,#6a3a1a,#aa7a2a)',
    'linear-gradient(135deg,#1a1a3a,#2a2a5a)',
];
$_arcIconBgs = ['var(--brand)', 'var(--accent)', '#1a6a5a', '#1a1a3a'];
?>

<!-- ════════════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════════════ -->
<section class="exp-detail-hero"
         <?php if ($heroBackground): ?>style="background-image:linear-gradient(160deg,rgba(13,46,26,.9) 0%,rgba(26,90,48,.72) 100%),url('<?= e($heroBackground) ?>');background-size:cover;background-position:center;"<?php endif; ?>>
  <div class="container">
    <nav aria-label="Breadcrumb" class="exp-breadcrumbs" style="margin-bottom:1.25rem;">
      <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
    </nav>

    <span class="section-eyebrow" style="color:var(--accent-light);">Sri Lanka Experience</span>
    <h1 class="section-title exp-title-sweep" style="color:#fff; font-size:clamp(1.8rem,4vw,3rem);">
      <span class="exp-title-inner"><?= e($expTitle) ?></span>
    </h1>
    <?php if ($expExcerpt): ?>
    <p class="section-lead" style="color:rgba(255,255,255,.84);max-width:560px;margin-top:.6rem;">
      <?= e($expExcerpt) ?>
    </p>
    <?php endif; ?>

    <!-- Category + quick-facts chips -->
    <div class="exp-detail-chips">
      <?php if ($category): ?>
      <a href="<?= e(base_url('experiences') . '?mood=' . urlencode($category['slug'])) ?>"
         class="exp-detail-chip exp-detail-chip--cat">
        <?= $category['emoji'] ?? '🌿' ?> <?= e($category['name']) ?>
      </a>
      <?php endif; ?>
      <?php if (!empty($experience['best_time']) && strlen($experience['best_time']) < 30): ?>
      <span class="exp-detail-chip">📅 <?= e($experience['best_time']) ?></span>
      <?php endif; ?>
      <?php if ($relatedDestinations): ?>
      <span class="exp-detail-chip">📍 <?= count($relatedDestinations) ?> destination<?= count($relatedDestinations) !== 1 ? 's' : '' ?></span>
      <?php endif; ?>
      <?php if (count($relatedItineraries) > 0): ?>
      <span class="exp-detail-chip">🗺 <?= count($relatedItineraries) ?> itinerar<?= count($relatedItineraries) === 1 ? 'y' : 'ies' ?></span>
      <?php endif; ?>
    </div>

    <!-- CTAs - side by side on mobile -->
    <div class="exp-detail-ctas">
      <a href="<?= e(base_url('contact') . '?experience=' . urlencode($expTitle)) ?>"
         class="btn btn-primary"
         onclick="if(typeof trackEvent==='function')trackEvent('contact_cta_click',{label:'experience_hero'})">
        Add to My Trip
      </a>
      <a href="<?= e(site_whatsapp_url('Hi, I\'m interested in the ' . $expTitle . ' experience in Sri Lanka.')) ?>"
         class="btn btn-whatsapp"
         target="_blank" rel="noopener"
         onclick="if(typeof trackEvent==='function')trackEvent('whatsapp_click',{label:'experience_hero'})">
        WhatsApp
      </a>
    </div>

    <!-- Culture | Destinations | Itineraries — one line with icons (mobile) -->
    <div class="exp-meta-line" aria-label="Experience details">
      <div class="exp-meta-item">
        <i class="fa-solid fa-landmark" aria-hidden="true"></i>
        <span>Culture</span>
      </div>
      <div class="exp-meta-item">
        <i class="fa-solid fa-map-marker-alt" aria-hidden="true"></i>
        <span><?= count($relatedDestinations) ?> destinations</span>
      </div>
      <div class="exp-meta-item">
        <i class="fa-solid fa-route" aria-hidden="true"></i>
        <span><?= count($relatedItineraries) ?> itineraries</span>
      </div>
    </div>
  </div>
</section>
<!-- ════════════════════════════════════════════════════════════
     INLINE ENQUIRY PROMPT
════════════════════════════════════════════════════════════ -->
<div class="exp-inquiry-prompt">
  <div class="container">
    <div class="exp-inquiry-inner">
      <div class="exp-inquiry-text">
        <strong>Want to include this on your trip?</strong>
        <p>Tell us your dates and interests — we'll fit <?= e($expTitle) ?> into a private route that makes sense on the road.</p>
      </div>
      <div class="exp-inquiry-actions">
        <a href="<?= e(base_url('contact') . '?experience=' . urlencode($expTitle)) ?>"
           class="btn btn-primary btn-sm">Add to My Trip →</a>
        <a href="<?= e(site_whatsapp_url('Hi, I\'m interested in adding ' . $expTitle . ' to my Sri Lanka trip.')) ?>"
           class="btn btn-whatsapp btn-sm" target="_blank" rel="noopener">WhatsApp</a>
      </div>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     INTRO / OVERVIEW
════════════════════════════════════════════════════════════ -->
<?php if ($expIntro): ?>
<section class="exp-section">
  <div class="container exp-section-prose">
    <div class="section-head" style="margin-bottom:1.5rem;">
      <div>
        <span class="section-eyebrow">Overview</span>
        <h2 class="section-title exp-section-heading" style="margin-bottom:0;">About This <em>Experience</em></h2>
      </div>
    </div>
    <div class="exp-prose"><?= $expIntro ?></div>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     DAY-IN-THE-LIFE ARC
════════════════════════════════════════════════════════════ -->
<?php if ($dayArc): ?>
<section class="exp-section exp-section--alt" id="day-arc">
  <div class="container">
    <div class="section-head" style="margin-bottom:1.5rem;">
      <div>
        <span class="section-eyebrow">How the day unfolds</span>
        <h2 class="section-title exp-section-heading" style="margin-bottom:0;">A Day in the <em><?= e(mb_strimwidth($expTitle, 0, 30, '…')) ?></em></h2>
        <p class="section-lead" style="margin-top:.5rem;">So you know exactly what to expect — and how to time it.</p>
      </div>
    </div>

    <div class="exp-arc-grid">
      <?php foreach ($dayArc as $ai => $seg):
          $arcBg     = $_arcBgs[$ai % count($_arcBgs)];
          $iconBg    = $_arcIconBgs[$ai % count($_arcIconBgs)];
          $arcImgUrl = !empty($seg['image_path']) ? stored_media_url((string)$seg['image_path']) : '';
      ?>
      <div class="exp-arc-segment">
        <div class="exp-arc-img"
             style="background:<?= $arcBg ?><?= $arcImgUrl ? ';background-image:url(\'' . e($arcImgUrl) . '\');background-size:cover;background-position:center' : '' ?>"></div>
        <div class="exp-arc-time">
          <div class="exp-arc-icon" style="background:<?= $iconBg ?>;"><?= e($seg['icon'] ?? '🕐') ?></div>
          <?= e($seg['time_of_day'] ?? '') ?>
        </div>
        <?php if ($seg['heading'] ?? ''): ?>
        <h3 class="exp-arc-heading"><?= e($seg['heading']) ?></h3>
        <?php endif; ?>
        <?php if ($seg['body'] ?? ''): ?>
        <p class="exp-arc-body"><?= e($seg['body']) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     GALLERY
════════════════════════════════════════════════════════════ -->


<!-- ════════════════════════════════════════════════════════════
     WHERE TO EXPERIENCE + BEST TIME
════════════════════════════════════════════════════════════ -->
<?php if ($expWhere || $expBest): ?>
<section class="exp-section exp-section--alt">
  <div class="container exp-2col">
    <?php if ($expWhere): ?>
    <div>
      <span class="section-eyebrow">On the road</span>
      <h2 class="section-title exp-section-heading" style="font-size:clamp(1.25rem,2vw,1.7rem);">Where to <em>Experience It</em></h2>
      <div class="exp-prose"><?= $expWhere ?></div>
    </div>
    <?php endif; ?>
    <?php if ($expBest): ?>
    <div>
      <span class="section-eyebrow">Planning</span>
      <h2 class="section-title exp-section-heading" style="font-size:clamp(1.25rem,2vw,1.7rem);">Best Time &amp; <em>Travel Notes</em></h2>
      <div class="exp-prose"><?= $expBest ?></div>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     TIPS
════════════════════════════════════════════════════════════ -->
<?php if ($tips): ?>
<section class="exp-section">
  <div class="container">
    <div class="section-head" style="margin-bottom:1.5rem;">
      <div>
        <span class="section-eyebrow">Know before you go</span>
        <h2 class="section-title exp-section-heading" style="margin-bottom:0;">Tips to <em>Remember</em></h2>
      </div>
    </div>
    <ul class="exp-tips-list">
      <?php foreach ($tips as $tip): ?>
      <li><?= e($tip) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     WHERE TO EXPERIENCE — larger destination cards
═════════════════════════════════════════════════════════════ -->
<?php if ($relatedDestinations): ?>
<section class="exp-section exp-section--alt" id="destinations">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-eyebrow">Where to Experience</span>
        <h2 class="section-title exp-section-heading" style="margin-bottom:0;">Where to <em>Experience</em></h2>
        <p class="section-lead" style="margin-top:.5rem;">Combine this experience with nearby places on your private route.</p>
      </div>
      <a href="<?= e(base_url('destinations')) ?>" class="section-link">All destinations →</a>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px,1fr)); gap:1rem;">
      <?php foreach ($relatedDestinations as $rd):
          $rdImg = !empty($rd['cover_image'])
              ? upload_url($rd['cover_image'])
              : (!empty($rd['hero_image']) ? upload_url($rd['hero_image']) : '');
      ?>
      <a href="<?= e(base_url('destinations/' . $rd['slug'])) ?>" class="card-dest">
        <?php if ($rdImg && !is_placeholder_media_url($rdImg)): ?>
          <?= responsive_image([
              'src' => $rdImg,
              'alt' => $rd['name'],
              'widths' => [400, 800],
              'sizes' => '(min-width: 992px) 220px, (min-width: 640px) 45vw, 100vw',
              'width' => 640,
              'height' => 640,
              'loading' => 'lazy',
          ]) ?>
        <?php else: ?>
          <div style="position:absolute; inset:0; background:linear-gradient(135deg, var(--brand), var(--surface-mid));"></div>
        <?php endif; ?>
        <span class="card-dest-overlay"></span>
        <span class="card-dest-name"><?= e($rd['name']) ?></span>
      </a>
      <?php endforeach; ?>
      <a href="<?= e(base_url('destinations')) ?>" class="card-dest" style="background:var(--surface-alt); border:1px dashed var(--line-strong);">
        <span style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:var(--muted);">→</span>
        <span class="card-dest-name" style="color:var(--text); text-shadow:none;">All Destinations</span>
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     RELATED ITINERARIES
════════════════════════════════════════════════════════════ -->
<?php if ($relatedItineraries): ?>
<section class="exp-section" id="itineraries">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-eyebrow">Keep the Journey Going</span>
        <h2 class="section-title exp-section-heading" style="margin-bottom:0;">More Routes to <em>Roam</em></h2>
        <p class="section-lead" style="margin-top:.5rem;">Use these routes as starting points — adjust the experience mix around your dates and pace.</p>
      </div>
      <a href="<?= e(base_url('itineraries')) ?>" class="section-link">View all itineraries →</a>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px,1fr)); gap:1.25rem;">
      <?php foreach ($relatedItineraries as $it):
          $itImg  = (package_card_image($it, 'itinerary') ?: '');
          $itDays = (int)$it['days'];
          $itExcerpt = !empty($it['excerpt']) ? mb_strimwidth((string)$it['excerpt'], 0, 85, '…') : '';
      ?>
      <a href="<?= e(base_url('itineraries/' . $it['slug'])) ?>" class="card" style="text-decoration:none; color:inherit;">
        <div class="card-thumb">
          <?php if ($itImg && !is_placeholder_media_url($itImg)): ?>
            <?= responsive_image([
                'src' => $itImg,
                'alt' => package_card_alt($it, 'itinerary'),
                'widths' => [400, 800],
                'sizes' => '(min-width: 992px) 360px, 100vw',
                'width' => 640,
                'height' => 480,
                'loading' => 'lazy',
            ]) ?>
          <?php else: ?>
            <div style="width:100%;height:100%;background:linear-gradient(135deg, var(--brand), var(--surface-mid));"></div>
          <?php endif; ?>
          <?php if ($itDays > 0): ?>
            <span class="card-badge"><?= $itDays ?> days</span>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <h3 class="card-title" style="font-size:1.05rem;"><?= e($it['name']) ?></h3>
          <?php if ($itExcerpt): ?><p class="card-excerpt"><?= e($itExcerpt) ?></p><?php endif; ?>
          <div class="card-footer">
            <span class="card-price-label">Private tour</span>
            <span class="card-price"><?= $itDays ? $itDays . ' days' : 'Custom route' ?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     RELATED EXPERIENCES
════════════════════════════════════════════════════════════ -->
<?php if ($relatedExperiences): ?>
<section class="exp-section exp-section--alt" id="related">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="section-eyebrow">More to explore</span>
        <h2 class="section-title exp-section-heading" style="margin-bottom:0;">Related <em>Experiences</em></h2>
      </div>
      <a href="<?= e(base_url('experiences')) ?>" class="section-link">All experiences →</a>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px,1fr)); gap:1rem;">
      <?php foreach ($relatedExperiences as $rel):
          $relatedImagePath = content_image_path($rel, ['hero_image','og_image']);
          $relImg = $relatedImagePath !== '' ? stored_media_url($relatedImagePath) : '';
      ?>
      <a href="<?= e(experience_url($rel)) ?>" class="card-exp">
        <?php if ($relImg && !is_placeholder_media_url($relImg)): ?>
          <?= responsive_image([
              'src' => $relImg,
              'alt' => $rel['title'],
              'widths' => [400, 800],
              'sizes' => '(min-width: 992px) 360px, 100vw',
              'width' => 640,
              'height' => 480,
              'loading' => 'lazy',
          ]) ?>
        <?php else: ?>
          <div style="position:absolute; inset:0; background:linear-gradient(135deg, var(--brand), var(--surface-mid));"></div>
        <?php endif; ?>
        <span class="card-exp-overlay"></span>
        <span class="card-exp-body">
          <span class="card-exp-title"><?= e($rel['title']) ?></span>
          <?php if ($rel['excerpt'] ?? ''): ?><span class="card-exp-excerpt"><?= e(mb_strimwidth($rel['excerpt'], 0, 65, '…')) ?></span><?php endif; ?>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php page_gallery_assets();page_gallery_public($pageGallery,$expTitle); ?>

<?php require __DIR__ . '/includes/cta.php'; ?>

<style>
/* ── Hero ────────────────────────────────────────────────── */
.exp-detail-hero {
  background:linear-gradient(160deg,#0d2e1a 0%,#1a5a30 100%);
  padding:var(--site-overlay-hero-offset, 6rem) 0 2.75rem;
  position:relative;overflow:hidden;
}
.exp-detail-hero::before {
  content:'';position:absolute;
  width:380px;height:380px;border-radius:50%;
  background:radial-gradient(circle,rgba(197,168,74,.1),transparent 70%);
  top:-100px;right:-60px;pointer-events:none;
}
.exp-detail-hero .container { position:relative;z-index:1; }

/* Chips */
.exp-detail-chips { display:flex;gap:.5rem;flex-wrap:nowrap;overflow-x:auto;margin-top:1.2rem;scrollbar-width:none;-webkit-overflow-scrolling:touch; }
.exp-detail-chips::-webkit-scrollbar { display:none; }
.exp-detail-chip {
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);
  backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
  color:#fff;font-size:.7rem;font-weight:600;white-space:nowrap;
  padding:.34rem .82rem;border-radius:999px;text-decoration:none;
  transition:background 150ms;flex-shrink:0;
}
.exp-detail-chip:hover { background:rgba(255,255,255,.18); }
.exp-detail-chip--cat { background:rgba(197,168,74,.2);border-color:rgba(197,168,74,.4); }

/* ── Meta line (Culture | Destinations | Itineraries) - one line mobile ── */
.exp-meta-line {
  display: inline-flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  margin-top: 0.75rem;
  font-size: 0.8rem;
  color: rgba(255,255,255,0.85);
}
.exp-meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-weight: 500;
  white-space: nowrap;
}
.exp-meta-item i {
  font-size: 0.9rem;
  color: var(--accent-light, #c8a44a);
}
@media (max-width: 575.98px) {
  .exp-meta-line {
    gap: 0.75rem;
    font-size: 0.75rem;
  }
  .exp-meta-item {
    gap: 0.25rem;
  }
}

/* CTAs */
.exp-detail-ctas {
  display:flex;gap:.6rem;margin-top:1.4rem;flex-wrap:nowrap;
}
@media (max-width:480px) {
  .exp-detail-ctas { flex-wrap:nowrap; }
  .exp-detail-ctas .btn-hero-primary,
  .exp-detail-ctas .btn-hero-wa {
    flex:1 1 0;text-align:center;padding:.65rem .8rem;font-size:.78rem;
  }
  .exp-detail-ctas .btn-hero-ghost {
    flex:0 0 auto;padding:.65rem .8rem;font-size:.78rem;
  }
}
.btn-hero-primary {
  display:inline-block;background:var(--brand);color:#fff;
  font-size:.84rem;font-weight:700;padding:.68rem 1.5rem;
  border-radius:999px;text-decoration:none;transition:background 150ms;
}
.btn-hero-primary:hover { background:var(--brand-dark); }
.btn-hero-wa {
  display:inline-block;background:#25d366;color:#fff;
  font-size:.84rem;font-weight:700;padding:.68rem 1.3rem;
  border-radius:999px;text-decoration:none;
}
.btn-hero-ghost {
  display:inline-block;background:transparent;color:#fff;
  font-size:.82rem;font-weight:600;padding:.68rem 1.3rem;
  border-radius:999px;text-decoration:none;
  border:1px solid rgba(255,255,255,.3);transition:background 150ms;
}
.btn-hero-ghost:hover { background:rgba(255,255,255,.08); }

/* ── Inquiry prompt ──────────────────────────────────────── */
.exp-inquiry-prompt {
  background:var(--surface);
  border-bottom:1px solid var(--line);
  box-shadow:var(--shadow-sm);
}
.exp-inquiry-inner {
  display:flex;flex-direction:column;gap:.85rem;
  padding:1.1rem 0;
}
@media (min-width:600px) {
  .exp-inquiry-inner { flex-direction:row;align-items:center;justify-content:space-between; }
}
.exp-inquiry-text strong { font-size:.9rem;color:var(--text);display:block;margin-bottom:.2rem; }
.exp-inquiry-text p { font-size:.78rem;color:var(--muted);margin:0; }
.exp-inquiry-actions { display:flex;gap:.5rem;flex-wrap:wrap;flex-shrink:0; }
.btn-brand-sm {
  display:inline-block;background:var(--brand);color:#fff;
  font-size:.8rem;font-weight:700;padding:.54rem 1.15rem;
  border-radius:999px;text-decoration:none;white-space:nowrap;
}
.btn-wa-sm {
  display:inline-block;background:#25d366;color:#fff;
  font-size:.8rem;font-weight:700;padding:.54rem 1rem;
  border-radius:999px;text-decoration:none;white-space:nowrap;
}

/* ── Sections ────────────────────────────────────────────── */
.exp-section { padding:2.75rem 0;border-bottom:1px solid var(--line); }
.exp-section:last-of-type { border-bottom:none; }
.exp-section--alt { background:var(--surface-alt); }
.exp-section-heading {
  font-family:var(--font-display,Georgia,serif);
  font-size:clamp(1.4rem,2.5vw,1.9rem);
  font-weight:400;color:var(--text);margin-bottom:.85rem;
}
.exp-section-heading em { font-style:italic; }
.exp-section-sub { font-size:.82rem;color:var(--muted);margin-bottom:1.5rem;margin-top:-.4rem; }
.exp-prose { font-size:.9rem;line-height:1.8;color:var(--text-body); }
.exp-section-prose { max-width:720px; }
.exp-2col {
  display:grid;grid-template-columns:1fr;gap:2rem;
}
@media (min-width:640px) { .exp-2col { grid-template-columns:repeat(2,1fr); } }

/* ── Day Arc ─────────────────────────────────────────────── */
.exp-arc-grid {
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:1.1rem;
  position:relative;
}
@media (max-width:699px) {
  .exp-arc-grid {
    display:flex;
    overflow-x:auto;
    scroll-snap-type:x mandatory;
    gap:1rem;
    padding-bottom:.5rem;
    scrollbar-width:none;
  }
  .exp-arc-grid::-webkit-scrollbar { display:none; }
  .exp-arc-segment {
    flex:0 0 85%;
    scroll-snap-align:start;
  }
}
@media (min-width:700px) {
  .exp-arc-grid { grid-template-columns:repeat(4,1fr); }
  /* horizontal connector */
  .exp-arc-grid::before {
    content:'';position:absolute;
    top:27px;left:12%;right:12%;height:2px;
    background:linear-gradient(to right,var(--brand),var(--accent),var(--brand));
    opacity:.2;z-index:0;
  }
}
.exp-arc-segment {
  background:var(--surface);border-radius:var(--radius-md);
  padding:1.1rem;border:1px solid var(--line);
  box-shadow:var(--shadow-sm);position:relative;z-index:1;
  opacity:0;transform:translateY(20px);
  animation:arcFadeIn .5s ease forwards;
}
.exp-arc-segment:nth-child(1) { animation-delay:.1s; }
.exp-arc-segment:nth-child(2) { animation-delay:.2s; }
.exp-arc-segment:nth-child(3) { animation-delay:.3s; }
.exp-arc-segment:nth-child(4) { animation-delay:.4s; }
.exp-arc-segment:nth-child(5) { animation-delay:.5s; }
@keyframes arcFadeIn { to { opacity:1;transform:translateY(0); } }
@media (prefers-reduced-motion: reduce) {
  .exp-arc-segment { opacity:1;transform:none;animation:none; }
}
.exp-arc-img {
  height:72px;border-radius:.5rem;margin-bottom:.7rem;
  background:linear-gradient(135deg,#2d6a41,#8f7a28);
  background-size:cover;background-position:center;
}
.exp-arc-time {
  font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
  color:var(--accent);margin-bottom:.55rem;
  display:flex;align-items:center;gap:.4rem;
}
.exp-arc-icon {
  width:26px;height:26px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:.8rem;flex-shrink:0;color:#fff;
}
.exp-arc-heading { font-size:.88rem;font-weight:700;color:var(--text);margin-bottom:.35rem; }
.exp-arc-body    { font-size:.75rem;color:var(--muted);line-height:1.65; }

/* ── Gallery ─────────────────────────────────────────────── */
.exp-gallery-grid {
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:.65rem;
}
@media (min-width:600px) { .exp-gallery-grid { grid-template-columns:repeat(3,1fr); } }
@media (min-width:900px) { .exp-gallery-grid { grid-template-columns:repeat(4,1fr); } }
.exp-gallery-cell {
  border-radius:var(--radius-sm);overflow:hidden;aspect-ratio:4/3;
  opacity:0;transform:translateY(20px) scale(.96);
  animation:galleryFadeIn .5s ease forwards;
}
.exp-gallery-cell:nth-child(1) { animation-delay:.05s; }
.exp-gallery-cell:nth-child(2) { animation-delay:.1s; }
.exp-gallery-cell:nth-child(3) { animation-delay:.15s; }
.exp-gallery-cell:nth-child(4) { animation-delay:.2s; }
.exp-gallery-cell:nth-child(5) { animation-delay:.25s; }
.exp-gallery-cell:nth-child(6) { animation-delay:.3s; }
.exp-gallery-cell:nth-child(n+7) { animation-delay:.35s; }
@keyframes galleryFadeIn { to { opacity:1;transform:translateY(0) scale(1); } }
@media (prefers-reduced-motion: reduce) {
  .exp-gallery-cell { opacity:1;transform:none;animation:none; }
}
.exp-gallery-cell img { width:100%;height:100%;object-fit:cover;display:block; }

/* ── Tips ────────────────────────────────────────────────── */
.exp-tips-list {
  list-style:none;padding:0;margin:0;
  display:grid;grid-template-columns:1fr;gap:.55rem;
}
@media (min-width:600px) { .exp-tips-list { grid-template-columns:repeat(2,1fr); } }
.exp-tips-list li {
  padding:.65rem .9rem .65rem 2.2rem;
  background:var(--surface-alt);border-radius:var(--radius-sm);
  font-size:.84rem;color:var(--text-body);position:relative;
}
.exp-tips-list li::before {
  content:'✓';
  position:absolute;left:.75rem;top:.65rem;
  color:var(--brand);font-weight:700;font-size:.82rem;
}

/* ── Destination chips scroll — LARGER ─────────────────── */
.exp-dest-scroll {
  display:flex;gap:1rem;overflow-x:auto;
  padding-bottom:.5rem;scrollbar-width:none;
  scroll-snap-type:x mandatory;
}
.exp-dest-scroll::-webkit-scrollbar { display:none; }
.exp-dest-chip {
  flex-shrink:0;width:200px;
  border-radius:var(--radius-md);overflow:hidden;
  border:1px solid var(--line);cursor:pointer;
  text-decoration:none;color:#fff;
  transition:transform 200ms,box-shadow 200ms;
  scroll-snap-align:start;
  position:relative;
}
.exp-dest-chip:hover { transform:translateY(-4px);box-shadow:var(--shadow-md); }
.exp-dest-chip-img {
  height:140px;
  background:linear-gradient(135deg,#1a4a2a,#3a8a5a);
  background-size:cover;background-position:center;
  display:flex;align-items:center;justify-content:center;font-size:1.5rem;
}
.exp-dest-chip-img--more {
  background:var(--surface-alt);font-size:1.1rem;color:var(--muted);
}
.exp-dest-chip--more { border:1px dashed var(--line-strong);color:var(--text); }
.exp-dest-chip-label {
  position:absolute;bottom:0;left:0;right:0;
  padding:.75rem .85rem;
  background:linear-gradient(transparent,rgba(0,0,0,.65));
  backdrop-filter:blur(4px);
  font-size:.82rem;font-weight:700;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}

/* ── Related itineraries row ─────────────────────────────── */
.exp-itin-row {
  display:grid;grid-template-columns:1fr;gap:.75rem;
}
@media (min-width:480px) { .exp-itin-row { grid-template-columns:repeat(2,1fr); } }
@media (min-width:780px) { .exp-itin-row { grid-template-columns:repeat(3,1fr); } }
@media (max-width:479px) {
  .exp-itin-row {
    display:flex;gap:.75rem;overflow-x:auto;
    scroll-snap-type:x mandatory;scrollbar-width:none;
    padding-bottom:.5rem;
  }
  .exp-itin-row::-webkit-scrollbar { display:none; }
  .exp-itin-card {
    flex:0 0 82%;scroll-snap-align:start;
  }
}
.exp-itin-card {
  display:flex;flex-direction:column;
  background:var(--surface);border:1px solid var(--line);
  border-radius:var(--radius-md);overflow:hidden;
  text-decoration:none;color:var(--text);
  transition:transform 150ms,box-shadow 150ms;
  opacity:0;transform:translateY(20px);
  animation:itinFadeIn .5s ease forwards;
}
.exp-itin-card:nth-child(1) { animation-delay:.05s; }
.exp-itin-card:nth-child(2) { animation-delay:.1s; }
.exp-itin-card:nth-child(3) { animation-delay:.15s; }
.exp-itin-card:nth-child(4) { animation-delay:.2s; }
.exp-itin-card:nth-child(5) { animation-delay:.25s; }
.exp-itin-card:nth-child(6) { animation-delay:.3s; }
@keyframes itinFadeIn { to { opacity:1;transform:translateY(0); } }
@media (prefers-reduced-motion: reduce) {
  .exp-itin-card { opacity:1;transform:none;animation:none; }
}
.exp-itin-card:hover { transform:translateY(-3px);box-shadow:var(--shadow-sm); }
.exp-itin-card-img {
  height:105px;
  background:linear-gradient(160deg,#0a2a18,#2d6a41);
  background-size:cover;background-position:center;
  position:relative;
}
.exp-itin-badge {
  position:absolute;top:.6rem;right:.6rem;
  background:#fff;color:var(--brand);
  font-size:.65rem;font-weight:700;
  padding:.18rem .5rem;border-radius:999px;
}
.exp-itin-card-body { padding:.75rem; }
.exp-itin-card-body strong { font-size:.82rem;font-weight:700;display:block;margin-bottom:.2rem; }
.exp-itin-card-body span   { font-size:.7rem;color:var(--muted); }
.exp-all-link { font-size:.82rem;font-weight:600;color:var(--brand);text-decoration:none; }

/* ── Related experiences ─────────────────────────────────── */
.exp-related-row {
  display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem;
}
@media (min-width:640px) { .exp-related-row { grid-template-columns:repeat(4,1fr); } }
.exp-related-card {
  display:flex;flex-direction:column;
  background:var(--surface);border:1px solid var(--line);
  border-radius:var(--radius-md);overflow:hidden;
  text-decoration:none;color:var(--text);
  transition:transform 150ms,box-shadow 150ms;
}
.exp-related-card:hover { transform:translateY(-3px);box-shadow:var(--shadow-sm); }
.exp-related-img {
  height:90px;
  background:linear-gradient(135deg,#1a4a2a,#3a8a5a);
  background-size:cover;background-position:center;
}
.exp-related-body { padding:.7rem; }
.exp-related-body strong { font-size:.8rem;font-weight:700;display:block;margin-bottom:.18rem; }
.exp-related-body span   { font-size:.68rem;color:var(--muted); }

/* ── Related experiences (Experiences on This Route) — horizontal carousel ── */
.exp-related-carousel {
  position: relative;
  margin: 0 -1rem;
  padding: 0 1rem;
}
.exp-related-track {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-padding: 0 1rem;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  padding: 0.5rem 0 1rem;
}
.exp-related-track::-webkit-scrollbar { display: none; }
.exp-related-slide {
  flex: 0 0 78vw;
  max-width: 340px;
  min-width: 280px;
  scroll-snap-align: start;
  position: relative;
  border-radius: 1rem;
  overflow: hidden;
  text-decoration: none;
  color: #fff;
  aspect-ratio: 3/4;
}
.exp-related-slide-img {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #1a4a2a, #3a8a5a);
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  transition: transform 0.4s ease;
}
.exp-related-slide:hover .exp-related-slide-img { transform: scale(1.05); }
.exp-related-slide-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 2rem 1.25rem 1.25rem;
  background: linear-gradient(transparent, rgba(0,0,0,0.7));
  backdrop-filter: blur(4px);
}
.exp-related-slide-overlay strong {
  display: block;
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 0.2rem;
}
.exp-related-slide-overlay span {
  font-size: 0.75rem;
  opacity: 0.8;
}
.exp-related-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255,255,255,0.9);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  color: #0a1f10;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  z-index: 5;
  transition: all 0.2s ease;
}
.exp-related-arrow:hover {
  background: #fff;
  transform: translateY(-50%) scale(1.1);
}
.exp-related-prev { left: 0.5rem; }
.exp-related-next { right: 0.5rem; }

/* ── Destination Carousel (Where to Experience) ── */
.exp-dest-carousel {
  position: relative;
  margin: 0 -1rem;
  padding: 0 1rem;
}
.exp-dest-track {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-padding: 0 1rem;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  padding: 0.5rem 0 1rem;
}
.exp-dest-track::-webkit-scrollbar { display: none; }
.exp-dest-card {
  flex-shrink: 0;
  width: 200px;
  border-radius: 1rem;
  overflow: hidden;
  border: 1px solid var(--line);
  cursor: pointer;
  text-decoration: none;
  color: #fff;
  transition: transform 200ms, box-shadow 200ms;
  scroll-snap-align: start;
  position: relative;
}
.exp-dest-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
.exp-dest-card-img {
  height: 140px;
  background: linear-gradient(135deg, #1a4a2a, #3a8a5a);
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}
.exp-dest-card-img--more {
  background: var(--surface-alt);
  font-size: 1.1rem;
  color: var(--muted);
}
.exp-dest-card--more { border: 1px dashed var(--line-strong); color: var(--text); }
.exp-dest-card-body {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 0.75rem 0.85rem;
  background: linear-gradient(transparent, rgba(0,0,0,0.65));
  backdrop-filter: blur(4px);
  font-size: 0.82rem;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.exp-dest-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255,255,255,0.9);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  color: #0a1f10;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  z-index: 5;
  transition: all 0.2s ease;
}
.exp-dest-arrow:hover {
  background: #fff;
  transform: translateY(-50%) scale(1.1);
}
.exp-dest-prev { left: 0.5rem; }
.exp-dest-next { right: 0.5rem; }

/* ── Itinerary Carousel (More Routes to Roam) ── */
.exp-itin-carousel {
  position: relative;
  margin: 0 -1rem;
  padding: 0 1rem;
}
.exp-itin-track {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-padding: 0 1rem;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  padding: 0.5rem 0 1rem;
}
.exp-itin-track::-webkit-scrollbar { display: none; }
.exp-itin-slide {
  flex: 0 0 78vw;
  max-width: 380px;
  min-width: 300px;
  scroll-snap-align: start;
  transition: opacity 0.3s ease, transform 0.3s ease;
  opacity: 0;
  transform: translateY(20px);
}
.exp-itin-slide.is-visible {
  opacity: 1;
  transform: translateY(0);
}
.exp-itin-slide.is-visible:nth-child(2n) { transition-delay: 0.1s; }
.exp-itin-slide.is-visible:nth-child(3n) { transition-delay: 0.2s; }
.exp-itin-card {
  display: block;
  position: relative;
  height: 70vh;
  min-height: 480px;
  max-height: 600px;
  border-radius: 1.25rem;
  overflow: hidden;
  text-decoration: none;
  color: #fff;
  box-shadow: 0 8px 32px rgba(0,0,0,0.2);
}
.exp-itin-card-img {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  background-color: #0a2a18;
  transition: transform 0.4s ease;
}
.exp-itin-card:hover .exp-itin-card-img {
  transform: scale(1.05);
}
.exp-itin-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 2rem 1.25rem 1.5rem;
  background: linear-gradient(transparent 0%, rgba(0,0,0,0.4) 30%, rgba(0,0,0,0.75) 100%);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border-top: 1px solid rgba(255,255,255,0.08);
}
.exp-itin-overlay h3 {
  font-family: var(--font-display);
  font-size: 1.3rem;
  font-weight: 700;
  margin-bottom: 0.35rem;
  color: #fff;
}
.exp-itin-overlay p {
  font-size: 0.82rem;
  color: rgba(255,255,255,0.8);
  line-height: 1.5;
  margin: 0;
}
.exp-itin-days {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
  z-index: 2;
}
.exp-itin-days::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, #fff 50%, #0a1f10 50%);
  z-index: 0;
}
.exp-itin-days-num {
  position: relative;
  z-index: 1;
  font-size: 1.1rem;
  font-weight: 800;
  line-height: 1;
  color: #0a1f10;
  margin-top: -2px;
}
.exp-itin-days-label {
  position: relative;
  z-index: 1;
  font-size: 0.55rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #fff;
  margin-top: 1px;
}

/* Carousel arrows */
.exp-itin-arrow,
.exp-dest-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(255,255,255,0.95);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  color: #0a1f10;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  z-index: 5;
  transition: all 0.2s ease;
}
.exp-itin-arrow:hover,
.exp-dest-arrow:hover {
  background: #fff;
  transform: translateY(-50%) scale(1.1);
}
.exp-itin-prev,
.exp-dest-prev { left: 0.5rem; }
.exp-itin-next,
.exp-dest-next { right: 0.5rem; }

@media (min-width: 768px) {
  .exp-dest-card { flex: 0 0 45vw; }
  .exp-itin-slide { flex: 0 0 45vw; }
}
@media (min-width: 1024px) {
  .exp-dest-card { flex: 0 0 30vw; max-width: 380px; }
  .exp-itin-slide { flex: 0 0 30vw; max-width: 380px; }
}

/* ── Motion reveal ── */
.motion-reveal {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.motion-reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}
.motion-reveal.is-visible:nth-child(2n) { transition-delay: 0.1s; }
.motion-reveal.is-visible:nth-child(3n) { transition-delay: 0.2s; }

/* ── Tag pills glassmorphism ── */
.exp-detail-chips {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: nowrap;
  overflow-x: auto;
  margin-top: 1.2rem;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}
.exp-detail-chips::-webkit-scrollbar { display: none; }
.exp-detail-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(255,255,255,0.1);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.15);
  color: #fff;
  font-size: 0.7rem;
  font-weight: 600;
  white-space: nowrap;
  padding: 0.34rem 0.82rem;
  border-radius: 999px;
  text-decoration: none;
  transition: background 150ms;
  flex-shrink: 0;
}
.exp-detail-chip:hover { background: rgba(255,255,255,0.18); }
.exp-detail-chip--cat { background: rgba(197,168,74,0.2); border-color: rgba(197,168,74,0.4); }

/* ── Hero title sweep animation ── */
.exp-title-sweep {
  background: linear-gradient(90deg, #fff 0%, #fff 100%);
  background-size: 200% 100%;
  background-position: 100% 0;
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: expTitleSweep 1.5s ease-out forwards;
}
@keyframes expTitleSweep {
  0% { background-position: 100% 0; }
  100% { background-position: 0 0; }
}
@media (prefers-reduced-motion: reduce) {
  .exp-title-sweep {
    background-position: 0 0;
    animation: none;
  }
}

/* ── Breadcrumbs fade-in ── */
.exp-breadcrumbs {
  opacity: 0;
  transform: translateY(-10px);
  animation: breadcrumbFade 0.5s ease forwards;
  animation-delay: 0.3s;
}
@keyframes breadcrumbFade {
  to { opacity: 1; transform: translateY(0); }
}

/* ── CTA side-by-side on mobile ── */
@media (max-width: 575.98px) {
  .exp-detail-ctas {
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .exp-detail-ctas .btn-hero-primary,
  .exp-detail-ctas .btn-hero-wa {
    flex: 1 1 calc(50% - 0.25rem);
    text-align: center;
    padding: 0.7rem 0.8rem;
    font-size: 0.8rem;
  }
  .exp-detail-ctas .btn-hero-ghost {
    flex-basis: 100%;
    text-align: center;
    padding: 0.7rem 0.8rem;
    font-size: 0.8rem;
  }
}

/* ── Section spacing ── */
.exp-section { padding: 3rem 0; }
.section-kicker { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--accent-light); margin-bottom: 0.5rem; display: block; }

/* ── CTA Section - side by side buttons on mobile ── */
.cta-actions-wrap {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
}
.cta-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 52px;
  padding: 12px 24px;
  border-radius: 999px;
  font-weight: 600;
  font-size: 0.9rem;
  text-decoration: none;
  transition: all 0.2s ease;
  flex: 1;
  max-width: 220px;
}
.cta-btn-primary {
  background: var(--color-brand-green);
  color: #fff;
  border: 2px solid var(--color-brand-green);
}
.cta-btn-primary:hover { background: var(--color-brand-dark); transform: translateY(-1px); box-shadow: var(--shadow-md); }
.cta-btn-whatsapp {
  background: #fff;
  color: var(--color-brand-green);
  border: 2px solid var(--color-brand-green);
}
.cta-btn-whatsapp::before {
  content: "";
  display: inline-block;
  width: 20px;
  height: 20px;
  margin-right: 8px;
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%231a5f3a'%3E%3Cpath d='M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z'/%3E%3C/svg%3E") no-repeat center;
  background-size: contain;
}
@media (max-width: 359.98px) {
  .cta-actions-wrap { flex-direction: column; align-items: center; }
  .cta-btn { max-width: 100%; width: 100%; }
}

/* ── Responsive hero ── */
@media (max-width: 575.98px) {
  .exp-detail-hero,
  .home-hero-banner {
    min-height: 50vh;
  }
}

</style>

<?php
schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
?>
