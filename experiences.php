<?php
/**
 * experiences.php — GEM redesign
 * Mood board listing: scrollable category tiles, card grid,
 * session-backed wishlist bar, "Find My Experiences" quiz CTA.
 */

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/responsive_image.php';

ensure_experiences_schema();

if (is_post()) {
    $action = trim((string) post_input('action', ''));
    if ($action !== 'save_wishlist') {
        http_response_code(400);
        exit;
    }

    guard_post_size();
    require_csrf();

    $postedToken = trim((string) post_input('token', ''));
    $cookieToken = route_session_token(false);
    if ($cookieToken === '' || !hash_equals($cookieToken, $postedToken)) {
        http_response_code(204);
        exit;
    }

    $allowedSlugs = [];
    if (db_table_exists('experiences') && db_column_exists('experiences', 'slug')) {
        $publishedFilter = db_column_exists('experiences', 'is_published') ? ' WHERE is_published = 1' : '';
        $allowedSlugs = array_map(
            static fn(array $row): string => (string) ($row['slug'] ?? ''),
            fetch_all('SELECT slug FROM experiences' . $publishedFilter)
        );
    }

    $slugs = route_session_sanitize_slugs(post_input('exp_slugs', ''), $allowedSlugs, 30);
    route_session_save_slugs($postedToken, 'exp_slugs', $slugs);
    http_response_code(204);
    exit;
}

// ── Column guards ─────────────────────────────────────────────────────────────
$_hasCategoryId   = db_column_exists('experiences', 'category_id');
$_hasItinCount    = db_column_exists('experiences', 'itinerary_count');
$_hasDestCount    = db_column_exists('experiences', 'destination_count');
$_hasCatTable     = db_table_exists('experience_categories');
$_hasCatEmoji     = $_hasCatTable && db_column_exists('experience_categories', 'emoji');

// ── Active filter ─────────────────────────────────────────────────────────────
$moodFilter = strtolower(trim((string) get_input('mood', '')));
$moodFilter = preg_replace('/[^a-z0-9\-]+/', '', $moodFilter) ?: '';

// ── Fetch categories (mood tiles) ─────────────────────────────────────────────
$categories = [];
if ($_hasCatTable) {
    $_emojiSel = $_hasCatEmoji ? ', emoji' : ", '🌿' AS emoji";
    $categories = fetch_all(
        "SELECT id, name, slug {$_emojiSel}
         FROM experience_categories
         WHERE is_active = 1
         ORDER BY sort_order ASC, name ASC"
    );
}

// ── Fetch experiences ─────────────────────────────────────────────────────────
$_where  = ['e.is_published = 1'];
$_params = [];
$_join   = '';

if ($moodFilter !== '' && $_hasCategoryId && $_hasCatTable) {
    $_join     = 'INNER JOIN experience_categories ec ON ec.id = e.category_id';
    $_where[]  = '(ec.slug = :mood OR ec.theme_slug = :mood)' ;
    // theme_slug may not exist; fall back gracefully
    if (!db_column_exists('experience_categories', 'theme_slug')) {
        $_join    = 'INNER JOIN experience_categories ec ON ec.id = e.category_id';
        $_where[count($_where) - 1] = 'ec.slug = :mood';
    }
    $_params['mood'] = $moodFilter;
}

$_itinCountSel = $_hasItinCount
    ? 'e.itinerary_count'
    : '(SELECT COUNT(*) FROM experience_itinerary ei
        INNER JOIN itineraries ii ON ii.id = ei.itinerary_id AND ii.is_published = 1
        WHERE ei.experience_id = e.id) AS itinerary_count';

$_destCountSel = $_hasDestCount
    ? ', e.destination_count'
    : ', (SELECT COUNT(*) FROM experience_destination ed WHERE ed.experience_id = e.id) AS destination_count';

$_catIdSel = $_hasCategoryId ? ', e.category_id' : '';

