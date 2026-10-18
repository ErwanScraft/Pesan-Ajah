<?php

declare(strict_types=1);

/**
 * Pesan Ajah — Central Debug Error Handler
 *
 * APP_DEBUG=true:
 *   Menampilkan detail error melalui popup HTML.
 *
 * APP_DEBUG=false:
 *   Menyimpan detail error ke error_log tanpa membocorkannya
 *   ke halaman yang dilihat pengunjung.
 */

if (!defined('APP_DEBUG')) {
    return;
}

/**
 * Mengubah nilai menjadi teks yang aman untuk HTML.
 */
function debug_escape(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Menyimpan detail error ke log server.
 */
function debug_log_error(array $error): void
{
    $message = sprintf(
        '[Pesan Ajah Debug] %s: %s in %s:%s',
        $error['type'] ?? 'Error',
        $error['message'] ?? 'Unknown error',
        $error['file'] ?? 'unknown',
        $error['line'] ?? '0'
    );

    error_log($message);

    if (!empty($error['trace'])) {
        error_log((string) $error['trace']);
    }
}

/**
 * Menampilkan popup error jika APP_DEBUG aktif.
 *
 * Popup hanya dapat ditampilkan jika respons masih berupa HTML
 * dan header belum dikirim.
 */
function debug_render_error_popup(array $error): void
{
    if (!APP_DEBUG || headers_sent()) {
        return;
    }

    $contentType = headers_list();

    foreach ($contentType as $header) {
        if (
            stripos($header, 'Content-Type:') === 0
            && stripos($header, 'text/html') === false
        ) {
            return;
        }
    }

    static $rendered = false;

    if ($rendered) {
        return;
    }

    $rendered = true;

    $type = debug_escape($error['type'] ?? 'Error');
    $message = debug_escape($error['message'] ?? 'Unknown error');
    $file = debug_escape($error['file'] ?? 'unknown');
    $line = debug_escape($error['line'] ?? '0');
    $trace = debug_escape($error['trace'] ?? '');

    echo <<<'HTML'
<div id="pesan-ajah-debug-error" role="alertdialog"
     aria-modal="true" aria-labelledby="pa-debug-title">
  <div class="pa-debug-backdrop"></div>
  <section class="pa-debug-dialog">
    <header class="pa-debug-header">
      <div>
        <strong id="pa-debug-title">Pesan Ajah — Debug Error</strong>
        <p>Mode debug aktif. Detail ini hanya untuk pengembangan.</p>
      </div>
      <button type="button" class="pa-debug-close"
              aria-label="Tutup popup"
              onclick="document.getElementById('pesan-ajah-debug-error').remove()">
        &times;
      </button>
    </header>
    <div class="pa-debug-content">
      <div class="pa-debug-badge">ERROR</div>
      <h3 class="pa-debug-type">
HTML;

    echo $type;

    echo <<<'HTML'
      </h3>
      <pre class="pa-debug-message">
HTML;

    echo $message;

    echo <<<'HTML'
</pre>
      <dl class="pa-debug-meta">
        <dt>File</dt>
        <dd>
HTML;

    echo $file;

    echo <<<'HTML'
</dd>
        <dt>Baris</dt>
        <dd>
HTML;

    echo $line;

    echo <<<'HTML'
</dd>
      </dl>
      <details class="pa-debug-trace">
        <summary>Stack trace</summary>
        <pre>
HTML;

    echo $trace !== '' ? $trace : 'Stack trace tidak tersedia.';

    echo <<<'HTML'
</pre>
      </details>
      <button type="button" class="pa-debug-copy"
              onclick="navigator.clipboard && navigator.clipboard.writeText(
                document.querySelector('#pesan-ajah-debug-error .pa-debug-message').innerText
              )">
        Salin pesan error
      </button>
    </div>
    <footer class="pa-debug-footer">
      Jangan biarkan APP_DEBUG=true di website produksi.
    </footer>
  </section>
</div>
<style>
#pesan-ajah-debug-error {
  position: fixed;
  inset: 0;
  z-index: 2147483647;
  font: 14px/1.5 system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  color: #172033;
}
#pesan-ajah-debug-error * { box-sizing: border-box; }
#pesan-ajah-debug-error .pa-debug-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(10, 16, 30, .72);
  backdrop-filter: blur(4px);
}
#pesan-ajah-debug-error .pa-debug-dialog {
  position: relative;
  width: min(720px, calc(100% - 28px));
  max-height: calc(100dvh - 32px);
  overflow: auto;
  margin: 16px auto;
  background: #fff;
  border: 1px solid #e4e7ec;
  border-radius: 16px;
  box-shadow: 0 24px 80px rgba(0, 0, 0, .3);
}
#pesan-ajah-debug-error .pa-debug-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 20px;
  border-bottom: 1px solid #eaecf0;
}
#pesan-ajah-debug-error .pa-debug-header p {
  margin: 4px 0 0;
  color: #667085;
  font-size: 12px;
}
#pesan-ajah-debug-error .pa-debug-close {
  align-self: flex-start;
  border: 0;
  background: transparent;
  font-size: 28px;
  line-height: 1;
  cursor: pointer;
}
#pesan-ajah-debug-error .pa-debug-content { padding: 20px; }
#pesan-ajah-debug-error .pa-debug-badge {
  display: inline-block;
  padding: 4px 8px;
  color: #b42318;
  background: #fef3f2;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .06em;
}
#pesan-ajah-debug-error .pa-debug-type {
  margin: 12px 0 8px;
  font-size: 18px;
  overflow-wrap: anywhere;
}
#pesan-ajah-debug-error pre {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  word-break: break-word;
  font: 12px/1.6 ui-monospace, SFMono-Regular, Consolas, monospace;
}
#pesan-ajah-debug-error .pa-debug-message {
  margin: 0;
  padding: 12px;
  background: #f8fafc;
  border: 1px solid #e4e7ec;
  border-radius: 8px;
}
#pesan-ajah-debug-error .pa-debug-meta {
  display: grid;
  grid-template-columns: 65px minmax(0, 1fr);
  gap: 8px 12px;
  margin: 18px 0;
}
#pesan-ajah-debug-error .pa-debug-meta dt { color: #667085; }
#pesan-ajah-debug-error .pa-debug-meta dd {
  margin: 0;
  overflow-wrap: anywhere;
  font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
  font-size: 12px;
}
#pesan-ajah-debug-error .pa-debug-trace {
  border-top: 1px solid #eaecf0;
  padding-top: 14px;
}
#pesan-ajah-debug-error .pa-debug-trace summary {
  cursor: pointer;
  font-weight: 600;
}
#pesan-ajah-debug-error .pa-debug-trace pre {
  max-height: 240px;
  overflow: auto;
  padding: 12px;
  background: #101828;
  color: #f2f4f7;
  border-radius: 8px;
}
#pesan-ajah-debug-error .pa-debug-copy {
  margin-top: 14px;
  padding: 9px 12px;
  border: 1px solid #d0d5dd;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
}
#pesan-ajah-debug-error .pa-debug-footer {
  padding: 12px 20px;
  background: #fffaeb;
  color: #92400e;
  border-top: 1px solid #fedf89;
  font-size: 12px;
}
@media (max-width: 480px) {
  #pesan-ajah-debug-error .pa-debug-dialog {
    width: calc(100% - 16px);
    margin: 8px auto;
    max-height: calc(100dvh - 16px);
    border-radius: 12px;
  }
  #pesan-ajah-debug-error .pa-debug-header,
  #pesan-ajah-debug-error .pa-debug-content { padding: 14px; }
}
</style>
HTML;
}

