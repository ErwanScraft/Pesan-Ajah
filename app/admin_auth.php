<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * Membaca kredensial admin dari .env.
 */
function admin_credentials(): array
{
    $username = trim((string) env('ADMIN_USERNAME', ''));
    $password = (string) env('ADMIN_PASSWORD', '');

    if ($username === '' || $password === '') {
        throw new RuntimeException(
            'ADMIN_USERNAME dan ADMIN_PASSWORD wajib dikonfigurasi.'
        );
    }

    if (strlen($username) > 50) {
        throw new RuntimeException(
            'ADMIN_USERNAME maksimal 50 karakter.'
        );
    }

    return [
        'username' => $username,
        'password' => $password,
    ];
}

/**
 * Autentikasi admin sepenuhnya menggunakan .env.
 * Tidak mengakses tabel users atau database.
 */
function authenticate_admin(
    string $username,
    string $password
): ?array {
    $credentials = admin_credentials();

    if (
        !hash_equals($credentials['username'], $username)
        || !hash_equals($credentials['password'], $password)
    ) {
        return null;
    }

    return [
        // ID sesi sintetis; bukan ID pengguna database.
        'id' => 1,
        'name' => $credentials['username'],
        'username' => $credentials['username'],
        'role' => 'admin',
    ];
}