$experiences = fetch_all(
    "SELECT e.id, e.slug, e.title, e.excerpt, e.hero_image, e.og_image, " . content_image_select_fragment('experiences','e',true) . ",
            e.is_featured, e.sort_order,
            {$_itinCountSel}
            {$_destCountSel}
            {$_catIdSel}
     FROM experiences e
     {$_join}
     WHERE " . implode(' AND ', $_where) . "
     ORDER BY e.is_featured DESC, e.sort_order ASC, e.title ASC",
    $_params
);

// Build a category-id → category map for fast lookup
$_catById = [];
foreach ($categories as $cat) {
    $_catById[$cat['id']] = $cat;
}

// ── Wishlist from session ─────────────────────────────────────────────────────
$_wishlistToken = route_session_token();
$_wishlistSlugs = route_session_fetch_slugs($_wishlistToken, 'exp_slugs');

// ── Hero image ────────────────────────────────────────────────────────────────
$_heroImage = '';
foreach ($experiences as $exp) {
    $candidate = content_image_path($exp, ['hero_image']);
    if ($candidate !== '') {
        $_heroImage = stored_media_url($candidate);
        if ($exp['is_featured']) break;
    }
}

// ── SEO ───────────────────────────────────────────────────────────────────────
$seoTitle       = 'Sri Lanka Experiences & Hidden Gems | Private Travel | ' . APP_NAME;
$seoDescription = 'Discover Sri Lanka\'s cultural moments, wildlife encounters, local food, and off-route hidden gems that can be woven into any private itinerary.';
$seoCanonical   = base_url('experiences');
$seoImage       = $_heroImage ?: DEFAULT_OG_IMAGE;
$seoType        = 'website';

$breadcrumbs = [
    ['name' => 'Home',        'url' => base_url()],
    ['name' => 'Experiences', 'url' => $seoCanonical],
];

$schemaItems = [];
foreach ($experiences as $exp) {
    $schemaItems[] = [
        'url'   => base_url('experiences/' . $exp['slug']),
        'name'  => (string)$exp['title'],
        'image' => stored_media_url(content_image_path($exp, ['cover_image','hero_image','og_image'])),
    ];
}

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    [
        '@type'   => 'CollectionPage',
        'name'    => 'Sri Lanka Experiences and Hidden Gems',
        'url'     => $seoCanonical,
        'isPartOf'=> ['@id' => base_url() . '#website'],
        'about'   => ['@type' => 'Thing', 'name' => 'Sri Lanka travel experiences'],
    ],
    schema_item_list('Sri Lanka Experiences', $schemaItems),
];

$bodyClass = 'page-experiences';
require __DIR__ . '/includes/header.php';
?>

<!-- ════════════════════════════════════════════════════════════
     HERO — mood tiles
