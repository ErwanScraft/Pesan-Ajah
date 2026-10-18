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

$homepageBanners = [
    [
        'image' => '/assets/images/banner_1.webp',
        'alt' => 'Belanja kebutuhan harian di Pesan Ajah',
        'url' => '',
    ],
    [
        'image' => '/assets/images/banner_2.webp',
        'alt' => 'Dasboard Warung',
        'url' => 'https://www.pesanajah.web.id/warung/login.php',
    ],
    [
        'image' => '/assets/images/banner_3.webp',
        'alt' => 'Warung Teras Rambutan',
        'url' => 'https://www.pesanajah.web.id/warung/terasrambutan',
    ],
];

$homepageBanners = array_values(array_filter(
    $homepageBanners,
    static fn (array $banner): bool => !empty($banner['image'])
));

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

<body data-marketplace-page="true">

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

                <!-- BANNER -->
                <section
                    class="homepage-banner<?= count($homepageBanners) > 1 ? ' has-multiple-slides' : '' ?>"
                    id="homepage-banner"
                    aria-label="Banner Pesan Ajah"
                    data-banner-carousel
                >
                    <div
                        class="homepage-banner__track"
                        data-banner-track
                    >
                        <?php foreach ($homepageBanners as $index => $banner): ?>
                            <div
                                class="homepage-banner__slide"
                                data-banner-slide
                                aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"
                            >
                                <?php if (!empty($banner['url'])): ?>
                                    <a
                                        href="<?= htmlspecialchars($banner['url'], ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="<?= htmlspecialchars($banner['alt'], ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <img
                                            class="homepage-banner__image"
                                            src="<?= htmlspecialchars($banner['image'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt="<?= htmlspecialchars($banner['alt'], ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                    </a>
                                <?php else: ?>
                                    <img
                                        class="homepage-banner__image"
                                        src="<?= htmlspecialchars($banner['image'], ENT_QUOTES, 'UTF-8') ?>"
                                        alt="<?= htmlspecialchars($banner['alt'], ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
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

        <!-- KATALOG PRODUK -->
        <section
            class="container section products-section"
            id="products"
        >
            <div class="section-heading">
                <div>
                    <h2>Warung</h2>
                </div>
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
