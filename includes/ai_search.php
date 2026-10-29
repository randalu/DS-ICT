<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Fetch a text embedding via OpenRouter.
 * Uses the same ai_api_key as content generation so production only needs
 * one key. Falls back to the legacy Gemini key + endpoint if configured.
 */
function ai_get_embedding(string $text): ?array
{
    $apiKey = trim((string) site_setting_get('ai_api_key', ''));
    if ($apiKey === '') {
        return null;
    }

    $model = trim((string) site_setting_get('ai_embedding_model', 'openai/text-embedding-3-small'));

    $url = 'https://openrouter.ai/api/v1/embeddings';

    $payload = [
        'model' => $model,
        'input' => $text,
    ];

    if (!function_exists('curl_init')) {
        return null;
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return null;
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
        CURLOPT_TIMEOUT => 20,
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
        return null;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return null;
    }

    // OpenRouter returns { data: [{ embedding: [...] }] }
    if (isset($data['data'][0]['embedding']) && is_array($data['data'][0]['embedding'])) {
        return $data['data'][0]['embedding'];
    }

    // Legacy Gemini shape fallback
    if (isset($data['embedding']['values']) && is_array($data['embedding']['values'])) {
        return $data['embedding']['values'];
    }

    return null;
}

function ai_cosine_similarity(array $vecA, array $vecB): float
{
    $dotProduct = 0.0;
    $normA = 0.0;
    $normB = 0.0;

    foreach ($vecA as $i => $valA) {
        $valB = $vecB[$i] ?? 0.0;
        $dotProduct += $valA * $valB;
        $normA += $valA * $valA;
        $normB += $valB * $valB;
    }

    if ($normA === 0.0 || $normB === 0.0) {
        return 0.0;
    }

    return $dotProduct / (sqrt($normA) * sqrt($normB));
}

function ai_search_query(string $query, int $limit = 5, ?callable $embedding = null): array
{
    // Trusted server-side transport seam; admin/CLI callers retain their behavior.
    if (!db_table_exists('search_index')) { return []; }
    $queryEmbedding = ($embedding ?? 'ai_get_embedding')($query);
    if (!$queryEmbedding) {
        return [];
    }

    $records = fetch_all("SELECT id, title, url, excerpt, embedding_json FROM search_index");
    $results = [];

    foreach ($records as $record) {
        if (empty($record['embedding_json'])) continue;

        $docEmbedding = json_decode($record['embedding_json'], true);
        if (!is_array($docEmbedding)) continue;

        $score = ai_cosine_similarity($queryEmbedding, $docEmbedding);

        $results[] = [
            'id' => $record['id'],
            'title' => $record['title'],
            'url' => $record['url'],
            'excerpt' => $record['excerpt'],
            'score' => $score
        ];
    }

    usort($results, static fn($a, $b) => $b['score'] <=> $a['score']);

    return array_slice($results, 0, $limit);
}
