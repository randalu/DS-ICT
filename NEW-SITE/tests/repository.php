<?php
declare(strict_types=1);

// Standalone fake-only suite. Never load root bootstrap, read environment config,
// instantiate PDO, connect to a DB, or execute the original application.
require_once dirname(__DIR__) . '/app/repository.php';
require_once dirname(__DIR__) . '/app/media.php';

use NewSite\CatalogReadConnection;
use function NewSite\{catalog_configuration, catalog_dsn, catalog_grants_are_read_only,
    catalog_faq, catalog_image, catalog_json_strings, catalog_lines, catalog_projection, catalog_schema,
    catalog_text, read_only_catalog, read_only_catalog_with_factory};

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
};
$throws = static function (callable $operation) use ($check): void {
    $thrown = false;
    try {
        $operation();
    } catch (Throwable) {
        $thrown = true;
    }
    $check($thrown, 'unsupported content throws instead of silently dropping/clipping it');
};
$config = ['NEW_SITE_DB_DSN' => 'mysql:host=localhost;dbname=preview_catalog;charset=utf8mb4',
    'NEW_SITE_DB_USER' => 'preview_reader', 'NEW_SITE_DB_PASSWORD' => 'synthetic-test-only-secret'];
$account = '`preview_reader`@`localhost`';
$safe = ['GRANT USAGE ON *.* TO ' . $account, 'GRANT SELECT ON `preview_catalog`.* TO ' . $account];

$check(catalog_dsn($config['NEW_SITE_DB_DSN']) === 'mysql:host=127.0.0.1;dbname=preview_catalog;port=3306;charset=utf8mb4', 'localhost canonicalizes to TCP loopback');
$check(catalog_dsn('mysql:port=3307;dbname=preview_catalog;host=127.0.0.1') === 'mysql:host=127.0.0.1;dbname=preview_catalog;port=3307;charset=utf8mb4', 'strict fields may be reordered and charset defaults safely');
foreach (['', 'sqlite::memory:', 'uri:file:///tmp/secret', 'mysql:host=db.example;dbname=preview_catalog',
    'mysql:host=127.0.0.2;dbname=preview_catalog', 'mysql:host=::1;dbname=preview_catalog',
    'mysql:host=localhost;dbname=mysql;unix_socket=/tmp/mysql.sock', 'mysql:host=localhost;dbname=preview_catalog;',
    'mysql:host=localhost;dbname=preview_catalog;host=remote', 'mysql:host=localhost;dbname=x;port=0',
    'mysql:host=localhost;dbname=x;port=65536', 'mysql:host=localhost;dbname=x;port=03306',
    'mysql:host=localhost;dbname=x;charset=latin1', 'mysql:host=localhost;dbname=x;charset=utf8mb4;init_command=DELETE',
    'mysql:host=localhost;dbname=x;user=reader', "mysql:host=localhost;dbname=x\n", 'mysql:host=localhost;dbname=x/*comment*/',
    'mysql:host=127.0.0.1.evil;dbname=x', 'mysql:dbname=x', 'mysql:host=localhost;dbname=x;port=3.5',
    'mysql:host=localhost;dbname=x;MYSQL_ATTR_READ_DEFAULT_FILE=secret', 'mysql:host=localhost;dbname=x;foo=bar'] as $dsn) {
    $check(catalog_dsn($dsn) === null, 'unsafe/incompatible DSN rejected');
}
$check(catalog_configuration([])['mode'] === 'fixture', 'absent config is fixture mode');
$check(catalog_configuration(['NEW_SITE_DB_DSN' => false, 'NEW_SITE_DB_USER' => null])['mode'] === 'fixture', 'getenv absent values are fixture mode');
$check(catalog_configuration($config)['mode'] === 'configured', 'complete synthetic config accepted');
$calls = 0;
$neverConnect = static function () use (&$calls): CatalogReadConnection {
    $calls++;
    throw new RuntimeException('Should never connect.');
};
foreach ([[], ['DB_DSN' => 'ignored-root-like-config'], ['NEW_SITE_DB_DSN' => null]] as $environment) {
    $state = read_only_catalog_with_factory($environment, $neverConnect);
    $check($state['mode'] === 'fixture' && $state['catalog'] === null, 'unconfigured read returns fixture/null without data');
}
foreach ([['NEW_SITE_DB_DSN' => ''], ['NEW_SITE_DB_PASSWORD' => ''], ['NEW_SITE_DB_USER' => 'reader'],
    array_replace($config, ['NEW_SITE_DB_DSN' => 'mysql:host=remote;dbname=x']),
    array_replace($config, ['NEW_SITE_DB_PASSWORD' => '']), array_replace($config, ['NEW_SITE_DB_PASSWORD' => false]),
    array_replace($config, ['NEW_SITE_DB_DSN' => []]), array_replace($config, ['NEW_SITE_DB_USER' => 'root']),
    array_replace($config, ['NEW_SITE_DB_USER' => "reader\0bad"]), array_replace($config, ['NEW_SITE_DB_USER' => 'reader;']),
    array_replace($config, ['NEW_SITE_DB_PASSWORD' => str_repeat('x', 4097)])] as $environment) {
    $state = read_only_catalog_with_factory($environment, $neverConnect);
    $check($state['mode'] === 'unavailable' && $state['catalog'] === null, 'invalid config is unavailable without fixture fallback');
}
$check($calls === 0, 'no factory calls for absent/invalid configuration');
$check(read_only_catalog([])['mode'] === 'fixture', 'public API no-config path never instantiates PDO');
$check(read_only_catalog(['NEW_SITE_DB_DSN' => 'invalid'])['mode'] === 'unavailable', 'public API invalid path never instantiates PDO');

