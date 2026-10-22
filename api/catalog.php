<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_method('GET');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

try {
    $db = database();

    /*
     * Ambil seluruh warung aktif yang dimiliki
     * akun warung aktif.
     */
    $storeStmt = $db->query(
        "SELECT
            s.id,
            s.name,
            s.slug,
            s.phone,
            s.is_open,
            s.free_delivery
            s.delivery_discount

        FROM stores s

        INNER JOIN users u
            ON u.id = s.user_id

        WHERE s.is_active = 1
          AND u.is_active = 1
          AND u.role = 'warung'

        ORDER BY s.name ASC"
    );

    $stores = $storeStmt->fetchAll();

    /*
     * Jika parameter store_id disediakan,
     * tampilkan produk warung tersebut saja.
     *
     * Tanpa store_id, tampilkan katalog marketplace.
     */
    $storeId = null;

    if (isset($_GET['store_id'])) {
        $validatedStoreId = filter_var(
            $_GET['store_id'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($validatedStoreId === false) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'ID warung tidak valid.',
            ]);

            exit;
        }

        $storeId = (int) $validatedStoreId;
    }

    $sql = "
        SELECT
            p.id,
            p.store_id,
            p.name,
            p.variant,
            p.description,
            p.category,
            p.price,
            p.unit,
            p.image,
            p.tag,
            p.is_available,

            s.name AS store_name,
            s.slug AS store_slug,
            s.owner_name AS store_owner,
            s.icon_image AS store_icon_image,
            s.phone AS store_phone,
            s.is_open AS store_is_open,
            s.free_delivery AS store_free_delivery,
            s.delivery_discount AS store_delivery_discount,
            s.latitude AS store_latitude,
            s.longitude AS store_longitude

        FROM products p

        INNER JOIN stores s
            ON s.id = p.store_id

        INNER JOIN users u
            ON u.id = s.user_id

        WHERE s.is_active = 1
          AND u.is_active = 1
          AND u.role = 'warung'

          AND p.is_available = 1
    ";

    $params = [];

    if ($storeId !== null) {
        $sql .= ' AND s.id = :store_id';

        $params['store_id'] = $storeId;
    }

    $sql .= '
        ORDER BY
            s.name ASC,
            p.name ASC
    ';

    $productStmt = $db->prepare($sql);
    $productStmt->execute($params);

    $products = $productStmt->fetchAll();

    /*
     * Normalisasi tipe data agar JavaScript
     * tidak salah membaca angka sebagai string.
     */
    foreach ($products as &$product) {
        $product['id'] = (int) $product['id'];
        $product['store_id'] = (int) $product['store_id'];

        $product['price'] = (float) $product['price'];

        $product['is_available'] =
            (bool) $product['is_available'];

        $product['store_is_open'] =
            (bool) $product['store_is_open'];

        $product['store_free_delivery'] =
            (bool) $product['store_free_delivery'];
        
        $product['store_delivery_discount'] = max(
            0,
            (int) ($product['store_delivery_discount'] ?? 0)
        );

        /*
         * Properti yang digunakan oleh frontend.
         */
        $product['store'] = $product['store_name'];

        $product['available'] =
            $product['is_available']
            && $product['store_is_open'];

        /*
         * Foto produk berasal dari kolom products.image.
         * Properti image sudah tersedia pada hasil SELECT.
         */
        $product['image'] = is_string($product['image'] ?? null)
            && $product['image'] !== ''
                ? $product['image']
                : null;
        
        /*
         * Pertahankan properti lama sementara agar
         * kode frontend lain tidak langsung rusak.
         */
        $product['emoji'] = '';

        $product['tag'] = is_string($product['tag'] ?? null)
            ? trim($product['tag'])
            : '';
            }

    unset($product);

    foreach ($stores as &$store) {
        $store['id'] = (int) $store['id'];
        $store['is_open'] = (bool) $store['is_open'];

        $store['free_delivery'] =
            (bool) $store['free_delivery'];
        $store['delivery_discount'] = max(
            0,
            (int) ($store['delivery_discount'] ?? 0)
        );
    }

    unset($store);

    echo json_encode([
        'success' => true,
        'stores' => $stores,
        'products' => $products,
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $exception) {
    error_log(
        '[Pesan Ajah] Gagal memuat katalog: '
        . $exception->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Katalog belum dapat dimuat.',
    ], JSON_UNESCAPED_UNICODE);
}