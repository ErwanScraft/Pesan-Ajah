<?php

declare(strict_types=1);

/**
 * Pesan Ajah
 *
 * PDO database connection.
 *
 * Configuration is loaded from .env through app/bootstrap.php.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (isset($pdo) && $pdo instanceof PDO) {
    return;
}

/*
|--------------------------------------------------------------------------
| Read database configuration
|--------------------------------------------------------------------------
*/

$driver = (string) env('DB_CONNECTION', 'mysql');
$host = (string) env('DB_HOST', 'localhost');
$port = (string) env('DB_PORT', '3306');
$database = (string) env('DB_DATABASE', '');
$username = (string) env('DB_USERNAME', '');
$password = (string) env('DB_PASSWORD', '');

/*
|--------------------------------------------------------------------------
| Validate configuration
|--------------------------------------------------------------------------
*/

if ($driver !== 'mysql') {
    throw new RuntimeException(
        'Driver database yang dikonfigurasi tidak didukung.'
    );
}

if (
    $host === ''
    || $database === ''
    || $username === ''
    || !ctype_digit($port)
    || (int) $port < 1
    || (int) $port > 65535
) {
    throw new RuntimeException(
        'Konfigurasi koneksi database belum lengkap atau tidak valid.'
    );
}

/*
|--------------------------------------------------------------------------
| Create PDO connection
|--------------------------------------------------------------------------
*/

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $host,
    (int) $port,
    $database
);

try {
    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES => false,

            PDO::ATTR_STRINGIFY_FETCHES => false,

            PDO::ATTR_PERSISTENT => false,
        ]
    );
} catch (PDOException $exception) {
    /*
     * Do not expose database credentials or connection details
     * to visitors in production.
     */
    error_log(
        '[Pesan Ajah] Database connection failed: '
        . $exception->getMessage()
    );

    if (APP_DEBUG) {
        throw $exception;
    }

    http_response_code(500);

    exit('Terjadi kesalahan pada layanan. Silakan coba kembali nanti.');
}