════════════════════════════════════════════════════════════ -->
<section class="exp-hero"
         <?php if ($_heroImage): ?>style="background-image:linear-gradient(to bottom, rgba(0,0,0,0.28) 0%, rgba(0,0,0,0.62) 100%), url('<?= e($_heroImage) ?>');background-size:cover;background-position:center;"<?php endif; ?>>
  <div class="container">
    <nav aria-label="Breadcrumb" style="margin-bottom:1.25rem;">
      <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
    </nav>
    <div class="exp-hero-top">
      <div>
        <span class="section-eyebrow" style="color:<?= $_heroImage ? 'var(--accent-light)' : 'var(--accent)' ?>;">Hidden Gems &amp; Local Moments</span>
        <h1 class="section-title" style="color:<?= $_heroImage ? '#fff' : 'var(--text)' ?>; font-size:clamp(2.2rem,5vw,3.6rem); margin-bottom:.6rem;">Sri Lanka <em style="font-style:italic; color:<?= $_heroImage ? 'var(--brand-200)' : 'var(--text)' ?>;">Experiences</em></h1>
        <p class="section-lead" style="color:<?= $_heroImage ? 'rgba(255,255,255,.84)' : 'var(--muted)' ?>; max-width:520px;">
          Inspiration points for your private trip — cultural moments, wildlife encounters,
          local food, and off-route details that turn a route into a journey.
          These are not fixed tours; they're ideas your driver can weave into any itinerary.
        </p>
      </div>
      <a href="<?= e(base_url('plan')) ?>"
         class="btn btn-primary">
        ✨ Find My Experiences
      </a>
    </div>
  </div>

  <!-- Mood tiles — scrollable on mobile, grid on desktop -->
  <?php if ($categories): ?>
  <div class="exp-mood-scroll-wrap" role="navigation" aria-label="Filter by mood">
    <div class="exp-mood-grid">
      <a href="<?= e(base_url('experiences')) ?>"
         class="exp-mood-tile <?= $moodFilter === '' ? 'active' : '' ?>"
         aria-pressed="<?= $moodFilter === '' ? 'true' : 'false' ?>">
        <span class="exp-mood-emoji" aria-hidden="true">🌍</span>
        <span class="exp-mood-name">All</span>
        <span class="exp-mood-count"><?= count($experiences) ?> exp.</span>
      </a>
      <?php foreach ($categories as $cat):
          $isActive  = $moodFilter === $cat['slug'];
          $catEmoji  = $cat['emoji'] ?? '🌿';
          $catHref   = base_url('experiences') . '?mood=' . urlencode($cat['slug']);
          // Count experiences in this category
          $catCount  = 0;
          foreach ($experiences as $e) {
              if (isset($e['category_id']) && (int)$e['category_id'] === (int)$cat['id']) $catCount++;
          }
          if ($moodFilter !== '' && !$isActive) {
              // When filtered, show actual DB count not just in-page count
              $catCount = (int)(fetch_one(
                  "SELECT COUNT(*) AS c FROM experiences e WHERE e.is_published = 1 AND e.category_id = :id",
                  ['id' => $cat['id']]
              )['c'] ?? 0);
          }
      ?>
      <a href="<?= e($catHref) ?>"
         class="exp-mood-tile <?= $isActive ? 'active' : '' ?>"
         aria-pressed="<?= $isActive ? 'true' : 'false' ?>">
        <span class="exp-mood-emoji" aria-hidden="true"><?= $catEmoji ?></span>
        <span class="exp-mood-name"><?= e($cat['name']) ?></span>
        <span class="exp-mood-count"><?= $catCount ?> exp.</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<!-- ════════════════════════════════════════════════════════════
     EXPERIENCE GRID
