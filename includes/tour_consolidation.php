<?php
declare(strict_types=1);
require_once __DIR__.'/tour_program.php';
require_once __DIR__.'/retired_package_pages.php';

function tour_consolidation_wording(string $text): string
{
    $text=str_ireplace(['The day-by-day guides below','the linked 12-day honeymoon itinerary','the 12-day planning guide','the 10-day planning guide','9-day guide','planning guides','planning guide'],['The daily plan on this page','the 12-day honeymoon route','the 12-day route idea','this 10-day route','9-day option','route options','route option'],$text);
    return $text;
}

/** Build Tour-owned copies before deleting any reference content. Safe to inspect without writes. */
function tour_consolidation_plan(): array
{
    $tours=fetch_all('SELECT * FROM tours ORDER BY id');
    $guides=db_table_exists('n_day_itinerary_pages')?fetch_all('SELECT * FROM n_day_itinerary_pages ORDER BY days'):[];
    $links=db_table_exists('itinerary_package_links')?fetch_all('SELECT * FROM itinerary_package_links ORDER BY id'):[];
    $connections=db_table_exists('itinerary_tour')?fetch_all('SELECT * FROM itinerary_tour ORDER BY id'):[];
    $itineraries=db_table_exists('itineraries')?fetch_all('SELECT * FROM itineraries ORDER BY id'):[];
    $allDays=db_table_exists('itinerary_days')?fetch_all('SELECT * FROM itinerary_days ORDER BY day_number,id'):[];
    $result=[];
    $plain=static fn(?string $value):string=>trim(html_entity_decode(strip_tags(str_replace(['\\r\\n','\\n'],"\n",(string)$value)),ENT_QUOTES|ENT_HTML5,'UTF-8'));
    $overrides=['classic-sri-lanka-private-tour-package'=>'sri-lanka-itinerary-12-days','sri-lanka-10-day-itinerary-package'=>'sri-lanka-itinerary-10-days','sri-lanka-14-day-grand-tour-package'=>'sri-lanka-itinerary-14-days','sri-lanka-honeymoon-package'=>'sri-lanka-honeymoon-itinerary-12-days','sri-lanka-family-tour-package'=>'sri-lanka-family-itinerary-10-days','sri-lanka-wildlife-safari-package'=>'sri-lanka-wildlife-itinerary-9-days','sri-lanka-wildlife-safari-tour'=>'sri-lanka-wildlife-itinerary-9-days','luxury-sri-lanka-slow-travel-package'=>'sri-lanka-luxury-itinerary-11-days'];
    foreach($tours as $tour){
        $existingProgram=!empty($tour['program_json'])?tour_program_decode($tour['program_json']):null;
        $ownedGuides=[];$sourceIt=null;$linkedDurations=[];$hasLink=false;
        foreach($links as $link){if($link['package_slug']!==$tour['slug']){continue;}
            $hasLink=true;
            if(preg_match('~/([0-9]+)-day-itinerary/?$~',$link['informational_path'],$m)){$linkedDurations[]=(int)$m[1];}
            foreach($itineraries as $it){if($link['informational_path']==='/itineraries/'.$it['slug']){$sourceIt=$it;}}
        }
        foreach($connections as $connection){if((int)$connection['tour_id']!==(int)$tour['id']){continue;}$hasLink=true;foreach($itineraries as $it){if((int)$it['id']===(int)$connection['itinerary_id']){$sourceIt=$it;}}}
        foreach($itineraries as $it){if(($overrides[$tour['slug']]??'')===$it['slug']){$sourceIt=$it;break;}}
        foreach($guides as $guide){if(in_array((int)$guide['days'],$linkedDurations,true)||trim((string)$guide['package_path'],'/')==='tours/'.$tour['slug']){$ownedGuides[]=$guide;}}
        if(!$guides&&!$links&&!$connections){continue;}
        if($existingProgram&&!$ownedGuides&&!$hasLink){continue;}
        $program=['route'=>$sourceIt?$plain($sourceIt['route_summary']??''):$plain($tour['itinerary']??$tour['sample_itinerary']??''),'days'=>[],'hotels'=>[],'sections'=>[]];
        $sections=[];
        foreach(['itinerary'=>'Route overview','sample_itinerary'=>'Route and timing options'] as $field=>$label){if(trim((string)($tour[$field]??''))!==''){$sections[]=['title'=>$label,'body'=>(string)$tour[$field]];}}
        $faq=decode_faq_json_items($tour['faq_json']??null);
        foreach($ownedGuides as $guide){
            foreach(['intro_html'=>'Planning this route','route_html'=>'Route choices','transfers_html'=>'Transfer planning','alternatives_html'=>'Alternative routes','pace_html'=>'Pace and comfort','seasonal_html'=>'Seasonal planning','best_for_html'=>'Who this route suits'] as $field=>$label){
                if(trim((string)($guide[$field]??''))!==''){$sections[]=['title'=>$guide['days'].'-day option: '.$label,'body'=>(string)$guide[$field]];}
            }
            $nights=json_decode((string)($guide['nights_json']??''),true);
            if(is_array($nights)&&$nights){$html='<ul>';foreach($nights as $night){$html.='<li>'.e((string)($night['place']??'')).': '.(int)($night['nights']??0).' nights. '.e((string)($night['note']??'')).'</li>';}$sections[]=['title'=>$guide['days'].'-day option: overnight distribution','body'=>$html.'</ul>'];}
            $faq=array_merge($faq,decode_faq_json_items($guide['faq_json']??null));
        }
        $sourceRows=[];foreach($allDays as $day){if($sourceIt&&(int)$day['itinerary_id']===(int)$sourceIt['id']){$sourceRows[]=$day;}}
        $stays=[];
        foreach($sourceRows as $i=>$day){
            $start=(int)$day['day_number'];$end=max($start,(int)($day['day_end']??$start));
            $highlights=json_decode((string)($day['highlights_json']??''),true);if(!is_array($highlights)||!$highlights){$highlights=[$plain($day['title'])];}
            $location=$plain($day['overnight_location']??'')?:$plain($day['title']);
            $program['days'][]=['day_start'=>$start,'day_end'=>$end,'title'=>$plain($day['title']),'location'=>$location,'summary'=>$plain($day['summary']??''),'highlights'=>$highlights,'overnight'=>$plain($day['overnight_location']??''),'travel_time'=>$plain($day['travel_time']??''),'image_asset_id'=>0,'image_alt'=>''];
            if($i<count($sourceRows)-1&&$location!==''){$stays[$location]=($stays[$location]??0)+$end-$start+1;}
        }
        foreach($stays as $place=>$nights){$program['hotels'][]=['name'=>'Stay in '.$place,'location'=>$place,'category'=>'Accommodation to be selected','description'=>'Proposed overnight base for this route. The property, room, meals and included nights are confirmed in the written quote.','nights'=>$nights,'status'=>'proposed','room_type'=>'','board_basis'=>'','image_asset_id'=>0,'image_alt'=>''];}
        if(!$program['hotels']&&$ownedGuides){foreach(json_decode((string)$ownedGuides[0]['nights_json'],true)??[] as $night){$program['hotels'][]=['name'=>'Stay in '.$night['place'],'location'=>$night['place'],'category'=>'Accommodation to be selected','description'=>$plain($night['note']??''),'nights'=>(int)$night['nights'],'status'=>'proposed','room_type'=>'','board_basis'=>'','image_asset_id'=>0,'image_alt'=>''];}}
        foreach($sections as &$section){$section['body']=tour_consolidation_wording($section['body']);if(preg_match('/^[0-9]+-day option: (Route choices|overnight distribution)$/',$section['title'])){$section['title']='Alternative '.$section['title'];}}unset($section);
        $program['sections']=$sections;
        if($existingProgram){
            foreach(['route','days','hotels'] as $field){if($existingProgram[$field]){$program[$field]=$existingProgram[$field];}}
            $program['sections']=array_merge($existingProgram['sections'],$program['sections']);
        }
        $seenSections=[];$uniqueSections=[];foreach($program['sections'] as $section){$key=hash('sha256',$section['title'].'|'.$section['body']);if(!isset($seenSections[$key])){$uniqueSections[]=$section;$seenSections[$key]=true;}}$program['sections']=$uniqueSections;
        $program=tour_program_normalize($program);
        $seen=[];$questions=[];$mergedFaq=[];foreach($faq as $f){$key=hash('sha256',$f['question'].'|'.$f['answer']);if(isset($seen[$key])){continue;}$q=mb_strtolower($f['question']);if(isset($questions[$q])){$f['question'].=' (alternative route option)';}$mergedFaq[]=$f;$seen[$key]=true;$questions[$q]=true;}
        $coverage=tour_coverage_decode($tour['coverage_json']??null);
        if(!$coverage){foreach($program['days'] as $day){if(preg_match('/arrival|departure|airport|recovery/i',$day['title'])){continue;}$coverage[]=['title'=>$day['title'],'location'=>$day['location'],'description'=>mb_substr($day['summary'],0,6000),'status'=>'proposed','destination_id'=>0,'experience_id'=>0,'image_asset_id'=>0,'image_alt'=>''];if(count($coverage)>=6){break;}}}
        $wording=[];foreach(['intro','itinerary','sample_itinerary'] as $field){if(array_key_exists($field,$tour)){$wording[$field]=is_string($tour[$field])?tour_consolidation_wording($tour[$field]):$tour[$field];}}
        foreach($mergedFaq as &$f){$f['answer']=tour_consolidation_wording($f['answer']);}unset($f);
        $result[]=['id'=>(int)$tour['id'],'slug'=>$tour['slug'],'source_itinerary'=>$sourceIt['slug']??null,'source_reference_days'=>array_column($ownedGuides,'days'),'program'=>$program,'faq'=>$mergedFaq,'coverage'=>$coverage,'wording'=>$wording];
    }
    return $result;
}

