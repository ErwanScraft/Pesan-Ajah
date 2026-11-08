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
            variant,
            category,
            price,
            unit,
            image,
            stock_quantity,
            is_available

        FROM products

        WHERE store_id = :store_id

        ORDER BY
            category ASC,
            sort_order ASC,
            name ASC'
    );

    $stmt->execute([
        'store_id' => $storeId,
    ]);

    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        $product['id'] = (int) $product['id'];
        $product['price'] = (int) $product['price'];

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

$items = $input['items'] ?? null;
$paymentMethod = $input['payment_method'] ?? null;

if (!is_array($items) || count($items) < 1 || count($items) > 100) {
    api_json([
        'success' => false,
        'message' => 'Keranjang tidak valid.',
    ], 422);
}

if (!in_array($paymentMethod, ['cash', 'qris', 'transfer'], true)) {
    api_json([
        'success' => false,
        'message' => 'Metode pembayaran tidak valid.',
    ], 422);
}

$cashReceived = 0;

if ($paymentMethod === 'cash') {
    $cashReceived = filter_var(
        $input['cash_received'] ?? null,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 0,
                'max_range' => 2147483647,
            ],
        ]
    );

    if ($cashReceived === false) {
        api_json([
            'success' => false,
            'message' => 'Nominal uang diterima tidak valid.',
        ], 422);
    }

    $cashReceived = (int) $cashReceived;
}

/*
 * Pisahkan item katalog dan item manual.
 * Item katalog digabung berdasarkan ID untuk mencegah
 * pengurangan stock ganda yang tidak terhitung.
 */

$catalogItems = [];
$manualItems = [];

foreach ($items as $item) {
    if (!is_array($item)) {
        api_json([
            'success' => false,
            'message' => 'Format item tidak valid.',
        ], 422);
    }

    $type = $item['type'] ?? '';

    $quantity = filter_var(
        $item['quantity'] ?? null,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
                'max_range' => 2147483647,
            ],
        ]
    );

    if ($quantity === false) {
        api_json([
            'success' => false,
            'message' => 'Jumlah item tidak valid.',
        ], 422);
    }

    $quantity = (int) $quantity;

    if ($type === 'catalog') {
        $productId = filter_var(
            $item['product_id'] ?? null,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($productId === false) {
            api_json([
                'success' => false,
                'message' => 'Produk katalog tidak valid.',
            ], 422);
        }

        $catalogItems[$productId] =
            ($catalogItems[$productId] ?? 0) + $quantity;

        if ($catalogItems[$productId] > 2147483647) {
            api_json([
                'success' => false,
                'message' => 'Jumlah produk melebihi batas.',
            ], 422);
        }

        continue;
    }

    if ($type === 'manual') {
        $name = api_required_text(
            $item['name'] ?? null,
            'Nama item manual',
            150
        );

        $price = filter_var(
            $item['price'] ?? null,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                    'max_range' => 2147483647,
                ],
            ]
        );

        if ($price === false) {
            api_json([
                'success' => false,
                'message' => 'Harga item manual tidak valid.',
            ], 422);
        }

        $manualItems[] = [
            'type' => 'manual',
            'product_id' => null,
            'name' => $name,
            'variant' => null,
            'price' => (int) $price,
            'quantity' => $quantity,
            'unit' => 'pcs',
        ];

        continue;
    }

    api_json([
        'success' => false,
        'message' => 'Jenis item tidak dikenal.',
    ], 422);
}

if (count($catalogItems) + count($manualItems) > 100) {
    api_json([
        'success' => false,
        'message' => 'Jumlah item dalam transaksi terlalu banyak.',
    ], 422);
}

$db = database();