foreach ([$safe, ['GRANT SELECT ON *.* TO ' . $account], ['GRANT USAGE ON *.* TO ' . $account],
    ['GRANT SELECT (`title`, `slug`) ON `preview_catalog`.`blog_posts` TO ' . $account],
    ["GRANT SELECT ON preview_catalog.* TO 'preview_reader'@'localhost'"]] as $grants) {
    $check(catalog_grants_are_read_only($grants), 'known SELECT/USAGE grammar accepted');
}
$badGrants = [[], [null], [''], ['GRANT ALL PRIVILEGES ON *.* TO ' . $account],
    ['GRANT SELECT, INSERT ON `preview_catalog`.* TO ' . $account],
    ['GRANT SELECT ON `preview_catalog`.* TO ' . $account . ' WITH GRANT OPTION'],
    ['GRANT USAGE ON *.* TO ' . $account . " IDENTIFIED BY PASSWORD 'hash'"],
    ['GRANT `reader_role`@`%` TO ' . $account], ['GRANT PROXY ON `writer`@`%` TO ' . $account],
    ['GRANT SELECT ON FUNCTION `preview_catalog`.`secret` TO ' . $account],
    ['GRANT USAGE ON `preview_catalog`.* TO ' . $account], ['GRANT SELECT (`title`) ON *.* TO ' . $account],
    ['GRANT SELECT ON *.`table` TO ' . $account], ['REVOKE INSERT ON *.* FROM ' . $account],
    ['GRANT SELECT ON *.* TO ' . $account . '; DROP TABLE x'], ['GRANT SELECT ON *.* TO ' . $account . ' REQUIRE SSL'],
    ['GRANT SELECT ON *.* TO ' . $account . "\n"], ['GRANT SELECT ON *.* TO ' . $account . ' -- comment'],
    ['GRANT SELECT ON *.* TO ' . $account, 'GRANT SELECT ON *.* TO `other`@`localhost`'],
    array_fill(0, 65, $safe[0])];
foreach (['INSERT', 'UPDATE', 'DELETE', 'CREATE', 'DROP', 'ALTER', 'FILE', 'EXECUTE', 'PROCESS', 'SUPER',
    'CREATE TEMPORARY TABLES', 'LOCK TABLES', 'CREATE ROUTINE', 'EVENT', 'TRIGGER', 'ROLE_ADMIN',
    'SYSTEM_VARIABLES_ADMIN', 'CONNECTION_ADMIN', 'GRANT OPTION'] as $privilege) {
    $badGrants[] = array_merge($safe, ['GRANT ' . $privilege . ' ON *.* TO ' . $account]);
}
foreach ($badGrants as $grants) {
    $check(!catalog_grants_are_read_only($grants), 'mutation/role/proxy/unknown or malformed grant rejected');
}

