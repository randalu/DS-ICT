<?php
declare(strict_types=1);

// Function-only module, loaded after the shared helpers. Public reads never run DDL.
const ICT_PRIVACY_SETTINGS_KEY = 'privacy_controls_json';
const ICT_PRIVACY_COOKIE = 'ict_privacy';

function ict_privacy_defaults(): array
{
    return ['schema_version' => 3, 'purpose_revision' => 1, 'preference_days' => 180,
        'first_party_enabled' => true, 'gtm_allowed' => false, 'external_reviews_allowed' => true,
        'translation_allowed' => true, 'chat_allowed' => false, 'review_invitations_allowed' => false,
        'public_ai_search_enabled' => false];
}

function ict_privacy_validate_settings(array $input): array
{
    $keys = array_keys(ict_privacy_defaults());
    if (array_diff(array_keys($input), $keys) || array_diff($keys, array_keys($input))) {
        throw new InvalidArgumentException('Complete the listed privacy controls only.');
    }
    $result = [];
    foreach (['schema_version' => [3, 3], 'purpose_revision' => [1, 10000], 'preference_days' => [1, 365]] as $key => [$min, $max]) {
        $value = filter_var($input[$key], FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) { throw new InvalidArgumentException('Use revision 1–10,000 and a choice duration of 1–365 days.'); }
        $result[$key] = $value;
    }
    foreach (['first_party_enabled', 'gtm_allowed', 'external_reviews_allowed', 'translation_allowed', 'chat_allowed', 'review_invitations_allowed', 'public_ai_search_enabled'] as $key) {
        if (!in_array($input[$key], [true, false, 1, 0, '1', '0'], true)) { throw new InvalidArgumentException('Use the listed on/off controls.'); }
        $result[$key] = in_array($input[$key], [true, 1, '1'], true);
    }
    return $result;
}

/** Read a complete C1 row without silently enabling the new C2 services. */
function ict_privacy_decode_settings(string $json): array
{
    $input = json_decode($json, true, 8, JSON_THROW_ON_ERROR);
    if (!is_array($input)) { throw new InvalidArgumentException('Invalid privacy controls.'); }
    if (($input['schema_version'] ?? null) === 1) {
        $legacyKeys = ['schema_version', 'purpose_revision', 'preference_days', 'first_party_enabled', 'gtm_allowed'];
        if (count($input) !== 5 || array_diff(array_keys($input), $legacyKeys)) { throw new InvalidArgumentException('Incomplete legacy privacy controls.'); }
        $input = array_replace(ict_privacy_defaults(), $input, ['schema_version' => 3, 'external_reviews_allowed' => false,
            'translation_allowed' => false, 'chat_allowed' => false, 'review_invitations_allowed' => false]);
    }
    if (($input['schema_version'] ?? null) === 2) {
        $legacyKeys = array_diff(array_keys(ict_privacy_defaults()), ['public_ai_search_enabled']);
        if (count($input) !== 9 || array_diff(array_keys($input), $legacyKeys)) { throw new InvalidArgumentException('Incomplete legacy privacy controls.'); }
        $input = array_replace($input, ['schema_version' => 3, 'public_ai_search_enabled' => false]);
    }
    return ict_privacy_validate_settings($input);
}

function ict_privacy_settings(): array
{
    try {
        $row = db_table_exists('site_settings') ? fetch_one('SELECT setting_value FROM site_settings WHERE setting_key = :key', ['key' => ICT_PRIVACY_SETTINGS_KEY]) : null;
        if ($row && is_string($row['setting_value'])) {
            return ict_privacy_decode_settings($row['setting_value']) + ['configured' => true];
        }
    } catch (Throwable) { /* Incompatible/malformed settings disable measurement. */ }
    return array_replace(ict_privacy_defaults(), ['first_party_enabled' => false, 'gtm_allowed' => false,
        'external_reviews_allowed' => false, 'translation_allowed' => false, 'chat_allowed' => false,
        'review_invitations_allowed' => false, 'public_ai_search_enabled' => false, 'configured' => false]);
}

