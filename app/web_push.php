<?php

declare(strict_types=1);

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Mengirim notifikasi pesanan baru ke semua perangkat
 * yang terdaftar pada warung terkait.
 *
 * Kegagalan push tidak boleh membatalkan pesanan.
 */
function send_store_order_push(
    PDO $db,
    int $storeId,
    string $orderNumber,
    string $buyerName
): void {
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';

    if (!is_file($autoload)) {
        error_log('[Pesan Ajah Push] Composer autoload tidak ditemukan.');
        return;
    }

    $publicKey = (string) env('VAPID_PUBLIC_KEY', '');
    $privateKey = (string) env('VAPID_PRIVATE_KEY', '');
    $subject = (string) env(
        'VAPID_SUBJECT',
        'mailto:admin@pesanajah.web.id'
    );

    if (
        $publicKey === ''
        || $privateKey === ''
        || $subject === ''
    ) {
        error_log('[Pesan Ajah Push] Konfigurasi VAPID belum lengkap.');
        return;
    }

    try {
        require_once $autoload;

        $stmt = $db->prepare(
            'SELECT
                id,
                endpoint,
                p256dh,
                auth,
                content_encoding
             FROM push_subscriptions
             WHERE store_id = :store_id'
        );

        $stmt->execute([
            'store_id' => $storeId,
        ]);

        $subscriptions = $stmt->fetchAll();

        if (!$subscriptions) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $payload = json_encode([
            'title' => 'Pesanan Baru Masuk!',
            'body' => $buyerName
                . ' mengirim pesanan '
                . $orderNumber,
            'order_number' => $orderNumber,
            'url' => '/warung/pesanan.php',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            return;
        }

        $deleteStmt = $db->prepare(
            'DELETE FROM push_subscriptions
             WHERE id = :id
               AND store_id = :store_id'
        );

        foreach ($subscriptions as $row) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $row['endpoint'],
                    'keys' => [
                        'p256dh' => $row['p256dh'],
                        'auth' => $row['auth'],
                    ],
                    'contentEncoding' => $row['content_encoding'],
                ]);

                $report = $webPush->sendOneNotification(
                    $subscription,
                    $payload,
                    ['TTL' => 60]
                );

                if ($report->isSubscriptionExpired()) {
                    $deleteStmt->execute([
                        'id' => (int) $row['id'],
                        'store_id' => $storeId,
                    ]);
                }
            } catch (Throwable $exception) {
                error_log(
                    '[Pesan Ajah Push] Gagal mengirim notifikasi: '
                    . $exception->getMessage()
                );
            }
        }
    } catch (Throwable $exception) {
        error_log(
            '[Pesan Ajah Push] Error: '
            . $exception->getMessage()
        );
    }
}