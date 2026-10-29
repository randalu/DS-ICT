<?php
declare(strict_types=1);

// Reject HTTP before loading configuration, starting a session or connecting to the DB.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

ini_set('display_errors', '0');
ini_set('log_errors', '0');
set_error_handler(static function (): never {
    throw new RuntimeException('Reset runtime error.');
});

function admin_reset_cli_reply(array $result, int $status): never
{
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    exit($status);
}

$arguments = array_slice($argv, 1);
if ($arguments === ['--help']) {
    admin_reset_cli_reply(['usage' => ['--check', '--apply --confirm-ids=1,2 --secret-stdin'],
        'password_input' => 'Use the reviewed private terminal launcher; never put passwords in arguments or environment.',
        'target_ids' => [1, 2]], 0);
}
if ($arguments !== ['--check'] && $arguments !== ['--apply', '--confirm-ids=1,2', '--secret-stdin']) {
    admin_reset_cli_reply(['ok' => false, 'error' => 'Unsupported arguments. Use --help.'], 2);
}

try {
    // This only defines helpers/configuration. No runtime schema-ensure routine is invoked.
    require_once __DIR__ . '/../includes/auth.php';
    if (!defined('ADMIN_CREDENTIAL_BINDING_VERSION') || ADMIN_CREDENTIAL_BINDING_VERSION !== 1) {
        throw new RuntimeException('Credential-binding guard must be deployed before reset.');
    }
    $connection = pdo();
    if ($connection->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
        throw new RuntimeException('Operational reset supports MySQL only.');
    }
    $preflight = admin_credential_reset_preflight($connection);
    if ($arguments === ['--check']) {
        admin_reset_cli_reply(['ok' => true, 'mode' => 'read-only account/schema check', 'preflight' => $preflight,
            'binding_version' => ADMIN_CREDENTIAL_BINDING_VERSION, 'database_writes' => false], 0);
    }
    $input = stream_get_contents(STDIN, 4097);
    if ($input === false || strlen($input) > 4096) {
        throw new RuntimeException('Invalid private input.');
    }
    $payload = json_decode($input, true, 4, JSON_THROW_ON_ERROR);
    if (!is_array($payload) || array_keys($payload) !== ['passwords'] || !is_array($payload['passwords'])) {
        throw new RuntimeException('Invalid private input.');
    }
    $result = admin_credential_reset($connection, $payload['passwords']);
    unset($input, $payload);
    admin_reset_cli_reply(['ok' => true, 'mode' => 'approved account reset', 'result' => $result,
        'old_admin_sessions' => 'rejected on their next guarded request'], 0);
} catch (Throwable) {
    // Never expose exception messages, arguments, DB identities, passwords or hashes.
    admin_reset_cli_reply(['ok' => false, 'error' => 'Reset/check failed. No complete reset confirmed; review securely.'], 1);
}
