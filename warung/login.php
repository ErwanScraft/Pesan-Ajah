<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Pemeriksaan sesi
|--------------------------------------------------------------------------
*/

if (is_logged_in()) {
    $currentUser = current_user();

    if (($currentUser['role'] ?? '') === 'admin') {
        redirect('/admin/');
    }

    if (($currentUser['role'] ?? '') === 'warung') {
        $stmt = database()->prepare(
            'SELECT
                u.id,
                u.username,
                u.is_active AS user_is_active,
                s.id AS store_id,
                s.is_active AS store_is_active
             FROM users u
             INNER JOIN stores s ON s.user_id = u.id
             WHERE u.id = :user_id
               AND u.role = :role
             LIMIT 1'
        );

        $stmt->execute([
            'user_id' => (int) $currentUser['id'],
            'role' => 'warung',
        ]);

        $account = $stmt->fetch();

        if (
            $account
            && (bool) $account['user_is_active']
            && (bool) $account['store_is_active']
        ) {
            redirect('/warung/dashboard.php');
        }

        logout_user();

        flash(
            'error',
            'Akun warung tidak aktif atau profil warung tidak tersedia.'
        );

        redirect('/warung/login.php');
    }

    logout_user();
}

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

$error = flash('error');

if (request_is_post()) {
    verify_csrf();

    $username = trim(
        (string) ($_POST['username'] ?? '')
    );

    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan kata sandi wajib diisi.';
    } elseif (
        strlen($username) > 50
        || strlen($password) > 4096
    ) {
        $error = 'Username atau kata sandi tidak valid.';
    } else {
        $stmt = database()->prepare(
            'SELECT
                u.id,
                u.username,
                u.password_hash,
                u.role,
                u.is_active AS user_is_active,
                s.id AS store_id,
                s.is_active AS store_is_active
             FROM users u
             LEFT JOIN stores s ON s.user_id = u.id
             WHERE u.username = :username
             LIMIT 1'
        );

        $stmt->execute([
            'username' => $username,
        ]);

        $user = $stmt->fetch();

        /*
         * Hanya akun warung yang dibuat admin
         * dan memiliki profil warung yang bisa login.
         */
        $validAccount = (
            $user
            && $user['role'] === 'warung'
            && (bool) $user['user_is_active']
            && $user['store_id'] !== null
            && (bool) $user['store_is_active']
            && password_verify(
                $password,
                (string) $user['password_hash']
            )
        );

        if ($validAccount) {
            login_user([
                'id' => (int) $user['id'],
                'name' => (string) $user['username'],
                'role' => 'warung',
            ]);

            redirect('/warung/dashboard.php');
        }

        $error = 'Username atau kata sandi tidak sesuai.';
    }
}

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta name="theme-color" content="#176b45">

    <title>Login Warung — Pesan Ajah</title>

    <link
        rel="icon"
        type="image/webp"
        href="/assets/images/logo.webp"
    >

    <link
        rel="stylesheet"
        href="/assets/css/style.css"
    >

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
        }

        .auth-brand {
            display: inline-block;
            margin-bottom: 16px;
        }

        .auth-card h1 {
            margin-bottom: 8px;
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

        .auth-card button {
            width: 100%;
        }

        .auth-error {
            padding: 12px;
            border-radius: 10px;
            color: #a32323;
            background: #fff0f0;
        }

        .auth-footer {
            margin-top: 20px;
            text-align: center;
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

            <h1>Login warung</h1>

            <p class="muted">
                Masuk menggunakan akun yang diberikan admin.
            </p>

            <?php if ($error !== null): ?>
                <p class="auth-error" role="alert">
                    <?= e($error) ?>
                </p>
            <?php endif; ?>

            <form
                method="post"
                action="/warung/login.php"
            >
                <?= csrf_field() ?>

                <label class="auth-field">
                    <span>Username</span>

                    <input
                        type="text"
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
                        maxlength="4096"
                        required
                    >
                </label>

                <button
                    class="button button-primary"
                    type="submit"
                >
                    Masuk ke dashboard
                </button>
            </form>

            <div class="auth-footer">
                <p class="muted">
                    Belum memiliki akun?
                </p>

                <p>
                    Hubungi administrator Pesan Ajah untuk
                    mendapatkan akun warung.
                </p>

                <a href="/">
                    Kembali ke marketplace
                </a>
            </div>
        </section>
    </main>
</body>
</html>