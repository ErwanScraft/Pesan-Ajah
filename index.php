<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PESAN AJAH — MARKETPLACE HOMEPAGE
|--------------------------------------------------------------------------
|
| Beranda marketplace.
| Katalog diambil melalui /api/catalog.php.
| Keranjang menggunakan partials/cart.php.
|
*/

require_once __DIR__ . '/app/bootstrap.php';

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
    <meta name="color-scheme" content="light">
    <meta name="application-name" content="Pesan Ajah">

    <meta
        name="description"
        content="Pesan Ajah adalah marketplace warung lokal Banyukuning. Temukan produk kebutuhan harian, pesan dari warung sekitar, lalu pilih layanan antar atau ambil sendiri."
    >

    <meta name="robots" content="index, follow">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="Pesan Ajah">

    <meta
        property="og:title"
        content="Pesan Ajah — Marketplace Warung Banyukuning"
    >

    <meta
        property="og:description"
        content="Belanja kebutuhan harian dari warung lokal Banyukuning dengan mudah."
    >

    <title>Pesan Ajah — Marketplace Warung Banyukuning</title>

    <link
        rel="icon"
        type="image/webp"
        href="/assets/images/logo.webp"
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="/assets/css/style.css"
    >

    <script
        type="module"
        src="/assets/js/main.js"
    ></script>
</head>

