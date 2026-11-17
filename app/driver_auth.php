<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * Memeriksa apakah sesi driver masih valid.
 */
function driver_is_authenticated(): bool
{
    $session = $_SESSION['driver_auth'] ?? null;

    if (
        !is_array($session)
        || empty($session['authenticated'])
        || empty($session['id'])
    ) {
        return false;
    }

    $stmt = database()->prepare(
        'SELECT id, username, name, is_active
         FROM drivers
         WHERE id = :id
         LIMIT 1'
    );

    $stmt->execute([
        'id' => (int) $session['id'],
    ]);

    $driver = $stmt->fetch();

    if (!$driver || !(bool) $driver['is_active']) {
        unset($_SESSION['driver_auth']);

        return false;
    }

    $_SESSION['driver_auth']['username'] = $driver['username'];
    $_SESSION['driver_auth']['name'] = $driver['name'];

    return true;
}

/**
 * Memastikan halaman hanya dapat diakses driver.
 */
function require_driver(): array
{
    if (!driver_is_authenticated()) {
        redirect('driver/login.php');
    }

    return $_SESSION['driver_auth'];
}

/**
 * Login driver menggunakan username dan password database.
 */
function driver_login(string $username, string $password): bool
{
    $stmt = database()->prepare(
        'SELECT id, username, password_hash, name, is_active
         FROM drivers
         WHERE username = :username
         LIMIT 1'
    );

    $stmt->execute([
        'username' => $username,
    ]);

    $driver = $stmt->fetch();

    if (
        !$driver
        || !(bool) $driver['is_active']
        || !password_verify($password, $driver['password_hash'])
    ) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['driver_auth'] = [
        'authenticated' => true,
        'id' => (int) $driver['id'],
        'username' => $driver['username'],
        'name' => $driver['name'],
        'logged_in_at' => time(),
    ];

    $update = database()->prepare(
        'UPDATE drivers
         SET last_seen_at = CURRENT_TIMESTAMP
         WHERE id = :id'
    );

    $update->execute([
        'id' => (int) $driver['id'],
    ]);

    return true;
}

/**
 * Mengakhiri sesi driver.
 */
function driver_logout(): void
{
    unset($_SESSION['driver_auth']);

    session_regenerate_id(true);
}
