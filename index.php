<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Pesan Ajah — Marketplace Warung Lokal';
$pageDescription = 'Belanja kebutuhan harian dari warung lokal dengan mudah.';

$pageMeta = [
    'theme_color' => '#176b45',
    'color_scheme' => 'light',
    'application_name' => 'Pesan Ajah',
    'robots' => 'index, follow',
];

$pageOpenGraph = [
    'type' => 'website',
    'locale' => 'id_ID',
    'site_name' => 'Pesan Ajah',
    'title' => $pageTitle,
    'description' => $pageDescription,
];

$productCategories = require APP_ROOT . '/config/categories.php';

?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="theme-color" content="<?= e($pageMeta['theme_color']) ?>">
    <meta name="color-scheme" content="<?= e($pageMeta['color_scheme']) ?>">
    <meta name="application-name" content="<?= e($pageMeta['application_name']) ?>">

    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="robots" content="<?= e($pageMeta['robots']) ?>">

    <meta property="og:type" content="<?= e($pageOpenGraph['type']) ?>">
    <meta property="og:locale" content="<?= e($pageOpenGraph['locale']) ?>">
    <meta property="og:site_name" content="<?= e($pageOpenGraph['site_name']) ?>">
    <meta property="og:title" content="<?= e($pageOpenGraph['title']) ?>">
    <meta property="og:description" content="<?= e($pageOpenGraph['description']) ?>">

    <title><?= e($pageTitle) ?></title>

    <link rel="icon" type="image/webp" href="/assets/images/logo.webp">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap"
    >

    <link
        rel="stylesheet"
        href="/assets/css/style.css?v=<?= asset_version('assets/css/style.css') ?>"
    >

    <script
        type="module"
        src="/assets/js/main.js?v=<?= asset_version('assets/js/main.js') ?>"
    ></script>

    <script
        async
        src="https://www.googletagmanager.com/gtm.js?id=GTM-5WJ727NJ"
    ></script>

    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());
    </script>
</head>

<body
    data-marketplace-page="true"
    data-page="index"
>

    <?php
    $isStorePage = false;
    require __DIR__ . '/_header.php';
    ?>

    <main>

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

                    <div
                        class="search-bar"
                        id="search-form"
                        role="search"
                    >
                        <span class="search-bar__icon" aria-hidden="true">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                            </svg>
                        </span>
                    
                        <input
                            id="search-input"
                            type="text"
                            placeholder="Tulis item yang kamu cari...."
                            aria-label="Cari produk atau warung"
                            autocomplete="off"
                        >
                    
                        <button
                            class="search-bar__clear"
                            id="clear-search"
                            type="button"
                            aria-label="Hapus pencarian"
                            hidden
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            >
                                <path d="M18 6 6 18"/>
                                <path d="m6 6 12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

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

        <section
            class="container section"
            id="categories"
            aria-labelledby="category-title"
        >

            <div class="section-heading">
                <div>
                    <h2 id="category-title">Kategori</h2>
                </div>
            </div>

            <div
                class="skeleton-category-grid"
                id="category-loading"
                aria-hidden="true"
            >
                <span class="skeleton-category skeleton-category--all">
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
                        <rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/>
                        <rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/>
                        <rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/>
                        <rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>
                    </svg>
                </span>

                <?php for ($i = 0; $i < 5; $i++): ?>
                    <span class="skeleton-category"></span>
                <?php endfor; ?>
            </div>

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
            ></div>
            
        </section>

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

                        <div class="catalog-skeleton__profile">

                            <span class="skeleton-avatar"></span>

                            <div class="catalog-skeleton__identity">
                                <span class="skeleton-line skeleton-line--name"></span>
                                <span class="skeleton-line skeleton-line--owner"></span>
                            </div>

                            <span class="skeleton-status"></span>

                        </div>

                        <div class="catalog-skeleton__products">

                            <?php for ($product = 0; $product < 4; $product++): ?>
                                <div class="catalog-skeleton__product">

                                    <span class="skeleton-product-image"></span>

                                    <div class="catalog-skeleton__product-info">

                                        <span class="skeleton-line skeleton-line--product"></span>
                                        <span class="skeleton-line skeleton-line--variant"></span>
                                    
                                        <span class="skeleton-stock"></span>

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

    <?php require __DIR__ . '/_footer.php'; ?>

    <?php require __DIR__ . '/partials/cart.php'; ?>

    <div
        class="toast"
        id="toast"
        role="status"
        aria-live="polite"
    ></div>

    <script
        async
        src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6568186199598824"
        crossorigin="anonymous"
    ></script>

</body>
</html>