final class FakeCatalogConnection implements CatalogReadConnection
{
    public array $events = [];
    public array $tables = [];
    public array $grants;
    public bool $active = false;
    public bool $failBegin = false;
    public bool $failRollback = false;
    public ?string $failTable = null;
    public ?string $omitColumn = null;

    public function __construct(array $grants)
    {
        $this->grants = array_map(static fn (mixed $grant): array => ['Grants' => $grant], $grants);
    }

    private function project(array $rows, string $sql): array
    {
        preg_match_all('/LEFT\(`([a-z_]+)`, ([0-9]+)\) AS `[a-z_]+`/', $sql, $projection, PREG_SET_ORDER);
        return array_map(static function (array $row) use ($projection): array {
            $selected = [];
            foreach ($projection as $column) {
                $value = $row[$column[1]] ?? null;
                // Model MySQL LEFT's character count, including the extra sentinel.
                if (is_string($value) && preg_match('/\A.{0,' . (int) $column[2] . '}/us', $value, $prefix) === 1) {
                    $value = $prefix[0];
                }
                $selected[$column[1]] = $value;
            }
            return $selected;
        }, $rows);
    }

    public function rows(string $sql, array $parameters, int $maximum): array
    {
        $this->events[] = ['sql' => $sql, 'parameters' => $parameters, 'maximum' => $maximum];
        if ($sql === 'SHOW GRANTS') {
            return $this->grants;
        }
        if (!$this->active) {
            throw new RuntimeException('Content/metadata read outside read-only transaction.');
        }
        if (str_contains($sql, 'information_schema.TABLES')) {
            $table = $parameters['table_name'];
            return isset($this->tables[$table]) ? [['TABLE_NAME' => $table]] : [];
        }
        if (preg_match('/\ASHOW COLUMNS FROM `([a-z_]+)`\z/', $sql, $match)) {
            $columns = array_keys($this->tables[$match[1]][0]);
            return array_map(static fn (string $column): array => ['Field' => $column], array_values(array_diff($columns, [$this->omitColumn])));
        }
        if (preg_match('/ FROM `([a-z_]+)` WHERE /', $sql, $match)) {
            if ($match[1] === $this->failTable) {
                throw new RuntimeException('synthetic-test-only-secret preview_reader mysql:host=localhost');
            }
            if ($match[1] === 'itinerary_days') {
                return $this->project(array_slice(array_values(array_filter($this->tables[$match[1]], static fn (array $row): bool =>
                    in_array((string) $row['itinerary_id'], $parameters, true))), 0, $maximum), $sql);
            }
            // Fake mimics SQL publication/date filtering, with fixed time for determinism.
            return $this->project(array_slice(array_values(array_filter($this->tables[$match[1]], static fn (array $row): bool =>
                (string) ($row['is_published'] ?? '') === '1'
                && (!isset($row['published_at']) || $row['published_at'] <= '2026-10-22 00:00:00'))), 0, $maximum), $sql);
        }
        throw new RuntimeException('Unexpected fake SQL.');
    }

    public function beginReadOnly(): void
    {
        $this->events[] = ['control' => 'SET TRANSACTION READ ONLY then beginTransaction'];
        if ($this->failBegin) {
            throw new RuntimeException('Synthetic begin failure.');
        }
        $this->active = true;
    }

    public function inTransaction(): bool { return $this->active; }

    public function rollback(): void
    {
        $this->events[] = ['control' => 'rollback'];
        $this->active = false;
        if ($this->failRollback) {
            throw new RuntimeException('synthetic-test-only-secret rollback failure');
        }
    }
}

