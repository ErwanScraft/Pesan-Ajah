<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (is_logged_in()) {
    $user = current_user();

    if (
        $user
        && $user['role'] === 'warung'
        && (bool) $user['is_active']
        && password_verify($password, $user['password_hash'])
    ) {
        $storeStmt = database()->prepare(
            'SELECT id, is_active
             FROM stores
             WHERE user_id = :user_id
             LIMIT 1'
        );
    
        $storeStmt->execute([
            'user_id' => (int) $user['id'],
        ]);
    
        $store = $storeStmt->fetch();
    
        if ($store && (bool) $store['is_active']) {
            login_user($user);
    
            redirect('/warung/dashboard.html');
        }
    }

    if (($user['role'] ?? '') === 'admin') {
        redirect('/admin/');
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
            'SELECT
                id,
                username,
                password_hash,
                role,
                is_active
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
            && $user['role'] === 'warung'
            && (bool) $user['is_active']
            && password_verify($password, $user['password_hash'])
        ) {
            $storeStmt = database()->prepare(
                'SELECT id, is_active
                 FROM stores
                 WHERE user_id = :user_id
                 LIMIT 1'
            );

            $storeStmt->execute([
                'user_id' => (int) $user['id'],
            ]);

            $store = $storeStmt->fetch();

            if ($store && (bool) $store['is_active']) {
                login_user($user);

                redirect('/warung/dashboard.html');
            }
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

    <title>Login Warung — Pesan Ajah</title>

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
            background: white;
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
    </style>
</head>

<body>
    <main class="auth-page">
        <section class="auth-card">
            <a href="/">
                <img
                    src="/assets/images/logo.webp"
                    width="48"
                    height="48"
                    alt="Pesan Ajah"
                >
            </a>

            <h1>Login warung</h1>

            <p class="muted">
                Masuk untuk mengelola warung Anda.
            </p>

            <?php if ($error !== null): ?>
                <p class="auth-error" role="alert">
                    <?= e($error) ?>
                </p>
            <?php endif; ?>

            <form method="post" action="/warung/login.php">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <label class="auth-field">
                    <span>Username</span>

                    <input
                        name="username"
                        autocomplete="username"
                        maxlength="50"
                        required
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
                    Masuk ke warung
                </button>
            </form>

            <p>
                <a href="/">Kembali ke marketplace</a>
            </p>
        </section>
    </main>
</body>
</html>