<?php
declare(strict_types=1);
require_once __DIR__ . '/activity_gallery.php';

function activity_pages_available(): bool
{
    return db_table_exists('activity_pages')
        && db_column_exists('activity_pages', 'slug')
        && db_column_exists('activity_pages', 'is_published');
}

function activity_page_url(array $page): string
{
    return !empty($page['is_hub'])
        ? base_url('activities')
        : base_url('activities/' . rawurlencode((string) ($page['slug'] ?? '')));
}

function activity_hub(bool $publishedOnly = true): ?array
{
    if (!activity_pages_available() || !db_column_exists('activity_pages', 'is_hub')) {
        return null;
    }
    $where = $publishedOnly ? ' AND is_published = 1' : '';
    return fetch_one("SELECT * FROM activity_pages WHERE is_hub = 1{$where} ORDER BY id LIMIT 1");
}

function activity_page_by_slug(string $slug, bool $publishedOnly = true): ?array
{
    $slug = strtolower(trim($slug));
    if (!activity_pages_available() || !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $slug)) {
        return null;
    }
    $where = $publishedOnly ? ' AND is_published = 1' : '';
    return fetch_one("SELECT * FROM activity_pages WHERE slug = :slug AND is_hub = 0{$where} LIMIT 1", ['slug' => $slug]);
}

function activity_published_categories(): array
{
    if (!activity_pages_available()) { return []; }
    return fetch_all('SELECT * FROM activity_pages WHERE is_hub = 0 AND is_published = 1 ORDER BY sort_order, name, id');
}

function activity_examples(int $pageId): array
{
    if ($pageId < 1 || !db_table_exists('activity_examples')) { return []; }
    return fetch_all('SELECT * FROM activity_examples WHERE activity_page_id = :id ORDER BY sort_order, id', ['id' => $pageId]);
}

function activity_json_list(mixed $json): array
{
    if (!is_string($json) || trim($json) === '') { return []; }
    try { $decoded = json_decode($json, true, 16, JSON_THROW_ON_ERROR); }
    catch (JsonException) { return []; }
    return is_array($decoded) ? array_values(array_filter(array_map(
        static fn($value): string => trim((string) $value),
        $decoded
    ), static fn(string $value): bool => $value !== '')) : [];
}

function activity_public_destinations(array $examples): array
{
    if (!db_table_exists('destinations')
        || !db_column_exists('destinations', 'is_published')
        || !db_column_exists('destinations', 'name')
        || !db_column_exists('destinations', 'slug')) { return $examples; }
    $destinations = fetch_all('SELECT id,name,slug FROM destinations WHERE is_published = 1 ORDER BY name');
    $lookup = [];
    foreach ($destinations as $destination) {
        $key = mb_strtolower(trim((string) $destination['name']));
        if ($key !== '') { $lookup[$key] = $destination; }
    }
    foreach ($examples as &$example) {
        $example['destination_items'] = [];
        foreach (activity_json_list($example['destinations_json'] ?? null) as $name) {
            $destination = $lookup[mb_strtolower($name)] ?? null;
            $example['destination_items'][] = ['name' => $name, 'url' => $destination ? base_url('destinations/' . $destination['slug']) : ''];
        }
    }
    unset($example);
    return $examples;
}

function activity_page_image(array $page, string $kind = 'hero'): string
{
    $candidate = match ($kind) {
        'cover' => trim((string) ($page['cover_image'] ?? '')),
        'og' => trim((string) ($page['og_image'] ?? '')),
        default => trim((string) ($page['hero_image'] ?? '')),
    };
    if ($candidate === '') { $candidate = trim((string) ($page['cover_image'] ?? '')); }
    if ($candidate === '') { $candidate = trim((string) ($page['hero_image'] ?? '')); }
    return $candidate === '' ? DEFAULT_OG_IMAGE : stored_media_url($candidate);
}

function activity_admin_date(mixed $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') { return null; }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) { throw new InvalidArgumentException('Enter a valid verification date.'); }
    return $value;
}

