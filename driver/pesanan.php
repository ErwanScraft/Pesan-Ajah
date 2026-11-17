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
            
            $driverStatus = $db->prepare(
                'SELECT
                    d.is_active,
                    d.is_online,
                    ds.is_enabled AS delivery_enabled
                 FROM drivers d
                 CROSS JOIN delivery_settings ds
                 WHERE d.id = :driver_id
                   AND ds.id = 1
                 LIMIT 1'
            );
            
            $driverStatus->execute([
                'driver_id' => (int) $driver['id'],
            ]);
            
            $currentDriver = $driverStatus->fetch();
            
            if (
                !$currentDriver
                || !(bool) $currentDriver['delivery_enabled']
                || !(bool) $currentDriver['is_active']
                || !(bool) $currentDriver['is_online']
            ) {
                throw new RuntimeException(
                    'Layanan Delivery offline atau status driver tidak aktif.'
                );
            }

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

    <title>Pesanan Delivery — Antar Ajah</title>

    <link
        rel="stylesheet"
        href="/assets/css/style.css?v=<?= asset_version('assets/css/style.css') ?>"
    >

    <link
        rel="stylesheet"
        href="/assets/css/pages/driver.css?v=<?= asset_version('assets/css/pages/driver.css') ?>"
    >
</head>

