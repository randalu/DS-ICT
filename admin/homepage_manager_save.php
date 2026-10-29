<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/homepage_manager.php');
}

guard_post_size();
require_csrf();
ensure_homepage_manager_schema();

$action = trim((string) post_input('action', ''));

function admin_homepage_redirect(): void
{
    redirect('admin/homepage_manager.php');
}

function admin_homepage_text(string $key, int $limit = 255): string
{
    return substr(trim((string) post_input($key, '')), 0, $limit);
}

function admin_homepage_site_path(mixed $value, string $label, array &$errors, int $limit = 255): ?string
{
    $path = substr(trim((string) $value), 0, $limit);
    if ($path === '') {
        return null;
    }

    $path = str_replace('\\', '/', $path);
    $parsedPath = parse_url($path, PHP_URL_PATH);

    if (
        preg_match('/[\x00-\x1F\x7F]/', $path)
        || str_starts_with($path, '//')
        || preg_match('/^[a-z][a-z0-9+.-]*:/i', $path)
        || $parsedPath === false
        || str_contains((string) $parsedPath, '..')
    ) {
        $errors[] = $label . ' must be a same-site path like contact or itineraries.';
        return null;
    }

    return ltrim($path, '/');
}

if ($action === 'upload_hero') {
    $errors = [];
    $primaryCtaUrl = admin_homepage_site_path(post_input('primary_cta_url', ''), 'Primary CTA URL', $errors);
    $secondaryCtaUrl = admin_homepage_site_path(post_input('secondary_cta_url', ''), 'Secondary CTA URL', $errors);
    if ($errors) {
        flash_set('error', implode(' ', $errors));
        admin_homepage_redirect();
    }

    try {
        $media = homepage_upload_media_asset($_FILES['hero_media'] ?? []);
        $posterPath = null;
        if (!empty($_FILES['poster_image']['name'])) {
            $posterPath = upload_image($_FILES['poster_image'], 'homepage-hero');
        }

        execute_query(
            "INSERT INTO homepage_hero_media
                (media_asset_id, media_type, file_path, poster_path, headline_script, headline_main,
                 supporting_text, primary_cta_label, primary_cta_url, secondary_cta_label,
                 secondary_cta_url, is_active, sort_order)
             VALUES
                (:media_asset_id, :media_type, :file_path, :poster_path, :headline_script, :headline_main,
                 :supporting_text, :primary_cta_label, :primary_cta_url, :secondary_cta_label,
                 :secondary_cta_url, :is_active, :sort_order)",
            [
                'media_asset_id' => $media['media_asset_id'],
                'media_type' => $media['media_type'],
                'file_path' => $media['file_path'],
                'poster_path' => $posterPath,
                'headline_script' => admin_homepage_text('headline_script', 180) ?: null,
                'headline_main' => admin_homepage_text('headline_main', 255) ?: null,
                'supporting_text' => trim((string) post_input('supporting_text', '')) ?: null,
                'primary_cta_label' => admin_homepage_text('primary_cta_label', 120) ?: null,
                'primary_cta_url' => $primaryCtaUrl,
                'secondary_cta_label' => admin_homepage_text('secondary_cta_label', 120) ?: null,
                'secondary_cta_url' => $secondaryCtaUrl,
                'is_active' => post_input('is_active') ? 1 : 0,
                'sort_order' => (int) post_input('sort_order', 0),
            ]
        );

        flash_set('success', 'Homepage hero media uploaded.');
    } catch (Throwable) {
        flash_set('error', 'Failed to upload homepage hero media.');
    }
    admin_homepage_redirect();
}

