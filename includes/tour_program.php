<?php
declare(strict_types=1);
require_once __DIR__.'/tour_coverage.php';

/** A Tour owns this content. No read-time dependency on itinerary or guide records. */
function tour_program_decode(?string $json): array
{
    $data=json_decode((string)$json,true);$default=['route'=>'','days'=>[],'hotels'=>[],'sections'=>[]];
    if(!is_array($data)||array_is_list($data)){return $default;}
    $out=$default;$out['route']=is_string($data['route']??null)?$data['route']:'';
    foreach(['days','hotels','sections'] as $group){
        if(!is_array($data[$group]??null)){continue;}
        foreach($data[$group] as $i=>$row){
            if(!is_array($row)){continue;}
            $fields=$group==='days'?['title','location','summary','overnight','travel_time','image_alt']:($group==='hotels'?['name','location','category','description','room_type','board_basis','image_alt']:['title','body']);
            foreach($fields as $field){$row[$field]=is_string($row[$field]??null)?$row[$field]:'';}
            if($group==='days'){$row['day_start']=max(1,(int)($row['day_start']??((int)$i+1)));$row['day_end']=max($row['day_start'],(int)($row['day_end']??$row['day_start']));$row['highlights']=is_array($row['highlights']??null)?array_values(array_filter($row['highlights'],'is_string')):[];}
            if($group==='hotels'){$row['nights']=max(0,(int)($row['nights']??0));$row['status']=in_array($row['status']??'', ['proposed','optional'],true)?$row['status']:'proposed';}
            if($group!=='sections'){$row['image_asset_id']=max(0,(int)($row['image_asset_id']??0));}
            $out[$group][]=$row;
        }
    }return $out;
}

function tour_program_normalize(mixed $data): array
{
    if(!is_array($data)){throw new InvalidArgumentException('Invalid tour programme.');}
    $text=static function(mixed $v,int $max,bool $required=false):string{
        if(!is_string($v)||str_contains($v,"\0")||mb_strlen($v)>$max||$v!==strip_tags($v)){throw new InvalidArgumentException('Programme fields must be plain text within their limits.');}
        $v=trim($v);if($required&&$v===''){throw new InvalidArgumentException('Complete the title and location for every day or stay.');}return $v;
    };
    $out=['route'=>$text($data['route']??'',12000),'days'=>[],'hotels'=>[],'sections'=>[]];
    foreach(['days'=>60,'hotels'=>40,'sections'=>60] as $group=>$max){
        if(!is_array($data[$group]??[])||count($data[$group]??[])>$max){throw new InvalidArgumentException('Too many programme '.$group.'.');}
        foreach(array_values($data[$group]??[]) as $i=>$r){
            if(!is_array($r)){throw new InvalidArgumentException('Invalid programme row.');}
            if($group==='sections'){
                $body=$r['body']??'';
                if(!is_string($body)||mb_strlen($body)>60000||str_contains($body,"\0")){throw new InvalidArgumentException('Invalid planning section.');}
                $out[$group][]=['title'=>$text($r['title']??'',180,true),'body'=>trim($body)];continue;
            }
            $row=[];
            $fields=$group==='days'?['title'=>180,'location'=>255,'summary'=>12000,'overnight'=>255,'travel_time'=>255]:['name'=>180,'location'=>255,'category'=>180,'description'=>12000,'room_type'=>180,'board_basis'=>180];
            foreach($fields as $field=>$limit){$row[$field]=$text($r[$field]??'',$limit,in_array($field,['title','name','location'],true));}
            if($group==='days'){
                foreach(['day_start','day_end'] as $field){$v=filter_var($r[$field]??($field==='day_start'?$i+1:$row['day_start']),FILTER_VALIDATE_INT);if($v===false||$v<1||$v>366){throw new InvalidArgumentException('Day numbers must be between 1 and 366.');}$row[$field]=$v;}
                if($row['day_end']<$row['day_start']){throw new InvalidArgumentException('A day range must end after it starts.');}
                $hl=$r['highlights']??[];if(is_string($hl)){$hl=preg_split('/\R/u',$hl);}
                if(!is_array($hl)||count($hl)>30){throw new InvalidArgumentException('Use at most 30 highlights per day.');}
                $row['highlights']=array_values(array_filter(array_map(static fn($h)=>$text($h,600),$hl),static fn($h)=>$h!==''));
            }else{
                $n=filter_var($r['nights']??0,FILTER_VALIDATE_INT);if($n===false||$n<0||$n>366){throw new InvalidArgumentException('Invalid number of nights.');}$row['nights']=$n;
                $row['status']=$r['status']??'proposed';if(!in_array($row['status'],['proposed','optional'],true)){throw new InvalidArgumentException('Choose proposed or optional accommodation.');}
            }
            $img=$r['image_asset_id']??0;if(!is_scalar($img)||!preg_match('/\A[0-9]{1,10}\z/',(string)$img)){throw new InvalidArgumentException('Invalid programme image.');}
            $row['image_asset_id']=(int)$img;$row['image_alt']=$text($r['image_alt']??'',255);
            if($row['image_asset_id']>0&&(!tour_coverage_resolve('image',$row['image_asset_id'])||$row['image_alt']==='')){throw new InvalidArgumentException('Choose an existing still image and add its description.');}
            $out[$group][]=$row;
        }
    }
    $previous=0;foreach($out['days'] as $day){if($day['day_start']<=$previous){throw new InvalidArgumentException('Days must be ordered without overlapping ranges.');}$previous=$day['day_end'];}
    return $out;
}

