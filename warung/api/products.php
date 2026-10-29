<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/warung_api.php';
require_once APP_ROOT . '/app/image_upload.php';

$store = api_store_owner();
$storeId = (int) $store['store_id'];

$method = request_method();

const PRODUCT_IMAGE_MAX_SIZE = 5 * 1024 * 1024;

const PRODUCT_IMAGE_DIRECTORY = 'storage/uploads/products/';

/**
 * Lokasi direktori penyimpanan foto.
 */
function product_image_directory(): string
{
    return APP_ROOT . '/' . PRODUCT_IMAGE_DIRECTORY;
}

/**
 * Hapus foto produk hanya jika berada di direktori upload produk.
 */
function delete_product_image_file(?string $image): void
{
    if (!is_string($image) || $image === '') {
        return;
    }

    $prefix = PRODUCT_IMAGE_DIRECTORY;

    if (!str_starts_with($image, $prefix)) {
        return;
    }

    $filename = substr($image, strlen($prefix));

    if (
        $filename === ''
        || !preg_match(
            '/^[a-f0-9]{32}\.(?:jpg|png|webp)$/',
            $filename
        )
    ) {
        return;
    }

    $directory = realpath(product_image_directory());

    if ($directory === false) {
        return;
    }

    $path = $directory . DIRECTORY_SEPARATOR . $filename;

    if (is_file($path) && !is_link($path)) {
        @unlink($path);
    }
}

/**
 * Unggah foto produk.
 *
 * Return:
 * - null jika tidak ada file yang diunggah.
 * - path relatif jika berhasil.
 *
 * Error validasi dikirim sebagai respons JSON.
 */
