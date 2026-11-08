<?php

declare(strict_types=1);

require_once __DIR__ . '/subscription.php';
require_once __DIR__ . '/web-push.php';
require_once __DIR__ . '/onesignal.php';

/**
 * Mengirim notifikasi pesanan ke seluruh provider.
 *
 * Setiap provider berjalan secara independen.
 * Kegagalan push tidak membatalkan pesanan.
 */
function send_store_order_notifications(
    PDO $db,
    int $storeId,
    string $orderNumber,
    string $buyerName
): void {
    $providers = [
        'Web Push' => static function () use (
            $db,
            $storeId,
            $orderNumber,
            $buyerName
        ): void {
            send_store_order_push(
                $db,
                $storeId,
                $orderNumber,
                $buyerName
            );
        },

        'OneSignal' => static function () use (
            $storeId,
            $orderNumber,
            $buyerName
        ): void {
            send_store_onesignal(
                $storeId,
                $orderNumber,
                $buyerName
            );
        },
    ];

    foreach ($providers as $provider => $send) {
        try {
            $send();
        } catch (Throwable $exception) {
            error_log(
                '[Push Notification][' . $provider . '] '
                . $exception->getMessage()
            );
        }
    }
}
