<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/warung_api.php';

$store = api_store_owner();
$storeId = (int) $store['store_id'];

if (request_method() === 'GET') {
    $stmt = database()->prepare(
        'SELECT
            o.id,
            o.order_number,
            o.buyer_name,
            o.buyer_phone,
            o.delivery_method,
            o.delivery_address,
            o.delivery_fee,
            o.subtotal,
            o.total,
            o.status,
            o.created_at,
            o.updated_at

        FROM orders o

        WHERE o.store_id = :store_id

        ORDER BY o.created_at DESC, o.id DESC'
    );

    $stmt->execute([
        'store_id' => $storeId,
    ]);

    $orders = $stmt->fetchAll();

    $itemStmt = database()->prepare(
        'SELECT
            product_name,
            product_variant,
            quantity,
            price,
            subtotal

        FROM order_items

        WHERE order_id = :order_id

        ORDER BY id ASC'
    );

    foreach ($orders as &$order) {
        $itemStmt->execute([
            'order_id' => (int) $order['id'],
        ]);

        $order['items'] = $itemStmt->fetchAll();

        $order['id'] = (int) $order['id'];
        $order['delivery_fee'] = (float) $order['delivery_fee'];
        $order['subtotal'] = (float) $order['subtotal'];
        $order['total'] = (float) $order['total'];
    }

    unset($order);

    api_json([
        'success' => true,
        'orders' => $orders,
    ]);
}

api_verify_csrf();

if (request_method() !== 'POST') {
    header('Allow: GET, POST');

    api_json([
        'success' => false,
        'message' => 'Metode permintaan tidak diizinkan.',
    ], 405);
}

$input = api_json_input();

$id = api_positive_id($input['id'] ?? null);

$status = $input['status'] ?? null;

$allowedStatuses = [
    'confirmed',
    'processing',
    'completed',
    'cancelled',
];

if (
    !is_string($status)
    || !in_array($status, $allowedStatuses, true)
) {
    api_json([
        'success' => false,
        'message' => 'Status pesanan tidak valid.',
    ], 422);
}

/*
 * Kunci baris pesanan dan validasi transisi status
 * dalam satu transaksi database.
 */
$db = database();

try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        'SELECT id, status

        FROM orders

        WHERE id = :id
          AND store_id = :store_id

        FOR UPDATE'
    );

    $stmt->execute([
        'id' => $id,
        'store_id' => $storeId,
    ]);

    $order = $stmt->fetch();

    if (!$order) {
        $db->rollBack();

        api_json([
            'success' => false,
            'message' => 'Pesanan tidak ditemukan.',
        ], 404);
    }

    $current = (string) $order['status'];

    $transitions = [
        'pending' => [
            'confirmed',
            'cancelled',
        ],

        'confirmed' => [
            'processing',
            'cancelled',
        ],

        'processing' => [
            'completed',
        ],

        'completed' => [],

        'cancelled' => [],
    ];

    if (
        !in_array(
            $status,
            $transitions[$current] ?? [],
            true
        )
    ) {
        $db->rollBack();

        api_json([
            'success' => false,
            'message' => 'Perubahan status tidak diizinkan.',
        ], 422);
    }

    $update = $db->prepare(
        'UPDATE orders

        SET status = :status

        WHERE id = :id
          AND store_id = :store_id'
    );

    $update->execute([
        'status' => $status,
        'id' => $id,
        'store_id' => $storeId,
    ]);

    $db->commit();

    api_json([
        'success' => true,
        'message' => 'Status pesanan berhasil diperbarui.',
        'status' => $status,
    ]);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log(
        '[Pesan Ajah] Gagal memperbarui status pesanan: '
        . $exception->getMessage()
    );

    api_json([
        'success' => false,
        'message' => 'Pesanan gagal diperbarui.',
    ], 500);
}