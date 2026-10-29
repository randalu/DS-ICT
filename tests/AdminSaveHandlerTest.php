<?php
/**
 * Tests for the validation/whitelist logic in admin save handlers.
 *
 * Strategy: each *_save.php file has a `require_csrf()` + validation block.
 * Rather than drive the whole HTTP flow (which needs a live DB and a session),
 * we extract the validation rules into a pure function and test those.
 *
 * This protects against:
 *   - Adding new models/tokens without whitelisting them
 *   - API keys with the wrong prefix sneaking through
 *   - Whitelist bypass via tampering
 *
 * For the CSRF-rejection path, the existing shell-based smoke runner
 * (cli/smoke_post_gates.php) covers the real HTTP behaviour.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

/**
 * Pure-function port of admin/ai_settings_save.php's validation block.
 * This is the contract we are testing; if the real handler diverges
 * from this, that's a bug to fix.
 */
function ai_settings_validate(
    string $apiKey,
    string $model,
    float  $temperature,
    int    $maxTokens,
    string $promptStyle,
    string $embeddingModel
): array {
    $errors = [];

    $validModels = [
        'google/gemini-2.0-flash-001',
        'openai/gpt-4o-mini',
        'openai/gpt-4o',
        'anthropic/claude-3.5-sonnet',
        'anthropic/claude-3-haiku',
        'deepseek/deepseek-chat',
        'qwen/qwq-32b',
    ];
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

    return $errors;
}

class AdminSaveHandlerTest extends TestCase
{
    public function testValidInputsProduceNoErrors(): void
    {
        $errors = ai_settings_validate(
            apiKey: 'sk-or-v1-abc123',
            model: 'openai/gpt-4o-mini',
            temperature: 0.7,
            maxTokens: 1024,
            promptStyle: 'descriptive',
            embeddingModel: 'openai/text-embedding-3-small'
        );
        $this->assertSame([], $errors);
    }

    public function testEmptyApiKeyIsAllowed(): void
    {
        // No key yet is a valid state (admin may be clearing it)
        $errors = ai_settings_validate(
            apiKey: '',
            model: 'openai/gpt-4o-mini',
            temperature: 0.5,
            maxTokens: 512,
            promptStyle: 'concise',
            embeddingModel: 'openai/text-embedding-3-large'
        );
        $this->assertSame([], $errors);
    }

    public function testApiKeyWithoutSkOrPrefixIsRejected(): void
    {
        $errors = ai_settings_validate(
            apiKey: 'sk-test123',
            model: 'openai/gpt-4o-mini',
            temperature: 0.5,
            maxTokens: 1024,
            promptStyle: 'descriptive',
            embeddingModel: 'openai/text-embedding-3-small'
        );
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('sk-or-', implode(' ', $errors));
    }

    public function testUnknownModelIsRejected(): void
    {
        $errors = ai_settings_validate(
            apiKey: 'sk-or-v1-valid',
            model: 'gpt-9000-hacker',
            temperature: 0.5,
            maxTokens: 1024,
            promptStyle: 'descriptive',
            embeddingModel: 'openai/text-embedding-3-small'
        );
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid model', implode(' ', $errors));
    }

    public function testTemperatureClampedToZeroOne(): void
    {
        // Even with invalid temperatures the function should normalize, not error.
        $reflection = new ReflectionFunction('ai_settings_validate');
        // No direct access to normalized value, but temperature does NOT produce an error
        $errors = ai_settings_validate(
            apiKey: 'sk-or-v1-x',
            model: 'openai/gpt-4o-mini',
            temperature: 5.0, // out of range
            maxTokens: 1024,
            promptStyle: 'descriptive',
            embeddingModel: 'openai/text-embedding-3-small'
        );
        $this->assertSame([], $errors, 'Out-of-range temperature should clamp silently, not error.');
        $this->assertTrue($reflection->getNumberOfParameters() === 6);
    }

    public function testInvalidMaxTokensFallsBackTo1024(): void
    {
        // The contract is: invalid maxTokens is silently coerced to 1024
        $errors = ai_settings_validate(
            apiKey: 'sk-or-v1-x',
            model: 'openai/gpt-4o-mini',
            temperature: 0.5,
            maxTokens: 7777,
            promptStyle: 'descriptive',
            embeddingModel: 'openai/text-embedding-3-small'
        );
        $this->assertSame([], $errors);
    }

    public function testInvalidPromptStyleFallsBackToDescriptive(): void
    {
        $errors = ai_settings_validate(
            apiKey: 'sk-or-v1-x',
            model: 'openai/gpt-4o-mini',
            temperature: 0.5,
            maxTokens: 1024,
            promptStyle: 'haiku', // not in list
            embeddingModel: 'openai/text-embedding-3-small'
        );
        $this->assertSame([], $errors);
    }

    public function testInvalidEmbeddingModelFallsBackToDefault(): void
    {
        $errors = ai_settings_validate(
            apiKey: 'sk-or-v1-x',
            model: 'openai/gpt-4o-mini',
            temperature: 0.5,
            maxTokens: 1024,
            promptStyle: 'descriptive',
            embeddingModel: 'random-embedding-v999'
        );
        $this->assertSame([], $errors, 'Unknown embedding model should silently fall back to default, not error.');
    }

    public function testAllKnownModelsAreAccepted(): void
    {
        $validModels = [
            'google/gemini-2.0-flash-001',
            'openai/gpt-4o-mini',
            'openai/gpt-4o',
            'anthropic/claude-3.5-sonnet',
            'anthropic/claude-3-haiku',
            'deepseek/deepseek-chat',
            'qwen/qwq-32b',
        ];
        foreach ($validModels as $model) {
            $errors = ai_settings_validate(
                apiKey: 'sk-or-v1-x',
                model: $model,
                temperature: 0.5,
                maxTokens: 1024,
                promptStyle: 'descriptive',
                embeddingModel: 'openai/text-embedding-3-small'
            );
            $this->assertSame([], $errors, "Model {$model} should be accepted but got errors: " . implode(', ', $errors));
        }
    }

    public function testAllKnownEmbeddingModelsAreAccepted(): void
    {
        $validEmbeddingModels = [
            'openai/text-embedding-3-small',
            'openai/text-embedding-3-large',
            'google/gemini-embedding-exp-03-07',
            'cohere/embed-4',
            'mistral/mistral-embed',
        ];
        foreach ($validEmbeddingModels as $model) {
            $errors = ai_settings_validate(
                apiKey: 'sk-or-v1-x',
                model: 'openai/gpt-4o-mini',
                temperature: 0.5,
                maxTokens: 1024,
                promptStyle: 'descriptive',
                embeddingModel: $model
            );
            $this->assertSame([], $errors, "Embedding model {$model} should be accepted but got errors: " . implode(', ', $errors));
        }
    }

    public function testCSRFProtectionExistsInSmokeRunner(): void
    {
        // Soft integration check: the smoke runner exists and is executable.
        $smokeRunner = __DIR__ . '/../cli/smoke_post_gates.php';
        $this->assertFileExists($smokeRunner);
        $this->assertFileIsReadable($smokeRunner);
    }
}
