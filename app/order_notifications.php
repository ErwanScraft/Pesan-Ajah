<?php

declare(strict_types=1);

require_once __DIR__ . '/web_push.php';
require_once __DIR__ . '/onesignal_push.php';

/**
 * Mengirim notifikasi pesanan baru melalui seluruh kanal.
 */
function send_store_order_notifications(
    PDO $db,
    int $storeId,
    string $orderNumber,
    string $buyerName
): void {
    // Web Push yang sudah digunakan Pesan Ajah.
    try {
        send_store_order_push(
            $db,
            $storeId,
            $orderNumber,
            $buyerName
        );
    } catch (Throwable $exception) {
        error_log(
            '[Web Push] '
            . $exception->getMessage()
        );
    }

    // OneSignal sebagai kanal tambahan.
    try {
        send_store_onesignal(
            $storeId,
            $orderNumber,
            $buyerName
        );
    } catch (Throwable $exception) {
        error_log(
            '[OneSignal] '
            . $exception->getMessage()
        );
    }
}
