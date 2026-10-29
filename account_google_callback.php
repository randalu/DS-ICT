<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/customer_auth.php';

ensure_customer_accounts_schema();

if (!customer_google_login_enabled()) {
    flash_set('error', 'Customer Google login is not enabled yet.');
    redirect('account/login');
}

$state = trim((string) get_input('state', ''));
$expectedState = trim((string) ($_SESSION[CUSTOMER_OAUTH_STATE_KEY] ?? ''));
$code = trim((string) get_input('code', ''));
$oauthError = trim((string) get_input('error', ''));

unset($_SESSION[CUSTOMER_OAUTH_STATE_KEY]);

if ($oauthError !== '') {
    flash_set('error', 'Google login was cancelled or blocked.');
    redirect('account/login');
}

if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
    flash_set('error', 'Google login state mismatch. Please try again.');
    redirect('account/login');
}

if ($code === '') {
    flash_set('error', 'Google did not return a login code. Please try again.');
    redirect('account/login');
}

try {
    $token = customer_google_exchange_code($code);
    $accessToken = trim((string) ($token['access_token'] ?? ''));
    if ($accessToken === '') {
        throw new RuntimeException('Google did not return an access token.');
    }

    $profile = customer_google_userinfo($accessToken);
    customer_login_from_google_profile($profile);
    flash_set('success', 'Signed in successfully.');

    $returnTo = trim((string) ($_SESSION[CUSTOMER_OAUTH_RETURN_KEY] ?? ''));
    unset($_SESSION[CUSTOMER_OAUTH_RETURN_KEY]);
    redirect($returnTo !== '' && str_starts_with($returnTo, '/account') ? ltrim($returnTo, '/') : 'account');
} catch (Throwable $e) {
    flash_set('error', 'Google login could not be completed. Please try again or contact us.');
    redirect('account/login');
}
