<?php

declare(strict_types=1);

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;

    return is_array($user) ? $user : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'username' => (string) $user['username'],
        'role' => (string) $user['role'],
    ];

    unset($_SESSION['csrf_token']);

    csrf_token();
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
}

function require_role(string $role): void
{
    $user = current_user();

    if ($user === null) {
        redirect(
            $role === 'admin'
                ? '/admin/login.php'
                : '/warung/login.php'
        );
    }

    if (($user['role'] ?? '') !== $role) {
        http_response_code(403);

        exit('Anda tidak memiliki izin untuk membuka halaman ini.');
    }

    $stmt = database()->prepare(
        'SELECT id, username, role, is_active
         FROM users
         WHERE id = :id
         LIMIT 1'
    );

    $stmt->execute([
        'id' => (int) $user['id'],
    ]);

    $record = $stmt->fetch();

    if (!$record || !(bool) $record['is_active']) {
        logout_user();

        redirect(
            $role === 'admin'
                ? '/admin/login.php'
                : '/warung/login.php'
        );
    }
}

function require_admin(): void
{
    require_role('admin');
}

function require_store_owner(): void
{
    require_role('warung');
}

function current_store(): ?array
{
    $user = current_user();

    if ($user === null || ($user['role'] ?? '') !== 'warung') {
        return null;
    }

    $stmt = database()->prepare(
        'SELECT *
         FROM stores
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $stmt->execute([
        'user_id' => (int) $user['id'],
    ]);

    $store = $stmt->fetch();

    return $store ?: null;
}