$newFake = static function () use ($safe): FakeCatalogConnection {
    $fake = new FakeCatalogConnection($safe);
    foreach (catalog_schema() as $table => $definition) {
        $fake->tables[$table] = [[
            'id' => '7', 'slug' => 'published-' . str_replace('_', '-', $table),
            $definition[3] => '<b>Published title</b>', 'excerpt' => 'Published summary',
            'intro' => '<p>Published introduction.</p><script>bad()</script>', 'is_published' => '1',
            'hero_image' => 'assets/uploads/private.jpg', 'og_image' => '',
            'price_from' => '125.00', 'price_currency' => 'USD', 'days' => '3',
            'faq_json' => '[{"q":"Question?","a":"<p>Answer.</p>"}]',
        ]];
    }
    $fake->tables['destinations'][0]['hero_image'] = 'assets/images/galle-lighthouse.w1200.webp';
    $fake->tables['tours'][0]['includes'] = '<ul><li>Published inclusion</li></ul>';
    $fake->tables['tours'][0]['excludes'] = 'Published exclusion';
    $fake->tables['experiences'][0]['tips_json'] = '["A sourced tip", "<b>Another tip</b>"]';
    $fake->tables['blog_posts'][0]['published_at'] = '2026-10-01 00:00:00';
    $fake->tables['blog_posts'][0]['content'] = '<h2>Heading</h2><p>Published story.</p>';
    $fake->tables['blog_posts'][0]['author'] = 'CMS Author';
    $fake->tables['blog_posts'][] = array_replace($fake->tables['blog_posts'][0], ['id' => '8', 'slug' => 'future-post', 'published_at' => '2099-01-01 00:00:00']);
    $fake->tables['blog_posts'][] = array_replace($fake->tables['blog_posts'][0], ['id' => '9', 'slug' => 'draft-post', 'is_published' => '0']);
    $fake->tables['itinerary_days'] = [[
        'itinerary_id' => '7', 'day_number' => '1', 'day_end' => '2', 'title' => 'A sourced day',
        'summary' => '<p>A sourced schedule.</p>', 'overnight_location' => 'CMS Location',
        'travel_time' => 'CMS travel time', 'image_path' => 'assets/uploads/no-access.jpg', 'highlights_json' => '["Sourced highlight"]',
    ], ['itinerary_id' => '999', 'day_number' => '1', 'title' => 'Unpublished parent', 'summary' => 'Never expose']];
    return $fake;
};
$run = static fn (FakeCatalogConnection $fake): array => read_only_catalog_with_factory($config,
    static fn (array $validated): CatalogReadConnection => $fake);
