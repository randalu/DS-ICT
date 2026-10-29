<?php
declare(strict_types=1);

$adminTitle = 'Media Library';
$adminPage = 'media_library';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

ensure_social_marketing_schema();

$page = max(1, (int) get_input('page', 1));
$perPage = 24;
$offset = paginate_offset($page, $perPage);
$search = trim((string) get_input('q', ''));
$destinationFilter = (int) get_input('destination_id', 0);

$hasDestinations = db_table_exists('destinations');
$hasMediaDestination = db_column_exists('media_assets', 'destination_id');
$destinations = $hasDestinations ? fetch_all('SELECT id, name FROM destinations ORDER BY name ASC') : [];
if (!$hasDestinations || !$hasMediaDestination) {
    $destinationFilter = 0;
}

$whereParts = [];
$params = [];
if ($search !== '') {
    $whereParts[] = '(m.file_name LIKE :search_file OR m.alt_text LIKE :search_alt OR m.tags LIKE :search_tags OR m.seo_title LIKE :search_seo)';
    $params['search_file'] = '%' . $search . '%';
    $params['search_alt'] = '%' . $search . '%';
    $params['search_tags'] = '%' . $search . '%';
    $params['search_seo'] = '%' . $search . '%';
}
if ($destinationFilter > 0 && $hasMediaDestination) {
    $whereParts[] = 'm.destination_id = :destination_filter';
    $params['destination_filter'] = $destinationFilter;
}
$where = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$destinationSelect = $hasMediaDestination ? 'm.destination_id' : 'NULL AS destination_id';
$destinationNameSelect = ($hasDestinations && $hasMediaDestination) ? 'd.name AS destination_name' : 'NULL AS destination_name';
$destinationJoin = ($hasDestinations && $hasMediaDestination) ? 'LEFT JOIN destinations d ON d.id = m.destination_id' : '';

$total = (int) (fetch_one("SELECT COUNT(*) AS total FROM media_assets m {$where}", $params)['total'] ?? 0);
$items = fetch_all(
    "SELECT m.id, m.file_path, m.file_name, m.mime_type, m.file_size,
            m.alt_text, m.seo_title, m.seo_description, m.tags,
            {$destinationSelect}, m.created_at,
            {$destinationNameSelect}
     FROM media_assets m
     {$destinationJoin}
     {$where}
     ORDER BY m.id DESC
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);
$pages = max(1, (int) ceil($total / max(1, $perPage)));

$paginationQuery = static function (int $targetPage) use ($search, $destinationFilter): string {
    $parts = ['page=' . $targetPage];
    if ($search !== '') {
        $parts[] = 'q=' . urlencode($search);
    }
    if ($destinationFilter > 0) {
        $parts[] = 'destination_id=' . $destinationFilter;
    }
    return '?' . implode('&', $parts);
};

require __DIR__ . '/_top.php';
?>