if ($action === 'save_heroes') {
    $deleteId = (int) post_input('delete_hero_id', 0);
    if ($deleteId > 0) {
        $hero = fetch_one('SELECT id FROM homepage_hero_media WHERE id = :id LIMIT 1', ['id' => $deleteId]);
        if (!$hero) {
            flash_set('error', 'Homepage hero item not found.');
            admin_homepage_redirect();
        }

        try {
            execute_query('DELETE FROM homepage_hero_media WHERE id = :id', ['id' => $deleteId]);
            flash_set('success', 'Homepage hero item deleted.');
        } catch (Throwable $e) {
            flash_set('error', 'Failed to delete homepage hero item.');
        }
        admin_homepage_redirect();
    }

    $heroes = post_input('heroes', []);
    $errors = [];
    $cleanHeroes = [];
    if (is_array($heroes)) {
        foreach ($heroes as $id => $row) {
            if (!is_array($row)) {
                continue;
            }
            $heroId = (int) $id;
            if ($heroId <= 0) {
                continue;
            }

            $cleanHeroes[$heroId] = [
                'headline_script' => substr(trim((string) ($row['headline_script'] ?? '')), 0, 180) ?: null,
                'headline_main' => substr(trim((string) ($row['headline_main'] ?? '')), 0, 255) ?: null,
                'supporting_text' => trim((string) ($row['supporting_text'] ?? '')) ?: null,
                'primary_cta_label' => substr(trim((string) ($row['primary_cta_label'] ?? '')), 0, 120) ?: null,
                'primary_cta_url' => admin_homepage_site_path($row['primary_cta_url'] ?? '', 'Primary CTA URL for hero #' . $heroId, $errors),
                'secondary_cta_label' => substr(trim((string) ($row['secondary_cta_label'] ?? '')), 0, 120) ?: null,
                'secondary_cta_url' => admin_homepage_site_path($row['secondary_cta_url'] ?? '', 'Secondary CTA URL for hero #' . $heroId, $errors),
                'is_active' => isset($row['is_active']) ? 1 : 0,
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'id' => $heroId,
            ];
        }
    }

    if ($errors) {
        flash_set('error', implode(' ', $errors));
        admin_homepage_redirect();
    }

    foreach ($cleanHeroes as $heroId => $cleanHero) {
        execute_query(
            "UPDATE homepage_hero_media
             SET headline_script = :headline_script,
                 headline_main = :headline_main,
                 supporting_text = :supporting_text,
                 primary_cta_label = :primary_cta_label,
                 primary_cta_url = :primary_cta_url,
                 secondary_cta_label = :secondary_cta_label,
                 secondary_cta_url = :secondary_cta_url,
                 is_active = :is_active,
                 sort_order = :sort_order
             WHERE id = :id",
            $cleanHero
        );
    }

    $pinnedHeroId = (int) post_input('pinned_hero_id', 0);
    site_setting_set('homepage_new_pinned_hero_id', $pinnedHeroId > 0 ? (string) $pinnedHeroId : '');

    flash_set('success', 'Homepage hero items saved.');
    admin_homepage_redirect();
}

if ($action === 'save_sections') {
    $submitted = post_input('sections', []);
    $defaults = homepage_section_defaults();
    $config = [];
    foreach ($defaults as $key => $default) {
        $row = isset($submitted[$key]) && is_array($submitted[$key]) ? $submitted[$key] : [];
        $config[$key] = [
            'label' => $default['label'],
            'enabled' => isset($row['enabled']),
            'sort_order' => (int) ($row['sort_order'] ?? $default['sort_order']),
        ];
    }

    site_setting_set('homepage_new_layout_json', (string) json_encode($config, JSON_UNESCAPED_SLASHES));
    flash_set('success', 'Homepage section layout saved.');
    admin_homepage_redirect();
}

if ($action === 'save_cta') {
    $errors = [];
    $ctaButtonUrl = admin_homepage_site_path(post_input('homepage_new_cta_button_url', ''), 'CTA button URL', $errors);
    if ($errors) {
        flash_set('error', implode(' ', $errors));
        admin_homepage_redirect();
    }

    foreach (['homepage_new_cta_heading', 'homepage_new_cta_body', 'homepage_new_cta_button_label'] as $key) {
        site_setting_set($key, trim((string) post_input($key, '')));
    }
    site_setting_set('homepage_new_cta_button_url', $ctaButtonUrl ?? '');

    if (!empty($_FILES['cta_image']['name'])) {
        try {
            site_setting_set('homepage_new_cta_image', (string) upload_image($_FILES['cta_image'], 'homepage'));
        } catch (Throwable) {
            flash_set('error', 'Failed to upload CTA image.');
            admin_homepage_redirect();
        }
    }

    flash_set('success', 'Homepage CTA banner saved.');
    admin_homepage_redirect();
}

if ($action === 'save_seo') {
    foreach (['homepage_new_seo_title', 'homepage_new_seo_description', 'homepage_new_og_title', 'homepage_new_og_description'] as $key) {
        site_setting_set($key, trim((string) post_input($key, '')));
    }
    if (!empty($_FILES['homepage_og_image']['name'])) {
        try {
            site_setting_set('homepage_new_og_image', (string) upload_image($_FILES['homepage_og_image'], 'homepage'));
        } catch (Throwable) {
            flash_set('error', 'Failed to upload homepage OG image.');
            admin_homepage_redirect();
        }
    }

    flash_set('success', 'Homepage SEO settings saved.');
    admin_homepage_redirect();
}

flash_set('error', 'Unknown homepage manager action.');
admin_homepage_redirect();
