<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

require_once APP_ROOT . '/app/delivery_availability.php';

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

/*
|--------------------------------------------------------------------------
| Ringkasan produk, rating, dan review
|--------------------------------------------------------------------------
*/

$deliveryAvailable = delivery_is_available(database());

$summaryStmt = database()->prepare(
    'SELECT
        COUNT(*) AS product_count
     FROM products
     WHERE store_id = :store_id'
);

$summaryStmt->execute([
    'store_id' => $storeId,
]);

$productCount = (int) $summaryStmt->fetchColumn();

$completedOrderStmt = database()->prepare(
    "SELECT COUNT(*)
     FROM orders
     WHERE store_id = :store_id
       AND status = 'completed'"
);

$completedOrderStmt->execute([
    'store_id' => $storeId,
]);

$completedOrderCount = (int) $completedOrderStmt->fetchColumn();

$ratingStmt = database()->prepare(
    'SELECT
        COUNT(*) AS review_count,
        COALESCE(AVG(rating), 0) AS average_rating
     FROM store_reviews
     WHERE store_id = :store_id'
);

$ratingStmt->execute([
    'store_id' => $storeId,
]);

$ratingSummary = $ratingStmt->fetch();

$reviewCount = (int) ($ratingSummary['review_count'] ?? 0);
$averageRating = (float) ($ratingSummary['average_rating'] ?? 0);

$reviewStmt = database()->prepare(
    'SELECT
        sr.rating,
        sr.comment,
        sr.created_at,
        o.buyer_name
     FROM store_reviews sr
     INNER JOIN orders o
        ON o.id = sr.order_id
     WHERE sr.store_id = :store_id
     ORDER BY sr.created_at DESC, sr.id DESC
     LIMIT 10'
);

$reviewStmt->execute([
    'store_id' => $storeId,
]);

$storeReviews = $reviewStmt->fetchAll();

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

$siteUrl = 'https://pesanajah.web.id';

$shareImageUrl = $storeIconUrl !== null
    ? $siteUrl . $storeIconUrl
    : $siteUrl . '/assets/images/logo.webp';

