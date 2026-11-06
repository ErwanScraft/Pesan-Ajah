<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_ROOT . '/app/delivery.php';

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

function edit_order_phone(mixed $value): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $phone = preg_replace('/\D+/', '', $value);

    if (!$phone) {
        return null;
    }

    if (str_starts_with($phone, '0')) {
        $phone = '62' . substr($phone, 1);
    } elseif (str_starts_with($phone, '8')) {
        $phone = '62' . $phone;
    }

    if (
        !str_starts_with($phone, '62')
        || strlen($phone) < 10
        || strlen($phone) > 16
    ) {
        return null;
    }

    return $phone;
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

$orderNumber = $input['order_number'] ?? null;
$token = $input['buyer_token'] ?? null;
$buyerName = trim((string) ($input['buyer_name'] ?? ''));
$buyerPhoneInput = trim((string) ($input['buyer_phone'] ?? ''));
$buyerPhone = edit_order_phone($buyerPhoneInput);
$notes = trim((string) ($input['notes'] ?? ''));
$address = trim((string) ($input['delivery_address'] ?? ''));
$mapsUrl = trim((string) ($input['delivery_maps_url'] ?? ''));
$items = $input['items'] ?? [];

if (
    !is_string($orderNumber)
    || !preg_match('/^PA\d{6}[A-F0-9]{8}$/i', $orderNumber)
    || !is_string($token)
    || !preg_match('/^[a-f0-9]{64}$/i', $token)
    || mb_strlen($buyerName, 'UTF-8') < 1
    || mb_strlen($buyerName, 'UTF-8') > 100
    || mb_strlen($notes, 'UTF-8') > 500
    || !is_array($items)
    || count($items) > 50
) {
    edit_order_json([
        'success' => false,
        'message' => 'Periksa kembali data pesanan.',
    ], 422);
}

if (mb_strlen($address, 'UTF-8') > 255) {
    edit_order_json([
        'success' => false,
        'message' => 'Detail alamat maksimal 255 karakter.',
    ], 422);
}

if ($mapsUrl !== '') {
    $parsedUrl = filter_var($mapsUrl, FILTER_VALIDATE_URL);
    $host = strtolower((string) parse_url($mapsUrl, PHP_URL_HOST));

    $allowedHosts = [
        'google.com',
        'www.google.com',
        'maps.google.com',
        'maps.app.goo.gl',
        'goo.gl',
    ];

    $validHost = false;

    foreach ($allowedHosts as $allowedHost) {
        if (
            $host === $allowedHost
            || str_ends_with($host, '.' . $allowedHost)
        ) {
            $validHost = true;
            break;
        }
    }

    if (
        !$parsedUrl
        || parse_url($mapsUrl, PHP_URL_SCHEME) !== 'https'
        || !$validHost
    ) {
        edit_order_json([
            'success' => false,
            'message' => 'Link Google Maps tidak valid.',
        ], 422);
    }
}

$latitudeInput = $input['delivery_latitude'] ?? null;
$longitudeInput = $input['delivery_longitude'] ?? null;

$latitude = null;
$longitude = null;

if ($latitudeInput !== null && $latitudeInput !== '') {
    if (
        !is_numeric($latitudeInput)
        || (float) $latitudeInput < -90
        || (float) $latitudeInput > 90
    ) {
        edit_order_json([
            'success' => false,
            'message' => 'Koordinat lintang tidak valid.',
        ], 422);
    }

    $latitude = (float) $latitudeInput;
}

if ($longitudeInput !== null && $longitudeInput !== '') {
    if (
        !is_numeric($longitudeInput)
        || (float) $longitudeInput < -180
        || (float) $longitudeInput > 180
    ) {
        edit_order_json([
            'success' => false,
            'message' => 'Koordinat bujur tidak valid.',
        ], 422);
    }

    $longitude = (float) $longitudeInput;
}

if (($latitude === null) !== ($longitude === null)) {
    edit_order_json([
        'success' => false,
        'message' => 'Koordinat lokasi harus diisi berpasangan.',
    ], 422);
}