$fake = $newFake();
$state = $run($fake);
$check(array_keys($state) === ['mode', 'source_message', 'catalog'], 'exact public result shape');
$check($state['mode'] === 'database', 'safe fake grants and schema produce database mode');
$check($fake->events[0]['sql'] === 'SHOW GRANTS', 'grant verification is first query');
$check(isset($fake->events[1]['control']), 'read-only transaction begins before metadata and data reads');
$check(end($fake->events)['control'] === 'rollback' && !$fake->active, 'successful read always rolls back');
$check($state['catalog']['reviews'] === [] && $state['catalog']['hero']['image'] === '', 'no invented reviews or hero fallback');
foreach (['destinations', 'journeys', 'tours', 'day_tours', 'experiences', 'articles'] as $collection) {
    $check(count($state['catalog'][$collection]) === 1, 'public collection mapped: ' . $collection);
    $record = $state['catalog'][$collection][0];
    $check($record['demo'] === false && $record['title'] === 'Published title', 'actual title/label instead of samples');
    $check($record['body'][0]['text'] === 'Published introduction.', 'CMS HTML is plain text, scripts removed');
    $expectedFaq = in_array($collection, ['experiences', 'articles'], true) ? [] : [['question' => 'Question?', 'answer' => 'Answer.']];
    $check($record['faq'] === $expectedFaq, 'only mapped FAQ text normalized');
    $check(isset($state['catalog']['records'][$record['family']][$record['slug']]), 'family/slug detail index');
}
$check($state['catalog']['destinations'][0]['image'] === 'assets/images/galle-lighthouse.w1200.webp', 'already-approved source image preserved');
$check($state['catalog']['articles'][0]['image'] === '' && !$state['catalog']['articles'][0]['media_available'], 'unapproved uploads honestly unavailable');
$check($state['catalog']['journeys'][0]['duration_days'] === 3 && count($state['catalog']['journeys'][0]['days']) === 1, 'duration separated from actual timeline');
$check($state['catalog']['journeys'][0]['days'][0]['location'] === 'CMS Location', 'actual overnight mapped without fabrication');
$check($state['catalog']['journeys'][0]['days'][0]['highlights'] === ['Sourced highlight'], 'optional day highlights mapped as plain strings');
$check($state['catalog']['tours'][0]['inclusions'] === ['Published inclusion'] && $state['catalog']['tours'][0]['exclusions'] === ['Published exclusion'], 'current tour controller inclusion/exclusion fields mapped');
$check(end($state['catalog']['experiences'][0]['body']) === ['heading' => 'Tips', 'text' => "A sourced tip\nAnother tip"], 'experience JSON tips normalized to plain text');
$check($state['catalog']['articles'][0]['author'] === 'CMS Author', 'sourced author mapped');
$check($state['catalog']['articles'][0]['date'] === '2026-10-01 00:00:00', 'sourced publication date mapped');
$check(!str_contains(json_encode($state), 'future-post') && !str_contains(json_encode($state), 'draft-post'), 'unpublished and future rows absent');
foreach ($fake->events as $event) {
    if (!isset($event['sql'])) { continue; }
    $sql = $event['sql'];
    $check(!preg_match('/SELECT\s+\*/i', $sql), 'no SELECT star');
    $check(!preg_match('/\b(customers|customer_users|contact_inquiries|messages|site_settings|media_assets)\b/i', $sql), 'no private/settings/media tables');
    $check(preg_match('/\A(?:SELECT|SHOW) /', $sql) === 1 && !str_contains($sql, ';'), 'only single pure read statements');
    if (str_starts_with($sql, 'SELECT ') && str_contains($sql, ' FROM `') && !str_contains($sql, '`itinerary_days`')) {
        $check(str_contains($sql, '`is_published` = 1') && str_contains($sql, 'LIMIT 101') && $event['maximum'] === 101,
            'publication and overflow-sentinel row limit present');
    }
    if (str_starts_with($sql, 'SELECT ') && str_contains($sql, ' FROM `blog_posts`')) {
        $check(str_contains($sql, '`published_at` <= CURRENT_TIMESTAMP'), 'future publication excluded in actual SQL');
    }
    if (str_contains($sql, 'information_schema.TABLES')) {
        $check(str_contains($sql, "TABLE_TYPE = 'BASE TABLE'") && isset($event['parameters']['table_name']), 'views excluded and metadata name bound');
    }
}

foreach ($badGrants as $grants) {
    $fake = new FakeCatalogConnection($grants);
    $state = $run($fake);
    $check($state['mode'] === 'unavailable' && $state['catalog'] === null, 'unsafe account unavailable/null');
    $check(count($fake->events) === 1 && $fake->events[0]['sql'] === 'SHOW GRANTS', 'rejected grants never reach any metadata/content reads or transaction');
}
$fake = new FakeCatalogConnection($safe);
$state = $run($fake);
$check($state['mode'] === 'database' && $state['catalog']['destinations'] === [], 'missing optional tables safely empty, no schema creation');
$fake = $newFake();
$fake->omitColumn = 'is_published';
$state = $run($fake);
$check($state['mode'] === 'database' && $state['catalog']['destinations'] === [] && $state['catalog']['articles'] === [], 'missing publication column never infers published');
$fake = $newFake();
$fake->omitColumn = 'published_at';
$state = $run($fake);
$check($state['mode'] === 'database' && $state['catalog']['articles'] === [], 'blog without future-date control skipped');
$fake = $newFake();
$fake->failTable = 'tours';
$state = $run($fake);
$check($state['mode'] === 'unavailable' && $state['catalog'] === null, 'partial query failure discards all catalog data');
$check(end($fake->events)['control'] === 'rollback' && !$fake->active, 'partial query failure rolls back');
$check(!str_contains(json_encode($state), 'synthetic-test-only-secret') && !str_contains(json_encode($state), 'preview_reader')
    && !str_contains(json_encode($state), 'mysql:host'), 'exception secrets not returned');
