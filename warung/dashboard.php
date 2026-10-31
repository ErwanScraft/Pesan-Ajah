<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_PATH . '/warung_auth.php';

$store = require_store_owner();

$pageKey = 'overview';
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

require __DIR__ . '/_header.php';

$isOpen = (bool) ($store['is_open'] ?? false);
$freeDelivery = (bool) ($store['free_delivery'] ?? false);

?>

<div class="warung-dashboard">

    <!-- STATUS OPERASIONAL -->
    <section class="overview-section">
        <div class="overview-heading">
            <div>
                <h2>Operasional Warung</h2>
                <p>Kontrol aktivitas warung Anda.</p>
            </div>
        </div>

        <div class="operation-card">

            <div
                class="operation-status <?= $isOpen ? 'is-open' : 'is-closed' ?>"
                id="operation-status-card"
            >
                <div class="operation-status-info">
                    <span class="operation-eyebrow">STATUS WARUNG</span>

                    <strong id="store-status-label">
                        <?= $isOpen ? 'Warung Sedang Buka' : 'Warung Sedang Tutup' ?>
                    </strong>

                    <span id="store-status-description">
                        <?= $isOpen
                            ? 'Pelanggan dapat melakukan pemesanan.'
                            : 'Warung tidak menerima pesanan baru.' ?>
                    </span>
                </div>

                <span class="operation-status-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M3 10h18M5 10v10h14V10M3 10l2-6h14l2 6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>
            </div>

            <button
                type="button"
                class="operation-toggle-button <?= $isOpen ? 'is-close' : 'is-open' ?>"
                id="store-toggle-button"
                data-is-open="<?= $isOpen ? '1' : '0' ?>"
            >
                <?= $isOpen ? 'Tutup Warung' : 'Buka Warung' ?>
            </button>

            <div class="operation-divider"></div>

            <div class="delivery-status">
                <span class="delivery-status-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M3 7h11v11H3zM14 11h4l3 3v4h-7"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <circle cx="7.5" cy="18" r="1.8"
                            fill="none" stroke="currentColor"
                            stroke-width="1.7"/>
                        <circle cx="17.5" cy="18" r="1.8"
                            fill="none" stroke="currentColor"
                            stroke-width="1.7"/>
                    </svg>
                </span>

                <div class="delivery-status-info">
                    <strong>Layanan Delivery</strong>
                    <span>Pesanan antar tersedia</span>
                </div>

                <span class="delivery-status-badge">
                    Tersedia
                </span>
            </div>

            <div class="delivery-status free-delivery-status">
                <span class="delivery-status-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M12 3v18M17 7H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>
                </span>

                <div class="delivery-status-info">
                    <strong>Gratis Ongkir</strong>
                    <span>
                        <?= $freeDelivery ? 'Potongan ongkir aktif' : 'Mengikuti biaya pengantaran' ?>
                    </span>
                </div>

                <span class="delivery-status-badge <?= $freeDelivery ? 'is-active' : 'is-inactive' ?>">
                    <?= $freeDelivery ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </div>

        </div>
    </section>

    <!-- STATISTIK -->
    <section class="overview-section">
        <div class="overview-heading">
            <div>
                <h2>Statistik</h2>
                <p id="dashboard-period-description">
                    Seluruh Pesanan.
                </p>
            </div>

            <select
                class="overview-period-select"
                id="dashboard-period"
                aria-label="Periode statistik"
            >
                <option value="all" selected>Semua</option>
                <option value="today">Hari Ini</option>
            </select>
        </div>

        <div class="overview-stat-grid">

            <article class="overview-stat-card">
                <span class="overview-stat-icon green">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>

                <strong id="stat-orders">0</strong>
                <span>Total Pesanan</span>
            </article>

            <article class="overview-stat-card">
                <span class="overview-stat-icon amber">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"
                            fill="none" stroke="currentColor"
                            stroke-width="1.7"/>
                        <path d="M12 7v5l3 2"
                            fill="none" stroke="currentColor"
                            stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </span>

                <strong id="stat-pending">0</strong>
                <span>Perlu Diproses</span>
            </article>

            <article class="overview-stat-card">
                <span class="overview-stat-icon blue">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M4 7h16v13H4zM8 7V4h8v3M8 12h8"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>

                <strong id="stat-completed">0</strong>
                <span>Pesanan Selesai</span>
            </article>

            <article class="overview-stat-card">
                <span class="overview-stat-icon purple">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M12 3v18M17 7H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>
                </span>

                <strong id="stat-revenue">Rp0</strong>
                <span>Total Penjualan</span>
            </article>

        </div>
    </section>

    <!-- RIWAYAT PESANAN -->
    <section class="overview-section">
        <div class="overview-heading overview-orders-heading">
            <div>
                <h2>Riwayat Pesanan</h2>
                <p>Pesanan terbaru dari pelanggan.</p>
            </div>

            <a
                class="overview-text-link"
                href="<?= e(url('warung/pesanan.php')) ?>"
            >
                Lihat Semua
                <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="overview-orders-list" id="recent-orders">
            <div class="overview-empty">
                Memuat pesanan...
            </div>
        </div>
    </section>

    <!-- QUICK BUTTONS -->
    <section class="overview-section">
        <div class="overview-heading">
            <div>
                <h2>Akses Cepat</h2>
            </div>
        </div>

        <div class="overview-quick-grid">

            <a href="<?= e(url('warung/pesanan.php')) ?>"
                class="overview-quick-card">
                <span class="overview-quick-icon green">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>
                </span>
                <strong>Pesanan</strong>
            </a>

            <a href="<?= e(url('warung/produk.php')) ?>"
                class="overview-quick-card">
                <span class="overview-quick-icon blue">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="m12 3 9 5-9 5-9-5 9-5ZM3 8v9l9 5 9-5V8M12 13v9"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>
                <strong>Produk</strong>
            </a>

            <a href="<?= e(url('warung/pengaturan.php')) ?>"
                class="overview-quick-card">
                <span class="overview-quick-icon purple">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        />
                        <path
                            d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.6a8 8 0 0 1-1.5.9l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.5-.9l-1.7.6-1.4-2.4 1.4-1.1a7 7 0 0 1 0-1.8l-1.4-1.1 1.4-2.4 1.7.6a8 8 0 0 1 1.5-.9l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.5.9l1.7-.6 1.4 2.4-1.4 1.1a7 7 0 0 1 0 1.8Z"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.3"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>
                <strong>Pengaturan</strong>
            </a>

            <a href="<?= e(url('')) ?>"
                class="overview-quick-card"
                target="_blank"
                rel="noopener">
                <span class="overview-quick-icon orange">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M3 10h18M5 10v10h14V10M3 10l2-6h14l2 6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </span>
                <strong>Lihat Warung</strong>
            </a>

        </div>
    </section>

</div>

<?php require __DIR__ . '/_footer.php'; ?>
