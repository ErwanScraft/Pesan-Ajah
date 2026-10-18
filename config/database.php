<?php

declare(strict_types=1);

/**
 * Pesan Ajah
 *
 * Koneksi MySQL menggunakan PDO dan konfigurasi .env.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

/**
 * Menghasilkan koneksi PDO.
 */
function database(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $driver = (string) env('DB_CONNECTION', 'mysql');
    $host = (string) env('DB_HOST', 'localhost');
    $port = (string) env('DB_PORT', '3306');
    $name = (string) env('DB_DATABASE', '');
    $username = (string) env('DB_USERNAME', '');
    $password = (string) env('DB_PASSWORD', '');

    if ($driver !== 'mysql') {
        throw new RuntimeException(
            'Driver database tidak didukung.'
        );
    }

    if (
        $host === ''
        || $name === ''
        || $username === ''
        || !ctype_digit($port)
        || (int) $port < 1
        || (int) $port > 65535
    ) {
        throw new RuntimeException(
            'Konfigurasi database belum lengkap.'
        );
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $host,
        (int) $port,
        $name
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

        return $pdo;
    } catch (PDOException $exception) {
        debug_report_error($exception, 'Koneksi database gagal');
    
        http_response_code(500);
    
        if (APP_DEBUG) {
            throw $exception;
        }
    
        // Pertahankan mekanisme respons generik yang digunakan
        // aplikasi saat ini untuk pengunjung.
        exit('Layanan sementara tidak tersedia. Silakan coba kembali.');
    }

        http_response_code(500);

        if (APP_DEBUG) {
            throw $exception;
        }

        exit('Layanan sedang mengalami gangguan. Silakan coba kembali.');
    }
}