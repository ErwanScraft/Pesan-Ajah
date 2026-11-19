<?php

declare(strict_types=1);

/**
 * Pesan Ajah — Asset Cache Manager
 *
 * Centralized asset versioning and cache policy.
 */

if (!defined('PESANAJAH_ASSET_CACHE_LOADED')) {
    define('PESANAJAH_ASSET_CACHE_LOADED', true);
}

/**
 * Mendapatkan root aplikasi.
 */
function asset_cache_root(): string
{
    return defined('APP_ROOT')
        ? APP_ROOT
        : dirname(__DIR__, 3);
}

/**
 * Menghasilkan versi berdasarkan SHA-256 isi file.
 *
 * Setiap perubahan isi file menghasilkan versi baru.
 * Aman untuk file yang tidak ditemukan.
 */
function asset_version(string $path): string
{
    $root = realpath(asset_cache_root());

    if ($root === false) {
        return '1';
    }

    $filePath = realpath(
        $root . '/' . ltrim($path, '/')
    );

    if (
        $filePath === false
        || !str_starts_with(
            $filePath,
            $root . DIRECTORY_SEPARATOR
        )
        || !is_file($filePath)
        || !is_readable($filePath)
    ) {
        return '1';
    }

    $hash = hash_file('sha256', $filePath);

    return $hash !== false
        ? substr($hash, 0, 16)
        : '1';
}

/**
 * Menghasilkan URL aset dengan versi otomatis.
 *
 * Contoh:
 * asset_url('assets/js/main.js')
 */
function asset_url(string $path): string
{
    $path = '/' . ltrim($path, '/');

    $separator = str_contains($path, '?')
        ? '&'
        : '?';

    return $path
        . $separator
        . 'v='
        . asset_version(ltrim(
            strtok($path, '?'),
            '/'
        ));
}

/**
 * Mengatur cache untuk halaman dinamis PHP.
 *
 * HTML selalu divalidasi ulang agar browser
 * memperoleh referensi aset versi terbaru.
 */
function asset_cache_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header(
        'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
    );

    header('Pragma: no-cache');
    header('Expires: 0');
}
