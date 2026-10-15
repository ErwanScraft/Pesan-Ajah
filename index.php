<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$path = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

$path = is_string($path)
    ? trim($path, '/')
    : '';

/*
|--------------------------------------------------------------------------
| Halaman utama
|--------------------------------------------------------------------------
|
| Jangan melakukan redirect ke URL yang sama.
| index.html menjadi halaman utama aplikasi.
|
*/

if ($path === '') {
    $homepage = APP_ROOT . '/index.html';

    if (is_file($homepage) && is_readable($homepage)) {
        readfile($homepage);
        exit;
    }

    http_response_code(404);
    exit('Halaman beranda tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Validasi path
|--------------------------------------------------------------------------
*/

if (
    str_contains($path, '\\')
    || str_contains($path, "\0")
) {
    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Routing halaman warung
|--------------------------------------------------------------------------
|
| URL utama:
| /warung/ErwanScraft
|
| URL lama tetap didukung:
| /erwanscraft
|
*/

$segments = explode('/', $path);

if (
    count($segments) === 2
    && strtolower($segments[0]) === 'warung'
) {
    $slug = strtolower($segments[1]);
} elseif (count($segments) === 1) {
    // Kompatibilitas dengan URL slug lama.
    $slug = strtolower($segments[0]);
} else {
    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Validasi slug warung
|--------------------------------------------------------------------------
*/

if (!is_valid_store_slug($slug)) {
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
| Ambil produk milik warung
|--------------------------------------------------------------------------
*/

$productStmt = database()->prepare(
    'SELECT
        id,
        name,
        description,
        category,
        price,
        unit,
        image,
        is_available
    FROM products
    WHERE store_id = :store_id
      AND is_available = 1
    ORDER BY name ASC'
);

$productStmt->execute([
    'store_id' => (int) $store['id'],
]);

$products = $productStmt->fetchAll();

/**
 * Normalisasi URL foto produk untuk halaman publik warung.
 */
function store_product_image_url(mixed $image): ?string
{
    if (!is_string($image)) {
        return null;
    }

    $image = trim($image);

    if ($image === '') {
        return null;
    }

    /*
     * URL absolut dipertahankan.
     */
    if (preg_match('~^https?://~i', $image)) {
        return $image;
    }

    /*
     * Tolak skema selain HTTP/HTTPS untuk mencegah
     * penggunaan skema URL yang tidak diperlukan.
     */
    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $image)) {
        return null;
    }

    $image = str_replace('\\', '/', $image);
    $image = preg_replace('~^(?:\./)+~', '', $image);
    $image = ltrim($image, '/');

    if (
        $image === ''
        || in_array('..', explode('/', $image), true)
    ) {
        return null;
    }

    return '/' . $image;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="theme-color" content="#176b45">

    <meta
        name="description"
        content="<?= e($store['description'] ?: $store['name'] . ' di Pesan Ajah') ?>"
    >

    <title>
        <?= e($store['name']) ?> — Pesan Ajah
    </title>

    <link
        rel="icon"
        type="image/webp"
        href="/assets/images/logo.webp"
    >

    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        .store-page {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            padding-bottom: 48px;
        }

        .store-profile {
            margin: 28px 0;
            padding: 28px;
            border: 1px solid #e3e9e5;
            border-radius: 18px;
            background: white;
        }

        .store-profile h1 {
            margin-bottom: 8px;
        }

        .store-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .store-chip {
            display: inline-flex;
            padding: 7px 11px;
            border-radius: 24px;
            background: #edf5ef;
            font-size: 13px;
        }

        .store-product-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .store-product {
            overflow: hidden;
            border: 1px solid #e3e9e5;
            border-radius: 14px;
            background: white;
        }

        .store-product-image {
            display: grid;
            width: 100%;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            place-items: center;
            background: #f5f7f2;
        }
        
        .store-product-image img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
        }
        
        .store-product-image img[hidden] {
            display: none !important;
        }
        
        .store-product-image-placeholder {
            display: grid;
            width: 100%;
            height: 100%;
            min-height: 120px;
            padding: 12px;
            place-items: center;
            color: #707970;
            font-size: 12px;
            line-height: 1.5;
            text-align: center;
        }
        
        .store-product-image-placeholder[hidden] {
            display: none !important;
        }
        
        .store-product-body {
            min-width: 0;
            padding: 14px;
            overflow-wrap: anywhere;
        }

        .store-product h3 {
            margin: 0 0 8px;
        }

        .store-product p {
            margin: 0 0 8px;
        }

        @media (max-width: 760px) {
            .store-product-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 380px) {
            .store-profile {
                padding: 18px;
            }

            .store-product-grid {
                gap: 10px;
            }
        }
        
        /* =========================================================
           PESAN AJAH — PUBLIC STORE CATALOG
           Scope: halaman publik /warung/{slug}
           ========================================================= */
        
        .store-page .products-section {
            min-width: 0;
        }
        
        .store-page #product-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 24px;
            min-width: 0;
        }
        
        /* Grup kategori yang dibuat catalog.js */
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
        
        /* Grid produk dalam setiap kategori */
        .store-page .store-category-products {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            min-width: 0;
        }
        
        /* Kartu produk yang dibuat JavaScript */
        .store-page .store-category-products .product-card {
            min-width: 0;
            overflow: hidden;
            border: 1px solid #e3e9e5;
            border-radius: 14px;
            background: #fff;
        }
        
        /* Gambar produk */
        .store-page .store-category-products .product-image {
            position: relative;
            display: grid;
            width: 100%;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            place-items: center;
            background: #f5f7f2;
        }
        
        .store-page .store-category-products .product-photo {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
        }
        
        .store-page .store-category-products .product-image-placeholder {
            padding: 12px;
            color: #707970;
            font-size: 12px;
            text-align: center;
        }
        
        /* Informasi produk */
        .store-page .store-category-products .product-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
            padding: 12px;
        }
        
        .store-page .store-category-products .product-store {
            overflow-wrap: anywhere;
            color: #68736b;
            font-size: 12px;
        }
        
        .store-page .store-category-products .product-name {
            margin: 0;
            overflow-wrap: anywhere;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
        }
        
        .store-page .store-category-products .product-unit {
            color: #68736b;
            font-size: 12px;
        }
        
        .store-page .store-category-products .product-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: auto;
        }
        
        .store-page .store-category-products .product-price {
            min-width: 0;
            overflow-wrap: anywhere;
            color: #176b45;
            font-size: 14px;
            font-weight: 700;
        }
        
        .store-page .store-category-products .add-button {
            display: inline-grid;
            flex-shrink: 0;
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
        
        .store-page .store-category-products .add-button:disabled {
            background: #e7ebe8;
            color: #858d87;
            cursor: not-allowed;
        }
        
        /* Kategori */
        .store-page .store-category-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            padding-bottom: 6px;
            overflow-x: auto;
            scrollbar-width: thin;
        }
        
        .store-page .store-category-nav a {
            flex-shrink: 0;
            padding: 9px 14px;
            border: 1px solid #e3e9e5;
            border-radius: 24px;
            background: #fff;
            color: #39443c;
            font-size: 13px;
            text-decoration: none;
            white-space: nowrap;
        }
        
        .store-page .store-category-nav a.active {
            border-color: #176b45;
            background: #176b45;
            color: #fff;
        }
        
        /* Tampilan tablet */
        @media (max-width: 900px) {
            .store-page .store-category-products {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
            }
        }
        
        /* Tampilan mobile */
        @media (max-width: 600px) {
            .store-page {
                width: min(100% - 24px, 1120px);
            }
        
            .store-page .store-category-products {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }
        
            .store-page .store-category-products .product-info {
                padding: 10px;
            }
        
            .store-page .store-category-products .product-name {
                font-size: 13px;
            }
        
            .store-page .store-category-products .product-price {
                font-size: 13px;
            }
        
            .store-page .store-category-products .add-button {
                width: 34px;
                height: 34px;
            }
        }
    </style>
