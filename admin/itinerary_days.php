<?php
declare(strict_types=1);

$adminTitle = 'Itinerary Day Plan';
$adminPage = 'itineraries';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

if (!db_table_exists('itineraries')) {
    flash_set('error', 'Itinerary records are not available in this database.');
    redirect('admin/dashboard.php');
}

$itineraryId = (int) get_input('itinerary_id', 0);
if ($itineraryId <= 0) {
    flash_set('error', 'Invalid itinerary ID.');
    redirect('admin/itineraries.php');
}

$itinerary = fetch_one(
    "SELECT id, name, days, slug, is_published FROM itineraries WHERE id = :id LIMIT 1",
    ['id' => $itineraryId]
);
if (!$itinerary) {
    flash_set('error', 'Itinerary not found.');
    redirect('admin/itineraries.php');
}

if (!db_table_exists('itinerary_days')) {
    require __DIR__ . '/_top.php';
    ?>
    <div class="alert alert-warning">
        Itinerary day schema is missing. Run the database schema or migrations before editing day sections.
    </div>
    <p><a href="<?= e(base_url('admin/itinerary_form.php?id=' . $itineraryId)) ?>" class="btn btn-outline-dark">Back to Itinerary</a></p>
    <?php
    require __DIR__ . '/_bottom.php';
    return;
}

$supportsDayImage = db_column_exists('itinerary_days', 'image_path');
$supportsHighlights = db_column_exists('itinerary_days', 'highlights_json');
$supportsDayEnd = db_column_exists('itinerary_days', 'day_end');
$dayEndExpr = $supportsDayEnd ? 'COALESCE(day_end, day_number)' : 'day_number';
$dayEndSelect = $dayEndExpr . ' AS day_end';

