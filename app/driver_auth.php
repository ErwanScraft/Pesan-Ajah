
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

/**
 * Mengambil konfigurasi akun driver dari .env.
 */
function driver_credentials(): array
{
    return [
        'username' => trim((string) env('DRIVER_USERNAME', '')),
        'password' => (string) env('DRIVER_PASSWORD', ''),
        'name' => trim((string) env('DRIVER_NAME', 'Driver Anter Ajah')),
    ];
}

/**
 * Memeriksa apakah sesi driver masih valid.
 */
function driver_is_authenticated(): bool
{
    $credentials = driver_credentials();
    $session = $_SESSION['driver_auth'] ?? null;

    if (
        !is_array($session)
        || empty($session['authenticated'])
        || $credentials['username'] === ''
        || $credentials['password'] === ''
    ) {
        return false;
    }

    return isset($session['username'])
        && is_string($session['username'])
        && hash_equals(
            $credentials['username'],
            $session['username']
        );
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
 * Membuat sesi driver setelah kredensial valid.
 */
function driver_login(string $username): void
{
    $credentials = driver_credentials();

    session_regenerate_id(true);

    $_SESSION['driver_auth'] = [
        'authenticated' => true,
        'username' => $username,
        'name' => $credentials['name'] !== ''
            ? $credentials['name']
            : 'Driver Anter Ajah',
        'logged_in_at' => time(),
    ];
}

/**
 * Mengakhiri sesi login driver.
 * Sesi admin/warung tidak dihapus.
 */
function driver_logout(): void
{
    unset($_SESSION['driver_auth']);

    session_regenerate_id(true);
}
