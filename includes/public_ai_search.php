<?php
declare(strict_types=1);
require_once __DIR__ . '/ai_search.php';

const ICT_PUBLIC_AI_MAX_CHARS = 1000;
const ICT_PUBLIC_AI_MAX_BODY = 16384;
const ICT_PUBLIC_AI_WINDOW = 900;
const ICT_PUBLIC_AI_REQUESTS = 5;
const ICT_PUBLIC_AI_NOTICE = 'openrouter-search-v1';

/** Read presence only: never return a key in page state or diagnostics. */
function ict_public_ai_available(): bool
{
    try {
        if (!db_table_exists('site_settings') || !db_table_exists('search_index')) { return false; }
        $row = fetch_one('SELECT CHAR_LENGTH(TRIM(setting_value)) > 0 AS present FROM site_settings WHERE setting_key = :key', ['key' => 'ai_api_key']);
        return (int) ($row['present'] ?? 0) === 1 && function_exists('curl_init');
    } catch (Throwable) { return false; }
}

/** Atomic IP reservation in the existing table, without DDL, cleanup or raw text. */
function ict_public_ai_reserve_ip(string $ip, int $now): string
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) { return 'unavailable'; }
    $bucket = 'ai:' . substr(hash('sha256', (string) inet_pton($ip)), 0, 40);
    $lock = 'ict-ai:' . substr(hash('sha256', $bucket), 0, 40);
    $locked = false;
    try {
        if (!db_table_exists('form_submission_limits')) { return 'unavailable'; }
        foreach (['form_key', 'ip_address', 'created_at'] as $column) {
            if (!db_column_exists('form_submission_limits', $column)) { return 'unavailable'; }
        }
        $locked = (int) (fetch_one('SELECT GET_LOCK(:name, 1) AS acquired', ['name' => $lock])['acquired'] ?? 0) === 1;
        if (!$locked) { return 'unavailable'; }
        // Server UTC conversion is unnecessary: timestamps use the same existing
        // DB clock for window selection and INSERT, independent of PHP timezone.
        $row = fetch_one('SELECT COUNT(*) AS total FROM form_submission_limits WHERE form_key = :key AND ip_address = :bucket AND created_at > DATE_SUB(NOW(), INTERVAL 900 SECOND)', ['key' => 'public_ai_search', 'bucket' => $bucket]);
        if (!$row || !isset($row['total'])) { return 'unavailable'; }
        if ((int) ($row['total'] ?? 0) >= ICT_PUBLIC_AI_REQUESTS) { return 'limited'; }
        if (!execute_query('INSERT INTO form_submission_limits (form_key, ip_address) VALUES (:key, :bucket)', ['key' => 'public_ai_search', 'bucket' => $bucket])) { return 'unavailable'; }
        return 'ok';
    } catch (Throwable) { return 'unavailable'; }
    finally {
        if ($locked) { try { fetch_one('SELECT RELEASE_LOCK(:name)', ['name' => $lock]); } catch (Throwable) {} }
    }
}

/** Session lock remains held during reservation/search; failed providers consume quota. */
function ict_public_ai_reserve(array &$session, int $now, callable $reserveIp): string
{
    $bucket = $session['_ict_public_ai_limit'] ?? null;
    if (!is_array($bucket) || !is_int($bucket['start'] ?? null) || !is_int($bucket['count'] ?? null)
        || $bucket['start'] > $now || $bucket['start'] <= $now - ICT_PUBLIC_AI_WINDOW || $bucket['count'] < 0) {
        $bucket = ['start' => $now, 'count' => 0];
    }
    if ($bucket['count'] >= ICT_PUBLIC_AI_REQUESTS) { return 'limited'; }
    $result = $reserveIp(request_ip_address(), $now);
    if ($result !== 'ok') { return $result === 'limited' ? 'limited' : 'unavailable'; }
    $session['_ict_public_ai_limit'] = ['start' => $bucket['start'], 'count' => $bucket['count'] + 1];
    return 'ok';
}

function ict_public_ai_query(mixed $input): string
{
    if (!is_string($input) || !mb_check_encoding($input, 'UTF-8') || mb_strlen($input) > ICT_PUBLIC_AI_MAX_CHARS) {
        throw new InvalidArgumentException('Use a search of 1–1,000 characters.');
    }
    $query = trim($input);
    if ($query === '' || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $query)) {
        throw new InvalidArgumentException('Enter a trip search without control characters.');
    }
    return $query;
}

