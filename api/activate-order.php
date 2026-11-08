<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/modules/push-notifications/main.php';
require_method('POST');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function activate_order_json(
    array $data,
    int $status = 200
): never {
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
    activate_order_json([
        'success' => false,
        'message' => 'Format permintaan tidak valid.',
    ], 400);
}

if (!is_array($input)) {
    activate_order_json([
        'success' => false,
        'message' => 'Data permintaan tidak valid.',
    ], 400);
}

$orderNumber = $input['order_number'] ?? null;
$token = $input['buyer_token'] ?? null;

if (
    !is_string($orderNumber)
    || !preg_match('/^PA\d{6}[A-F0-9]{8}$/i', $orderNumber)
    || !is_string($token)
    || !preg_match('/^[a-f0-9]{64}$/i', $token)
) {
    activate_order_json([
        'success' => false,
        'message' => 'Data pesanan tidak valid.',
    ], 422);
}

$db = database();

try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        'SELECT
            id,
            store_id,
            order_number,
            buyer_name,
            status
         FROM orders
         WHERE order_number = :order_number
           AND buyer_token_hash = :token_hash
         FOR UPDATE'
    );

    $stmt->execute([
        'order_number' => $orderNumber,
        'token_hash' => hash('sha256', $token),
    ]);

    $order = $stmt->fetch();

    if (!$order) {
        $db->rollBack();

        activate_order_json([
            'success' => false,
            'message' => 'Pesanan tidak ditemukan atau akses tidak valid.',
        ], 404);
    }

    if ($order['status'] === 'pending') {
        $db->commit();
    
        activate_order_json([
            'success' => true,
            'status' => 'pending',
            'message' => 'Pesanan sudah aktif.',
        ]);
    }

    if ($order['status'] !== 'draft') {
        $db->rollBack();

        activate_order_json([
            'success' => false,
            'message' => 'Pesanan tidak dapat diaktifkan dari status saat ini.',
        ], 409);
    }
    
    /*
    |---------------------------------------------------------------------------
    | PENGURANGAN STOCK OTOMATIS
    |--------------------------------------------------------------------------
    | Hanya dilakukan ketika draft berubah menjadi pending.
    | Produk dengan stock_quantity NULL tidak dibatasi.
    */
    
    $itemStmt = $db->prepare(
        'SELECT
            product_id,
            SUM(quantity) AS total_quantity
    
        FROM order_items
    
        WHERE order_id = :order_id
          AND product_id IS NOT NULL
    
        GROUP BY product_id
    
        ORDER BY product_id ASC'
    );
    
    $itemStmt->execute([
        'order_id' => (int) $order['id'],
    ]);
    
    $orderItems = $itemStmt->fetchAll();
    
    $productStmt = $db->prepare(
        'SELECT
            id,
            name,
            is_available,
            stock_quantity
    
        FROM products
    
        WHERE id = :product_id
    
        LIMIT 1
    
        FOR UPDATE'
    );
    
    $deductStmt = $db->prepare(
        'UPDATE products
    
         SET stock_quantity = stock_quantity - :deduct_quantity
    
         WHERE id = :product_id
           AND stock_quantity IS NOT NULL
           AND stock_quantity >= :check_quantity'
    );
    
    foreach ($orderItems as $item) {
        $productId = (int) $item['product_id'];
        $quantity = (int) $item['total_quantity'];
    
        $productStmt->execute([
            'product_id' => $productId,
        ]);
    
        $product = $productStmt->fetch();
    
        if (!$product || !(bool) $product['is_available']) {
            throw new RuntimeException(
                'Salah satu produk pesanan sudah tidak tersedia.'
            );
        }
    
        // NULL berarti stok tidak dibatasi.
        if ($product['stock_quantity'] === null) {
            continue;
        }
    
        $currentStock = (int) $product['stock_quantity'];
    
        if ($currentStock < $quantity) {
            throw new RuntimeException(
                'Stock '
                . $product['name']
                . ' tidak mencukupi. Stock tersisa: '
                . $currentStock
            );
        }
    
        $deductStmt->execute([
            'deduct_quantity' => $quantity,
            'product_id' => $productId,
            'check_quantity' => $quantity,
        ]);
    
        if ($deductStmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Stock produk gagal diperbarui.'
            );
        }
    }
    
    $update = $db->prepare(
        "UPDATE orders
         SET status = 'pending'
         WHERE id = :id
           AND status = 'draft'"
    );

    $update->execute([
        'id' => (int) $order['id'],
    ]);

    if ($update->rowCount() !== 1) {
        throw new RuntimeException(
            'Status pesanan gagal diperbarui.'
        );
    }

    $db->commit();
    
    /*
     * Kirim notifikasi setelah draft berhasil menjadi pending.
     * Kegagalan push tidak membatalkan pesanan.
     */
    send_store_order_notifications(
        $db,
        (int) $order['store_id'],
        (string) $order['order_number'],
        (string) $order['buyer_name']
    );
    
    activate_order_json([
        'success' => true,
        'status' => 'pending',
        'message' => 'Pesanan berhasil diaktifkan.',
    ]);

} catch (RuntimeException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    activate_order_json([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 409);

} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log(
        '[Pesan Ajah] Gagal mengaktifkan pesanan: '
        . $exception->getMessage()
    );

    activate_order_json([
        'success' => false,
        'message' => 'Pesanan belum dapat diaktifkan. Silakan coba lagi.',
    ], 500);
}
