<?php
/**
 * itineraries.php — GEM redesign
 * Duration quick-pick + scrollable filter bar + responsive card grid.
 */

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/responsive_image.php';

// ── Column guards ─────────────────────────────────────────────────────────────
$_hasTraveller   = db_column_exists('itineraries', 'traveller_types');
$_hasStartPoint  = db_column_exists('itineraries', 'start_point');
$_hasDestCount   = db_column_exists('itineraries', 'destination_count');
$_hasCategoryId  = db_column_exists('itineraries', 'category_id');
$_hasThemeSlug   = db_column_exists('itinerary_categories', 'theme_slug');
$_hasCatEmoji    = db_column_exists('itinerary_categories', 'emoji');

// ── Active filters (from URL) ─────────────────────────────────────────────────
$daysFilter      = (int) get_input('days', 0);
$themeFilter     = strtolower(trim((string) get_input('theme', '')));
$themeFilter     = preg_replace('/[^a-z0-9\-]+/', '', $themeFilter) ?: '';
$travellerFilter = strtolower(trim((string) get_input('traveller', '')));
$travellerFilter = preg_replace('/[^a-z]+/', '', $travellerFilter) ?: '';
$startFilter     = trim((string) get_input('start', ''));

// ── Build query ───────────────────────────────────────────────────────────────
$where  = ['i.is_published = 1'];
$params = [];
$join   = '';

if ($daysFilter > 0) {
    // Bucket logic: "7" means 6-8 days, "10" means 9-11, "14" means 12+, else exact
    if ($daysFilter === 7) {
        $where[]  = 'i.days BETWEEN 6 AND 8';
    } elseif ($daysFilter === 10) {
        $where[]  = 'i.days BETWEEN 9 AND 11';
    } elseif ($daysFilter === 14) {
        $where[]  = 'i.days >= 12';
    } elseif ($daysFilter <= 5) {
        $where[]  = 'i.days BETWEEN 3 AND 5';
    } else {
        $where[]  = 'i.days = :days';
        $params['days'] = $daysFilter;
    }
}

if ($themeFilter !== '' && $_hasCategoryId && db_table_exists('itinerary_categories')) {
    $join = 'LEFT JOIN itinerary_categories c ON c.id = i.category_id';
    if ($_hasThemeSlug) {
        $where[]              = '(c.theme_slug = :theme_slug_1 OR c.slug = :theme_slug_2)';
        $params['theme_slug_1'] = $themeFilter;
        $params['theme_slug_2'] = $themeFilter;
    } else {
        $where[]           = 'c.slug = :theme_slug';
        $params['theme_slug'] = $themeFilter;
    }
}

if ($travellerFilter !== '' && $_hasTraveller) {
    $where[]               = 'FIND_IN_SET(:traveller, i.traveller_types)';
    $params['traveller']   = $travellerFilter;
}

if ($startFilter !== '' && $_hasStartPoint) {
    $where[]             = 'i.start_point = :start_point';
    $params['start_point'] = $startFilter;
}

// Extra SELECT columns
$_selectExtra = implode('', array_filter([
    $_hasTraveller  ? ', i.traveller_types' : '',
    $_hasStartPoint ? ', i.start_point, i.end_point' : '',
    $_hasDestCount  ? ', i.destination_count' : '',
    $_hasCategoryId ? ', i.category_id' : '',
]));

$itineraries = fetch_all(
    "SELECT i.id, i.name, i.slug, i.days,
            i.excerpt, i.hero_image, i.og_image, i.meta_description,
            i.is_featured, i.price_from, i.price_to, i.price_currency,
            " . (db_column_exists('itineraries', 'price_unit') ? 'i.price_unit' : 'NULL AS price_unit') . "
            {$_selectExtra}
     FROM itineraries i
     {$join}
     WHERE " . implode(' AND ', $where) . "
     ORDER BY i.is_featured DESC, i.sort_order ASC, i.days ASC, i.name ASC",
    $params
);
$nDayGuideDurations = array_values(array_unique(array_filter(
    array_map(static fn(array $itinerary): int => (int) ($itinerary['days'] ?? 0), $itineraries),
    static fn(int $days): bool => $days > 0 && $days < 100
)));
sort($nDayGuideDurations, SORT_NUMERIC);

