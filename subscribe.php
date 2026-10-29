<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

class SubscribeHandler
{
    public function handle(array $post, array $server): array
    {
        if (($server['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return ['redirect' => base_url('blog')];
        }

        $email = trim((string) ($post['email'] ?? ''));
        $redirectTarget = subscribe_return_path($server);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'flash_error' => 'Please enter a valid email address.',
                'redirect' => $redirectTarget
            ];
        }

        try {
            $this->ensureTable();
            $this->saveSubscriber($email);
            return [
                'flash_success' => 'You are subscribed. Thank you!',
                'redirect' => $redirectTarget
            ];
        } catch (Throwable $e) {
            return [
                'flash_error' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Subscription failed.',
                'redirect' => $redirectTarget
            ];
        }
    }

    protected function ensureTable(): void
    {
        ensure_newsletter_table();
    }

    protected function saveSubscriber(string $email): void
    {
        execute_query(
            "INSERT INTO newsletter_subscribers (email, subscribed_at) VALUES (:e, NOW())
             ON DUPLICATE KEY UPDATE subscribed_at = subscribed_at",
            ['e' => $email]
        );
    }
}

if (subscribe_is_request_target($_SERVER)) {
    guard_post_size();
    require_csrf();

    $requestIp = request_ip_address();
    if ($requestIp !== '' && form_submission_limit_exceeded('subscribe', $requestIp, 900, 5)) {
        flash_set('error', 'Too many subscription attempts. Please wait a moment and try again.');
        redirect(subscribe_return_path($_SERVER));
    }

    $handler = new SubscribeHandler();
    $result = $handler->handle($_POST, $_SERVER);

    if (isset($result['flash_error'])) {
        flash_set('error', (string) $result['flash_error']);
    } elseif (isset($result['flash_success'])) {
        flash_set('success', (string) $result['flash_success']);
    }

    if (isset($result['redirect'])) {
        redirect((string) $result['redirect']);
    }
}

function subscribe_is_request_target(array $server): bool
{
    return basename((string) ($server['SCRIPT_FILENAME'] ?? '')) === 'subscribe.php'
        || basename((string) ($server['SCRIPT_NAME'] ?? '')) === 'subscribe.php';
}

function subscribe_return_path(?array $server = null): string
{
    $server ??= $_SERVER;
    $referer = trim((string) ($server['HTTP_REFERER'] ?? ''));
    if ($referer === '' || preg_match('/[\x00-\x1F\x7F]/', $referer)) {
        return 'blog';
    }

    $parts = parse_url($referer);
    if (!is_array($parts)) {
        return 'blog';
    }

    $refererHost = strtolower((string) ($parts['host'] ?? ''));
    $appHost = strtolower((string) (parse_url(APP_URL, PHP_URL_HOST) ?: ($server['HTTP_HOST'] ?? '')));
    if ($refererHost !== '' && $appHost !== '' && $refererHost !== $appHost) {
        return 'blog';
    }

    $path = str_replace('\\', '/', (string) ($parts['path'] ?? ''));
    $query = isset($parts['query']) && is_string($parts['query']) ? '?' . $parts['query'] : '';
    if ($path === '' || str_starts_with($path, '//') || str_contains($path, '..')) {
        return 'blog';
    }

    $appPath = str_replace('\\', '/', (string) (parse_url(APP_URL, PHP_URL_PATH) ?: ''));
    $appPath = '/' . trim($appPath, '/');
    if ($appPath !== '/' && ($path === $appPath || str_starts_with($path, $appPath . '/'))) {
        $path = substr($path, strlen($appPath)) ?: '/';
    }

    return ltrim($path . $query, '/') ?: 'blog';
}

function ensure_newsletter_table(): void
{
    if (db_table_exists('newsletter_subscribers')) {
        return;
    }
    db()->exec(
        "CREATE TABLE newsletter_subscribers (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(180) NOT NULL,
            subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            source VARCHAR(60) NULL,
            UNIQUE KEY uq_newsletter_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}
