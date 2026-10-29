<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_once APP_ROOT . '/app/delivery.php';

require_method('POST');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function checkout_json(
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

function checkout_phone(mixed $value): ?string
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

function checkout_rupiah(float $amount): string
{
    return 'Rp' . number_format(
        $amount,
        0,
        ',',
        '.'
    );
}

/*
|--------------------------------------------------------------------------
| BACA DAN VALIDASI REQUEST
|--------------------------------------------------------------------------
*/

try {
    $input = json_decode(
        file_get_contents('php://input') ?: '',
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException) {
    checkout_json([
        'success' => false,
        'message' => 'Data checkout tidak valid.',
    ], 400);
}

if (!is_array($input)) {
    checkout_json([
        'success' => false,
        'message' => 'Data checkout tidak valid.',
    ], 400);
}

$csrf = $input['_token'] ?? '';
$storedCsrf = $_SESSION['_csrf_token'] ?? '';

if (
    !is_string($csrf)
    || !is_string($storedCsrf)
    || $storedCsrf === ''
    || !hash_equals($storedCsrf, $csrf)
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

$buyerPhoneNormalized = checkout_phone($buyerPhone);

$buyerNote = trim(
    is_string($input['buyer_note'] ?? null)
        ? str_replace(
            ["\r\n", "\r"],
            "\n",
            $input['buyer_note']
        )
        : ''
);

if (mb_strlen($buyerNote, 'UTF-8') > 500) {
    checkout_json([
        'success' => false,
        'message' => 'Catatan pembeli maksimal 500 karakter.',
    ], 422);
}

$deliveryMethod = $input['delivery_method'] ?? '';

$deliveryAddress = trim(
    is_string($input['delivery_address'] ?? null)
        ? $input['delivery_address']
        : ''
);

$deliveryMapsUrl = trim(
    is_string($input['delivery_maps_url'] ?? null)
        ? $input['delivery_maps_url']
        : ''
);

$latitudeInput = $input['delivery_latitude'] ?? null;
$longitudeInput = $input['delivery_longitude'] ?? null;

$items = $input['items'] ?? [];

if (
    $storeId === false
    || $buyerName === ''
    || mb_strlen($buyerName, 'UTF-8') > 100
    || $buyerPhoneNormalized === null
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
    && mb_strlen($deliveryAddress, 'UTF-8') > 255
) {
    checkout_json([
        'success' => false,
        'message' => 'Detail alamat maksimal 255 karakter.',
    ], 422);
}

$deliveryLatitude = null;
$deliveryLongitude = null;

if ($latitudeInput !== null && $latitudeInput !== '') {
    if (
        !is_numeric($latitudeInput)
        || (float) $latitudeInput < -90
        || (float) $latitudeInput > 90
    ) {
        checkout_json([
            'success' => false,
            'message' => 'Koordinat lintang tidak valid.',
        ], 422);
    }

    $deliveryLatitude = (float) $latitudeInput;
}

if ($longitudeInput !== null && $longitudeInput !== '') {
    if (
        !is_numeric($longitudeInput)
        || (float) $longitudeInput < -180
        || (float) $longitudeInput > 180
    ) {
        checkout_json([
            'success' => false,
            'message' => 'Koordinat bujur tidak valid.',
        ], 422);
    }

    $deliveryLongitude = (float) $longitudeInput;
}

if (
    ($deliveryLatitude === null)
    !== ($deliveryLongitude === null)
) {
    checkout_json([
        'success' => false,
        'message' => 'Koordinat lokasi harus diisi bersama.',
    ], 422);
}

/*
|--------------------------------------------------------------------------
| NORMALISASI KERANJANG
|--------------------------------------------------------------------------
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
        ['options' => [
            'min_range' => 1,
            'max_range' => 99,
        ]]
    );

    if ($productId === false || $quantity === false) {
        checkout_json([
            'success' => false,
            'message' => 'Produk atau jumlah produk tidak valid.',
        ], 422);
    }

    $productId = (int) $productId;
    $quantity = (int) $quantity;

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
    |--------------------------------------------------------------------------
    | VALIDASI WARUNG
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare(
        "SELECT
            s.id,
            s.name,
            s.phone,
            s.is_open,
            s.free_delivery,
            s.delivery_discount,
            s.is_active,
            u.is_active AS user_is_active,
            s.latitude,
            s.longitude

        FROM stores s

        INNER JOIN users u
            ON u.id = s.user_id

        WHERE s.id = :store_id
          AND u.role = 'warung'

        LIMIT 1

        FOR UPDATE"
    );

    $stmt->execute([
        'store_id' => (int) $storeId,
    ]);

    $store = $stmt->fetch();

    if (
        !$store
        || !(bool) $store['is_active']
        || !(bool) $store['user_is_active']
    ) {
        throw new RuntimeException(
            'Warung tidak tersedia.'
        );
    }

    if (!(bool) $store['is_open']) {
        throw new RuntimeException(
            'Warung sedang tutup.'
        );
    }

    $sellerPhone = checkout_phone(
        $store['phone'] ?? null
    );

    if ($sellerPhone === null) {
        throw new RuntimeException(
            'Nomor WhatsApp warung belum valid.'
        );
    }
    
    /*
    |-------------------------------------------------------------------------- 
    | HITUNG JARAK DAN ONGKIR
    |--------------------------------------------------------------------------
    */
    
    $deliveryDistanceKm = null;
    $deliveryFeeOriginal = 0;
    $deliveryDiscount = 0;
    $deliveryFee = 0;
    
    if ($deliveryMethod === 'delivery') {
        $isFreeDelivery = (bool) $store['free_delivery'];
    
        if (!$isFreeDelivery) {
            if (
                $deliveryLatitude === null
                || $deliveryLongitude === null
            ) {
                throw new RuntimeException(
                    'Pilih lokasi pengantaran menggunakan GPS agar ongkir dapat dihitung.'
                );
            }
    
            if (
                $store['latitude'] === null
                || $store['longitude'] === null
            ) {
                throw new RuntimeException(
                    'Lokasi warung belum diatur. Minta pemilik warung menyimpan lokasi GPS di Pengaturan.'
                );
            }
        }
    
        if (
            $deliveryLatitude !== null
            && $deliveryLongitude !== null
            && $store['latitude'] !== null
            && $store['longitude'] !== null
        ) {
            $deliveryDistanceKm = delivery_distance_km(
                (float) $store['latitude'],
                (float) $store['longitude'],
                $deliveryLatitude,
                $deliveryLongitude
            );
    
            $deliveryFeeOriginal = delivery_fee_from_distance(
                $deliveryDistanceKm
            );
        }
    
        if ($isFreeDelivery) {
            // Gratis ongkir tetap memiliki prioritas.
            $deliveryDiscount = $deliveryFeeOriginal;
            $deliveryFee = 0;
        } else {
            $configuredDiscount = max(
                0,
                (int) ($store['delivery_discount'] ?? 0)
            );
    
            $deliveryDiscount = min(
                $configuredDiscount,
                $deliveryFeeOriginal
            );
    
            $deliveryFee = $deliveryFeeOriginal
                - $deliveryDiscount;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SIMPAN PROFIL PEMBELI
    |--------------------------------------------------------------------------
    */

    $buyerAddress = $deliveryMethod === 'delivery'
        ? $deliveryAddress
        : null;

    $buyerMapsUrl = $deliveryMethod === 'delivery'
        ? ($deliveryMapsUrl !== '' ? $deliveryMapsUrl : null)
        : null;

    $buyerStmt = $db->prepare(
        "INSERT INTO buyers (
            name,
            phone,
            delivery_address,
            delivery_maps_url,
            delivery_latitude,
            delivery_longitude
        ) VALUES (
            :name,
            :phone,
            :address,
            :maps_url,
            :latitude,
            :longitude
        )

        ON DUPLICATE KEY UPDATE
            id = LAST_INSERT_ID(id),
            name = VALUES(name),
            delivery_address = COALESCE(
                VALUES(delivery_address),
                delivery_address
            ),
            delivery_maps_url = COALESCE(
                VALUES(delivery_maps_url),
                delivery_maps_url
            ),
            delivery_latitude = COALESCE(
                VALUES(delivery_latitude),
                delivery_latitude
            ),
            delivery_longitude = COALESCE(
                VALUES(delivery_longitude),
                delivery_longitude
            )"
    );

    $buyerStmt->execute([
        'name' => $buyerName,
        'phone' => $buyerPhoneNormalized,
        'address' => $buyerAddress,
        'maps_url' => $buyerMapsUrl,
        'latitude' => $deliveryMethod === 'delivery'
            ? $deliveryLatitude
            : null,
        'longitude' => $deliveryMethod === 'delivery'
            ? $deliveryLongitude
            : null,
    ]);

    $buyerId = (int) $db->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | VALIDASI PRODUK DAN HITUNG TOTAL
    |--------------------------------------------------------------------------
    */

    $productStmt = $db->prepare(
        'SELECT
            id,
            name,
            variant,
            price,
            is_available

        FROM products

        WHERE id = :product_id
          AND store_id = :store_id

        LIMIT 1

        FOR UPDATE'
    );

    $orderItems = [];
    $subtotal = 0.0;

    foreach ($quantities as $productId => $quantity) {
        $productStmt->execute([
            'product_id' => $productId,
            'store_id' => (int) $storeId,
        ]);

        $product = $productStmt->fetch();

        if (!$product || !(bool) $product['is_available']) {
            throw new RuntimeException(
                'Produk tidak ditemukan atau sudah habis.'
            );
        }

        $price = (float) $product['price'];
        $lineSubtotal = round($price * $quantity, 2);

        $subtotal = round(
            $subtotal + $lineSubtotal,
            2
        );

        $orderItems[] = [
            'product_id' => (int) $product['id'],
            'product_name' => (string) $product['name'],
            'product_variant' => trim(
                (string) ($product['variant'] ?? '')
            ) !== ''
                ? (string) $product['variant']
                : null,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $lineSubtotal,
        ];
    }

    $total = round($subtotal + $deliveryFee, 2);

    /*
    |--------------------------------------------------------------------------
    | SIMPAN PESANAN DAN TOKEN BUYER
    |--------------------------------------------------------------------------
    */

    $orderNumber = 'PA'
        . date('ymd')
        . strtoupper(bin2hex(random_bytes(4)));

    $buyerToken = bin2hex(random_bytes(32));
    $buyerTokenHash = hash('sha256', $buyerToken);

    $orderStmt = $db->prepare(
        "INSERT INTO orders (
            store_id,
            buyer_id,
            order_number,
            buyer_name,
            buyer_phone,
            delivery_method,
            delivery_address,
            delivery_fee,
            delivery_fee_original,
            delivery_discount,
            delivery_distance_km,
            subtotal,
            total,
            status,
            buyer_token_hash,
            delivery_maps_url,
            delivery_latitude,
            delivery_longitude
        ) VALUES (
            :store_id,
            :buyer_id,
            :order_number,
            :buyer_name,
            :buyer_phone,
            :delivery_method,
            :delivery_address,
            :delivery_fee,
            :delivery_fee_original,
            :delivery_discount,
            :delivery_distance_km,
            :subtotal,
            :total,
            'draft',
            :token_hash,
            :maps_url,
            :latitude,
            :longitude
        )"
    );

    $orderStmt->execute([
        'store_id' => (int) $storeId,
        'buyer_id' => $buyerId,
        'order_number' => $orderNumber,
        'buyer_name' => $buyerName,
        'buyer_phone' => $buyerPhone,
        'delivery_method' => $deliveryMethod,
        'delivery_address' => $buyerAddress,
        'delivery_fee' => $deliveryFee,
        'delivery_fee_original' => $deliveryFeeOriginal,
        'delivery_discount' => $deliveryDiscount,
        'delivery_distance_km' => $deliveryDistanceKm,
        'subtotal' => $subtotal,
        'total' => $total,
        'token_hash' => $buyerTokenHash,
        'maps_url' => $buyerMapsUrl,
        'latitude' => $deliveryMethod === 'delivery'
            ? $deliveryLatitude
            : null,
        'longitude' => $deliveryMethod === 'delivery'
            ? $deliveryLongitude
            : null,
    ]);

    $orderId = (int) $db->lastInsertId();

    $itemStmt = $db->prepare(
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

    foreach ($orderItems as $item) {
        $itemStmt->execute([
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
     * Pesanan harus benar-benar tersimpan sebelum
     * URL WhatsApp dikirim ke browser.
     */
    $db->commit();

    /*
    |--------------------------------------------------------------------------
    | FORMAT PESAN WHATSAPP
    |--------------------------------------------------------------------------
    */
    
    $message = [];
    
    $message[] = '*PESAN AJAH';
    $message[] = 'PESANAN BARU*';
    $message[] = '';
    $message[] = '━━━━━━━━━━━━━━━━━━';
    $message[] = '*INFORMASI PESANAN*';
    $message[] = 'No. Pesanan: ' . $orderNumber;
    $message[] = 'Status: Menunggu konfirmasi';
    $message[] = '';
    
    $message[] = '━━━━━━━━━━━━━━━━━━';
    $message[] = '*DATA PEMBELI*';
    $message[] = 'Nama: ' . $buyerName;
    $message[] = 'WhatsApp: ' . $buyerPhone;
    $message[] = '';
    
    if ($buyerNote !== '') {
        $message[] = '━━━━━━━━━━━━━━━━━━';
        $message[] = '*CATATAN PEMBELI*';
        $message[] = $buyerNote;
        $message[] = '';
    }
    
    $message[] = '━━━━━━━━━━━━━━━━━━';
    $message[] = '*INFORMASI PENGAMBILAN*';
    
    if ($deliveryMethod === 'delivery') {
        $message[] = 'Metode: Diantar ke alamat';
        $message[] = '';
        $message[] = '*Alamat:*';
        $message[] = $deliveryAddress;
    
        if ($buyerMapsUrl !== null && $buyerMapsUrl !== '') {
            $message[] = '';
            $message[] = '*Lokasi Google Maps:*';
            $message[] = $buyerMapsUrl;
        }
    
        if ($deliveryDistanceKm !== null) {
            $message[] = 'Jarak garis lurus: '
                . number_format(
                    $deliveryDistanceKm,
                    2,
                    ',',
                    '.'
                )
                . ' km';
        }
    } else {
        $message[] = 'Metode: Ambil sendiri di warung';
    }
    
    $message[] = '';
    $message[] = '━━━━━━━━━━━━━━━━━━';
    $message[] = '*RINCIAN PRODUK*';
    $message[] = '';
    
    foreach ($orderItems as $index => $item) {
        $message[] = '*' . ($index + 1) . '. '
            . $item['product_name'] . '*';
    
        if (!empty($item['product_variant'])) {
            $message[] = '   Varian: '
                . $item['product_variant'];
        }
    
        $message[] = '   Jumlah: '
            . $item['quantity'] . ' x '
            . checkout_rupiah((float) $item['price']);
    
        $message[] = '   Subtotal: '
            . checkout_rupiah((float) $item['subtotal']);
    
        $message[] = '';
    }
    
    $message[] = '━━━━━━━━━━━━━━━━━━';
    $message[] = '*RINGKASAN PEMBAYARAN*';
    $message[] = 'Subtotal: ' . checkout_rupiah($subtotal);
    
    if ($deliveryMethod === 'delivery') {
        $message[] = 'Ongkir standar: '
            . checkout_rupiah($deliveryFeeOriginal);
    
        if ($deliveryDiscount > 0) {
            $message[] = 'Diskon ongkir: -'
                . checkout_rupiah($deliveryDiscount);
        }
    
        $message[] = 'Ongkir dibayar: '
            . checkout_rupiah($deliveryFee);
    }
    
    $message[] = '';
    $message[] = '━━━━━━━━━━━━━━━━━━';
    $message[] = '*TOTAL PEMBAYARAN*';
    $message[] = '*' . checkout_rupiah($total) . '*';
    $message[] = '';
    $message[] = '━━━━━━━━━━━━━━━━━━';
    $message[] = 'Pesanan telah tercatat di Pesan Ajah.';
    $message[] = 'Mohon konfirmasi pesanan ini.';
    $message[] = 'Terima kasih.';
    
    $whatsappUrl = 'https://wa.me/'
        . $sellerPhone
        . '?text='
        . rawurlencode(implode("\n", $message));
    
    checkout_json([
        'success' => true,
        'message' => 'Pesanan berhasil disimpan.',
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'status' => 'draft',
        'subtotal' => $subtotal,
        'delivery_fee' => $deliveryFee,
        'delivery_fee_original' => $deliveryFeeOriginal,
        'delivery_discount' => $deliveryDiscount,
        'delivery_distance_km' => $deliveryDistanceKm,
        'total' => $total,
        'buyer_token' => $buyerToken,
        'whatsapp_url' => $whatsappUrl,
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
        'message' => $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Checkout gagal disimpan. Periksa konfigurasi database.',
    ], $exception instanceof RuntimeException ? 422 : 500);

}