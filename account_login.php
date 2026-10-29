<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/customer_auth.php';
require_once __DIR__ . '/includes/schema.php';

ensure_customer_accounts_schema();

$returnTo = trim((string) get_input('return', ''));
$safeReturnTo = $returnTo !== '' && str_starts_with($returnTo, '/account') ? ltrim($returnTo, '/') : 'account';

if (customer_check()) {
    redirect($safeReturnTo);
}

$googleAuthUrl = customer_google_login_enabled() ? customer_google_auth_url('/' . $safeReturnTo) : '';

$seoTitle = 'Customer Account Login | ' . APP_NAME;
$seoDescription = 'Sign in with Google to track your Island Culture Travels inquiry and messages.';
$seoCanonical = base_url('account/login');
$seoImage = DEFAULT_OG_IMAGE;
$seoType = 'website';
$seoRobots = 'noindex,nofollow';
$bodyClass = 'page-account-login';

require __DIR__ . '/includes/header.php';
?>

<section class="account-hero py-5">
    <div class="container">
        <div class="account-hero-card">
            <span class="section-kicker">Customer Account</span>
            <h1>Track Your Sri Lanka Tour Inquiry</h1>
            <p>Use your verified Google email to see inquiries linked to the same email address and exchange messages with our team.</p>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container account-container">
        <?php if ($error = flash_get('error')): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success = flash_get('success')): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="account-card account-login-card">
            <h2>Sign in with Google</h2>
            <p class="text-secondary">Google login is used only to verify your email and protect your inquiry messages. Admin login remains separate.</p>

            <?php if ($googleAuthUrl !== ''): ?>
                <a class="btn btn-success btn-lg" href="<?= e($googleAuthUrl) ?>">Continue with Google</a>
            <?php else: ?>
                <div class="alert alert-light border mb-3">
                    Customer login is not enabled yet. Add the Google Client ID and Client Secret in admin analytics settings, then enable it.
                </div>
                <a class="btn btn-outline-dark" href="<?= e(base_url('contact')) ?>">Contact Us Instead</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
