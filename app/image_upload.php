<?php

declare(strict_types=1);

/**
 * Pesan Ajah — Image Upload
 *
 * Mengonversi gambar yang sudah divalidasi menjadi WebP.
 * File sumber tidak diubah; hasil konversi disimpan ke tujuan.
 */
function save_uploaded_image_as_webp(
    string $sourcePath,
    string $destinationPath,
    int $quality = 80
): bool {
    if (
        !is_uploaded_file($sourcePath)
        || !is_file($sourcePath)
        || !function_exists('imagecreatefromstring')
        || !function_exists('imagewebp')
    ) {
        return false;
    }

    $quality = max(0, min(100, $quality));

    $imageInfo = @getimagesize($sourcePath);

    if (
        $imageInfo === false
        || empty($imageInfo[0])
        || empty($imageInfo[1])
        || $imageInfo[0] > 4096
        || $imageInfo[1] > 4096
    ) {
        return false;
    }

    $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    if (
        !in_array(
            $imageInfo['mime'] ?? '',
            $allowedMimeTypes,
            true
        )
    ) {
        return false;
    }

    $contents = @file_get_contents($sourcePath);

    if ($contents === false) {
        return false;
    }

    $image = @imagecreatefromstring($contents);

    unset($contents);

    if ($image === false) {
        return false;
    }

    try {
        /*
         * Pertahankan transparansi, khususnya untuk PNG.
         */
        if (!imageistruecolor($image)) {
            if (!imagepalettetotruecolor($image)) {
                return false;
            }
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $saved = @imagewebp(
            $image,
            $destinationPath,
            $quality
        );

        if (!$saved || !is_file($destinationPath)) {
            return false;
        }

        /*
         * Pastikan file hasil benar-benar WebP.
         */
        $resultInfo = @getimagesize($destinationPath);

        if (
            $resultInfo === false
            || ($resultInfo['mime'] ?? '') !== 'image/webp'
        ) {
            @unlink($destinationPath);

            return false;
        }

        return true;
    } finally {
        imagedestroy($image);
    }
}