/** Dependencies are trusted PHP callables for tests, never request parameters. */
function ict_public_ai_request(array $server, array $get, array $post, array &$session, array $dependencies = []): array
{
    $state = ['enabled' => false, 'query' => '', 'results' => [], 'searched' => false, 'error' => '', 'status' => 200];
    $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['GET', 'POST'], true)) { return array_replace($state, ['status' => 405, 'error' => 'Use the search page or its form.']); }
    if ($method === 'GET') {
        // Legacy links may prefill a bounded local field; GET never sends text.
        $prefill = $get['q'] ?? '';
        if (is_string($prefill) && mb_check_encoding($prefill, 'UTF-8')) { $state['query'] = mb_substr($prefill, 0, ICT_PUBLIC_AI_MAX_CHARS); }
    }
    $settings = ict_privacy_settings();
    if (!$settings['configured'] || !$settings['public_ai_search_enabled']) { return $state; }
    try { $available = ($dependencies['available'] ?? 'ict_public_ai_available')(); }
    catch (Throwable) { $available = false; }
    if ($available !== true || session_status() !== PHP_SESSION_ACTIVE) { return $state; }
    $state['enabled'] = true;
    if ($method !== 'POST') { return $state; }
    if ((int) ($server['CONTENT_LENGTH'] ?? 0) > ICT_PUBLIC_AI_MAX_BODY) {
        return array_replace($state, ['status' => 413, 'error' => 'This search request is too large.']);
    }
    if (!csrf_verify(is_string($post['_token'] ?? null) ? $post['_token'] : null)) {
        return array_replace($state, ['status' => 419, 'error' => 'Your search session expired. Reload and try again.']);
    }
    if (($post['search_action'] ?? null) !== 'search_with_ai' || ($post['ai_notice'] ?? null) !== ICT_PUBLIC_AI_NOTICE) {
        return array_replace($state, ['status' => 422, 'error' => 'Review the search notice and use Search with AI.']);
    }
    try { $state['query'] = ict_public_ai_query($post['q'] ?? null); }
    catch (InvalidArgumentException $exception) { return array_replace($state, ['status' => 422, 'error' => $exception->getMessage()]); }
    try {
        $quota = ict_public_ai_reserve($session, time(), $dependencies['reserve_ip'] ?? 'ict_public_ai_reserve_ip');
        if ($quota !== 'ok') {
            return array_replace($state, ['status' => $quota === 'limited' ? 429 : 503, 'error' => $quota === 'limited' ? 'Too many AI searches. Wait 15 minutes, or use the links below.' : 'AI search is temporarily unavailable. Use the links below.']);
        }
        $results = ($dependencies['search'] ?? 'ict_public_ai_search')($state['query']);
        if (!is_array($results)) { throw new RuntimeException('Search unavailable'); }
        $state['results'] = $results;
        $state['searched'] = true;
    } catch (Throwable) { $state['status'] = 503; $state['error'] = 'AI search is temporarily unavailable. Use the links below.'; }
    return $state;
}

/** Public transport has bounded response/timeouts; general admin/CLI transport unchanged. */
function ict_public_ai_embedding(string $query): ?array
{
    $keyRow = fetch_one('SELECT setting_value FROM site_settings WHERE setting_key = :key', ['key' => 'ai_api_key']);
    $key = trim((string) ($keyRow['setting_value'] ?? ''));
    if ($key === '' || !function_exists('curl_init')) { return null; }
    $modelRow = fetch_one('SELECT setting_value FROM site_settings WHERE setting_key = :key', ['key' => 'ai_embedding_model']);
    $model = trim((string) ($modelRow['setting_value'] ?? 'openai/text-embedding-3-small'));
    $ch = curl_init('https://openrouter.ai/api/v1/embeddings');
    if ($ch === false) { return null; }
    $raw = '';
    $options = [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['model' => $model, 'input' => $query], JSON_THROW_ON_ERROR),
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$raw): int {
            if (strlen($raw) + strlen($chunk) > 262144) { return 0; }
            $raw .= $chunk; return strlen($chunk);
        }];
    $ca = __DIR__ . '/../cacert.pem';
    if (is_file($ca)) { $options[CURLOPT_CAINFO] = $ca; }
    try {
        curl_setopt_array($ch, $options); $success = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    } finally { curl_close($ch); }
    if ($success === false || $code !== 200) { return null; }
    return ict_public_ai_decode_embedding($raw);
}

function ict_public_ai_decode_embedding(string $raw): ?array
{
    if (strlen($raw) > 262144) { return null; }
    try { $body = json_decode($raw, true, 12, JSON_THROW_ON_ERROR); } catch (Throwable) { return null; }
    $vector = $body['data'][0]['embedding'] ?? null;
    if (!is_array($vector) || !array_is_list($vector) || count($vector) < 1 || count($vector) > 8192) { return null; }
    foreach ($vector as $value) { if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value)) { return null; } }
    return $vector;
}

function ict_public_ai_search(string $query): array
{
    return ai_search_query($query, 10, static function (string $text): array {
        $vector = ict_public_ai_embedding($text);
        if ($vector === null) { throw new RuntimeException('Search unavailable'); }
        return $vector;
    });
}

function ict_public_ai_result_url(mixed $path): string
{
    if (!is_string($path) || !preg_match('~\A/?[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*\z~', $path)) { return ''; }
    return base_url(ltrim($path, '/'));
}