════════════════════════════════════════════════════════════ -->
<section class="exp-grid-section">
  <div class="container">

    <div class="section-head exp-grid-header">
      <div>
        <?php if ($moodFilter !== '' && $categories):
            $activecat = array_values(array_filter($categories, fn($c) => $c['slug'] === $moodFilter));
            $activeName = $activecat[0]['name'] ?? ucfirst($moodFilter);
        ?>
        <span class="section-eyebrow"><?= e($activeName) ?></span>
        <h2 class="section-title" style="margin-bottom:0;"><?= count($experiences) ?> <?= e($activeName) ?> <em>Experiences</em></h2>
        <p class="section-lead" style="margin-top:.5rem;">Filtered by <?= e($activeName) ?> — tap any card for the full story.</p>
        <?php else: ?>
        <span class="section-eyebrow">All Experiences</span>
        <h2 class="section-title" style="margin-bottom:0;"><?= count($experiences) ?> <em>Experience</em> ideas for your trip</h2>
        <p class="section-lead" style="margin-top:.5rem;">Immersive ideas to weave into any private itinerary — culture, wildlife, food, and local moments.</p>
        <?php endif; ?>
      </div>
      <?php if ($moodFilter !== ''): ?>
      <a href="<?= e(base_url('experiences')) ?>" class="section-link exp-clear-link">✕ Show all</a>
      <?php endif; ?>
    </div>

    <!-- Category filter using form controls (design system) — preserves URLs -->
    <?php if ($categories): ?>
    <div style="display:flex; gap:.75rem; align-items:flex-end; flex-wrap:wrap; margin-bottom:1.5rem;">
      <div class="form-field" style="min-width:220px; max-width:260px;">
        <label for="exp-category-filter" class="form-label" style="font-size:var(--text-xs); text-transform:uppercase; letter-spacing:.08em;">Filter by category</label>
        <select id="exp-category-filter" class="form-select" onchange="if(this.value) window.location.href=this.value">
          <option value="<?= e(base_url('experiences')) ?>" <?= $moodFilter === '' ? 'selected' : '' ?>>All Experiences</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= e(base_url('experiences') . '?mood=' . urlencode($cat['slug'])) ?>" <?= $moodFilter === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <?php endif; ?>

    <?php if (empty($experiences)): ?>
    <div class="exp-empty">
      <p>No experiences published yet for this mood.</p>
      <a href="<?= e(base_url('experiences')) ?>" class="btn btn-primary">View all experiences</a>
    </div>

    <?php else: ?>
    <div class="exp-cards-grid" id="expGrid">
      <?php foreach ($experiences as $exp):
          $imagePath = content_image_path($exp, ['cover_image','hero_image','og_image']);
          $img = $imagePath !== '' ? stored_media_url($imagePath) : '';
          $itinCnt  = (int)($exp['itinerary_count'] ?? 0);
          $destCnt  = (int)($exp['destination_count'] ?? 0);
          $cat      = !empty($exp['category_id']) ? ($_catById[(int)$exp['category_id']] ?? null) : null;
          $catLabel = $cat ? (($cat['emoji'] ?? '') . ' ' . $cat['name']) : '';
          $excerpt  = mb_strimwidth((string)($exp['excerpt'] ?? ''), 0, 115, '…');
          $inWishlist = in_array($exp['slug'], $_wishlistSlugs, true);
          $metaLabel  = $itinCnt > 0
              ? $itinCnt . ' itinerar' . ($itinCnt === 1 ? 'y' : 'ies')
              : ($destCnt > 0 ? $destCnt . ' destination' . ($destCnt !== 1 ? 's' : '') : 'Experience idea');
      ?>
      <article class="card-exp-wrap" style="position:relative;">
        <a href="<?= e(base_url('experiences/' . $exp['slug'])) ?>" class="card-exp" aria-label="View <?= e($exp['title']) ?>">
          <?php if ($img && !is_placeholder_media_url($img)): ?>
            <?= responsive_image([
                'src' => $img,
                'alt' => $exp['title'],
                'widths' => [400, 800],
                'sizes' => '(min-width: 1060px) 22vw, (min-width: 800px) 33vw, (min-width: 480px) 50vw, 100vw',
                'width' => 640,
                'height' => 480,
                'loading' => 'lazy',
            ]) ?>
          <?php else: ?>
            <div style="position:absolute; inset:0; background:linear-gradient(135deg, var(--brand), var(--surface-mid));"></div>
          <?php endif; ?>
          <span class="card-exp-overlay"></span>
          <span class="card-exp-body">
            <?php if ($catLabel): ?><span class="card-exp-badge" style="background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.28); backdrop-filter:blur(6px); color:#fff;"><?= e(trim($catLabel)) ?></span><?php endif; ?>
            <span class="card-exp-title"><?= e($exp['title']) ?></span>
            <?php if ($excerpt): ?><span class="card-exp-excerpt"><?= e($excerpt) ?></span><?php endif; ?>
            <span style="display:block; margin-top:.5rem; font-size:var(--text-xs); font-weight:600; color:rgba(255,255,255,.92);"><?= e($metaLabel) ?></span>
          </span>
        </a>
        <button class="exp-wishlist-btn <?= $inWishlist ? 'saved' : '' ?>"
                type="button"
                data-slug="<?= e($exp['slug']) ?>"
                aria-label="<?= $inWishlist ? 'Remove ' . e($exp['title']) . ' from wishlist' : 'Save ' . e($exp['title']) . ' to wishlist' ?>">
          <?= $inWishlist ? '♥' : '♡' ?>
        </button>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Cross-links -->
    <div class="exp-crosslinks">
      <a href="<?= e(base_url('itineraries')) ?>">🗺 Browse Itineraries</a>
      <a href="<?= e(base_url('destinations')) ?>">📍 Explore Destinations</a>
      <a href="<?= e(base_url('plan')) ?>">✏️ Plan My Trip</a>
    </div>

  </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     WISHLIST BAR
