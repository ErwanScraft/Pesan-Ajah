<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$productCategories = require APP_ROOT . '/config/categories.php';

$slug = strtolower(trim((string) ($_GET['slug'] ?? '')));

if ($slug === '' || !is_valid_store_slug($slug)) {
    http_response_code(404);
    exit('Warung tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Data warung
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
    INNER JOIN users u ON u.id = s.user_id
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

$storeId = (int) $store['id'];
$storeName = trim((string) ($store['name'] ?? 'Warung'));
$storeOwner = trim((string) ($store['owner_name'] ?? ''));
$storeDescription = trim((string) ($store['description'] ?? ''));
$storeAddress = trim((string) ($store['address'] ?? ''));
$storeMapsUrl = $storeAddress !== ''
    ? 'https://www.google.com/maps/search/?api=1&query='
        . rawurlencode($storeName . ' ' . $storeAddress)
    : null;
$isStoreOpen = (bool) ($store['is_open'] ?? false);
$hasFreeDelivery = (bool) ($store['free_delivery'] ?? false);

/*
|--------------------------------------------------------------------------
| URL logo warung
|--------------------------------------------------------------------------
*/

$storeIconUrl = null;
$rawIcon = trim((string) ($store['icon_image'] ?? ''));

if ($rawIcon !== '') {
    if (preg_match('~^https?://~i', $rawIcon)) {
        $storeIconUrl = $rawIcon;
    } elseif (!preg_match('/^[a-z][a-z0-9+.-]*:/i', $rawIcon)) {
        $safePath = str_replace('\\', '/', $rawIcon);
        $safePath = preg_replace('~^(?:\./)+~', '', $safePath);
        $safePath = ltrim($safePath, '/');

        if (
            $safePath !== ''
            && !in_array('..', explode('/', $safePath), true)
        ) {
            $storeIconUrl = '/' . $safePath;
        }
    }
}

$storeInitial = 'W';

if ($storeName !== '') {
    $storeInitial = function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($storeName, 0, 1, 'UTF-8'), 'UTF-8')
        : strtoupper(substr($storeName, 0, 1));
}

$metaDescription = $storeDescription !== ''
    ? $storeDescription
    : 'Pesan produk dari ' . $storeName . ' melalui Pesan Ajah.';

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <meta name="color-scheme" content="light">

    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="robots" content="index, follow">

    <!-- Preview WhatsApp -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Pesan Ajah">
    <meta property="og:title" content="<?= e($storeName) ?> — Pesan Ajah">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:image" content="<?= e($shareImageUrl) ?>">
    <meta property="og:url" content="<?= e($storePageUrl) ?>">
    <meta property="og:image:alt" content="Logo <?= e($storeName) ?>">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= e($storeName) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">
    <meta name="twitter:image" content="<?= e($shareImageUrl) ?>">

    <title><?= e($storeName) ?> — Pesan Ajah</title>

    <link rel="icon" type="image/webp" href="/assets/images/logo.webp">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/warung.css">
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

        <!-- Banner -->
        <section class="store-banner" aria-label="Banner warung">
            <img
                src="/assets/images/banner_1.webp"
                alt=""
                width="1600"
                height="900"
                fetchpriority="high"
            >
        </section>

        <!-- Identitas warung -->
        <section class="store-identity" aria-labelledby="store-profile-name">
            <div class="store-identity-logo">
                <?php if ($storeIconUrl !== null): ?>
                    <img
                        src="<?= e($storeIconUrl) ?>"
                        alt="Logo <?= e($storeName) ?>"
                        width="88"
                        height="88"
                    >
                <?php else: ?>
                    <span aria-hidden="true"><?= e($storeInitial) ?></span>
                <?php endif; ?>
            </div>

            <div class="store-identity-text">
                <h1 id="store-profile-name"><?= e($storeName) ?></h1>

                <?php if ($storeOwner !== ''): ?>
                    <p><?= e($storeOwner) ?></p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Detail: hanya status dan alamat -->
        <section class="store-details" aria-labelledby="store-details-title">
            <h2 id="store-details-title">Detail Warung</h2>

            <div class="store-detail-list">

                <div class="store-detail-item">
                    <span class="store-detail-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7v5l-3 2"></path>
                        </svg>
                    </span>

                    <div class="store-detail-content">
                        <span class="store-detail-label">Status</span>
                        <p>
                            <span class="store-status <?= $isStoreOpen ? 'is-open' : 'is-closed' ?>">
                                <span class="store-status-dot"></span>
                                <?= $isStoreOpen ? 'Buka' : 'Tutup' ?>
                            </span>
                        </p>
                    </div>
                </div>

                <div class="store-detail-item">
                    <span class="store-detail-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path>
                            <circle cx="12" cy="10" r="2.5"></circle>
                        </svg>
                    </span>
                
                    <div class="store-detail-content">
                        <span class="store-detail-label">Alamat</span>
                
                        <div class="store-address-row">
                            <p><?= e($storeAddress !== '' ? $storeAddress : 'Alamat belum diisi') ?></p>
                
                            <?php if ($storeMapsUrl !== null): ?>
                                <a
                                    class="store-maps-button"
                                    href="<?= e($storeMapsUrl) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="Lihat lokasi <?= e($storeName) ?> di Google Maps"
                                    title="Lihat lokasi di Google Maps"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z"></path>
                                        <circle cx="12" cy="9" r="2.5"></circle>
                                        <path d="M19 3v5m-2.5-2.5h5"></path>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($hasFreeDelivery): ?>
                    <div class="store-detail-delivery">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 7h11v11H3z"></path>
                            <path d="M14 11h4l3 3v4h-7z"></path>
                            <circle cx="7.5" cy="18" r="2"></circle>
                            <circle cx="17.5" cy="18" r="2"></circle>
                        </svg>
                        <span>Gratis ongkir dari warung</span>
                    </div>
                <?php endif; ?>

            </div>
        </section>

        <!-- Katalog produk -->
        <section class="store-catalog" id="products" aria-labelledby="store-products-title">

            <div class="store-catalog-heading">
                <h2 id="store-products-title">Produk</h2>
            </div>

            <!-- Pencarian -->
            <form class="store-search-form" id="search-form" role="search">
                <input
                    id="search-input"
                    type="search"
                    placeholder="Cari produk di warung ini..."
                    aria-label="Cari produk di warung ini"
                >
                <button type="submit">Cari</button>
            </form>

            <!-- Kategori dibuat otomatis oleh catalog.js -->
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
            ></nav>

            <!-- catalog.js mengelompokkan produk di sini -->
            <div class="store-products" id="product-grid" aria-live="polite"></div>

            <div class="store-empty" id="empty-state" hidden>
                <h3>Produk tidak ditemukan</h3>
                <p>Coba kata kunci lain atau pilih kategori berbeda.</p>

                <button class="button button-outline" id="reset-search" type="button">
                    Lihat semua produk
                </button>
            </div>

        </section>
    </main>

    <?php require __DIR__ . '/_footer.php'; ?>
    <?php require __DIR__ . '/partials/cart.php'; ?>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <script type="module" src="/assets/js/main.js"></script>
</body>
</html>
