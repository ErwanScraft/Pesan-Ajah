<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_method('POST');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function edit_order_json(array $data, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

try {
    $input = json_decode(
        file_get_contents('php://input') ?: '',
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException) {
    edit_order_json([
        'success' => false,
        'message' => 'Format permintaan tidak valid.',
    ], 400);
}

if (!is_array($input)) {
    edit_order_json([
        'success' => false,
        'message' => 'Data permintaan tidak valid.',
    ], 400);
}

$orderNumber = $input['order_number'] ?? '';
$token = $input['buyer_token'] ?? '';
$notes = trim((string) ($input['notes'] ?? ''));
$items = $input['items'] ?? [];

if (
    !is_string($orderNumber)
    || !preg_match('/^PA\d{6}[A-F0-9]{8}$/i', $orderNumber)
    || !is_string($token)
    || !preg_match('/^[a-f0-9]{64}$/i', $token)
    || mb_strlen($notes, 'UTF-8') > 500
    || !is_array($items)
    || count($items) > 50
) {
    edit_order_json([
        'success' => false,
        'message' => 'Data pesanan tidak valid.',
    ], 422);
}

/*
|--------------------------------------------------------------------------
| NORMALISASI ITEM
|--------------------------------------------------------------------------
*/

$requestedItems = [];

foreach ($items as $item) {
    if (!is_array($item)) {
        edit_order_json([
            'success' => false,
            'message' => 'Rincian produk tidak valid.',
        ], 422);
    }

    $productId = filter_var(
        $item['product_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $quantity = filter_var(
        $item['quantity'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => [
            'min_range' => 1,
            'max_range' => 99,
        ]]
    );

    if ($productId === false || $quantity === false) {
        edit_order_json([
            'success' => false,
            'message' => 'Jumlah produk tidak valid.',
        ], 422);
    }

    if (isset($requestedItems[$productId])) {
        edit_order_json([
            'success' => false,
            'message' => 'Produk tidak boleh dikirim berulang.',
        ], 422);
    }

    $requestedItems[$productId] = (int) $quantity;
}

if (!$requestedItems) {
    edit_order_json([
        'success' => false,
        'message' => 'Pesanan harus memiliki minimal satu produk.',
    ], 422);
}

$db = database();

try {
    $db->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | VALIDASI PESANAN DAN KEPEMILIKAN
    |--------------------------------------------------------------------------
    */

    $orderStmt = $db->prepare(
        'SELECT
            id,
            store_id,
            status,
            delivery_fee
         FROM orders
         WHERE order_number = :order_number
           AND buyer_token_hash = :token_hash
         LIMIT 1
         FOR UPDATE'
    );

    $orderStmt->execute([
        'order_number' => $orderNumber,
        'token_hash' => hash('sha256', $token),
    ]);

    $order = $orderStmt->fetch();

    if (!$order) {
        throw new RuntimeException(
            'Pesanan tidak ditemukan atau akses tidak valid.'
        );
    }

    if (!in_array(
        $order['status'],
        ['pending', 'confirmed', 'processing'],
        true
    )) {
        throw new RuntimeException(
            'Pesanan tidak dapat diedit pada status saat ini.'
        );
    }

    $orderId = (int) $order['id'];
    $storeId = (int) $order['store_id'];

    /*
    |--------------------------------------------------------------------------
    | AMBIL ITEM LAMA
    |--------------------------------------------------------------------------
    */

    $oldStmt = $db->prepare(
        'SELECT
            id,
            product_id,
            product_name,
            product_variant,
            quantity,
            price
         FROM order_items
         WHERE order_id = :order_id
         ORDER BY id ASC
         FOR UPDATE'
    );

    $oldStmt->execute([
        'order_id' => $orderId,
    ]);

    $oldItems = $oldStmt->fetchAll();

    $oldProducts = [];
    $oldStock = [];

    foreach ($oldItems as $oldItem) {
        if ($oldItem['product_id'] === null) {
            continue;
        }

        $productId = (int) $oldItem['product_id'];

        $oldStock[$productId] =
            ($oldStock[$productId] ?? 0)
            + (int) $oldItem['quantity'];

        if (!isset($oldProducts[$productId])) {
            $oldProducts[$productId] = $oldItem;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | AMBIL PRODUK DAN VALIDASI STOK
    |--------------------------------------------------------------------------
    */

    $productStmt = $db->prepare(
        'SELECT
            id,
            name,
            variant,
            price,
            is_available,
            stock_quantity
         FROM products
         WHERE id = :product_id
           AND store_id = :store_id
         LIMIT 1
         FOR UPDATE'
    );

    $newItems = [];
    $newStock = [];
    $subtotal = 0;

    foreach ($requestedItems as $productId => $quantity) {
        $productStmt->execute([
            'product_id' => $productId,
            'store_id' => $storeId,
        ]);

        $product = $productStmt->fetch();

        if (!$product) {
            throw new RuntimeException(
                'Produk tidak ditemukan di warung ini.'
            );
        }

        /*
         * Produk lama mempertahankan harga snapshot pesanan.
         * Produk yang baru ditambahkan menggunakan harga katalog.
         */
        if (isset($oldProducts[$productId])) {
            $oldProduct = $oldProducts[$productId];

            $price = (float) $oldProduct['price'];
            $name = (string) $oldProduct['product_name'];
            $variant = $oldProduct['product_variant'];
        } else {
            if (!(bool) $product['is_available']) {
                throw new RuntimeException(
                    'Produk ' . $product['name'] . ' tidak tersedia.'
                );
            }

            $price = (float) $product['price'];
            $name = (string) $product['name'];
            $variant = trim((string) ($product['variant'] ?? '')) !== ''
                ? (string) $product['variant']
                : null;
        }

        $newStock[$productId] = $quantity;

        $lineSubtotal = round($price * $quantity, 2);

        $subtotal = round($subtotal + $lineSubtotal, 2);

        $newItems[] = [
            'product_id' => $productId,
            'product_name' => $name,
            'product_variant' => $variant,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $lineSubtotal,
        ];
    }

    /*
     * Pertahankan snapshot produk yang sudah dihapus dari katalog.
     * Produk tersebut tidak bisa diedit dari katalog.
     */
    foreach ($oldItems as $oldItem) {
        if ($oldItem['product_id'] !== null) {
            continue;
        }

        $quantity = (int) $oldItem['quantity'];
        $price = (float) $oldItem['price'];

        $newItems[] = [
            'product_id' => null,
            'product_name' => $oldItem['product_name'],
            'product_variant' => $oldItem['product_variant'],
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => round($price * $quantity, 2),
        ];

        $subtotal = round(
            $subtotal + ($price * $quantity),
            2
        );
    }

    if (!$newItems) {
        throw new RuntimeException(
            'Pesanan harus memiliki minimal satu produk.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SINKRONISASI STOK BERDASARKAN SELISIH
    |--------------------------------------------------------------------------
    |
    | Stok pesanan sudah dikurangi saat pesanan diaktifkan.
    | Jangan mengurangi ulang seluruh jumlah pesanan.
    |--------------------------------------------------------------------------
    */

    $stockIds = array_unique(array_merge(
        array_keys($oldStock),
        array_keys($newStock)
    ));

    sort($stockIds);

    $stockStmt = $db->prepare(
        'SELECT id, name, is_available, stock_quantity
         FROM products
         WHERE id = :id
           AND store_id = :store_id
         LIMIT 1
         FOR UPDATE'
    );

    $deductStmt = $db->prepare(
        'UPDATE products
         SET stock_quantity = stock_quantity - :quantity
         WHERE id = :id
           AND store_id = :store_id
           AND stock_quantity IS NOT NULL
           AND stock_quantity >= :required'
    );

    $restoreStmt = $db->prepare(
        'UPDATE products
         SET stock_quantity = stock_quantity + :quantity
         WHERE id = :id
           AND store_id = :store_id
           AND stock_quantity IS NOT NULL'
    );

    foreach ($stockIds as $productId) {
        $difference =
            ($newStock[$productId] ?? 0)
            - ($oldStock[$productId] ?? 0);

        if ($difference === 0) {
            continue;
        }

        $stockStmt->execute([
            'id' => $productId,
            'store_id' => $storeId,
        ]);

        $product = $stockStmt->fetch();

        if (!$product) {
            continue;
        }

        if ($product['stock_quantity'] === null) {
            continue;
        }

        if ($difference > 0) {
            if (!(bool) $product['is_available']) {
                throw new RuntimeException(
                    'Produk ' . $product['name'] . ' tidak tersedia.'
                );
            }

            if ((int) $product['stock_quantity'] < $difference) {
                throw new RuntimeException(
                    'Stok ' . $product['name']
                    . ' tidak mencukupi. Tersisa '
                    . (int) $product['stock_quantity'] . '.'
                );
            }

            $deductStmt->execute([
                'quantity' => $difference,
                'id' => $productId,
                'store_id' => $storeId,
                'required' => $difference,
            ]);

            if ($deductStmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Stok produk gagal diperbarui.'
                );
            }
        } else {
            $restoreStmt->execute([
                'quantity' => abs($difference),
                'id' => $productId,
                'store_id' => $storeId,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SIMPAN ITEM DAN TOTAL
    |--------------------------------------------------------------------------
    */

    $deleteStmt = $db->prepare(
        'DELETE FROM order_items
         WHERE order_id = :order_id'
    );

    $deleteStmt->execute([
        'order_id' => $orderId,
    ]);

    $insertStmt = $db->prepare(
        'INSERT INTO order_items (
            order_id,
            product_id,
            product_name,
            product_variant,
            quantity,
            price,
            subtotal
        ) VALUES (
            :order_id,
            :product_id,
            :product_name,
            :product_variant,
            :quantity,
            :price,
            :subtotal
        )'
    );

    foreach ($newItems as $item) {
        $insertStmt->execute([
            'order_id' => $orderId,
            'product_id' => $item['product_id'],
            'product_name' => $item['product_name'],
            'product_variant' => $item['product_variant'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'subtotal' => $item['subtotal'],
        ]);
    }

    /*
     * Ongkir dipertahankan. Hanya subtotal dan total produk
     * yang dihitung ulang.
     */
    $deliveryFee = (float) $order['delivery_fee'];
    $total = round($subtotal + $deliveryFee, 2);

    $updateStmt = $db->prepare(
        'UPDATE orders
         SET notes = :notes,
             subtotal = :subtotal,
             total = :total
         WHERE id = :id'
    );

    $updateStmt->execute([
        'notes' => $notes !== '' ? $notes : null,
        'subtotal' => $subtotal,
        'total' => $total,
        'id' => $orderId,
    ]);

    $db->commit();

    edit_order_json([
        'success' => true,
        'message' => 'Pesanan berhasil diperbarui.',
        'subtotal' => $subtotal,
        'total' => $total,
    ]);
} catch (RuntimeException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    edit_order_json([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 409);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log(
        '[Pesan Ajah] Gagal mengedit pesanan: '
        . $exception->getMessage()
    );

    edit_order_json([
        'success' => false,
        'message' => 'Pesanan gagal diperbarui. Silakan coba lagi.',
    ], 500);
}