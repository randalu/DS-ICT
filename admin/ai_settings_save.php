<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/ai_settings.php');
}

guard_post_size();
require_csrf();

$apiKey = trim((string) post_input('ai_api_key', ''));
$model = trim((string) post_input('ai_model', 'openrouter/free'));
$temperature = (float) post_input('ai_temperature', '0.7');
$maxTokens = (int) post_input('ai_max_tokens', '1024');
$promptStyle = trim((string) post_input('ai_prompt_style', 'descriptive'));
$embeddingModel = trim((string) post_input('ai_embedding_model', 'openai/text-embedding-3-small'));

old_input_set($_POST);

$errors = [];

$validModels = ['openrouter/free', 'google/gemini-2.0-flash-001', 'google/gemini-2.5-flash-preview', 'openai/gpt-4o-mini', 'openai/gpt-4o', 'anthropic/claude-sonnet-4-5', 'anthropic/claude-haiku-4-5', 'deepseek/deepseek-chat', 'qwen/qwq-32b'];
if (!in_array($model, $validModels, true)) {
    $errors[] = 'Invalid model selected.';
}

$temperature = max(0, min(1, $temperature));

$validTokenValues = [256, 512, 1024, 2048, 4096];
if (!in_array($maxTokens, $validTokenValues, true)) {
    $maxTokens = 1024;
}

$validStyles = ['descriptive', 'concise', 'marketing'];
if (!in_array($promptStyle, $validStyles, true)) {
    $promptStyle = 'descriptive';
}

$validEmbeddingModels = [
    'openai/text-embedding-3-small',
    'openai/text-embedding-3-large',
    'google/gemini-embedding-exp-03-07',
    'cohere/embed-4',
    'mistral/mistral-embed',
];
if (!in_array($embeddingModel, $validEmbeddingModels, true)) {
    $embeddingModel = 'openai/text-embedding-3-small';
}

if ($apiKey !== '' && !str_starts_with($apiKey, 'sk-or-')) {
    $errors[] = 'API key should start with "sk-or-". Verify your OpenRouter key.';
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect('admin/ai_settings.php');
}

site_setting_set('ai_api_key', $apiKey);
site_setting_set('ai_model', $model);
site_setting_set('ai_temperature', (string) $temperature);
site_setting_set('ai_max_tokens', (string) $maxTokens);
site_setting_set('ai_prompt_style', $promptStyle);
site_setting_set('ai_embedding_model', $embeddingModel);

old_input_clear();
flash_set('success', 'AI settings saved.');
redirect('admin/ai_settings.php');
