<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/warung_api.php';

$store = api_store_owner();
$storeId = (int) $store['store_id'];

$method = request_method();

if ($method === 'GET') {
    $stmt = database()->prepare(
        'SELECT
            id,
            name,
            description,
            category,
            price,
            unit,
            image,
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

api_verify_csrf();

$input = api_json_input();

if ($method === 'POST') {
    $name = api_required_text(
        $input['name'] ?? null,
        'Nama produk',
        120
    );

    $category = api_required_text(
        $input['category'] ?? null,
        'Kategori',
        80
    );

    $unit = api_required_text(
        $input['unit'] ?? null,
        'Satuan',
        30
    );

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

    $available = filter_var(
        $input['available'] ?? true,
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    );

    if ($available === null) {
        api_json([
            'success' => false,
            'message' => 'Status ketersediaan tidak valid.',
        ], 422);
    }

    $stmt = database()->prepare(
        'INSERT INTO products (
            store_id,
            name,
            description,
            category,
            price,
            unit,
            is_available
        ) VALUES (
            :store_id,
            :name,
            :description,
            :category,
            :price,
            :unit,
            :is_available
        )'
    );

    $stmt->execute([
        'store_id' => $storeId,
        'name' => $name,
        'description' => $description !== '' ? $description : null,
        'category' => $category,
        'price' => $price,
        'unit' => $unit,
        'is_available' => $available ? 1 : 0,
    ]);

    api_json([
        'success' => true,
        'message' => 'Produk berhasil ditambahkan.',
        'id' => (int) database()->lastInsertId(),
    ], 201);
}

if ($method === 'PUT') {
    $id = api_positive_id($input['id'] ?? null);

    $name = api_required_text(
        $input['name'] ?? null,
        'Nama produk',
        120
    );

    $category = api_required_text(
        $input['category'] ?? null,
        'Kategori',
        80
    );

    $unit = api_required_text(
        $input['unit'] ?? null,
        'Satuan',
        30
    );

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

    $available = filter_var(
        $input['available'] ?? true,
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    );

    if ($available === null) {
        api_json([
            'success' => false,
            'message' => 'Status ketersediaan tidak valid.',
        ], 422);
    }

    $stmt = database()->prepare(
        'UPDATE products

        SET
            name = :name,
            description = :description,
            category = :category,
            price = :price,
            unit = :unit,
            is_available = :is_available

        WHERE id = :id
          AND store_id = :store_id'
    );

    $stmt->execute([
        'name' => $name,
        'description' => $description !== '' ? $description : null,
        'category' => $category,
        'price' => $price,
        'unit' => $unit,
        'is_available' => $available ? 1 : 0,
        'id' => $id,
        'store_id' => $storeId,
    ]);

    $check = database()->prepare(
        'SELECT id
         FROM products
         WHERE id = :id
           AND store_id = :store_id
         LIMIT 1'
    );

    $check->execute([
        'id' => $id,
        'store_id' => $storeId,
    ]);

    if (!$check->fetch()) {
        api_json([
            'success' => false,
            'message' => 'Produk tidak ditemukan.',
        ], 404);
    }

    api_json([
        'success' => true,
        'message' => 'Produk berhasil diperbarui.',
    ]);
}

if ($method === 'DELETE') {
    $id = api_positive_id($input['id'] ?? null);

    /*
     * order_items.product_id menggunakan ON DELETE SET NULL.
     * Riwayat tetap mempertahankan product_name dan harga.
     */
    $stmt = database()->prepare(
        'DELETE FROM products
         WHERE id = :id
           AND store_id = :store_id'
    );

    $stmt->execute([
        'id' => $id,
        'store_id' => $storeId,
    ]);

    if ($stmt->rowCount() === 0) {
        api_json([
            'success' => false,
            'message' => 'Produk tidak ditemukan.',
        ], 404);
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