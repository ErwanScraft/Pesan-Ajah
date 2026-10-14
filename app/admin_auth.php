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
 * Memastikan akun admin tersedia di database.
 *
 * Password database tetap disimpan menggunakan password_hash().
 */
function provision_admin_account(array $credentials): ?array
{
    $pdo = database();

    $stmt = $pdo->prepare(
        'SELECT id, username, password_hash, role, is_active
         FROM users
         WHERE username = :username
         LIMIT 1'
    );

    $stmt->execute([
        'username' => $credentials['username'],
    ]);

    $user = $stmt->fetch();

    $passwordHash = password_hash(
        $credentials['password'],
        PASSWORD_DEFAULT
    );

    if ($passwordHash === false) {
        throw new RuntimeException(
            'Password admin gagal diproses.'
        );
    }

    if (!$user) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (
                username,
                password_hash,
                role,
                is_active,
                created_at,
                updated_at
            ) VALUES (
                :username,
                :password_hash,
                :role,
                :is_active,
                NOW(),
                NOW()
            )'
        );

        $stmt->execute([
            'username' => $credentials['username'],
            'password_hash' => $passwordHash,
            'role' => 'admin',
            'is_active' => 1,
        ]);

        $userId = (int) $pdo->lastInsertId();

        return [
            'id' => $userId,
            'username' => $credentials['username'],
            'role' => 'admin',
            'is_active' => 1,
        ];
    }

    /*
     * Jangan mengubah akun warung menjadi admin.
     */
    if ($user['role'] !== 'admin') {
        return null;
    }

    /*
     * Jangan otomatis mengaktifkan admin yang dinonaktifkan.
     */
    if (!(bool) $user['is_active']) {
        return null;
    }

    /*
     * Sinkronkan hash database setelah kredensial .env
     * berhasil diverifikasi oleh authenticate_admin().
     */
    if (!password_verify(
        $credentials['password'],
        (string) $user['password_hash']
    )) {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET password_hash = :password_hash,
                 updated_at = NOW()
             WHERE id = :id
               AND role = :role
               AND is_active = 1'
        );

        $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => (int) $user['id'],
            'role' => 'admin',
        ]);
    }

    return [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'role' => 'admin',
        'is_active' => (int) $user['is_active'],
    ];
}

/**
 * Autentikasi admin berdasarkan konfigurasi .env.
 */
function authenticate_admin(
    string $username,
    string $password
): ?array {
    $credentials = admin_credentials();

    /*
     * Verifikasi kredensial terlebih dahulu.
     * Jangan membuat atau mengubah akun sebelum password benar.
     */
    if (
        !hash_equals($credentials['username'], $username)
        || !hash_equals($credentials['password'], $password)
    ) {
        return null;
    }

    return provision_admin_account($credentials);
}