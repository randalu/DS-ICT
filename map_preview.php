<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$seoTitle = 'Map Preview | ' . APP_NAME;
$seoDescription = 'Temporary local map preview page.';
$seoCanonical = base_url('map_preview.php');
$seoRobots = 'noindex,nofollow,noarchive';
$seoImage = DEFAULT_OG_IMAGE;
$seoType = 'website';
$bodyClass = 'page-map-preview';

require __DIR__ . '/includes/header.php';
?>

<section class="py-5 border-bottom" style="background: var(--surface);">
    <div class="container">
        <span class="section-kicker">Temporary Preview</span>
        <h1 class="section-heading mb-3">Google Map Preview</h1>
        <p class="text-secondary mb-0">
            This public preview page is not linked from navigation, sitemap, footer, or admin. It is marked noindex.
        </p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="map-preview-shell">
            <iframe
                src="https://storage.googleapis.com/maps-solutions-cahe1s9bxu/neighborhood-discovery/6s93/neighborhood-discovery.html"
                title="Sri Lanka neighborhood discovery map"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                style="border:0;width:100%;height:100%;">
            </iframe>
        </div>
    </div>
</section>

<style>
.map-preview-shell {
    width: 100%;
    height: clamp(520px, 70vh, 760px);
    overflow: hidden;
    border: 1px solid rgba(26, 70, 48, 0.14);
    border-radius: 1.35rem;
    background: #f4efe6;
    box-shadow: 0 20px 50px rgba(26, 70, 48, 0.10);
}

@media (max-width: 767.98px) {
    .map-preview-shell {
        height: 520px;
        border-radius: 1rem;
    }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
