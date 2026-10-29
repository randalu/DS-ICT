<?php
declare(strict_types=1);

/**
 * Design QA checklist (development only).
 *
 * A single page that lists every v3 route with the viewport/animation/state
 * checks the phase gates require, so visual review does not depend on someone
 * remembering the list. Returns 404 outside APP_DEBUG.
 */

require __DIR__ . '/app/bootstrap.php';

if (!defined('APP_DEBUG') || !APP_DEBUG) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found\n";
    exit;
}

$routes = [
    ['Home', 'index.php', 'v3', 'hero · marquee · bento · rail · matcher island · steps · journal · CTA'],
    ['Destinations index', 'destinations.php', 'v2', 'filters · grid · pagination'],
    ['Experiences index', 'experiences.php', 'v2', 'category filter · grid'],
    ['Tours index', 'tours.php', 'v2', 'search · grid · theme links'],
    ['Itineraries index', 'itineraries.php', 'v2', 'length filter · grid'],
    ['Day tours index', 'day-tours.php', 'v2', 'difficulty + price · grid'],
    ['Activities index', 'activities.php', 'v2', 'search · grid'],
    ['Destination detail', 'destination.php', 'legacy', 'rd- design system'],
    ['Experience detail', 'experience.php', 'legacy', 'exp- design system'],
    ['Tour detail', 'tour.php', 'legacy', 'page-hero-banner'],
    ['Itinerary detail', 'itinerary.php', 'legacy', 'itin- design system'],
    ['Day tour detail', 'day-tour.php', 'legacy', 'dt- design system'],
    ['Blog index', 'blog.php', 'legacy', 'page-hero-banner'],
    ['Blog article', 'blog_post.php', 'legacy', 'page-hero-banner'],
    ['Contact', 'contact.php', 'legacy', 'Bootstrap form'],
    ['Reviews', 'reviews.php', 'legacy', 'Bootstrap'],
];

$checks = [
    'Viewports: 390 / 768 / 1024 / 1440',
    'Keyboard only: tab through header, filters, cards, footer',
    'prefers-reduced-motion: no reveal, no marquee, no parallax',
    'No horizontal scroll at 390',
    'Focus visible on every interactive element',
    'Images: alt present, no layout shift while loading',
    'Console: no Vue/Alpine warnings, no 404s',
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Design QA — <?= e(APP_NAME) ?></title>
    <link href="<?= e(base_url('assets/fonts/brand.css')) ?>" rel="stylesheet">
    <?php foreach (['tokens', 'base', 'layout', 'components', 'pages'] as $sheet): ?>
        <link href="<?= e(base_url('assets/css/v3/' . $sheet . '.css')) ?>" rel="stylesheet">
    <?php endforeach; ?>
    <style>
        body { padding: 3rem 0; }
        .qa-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; font-size: .875rem; }
        .qa-table th, .qa-table td { text-align: left; padding: .7rem .6rem; border-bottom: 1px solid var(--ix-line); vertical-align: top; }
        .qa-table th { font-size: .6875rem; letter-spacing: .16em; text-transform: uppercase; color: var(--ix-text-faint); }
        .qa-tag { display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .625rem; letter-spacing: .12em; text-transform: uppercase; border: 1px solid var(--ix-line-strong); }
        .qa-tag--v3 { color: var(--ix-coral); border-color: rgba(255,107,74,.5); }
        .qa-tag--v2 { color: var(--ix-seafoam); border-color: rgba(127,216,196,.5); }
        .qa-list { margin-top: 1.5rem; display: grid; gap: .5rem; }
        .qa-list li { padding-left: 1.4rem; position: relative; color: var(--ix-text-dim); }
        .qa-list li::before { content: "□"; position: absolute; left: 0; color: var(--ix-coral); }
    </style>
</head>
<body class="ix3">
<main class="ix-wrap">
    <p class="ix-eyebrow">Internal · noindex</p>
    <h1 class="ix-h2">Design QA checklist</h1>
    <p class="ix-lead">Every public route, which shell it currently renders, and the checks each phase gate requires.</p>

    <table class="qa-table">
        <thead>
            <tr><th>Route</th><th>File</th><th>Shell</th><th>Layout signature</th></tr>
        </thead>
        <tbody>
        <?php foreach ($routes as [$label, $file, $shell, $note]): ?>
            <tr>
                <td><a href="<?= e(base_url($file)) ?>"><?= e($label) ?></a></td>
                <td><code><?= e($file) ?></code></td>
                <td><span class="qa-tag qa-tag--<?= e($shell) ?>"><?= e($shell) ?></span></td>
                <td><?= e($note) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="ix-h3" style="margin-top:3rem">Every gate</h2>
    <ul class="qa-list">
        <?php foreach ($checks as $check): ?>
            <li><?= e($check) ?></li>
        <?php endforeach; ?>
    </ul>
</main>
</body>
</html>
