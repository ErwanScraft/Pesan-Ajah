<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PESAN AJAH — PUBLIC STORE PAGE
|--------------------------------------------------------------------------
|
| URL:
| /warung/{slug}
|
| Routing ditangani oleh .htaccess:
| warung.php?slug={slug}
|
*/

require_once __DIR__ . '/app/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Ambil dan validasi slug
|--------------------------------------------------------------------------
*/

$slug = strtolower(
    trim((string) ($_GET['slug'] ?? ''))
);

if (
    $slug === ''
    || !is_valid_store_slug($slug)
) {
    http_response_code(404);
    exit('Warung tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Ambil profil warung
|--------------------------------------------------------------------------
*/

$storeStmt = database()->prepare(
    'SELECT
        s.id,
        s.name,
        s.slug,
        s.owner,
        s.phone,
        s.address,
        s.description,
        s.icon_image,
        s.is_open,
        s.free_delivery
    FROM stores s
    INNER JOIN users u
        ON u.id = s.user_id
    WHERE s.slug = :slug
      AND s.is_active = 1
      AND u.is_active = 1
      AND u.role = :role
    LIMIT 1'
);

$storeStmt->execute([
    'slug' => $slug,
    'role' => 'warung',
]);

$store = $storeStmt->fetch();

if (!$store) {
    http_response_code(404);
    exit('Warung tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Normalisasi data profil
|--------------------------------------------------------------------------
*/

$storeId = (int) $store['id'];

$storeName = trim(
    (string) ($store['name'] ?? 'Warung')
);

$storeOwner = trim(
    (string) ($store['owner'] ?? '')
);

$storeDescription = trim(
    (string) ($store['description'] ?? '')
);

$storeAddress = trim(
    (string) ($store['address'] ?? '')
);

$storePhone = trim(
    (string) ($store['phone'] ?? '')
);

$isStoreOpen = (bool) $store['is_open'];

$hasFreeDelivery = (bool) $store['free_delivery'];

/*
|--------------------------------------------------------------------------
| Normalisasi URL gambar ikon toko
|--------------------------------------------------------------------------
*/

function public_store_image_url(mixed $image): ?string
{
    if (!is_string($image)) {
        return null;
    }

    $image = trim($image);

    if ($image === '') {
        return null;
    }

    // Pertahankan URL gambar eksternal HTTP/HTTPS.
    if (preg_match('~^https?://~i', $image)) {
        return $image;
    }

    // Tolak skema URL selain HTTP/HTTPS.
    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $image)) {
        return null;
    }

    $image = str_replace('\\', '/', $image);

    $image = preg_replace(
        '~^(?:\./)+~',
        '',
        $image
    );

    $image = ltrim($image, '/');

    // Tolak path kosong atau path traversal.
    if (
        $image === ''
        || in_array('..', explode('/', $image), true)
    ) {
        return null;
    }

    return '/' . $image;
}

$storeIconUrl = public_store_image_url(
    $store['icon_image'] ?? null
);

/*
|--------------------------------------------------------------------------
| Ambil daftar kategori untuk navigasi
|--------------------------------------------------------------------------
|
| Produk sebenarnya dimuat dari /api/catalog.php.
| Query ini hanya menyediakan daftar kategori awal.
|
*/

$categoryStmt = database()->prepare(
    'SELECT DISTINCT category
     FROM products
     WHERE store_id = :store_id
       AND is_available = 1
     ORDER BY category ASC'
);

$categoryStmt->execute([
    'store_id' => $storeId,
]);

$categories = [];

foreach ($categoryStmt->fetchAll() as $row) {
    $category = trim(
        (string) ($row['category'] ?? '')
    );

    if ($category === '') {
        $category = 'Lainnya';
    }

    if (!in_array($category, $categories, true)) {
        $categories[] = $category;
    }
}

sort($categories, SORT_NATURAL | SORT_FLAG_CASE);

/*
|--------------------------------------------------------------------------
| Inisial ikon toko jika gambar tidak tersedia
|--------------------------------------------------------------------------
*/

$storeInitial = 'W';

if ($storeName !== '') {
    $storeInitial = function_exists('mb_substr')
        ? mb_strtoupper(
            mb_substr($storeName, 0, 1, 'UTF-8'),
            'UTF-8'
        )
        : strtoupper(substr($storeName, 0, 1));
}

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

    <meta
        name="description"
        content="<?= e(
            $storeDescription !== ''
                ? $storeDescription
                : 'Pesan produk dari ' . $storeName . ' melalui Pesan Ajah.'
        ) ?>"
    >

    <meta name="robots" content="index, follow">

    <meta property="og:type" content="website">

    <meta property="og:site_name" content="Pesan Ajah">

    <meta
        property="og:title"
        content="<?= e($storeName) ?> — Pesan Ajah"
    >

    <meta
        property="og:description"
        content="<?= e(
            $storeDescription !== ''
                ? $storeDescription
                : 'Temukan produk pilihan dari ' . $storeName . '.'
        ) ?>"
    >

    <title>
        <?= e($storeName) ?> — Pesan Ajah
    </title>

    <link
        rel="icon"
        type="image/webp"
        href="/assets/images/logo.webp"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

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

    <style>
        /* =================================================
           PUBLIC STORE — LAYOUT
           ================================================= */

        .store-page {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            padding-bottom: 48px;
        }

        .store-header-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            min-width: 0;
        }

        /* =================================================
           STORE PROFILE
           ================================================= */

        .store-profile {
            display: flex;
            align-items: flex-start;
            gap: 24px;
            margin: 28px 0;
            padding: 28px;
            border: 1px solid #e3e9e5;
            border-radius: 18px;
            background: #fff;
        }

        .store-profile-brand {
            flex: 0 0 88px;
        }

        .store-profile-icon {
            display: block;
            width: 88px;
            height: 88px;
            overflow: hidden;
            border: 1px solid #e3e9e5;
            border-radius: 18px;
            background: #f5f7f2;
            object-fit: cover;
            object-position: center;
        }

        .store-profile-icon-placeholder {
            display: grid;
            place-items: center;
            color: #176b45;
            font-size: 34px;
            font-weight: 800;
        }

        .store-profile-content {
            flex: 1;
            min-width: 0;
        }

        .store-profile-label {
            display: inline-block;
            margin-bottom: 8px;
            color: #176b45;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .store-profile-heading h1 {
            margin: 0;
            overflow-wrap: anywhere;
            font-size: clamp(24px, 4vw, 32px);
            line-height: 1.25;
        }

        .store-owner {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 10px;
            color: #68736b;
            font-size: 13px;
        }

        .store-owner svg {
            flex: 0 0 18px;
            width: 18px;
            height: 18px;
        }

        .store-owner strong {
            color: #27342b;
        }

        .store-profile-description {
            margin: 14px 0;
            color: #566158;
            font-size: 14px;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .store-profile-description-muted {
            color: #7a837c;
        }

        /* =================================================
           STORE META
           ================================================= */

        .store-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
        }

        .store-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 32px;
            max-width: 100%;
            padding: 7px 10px;
            border: 1px solid #e3e9e5;
            border-radius: 9px;
            background: #fff;
            color: #465149;
            font-size: 12px;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .store-chip svg {
            flex: 0 0 17px;
            width: 17px;
            height: 17px;
        }

        .store-chip.is-open {
            border-color: #cce8d5;
            background: #f0faf3;
            color: #176b45;
        }

        .store-chip.is-closed {
            border-color: #f1d7d7;
            background: #fff5f5;
            color: #a33c3c;
        }

        .store-status-dot {
            width: 7px;
            height: 7px;
            flex: 0 0 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .store-chip-delivery {
            color: #176b45;
        }

        .store-profile-contact {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 16px;
            font-size: 13px;
        }

        .store-contact-label {
            color: #68736b;
        }

        .store-contact-value {
            color: #27342b;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        /* =================================================
           SEARCH
           ================================================= */

        .store-search-section {
            margin-bottom: 22px;
        }

        .store-search-form {
            display: flex;
            gap: 10px;
            width: 100%;
            padding: 8px;
            border: 1px solid #e3e9e5;
            border-radius: 14px;
            background: #fff;
        }

        .store-search-form input {
            flex: 1;
            width: 100%;
            min-width: 0;
            padding: 10px 12px;
            border: 0;
            outline: 0;
            background: transparent;
            color: #27342b;
            font: inherit;
        }

        .store-search-form input:focus-visible {
            outline: 2px solid #176b45;
            outline-offset: -2px;
            border-radius: 8px;
        }

        /* =================================================
           CATEGORY NAVIGATION
           ================================================= */

        .store-category-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            padding-bottom: 6px;
            overflow-x: auto;
            scrollbar-width: thin;
        }

        .store-category-nav a {
            flex: 0 0 auto;
            padding: 9px 14px;
            border: 1px solid #e3e9e5;
            border-radius: 24px;
            background: #fff;
            color: #39443c;
            font-size: 13px;
            text-decoration: none;
            white-space: nowrap;
        }

        .store-category-nav a.active {
            border-color: #176b45;
            background: #176b45;
            color: #fff;
        }

        /* =================================================
           PRODUCTS
           ================================================= */

        .store-page .products-section {
            min-width: 0;
        }

        .store-page #product-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 24px;
            min-width: 0;
        }

        .store-page .store-category-section {
            min-width: 0;
            scroll-margin-top: 100px;
        }

        .store-page .store-category-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .store-page .store-category-heading h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.4;
        }

        .store-page .store-category-count {
            flex-shrink: 0;
            color: #68736b;
            font-size: 12px;
        }

        .store-page .store-category-products {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            min-width: 0;
        }

        .store-page .product-card {
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            border: 1px solid #e3e9e5;
            border-radius: 14px;
            background: #fff;
        }

        .store-page .product-image {
            position: relative;
            display: grid;
            width: 100%;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            place-items: center;
            background: #f5f7f2;
        }

        .store-page .product-photo {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
        }

        .store-page .product-image-placeholder {
            padding: 12px;
            color: #707970;
            font-size: 12px;
            text-align: center;
        }

        .store-page .product-tag {
            position: absolute;
            top: 8px;
            left: 8px;
            padding: 5px 8px;
            border-radius: 7px;
            background: #176b45;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
        }

        .store-page .product-info {
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
            padding: 12px;
        }

        .store-page .product-store {
            color: #68736b;
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .store-page .product-name {
            margin: 0;
            overflow-wrap: anywhere;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
        }

        .store-page .product-unit {
            color: #68736b;
            font-size: 12px;
        }

        .store-page .product-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: auto;
        }

        .store-page .product-price {
            min-width: 0;
            color: #176b45;
            font-size: 14px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .store-page .add-button {
            display: inline-grid;
            flex: 0 0 36px;
            width: 36px;
            height: 36px;
            place-items: center;
            border: 0;
            border-radius: 10px;
            background: #176b45;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
        }

        .store-page .add-button:disabled {
            background: #e7ebe8;
            color: #858d87;
            cursor: not-allowed;
        }

        .store-page .store-product-store-link {
            color: inherit;
            text-decoration: none;
        }

        .store-page .store-product-store-link:hover {
            color: #176b45;
            text-decoration: underline;
        }

        .store-page #product-grid[hidden] {
            display: none !important;
        }

        .store-page #empty-state {
            margin: 24px 0;
            padding: 32px 20px;
            border: 1px dashed #d9e2db;
            border-radius: 16px;
            text-align: center;
        }

        /* =================================================
           RESPONSIVE
           ================================================= */

        @media (max-width: 900px) {
            .store-page .store-category-products {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
            }
        }

        @media (max-width: 600px) {
            .store-page {
                width: min(100% - 24px, 1120px);
            }

            .store-profile {
                gap: 14px;
                margin-top: 18px;
                padding: 18px;
            }

            .store-profile-brand {
                flex-basis: 64px;
            }

            .store-profile-icon {
                width: 64px;
                height: 64px;
                border-radius: 14px;
            }

            .store-profile-icon-placeholder {
                font-size: 25px;
            }

            .store-profile-heading h1 {
                font-size: 22px;
            }

            .store-owner {
                align-items: flex-start;
                font-size: 12px;
            }

            .store-profile-description {
                font-size: 13px;
            }

            .store-page .store-category-products {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .store-page .product-info {
                padding: 10px;
            }

            .store-page .product-name {
                font-size: 13px;
            }

            .store-page .product-price {
                font-size: 13px;
            }

            .store-page .add-button {
                flex-basis: 34px;
                width: 34px;
                height: 34px;
            }

            .store-header-actions {
                gap: 8px;
            }
        }

        @media (max-width: 380px) {
            .store-profile {
                gap: 12px;
                padding: 14px;
            }

            .store-profile-brand {
                flex-basis: 52px;
            }

            .store-profile-icon {
                width: 52px;
                height: 52px;
            }

            .store-profile-heading h1 {
                font-size: 19px;
            }

            .store-page .store-category-products {
                gap: 8px;
            }

            .store-page .product-info {
                padding: 8px;
            }
        }
    </style>
</head>

<body
    class="store-page-body"
    data-store-page="true"
    data-store-id="<?= $storeId ?>"
>

    <!-- =====================================================
         HEADER
         ===================================================== -->

    <?php
    $isStorePage = true;
    require __DIR__ . '/_header.php';
    ?>

    <main class="store-page">

        <!-- =================================================
             PROFIL WARUNG
             ================================================= -->

        <section
            class="store-profile"
            aria-labelledby="store-profile-name"
        >

            <div class="store-profile-brand">

                <?php if ($storeIconUrl !== null): ?>

                    <img
                        class="store-profile-icon"
                        src="<?= e($storeIconUrl) ?>"
                        alt="Logo <?= e($storeName) ?>"
                        width="88"
                        height="88"
                    >

                <?php else: ?>

                    <div
                        class="store-profile-icon store-profile-icon-placeholder"
                        aria-hidden="true"
                    >
                        <?= e($storeInitial) ?>
                    </div>

                <?php endif; ?>

            </div>

            <div class="store-profile-content">

                <div class="store-profile-heading">

                    <span class="store-profile-label">
                        WARUNG LOKAL
                    </span>

                    <h1 id="store-profile-name">
                        <?= e($storeName) ?>
                    </h1>

                    <?php if ($storeOwner !== ''): ?>

                        <div class="store-owner">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M20 21a8 8 0 0 0-16 0"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />

                                <circle
                                    cx="12"
                                    cy="7"
                                    r="4"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                            </svg>

                            <span>
                                Pemilik:
                                <strong><?= e($storeOwner) ?></strong>
                            </span>

                        </div>

                    <?php endif; ?>

                </div>

                <?php if ($storeDescription !== ''): ?>

                    <p class="store-profile-description">
                        <?= e($storeDescription) ?>
                    </p>

                <?php else: ?>

                    <p class="store-profile-description store-profile-description-muted">
                        Temukan produk pilihan dari
                        <?= e($storeName) ?>.
                    </p>

                <?php endif; ?>

                <!-- Status dan informasi toko -->

                <div class="store-meta">

                    <span
                        class="store-chip <?= $isStoreOpen
                            ? 'is-open'
                            : 'is-closed' ?>"
                    >
                        <span
                            class="store-status-dot"
                            aria-hidden="true"
                        ></span>

                        <?= $isStoreOpen
                            ? 'Sedang buka'
                            : 'Sedang tutup' ?>
                    </span>

                    <?php if ($hasFreeDelivery): ?>

                        <span class="store-chip store-chip-delivery">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M3 7h11v11H3z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />

                                <path
                                    d="M14 11h4l3 3v4h-7z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />

                                <circle
                                    cx="7.5"
                                    cy="18"
                                    r="2"
                                    fill="white"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />

                                <circle
                                    cx="17.5"
                                    cy="18"
                                    r="2"
                                    fill="white"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                            </svg>

                            Gratis ongkir

                        </span>

                    <?php endif; ?>

                    <?php if ($storeAddress !== ''): ?>

                        <span class="store-chip store-chip-address">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />

                                <circle
                                    cx="12"
                                    cy="10"
                                    r="2.5"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                            </svg>

                            <span><?= e($storeAddress) ?></span>

                        </span>

                    <?php endif; ?>

                </div>

                <?php if ($storePhone !== ''): ?>

                    <div class="store-profile-contact">

                        <span class="store-contact-label">
                            Kontak warung
                        </span>

                        <span class="store-contact-value">
                            <?= e($storePhone) ?>
                        </span>

                    </div>

                <?php endif; ?>

            </div>

        </section>

        <!-- =================================================
             PENCARIAN PRODUK
             ================================================= -->

        <section class="store-search-section">

            <form
                class="store-search-form"
                id="search-form"
                role="search"
            >

                <input
                    id="search-input"
                    type="search"
                    placeholder="Cari produk di warung ini..."
                    aria-label="Cari produk di warung ini"
                >

                <button
                    class="button button-primary"
                    type="submit"
                >
                    Cari
                </button>

            </form>

        </section>

        <!-- =================================================
             KATEGORI
             ================================================= -->

        <nav
            class="store-category-nav"
            id="category-list"
            aria-label="Kategori produk"
        >

            <a
                href="#"
                class="active"
                data-category="Semua"
                aria-pressed="true"
            >
                Semua
            </a>

            <?php foreach ($categories as $category): ?>

                <a
                    href="#"
                    data-category="<?= e($category) ?>"
                    aria-pressed="false"
                >
                    <?= e($category) ?>
                </a>

            <?php endforeach; ?>

        </nav>

        <!-- =================================================
             KATALOG
             ================================================= -->

        <section
            class="products-section"
            id="products"
        >

            <div class="section-heading">

                <div>
                    <span class="eyebrow eyebrow-plain">
                        KATALOG WARUNG
                    </span>

                    <h2>Produk tersedia</h2>

                    <p class="muted">
                        Pilih produk yang ingin kamu pesan.
                    </p>
                </div>

                <span class="muted" id="product-total">
                    Memuat produk...
                </span>

            </div>

            <div
                class="store-products"
                id="product-grid"
                aria-live="polite"
            ></div>

            <div
                class="store-empty"
                id="empty-state"
                hidden
            >
                <h3>Produk tidak ditemukan</h3>

                <p>
                    Coba kata kunci lain atau periksa kembali
                    kategori produk.
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

    <script
        type="module"
        src="/assets/js/main.js"
    ></script>

</body>
</html>