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

        <p class="muted">
            Pantau aktivitas warung dari satu tempat.
        </p>
    </div>

    <span class="demo-badge">
        <span class="dot"></span>
        Mode demo
    </span>
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

        <small class="stat-caption">
            Pesanan yang tercatat
        </small>
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

        <small class="stat-caption">
            Menunggu tindakan
        </small>
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

        <small class="stat-caption">
            Produk yang dapat dipesan
        </small>
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

        <small class="stat-caption">
            Status operasional
        </small>
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
    
        <small class="stat-caption">
            Total pesanan yang selesai
        </small>
    </article>

</div>

<section class="dashboard-card">

    <div class="card-heading">
        <div>
            <h3>Akses cepat</h3>

            <p class="muted">
                Pilih bagian yang ingin dikelola.
            </p>
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

                <small>
                    Lihat dan perbarui status pesanan.
                </small>
            </span>
        </div>

        <a
            class="button button-outline"
            href="<?= e(url('warung/pesanan.php')) ?>"
        >
            Buka pesanan
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

                <small>
                    Kelola katalog dan ketersediaan.
                </small>
            </span>
        </div>

        <a
            class="button button-outline"
            href="<?= e(url('warung/produk.php')) ?>"
        >
            Buka produk
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

                <small>
                    Ubah profil dan status operasional.
                </small>
            </span>
        </div>

        <a
            class="button button-outline"
            href="<?= e(url('warung/pengaturan.php')) ?>"
        >
            Buka pengaturan
        </a>
    </div>

</section>

<div class="dashboard-note">
    <strong>Catatan:</strong>

    <p>
        Halaman ini sudah dibatasi untuk akun warung yang aktif.
        Data statistik pesanan dan produk masih menggunakan
        implementasi demo pada JavaScript.
    </p>
</div>

<?php require __DIR__ . '/_footer.php'; ?>