function upload_product_image(): ?string
{
    if (!isset($_FILES['image'])) {
        return null;
    }

    $file = $_FILES['image'];

    if (!is_array($file)) {
        api_json([
            'success' => false,
            'message' => 'Data foto tidak valid.',
        ], 422);
    }

    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($error !== UPLOAD_ERR_OK) {
        $message = match ($error) {
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE =>
                'Ukuran foto melebihi batas upload server.',

            UPLOAD_ERR_PARTIAL =>
                'Foto gagal diunggah sepenuhnya.',

            UPLOAD_ERR_NO_TMP_DIR =>
                'Direktori sementara upload tidak tersedia.',

            UPLOAD_ERR_CANT_WRITE =>
                'Server tidak dapat menyimpan foto.',

            default =>
                'Upload foto gagal.',
        };

        api_json([
            'success' => false,
            'message' => $message,
        ], 422);
    }

    $temporaryPath = $file['tmp_name'] ?? '';
    $fileSize = (int) ($file['size'] ?? 0);

    if (
        !is_string($temporaryPath)
        || !is_uploaded_file($temporaryPath)
    ) {
        api_json([
            'success' => false,
            'message' => 'File upload tidak valid.',
        ], 422);
    }

    if (
        $fileSize < 1
        || $fileSize > PRODUCT_IMAGE_MAX_SIZE
    ) {
        api_json([
            'success' => false,
            'message' => 'Ukuran foto maksimal 5 MB.',
        ], 422);
    }

    /*
     * Validasi konten gambar, bukan hanya ekstensi file.
     */
    $imageInfo = @getimagesize($temporaryPath);

    if ($imageInfo === false) {
        api_json([
            'success' => false,
            'message' => 'File yang dipilih bukan gambar valid.',
        ], 422);
    }

    $mime = (string) ($imageInfo['mime'] ?? '');

    $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];
    
    if (!in_array($mime, $allowedMimeTypes, true)) {
        api_json([
            'success' => false,
            'message' => 'Format foto harus JPG, PNG, atau WebP.',
        ], 422);
    }

    $directory = product_image_directory();

    if (
        !is_dir($directory)
        && !mkdir($directory, 0755, true)
        && !is_dir($directory)
    ) {
        api_json([
            'success' => false,
            'message' => 'Direktori penyimpanan foto tidak tersedia.',
        ], 500);
    }

    if (!is_writable($directory)) {
        api_json([
            'success' => false,
            'message' => 'Direktori foto tidak dapat ditulis server.',
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
    
    $destination = $directory . $filename;
    
    if (
        !save_uploaded_image_as_webp(
            $temporaryPath,
            $destination,
            80
        )
    ) {
        @unlink($destination);
    
        api_json([
            'success' => false,
            'message' => 'Foto gagal dikonversi menjadi WebP.',
        ], 422);
    }
    
    return PRODUCT_IMAGE_DIRECTORY . $filename;
}

/**
 * Mengambil nilai boolean dari input JSON atau multipart.
 */
function product_available_value(mixed $value): bool
{
    $available = filter_var(
        $value ?? true,
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    );

    if ($available === null) {
        api_json([
            'success' => false,
            'message' => 'Status ketersediaan tidak valid.',
        ], 422);
    }

    return $available;
}

/**
 * Validasi seluruh data teks produk.
 */
function validate_product_input(array $input): array
{
    $name = api_required_text(
        $input['name'] ?? null,
        'Nama produk',
        120
    );
    
    $variant = trim(
        is_string($input['variant'] ?? null)
            ? $input['variant']
            : ''
    );
    
    if (mb_strlen($variant, 'UTF-8') > 100) {
        api_json([
            'success' => false,
            'message' => 'Varian maksimal 100 karakter.',
        ], 422);
    }

    $category = api_required_text(
        $input['category'] ?? null,
        'Kategori',
        80
    );
    
    $allowedCategories = require APP_ROOT . '/config/categories.php';
    
    if (!in_array($category, $allowedCategories, true)) {
        api_json([
            'success' => false,
            'message' => 'Kategori produk tidak valid.',
        ], 422);
    }

    /*
     * Satuan baru dikirim sebagai jumlah + jenis.
     * Input "unit" tetap didukung untuk kompatibilitas API lama,
     * termasuk pembaruan ketersediaan produk.
     */
    if (
        array_key_exists('unit_quantity', $input)
        || array_key_exists('unit_name', $input)
    ) {
        $quantityInput = trim(
            (string) ($input['unit_quantity'] ?? '')
        );
    
        $unitName = api_required_text(
            $input['unit_name'] ?? null,
            'Jenis satuan',
            20
        );
    
        if (
            !preg_match('/^\d+(?:\.\d{1,3})?$/', $quantityInput)
            || (float) $quantityInput <= 0
            || (float) $quantityInput > 999999
        ) {
            api_json([
                'success' => false,
                'message' => 'Jumlah satuan harus lebih dari 0 dan maksimal 999999.',
            ], 422);
        }
    
        if (preg_match('/[\r\n\t]/', $unitName)) {
            api_json([
                'success' => false,
                'message' => 'Jenis satuan tidak valid.',
            ], 422);
        }
    
        // Normalisasi angka: 1.500 menjadi 1.5, 2.000 menjadi 2.
        $quantity = rtrim(
            rtrim(number_format((float) $quantityInput, 3, '.', ''), '0'),
            '.'
        );
    
        $unit = $quantity . ' ' . $unitName;
    } else {
        // Fallback agar permintaan API lama tetap berfungsi.
        $unit = api_required_text(
            $input['unit'] ?? null,
            'Satuan',
            30
        );
    }

    $description = trim(
        is_string($input['description'] ?? null)
            ? $input['description']
            : ''
    );

    if (mb_strlen($description, 'UTF-8') > 5000) {
        api_json([
            'success' => false,
            'message' => 'Deskripsi maksimal 5.000 karakter.',
        ], 422);
    }

    $price = filter_var(
        $input['price'] ?? null,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
                'max_range' => 999999999999,
            ],
        ]
    );

    if ($price === false) {
        api_json([
            'success' => false,
            'message' => 'Harga harus berupa bilangan bulat positif.',
        ], 422);
    }
    
    $tag = trim(
        is_string($input['tag'] ?? null)
            ? $input['tag']
            : ''
    );
    
    if (mb_strlen($tag, 'UTF-8') > 40) {
        api_json([
            'success' => false,
            'message' => 'Label produk maksimal 40 karakter.',
        ], 422);
    }

    return [
        'name' => $name,
        'variant' => $variant !== '' ? $variant : null,
        'category' => $category,
        'unit' => $unit,
        'description' => $description !== ''
            ? $description
            : null,
        'price' => $price,
        'available' => product_available_value(
            $input['available'] ?? true
        ),
        'tag' => $tag !== '' ? $tag : null,
    ];
}

/*
|--------------------------------------------------------------------------
| GET — Daftar produk milik warung
|--------------------------------------------------------------------------
*/

if ($method === 'GET') {
    $stmt = database()->prepare(
        'SELECT
            id,
            name,
            variant,
            description,
            category,
            price,
            unit,
            image,
            tag,
            is_available,
            created_at,
            updated_at

        FROM products

        WHERE store_id = :store_id

        ORDER BY created_at DESC, id DESC'
    );

    $stmt->execute([
        'store_id' => $storeId,
    ]);

    api_json([
        'success' => true,
        'products' => $stmt->fetchAll(),
    ]);
}

/*
|--------------------------------------------------------------------------
| Validasi CSRF untuk seluruh operasi perubahan
|--------------------------------------------------------------------------
*/

api_verify_csrf();

/*
 * POST multipart dipakai untuk upload foto.
 * PUT JSON tetap didukung untuk update biasa,
 * misalnya ketika hanya mengubah status ketersediaan.
 */