<body data-marketplace-page="true">

    <!-- =====================================================
         HEADER
         ===================================================== -->

    <header class="site-header">
        <div class="container header-inner">

            <a
                class="brand"
                href="/"
                aria-label="Pesan Ajah — Beranda"
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

            <div class="location">
                <span class="pin" aria-hidden="true">⌖</span>

                <span>
                    <small>Wilayah layanan</small>
                    <strong>Banyukuning</strong>
                </span>
            </div>

            <div class="header-actions">

                <a
                    class="text-link dashboard-link"
                    href="/user/"
                >
                    Dashboard Buyer
                </a>

                <button
                    class="button button-primary cart-trigger"
                    id="cart-trigger"
                    type="button"
                    aria-label="Buka keranjang belanja"
                    title="Keranjang belanja"
                >
                    <svg
                        class="cart-icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H6"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <circle
                            cx="10"
                            cy="20"
                            r="1.2"
                            fill="currentColor"
                        />

                        <circle
                            cx="18"
                            cy="20"
                            r="1.2"
                            fill="currentColor"
                        />
                    </svg>

                    <span class="count" id="cart-count">0</span>
                </button>

            </div>
        </div>
    </header>

    <main>

        <!-- =================================================
             HERO
             ================================================= -->

        <section class="hero">
            <div class="container hero-layout">

                <div class="hero-copy">

                    <span class="eyebrow">
                        <span class="dot"></span>
                        Marketplace lokal Banyukuning
                    </span>

                    <h1>
                        Belanja kebutuhan harian,
                        <span>tanpa ribet.</span>
                    </h1>

                    <p>
                        Temukan kebutuhan dari warung sekitar.
                        Pilih produk, masukkan keranjang, dan
                        siapkan pesananmu dengan mudah.
                    </p>

                    <form
                        class="search-bar"
                        id="search-form"
                        role="search"
                    >
                        <span aria-hidden="true">⌕</span>

                        <input
                            id="search-input"
                            type="search"
                            placeholder="Cari produk atau nama warung..."
                            aria-label="Cari produk atau warung"
                        >

                        <button
                            class="button button-primary"
                            type="submit"
                        >
                            Cari
                        </button>
                    </form>

                    <div class="hero-points">
                        <span>✓ Warung lokal</span>
                        <span>✓ Pesan praktis</span>
                        <span>✓ Antar atau ambil sendiri</span>
                    </div>

                </div>

                <div
                    class="hero-card"
                    aria-label="Ilustrasi belanja kebutuhan harian"
                >
                    <div class="hero-circle"></div>

                    <div class="mini-receipt">
                        <span>DAFTAR BELANJA</span>
                        <i></i>
                        <i></i>
                        <i></i>
                        <b>✓ Siap dipesan</b>
                    </div>

                    <div class="grocery-bag">
                        <span class="bag-handle"></span>
                        <strong>P.</strong>
                        <small>Belanja lokal</small>
                    </div>

                    <div class="floating-label">
                        <span class="check-mark">✓</span>

                        <span>
                            <b>Warung sekitar</b>
                            <small>Dukung usaha lokal</small>
                        </span>
                    </div>
                </div>

            </div>
        </section>

        <!-- =================================================
             KATEGORI
             ================================================= -->

        <section class="container section">

            <div class="section-heading">
                <div>
                    <span class="eyebrow eyebrow-plain">
                        MULAI DARI SINI
                    </span>

                    <h2>Kategori belanja</h2>
                </div>

                <p>Pilih kategori yang kamu butuhkan.</p>
            </div>

            <div class="category-grid" id="category-list">

                <button
                    class="category-card active"
                    type="button"
                    data-category="Semua"
                    aria-pressed="true"
                >
                    <span>⌂</span>
                    Semua
                </button>

                <button
                    class="category-card"
                    type="button"
                    data-category="Sembako"
                    aria-pressed="false"
                >
                    <span>🌾</span>
                    Sembako
                </button>

                <button
                    class="category-card"
                    type="button"
                    data-category="Minuman"
                    aria-pressed="false"
                >
                    <span>🥤</span>
                    Minuman
                </button>

                <button
                    class="category-card"
                    type="button"
                    data-category="Makanan"
                    aria-pressed="false"
                >
                    <span>🍜</span>
                    Makanan
                </button>

                <button
                    class="category-card"
                    type="button"
                    data-category="Kebutuhan Rumah"
                    aria-pressed="false"
                >
                    <span>🧹</span>
                    Kebutuhan rumah
                </button>

                <button
                    class="category-card"
                    type="button"
                    data-category="Lainnya"
                    aria-pressed="false"
                >
                    <span>🛍️</span>
                    Lainnya
                </button>

            </div>
        </section>

        <!-- =================================================
             KATALOG PRODUK
             ================================================= -->

        <section
            class="container section products-section"
            id="products"
        >

            <div class="section-heading">
                <div>
                    <span class="eyebrow eyebrow-plain">
                        PILIHAN UNTUKMU
                    </span>

                    <h2>Produk warung</h2>

                    <p class="muted">
                        Temukan produk yang tersedia dari warung lokal.
                    </p>
                </div>

                <span class="muted" id="product-total">
                    Memuat produk...
                </span>
            </div>

            <div
                class="product-grid"
                id="product-grid"
                aria-live="polite"
            ></div>

            <div
                class="empty-state"
                id="empty-state"
                hidden
            >
                <h3>Belum ada produk</h3>

                <p>
                    Produk akan muncul di sini setelah warung
                    menambahkan katalog.
                </p>

                <button
                    class="button button-outline"
                    id="reset-search"
                    type="button"
                >
                    Lihat semua produk
                </button>
            </div>

        </section>

        <!-- =================================================
             PROMOSI PENJUAL
             ================================================= -->

        <section class="container seller-promo">

            <div class="promo-icon" aria-hidden="true">
                ⌂
            </div>

            <div class="promo-copy">

                <span class="eyebrow eyebrow-plain">
                    UNTUK PEMILIK WARUNG
                </span>

                <h2>
                    Warungmu bisa ditemukan lebih banyak warga.
                </h2>

                <p>
                    Kelola produk, stok, status warung, dan pesanan
                    dari dashboard sederhana.
                </p>

            </div>

            <a
                class="button button-primary"
                href="/warung/login.php"
            >
                Dashboard Warung
                <span aria-hidden="true">→</span>
            </a>

        </section>

    </main>

    <!-- =====================================================
         FOOTER
         ===================================================== -->

    <footer class="site-footer">

        <div class="container footer-inner">

            <a
                class="brand"
                href="/"
                aria-label="Pesan Ajah — Beranda"
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

            <p>Marketplace warung lokal Banyukuning.</p>

            <small>
                © <span id="current-year"></span> Pesan Ajah
            </small>

        </div>

    </footer>

    <!-- =====================================================
         KERANJANG BERSAMA
         ===================================================== -->

    <?php require __DIR__ . '/partials/cart.php'; ?>

    <!-- =====================================================
         NOTIFIKASI
         ===================================================== -->

    <div
        class="toast"
        id="toast"
        role="status"
        aria-live="polite"
    ></div>

</body>
</html>