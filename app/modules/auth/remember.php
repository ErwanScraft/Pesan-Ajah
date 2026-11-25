<?php

declare(strict_types=1);

const WARUNG_REMEMBER_COOKIE = 'pa_warung_remember';
const WARUNG_REMEMBER_DAYS = 30;

/**
 * Konfigurasi cookie remember-me.
 */
function remember_cookie_options(int $expires): array
{
    $options = [
        'expires' => $expires,
        'path' => '/',
        'secure' => env_bool(
            'SESSION_SECURE',
            APP_ENV === 'production'
        ),
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    $domain = trim(
        (string) env('REMEMBER_COOKIE_DOMAIN', '')
    );

    if ($domain !== '') {
        $options['domain'] = $domain;
    }

    return $options;
}

/**
 * Menghapus cookie remember-me dari browser.
 */
function remember_clear_cookie(): void
{
    setcookie(
        WARUNG_REMEMBER_COOKIE,
        '',
        remember_cookie_options(time() - 3600)
    );
}

/**
 * Membuat token login persisten untuk satu perangkat.
 */
function remember_create(
    int $userId,
    string $passwordHash
): void {
    if ($userId < 1 || $passwordHash === '') {
        return;
    }

    // Hapus token lama pada browser ini.
    remember_forget();

    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));

    $tokenHash = hash('sha256', $validator);
    $passwordSnapshot = hash('sha256', $passwordHash);

    $stmt = database()->prepare(
        'INSERT INTO warung_remember_tokens (
            user_id,
            selector,
            token_hash,
            password_hash_snapshot,
            expires_at
        ) VALUES (
            :user_id,
            :selector,
            :token_hash,
            :password_hash_snapshot,
            DATE_ADD(NOW(), INTERVAL 30 DAY)
        )'
    );

    $stmt->execute([
        'user_id' => $userId,
        'selector' => $selector,
        'token_hash' => $tokenHash,
        'password_hash_snapshot' => $passwordSnapshot,
    ]);

    $cookieValue = $selector . '.' . $validator;

    $success = setcookie(
        WARUNG_REMEMBER_COOKIE,
        $cookieValue,
        remember_cookie_options(
            time() + (WARUNG_REMEMBER_DAYS * 86400)
        )
    );

    if (!$success) {
        database()->prepare(
            'DELETE FROM warung_remember_tokens
             WHERE selector = :selector'
        )->execute([
            'selector' => $selector,
        ]);

        error_log(
            '[Pesan Ajah] Cookie remember-me gagal dibuat.'
        );
    }
}

/**
 * Mencabut token pada perangkat saat ini.
 */
function remember_forget(): void
{
    $cookie = $_COOKIE[WARUNG_REMEMBER_COOKIE] ?? '';

    if (
        is_string($cookie)
        && preg_match(
            '/^([a-f0-9]{32})\.([a-f0-9]{64})$/',
            $cookie,
            $matches
        )
    ) {
        try {
            database()->prepare(
                'DELETE FROM warung_remember_tokens
                 WHERE selector = :selector'
            )->execute([
                'selector' => $matches[1],
            ]);
        } catch (Throwable $exception) {
            error_log(
                '[Pesan Ajah] Gagal mencabut remember token: '
                . $exception->getMessage()
            );
        }
    }

    remember_clear_cookie();
}

/**
 * Memulihkan session seller dari cookie remember-me.
 */
function remember_restore_session(): void
{
    // Jangan menimpa session yang sudah aktif.
    if (auth_user() !== null) {
        return;
    }

    $cookie = $_COOKIE[WARUNG_REMEMBER_COOKIE] ?? '';

    if (!is_string($cookie) || $cookie === '') {
        return;
    }

    if (
        !preg_match(
            '/^([a-f0-9]{32})\.([a-f0-9]{64})$/',
            $cookie,
            $matches
        )
    ) {
        remember_clear_cookie();
        return;
    }

    $selector = $matches[1];
    $validator = $matches[2];

    $stmt = database()->prepare(
        'SELECT
            rt.id AS token_id,
            rt.token_hash,
            rt.password_hash_snapshot,

            u.id AS user_id,
            u.username,
            u.password_hash,
            u.role,
            u.is_active AS user_is_active,

            s.id AS store_id,
            s.is_active AS store_is_active

        FROM warung_remember_tokens rt

        INNER JOIN users u
            ON u.id = rt.user_id

        INNER JOIN stores s
            ON s.user_id = u.id

        WHERE rt.selector = :selector
          AND rt.expires_at > NOW()
          AND u.role = :role

        LIMIT 1'
    );

    $stmt->execute([
        'selector' => $selector,
        'role' => 'warung',
    ]);

    $token = $stmt->fetch();

    $valid = (
        $token
        && (bool) $token['user_is_active']
        && (bool) $token['store_is_active']
        && hash_equals(
            (string) $token['token_hash'],
            hash('sha256', $validator)
        )
        && hash_equals(
            (string) $token['password_hash_snapshot'],
            hash('sha256', (string) $token['password_hash'])
        )
    );

    if (!$valid) {
        remember_forget();
        return;
    }

    login_user([
        'id' => (int) $token['user_id'],
        'name' => (string) $token['username'],
        'role' => 'warung',
    ]);
}