$fake = $newFake();
$fake->failBegin = true;
$state = $run($fake);
$check($state['mode'] === 'unavailable' && count($fake->events) === 2, 'transaction setup failure blocks all content');
$fake = $newFake();
$fake->failRollback = true;
$state = $run($fake);
$check($state['mode'] === 'unavailable' && $state['catalog'] === null, 'rollback failure discards success and data');
$fake = $newFake();
$fake->active = true;
$state = $run($fake);
$check($state['mode'] === 'unavailable' && count($fake->events) === 1 && end($fake->events)['control'] === 'rollback', 'pre-existing transaction rejected and cleaned up');
$state = read_only_catalog_with_factory($config, static function (): never { throw new RuntimeException('synthetic-test-only-secret'); });
$check($state['mode'] === 'unavailable' && !str_contains(json_encode($state), 'synthetic-test-only-secret'), 'connection error generic and secret-free');
$factoryConfig = [];
$state = read_only_catalog_with_factory($config + ['DB_PASSWORD' => 'ignored-original-config'], static function (array $received) use (&$factoryConfig, $safe): CatalogReadConnection {
    $factoryConfig = $received;
    return new FakeCatalogConnection($safe);
});
$check(array_keys($factoryConfig) === ['mode', 'dsn', 'user', 'password']
    && $factoryConfig['dsn'] === catalog_dsn($config['NEW_SITE_DB_DSN']), 'factory receives only canonical NEW-SITE configuration');
$fake = new FakeCatalogConnection($safe);
$fake->grants[] = ['unexpected' => $safe[0], 'extra' => 'unknown'];
$state = $run($fake);
$check($state['mode'] === 'unavailable' && count($fake->events) === 1, 'unrecognized grant result shape fails before data reads');
$check(catalog_json_strings('["<b>Text</b>", "", "0"]') === ['Text', '', '0'], 'valid JSON list items retained as plain strings without dropping empty/zero entries');
foreach (['["Text", {"unexpected":"nested"}]', '{"not":"a list"}', '{}', '{"0":"tip"}', 'invalid', '[null]'] as $json) {
    $throws(static fn () => catalog_json_strings($json));
}

// Bounds are acceptance limits. At the boundary content is preserved completely;
// any overflow rejects the whole catalogue and cleans up the read transaction.
foreach ([500, 1000, 2000, 5000, 32768] as $limit) {
    $text = str_repeat('x', $limit);
    $check(catalog_text($text, $limit) === $text, 'text at supported byte bound preserved completely');
    $throws(static fn () => catalog_text($text . 'x', $limit));
}
$unicode = str_repeat('é', 16384);
$check(strlen($unicode) === 32768 && catalog_text($unicode) === $unicode, 'UTF-8 text at byte bound preserved without a split character');
$throws(static fn () => catalog_text($unicode . 'é'));
$check(catalog_projection(['intro'], ['intro']) === 'LEFT(`intro`, 32769) AS `intro`', 'SQL source projection includes overflow character instead of silently clipping');
$json = str_pad(json_encode([str_repeat('j', 2000)], JSON_THROW_ON_ERROR), 32768, ' ');
$check(catalog_json_strings($json) === [str_repeat('j', 2000)], 'valid complete JSON at raw byte bound accepted');
$throws(static fn () => catalog_json_strings($json . ' '));
$throws(static fn () => catalog_json_strings(json_encode([str_repeat('j', 2001)], JSON_THROW_ON_ERROR)));
$check(count(catalog_json_strings(json_encode(array_fill(0, 40, 'tip'), JSON_THROW_ON_ERROR))) === 40, 'complete 40-item JSON list accepted');
$throws(static fn () => catalog_json_strings(json_encode(array_fill(0, 41, 'tip'), JSON_THROW_ON_ERROR)));
$check(count(catalog_lines(implode("\n", array_fill(0, 40, '0')))) === 40, 'all 40 text list items retained, including zero');
$throws(static fn () => catalog_lines(implode("\n", array_fill(0, 41, 'item'))));
$faq = ['question' => str_repeat('q', 500), 'answer' => str_repeat('a', 5000)];
$check(catalog_faq(json_encode([$faq], JSON_THROW_ON_ERROR)) === [$faq], 'FAQ question/answer at field limits preserved');
$check(count(catalog_faq(json_encode(array_fill(0, 30, ['q' => 'Q', 'a' => 'A']), JSON_THROW_ON_ERROR))) === 30, 'complete 30-item FAQ accepted');
foreach (['[{"q":"Question without answer"}]', '["not an FAQ object"]', '[{"q":"Q","a":"A","unknown":"lost content"}]',
    '[{"q":"Q","question":"Different","a":"A"}]', '[{"q":"Q","a":"A"}',
    json_encode([array_replace($faq, ['question' => $faq['question'] . 'q'])], JSON_THROW_ON_ERROR),
    json_encode([array_replace($faq, ['answer' => $faq['answer'] . 'a'])], JSON_THROW_ON_ERROR),
    json_encode(array_fill(0, 31, ['q' => 'Q', 'a' => 'A']), JSON_THROW_ON_ERROR)] as $invalidFaq) {
    $throws(static fn () => catalog_faq($invalidFaq));
}
$check(catalog_json_strings(null) === [] && catalog_json_strings('') === [] && catalog_faq(null) === []
    && catalog_lines(null) === [], 'missing optional lists remain schema-safe');

