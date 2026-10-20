<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/driver_auth.php';

$driver = require_driver();
$db = database();

$message = flash('success');
$error = flash('error');


if (request_is_post()) {
    verify_csrf();

    $action = is_string($_POST['action'] ?? null)
        ? $_POST['action']
        : '';

    $orderId = filter_var(
        $_POST['order_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($orderId === false || $orderId === null) {
        flash('error', 'ID pesanan tidak valid.');
        redirect('driver/pesanan.php');
    }

    try {
        if ($action === 'accept') {
            $db->beginTransaction();

            $stmt = $db->prepare(
                'SELECT id, store_id, delivery_method, status
                 FROM orders
                 WHERE id = :id
                 FOR UPDATE'
            );
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (
                !$order
                || $order['delivery_method'] !== 'delivery'
                || $order['status'] !== 'ready_for_driver'
            ) {
                throw new RuntimeException(
                    'Pesanan belum siap untuk diambil driver.'
                );
            }

            $insert = $db->prepare(
                "INSERT INTO delivery_assignments (
                    order_id,
                    store_id,
                    driver_username,
                    status
                ) VALUES (
                    :order_id,
                    :store_id,
                    :driver_username,
                    'accepted'
                )"
            );

            $insert->execute([
                'order_id' => (int) $order['id'],
                'store_id' => (int) $order['store_id'],
                'driver_username' => (string) $driver['username'],
            ]);
            
            $updateOrder = $db->prepare(
                "UPDATE orders
                 SET status = 'in_delivery'
                 WHERE id = :id
                   AND delivery_method = 'delivery'
                   AND status = 'ready_for_driver'"
            );
            
            $updateOrder->execute([
                'id' => (int) $order['id'],
            ]);
            
            if ($updateOrder->rowCount() !== 1) {
                throw new RuntimeException(
                    'Status pesanan berubah. Muat ulang halaman.'
                );
            }

            $db->commit();

            flash('success', 'Pesanan berhasil kamu ambil.');
            redirect('driver/pesanan.php');
        }

        if ($action !== 'advance') {
            throw new RuntimeException(
                'Aksi pengantaran tidak valid.'
            );
        }

        $assignmentId = filter_var(
            $_POST['assignment_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($assignmentId === false || $assignmentId === null) {
            throw new RuntimeException(
                'ID penugasan tidak valid.'
            );
        }

        /*
         * Alur status hanya boleh maju satu tahap.
         * Kolom timestamp berasal dari daftar tetap,
         * bukan dari input pengguna.
         */
        $transitions = [
            'accepted' => [
                'next' => 'to_store',
                'timestamp' => 'to_store_at',
                'message' => 'Status diperbarui: menuju warung.',
            ],
            'to_store' => [
                'next' => 'picked_up',
                'timestamp' => 'picked_up_at',
                'message' => 'Pesanan ditandai sudah diambil dari warung.',
            ],
            'picked_up' => [
                'next' => 'to_buyer',
                'timestamp' => 'to_buyer_at',
                'message' => 'Status diperbarui: menuju pembeli.',
            ],
            'to_buyer' => [
                'next' => 'completed',
                'timestamp' => 'completed_at',
                'message' => 'Pengantaran berhasil diselesaikan.',
            ],
        ];

        $db->beginTransaction();

        /*
         * Kunci hanya penugasan milik driver yang sedang login.
         * ID penugasan dari pengguna tidak cukup untuk memperoleh akses.
         */
        $stmt = $db->prepare(
            'SELECT id, order_id, status
             FROM delivery_assignments
             WHERE id = :id
               AND driver_username = :driver
             FOR UPDATE'
        );

        $stmt->execute([
            'id' => $assignmentId,
            'driver' => (string) $driver['username'],
        ]);

        $assignment = $stmt->fetch();

        if (!$assignment) {
            throw new RuntimeException(
                'Penugasan tidak ditemukan atau bukan milik akunmu.'
            );
        }

        $currentStatus = (string) $assignment['status'];

        if (!isset($transitions[$currentStatus])) {
            throw new RuntimeException(
                'Pengantaran sudah selesai atau tidak dapat dilanjutkan.'
            );
        }

        $transition = $transitions[$currentStatus];
        $nextStatus = $transition['next'];
        $timestampColumn = $transition['timestamp'];

        /*
         * Nama kolom timestamp diambil dari konfigurasi tetap
         * di atas, sedangkan seluruh nilai data menggunakan parameter.
         */
        $update = $db->prepare(
            "UPDATE delivery_assignments
             SET status = :next_status,
                 {$timestampColumn} = CURRENT_TIMESTAMP
             WHERE id = :id
               AND driver_username = :driver
               AND status = :current_status"
        );

        $update->execute([
            'next_status' => $nextStatus,
            'id' => (int) $assignment['id'],
            'driver' => (string) $driver['username'],
            'current_status' => $currentStatus,
        ]);

        if ($update->rowCount() !== 1) {
            throw new RuntimeException(
                'Status berubah atau sudah diproses. Muat ulang halaman.'
            );
        }
        
        if ($nextStatus === 'completed') {
            $completeOrder = $db->prepare(
                "UPDATE orders
                 SET status = 'completed',
                     completed_at = CURRENT_TIMESTAMP
                 WHERE id = :id
                   AND delivery_method = 'delivery'
                   AND status = 'in_delivery'"
            );
        
            $completeOrder->execute([
                'id' => (int) $assignment['order_id'],
            ]);
        
            if ($completeOrder->rowCount() !== 1) {
                throw new RuntimeException(
                    'Pesanan tidak dapat diselesaikan. Periksa status pesanan.'
                );
            }
        }

        $db->commit();

        flash('success', $transition['message']);
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        if ($exception instanceof RuntimeException) {
            flash('error', $exception->getMessage());
        } elseif ($exception instanceof PDOException
            && $action === 'accept'
            && (string) $exception->getCode() === '23000'
        ) {
            // Indeks unik order_id mencegah pesanan diambil dua kali.
            flash('error', 'Pesanan sudah diambil atau tidak tersedia.');
        } else {
            error_log(
                '[Anter Ajah] Gagal memperbarui pengantaran: '
                . $exception->getMessage()
            );

            flash(
                'error',
                'Status pengantaran gagal diperbarui. Silakan coba lagi.'
            );
        }
    }

    redirect('driver/pesanan.php');
}


// Pesanan yang masih tersedia.
$queueStmt = $db->query(
    "SELECT
        o.id,
        o.order_number,
        o.store_id,
        s.name AS store_name,
        s.phone AS store_phone,
        o.buyer_name,
        o.buyer_phone,
        o.delivery_address,
        o.delivery_maps_url,
        o.delivery_fee,
        o.delivery_distance_km,
        o.subtotal,
        o.total,
        o.status AS order_status,
        o.created_at
     FROM orders o
     INNER JOIN stores s ON s.id = o.store_id
     WHERE o.delivery_method = 'delivery'
       AND o.status = 'ready_for_driver'
       AND NOT EXISTS (
           SELECT 1
           FROM delivery_assignments da
           WHERE da.order_id = o.id
       )
     ORDER BY o.created_at ASC, o.id ASC"
);

$queue = $queueStmt->fetchAll();

// Pengantaran aktif milik driver ini.
$activeStmt = $db->prepare(
    "SELECT
        da.id AS assignment_id,
        da.status AS delivery_status,
        da.accepted_at,
        o.id AS order_id,
        o.order_number,
        s.name AS store_name,
        s.phone AS store_phone,
        o.buyer_name,
        o.buyer_phone,
        o.delivery_address,
        o.delivery_maps_url,
        o.delivery_fee,
        o.delivery_distance_km,
        o.total,
        o.status AS order_status
     FROM delivery_assignments da
     INNER JOIN orders o ON o.id = da.order_id
     INNER JOIN stores s ON s.id = da.store_id
     WHERE da.driver_username = :driver
       AND da.status NOT IN ('completed', 'cancelled')
     ORDER BY da.accepted_at ASC"
);

$activeStmt->execute([
    'driver' => (string) $driver['username'],
]);

$activeOrders = $activeStmt->fetchAll();

function driver_rupiah(mixed $value): string
{
    return 'Rp' . number_format((float) $value, 0, ',', '.');
}

function driver_status_label(string $status): string
{
    return match ($status) {
        'accepted' => 'Pesanan diambil',
        'to_store' => 'Menuju warung',
        'picked_up' => 'Pesanan sudah diambil',
        'to_buyer' => 'Menuju pembeli',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        default => 'Status tidak dikenal',
    };
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#176b45">
    <title>Pesanan Delivery — Anter Ajah</title>

    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        .delivery-shell {
            width: min(100% - 32px, 1000px);
            margin: 0 auto;
            padding: 24px 0 48px;
        }

        .delivery-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }

        .delivery-heading h1 {
            margin-bottom: 6px;
        }

        .delivery-section {
            margin-top: 28px;
        }

        .delivery-list {
            display: grid;
            gap: 16px;
        }

        .delivery-card {
            min-width: 0;
            padding: 20px;
            border: 1px solid #e3e9e5;
            border-radius: 16px;
            background: #fff;
        }

        .delivery-card h2,
        .delivery-card h3 {
            margin-top: 0;
        }

        .delivery-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin: 18px 0;
        }

        .delivery-meta span {
            display: block;
            overflow-wrap: anywhere;
        }

        .delivery-label {
            margin-bottom: 4px;
            color: #647067;
            font-size: 13px;
        }

        .delivery-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .delivery-message {
            padding: 12px 14px;
            margin-bottom: 16px;
            border-radius: 10px;
        }

        .delivery-message.success {
            color: #176b45;
            background: #e8f6ee;
        }

        .delivery-message.error {
            color: #a32323;
            background: #fff0f0;
        }

        .delivery-empty {
            padding: 24px;
            border: 1px dashed #cbd5ce;
            border-radius: 14px;
            color: #647067;
            text-align: center;
        }

        @media (max-width: 520px) {
            .delivery-meta {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .delivery-card {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <main class="delivery-shell">
        <header class="delivery-heading">
            <div>
                <p class="muted">Anter Ajah</p>
                <h1>Pesanan Delivery</h1>
                <p class="muted">
                    Halo, <?= e($driver['name']) ?>.
                </p>
            </div>

            <a class="button button-outline" href="/driver/index.php">
                Dashboard
            </a>
        </header>

        <?php if ($message !== null): ?>
            <p class="delivery-message success" role="status">
                <?= e($message) ?>
            </p>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <p class="delivery-message error" role="alert">
                <?= e($error) ?>
            </p>
        <?php endif; ?>

        <section class="delivery-section">
            <h2>Pengantaran aktif (<?= count($activeOrders) ?>)</h2>

            <?php if (!$activeOrders): ?>
                <div class="delivery-empty">
                    Belum ada pesanan yang kamu ambil.
                </div>
            <?php else: ?>
                <div class="delivery-list">
                    <?php foreach ($activeOrders as $order): ?>
                        <article class="delivery-card">
                            <h3>
                                Pesanan #<?= e($order['order_number']) ?>
                            </h3>

                            <p>
                                Status:
                                <strong>
                                    <?= e(driver_status_label(
                                        (string) $order['delivery_status']
                                    )) ?>
                                </strong>
                            </p>

                            <div class="delivery-meta">
                                <div>
                                    <span class="delivery-label">Warung</span>
                                    <span><?= e($order['store_name']) ?></span>
                                </div>

                                <div>
                                    <span class="delivery-label">Pembeli</span>
                                    <span><?= e($order['buyer_name']) ?></span>
                                </div>

                                <div>
                                    <span class="delivery-label">Telepon pembeli</span>
                                    <span><?= e($order['buyer_phone']) ?></span>
                                </div>

                                <div>
                                    <span class="delivery-label">Alamat pengantaran</span>
                                    <span>
                                        <?= e($order['delivery_address'] ?: 'Alamat detail belum diisi') ?>
                                    </span>
                                </div>

                                <div>
                                    <span class="delivery-label">Jarak estimasi</span>
                                    <span>
                                        <?= $order['delivery_distance_km'] !== null
                                            ? e(number_format(
                                                (float) $order['delivery_distance_km'],
                                                2,
                                                ',',
                                                '.'
                                            ) . ' km')
                                            : 'Tidak tersedia' ?>
                                    </span>
                                </div>

                                <div>
                                    <span class="delivery-label">Ongkir tercatat</span>
                                    <span><?= e(driver_rupiah($order['delivery_fee'])) ?></span>
                                </div>
                            </div>

                            <div class="delivery-actions">
                                <?php if (!empty($order['delivery_maps_url'])): ?>
                                    <a
                                        class="button button-outline"
                                        href="<?= e($order['delivery_maps_url']) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Lokasi pembeli
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($order['buyer_phone'])): ?>
                                    <a
                                        class="button button-outline"
                                        href="tel:<?= e($order['buyer_phone']) ?>"
                                    >
                                        Telepon pembeli
                                    </a>
                                <?php endif; ?>
                                                       
                                <?php
                                $nextDeliveryAction = [
                                    'accepted' => [
                                        'label' => 'Mulai menuju warung',
                                        'confirm' => 'Mulai perjalanan menuju warung?',
                                    ],
                                    'to_store' => [
                                        'label' => 'Tandai pesanan sudah diambil',
                                        'confirm' => 'Konfirmasi bahwa pesanan sudah diterima dari warung?',
                                    ],
                                    'picked_up' => [
                                        'label' => 'Mulai menuju pembeli',
                                        'confirm' => 'Mulai perjalanan menuju pembeli?',
                                    ],
                                    'to_buyer' => [
                                        'label' => 'Selesaikan pengantaran',
                                        'confirm' => 'Konfirmasi bahwa pesanan sudah diterima pembeli?',
                                    ],
                                ];
                                
                                $currentDeliveryStatus = (string) $order['delivery_status'];
                                ?>
                                
                                <?php if (isset($nextDeliveryAction[$currentDeliveryStatus])): ?>
                                    <form method="post" action="/driver/pesanan.php">
                                        <?= csrf_field() ?>
                                
                                        <input type="hidden" name="action" value="advance">
                                
                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= (int) $order['order_id'] ?>"
                                        >
                                
                                        <input
                                            type="hidden"
                                            name="assignment_id"
                                            value="<?= (int) $order['assignment_id'] ?>"
                                        >
                                
                                        <button
                                            class="button button-primary"
                                            type="submit"
                                            onclick="return confirm(<?= e(json_encode(
                                                $nextDeliveryAction[$currentDeliveryStatus]['confirm'],
                                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                                            )) ?>)"
                                        >
                                            <?= e($nextDeliveryAction[$currentDeliveryStatus]['label']) ?>
                                        </button>
                                    </form>
                                <?php endif; ?>

                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="delivery-section">
            <h2>Antrean tersedia (<?= count($queue) ?>)</h2>

            <?php if (!$queue): ?>
                <div class="delivery-empty">
                    Belum ada pesanan Delivery yang siap diambil.
                </div>
            <?php else: ?>
                <div class="delivery-list">
                    <?php foreach ($queue as $order): ?>
                        <article class="delivery-card">
                            <h3>
                                Pesanan #<?= e($order['order_number']) ?>
                            </h3>

                            <p>
                                Warung:
                                <strong><?= e($order['store_name']) ?></strong>
                            </p>

                            <div class="delivery-meta">
                                <div>
                                    <span class="delivery-label">Pembeli</span>
                                    <span><?= e($order['buyer_name']) ?></span>
                                </div>

                                <div>
                                    <span class="delivery-label">Telepon pembeli</span>
                                    <span><?= e($order['buyer_phone']) ?></span>
                                </div>

                                <div>
                                    <span class="delivery-label">Alamat pengantaran</span>
                                    <span>
                                        <?= e($order['delivery_address'] ?: 'Alamat detail belum diisi') ?>
                                    </span>
                                </div>

                                <div>
                                    <span class="delivery-label">Jarak estimasi</span>
                                    <span>
                                        <?= $order['delivery_distance_km'] !== null
                                            ? e(number_format(
                                                (float) $order['delivery_distance_km'],
                                                2,
                                                ',',
                                                '.'
                                            ) . ' km')
                                            : 'Tidak tersedia' ?>
                                    </span>
                                </div>

                                <div>
                                    <span class="delivery-label">Ongkir tercatat</span>
                                    <span><?= e(driver_rupiah($order['delivery_fee'])) ?></span>
                                </div>

                                <div>
                                    <span class="delivery-label">Status pesanan warung</span>
                                    <span><?= e($order['order_status']) ?></span>
                                </div>
                            </div>

                            <div class="delivery-actions">
                                <?php if (!empty($order['delivery_maps_url'])): ?>
                                    <a
                                        class="button button-outline"
                                        href="<?= e($order['delivery_maps_url']) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Lokasi pembeli
                                    </a>
                                <?php endif; ?>

                                <form method="post" action="/driver/pesanan.php">
                                    <?= csrf_field() ?>

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="accept"
                                    >

                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= (int) $order['id'] ?>"
                                    >

                                    <button
                                        class="button button-primary"
                                        type="submit"
                                    >
                                        Ambil pesanan
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
