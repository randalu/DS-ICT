<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
header('Cache-Control: private, no-store');
header('Vary: Cookie', false);
if (!is_post()) { http_response_code(405); header('Allow: POST'); exit; }
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 4096) { http_response_code(413); exit; }
guard_post_size();
require_csrf();
$json = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
try {
    ict_privacy_save_choice($_POST);
    if ($json) { header('Content-Type: application/json; charset=UTF-8'); echo json_encode(['ok' => true, 'config' => ict_privacy_browser_config()], JSON_THROW_ON_ERROR); exit; }
    flash_set('success', 'Your privacy choices were saved.');
    $return = ict_privacy_local_return(post_input('return_path'));
    header('Location: ' . ict_privacy_url($return), true, 303);
} catch (Throwable $exception) {
    $message = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Could not save your privacy choices. Please try again.';
    if ($json) { http_response_code(422); header('Content-Type: application/json; charset=UTF-8'); echo json_encode(['ok' => false, 'error' => $message], JSON_THROW_ON_ERROR); }
    else { flash_set('error', $message); header('Location: ' . ict_privacy_url('privacy_choices.php'), true, 303); }
}
exit;
