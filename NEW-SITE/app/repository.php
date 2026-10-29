<?php
declare(strict_types=1);

namespace NewSite;

/**
 * Independent preview repository. Never import the original application's bootstrap.
 * Source references only: database/schema.sql; destination.php; itinerary.php;
 * tour.php; day-tour.php; experience.php; includes/schema_helpers.php blog readers.
 * No credentials are discovered from files, original constants, or root config.
 */
interface CatalogReadConnection
{
    public function rows(string $sql, array $parameters, int $maximum): array;
    public function beginReadOnly(): void;
    public function inTransaction(): bool;
    public function rollback(): void;
}

final class PdoCatalogReadConnection implements CatalogReadConnection
{
    private \PDO $pdo;

    public function __construct(array $config)
    {
        // PHP 8.3 PDO constants. No init command, option file, local infile or pooling.
        $this->pdo = new \PDO($config['dsn'], $config['user'], $config['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_PERSISTENT => false,
            \PDO::ATTR_TIMEOUT => 3,
            \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
            \PDO::MYSQL_ATTR_LOCAL_INFILE => false,
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
        ]);
    }

    public function rows(string $sql, array $parameters, int $maximum): array
    {
        $statement = $this->pdo->prepare($sql);
        try {
            $statement->execute($parameters);
            $rows = [];
            while (($row = $statement->fetch()) !== false) {
                if (count($rows) >= $maximum) {
                    throw new \RuntimeException('Preview read bound exceeded.');
                }
                $rows[] = $row;
            }
            return $rows;
        } finally {
            $statement->closeCursor();
        }
    }