// ── Categories for filter chips ───────────────────────────────────────────────
$categories = [];
if ($_hasCategoryId && db_table_exists('itinerary_categories')) {
    $_catSelect = implode('', array_filter([
        $_hasThemeSlug ? ', theme_slug' : '',
        $_hasCatEmoji  ? ', emoji'      : '',
    ]));
    $categories = fetch_all(
        "SELECT id, name, slug {$_catSelect}
         FROM itinerary_categories
         WHERE is_published = 1
         ORDER BY sort_order ASC, name ASC"
    );
}

// ── Start points for filter ───────────────────────────────────────────────────
$startPoints = [];
if ($_hasStartPoint) {
    $rows = fetch_all(
        "SELECT DISTINCT start_point FROM itineraries
         WHERE is_published = 1 AND start_point IS NOT NULL AND start_point != ''
         ORDER BY start_point ASC"
    );
    $startPoints = array_column($rows, 'start_point');
}

// ── Hero image ────────────────────────────────────────────────────────────────
$coverRecords = array_values(array_filter(
    array_map(static fn(array $row): array => package_card_record($row, 'itinerary'), $itineraries),
    static fn(array $row): bool => trim((string) ($row['cover_image'] ?? '')) !== ''
        && !is_placeholder_media_url((string) $row['cover_image'])
));
$heroImage = random_record_hero_image($coverRecords ?: $itineraries, ['cover_image', 'hero_image']);

// ── SEO ───────────────────────────────────────────────────────────────────────
$seoTitle       = 'Private Sri Lanka Itineraries | Chauffeur-Guided Tours | ' . APP_NAME;
$seoDescription = 'Browse ready-to-customise Sri Lanka itineraries — short breaks, family journeys, honeymoons, and multi-region private tours from 3 to 14 days.';
$seoCanonical   = base_url('itineraries');
$seoImage       = $heroImage ?: DEFAULT_OG_IMAGE;
$seoType        = 'website';

$breadcrumbs = [
    ['name' => 'Home',        'url' => base_url()],
    ['name' => 'Itineraries', 'url' => $seoCanonical],
];

$schemaItems = [];
foreach ($itineraries as $it) {
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
    schema_item_list('Sri Lanka Itineraries', $schemaItems),
];

$bodyClass = 'page-itineraries';
$extraStylesheets[] = 'assets/css/journey-layouts.css';
require __DIR__ . '/includes/header.php';

// ── Duration buckets ──────────────────────────────────────────────────────────
$_durBuckets = [
    0  => ['label' => 'All',     'desc' => ''],
    3  => ['label' => '3–5',     'desc' => 'days'],
    7  => ['label' => '7–8',     'desc' => 'days'],
    10 => ['label' => '10',      'desc' => 'days'],
    14 => ['label' => '12–14',   'desc' => 'days'],
];

$_isFiltered = $daysFilter > 0 || $themeFilter !== '' || $travellerFilter !== '' || $startFilter !== '';

// ── Query-string fragments for filter links ──
$durQ   = $daysFilter > 0 ? 'days=' . $daysFilter . '&' : '';
$travQ  = $travellerFilter !== '' ? 'traveller=' . urlencode($travellerFilter) . '&' : '';
$startQ = $startFilter !== '' ? 'start=' . urlencode($startFilter) . '&' : '';
?>

<!-- ════════════════════════════════════════════════════════════
     HERO — duration picker (redesign: fullbleed hero with design-system headings)
