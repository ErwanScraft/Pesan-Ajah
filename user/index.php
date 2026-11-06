<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

header('Cache-Control: no-store, private');

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#176b45">
    <meta name="robots" content="noindex, nofollow">

    <title>Pesanan Saya — Pesan Ajah</title>

    <link rel="icon" type="image/webp" href="/assets/images/logo.webp">

    <link
        rel="stylesheet"
        href="/assets/css/style.css?v=<?= asset_version('assets/css/style.css') ?>"
    >

    <link
        rel="stylesheet"
        href="/assets/css/buyer-dashboard.css?v=<?= asset_version('assets/css/buyer-dashboard.css') ?>"
    >

    <script
        type="module"
        src="/assets/js/buyer-dashboard.js?v=<?= asset_version('assets/js/buyer-dashboard.js') ?>"
    ></script>
</head>
<body>
    <?php
    $isStorePage = false;
    require dirname(__DIR__) . '/_header.php';
    ?>

    <main class="buyer-page">
        <div class="container buyer-container">

            <section class="buyer-intro">
                <div>
                    <span class="buyer-intro-eyebrow">AKUN PEMBELI</span>
                    <h1>Pesanan Saya</h1>
                    <p>Pantau status dan riwayat pesananmu.</p>
                </div>

                <a class="buyer-shop-link" href="/">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path
                            d="M12 5v14M5 12h14"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>
                    Belanja
                </a>
            </section>

            <section class="buyer-summary" aria-label="Ringkasan pesanan">
                <article class="buyer-summary-card buyer-summary-active">
                    <div class="buyer-summary-top">
                        <span class="buyer-summary-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M6 7h12l1 13H5L6 7Z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M9 8V6a3 3 0 0 1 6 0v2"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>
                        <span class="buyer-summary-label">Pesanan aktif</span>
                    </div>

                    <strong id="buyer-active-count">—</strong>
                    <span class="buyer-summary-caption">Sedang berjalan</span>
                </article>

                <article class="buyer-summary-card buyer-summary-completed">
                    <div class="buyer-summary-top">
                        <span class="buyer-summary-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path
                                    d="m5 12 4 4L19 6"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                        <span class="buyer-summary-label">Pesanan selesai</span>
                    </div>

                    <strong id="buyer-completed-count">—</strong>
                    <span class="buyer-summary-caption">Total pesanan berhasil</span>
                </article>
            </section>

            <section class="buyer-order-section">
                <div class="buyer-section-heading">
                    <div>
                        <h2>Daftar pesanan</h2>
                        <p>Pilih status untuk melihat pesanan.</p>
                    </div>
                </div>

                <nav
                    class="buyer-order-tabs"
                    aria-label="Kategori pesanan"
                    role="tablist"
                >
                    <button
                        type="button"
                        class="buyer-order-tab is-active"
                        data-category="waiting"
                        role="tab"
                        aria-selected="true"
                    >
                        <span>Menunggu</span>
                        <span class="buyer-tab-count" id="buyer-count-waiting">0</span>
                    </button>

                    <button
                        type="button"
                        class="buyer-order-tab"
                        data-category="processing"
                        role="tab"
                        aria-selected="false"
                    >
                        <span>Diproses</span>
                        <span class="buyer-tab-count" id="buyer-count-processing">0</span>
                    </button>

                    <button
                        type="button"
                        class="buyer-order-tab"
                        data-category="handoff"
                        role="tab"
                        aria-selected="false"
                    >
                        <span>Pengiriman</span>
                        <span class="buyer-tab-count" id="buyer-count-handoff">0</span>
                    </button>

                    <button
                        type="button"
                        class="buyer-order-tab"
                        data-category="completed"
                        role="tab"
                        aria-selected="false"
                    >
                        <span>Riwayat</span>
                        <span class="buyer-tab-count" id="buyer-count-completed">0</span>
                    </button>
                </nav>

                <div
                    class="buyer-message"
                    id="buyer-message"
                    role="status"
                    aria-live="polite"
                >
                    Memuat pesanan...
                </div>

                <section
                    class="buyer-orders"
                    id="buyer-orders"
                    aria-label="Daftar pesanan"
                    aria-live="polite"
                ></section>

                <section class="buyer-empty" id="buyer-empty" hidden>
                    <div class="buyer-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path
                                d="M6 7h12l1 13H5L6 7Z"
                                stroke="currentColor"
                                stroke-width="1.6"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M9 8V6a3 3 0 0 1 6 0v2"
                                stroke="currentColor"
                                stroke-width="1.6"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>

                    <h2 id="buyer-empty-title">Belum ada pesanan</h2>

                    <p id="buyer-empty-description">
                        Pesananmu akan muncul di kategori ini.
                    </p>

                    <a class="button button-primary" href="/">
                        Mulai belanja
                    </a>
                </section>
            </section>

            <p class="buyer-security-note">
                Riwayat pesanan tersimpan di browser ini. Jika data browser
                dihapus atau kamu berganti perangkat, pesanan mungkin tidak
                muncul lagi.
            </p>

        </div>
    </main>

    <?php require dirname(__DIR__) . '/_footer.php'; ?>
</body>
</html>
