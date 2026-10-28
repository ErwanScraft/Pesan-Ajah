<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function javascript_log_response(int $status, array $data): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

if (request_method() !== 'POST') {
    header('Allow: POST');

    javascript_log_response(405, [
        'success' => false,
        'message' => 'Method tidak diizinkan.',
    ]);
}

$submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$storedToken = $_SESSION['_csrf_token'] ?? '';

if (
    !is_string($submittedToken)
    || !is_string($storedToken)
    || $storedToken === ''
    || !hash_equals($storedToken, $submittedToken)
) {
    javascript_log_response(419, [
        'success' => false,
        'message' => 'Token tidak valid.',
    ]);
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

if ($contentLength < 1 || $contentLength > 12000) {
    javascript_log_response(413, [
        'success' => false,
        'message' => 'Ukuran laporan tidak valid.',
    ]);
}

$raw = file_get_contents('php://input');

try {
    $report = json_decode(
        $raw === false ? '' : $raw,
        true,
        32,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException) {
    javascript_log_response(400, [
        'success' => false,
        'message' => 'Format laporan tidak valid.',
    ]);
}

if (!is_array($report)) {
    javascript_log_response(400, [
        'success' => false,
        'message' => 'Laporan harus berupa objek.',
    ]);
}

/*
 * Batasi frekuensi laporan per sesi.
 */
$now = time();
$rate = $_SESSION['_javascript_log_rate'] ?? [
    'start' => $now,
    'count' => 0,
];

if (
    !is_array($rate)
    || $now - (int) ($rate['start'] ?? 0) >= 60
) {
    $rate = [
        'start' => $now,
        'count' => 0,
    ];
}

if ((int) $rate['count'] >= 20) {
    $_SESSION['_javascript_log_rate'] = $rate;

    javascript_log_response(429, [
        'success' => false,
        'message' => 'Batas laporan tercapai.',
    ]);
}

$rate['count']++;
$_SESSION['_javascript_log_rate'] = $rate;

/*
 * Hanya terima tipe laporan yang dikenal.
 */
$allowedTypes = [
    'javascript',
    'unhandled_promise',
    'api',
];

$type = $report['type'] ?? 'javascript';

if (!is_string($type) || !in_array($type, $allowedTypes, true)) {
    $type = 'javascript';
}

$cleanText = static function (
    mixed $value,
    int $limit
): string {
    if (!is_string($value)) {
        return '';
    }

    $value = preg_replace(
        '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
        '',
        $value
    ) ?? '';

    return mb_substr($value, 0, $limit, 'UTF-8');
};

/*
 * Hanya izinkan path lokal, tanpa query string atau fragment.
 */
$page = $cleanText($report['page'] ?? '', 300);

if (
    $page === ''
    || !str_starts_with($page, '/')
    || str_starts_with($page, '//')
    || preg_match('/[\r\n]/', $page)
) {
    $page = '/unknown';
}

$requestId = $cleanText($report['request_id'] ?? '', 80);

if (!preg_match('/^[A-Za-z0-9_-]+$/', $requestId)) {
    $requestId = bin2hex(random_bytes(12));
}

$entry = [
    'time' => date(DATE_ATOM),
    'request_id' => $requestId,
    'type' => $type,
    'message' => $cleanText($report['message'] ?? '', 2000),
    'stack' => $cleanText($report['stack'] ?? '', 4000),
    'page' => $page,
    'source' => $cleanText($report['source'] ?? '', 300),
    'line' => is_numeric($report['line'] ?? null)
        ? max(0, (int) $report['line'])
        : null,
    'column' => is_numeric($report['column'] ?? null)
        ? max(0, (int) $report['column'])
        : null,
    'http_status' => is_numeric($report['http_status'] ?? null)
        ? max(0, min(599, (int) $report['http_status']))
        : null,
];

/*
 * Simpan di luar public_html.
 * Contoh: /home/pesanaja/pesan-ajah-logs/javascript/
 */
$logDirectory = dirname(APP_ROOT)
    . '/pesan-ajah-logs/javascript';

if (
    !is_dir($logDirectory)
    && !mkdir($logDirectory, 0750, true)
    && !is_dir($logDirectory)
) {
    javascript_log_response(500, [
        'success' => false,
        'message' => 'Penyimpanan log tidak tersedia.',
    ]);
}

$logFile = $logDirectory
    . '/javascript-' . date('Y-m-d') . '.log';

/*
 * Rotasi sederhana jika file harian melewati 5 MB.
 */
if (is_file($logFile) && filesize($logFile) >= 5 * 1024 * 1024) {
    $backupFile = $logFile . '.1';

    if (is_file($backupFile)) {
        @unlink($backupFile);
    }

    @rename($logFile, $backupFile);
}

$line = json_encode(
    $entry,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_INVALID_UTF8_SUBSTITUTE
);

if (
    $line === false
    || file_put_contents(
        $logFile,
        $line . PHP_EOL,
        FILE_APPEND | LOCK_EX
    ) === false
) {
    javascript_log_response(500, [
        'success' => false,
        'message' => 'Laporan gagal disimpan.',
    ]);
}

javascript_log_response(200, [
    'success' => true,
    'request_id' => $requestId,
]);
