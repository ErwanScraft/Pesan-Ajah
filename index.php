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

$productCategories = require APP_ROOT . '/config/categories.php';

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
        content="Belanja kebutuhan harian dari warung lokal Banyukuning dengan mudah."
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

<body
    data-marketplace-page="true"
    data-page="index"
    data-catalog-loading="true"
>

    <!-- HEADER -->
    <?php
    $isStorePage = false;
    require __DIR__ . '/_header.php';
    ?>

    <main>

        <!-- HERO -->
        <section class="hero">
            <div class="container hero-layout">

                <div class="hero-copy">

                    <h1>
                        Belanja kebutuhan harian,
                        <span>Lebih Praktis.</span>
                    </h1>

                    <p>
                        Belanja kebutuhan harian dari warung sekitar
                        dengan mudah.
                    </p>

                    <form
                        class="search-bar"
                        id="search-form"
                        role="search"
                    >

                        <input
                            id="search-input"
                            type="search"
                            placeholder="Cari produk atau nama warung."
                            aria-label="Cari produk atau warung"
                        >

                        <button
                            class="button button-primary"
                            type="submit"
                        >
                            Cari
                        </button>
                    </form>

                </div>

                <!-- BANNER BERANDA -->
                <section
                    class="homepage-banner"
                    id="homepage-banner"
                    aria-label="Banner Pesan Ajah"
                    data-banner-carousel
                >
                    <div
                        class="homepage-banner__track"
                        data-banner-track
                    ></div>
                </section>

            </div>
        </section>

        <!-- KATEGORI -->
        <section
            class="container section"
            id="categories"
            aria-labelledby="category-title"
        >

            <div class="section-heading">
                <div>
                    <h2 id="category-title">
                        Kategori
                    </h2>
                </div>
            </div>

            <!-- SKELETON KATEGORI -->
            <div
                class="skeleton-category-grid"
                id="category-loading"
                aria-hidden="true"
            >
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <span class="skeleton-category"></span>
                <?php endfor; ?>
            </div>

            <!-- DAFTAR KATEGORI ASLI -->
            <div
                class="category-grid"
                id="category-list"
                aria-label="Kategori produk"
                data-category-order="<?= e(json_encode(
                    $productCategories,
                    JSON_UNESCAPED_UNICODE
                    | JSON_HEX_APOS
                    | JSON_HEX_QUOT
                )) ?>"
            >

                <button
                    class="category-card category-all-button active"
                    type="button"
                    data-category="Semua"
                    aria-label="Tampilkan semua produk"
                    title="Semua kategori"
                    aria-pressed="true"
                >
                    <span
                        class="category-all-icon"
                        aria-hidden="true"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect
                                x="3.5"
                                y="3.5"
                                width="7"
                                height="7"
                                rx="1.5"
                            />
                            <rect
                                x="13.5"
                                y="3.5"
                                width="7"
                                height="7"
                                rx="1.5"
                            />
                            <rect
                                x="3.5"
                                y="13.5"
                                width="7"
                                height="7"
                                rx="1.5"
                            />
                            <rect
                                x="13.5"
                                y="13.5"
                                width="7"
                                height="7"
                                rx="1.5"
                            />
                        </svg>
                    </span>
                </button>

            </div>
        </section>

        <!-- KATALOG WARUNG -->
        <section
            class="container section products-section"
            id="products"
        >

            <div class="section-heading">
                <div>
                    <h2>Warung</h2>
                </div>
            </div>

            <!-- SKELETON KATALOG -->
            <div
                class="catalog-skeleton"
                id="catalog-skeleton"
                role="status"
                aria-label="Memuat katalog warung"
            >

                <span class="sr-only">
                    Memuat daftar warung...
                </span>

                <?php for ($store = 0; $store < 2; $store++): ?>
                    <section
                        class="catalog-skeleton__store"
                        aria-hidden="true"
                    >

                        <!-- PROFIL WARUNG -->
                        <div class="catalog-skeleton__profile">

                            <span class="skeleton-avatar"></span>

                            <div class="catalog-skeleton__identity">
                                <span class="skeleton-line skeleton-line--name"></span>
                                <span class="skeleton-line skeleton-line--owner"></span>
                            </div>

                            <span class="skeleton-status"></span>

                        </div>

                        <!-- PRODUK WARUNG -->
                        <div class="catalog-skeleton__products">

                            <?php for ($product = 0; $product < 4; $product++): ?>
                                <div class="catalog-skeleton__product">

                                    <span class="skeleton-product-image"></span>

                                    <div class="catalog-skeleton__product-info">

                                        <span class="skeleton-line skeleton-line--product"></span>

                                        <span class="skeleton-line skeleton-line--variant"></span>

                                        <div class="catalog-skeleton__product-bottom">

                                            <span class="skeleton-line skeleton-line--price"></span>

                                            <span class="skeleton-add-button"></span>

                                        </div>
                                    </div>

                                </div>
                            <?php endfor; ?>

                        </div>
                    </section>
                <?php endfor; ?>

            </div>

            <!-- KATALOG ASLI: DIISI OLEH main.js -->
            <div
                class="product-grid"
                id="product-grid"
                aria-live="polite"
            ></div>

            <!-- KONDISI KATALOG KOSONG -->
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

    </main>

    <!-- FOOTER -->
    <?php require __DIR__ . '/_footer.php'; ?>

    <!-- KERANJANG -->
    <?php require __DIR__ . '/partials/cart.php'; ?>

    <!-- NOTIFIKASI -->
    <div
        class="toast"
        id="toast"
        role="status"
        aria-live="polite"
    ></div>

</body>
</html>
