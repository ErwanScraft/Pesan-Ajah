<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_method('POST');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function buyer_orders_json(
    array $data,
    int $status = 200
): never {
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}

$raw = file_get_contents('php://input');

try {
    $input = json_decode(
        $raw ?: '',
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException) {
    buyer_orders_json([
        'success' => false,
        'message' => 'Format permintaan tidak valid.',
    ], 400);
}

if (!is_array($input)) {
    buyer_orders_json([
        'success' => false,
        'message' => 'Data permintaan tidak valid.',
    ], 400);
}

$tokens = $input['tokens'] ?? [];

if (!is_array($tokens)) {
    buyer_orders_json([
        'success' => false,
        'message' => 'Daftar token tidak valid.',
    ], 422);
}

$tokens = array_slice($tokens, 0, 50);

$hashes = [];

foreach ($tokens as $token) {
    if (
        !is_string($token)
        || !preg_match('/^[a-f0-9]{64}$/i', $token)
    ) {
        continue;
    }

    $hashes[] = hash('sha256', $token);
}

$hashes = array_values(array_unique($hashes));

if (count($hashes) === 0) {
    buyer_orders_json([
        'success' => true,
        'orders' => [],
    ]);
}

$db = database();

$placeholders = [];
$params = [];

foreach ($hashes as $index => $hash) {
    $key = ':token_' . $index;

    $placeholders[] = $key;
    $params[$key] = $hash;
}

$sql = '
    SELECT
        o.id,
        o.order_number,
        o.buyer_name,
        o.delivery_method,
        o.delivery_address,
        o.delivery_maps_url,
        o.delivery_latitude,
        o.delivery_longitude,
        o.delivery_fee,
        o.subtotal,
        o.total,
        o.status,
        o.created_at,
        o.updated_at,
        s.name AS store_name

    FROM orders o

    INNER JOIN stores s
        ON s.id = o.store_id

    WHERE o.buyer_token_hash IN (
        ' . implode(', ', $placeholders) . '
    )

    ORDER BY o.created_at DESC, o.id DESC
';

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $orders = $stmt->fetchAll();

    $itemStmt = $db->prepare(
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

        if ($order['delivery_latitude'] !== null) {
            $order['delivery_latitude'] =
                (float) $order['delivery_latitude'];
        }

        if ($order['delivery_longitude'] !== null) {
            $order['delivery_longitude'] =
                (float) $order['delivery_longitude'];
        }
    }

    unset($order);

    buyer_orders_json([
        'success' => true,
        'orders' => $orders,
    ]);
} catch (Throwable $exception) {
    error_log(
        '[Pesan Ajah] Gagal memuat Dashboard Buyer: '
        . $exception->getMessage()
    );

    buyer_orders_json([
        'success' => false,
        'message' => 'Pesanan gagal dimuat. Silakan coba lagi.',
    ], 500);
}