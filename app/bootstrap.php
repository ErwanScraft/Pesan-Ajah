<?php

declare(strict_types=1);

/**
 * Pesan Ajah
 *
 * Application bootstrap:
 * - Load environment configuration.
 * - Configure timezone and error handling.
 * - Initialize secure sessions.
 * - Provide authentication and CSRF helpers.
 * - Provide common application utilities.
 */

use Dotenv\Dotenv;

if (defined('PESANAJAH_BOOTSTRAPPED')) {
    return;
}

define('PESANAJAH_BOOTSTRAPPED', true);

/*
|--------------------------------------------------------------------------
| Application paths
|--------------------------------------------------------------------------
*/

define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', APP_ROOT . '/app');
define('CONFIG_PATH', APP_ROOT . '/config');
define('ASSETS_PATH', APP_ROOT . '/assets');

/*
|--------------------------------------------------------------------------
| Load Composer dependencies
|--------------------------------------------------------------------------
*/

$autoloadPath = APP_ROOT . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    throw new RuntimeException(
        'Dependensi aplikasi belum terpasang. Jalankan Composer install.'
    );
}

require_once $autoloadPath;

/*
|--------------------------------------------------------------------------
| Load environment configuration
|--------------------------------------------------------------------------
|
| Default:
| - Project: /home/account/public_html
| - Environment: /home/account/.env
|
| PESANAJAH_ENV_PATH dapat digunakan untuk menentukan path alternatif
| melalui environment server.
|
*/

$configuredEnvPath = getenv('PESANAJAH_ENV_PATH');

$envPath = is_string($configuredEnvPath) && $configuredEnvPath !== ''
    ? $configuredEnvPath
    : dirname(APP_ROOT) . '/.env';

if (!is_file($envPath) || !is_readable($envPath)) {
    throw new RuntimeException(
        'File konfigurasi lingkungan tidak ditemukan atau tidak dapat dibaca.'
    );
}

$dotenv = Dotenv::createImmutable(dirname($envPath));
$dotenv->safeLoad();

/*
|--------------------------------------------------------------------------
| Environment helpers
|--------------------------------------------------------------------------
*/

/**
 * Retrieve an environment value.
 */
function env(string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    if (array_key_exists($key, $_SERVER)) {
        return $_SERVER[$key];
    }

    return $default;
}

/**
 * Parse a boolean environment value.
 */
function env_bool(string $key, bool $default = false): bool
{
    $value = env($key, $default);

    if (is_bool($value)) {
        return $value;
    }

    $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

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
| HTTPS detection
|--------------------------------------------------------------------------
|
| HTTPS should normally be enabled on the production domain.
| Do not trust arbitrary X-Forwarded-Proto headers unless the hosting
| proxy is configured and trusted.
|
*/

function request_is_https(): bool
{
    if (
        isset($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== ''
        && strtolower((string) $_SERVER['HTTPS']) !== 'off'
    ) {
        return true;
    }

    return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

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

    session_name($sessionName);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    $secureCookie = env_bool(
        'SESSION_SECURE',
        APP_ENV === 'production'
    );

    if (APP_ENV === 'production' && !$secureCookie) {
        throw new RuntimeException(
            'SESSION_SECURE harus aktif pada lingkungan produksi.'
        );
    }

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
| Output escaping
|--------------------------------------------------------------------------
*/

/**
 * Escape a value for HTML output.
 */
function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| URL helpers
|--------------------------------------------------------------------------
*/

/**
 * Generate an application URL.
 */
function url(string $path = ''): string
{
    $baseUrl = APP_URL;

    if ($baseUrl === '') {
        throw new RuntimeException(
            'APP_URL belum dikonfigurasi pada file .env.'
        );
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

/**
 * Redirect to an internal application path.
 */
function redirect(string $path, int $statusCode = 302): never
{
    if (
        $path === ''
        || preg_match('/[\r\n]/', $path)
        || preg_match('/^[a-z][a-z0-9+.-]*:/i', $path)
        || str_starts_with($path, '//')
    ) {
        throw new InvalidArgumentException(
            'Tujuan redirect tidak valid.'
        );
    }

    $destination = url($path);

    header('Location: ' . $destination, true, $statusCode);

    exit;
}

/*
|--------------------------------------------------------------------------
| HTTP method helpers
|--------------------------------------------------------------------------
*/

function request_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
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
| CSRF protection
|--------------------------------------------------------------------------
*/

/**
 * Generate or retrieve the current CSRF token.
 */
function csrf_token(): string
{
    if (
        !isset($_SESSION['_csrf_token'])
        || !is_string($_SESSION['_csrf_token'])
        || strlen($_SESSION['_csrf_token']) !== 64
    ) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Generate a hidden CSRF input.
 */
function csrf_field(): string
{
    return sprintf(
        '<input type="hidden" name="_token" value="%s">',
        e(csrf_token())
    );
}

/**
 * Validate the CSRF token from a POST request.
 */
function verify_csrf(): void
{
    require_method('POST');

    $submittedToken = $_POST['_token'] ?? null;
    $sessionToken = $_SESSION['_csrf_token'] ?? null;

    if (
        !is_string($submittedToken)
        || !is_string($sessionToken)
        || !hash_equals($sessionToken, $submittedToken)
    ) {
        http_response_code(419);

        exit('Sesi formulir tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.');
    }
}

/*
|--------------------------------------------------------------------------
| Authentication helpers
|--------------------------------------------------------------------------
*/

/**
 * Return the authenticated user stored in the session.
 *
 * The database must still be checked when sensitive operations execute,
 * especially to verify that the account is active.
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

/**
 * Check whether a user is authenticated.
 */
function is_authenticated(): bool
{
    return auth_user() !== null;
}

/**
 * Check whether the current user has the specified role.
 */
function has_role(string $role): bool
{
    $user = auth_user();

    return $user !== null && $user['role'] === $role;
}

/**
 * Require an authenticated user.
 */
function require_auth(): array
{
    $user = auth_user();

    if ($user === null) {
        redirect('warung/login.php');
    }

    return $user;
}

/**
 * Require a specific role.
 */
function require_role(string $role): array
{
    $user = require_auth();

    if ($user['role'] !== $role) {
        http_response_code(403);

        exit('Anda tidak memiliki izin untuk mengakses halaman ini.');
    }

    return $user;
}

/**
 * Store authenticated user data in the session.
 *
 * Call this only after verifying the password and account status
 * against the database.
 */
function login_user(array $user): void
{
    $userId = filter_var(
        $user['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $role = $user['role'] ?? null;

    if (
        $userId === false
        || !in_array($role, ['admin', 'warung'], true)
    ) {
        throw new InvalidArgumentException(
            'Data pengguna untuk login tidak valid.'
        );
    }

    session_regenerate_id(true);

    $_SESSION['auth_user'] = [
        'id' => $userId,
        'name' => (string) ($user['name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
        'role' => $role,
    ];

    // Generate a fresh CSRF token after login.
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Log out the current user.
 */
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

/**
 * Convert a name into a URL-friendly slug.
 */
function slugify(string $value): string
{
    $value = trim($value);

    if (function_exists('iconv')) {
        $converted = iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $value
        );

        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value;
}

/**
 * Check whether a slug uses a reserved application route.
 */
function is_reserved_slug(string $slug): bool
{
    $reserved = [
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
        'index.php',
        'favicon.ico',
        'robots.txt',
        'sitemap.xml',
    ];

    return in_array(strtolower($slug), $reserved, true);
}

/**
 * Validate a store slug.
 */
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