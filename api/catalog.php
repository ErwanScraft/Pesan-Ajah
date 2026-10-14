<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_method('GET');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$storeId = filter_input(
    INPUT_GET,
    'store_id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($storeId === false || $storeId === null) {
    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'ID warung tidak valid.',
    ]);

    exit;
}

$storeStmt = database()->prepare(
    'SELECT
        s.id,
        s.name,
        s.slug,
        s.is_open,
        s.free_delivery

    FROM stores s

    INNER JOIN users u
        ON u.id = s.user_id

    WHERE s.id = :store_id
      AND s.is_active = 1
      AND u.is_active = 1
      AND u.role = :role

    LIMIT 1'
);

$storeStmt->execute([
    'store_id' => $storeId,
    'role' => 'warung',
]);

$store = $storeStmt->fetch();

if (!$store) {
    http_response_code(404);

    echo json_encode([
        'success' => false,
        'message' => 'Warung tidak ditemukan.',
    ]);

    exit;
}

$productStmt = database()->prepare(
    'SELECT
        id,
        name,
        description,
        category,
        price,
        unit,
        image,
        is_available

    FROM products

    WHERE store_id = :store_id
      AND is_available = 1

    ORDER BY name ASC'
);

$productStmt->execute([
    'store_id' => $storeId,
]);

echo json_encode([
    'success' => true,
    'store' => $store,
    'products' => $productStmt->fetchAll(),
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);