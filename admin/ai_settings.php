<?php
declare(strict_types=1);

$adminTitle = 'AI Settings';
$adminPage = 'ai_settings';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$apiKey = trim((string) site_setting_get('ai_api_key', ''));
$model = trim((string) site_setting_get('ai_model', 'openrouter/free'));
$temperature = (float) site_setting_get('ai_temperature', '0.7');
$maxTokens = (int) site_setting_get('ai_max_tokens', '1024');
$promptStyle = trim((string) site_setting_get('ai_prompt_style', 'descriptive'));
$embeddingModel = trim((string) site_setting_get('ai_embedding_model', 'openai/text-embedding-3-small'));

$models = [
    'openrouter/free'                 => 'OpenRouter Free (rotating free model — recommended)',
    'google/gemini-2.0-flash-001'     => 'Gemini 2.0 Flash (fast, free tier)',
    'google/gemini-2.5-flash-preview' => 'Gemini 2.5 Flash Preview',
    'openai/gpt-4o-mini'              => 'GPT-4o Mini (fast, cheap)',
    'openai/gpt-4o'                   => 'GPT-4o (best quality)',
    'anthropic/claude-sonnet-4-5'     => 'Claude Sonnet 4.5 (excellent writing)',
    'anthropic/claude-haiku-4-5'      => 'Claude Haiku 4.5 (fast)',
    'deepseek/deepseek-chat'          => 'DeepSeek Chat (good value)',
    'qwen/qwq-32b'                    => 'Qwen QwQ 32B (reasoning)',
];

$embeddingModels = [
    'openai/text-embedding-3-small' => 'Text Embedding 3 Small (1536 dim, fast, recommended)',
    'openai/text-embedding-3-large' => 'Text Embedding 3 Large (3072 dim, best quality)',
    'google/gemini-embedding-exp-03-07' => 'Gemini Embedding Exp (newest, 3072 dim)',
    'cohere/embed-4' => 'Cohere Embed 4 (1536 dim, multilingual)',
    'mistral/mistral-embed' => 'Mistral Embed (1024 dim)',
];

$promptStyles = [
    'descriptive' => 'Descriptive — balanced detail (default)',
    'concise' => 'Concise — short and factual',
    'marketing' => 'Marketing — compelling travel copy',
];

$oldApiKey = old_input('ai_api_key', '__ICT_NO_OLD__');
if ($oldApiKey !== '__ICT_NO_OLD__') {
    $apiKey = trim((string) $oldApiKey);
}

$oldModel = old_input('ai_model', '__ICT_NO_OLD__');
if ($oldModel !== '__ICT_NO_OLD__') {
    $model = trim((string) $oldModel);
}

$oldTemperature = old_input('ai_temperature', '__ICT_NO_OLD__');
if ($oldTemperature !== '__ICT_NO_OLD__') {
    $temperature = (float) $oldTemperature;
}

$oldMaxTokens = old_input('ai_max_tokens', '__ICT_NO_OLD__');
if ($oldMaxTokens !== '__ICT_NO_OLD__') {
    $maxTokens = (int) $oldMaxTokens;
}

$oldPromptStyle = old_input('ai_prompt_style', '__ICT_NO_OLD__');
if ($oldPromptStyle !== '__ICT_NO_OLD__') {
    $promptStyle = trim((string) $oldPromptStyle);
}

$oldEmbeddingModel = old_input('ai_embedding_model', '__ICT_NO_OLD__');
if ($oldEmbeddingModel !== '__ICT_NO_OLD__') {
    $embeddingModel = trim((string) $oldEmbeddingModel);
}

require __DIR__ . '/_top.php';
?>

<?php if (($errorMsg = flash_get('error')) !== null): ?>
    <div class="alert alert-danger" role="alert"><?= e($errorMsg) ?></div>
<?php endif; ?>
<?php if (($successMsg = flash_get('success')) !== null): ?>
    <div class="alert alert-success" role="status"><?= e($successMsg) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(base_url('admin/ai_settings_save.php')) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="card-soft mb-4">
        <div class="card-body">
            <h4 class="card-title mb-3">API Connection</h4>

            <div class="mb-3">
                <label class="form-label" for="ai_api_key">OpenRouter API Key (for generation)</label>
                <input type="password" id="ai_api_key" name="ai_api_key" class="form-control"
                       value="<?= e($apiKey) ?>" placeholder="sk-or-v1-..." autocomplete="off"
                       aria-describedby="ai_api_key_help" style="max-width:480px;">
                <div id="ai_api_key_help" class="form-text">
                    Get a free key at <a href="https://openrouter.ai/keys" target="_blank" rel="noopener">openrouter.ai/keys</a>.
                    Your key is stored in the database and never exposed publicly.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="ai_embedding_model">AI Search Embedding Model</label>
                <select id="ai_embedding_model" name="ai_embedding_model" class="form-select"
                        aria-describedby="ai_embedding_model_help" style="max-width:480px;">
                    <?php foreach ($embeddingModels as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= $embeddingModel === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="ai_embedding_model_help" class="form-text">
                    Embeddings power the semantic AI Search feature. The OpenRouter API key above is used; no separate key needed. After changing the model, re-run <code>php cli/seed_ai_content.php</code> to rebuild the index.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="ai_model">Default Model</label>
                <select id="ai_model" name="ai_model" class="form-select" style="max-width:400px;">
                    <?php foreach ($models as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= $model === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">The model used for auto-generating content across admin forms.</div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="ai_temperature">Temperature</label>
                    <input type="range" id="ai_temperature" name="ai_temperature" class="form-range"
                           min="0" max="1" step="0.1" value="<?= e((string) $temperature) ?>"
                           oninput="document.getElementById('ai_temp_value').textContent=this.value">
                    <div class="form-text">Creativity: <span id="ai_temp_value"><?= e((string) $temperature) ?></span></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="ai_max_tokens">Max Tokens</label>
                    <select id="ai_max_tokens" name="ai_max_tokens" class="form-select">
                        <?php foreach ([256, 512, 1024, 2048, 4096] as $val): ?>
                            <option value="<?= $val ?>"<?= $maxTokens === $val ? ' selected' : '' ?>><?= $val ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Maximum length of generated content.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="ai_prompt_style">Writing Style</label>
                    <select id="ai_prompt_style" name="ai_prompt_style" class="form-select">
                        <?php foreach ($promptStyles as $value => $label): ?>
                            <option value="<?= e($value) ?>"<?= $promptStyle === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Controls how the AI writes content.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save AI Settings</button>
        <a href="<?= e(base_url('admin/dashboard.php')) ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
</form>

<?php require __DIR__ . '/_bottom.php'; ?>
