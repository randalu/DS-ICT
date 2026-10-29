<?php
declare(strict_types=1);
require_once __DIR__.'/admin_ai_limits.php';

function admin_openai_key(): string { return trim((string) (getenv('OPENAI_ADMIN_API_KEY') ?: '')); }
function admin_openai_reject_credentials(array $input): void
{
    foreach($input as$key=>$value){
        if(in_array(strtolower((string)$key),['key','api_key','openai_admin_api_key','endpoint'],true)){throw new InvalidArgumentException('OpenAI credentials and endpoints are configured on the server only.');}
        if(is_array($value))admin_openai_reject_credentials($value);
    }
}
function admin_openai_model(string $feature): string
{
    $name = match ($feature) { 'alt'=>'OPENAI_ALT_MODEL', 'audit'=>'OPENAI_AUDIT_MODEL', default=>'OPENAI_SEO_MODEL' };
    $value = trim((string) (getenv($name) ?: 'gpt-6-luna'));
    if (!preg_match('/^[a-zA-Z0-9._-]{1,100}$/D', $value)) { throw new RuntimeException('Invalid server OpenAI model configuration.'); }
    return $value;
}
function admin_openai_version(): string
{
    // Detect file-based rotations during a slow call, without exposing any setting.
    $file=dirname(__DIR__).'/.env';$lines=[];
    if(is_file($file)){
        foreach(file($file,FILE_IGNORE_NEW_LINES)?:[]as$line){if(preg_match('/^\s*OPENAI_(?:ADMIN_API_KEY|ALT_MODEL|SEO_MODEL|AUDIT_MODEL)\s*=/',$line))$lines[]=$line;}
    }
    return hash('sha256', admin_openai_key().'|'.admin_openai_model('alt').'|'.admin_openai_model('seo').'|'.admin_openai_model('audit').'|'.implode("\n",$lines));
}
function admin_openai_usage(int $admin): array
{
    if (!db_table_exists('admin_ai_requests')) { return []; }
    return fetch_all('SELECT feature,COUNT(*) AS requests,SUM(input_tokens) AS input_tokens,SUM(output_tokens) AS output_tokens FROM admin_ai_requests WHERE admin_id=:admin AND created_at>=CURRENT_DATE GROUP BY feature',['admin'=>$admin]);
}
/** Reservation serializes site quotas. Failed and uncertain requests count too. */
function admin_openai_begin(string $feature,string $token,int $admin): int
{
    if (!in_array($feature,['alt','seo','audit'],true) || !preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$token)) { throw new InvalidArgumentException('Invalid AI request identifier.'); }
    if (!db_table_exists('admin_ai_requests')) { throw new InvalidArgumentException('Apply the shared admin OpenAI migration before using AI. Manual editing remains available.'); }
    $lock='ict-admin-ai-'.substr(hash('sha256',DB_NAME),0,35);
    if ((int)(fetch_one('SELECT GET_LOCK(:name,0) AS ok',['name'=>$lock])['ok']??0)!==1) { throw new DomainException('AI request reservation is busy. Retry shortly.'); }
    try {
        if (fetch_one('SELECT id FROM admin_ai_requests WHERE admin_id=:admin AND request_token=:token',['admin'=>$admin,'token'=>$token])) { throw new DomainException('This AI request was already submitted. Review the outcome before retrying.'); }
        execute_query("UPDATE admin_ai_requests SET status='failed_or_unknown',finished_at=NOW() WHERE status='running' AND created_at<DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        if (fetch_one("SELECT id FROM admin_ai_requests WHERE admin_id=:admin AND status='running'",['admin'=>$admin])) { throw new DomainException('Another AI request is running for your account. Wait for it to finish.'); }
        if ($feature==='alt') {
            $hour=(int)(fetch_one("SELECT COUNT(*) AS n FROM admin_ai_requests WHERE feature='alt' AND admin_id=:admin AND created_at>=DATE_SUB(NOW(), INTERVAL 1 HOUR)",['admin'=>$admin])['n']??0);
            $day=(int)(fetch_one("SELECT COUNT(*) AS n FROM admin_ai_requests WHERE feature='alt' AND created_at>=CURRENT_DATE")['n']??0);
            if ($hour>=admin_ai_request_limit('alt','hour') || $day>=admin_ai_request_limit('alt','day')) { throw new InvalidArgumentException('Image alt request limit reached. Try again later.'); }
        }
        execute_query("INSERT INTO admin_ai_requests(admin_id,request_token,feature,model,status,created_at) VALUES(:admin,:token,:feature,:model,'running',NOW())",['admin'=>$admin,'token'=>$token,'feature'=>$feature,'model'=>admin_openai_model($feature)]);
        return (int)db()->lastInsertId();
    } finally { execute_query('DO RELEASE_LOCK(:name)',['name'=>$lock]); }
}
function admin_openai_finish(int $id,string $status,array $usage=[]): void
{
    execute_query('UPDATE admin_ai_requests SET status=:status,input_tokens=:input,output_tokens=:output,finished_at=NOW() WHERE id=:id',['status'=>$status,'input'=>(int)($usage['input']??0),'output'=>(int)($usage['output']??0),'id'=>$id]);
}
/** Transport has no browser-configurable endpoint and never logs provider bodies or credentials. */
function admin_openai_response(string $key, array $payload, ?callable $transport = null): array
{
    $payload['model'] = $payload['model'] ?? admin_openai_model('seo');
    $payload['store'] = false;
    $payload['max_output_tokens'] = $payload['max_output_tokens'] ?? 4500;
    if ($transport !== null) { $data = $transport($payload); }
    else {
        if (!function_exists('curl_init')) { throw new RuntimeException('PHP cURL is required.'); }
        $curl = curl_init('https://api.openai.com/v1/responses');
        $options = [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR), CURLOPT_TIMEOUT => 90,
            CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS];
        $ca = __DIR__ . '/../cacert.pem';
        if (is_file($ca)) { $options[CURLOPT_CAINFO] = $ca; }
        curl_setopt_array($curl, $options);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($raw === false) { throw new RuntimeException('OpenAI connection timed out or failed. The request may have been billed; it will not be retried automatically.'); }
        if ($status !== 200) {
            throw new RuntimeException(match ($status) {
                401, 403 => 'OpenAI rejected this key or model access.',
                404 => 'The selected model is unavailable for this account. No alternative model was used.',
                429 => 'OpenAI rate or billing limit reached. Retry manually later.',
                default => 'OpenAI could not complete the request. No automatic retry was made.',
            });
        }
        $data = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    }
    if (!is_array($data) || ($data['status'] ?? '') !== 'completed') { throw new RuntimeException('OpenAI returned incomplete output; no suggestion was applied.'); }
    $text = ''; $sources = [];
    foreach ($data['output'] ?? [] as $item) {
        foreach ($item['content'] ?? [] as $part) {
            if (($part['type'] ?? '') === 'refusal') { throw new RuntimeException('OpenAI declined the request.'); }
            if (($part['type'] ?? '') === 'output_text') { $text .= $part['text'] ?? ''; }
            foreach ($part['annotations'] ?? [] as $annotation) {
                if (($annotation['type'] ?? '') === 'url_citation') {
                    $url = (string) ($annotation['url'] ?? '');
                    if (filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https') {
                        $sources[$url] = ['url' => $url, 'title' => mb_substr((string) ($annotation['title'] ?? $url), 0, 300)];
                    }
                }
            }
        }
    }
    if (trim($text) === '' || strlen($text) > 100000) { throw new RuntimeException('OpenAI returned empty or oversized output.'); }
    return ['text' => $text, 'sources' => array_values($sources), 'usage' => [
        'input' => max(0, (int) ($data['usage']['input_tokens'] ?? 0)),
        'output' => max(0, (int) ($data['usage']['output_tokens'] ?? 0))]];
}
