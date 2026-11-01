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
            unit,
            image,
            stock_quantity,
            is_available

        FROM products

        WHERE store_id = :store_id

        ORDER BY name ASC, id ASC'
    );

    $stmt->execute([
        'store_id' => $storeId,
    ]);

    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        $product['id'] = (int) $product['id'];

        $product['stock_quantity'] =
            $product['stock_quantity'] === null
                ? null
                : (int) $product['stock_quantity'];

        $product['is_available'] =
            (bool) $product['is_available'];
    }

    unset($product);

    api_json([
        'success' => true,
        'products' => $products,
    ]);
}

api_verify_csrf();

if ($method !== 'POST') {
    header('Allow: GET, POST');

    api_json([
        'success' => false,
        'message' => 'Metode permintaan tidak diizinkan.',
    ], 405);
}

$input = api_json_input();

$productId = api_positive_id(
    $input['product_id'] ?? null
);

$action = $input['action'] ?? null;

if (!in_array($action, ['set', 'increase', 'decrease'], true)) {
    api_json([
        'success' => false,
        'message' => 'Aksi perubahan stock tidak valid.',
    ], 422);
}

$quantity = filter_var(
    $input['quantity'] ?? null,
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => $action === 'set' ? 0 : 1,
            'max_range' => 2147483647,
        ],
    ]
);

if ($quantity === false) {
    api_json([
        'success' => false,
        'message' => 'Jumlah stock tidak valid.',
    ], 422);
}

$quantity = (int) $quantity;

$db = database();

try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        'SELECT
            id,
            stock_quantity

        FROM products

        WHERE id = :product_id
          AND store_id = :store_id

        LIMIT 1

        FOR UPDATE'
    );

    $stmt->execute([
        'product_id' => $productId,
        'store_id' => $storeId,
    ]);

    $product = $stmt->fetch();

    if (!$product) {
        $db->rollBack();

        api_json([
            'success' => false,
            'message' => 'Produk tidak ditemukan.',
        ], 404);
    }

    $currentStock = $product['stock_quantity'] === null
        ? null
        : (int) $product['stock_quantity'];

    if ($action === 'set') {
        $newStock = $quantity;
    } elseif ($action === 'increase') {
        if ($currentStock === null) {
            $newStock = $quantity;
        } else {
            if ($currentStock > 2147483647 - $quantity) {
                throw new RuntimeException(
                    'Jumlah stock melebihi batas.'
                );
            }

            $newStock = $currentStock + $quantity;
        }
    } else {
        if ($currentStock === null) {
            throw new RuntimeException(
                'Tentukan stock awal produk terlebih dahulu.'
            );
        }

        if ($currentStock < $quantity) {
            throw new RuntimeException(
                'Stock tidak mencukupi untuk dikurangi.'
            );
        }

        $newStock = $currentStock - $quantity;
    }

    $update = $db->prepare(
        'UPDATE products

         SET stock_quantity = :stock_quantity

         WHERE id = :product_id
           AND store_id = :store_id'
    );

    $update->execute([
        'stock_quantity' => $newStock,
        'product_id' => $productId,
        'store_id' => $storeId,
    ]);

    $db->commit();

    api_json([
        'success' => true,
        'message' => 'Stock berhasil diperbarui.',
        'stock_quantity' => $newStock,
    ]);

} catch (RuntimeException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    api_json([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 422);

} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log(
        '[Pesan Ajah] Gagal memperbarui stock: '
        . $exception->getMessage()
    );

    api_json([
        'success' => false,
        'message' => 'Stock gagal diperbarui.',
    ], 500);
}