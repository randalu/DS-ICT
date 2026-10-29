<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__.'/admin_ai_limits.php';
require_once __DIR__ . '/package_fields.php';
require_once __DIR__.'/page_media.php';
require_once __DIR__ . '/seo_openai.php';

function seo_types(): array
{
    return ['destination' => ['table' => 'destinations', 'title' => 'name', 'route' => 'destinations', 'label' => 'Destinations'],
        'itinerary' => ['table' => 'itineraries', 'title' => 'name', 'route' => 'itineraries', 'label' => 'Itineraries'],
        'tour' => ['table' => 'tours', 'title' => 'name', 'route' => 'tours', 'label' => 'Tour packages'],
        'day_tour' => ['table' => 'day_tours', 'title' => 'name', 'route' => 'day-tours', 'label' => 'Day tours'],
        'activity' => ['table' => 'activity_pages', 'title' => 'name', 'route' => 'activities', 'label' => 'Activities'],
        'experience' => ['table'=>'experiences','title'=>'title','route'=>'experiences','label'=>'Experiences'],
        'page' => ['table'=>'static_pages','title'=>'title','route'=>'','label'=>'Static pages'],
        'blog_landing' => ['table'=>'blog_landing','title'=>'title','route'=>'blog','label'=>'Blog landing'],
        'blog' => ['table' => 'blog_posts', 'title' => 'title', 'route' => 'blog', 'label' => 'Blog posts']];
}
function seo_type(string $type): array
{
    return seo_types()[$type] ?? throw new InvalidArgumentException('Choose a supported content type.');
}
function seo_field_definitions(string $type): array
{
    seo_type($type);
    $fields = ['meta_title' => ['label' => 'SEO title', 'limit' => 255, 'format' => 'text'],
        'meta_description' => ['label' => 'Meta description', 'limit' => 320, 'format' => 'text'],
        'excerpt' => ['label' => 'Excerpt', 'limit' => 500, 'format' => 'text']];
    foreach(['hero_alt','cover_alt','og_alt','hero_poster_alt'] as $alt)$fields[$alt]=['label'=>ucwords(str_replace('_',' ',$alt)),'limit'=>255,'format'=>'text','ai'=>false];
    if($type==='page' || $type==='blog_landing')$fields+=['title'=>['label'=>'Heading','limit'=>$type==='page'?150:250,'format'=>'text'],'intro'=>['label'=>'Introduction','limit'=>30000,'format'=>$type==='blog_landing'?'text':'html'],'cta_title'=>['label'=>'CTA heading','limit'=>255,'format'=>'text'],'cta_text'=>['label'=>'CTA text','limit'=>30000,'format'=>$type==='blog_landing'?'text':'html']];
    if ($type === 'activity') { return $fields; }
    if (isset(package_tables()[$type])) {
        foreach (['highlights','route_summary','best_for','travel_notes','nearby_travel_times','works_well_with','pricing_notes','vehicle_options','sample_itinerary','itinerary','includes','excludes','cta_text'] as $key) {
            $fields[$key] = ['label' => ucwords(str_replace('_', ' ', $key)), 'limit' => 30000, 'format' => 'html'];
        }
        $fields['cta_title'] = ['label' => 'Call to action title', 'limit' => 255, 'format' => 'text'];
        $fields['cover_alt'] = ['label' => 'Cover image description', 'limit' => 255, 'format' => 'text', 'ai' => false];
    }
    return $fields + ($type === 'blog' ? [
        'title' => ['label' => 'Blog title', 'limit' => 255, 'format' => 'text'],
        'content' => ['label' => 'Selected blog body text', 'limit' => 100000, 'format' => 'html']]
        : ['intro' => ['label' => 'Introduction', 'limit' => 30000, 'format' => 'html'],
            'faq_json' => ['label' => 'FAQs (JSON)', 'limit' => 30000, 'format' => 'faq']]);
}
function seo_ready(): bool
{
    foreach (['seo_plans', 'seo_guidance', 'seo_briefs', 'seo_suggestions', 'seo_revisions', 'seo_requests'] as $table) {
        if (!db_table_exists($table)) { return false; }
    }
    return true;
}
function seo_require_ready(): void
{
    if (!seo_ready()) { throw new InvalidArgumentException('SEO migration has not been applied. Ask the owner to apply the reviewed migration.'); }
}
function seo_json(mixed $value): string { return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); }
function seo_record(string $type, int $id, bool $lock = false): array
{
    $table = seo_type($type)['table'];
    if ($id < 1 || !db_table_exists($table)) { throw new InvalidArgumentException('Content is unavailable.'); }
    $record = fetch_one("SELECT * FROM {$table} WHERE id=:id" . ($lock ? ' FOR UPDATE' : ''), ['id' => $id])
        ?? throw new InvalidArgumentException('Content was not found.');
    $record=package_seo_attach($type,$record,$lock);
    if(isset(page_media_types()[$type])){
        $record['_page_gallery']=page_gallery_rows($type,$id);
        foreach($record['_page_gallery'] as $photo)$record['page_gallery_'.$photo['id'].'_alt_text']=(string)$photo['alt_text'];
    }
    return $record;
}
function seo_plan(string $type, int $id): array
{
    return fetch_one('SELECT * FROM seo_plans WHERE content_type=:type AND content_id=:id', ['type' => $type, 'id' => $id]) ?? ['version' => 0, 'plan_json' => '{}', 'reviewed_at' => null];
}
function seo_revision(array $record, array $plan): string
{
    ksort($record);
    return hash('sha256', seo_json([$record, (int) $plan['version']]));
}
/** Mirror the existing public seo_normalize_title without rendering includes/seo.php. */
function seo_display_title(string $title): string
{
    $title = trim(preg_replace('/\s+/', ' ', $title) ?? '');
    if ($title === '' || strcasecmp($title, APP_NAME) === 0) { return APP_NAME; }
    $title = preg_replace('/\s+[—–-]\s+' . preg_quote(APP_NAME, '/') . '$/i', ' | ' . APP_NAME, $title) ?? $title;
    return str_contains(strtolower($title), strtolower(APP_NAME)) ? $title : $title . ' | ' . APP_NAME;
}
function seo_guidance(): array
{
    $row = fetch_one('SELECT * FROM seo_guidance ORDER BY id DESC LIMIT 1');
    return ['id' => $row ? (int) $row['id'] : 0, 'values' => $row ? json_decode($row['guidance_json'], true, 32, JSON_THROW_ON_ERROR)
        : ['tone' => 'Clear, warm, factual British English', 'audience' => 'International visitors seeking private Sri Lanka travel', 'terminology' => 'Private travel; driver-guided itineraries', 'verified_facts' => '', 'prohibited_claims' => 'Unverified prices, licences, insurance, ratings or guarantees']];
}
function seo_latest_brief(string $kind, string $type, int $id, string $market): ?array
{
    $row = fetch_one('SELECT * FROM seo_briefs WHERE kind=:kind AND content_type=:type AND content_id=:id AND market=:market ORDER BY id DESC LIMIT 1', compact('kind', 'type', 'id', 'market'));
    if (!$row) { return null; }
    return ['id' => (int) $row['id'], 'kind' => $kind, 'content_type' => $type, 'content_id' => $id, 'market' => $market,
        'created_at' => $row['created_at'], 'stale' => strtotime($row['created_at']) < time() - 30 * 86400,
        'data' => json_decode($row['brief_json'], true, 32, JSON_THROW_ON_ERROR)];
}
function seo_present(string $type, int $id): array
{
    seo_require_ready();
    $def = seo_type($type); $record = seo_record($type, $id); $plan = seo_plan($type, $id);
    $fields = [];
    foreach (seo_field_definitions($type) as $key => $field) {
        if (array_key_exists($key, $record)) { $fields[$key] = $field + ['value' => (string) ($record[$key] ?? '')]; }
    }
    foreach ($record as $key => $value) {
        $definition = package_seo_definition($key);
        if ($definition) {
            if (preg_match('/\Acoverage_(\d+)_(.+)\z/', $key, $match)) {
                $card = tour_coverage_decode($record['coverage_json'] ?? null)[(int) $match[1]] ?? [];
                $definition['label'] = 'Coverage ' . ((int) $match[1] + 1) . ' · ' . ($card['location'] ?? '') . ' — ' . ucwords(str_replace('_', ' ', $match[2]));
            }
            if (preg_match('/\Aday_(\d+)_(.+)\z/', $key, $match)) {
                foreach ($record['_days'] ?? [] as $day) { if ((int) $day['id'] === (int) $match[1]) { $definition['label'] = 'Day ' . $day['day_number'] . ' · ' . $day['title'] . ' — ' . ucwords(str_replace('_', ' ', $match[2])); break; } }
            }
            if (preg_match('/\Agallery_(\d+)_alt_text\z/', $key, $match)) { $definition['label'] = 'Gallery photo #' . $match[1] . ' description'; }
            if (preg_match('/\Aguide_(\d+)_(informational_label|package_label)\z/', $key, $match)) {
                foreach ($record['_guides'] ?? [] as $guide) { if ((int) $guide['id'] === (int) $match[1]) { $definition['label'] = $guide['informational_path'] . ' — ' . ($match[2] === 'informational_label' ? 'Guide link wording' : 'Tour link wording'); break; } }
            }
            $fields[$key] = $definition + ['value' => (string) $value];
        }
    }
    $title = (string) $record[$def['title']]; $keywordPlan = json_decode($plan['plan_json'], true, 32, JSON_THROW_ON_ERROR);
    $fallbackTitle = $title . ($type === 'destination' ? ' Travel Guide' : '') . ' | ' . APP_NAME;
    $description = (string) ($record['meta_description'] ?? '') ?: ((string) ($record['excerpt'] ?? '') ?: match ($type) {
        'destination' => 'Discover ' . $title . ' with private Sri Lanka tour planning.',
        'itinerary' => 'Explore the ' . $title . ' with Island Culture Travels.',
        'tour' => 'Plan a private Sri Lanka tour with Island Culture Travels.',
        'activity' => 'Discover ' . $title . ' activities and destinations in Sri Lanka.',
        default => '',
    });
    $url = $type === 'activity' && !empty($record['is_hub'])
        ? base_url('activities')
        : base_url($def['route'] . '/' . $record['slug']);
    if($type==='page')$url=static_page_public_url((string)$record['slug'],(string)($record['canonical_url']??''));
    if($type==='blog_landing')$url=base_url('blog');
    $editorLinks = isset(package_tables()[$type]) ? package_editor_links($type, $id) : [];
    if (in_array($type,['experience','page','blog_landing'],true)) {$editorLinks=[['label'=>'Full editor','url'=>base_url('admin/'.page_media_definition($type)[2].'?id='.$id)],['label'=>'Images','url'=>base_url('admin/image_manager.php?type='.$type.'&id='.$id)],['label'=>'Site Audit','url'=>base_url('admin/site_audit.php')]];}
    if ($type === 'activity') {
        $editorLinks = [
            ['label' => 'Activity editor', 'url' => base_url('admin/activity_form.php?id=' . $id)],
            ['label' => 'Image Manager', 'url' => base_url('admin/image_manager.php?type=activity&id=' . $id)],
        ];
    }
    return ['type' => $type, 'id' => $id, 'title' => $title, 'published' => (bool) ($record['is_published'] ?? false),
        'url' => $url, 'fields' => $fields, 'plan' => $keywordPlan,
        'editor_links' => $editorLinks,
        'context' => isset(package_tables()[$type]) ? package_field_inventory($type, $id) : [],
        'revision' => seo_revision($record, $plan), 'reviewed_at' => $plan['reviewed_at'],
        'rendered' => ['title' => seo_display_title((string) ($record['meta_title'] ?? '') ?: $fallbackTitle), 'description' => $description],
        'brief' => seo_latest_brief('keywords', $type, $id, (string) ($keywordPlan['market'] ?? 'International')),
        'revisions' => fetch_all('SELECT id,admin_id,policy_version,created_at FROM seo_revisions WHERE content_type=:type AND content_id=:id ORDER BY id DESC LIMIT 20', ['type' => $type, 'id' => $id]),
        'overlap' => seo_overlap($type, $id, (string) ($keywordPlan['primary_phrase'] ?? ''))];
}
function seo_list(array $input): array
{
    $type = (string) ($input['type'] ?? 'destination'); $def = seo_type($type); $table = $def['table'];
    if (!db_table_exists($table)) { return ['items' => [], 'more' => false, 'page' => 1, 'notice' => 'This content collection is unavailable.']; }
    $where = ['1=1']; $params = []; $title = $def['title'];
    $q = mb_substr(trim((string) ($input['q'] ?? '')), 0, 100);
    if ($q !== '') { $where[] = "c.{$title} LIKE :q"; $params['q'] = '%' . $q . '%'; }
    if (in_array($input['published'] ?? '', ['0', '1'], true) && db_column_exists($table, 'is_published')) {
        $where[] = 'c.is_published=:published'; $params['published'] = (int) $input['published'];
    }
    if (($input['missing'] ?? '') === '1') {
        $missing = [];
        foreach (['meta_title', 'meta_description'] as $column) {
            $missing[] = db_column_exists($table, $column) ? "COALESCE(TRIM(c.{$column}),'')=''" : '1=1';
        }
        $where[] = '(' . implode(' OR ', $missing) . ')';
    }
    $join = ''; $reviewed = 'NULL';
    if (seo_ready()) {
        $join = ' LEFT JOIN seo_plans p ON p.content_type=:type AND p.content_id=c.id'; $params['type'] = $type; $reviewed = 'p.reviewed_at';
        if (($input['review'] ?? '') === 'never') { $where[] = 'p.reviewed_at IS NULL'; }
        elseif (($input['review'] ?? '') === 'old') { $where[] = '(p.reviewed_at IS NULL OR p.reviewed_at < DATE_SUB(NOW(), INTERVAL 30 DAY))'; }
    }
    $page = max(1, min(100000, (int) ($input['page'] ?? 1))); $offset = ($page - 1) * 20;
    $published = db_column_exists($table, 'is_published') ? 'c.is_published' : '0';
    $rows = fetch_all("SELECT c.id,c.{$title} AS title,{$published} AS is_published,{$reviewed} AS reviewed_at FROM {$table} c{$join} WHERE " . implode(' AND ', $where) . " ORDER BY c.{$title},c.id LIMIT 21 OFFSET {$offset}", $params);
    return ['items' => array_slice($rows, 0, 20), 'more' => count($rows) > 20, 'page' => $page];
}
function seo_overlap(string $type, int $id, string $phrase): array
{
    if ($phrase === '') { return []; }
    $matches = [];
    foreach (fetch_all('SELECT content_type,content_id,plan_json FROM seo_plans WHERE NOT(content_type=:type AND content_id=:id)', compact('type', 'id')) as $row) {
        $plan = json_decode($row['plan_json'], true);
        if (mb_strtolower(trim((string) ($plan['primary_phrase'] ?? ''))) === mb_strtolower(trim($phrase))) { $matches[] = ['type' => $row['content_type'], 'id' => (int) $row['content_id']]; }
        if (count($matches) >= 20) { break; }
    }
    return $matches;
}
function seo_validate_plan(array $plan): array
{
    $result = [];
    foreach (['primary_phrase' => 180, 'supporting_phrases' => 2000, 'intent' => 500, 'questions' => 3000, 'market' => 100, 'notes' => 3000] as $key => $limit) {
        if (!is_string($plan[$key] ?? '')) { throw new InvalidArgumentException('Keyword plan fields must be text.'); }
        $result[$key] = trim($plan[$key] ?? '');
        if (mb_strlen($result[$key]) > $limit || str_contains($result[$key], "\0")) { throw new InvalidArgumentException('Keyword plan field is too long or invalid.'); }
    }
    $result['market'] = $result['market'] ?: 'International';
    return $result;
}
function seo_safe_html(string $html): string
{
    if (!class_exists(DOMDocument::class)) { throw new RuntimeException('PHP DOM is required for safe rich text.'); }
    $doc = new DOMDocument('1.0', 'UTF-8'); $previous = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="seo-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors(); libxml_use_internal_errors($previous);
    $root = $doc->getElementById('seo-root');
    if (!$root) { throw new InvalidArgumentException('Invalid rich text.'); }
    $clean = function (DOMNode $parent) use (&$clean): void {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) { $parent->removeChild($child); continue; }
            if (!$child instanceof DOMElement) { continue; }
            if (in_array(strtolower($child->tagName), ['script','style','iframe','object','svg','math','form','input','button','textarea','template'], true)) { $parent->removeChild($child); continue; }
            $clean($child);
            $tag = strtolower($child->tagName);
            if (!in_array($tag, ['p','br','h2','h3','ul','ol','li','strong','em','a','blockquote'], true)) {
                while ($child->firstChild) { $parent->insertBefore($child->firstChild, $child); } $parent->removeChild($child); continue;
            }
            $href = $child->getAttribute('href');
            foreach (iterator_to_array($child->attributes) as $attribute) { $child->removeAttribute($attribute->name); }
            if ($tag === 'a' && filter_var($href, FILTER_VALIDATE_URL) && in_array(parse_url($href, PHP_URL_SCHEME), ['http','https'], true) && parse_url($href, PHP_URL_HOST) === parse_url(base_url(), PHP_URL_HOST)) { $child->setAttribute('href', $href); }
        }
    };
    $clean($root); $result = '';
    foreach ($root->childNodes as $child) { $result .= $doc->saveHTML($child); }
    return $result;
}
function seo_validate_field(string $type, string $field, mixed $value): string
{
    $def = seo_field_definitions($type)[$field] ?? package_seo_definition($field) ?? throw new InvalidArgumentException('This field cannot be changed in SEO Manager.');
    if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || mb_strlen($value) > $def['limit'] || str_contains($value, "\0")) { throw new InvalidArgumentException('Field exceeds its length limit or contains invalid text.'); }
    $value = trim($value);
    if ($field === 'title' && $value === '') { throw new InvalidArgumentException('Blog title cannot be empty.'); }
    if (preg_match('/\Agallery_\d+_alt_text\z/', $field) && $value === '') { throw new InvalidArgumentException('Describe the gallery photo.'); }
    if (preg_match('/\Aguide_\d+_(informational_label|package_label)\z/', $field) && $value === '') { throw new InvalidArgumentException('Planning guide link wording cannot be empty.'); }
    if ($def['format'] === 'text' && $value !== strip_tags($value)) { throw new InvalidArgumentException('Metadata and summaries must be plain text.'); }
    if ((preg_match('/\Acoverage_\d+_(title|description)\z/', $field) || preg_match('/\Aday_\d+_title\z/', $field)) && $value === '') { throw new InvalidArgumentException('Card and day titles and descriptions cannot be empty.'); }
    if ($def['format'] === 'list' && $value !== '') {
        try { $list = json_decode($value, true, 16, JSON_THROW_ON_ERROR); } catch (JsonException) { throw new InvalidArgumentException('Highlights must be a JSON list of text.'); }
        if (!is_array($list) || !array_is_list($list) || count($list) > 40) { throw new InvalidArgumentException('Use up to 40 highlights.'); }
        foreach ($list as $item) { if (!is_string($item) || mb_strlen($item) > 500 || strip_tags($item) !== $item) { throw new InvalidArgumentException('Highlights must be plain text.'); } }
        return seo_json($list);
    }
    if ($def['format'] === 'html') { return seo_safe_html($value); }
    if ($def['format'] === 'faq' && $value !== '') {
        try { $faqs = json_decode($value, true, 16, JSON_THROW_ON_ERROR); }
        catch (JsonException) { throw new InvalidArgumentException('FAQs must be valid JSON.'); }
        if (!str_starts_with(ltrim($value), '[') || !is_array($faqs) || !array_is_list($faqs) || count($faqs) > 30) { throw new InvalidArgumentException('FAQs must be a list of up to 30 question/answer pairs.'); }
        foreach ($faqs as $faq) {
            if (!is_array($faq) || (!in_array(array_keys($faq), [['question','answer'],['answer','question'],['q','a'],['a','q']], true))) { throw new InvalidArgumentException('Each FAQ needs question and answer only (question/answer or q/a).'); }
            foreach (isset($faq['q']) ? ['q','a'] : ['question','answer'] as $key) {
                if (!is_string($faq[$key] ?? null) || trim($faq[$key]) === '' || mb_strlen($faq[$key]) > 3000 || strip_tags($faq[$key]) !== $faq[$key]) { throw new InvalidArgumentException('FAQ questions and answers must contain plain text.'); }
            }
        }
        return seo_json($faqs);
    }
    return $value;
}
function seo_transaction(callable $operation): mixed
{
    $pdo = db(); $nested = $pdo->inTransaction();
    $nested ? $pdo->exec('SAVEPOINT seo_save') : $pdo->beginTransaction();
    try {
        $value = $operation();
        $nested ? $pdo->exec('RELEASE SAVEPOINT seo_save') : $pdo->commit(); return $value;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $nested ? $pdo->exec('ROLLBACK TO SAVEPOINT seo_save') : $pdo->rollBack(); } throw $e;
    }
}
function seo_body_patch(string $original, mixed $patch): string
{
    if (!is_array($patch) || !is_string($patch['selection'] ?? null) || !is_string($patch['replacement'] ?? null)) { throw new InvalidArgumentException('Blog body saves require a selected text passage and replacement.'); }
    $selection = $patch['selection']; $replacement = $patch['replacement'];
    if ($selection === '' || mb_strlen($selection) > 12000 || mb_strlen($replacement) > 12000 || substr_count($original, $selection) !== 1 || strpbrk($selection, '<>') !== false) { throw new InvalidArgumentException('Select one unique text passage without HTML tags. Other body content will be preserved.'); }
    $offset = strpos($original, $selection); $prefix = substr($original, 0, $offset);
    $open = strrpos($prefix, '<'); $close = strrpos($prefix, '>');
    if ($open !== false && ($close === false || $open > $close)) { throw new InvalidArgumentException('Select body text, not an HTML attribute.'); }
    foreach (['script','style','iframe','object','svg','math','template','textarea'] as $tag) {
        $opened = strripos($prefix, '<' . $tag); $closed = strripos($prefix, '</' . $tag);
        if ($opened !== false && ($closed === false || $opened > $closed)) { throw new InvalidArgumentException('This embedded content cannot be edited as a text passage.'); }
    }
    $result = $prefix . seo_safe_html($replacement) . substr($original, $offset + strlen($selection));
    if (mb_strlen($result) > 100000) { throw new InvalidArgumentException('Blog body is too long.'); }
    return $result;
}
function seo_save(string $type, int $id, string $revision, array $values, ?array $newPlan, int $admin, array $suggestions = [], bool $restoring = false): array
{
    seo_require_ready(); $table = seo_type($type)['table'];
    // Audit and content updates must have the same rollback guarantee.
    $tables = [$table, 'seo_plans', 'seo_revisions'];
    foreach (array_keys($values) as $field) {
        if (preg_match('/\Aday_\d+_/', (string) $field)) { $tables[] = 'itinerary_days'; }
        if (preg_match('/\Agallery_\d+_/', (string) $field)) { $tables[] = 'day_tour_gallery'; }
        if(str_starts_with((string)$field,'page_gallery_'))$tables[]=page_gallery_storage($type)[0];
    }
    foreach (array_unique($tables) as $name) {
        $engine = fetch_one('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:name', ['name' => $name]);
        if (strcasecmp((string) ($engine['ENGINE'] ?? ''), 'InnoDB') !== 0) { throw new InvalidArgumentException('SEO saves require transactional InnoDB tables.'); }
    }
    if (!$values && $newPlan === null) { throw new InvalidArgumentException('Select at least one field or keyword plan to save.'); }
    seo_transaction(function () use ($type, $id, $revision, $values, $newPlan, $admin, $table, $suggestions, $restoring): void {
        $record = seo_record($type, $id, true); $plan = seo_plan($type, $id);
        if (!hash_equals(seo_revision($record, $plan), $revision)) { throw new DomainException('This record changed. Reload and compare before saving.'); }
        $before = []; $after = []; $provenance = [];
        foreach ($values as $field => $value) {
            if (!array_key_exists($field, $record)) { throw new InvalidArgumentException('This field is unavailable in this database.'); }
            seo_field_definitions($type)[$field] ?? package_seo_definition($field) ?? throw new InvalidArgumentException('Unsupported field.');
            if ($field === 'content') {
                $after[$field] = $restoring ? $value : seo_body_patch((string) $record[$field], $value);
            } else {
                $validated = seo_validate_field($type, $field, $value ?? ($restoring ? '' : null));
                $after[$field] = $restoring && $value === null ? null : $validated;
            }
            $before[$field] = $record[$field];
            if ($field === 'cover_alt' && !empty($record['cover_image']) && trim((string) $after[$field]) === '') { throw new InvalidArgumentException('Describe the assigned cover image.'); }
            if (preg_match('/\Acoverage_(\d+)_image_alt\z/', $field, $match) && trim((string) $after[$field]) === '') {
                $card = tour_coverage_decode($record['coverage_json'] ?? null)[(int) $match[1]] ?? [];
                if (!empty($card['image_asset_id'])) { throw new InvalidArgumentException('Describe the assigned coverage image.'); }
            }
            if (isset($suggestions[$field])) {
                $suggestion = fetch_one('SELECT * FROM seo_suggestions WHERE id=:id AND admin_id=:admin AND content_type=:type AND content_id=:content AND field_name=:field',
                    ['id' => (int) $suggestions[$field], 'admin' => $admin, 'type' => $type, 'content' => $id, 'field' => $field]);
                if (!$suggestion || !hash_equals($suggestion['source_revision'], $revision)) { throw new DomainException('Suggestion no longer matches this record. Generate again.'); }
                $provenance[$field] = ['suggestion_id' => (int) $suggestion['id'], 'guidance_id' => $suggestion['guidance_id'], 'policy' => $suggestion['policy_version'],
                    'edited' => $after[$field] !== (json_decode($suggestion['suggestion_json'], true)['replacement'] ?? '')];
            }
        }
        if ($newPlan !== null) { $before['_plan'] = json_decode($plan['plan_json'], true); $after['_plan'] = $restoring ? $newPlan : seo_validate_plan($newPlan); }
        foreach (array_keys($after) as $field) {
            if (package_seo_definition($field)) { $before['_package_identity'] = $after['_package_identity'] = package_seo_identity($record); break; }
        }
        package_seo_write($type, $record, $after);
        $planJson = isset($after['_plan']) ? seo_json($after['_plan']) : $plan['plan_json'];
        execute_query('INSERT INTO seo_plans(content_type,content_id,plan_json,version,reviewed_at,updated_at) VALUES(:type,:id,:plan,1,NOW(),NOW()) ON DUPLICATE KEY UPDATE plan_json=VALUES(plan_json),version=version+1,reviewed_at=NOW(),updated_at=NOW()', ['type' => $type, 'id' => $id, 'plan' => $planJson]);
        execute_query('INSERT INTO seo_revisions(admin_id,content_type,content_id,before_json,after_json,policy_version,provenance_json,created_at) VALUES(:admin,:type,:id,:before,:after,:policy,:provenance,NOW())',
            ['admin' => $admin, 'type' => $type, 'id' => $id, 'before' => seo_json($before), 'after' => seo_json($after), 'policy' => SEO_POLICY_VERSION, 'provenance' => seo_json($provenance)]);
    });
    return seo_present($type, $id);
}
function seo_restore(string $type, int $id, string $revision, int $revisionId, int $admin): array
{
    $row = fetch_one('SELECT before_json FROM seo_revisions WHERE id=:rid AND content_type=:type AND content_id=:id', ['rid' => $revisionId, 'type' => $type, 'id' => $id]);
    if (!$row) { throw new InvalidArgumentException('Revision does not belong to this record.'); }
    $values = json_decode($row['before_json'], true, 32, JSON_THROW_ON_ERROR); $plan = $values['_plan'] ?? null; unset($values['_plan']);
    if (isset($values['_package_identity'])) {
        if (!hash_equals((string) $values['_package_identity'], package_seo_identity(seo_record($type, $id)))) { throw new DomainException('Package structure changed. Review the saved wording manually before restoring it.'); }
        unset($values['_package_identity']);
    }
    return seo_save($type, $id, $revision, $values, is_array($plan) ? $plan : null, $admin, [], true);
}

