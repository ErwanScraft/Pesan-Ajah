<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/driver_auth.php';

$driver = require_driver();
$db = database();

$message = flash('success');
$error = flash('error');

if (request_is_post()) {
    verify_csrf();

    $action = $_POST['action'] ?? '';
    $orderId = filter_var(
        $_POST['order_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($action !== 'accept' || $orderId === false) {
        flash('error', 'Permintaan pengambilan pesanan tidak valid.');
        redirect('driver/pesanan.php');
    }

    try {
        $db->beginTransaction();

        // Kunci pesanan agar dua driver tidak bisa
        // mengambil pesanan yang sama secara bersamaan.
        $stmt = $db->prepare(
            'SELECT
                id,
                store_id,
                delivery_method,
                status
             FROM orders
             WHERE id = :id
             FOR UPDATE'
        );

        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch();

        if (
            !$order
            || $order['delivery_method'] !== 'delivery'
            || !in_array(
                $order['status'],
                ['confirmed', 'processing'],
                true
            )
        ) {
            throw new RuntimeException(
                'Pesanan tidak tersedia untuk diambil.'
            );
        }

        $existing = $db->prepare(
            'SELECT id
             FROM delivery_assignments
             WHERE order_id = :order_id
             LIMIT 1'
        );

        $existing->execute(['order_id' => $orderId]);

        if ($existing->fetch()) {
            throw new RuntimeException(
                'Pesanan sudah diambil oleh driver lain.'
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

        $db->commit();

        flash('success', 'Pesanan berhasil kamu ambil.');
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        if ($exception instanceof RuntimeException) {
            flash('error', $exception->getMessage());
        } else {
            error_log(
                '[Anter Ajah] Gagal mengambil pesanan: '
                . $exception->getMessage()
            );

            flash(
                'error',
                'Pesanan gagal diambil. Silakan coba kembali.'
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
       AND o.status IN ('confirmed', 'processing')
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
