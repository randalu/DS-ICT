<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/public_ai_search.php';

header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
if (is_post()) {
    // Route-specific limit precedes the shared POST-size and CSRF helpers.
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > ICT_PUBLIC_AI_MAX_BODY) {
        http_response_code(413); exit('This search request is too large.');
    }
    guard_post_size();
    require_csrf();
}
$publicAiState = ict_public_ai_request($_SERVER, $_GET, $_POST, $_SESSION);
http_response_code($publicAiState['status']);
if ($publicAiState['status'] === 405) { header('Allow: GET, POST'); }
if ($publicAiState['status'] === 429) { header('Retry-After: 900'); }
$seoTitle = 'AI Search | ' . APP_NAME;
$seoDescription = 'Explore Island Culture Travels destinations, itineraries, tours and experiences for Sri Lanka trip ideas.';
$seoCanonical = base_url('ai-search');
$seoRobots = 'noindex,follow,max-image-preview:large';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/public_ai_search_view.php';
require __DIR__ . '/includes/footer.php';
