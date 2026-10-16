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
    
    <link
        rel="stylesheet"
        href="/assets/css/store.css"
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

    <?php
    $isStorePage = false;
    require __DIR__ . '/_header.php';
    ?>

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

                <!-- PESAN AJAH: HOMEPAGE BANNER -->
                <?php
                $homepageBanners = [
                    [
                        'image' => '/assets/images/banner_1.webp',
                        'alt' => 'Belanja kebutuhan harian di Pesan Ajah',
                    ],
                
                    // Tambahkan banner berikutnya di sini:
                    // [
                    //     'image' => '/assets/images/banner_2.webp',
                    //     'alt' => 'Promo belanja Pesan Ajah',
                    // ],
                ];
                
                $homepageBanners = array_values(array_filter(
                    $homepageBanners,
                    static fn (array $banner): bool =>
                        !empty($banner['image'])
                ));
                ?>
                
                <section
                    class="homepage-banner<?= count($homepageBanners) > 1 ? ' has-multiple-slides' : '' ?>"
                    id="homepage-banner"
                    aria-label="Banner Pesan Ajah"
                    data-banner-carousel
                >
                    <div class="homepage-banner__track" data-banner-track>
                        <?php foreach ($homepageBanners as $index => $banner): ?>
                            <div
                                class="homepage-banner__slide"
                                data-banner-slide
                                aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"
                            >
                                <img
                                    class="homepage-banner__image"
                                    src="<?= htmlspecialchars(
                                        $banner['image'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $banner['alt'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    width="1200"
                                    height="400"
                                    <?= $index === 0
                                        ? 'fetchpriority="high" loading="eager"'
                                        : 'loading="lazy"' ?>
                                    decoding="async"
                                >
                            </div>
                        <?php endforeach; ?>
                    </div>
                
                    <?php if (count($homepageBanners) > 1): ?>
                        <button
                            class="homepage-banner__control homepage-banner__control--prev"
                            type="button"
                            aria-label="Banner sebelumnya"
                            data-banner-prev
                        >
                            <span aria-hidden="true">‹</span>
                        </button>
                
                        <button
                            class="homepage-banner__control homepage-banner__control--next"
                            type="button"
                            aria-label="Banner berikutnya"
                            data-banner-next
                        >
                            <span aria-hidden="true">›</span>
                        </button>
                
                        <div
                            class="homepage-banner__dots"
                            aria-label="Pilih banner"
                            data-banner-dots
                        ></div>
                    <?php endif; ?>
                </section>

            </div>
        </section>

        <!-- =====================================================
             KATEGORI BELANJA
             ===================================================== -->
        
        <section
            class="container section"
            id="categories"
            aria-labelledby="category-title"
        >
            <div class="section-heading">
                <div>
                    <span class="eyebrow eyebrow-plain">
                        MULAI DARI SINI
                    </span>
        
                    <h2 id="category-title">
                        Kategori belanja
                    </h2>
                </div>
        
                <p>
                    Pilih kategori yang kamu butuhkan.
                </p>
            </div>
        
            <!--
                Kategori dibuat secara dinamis oleh catalog.js.
                Jangan menambahkan tombol kategori statis di luar
                container ini agar tidak terjadi duplikasi.
            -->
            <div
                class="category-grid"
                id="category-list"
                aria-label="Kategori produk"
            >
                <button
                    class="category-card active"
                    type="button"
                    data-category="Semua"
                    aria-pressed="true"
                >
                    <span aria-hidden="true">⌂</span>
                    Semua
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

    <?php require __DIR__ . '/_footer.php'; ?>

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