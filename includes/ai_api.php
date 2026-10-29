<?php
declare(strict_types=1);

function ai_generate(string $prompt, string $systemPrompt = ''): string
{
    $apiKey = trim((string) site_setting_get('ai_api_key', ''));
    if ($apiKey === '') {
        return '';
    }

    $model = trim((string) site_setting_get('ai_model', 'openrouter/free'));
    $temperature = (float) site_setting_get('ai_temperature', '0.7');
    $maxTokens = (int) site_setting_get('ai_max_tokens', '1024');

    if (!function_exists('curl_init')) {
        return '';
    }

    $messages = [];
    if ($systemPrompt !== '') {
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
    }
    $messages[] = ['role' => 'user', 'content' => $prompt];

    $payload = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => $temperature,
        'max_tokens' => $maxTokens,
    ];

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    if ($ch === false) {
        return '';
    }

    $curlOpts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'HTTP-Referer: ' . APP_URL,
            'X-Title: Island Culture Travels',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ];

    $caBundle = __DIR__ . '/../cacert.pem';
    if (is_file($caBundle)) {
        $curlOpts[CURLOPT_CAINFO] = $caBundle;
    }

    curl_setopt_array($ch, $curlOpts);

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || $raw === false || $raw === '') {
        return '';
    }

    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['choices'][0]['message']['content'])) {
        return '';
    }

    $text = trim((string) $data['choices'][0]['message']['content']);
    return $text;
}