════════════════════════════════════════════════════════════= -->
<section class="itin-hero"
         <?php if ($heroImage): ?>
         style="background-image:linear-gradient(170deg,rgba(10,31,16,.92) 0%,rgba(19,43,28,.78) 55%,rgba(30,61,40,.65) 100%),url('<?= e($heroImage) ?>');background-size:cover;background-position:center;"
         <?php endif; ?>>
  <div class="container">
    <nav aria-label="Breadcrumb" style="margin-bottom:1.25rem;">
      <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
    </nav>
    <span class="section-eyebrow" style="color:var(--accent-light);">Travel Plans</span>
    <h1 class="section-title" style="color:#fff; font-size:clamp(2.2rem,5vw,3.6rem); margin-bottom:.6rem;">
      Sri Lanka <em style="font-style:italic; color:var(--brand-200);">Itineraries</em>
    </h1>
    <p class="section-lead" style="color:rgba(255,255,255,.84); max-width:560px; margin-top:.2rem;">
      Start with a proven route, then customise the pace, stops, and vehicle to match your
      travel style. Every plan is fully private — no shared buses, no group compromises.
    </p>

    <!-- Duration quick-pick (mobile carousel) -->
    <div class="itin-dur-scroll" x-data="durAutoScroll()" x-init="init()">
      <div class="itin-dur-track" x-ref="durTrack">
        <?php foreach ($_durBuckets as $bucket => $info): ?>
        <a href="<?= e(base_url('itineraries') . ($bucket > 0 ? '?days=' . $bucket : '') . ($themeFilter ? ($bucket > 0 ? '&' : '?') . 'theme=' . urlencode($themeFilter) : '')) ?>"
           class="itin-dur-pill <?= $daysFilter === $bucket ? 'active' : '' ?>"
           data-days="<?= $bucket ?>">
          <span class="itin-dur-num"><?= e($info['label']) ?></span>
          <?php if ($info['desc']): ?>
          <small class="itin-dur-desc"><?= e($info['desc']) ?></small>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if ($nDayGuideDurations): ?>
    <nav class="itin-crosslinks mt-3" aria-label="Exact-duration itinerary guides">
      <span class="filter-label">Exact-duration guides:</span>
      <?php foreach ($nDayGuideDurations as $guideDays): ?>
      <a href="<?= e(base_url($guideDays . '-day-itinerary')) ?>"><?= $guideDays ?>-day guide</a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     FILTER BAR — horizontally scrollable
