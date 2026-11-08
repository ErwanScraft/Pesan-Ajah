<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/warung_api.php';

require_once APP_PATH . '/modules/push-notifications/main.php';

$store = api_store_owner();
$storeId = (int) $store['store_id'];

if (request_method() === 'GET') {
    $publicKey = push_notification_public_key();

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

try {
    if ($action === 'unsubscribe') {
        $endpoint = $input['endpoint'] ?? null;

        if (!is_string($endpoint)) {
            throw new InvalidArgumentException(
                'Endpoint subscription tidak valid.'
            );
        }

        push_notification_delete_subscription(
            database(),
            $storeId,
            $endpoint
        );

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

    push_notification_save_subscription(
        database(),
        $storeId,
        $input
    );

    api_json([
        'success' => true,
        'message' => 'Perangkat berhasil didaftarkan.',
    ]);

} catch (InvalidArgumentException $exception) {
    api_json([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 422);

} catch (Throwable $exception) {
    error_log(
        '[Push Subscription] '
        . $exception->getMessage()
    );

    api_json([
        'success' => false,
        'message' => 'Subscription gagal diproses.',
    ], 500);
}
