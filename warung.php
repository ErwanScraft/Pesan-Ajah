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

$productCategories = require APP_ROOT . '/config/categories.php';

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
        s.owner_name,
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
    (string) ($store['owner_name'] ?? '')
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

$categoryOrder = array_flip($productCategories);

usort(
    $categories,
    static function (string $a, string $b) use ($categoryOrder): int {
        $positionA = $categoryOrder[$a] ?? PHP_INT_MAX;
        $positionB = $categoryOrder[$b] ?? PHP_INT_MAX;

        return ($positionA <=> $positionB)
            ?: strnatcasecmp($a, $b);
    }
);

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

    <title><?= e($storeName) ?> — Pesan Ajah</title>

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

    <link
        rel="stylesheet"
        href="/assets/css/warung.css"
    >
</head>

<body
    class="store-page-body"
    data-store-page="true"
    data-store-id="<?= $storeId ?>"
>

    <?php
    $isStorePage = true;
    require __DIR__ . '/_header.php';
    ?>

    <main class="store-page">

        <!-- PROFIL WARUNG -->

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
                        width="92"
                        height="92"
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

        <!-- PENCARIAN PRODUK -->

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

        <!-- NAVIGASI KATEGORI -->

        <nav
            class="store-category-nav"
            id="category-list"
            aria-label="Kategori produk"
            data-category-order="<?= e(json_encode(
                $productCategories,
                JSON_UNESCAPED_UNICODE
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
            )) ?>"
        >

            <a
                href="#"
                class="category-card category-all-button active"
                data-category="Semua"
                aria-label="Tampilkan semua produk"
                title="Semua kategori"
                aria-pressed="true"
            >
                <span class="category-all-icon" aria-hidden="true">

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
            </a>

        </nav>

        <!-- KATALOG -->

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

    <?php require __DIR__ . '/_footer.php'; ?>

    <?php require __DIR__ . '/partials/cart.php'; ?>

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
