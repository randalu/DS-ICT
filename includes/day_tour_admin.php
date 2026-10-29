<?php
declare(strict_types=1);
require_once __DIR__ . '/image_manager.php';

/** Existing day_tours columns only; optional fields are never installed by the editor. */
function day_tour_admin_fields(): array
{
    return [
        'Content' => ['name'=>['Name','text',150], 'slug'=>['Slug','text',180], 'excerpt'=>['Excerpt','textarea',500],
            'intro'=>['Introduction','richtext'], 'highlights'=>['Highlights','richtext'], 'itinerary'=>['Day itinerary','richtext'],
            'includes'=>['Includes','richtext'], 'excludes'=>['Excludes','richtext']],
        'Schedule and transport' => ['meeting_point'=>['Meeting point','text',255], 'duration_hours'=>['Duration (hours)','number',null,'0.5'],
            'start_time'=>['Start time','time'], 'vehicle_type'=>['Vehicle type','text',80], 'max_group_size'=>['Maximum group size','number'],
            'difficulty'=>['Difficulty','select'], 'best_time'=>['Best time','text',120], 'best_time_json'=>['Best time details (JSON)','json']],
        'Pricing' => ['price_from'=>['Price from','number',null,'0.01'], 'price_to'=>['Price to','number',null,'0.01'],
            'price_currency'=>['Currency','text',3], 'price_unit'=>['Price unit','text',80]],
        'SEO and FAQs' => ['meta_title'=>['Meta title','text',255], 'meta_description'=>['Meta description','textarea',320],
            'canonical_url'=>['Canonical URL','url',255], 'faq_json'=>['FAQs (JSON)','json']],
    ];
}
function day_tour_admin_relations(): array
{
    return ['destinations'=>['day_tour_destination','destination_id','destinations','name','Related destinations'],
        'itineraries'=>['day_tour_itinerary','itinerary_id','itineraries','name','Related itineraries'],
        'experiences'=>['day_tour_experience','experience_id','experiences','title','Related experiences']];
}
function day_tour_admin_relation_available(array $definition): bool
{
    [$pivot,$foreign,$table] = $definition;
    return db_table_exists($pivot) && db_table_exists($table) && db_column_exists($pivot,'day_tour_id') && db_column_exists($pivot,$foreign);
}
function day_tour_admin_record(int $id, bool $lock = false): array
{
    return image_manager_record('day_tour', $id, $lock);
}
function day_tour_admin_revision(array $record): string
{
    $state = [$record];
    foreach (day_tour_admin_relations() as $definition) {
        if (!day_tour_admin_relation_available($definition)) { continue; }
        [$pivot,$foreign] = $definition;
        $state[] = fetch_all("SELECT * FROM {$pivot} WHERE day_tour_id=:id ORDER BY {$foreign}", ['id'=>(int)($record['id']??0)]);
    }
    return hash_hmac('sha256',json_encode($state,JSON_THROW_ON_ERROR),csrf_token());
}
function day_tour_admin_save(array $input, array $files = []): int
{
    foreach (['id','revision','flags_present','is_featured','is_published','sort_order'] as $field) {
        if (isset($input[$field]) && !is_string($input[$field]) && !is_int($input[$field])) { throw new InvalidArgumentException('Invalid form fields.'); }
    }
    if (filter_var($input['id']??0,FILTER_VALIDATE_INT)===false || (int)($input['id']??0)<0) { throw new InvalidArgumentException('Invalid day tour identifier.'); }
    foreach (['is_featured','is_published'] as $field) {
        if (isset($input[$field]) && !in_array((string)$input[$field],['0','1'],true)) { throw new InvalidArgumentException('Invalid publication flags.'); }
    }
    $id = (int)($input['id']??0);
    $before = $id ? day_tour_admin_record($id) : [];
    $data = [];
    foreach (day_tour_admin_fields() as $fields) {
        foreach ($fields as $field=>[$label,$kind]) {
            if (!db_column_exists('day_tours',$field) || !array_key_exists($field,$input)) { continue; }
            if (!is_string($input[$field])) { throw new InvalidArgumentException('Invalid '.$label.'.'); }
            $value = trim($input[$field]);
            $limit = $fields[$field][2]??null;
            if ($limit && mb_strlen($value)>$limit) { throw new InvalidArgumentException($label.' is too long.'); }
            if (mb_strlen($value)>200000) { throw new InvalidArgumentException($label.' is too long.'); }
            if ($kind==='json' && $value!=='') {
                try { $json=json_decode($value,true,512,JSON_THROW_ON_ERROR); }
                catch (JsonException) { throw new InvalidArgumentException($label.' must be valid JSON.'); }
                if (!is_array($json)) { throw new InvalidArgumentException($label.' must contain a JSON array or object.'); }
            }
            if ($kind==='number') {
                if ($value==='' && in_array($field,['price_from','price_to'],true)) { $value=null; }
                elseif (!is_numeric($value) || !is_finite((float)$value) || (float)$value<0) { throw new InvalidArgumentException($label.' must be a nonnegative number.'); }
            }
            $data[$field]=$value;
        }
    }
    $name = (string)($data['name']??$before['name']??'');
    if ($name==='') { throw new InvalidArgumentException('Name is required.'); }
    $data['name']=$name;
    $rawSlug=(string)($data['slug']??$before['slug']??'');
    $data['slug']=slugify($rawSlug!==''?$rawSlug:$name);
    if (mb_strlen($data['slug'])>180) { throw new InvalidArgumentException('Slug is too long.'); }
    if (isset($data['canonical_url'])) {
        if ($data['canonical_url']==='') { $data['canonical_url']=base_url('day-tours/'.$data['slug']); }
        elseif (!filter_var($data['canonical_url'],FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($data['canonical_url'],PHP_URL_SCHEME)),['http','https'],true)) { throw new InvalidArgumentException('Canonical URL must use http or https.'); }
    }
    if (isset($data['price_currency'])) {
        $data['price_currency']=strtoupper($data['price_currency']);
        if (!preg_match('/^[A-Z]{3}$/D',$data['price_currency'])) { throw new InvalidArgumentException('Use a three-letter currency code.'); }
    }
    foreach (['price_from','price_to'] as $field) { if (isset($data[$field]) && (float)$data[$field]>99999999.99) { throw new InvalidArgumentException('Price exceeds the supported range.'); } }
    $from=array_key_exists('price_from',$data)?$data['price_from']:($before['price_from']??null);$to=array_key_exists('price_to',$data)?$data['price_to']:($before['price_to']??null);
    if ($from!==null && $to!==null && (float)$to<(float)$from) { throw new InvalidArgumentException('Price to must be at least price from.'); }
    if (isset($data['duration_hours']) && ((float)$data['duration_hours']<0.5 || (float)$data['duration_hours']>999.9)) { throw new InvalidArgumentException('Duration must be between 0.5 and 999.9 hours.'); }
    if (isset($data['max_group_size']) && (!ctype_digit($data['max_group_size']) || (int)$data['max_group_size']<1 || (int)$data['max_group_size']>65535)) { throw new InvalidArgumentException('Group size must be between 1 and 65535.'); }
    if (isset($data['difficulty']) && !in_array($data['difficulty'],['easy','moderate','active'],true)) { throw new InvalidArgumentException('Invalid difficulty.'); }
    if (isset($data['start_time'])) {
        if ($data['start_time']==='') { $data['start_time']=null; }
        elseif (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/D',$data['start_time'])) { throw new InvalidArgumentException('Invalid start time.'); }
    }
    if (isset($input['flags_present'])) {
        foreach (['is_featured','is_published'] as $field) { if (db_column_exists('day_tours',$field)) { $data[$field]=isset($input[$field])&&$input[$field]==='1'?1:0; } }
        if (db_column_exists('day_tours','sort_order')) {
            $sort=(string)($input['sort_order']??'0');
            if (filter_var($sort,FILTER_VALIDATE_INT)===false || (float)$sort>2147483647 || (float)$sort< -2147483648) { throw new InvalidArgumentException('Invalid sort order.'); }
            $data['sort_order']=(int)$sort;
        }
    } elseif (!$id && db_column_exists('day_tours','is_published')) { $data['is_published']=0; }
    if (isset($input['relations_present']) && !is_array($input['relations_present'])) { throw new InvalidArgumentException('Invalid relationship fields.'); }
    $relations=[];
    foreach (day_tour_admin_relations() as $key=>$definition) {
        if (!isset($input['relations_present'][$key])) { continue; }
        if (!day_tour_admin_relation_available($definition)) { throw new InvalidArgumentException('This relationship is unavailable.'); }
        $ids=$input[$key]??[];
        if (!is_array($ids) || count($ids)>1000) { throw new InvalidArgumentException('Invalid relationship selection.'); }
        $valid=[];
        foreach ($ids as $candidate) {
            if ((!is_string($candidate)&&!is_int($candidate)) || !ctype_digit((string)$candidate) || (int)$candidate<=0) { throw new InvalidArgumentException('Invalid relationship selection.'); }
            if (!fetch_one('SELECT id FROM '.$definition[2].' WHERE id=:id',['id'=>(int)$candidate])) { throw new InvalidArgumentException('Selected related record no longer exists.'); }
            $valid[(int)$candidate]=(int)$candidate;
        }
        $relations[$key]=array_values($valid);
    }
    $connection=db();
    if ($connection->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql') {
        $tables=['day_tours'];foreach(array_keys($relations) as $key){$tables[]=day_tour_admin_relations()[$key][0];}
        if (array_filter($files, static fn(array $file): bool => ($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)) { $tables[]='media_assets'; }
        foreach(array_unique($tables) as $table){$meta=fetch_one('SHOW TABLE STATUS WHERE Name=:name',['name'=>$table]);if(strtolower((string)($meta['Engine']??''))!=='innodb'){throw new InvalidArgumentException('Saving requires transactional tables.');}}
    }
    $nested=$connection->inTransaction();$nested?$connection->exec('SAVEPOINT day_tour_admin'):$connection->beginTransaction();$created=[];
    try {
        $record=$id?day_tour_admin_record($id,true):[];
        $coverPath=null;
        try { $data=array_merge($data,package_cover_updates('day_tour',$record,$input,$files,$coverPath)); }
        finally { if($coverPath!==null){$created[]=$coverPath;} }
        if (!hash_equals(day_tour_admin_revision($record),(string)($input['revision']??''))) { throw new DomainException('This day tour changed in another editor. Reload it before saving again.'); }
        if (fetch_one('SELECT id FROM day_tours WHERE slug=:slug AND id<>:id',['slug'=>$data['slug'],'id'=>$id])) { throw new InvalidArgumentException('This slug already exists.'); }
        foreach (['hero_image','og_image'] as $slot) {
            if (!db_column_exists('day_tours',$slot) || !isset($files[$slot]) || ($files[$slot]['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) { continue; }
            $path=null;
            try { $media=image_manager_upload($files[$slot],false,$path);$data[$slot]=$media['path']; }
            finally { if($path!==null){$created[]=$path;} }
        }
        if ($id) {
            $sets=array_map(static fn(string $column):string=>$column.'=:'.$column,array_keys($data));
            execute_query('UPDATE day_tours SET '.implode(',',$sets).' WHERE id=:id',$data+['id'=>$id]);
        } else {
            $columns=array_keys($data);execute_query('INSERT INTO day_tours ('.implode(',',$columns).') VALUES (:'.implode(',:',$columns).')',$data);$id=(int)$connection->lastInsertId();
        }
        foreach ($relations as $key=>$ids) {
            [$pivot,$foreign]=day_tour_admin_relations()[$key];
            $existing=array_map('intval',array_column(fetch_all("SELECT {$foreign} FROM {$pivot} WHERE day_tour_id=:id",['id'=>$id]),$foreign));
            foreach(array_diff($existing,$ids) as $remove){execute_query("DELETE FROM {$pivot} WHERE day_tour_id=:id AND {$foreign}=:related",['id'=>$id,'related'=>$remove]);}
            foreach(array_diff($ids,$existing) as $add){execute_query("INSERT INTO {$pivot}(day_tour_id,{$foreign}) VALUES(:id,:related)",['id'=>$id,'related'=>$add]);}
        }
        $nested?$connection->exec('RELEASE SAVEPOINT day_tour_admin'):$connection->commit();return $id;
    } catch(Throwable $error) {
        if($connection->inTransaction()){$nested?$connection->exec('ROLLBACK TO SAVEPOINT day_tour_admin'):$connection->rollBack();}
        foreach($created as $path){delete_uploaded_media($path);}throw $error;
    }
}
