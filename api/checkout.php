<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_method('POST');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

function checkout_json(
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

/*
|--------------------------------------------------------------------------
| NORMALISASI NOMOR WHATSAPP
|--------------------------------------------------------------------------
*/

function checkout_whatsapp_number(
    mixed $phone
): ?string {
    if (!is_string($phone)) {
        return null;
    }

    $number = preg_replace('/\D+/', '', $phone);

    if (!is_string($number) || $number === '') {
        return null;
    }

    if (str_starts_with($number, '0')) {
        $number = '62' . substr($number, 1);
    } elseif (str_starts_with($number, '8')) {
        $number = '62' . $number;
    }

    if (
        !str_starts_with($number, '62')
        || strlen($number) < 10
        || strlen($number) > 16
    ) {
        return null;
    }

    return $number;
}

/*
|--------------------------------------------------------------------------
| FORMAT RUPIAH
|--------------------------------------------------------------------------
*/

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
| BACA JSON
|--------------------------------------------------------------------------
*/

$rawInput = file_get_contents('php://input');

try {
    $input = json_decode(
        $rawInput ?: '',
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException) {
    checkout_json([
        'success' => false,
        'message' => 'Format data checkout tidak valid.',
    ], 400);
}

if (!is_array($input)) {
    checkout_json([
        'success' => false,
        'message' => 'Data checkout tidak valid.',
    ], 400);
}

/*
|--------------------------------------------------------------------------
| VALIDASI CSRF
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| DATA PEMBELI
|--------------------------------------------------------------------------
*/

$storeId = filter_var(
    $input['store_id'] ?? null,
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => 1,
        ],
    ]
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

$deliveryMapsUrl = trim(
    is_string($input['delivery_maps_url'] ?? null)
        ? $input['delivery_maps_url']
        : ''
);

$latitudeInput = $input['delivery_latitude'] ?? null;
$longitudeInput = $input['delivery_longitude'] ?? null;

$deliveryLatitude = null;
$deliveryLongitude = null;

$items = $input['items'] ?? [];

/*
|--------------------------------------------------------------------------
| VALIDASI NOMOR TELEPON
|--------------------------------------------------------------------------
|
| Nomor WhatsApp dinormalisasi untuk profil buyer.
| Nomor asli tetap disimpan pada orders agar informasi
| transaksi sesuai dengan input pembeli.
|--------------------------------------------------------------------------
*/

$buyerPhoneNormalized = checkout_whatsapp_number(
    $buyerPhone
);

/*
|--------------------------------------------------------------------------
| VALIDASI GOOGLE MAPS
|--------------------------------------------------------------------------
*/

if ($deliveryMapsUrl !== '') {
    if (mb_strlen($deliveryMapsUrl, 'UTF-8') > 500) {
        checkout_json([
            'success' => false,
            'message' => 'Tautan Google Maps terlalu panjang.',
        ], 422);
    }

    $parts = parse_url($deliveryMapsUrl);

    $host = strtolower(
        is_array($parts)
            ? (string) ($parts['host'] ?? '')
            : ''
    );

    $allowedHosts = [
        'google.com',
        'www.google.com',
        'maps.google.com',
        'maps.app.goo.gl',
        'goo.gl',
    ];

    $validMapsHost = in_array(
        $host,
        $allowedHosts,
        true
    ) || str_ends_with($host, '.google.com');

    if (
        !filter_var(
            $deliveryMapsUrl,
            FILTER_VALIDATE_URL
        )
        || strtolower(
            (string) ($parts['scheme'] ?? '')
        ) !== 'https'
        || !$validMapsHost
    ) {
        checkout_json([
            'success' => false,
            'message' => 'Tautan lokasi harus berupa tautan Google Maps yang valid.',
        ], 422);
    }
}

/*
|--------------------------------------------------------------------------
| VALIDASI KOORDINAT
|--------------------------------------------------------------------------
*/

if (
    $latitudeInput !== null
    && $latitudeInput !== ''
) {
    if (!is_numeric($latitudeInput)) {
        checkout_json([
            'success' => false,
            'message' => 'Koordinat lintang tidak valid.',
        ], 422);
    }

    $deliveryLatitude = (float) $latitudeInput;

    if (
        !is_finite($deliveryLatitude)
        || $deliveryLatitude < -90
        || $deliveryLatitude > 90
    ) {
        checkout_json([
            'success' => false,
            'message' => 'Koordinat lintang berada di luar rentang yang valid.',
        ], 422);
    }
}

if (
    $longitudeInput !== null
    && $longitudeInput !== ''
) {
    if (!is_numeric($longitudeInput)) {
        checkout_json([
            'success' => false,
            'message' => 'Koordinat bujur tidak valid.',
        ], 422);
    }

    $deliveryLongitude = (float) $longitudeInput;

    if (
        !is_finite($deliveryLongitude)
        || $deliveryLongitude < -180
        || $deliveryLongitude > 180
    ) {
        checkout_json([
            'success' => false,
            'message' => 'Koordinat bujur berada di luar rentang yang valid.',
        ], 422);
    }
}

if (
    ($deliveryLatitude === null)
    !== ($deliveryLongitude === null)
) {
    checkout_json([
        'success' => false,
        'message' => 'Koordinat lintang dan bujur harus diisi bersama.',
    ], 422);
}

/*
|--------------------------------------------------------------------------
| VALIDASI METODE PESANAN
|--------------------------------------------------------------------------
*/

if (
    $deliveryMethod === 'pickup'
    && (
        $deliveryMapsUrl !== ''
        || $deliveryLatitude !== null
        || $deliveryLongitude !== null
    )
) {
    checkout_json([
        'success' => false,
        'message' => 'Lokasi pengantaran tidak diperlukan untuk metode ambil sendiri.',
    ], 422);
}

if (
    $storeId === false
    || $buyerName === ''
    || mb_strlen($buyerName, 'UTF-8') > 100
    || $buyerPhone === ''
    || mb_strlen($buyerPhone, 'UTF-8') > 25
    || $buyerPhoneNormalized === null
    || !in_array(
        $deliveryMethod,
        ['delivery', 'pickup'],
        true
    )
    || !is_array($items)
    || count($items) < 1
    || count($items) > 50
) {
    checkout_json([
        'success' => false,
        'message' => 'Periksa kembali nama, nomor WhatsApp, metode pesanan, dan produk.',
    ], 422);
}

if (
    $deliveryMethod === 'delivery'
    && (
        $deliveryAddress === ''
        || mb_strlen($deliveryAddress, 'UTF-8') > 255
    )
) {
    checkout_json([
        'success' => false,
        'message' => 'Alamat pengantaran wajib diisi dan maksimal 255 karakter.',
    ], 422);
}

/*
|--------------------------------------------------------------------------
| NORMALISASI KERANJANG
|--------------------------------------------------------------------------
|
| Browser hanya mengirim product_id dan quantity.
| Harga dari browser tidak dipercaya.
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
        [
            'options' => [
                'min_range' => 1,
            ],
        ]
    );

    $quantity = filter_var(
        $item['quantity'] ?? null,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
                'max_range' => 99,
            ],
        ]
    );

    if (
        $productId === false
        || $quantity === false
    ) {
        checkout_json([
            'success' => false,
            'message' => 'ID atau jumlah produk tidak valid.',
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

if ($quantities === []) {
    checkout_json([
        'success' => false,
        'message' => 'Keranjang masih kosong.',
    ], 422);
}

/*
|--------------------------------------------------------------------------
| DATABASE TRANSACTION
|--------------------------------------------------------------------------
*/

$db = database();

try {
    $db->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | VALIDASI WARUNG
    |--------------------------------------------------------------------------
    */

    $storeStmt = $db->prepare(
        "SELECT
            s.id,
            s.name,
            s.slug,
            s.phone,
            s.address,
            s.is_open,
            s.free_delivery,
            s.is_active,
            u.is_active AS user_is_active

        FROM stores s

        INNER JOIN users u
            ON u.id = s.user_id

        WHERE s.id = :store_id
          AND u.role = 'warung'

        LIMIT 1

        FOR UPDATE"
    );

    $storeStmt->execute([
        'store_id' => (int) $storeId,
    ]);

    $store = $storeStmt->fetch();

    if (
        !$store
        || !(bool) $store['is_active']
        || !(bool) $store['user_is_active']
    ) {
        $db->rollBack();

        checkout_json([
            'success' => false,
            'message' => 'Warung tidak tersedia.',
        ], 404);
    }

    if (!(bool) $store['is_open']) {
        $db->rollBack();

        checkout_json([
            'success' => false,
            'message' => 'Warung sedang tutup dan tidak menerima pesanan.',
        ], 409);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI WHATSAPP SELLER
    |--------------------------------------------------------------------------
    */

    $sellerPhone = checkout_whatsapp_number(
        $store['phone'] ?? null
    );

    if ($sellerPhone === null) {
        $db->rollBack();

        checkout_json([
            'success' => false,
            'message' => 'Nomor WhatsApp warung belum valid. Hubungi admin warung.',
        ], 422);
    }

    /*
    |--------------------------------------------------------------------------
    | SIMPAN / PERBARUI PROFIL BUYER
    |--------------------------------------------------------------------------
    |
    | Tabel buyers harus sudah dibuat.
    | Nomor telepon yang dinormalisasi menjadi identitas profil.
    |
    | Pembelian dengan nomor yang sama memperbarui profil yang sama.
    |--------------------------------------------------------------------------
    */

    $buyerAddress = $deliveryMethod === 'delivery'
        ? $deliveryAddress
        : null;

    $buyerMapsUrl = (
        $deliveryMethod === 'delivery'
        && $deliveryMapsUrl !== ''
    )
        ? $deliveryMapsUrl
        : null;

    $buyerLatitude = $deliveryMethod === 'delivery'
        ? $deliveryLatitude
        : null;

    $buyerLongitude = $deliveryMethod === 'delivery'
        ? $deliveryLongitude
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
            :delivery_address,
            :delivery_maps_url,
            :delivery_latitude,
            :delivery_longitude
        )
        ON DUPLICATE KEY UPDATE
            id = LAST_INSERT_ID(id),
            name = VALUES(name),
            delivery_address = COALESCE(
                VALUES(delivery_address),
                delivery_address
            ),
            delivery_maps_url = IF(
                VALUES(delivery_address) IS NOT NULL,
                VALUES(delivery_maps_url),
                delivery_maps_url
            ),
            delivery_latitude = IF(
                VALUES(delivery_address) IS NOT NULL,
                VALUES(delivery_latitude),
                delivery_latitude
            ),
            delivery_longitude = IF(
                VALUES(delivery_address) IS NOT NULL,
                VALUES(delivery_longitude),
                delivery_longitude
            )"
    );

    $buyerStmt->execute([
        'name' => $buyerName,
        'phone' => $buyerPhoneNormalized,
        'delivery_address' => $buyerAddress,
        'delivery_maps_url' => $buyerMapsUrl,
        'delivery_latitude' => $buyerLatitude,
        'delivery_longitude' => $buyerLongitude,
    ]);

    $buyerId = (int) $db->lastInsertId();

    if ($buyerId < 1) {
        throw new RuntimeException(
            'Profil pembeli gagal disimpan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI DAN AMBIL PRODUK
    |--------------------------------------------------------------------------
    */

    $productStmt = $db->prepare(
        'SELECT
            id,
            store_id,
            name,
            price,
            unit,
            is_available

        FROM products

        WHERE id = :id
          AND store_id = :store_id

        LIMIT 1

        FOR UPDATE'
    );

    $orderItems = [];
    $subtotal = 0.0;

    foreach ($quantities as $productId => $quantity) {
        $productStmt->execute([
            'id' => $productId,
            'store_id' => (int) $storeId,
        ]);

        $product = $productStmt->fetch();

        if (
            !$product
            || !(bool) $product['is_available']
        ) {
            $db->rollBack();

            checkout_json([
                'success' => false,
                'message' => 'Salah satu produk sudah tidak tersedia. Periksa keranjang kembali.',
            ], 409);
        }

        $price = (float) $product['price'];

        $lineSubtotal = round(
            $price * $quantity,
            2
        );

        $subtotal = round(
            $subtotal + $lineSubtotal,
            2
        );

        $orderItems[] = [
            'product_id' => (int) $product['id'],
            'product_name' => (string) $product['name'],
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $lineSubtotal,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | HITUNG ONGKIR DAN TOTAL
    |--------------------------------------------------------------------------
    |
    | Delivery normal       : Rp1.000
    | Delivery gratis       : Rp0
    | Ambil sendiri         : Rp0
    |--------------------------------------------------------------------------
    */

    $deliveryFee = 0.0;

    if ($deliveryMethod === 'delivery') {
        $deliveryFee = (bool) $store['free_delivery']
            ? 0.0
            : 1000.0;
    }

    $total = round(
        $subtotal + $deliveryFee,
        2
    );

    /*
    |--------------------------------------------------------------------------
    | GENERATE IDENTITAS PESANAN
    |--------------------------------------------------------------------------
    */

    $orderNumber = 'PA'
        . date('ymd')
        . strtoupper(bin2hex(random_bytes(4)));

    /*
     * Token mentah diberikan kepada pembeli.
     * Database hanya menyimpan hash token.
     */
    $buyerTrackingToken = bin2hex(
        random_bytes(32)
    );

    $buyerTrackingTokenHash = hash(
        'sha256',
        $buyerTrackingToken
    );

    /*
    |--------------------------------------------------------------------------
    | SIMPAN PESANAN
    |--------------------------------------------------------------------------
    |
    | buyer_id menghubungkan pesanan dengan profil buyers.
    |--------------------------------------------------------------------------
    */

    $insertOrder = $db->prepare(
        "INSERT INTO orders (
            store_id,
            buyer_id,
            order_number,
            buyer_name,
            buyer_phone,
            delivery_method,
            delivery_address,
            delivery_fee,
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
            :subtotal,
            :total,
            'pending',
            :buyer_token_hash,
            :delivery_maps_url,
            :delivery_latitude,
            :delivery_longitude
        )"
    );

    $insertOrder->execute([
        'store_id' => (int) $storeId,
        'buyer_id' => $buyerId,

        'order_number' => $orderNumber,
        'buyer_name' => $buyerName,
        'buyer_phone' => $buyerPhone,

        'delivery_method' => $deliveryMethod,

        'delivery_address' => (
            $deliveryMethod === 'delivery'
       