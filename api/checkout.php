<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_method('POST');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function checkout_json(array $data, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

$input = json_decode(
    file_get_contents('php://input') ?: '',
    true
);

if (!is_array($input)) {
    checkout_json([
        'success' => false,
        'message' => 'Data checkout tidak valid.',
    ], 400);
}

$csrf = $input['_token'] ?? '';
$storedToken = $_SESSION['_csrf_token'] ?? '';

if (
    !is_string($csrf)
    || !is_string($storedToken)
    || $storedToken === ''
    || !hash_equals($storedToken, $csrf)
) {
    checkout_json([
        'success' => false,
        'message' => 'Sesi checkout tidak valid. Muat ulang halaman.',
    ], 419);
}

$storeId = filter_var(
    $input['store_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$buyerName = trim(
    is_string($input['buyer_name'] ?? null)
        ? $input['buyer_name']
        : ''
);

$buyerPhone = trim(
    is_string($input['buyer_phone'] ?? null)
        ? $input['buyer_phone']
        : ''
);

$deliveryMethod = $input['delivery_method'] ?? '';

$deliveryAddress = trim(
    is_string($input['delivery_address'] ?? null)
        ? $input['delivery_address']
        : ''
);

$items = $input['items'] ?? [];

if (
    $storeId === false
    || $buyerName === ''
    || mb_strlen($buyerName, 'UTF-8') > 100
    || $buyerPhone === ''
    || mb_strlen($buyerPhone, 'UTF-8') > 25
    || !in_array($deliveryMethod, ['delivery', 'pickup'], true)
    || !is_array($items)
    || count($items) < 1
    || count($items) > 50
) {
    checkout_json([
        'success' => false,
        'message' => 'Periksa kembali data pembeli dan produk.',
    ], 422);
}

if (
    $deliveryMethod === 'delivery'
    && $deliveryAddress === ''
) {
    checkout_json([
        'success' => false,
        'message' => 'Alamat pengantaran wajib diisi.',
    ], 422);
}

/*
 * Verifikasi warung aktif dan sedang buka.
 */
$storeStmt = database()->prepare(
    'SELECT
        id,
        is_open,
        free_delivery,
        is_active

    FROM stores

    WHERE id = :id

    LIMIT 1'
);

$storeStmt->execute([
    'id' => $storeId,
]);

$store = $storeStmt->fetch();

if (!$store || !(bool) $store['is_active']) {
    checkout_json([
        'success' => false,
        'message' => 'Warung tidak tersedia.',
    ], 404);
}

if (!(bool) $store['is_open']) {
    checkout_json([
        'success' => false,
        'message' => 'Warung sedang tutup.',
    ], 409);
}

/*
 * Gabungkan ID duplikat agar satu produk tidak diproses
 * berkali-kali secara terpisah.
 */
$quantities = [];

foreach ($items as $item) {
    if (!is_array($item)) {
        checkout_json([
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
        ['options' => ['min_range' => 1, 'max_range' => 99]]
    );

    if ($productId === false || $quantity === false) {
        checkout_json([
            'success' => false,
            'message' => 'ID atau jumlah produk tidak valid.',
        ], 422);
    }

    $quantities[$productId] =
        ($quantities[$productId] ?? 0) + $quantity;

    if ($quantities[$productId] > 99) {
        checkout_json([
            'success' => false,
            'message' => 'Jumlah produk melebihi batas.',
        ], 422);
    }
}

$db = database();

try {
    $db->beginTransaction();

    /*
     * Kunci data warung selama validasi checkout.
     */
    $lockStore = $db->prepare(
        'SELECT
            id,
            is_open,
            free_delivery,
            is_active

        FROM stores

        WHERE id = :id

        FOR UPDATE'
    );

    $lockStore->execute([
        'id' => $storeId,
    ]);

    $lockedStore = $lockStore->fetch();

    if (
        !$lockedStore
        || !(bool) $lockedStore['is_active']
        || !(bool) $lockedStore['is_open']
    ) {
        $db->rollBack();

        checkout_json([
            'success' => false,
            'message' => 'Warung tidak menerima pesanan saat ini.',
        ], 409);
    }

    $productStmt = $db->prepare(
        'SELECT
            id,
            name,
            price,
            is_available

        FROM products

        WHERE id = :id
          AND store_id = :store_id

        FOR UPDATE'
    );

    $subtotal = 0.0;
    $orderItems = [];

    foreach ($quantities as $productId => $quantity) {
        $productStmt->execute([
            'id' => $productId,
            'store_id' => $storeId,
        ]);

        $product = $productStmt->fetch();

        if (!$product || !(bool) $product['is_available']) {
            $db->rollBack();

            checkout_json([
                'success' => false,
                'message' => 'Salah satu produk tidak tersedia.',
            ], 409);
        }

        $price = (float) $product['price'];
        $lineSubtotal = round($price * $quantity, 2);

        $subtotal += $lineSubtotal;

        $orderItems[] = [
            'product_id' => (int) $product['id'],
            'product_name' => (string) $product['name'],
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $lineSubtotal,
        ];
    }

    $deliveryFee = 0.0;

    if ($deliveryMethod === 'delivery') {
        $deliveryFee = (bool) $lockedStore['free_delivery']
            ? 0.0
            : 1000.0;
    }

    $total = round($subtotal + $deliveryFee, 2);

    $orderNumber = 'PA'
        . date('ymd')
        . strtoupper(bin2hex(random_bytes(4)));

    $insertOrder = $db->prepare(
        'INSERT INTO orders (
            store_id,
            order_number,
            buyer_name,
            buyer_phone,
            delivery_method,
            delivery_address,
            delivery_fee,
            subtotal,
            total,
            status
        ) VALUES (
            :store_id,
            :order_number,
            :buyer_name,
            :buyer_phone,
            :delivery_method,
            :delivery_address,
            :delivery_fee,
            :subtotal,
            :total,
            :status
        )'
    );

    $insertOrder->execute([
        'store_id' => $storeId,
        'order_number' => $orderNumber,
        'buyer_name' => $buyerName,
        'buyer_phone' => $buyerPhone,
        'delivery_method' => $deliveryMethod,
        'delivery_address' => $deliveryMethod === 'delivery'
            ? $deliveryAddress
            : null,
        'delivery_fee' => $deliveryFee,
        'subtotal' => $subtotal,
        'total' => $total,
        'status' => 'pending',
    ]);

    $orderId = (int) $db->lastInsertId();

    $insertItem = $db->prepare(
        'INSERT INTO order_items (
            order_id,
            product_id,
            product_name,
            quantity,
            price,
            subtotal
        ) VALUES (
            :order_id,
            :product_id,
            :product_name,
            :quantity,
            :price,
            :subtotal
        )'
    );

    foreach ($orderItems as $item) {
        $insertItem->execute([
            'order_id' => $orderId,
            'product_id' => $item['product_id'],
            'product_name' => $item['product_name'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'subtotal' => $item['subtotal'],
        ]);
    }

    $db->commit();

    checkout_json([
        'success' => true,
        'message' => 'Pesanan berhasil dibuat.',
        'order_number' => $orderNumber,
        'total' => $total,
    ], 201);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log(
        '[Pesan Ajah] Checkout gagal: '
        . $exception->getMessage()
    );

    checkout_json([
        'success' => false,
        'message' => 'Pesanan gagal dibuat. Silakan coba lagi.',
    ], 500);
}