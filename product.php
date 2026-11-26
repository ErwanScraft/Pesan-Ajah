<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

require_method('GET');

$productId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($productId === false) {
    http_response_code(404);
    exit('Produk tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Ambil data produk dan warung
|--------------------------------------------------------------------------
*/

$stmt = database()->prepare(
    "SELECT
        p.id,
        p.name,
        p.variant,
        p.description,
        p.price,
        p.unit,
        p.image,

        s.name AS store_name,
        s.slug AS store_slug

    FROM products p

    INNER JOIN stores s
        ON s.id = p.store_id

    INNER JOIN users u
        ON u.id = s.user_id

    WHERE p.id = :product_id
      AND s.is_active = 1
      AND u.is_active = 1
      AND u.role = 'warung'

    LIMIT 1"
);

$stmt->execute([
    'product_id' => $productId,
]);

$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('Produk tidak ditemukan.');
}

/*
|--------------------------------------------------------------------------
| Informasi produk
|--------------------------------------------------------------------------
*/

$productName = trim((string) $product['name']);

$productVariant = trim(
    (string) ($product['variant'] ?? '')
);

$productDescription = trim(
    (string) ($product['description'] ?? '')
);

$productDescription = $productDescription !== ''
    ? $productDescription
    : 'Lihat informasi produk ini di Pesan Ajah.';

$productPrice = 'Rp' . number_format(
    (float) $product['price'],
    0,
    ',',
    '.'
);

$productUnit = trim(
    (string) ($product['unit'] ?? '')
);

$productStore = trim(
    (string) $product['store_name']
);

$siteUrl = APP_URL !== ''
    ? APP_URL
    : 'https://pesanajah.web.id';

$productUrl = $siteUrl
    . '/product.php?id='
    . (int) $product['id'];

/*
|--------------------------------------------------------------------------
| URL gambar produk
|--------------------------------------------------------------------------
*/

$rawImage = trim(
    (string) ($product['image'] ?? '')
);

if (
    preg_match('~^https://~i', $rawImage)
) {
    $productImage = $rawImage;

} elseif (
    preg_match('~^http://~i', $rawImage)
) {
    // Jangan gunakan gambar HTTP pada metadata HTTPS.
    $productImage = $siteUrl . '/assets/images/no_images.webp';

} else {
    $safePath = str_replace('\\', '/', $rawImage);

    $safePath = preg_replace(
        '~^(?:\./)+~',
        '',
        $safePath
    );

    $safePath = ltrim($safePath, '/');

    if (
        $safePath !== ''
        && !in_array('..', explode('/', $safePath), true)
        && !preg_match('/^[a-z][a-z0-9+.-]*:/i', $safePath)
    ) {
        $productImage = $siteUrl . '/' . $safePath;

    } else {
        $productImage = $siteUrl
            . '/assets/images/no_images.webp';
    }
}

/*
|--------------------------------------------------------------------------
| Open Graph Metadata
|--------------------------------------------------------------------------
*/

$pageTitle = $productName
    . ' - '
    . $productPrice
    . ' | Pesan Ajah';

$pageDescription = $productPrice;

if ($productUnit !== '') {
    $pageDescription .= ' / ' . $productUnit;
}

$pageDescription .= ' | ' . $productDescription;

$pageDescription = mb_substr(
    $pageDescription,
    0,
    300,
    'UTF-8'
);

$storeUrl = $siteUrl
    . '/warung.php?slug='
    . rawurlencode((string) $product['store_slug']);

?>

<!doctype html>
<html lang="id">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title><?= e($pageTitle) ?></title>

    <meta
        name="description"
        content="<?= e($pageDescription) ?>"
    >

    <link
        rel="canonical"
        href="<?= e($productUrl) ?>"
    >

    <meta property="og:type" content="product">

    <meta
        property="og:site_name"
        content="Pesan Ajah"
    >

    <meta
        property="og:title"
        content="<?= e($productName) ?>"
    >

    <meta
        property="og:description"
        content="<?= e($pageDescription) ?>"
    >

    <meta
        property="og:image"
        content="<?= e($productImage) ?>"
    >

    <meta
        property="og:image:alt"
        content="<?= e($productName) ?>"
    >

    <meta
        property="og:url"
        content="<?= e($productUrl) ?>"
    >

    <meta
        property="og:locale"
        content="id_ID"
    >

    <meta name="twitter:card" content="summary_large_image">

    <meta
        name="twitter:title"
        content="<?= e($productName) ?>"
    >

    <meta
        name="twitter:description"
        content="<?= e($pageDescription) ?>"
    >

    <meta
        name="twitter:image"
        content="<?= e($productImage) ?>"
    >

    <link
        rel="icon"
        type="image/webp"
        href="/assets/images/logo.webp"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px 16px;

            background: #f5f7f5;
            color: #202820;

            font-family: Arial, sans-serif;
        }

        .product-share {
            width: 100%;
            max-width: 480px;

            margin: 30px auto;

            overflow: hidden;

            border-radius: 16px;

            background: #fff;

            box-shadow: 0 4px 20px rgb(0 0 0 / 6%);
        }

        .product-share__image {
            display: block;

            width: 100%;
            aspect-ratio: 1 / 1;

            object-fit: contain;

            background: #f5f5f5;
        }

        .product-share__content {
            padding: 22px;
        }

        .product-share__name {
            margin: 0;

            font-size: 22px;
            font-weight: 700;
        }

        .product-share__variant {
            margin: 8px 0 0;

            color: #788178;
            font-size: 13px;
        }

        .product-share__price {
            margin-top: 18px;

            color: #176b45;

            font-size: 22px;
            font-weight: 800;
        }

        .product-share__description {
            margin-top: 18px;

            color: #667066;

            font-size: 14px;
            line-height: 1.7;

            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .product-share__store {
            display: block;

            margin-top: 20px;

            padding: 14px;

            border-radius: 10px;

            background: #176b45;
            color: #fff;

            text-align: center;
            text-decoration: none;

            font-size: 14px;
            font-weight: 700;
        }

        .product-share__brand {
            margin-top: 24px;

            color: #879087;

            font-size: 12px;
            text-align: center;
        }

    </style>

</head>

<body>

    <main class="product-share">

        <img
            class="product-share__image"
            src="<?= e($productImage) ?>"
            alt="<?= e($productName) ?>"
        >

        <div class="product-share__content">

            <h1 class="product-share__name">
                <?= e($productName) ?>
            </h1>

            <?php if ($productVariant !== ''): ?>

                <p class="product-share__variant">
                    <?= e($productVariant) ?>
                </p>

            <?php endif; ?>

            <div class="product-share__price">

                <?= e($productPrice) ?>

                <?php if ($productUnit !== ''): ?>

                    <small>
                        / <?= e($productUnit) ?>
                    </small>

                <?php endif; ?>

            </div>

            <div class="product-share__description">

                <?= nl2br(e($productDescription)) ?>

            </div>

            <a
                class="product-share__store"
                href="<?= e($storeUrl) ?>"
            >
                Kunjungi <?= e($productStore) ?>
            </a>

            <div class="product-share__brand">
                PESAN AJAH · Belanja Jauh Lebih Simpel
            </div>

        </div>

    </main>

</body>

</html>
