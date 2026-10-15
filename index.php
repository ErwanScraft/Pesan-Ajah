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
        <section class="store-profile">
            <?php
            $storeIconUrl = store_product_image_url(
                $store['icon_image'] ?? null
            );
            ?>
        
            <?php if ($storeIconUrl !== null): ?>
                <img
                    class="store-profile-icon"
                    src="<?= e($storeIconUrl) ?>"
                    alt="Ikon <?= e($store['name']) ?>"
                    width="88"
                    height="88"
                >
            <?php endif; ?>
        
            <div class="store-profile-content">
                <span class="eyebrow eyebrow-plain">
                    WARUNG LOKAL BANYUKUNING
                </span>
        
                <h1><?= e($store['name']) ?></h1>
        
                <p class="store-profile-description muted">
                    <?= e(
                        $store['description']
                        ?: 'Selamat datang di ' . $store['name'] . '.'
                    ) ?>
                </p>
        
                <div class="store-meta">
                    <span
                        class="store-chip <?= $store['is_open']
                            ? 'is-open'
                            : 'is-closed' ?>"
                    >
                        <?= $store['is_open']
                            ? 'Warung buka'
                            : 'Warung tutup' ?>
                    </span>
        
                    <?php if ($store['free_delivery']): ?>
                        <span class="store-chip">
                            Gratis ongkir
                        </span>
                    <?php endif; ?>
        
                    <?php if (!empty($store['address'])): ?>
                        <span class="store-chip">
                            <?= e($store['address']) ?>
                        </span>
                    <?php endif; ?>
                </div>
        
                <?php if (!empty($store['phone'])): ?>
                    <p class="store-contact">
                        Kontak: <?= e($store['phone']) ?>
                    </p>
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
</body>
</html>