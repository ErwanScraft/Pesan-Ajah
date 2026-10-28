<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/warung_api.php';
require_once dirname(__DIR__, 2) . '/app/image_upload.php';

$store = api_store_owner();

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (request_method() !== 'POST') {
    header('Allow: POST');

    api_json([
        'success' => false,
        'message' => 'Metode permintaan tidak diizinkan.',
    ], 405);
}

api_verify_csrf();

if (
    !isset($_FILES['image'])
    || !is_array($_FILES['image'])
) {
    api_json([
        'success' => false,
        'message' => 'Pilih gambar ikon toko terlebih dahulu.',
    ], 422);
}

$file = $_FILES['image'];

if (
    !isset($file['error'], $file['size'], $file['tmp_name'])
    || !is_int($file['error'])
    || !is_int($file['size'])
    || !is_string($file['tmp_name'])
) {
    api_json([
        'success' => false,
        'message' => 'Data gambar tidak valid.',
    ], 422);
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    api_json([
        'success' => false,
        'message' => 'Gambar gagal diunggah.',
    ], 422);
}

if (
    $file['size'] < 1
    || $file['size'] > 2 * 1024 * 1024
    || !is_uploaded_file($file['tmp_name'])
) {
    api_json([
        'success' => false,
        'message' => 'Ukuran gambar maksimal 2 MB.',
    ], 422);
}

if (!extension_loaded('fileinfo')) {
    api_json([
        'success' => false,
        'message' => 'Server tidak mendukung validasi gambar.',
    ], 500);
}

$imageInfo = @getimagesize($file['tmp_name']);

if (
    $imageInfo === false
    || empty($imageInfo[0])
    || empty($imageInfo[1])
    || $imageInfo[0] > 4096
    || $imageInfo[1] > 4096
) {
    api_json([
        'success' => false,
        'message' => 'File bukan gambar yang valid.',
    ], 422);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/webp',
];

if (
    !is_string($mimeType)
    || !in_array($mimeType, $allowedMimeTypes, true)
    || ($imageInfo['mime'] ?? '') !== $mimeType
) {
    api_json([
        'success' => false,
        'message' => 'Gunakan gambar JPG, PNG, atau WebP.',
    ], 422);
}

$storeId = (int) $store['store_id'];

if ($storeId < 1) {
    api_json([
        'success' => false,
        'message' => 'Data warung tidak valid.',
    ], 403);
}

$relativeDirectory = 'storage/uploads/stores';
$absoluteDirectory = dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR
    . str_replace(
        '/',
        DIRECTORY_SEPARATOR,
        $relativeDirectory
    );

if (
    !is_dir($absoluteDirectory)
    && !mkdir($absoluteDirectory, 0755, true)
    && !is_dir($absoluteDirectory)
) {
    api_json([
        'success' => false,
        'message' => 'Direktori penyimpanan gambar tidak tersedia.',
    ], 500);
}

if (
    !function_exists('imagecreatefromstring')
    || !function_exists('imagewebp')
) {
    api_json([
        'success' => false,
        'message' => 'Server belum mendukung konversi WebP.',
    ], 500);
}

$filename = bin2hex(random_bytes(16)) . '.webp';

$absolutePath = $absoluteDirectory
    . DIRECTORY_SEPARATOR
    . $filename;

$relativePath = $relativeDirectory . '/' . $filename;

if (
    !save_uploaded_image_as_webp(
        $file['tmp_name'],
        $absolutePath,
        80
    )
) {
    @unlink($absolutePath);

    api_json([
        'success' => false,
        'message' => 'Ikon gagal dikonversi menjadi WebP.',
    ], 422);
}

try {
    $db = database();

    $stmt = $db->prepare(
        'SELECT icon_image
         FROM stores
         WHERE id = :store_id
         LIMIT 1'
    );

    $stmt->execute([
        'store_id' => $storeId,
    ]);

    $currentImage = $stmt->fetchColumn();

    $update = $db->prepare(
        'UPDATE stores
         SET icon_image = :icon_image
         WHERE id = :store_id'
    );

    $update->execute([
        'icon_image' => $relativePath,
        'store_id' => $storeId,
    ]);

    /*
     * Hapus gambar lama hanya jika berasal dari
     * direktori ikon toko milik aplikasi.
     */
    if (
        is_string($currentImage)
        && preg_match(
            '~^storage/uploads/stores/[a-f0-9]{32}\.(?:jpg|png|webp)$~',
            $currentImage
        )
    ) {
        $oldPath = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $currentImage);

        if (
            is_file($oldPath)
            && realpath(dirname($oldPath)) === realpath($absoluteDirectory)
        ) {
            @unlink($oldPath);
        }
    }

    api_json([
        'success' => true,
        'message' => 'Ikon toko berhasil diperbarui.',
        'icon_image' => $relativePath,
    ]);
} catch (Throwable $exception) {
    @unlink($absolutePath);

    error_log(
        '[Pesan Ajah] Gagal menyimpan ikon toko: '
        . $exception->getMessage()
    );

    api_json([
        'success' => false,
        'message' => 'Ikon toko gagal disimpan ke database.',
    ], 500);
}