function tour_consolidation_install_guards(): void
{
    foreach(['n_day_itinerary_pages'=>'day_pages','itinerary_package_links'=>'guide_links','itinerary_tour'=>'tour_connections'] as $table=>$label){
        if(!db_table_exists($table)){continue;}
        foreach(['INSERT','UPDATE'] as $event){$name='ict_retired_'.$label.'_'.strtolower($event);db()->exec('DROP TRIGGER IF EXISTS `'.$name.'`');db()->exec('CREATE TRIGGER `'.$name.'` BEFORE '.$event.' ON `'.$table.'` FOR EACH ROW SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT=\'Separate Tour reference pages and connections are permanently retired\'');}
    }
    foreach(['static_pages'=>['cms','slug','^[0-9]+-day-itinerary$'],'seo_intent_map'=>['intent','target_path','^/[0-9]+-day-itinerary/?$']] as $table=>[$label,$column,$pattern]){
        if(!db_table_exists($table)){continue;}
        foreach(['INSERT','UPDATE'] as $event){$name='ict_retired_'.$label.'_'.strtolower($event);db()->exec('DROP TRIGGER IF EXISTS `'.$name.'`');db()->exec('CREATE TRIGGER `'.$name.'` BEFORE '.$event.' ON `'.$table.'` FOR EACH ROW BEGIN IF NEW.`'.$column.'` REGEXP '.db()->quote($pattern).' THEN SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT=\'Duration reference URLs are permanently retired\'; END IF; END');}
    }
}

