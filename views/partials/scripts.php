<?php
declare(strict_types=1);

/**
 * Closing scripts for the v2 public layout.
 *
 * ES modules only, deferred by definition, and no Bootstrap/Alpine/jQuery.
 * All three files are progressive enhancements: the page is fully readable
 * and navigable with JavaScript disabled.
 *
 * @var \ICT\View\Page $page
 */

$jsVersion = static function (string $file): string {
    $path = APP_ROOT . '/assets/js/site/' . $file;

    return is_file($path) ? (string) filemtime($path) : '1';
};
?>
<script type="module" src="<?= e(base_url('assets/js/site/nav.js')) ?>?v=<?= e($jsVersion('nav.js')) ?>"></script>
<script type="module" src="<?= e(base_url('assets/js/site/reveal.js')) ?>?v=<?= e($jsVersion('reveal.js')) ?>"></script>
<script type="module" src="<?= e(base_url('assets/js/site/filters.js')) ?>?v=<?= e($jsVersion('filters.js')) ?>"></script>
<script type="module" src="<?= e(base_url('assets/js/site/analytics.js')) ?>?v=<?= e($jsVersion('analytics.js')) ?>"></script>
<?php render_google_tag_queued_events(); ?>
</body>
</html>
