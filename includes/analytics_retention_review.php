<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
const ICT_ANALYTICS_HOLD_KEY = 'analytics_cleanup_hold_json';

function ict_analytics_hold_defaults(): array
{
    return ['schema_version'=>1,'revision'=>0,'paused'=>true,'reason'=>'Release review and recovery evidence pending',
        'reference'=>'','next_review_date'=>date('Y-m-d',strtotime('+1 month')),'reviewed_by'=>0,'reviewed_at'=>''];
}

function ict_analytics_hold_decode(string $json): array
{
    $row=json_decode($json,true,8,JSON_THROW_ON_ERROR);
    $keys=array_keys(ict_analytics_hold_defaults());
    if(!is_array($row)||count($row)!==count($keys)||array_diff(array_keys($row),$keys)
        ||($row['schema_version']??null)!==1||($row['paused']??null)!==true
        ||!is_int($row['revision']??null)||$row['revision']<1||!is_int($row['reviewed_by']??null)||$row['reviewed_by']<1
        ||!is_string($row['reviewed_at']??null)||!preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z\z/',$row['reviewed_at'])){
        throw new InvalidArgumentException('Invalid or unpaused hold state.');
    }
    ict_analytics_hold_input(['revision'=>$row['revision'],'reason'=>$row['reason']??null,'reference'=>$row['reference']??null,'next_review_date'=>$row['next_review_date']??null]);
    $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z',$row['reviewed_at'],new DateTimeZone('UTC'));
    if(!$date||$date->format('Y-m-d\TH:i:s\Z')!==$row['reviewed_at']||$date->getTimestamp()>time()+60){throw new InvalidArgumentException('Invalid review timestamp.');}
    return $row;
}

function ict_analytics_hold_input(array $input): array
{
    $keys=['revision','reason','reference','next_review_date'];
    if(count($input)!==4||array_diff(array_keys($input),$keys)){throw new InvalidArgumentException('Use the listed hold fields only.');}
    $revision=filter_var($input['revision']??null,FILTER_VALIDATE_INT);
    if($revision===false||$revision<0||$revision>1000000){throw new InvalidArgumentException('Reload the current hold review.');}
    foreach(['reason'=>500,'reference'=>120] as $key=>$limit){
        if(!is_string($input[$key]??null)||!mb_check_encoding($input[$key],'UTF-8')||mb_strlen($input[$key])>$limit||preg_match('/[\x00-\x1F\x7F]/',$input[$key])){throw new InvalidArgumentException('Use a short, non-sensitive reason and reference.');}
    }
    if(trim($input['reason'])===''){throw new InvalidArgumentException('Enter a hold reason.');}
    $raw=$input['next_review_date']??null;
    $date=is_string($raw)?DateTimeImmutable::createFromFormat('!Y-m-d',$raw):false;
    if(!$date||$date->format('Y-m-d')!==$raw){throw new InvalidArgumentException('Use a valid next review date.');}
    return ['revision'=>$revision,'reason'=>trim($input['reason']),'reference'=>trim($input['reference']),'next_review_date'=>$raw];
}

function ict_analytics_hold_state(): array
{
    try {
        $row=db_table_exists('analytics_settings')?fetch_one('SELECT setting_value FROM analytics_settings WHERE setting_key=:key',['key'=>ICT_ANALYTICS_HOLD_KEY]):null;
        if($row){return ict_analytics_hold_decode((string)$row['setting_value'])+['configured'=>true,'invalid'=>false];}
    }catch(Throwable){return ict_analytics_hold_defaults()+['configured'=>false,'invalid'=>true];}
    return ict_analytics_hold_defaults()+['configured'=>false,'invalid'=>false];
}

