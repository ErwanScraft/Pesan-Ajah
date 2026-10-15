<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

header('Cache-Control: no-store, private');

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta name="theme-color" content="#176b45">
    <meta name="robots" content="noindex, nofollow">

    <title>Dashboard Buyer — Pesan Ajah</title>

    <link
        rel="icon"
        type="image/webp"
        href="/assets/images/logo.webp"
    >

    <link rel="stylesheet" href="/assets/css/style.css">
    <link
        rel="stylesheet"
        href="/assets/css/buyer-dashboard.css"
    >

    <script
        type="module"
        src="/assets/js/buyer-dashboard.js"
    ></script>
</head>

<body>
    <header class="site-header">
        <div class="container header-inner">
            <a
                class="brand"
                href="/"
                aria-label="Kembali ke Pesan Ajah"
            >
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

            <a class="text-link" href="/">
                ← Kembali belanja
            </a>
        </div>
    </header>

    <main class="buyer-page">
        <div class="container buyer-container">
            <section class="buyer-intro">
                <span class="eyebrow eyebrow-plain">
                    DASHBOARD PEMBELI
                </span>

                <h1>Pesanan Saya</h1>

                <p>
                    Pantau status pesanan yang pernah kamu buat
                    di Pesan Ajah.
                </p>
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
                aria-live="polite"
            ></section>

            <section
                class="buyer-empty"
                id="buyer-empty"
                hidden
            >
                <div class="buyer-empty-icon" aria-hidden="true">
                    PA
                </div>

                <h2>Belum ada pesanan tersimpan</h2>

                <p>
                    Pesanan yang kamu buat melalui browser ini
                    akan muncul di sini.
                </p>

                <a class="button button-primary" href="/">
                    Mulai belanja
                </a>
            </section>

            <p class="buyer-security-note">
                Riwayat pesanan pada halaman ini bergantung pada token
                yang tersimpan di browser. Jika data browser dihapus
                atau kamu berpindah perangkat, pesanan mungkin tidak
                muncul lagi di halaman ini.
            </p>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p>Marketplace warung lokal Banyukuning.</p>

            <small>
                © <span id="buyer-current-year"></span> Pesan Ajah
            </small>
        </div>
    </footer>
</body>
</html>