$assertRejected = static function (FakeCatalogConnection $fake, string $reason) use ($run, $check): void {
    $result = $run($fake);
    $check($result['mode'] === 'unavailable' && $result['catalog'] === null, $reason . ': no partial catalogue or fixture fallback');
    $check(end($fake->events)['control'] === 'rollback' && !$fake->active, $reason . ': rollback attempted');
    $check(!str_contains(json_encode($result), 'Published title') && !str_contains(json_encode($result), 'synthetic-test-only-secret'),
        $reason . ': no partial data/secret details leaked');
};
foreach (catalog_schema() as $table => $definition) {
    $fake = $newFake();
    $prototype = $fake->tables[$table][0];
    $fake->tables[$table] = [];
    for ($index = 1; $index <= 101; $index++) {
        $fake->tables[$table][] = array_replace($prototype, ['id' => (string) $index, 'slug' => 'row-' . $index]);
    }
    $assertRejected($fake, $table . ' collection 101st-row sentinel');
}
$fake = $newFake();
$prototype = $fake->tables['destinations'][0];
$fake->tables['destinations'] = [];
for ($index = 1; $index <= 100; $index++) {
    $fake->tables['destinations'][] = array_replace($prototype, ['id' => (string) $index, 'slug' => 'row-' . $index]);
}
$result = $run($fake);
$check($result['mode'] === 'database' && count($result['catalog']['destinations']) === 100
    && isset($result['catalog']['records']['destination']['row-100']), 'complete catalogue at 100-row bound accepted');
$check(str_contains($result['source_message'], 'Bounded complete-catalogue candidate')
    && str_contains($result['source_message'], 'not original-site parity'), 'source notice explicitly limits completeness/parity claim');

$fake = $newFake();
$prototype = $fake->tables['itineraries'][0];
$fake->tables['itineraries'] = [];
for ($index = 7; $index <= 12; $index++) {
    $fake->tables['itineraries'][] = array_replace($prototype, ['id' => (string) $index, 'slug' => 'journey-' . $index]);
}
$day = $fake->tables['itinerary_days'][0];
$fake->tables['itinerary_days'] = [];
for ($index = 0; $index < 2000; $index++) {
    $fake->tables['itinerary_days'][] = array_replace($day, ['itinerary_id' => (string) (7 + intdiv($index, 365)),
        'day_number' => (string) (1 + $index % 365), 'day_end' => null]);
}
$result = $run($fake);
$check($result['mode'] === 'database' && array_sum(array_map(static fn (array $record): int => count($record['days']),
    $result['catalog']['journeys'])) === 2000, 'complete 2000-row timeline accepted without omitted days');
$timelineQueries = array_values(array_filter($fake->events, static fn (array $event): bool =>
    isset($event['sql']) && str_contains($event['sql'], ' FROM `itinerary_days` WHERE ')));
$check(count($timelineQueries) === 1 && str_contains($timelineQueries[0]['sql'], 'LIMIT 2001')
    && $timelineQueries[0]['maximum'] === 2001, 'timeline SQL and fetch allow overflow sentinel');
