<?php

declare(strict_types=1);

/**
 * Pesan Ajah
 *
 * Bootstrap aplikasi tanpa dependensi Composer.
 */

if (defined('PESANAJAH_BOOTSTRAPPED')) {
    return;
}

define('PESANAJAH_BOOTSTRAPPED', true);

define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', APP_ROOT . '/app');
define('CONFIG_PATH', APP_ROOT . '/config');
define('ASSETS_PATH', APP_ROOT . '/assets');

/*
|--------------------------------------------------------------------------
| Load .env
|--------------------------------------------------------------------------
*/

$customEnvPath = getenv('PESANAJAH_ENV_PATH');

$envPath = is_string($customEnvPath) && $customEnvPath !== ''
    ? $customEnvPath
    : dirname(APP_ROOT) . '/.env';

if (!is_file($envPath) || !is_readable($envPath)) {
    throw new RuntimeException(
        'Konfigurasi aplikasi tidak ditemukan.'
    );
}

/**
 * Membaca file .env sederhana tanpa library eksternal.
 */
function load_env_file(string $path): void
{
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        throw new RuntimeException(
            'File konfigurasi tidak dapat dibaca.'
        );
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $separator = strpos($line, '=');

        if ($separator === false) {
            continue;
        }

        $key = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));

        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
            continue;
        }

        // Jangan menimpa environment variable dari server.
        if (getenv($key) !== false) {
            continue;
        }

        // Hapus tanda kutip pembungkus jika ada.
        if (
            strlen($value) >= 2
            && (
                ($value[0] === '"' && $value[-1] === '"')
                || ($value[0] === "'" && $value[-1] === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;

        putenv($key . '=' . $value);
    }
}

load_env_file($envPath);

/*
|--------------------------------------------------------------------------
| Environment helpers
|--------------------------------------------------------------------------
*/

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);

    if ($value !== false) {
        return $value;
    }

    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

function env_bool(string $key, bool $default = false): bool
{
    $value = env($key, $default);

    if (is_bool($value)) {
        return $value;
    }

    $parsed = filter_var(
        $value,
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    );

    return $parsed ?? $default;
}

/*
|--------------------------------------------------------------------------
| Application configuration
|--------------------------------------------------------------------------
*/

define('APP_NAME', (string) env('APP_NAME', 'Pesan Ajah'));
define('APP_ENV', (string) env('APP_ENV', 'production'));
define('APP_DEBUG', env_bool('APP_DEBUG', false));
define('APP_URL', rtrim((string) env('APP_URL', ''), '/'));

date_default_timezone_set(
    (string) env('APP_TIMEZONE', 'Asia/Jakarta')
);

/*
|--------------------------------------------------------------------------
| Error handling
|--------------------------------------------------------------------------
*/

error_reporting(E_ALL);

ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

/*
|--------------------------------------------------------------------------
| Session configuration
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionName = (string) env(
        'SESSION_NAME',
        'pesan_ajah_session'
    );

    if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $sessionName)) {
        throw new RuntimeException('Nama session tidak valid.');
    }

    $secureCookie = env_bool(
        'SESSION_SECURE',
        APP_ENV === 'production'
    );

    if (APP_ENV === 'production' && !$secureCookie) {
        throw new RuntimeException(
            'SESSION_SECURE harus aktif di lingkungan produksi.'
        );
    }

    session_name($sessionName);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/*
|--------------------------------------------------------------------------
| Common helpers
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function url(string $path = ''): string
{
    if (APP_URL === '') {
        throw new RuntimeException(
            'APP_URL belum dikonfigurasi.'
        );
    }

    return APP_URL . '/' . ltrim($path, '/');
}

function redirect(string $path, int $statusCode = 302): never
{
    if (
        preg_match('/[\r\n]/', $path)
        || preg_match('/^[a-z][a-z0-9+.-]*:/i', $path)
        || str_starts_with($path, '//')
    ) {
        throw new InvalidArgumentException(
            'Tujuan redirect tidak valid.'
        );
    }

    header('Location: ' . url($path), true, $statusCode);

    exit;
}

function request_method(): string
{
    return strtoupper(
        (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')
    );
}

function require_method(string ...$methods): void
{
    if (!in_array(request_method(), $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        http_response_code(405);

        exit('Method tidak diizinkan.');
    }
}

/*
|--------------------------------------------------------------------------
| Authentication compatibility helpers
|--------------------------------------------------------------------------
*/