    public function beginReadOnly(): void
    {
        if ($this->pdo->inTransaction()) {
            throw new \RuntimeException('Unexpected preview transaction.');
        }
        // Next-transaction characteristic must precede beginTransaction(). This is
        // defense in depth, NOT a replacement for rejecting writable account grants.
        $this->pdo->exec('SET TRANSACTION READ ONLY');
        if (!$this->pdo->beginTransaction()) {
            throw new \RuntimeException('Preview transaction unavailable.');
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function rollback(): void
    {
        if (!$this->pdo->rollBack()) {
            throw new \RuntimeException('Preview rollback unavailable.');
        }
    }
}

/** Pure, deliberately narrow DSN parser. Canonicalize localhost to TCP loopback. */
function catalog_dsn(string $dsn): ?string
{
    if (strlen($dsn) > 256 || !str_starts_with($dsn, 'mysql:')) {
        return null;
    }
    $parts = [];
    foreach (explode(';', substr($dsn, 6)) as $part) {
        if (!preg_match('/\A(host|dbname|port|charset)=([A-Za-z0-9_.]+)\z/D', $part, $match)
            || array_key_exists($match[1], $parts)) {
            return null;
        }
        $parts[$match[1]] = $match[2];
    }
    if (!isset($parts['host'], $parts['dbname'])
        || !in_array($parts['host'], ['127.0.0.1', 'localhost'], true)
        || !preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,63}\z/D', $parts['dbname'])
        || (isset($parts['charset']) && $parts['charset'] !== 'utf8mb4')) {
        return null;
    }
    $port = $parts['port'] ?? '3306';
    if (!preg_match('/\A[1-9][0-9]{0,4}\z/D', $port) || (int) $port > 65535) {
        return null;
    }
    return 'mysql:host=127.0.0.1;dbname=' . $parts['dbname'] . ';port=' . $port . ';charset=utf8mb4';
}

/** Pure config classification: even an explicitly empty value is configuration. */
function catalog_configuration(array $environment): array
{
    $keys = ['NEW_SITE_DB_DSN', 'NEW_SITE_DB_USER', 'NEW_SITE_DB_PASSWORD'];
    $supplied = array_filter($keys, static fn (string $key): bool =>
        array_key_exists($key, $environment) && $environment[$key] !== false && $environment[$key] !== null);
    if ($supplied === []) {
        return ['mode' => 'fixture'];
    }
    foreach ($keys as $key) {
        if (!isset($environment[$key]) || !is_string($environment[$key]) || $environment[$key] === ''
            || str_contains($environment[$key], "\0")) {
            return ['mode' => 'unavailable'];
        }
    }
    $dsn = catalog_dsn($environment['NEW_SITE_DB_DSN']);
    $user = $environment['NEW_SITE_DB_USER'];
    if ($dsn === null || !preg_match('/\A[A-Za-z0-9_.-]{1,80}\z/D', $user)
        || strtolower($user) === 'root' || strlen($environment['NEW_SITE_DB_PASSWORD']) > 4096) {
        return ['mode' => 'unavailable'];
    }
    return ['mode' => 'configured', 'dsn' => $dsn, 'user' => $user,
        'password' => $environment['NEW_SITE_DB_PASSWORD']];
}

/**
 * Pure strict SHOW GRANTS grammar, not a blacklist. All lines must be recognized.
 * Only USAGE / SELECT, same grantee, no roles, proxy, ALL, WITH GRANT OPTION,
 * authentication/resource clauses, routines, REVOKEs or unknown syntax accepted.
 * SHOW GRANTS (without FOR) includes mandatory roles on MySQL 8; role lines fail.
 * MySQL-style identifiers/accounts only; other server dialects fail closed.
 */
function catalog_grants_are_read_only(array $grants): bool
{
    if ($grants === [] || count($grants) > 64) {
        return false;
    }
    $identifier = '(?:`[A-Za-z0-9_$%]+`|[A-Za-z0-9_$%]+)';
    $account = '(?:`[A-Za-z0-9_.%:@-]+`|\'[A-Za-z0-9_.%:@-]+\')';
    $grantee = null;
    foreach ($grants as $grant) {
        if (!is_string($grant) || strlen($grant) > 16384
            || !preg_match('/\AGRANT (USAGE|SELECT(?:\s*\(' . $identifier . '(?:\s*,\s*' . $identifier . ')*\))?) ON ('
                . $identifier . '|\*)\.(' . $identifier . '|\*) TO (' . $account . '@' . $account . ')\z/D', $grant, $match)) {
            return false;
        }
        if (($match[2] === '*' && $match[3] !== '*')
            || ($match[1] === 'USAGE' && ($match[2] !== '*' || $match[3] !== '*'))
            || (str_contains($match[1], '(') && ($match[2] === '*' || $match[3] === '*'))
            || ($grantee !== null && $grantee !== $match[4])) {
            return false;
        }
        $grantee = $match[4];
    }
    return true;
}

/** Static table/column/route allowlist, based on current public controllers. */
function catalog_schema(): array
{
    $common = ['id', 'slug', 'excerpt', 'intro', 'hero_image', 'og_image', 'hero_media_type',
        'hero_video_poster', 'meta_title', 'meta_description', 'is_published', 'is_featured', 'sort_order'];
    $commercial = ['highlights', 'pricing_notes', 'price_from', 'price_to', 'price_currency', 'price_unit',
        'vehicle_options', 'faq_json'];
    return [
        'destinations' => ['destination', 'destinations', '/destinations/', 'name', array_merge($common, [
            'name', 'cover_image', 'why_visit', 'things_to_do', 'best_time', 'how_to_get_there',
            'suggested_itinerary', 'where_to_stay', 'travel_tips', 'nearby_travel_times', 'works_well_with',
            'faq_json', 'province', 'temperature', 'destination_type', 'recommended_stay', 'region_label'])],
        'itineraries' => ['itinerary', 'journeys', '/itineraries/', 'name', array_merge($common, $commercial, [
            'name', 'days', 'category_id', 'route_summary', 'nearby_travel_times', 'works_well_with',
            'best_for', 'travel_notes', 'traveller_types', 'start_point', 'end_point'])],
        'tours' => ['tour', 'tours', '/tours/', 'name', array_merge($common, $commercial, ['name', 'sample_itinerary', 'includes', 'excludes'])],
        'day_tours' => ['day-tour', 'day_tours', '/day-tours/', 'name', array_merge($common, $commercial, [
            'name', 'itinerary', 'includes', 'excludes', 'meeting_point', 'duration_hours', 'start_time',
            'vehicle_type', 'max_group_size', 'difficulty', 'best_time'])],
        'experiences' => ['experience', 'experiences', '/experiences/', 'title', array_merge($common, [
            'title', 'where_to_experience', 'tips_json', 'best_time', 'category_id'])],
        'blog_posts' => ['blog', 'articles', '/blog/', 'title', array_merge($common, [
            'title', 'content', 'author', 'published_at', 'category_id'])],
    ];
}

/**
 * Plain text only. Limits are raw UTF-8 byte bounds, NOT truncation requests.
 * Consumers must still HTML-escape; rich markup stripping is not source parity.
 */
function catalog_text(mixed $value, int $limit = 32768): string
{
    if ($value === null) {
        return '';
    }
    if ((!is_string($value) && !is_int($value) && !is_float($value))
        || strlen((string) $value) > $limit) {
        throw new \RuntimeException('Preview text bound or shape unsupported.');
    }
    $text = (string) $value;
    $text = (string) preg_replace('~<(script|style|iframe|object|svg)\b[^>]*>.*?</\1\s*>~is', '', $text);
    $text = (string) preg_replace('~</?(?:p|div|li|h[1-6]|br|section)\b[^>]*>~i', "\n", $text);
    // Decode before the final strip as an extra precaution for encoded markup.
    return trim(strip_tags(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

/** Never inspect image files or uploads. A source not already approved stays empty. */
function catalog_image(array $row): string
{
    $keys = ($row['hero_media_type'] ?? '') === 'video'
        ? ['hero_video_poster', 'cover_image', 'og_image'] : ['hero_image', 'cover_image', 'og_image'];
    $allowed = function_exists(__NAMESPACE__ . '\\media_allowlist') ? media_allowlist() : [];
    foreach ($keys as $key) {
        $path = $row[$key] ?? '';
        if (is_string($path) && in_array($path, $allowed, true)) {
            return $path;
        }
    }
    return '';
}

function catalog_lines(mixed $value): array
{
    $text = catalog_text($value);
    $lines = $text === '' ? [] : array_values(array_filter(array_map('trim', explode("\n", $text)),
        static fn (string $line): bool => $line !== ''));
    if (count($lines) > 40) {
        throw new \RuntimeException('Preview list bound exceeded.');
    }
    return $lines;
}

/** Absent optional JSON is empty; present invalid/oversized JSON rejects the read. */
function catalog_json_array(mixed $value, int $maximum): array
{
    if ($value === null || $value === '') {
        return [];
    }
    if (!is_string($value) || strlen($value) > 32768) {
        throw new \RuntimeException('Preview JSON bound or shape unsupported.');
    }
    if (trim($value) === '') {
        return [];
    }
    if (!str_starts_with(ltrim($value), '[')) {
        throw new \RuntimeException('Preview JSON root must be a list.');
    }
    $decoded = json_decode($value, true, 10, JSON_THROW_ON_ERROR);
    if (!is_array($decoded) || !array_is_list($decoded) || count($decoded) > $maximum) {
        throw new \RuntimeException('Preview JSON list bound or shape unsupported.');
    }
    return $decoded;
}

function catalog_json_strings(mixed $value): array
{
    return array_map(static function (mixed $item): string {
        if ($item === null) {
            throw new \RuntimeException('Preview JSON string item unsupported.');
        }
        return catalog_text($item, 2000);
    }, catalog_json_array($value, 40));
}

function catalog_faq(mixed $value): array
{
    $faq = [];
    foreach (catalog_json_array($value, 30) as $item) {
        if (!is_array($item) || array_diff(array_keys($item), ['question', 'answer', 'q', 'a']) !== []
            || (isset($item['question'], $item['q']) && $item['question'] !== $item['q'])
            || (isset($item['answer'], $item['a']) && $item['answer'] !== $item['a'])) {
            throw new \RuntimeException('Preview FAQ item unsupported.');
        }
        $question = catalog_text($item['question'] ?? $item['q'] ?? '', 500);
        $answer = catalog_text($item['answer'] ?? $item['a'] ?? '', 5000);
        if ($question === '' || $answer === '') {
            throw new \RuntimeException('Preview FAQ item incomplete.');
        }
        $faq[] = ['question' => $question, 'answer' => $answer];
    }
    return $faq;
}

function catalog_record(array $row, array $definition): ?array
{
    [$family, , $prefix, $titleColumn] = $definition;
    $slug = $row['slug'] ?? '';
    $title = catalog_text($row[$titleColumn] ?? '', 500);
    $id = (string) ($row['id'] ?? '');
    if (!is_string($slug) || !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $slug)
        || strlen($slug) > 180 || !ctype_digit($id) || (int) $id < 1 || $title === '') {
        return null;
    }
    $body = [];
    $headings = ['intro' => 'Overview', 'content' => 'Article', 'why_visit' => 'Why visit',
        'things_to_do' => 'Things to do', 'best_time' => 'Best time', 'how_to_get_there' => 'Getting there',
        'suggested_itinerary' => 'Suggested itinerary', 'where_to_stay' => 'Where to stay',
        'travel_tips' => 'Travel tips', 'nearby_travel_times' => 'Travel times', 'works_well_with' => 'Works well with',
        'highlights' => 'Highlights', 'route_summary' => 'Route summary', 'best_for' => 'Best for',
        'travel_notes' => 'Travel notes', 'pricing_notes' => 'Pricing notes', 'vehicle_options' => 'Vehicle options',
        'sample_itinerary' => 'Sample itinerary', 'itinerary' => 'Itinerary', 'where_to_experience' => 'Where to experience'];
    foreach ($headings as $key => $heading) {
        $text = catalog_text($row[$key] ?? '');
        if ($text !== '') {
            $body[] = ['heading' => $heading, 'text' => $text];
        }
    }
    $tips = catalog_json_strings($row['tips_json'] ?? '');
    if ($tips !== []) {
        $body[] = ['heading' => 'Tips', 'text' => implode("\n", $tips)];
    }
    $facts = [];
    foreach (['days' => 'Duration (days)', 'province' => 'Province', 'destination_type' => 'Destination type',
        'recommended_stay' => 'Recommended stay', 'duration_hours' => 'Duration (hours)', 'difficulty' => 'Difficulty',
        'start_point' => 'Start', 'end_point' => 'Finish', 'meeting_point' => 'Meeting point',
        'start_time' => 'Start time', 'vehicle_type' => 'Vehicle', 'max_group_size' => 'Maximum group size'] as $key => $label) {
        $value = catalog_text($row[$key] ?? '', 500);
        if ($value !== '') {
            $facts[] = ['label' => $label, 'value' => $value];
        }
    }
    $sections = [];
    foreach ($body as $index => $section) {
        $sections[] = ['id' => 'section-' . ($index + 1)] + $section;
    }
    $record = ['id' => $id, 'slug' => $slug, 'family' => $family, 'title' => $title,
        'summary' => catalog_text($row['excerpt'] ?? '', 2000), 'url' => $prefix . $slug,
        'image' => catalog_image($row), 'image_alt' => $title,
        'label' => 'Published CMS content - read-only preview', 'demo' => false,
        'body' => $body, 'sections' => $sections, 'facts' => $facts, 'days' => [], 'route' => [],
        'route_label' => 'Published CMS itinerary', 'inclusions' => catalog_lines($row['includes'] ?? ''),
        'exclusions' => catalog_lines($row['excludes'] ?? ''), 'faq' => catalog_faq($row['faq_json'] ?? ''),
        'gallery' => [], 'media_available' => catalog_image($row) !== ''];
    // Explicit public scalar fields for filtering and detailed page composition.
    // Duration is duration_days because days[] is the normalized timeline contract.
    foreach (['meta_title', 'meta_description', 'category_id', 'province', 'region_label', 'destination_type',
        'recommended_stay', 'duration_hours', 'difficulty', 'traveller_types', 'start_point', 'end_point',
        'price_from', 'price_to', 'price_currency', 'price_unit', 'author', 'published_at'] as $key) {
        if (isset($row[$key])) {
            $record[$key] = catalog_text($row[$key], 2000);
        }
    }
    if (isset($row['days']) && ctype_digit((string) $row['days']) && (int) $row['days'] > 0) {
        $record['duration_days'] = (int) $row['days'];
    }
    if ($family === 'blog' && !empty($record['published_at'])) {
        $record['date'] = $record['published_at'];
    }
    return $record;
}

/** Metadata checks never inspect unallowlisted tables or application settings. */
function catalog_columns(CatalogReadConnection $connection, string $table): array
{
    if (!isset(catalog_schema()[$table]) && $table !== 'itinerary_days') {
        throw new \InvalidArgumentException('Unknown preview table.');
    }
    // Base tables only: no views/definer routines disguised as public content.
    $found = $connection->rows('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'
        . " AND TABLE_NAME = :table_name AND TABLE_TYPE = 'BASE TABLE' LIMIT 1", ['table_name' => $table], 1);
    if ($found === []) {
        return [];
    }
    $columns = $connection->rows('SHOW COLUMNS FROM `' . $table . '`', [], 256);
    return array_column($columns, 'Field');
}

function catalog_projection(array $allowed, array $columns): string
{
    // One extra character is a sentinel, never an accepted clipped prefix.
    // strlen validation below additionally enforces the 32768-byte UTF-8 bound.
    return implode(', ', array_map(static fn (string $column): string =>
        'LEFT(`' . $column . '`, 32769) AS `' . $column . '`', array_values(array_intersect($allowed, $columns))));
}

function catalog_assert_row_bounds(array $row): void
{
    foreach ($row as $value) {
        if ($value !== null && ((!is_string($value) && !is_int($value) && !is_float($value))
            || strlen((string) $value) > 32768)) {
            throw new \RuntimeException('Preview source field bound or shape unsupported.');
        }
    }
}

function catalog_fetch(CatalogReadConnection $connection): array
{
    $catalog = ['hero' => ['image' => '', 'alt' => '', 'place' => ''], 'destinations' => [],
        'journeys' => [], 'tours' => [], 'day_tours' => [], 'experiences' => [], 'articles' => [],
        'reviews' => [], 'records' => []];
    foreach (catalog_schema() as $table => $definition) {
        [$family, $collection, , $titleColumn, $allowed] = $definition;
        $catalog['records'][$family] = [];
        $columns = catalog_columns($connection, $table);
        $required = ['id', 'slug', $titleColumn, 'is_published'];
        if ($table === 'blog_posts') {
            $required[] = 'published_at';
        }
        if (array_diff($required, $columns) !== []) {
            continue; // Never infer publication on older, incompatible tables.
        }
        $where = '`is_published` = 1';
        if (in_array('published_at', $columns, true)) {
            $where .= ' AND (`published_at` IS NULL OR `published_at` <= CURRENT_TIMESTAMP)';
        }
        $order = [];
        foreach (['is_featured' => 'DESC', 'sort_order' => 'ASC'] as $column => $direction) {
            if (in_array($column, $columns, true)) {
                $order[] = '`' . $column . '` ' . $direction;
            }
        }
        $order[] = '`id` ASC';
        $rows = $connection->rows('SELECT ' . catalog_projection($allowed, $columns) . ' FROM `' . $table . '` WHERE '
            . $where . ' ORDER BY ' . implode(', ', $order) . ' LIMIT 101', [], 101);
        if (count($rows) > 100) {
            throw new \RuntimeException('Preview catalogue bound exceeded.');
        }
        foreach ($rows as $row) {
            catalog_assert_row_bounds($row);
            $record = catalog_record($row, $definition);
            if ($record === null || isset($catalog['records'][$family][$record['slug']])) {
                throw new \RuntimeException('Preview source record cannot be preserved.');
            }
            $catalog['records'][$family][$record['slug']] = $record;
        }
        $catalog[$collection] = array_values($catalog['records'][$family]);
    }
    if ($catalog['journeys'] !== []) {
        $columns = catalog_columns($connection, 'itinerary_days');
        if (array_diff(['itinerary_id', 'day_number', 'title', 'summary'], $columns) === []) {
            $allowed = ['itinerary_id', 'day_number', 'day_end', 'title', 'summary', 'overnight_location', 'travel_time', 'image_path', 'highlights_json'];
            // One bounded query across only the already-selected published parents.
            $ids = array_column($catalog['journeys'], 'id');
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $days = $connection->rows('SELECT ' . catalog_projection($allowed, $columns)
                . ' FROM `itinerary_days` WHERE `itinerary_id` IN (' . $placeholders . ')'
                . ' ORDER BY `itinerary_id` ASC, `day_number` ASC LIMIT 2001', $ids, 2001);
            if (count($days) > 2000) {
                throw new \RuntimeException('Preview timeline bound exceeded.');
            }
            $byId = [];
            foreach ($catalog['journeys'] as $record) {
                $byId[$record['id']] = $record['slug'];
            }
            foreach ($days as $row) {
                catalog_assert_row_bounds($row);
                $slug = $byId[(string) ($row['itinerary_id'] ?? '')] ?? null;
                $number = (int) ($row['day_number'] ?? 0);
                $end = (int) ($row['day_end'] ?? $number);
                if ($slug === null || !ctype_digit((string) ($row['day_number'] ?? ''))
                    || !ctype_digit((string) ($row['day_end'] ?? $number))
                    || $number < 1 || $number > 365 || $end < $number || $end > 365) {
                    throw new \RuntimeException('Preview timeline item cannot be preserved.');
                }
                $catalog['records']['itinerary'][$slug]['days'][] = [
                    'day_number' => $number, 'day_end' => $end,
                    'title' => catalog_text($row['title'] ?? '', 1000), 'summary' => catalog_text($row['summary'] ?? ''),
                    'location' => catalog_text($row['overnight_location'] ?? '', 500),
                    'travel_time' => catalog_text($row['travel_time'] ?? '', 500),
                    'image' => catalog_image(['hero_image' => $row['image_path'] ?? '']),
                    'highlights' => catalog_json_strings($row['highlights_json'] ?? ''),
                ];
            }
            $catalog['journeys'] = array_values($catalog['records']['itinerary']);
        }
    }
    return $catalog;
}

function catalog_unavailable(): array
{
    // Never display/log exception text, DSNs, usernames, passwords or partial data.
    return ['mode' => 'unavailable', 'source_message' => 'Published CMS content unavailable. The optional local read-only source could not be verified; no sample content is substituted.', 'catalog' => null];
}

/** Internal DI seam: tests pass a fake; HTTP/environment cannot supply a factory. */
function read_only_catalog_with_factory(?array $environment, callable $factory): array
{
    $environment ??= [
        'NEW_SITE_DB_DSN' => getenv('NEW_SITE_DB_DSN'),
        'NEW_SITE_DB_USER' => getenv('NEW_SITE_DB_USER'),
        'NEW_SITE_DB_PASSWORD' => getenv('NEW_SITE_DB_PASSWORD'),
    ];
    $config = catalog_configuration($environment);
    if ($config['mode'] === 'fixture') {
        return ['mode' => 'fixture', 'source_message' => 'Design preview using labelled samples. No database is configured or connected.', 'catalog' => null];
    }
    if ($config['mode'] !== 'configured') {
        return catalog_unavailable();
    }
    $connection = null;
    $result = catalog_unavailable();
    try {
        $connection = $factory($config);
        if (!$connection instanceof CatalogReadConnection || $connection->inTransaction()) {
            throw new \RuntimeException('Invalid preview connection.');
        }
        // Do not use FOR CURRENT_USER: MySQL's no-FOR form includes mandatory roles.
        $grantRows = $connection->rows('SHOW GRANTS', [], 64);
        $grants = [];
        foreach ($grantRows as $row) {
            if (!is_array($row) || count($row) !== 1) {
                throw new \RuntimeException('Invalid preview grant result.');
            }
            $grants[] = array_values($row)[0];
        }
        if (!catalog_grants_are_read_only($grants)) {
            throw new \RuntimeException('Preview grants rejected.');
        }
        $connection->beginReadOnly();
        if (!$connection->inTransaction()) {
            throw new \RuntimeException('Preview transaction not active.');
        }
        $catalog = catalog_fetch($connection);
        $result = ['mode' => 'database', 'source_message' => 'Bounded complete-catalogue candidate from a verified SELECT-only local account, for available mapped public fields only. All selected records fit preview limits; plain-text conversion is not original-site parity. Submissions and bookings remain disabled.', 'catalog' => $catalog];
    } catch (\Throwable) {
        $result = catalog_unavailable();
    } finally {
        if ($connection instanceof CatalogReadConnection) {
            try {
                if ($connection->inTransaction()) {
                    $connection->rollback();
                }
            } catch (\Throwable) {
                $result = catalog_unavailable();
            }
        }
        $connection = null; // No persistent connection or returned PDO handle.
    }
    return $result;
}

/** @return array{mode: string, source_message: string, catalog: ?array} */
function read_only_catalog(?array $environment = null): array
{
    return read_only_catalog_with_factory($environment,
        static fn (array $config): CatalogReadConnection => new PdoCatalogReadConnection($config));
}