$fake->tables['itinerary_days'][] = array_replace($day, ['itinerary_id' => '12', 'day_number' => '176', 'day_end' => null]);
$assertRejected($fake, 'timeline 2001st-row sentinel');

foreach ([['destinations', 'intro', str_repeat('b', 32769)],
    ['destinations', 'intro', str_repeat('b', 65536)], // models server-side cap+1 projection
    ['destinations', 'intro', str_repeat('é', 16385)],
    ['destinations', 'name', str_repeat('n', 501)], ['destinations', 'excerpt', str_repeat('e', 2001)],
    ['destinations', 'meta_title', str_repeat('m', 2001)],
    ['destinations', 'faq_json', '[{"q":"Question","a":'],
    ['destinations', 'faq_json', json_encode(array_fill(0, 31, ['q' => 'Q', 'a' => 'A']), JSON_THROW_ON_ERROR)],
    ['tours', 'includes', implode("\n", array_fill(0, 41, 'inclusion'))],
    ['experiences', 'tips_json', json_encode(array_fill(0, 41, 'tip'), JSON_THROW_ON_ERROR)],
    ['experiences', 'tips_json', '["tip", {"unexpected":"nested content"}]'],
    ['experiences', 'tips_json', str_repeat(' ', 32769)],
    ['itinerary_days', 'summary', str_repeat('s', 32769)],
    ['itinerary_days', 'title', str_repeat('t', 1001)],
    ['itinerary_days', 'overnight_location', str_repeat('l', 501)],
    ['itinerary_days', 'highlights_json', '["incomplete"'],
    ['itinerary_days', 'day_end', '366']] as [$table, $field, $value]) {
    $fake = $newFake();
    $fake->tables[$table][0][$field] = $value;
    $assertRejected($fake, $table . '.' . $field . ' unsupported length/shape');
}
$fake = $newFake();
$bodyAtLimit = str_repeat('body', 8192);
$fake->tables['destinations'][0]['intro'] = $bodyAtLimit;
$fake->tables['destinations'][0]['name'] = str_repeat('n', 500);
$fake->tables['destinations'][0]['excerpt'] = str_repeat('e', 2000);
$result = $run($fake);
$check($result['mode'] === 'database' && $result['catalog']['destinations'][0]['body'][0]['text'] === $bodyAtLimit
    && $result['catalog']['destinations'][0]['title'] === str_repeat('n', 500)
    && $result['catalog']['destinations'][0]['summary'] === str_repeat('e', 2000), 'full mapped source text at each field bound survives SQL projection and mapping');
$fake = $newFake();
$fake->omitColumn = 'intro';
$result = $run($fake);
$check($result['mode'] === 'database' && $result['catalog']['destinations'][0]['body'] === [], 'missing optional source column remains schema-safe');
$fake = $newFake();
$fake->tables['destinations'][0]['slug'] = 'invalid/slug';
$assertRejected($fake, 'unrepresentable record must not disappear silently');
$fake = $newFake();
$fake->tables['destinations'][] = array_replace($fake->tables['destinations'][0], ['id' => '8']);
$assertRejected($fake, 'duplicate record must not overwrite catalogue content');
$check(catalog_image(['hero_image' => 'https://external.invalid/a.jpg']) === '', 'remote media rejected');
$check(catalog_image(['hero_image' => 'assets/uploads/no-access.jpg']) === '', 'uploads not opened or accepted');
$check(catalog_image(['hero_media_type' => 'video', 'hero_image' => 'assets/images/galle-lighthouse.w1200.webp']) === '', 'video assignment not masqueraded as still image');
$check(catalog_text('<p>Text</p><script>never()</script>') === 'Text', 'plain text sanitizer drops active element content');
$check(!str_contains(catalog_text('&lt;img src=x onerror=evil()&gt;Text'), '<'), 'encoded HTML is not returned as markup');
$check(session_status() === PHP_SESSION_NONE, 'no customer/admin session started');
foreach (get_included_files() as $file) {
    $check(str_starts_with(realpath($file), realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR), 'imports stay inside NEW-SITE');
}
echo 'PASS: ' . $checks . ' repository assertions (fake connections only; no live database verified).' . PHP_EOL;