════════════════════════════════════════════════════════════ -->
<div class="exp-wishlist-bar" id="expWishlistBar" aria-label="Your experience wishlist">
  <div class="exp-wishlist-bar-text">
    <strong id="expWishlistLabel">Your wishlist</strong>
    <span id="expWishlistSub">Save experiences to build a shortlist we can weave into your itinerary.</span>
  </div>
  <div class="exp-wishlist-chips" id="expWishlistChips">
    <span class="exp-wishlist-empty-hint" id="expWishlistEmpty">Nothing saved yet</span>
  </div>
  <a href="<?= e(base_url('plan')) ?>" class="btn-gold" id="expWishlistBtn"
     aria-disabled="true" tabindex="-1">→ Send My Wishlist</a>
</div>

<!-- Toast -->
<div class="ict-toast" id="ict-toast" role="status" aria-live="polite"></div>

<style>
/* ── Hero ────────────────────────────────────────────────── */
.exp-hero {
  background:var(--surface-alt);
  padding:var(--site-overlay-hero-offset, 6rem) 0 2.5rem;
  position:relative;
}
.exp-hero .breadcrumb,
.exp-hero .breadcrumb-item,
.exp-hero .breadcrumb-item a,
.exp-hero .breadcrumb-item.active { color: rgba(255,255,255,.82) !important; }
.exp-hero .breadcrumb-item a:hover { color:#fff !important; }
.exp-hero .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,.45); }
.exp-hero:not([style*="background-image"]) .breadcrumb,
.exp-hero:not([style*="background-image"]) .breadcrumb-item,
.exp-hero:not([style*="background-image"]) .breadcrumb-item a,
.exp-hero:not([style*="background-image"]) .breadcrumb-item.active { color: var(--muted) !important; }
.exp-hero:not([style*="background-image"]) .breadcrumb-item + .breadcrumb-item::before { color: var(--line-strong); }
.exp-hero-top {
  display:flex;
  flex-direction:column;
  gap:1.25rem;
  margin-bottom:1.75rem;
}
@media (min-width:600px) {
  .exp-hero-top { flex-direction:row;align-items:flex-end; }
}
.exp-hero .section-heading em,
.exp-hero .section-title em { font-style:italic; }
.exp-hero-lead,
.exp-hero .section-lead { font-size:.88rem; line-height:1.75; max-width:500px; margin-top:.5rem; }

/* ── Mood tiles ──────────────────────────────────────────── */
.exp-mood-scroll-wrap {
  overflow-x:auto;
  -webkit-overflow-scrolling:touch;
  scrollbar-width:none;
  border-top:1px solid var(--line);
  background:var(--surface-alt);
}
.exp-mood-scroll-wrap::-webkit-scrollbar { display:none; }
.exp-mood-grid {
  display:flex;
  min-width:max-content;
}
@media (min-width:700px) {
  .exp-mood-grid {
    min-width:0;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(100px,1fr));
  }
}
.exp-mood-tile {
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:.4rem;
  padding:.9rem .85rem;
  text-decoration:none;
  border-top:3px solid transparent;
  border-right:1px solid var(--line);
  cursor:pointer;
  transition:all 180ms;
  text-align:center;
  min-width:85px;
  background:var(--surface-alt);
}
.exp-mood-tile:last-child { border-right:none; }
.exp-mood-tile:hover { background:var(--surface);border-top-color:var(--brand-soft); }
.exp-mood-tile.active { background:var(--surface);border-top-color:var(--brand); }
.exp-mood-emoji { font-size:1.55rem;display:block;line-height:1; }
.exp-mood-name {
  font-size:.62rem;font-weight:700;letter-spacing:.06em;
  text-transform:uppercase;color:var(--text);
}
.exp-mood-count { font-size:.58rem;color:var(--muted); }