function tour_consolidation_apply(string $backupPath, array $preserveFields = []): array
{
    if(is_file($backupPath)){throw new RuntimeException('Backup already exists. Choose a new filename.');}
    if(!db_column_exists('tours','program_json')){throw new RuntimeException('Apply 2026-10-24-tour-program.sql first.');}
    tour_consolidation_install_guards();
    $tables=['tours','n_day_itinerary_pages','itinerary_package_links','itinerary_tour','static_pages','itineraries','itinerary_days','seo_intent_map'];$backup=[];
    db()->beginTransaction();
    try{
        foreach($tables as $table){if(db_table_exists($table)){$backup[$table]=fetch_all('SELECT * FROM '.$table.' ORDER BY id FOR UPDATE');}}
        $plan=tour_consolidation_plan();
        if(file_put_contents($backupPath,json_encode($backup,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),LOCK_EX)===false){throw new RuntimeException('Cannot save content backup.');}
        foreach($plan as $item){
            $sets=['program_json=:p','faq_json=:f','coverage_json=:c'];$params=['p'=>json_encode($item['program'],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),'f'=>json_encode($item['faq'],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),'c'=>json_encode($item['coverage'],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),'id'=>$item['id']];
            foreach($item['wording'] as $field=>$value){if(in_array($field,$preserveFields[$item['slug']]??[],true)){continue;}$sets[]=$field.'=:'.$field;$params[$field]=$value;}
            execute_query('UPDATE tours SET '.implode(',',$sets).' WHERE id=:id',$params);
        }
        foreach(['n_day_itinerary_pages','itinerary_package_links','itinerary_tour'] as $table){if(db_table_exists($table)){execute_query('DELETE FROM '.$table);}}
        if(db_table_exists('static_pages')){execute_query("DELETE FROM static_pages WHERE slug REGEXP '^[0-9]+-day-itinerary$'");}
        if(db_table_exists('seo_intent_map')){execute_query("DELETE FROM seo_intent_map WHERE target_path REGEXP '^/[0-9]+-day-itinerary/?$'");}
        db()->commit();
    }catch(Throwable $e){if(db()->inTransaction()){db()->rollBack();}throw $e;}
    return array_map(static fn($item)=>['id'=>$item['id'],'slug'=>$item['slug'],'days'=>count($item['program']['days']),'stays'=>count($item['program']['hotels']),'sections'=>count($item['program']['sections'])],$plan);
}

