<?php

declare(strict_types=1);

/**
 * Mengambil public key VAPID.
 */
function push_notification_public_key(): string
{
    return (string) env('VAPID_PUBLIC_KEY', '');
}

/**
 * Menyimpan subscription perangkat.
 */
function push_notification_save_subscription(
    PDO $db,
    int $storeId,
    array $input
): void {
    $endpoint = $input['endpoint'] ?? null;
    $keys = $input['keys'] ?? null;

    if (
        !is_string($endpoint)
        || strlen($endpoint) > 2048
        || !filter_var($endpoint, FILTER_VALIDATE_URL)
        || !str_starts_with($endpoint, 'https://')
    ) {
        throw new InvalidArgumentException(
            'Endpoint subscription tidak valid.'
        );
    }

    if (!is_array($keys)) {
        throw new InvalidArgumentException(
            'Data kunci subscription tidak valid.'
        );
    }

    $p256dh = $keys['p256dh'] ?? null;
    $auth = $keys['auth'] ?? null;

    if (
        !is_string($p256dh)
        || !is_string($auth)
        || $p256dh === ''
        || $auth === ''
        || strlen($p256dh) > 255
        || strlen($auth) > 255
    ) {
        throw new InvalidArgumentException(
            'Kunci subscription tidak valid.'
        );
    }

    $contentEncoding = $input['contentEncoding']
        ?? 'aes128gcm';

    if (
        !is_string($contentEncoding)
        || !in_array($contentEncoding, ['aes128gcm'], true)
    ) {
        throw new InvalidArgumentException(
            'Format enkripsi tidak didukung.'
        );
    }

    $stmt = $db->prepare(
        'INSERT INTO push_subscriptions (
            store_id,
            endpoint,
            endpoint_hash,
            p256dh,
            auth,
            content_encoding
        ) VALUES (
            :store_id,
            :endpoint,
            :endpoint_hash,
            :p256dh,
            :auth,
            :content_encoding
        )
        ON DUPLICATE KEY UPDATE
            store_id = VALUES(store_id),
            endpoint = VALUES(endpoint),
            p256dh = VALUES(p256dh),
            auth = VALUES(auth),
            content_encoding = VALUES(content_encoding),
            updated_at = CURRENT_TIMESTAMP'
    );

    $stmt->execute([
        'store_id' => $storeId,
        'endpoint' => $endpoint,
        'endpoint_hash' => hash('sha256', $endpoint),
        'p256dh' => $p256dh,
        'auth' => $auth,
        'content_encoding' => $contentEncoding,
    ]);
}

/**
 * Menghapus subscription perangkat.
 */
function push_notification_delete_subscription(
    PDO $db,
    int $storeId,
    string $endpoint
): void {
    if (
        strlen($endpoint) > 2048
        || !filter_var($endpoint, FILTER_VALIDATE_URL)
        || !str_starts_with($endpoint, 'https://')
    ) {
        throw new InvalidArgumentException(
            'Endpoint subscription tidak valid.'
        );
    }

    $stmt = $db->prepare(
        'DELETE FROM push_subscriptions
         WHERE store_id = :store_id
           AND endpoint_hash = :endpoint_hash'
    );

    $stmt->execute([
        'store_id' => $storeId,
        'endpoint_hash' => hash('sha256', $endpoint),
    ]);
}
