<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/warung_api.php';

api_verify_csrf();

if (request_method() !== 'POST') {
    header('Allow: POST');

    api_json([
        'success' => false,
        'message' => 'Metode tidak diizinkan.',
    ], 405);
}

$store = api_store_owner();
$storeId = (int) $store['store_id'];

$input = api_json_input();

$id = api_positive_id($input['id'] ?? null);
$notes = trim((string) ($input['notes'] ?? ''));
$items = $input['items'] ?? null;

if (
    !is_array($items)
    || count($items) > 50
    || mb_strlen($notes, 'UTF-8') > 500
) {
    api_json([
        'success' => false,
        'message' => 'Data pesanan tidak valid.',
    ], 422);
}

$requested = [];

foreach ($items as $item) {
    if (!is_array($item)) {
        api_json([
            'success' => false,
            'message' => 'Item tidak valid.',
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
        api_json([
            'success' => false,
            'message' => 'Jumlah produk tidak valid.',
        ], 422);
    }

    if (isset($requested[$productId])) {
        api_json([
            'success' => false,
            'message' => 'Produk duplikat.',
        ], 422);
    }

    $requested[$productId] = (int) $quantity;
}

$db = database();

try {
    $db->beginTransaction();

    // Validasi kepemilikan dan status.
    $stmt = $db->prepare(
        'SELECT id, status, delivery_fee
         FROM orders
         WHERE id = :id
           AND store_id = :store_id
         LIMIT 1
         FOR UPDATE'
    );

    $stmt->execute([
        'id' => $id,
        'store_id' => $storeId,
    ]);

    $order = $stmt->fetch();

    if (!$order) {
        throw new RuntimeException(
            'Pesanan tidak ditemukan.'
        );
    }

    if (!in_array(
        $order['status'],
        ['pending', 'confirmed', 'processing'],
        true
    )) {
        throw new RuntimeException(
            'Pesanan tidak dapat diedit pada status ini.'
        );
    }

    // Ambil item lama.
    $stmt = $db->prepare(
        'SELECT
            id,
            product_id,
            product_name,
            product_variant,
            quantity,
            price
         FROM order_items
         WHERE order_id = :id
         ORDER BY id ASC
         FOR UPDATE'
    );

    $stmt->execute(['id' => $id]);

    $oldItems = $stmt->fetchAll();

    $oldProducts = [];
    $oldStock = [];

    foreach ($oldItems as $item) {
        if ($item['product_id'] === null) {
            continue;
        }

        $productId = (int) $item['product_id'];

        $oldStock[$productId] =
            ($oldStock[$productId] ?? 0)
            + (int) $item['quantity'];

        if (!isset($oldProducts[$productId])) {
            $oldProducts[$productId] = $item;
        }
    }

    // Ambil produk katalog.
    $productStmt = $db->prepare(
        'SELECT
            id,
            name,
            variant,
            price,
            is_available,
            stock_quantity
         FROM products
         WHERE id = :id
           AND store_id = :store_id
         LIMIT 1
         FOR UPDATE'
    );

    $newItems = [];
    $newStock = [];
    $subtotal = 0;

    foreach ($requested as $productId => $quantity) {
        $productStmt->execute([
            'id' => $productId,
            'store_id' => $storeId,
        ]);

        $product = $productStmt->fetch();

        if (!$product) {
            throw new RuntimeException(
                'Produk tidak ditemukan.'
            );
        }

        if (isset($oldProducts[$productId])) {
            // Harga snapshot lama dipertahankan.
            $old = $oldProducts[$productId];

            $name = $old['product_name'];
            $variant = $old['product_variant'];
            $price = (float) $old['price'];
        } else {
            if (!(bool) $product['is_available']) {
                throw new RuntimeException(
                    'Produk ' . $product['name']
                    . ' tidak tersedia.'
                );
            }

            $name = $product['name'];
            $variant = $product['variant'];
            $price = (float) $product['price'];
        }

        $newStock[$productId] = $quantity;

        $lineTotal = round(
            $price * $quantity,
            2
        );

        $subtotal += $lineTotal;

        $newItems[] = [
            'product_id' => $productId,
            'product_name' => $name,
            'product_variant' => $variant,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $lineTotal,
        ];
    }

    // Pertahankan produk lama yang sudah dihapus dari katalog.
    foreach ($oldItems as $item) {
        if ($item['product_id'] !== null) {
            continue;
        }

        $quantity = (int) $item['quantity'];
        $price = (float) $item['price'];

        $newItems[] = [
            'product_id' => null,
            'product_name' => $item['product_name'],
            'product_variant' => $item['product_variant'],
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $price * $quantity,
        ];

        $subtotal += $price * $quantity;
    }

    if (!$newItems) {
        throw new RuntimeException(
            'Pesanan minimal memiliki satu produk.'
        );
    }

    // Sinkronisasi stok berdasarkan selisih.
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
         FOR UPDATE'
    );

    $deduct = $db->prepare(
        'UPDATE products
         SET stock_quantity = stock_quantity - :quantity
         WHERE id = :id
           AND store_id = :store_id
           AND stock_quantity IS NOT NULL
           AND stock_quantity >= :required'
    );

    $restore = $db->prepare(
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

        if (!$product || $product['stock_quantity'] === null) {
            continue;
        }

        if ($difference > 0) {
            if (!(bool) $product['is_available']) {
                throw new RuntimeException(
                    'Produk ' . $product['name']
                    . ' tidak tersedia.'
                );
            }

            if ((int) $product['stock_quantity'] < $difference) {
                throw new RuntimeException(
                    'Stok ' . $product['name']
                    . ' tidak mencukupi.'
                );
            }

            $deduct->execute([
                'quantity' => $difference,
                'id' => $productId,
                'store_id' => $storeId,
                'required' => $difference,
            ]);

            if ($deduct->rowCount() !== 1) {
                throw new RuntimeException(
                    'Gagal memperbarui stok.'
                );
            }
        } else {
            $restore->execute([
                'quantity' => abs($difference),
                'id' => $productId,
                'store_id' => $storeId,
            ]);
        }
    }

    // Simpan item baru.
    $delete = $db->prepare(
        'DELETE FROM order_items
         WHERE order_id = :id'
    );

    $delete->execute(['id' => $id]);

    $insert = $db->prepare(
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
        $insert->execute([
            'order_id' => $id,
            'product_id' => $item['product_id'],
            'product_name' => $item['product_name'],
            'product_variant' => $item['product_variant'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'subtotal' => $item['subtotal'],
        ]);
    }

    $deliveryFee = (float) $order['delivery_fee'];
    $total = round($subtotal + $deliveryFee, 2);

    $update = $db->prepare(
        'UPDATE orders
         SET notes = :notes,
             subtotal = :subtotal,
             total = :total
         WHERE id = :id
           AND store_id = :store_id'
    );

    $update->execute([
        'notes' => $notes !== '' ? $notes : null,
        'subtotal' => $subtotal,
        'total' => $total,
        'id' => $id,
        'store_id' => $storeId,
    ]);

    $db->commit();

    api_json([
        'success' => true,
        'message' => 'Pesanan berhasil diperbarui.',
        'subtotal' => $subtotal,
        'total' => $total,
    ]);

} catch (RuntimeException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    api_json([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 409);

} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log(
        '[Pesan Ajah] Edit pesanan warung: '
        . $exception->getMessage()
    );

    api_json([
        'success' => false,
        'message' => 'Gagal memperbarui pesanan.',
    ], 500);
}
