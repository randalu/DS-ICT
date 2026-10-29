<?php
declare(strict_types=1);

/**
 * Closing scripts for the v3 layout.
 *
 * Alpine is loaded on every v3 page (drawer, future micro-interactions). Vue is
 * loaded only when the page declares islands, because the global build is the
 * single heaviest asset on the site (~61 KB gzip).
 *
 * @var \ICT\View\Page $page
 */

$hasIslands = !empty($page->data['islands']);

$version = static function (string $relative): string {
    $path = APP_ROOT . '/' . ltrim($relative, '/');

    return is_file($path) ? (string) filemtime($path) : '1';
};
?>
<script defer src="<?= e(ict_privacy_url('assets/vendor/alpinejs-3.14.8/dist/cdn.min.js')) ?>"></script>
<script defer src="<?= e(base_url('assets/js/site/theme.js')) ?>?v=<?= e($version('assets/js/site/theme.js')) ?>"></script>
<?php if ($hasIslands): ?>
    <script defer src="<?= e(base_url('lib/vue-3.5.44/dist/vue.global.prod.js')) ?>"></script>
    <script defer src="<?= e(base_url('assets/js/site/islands.js')) ?>?v=<?= e($version('assets/js/site/islands.js')) ?>"></script>
<?php endif; ?>
<script type="module" src="<?= e(base_url('assets/js/site/motion.js')) ?>?v=<?= e($version('assets/js/site/motion.js')) ?>"></script>
<?php render_google_tag_queued_events(); ?>
</body>
</html>