function seo_connection(): ?array
{
    unset($_SESSION['_ict_seo_connection']);
    $key = admin_openai_key();
    return $key === '' ? null : ['key'=>$key,'nonce'=>admin_openai_version()];
}
function seo_revalidate_session(int $admin, string $stamp, string $nonce): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    $row = fetch_one('SELECT id,password_hash,is_active FROM admins WHERE id=:id', ['id' => $admin]);
    $connection = seo_connection();
    if (!$row || !(bool) $row['is_active'] || admin_id() !== $admin || !hash_equals($stamp, (string) ($_SESSION[ADMIN_CREDENTIAL_STAMP_KEY] ?? '')) || !admin_session_credentials_match($_SESSION, $admin, $row['password_hash']) || !$connection || !hash_equals($nonce, (string) ($connection['nonce'] ?? ''))) {
        unset($_SESSION['_ict_seo_connection']); throw new DomainException('Your login or server OpenAI configuration changed. Reload and review before retrying.');
    }
}
function seo_usage(int $admin): array
{
    $rows = fetch_all('SELECT kind,COUNT(*) AS requests,COALESCE(SUM(input_tokens),0) AS input_tokens,COALESCE(SUM(output_tokens),0) AS output_tokens FROM seo_requests WHERE admin_id=:admin AND created_at>=CURRENT_DATE GROUP BY kind', ['admin' => $admin]);
    return ['today' => $rows, 'generation_limit' => seo_limit('generation'), 'research_limit' => seo_limit('research'), 'model' => admin_openai_model('seo'), 'shared_usage'=>admin_openai_usage($admin), 'models'=>['alt'=>admin_openai_model('alt'),'seo'=>admin_openai_model('seo'),'audit'=>admin_openai_model('audit')], 'connected' => seo_connection() !== null];
}
function seo_limit(string $kind): int
{
    return admin_ai_request_limit($kind);
}
/** DB advisory lock spans slow API calls while the PHP session lock is released. */
function seo_paid_request(string $kind, string $token, callable $work, ?callable $transport = null, string $feature = 'seo'): mixed
{
    seo_require_ready();
    $connection = seo_connection();
    if (!$connection) { throw new InvalidArgumentException('The server OpenAI key is not configured. Ask the server administrator to configure OPENAI_ADMIN_API_KEY.'); }
    if (!preg_match('/^[a-zA-Z0-9-]{16,64}$/D', $token)) { throw new InvalidArgumentException('Invalid request identifier.'); }
    $admin = (int) admin_id(); $stamp = (string) $_SESSION[ADMIN_CREDENTIAL_STAMP_KEY];
    $lock = 'ict-seo-' . substr(hash('sha256', DB_NAME . ':' . $admin), 0, 40);
    if ((int) (fetch_one('SELECT GET_LOCK(:name,0) AS acquired', ['name' => $lock])['acquired'] ?? 0) !== 1) { throw new DomainException('Another SEO request is running. Wait for it to finish.'); }
    $requestId = 0; $sharedId = 0; $usage = ['input' => 0, 'output' => 0];
    try {
        if (fetch_one('SELECT id FROM seo_requests WHERE admin_id=:admin AND request_token=:token', compact('admin','token'))) { throw new DomainException('This request was already submitted. Review its outcome before retrying.'); }
        $count = (int) (fetch_one('SELECT COUNT(*) AS n FROM seo_requests WHERE admin_id=:admin AND kind=:kind AND created_at>=CURRENT_DATE', compact('admin','kind'))['n'] ?? 0);
        if ($count >= seo_limit($kind)) { throw new InvalidArgumentException('Daily SEO request limit reached.'); }
        execute_query("INSERT INTO seo_requests(admin_id,request_token,kind,status,created_at) VALUES(:admin,:token,:kind,'running',NOW())", compact('admin','token','kind'));
        $requestId = (int) db()->lastInsertId();
        $sharedId = admin_openai_begin($feature, $token, $admin);
        session_write_close();
        $call = static function (array $payload) use ($connection, $requestId, &$usage, $transport, $feature): array {
            $payload['model']=admin_openai_model($feature);
            $result = admin_openai_response($connection['key'], $payload, $transport);
            $usage['input'] += $result['usage']['input']; $usage['output'] += $result['usage']['output'];
            execute_query('UPDATE seo_requests SET input_tokens=:input,output_tokens=:output WHERE id=:id', $usage + ['id' => $requestId]);
            return $result;
        };
        $result = $work($call, static fn() => seo_revalidate_session($admin, $stamp, $connection['nonce']));
        seo_revalidate_session($admin, $stamp, $connection['nonce']);
        execute_query("UPDATE seo_requests SET status='completed',finished_at=NOW() WHERE id=:id", ['id' => $requestId]);
        admin_openai_finish($sharedId, 'completed', $usage);
        return $result;
    } catch (Throwable $e) {
        if ($sharedId) { admin_openai_finish($sharedId,'failed_or_unknown',$usage); }
        if ($requestId) { execute_query("UPDATE seo_requests SET status='failed_or_unknown',finished_at=NOW() WHERE id=:id", ['id' => $requestId]); }
        throw $e;
    } finally {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        execute_query('DO RELEASE_LOCK(:name)', ['name' => $lock]);
    }
}