function tour_program_save(int $id,array $data,string $revision): void
{
    if(!db_column_exists('tours','program_json')){throw new RuntimeException('Apply the Tour programme migration first.');}
    $engine=fetch_one('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=\'tours\'');
    if(strcasecmp((string)($engine['ENGINE']??''),'InnoDB')!==0){throw new RuntimeException('Tour programme saves require transactional InnoDB storage.');}
    $program=tour_program_normalize($data);$pdo=db();$nested=$pdo->inTransaction();$nested?$pdo->exec('SAVEPOINT tour_program_save'):$pdo->beginTransaction();
    try{
        $record=fetch_one('SELECT program_json FROM tours WHERE id=:id FOR UPDATE',['id'=>$id])??throw new InvalidArgumentException('Tour not found.');
        if(!hash_equals(tour_coverage_revision($record['program_json']??null),$revision)){throw new DomainException('The programme or its photos changed. Reload and compare before saving.');}
        execute_query('UPDATE tours SET program_json=:json WHERE id=:id',['json'=>json_encode($program,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'id'=>$id]);
        $nested?$pdo->exec('RELEASE SAVEPOINT tour_program_save'):$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction()){$nested?$pdo->exec('ROLLBACK TO SAVEPOINT tour_program_save'):$pdo->rollBack();}throw $e;}
}

function tour_program_image(array $row): string
{
    if(empty($row['image_asset_id'])||!db_table_exists('media_assets')){return '';}
    static $assets=[];$id=(int)$row['image_asset_id'];
    if(!array_key_exists($id,$assets)){$assets[$id]=fetch_one('SELECT file_path,mime_type FROM media_assets WHERE id=:id',['id'=>$id]);}
    $r=$assets[$id];return $r&&str_starts_with((string)$r['mime_type'],'image/')?stored_media_url($r['file_path']):'';
}

function tour_program_media_reference_count(int $id): int
{
    if(!db_table_exists('tours')||!db_column_exists('tours','program_json')){return 0;}$count=0;
    foreach(fetch_all('SELECT program_json FROM tours WHERE program_json IS NOT NULL') as $r){$p=tour_program_decode($r['program_json']);foreach(array_merge($p['days'],$p['hotels']) as $row){if((int)($row['image_asset_id']??0)===$id){$count++;}}}return $count;
}
