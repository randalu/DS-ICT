<?php
declare(strict_types=1);

$adminTitle = 'Homepage Manager';
$adminPage = 'homepage_manager';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
ensure_homepage_manager_schema();

$settings = homepage_new_settings();
$sections = homepage_new_section_config();
$heroRows = homepage_new_hero_rows(false);
$pinnedHeroId = (int) ($settings['homepage_new_pinned_hero_id'] ?? 0);

$mediaUrl = static function (?string $path): string {
    return stored_media_url($path, DEFAULT_OG_IMAGE);
};

require __DIR__ . '/_top.php';
?>

<div class="row g-3">
    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Hero Media Pool</h2>
                        <p class="admin-help-text mb-0">Active hero items rotate daily on the homepage. Pin one item to override rotation until you unpin it.</p>
                    </div>
                    <a href="<?= e(base_url()) ?>" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm">Preview Homepage</a>
                </div>

                <form method="post" action="<?= e(base_url('admin/homepage_manager_save.php')) ?>" enctype="multipart/form-data" class="border rounded-4 p-3 mb-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload_hero">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Hero image/video</label>
                            <input type="file" name="hero_media" class="form-control" accept="image/*,video/mp4,video/webm,video/quicktime" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Video poster image</label>
                            <input type="file" name="poster_image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="10">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="new_hero_active" checked>
                                <label class="form-check-label" for="new_hero_active">Active</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Script Line</label>
                            <input type="text" name="headline_script" class="form-control" value="The Beauty of Sri Lanka">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Main Headline</label>
                            <textarea name="headline_main" rows="2" class="form-control">Private Sri Lanka Tours with Your Own Driver-Guide</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supporting Text</label>
                            <textarea name="supporting_text" rows="2" class="form-control">Explore private Sri Lanka tours and chauffeur-guided travel for couples, families, wildlife, beaches, and culture—with a flexible itinerary shaped around your dates and pace.</textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Primary CTA Label</label>
                            <input type="text" name="primary_cta_label" class="form-control" value="Explore Itineraries">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Primary CTA URL</label>
                            <input type="text" name="primary_cta_url" class="form-control" value="itineraries">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Secondary CTA Label</label>
                            <input type="text" name="secondary_cta_label" class="form-control" value="Plan Your Trip">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Secondary CTA URL</label>
                            <input type="text" name="secondary_cta_url" class="form-control" value="contact">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success">Upload Hero Media</button>
                        </div>
                    </div>
                </form>

                <form method="post" action="<?= e(base_url('admin/homepage_manager_save.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_heroes">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>Preview</th>
                                <th>Text</th>
                                <th>CTAs</th>
                                <th>Active</th>
                                <th>Pinned</th>
                                <th>Order</th>
                                <th class="text-end">Delete</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!$heroRows): ?>
                                <tr><td colspan="7" class="text-secondary">No uploaded hero media yet. The default static hero is currently used.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($heroRows as $hero): ?>
                                <?php $heroId = (int) $hero['id']; ?>
                                <tr>
                                    <td style="width:180px;">
                                        <?php if (($hero['media_type'] ?? '') === 'video'): ?>
                                            <video src="<?= e($mediaUrl((string) $hero['file_path'])) ?>" muted preload="metadata" style="width:160px;height:90px;object-fit:cover;border-radius:.5rem;background:#000;"></video>
                                        <?php else: ?>
                                            <img src="<?= e($mediaUrl((string) $hero['file_path'])) ?>" alt="" style="width:160px;height:90px;object-fit:cover;border-radius:.5rem;">
                                        <?php endif; ?>
                                        <div class="small text-secondary mt-1"><?= e((string) $hero['media_type']) ?></div>
                                    </td>
                                    <td>
                                        <input type="text" name="heroes[<?= $heroId ?>][headline_script]" class="form-control form-control-sm mb-1" value="<?= e((string) ($hero['headline_script'] ?? '')) ?>" placeholder="Script line">
                                        <textarea name="heroes[<?= $heroId ?>][headline_main]" rows="2" class="form-control form-control-sm mb-1" placeholder="Main headline"><?= e((string) ($hero['headline_main'] ?? '')) ?></textarea>
                                        <textarea name="heroes[<?= $heroId ?>][supporting_text]" rows="2" class="form-control form-control-sm" placeholder="Supporting text"><?= e((string) ($hero['supporting_text'] ?? '')) ?></textarea>
                                    </td>
                                    <td>
                                        <input type="text" name="heroes[<?= $heroId ?>][primary_cta_label]" class="form-control form-control-sm mb-1" value="<?= e((string) ($hero['primary_cta_label'] ?? '')) ?>" placeholder="Primary label">
                                        <input type="text" name="heroes[<?= $heroId ?>][primary_cta_url]" class="form-control form-control-sm mb-2" value="<?= e((string) ($hero['primary_cta_url'] ?? '')) ?>" placeholder="Primary URL">
                                        <input type="text" name="heroes[<?= $heroId ?>][secondary_cta_label]" class="form-control form-control-sm mb-1" value="<?= e((string) ($hero['secondary_cta_label'] ?? '')) ?>" placeholder="Secondary label">
                                        <input type="text" name="heroes[<?= $heroId ?>][secondary_cta_url]" class="form-control form-control-sm" value="<?= e((string) ($hero['secondary_cta_url'] ?? '')) ?>" placeholder="Secondary URL">
                                    </td>
                                    <td>
                                        <input type="checkbox" name="heroes[<?= $heroId ?>][is_active]" value="1" class="form-check-input" <?= (int) ($hero['is_active'] ?? 0) === 1 ? 'checked' : '' ?>>
                                    </td>
                                    <td>
                                        <div class="form-check">
                                            <input type="radio" name="pinned_hero_id" value="<?= $heroId ?>" id="pin_hero_<?= $heroId ?>" class="form-check-input" <?= $pinnedHeroId === $heroId ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="pin_hero_<?= $heroId ?>">Pin</label>
                                        </div>
                                    </td>
                                    <td style="width:110px;">
                                        <input type="number" name="heroes[<?= $heroId ?>][sort_order]" class="form-control form-control-sm" value="<?= e((string) ($hero['sort_order'] ?? 0)) ?>">
                                    </td>
                                    <td class="text-end">
                                        <button type="submit" name="delete_hero_id" value="<?= $heroId ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this homepage hero item?');">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($heroRows): ?>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="submit" class="btn btn-success">Save Hero Items</button>
                            <button type="submit" name="pinned_hero_id" value="0" class="btn btn-outline-secondary">Unpin Hero Override</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h5 mb-2">Section Layout</h2>
                <p class="admin-help-text mb-3">Controls major sections on the homepage. Header and footer are fixed.</p>
                <form method="post" action="<?= e(base_url('admin/homepage_manager_save.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_sections">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                            <tr><th>Section</th><th>Show</th><th>Order</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($sections as $key => $section): ?>
                                <tr>
                                    <td><?= e((string) $section['label']) ?></td>
                                    <td><input type="checkbox" class="form-check-input" name="sections[<?= e($key) ?>][enabled]" value="1" <?= !empty($section['enabled']) ? 'checked' : '' ?>></td>
                                    <td><input type="number" class="form-control form-control-sm" name="sections[<?= e($key) ?>][sort_order]" value="<?= e((string) ($section['sort_order'] ?? 0)) ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-success mt-3">Save Section Layout</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h5 mb-2">CTA Banner</h2>
                <p class="text-secondary small mb-3">Text is intentionally compact so it stays inside the banner.</p>
                <form method="post" action="<?= e(base_url('admin/homepage_manager_save.php')) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_cta">
                    <div class="mb-2">
                        <label class="form-label">Heading</label>
                        <input type="text" name="homepage_new_cta_heading" class="form-control" value="<?= e((string) $settings['homepage_new_cta_heading']) ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Body</label>
                        <textarea name="homepage_new_cta_body" rows="2" class="form-control"><?= e((string) $settings['homepage_new_cta_body']) ?></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Button Label</label>
                            <input type="text" name="homepage_new_cta_button_label" class="form-control" value="<?= e((string) $settings['homepage_new_cta_button_label']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Button URL</label>
                            <input type="text" name="homepage_new_cta_button_url" class="form-control" value="<?= e((string) $settings['homepage_new_cta_button_url']) ?>">
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">CTA Image</label>
                        <input type="file" name="cta_image" class="form-control" accept="image/*">
                        <div class="form-text">Current: <code><?= e((string) $settings['homepage_new_cta_image']) ?></code></div>
                    </div>
                    <button type="submit" class="btn btn-success mt-3">Save CTA Banner</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-2">Homepage SEO</h2>
                <form method="post" action="<?= e(base_url('admin/homepage_manager_save.php')) ?>" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_seo">
                    <div class="col-md-6">
                        <label class="form-label">SEO Title</label>
                        <input type="text" name="homepage_new_seo_title" class="form-control" value="<?= e((string) $settings['homepage_new_seo_title']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">OG Title</label>
                        <input type="text" name="homepage_new_og_title" class="form-control" value="<?= e((string) $settings['homepage_new_og_title']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Meta Description</label>
                        <textarea name="homepage_new_seo_description" rows="3" class="form-control"><?= e((string) $settings['homepage_new_seo_description']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">OG Description</label>
                        <textarea name="homepage_new_og_description" rows="3" class="form-control"><?= e((string) $settings['homepage_new_og_description']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">OG Image Override</label>
                        <input type="file" name="homepage_og_image" class="form-control" accept="image/*">
                        <div class="form-text">Current: <code><?= e((string) $settings['homepage_new_og_image']) ?></code></div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">Save Homepage SEO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