$contentType = (string) (
    $_SERVER['CONTENT_TYPE'] ?? ''
);

$isMultipart = str_contains(
    strtolower($contentType),
    'multipart/form-data'
);

$input = $isMultipart
    ? $_POST
    : api_json_input();


/*
|--------------------------------------------------------------------------
| DELETE PRODUCT — POST action
|--------------------------------------------------------------------------
*/

$isDeleteProduct = $method === 'DELETE'
    || (
        $method === 'POST'
        && ($input['action'] ?? '') === 'delete'
    );

if ($isDeleteProduct) {
    $id = api_positive_id($input['id'] ?? null);
    $db = database();

    $find = $db->prepare(
        'SELECT image
         FROM products
         WHERE id = :id
           AND store_id = :store_id
         LIMIT 1'
    );

    $find->execute([
        'id' => $id,
        'store_id' => $storeId,
    ]);

    $product = $find->fetch();

    if (!$product) {
        api_json([
            'success' => false,
            'message' => 'Produk tidak ditemukan atau bukan milik warung ini.',
        ], 404);
    }

    try {
        $db->beginTransaction();

        /*
         * Lepaskan referensi produk dari riwayat pesanan.
         * Snapshot nama dan harga tidak dihapus.
         */
        $detach = $db->prepare(
            'UPDATE order_items
             SET product_id = NULL
             WHERE product_id = :product_id'
        );

        $detach->execute([
            'product_id' => $id,
        ]);

        $delete = $db->prepare(
            'DELETE FROM products
             WHERE id = :id
               AND store_id = :store_id'
        );

        $delete->execute([
            'id' => $id,
            'store_id' => $storeId,
        ]);

        if ($delete->rowCount() !== 1) {
            throw new RuntimeException(
                'Produk tidak berhasil dihapus dari database.'
            );
        }

        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        error_log(
            '[Pesan Ajah Product Delete] '
            . 'store_id=' . $storeId
            . '; product_id=' . $id
            . '; error=' . $exception->getMessage()
            . '; file=' . $exception->getFile()
            . '; line=' . $exception->getLine()
        );

        api_json([
            'success' => false,
            'message' => 'Produk gagal dihapus. Periksa error_log hosting.',
        ], 500);
    }

    /*
     * Hapus foto setelah transaksi database berhasil.
     */
    if (!empty($product['image'])) {
        delete_product_image_file(
            (string) $product['image']
        );
    }

    api_json([
        'success' => true,
        'message' => 'Produk berhasil dihapus.',
    ]);
}

/*
|--------------------------------------------------------------------------
| POST — Tambah produk atau edit produk dengan foto
|--------------------------------------------------------------------------
|
| POST tanpa ID: tambah produk.
| POST dengan ID: edit produk melalui multipart/form-data.
|
*/

$isProductUpdate = $method === 'PUT'
    || (
        $method === 'POST'
        && isset($input['id'])
    );

