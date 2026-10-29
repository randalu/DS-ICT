<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image_manager.php';

$returnKey = in_array(get_input('return', ''), ['image_manager', 'seo_manager', 'quotations'], true) ? get_input('return', '') : '';
if (admin_check()) { redirect(image_manager_return($returnKey)); }

$errors = [];

if (is_post()) {
    $requestIp = request_ip_address();

    if (admin_login_rate_limit_exceeded($requestIp)) {
        $errors[] = 'Too many failed login attempts. Please wait 15 minutes and try again.';
    }

    require_csrf();

    $login = trim((string) post_input('login', ''));
    $password = (string) post_input('password', '');

    old_input_set($_POST);

    if ($login === '') {
        $errors[] = 'Email or username is required.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (!$errors) {
        if (admin_login($login, $password)) {
            old_input_clear();
            flash_set('success', 'Welcome back.');
            redirect(image_manager_return($returnKey));
        }

        $errors[] = 'Invalid login credentials.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Login | <?= e(APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/typography.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/typography.css')) ?>">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; }
        .login-brand { text-align: center; margin-bottom: 1.5rem; }
        .login-brand h1 { font-size: 1.3rem; font-weight: 800; color: #111827; }
        .login-brand p { font-size: 0.8rem; color: #6b7280; font-weight: 600; margin: 0; }
        .login-card { border: 1px solid #dfe5ef; border-radius: 1rem; box-shadow: 0 18px 42px rgba(15,23,42,0.08); background: #fff; }
        .login-card .card-body { padding: 2rem; }
    </style>
</head>
<body>
<div class="login-card" style="width:100%;max-width:420px;">
    <div class="card-body">
        <div class="login-brand">
            <h1><?= e(APP_NAME) ?></h1>
            <p>Administration Panel</p>
        </div>

        <?php if ($msg = flash_get('error')): ?>
            <div class="alert alert-danger"><?= e($msg) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="admin-login" class="form-label">Email or Username</label>
                <input type="text" id="admin-login" name="login" class="form-control" value="<?= e((string) old_input('login', '')) ?>" required autofocus>
            </div>

            <div class="mb-4">
                <label for="admin-password" class="form-label">Password</label>
                <input type="password" id="admin-password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Sign In</button>
        </form>
    </div>
</div>
</body>
</html>