/* ── Grid section ────────────────────────────────────────── */
.exp-grid-section { padding:2.5rem 0 3rem;background:var(--bg); }
.exp-grid-header.section-head { margin-bottom:1.4rem; }
.exp-grid-header .section-eyebrow { margin-bottom:.35rem; }
.exp-grid-header .section-title em { font-style:italic; }
.exp-grid-header .section-lead { font-size:var(--text-sm); margin-top:.45rem; }
.exp-grid-heading {
  font-family:var(--font-display,Georgia,serif);
  font-size:clamp(1.2rem,2vw,1.7rem);
  font-weight:400;color:var(--text);margin:0;
}
.exp-grid-heading em { font-style:italic; }
.exp-clear-link {
  font-size:.78rem;font-weight:600;color:var(--muted);
  text-decoration:none;padding:.35rem .75rem;
  border:1px solid var(--line-strong);border-radius:999px;
  transition:all 150ms;
}
.exp-clear-link:hover { background:var(--surface-alt);color:var(--text); }

/* Empty */
.exp-empty { text-align:center;padding:3rem 1rem;color:var(--muted); }
.exp-empty p { margin-bottom:1rem; }
.btn-brand {
  display:inline-block;background:var(--brand);color:#fff;
  font-size:.82rem;font-weight:700;padding:.65rem 1.5rem;
  border-radius:999px;text-decoration:none;
}

/* Card grid */
.exp-cards-grid {
  display:grid;grid-template-columns:1fr;gap:1.1rem;
}
@media (min-width:480px) { .exp-cards-grid { grid-template-columns:repeat(2,1fr); } }
@media (min-width:800px) { .exp-cards-grid { grid-template-columns:repeat(3,1fr); } }
@media (min-width:1060px){ .exp-cards-grid { grid-template-columns:repeat(4,1fr); } }

/* Card */
.exp-card {
  border-radius:var(--radius-lg);
  overflow:hidden;
  box-shadow:var(--shadow-sm);
  border:1px solid var(--line);
  background:var(--surface);
  display:flex;flex-direction:column;
  transition:transform 200ms,box-shadow 200ms;
}
.exp-card:hover { transform:translateY(-4px);box-shadow:var(--shadow-md); }

