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
           AND o.status <> 'draft'

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
$action = $input['action'] ?? null;

/*
 * Hapus riwayat pesanan.
 * Hanya pesanan berstatus completed milik warung
 * yang sedang login yang dapat dihapus.
 */
if ($action === 'delete') {
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

        $status = (string) $order['status'];
        
        if (!in_array($status, ['completed', 'cancelled'], true)) {
            $db->rollBack();
        
            api_json([
                'success' => false,
                'message' => 'Hanya pesanan selesai atau dibatalkan yang dapat dihapus.',
            ], 422);
        }
        
        /*
         * Hapus penugasan driver terlebih dahulu karena
         * delivery_assignments.order_id mereferensikan orders.id.
         *
         * Tetap berada dalam transaksi yang sama agar semua
         * perubahan dibatalkan jika penghapusan gagal.
         */
        $deleteAssignment = $db->prepare(
            'DELETE FROM delivery_assignments
             WHERE order_id = :order_id
               AND store_id = :store_id'
        );
        
        $deleteAssignment->execute([
            'order_id' => $id,
            'store_id' => $storeId,
        ]);
        
        /*
         * Hapus item pesanan terlebih dahulu agar tidak
         * terkendala foreign key pada order_items.
         */
        $deleteItems = $db->prepare(
            'DELETE FROM order_items
             WHERE order_id = :order_id'
        );

        $deleteItems->execute([
            'order_id' => $id,
        ]);

        /*
         * Pastikan pesanan masih milik warung ini
         * dan statusnya tetap dapat dihapus.
         */
        $deleteOrder = $db->prepare(
            'DELETE FROM orders
             WHERE id = :id
               AND store_id = :store_id
               AND status IN (\'completed\', \'cancelled\')'
        );
        
        $deleteOrder->execute([
            'id' => $id,
            'store_id' => $storeId,
        ]);

        if ($deleteOrder->rowCount() !== 1) {
            $db->rollBack();

            api_json([
                'success' => false,
                'message' => 'Pesanan tidak dapat dihapus.',
            ], 409);
        }

        $db->commit();

        api_json([
            'success' => true,
            'message' => 'Riwayat pesanan berhasil dihapus.',
        ]);
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        error_log(
            '[Pesan Ajah] Gagal menghapus riwayat pesanan: '
            . $exception->getMessage()
        );

        api_json([
            'success' => false,
            'message' => 'Riwayat pesanan gagal dihapus.',
        ], 500);
    }
}

$status = $input['status'] ?? null;

$allowedStatuses = [
    'confirmed',
    'processing',
    'ready_for_pickup',
    'ready_for_driver',
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

$db = database();

try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        'SELECT id, status, delivery_method
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
    $method = (string) $order['delivery_method'];

    /*
     * Transisi umum:
     * pending -> confirmed -> processing
     *
     * Tahap berikutnya bergantung pada metode pesanan.
     */
    $transitions = [
        'pending' => [
            'confirmed',
        ],
        'confirmed' => [
            'processing',
        ],
        'processing' => $method === 'pickup'
            ? ['ready_for_pickup']
            : ['ready_for_driver'],
        'ready_for_pickup' => [],
        'ready_for_driver' => [],
        'in_delivery' => [],
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

    /*
     * Pastikan tahap akhir sesuai metode pengiriman.
     */
    if (
        ($status === 'ready_for_pickup' && $method !== 'pickup')
        || ($status === 'ready_for_driver' && $method !== 'delivery')
    ) {
        $db->rollBack();

        api_json([
            'success' => false,
            'message' => 'Status tidak sesuai metode pesanan.',
        ], 422);
    }

    $update = $db->prepare(
        'UPDATE orders
         SET status = :status,
             confirmed_at = CASE
                 WHEN :confirmed_status = \'confirmed\'
                     THEN COALESCE(confirmed_at, CURRENT_TIMESTAMP)
                 ELSE confirmed_at
             END
         WHERE id = :id
           AND store_id = :store_id'
    );

    $update->execute([
        'status' => $status,
        'confirmed_status' => $status,
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