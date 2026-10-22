<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_PATH . '/warung_auth.php';

$store = require_store_owner();

$pageKey = 'overview';
$pageTitle = 'Rincian';
$activePage = 'dashboard';

require __DIR__ . '/_header.php';

?>

<div class="welcome-row">
    <div>
        <h2>Ringkasan warung</h2>
    </div>
</div>

<div class="stat-grid">

    <article class="stat-card">
        <div class="stat-top">
            <span>Total pesanan</span>
            <span
                class="stat-icon green"
                aria-hidden="true"
            >▤</span>
        </div>

        <strong id="stat-orders">0</strong>
    </article>

    <article class="stat-card">
        <div class="stat-top">
            <span>Perlu diproses</span>
            <span
                class="stat-icon amber"
                aria-hidden="true"
            >◷</span>
        </div>

        <strong id="stat-pending">0</strong>
    </article>

    <article class="stat-card">
        <div class="stat-top">
            <span>Produk tersedia</span>
            <span
                class="stat-icon blue"
                aria-hidden="true"
            >□</span>
        </div>

        <strong id="stat-products">0</strong>
    </article>

    <article class="stat-card">
        <div class="stat-top">
            <span>Status warung</span>
            <span
                class="stat-icon green"
                aria-hidden="true"
            >●</span>
        </div>

        <strong
            class="status-value"
            id="store-status-label"
        >
            <?= (bool) $store['is_open'] ? 'Buka' : 'Tutup' ?>
        </strong>
    </article>

    <article class="stat-card">
        <div class="stat-top">
            <span>Omzet selesai</span>
            <span
                class="stat-icon green"
                aria-hidden="true"
            >Rp</span>
        </div>

        <strong id="stat-revenue">Rp0</strong>
    </article>

</div>

<section class="dashboard-card">

    <div class="card-heading">
        <div>
            <h3>Akses cepat</h3>
        </div>
    </div>

    <div class="manage-row">
        <div class="manage-product">
            <span
                class="manage-emoji"
                aria-hidden="true"
            >▤</span>

            <span>
                <strong>Pesanan</strong>
            </span>
        </div>

        <a
            class="button button-outline"
            href="<?= e(url('warung/pesanan.php')) ?>"
        >
            Kelola
        </a>
    </div>

    <div class="manage-row">
        <div class="manage-product">
            <span
                class="manage-emoji"
                aria-hidden="true"
            >□</span>

            <span>
                <strong>Produk</strong>
            </span>
        </div>

        <a
            class="button button-outline"
            href="<?= e(url('warung/produk.php')) ?>"
        >
            Kelola
        </a>
    </div>

    <div class="manage-row">
        <div class="manage-product">
            <span
                class="manage-emoji"
                aria-hidden="true"
            >⚙</span>

            <span>
                <strong>Pengaturan warung</strong>
            </span>
        </div>

        <a
            class="button button-outline"
            href="<?= e(url('warung/pengaturan.php')) ?>"
        >
            Buka
        </a>
    </div>

</section>

<?php require __DIR__ . '/_footer.php'; ?>