.exp-card-img-link { display:block;text-decoration:none; }
.exp-card-img {
  height:148px;
  position:relative;
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  padding:.75rem;
  background:linear-gradient(135deg,#1a4a2a,#4a8a5a);
  background-size:cover;background-position:center;
}
.exp-cat-tag {
  font-size:.58rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;
  color:#fff;background:rgba(0,0,0,.35);padding:.18rem .48rem;border-radius:3px;
  align-self:flex-start;max-width:70%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.exp-wishlist-btn {
  width:30px;height:30px;
  background:rgba(255,255,255,.92);border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:.82rem;cursor:pointer;flex-shrink:0;
  border:none;line-height:1;align-self:flex-start;
  transition:background 150ms,color 150ms;
}
.exp-wishlist-btn:hover { background:#fff; }
.exp-wishlist-btn.saved { background:#e8334a;color:#fff; }

.exp-card-body {
  padding:.9rem;flex:1;
  display:flex;flex-direction:column;
}
.exp-card-body h3 {
  font-size:.88rem;font-weight:700;color:var(--text);
  margin-bottom:.22rem;line-height:1.3;
}
.exp-card-body h3 a { color:inherit;text-decoration:none; }
.exp-card-body h3 a:hover { color:var(--brand); }
.exp-card-body p {
  font-size:.73rem;color:var(--muted);line-height:1.55;
  flex:1;margin-bottom:.65rem;
}
.exp-card-footer {
  display:flex;align-items:center;justify-content:space-between;
  padding-top:.55rem;border-top:1px solid var(--line);
}
.exp-card-meta  { font-size:.66rem;color:var(--brand);font-weight:600; }
.exp-card-cta   { font-size:.72rem;font-weight:700;color:var(--brand);text-decoration:none; }
.exp-card-cta:hover { color:var(--brand-dark); }

/* Design-system card-exp wrapper — preserves wishlist button behavior */
.card-exp-wrap { position:relative; }
.card-exp-wrap .exp-wishlist-btn {
  position:absolute;
  top:.75rem; right:.75rem;
  z-index:2;
  width:32px; height:32px;
}
.card-exp-wrap .exp-wishlist-btn.saved { background:#e8334a; color:#fff; }
.exp-cards-grid .card-exp { min-height:220px; }
.exp-cards-grid .card-exp img { display:block; }

/* Cross-links */
.exp-crosslinks {
  display:flex;gap:1rem;flex-wrap:wrap;justify-content:center;
  padding-top:2.5rem;border-top:1px solid var(--line);margin-top:2.5rem;
}
.exp-crosslinks a {
  font-size:.82rem;font-weight:600;color:var(--brand);
  text-decoration:none;padding:.4rem .9rem;
  border:1px solid var(--brand-soft);border-radius:999px;
  background:var(--brand-soft);transition:all 150ms;
}
.exp-crosslinks a:hover { background:var(--brand);border-color:var(--brand);color:#fff; }

/* ── Wishlist bar ─────────────────────────────────────────── */
.exp-wishlist-bar {
  background:var(--surface-dark);padding:1rem 1.5rem;
  display:flex;flex-direction:column;gap:.7rem;
}
@media (min-width:600px) {
  .exp-wishlist-bar { flex-direction:row;align-items:center;justify-content:space-between; }
}
.exp-wishlist-bar-text strong { color:#fff;font-size:.88rem;display:block; }
#expWishlistSub  { color:rgba(255,255,255,.74);font-size:.7rem;display:block;margin-top:.1rem; }
.exp-wishlist-chips { display:flex;gap:.4rem;flex-wrap:wrap;align-items:center; }
.exp-wishlist-chip {
  background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.2);
  color:#fff;font-size:.66rem;font-weight:600;
  padding:.26rem .65rem;border-radius:999px;
  display:flex;align-items:center;gap:.3rem;
  animation:spotIn 200ms ease;
}
@keyframes spotIn { from{transform:scale(.8);opacity:0}to{transform:scale(1);opacity:1} }
.exp-wishlist-chip-rm {
  color:rgba(255,255,255,.68);cursor:pointer;
  font-size:.7rem;border:none;background:none;line-height:1;padding:0;
}
.exp-wishlist-chip-rm:hover { color:rgba(255,255,255,.8); }
.exp-wishlist-empty-hint { font-size:.68rem;color:rgba(255,255,255,.66);font-style:italic; }
.btn-gold {
  display:inline-flex;align-items:center;gap:.35rem;
  background:var(--accent);color:#fff;
  font-size:.78rem;font-weight:700;
  padding:.58rem 1.25rem;border-radius:999px;
  text-decoration:none;white-space:nowrap;flex-shrink:0;
  transition:background 150ms,opacity 200ms;
}
.btn-gold[aria-disabled="true"] { opacity:.4;pointer-events:none; }

/* Toast */
.ict-toast {
  position:fixed;bottom:1.5rem;left:50%;
  transform:translateX(-50%) translateY(80px);
  background:var(--surface-dark);color:#fff;
  font-size:.8rem;font-weight:600;
  padding:.65rem 1.3rem;border-radius:999px;
  box-shadow:var(--shadow-lg);z-index:9999;
  transition:transform 280ms cubic-bezier(.34,1.56,.64,1),opacity 280ms;
  opacity:0;white-space:nowrap;pointer-events:none;
}
.ict-toast.show { transform:translateX(-50%) translateY(0);opacity:1; }
</style>

<script>
(function () {
'use strict';

// ── Wishlist state ────────────────────────────────────────
// Titles for display (slug → title map from PHP)
const EXP_TITLES = <?= json_encode(
    array_combine(
        array_column($experiences, 'slug'),
        array_column($experiences, 'title')
    ),
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
) ?>;

let wishlist = <?= json_encode(array_values($_wishlistSlugs), JSON_UNESCAPED_UNICODE) ?>;

// Restore from sessionStorage if server had nothing
if (wishlist.length === 0) {
    try {
        const saved = JSON.parse(sessionStorage.getItem('ict_exp_wishlist') || '[]');
        if (Array.isArray(saved)) wishlist = saved.filter(s => EXP_TITLES[s]);
    } catch(e) {}
}

const wishlistBar   = document.getElementById('expWishlistBar');
const wishlistChips = document.getElementById('expWishlistChips');
const wishlistBtn   = document.getElementById('expWishlistBtn');
const wishlistLabel = document.getElementById('expWishlistLabel');
const wishlistSub   = document.getElementById('expWishlistSub');
const wishlistEmpty = document.getElementById('expWishlistEmpty');
const toast         = document.getElementById('ict-toast');

// ── Toast ─────────────────────────────────────────────────
let _tt;
function showToast(msg) {
    toast.textContent = msg;
    toast.classList.add('show');
    clearTimeout(_tt);
    _tt = setTimeout(() => toast.classList.remove('show'), 2400);
}

// ── Render wishlist bar ───────────────────────────────────
function renderWishlist() {
    wishlistChips.querySelectorAll('.exp-wishlist-chip').forEach(c => c.remove());
    wishlist.forEach(slug => {
        const title = EXP_TITLES[slug] || slug;
        const chip  = document.createElement('div');
        chip.className    = 'exp-wishlist-chip';
        chip.dataset.slug = slug;
        chip.innerHTML    = `${title} <button class="exp-wishlist-chip-rm" type="button"
            data-slug="${slug}" aria-label="Remove ${title}">✕</button>`;
        wishlistChips.insertBefore(chip, wishlistEmpty);
    });

    const has = wishlist.length > 0;
    wishlistEmpty.style.display = has ? 'none' : '';
    wishlistBtn.setAttribute('aria-disabled', has ? 'false' : 'true');
    wishlistBtn.setAttribute('tabindex', has ? '0' : '-1');
    wishlistLabel.textContent = has
        ? `Your wishlist — ${wishlist.length} experience${wishlist.length > 1 ? 's' : ''}`
        : 'Your wishlist';
    wishlistSub.textContent = has
        ? "We'll use this to suggest the best itinerary match for your trip."
        : 'Save experiences to build a shortlist we can weave into your itinerary.';
    if (has) {
        wishlistBtn.href = `<?= e(base_url('plan')) ?>?experiences=${encodeURIComponent(wishlist.join(','))}`;
    }
}

// ── Toggle wishlist ───────────────────────────────────────
function toggleWishlist(slug) {
    const title = EXP_TITLES[slug] || slug;
    const idx   = wishlist.indexOf(slug);
    if (idx === -1) {
        wishlist.push(slug);
        showToast(`♥ "${title}" saved to your wishlist`);
    } else {
        wishlist.splice(idx, 1);
        showToast(`Removed "${title}"`);
    }
    renderWishlist();
    syncButtons(slug);
    persist();
}

// ── Sync all heart buttons ────────────────────────────────
function syncButtons(slug) {
    document.querySelectorAll(`.exp-wishlist-btn[data-slug="${slug}"]`).forEach(btn => {
        const saved = wishlist.includes(slug);
        btn.textContent = saved ? '♥' : '♡';
        btn.classList.toggle('saved', saved);
        btn.setAttribute('aria-label',
            (saved ? 'Remove ' : 'Save ') + (EXP_TITLES[slug] || slug) +
            (saved ? ' from' : ' to') + ' wishlist');
    });
}

// ── Persist ───────────────────────────────────────────────
function persist() {
    try { sessionStorage.setItem('ict_exp_wishlist', JSON.stringify(wishlist)); } catch(e) {}
    const token = document.cookie.match('(^|;)\\s*ict_route_token\\s*=\\s*([^;]+)');
    if (token) {
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=save_wishlist&exp_slugs=${encodeURIComponent(wishlist.join(','))}&token=${encodeURIComponent(token[2])}&_token=<?= csrf_token() ?>`,
            keepalive: true,
        }).catch(() => {});
    }
}

// ── Events ────────────────────────────────────────────────
document.querySelectorAll('.exp-wishlist-btn').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        toggleWishlist(btn.dataset.slug);
    });
});

wishlistChips.addEventListener('click', e => {
    const rm = e.target.closest('.exp-wishlist-chip-rm');
    if (rm) toggleWishlist(rm.dataset.slug);
});

// ── Init ──────────────────────────────────────────────────
renderWishlist();
wishlist.forEach(s => syncButtons(s));

})();
</script>

<?php require __DIR__ . '/includes/cta.php'; ?>
<?php schema_output($schemaGraph); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
