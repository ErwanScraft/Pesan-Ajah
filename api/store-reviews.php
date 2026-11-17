<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_method('GET', 'POST');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function store_reviews_json(
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
| Identitas perangkat pembeli
|--------------------------------------------------------------------------
*/

$deviceToken = $_COOKIE['pa_buyer_device'] ?? '';

if (
    !is_string($deviceToken)
    || !preg_match('/^[a-f0-9]{64}$/i', $deviceToken)
) {
    store_reviews_json([
        'success' => false,
        'message' => 'Identitas pembeli tidak ditemukan. Gunakan perangkat yang digunakan saat checkout.',
    ], 401);
}

$db = database();

$deviceTokenHash = hash('sha256', $deviceToken);

/*
|--------------------------------------------------------------------------
| Ambil pesanan yang bisa direview
|--------------------------------------------------------------------------
*/

if (request_method() === 'GET') {
    $storeId = filter_var(
        $_GET['store_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($storeId === false) {
        store_reviews_json([
            'success' => false,
            'message' => 'Warung tidak valid.',
        ], 422);
    }

    try {
        $stmt = $db->prepare(
            "SELECT
                o.id,
                o.order_number,
                o.created_at

             FROM buyer_devices bd

             INNER JOIN orders o
                ON o.buyer_id = bd.buyer_id

             WHERE bd.token_hash = :token_hash
               AND o.store_id = :store_id
               AND o.status = 'completed'

               AND NOT EXISTS (
                    SELECT 1
                    FROM store_reviews sr
                    WHERE sr.order_id = o.id
               )

             ORDER BY o.created_at DESC, o.id DESC"
        );

        $stmt->execute([
            'token_hash' => $deviceTokenHash,
            'store_id' => $storeId,
        ]);

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        store_reviews_json([
            'success' => true,
            'orders' => array_map(
                static fn (array $order): array => [
                    'id' => (int) $order['id'],
                    'order_number' => (string) $order['order_number'],
                    'created_at' => (string) $order['created_at'],
                ],
                $orders
            ),
        ]);
    } catch (Throwable $exception) {
        error_log(
            '[Pesan Ajah] Gagal memuat pesanan review: '
            . $exception->getMessage()
        );

        store_reviews_json([
            'success' => false,
            'message' => 'Daftar pesanan tidak dapat dimuat.',
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| Validasi data review
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
    store_reviews_json([
        'success' => false,
        'message' => 'Format permintaan tidak valid.',
    ], 400);
}

if (!is_array($input)) {
    store_reviews_json([
        'success' => false,
        'message' => 'Data review tidak valid.',
    ], 400);
}

$orderId = filter_var(
    $input['order_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$storeId = filter_var(
    $input['store_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$rating = filter_var(
    $input['rating'] ?? null,
    FILTER_VALIDATE_INT
);

$comment = trim(
    is_string($input['comment'] ?? null)
        ? $input['comment']
        : ''
);

if ($orderId === false || $storeId === false) {
    store_reviews_json([
        'success' => false,
        'message' => 'Pesanan tidak valid.',
    ], 422);
}

if (
    $rating === false
    || $rating < 1
    || $rating > 5
) {
    store_reviews_json([
        'success' => false,
        'message' => 'Pilih rating antara 1 sampai 5 bintang.',
    ], 422);
}

if (mb_strlen($comment, 'UTF-8') > 1000) {
    store_reviews_json([
        'success' => false,
        'message' => 'Review maksimal 1000 karakter.',
    ], 422);
}

/*
|--------------------------------------------------------------------------
| Simpan review
|--------------------------------------------------------------------------
*/

try {
    $db->beginTransaction();

    /*
     * Pesanan harus benar-benar milik perangkat pembeli,
     * berasal dari warung yang sedang dibuka, dan sudah selesai.
     */
    $orderStmt = $db->prepare(
        "SELECT
            o.id,
            o.store_id,
            o.buyer_id,
            o.status

         FROM orders o

         INNER JOIN buyer_devices bd
            ON bd.buyer_id = o.buyer_id

         WHERE o.id = :order_id
           AND o.store_id = :store_id
           AND bd.token_hash = :token_hash
           AND o.status = 'completed'

         LIMIT 1
         FOR UPDATE"
    );

    $orderStmt->execute([
        'order_id' => $orderId,
        'store_id' => $storeId,
        'token_hash' => $deviceTokenHash,
    ]);

    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $db->rollBack();

        store_reviews_json([
            'success' => false,
            'message' => 'Pesanan tidak ditemukan atau belum selesai.',
        ], 404);
    }

    $insert = $db->prepare(
        'INSERT INTO store_reviews (
            store_id,
            order_id,
            buyer_id,
            rating,
            comment
        ) VALUES (
            :store_id,
            :order_id,
            :buyer_id,
            :rating,
            :comment
        )'
    );

    $insert->execute([
        'store_id' => (int) $order['store_id'],
        'order_id' => (int) $order['id'],
        'buyer_id' => (int) $order['buyer_id'],
        'rating' => $rating,
        'comment' => $comment !== '' ? $comment : null,
    ]);

    $db->commit();

    store_reviews_json([
        'success' => true,
        'message' => 'Review berhasil dikirim.',
    ]);
} catch (PDOException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    if ($exception->getCode() === '23000') {
        store_reviews_json([
            'success' => false,
            'message' => 'Pesanan ini sudah pernah direview.',
        ], 409);
    }

    error_log(
        '[Pesan Ajah] Gagal menyimpan review: '
        . $exception->getMessage()
    );

    store_reviews_json([
        'success' => false,
        'message' => 'Review gagal disimpan. Silakan coba lagi.',
    ], 500);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log(
        '[Pesan Ajah] Error review: '
        . $exception->getMessage()
    );

    store_reviews_json([
        'success' => false,
        'message' => 'Terjadi kesalahan saat menyimpan review.',
    ], 500);
}
