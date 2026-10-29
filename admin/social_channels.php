<?php
declare(strict_types=1);

$adminTitle = 'Social Channels';
$adminPage = 'social_channels';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

ensure_social_marketing_schema();

$platformOptions = social_media_platform_options();

$connections = fetch_all('SELECT * FROM social_platform_connections ORDER BY platform ASC');
$connectionMap = [];
foreach ($connections as $connection) {
    $connectionMap[strtolower((string) ($connection['platform'] ?? ''))] = $connection;
}

$recentPublishLogs = fetch_all(
    'SELECT post_id, platform, publish_status, attempted_at, response_json
     FROM social_publish_logs
     ORDER BY id DESC
     LIMIT 30'
);

$publishLogSummary = static function (array $log): string {
    $payload = json_decode((string) ($log['response_json'] ?? ''), true);
    if (!is_array($payload)) {
        return 'Details hidden';
    }

    if (!empty($payload['blog_post_id'])) {
        return 'Blog draft #' . (int) $payload['blog_post_id'];
    }

    if (!empty($payload['target_platform'])) {
        return 'Publish package prepared for ' . ucfirst((string) $payload['target_platform']);
    }

    return 'Publish details hidden';
};

require __DIR__ . '/_top.php';
?>

<div class="card card-soft mb-3">
    <div class="card-body">
        <h2 class="h5 mb-2">Platform Connections</h2>
        <p class="small text-secondary mb-3">Set each channel to <strong>manual</strong> or <strong>api</strong>. API mode is prepared for connector integration; manual mode logs publish packages for copy/share workflows.</p>

        <form method="post" action="<?= e(base_url('admin/social_channels_save.php')) ?>">
            <?= csrf_field() ?>

            <div class="table-responsive mb-3">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th>Mode</th>
                            <th>Account Label</th>
                            <th>Access Token</th>
                            <th>Refresh Token</th>
                            <th>Expires At</th>
                            <th>Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($platformOptions as $platformKey => $platformLabel): ?>
                            <?php
                            $connection = $connectionMap[$platformKey] ?? [];
                            $expiresAt = '';
                            if (!empty($connection['token_expires_at'])) {
                                $ts = strtotime((string) $connection['token_expires_at']);
                                $expiresAt = $ts ? date('Y-m-d\TH:i', $ts) : '';
                            }
                            ?>
                            <tr>
                                <td class="fw-semibold"><?= e($platformLabel) ?></td>
                                <td>
                                    <?php $mode = strtolower((string) ($connection['auth_mode'] ?? 'manual')); ?>
                                    <select name="platform_<?= e($platformKey) ?>_auth_mode" class="form-select form-select-sm">
                                        <option value="manual" <?= $mode === 'manual' ? 'selected' : '' ?>>manual</option>
                                        <option value="api" <?= $mode === 'api' ? 'selected' : '' ?>>api</option>
                                    </select>
                                </td>
                                <td><input type="text" name="platform_<?= e($platformKey) ?>_account_label" class="form-control form-control-sm" maxlength="150" value="<?= e((string) ($connection['account_label'] ?? '')) ?>"></td>
                                <td>
                                    <input type="password" name="platform_<?= e($platformKey) ?>_access_token" class="form-control form-control-sm" value="" autocomplete="off" placeholder="<?= !empty($connection['access_token']) ? 'Saved - leave blank to keep' : 'Paste token' ?>">
                                </td>
                                <td>
                                    <input type="password" name="platform_<?= e($platformKey) ?>_refresh_token" class="form-control form-control-sm" value="" autocomplete="off" placeholder="<?= !empty($connection['refresh_token']) ? 'Saved - leave blank to keep' : 'Paste token' ?>">
                                </td>
                                <td><input type="datetime-local" name="platform_<?= e($platformKey) ?>_token_expires_at" class="form-control form-control-sm" value="<?= e($expiresAt) ?>"></td>
                                <td><input type="checkbox" class="form-check-input" name="platform_<?= e($platformKey) ?>_is_active" value="1" <?= (int) ($connection['is_active'] ?? 0) === 1 ? 'checked' : '' ?>></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-success">Save Channel Settings</button>
            <a href="<?= e(base_url('admin/social_posts.php')) ?>" class="btn btn-outline-dark ms-2">Go to Social Drafts</a>
        </form>
    </div>
</div>

<div class="card card-soft">
    <div class="card-body">
        <h2 class="h5 mb-3">Recent Publish Logs</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Post ID</th>
                        <th>Platform</th>
                        <th>Status</th>
                        <th>Response</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentPublishLogs): ?>
                        <?php foreach ($recentPublishLogs as $log): ?>
                            <tr>
                                <td><?= e((string) ($log['attempted_at'] ?? '')) ?></td>
                                <td>#<?= (int) ($log['post_id'] ?? 0) ?></td>
                                <td><?= e((string) ($log['platform'] ?? '')) ?></td>
                                <td><?= e((string) ($log['publish_status'] ?? '')) ?></td>
                                <td class="text-break small text-secondary"><?= e($publishLogSummary($log)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-secondary">No publish logs yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