/** Authenticated reviewer ID/time are server-owned. Hold saves never dispose data. */
function ict_analytics_hold_save(array $input,int $reviewer): void
{
    $input=ict_analytics_hold_input($input);
    if($reviewer<1||!fetch_one('SELECT id FROM admins WHERE id=:id AND is_active=1',['id'=>$reviewer])){throw new InvalidArgumentException('An active authenticated reviewer is required.');}
    if(!db_table_exists('analytics_settings')){throw new RuntimeException('Existing analytics settings table unavailable.');}
    $pdo=db();$owns=!$pdo->inTransaction();if($owns){$pdo->beginTransaction();}else{$pdo->exec('SAVEPOINT ict_hold_save');}
    try{
        $row=fetch_one('SELECT setting_value FROM analytics_settings WHERE setting_key=:key FOR UPDATE',['key'=>ICT_ANALYTICS_HOLD_KEY]);
        $revision=0;
        if($row){try{$revision=ict_analytics_hold_decode((string)$row['setting_value'])['revision'];}catch(Throwable){throw new InvalidArgumentException('Stored hold is invalid; review it before replacing it.');}}
        if($revision!==$input['revision']){throw new InvalidArgumentException('The hold changed. Reload before saving.');}
        $settings=['schema_version'=>1,'revision'=>$revision+1,'paused'=>true,'reason'=>$input['reason'],'reference'=>$input['reference'],
            'next_review_date'=>$input['next_review_date'],'reviewed_by'=>$reviewer,'reviewed_at'=>gmdate('Y-m-d\TH:i:s\Z')];
        execute_query('INSERT INTO analytics_settings(setting_key,setting_value) VALUES(:key,:value) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',['key'=>ICT_ANALYTICS_HOLD_KEY,'value'=>json_encode($settings,JSON_THROW_ON_ERROR)]);
        if($owns){$pdo->commit();}else{$pdo->exec('RELEASE SAVEPOINT ict_hold_save');}
    }catch(Throwable $exception){if($pdo->inTransaction()){if($owns){$pdo->rollBack();}else{$pdo->exec('ROLLBACK TO SAVEPOINT ict_hold_save');$pdo->exec('RELEASE SAVEPOINT ict_hold_save');}}throw $exception;}
}

/** SELECT-only report. Manual periods/triggers are not converted into deletion instructions. */
function ict_analytics_retention_report(?DateTimeImmutable $now=null): array
{
    $now??=new DateTimeImmutable('now');$hold=ict_analytics_hold_state();
    $report=['mode'=>'dry-run','disposal_paused'=>true,'apply_available'=>false,'hold'=>$hold,'review_overdue'=>$hold['configured']&&$hold['next_review_date']<=$now->format('Y-m-d'),
        'runtime_days'=>null,'cutoff'=>null,'manual_rule'=>null,'manual_rule_configured'=>false,'periods_reconciled'=>false,'candidate_count'=>null,
        'blocked_reasons'=>['This release supports dry runs only; no apply operation exists.']];
    if(!$hold['configured']){$report['blocked_reasons'][]=$hold['invalid']?'Stored hold is invalid; disposal remains paused.':'No recorded hold review; disposal remains paused.';}
    elseif($report['review_overdue']){$report['blocked_reasons'][]='Hold review is due/overdue; the hold does not expire automatically.';}
    else{$report['blocked_reasons'][]='Reviewed global hold is active.';}
    try{
        $row=db_table_exists('analytics_settings')?fetch_one('SELECT setting_value FROM analytics_settings WHERE setting_key=:key',['key'=>'events_retention_days']):null;
        $days=filter_var($row['setting_value']??null,FILTER_VALIDATE_INT);
        if($days===false||$days<30||$days>3650){throw new RuntimeException('Runtime cutoff is unavailable or invalid.');}
        $report['runtime_days']=$days;$report['cutoff']=$now->modify('-'.$days.' days')->format('Y-m-d H:i:s');
        if(db_table_exists('analytics_events')&&db_column_exists('analytics_events','created_at')){
            $count=fetch_one('SELECT COUNT(*) AS total FROM analytics_events WHERE created_at < :cutoff',['cutoff'=>$report['cutoff']]);
            $report['candidate_count']=$count?(int)$count['total']:null;
        }
        $manual=db_table_exists('site_settings')?fetch_one('SELECT setting_value FROM site_settings WHERE setting_key=:key',['key'=>'data_retention_draft_json']):null;
        require_once __DIR__ . '/retention_settings.php';
        $rules=$manual?ict_retention_decode((string)$manual['setting_value']):null;
        $rule=$rules['rules']['analytics']??null;
        if(is_array($rule)&&is_int($rule['period']??null)&&$rule['period']>0&&in_array($rule['unit']??null,['days','months'],true)&&is_string($rule['trigger']??null)&&$rule['trigger']!==''){
            $report['manual_rule']=$rule;$report['manual_rule_configured']=true;
            $report['periods_reconciled']=$rule['unit']==='days'&&$rule['period']===$days&&strtolower(trim($rule['trigger']))==='event date';
        }
    }catch(Throwable){$report['blocked_reasons'][]='Cutoff, event schema or manual retention rule could not be verified.';}
    if($report['candidate_count']===null){$report['blocked_reasons'][]='Candidate count unavailable; never treat it as zero.';}
    if(!$report['periods_reconciled']){$report['blocked_reasons'][]='Runtime cutoff/manual analytics rule require explicit reconciliation; months/free-text triggers are not converted.';}
    return $report;
}