function ict_privacy_save_settings(array $input): void
{
    $settings = ict_privacy_validate_settings($input);
    $pdo = db();
    if (!db_table_exists('site_settings')) { throw new RuntimeException('The existing settings table is unavailable.'); }
    $owns = !$pdo->inTransaction();
    if ($owns) { $pdo->beginTransaction(); } else { $pdo->exec('SAVEPOINT ict_privacy_save'); }
    try {
        $existing = fetch_one('SELECT setting_value FROM site_settings WHERE setting_key = :key FOR UPDATE', ['key' => ICT_PRIVACY_SETTINGS_KEY]);
        if ($existing) {
            try { $old = ict_privacy_decode_settings((string) $existing['setting_value']); }
            catch (Throwable) { $old = null; }
            if ($old && $settings['purpose_revision'] < $old['purpose_revision']) { throw new InvalidArgumentException('The purpose revision cannot decrease.'); }
        }
        execute_query('INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', ['key' => ICT_PRIVACY_SETTINGS_KEY, 'value' => json_encode($settings, JSON_THROW_ON_ERROR)]);
        if ($owns) { $pdo->commit(); } else { $pdo->exec('RELEASE SAVEPOINT ict_privacy_save'); }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            if ($owns) { $pdo->rollBack(); }
            else { $pdo->exec('ROLLBACK TO SAVEPOINT ict_privacy_save'); $pdo->exec('RELEASE SAVEPOINT ict_privacy_save'); }
        }
        throw $exception;
    }
}

function ict_privacy_parse_cookie(?string $cookie, array $settings, ?int $now = null): array
{
    $now ??= time();
    $denied = ['chosen' => false, 'analytics' => false, 'external_reviews' => false, 'translation' => false,
        'chat' => false, 'choice_at' => 0, 'expires_at' => 0, 'choice_token' => ''];
    if ($cookie === null || strlen($cookie) > 512) { return $denied; }
    try { $state = json_decode($cookie, true, 8, JSON_THROW_ON_ERROR); } catch (Throwable) { return $denied; }
    if (!is_array($state) || count($state) !== 9 || array_diff(array_keys($state), ['v', 'r', 'a', 'e', 'l', 'c', 't', 'x', 'n'])
        || ($state['v'] ?? null) !== 2 || ($state['r'] ?? null) !== $settings['purpose_revision']
        || !is_bool($state['a'] ?? null) || !is_bool($state['e'] ?? null) || !is_bool($state['l'] ?? null) || !is_bool($state['c'] ?? null)
        || !is_string($state['n'] ?? null) || !preg_match('/\A[a-f0-9]{32}\z/', $state['n']) || !is_int($state['t'] ?? null) || !is_int($state['x'] ?? null)
        || $state['t'] <= 0 || $state['t'] > $now || $state['x'] <= $now || $state['x'] <= $state['t']
        || $state['x'] - $state['t'] > (int) $settings['preference_days'] * 86400) { return $denied; }
    return ['chosen' => true, 'analytics' => $state['a'], 'external_reviews' => $state['e'], 'translation' => $state['l'],
        'chat' => $state['c'], 'choice_at' => $state['t'], 'expires_at' => $state['x'], 'choice_token' => $state['n']];
}

function ict_privacy_state(?array $settings = null): array
{
    $settings ??= ict_privacy_settings();
    $cookie = $_COOKIE[ICT_PRIVACY_COOKIE] ?? null;
    return ict_privacy_parse_cookie(is_string($cookie) ? $cookie : null, $settings);
}

function ict_privacy_cookie_path(): string
{
    $path = '/' . trim((string) parse_url(APP_URL, PHP_URL_PATH), '/');
    return preg_match('~\A/[A-Za-z0-9/_-]*\z~', $path) ? $path : '/';
}

function ict_privacy_cookie_value(bool|array $choice, array $settings, ?int $now = null): string
{
    $now ??= time();
    $choice = is_bool($choice) ? ['analytics' => $choice] : $choice;
    return json_encode(['v' => 2, 'r' => $settings['purpose_revision'], 'a' => ($choice['analytics'] ?? false) === true,
        'e' => ($choice['external_reviews'] ?? false) === true, 'l' => ($choice['translation'] ?? false) === true,
        'c' => ($choice['chat'] ?? false) === true, 't' => $now,
        'x' => $now + (int) $settings['preference_days'] * 86400, 'n' => bin2hex(random_bytes(16))], JSON_THROW_ON_ERROR);
}

function ict_privacy_url(string $path): string { return rtrim(ict_privacy_cookie_path(), '/') . '/' . ltrim($path, '/'); }

