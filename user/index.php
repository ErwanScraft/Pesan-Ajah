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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/buyer-dashboard.css">

    <script type="module" src="/assets/js/buyer-dashboard.js"></script>
</head>
<body>
    <header class="site-header buyer-site-header">
        <div class="container header-inner">
            <a class="brand" href="/" aria-label="Pesan Ajah — Beranda">
                <img
                    class="brand-logo"
                    src="/assets/images/logo.webp"
                    alt=""
                    width="44"
                    height="44"
                >
                <span class="brand-text">
                    <strong>Pesan Ajah</strong>
                    <small>Belanja dekat, jadi mudah.</small>
                </span>
            </a>

            <a class="button button-outline buyer-back-button" href="/">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                        d="M19 12H5M12 19l-7-7 7-7"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                <span>Belanja</span>
            </a>
        </div>
    </header>

    <main class="buyer-page">
        <div class="container buyer-container">
            <section class="buyer-intro">
                <span class="eyebrow eyebrow-plain">DASHBOARD PEMBELI</span>
                <h1>Pesanan saya</h1>
                <p>Pantau pesanan dan status pengantaranmu.</p>
            </section>

            <section class="buyer-summary" aria-label="Ringkasan pesanan">
                <article class="buyer-summary-card">
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
                    <span class="buyer-summary-label">Dalam proses</span>
                    <strong id="buyer-active-count">—</strong>
                </article>

                <article class="buyer-summary-card">
                    <span class="buyer-summary-icon is-complete" aria-hidden="true">
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
                    <span class="buyer-summary-label">Selesai</span>
                    <strong id="buyer-completed-count">—</strong>
                </article>
            </section>

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
                <h2>Belum ada pesanan</h2>
                <p>Pesananmu akan muncul di halaman ini setelah checkout.</p>
                <a class="button button-primary" href="/">Mulai belanja</a>
            </section>

            <p class="buyer-security-note">
                Riwayat tersimpan di browser ini. Jika data browser dihapus
                atau kamu berganti perangkat, pesanan mungkin tidak muncul lagi.
            </p>
        </div>
    </main>

    <footer class="site-footer buyer-site-footer">
        <div class="container footer-inner">
            <p>Marketplace warung lokal Banyukuning.</p>
            <small>
                © <span id="buyer-current-year"></span> Pesan Ajah
            </small>
        </div>
    </footer>
</body>
</html>