/** Called only by an explicitly run CLI migration, never by a public/admin GET. */
function tour_consolidation_activate(string $backupDirectory, array $preserveFields = []): array
{
    if(PHP_SAPI!=='cli'){throw new RuntimeException('Tour consolidation is CLI-only.');}
    $hasLegacy=false;
    foreach(['n_day_itinerary_pages','itinerary_package_links','itinerary_tour'] as $table){
        if(db_table_exists($table)&&(int)(fetch_one('SELECT COUNT(*) AS n FROM '.$table)['n']??0)>0){$hasLegacy=true;}
    }
    if(db_table_exists('static_pages')&&(int)(fetch_one("SELECT COUNT(*) AS n FROM static_pages WHERE slug REGEXP '^[0-9]+-day-itinerary$'")['n']??0)>0){$hasLegacy=true;}
    if(db_table_exists('seo_intent_map')&&(int)(fetch_one("SELECT COUNT(*) AS n FROM seo_intent_map WHERE target_path REGEXP '^/[0-9]+-day-itinerary/?$'")['n']??0)>0){$hasLegacy=true;}
    if(!$hasLegacy){tour_consolidation_install_guards();return [];}
    if(!is_dir($backupDirectory)&&!mkdir($backupDirectory,0700,true)&&!is_dir($backupDirectory)){throw new RuntimeException('Cannot create the private Tour content backup directory.');}
    $backup=$backupDirectory.'/tour-content-'.date('Ymd-His').'-'.bin2hex(random_bytes(5)).'.json';
    return tour_consolidation_apply($backup,$preserveFields);
}
