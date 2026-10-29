<?php
declare(strict_types=1);

$adminTitle = 'Trip Planner Settings';
$adminPage = 'trip_planner_settings';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
ensure_site_settings_schema();

$intro = trim((string) site_setting_get('trip_planner_intro', trip_planner_intro_text()));
$daysCsv = trim((string) site_setting_get('trip_planner_days_csv', implode(',', trip_planner_days_options())));
$metaTitle = trim((string) site_setting_get('trip_planner_meta_title', trip_planner_meta_title()));
$metaDescription = trim((string) site_setting_get('trip_planner_meta_description', trip_planner_meta_description()));

$themes = trip_planner_theme_options();
$themeLines = [];
foreach ($themes as $t) {
    $slug = trim((string) ($t['slug'] ?? ''));
    $label = trim((string) ($t['label'] ?? ''));
    if ($slug !== '' && $label !== '') {
        $themeLines[] = $slug . ' | ' . $label;
    }
}
$themesText = implode("\n", $themeLines);

$oldIntro = old_input('trip_planner_intro', '__ICT_NO_OLD__');
if ($oldIntro !== '__ICT_NO_OLD__') {
    $intro = trim((string) $oldIntro);
    $daysCsv = trim((string) old_input('trip_planner_days_csv', $daysCsv));
    $themesText = (string) old_input('trip_planner_themes_text', $themesText);
    $metaTitle = trim((string) old_input('trip_planner_meta_title', $metaTitle));
    $metaDescription = trim((string) old_input('trip_planner_meta_description', $metaDescription));
}

require __DIR__ . '/_top.php';
?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-2">Planner Controls</h2>
                <p class="text-secondary small mb-3">
                    Controls the options displayed on <code>/plan</code>. The page is intentionally <code>noindex</code> and meant for conversion, not SEO indexing.
                </p>

                <form method="post" action="<?= e(base_url('admin/trip_planner_settings_save.php')) ?>" data-ai-context="trip_planner" data-ai-title="Trip Planner Settings">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Intro Text</label>
                        <textarea name="trip_planner_intro" rows="3" class="form-control" data-ai-field="trip_planner_intro" data-ai-label="Trip Planner Intro Text" data-ai-context="Introduction paragraph shown at the top of the trip planner / Plan My Trip form. Should welcome the visitor, explain that the team will build a custom itinerary, and encourage them to fill in the form. Warm, personal tone. 2-3 sentences."><?= e($intro) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Day Options (CSV)</label>
                        <input type="text" name="trip_planner_days_csv" class="form-control" value="<?= e($daysCsv) ?>" placeholder="3,4,5,6,7,8,9,10,12,14">
                        <div class="form-text">Numbers 1-30. These are the values shown in the dropdown.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Themes (one per line: <code>slug | Label</code>)</label>
                        <textarea name="trip_planner_themes_text" rows="6" class="form-control" placeholder="honeymoon-tours | Honeymoon"><?= e($themesText) ?></textarea>
                        <div class="form-text">Slugs should match your theme routes (e.g. <code>honeymoon-tours</code>).</div>
                    </div>

                    <hr class="my-4">

                    <h3 class="h6 mb-2">SEO Meta (Optional)</h3>
                    <div class="mb-3">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="trip_planner_meta_title" class="form-control" value="<?= e($metaTitle) ?>" data-ai-field="trip_planner_meta_title" data-ai-label="Trip Planner Meta Title" data-ai-context="SEO page title for the Plan My Trip page. 50-60 characters. Include 'Sri Lanka' and a relevant action phrase. No markdown.">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Meta Description</label>
                        <textarea name="trip_planner_meta_description" rows="3" class="form-control" data-ai-field="trip_planner_meta_description" data-ai-label="Trip Planner Meta Description" data-ai-context="SEO meta description for the Plan My Trip page. 140-160 characters. Should describe what the trip planner does and include the keyword 'Sri Lanka private tour'. No markdown."><?= e($metaDescription) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">Save Trip Planner Settings</button>
                    <a href="<?= e(base_url('plan')) ?>" class="btn btn-outline-dark ms-2" target="_blank" rel="noopener">Preview /plan</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h6 mb-2">Interlinking Tip</h2>
                <p class="small text-secondary mb-0">
                    For SEO, the indexable “When to Visit” entrypoint is <code>/seasonal</code> and the monthly pages.
                    The planner is a conversion tool linked from headers, footers, and relevant landing pages.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>