function ict_privacy_validate_choice(array $input, array $settings): bool
{
    return ict_privacy_validate_choices($input, $settings)['analytics'];
}

function ict_privacy_validate_choices(array $input, array $settings): array
{
    $revision = filter_var($input['purpose_revision'] ?? null, FILTER_VALIDATE_INT);
    if ($revision !== $settings['purpose_revision']) { throw new InvalidArgumentException('The privacy notice changed. Reload and choose again.'); }
    $action = $input['choice_action'] ?? null;
    if (!in_array($action, ['accept', 'reject', 'custom'], true)) { throw new InvalidArgumentException('Choose accept, reject or save choices.'); }
    $choices = [];
    foreach (ict_privacy_available_categories($settings) as $category => $available) {
        $posted = $input[$category] ?? ($category === 'analytics' && $available ? null : '0');
        if ($action === 'custom' && !in_array($posted, ['1', '0'], true)) { throw new InvalidArgumentException('Use the listed privacy choices.'); }
        $choices[$category] = $available && ($action === 'accept' || ($action === 'custom' && $posted === '1'));
    }
    return $choices;
}

function ict_privacy_save_choice(array $input): array
{
    $settings = ict_privacy_settings();
    $choices = ict_privacy_validate_choices($input, $settings);
    $value = ict_privacy_cookie_value($choices, $settings);
    $state = ict_privacy_parse_cookie($value, $settings);
    if (headers_sent() || !setcookie(ICT_PRIVACY_COOKIE, $value, ['expires' => $state['expires_at'],
        'path' => ict_privacy_cookie_path(), 'secure' => request_is_https(), 'httponly' => true, 'samesite' => 'Lax'])) {
        throw new RuntimeException('The privacy choices could not be saved.');
    }
    $_COOKIE[ICT_PRIVACY_COOKIE] = $value;
    unset($_SESSION['google_tag_events']);
    return $state;
}

function ict_privacy_local_return(mixed $input): string
{
    if (!is_string($input) || strlen($input) > 512 || preg_match('/[\x00-\x20\x7f\\\\]/', $input)) { return ''; }
    $path = (string) (parse_url($input, PHP_URL_PATH) ?: '');
    if ($path === '' || str_starts_with($path, '//') || parse_url($input, PHP_URL_HOST) !== null || str_contains($path, ':') || str_contains(rawurldecode($path), '..')) { return ''; }
    $path = ltrim($path, '/');
    $base = trim(ict_privacy_cookie_path(), '/');
    if ($base !== '' && str_starts_with($path, $base . '/')) { $path = substr($path, strlen($base) + 1); }
    return preg_match('~\A[A-Za-z0-9/_-]+(?:\.php)?\z~', $path) ? $path : '';
}

/** Only coarse public route groups. Direct file routes and pretty routes are supported. */
function ict_analytics_page_group(string $path): string
{
    $path = rawurldecode((string) (parse_url($path, PHP_URL_PATH) ?: ''));
    $base = rtrim(ict_privacy_cookie_path(), '/');
    if ($base !== '' && str_starts_with($path, $base . '/')) { $path = substr($path, strlen($base)); }
    $path = strtolower(trim($path, '/'));
    if (preg_match('~\A(?:admin(?:/|$)|account(?:[._/-]|$)|ai[-_]search(?:\.php|/|$)|privacy_choices(?:_save)?(?:\.php|/|$))~', $path)) { return ''; }
    if ($path === '' || $path === 'index.php') { return 'home'; }
    $groups = ['contact' => 'contact', 'destinations' => 'destinations', 'destination' => 'destinations',
        'experiences' => 'experiences', 'experience' => 'experiences', 'tours' => 'tours', 'tour' => 'tours',
        'day-tours' => 'tours', 'day-tour' => 'tours', 'theme' => 'tours', 'itineraries' => 'itineraries', 'itinerary' => 'itineraries',
        'country' => 'guides', 'blog_post' => 'guides', 'sri_lanka_in_month' => 'guides',
        'n_day_itinerary' => 'itineraries', 'plan' => 'planning', 'compare' => 'planning',
        'reviews' => 'reviews', 'about-us' => 'about', 'why-choose-us' => 'about', 'faq' => 'about',
        'from' => 'guides', 'seasonal' => 'guides', 'blog' => 'guides', 'blog-post' => 'guides', 'static_page' => 'content'];
    if (preg_match('~\A([a-z_-]+)(?:\.php|/[a-z0-9-]+)?\z~', $path, $match) && isset($groups[$match[1]])) { return $groups[$match[1]]; }
    if (preg_match('~\A(?:[0-9]{1,2}-day-itinerary)\z~', $path)) { return 'itineraries'; }
    if (preg_match('~\A(?:honeymoon|family|adventure|wellness|luxury|wildlife)-tours\z~', $path)) { return 'tours'; }
    if (preg_match('~\Asri-lanka-in-(?:january|february|march|april|may|june|july|august|september|october|november|december)\z~', $path)) { return 'guides'; }
    $slug = $path === 'private-driver-sri-lanka/cost' ? 'private-driver-sri-lanka-cost' : $path;
    if (preg_match('/\A[a-z0-9-]+\z/', $slug) === 1) {
        try {
            if (db_table_exists('static_pages') && db_column_exists('static_pages', 'is_published')
                && fetch_one('SELECT id FROM static_pages WHERE slug = :slug AND is_published = 1 LIMIT 1', ['slug' => $slug])) { return 'content'; }
        } catch (Throwable) { /* Unknown routes remain excluded. */ }
    }
    return '';
}

