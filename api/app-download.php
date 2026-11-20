<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_method('GET');

$apkPath = APP_ROOT . '/assets/downloads/pesan-ajah.apk';

if (!is_file($apkPath) || !is_readable($apkPath)) {
    http_response_code(404);
    exit('File aplikasi belum tersedia.');
}

$fileSize = filesize($apkPath);

if ($fileSize === false) {
    http_response_code(500);
    exit('File aplikasi tidak dapat dibaca.');
}

/*
|--------------------------------------------------------------------------
| Catat permintaan download
|--------------------------------------------------------------------------
|
| Counter menghitung permintaan download yang diterima server.
| Counter bukan penghitungan instalasi aplikasi.
|
*/

try {
    $db = database();

    $stmt = $db->prepare(
        'UPDATE app_download_stats
         SET total_downloads = total_downloads + 1
         WHERE id = 1'
    );

    $stmt->execute();

    /*
     * Pengamanan jika baris statistik belum tersedia.
     */
    if ($stmt->rowCount() === 0) {
        $db->exec(
            'INSERT INTO app_download_stats (
                id,
                total_downloads
            ) VALUES (1, 1)
            ON DUPLICATE KEY UPDATE
                total_downloads = total_downloads + 1'
        );
    }
} catch (Throwable $exception) {
    /*
     * Kegagalan statistik tidak boleh menggagalkan
     * proses pengiriman APK.
     */
    error_log(
        '[Pesan Ajah] Download counter: '
        . $exception->getMessage()
    );
}

/*
|--------------------------------------------------------------------------
| Kirim file APK
|--------------------------------------------------------------------------
*/

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="pesan-ajah.apk"');
header('Content-Length: ' . $fileSize);
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

readfile($apkPath);

exit;