function activity_admin_urls(mixed $value, int $limit = 12): array
{
    $lines = is_array($value) ? $value : preg_split('/\R/u', (string) $value);
    $urls = [];
    foreach ($lines ?: [] as $line) {
        $url = trim((string) $line);
        if ($url === '') { continue; }
        if (filter_var($url, FILTER_VALIDATE_URL) === false || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http','https'], true)) {
            throw new InvalidArgumentException('Research sources must be complete http or https URLs.');
        }
        if (!in_array($url, $urls, true)) { $urls[] = $url; }
        if (count($urls) > $limit) { throw new InvalidArgumentException('Too many research sources were supplied.'); }
    }
    return $urls;
}

function activity_admin_examples(array $input, bool $published, string $slug = ''): array
{
    $rows = $input['examples'] ?? [];
    if (!is_array($rows)) { throw new InvalidArgumentException('Activity examples must be submitted as rows.'); }
    if (count($rows)>5) { throw new InvalidArgumentException('A category can have up to five entries.'); }
    $examples = [];
    foreach (array_slice($rows, 0, 5) as $row) {
        if (!is_array($row)) { continue; }
        if (!empty($row['remove'])) { continue; }
        $entryId=filter_var($row['id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        if ($entryId===false) { throw new InvalidArgumentException('Invalid activity entry ID.'); }
        $title = trim(strip_tags((string) ($row['title'] ?? '')));
        $rawDestinations = trim((string) ($row['destinations'] ?? ''));
        if ($title === '' && $rawDestinations === '') { continue; }
        $destinations = array_values(array_unique(array_filter(array_map(
            static fn(string $value): string => trim(strip_tags($value)),
            preg_split('/[,;\r\n]+/u', $rawDestinations) ?: []
        ), static fn(string $value): bool => $value !== '')));
        $sources = activity_admin_urls($row['sources'] ?? '', 8);
        $verified = activity_admin_date($row['last_verified_at'] ?? '');
        if ($title === '' || !$destinations) { throw new InvalidArgumentException('Every example needs a name and at least one destination.'); }
        if (mb_strlen($title) > 255 || count($destinations) > 8) { throw new InvalidArgumentException('An example is too long or has too many destinations.'); }
        if ($published && (!$sources || !$verified)) { throw new InvalidArgumentException('Every published example needs a source URL and a verification date.'); }
        $examples[] = ['id'=>$entryId, 'title' => $title, 'destinations' => $destinations, 'sources' => $sources, 'verified' => $verified];
    }
    $minimum = $slug === 'hot-air-ballooning' ? 1 : 3;
    if (count($examples) > 5 || ($published && count($examples) < $minimum)) {
        throw new InvalidArgumentException($minimum === 1
            ? 'A published ballooning page needs one researched regional activity with its available destinations.'
            : 'A published activity category needs 3 to 5 researched examples.');
    }
    return $examples;
}

function activity_admin_save(array $input): int
{
    if (!db_table_exists('activity_pages') || !db_table_exists('activity_examples')) {
        throw new InvalidArgumentException('Apply the Activities migration before editing activity content.');
    }
    $id = max(0, (int) ($input['id'] ?? 0));
    $existing = $id ? (fetch_one('SELECT * FROM activity_pages WHERE id = :id', ['id' => $id]) ?? throw new InvalidArgumentException('Activity page not found.')) : [];
    $isHub = !empty($existing['is_hub']);
    if (!$id && !empty($input['is_hub'])) { throw new InvalidArgumentException('Create a category first; the Activities hub is created by the content seed.'); }
    $name = trim(strip_tags((string) ($input['name'] ?? '')));
    $slug = strtolower(trim((string) ($input['slug'] ?? '')));
    $slug = trim(preg_replace('/[^a-z0-9-]+/', '-', $slug) ?? '', '-');
    $excerpt = trim(strip_tags((string) ($input['excerpt'] ?? '')));
    $metaTitle = trim(strip_tags((string) ($input['meta_title'] ?? '')));
    $metaDescription = trim(strip_tags((string) ($input['meta_description'] ?? '')));
    $published = !empty($input['is_published']);
    $verifiedAt = activity_admin_date($input['research_verified_at'] ?? '');
    $sources = activity_admin_urls($input['research_sources'] ?? '', 24);
    if ($isHub) { $slug = 'activities'; }
    if ($name === '' || mb_strlen($name) > 180 || $excerpt === '' || mb_strlen($excerpt) > 900) {
        throw new InvalidArgumentException('Enter a name and a plain-language summary of up to 900 characters.');
    }
    if (!$isHub && ($slug === '' || !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $slug) || $slug === 'activities')) {
        throw new InvalidArgumentException('Enter a valid category slug other than activities.');
    }
    if ($metaTitle !== '' && mb_strlen($metaTitle) > 255) { throw new InvalidArgumentException('SEO title must be 255 characters or fewer.'); }
    if ($metaDescription !== '' && mb_strlen($metaDescription) > 320) { throw new InvalidArgumentException('SEO description must be 320 characters or fewer.'); }
    if ($published && ($metaTitle === '' || $metaDescription === '' || !$sources || !$verifiedAt)) {
        throw new InvalidArgumentException('Before publishing, add the page SEO title and description, sources, and a verified date.');
    }
    if (!$isHub) {
        $conflict = fetch_one('SELECT id FROM activity_pages WHERE slug = :slug AND id <> :id LIMIT 1', ['slug' => $slug, 'id' => $id]);
        if ($conflict) { throw new InvalidArgumentException('That category slug is already in use.'); }
    }
    $examples = $isHub ? [] : activity_admin_examples($input, $published, $slug);
    if ($published && !$isHub && (!$existing || empty($existing['hero_image']) || empty($existing['cover_image']) || empty($existing['og_image']) || empty($existing['cover_alt']))) {
        throw new InvalidArgumentException('Select hero, cover, and Open Graph images in Image Manager and add cover alt text before publishing.');
    }
    if ($published && $isHub) {
        $publishedCategories = fetch_one('SELECT COUNT(*) AS total FROM activity_pages WHERE is_hub = 0 AND is_published = 1');
        if ((int) ($publishedCategories['total'] ?? 0) < 3) { throw new InvalidArgumentException('Publish at least three reviewed categories before publishing the Activities hub.'); }
        if (!$existing || empty($existing['hero_image']) || empty($existing['cover_image']) || empty($existing['og_image']) || empty($existing['cover_alt'])) {
            throw new InvalidArgumentException('Select hero, cover, and Open Graph images in Image Manager and add cover alt text before publishing the hub.');
        }
    }
    $connection = db();
    $nested=$connection->inTransaction();
    $nested ? $connection->exec('SAVEPOINT activity_content_save') : $connection->beginTransaction();
    try {
        if ($id) {
            fetch_one('SELECT id FROM activity_pages WHERE id=:id FOR UPDATE',['id'=>$id]);
            if (!is_string($input['revision']??null) || !hash_equals(activity_content_revision($id),$input['revision'])) {
                throw new DomainException('This category, its entries, or its images changed. Review your edits, then reload saved content before trying again.');
            }
        }
        $currentEntries=$id&&!$isHub?activity_examples($id):[];
        $currentIds=array_map(static fn($entry)=>(int)$entry['id'],$currentEntries);
        $kept=[];$titles=[];
        foreach($examples as $example){
            $entryId=$example['id'];
            $titleKey=mb_strtolower($example['title']);
            if(in_array($titleKey,$titles,true)){throw new InvalidArgumentException('Every entry needs a distinct title.');}$titles[]=$titleKey;
            if($entryId && (!in_array($entryId,$currentIds,true)||in_array($entryId,$kept,true))){throw new InvalidArgumentException('An entry is duplicated or belongs to another category.');}
            if($entryId){$kept[]=$entryId;}
            if($published && (!$entryId || !activity_gallery_available() || !activity_gallery_images($entryId))){throw new InvalidArgumentException('Every published entry needs at least one image. Add images through Image Manager, or save this category as a draft.');}
            if($published){foreach(activity_gallery_images($entryId) as $photo){activity_gallery_alt($photo['alt_text']);if(!activity_gallery_file_exists((string)$photo['file_path'])){throw new InvalidArgumentException('An entry image file is missing. Replace it in Image Manager before saving as published.');}}}
        }
        $removed=array_diff($currentIds,$kept);
        try{$confirmed=json_decode((string)($input['confirmed_removals']??'[]'),true,16,JSON_THROW_ON_ERROR);}catch(JsonException){throw new InvalidArgumentException('Invalid removal confirmation.');}
        if(!is_array($confirmed)||!array_is_list($confirmed)){throw new InvalidArgumentException('Invalid removal confirmation.');}
        foreach($removed as $entryId){if(activity_gallery_images($entryId) && !in_array($entryId,$confirmed,true)){throw new InvalidArgumentException('Confirm removal of entries with images. Their files will stay in the Media Library.');}}
        if ($id) {
            execute_query('UPDATE activity_pages SET name=:name,slug=:slug,excerpt=:excerpt,meta_title=:meta_title,meta_description=:meta_description,
                research_sources_json=:sources,research_verified_at=:verified,is_published=:published,sort_order=:sort WHERE id=:id',
                ['name'=>$name,'slug'=>$slug,'excerpt'=>$excerpt,'meta_title'=>$metaTitle,'meta_description'=>$metaDescription,
                    'sources'=>json_encode($sources, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
                    'verified'=>$verifiedAt,'published'=>$published?1:0,'sort'=>max(0,(int)($input['sort_order']??0)),'id'=>$id]);
        } else {
            execute_query('INSERT INTO activity_pages(name,slug,is_hub,excerpt,meta_title,meta_description,research_sources_json,research_verified_at,is_published,sort_order)
                VALUES(:name,:slug,0,:excerpt,:meta_title,:meta_description,:sources,:verified,:published,:sort)',
                ['name'=>$name,'slug'=>$slug,'excerpt'=>$excerpt,'meta_title'=>$metaTitle,'meta_description'=>$metaDescription,
                    'sources'=>json_encode($sources, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
                    'verified'=>$verifiedAt,'published'=>$published?1:0,'sort'=>max(0,(int)($input['sort_order']??0))]);
            $id = (int) $connection->lastInsertId();
        }
        if (!$isHub) {
            foreach($removed as $entryId){execute_query('DELETE FROM activity_examples WHERE id=:entry AND activity_page_id=:page',['entry'=>$entryId,'page'=>$id]);}
            // Allow two existing entries to exchange titles without violating the unique title key mid-save.
            foreach($currentEntries as $entry){if(in_array((int)$entry['id'],$kept,true)){execute_query('UPDATE activity_examples SET title=:title WHERE id=:id',['title'=>'__editing_'.bin2hex(random_bytes(16)),'id'=>(int)$entry['id']]);}}
            foreach ($examples as $index => $example) {
                $params=['id'=>$id,'title'=>$example['title'],
                        'destinations'=>json_encode($example['destinations'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
                        'sources'=>json_encode($example['sources'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
                        'verified'=>$example['verified'],'sort'=>$index];
                if($example['id']){
                    execute_query('UPDATE activity_examples SET title=:title,destinations_json=:destinations,source_urls_json=:sources,last_verified_at=:verified,sort_order=:sort WHERE activity_page_id=:id AND id=:entry',$params+['entry'=>$example['id']]);
                }else{
                    execute_query('INSERT INTO activity_examples(activity_page_id,title,destinations_json,source_urls_json,last_verified_at,sort_order) VALUES(:id,:title,:destinations,:sources,:verified,:sort)',$params);
                }
            }
        }
        $nested ? $connection->exec('RELEASE SAVEPOINT activity_content_save') : $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) { $nested ? $connection->exec('ROLLBACK TO SAVEPOINT activity_content_save') : $connection->rollBack(); }
        throw $error;
    }
    return $id;
}