function ict_analytics_request_group(): string
{
    if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) !== 'track-event.php' && basename(request_path()) !== 'track-event.php') {
        return ict_analytics_page_group(request_path());
    }
    $ref = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''));
    $host = parse_url((request_is_https() ? 'https://' : 'http://') . (string) ($_SERVER['HTTP_HOST'] ?? ''));
    if (!is_array($ref) || !is_array($host) || !in_array($ref['scheme'] ?? '', ['http', 'https'], true)
        || strtolower($ref['host'] ?? '') !== strtolower($host['host'] ?? '')
        || ($ref['scheme'] ?? '') !== ($host['scheme'] ?? '')
        || (int) ($ref['port'] ?? (($ref['scheme'] ?? '') === 'https' ? 443 : 80)) !== (int) ($host['port'] ?? (($host['scheme'] ?? '') === 'https' ? 443 : 80))) { return ''; }
    return ict_analytics_page_group((string) ($ref['path'] ?? ''));
}

function ict_analytics_allowed(string $transport = 'first_party'): bool
{
    if (ict_analytics_request_group() === '') { return false; }
    if ($transport === 'first_party') { return true; }
    $settings = ict_privacy_settings();
    return $transport === 'gtm' && $settings['configured'] && $settings['gtm_allowed'] && ict_privacy_state($settings)['analytics'];
}

function ict_analytics_click_keys(): array
{
    return ['whatsapp_click', 'phone_click', 'email_click', 'contact_cta_click', 'sticky_cta_click', 'external_link_click'];
}

function ict_analytics_target_group(string $event): string
{
    return match ($event) { 'whatsapp_click' => 'whatsapp', 'phone_click' => 'phone', 'email_click' => 'email',
        'contact_cta_click' => 'contact', 'sticky_cta_click' => 'sticky', 'external_link_click' => 'external', default => '' };
}

function ict_analytics_disposal_paused(): bool { return true; } // C4 records holds/dry runs; no apply/unpause path is authorized.

function ict_privacy_available_categories(?array $settings = null): array
{
    $settings ??= ict_privacy_settings();
    $configured = $settings['configured'] ?? true; // Validated explicit settings may be used by fixtures.
    return ['analytics' => $configured && $settings['gtm_allowed'],
        'external_reviews' => $configured && $settings['external_reviews_allowed'],
        'translation' => $configured && $settings['translation_allowed'],
        'chat' => $configured && $settings['chat_allowed'] && TAWK_TO_ENABLED && preg_match('/\A[a-zA-Z0-9]+\z/', TAWK_TO_PROPERTY_ID) === 1];
}

function ict_optional_allowed(string $category): bool
{
    $settings = ict_privacy_settings();
    $available = ict_privacy_available_categories($settings);
    return in_array($category, ['external_reviews', 'translation', 'chat'], true) && $settings['configured']
        && ($available[$category] ?? false) && ict_privacy_state($settings)[$category]
        && ict_optional_public_context();
}

