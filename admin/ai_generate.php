<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

header('Content-Type: application/json; charset=utf-8');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

guard_post_size();
require_csrf();

$field = trim((string) post_input('field', ''));
$pageType = trim((string) post_input('page_type', ''));
$pageTitle = trim((string) post_input('page_title', ''));
$fieldLabel = trim((string) post_input('field_label', ''));
$existingContent = trim((string) post_input('existing_content', ''));
$fieldContext = trim((string) post_input('field_context', ''));

if ($field === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Field name is required']);
    exit;
}

require_once __DIR__ . '/../includes/ai_api.php';

$style = trim((string) site_setting_get('ai_prompt_style', 'descriptive'));

$styleGuide = match ($style) {
    'concise' => 'Write 1-3 short sentences. Be direct and factual. Avoid fluff.',
    'marketing' => 'Write compelling, evocative marketing copy. Use vivid language that inspires travel. 2-4 sentences.',
    default => 'Write 2-4 informative and readable sentences. Balance detail with clarity.',
};

$systemPrompt = 'You are a professional travel content writer for Island Culture Travels, a Sri Lanka private tour operator. '
    . 'Write in clear, natural English. '
    . 'Do not use markdown, bullet points, or headings unless the output is explicitly requested as structured text. '
    . 'Do not include greetings, disclaimers, or meta-commentary. '
    . $styleGuide;

if ($fieldContext !== '') { // field_context
    $systemPrompt .= ' Specific guidance for this field: ' . $fieldContext;
}

$contextParts = [];
if ($pageType !== '') {
    $contextParts[] = "Page type: {$pageType}";
}
if ($pageTitle !== '') {
    $contextParts[] = "Page title: {$pageTitle}";
}
if ($fieldLabel !== '') {
    $contextParts[] = "Field: {$fieldLabel}";
}
$context = implode(' | ', $contextParts);

if ($existingContent !== '') {
    $prompt = 'Improve and rewrite the following text. Make it more engaging, clear, and professional. '
        . 'Keep the same meaning and factual accuracy but enhance the wording, flow, and impact.';
    if ($context !== '') {
        $prompt .= ' Context: ' . $context . '.';
    }
    $prompt .= "\n\nText to improve:\n" . $existingContent;
} else {
    $prompt = $context !== '' ? "{$context}\n{$field}" : $field;
}

$text = ai_generate($prompt, $systemPrompt);

if ($text === '') {
    http_response_code(502);
    echo json_encode(['error' => 'AI generation failed. Check your API key and model settings.']);
    exit;
}

echo json_encode(['text' => $text], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
