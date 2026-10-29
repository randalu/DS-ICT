<?php
declare(strict_types=1);

/**
 * <head> for the v2 public layout.
 *
 * SEO parity is guaranteed by delegating to includes/seo.php, which owns
 * title normalisation, canonical normalisation, robots and OG/Twitter output.
 *
 * @var \ICT\View\Page $page
 */

$seoTitle = $page->meta('title', APP_NAME);
$seoDescription = $page->meta('description');
$seoCanonical = $page->meta('canonical', base_url(ltrim(request_path(), '/')));
$seoImage = $page->meta('image', DEFAULT_OG_IMAGE);
$seoImageAlt = $page->meta('image_alt');
$seoType = $page->meta('type', 'website');
$seoRobots = $page->meta('robots', 'index,follow,max-image-preview:large');
$seoOgTitle = $page->meta('og_title');
$seoOgDescription = $page->meta('og_description');

$cssFiles = ['tokens', 'base', 'layout', 'components', 'pages'];
$cssBase = APP_ROOT . '/assets/css/site/';
$fontDisplay = APP_ROOT . '/assets/fonts/destination-widget/fraunces-latin.woff2';
$fontBody = APP_ROOT . '/assets/fonts/destination-widget/manrope-latin.woff2';
?>
<!doctype html>
<html lang="en">
<head>
    <?php require APP_ROOT . '/includes/seo.php'; ?>

    <meta name="theme-color" content="#123F49">

    <link rel="preload" href="<?= e(base_url('assets/fonts/destination-widget/fraunces-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= e(base_url('assets/fonts/destination-widget/manrope-latin.woff2')) ?>" as="font" type="font/woff2" crossorigin>

    <script>window.ICTPrivacy = <?= json_encode(ict_privacy_browser_config(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;</script>
    <script src="<?= e(ict_privacy_url('assets/js/privacy-controls.js')) ?>?v=<?= (int) filemtime(APP_ROOT . '/assets/js/privacy-controls.js') ?>" defer></script>
    <script src="<?= e(ict_privacy_url('assets/js/optional-resources.js')) ?>" defer></script>
    <?php render_google_tag_manager_head(); ?>
    <?= ict_privacy_optional_template('external_reviews', 'trustpilot-loader', '<script async src="https://widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js"></script>') ?>
    <?= ict_privacy_optional_template('translation', 'gtranslate', '<script>window.gtranslateSettings={"default_language":"en","languages":["en","fr","de","es","it","nl","ja","zh-CN","ko","ru","ar"],"wrapper_selector":".gtranslate_wrapper","switcher_horizontal_position":"inline","alt_flags":{"en":"usa"}};</script><script async src="https://cdn.gtranslate.net/widgets/latest/dwf.js"></script>') ?>

    <link href="<?= e(base_url('assets/css/typography.css')) ?>?v=<?= is_file(APP_ROOT . '/assets/css/typography.css') ? (int) filemtime(APP_ROOT . '/assets/css/typography.css') : 1 ?>" rel="stylesheet">
    <?php foreach ($cssFiles as $cssFile): ?>
        <link href="<?= e(base_url('assets/css/site/' . $cssFile . '.css')) ?>?v=<?= is_file($cssBase . $cssFile . '.css') ? (int) filemtime($cssBase . $cssFile . '.css') : 1 ?>" rel="stylesheet">
    <?php endforeach; ?>
</head>
<body class="<?= e($page->bodyClass) ?>">