<body class="driver-page delivery-page">

    <main class="driver-shell delivery-shell">
    
        <header class="driver-topbar">
    
            <a
                href="/driver/index.php"
                class="driver-brand"
                style="text-decoration:none;color:inherit"
            >
                <span class="driver-brand-icon">A</span>
    
                <div>
                    <strong>Antar Ajah</strong>
                    <span>Manajemen Pengantaran</span>
                </div>
            </a>
    
            <a
                href="/driver/index.php"
                class="driver-logout"
                style="text-decoration:none"
            >
                Dashboard
            </a>
    
        </header>
    
        <section class="driver-welcome">
            <div>
                <span class="driver-eyebrow">OPERASIONAL</span>
    
                <h1>Pesanan Delivery</h1>
    
                <p>
                    Halo, <?= e($driver['name']) ?>.
                    Kelola semua pengantaran di sini.
                </p>
            </div>
    
            <div class="driver-avatar">
                <?= e(mb_strtoupper(mb_substr((string) $driver['name'], 0, 1))) ?>
            </div>
        </section>
    
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
    
        <!-- PENGANTARAN AKTIF -->
    
        <section class="driver-section-heading">
            <div>
                <span class="driver-eyebrow">SEDANG BERLANGSUNG</span>
    
                <h2>
                    Pengantaran aktif
                    (<?= count($activeOrders) ?>)
                </h2>
            </div>
        </section>
    
        <?php if (!$activeOrders): ?>
    
            <div class="delivery-empty">
                Belum ada pengantaran aktif.
                Ambil pesanan dari antrean untuk mulai bekerja.
            </div>
    
        <?php else: ?>
    
            <div class="delivery-list">
    
                <?php foreach ($activeOrders as $order): ?>
    
                    <?php
                    $deliverySteps = [
                        'accepted' => 0,
                        'to_store' => 1,
                        'picked_up' => 2,
                        'to_buyer' => 3,
                        'completed' => 4,
                    ];
    
                    $currentStep = $deliverySteps[
                        (string) $order['delivery_status']
                    ] ?? 0;
    
                    $nextDeliveryAction = [
                        'accepted' => [
                            'label' => 'Mulai menuju warung',
                            'confirm' => 'Mulai perjalanan menuju warung?',
                        ],
                        'to_store' => [
                            'label' => 'Tandai pesanan sudah diambil',
                            'confirm' => 'Konfirmasi pesanan sudah diterima dari warung?',
                        ],
                        'picked_up' => [
                            'label' => 'Mulai menuju pembeli',
                            'confirm' => 'Mulai perjalanan menuju pembeli?',
                        ],
                        'to_buyer' => [
                            'label' => 'Selesaikan pengantaran',
                            'confirm' => 'Konfirmasi pesanan sudah diterima pembeli?',
                        ],
                    ];
    
                    $currentDeliveryStatus = (string) $order['delivery_status'];
    
                    $stepLabels = [
                        'Diterima',
                        'Menuju warung',
                        'Diambil',
                        'Menuju pembeli',
                    ];
                    ?>
    
                    <article class="delivery-card">
    
                        <div class="driver-active-top">
    
                            <span class="driver-live-indicator">
                                Pengantaran aktif
                            </span>
    
                            <span class="driver-order-number">
                                #<?= e($order['order_number']) ?>
                            </span>
    
                        </div>
    
                        <h3 style="margin-top:16px">
                            <?= e($order['store_name']) ?>
                        </h3>
    
                        <p>
                            Pembeli:
                            <strong><?= e($order['buyer_name']) ?></strong>
                        </p>
    
                        <!-- PROGRESS PENGANTARAN -->
    
                        <div class="delivery-stepper">
    
                            <?php foreach ($stepLabels as $index => $label): ?>
    
                                <?php
                                $stepClass = '';
    
                                if ($index < $currentStep) {
                                    $stepClass = 'done';
                                } elseif ($index === $currentStep) {
                                    $stepClass = 'current';
                                }
                                ?>
    
                                <div class="delivery-step <?= $stepClass ?>">
    
                                    <span class="delivery-step-icon">
                                        <?= $index < $currentStep ? '✓' : ($index + 1) ?>
                                    </span>
    
                                    <span class="delivery-step-label">
                                        <?= e($label) ?>
                                    </span>
    
                                </div>
    
                            <?php endforeach; ?>
    
                        </div>
    
                        <div class="driver-active-status">
                            <?= e(driver_status_label($currentDeliveryStatus)) ?>
                        </div>
    
                        <!-- INFORMASI PESANAN -->
    
                        <div class="delivery-meta">
    
                            <div>
                                <span class="delivery-label">Nama warung</span>
                                <span><?= e($order['store_name']) ?></span>
                            </div>
    
                            <div>
                                <span class="delivery-label">Nomor warung</span>
                                <span><?= e($order['store_phone'] ?: '-') ?></span>
                            </div>
    
                            <div>
                                <span class="delivery-label">Nama pembeli</span>
                                <span><?= e($order['buyer_name']) ?></span>
                            </div>
    
                            <div>
                                <span class="delivery-label">Nomor pembeli</span>
                                <span><?= e($order['buyer_phone'] ?: '-') ?></span>
                            </div>
    
                            <div>
                                <span class="delivery-label">Alamat pengantaran</span>
                                <span>
                                    <?= e(
                                        $order['delivery_address']
                                        ?: 'Alamat detail belum diisi'
                                    ) ?>
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
                                <span>
                                    <?= e(driver_rupiah($order['delivery_fee'])) ?>
                                </span>
                            </div>
    
                        </div>
    
                        <!-- AKSI -->
    
                        <div class="delivery-actions">
    
                            <?php if (!empty($order['delivery_maps_url'])): ?>
    
                                <a
                                    class="button button-outline"
                                    href="<?= e($order['delivery_maps_url']) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Buka lokasi pembeli
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
    
                            <?php if (isset($nextDeliveryAction[$currentDeliveryStatus])): ?>
    
                                <form
                                    method="post"
                                    action="/driver/pesanan.php"
                                    onsubmit="return confirm(<?= e(json_encode(
                                        $nextDeliveryAction[$currentDeliveryStatus]['confirm'],
                                        JSON_HEX_TAG
                                        | JSON_HEX_APOS
                                        | JSON_HEX_QUOT
                                        | JSON_HEX_AMP
                                    )) ?>)"
                                >
    
                                    <?= csrf_field() ?>
    
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="advance"
                                    >
    
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
    
        <!-- ANTREAN TERSEDIA -->
    
        <section class="driver-section-heading">
    
            <div>
                <span class="driver-eyebrow">PESANAN BARU</span>
    
                <h2>
                    Antrean tersedia
                    (<?= count($queue) ?>)
                </h2>
            </div>
    
        </section>
    
        <?php if (!$queue): ?>
    
            <div class="delivery-empty">
                Belum ada pesanan Delivery yang siap diambil.
            </div>
    
        <?php else: ?>
    
            <div class="delivery-list">
    
                <?php foreach ($queue as $order): ?>
    
                    <article class="delivery-card">
    
                        <div class="driver-active-top">
    
                            <span class="driver-live-indicator">
                                Siap diambil
                            </span>
    
                            <span class="driver-order-number">
                                #<?= e($order['order_number']) ?>
                            </span>
    
                        </div>
    
                        <h3 style="margin-top:16px">
                            <?= e($order['store_name']) ?>
                        </h3>
    
                        <p>
                            Pembeli:
                            <strong><?= e($order['buyer_name']) ?></strong>
                        </p>
    
                        <div class="delivery-meta">
    
                            <div>
                                <span class="delivery-label">Nomor pembeli</span>
                                <span><?= e($order['buyer_phone'] ?: '-') ?></span>
                            </div>
    
                            <div>
                                <span class="delivery-label">Alamat pengantaran</span>
                                <span>
                                    <?= e(
                                        $order['delivery_address']
                                        ?: 'Alamat detail belum diisi'
                                    ) ?>
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
                                <span>
                                    <?= e(driver_rupiah($order['delivery_fee'])) ?>
                                </span>
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
                                    Buka lokasi pembeli
                                </a>
    
                            <?php endif; ?>
    
                            <form
                                method="post"
                                action="/driver/pesanan.php"
                                onsubmit="return confirm('Ambil pesanan ini?')"
                            >
    
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
    
    </main>
    
    <nav class="driver-bottom-nav">
    
        <a
            href="/driver/index.php"
            class="driver-nav-item"
        >
            <span class="driver-nav-icon">⌂</span>
            <span>Dashboard</span>
        </a>
    
        <a
            href="/driver/pesanan.php"
            class="driver-nav-item active"
            aria-current="page"
        >
            <span class="driver-nav-icon">▤</span>
            <span>Pesanan</span>
    
            <?php if (count($queue) > 0): ?>
                <span class="driver-nav-badge">
                    <?= count($queue) > 99 ? '99+' : count($queue) ?>
                </span>
            <?php endif; ?>
        </a>
        
        <a href="/driver/profile.php" class="driver-nav-item">
            <span class="driver-nav-icon">♙</span>
            <span>Profil</span>
        </a>
        
        <a href="/driver/settings.php" class="driver-nav-item">
            <span class="driver-nav-icon">⚙</span>
            <span>Pengaturan</span>
        </a>
    
    </nav>

</body>
</html>