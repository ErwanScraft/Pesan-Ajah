<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/warung_api.php';

require_method('GET');

$store = api_store_owner();

$storeId = (int) $store['store_id'];

/*
 * Total pesanan dan pesanan yang masih memerlukan tindakan.
 */
$orderStmt = database()->prepare(
    "SELECT
        COUNT(*) AS total_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN status IN ('pending', 'confirmed', 'processing')
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS pending_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'completed'
                    THEN total
                    ELSE 0
                END
            ),
            0
        ) AS completed_revenue

    FROM orders

    WHERE store_id = :store_id"
);

$orderStmt->execute([
    'store_id' => $storeId,
]);

$orderStats = $orderStmt->fetch();

/*
 * Total produk dan produk tersedia.
 */
$productStmt = database()->prepare(
    'SELECT
        COUNT(*) AS total_products,

        COALESCE(
            SUM(
                CASE
                    WHEN is_available = 1
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS available_products

    FROM products

    WHERE store_id = :store_id'
);

$productStmt->execute([
    'store_id' => $storeId,
]);

$productStats = $productStmt->fetch();

/*
 * Pesanan terbaru.
 */
$recentStmt = database()->prepare(
    'SELECT
        id,
        order_number,
        buyer_name,
        total,
        status,
        created_at

    FROM orders

    WHERE store_id = :store_id

    ORDER BY created_at DESC, id DESC

    LIMIT 5'
);

$recentStmt->execute([
    'store_id' => $storeId,
]);

api_json([
    'success' => true,

    'stats' => [
        'total_orders' => (int) $orderStats['total_orders'],
        'pending_orders' => (int) $orderStats['pending_orders'],
        'total_products' => (int) $productStats['total_products'],
        'available_products' => (int) $productStats['available_products'],
        'completed_revenue' => (float) $orderStats['completed_revenue'],
        'is_open' => (bool) $store['is_open'],
    ],

    'recent_orders' => $recentStmt->fetchAll(),
]);