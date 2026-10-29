<?php
declare(strict_types=1);
require_once __DIR__.'/seo_policy.php';
require_once __DIR__.'/admin_openai.php';
function seo_openai_response(string $key,array $payload,?callable $transport=null): array { $payload['model']=admin_openai_model('seo'); return admin_openai_response($key,$payload,$transport); }

function seo_output_schema(string $kind): array
{
    $list = ['type' => 'array', 'items' => ['type' => 'string']];
    $properties = $kind === 'generation'
        ? ['replacement' => ['type' => 'string'], 'explanation' => ['type' => 'string'], 'intent' => ['type' => 'string'], 'keywords' => $list, 'warnings' => $list]
        : ['findings' => $list, 'uncertainties' => $list, 'actions' => $list, 'primary_phrase' => ['type' => 'string'], 'supporting_phrases' => $list, 'questions' => $list, 'intent' => ['type' => 'string']];
    return ['type' => 'json_schema', 'name' => 'seo_' . $kind, 'strict' => true,
        'schema' => ['type' => 'object', 'properties' => $properties, 'required' => array_keys($properties), 'additionalProperties' => false]];
}

function seo_decode_output(string $text, string $kind): array
{
    try { $data = json_decode($text, true, 32, JSON_THROW_ON_ERROR); }
    catch (JsonException) { throw new RuntimeException('The model returned malformed structured output.'); }
    $schema = seo_output_schema($kind)['schema'];
    if (!is_array($data) || array_diff(array_keys($data), $schema['required']) || array_diff($schema['required'], array_keys($data))) { throw new RuntimeException('The model returned unexpected fields.'); }
    foreach ($schema['properties'] as $field => $definition) {
        if ($definition['type'] === 'string') {
            if (!is_string($data[$field]) || mb_strlen($data[$field]) > 30000) { throw new RuntimeException('Invalid model text.'); }
        } else {
            if (!is_array($data[$field]) || !array_is_list($data[$field]) || count($data[$field]) > 30) { throw new RuntimeException('Invalid model list.'); }
            foreach ($data[$field] as $value) { if (!is_string($value) || mb_strlen($value) > 2000) { throw new RuntimeException('Invalid model list item.'); } }
        }
    }
    if (preg_match('/(?:search volume|keyword difficulty|monthly searches|CPC)\s*[:=]?\s*\d/iu', json_encode($data, JSON_UNESCAPED_UNICODE))) {
        throw new RuntimeException('The model supplied unmeasured keyword metrics. No result was accepted.');
    }
    return $data;
}
