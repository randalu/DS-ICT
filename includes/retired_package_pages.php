<?php
declare(strict_types=1);

function package_reference_path_retired(string $path): bool
{
    $path=(string)(parse_url($path,PHP_URL_PATH)??$path);
    return preg_match('~(?:^|/)(?:[0-9]+-day-itinerary|n_day_itinerary\.php)/?$~i',$path)===1;
}

function package_reference_gone(): never
{
    http_response_code(410);header('X-Robots-Tag: noindex, nofollow');
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Page removed</title><main><h1>This reference page has been removed</h1><p>Each private Tour now contains its route, experiences, daily plan and accommodation details.</p><a href="'.htmlspecialchars(base_url('tours'),ENT_QUOTES,'UTF-8').'">Explore private Tours</a></main></html>';exit;
}

/** Old saved prose must not reintroduce links to the removed reference pages. */
function package_reference_rewrite_links(string $html): string
{
    return preg_replace_callback('~href\s*=\s*([\'"])(.*?)\1~i',static function(array $m):string{
        $url=html_entity_decode($m[2],ENT_QUOTES|ENT_HTML5,'UTF-8');
        if(!package_reference_path_retired($url)){return $m[0];}
        $host=strtolower((string)parse_url($url,PHP_URL_HOST));$own=strtolower((string)parse_url(base_url(),PHP_URL_HOST));
        if($host!==''&&!in_array($host,[$own,'srilankaprivatetravels.com','www.srilankaprivatetravels.com'],true)){return $m[0];}
        return 'href='.$m[1].htmlspecialchars(base_url('tours'),ENT_QUOTES,'UTF-8').$m[1];
    },$html)??$html;
}
