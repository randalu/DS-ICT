<?php
declare(strict_types=1);
/** Request quotas are ten times the previous configured values; content and security limits are independent. */
function admin_ai_scaled_limit(string $environment,int $previousDefault,int $previousMaximum=10000): int
{
    $value=getenv($environment);
    $base=$value!==false && ctype_digit($value)?min($previousMaximum,(int)$value):$previousDefault;
    return $base*10;
}
function admin_ai_request_limit(string $feature,string $period='day'): int
{
    return match($feature.':'.$period){
        'alt:hour'=>admin_ai_scaled_limit('OPENAI_ALT_HOURLY_LIMIT',20),
        'alt:day'=>admin_ai_scaled_limit('OPENAI_ALT_DAILY_LIMIT',100),
        'generation:day'=>admin_ai_scaled_limit('SEO_GENERATION_DAILY_LIMIT',50),
        'research:day'=>admin_ai_scaled_limit('SEO_RESEARCH_DAILY_LIMIT',10),
        default=>throw new InvalidArgumentException('Unsupported AI quota.')
    };
}