if (is_post()) {
    guard_post_size();
    require_csrf();

    $action = (string) post_input('action', '');
    $dayId = (int) post_input('day_id', 0);

    if ($action === 'delete') {
        if ($dayId > 0) {
            execute_query(
                'DELETE FROM itinerary_days WHERE id = :id AND itinerary_id = :itinerary_id',
                ['id' => $dayId, 'itinerary_id' => $itineraryId]
            );

            if ((int) ($itinerary['is_published'] ?? 0) === 1 && !empty($itinerary['slug'])) {
                indexnow_submit_urls([
                    base_url('itineraries/' . $itinerary['slug']),
                    base_url('itineraries'),
                    base_url('sitemap.xml'),
                ]);
            }

            flash_set('success', 'Day section removed.');
        }
        redirect('admin/itinerary_days.php?itinerary_id=' . $itineraryId);
    }

    $dayStart = max(1, (int) post_input('day_number', 1));
    $dayEnd = max($dayStart, (int) post_input('day_end', $dayStart));
    $title = trim((string) post_input('title', ''));
    $summary = trim((string) post_input('summary', ''));
    $overnightLocation = trim((string) post_input('overnight_location', ''));
    $travelTime = trim((string) post_input('travel_time', ''));
    $currentImagePath = '';

    if ($supportsDayImage && $dayId > 0) {
        $existingDay = fetch_one(
            'SELECT image_path FROM itinerary_days WHERE id = :id AND itinerary_id = :itinerary_id LIMIT 1',
            ['id' => $dayId, 'itinerary_id' => $itineraryId]
        );
        $currentImagePath = (string) ($existingDay['image_path'] ?? '');
    }

    if ($supportsDayImage && !empty($_FILES['day_image']['name'])) {
        $currentImagePath = (string) upload_image($_FILES['day_image'], 'itinerary-days');
    }

    old_input_set($_POST);

    $errors = [];
    $highlights = null;
    if ($supportsHighlights && array_key_exists('highlights_json', $_POST)) {
        require_once __DIR__ . '/../includes/seo_manager.php';
        try { $highlights = seo_validate_field('itinerary', 'day_' . max(1, $dayId) . '_highlights_json', $_POST['highlights_json']); }
        catch (InvalidArgumentException $e) { $errors[] = $e->getMessage(); }
    }
    if ($title === '') {
        $errors[] = 'Section title is required.';
    }

    if ($dayEnd < $dayStart) {
        $errors[] = 'Day end must be greater than or equal to day start.';
    }

    $overlapExpr = $supportsDayEnd ? 'COALESCE(day_end, day_number)' : 'day_number';
    $overlap = fetch_one(
        "SELECT id
         FROM itinerary_days
         WHERE itinerary_id = :itinerary_id
           AND id != :id
           AND day_number <= :new_end
           AND {$overlapExpr} >= :new_start
         LIMIT 1",
        [
            'itinerary_id' => $itineraryId,
            'id' => $dayId,
            'new_start' => $dayStart,
            'new_end' => $dayEnd,
        ]
    );

    if ($overlap) {
        $errors[] = 'This day range overlaps another day section.';
    }

    if ($errors) {
        flash_set('error', implode(' ', $errors));
        redirect('admin/itinerary_days.php?itinerary_id=' . $itineraryId . ($dayId > 0 ? '&edit_id=' . $dayId : ''));
    }

    if ($dayId > 0) {
        $params = [
            'day_number' => $dayStart,
            'title' => $title,
            'summary' => $summary !== '' ? $summary : null,
            'overnight_location' => $overnightLocation !== '' ? $overnightLocation : null,
            'travel_time' => $travelTime !== '' ? $travelTime : null,
            'id' => $dayId,
            'itinerary_id' => $itineraryId,
        ];

        $sql = '
            UPDATE itinerary_days SET
                day_number = :day_number,
                title = :title,
                summary = :summary,
                overnight_location = :overnight_location,
                travel_time = :travel_time
        ';

        if ($supportsDayEnd) {
            $sql .= ', day_end = :day_end';
            $params['day_end'] = $dayEnd;
        }

        if ($supportsDayImage) {
            $sql .= ', image_path = :image_path';
            $params['image_path'] = $currentImagePath !== '' ? $currentImagePath : null;
        }

        if ($highlights !== null) { $sql .= ', highlights_json = :highlights_json'; $params['highlights_json'] = $highlights ?: null; }
        $sql .= '
            WHERE id = :id
              AND itinerary_id = :itinerary_id
        ';

        execute_query($sql, $params);

        flash_set('success', 'Day section updated.');
    } else {
        $columns = ['itinerary_id', 'day_number', 'title', 'summary', 'overnight_location', 'travel_time'];
        $placeholders = [':itinerary_id', ':day_number', ':title', ':summary', ':overnight_location', ':travel_time'];
        $params = [
            'itinerary_id' => $itineraryId,
            'day_number' => $dayStart,
            'title' => $title,
            'summary' => $summary !== '' ? $summary : null,
            'overnight_location' => $overnightLocation !== '' ? $overnightLocation : null,
            'travel_time' => $travelTime !== '' ? $travelTime : null,
        ];

        if ($supportsDayEnd) {
            $columns[] = 'day_end';
            $placeholders[] = ':day_end';
            $params['day_end'] = $dayEnd;
        }

        if ($supportsDayImage) {
            $columns[] = 'image_path';
            $placeholders[] = ':image_path';
            $params['image_path'] = $currentImagePath !== '' ? $currentImagePath : null;
        }

        if ($highlights !== null) { $columns[] = 'highlights_json'; $placeholders[] = ':highlights_json'; $params['highlights_json'] = $highlights ?: null; }
        execute_query(
            'INSERT INTO itinerary_days (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')',
            $params
        );

        flash_set('success', 'Day section added.');
    }

    if ((int) ($itinerary['is_published'] ?? 0) === 1 && !empty($itinerary['slug'])) {
        indexnow_submit_urls([
            base_url('itineraries/' . $itinerary['slug']),
            base_url('itineraries'),
            base_url('sitemap.xml'),
        ]);
    }

    old_input_clear();
    redirect('admin/itinerary_days.php?itinerary_id=' . $itineraryId);
}

$editId = (int) get_input('edit_id', 0);
$editRecord = [];
if ($editId > 0) {
    $editRecord = fetch_one(
        'SELECT *, ' . $dayEndSelect . ' FROM itinerary_days WHERE id = :id AND itinerary_id = :itinerary_id LIMIT 1',
        ['id' => $editId, 'itinerary_id' => $itineraryId]
    ) ?: [];
}

$days = fetch_all(
    "SELECT id, day_number, {$dayEndSelect}, title, summary, overnight_location, travel_time, " . ($supportsDayImage ? 'image_path' : 'NULL AS image_path') . ", updated_at
     FROM itinerary_days
     WHERE itinerary_id = :itinerary_id
     ORDER BY day_number ASC, {$dayEndExpr} ASC",
    ['itinerary_id' => $itineraryId]
);

