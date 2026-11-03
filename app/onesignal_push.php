<?php

declare(strict_types=1);

/**
 * Mengirim notifikasi pesanan baru melalui OneSignal.
 *
 * Target hanya perangkat yang login sebagai warung terkait.
 * Kegagalan OneSignal tidak boleh membatalkan pesanan.
 */
function send_store_onesignal(
    int $storeId,
    string $orderNumber,
    string $buyerName
): void {
    $appId = (string) env('ONESIGNAL_APP_ID', '');
    $apiKey = (string) env('ONESIGNAL_REST_API_KEY', '');

    if ($appId === '' || $apiKey === '') {
        error_log(
            '[OneSignal] Konfigurasi App ID atau REST API Key belum tersedia.'
        );
        return;
    }

    try {
        $payload = [
            'app_id' => $appId,

            'include_aliases' => [
                'external_id' => [
                    'store_' . $storeId,
                ],
            ],

            'target_channel' => 'push',

            'headings' => [
                'id' => 'Pesanan Baru Masuk!',
            ],

            'contents' => [
                'id' => $buyerName
                    . ' mengirim pesanan '
                    . $orderNumber,
            ],

            'url' => url('warung/pesanan.php'),

            'data' => [
                'order_number' => $orderNumber,
                'store_id' => $storeId,
                'type' => 'new_order',
            ],
        ];

        $jsonPayload = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',

                'header' =>
                    "Content-Type: application/json\r\n"
                    . "Authorization: Key {$apiKey}\r\n",

                'content' => $jsonPayload,
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents(
            'https://api.onesignal.com/notifications',
            false,
            $context
        );

        $statusLine = $http_response_header[0] ?? '';

        preg_match(
            '/\s(\d{3})\s/',
            $statusLine,
            $matches
        );

        $statusCode = (int) ($matches[1] ?? 0);

        if (
            $response === false
            || $statusCode < 200
            || $statusCode >= 300
        ) {
            error_log(
                '[OneSignal] Pengiriman gagal. HTTP '
                . $statusCode
                . ' - '
                . (is_string($response) ? $response : 'Tidak ada respons.')
            );

            return;
        }

        $result = json_decode($response, true);

        if (!is_array($result)) {
            error_log(
                '[OneSignal] Respons API tidak valid. '
                . 'Store ID: '
                . $storeId
                . ' | Response: '
                . $response
            );

            return;
        }

        $notificationId = $result['id'] ?? null;
        $recipients = isset($result['recipients'])
            ? (int) $result['recipients']
            : null;

        if ($recipients === 0) {
            error_log(
                '[OneSignal] Tidak ada perangkat penerima. '
                . 'Store ID: '
                . $storeId
                . ' | Order: '
                . $orderNumber
                . ' | Notification ID: '
                . ($notificationId ?? 'N/A')
                . ' | Response: '
                . $response
            );

            return;
        }

        error_log(
            '[OneSignal] Permintaan notifikasi diterima API. '
            . 'Store ID: '
            . $storeId
            . ' | Order: '
            . $orderNumber
            . ' | Recipients: '
            . ($recipients ?? 'Tidak tersedia')
            . ' | Notification ID: '
            . ($notificationId ?? 'N/A')
        );

    } catch (Throwable $exception) {
        error_log(
            '[OneSignal] Error: '
            . $exception->getMessage()
        );
    }
}
