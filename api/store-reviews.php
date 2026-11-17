<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_method('POST');

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

$token = $input['token'] ?? '';
$rating = filter_var(
    $input['rating'] ?? null,
    FILTER_VALIDATE_INT
);

$comment = trim(
    is_string($input['comment'] ?? null)
        ? $input['comment']
        : ''
);

if (
    !is_string($token)
    || !preg_match('/^[a-f0-9]{64}$/i', $token)
) {
    store_reviews_json([
        'success' => false,
        'message' => 'Token pesanan tidak valid.',
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
        'message' => 'Komentar maksimal 1000 karakter.',
    ], 422);
}

$db = database();

try {
    $db->beginTransaction();

    /*
     * Token pembeli harus cocok dengan pesanan.
     * Review hanya boleh dibuat untuk pesanan selesai.
     */
    $orderStmt = $db->prepare(
        "SELECT
            id,
            store_id,
            buyer_id,
            status
         FROM orders
         WHERE buyer_token_hash = :token_hash
         LIMIT 1
         FOR UPDATE"
    );

    $orderStmt->execute([
        'token_hash' => hash('sha256', $token),
    ]);

    $order = $orderStmt->fetch();

    if (!$order) {
        $db->rollBack();

        store_reviews_json([
            'success' => false,
            'message' => 'Pesanan tidak ditemukan.',
        ], 404);
    }

    if ($order['status'] !== 'completed') {
        $db->rollBack();

        store_reviews_json([
            'success' => false,
            'message' => 'Review hanya dapat diberikan untuk pesanan yang selesai.',
        ], 422);
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
        'buyer_id' => $order['buyer_id'] !== null
            ? (int) $order['buyer_id']
            : null,
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

    // Duplicate order_id berarti pesanan sudah pernah direview.
    if ($exception->getCode() === '23000') {
        store_reviews_json([
            'success' => false,
            'message' => 'Pesanan ini sudah memiliki review.',
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