/** Widget contexts also cover public partner pages excluded from measurement. */
function ict_optional_public_context(): bool
{
    $path=rawurldecode((string)(parse_url(request_path(),PHP_URL_PATH) ?: ''));
    $base=rtrim(ict_privacy_cookie_path(),'/');
    if ($base!=='' && str_starts_with($path,$base.'/')) { $path=substr($path,strlen($base)); }
    $path=strtolower(trim($path,'/'));
    if (preg_match('~\A(?:admin(?:/|$)|account(?:[._/-]|$)|ai[-_]search(?:\.php|/|$)|privacy_choices(?:_save)?(?:\.php|/|$))~',$path)) { return false; }
    if (ict_analytics_page_group(request_path())!=='') { return true; }
    if (preg_match('~\A(?:country|blog_post|business_partners|business_partner|business_partner_apply|n_day_itinerary|sri_lanka_in_month|plan|compare)\.php\z~',$path)
        || in_array($path,['plan','compare','business-partners','business-partners/apply'],true)
        || preg_match('~\Abusiness-partners/(?:partner/)?[a-z0-9-]+\z~',$path)
        || preg_match('~\A(?:[0-9]{1,2}-day-itinerary|honeymoon-tours|family-tours|adventure-tours|wellness-tours|luxury-tours|wildlife-tours|sri-lanka-in-(?:january|february|march|april|may|june|july|august|september|october|november|december))\z~',$path)) { return true; }
    $slug=$path==='private-driver-sri-lanka/cost' ? 'private-driver-sri-lanka-cost' : $path;
    if (preg_match('/\A[a-z0-9-]+\z/',$slug)!==1) { return false; }
    try {
        return db_table_exists('static_pages') && db_column_exists('static_pages','is_published')
            && fetch_one('SELECT id FROM static_pages WHERE slug=:slug AND is_published=1 LIMIT 1',['slug'=>$slug])!==null;
    } catch (Throwable) { return false; }
}

/** Trusted application snippets only; inert until the browser independently checks the grant. */
function ict_privacy_optional_template(string $category, string $id, string $html): string
{
    if (!ict_optional_allowed($category) || preg_match('/\A[a-z0-9_-]+\z/', $id) !== 1) { return ''; }
    return '<template data-optional-category="' . e($category) . '" data-optional-id="' . e($id) . '">' . $html . '</template>';
}

function ict_privacy_local_avatar_url(string $url): string
{
    $parts = parse_url($url);
    if (!is_array($parts) || isset($parts['user']) || isset($parts['pass'])) { return ''; }
    if (isset($parts['host'])) {
        $origin = parse_url(APP_URL);
        if (strtolower($parts['host']) !== strtolower((string) ($origin['host'] ?? ''))
            || ($parts['scheme'] ?? '') !== ($origin['scheme'] ?? '') || ($parts['port'] ?? null) !== ($origin['port'] ?? null)) { return ''; }
    } elseif (str_starts_with($url, '//') || isset($parts['scheme'])) { return ''; }
    $path = ltrim((string) ($parts['path'] ?? ''), '/');
    $base = trim(ict_privacy_cookie_path(), '/');
    if ($base !== '' && str_starts_with($path, $base . '/')) { $path = substr($path, strlen($base) + 1); }
    return preg_match('~\Aassets/(?:images|uploads)/[A-Za-z0-9/_-]+\.(?:png|jpe?g|webp|avif|gif|svg)\z~i', $path) ? ict_privacy_url($path) : '';
}

function ict_privacy_browser_config(): array
{
    $settings = ict_privacy_settings();
    return ['state' => ict_privacy_state($settings), 'revision' => $settings['purpose_revision'],
        'firstPartyAllowed' => ict_analytics_allowed(), 'gtmAllowed' => ict_analytics_allowed('gtm'),
        'pageGroup' => ict_analytics_page_group(request_path()), 'choiceUrl' => ict_privacy_url('privacy_choices.php'),
        'saveUrl' => ict_privacy_url('privacy_choices_save.php'), 'cookiePath' => ict_privacy_cookie_path(),
        'firstPartyEnabled' => true,
        'gtmEnabled' => $settings['configured'] && $settings['gtm_allowed'],
        'availableCategories' => ict_privacy_available_categories($settings), 'optionalContext' => ict_optional_public_context(), 'csrf' => csrf_token()];
}
