<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/driver_auth.php';

if (driver_is_authenticated()) {
    redirect('driver/index.php');
}

$error = null;

if (request_is_post()) {
    verify_csrf();

    $username = trim(
        is_string($_POST['username'] ?? null)
            ? $_POST['username']
            : ''
    );

    $password = is_string($_POST['password'] ?? null)
        ? $_POST['password']
        : '';

    $credentials = driver_credentials();

    if (
        $credentials['username'] === ''
        || $credentials['password'] === ''
    ) {
        $error = 'Akun driver belum dikonfigurasi di server.';
    } elseif (
        $username === ''
        || $password === ''
        || strlen($username) > 100
        || strlen($password) > 4096
    ) {
        $error = 'Username atau password tidak valid.';
    } elseif (
        hash_equals($credentials['username'], $username)
        && hash_equals($credentials['password'], $password)
    ) {
        driver_login($username);

        redirect('driver/index.php');
    } else {
        $error = 'Username atau password tidak sesuai.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#176b45">

    <title>Login Driver — Anter Ajah</title>

    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        .driver-login-page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            background: #f5f8f6;
        }

        .driver-login-card {
            width: 100%;
            max-width: 400px;
            padding: 28px;
            border: 1px solid #e3e9e5;
            border-radius: 18px;
            background: #fff;
        }

        .driver-login-card h1 {
            margin: 0 0 8px;
        }

        .driver-login-card form {
            display: grid;
            gap: 16px;
            margin-top: 24px;
        }

        .driver-login-field {
            display: grid;
            gap: 8px;
        }

        .driver-login-field input {
            width: 100%;
            min-height: 46px;
            padding: 12px;
            border: 1px solid #d8e0db;
            border-radius: 10px;
            font: inherit;
        }

        .driver-login-card button {
            width: 100%;
        }

        .driver-login-error {
            padding: 12px;
            border-radius: 10px;
            color: #a32323;
            background: #fff0f0;
        }
    </style>
</head>
<body>
    <main class="driver-login-page">
        <section class="driver-login-card">
            <h1>Anter Ajah</h1>
            <p class="muted">Masuk ke dashboard driver.</p>

            <?php if ($error !== null): ?>
                <p class="driver-login-error" role="alert">
                    <?= e($error) ?>
                </p>
            <?php endif; ?>

            <form method="post" action="/driver/login.php">
                <?= csrf_field() ?>

                <label class="driver-login-field">
                    <span>Username</span>
                    <input
                        type="text"
                        name="username"
                        autocomplete="username"
                        maxlength="100"
                        required
                    >
                </label>

                <label class="driver-login-field">
                    <span>Password</span>
                    <input
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        maxlength="4096"
                        required
                    >
                </label>

                <button
                    class="button button-primary"
                    type="submit"
                >
                    Masuk
                </button>
            </form>

            <p class="muted">
                Dashboard khusus operasional pengantaran.
            </p>
        </section>
    </main>
</body>
</html>
