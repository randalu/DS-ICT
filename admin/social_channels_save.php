<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/social_channels.php');
}

guard_post_size();
require_csrf();
ensure_social_marketing_schema();

$platformOptions = social_media_platform_options();
$existingConnections = fetch_all('SELECT platform, access_token, refresh_token FROM social_platform_connections');
$existingConnectionMap = [];
foreach ($existingConnections as $connection) {
    $existingConnectionMap[strtolower((string) ($connection['platform'] ?? ''))] = $connection;
}

$errors = [];
$rows = [];
foreach ($platformOptions as $platformKey => $platformLabel) {
    $prefix = 'platform_' . $platformKey . '_';

    $authMode = strtolower(trim((string) post_input($prefix . 'auth_mode', 'manual')));
    if (!in_array($authMode, ['manual', 'api'], true)) {
        $authMode = 'manual';
    }

    $accountLabel = trim((string) post_input($prefix . 'account_label', ''));
    $accessToken = trim((string) post_input($prefix . 'access_token', ''));
    $refreshToken = trim((string) post_input($prefix . 'refresh_token', ''));
    $existingConnection = $existingConnectionMap[$platformKey] ?? [];

    if (mb_strlen($accountLabel) > 150) {
        $errors[] = $platformLabel . ' account label must be 150 characters or fewer.';
    }

    if ($accessToken === '') {
        $accessToken = trim((string) ($existingConnection['access_token'] ?? ''));
    }
    if ($refreshToken === '') {
        $refreshToken = trim((string) ($existingConnection['refresh_token'] ?? ''));
    }
    $expiresAtRaw = trim((string) post_input($prefix . 'token_expires_at', ''));
    $expiresAt = null;
    if ($expiresAtRaw !== '') {
        $ts = strtotime($expiresAtRaw);
        if ($ts !== false) {
            $expiresAt = date('Y-m-d H:i:s', $ts);
        } else {
            $errors[] = $platformLabel . ' token expiry date is invalid.';
        }
    }

    $isActive = post_input($prefix . 'is_active') ? 1 : 0;

    $rows[] = [
        'platform' => $platformKey,
        'auth_mode' => $authMode,
        'account_label' => $accountLabel !== '' ? $accountLabel : null,
        'access_token' => $accessToken !== '' ? $accessToken : null,
        'refresh_token' => $refreshToken !== '' ? $refreshToken : null,
        'token_expires_at' => $expiresAt,
        'is_active' => $isActive,
    ];
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect('admin/social_channels.php');
}

try {
    db()->beginTransaction();

    foreach ($rows as $row) {
        execute_query(
            "INSERT INTO social_platform_connections
            (platform, auth_mode, account_label, access_token, refresh_token, token_expires_at, is_active)
            VALUES
            (:platform, :auth_mode, :account_label, :access_token, :refresh_token, :token_expires_at, :is_active)
            ON DUPLICATE KEY UPDATE
                auth_mode = VALUES(auth_mode),
                account_label = VALUES(account_label),
                access_token = VALUES(access_token),
                refresh_token = VALUES(refresh_token),
                token_expires_at = VALUES(token_expires_at),
                is_active = VALUES(is_active)",
            $row
        );
    }

    db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash_set('error', 'Unable to save social channel settings. Please review the inputs and try again.');
    redirect('admin/social_channels.php');
}

flash_set('success', 'Social channel settings saved.');
redirect('admin/social_channels.php');