require __DIR__ . '/_top.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="text-secondary small">Itinerary</div>
        <div class="h5 mb-0"><?= e($itinerary['name']) ?></div>
    </div>
    <a href="<?= e(base_url('admin/itinerary_form.php?id=' . $itineraryId)) ?>" class="btn btn-outline-dark">Back to Itinerary</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-3"><?= $editId > 0 ? 'Edit Day Section' : 'Add Day Section' ?></h2>

                <form method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="day_id" value="<?= (int) $editId ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Day Start *</label>
                            <input type="number" min="1" name="day_number" class="form-control" value="<?= e((string) admin_form_value($editRecord, 'day_number', count($days) + 1)) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Day End *</label>
                            <input type="number" min="1" name="day_end" class="form-control" value="<?= e((string) admin_form_value($editRecord, 'day_end', admin_form_value($editRecord, 'day_number', count($days) + 1))) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" value="<?= e((string) admin_form_value($editRecord, 'title')) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Summary</label>
                        <textarea name="summary" rows="5" class="form-control"><?= e((string) admin_form_value($editRecord, 'summary')) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <?php if ($supportsHighlights): ?><label class="form-label">Day highlights (JSON list)</label><textarea name="highlights_json" class="form-control mb-3" rows="3" placeholder='["Temple visit", "Garden walk"]'><?= e((string) admin_form_value($editRecord, 'highlights_json')) ?></textarea><?php endif; ?>
                        <label class="form-label">Overnight Location</label>
                        <input type="text" name="overnight_location" class="form-control" value="<?= e((string) admin_form_value($editRecord, 'overnight_location')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Travel Time</label>
                        <input type="text" name="travel_time" class="form-control" value="<?= e((string) admin_form_value($editRecord, 'travel_time')) ?>" placeholder="e.g. 3.5 hours">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Day Image</label>
                        <input type="file" name="day_image" class="form-control" accept="image/*">
                        <?php if (!empty($editRecord['image_path'])): ?>
                            <div class="small mt-2 text-secondary"><?= e((string) $editRecord['image_path']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success"><?= $editId > 0 ? 'Update Section' : 'Add Section' ?></button>
                        <?php if ($editId > 0): ?>
                            <a href="<?= e(base_url('admin/itinerary_days.php?itinerary_id=' . $itineraryId)) ?>" class="btn btn-outline-dark">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-3">Day Sections</h2>

                <?php if ($days): ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>Days</th>
                                <th>Title</th>
                                <th>Image</th>
                                <th>Overnight</th>
                                <th>Travel Time</th>
                                <th class="text-end">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($days as $day): ?>
                                <?php
                                $startDay = (int) ($day['day_number'] ?? 1);
                                $endDay = (int) ($day['day_end'] ?? $startDay);
                                ?>
                                <tr>
                                    <td><?= e(day_range_label($startDay, $endDay)) ?></td>
                                    <td><?= e($day['title']) ?></td>
                                    <td>
                                        <?php if (!empty($day['image_path'])): ?>
                                            <img src="<?= e(stored_media_url((string) $day['image_path'])) ?>" alt="<?= e($day['title']) ?>" style="width:80px;height:56px;object-fit:cover;border-radius:8px;">
                                        <?php else: ?>
                                            <span class="text-secondary small">â€”</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e((string) ($day['overnight_location'] ?? '')) ?></td>
                                    <td><?= e((string) ($day['travel_time'] ?? '')) ?></td>
                                    <td class="text-end">
                                        <a href="<?= e(base_url('admin/itinerary_days.php?itinerary_id=' . $itineraryId . '&edit_id=' . (int) $day['id'])) ?>" class="btn btn-sm btn-outline-dark">Edit</a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this day section?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="day_id" value="<?= (int) $day['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php if (!empty($day['summary'])): ?>
                                    <tr>
                                        <td></td>
                                        <td colspan="5" class="text-secondary"><?= nl2br(e($day['summary'])) ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-secondary">No day sections yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/route_map_admin.php';
route_map_admin_form('itinerary', $itinerary, 'itinerary_days.php');
require __DIR__ . '/_bottom.php'; ?>