$storePageUrl = $siteUrl . '/warung/' . rawurlencode($slug);

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

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Pesan Ajah">
    <meta property="og:title" content="<?= e($storeName) ?> — Pesan Ajah">
    <meta property="og:description" content="<?= e($metaDescription) ?>">

    <title><?= e($storeName) ?> — Pesan Ajah</title>

    <link rel="icon" type="image/webp" href="/assets/images/logo.webp">
    <link
        rel="stylesheet"
        href="/assets/css/style.css?v=<?= asset_version('assets/css/style.css') ?>"
    >
    
    <!-- Google Analytic -->
    <script async src="https://www.googletagmanager.com/gtm.js?id=GTM-5WJ727NJ"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
    </script>
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


        <!-- Banner warung -->
        <section class="store-banner" aria-label="Banner warung">
            <img
                src="/assets/images/banner_1.webp"
                alt=""
                width="1600"
                height="900"
                fetchpriority="high"
            >
        </section>

        <!-- Profil warung -->
        <section class="store-profile" aria-labelledby="store-profile-name">
            <div class="store-identity-logo">
                <?php if ($storeIconUrl !== null): ?>
                    <img
                        src="<?= e($storeIconUrl) ?>"
                        alt="Logo <?= e($storeName) ?>"
                        width="100"
                        height="100"
                    >
                <?php else: ?>
                    <span aria-hidden="true"><?= e($storeInitial) ?></span>
                <?php endif; ?>
            </div>

            <div class="store-identity-text">
                <h1 id="store-profile-name">
                    <?= e($storeName) ?>
                </h1>
            
                <span class="store-compact-rating">
                    <span aria-hidden="true">★</span>
                    <?= number_format($averageRating, 1) ?>
                </span>
            </div>

            <span class="store-profile-status store-status <?= $isStoreOpen ? 'is-open' : 'is-closed' ?>">
                <?= $isStoreOpen ? 'Buka' : 'Tutup' ?>
            </span>
        </section>
        
        <!-- Ringkasan warung -->
        <section class="store-overview" aria-label="Ringkasan warung">
        
            <!-- Penilaian -->
            <div class="store-overview-item">
                <div class="store-overview-value">
                    <span class="store-overview-star" aria-hidden="true">★</span>
                    <?= number_format($averageRating, 1) ?>
                </div>
        
                <button
                    type="button"
                    class="store-overview-label store-reviews-trigger"
                    id="store-reviews-open"
                    aria-label="Lihat ulasan warung"
                >
                    Ulasan
                    <?php if ($reviewCount > 0): ?>
                        (<?= number_format($reviewCount, 0, ',', '.') ?>)
                    <?php endif; ?>
                </button>
            </div>
        
            <!-- Produk -->
            <div class="store-overview-item">
                <strong class="store-overview-value">
                    <?= number_format($productCount, 0, ',', '.') ?>
                </strong>
        
                <span class="store-overview-label">Produk</span>
            </div>
        
            <!-- Pesanan Selesai -->
            <div class="store-overview-item">
                <strong class="store-overview-value">
                    <?= number_format($completedOrderCount, 0, ',', '.') ?>
                </strong>
        
                <span class="store-overview-label">Pesanan Selesai</span>
            </div>
        
        </section>
        
        <div class="store-review-action">
            <button
                type="button"
                class="store-review-button"
                id="store-review-open"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/>
                </svg>
                Review
            </button>
        </div>

        <!-- Informasi warung -->
        <section class="store-details" aria-labelledby="store-details-title">
            <h2 id="store-details-title">Informasi</h2>

            <div class="store-detail-list">

                <!-- Owner -->
                <div class="store-detail-item">
                    <span class="store-detail-label">Owner</span>
                    <p>
                        <?= e($storeOwner !== '' ? $storeOwner : 'Belum diisi') ?>
                    </p>
                </div>
                
                <!-- Status warung dan Delivery -->
                <div class="store-detail-item">
                    <span class="store-detail-label">Status</span>
                
                    <div class="store-detail-status">
                        <span class="store-status <?= $isStoreOpen ? 'is-open' : 'is-closed' ?>">
                            <?= $isStoreOpen ? 'Buka' : 'Tutup' ?>
                        </span>
                
                        <span class="store-delivery-status <?= $deliveryAvailable ? 'is-online' : 'is-offline' ?>">
                            Delivery <?= $deliveryAvailable ? 'On' : 'Off' ?>
                        </span>
                    </div>
                </div>

                <!-- Alamat -->
                <div class="store-detail-item">
                    <span class="store-detail-label">Alamat</span>
                    <p>
                        <?php if ($storeMapsUrl !== null): ?>
                            <a
                                class="store-address-link"
                                href="<?= e($storeMapsUrl) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Lihat alamat <?= e($storeName) ?> di Google Maps"
                            >
                                <?= e($storeAddress) ?>
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M7 17 17 7M7 7h10v10"></path>
                                </svg>
                            </a>
                        <?php else: ?>
                            Alamat belum diisi
                        <?php endif; ?>
                    </p>
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
    
    <!-- Template Ulasan Warung -->
    <template id="store-reviews-template">
    
        <div class="store-reviews-sheet-body">
    
            <?php if ($storeReviews): ?>
    
                <div class="store-review-list">
    
                    <?php foreach ($storeReviews as $review): ?>
    
                        <article class="store-review-card">
    
                            <div class="store-review-card-heading">
    
                                <strong>
                                    <?= e($review['buyer_name'] ?: 'Pembeli') ?>
                                </strong>
    
                                <span
                                    class="store-review-stars"
                                    aria-label="<?= (int) $review['rating'] ?> dari 5 bintang"
                                >
                                    <?= str_repeat('★', (int) $review['rating']) ?><span><?= str_repeat('★', 5 - (int) $review['rating']) ?></span>
                                </span>
    
                            </div>
    
                            <?php if (!empty($review['comment'])): ?>
                                <p>
                                    <?= nl2br(e($review['comment'])) ?>
                                </p>
                            <?php endif; ?>
    
                        </article>
    
                    <?php endforeach; ?>
    
                </div>
    
            <?php else: ?>
    
                <div class="store-review-empty">
                    Belum ada ulasan untuk warung ini.
                </div>
    
            <?php endif; ?>
    
        </div>
    
        <div class="store-reviews-sheet-footer">
    
            <p>Sudah pernah Memesan di Warung ini?</p>
    
            <button
                type="button"
                class="store-review-submit"
                id="store-reviews-write"
            >
                Yuk ikut Review
            </button>
    
        </div>
    
    </template>
    
    <div
        class="store-review-modal"
        id="store-review-modal"
        hidden
        aria-hidden="true"
    >
        <div class="store-review-modal-backdrop" data-review-close></div>
    
        <section
            class="store-review-modal-content"
            role="dialog"
            aria-modal="true"
            aria-labelledby="store-review-modal-title"
        >
            <div class="store-review-modal-heading">
                <div>
                    <h2 id="store-review-modal-title">Review Warung</h2>
                    <p>Bagikan pengalaman belanja Anda.</p>
                </div>
    
                <button
                    type="button"
                    class="store-review-modal-close"
                    aria-label="Tutup"
                    data-review-close
                >&times;</button>
            </div>
    
            <form id="store-review-form">
                <input
                    type="hidden"
                    id="store-review-store-id"
                    value="<?= $storeId ?>"
                >
    
                <div class="store-review-field">
                    <label for="store-review-order">Pesanan</label>
    
                    <select id="store-review-order" required>
                        <option value="">Memuat pesanan...</option>
                    </select>
    
                    <small>Pilih pesanan yang sudah selesai.</small>
                </div>
    
                <div class="store-review-field">
                    <span class="store-review-field-label">Bintang</span>
    
                    <div class="store-review-rating" id="store-review-rating">
                        <?php for ($star = 1; $star <= 5; $star++): ?>
                            <label>
                                <input
                                    type="radio"
                                    name="rating"
                                    value="<?= $star ?>"
                                    <?= $star === 5 ? 'checked' : '' ?>
                                >
                                <span aria-label="<?= $star ?> bintang">★</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
    
                <div class="store-review-field">
                    <label for="store-review-comment">
                        Review <span>(Opsional)</span>
                    </label>
    
                    <textarea
                        id="store-review-comment"
                        rows="4"
                        maxlength="1000"
                        placeholder="Ceritakan pengalaman Anda..."
                    ></textarea>
                </div>
    
                <button
                    type="submit"
                    class="store-review-submit"
                    id="store-review-submit"
                >
                    Post Review
                </button>
    
                <p
                    class="store-review-form-message"
                    id="store-review-form-message"
                    role="status"
                    aria-live="polite"
                ></p>
            </form>
        </section>
    </div>

    <?php require __DIR__ . '/_footer.php'; ?>
    <?php require __DIR__ . '/partials/cart.php'; ?>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <script
        type="module"
        src="/assets/js/main.js?v=<?= asset_version('assets/js/main.js') ?>"
    ></script>
</body>
</html>