function is_logged_in(): bool
{
    return is_authenticated();
}

function current_user(): ?array
{
    return auth_user();
}

function request_is_post(): bool
{
    return request_method() === 'POST';
}

/**
 * Memastikan pengguna sudah login dan memiliki role yang sesuai.
 */
function require_admin(): array
{
    $user = auth_user();

    if ($user === null) {
        redirect('admin/login.php');
    }

    if (($user['role'] ?? null) !== 'admin') {
        http_response_code(403);

        exit('Anda tidak memiliki izin mengakses halaman ini.');
    }

    return $user;
}

/**
 * Memastikan pengguna memiliki akses sebagai pemilik warung.
 */
function require_warung(): array
{
    $user = auth_user();

    if ($user === null) {
        redirect('warung/login.php');
    }

    if (($user['role'] ?? null) !== 'warung') {
        http_response_code(403);

        exit('Anda tidak memiliki izin mengakses halaman ini.');
    }

    return $user;
}

/**
 * Menyimpan atau membaca notifikasi session.
 *
 * flash('success', 'Data berhasil disimpan.');
 * flash('success');
 */
function flash(string $key, ?string $message = null): ?string
{
    if (!isset($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }

    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;

        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;

    unset($_SESSION['_flash'][$key]);

    return is_string($value) ? $value : null;
}
/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

function csrf_token(): string
{
    if (
        !isset($_SESSION['_csrf_token'])
        || !is_string($_SESSION['_csrf_token'])
    ) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="'
        . e(csrf_token())
        . '">';
}

function verify_csrf(): void
{
    require_method('POST');

    $submitted = $_POST['_token']
        ?? $_POST['csrf_token']
        ?? '';

    $stored = $_SESSION['_csrf_token'] ?? '';

    if (
        !is_string($submitted)
        || !is_string($stored)
        || $stored === ''
        || !hash_equals($stored, $submitted)
    ) {
        http_response_code(419);

        exit(
            'Sesi formulir tidak valid. '
            . 'Silakan muat ulang halaman.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Authentication helpers
|--------------------------------------------------------------------------
*/

function auth_user(): ?array
{
    $user = $_SESSION['auth_user'] ?? null;

    if (!is_array($user)) {
        return null;
    }

    if (
        !isset($user['id'], $user['role'])
        || !is_numeric($user['id'])
        || !in_array($user['role'], ['admin', 'warung'], true)
    ) {
        return null;
    }

    return $user;
}

function is_authenticated(): bool
{
    return auth_user() !== null;
}

function has_role(string $role): bool
{
    return (auth_user()['role'] ?? null) === $role;
}

function require_auth(): array
{
    $user = auth_user();

    if ($user === null) {
        redirect('warung/login.php');
    }

    return $user;
}

function require_role(string $role): array
{
    $user = require_auth();

    if ($user['role'] !== $role) {
        http_response_code(403);

        exit('Anda tidak memiliki izin mengakses halaman ini.');
    }

    return $user;
}

function login_user(array $user): void
{
    $id = filter_var(
        $user['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $role = $user['role'] ?? null;

    if (
        $id === false
        || !in_array($role, ['admin', 'warung'], true)
    ) {
        throw new InvalidArgumentException(
            'Data pengguna tidak valid.'
        );
    }

    session_regenerate_id(true);

    $_SESSION['auth_user'] = [
        'id' => $id,
        'name' => (string) ($user['name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'role' => $role,
    ];

    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/*
|--------------------------------------------------------------------------
| Store slug helpers
|--------------------------------------------------------------------------
*/

function slugify(string $value): string
{
    if (function_exists('iconv')) {
        $converted = iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            trim($value)
        );

        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

    return trim($value, '-');
}

function is_reserved_slug(string $slug): bool
{
    return in_array(strtolower($slug), [
        'admin',
        'api',
        'app',
        'assets',
        'config',
        'database',
        'login',
        'logout',
        'produk',
        'pesanan',
        'user',
        'warung',
        'scripts',
        'storage',
        'vendor',
        'index',
    ], true);
}

function is_valid_store_slug(string $slug): bool
{
    if (
        strlen($slug) < 3
        || strlen($slug) > 80
        || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)
    ) {
        return false;
    }

    return !is_reserved_slug($slug);
}

/*
|--------------------------------------------------------------------------
| Database bootstrap
|--------------------------------------------------------------------------
*/

require_once CONFIG_PATH . '/database.php';