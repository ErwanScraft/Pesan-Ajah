<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * Memeriksa apakah layanan Delivery aktif secara global.
 */
function delivery_is_available(?PDO $db = null): bool
{
    $db ??= database();

    $stmt = $db->query(
        "SELECT
            ds.is_enabled,
            EXISTS (
                SELECT 1
                FROM drivers d
                WHERE d.is_active = 1
                  AND d.is_online = 1
            ) AS has_online_driver
         FROM delivery_settings ds
         WHERE ds.id = 1
         LIMIT 1"
    );

    $settings = $stmt->fetch();

    return $settings !== false
        && (bool) $settings['is_enabled']
        && (bool) $settings['has_online_driver'];
}
