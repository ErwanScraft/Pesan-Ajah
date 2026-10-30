<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/warung_api.php';

$store = api_store_owner();
$storeId = (int) $store['store_id'];

if (request_method() === 'GET') {
    $publicKey = (string) env('VAPID_PUBLIC_KEY', '');

    if ($publicKey === '') {
        api_json([
            'success' => false,
            'message' => 'Konfigurasi notifikasi belum tersedia.',
        ], 503);
    }

    api_json([
        'success' => true,
        'public_key' => $publicKey,
    ]);
}

api_verify_csrf();

if (request_method() !== 'POST') {
    header('Allow: GET, POST');

    api_json([
        'success' => false,
        'message' => 'Metode permintaan tidak diizinkan.',
    ], 405);
}

$input = api_json_input();
$action = $input['action'] ?? 'subscribe';

$endpoint = $input['endpoint'] ?? null;

if (
    !is_string($endpoint)
    || strlen($endpoint) > 2048
    || !filter_var($endpoint, FILTER_VALIDATE_URL)
    || !str_starts_with($endpoint, 'https://')
) {
    api_json([
        'success' => false,
        'message' => 'Endpoint subscription tidak valid.',
    ], 422);
}

$endpointHash = hash('sha256', $endpoint);

if ($action === 'unsubscribe') {
    $stmt = database()->prepare(
        'DELETE FROM push_subscriptions
         WHERE store_id = :store_id
           AND endpoint_hash = :endpoint_hash'
    );

    $stmt->execute([
        'store_id' => $storeId,
        'endpoint_hash' => $endpointHash,
    ]);

    api_json([
        'success' => true,
        'message' => 'Notifikasi perangkat dinonaktifkan.',
    ]);
}

if ($action !== 'subscribe') {
    api_json([
        'success' => false,
        'message' => 'Aksi tidak valid.',
    ], 422);
}

$keys = $input['keys'] ?? null;

if (!is_array($keys)) {
    api_json([
        'success' => false,
        'message' => 'Data kunci subscription tidak valid.',
    ], 422);
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
    api_json([
        'success' => false,
        'message' => 'Kunci subscription tidak valid.',
    ], 422);
}

$contentEncoding = $input['contentEncoding'] ?? 'aes128gcm';

if (
    !is_string($contentEncoding)
    || !in_array($contentEncoding, ['aes128gcm'], true)
) {
    api_json([
        'success' => false,
        'message' => 'Format enkripsi tidak didukung.',
    ], 422);
}

$stmt = database()->prepare(
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
    'endpoint_hash' => $endpointHash,
    'p256dh' => $p256dh,
    'auth' => $auth,
    'content_encoding' => $contentEncoding,
]);

api_json([
    'success' => true,
    'message' => 'Perangkat berhasil didaftarkan.',
]);