</head>

<body
    data-store-page="true"
    data-store-id="<?= (int) $store['id'] ?>"
>
    <header class="site-header store-header">
        <div class="container header-inner">
            <a
                class="brand"
                href="/"
                aria-label="Pesan Ajah — Beranda"
            >
                <img
                    class="brand-logo"
                    src="/assets/images/logo.webp"
                    width="44"
                    height="44"
                    alt=""
                >
    
                <span class="brand-text">
                    <strong>Pesan Ajah</strong>
                    <small>Belanja dekat, jadi mudah.</small>
                </span>
            </a>
    
            <div class="store-header-actions">
                <a
                    class="text-link dashboard-link"
                    href="/user/"
                >
                    Dashboard Buyer
                </a>
    
                <button
                    class="button button-primary cart-trigger store-cart-trigger"
                    id="cart-trigger"
                    type="button"
                    aria-label="Buka keranjang belanja"
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

    <main class="store-page">
        <section
            class="store-profile"
            aria-labelledby="store-profile-name"
        >
            <?php
            $storeIconUrl = store_product_image_url(
                $store['icon_image'] ?? null
            );
        
            $storeName = trim((string) ($store['name'] ?? 'Warung'));
            $storeOwner = trim((string) ($store['owner'] ?? ''));
            $storeDescription = trim(
                (string) ($store['description'] ?? '')
            );
            $storeAddress = trim((string) ($store['address'] ?? ''));
            $storePhone = trim((string) ($store['phone'] ?? ''));
        
            $isStoreOpen = (bool) $store['is_open'];
            $hasFreeDelivery = (bool) $store['free_delivery'];
            ?>
        
            <!-- Ikon toko -->
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
                        <span>
                            <?= e(
                                function_exists('mb_substr')
                                    ? mb_strtoupper(
                                        mb_substr($storeName, 0, 1, 'UTF-8'),
                                        'UTF-8'
                                    )
                                    : strtoupper(substr($storeName, 0, 1))
                            ) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        
            <!-- Informasi utama warung -->
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
                        Temukan produk pilihan dari <?= e($storeName) ?>.
                    </p>
                <?php endif; ?>
        
                <!-- Status dan informasi toko -->
                <div class="store-meta">
        
                    <span class="store-chip <?= $isStoreOpen
                        ? 'is-open'
                        : 'is-closed' ?>">
        
                        <span
                            class="store-status-dot"
                            aria-hidden="true"
                        ></span>
        
                        <?= $isStoreOpen ? 'Sedang buka' : 'Sedang tutup' ?>
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
        
        <nav
            class="store-category-nav"
            id="category-list"
            aria-label="Kategori produk"
        >
            <?php
            $categories = [];
        
            foreach ($products as $product) {
                $category = trim(
                    (string) ($product['category'] ?? '')
                );
        
                if ($category === '') {
                    $category = 'Lainnya';
                }
        
                if (!in_array($category, $categories, true)) {
                    $categories[] = $category;
                }
            }
        
            sort($categories, SORT_NATURAL | SORT_FLAG_CASE);
            ?>
        
            <?php foreach ($categories as $category): ?>
                <a
                    href="#"
                    data-category="<?= e($category) ?>"
                >
                    <?= e($category) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        
        <section
            class="container products-section"
            id="products"
        >
            <div class="section-heading">
                <div>
                    <span class="eyebrow eyebrow-plain">
                        KATALOG WARUNG
                    </span>
        
                    <h2>Produk tersedia</h2>
                </div>
        
                <span class="muted" id="product-total">
                    <?= count($products) ?> produk
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
                    Coba kata kunci lain atau periksa kembali kategori produk.
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
    <script>
      document
        .querySelectorAll(".store-product-image img")
        .forEach((image) => {
          image.addEventListener(
            "error",
            () => {
              image.hidden = true;
    
              const placeholder = image
                .closest(".store-product-image")
                ?.querySelector(".store-product-image-placeholder");
    
              if (placeholder) {
                placeholder.hidden = false;
              }
            },
            { once: true },
          );
        });
    </script>
    <?php require __DIR__ . '/partials/cart.php'; ?>
    <script type="module" src="/assets/js/main.js"></script>
</body>
</html>