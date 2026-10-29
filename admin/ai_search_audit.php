<?php
declare(strict_types=1);

$adminTitle = 'AI/Search Audit';
$adminPage = 'ai_search_audit';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai_search_audit.php';

admin_require_auth();

$targets = ai_search_content_target_status();
$issues = ai_search_quality_issues(5);
$missingImageQueue = ai_search_missing_image_queue(120);
$placeholderImageQueue = ai_search_placeholder_image_queue(120);
$nextBlogPosts = ai_search_next_blog_posts(20);

require __DIR__ . '/_top.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h4 class="card-title card-title-dash mb-1">AI/Search Audit</h4>
        <p class="text-muted mb-0">Coverage, metadata, content depth, media, and internal links for the current SEO content pack.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(base_url('admin/blog_posts.php')) ?>" class="btn btn-outline-primary btn-sm">Review Blog Queue</a>
        <a href="<?= e(base_url('sitemap.xml')) ?>" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm">Open Sitemap</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($targets as $target): ?>
        <div class="col-md-6 col-xl-4">
            <a href="<?= e((string) $target['href']) ?>" class="text-decoration-none">
                <div class="card card-soft h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <p class="text-muted small mb-1"><?= e((string) $target['label']) ?></p>
                                <h3 class="mb-0"><?= (int) $target['value'] ?><span class="text-muted fs-6">/<?= (int) $target['target'] ?></span></h3>
                            </div>
                            <span class="badge <?= !empty($target['complete']) ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= !empty($target['complete']) ? 'Ready' : 'Needs work' ?>
                            </span>
                        </div>
                        <div class="progress mt-3" style="height:6px;">
                            <div class="progress-bar <?= !empty($target['complete']) ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= (int) $target['percent'] ?>%;" aria-valuenow="<?= (int) $target['percent'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="card card-soft mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="card-title card-title-dash mb-1">Quality Issues</h4>
                <p class="text-muted mb-0">Use this as the editorial checklist before image replacement and final publishing QA.</p>
            </div>
            <span class="badge <?= $issues ? 'bg-warning text-dark' : 'bg-success' ?>"><?= count($issues) ?> checks need review</span>
        </div>

        <?php if (!$issues): ?>
            <div class="alert alert-success mb-0">No AI/search content issues found by the current audit rules.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Severity</th>
                        <th>Area</th>
                        <th>Issue</th>
                        <th>Count</th>
                        <th>Samples</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($issues as $issue): ?>
                        <tr>
                            <td>
                                <?php
                                $severity = (string) ($issue['severity'] ?? 'Medium');
                                $badge = $severity === 'High' ? 'bg-danger' : ($severity === 'Low' ? 'bg-secondary' : 'bg-warning text-dark');
                                ?>
                                <span class="badge <?= e($badge) ?>"><?= e($severity) ?></span>
                            </td>
                            <td><?= e((string) ($issue['area'] ?? 'Content')) ?></td>
                            <td><?= e((string) ($issue['issue'] ?? 'Review needed')) ?></td>
                            <td><?= (int) ($issue['count'] ?? 0) ?></td>
                            <td>
                                <?php if (empty($issue['samples'])): ?>
                                    <span class="text-muted small">No sample rows.</span>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-1">
                                        <?php foreach ($issue['samples'] as $sample): ?>
                                            <a href="<?= e(ai_search_issue_edit_url($issue, $sample)) ?>" class="small"><?= e((string) ($sample['title'] ?? $sample['slug'] ?? 'Untitled')) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-soft mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="card-title card-title-dash mb-1">Missing Media Queue</h4>
                <p class="text-muted mb-0">Published records still missing hero or social media fields.</p>
            </div>
            <span class="badge <?= $missingImageQueue ? 'bg-warning text-dark' : 'bg-success' ?>">
                <?= array_sum(array_map(static fn(array $group): int => (int) ($group['count'] ?? 0), $missingImageQueue)) ?> records
            </span>
        </div>

        <?php if (!$missingImageQueue): ?>
            <div class="alert alert-success mb-0">No published records are missing hero or social images.</div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($missingImageQueue as $group): ?>
                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <h5 class="h6 mb-0"><?= e((string) $group['area']) ?></h5>
                                <span class="badge bg-secondary"><?= (int) $group['count'] ?></span>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <?php foreach (($group['records'] ?? []) as $record): ?>
                                    <?php $editUrl = base_url((string) $group['edit_path'] . '?id=' . (int) $record['id']); ?>
                                    <a href="<?= e($editUrl) ?>" class="small"><?= e((string) ($record['title'] ?? $record['slug'] ?? 'Untitled')) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-soft mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="card-title card-title-dash mb-1">Placeholder Replacement Queue</h4>
                <p class="text-muted mb-0">Records using temporary placeholder images. Replace these with final destination, tour, and article images.</p>
            </div>
            <span class="badge <?= $placeholderImageQueue ? 'bg-warning text-dark' : 'bg-success' ?>">
                <?= array_sum(array_map(static fn(array $group): int => (int) ($group['count'] ?? 0), $placeholderImageQueue)) ?> records
            </span>
        </div>

        <?php if (!$placeholderImageQueue): ?>
            <div class="alert alert-success mb-0">No records are using the temporary placeholder image set.</div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($placeholderImageQueue as $group): ?>
                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3 h-100 bg-white">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <h5 class="h6 mb-0"><?= e((string) $group['area']) ?></h5>
                                <span class="badge bg-warning text-dark"><?= (int) $group['count'] ?></span>
                            </div>
                            <div class="d-flex flex-column gap-1">
                                <?php foreach (($group['records'] ?? []) as $record): ?>
                                    <?php $editUrl = base_url((string) $group['edit_path'] . '?id=' . (int) $record['id']); ?>
                                    <a href="<?= e($editUrl) ?>" class="small"><?= e((string) ($record['title'] ?? $record['slug'] ?? 'Untitled')) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="card-title card-title-dash mb-1">Scheduled Blog Queue</h4>
                <p class="text-muted mb-0">Preview posts before publication; previews are authenticated and noindex.</p>
            </div>
            <a href="<?= e(base_url('admin/blog_post_form.php')) ?>" class="btn btn-success btn-sm">New Post</a>
        </div>

        <?php if (!$nextBlogPosts): ?>
            <div class="alert alert-warning mb-0">No future scheduled blog posts found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Publish Date</th>
                        <th>Title</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($nextBlogPosts as $post): ?>
                        <?php $publicUrl = base_url('blog/' . $post['slug']); ?>
                        <tr>
                            <td class="text-secondary small"><?= e(date('d M Y H:i', strtotime((string) $post['published_at']))) ?></td>
                            <td><?= e((string) $post['title']) ?></td>
                            <td class="text-end">
                                <a href="<?= e($publicUrl . '?preview=1') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Preview</a>
                                <a href="<?= e(base_url('admin/blog_post_form.php?id=' . (int) $post['id'])) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