try {
    $db->beginTransaction();

    $saleItems = [];
    $total = 0;

    /*
     * Urutkan ID untuk mengurangi kemungkinan deadlock
     * ketika beberapa kasir mengakses produk bersamaan.
     */

    ksort($catalogItems, SORT_NUMERIC);

    $selectProduct = $db->prepare(
        'SELECT
            id,
            name,
            variant,
            price,
            unit,
            stock_quantity,
            is_available

        FROM products

        WHERE id = :product_id
          AND store_id = :store_id

        LIMIT 1

        FOR UPDATE'
    );

    $updateStock = $db->prepare(
        'UPDATE products

         SET stock_quantity = :stock_quantity

         WHERE id = :product_id
           AND store_id = :store_id'
    );

    foreach ($catalogItems as $productId => $quantity) {
        $selectProduct->execute([
            'product_id' => $productId,
            'store_id' => $storeId,
        ]);

        $product = $selectProduct->fetch();

        if (!$product) {
            throw new RuntimeException(
                'Salah satu produk tidak ditemukan.'
            );
        }

        if (!(bool) $product['is_available']) {
            throw new RuntimeException(
                'Produk ' . $product['name'] . ' tidak tersedia.'
            );
        }

        $stock = $product['stock_quantity'] === null
            ? null
            : (int) $product['stock_quantity'];

        if ($stock !== null && $stock < $quantity) {
            throw new RuntimeException(
                'Stock ' . $product['name'] . ' tidak mencukupi.'
            );
        }

        $price = (int) $product['price'];

        if ($price < 1) {
            throw new RuntimeException(
                'Harga produk ' . $product['name'] . ' tidak valid.'
            );
        }

        $subtotal = $price * $quantity;

        if ($subtotal > PHP_INT_MAX - $total) {
            throw new RuntimeException(
                'Total transaksi melebihi batas.'
            );
        }

        $total += $subtotal;

        $saleItems[] = [
            'type' => 'catalog',
            'product_id' => (int) $product['id'],
            'name' => (string) $product['name'],
            'variant' => $product['variant'],
            'price' => $price,
            'quantity' => $quantity,
            'unit' => (string) $product['unit'],
            'subtotal' => $subtotal,
        ];

        /*
         * Stock NULL berarti produk tidak dibatasi stock.
         * Hanya stock terbatas yang dikurangi.
         */

        if ($stock !== null) {
            $updateStock->execute([
                'stock_quantity' => $stock - $quantity,
                'product_id' => $productId,
                'store_id' => $storeId,
            ]);
        }
    }

    /*
     * Item manual tidak mengakses tabel products.
     * Harga dan nama diambil dari input setelah validasi.
     */

    foreach ($manualItems as $item) {
        $subtotal = $item['price'] * $item['quantity'];

        if ($subtotal > PHP_INT_MAX - $total) {
            throw new RuntimeException(
                'Total transaksi melebihi batas.'
            );
        }

        $total += $subtotal;

        $saleItems[] = [
            ...$item,
            'subtotal' => $subtotal,
        ];
    }

    if ($total < 1 || $total > 4294967295) {
        throw new RuntimeException(
            'Total pembayaran berada di luar batas.'
        );
    }

    if ($paymentMethod === 'cash') {
        if ($cashReceived < $total) {
            throw new RuntimeException(
                'Uang yang diterima belum mencukupi.'
            );
        }

        $change = $cashReceived - $total;
    } else {
        $cashReceived = 0;
        $change = 0;
    }

    $db->commit();

    api_json([
        'success' => true,
        'message' => 'Pembayaran berhasil diproses.',

        'receipt' => [
            'store_name' => (string) $store['name'],
            'store_logo' => (string) ($store['icon_image'] ?? ''),
            'store_address' => (string) ($store['address'] ?? ''),
            'store_phone' => (string) ($store['phone'] ?? ''),
            'items' => $saleItems,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'cash_received' => $cashReceived,
            'change' => $change,
            'created_at' => date('d-m-Y H:i:s'),
        ],
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
        '[Pesan Ajah Kasir] '
        . $exception->getMessage()
    );

    api_json([
        'success' => false,
        'message' => 'Transaksi gagal diproses. Stock tidak diubah.',
    ], 500);
}