<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/web_push.php';

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
    send_store_order_push(
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
