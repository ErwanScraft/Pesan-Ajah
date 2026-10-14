<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$path = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

$path = is_string($path) ? trim($path, '/') : '';

if ($path === '') {
    redirect('/');
}

if (
    str_contains($path, '/')
    || str_contains($path, '\\')
    || str_contains($path, "\0")
) {
    http_response_code(404);

    exit('Halaman tidak ditemukan.');
}

$slug = strtolower($path);

if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
    http_response_code(404);

    exit('Halaman tidak ditemukan.');
}

$stmt = database()->prepare(
    'SELECT
        id,
        name,
        slug,
        owner,
        phone,
        address,
        description,
        is_open,
        free_delivery
     FROM stores
     WHERE slug = :slug
       AND is_active = 1
     LIMIT 1'
);

$stmt->execute([
    'slug' => $slug,
]);

$store = $stmt->fetch();

if (!$store) {
    http_response_code(404);

    exit('Warung tidak ditemukan atau sedang tidak tersedia.');
}

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

        .store-product img {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            background: #f3f5f3;
        }

        .store-product-body {
            padding: 14px;
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

<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="/">
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

            <a class="button button-outline" href="/">
                Marketplace
            </a>
        </div>
    </header>

    <main class="store-page">
        <section class="store-profile">
            <span class="eyebrow eyebrow-plain">
                WARUNG LOKAL BANYUKUNING
            </span>

            <h1><?= e($store['name']) ?></h1>

            <p class="muted">
                <?= e($store['description'] ?: 'Selamat datang di ' . $store['name'] . '.') ?>
            </p>

            <div class="store-meta">
                <span class="store-chip">
                    <?= $store['is_open'] ? 'Warung buka' : 'Warung tutup' ?>
                </span>

                <?php if ($store['free_delivery']): ?>
                    <span class="store-chip">
                        Gratis ongkir
                    </span>
                <?php endif; ?>

                <span class="store-chip">
                    <?= e($store['address']) ?>
                </span>
            </div>

            <?php if ($store['phone']): ?>
                <p>
                    Kontak:
                    <?= e($store['phone']) ?>
                </p>
            <?php endif; ?>
        </section>

        <section>
            <div class="section-heading">
                <div>
                    <span class="eyebrow eyebrow-plain">
                        KATALOG WARUNG
                    </span>

                    <h2>Produk tersedia</h2>
                </div>

                <span class="muted">
                    <?= count($products) ?> produk
                </span>
            </div>

            <div class="store-product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="store-product">
                        <?php if (!empty($product['image'])): ?>
                            <img
                                src="<?= e($product['image']) ?>"
                                alt="<?= e($product['name']) ?>"
                                loading="lazy"
                            >
                        <?php endif; ?>

                        <div class="store-product-body">
                            <h3><?= e($product['name']) ?></h3>

                            <?php if (!empty($product['description'])): ?>
                                <p class="muted">
                                    <?= e($product['description']) ?>
                                </p>
                            <?php endif; ?>

                            <strong>
                                Rp<?= number_format(
                                    (float) $product['price'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </strong>

                            <small>
                                / <?= e($product['unit']) ?>
                            </small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if (!$products): ?>
                <p class="muted">
                    Warung ini belum menambahkan produk.
                </p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>