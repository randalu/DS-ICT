<?php
declare(strict_types=1);

function recaptcha_site_key(): string
{
    $adminKey = site_setting_get('recaptcha_site_key', '');
    return $adminKey !== '' ? $adminKey : RECAPTCHA_SITE_KEY;
}

function recaptcha_secret_key(): string
{
    $adminKey = site_setting_get('recaptcha_secret_key', '');
    return $adminKey !== '' ? $adminKey : RECAPTCHA_SECRET_KEY;
}

function recaptcha_enabled(): bool
{
    $adminEnabled = site_setting_get('recaptcha_enabled', '');
    if ($adminEnabled === '1') {
        return recaptcha_site_key() !== '' && recaptcha_secret_key() !== '';
    }
    return RECAPTCHA_ENABLED && recaptcha_site_key() !== '' && recaptcha_secret_key() !== '';
}

function recaptcha_verify(?string $token): bool
{
    if (!$token) {
        return false;
    }

    $secret = recaptcha_secret_key();
    if ($secret === '') {
        return false;
    }

    try {
        $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 10,
                'content' => http_build_query([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => request_ip_address(),
                ]),
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            ],
        ]));

        if ($response === false) {
            return false;
        }

        $data = json_decode($response, true);
        return is_array($data) && ($data['success'] ?? false) === true;
    } catch (Throwable) {
        return false;
    }
}

function recaptcha_html(): string
{
    $siteKey = recaptcha_site_key();
    if (!recaptcha_enabled() || $siteKey === '') {
        return '';
    }

    return '<div class="g-recaptcha mb-3" data-sitekey="' . e($siteKey) . '"></div>';
}
