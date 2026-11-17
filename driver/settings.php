<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/driver_auth.php';
require_once dirname(__DIR__) . '/app/delivery_availability.php';

$driver = require_driver();
$db = database();

if (request_is_post()) {
    verify_csrf();

    $online = isset($_POST['is_online']) ? 1 : 0;

    $stmt = $db->prepare(
        'UPDATE drivers
         SET is_online = :online,
             last_seen_at = CURRENT_TIMESTAMP
         WHERE id = :id
           AND is_active = 1'
    );

    $stmt->execute([
        'online' => $online,
        'id' => $driver['id'],
    ]);

    flash(
        'success',
        $online
            ? 'Status driver sekarang ON.'
            : 'Status driver sekarang OFF.'
    );

    redirect('driver/settings.php');
}

$stmt = $db->prepare(
    'SELECT is_online, is_active
     FROM drivers
     WHERE id = :id'
);

$stmt->execute(['id' => $driver['id']]);

$status = $stmt->fetch();

$deliveryAvailable = delivery_is_available($db);
$success = flash('success');

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Driver — Antar Ajah</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= asset_version('assets/css/style.css') ?>">
    <link rel="stylesheet" href="/assets/css/pages/driver.css?v=<?= asset_version('assets/css/pages/driver.css') ?>">
</head>
<body class="driver-page">

<main class="driver-shell">

    <header class="driver-topbar">
        <div class="driver-brand">
            <span class="driver-brand-icon">A</span>
            <div>
                <strong>Antar Ajah</strong>
                <span>Pengaturan Driver</span>
            </div>
        </div>

        <a class="driver-logout" href="/driver/index.php">Dashboard</a>
    </header>

    <h1>Pengaturan Operasional</h1>

    <?php if ($success): ?>
        <p class="delivery-message success"><?= e($success) ?></p>
    <?php endif; ?>

    <section class="driver-active-card">

        <span class="driver-eyebrow">STATUS AKUN SAYA</span>

        <h2><?= $status['is_online'] ? 'Sedang Online' : 'Sedang Offline' ?></h2>

        <p class="muted">
            ON berarti kamu bersedia menerima penugasan baru.
            OFF berarti kamu tidak menerima penugasan baru.
        </p>

        <form method="post" style="margin-top:20px">
            <?= csrf_field() ?>

            <?php if ($status['is_online']): ?>
                <button
                    class="driver-button driver-button-secondary"
                    type="submit"
                >
                    Matikan status (OFF)
                </button>
            <?php else: ?>
                <input type="hidden" name="is_online" value="1">

                <button
                    class="driver-button driver-button-primary"
                    type="submit"
                >
                    Aktifkan status (ON)
                </button>
            <?php endif; ?>
        </form>

    </section>

    <section class="driver-queue-card">
        <span class="driver-eyebrow">KETERSEDIAAN LAYANAN</span>

        <h2>
            <?= $deliveryAvailable ? 'Delivery ONLINE' : 'Delivery OFFLINE' ?>
        </h2>

        <p>
            Status layanan mengikuti pengaturan Admin dan ketersediaan
            seluruh driver.
        </p>
    </section>

</main>

</body>
</html>