/**
 * Dipanggil dari error handler, exception handler, atau catch lokal.
 */
function debug_report_error(
    Throwable|array $error,
    ?string $context = null
): void {
    if ($error instanceof Throwable) {
        $data = [
            'type' => get_class($error),
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
        ];
    } else {
        $data = $error;
    }

    if ($context !== null && $context !== '') {
        $data['message'] = '[' . $context . '] '
            . ($data['message'] ?? 'Unknown error');
    }

    debug_log_error($data);

    if (APP_DEBUG) {
        $GLOBALS['pesan_ajah_debug_errors'][] = $data;
    }
}

/**
 * Handler untuk PHP Warning, Notice, dan error sejenis.
 */
function debug_error_handler(
    int $severity,
    string $message,
    string $file,
    int $line
): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    $data = [
        'type' => 'PHP Error (' . $severity . ')',
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'trace' => '',
    ];

    debug_report_error($data);

    // Saat debug aktif, cegah PHP menampilkan error mentah.
    return APP_DEBUG;
}

/**
 * Exception yang tidak tertangani.
 */
function debug_exception_handler(Throwable $exception): void
{
    debug_report_error($exception, 'Uncaught exception');

    http_response_code(500);

    if (APP_DEBUG) {
        debug_render_error_popup([
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        return;
    }

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }

    echo '<!doctype html><html lang="id"><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Terjadi Kesalahan</title>'
        . '<body style="font:16px system-ui;padding:32px">'
        . '<h1>Terjadi kesalahan pada server</h1>'
        . '<p>Silakan coba kembali beberapa saat lagi.</p>'
        . '</body></html>';
}

/**
 * Menangani fatal error yang terjadi setelah bootstrap dimuat.
 */
function debug_shutdown_handler(): void
{
    $lastError = error_get_last();

    if ($lastError === null) {
        return;
    }

    $fatalTypes = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
    ];

    if (!in_array($lastError['type'], $fatalTypes, true)) {
        return;
    }

    $data = [
        'type' => 'Fatal PHP Error (' . $lastError['type'] . ')',
        'message' => $lastError['message'] ?? 'Fatal error',
        'file' => $lastError['file'] ?? 'unknown',
        'line' => $lastError['line'] ?? 0,
        'trace' => '',
    ];

    debug_report_error($data);

    if (APP_DEBUG) {
        debug_render_error_popup($data);
    } elseif (!headers_sent()) {
        http_response_code(500);
    }
}

/**
 * Cetak popup yang dikumpulkan selama proses request.
 *
 * Untuk warning yang terjadi sebelum halaman selesai dirender,
 * popup akan muncul pada bagian akhir respons HTML.
 */
function debug_render_collected_errors(): void
{
    if (!APP_DEBUG || empty($GLOBALS['pesan_ajah_debug_errors'])) {
        return;
    }

    $errors = $GLOBALS['pesan_ajah_debug_errors'];
    $error = end($errors);

    if (is_array($error)) {
        debug_render_error_popup($error);
    }
}