════════════════════════════════════════════════════════════ -->
<div class="itin-filter-bar" role="navigation" aria-label="Filter itineraries">

  <?php if ($categories): ?>
  <div class="itin-theme-widget" x-data="themeFilter()" x-init="startAutoRotate()">
    <p class="itin-theme-question">What theme interests you?</p>
    <div class="itin-theme-answer">
      <a href="?<?= $durQ ?><?= $travQ ?><?= $startQ ?>"
         class="itin-theme-option"
         :class="{ 'active': current === 'all' }">
        All Itineraries
      </a>
      <?php foreach ($categories as $cat):
          $catTheme = $cat['theme_slug'] ?? $cat['slug'];
          $catEmoji = $cat['emoji']      ?? '';
      ?>
      <a href="?theme=<?= e($catTheme) ?>&<?= $durQ ?><?= $travQ ?><?= $startQ ?>"
         class="itin-theme-option"
         :class="{ 'active': current === '<?= e($catTheme) ?>' }"
         @click="stopAutoRotate()">
        <?= $catEmoji ? $catEmoji . ' ' : '' ?><?= e($cat['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="itin-filter-inner">
    <!-- Traveller type chips -->
    <?php if ($_hasTraveller):
        $_travellers = [
            'couple'    => ['icon' => '👫', 'label' => 'Couple'],
            'family'    => ['icon' => '👨‍👩‍👧', 'label' => 'Family'],
            'honeymoon' => ['icon' => '💑', 'label' => 'Honeymoon'],
            'solo'      => ['icon' => '🧳', 'label' => 'Solo'],
        ];
        foreach ($_travellers as $tv => $tinfo):
            $isActive = $travellerFilter === $tv;
            $href     = base_url('itineraries') . '?traveller=' . urlencode($tv)
                      . ($daysFilter > 0    ? '&days='  . $daysFilter              : '')
                      . ($themeFilter !== '' ? '&theme=' . urlencode($themeFilter)  : '');
    ?>
    <a href="<?= e($href) ?>"
       class="filter-chip <?= $isActive ? 'active' : '' ?>"
       aria-pressed="<?= $isActive ? 'true' : 'false' ?>">
      <?= $tinfo['icon'] ?> <?= e($tinfo['label']) ?>
    </a>
    <?php endforeach; ?>
    <div class="filter-sep" aria-hidden="true"></div>
    <?php endif; ?>

    <!-- Start point -->
    <?php if ($startPoints): ?>
    <span class="filter-label">Starts</span>
    <?php foreach ($startPoints as $sp):
        $isActive = $startFilter === $sp;
        $href     = base_url('itineraries') . '?start=' . urlencode($sp)
                  . ($daysFilter > 0    ? '&days='  . $daysFilter              : '')
                  . ($themeFilter !== '' ? '&theme=' . urlencode($themeFilter)  : '');
    ?>
    <a href="<?= e($href) ?>"
       class="filter-chip <?= $isActive ? 'active' : '' ?>">
      📍 <?= e(mb_strimwidth($sp, 0, 18, '…')) ?>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>

  </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     GRID
════════════════════════════════════════════════════════════ -->
<section class="itin-grid-section">
  <div class="container">

    <div class="section-head itin-grid-header">
      <div>
        <span class="section-eyebrow">
          <?php if ($daysFilter > 0): ?>
            <?= e($_durBuckets[$daysFilter]['label'] . ' ' . $_durBuckets[$daysFilter]['desc']) ?> routes
          <?php else: ?>
            All routes
          <?php endif; ?>
        </span>
        <h2 class="section-title" style="margin-bottom:0;">
          <?= count($itineraries) ?> <?= $_isFiltered ? 'filtered' : '' ?>
          itinerar<?= count($itineraries) === 1 ? 'y' : 'ies' ?> —
          <em>private, chauffeur-guided</em>
        </h2>
        <p class="section-lead" style="margin-top:.5rem;">Handpicked private routes — chauffeur-guided, fully customisable to your pace and stops.</p>
      </div>
      <?php if ($_isFiltered): ?>
      <a href="<?= e(base_url('itineraries')) ?>" class="section-link itin-clear-link">✕ Clear filters</a>
      <?php endif; ?>
    </div>

    <?php if (empty($itineraries)): ?>
    <div class="itin-empty">
      <p>No itineraries match your current filters.</p>
      <a href="<?= e(base_url('itineraries')) ?>" class="btn btn-primary">View all itineraries</a>
    </div>

    <?php else: ?>
    <div class="itin-grid">
      <div class="itin-grid-track">
        <?php foreach ($itineraries as $it):
            $img = (package_card_image($it, 'itinerary') ?: '');
            $days      = (int)$it['days'];
            $nights    = $days > 0 ? $days - 1 : 0;
            $excerpt   = mb_strimwidth((string)($it['excerpt'] ?? $it['meta_description'] ?? ''), 0, 110, '…');
            $priceFrom = !empty($it['price_from']) ? (float)$it['price_from'] : null;
            $priceCurr = !empty($it['price_currency']) ? $it['price_currency'] : 'USD';
            $priceLabel = format_price_range($it, false);
        ?>
        <a href="<?= e(base_url('itineraries/' . $it['slug'])) ?>" class="card" data-lightbox-ignore>
          <div class="card-thumb">
            <?php if ($img): ?>
              <?= responsive_image([
                  'src' => $img,
                  'alt' => package_card_alt($it, 'itinerary'),
                  'widths' => [400, 800],
                  'sizes' => '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw',
                  'width' => 800,
                  'height' => 600,
                  'loading' => 'lazy',
              ]) ?>
            <?php else: ?>
              <div style="width:100%;height:100%;background:linear-gradient(135deg, var(--brand), var(--surface-mid));"></div>
            <?php endif; ?>
            <?php if ($days > 0): ?>
            <span class="card-badge"><?= $days ?> Days · <?= $nights ?> N</span>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <h3 class="card-title"><?= e($it['name']) ?></h3>
            <p class="card-excerpt"><?= e($excerpt) ?></p>
            <div class="card-footer">
              <?php if ($priceLabel !== ''): ?>
                <span class="card-price-label"><?= $priceFrom ? 'Starting from' : 'Up to' ?></span>
                <span class="card-price"><?= e($priceLabel) ?></span>
              <?php else: ?>
                <span class="card-price-label">Private tour</span>
                <span class="card-price">Custom quote</span>
              <?php endif; ?>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Cross-links -->
    <div class="itin-crosslinks">
      <a href="<?= e(base_url('seasonal')) ?>">📅 When to Visit</a>
      <a href="<?= e(base_url('plan')) ?>">✏️ Custom Trip Planner</a>
      <a href="<?= e(base_url('experiences')) ?>">🌿 Experiences &amp; Hidden Gems</a>
    </div>

  </div>
</section>

<?php require __DIR__ . '/includes/cta.php'; ?>

<style>
/* ── Hero ────────────────────────────────────────────────── */
.itin-hero {
  background: linear-gradient(170deg,#0a1f10 0%,#132b1c 55%,#1e3d28 100%);
  padding: 3rem 0 2.5rem;
  position: relative;
  overflow: hidden;
}
.itin-hero::before {
  content:'';
  position:absolute;
  width:500px;height:500px;border-radius:50%;
  border:1px solid rgba(255,255,255,.04);
  top:-160px;right:-120px;pointer-events:none;
}
.itin-hero .section-heading em { font-style:italic; color:var(--accent-light); }
.itin-hero-lead {
  color:rgba(255,255,255,.65);
  font-size:.92rem;
  line-height:1.75;
  max-width:560px;
  margin-top:.6rem;
}

/* Duration row */
.itin-dur-row {
  display:flex;
  align-items:center;
  gap:.5rem;
  margin-top:1.6rem;
  flex-wrap:wrap;
}
.itin-dur-label {
  font-size:.68rem;
  font-weight:600;
  color:rgba(255,255,255,.4);
  letter-spacing:.1em;
  text-transform:uppercase;
  flex-shrink:0;
}
.itin-dur-btn {
  background:rgba(255,255,255,.08);
  border:1px solid rgba(255,255,255,.18);
  color:rgba(255,255,255,.78);
  font-size:.8rem;
  font-weight:600;
  padding:.42rem 1rem;
  border-radius:999px;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  gap:.25rem;
  transition:all 180ms;
}
.itin-dur-btn strong { font-size:1rem; }
.itin-dur-btn span   { font-size:.72rem; opacity:.75; }
.itin-dur-btn:hover  { background:rgba(255,255,255,.15); color:#fff; }
.itin-dur-btn.active { background:var(--brand); border-color:var(--brand); color:#fff; }

/* ── Filter bar ──────────────────────────────────────────── */
.itin-filter-bar {
  background:var(--surface);
  border-bottom:1px solid var(--line);
  box-shadow:var(--shadow-sm);
  overflow-x:auto;
  -webkit-overflow-scrolling:touch;
  scrollbar-width:none;
  position:sticky;
  top:0;
  z-index:30;
}
.itin-filter-bar::-webkit-scrollbar { display:none; }
.itin-filter-inner {
  display:flex;
  align-items:center;
  gap:.5rem;
  padding:.7rem 1.25rem;
  min-width:max-content;
}
.filter-label {
  font-size:.65rem;
  font-weight:700;
  letter-spacing:.1em;
  text-transform:uppercase;
  color:var(--muted);
  white-space:nowrap;
  flex-shrink:0;
}
.filter-chip {
  border:1px solid var(--line-strong);
  background:var(--surface-alt);
  color:var(--text-body);
  font-size:.74rem;
  font-weight:600;
  padding:.32rem .85rem;
  border-radius:999px;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  gap:.3rem;
  white-space:nowrap;
  text-decoration:none;
  transition:all 150ms;
}
.filter-chip:hover  { background:var(--brand-soft); border-color:var(--brand); color:var(--brand); }
.filter-chip.active { background:var(--brand); border-color:var(--brand); color:#fff; }
.filter-sep {
  width:1px;height:20px;
  background:var(--line-strong);
  flex-shrink:0;
}

/* ── Grid section ────────────────────────────────────────── */
.itin-grid-section {
  padding:2.5rem 0 3rem;
  background:var(--bg);
}
.itin-grid-header {
  display:flex;
  align-items:flex-end;
  justify-content:space-between;
  gap:1rem;
  margin-bottom:1.5rem;
  flex-wrap:wrap;
}
.itin-grid-heading {
  font-family:var(--font-display, Georgia,serif);
  font-size:clamp(1.2rem,2vw,1.6rem);
  font-weight:400;
  color:var(--text);
  margin:0;
}
.itin-grid-heading em { font-style:italic; }
.itin-clear-link {
  font-size:.78rem;
  font-weight:600;
  color:var(--muted);
  text-decoration:none;
  white-space:nowrap;
  padding:.35rem .75rem;
  border:1px solid var(--line-strong);
  border-radius:999px;
  transition:all 150ms;
}
.itin-clear-link:hover { background:var(--surface-alt); color:var(--text); }

/* Empty state */
.itin-empty {
  text-align:center;
  padding:3rem 1rem;
  color:var(--muted);
}
.itin-empty p { margin-bottom:1rem; }
.btn-brand {
  display:inline-block;
  background:var(--brand);
  color:#fff;
  font-size:.82rem;
  font-weight:700;
  padding:.65rem 1.5rem;
  border-radius:999px;
  text-decoration:none;
}

/* Card grid */
.itin-cards-grid {
  display:grid;
  grid-template-columns:1fr;
  gap:1.1rem;
}
@media (min-width:480px) { .itin-cards-grid { grid-template-columns:repeat(2,1fr); } }
@media (min-width:800px) { .itin-cards-grid { grid-template-columns:repeat(3,1fr); } }

/* Card */
.itin-card {
  border-radius:var(--radius-lg);
  overflow:hidden;
  box-shadow:var(--shadow-sm);
  border:1px solid var(--line);
  background:var(--surface);
  transition:transform 200ms,box-shadow 200ms;
  display:flex;
  flex-direction:column;
}
.itin-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-md); }

.itin-card-img-link { display:block; text-decoration:none; }
.itin-card-img {
  height:180px;
  position:relative;
  display:flex;
  align-items:flex-start;
  justify-content:flex-end;
  padding:.8rem;
  background:linear-gradient(160deg,#0a2a18,#2d6a41);
  background-size:cover;
  background-position:center;
}
.itin-days-badge {
  background:#fff;
  color:var(--text);
  font-size:.72rem;
  font-weight:700;
  padding:.32rem .7rem;
  border-radius:999px;
  box-shadow:0 2px 8px rgba(0,0,0,.14);
  display:flex;
  align-items:center;
  gap:.25rem;
}
.itin-days-badge strong { font-size:1rem; color:var(--brand); }
.itin-days-badge span   { opacity:.7; }

.itin-card-img-overlay {
  position:absolute;
  bottom:0;left:0;right:0;
  background:linear-gradient(transparent,rgba(0,0,0,.52));
  padding:2rem .85rem .75rem;
  display:flex;
  gap:.35rem;
  flex-wrap:wrap;
}
.itin-tag {
  font-size:.6rem;
  font-weight:700;
  letter-spacing:.06em;
  text-transform:uppercase;
  color:rgba(255,255,255,.92);
  background:rgba(255,255,255,.14);
  border:1px solid rgba(255,255,255,.22);
  padding:.18rem .48rem;
  border-radius:4px;
}

.itin-card-body {
  padding:1rem;
  flex:1;
  display:flex;
  flex-direction:column;
}
.itin-card-body h3 {
  font-size:.92rem;
  font-weight:700;
  color:var(--text);
  margin-bottom:.28rem;
  line-height:1.3;
}
.itin-card-body h3 a { color:inherit; text-decoration:none; }
.itin-card-body h3 a:hover { color:var(--brand); }
.itin-card-body p {
  font-size:.74rem;
  color:var(--muted);
  line-height:1.55;
  flex:1;
  margin-bottom:.75rem;
}
.itin-card-footer {
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding-top:.6rem;
  border-top:1px solid var(--line);
}
.itin-card-meta  { font-size:.7rem; color:var(--muted); }
.itin-card-meta strong { color:var(--text); }
.itin-card-cta {
  font-size:.74rem;
  font-weight:700;
  color:var(--brand);
  text-decoration:none;
}
.itin-card-cta:hover { color:var(--brand-dark); }

/* Cross-links */
.itin-crosslinks {
  display:flex;
  gap:1rem;
  flex-wrap:wrap;
  justify-content:center;
  padding-top:2.5rem;
  border-top:1px solid var(--line);
  margin-top:2.5rem;
}
.itin-crosslinks a {
  font-size:.82rem;
  font-weight:600;
  color:var(--brand);
  text-decoration:none;
  padding:.4rem .9rem;
  border:1px solid var(--brand-soft);
  border-radius:999px;
  background:var(--brand-soft);
  transition:all 150ms;
}
.itin-crosslinks a:hover {
  background:var(--brand);
  border-color:var(--brand);
  color:#fff;
}

/* ── Hero z-index fix ───────────────────────────────────── */
.itin-hero {
    position: relative;
    z-index: 1;
    padding-top: calc(var(--site-overlay-hero-offset, 6rem) + 1rem) !important;
}
.itin-hero::before {
    z-index: 0;
    position: absolute;
}
.itin-hero > .container {
    position: relative;
    z-index: 3;
}
.itin-hero .section-kicker {
    position: relative;
    z-index: 2;
}
.itin-hero .section-heading {
    position: relative;
    z-index: 3;
}

/* ── Duration carousel ──────────────────────────────────── */
.itin-dur-scroll {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    margin: 1.5rem -1rem 0;
    padding: 0 1rem;
    scroll-snap-type: x mandatory;
}
.itin-dur-scroll::-webkit-scrollbar { display: none; }
.itin-dur-track {
    display: flex;
    gap: 0.6rem;
    padding: 0.25rem 0;
}
.itin-dur-pill {
    flex: 0 0 auto;
    scroll-snap-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.6rem 1.2rem;
    border-radius: 50px;
    background: rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.8);
    border: 1px solid rgba(255,255,255,0.2);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    min-height: 44px;
    transition: all 0.2s ease;
    white-space: nowrap;
}
.itin-dur-pill.active {
    background: var(--accent-light, #c5a84a);
    color: #0a1f10;
    border-color: var(--accent-light, #c5a84a);
}
.itin-dur-num { font-size: 0.95rem; }
.itin-dur-desc { font-size: 0.65rem; opacity: 0.75; }

/* ── Theme Q&A Widget ───────────────────────────────────── */
.itin-theme-widget {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--line);
}
.itin-theme-question {
    color: var(--muted);
    font-size: 0.88rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}
.itin-theme-answer {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    gap: 0.5rem;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 0.25rem;
}
.itin-theme-answer::-webkit-scrollbar { display: none; }
.itin-theme-option {
    flex: 0 0 auto;
    padding: 0.55rem 1rem;
    border-radius: 50px;
    background: var(--surface-alt);
    color: var(--text-body);
    border: 1px solid var(--line-strong);
    text-decoration: none;
    font-size: 0.82rem;
    font-weight: 600;
    white-space: nowrap;
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    transition: all 0.25s ease;
}
.itin-theme-option.active {
    background: var(--brand, #2d6a41);
    color: #fff;
    border-color: var(--brand, #2d6a41);
}

.itin-grid {
    position: relative;
}
.itin-grid-track {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}
.itin-c-slide {
    transition: opacity 0.3s ease, transform 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
}
.itin-c-slide.is-visible {
    opacity: 1;
    transform: translateY(0);
}
.itin-c-card {
    display: block;
    position: relative;
    height: 380px;
    border-radius: 1.25rem;
    overflow: hidden;
    text-decoration: none;
    color: #fff;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
}
.itin-c-img {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    background-color: #0a2a18;
    transition: transform 0.4s ease;
}
.itin-c-card:hover .itin-c-img {
    transform: scale(1.05);
}

/* Days/Nights two-sided badge */
.itin-c-days {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.3);
    z-index: 2;
}
.itin-c-days::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, #fff 50%, #0a1f10 50%);
    z-index: 0;
}
.itin-c-days-inner,
.itin-c-nights-inner {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    line-height: 1;
}
.itin-c-days-num {
    font-size: 1.2rem;
    font-weight: 800;
    color: #0a1f10;
}
.itin-c-days-label {
    font-size: 0.55rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #0a1f10;
}
.itin-c-nights-inner {
    margin-top: 2px;
}
.itin-c-nights-num {
    font-size: 1.2rem;
    font-weight: 800;
    color: #fff;
}
.itin-c-nights-label {
    font-size: 0.55rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #fff;
}

/* Price badge */
.itin-c-price {
    position: absolute;
    bottom: 1rem;
    left: 1rem;
    z-index: 2;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-radius: 50px;
    padding: 0.4rem 1rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}
.itin-c-price-num {
    font-size: 1.1rem;
    font-weight: 800;
    color: #0a1f10;
}
.itin-c-price-label {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #666;
    margin-left: 0.25rem;
}

.itin-c-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 2rem 1.25rem 1.5rem;
    background: linear-gradient(transparent 0%, rgba(0,0,0,0.4) 30%, rgba(0,0,0,0.8) 100%);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-top: 1px solid rgba(255,255,255,0.08);
}
.itin-c-overlay h3 {
    font-family: var(--font-display);
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 0.35rem;
    color: #fff;
}
.itin-c-overlay p {
    font-size: 0.82rem;
    color: rgba(255,255,255,0.8);
    line-height: 1.5;
    margin: 0;
}

/* Responsive grid breakpoints — shared card grid */
@media (min-width: 768px) {
    .itin-grid-track {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (min-width: 1024px) {
    .itin-grid-track {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Breadcrumb on dark hero */
.itin-hero .breadcrumb,
.itin-hero .breadcrumb-item,
.itin-hero .breadcrumb-item.active,
.itin-hero .breadcrumb-item a {
    color: rgba(255,255,255,.82) !important;
}
.itin-hero .breadcrumb-item a:hover { color: #fff !important; }
.itin-hero .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,.45); }

/* Shared card tweak inside itineraries grid */
.itin-grid-track .card {
    height: 100%;
    text-decoration: none;
    color: inherit;
}
.itin-grid-track .card-thumb { aspect-ratio: 4/3; }
.itin-grid-track .card-thumb img,
.itin-grid-track .card-thumb picture { width:100%; height:100%; display:block; object-fit:cover; }
.itin-grid-track .card-thumb picture img { width:100%; height:100%; object-fit:cover; }

/* Motion reveal entrance animation */
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

/* Fallback: reveal carousel cards if IntersectionObserver doesn't fire */
@keyframes itin-card-reveal {
    to { opacity: 1; transform: translateY(0); }
}
.itin-c-slide:not(.is-visible) {
    animation: itin-card-reveal 0.5s ease 1.5s forwards;
}
</style>

<?php schema_output($schemaGraph); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