if ($method === 'POST' || $method === 'PUT') {
    $id = $isProductUpdate
        ? api_positive_id($input['id'] ?? null)
        : null;

    $data = validate_product_input($input);

    /*
     * Pastikan produk yang diedit benar-benar milik warung ini.
     */
    $existingProduct = null;

    if ($id !== null) {
        $find = database()->prepare(
            'SELECT id, image
             FROM products
             WHERE id = :id
               AND store_id = :store_id
             LIMIT 1'
        );

        $find->execute([
            'id' => $id,
            'store_id' => $storeId,
        ]);

        $existingProduct = $find->fetch();

        if (!$existingProduct) {
            api_json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }
    }

    /*
     * Upload dilakukan setelah validasi data teks.
     */
    $newImage = $isMultipart
        ? upload_product_image()
        : null;

    $removeImage = filter_var(
        $input['remove_image'] ?? false,
        FILTER_VALIDATE_BOOLEAN
    );

    $oldImage = $existingProduct['image'] ?? null;

    $imageToSave = $oldImage;

    if ($newImage !== null) {
        $imageToSave = $newImage;
    } elseif ($removeImage) {
        $imageToSave = null;
    }

    try {
        if ($id === null) {
            $stmt = database()->prepare(
                'INSERT INTO products (
                    store_id,
                    name,
                    variant,
                    description,
                    category,
                    price,
                    unit,
                    image,
                    tag,
                    is_available
                ) VALUES (
                    :store_id,
                    :name,
                    :variant,
                    :description,
                    :category,
                    :price,
                    :unit,
                    :image,
                    :tag,
                    :is_available
                )'
            );
            
            $stmt->execute([
                'store_id' => $storeId,
                'name' => $data['name'],
                'variant' => $data['variant'],
                'description' => $data['description'],
                'category' => $data['category'],
                'price' => $data['price'],
                'unit' => $data['unit'],
                'image' => $imageToSave,
                'tag' => $data['tag'],
                'is_available' => $data['available'] ? 1 : 0,
            ]);

            $productId = (int) database()->lastInsertId();
        } else {
            $stmt = database()->prepare(
                'UPDATE products
                 SET
                    name = :name,
                    variant = :variant,
                    description = :description,
                    category = :category,
                    price = :price,
                    unit = :unit,
                    image = :image,
                    tag = :tag,
                    is_available = :is_available
                 WHERE id = :id
                   AND store_id = :store_id'
            );

            $stmt->execute([
                'name' => $data['name'],
                'variant' => $data['variant'],
                'description' => $data['description'],
                'category' => $data['category'],
                'price' => $data['price'],
                'unit' => $data['unit'],
                'image' => $imageToSave,
                'tag' => $data['tag'],
                'is_available' => $data['available'] ? 1 : 0,
                'id' => $id,
                'store_id' => $storeId,
            ]);

            $productId = $id;
        }

    } catch (Throwable $exception) {
        // Hapus foto baru jika penyimpanan database gagal.
        if ($newImage !== null) {
            delete_product_image_file($newImage);
        }

        // Catat detail di error_log server untuk debugging.
        error_log(
            '[Pesan Ajah Product Save] '
            . 'store_id=' . $storeId
            . '; operation=' . ($id === null ? 'INSERT' : 'UPDATE')
            . '; error=' . $exception->getMessage()
            . '; file=' . $exception->getFile()
            . '; line=' . $exception->getLine()
        );

        // Berikan respons JSON yang bisa dibaca JavaScript.
        api_json([
            'success' => false,
            'message' => $id === null
                ? 'Produk baru gagal disimpan ke database. Periksa error_log hosting.'
                : 'Perubahan produk gagal disimpan ke database. Periksa error_log hosting.',
        ], 500);
    }


    /*
     * Hapus file lama setelah perubahan database berhasil.
     */
    if (
        $oldImage
        && $oldImage !== $imageToSave
    ) {
        delete_product_image_file((string) $oldImage);
    }

    api_json([
        'success' => true,
        'message' => $id === null
            ? 'Produk berhasil ditambahkan.'
            : 'Produk berhasil diperbarui.',
        'id' => $productId,
        'image' => $imageToSave,
    ], $id === null ? 201 : 200);
}

/*
|--------------------------------------------------------------------------
| DELETE — Hapus produk
|--------------------------------------------------------------------------
*/


if ($method === 'DELETE') {
    $id = api_positive_id($input['id'] ?? null);

    $db = database();

    /*
     * Pastikan produk dimiliki oleh warung yang sedang login.
     */
    $find = $db->prepare(
        'SELECT image
         FROM products
         WHERE id = :id
           AND store_id = :store_id
         LIMIT 1'
    );

    $find->execute([
        'id' => $id,
        'store_id' => $storeId,
    ]);

    $product = $find->fetch();

    if (!$product) {
        api_json([
            'success' => false,
            'message' => 'Produk tidak ditemukan.',
        ], 404);
    }

    try {
        $db->beginTransaction();

        /*
         * Lepaskan referensi produk dari riwayat pesanan.
         * Snapshot nama, varian, harga, dan subtotal tetap utuh.
         */
        $detach = $db->prepare(
            'UPDATE order_items
             SET product_id = NULL
             WHERE product_id = :product_id'
        );

        $detach->execute([
            'product_id' => $id,
        ]);

        /*
         * Hapus produk milik warung yang sedang login.
         */
        $delete = $db->prepare(
            'DELETE FROM products
             WHERE id = :id
               AND store_id = :store_id'
        );

        $delete->execute([
            'id' => $id,
            'store_id' => $storeId,
        ]);

        if ($delete->rowCount() !== 1) {
            throw new RuntimeException(
                'Produk tidak berhasil dihapus.'
            );
        }

        $db->commit();

    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        error_log(
            '[Pesan Ajah Product Delete] '
            . 'store_id=' . $storeId
            . '; product_id=' . $id
            . '; error=' . $exception->getMessage()
            . '; file=' . $exception->getFile()
            . '; line=' . $exception->getLine()
        );

        api_json([
            'success' => false,
            'message' => 'Produk gagal dihapus. Silakan coba lagi atau periksa error_log hosting.',
        ], 500);
    }

    /*
     * Hapus foto setelah transaksi database berhasil.
     */
    if (!empty($product['image'])) {
        delete_product_image_file(
            (string) $product['image']
        );
    }

    api_json([
        'success' => true,
        'message' => 'Produk berhasil dihapus.',
    ]);
}

header('Allow: GET, POST, PUT, DELETE');

api_json([
    'success' => false,
    'message' => 'Metode permintaan tidak diizinkan.',
], 405);