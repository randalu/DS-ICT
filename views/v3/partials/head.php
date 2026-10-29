<?php
declare(strict_types=1);

/**
 * <head> for the Daylight (v3.1) layout. Light is the server default.
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

$css = ['tokens', 'base', 'layout', 'components', 'pages'];
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <?php require APP_ROOT . '/includes/seo.php'; ?>

    <meta name="theme-color" content="#F6F6F3" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0C1412" media="(prefers-color-scheme: dark)">

    <script>(function(){try{var t=localStorage.getItem('ict-theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}})();</script>

    <script>window.ICTPrivacy = <?= json_encode(ict_privacy_browser_config(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;</script>
    <script src="<?= e(ict_privacy_url('assets/js/privacy-controls.js')) ?>?v=<?= (int) filemtime(APP_ROOT . '/assets/js/privacy-controls.js') ?>" defer></script>
    <script src="<?= e(ict_privacy_url('assets/js/optional-resources.js')) ?>" defer></script>
    <?php render_google_tag_manager_head(); ?>
    <?= ict_privacy_optional_template('external_reviews', 'trustpilot-loader', '<script async src="https://widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js"></script>') ?>
    <?= ict_privacy_optional_template('translation', 'gtranslate', '<script>window.gtranslateSettings={"default_language":"en","languages":["en","fr","de","es","it","nl","ja","zh-CN","ko","ru","ar"],"wrapper_selector":".gtranslate_wrapper","switcher_horizontal_position":"inline","alt_flags":{"en":"usa"}};</script><script async src="https://cdn.gtranslate.net/widgets/latest/dwf.js"></script>') ?>

    <link href="<?= e(base_url('assets/fonts/brand.css')) ?>" rel="stylesheet">
    <?php foreach ($css as $file): ?>
        <link href="<?= e(base_url('assets/css/v3/' . $file . '.css')) ?>?v=<?= (int) filemtime(APP_ROOT . '/assets/css/v3/' . $file . '.css') ?>" rel="stylesheet">
    <?php endforeach; ?>
    <style>[x-cloak]{display:none !important}</style>
</head>
<body class="ix3 <?= e($page->bodyClass) ?>" x-data="{ menu: false }" :class="{ 'ix-drawer-open': menu }">