<div class="card card-soft mb-3">
    <div class="card-body">
        <h2 class="h5 mb-3">Bulk Upload Media</h2>
        <form method="post" action="<?= e(base_url('admin/media_asset_save.php')) ?>" enctype="multipart/form-data" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="mode" value="upload">
            <div class="col-md-9">
                <label class="form-label">Choose files</label>
                <input type="file" name="asset_files[]" class="form-control" accept="image/*,video/*" multiple required>
                <small class="text-secondary d-block mt-1">
                    Select multiple files to upload at once. Add alt text, SEO metadata, tags and destination below after upload.
                </small>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-success">Upload Files</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <form class="row g-2 align-items-center" method="get">
                <div class="col-auto">
                    <input type="text" name="q" class="form-control" placeholder="Search media..." value="<?= e($search) ?>">
                </div>
                <?php if ($destinations && $hasMediaDestination): ?>
                    <div class="col-auto">
                        <select name="destination_id" class="form-select">
                            <option value="">All destinations</option>
                            <?php foreach ($destinations as $destination): ?>
                                <?php $dId = (int) ($destination['id'] ?? 0); ?>
                                <option value="<?= $dId ?>" <?= $destinationFilter === $dId ? 'selected' : '' ?>>
                                    <?= e((string) ($destination['name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="col-auto">
                    <button class="btn btn-outline-dark">Filter</button>
                    <?php if ($search !== '' || $destinationFilter > 0): ?>
                        <a href="<?= e(base_url('admin/media_library.php')) ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
            <div class="small text-secondary">Total assets: <?= number_format($total) ?></div>
        </div>

        <?php if (!$items): ?>
            <div class="text-center text-secondary py-5">No media assets found.</div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($items as $item): ?>
                    <?php
                    $assetId = (int) ($item['id'] ?? 0);
                    $assetUrl = stored_media_url((string) ($item['file_path'] ?? ''));
                    $mimeType = strtolower((string) ($item['mime_type'] ?? ''));
                    $fileName = (string) ($item['file_name'] ?? '');
                    $assetDestinationId = (int) ($item['destination_id'] ?? 0);
                    $assetDestinationName = trim((string) ($item['destination_name'] ?? ''));
                    $isImage = str_starts_with($mimeType, 'image/');
                    $isVideo = str_starts_with($mimeType, 'video/');
                    $collapseId = 'metaCollapse_' . $assetId;
                    ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="border rounded h-100 d-flex flex-column">
                            <button type="button"
                                    class="btn p-0 border-0 bg-light"
                                    data-bs-toggle="modal"
                                    data-bs-target="#mediaPreviewModal"
                                    data-asset-url="<?= e($assetUrl) ?>"
                                    data-asset-mime="<?= e($mimeType) ?>"
                                    data-asset-name="<?= e($fileName) ?>"
                                    style="display:block;width:100%;overflow:hidden;border-top-left-radius:.25rem;border-top-right-radius:.25rem;">
                                <?php if ($isImage): ?>
                                    <img src="<?= e($assetUrl) ?>"
                                         alt="<?= e((string) ($item['alt_text'] ?? '')) ?>"
                                         style="width:100%;height:180px;object-fit:cover;display:block;">
                                <?php elseif ($isVideo): ?>
                                    <video src="<?= e($assetUrl) ?>"
                                           muted preload="metadata"
                                           style="width:100%;height:180px;object-fit:cover;display:block;background:#000;"></video>
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center text-secondary"
                                         style="width:100%;height:180px;">
                                        <span class="badge bg-secondary">File</span>
                                    </div>
                                <?php endif; ?>
                            </button>

                            <div class="p-2 small flex-grow-1 d-flex flex-column">
                                <div class="fw-semibold text-truncate" title="<?= e($fileName) ?>"><?= e($fileName) ?></div>
                                <div class="text-secondary small">
                                    <?= e($mimeType) ?> &middot; <?= e(format_file_size((int) ($item['file_size'] ?? 0))) ?>
                                </div>
                                <?php if ($assetDestinationName !== ''): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-info-subtle text-dark border">📍 <?= e($assetDestinationName) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="text-secondary small mt-1"><?= e((string) ($item['created_at'] ?? '')) ?></div>

                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-dark flex-grow-1"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#<?= e($collapseId) ?>"
                                            aria-expanded="false">
                                        Edit metadata
                                    </button>
                                </div>

                                <div class="collapse mt-2" id="<?= e($collapseId) ?>">
                                    <form method="post" action="<?= e(base_url('admin/media_asset_save.php')) ?>" class="d-grid gap-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="mode" value="update">
                                        <input type="hidden" name="id" value="<?= $assetId ?>">

                                        <?php if ($destinations && $hasMediaDestination): ?>
                                            <select name="destination_id" class="form-select form-select-sm">
                                                <option value="">No destination</option>
                                                <?php foreach ($destinations as $destination): ?>
                                                    <?php $dId = (int) ($destination['id'] ?? 0); ?>
                                                    <option value="<?= $dId ?>" <?= $assetDestinationId === $dId ? 'selected' : '' ?>>
                                                        <?= e((string) ($destination['name'] ?? '')) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>

                                        <input type="text" name="alt_text" class="form-control form-control-sm"
                                               placeholder="Alt text" value="<?= e((string) ($item['alt_text'] ?? '')) ?>">
                                        <input type="text" name="seo_title" class="form-control form-control-sm"
                                               placeholder="SEO title" value="<?= e((string) ($item['seo_title'] ?? '')) ?>">
                                        <input type="text" name="seo_description" class="form-control form-control-sm"
                                               placeholder="SEO description" value="<?= e((string) ($item['seo_description'] ?? '')) ?>">
                                        <input type="text" name="tags" class="form-control form-control-sm"
                                               placeholder="tags,comma,separated" value="<?= e((string) ($item['tags'] ?? '')) ?>">

                                        <button type="submit" class="btn btn-sm btn-success">Save Metadata</button>
                                    </form>
                                </div>

                                <div class="mt-2 d-flex gap-1">
                                    <a href="<?= e(base_url('admin/media_asset_download.php?id=' . $assetId)) ?>"
                                       class="btn btn-sm btn-outline-secondary flex-grow-1">Download</a>
                                    <a href="<?= e($assetUrl) ?>" target="_blank" rel="noopener"
                                       class="btn btn-sm btn-outline-dark flex-grow-1">Open</a>
                                    <form method="post" action="<?= e(base_url('admin/media_asset_delete.php')) ?>"
                                          onsubmit="return confirm('Delete this media asset?');" class="flex-grow-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $assetId ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($pages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination mb-0">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item<?= $i === $page ? ' active' : '' ?>">
                            <a class="page-link" href="<?= e($paginationQuery($i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="mediaPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaPreviewTitle">Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" id="mediaPreviewBody"></div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('mediaPreviewModal');
    if (!modal) return;
    var title = document.getElementById('mediaPreviewTitle');
    var body = document.getElementById('mediaPreviewBody');

    modal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) return;
        var url = trigger.getAttribute('data-asset-url') || '';
        var mime = (trigger.getAttribute('data-asset-mime') || '').toLowerCase();
        var name = trigger.getAttribute('data-asset-name') || 'Preview';

        title.textContent = name;
        body.innerHTML = '';

        if (mime.indexOf('image/') === 0) {
            var img = document.createElement('img');
            img.src = url;
            img.alt = name;
            img.style.maxWidth = '100%';
            img.style.maxHeight = '75vh';
            body.appendChild(img);
        } else if (mime.indexOf('video/') === 0) {
            var video = document.createElement('video');
            video.src = url;
            video.controls = true;
            video.style.maxWidth = '100%';
            video.style.maxHeight = '75vh';
            body.appendChild(video);
        } else {
            var link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = 'Open file';
            body.appendChild(link);
        }
    });

    modal.addEventListener('hidden.bs.modal', function () {
        body.innerHTML = '';
    });
}());
</script>

<?php require __DIR__ . '/_bottom.php'; ?>
