<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/driver_auth.php';

$driver = require_driver();
$db = database();

// Antrean pesanan yang tersedia.
$queueStmt = $db->query(
    "SELECT COUNT(*)
     FROM orders o
     WHERE o.delivery_method = 'delivery'
       AND o.status = 'ready_for_driver'
       AND NOT EXISTS (
           SELECT 1
           FROM delivery_assignments da
           WHERE da.order_id = o.id
       )"
);

$queueCount = (int) $queueStmt->fetchColumn();

// Pengantaran aktif milik driver.
$activeStmt = $db->prepare(
    "SELECT COUNT(*)
     FROM delivery_assignments
     WHERE driver_username = :driver
       AND status NOT IN ('completed', 'cancelled')"
);

$activeStmt->execute([
    'driver' => (string) $driver['username'],
]);

$activeCount = (int) $activeStmt->fetchColumn();

// Pengantaran yang diselesaikan hari ini.
$completedStmt = $db->prepare(
    "SELECT COUNT(*)
     FROM delivery_assignments
     WHERE driver_username = :driver
       AND status = 'completed'
       AND DATE(completed_at) = CURRENT_DATE"
);

$completedStmt->execute([
    'driver' => (string) $driver['username'],
]);

$completedCount = (int) $completedStmt->fetchColumn();

// Status terakhir pengantaran aktif.
$latestStmt = $db->prepare(
    "SELECT
        da.status,
        o.order_number,
        s.name AS store_name,
        o.buyer_name
     FROM delivery_assignments da
     INNER JOIN orders o ON o.id = da.order_id
     INNER JOIN stores s ON s.id = da.store_id
     WHERE da.driver_username = :driver
       AND da.status NOT IN ('completed', 'cancelled')
     ORDER BY da.accepted_at ASC
     LIMIT 1"
);

$latestStmt->execute([
    'driver' => (string) $driver['username'],
]);

$latestDelivery = $latestStmt->fetch() ?: null;

function driver_dashboard_status(string $status): string
{
    return match ($status) {
        'accepted' => 'Pesanan diterima',
        'to_store' => 'Menuju warung',
        'picked_up' => 'Pesanan sudah diambil',
        'to_buyer' => 'Menuju pembeli',
        default => 'Pengantaran aktif',
    };
}

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#176b45">

    <title>Dashboard Driver — Antar Ajah</title>

    <link
        rel="stylesheet"
        href="/assets/css/style.css?v=<?= asset_version('assets/css/style.css') ?>"
    >

    <link
        rel="stylesheet"
        href="/assets/css/pages/driver.css?v=<?= asset_version('assets/css/pages/driver.css') ?>"
    >
</head>
<body class="driver-page">

    <main class="driver-shell">

        <header class="driver-topbar">
            <div class="driver-brand">
                <span class="driver-brand-icon">A</span>

                <div>
                    <strong>Antar Ajah</strong>
                    <span>Driver Dashboard</span>
                </div>
            </div>

            <form method="post" action="/driver/logout.php">
                <?= csrf_field() ?>

                <button
                    type="submit"
                    class="driver-logout"
                    aria-label="Keluar"
                >
                    <span>Keluar</span>
                </button>
            </form>
        </header>

        <section class="driver-welcome">
            <div>
                <span class="driver-eyebrow">AREA DRIVER</span>

                <h1>
                    Halo, <?= e($driver['name']) ?>
                </h1>

                <p>
                    Pantau aktivitas dan pengantaran pesananmu.
                </p>
            </div>

            <div class="driver-avatar">
                <?= e(mb_strtoupper(mb_substr((string) $driver['name'], 0, 1))) ?>
            </div>
        </section>

        <section class="driver-stats">

            <article class="driver-stat driver-stat-primary">
                <span class="driver-stat-icon">↗</span>

                <span class="driver-stat-label">
                    Antrean tersedia
                </span>

                <strong>
                    <?= $queueCount ?>
                </strong>

                <span class="driver-stat-caption">
                    Pesanan siap diambil
                </span>
            </article>

            <article class="driver-stat">
                <span class="driver-stat-icon">⌁</span>

                <span class="driver-stat-label">
                    Pengantaran aktif
                </span>

                <strong>
                    <?= $activeCount ?>
                </strong>

                <span class="driver-stat-caption">
                    Sedang kamu tangani
                </span>
            </article>

            <article class="driver-stat">
                <span class="driver-stat-icon">✓</span>

                <span class="driver-stat-label">
                    Selesai hari ini
                </span>

                <strong>
                    <?= $completedCount ?>
                </strong>

                <span class="driver-stat-caption">
                    Pengantaran berhasil
                </span>
            </article>

        </section>

        <section class="driver-section-heading">
            <div>
                <span class="driver-eyebrow">AKTIVITAS</span>
                <h2>Operasional pengantaran</h2>
            </div>
        </section>

        <?php if ($latestDelivery): ?>

            <section class="driver-active-card">

                <div class="driver-active-top">
                    <span class="driver-live-indicator">
                        Pengantaran berlangsung
                    </span>

                    <span class="driver-order-number">
                        #<?= e($latestDelivery['order_number']) ?>
                    </span>
                </div>

                <h3>
                    <?= e($latestDelivery['store_name']) ?>
                </h3>

                <p class="driver-active-buyer">
                    Pembeli: <?= e($latestDelivery['buyer_name']) ?>
                </p>

                <div class="driver-active-status">
                    <?= e(driver_dashboard_status(
                        (string) $latestDelivery['status']
                    )) ?>
                </div>

                <a
                    href="/driver/pesanan.php"
                    class="driver-button driver-button-primary"
                >
                    Lihat pengantaran
                    <span>→</span>
                </a>

            </section>

        <?php else: ?>

            <section class="driver-empty-card">
                <div class="driver-empty-icon">⌁</div>

                <h3>Belum ada pengantaran aktif</h3>

                <p>
                    Ambil pesanan dari antrean untuk mulai melakukan pengantaran.
                </p>
            </section>

        <?php endif; ?>

        <section class="driver-queue-card">

            <div class="driver-queue-content">
                <div>
                    <span class="driver-eyebrow">PESANAN BARU</span>

                    <h3>Antrean pengantaran</h3>

                    <p>
                        Ada <?= $queueCount ?> pesanan yang siap diambil.
                    </p>
                </div>

                <div class="driver-queue-count">
                    <?= $queueCount ?>
                </div>
            </div>

            <a
                href="/driver/pesanan.php"
                class="driver-button driver-button-secondary"
            >
                Buka antrean pesanan
                <span>→</span>
            </a>

        </section>

        <footer class="driver-footer">
            <span>ANTAR AJAH</span>
            <p>Bagian dari ekosistem Pesan Ajah</p>
        </footer>

    </main>

    <nav class="driver-bottom-nav">

        <a
            href="/driver/index.php"
            class="driver-nav-item active"
            aria-current="page"
        >
            <span class="driver-nav-icon">⌂</span>
            <span>Dashboard</span>
        </a>

        <a
            href="/driver/pesanan.php"
            class="driver-nav-item"
        >
            <span class="driver-nav-icon">▤</span>
            <span>Pesanan</span>

            <?php if ($queueCount > 0): ?>
                <span class="driver-nav-badge">
                    <?= $queueCount > 99 ? '99+' : $queueCount ?>
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
