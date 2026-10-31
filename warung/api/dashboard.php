<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/warung_api.php';

require_method('GET');

$store = api_store_owner();
$storeId = (int) $store['store_id'];

/*
 * Periode statistik:
 * - all   : seluruh pesanan (default)
 * - today : pesanan hari ini
 */
$period = $_GET['period'] ?? 'all';

if (!is_string($period) || !in_array($period, ['all', 'today'], true)) {
    api_json([
        'success' => false,
        'message' => 'Periode statistik tidak valid.',
    ], 422);
}

/*
 * Filter tanggal hanya ditambahkan untuk periode hari ini.
 * Tidak menggunakan DATE(created_at) agar index created_at
 * tetap dapat dimanfaatkan oleh database.
 */
$dateCondition = $period === 'today'
    ? 'AND created_at >= CURRENT_DATE()
       AND created_at < CURRENT_DATE() + INTERVAL 1 DAY'
    : '';

$orderStmt = database()->prepare(
    "SELECT
        COUNT(*) AS total_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN status IN (
                        'pending',
                        'confirmed',
                        'processing'
                    )
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
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS completed_orders,

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

    WHERE store_id = :store_id
        AND status <> 'draft'
        {$dateCondition}"
);

$orderStmt->execute([
    'store_id' => $storeId,
]);

$orderStats = $orderStmt->fetch();

/*
 * Statistik produk tidak bergantung pada periode pesanan.
 */
$productStmt = database()->prepare(
    "SELECT
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

    WHERE store_id = :store_id"
);

$productStmt->execute([
    'store_id' => $storeId,
]);

$productStats = $productStmt->fetch();

/*
 * Riwayat selalu mengambil lima pesanan terbaru,
 * terlepas dari filter statistik.
 */
$recentStmt = database()->prepare(
    "SELECT
        id,
        order_number,
        buyer_name,
        total,
        status,
        delivery_method,
        created_at

    FROM orders

    WHERE store_id = :store_id
        AND status <> 'draft'

    ORDER BY created_at DESC, id DESC

    LIMIT 5"
);

$recentStmt->execute([
    'store_id' => $storeId,
]);

api_json([
    'success' => true,

    'period' => $period,

    'stats' => [
        'total_orders' => (int) $orderStats['total_orders'],
        'pending_orders' => (int) $orderStats['pending_orders'],
        'completed_orders' => (int) $orderStats['completed_orders'],
        'total_products' => (int) $productStats['total_products'],
        'available_products' => (int) $productStats['available_products'],
        'completed_revenue' => (int) $orderStats['completed_revenue'],
        'is_open' => (bool) $store['is_open'],
        'free_delivery' => (bool) $store['free_delivery'],
    ],

    'recent_orders' => $recentStmt->fetchAll(),
]);
