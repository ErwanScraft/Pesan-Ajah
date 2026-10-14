<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (is_logged_in()) {
    $user = current_user();

    if (($user['role'] ?? '') === 'admin') {
        redirect('/admin/');
    }

    if (($user['role'] ?? '') === 'warung') {
        redirect('/warung/login.php');
    }
}

$error = null;

if (request_is_post()) {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan kata sandi wajib diisi.';
    } else {
        $stmt = database()->prepare(
            'SELECT id, username, password_hash, role, is_active
             FROM users
             WHERE username = :username
             LIMIT 1'
        );

        $stmt->execute([
            'username' => $username,
        ]);

        $user = $stmt->fetch();

        if (
            $user
            && $user['role'] === 'admin'
            && (bool) $user['is_active']
            && password_verify($password, $user['password_hash'])
        ) {
            login_user($user);

            redirect('/admin/');
        }

        $error = 'Username atau kata sandi tidak sesuai.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="theme-color" content="#176b45">

    <title>Login Admin — Pesan Ajah</title>

    <link
        rel="icon"
        type="image/webp"
        href="/assets/images/logo.webp"
    >

    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        .auth-page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f5f8f6;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            padding: 28px;
            border: 1px solid #e5eae7;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 12px 36px rgb(0 0 0 / 5%);
        }

        .auth-card h1 {
            margin: 0 0 8px;
        }

        .auth-card form {
            display: grid;
            gap: 16px;
            margin-top: 24px;
        }

        .auth-field {
            display: grid;
            gap: 8px;
        }

        .auth-field input {
            width: 100%;
            min-height: 46px;
            padding: 12px;
            border: 1px solid #d8e0db;
            border-radius: 10px;
            font: inherit;
        }

        .auth-error {
            padding: 12px;
            border-radius: 10px;
            color: #a32323;
            background: #fff0f0;
        }

        .auth-brand {
            display: block;
            margin-bottom: 24px;
        }
    </style>
</head>

<body>
    <main class="auth-page">
        <section class="auth-card">
            <a class="auth-brand" href="/">
                <img
                    src="/assets/images/logo.webp"
                    width="48"
                    height="48"
                    alt="Pesan Ajah"
                >
            </a>

            <h1>Admin Panel</h1>

            <p>Masuk untuk mengelola warung Pesan Ajah.</p>

            <?php if ($error !== null): ?>
                <p class="auth-error" role="alert">
                    <?= e($error) ?>
                </p>
            <?php endif; ?>

            <form method="post" action="/admin/login.php">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <label class="auth-field">
                    <span>Username</span>

                    <input
                        type="text"
                        name="username"
                        autocomplete="username"
                        required
                        maxlength="50"
                    >
                </label>

                <label class="auth-field">
                    <span>Kata sandi</span>

                    <input
                        type="password"
                        name="password"
                        autocomplete="current-password"
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

            <p>
                <a href="/">Kembali ke marketplace</a>
            </p>
        </section>
    </main>
</body>
</html>