$normalizedItems = [];

foreach ($items as $item) {
    if (!is_array($item)) {
        edit_order_json([
            'success' => false,
            'message' => 'Rincian produk tidak valid.',
        ], 422);
    }

    $itemId = filter_var(
        $item['id'] ?? null,
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

    if ($itemId === false || $quantity === false) {
        edit_order_json([
            'success' => false,
            'message' => 'Jumlah produk tidak valid.',
        ], 422);
    }

    if (isset($normalizedItems[$itemId])) {
        edit_order_json([
            'success' => false,
            'message' => 'Produk tidak boleh dikirim berulang.',
        ], 422);
    }

    $normalizedItems[$itemId] = (int) $quantity;
}

$db = database();

try {
    $db->beginTransaction();

    /*
     * Kunci pesanan dan validasi kepemilikan menggunakan token.
     */
    $orderStmt = $db->prepare(
        'SELECT
            o.id,
            o.store_id,
            o.buyer_id,
            o.status,
            o.delivery_method,
            o.delivery_address,
            o.delivery_maps_url,
            o.delivery_latitude,
            o.delivery_longitude
         FROM orders o
         WHERE o.order_number = :order_number
           AND o.buyer_token_hash = :token_hash
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
    $deliveryMethod = (string) $order['delivery_method'];

    if ($deliveryMethod === 'delivery' && $buyerPhone === null) {
        throw new RuntimeException(
            'Nomor WhatsApp wajib diisi untuk Delivery.'
        );
    }

    if ($deliveryMethod === 'pickup') {
        $buyerPhone = $buyerPhone ?: null;
        $address = '';
        $mapsUrl = '';
        $latitude = null;
        $longitude = null;
    }

    /*
     * Ambil item lama.
     */
    $oldItemStmt = $db->prepare(
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

    $oldItemStmt->execute([
        'order_id' => $orderId,
    ]);

    $oldItems = $oldItemStmt->fetchAll();

    if (!$oldItems) {
        throw new RuntimeException(
            'Rincian pesanan tidak ditemukan.'
        );
    }

    $oldItemMap = [];

    foreach ($oldItems as $oldItem) {
        $oldItemMap[(int) $oldItem['id']] = $oldItem;
    }

    /*
     * Item tanpa product_id merupakan snapshot produk yang
     * sudah dihapus. Item tersebut dipertahankan tanpa perubahan.
     */
    $newItems = [];
    $oldStock = [];
    $newStock = [];

    foreach ($oldItems as $oldItem) {
        $itemId = (int) $oldItem['id'];
        $productId = $oldItem['product_id'] !== null
            ? (int) $oldItem['product_id']
            : null;

        $oldQuantity = (int) $oldItem['quantity'];

        if ($productId === null) {
            $newItems[] = [
                'id' => $itemId,
                'product_id' => null,
                'product_name' => $oldItem['product_name'],
                'product_variant' => $oldItem['product_variant'],
                'quantity' => $oldQuantity,
                'price' => (int) $oldItem['price'],
            ];

            continue;
        }

        $oldStock[$productId] =
            ($oldStock[$productId] ?? 0) + $oldQuantity;

        if (!array_key_exists($itemId, $normalizedItems)) {
            continue;
        }

        $quantity = $normalizedItems[$itemId];

        $newStock[$productId] =
            ($newStock[$productId] ?? 0) + $quantity;

        $newItems[] = [
            'id' => $itemId,
            'product_id' => $productId,
            'product_name' => $oldItem['product_name'],
            'product_variant' => $oldItem['product_variant'],
            'quantity' => $quantity,
            'price' => (int) $oldItem['price'],
        ];
    }

    /*
     * Pastikan tidak ada ID item asing yang disisipkan.
     */
    foreach ($normalizedItems as $itemId => $quantity) {
        if (!isset($oldItemMap[$itemId])) {
            throw new RuntimeException(
                'Rincian produk tidak valid.'
            );
        }
    }

    if (!$newItems) {
        throw new RuntimeException(
            'Pesanan harus memiliki minimal satu produk.'
        );
    }

    /*
     * Kunci produk dan sesuaikan stok berdasarkan selisih.
     * Stok pesanan sudah dikurangi saat status menjadi pending.
     */
    $productStmt = $db->prepare(
        'SELECT
            id,
            name,
            is_available,
            stock_quantity
         FROM products
         WHERE id = :product_id
           AND store_id = :store_id
         LIMIT 1
         FOR UPDATE'
    );

    $deductStmt = $db->prepare(
        'UPDATE products
         SET stock_quantity = stock_quantity - :quantity
         WHERE id = :product_id
           AND store_id = :store_id
           AND stock_quantity IS NOT NULL
           AND stock_quantity >= :check_quantity'
    );

    $restoreStmt = $db->prepare(
        'UPDATE products
         SET stock_quantity = stock_quantity + :quantity
         WHERE id = :product_id
           AND store_id = :store_id
           AND stock_quantity IS NOT NULL'
    );

    $productIds = array_unique(array_merge(
        array_keys($oldStock),
        array_keys($newStock)
    ));

    sort($productIds);

    foreach ($productIds as $productId) {
        $productStmt->execute([
            'product_id' => $productId,
            'store_id' => $storeId,
        ]);

        $product = $productStmt->fetch();

        if (!$product) {
            continue;
        }

        $difference =
            ($newStock[$productId] ?? 0)
            - ($oldStock[$productId] ?? 0);

        if ($difference === 0 || $product['stock_quantity'] === null) {
            continue;
        }

        if ($difference > 0) {
            if (!(bool) $product['is_available']) {
                throw new RuntimeException(
                    'Produk ' . $product['name'] . ' sudah tidak tersedia.'
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
                'product_id' => $productId,
                'store_id' => $storeId,
                'check_quantity' => $difference,
            ]);

            if ($deductStmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Stok produk gagal diperbarui.'
                );
            }
        } else {
            $restoreQuantity = abs($difference);

            $restoreStmt->execute([
                'quantity' => $restoreQuantity,
                'product_id' => $productId,
                'store_id' => $storeId,
            ]);
        }
    }

    /*
     * Hitung subtotal menggunakan harga snapshot pesanan.
     * Harga tidak berubah hanya karena katalog diperbarui.
     */
    $subtotal = 0;

    foreach ($newItems as &$newItem) {
        $newItem['subtotal'] =
            $newItem['price'] * $newItem['quantity'];

        $subtotal += $newItem['subtotal'];
    }

    unset($newItem);

    /*
     * Hitung ulang ongkir menggunakan aturan checkout.
     */
    $deliveryFee = 0;
    $deliveryFeeOriginal = 0;
    $deliveryDiscount = 0;
    $deliveryDistanceKm = null;

    if ($deliveryMethod === 'delivery') {
        $storeStmt = $db->prepare(
            'SELECT
                latitude,
                longitude,
                free_delivery,
                delivery_discount
             FROM stores
             WHERE id = :store_id
             LIMIT 1
             FOR UPDATE'
        );

        $storeStmt->execute([
            'store_id' => $storeId,
        ]);

        $store = $storeStmt->fetch();

        if (!$store) {
            throw new RuntimeException(
                'Data warung tidak ditemukan.'
            );
        }

        $isFreeDelivery = (bool) $store['free_delivery'];

        if (
            !$isFreeDelivery
            && (
                $latitude === null
                || $longitude === null
                || $store['latitude'] === null
                || $store['longitude'] === null
            )
        ) {
            throw new RuntimeException(
                'Lokasi pengantaran belum lengkap.'
            );
        }

        if (
            $latitude !== null
            && $longitude !== null
            && $store['latitude'] !== null
            && $store['longitude'] !== null
        ) {
            $deliveryDistanceKm = delivery_distance_km(
                (float) $store['latitude'],
                (float) $store['longitude'],
                $latitude,
                $longitude
            );

            $deliveryFeeOriginal = DELIVERY_BASE_FEE
                + delivery_fee_from_distance($deliveryDistanceKm);
        }

        if ($isFreeDelivery) {
            $deliveryDiscount = $deliveryFeeOriginal;
            $deliveryFee = 0;
        } else {
            $deliveryDiscount = min(
                max(0, (int) $store['delivery_discount']),
                DELIVERY_BASE_FEE
            );

            $deliveryFee = (
                DELIVERY_BASE_FEE - $deliveryDiscount
            ) + ($deliveryFeeOriginal - DELIVERY_BASE_FEE);
        }
    }

    $total = $subtotal + $deliveryFee;

    /*
     * Simpan ulang rincian produk.
     */
    $deleteItems = $db->prepare(
        'DELETE FROM order_items
         WHERE order_id = :order_id'
    );

    $deleteItems->execute([
        'order_id' => $orderId,
    ]);

    $insertItem = $db->prepare(
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

    foreach ($newItems as $newItem) {
        $insertItem->execute([
            'order_id' => $orderId,
            'product_id' => $newItem['product_id'],
            'product_name' => $newItem['product_name'],
            'product_variant' => $newItem['product_variant'],
            'quantity' => $newItem['quantity'],
            'price' => $newItem['price'],
            'subtotal' => $newItem['subtotal'],
        ]);
    }

    /*
     * Perbarui data pesanan.
     */
    $updateOrder = $db->prepare(
        'UPDATE orders SET
            buyer_name = :buyer_name,
            buyer_phone = :buyer_phone,
            notes = :notes,
            delivery_address = :address,
            delivery_maps_url = :maps_url,
            delivery_latitude = :latitude,
            delivery_longitude = :longitude,
            delivery_fee = :delivery_fee,
            delivery_fee_original = :fee_original,
            delivery_discount = :discount,
            delivery_distance_km = :distance,
            subtotal = :subtotal,
            total = :total
         WHERE id = :id'
    );

    $updateOrder->execute([
        'buyer_name' => $buyerName,
        'buyer_phone' => $buyerPhone,
        'notes' => $notes !== '' ? $notes : null,
        'address' => $deliveryMethod === 'delivery' ? $address : null,
        'maps_url' => $deliveryMethod === 'delivery' && $mapsUrl !== ''
            ? $mapsUrl
            : null,
        'latitude' => $deliveryMethod === 'delivery' ? $latitude : null,
        'longitude' => $deliveryMethod === 'delivery' ? $longitude : null,
        'delivery_fee' => $deliveryFee,
        'fee_original' => $deliveryFeeOriginal,
        'discount' => $deliveryDiscount,
        'distance' => $deliveryDistanceKm,
        'subtotal' => $subtotal,
        'total' => $total,
        'id' => $orderId,
    ]);

    /*
     * Sinkronkan profil pembeli jika relasinya tersedia.
     */
    if (!empty($order['buyer_id'])) {
        $buyerUpdate = $db->prepare(
            'UPDATE buyers SET
                name = :name,
                phone = :phone,
                delivery_address = :address,
                delivery_maps_url = :maps_url,
                delivery_latitude = :latitude,
                delivery_longitude = :longitude
             WHERE id = :id'
        );

        $buyerUpdate->execute([
            'name' => $buyerName,
            'phone' => $buyerPhone,
            'address' => $deliveryMethod === 'delivery' ? $address : null,
            'maps_url' => $deliveryMethod === 'delivery' && $mapsUrl !== ''
                ? $mapsUrl
                : null,
            'latitude' => $deliveryMethod === 'delivery' ? $latitude : null,
            'longitude' => $deliveryMethod === 'delivery' ? $longitude : null,
            'id' => (int) $order['buyer_id'],
        ]);
    }

    $db->commit();

    edit_order_json([
        'success' => true,
        'message' => 'Pesanan berhasil diperbarui.',
        'subtotal' => $subtotal,
        'delivery_fee' => $deliveryFee,
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
