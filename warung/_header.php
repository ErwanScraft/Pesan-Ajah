<?php

declare(strict_types=1);

/*
 * File ini harus dipanggil setelah:
 *
 * require_once dirname(__DIR__) . '/app/bootstrap.php';
 * require_once APP_PATH . '/warung_auth.php';
 * $store = require_store_owner();
 *
 * Variabel yang harus disediakan:
 * $pageKey
 * $pageTitle
 * $activePage
 * $store
 */

$productCategories = require APP_ROOT . '/config/categories.php';

$storeName = (string) ($store['name'] ?? 'Warung Anda');

$storeInitial = function_exists('mb_substr')
    ? mb_strtoupper(
        mb_substr($storeName, 0, 1, 'UTF-8'),
        'UTF-8'
    )
    : strtoupper(substr($storeName, 0, 1));

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
        content="Kelola warung, produk, pesanan, dan operasional melalui dashboard Pesan Ajah."
    >

    <title><?= e($pageTitle) ?> — Pesan Ajah</title>

    <link
        rel="icon"
        type="image/webp"
        href="<?= e(url('assets/images/logo.webp')) ?>"
    >

    <link
        rel="stylesheet"
        href="<?= e(url('assets/css/style.css')) ?>"
    >
    
    <?php if (($pageKey ?? '') === 'products'): ?>
        <link
            rel="stylesheet"
            href="<?= e(url('assets/css/products.css')) ?>"
        >
    <?php endif; ?>

    <script
        type="module"
        src="<?= e(url('assets/js/dashboard.js')) ?>"
    ></script>
</head>

<body
    class="dashboard-page"
    data-page="<?= e($pageKey) ?>"
    data-store-id="<?= e($store['store_id'] ?? '') ?>"
    data-csrf-token="<?= e(csrf_token()) ?>"
    data-category-order="<?= e(json_encode(
        $productCategories,
        JSON_UNESCAPED_UNICODE
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
    )) ?>"
>

    <div class="dashboard-shell">

        <aside class="sidebar" id="sidebar">

            <a
                class="brand sidebar-brand"
                href="<?= e(url('')) ?>"
            >
                <img
                    class="brand-logo"
                    src="<?= e(url('assets/images/logo.webp')) ?>"
                    alt=""
                    width="44"
                    height="44"
                >

                <span class="brand-text">
                    <strong>Pesan Ajah</strong>
                    <small>Panel warung</small>
                </span>
            </a>

            <div class="sidebar-label">
                MENU WARUNG
            </div>

            <nav
                class="side-nav"
                aria-label="Navigasi dashboard"
            >
                <a
                    class="side-link <?= $activePage === 'dashboard' ? 'active' : '' ?>"
                    href="<?= e(url('warung/dashboard.php')) ?>"
                    <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?>
                >
                    <span aria-hidden="true">▦</span>
                    Rincian
                </a>

                <a
                    class="side-link <?= $activePage === 'pesanan' ? 'active' : '' ?>"
                    href="<?= e(url('warung/pesanan.php')) ?>"
                    <?= $activePage === 'pesanan' ? 'aria-current="page"' : '' ?>
                >
                    <span aria-hidden="true">▤</span>
                    Pesanan

                    <?php if ($activePage === 'pesanan'): ?>
                        <span
                            class="nav-count"
                            id="pending-count"
                        >0</span>
                    <?php endif; ?>
                </a>

                <a
                    class="side-link <?= $activePage === 'produk' ? 'active' : '' ?>"
                    href="<?= e(url('warung/produk.php')) ?>"
                    <?= $activePage === 'produk' ? 'aria-current="page"' : '' ?>
                >
                    <span aria-hidden="true">□</span>
                    Produk
                </a>

                <a
                    class="side-link <?= $activePage === 'pengaturan' ? 'active' : '' ?>"
                    href="<?= e(url('warung/pengaturan.php')) ?>"
                    <?= $activePage === 'pengaturan' ? 'aria-current="page"' : '' ?>
                >
                    <span aria-hidden="true">⚙</span>
                    Pengaturan
                </a>
            </nav>

            <div class="sidebar-bottom">

                <div
                    class="seller-avatar"
                    id="sidebar-store-avatar"
                >
                    <img
                        id="sidebar-store-icon"
                        src="<?= !empty($store['icon_image'])
                            ? e(url($store['icon_image']))
                            : '' ?>"
                        alt="Ikon <?= e($storeName) ?>"
                        <?= empty($store['icon_image'])
                            ? 'hidden'
                            : '' ?>
                    >
                
                    <span
                        id="sidebar-store-initial"
                        <?= !empty($store['icon_image'])
                            ? 'hidden'
                            : '' ?>
                    >
                        <?= e($storeInitial) ?>
                    </span>
                </div>

                <div>
                    <strong id="sidebar-store-name">
                        <?= e($storeName) ?>
                    </strong>

                    <small>Panel warung</small>
                </div>

                <a
                    href="<?= e(url('')) ?>"
                    aria-label="Kembali ke marketplace"
                >↗</a>

            </div>

            <form
                method="post"
                action="<?= e(url('warung/logout.php')) ?>"
                class="sidebar-logout"
            >
                <?= csrf_field() ?>

                <button
                    class="button button-outline"
                    type="submit"
                >
                    Keluar
                </button>
            </form>

        </aside>

        <main class="dashboard-main">

            <header class="dashboard-topbar">

                <button
                    class="icon-button mobile-menu"
                    id="mobile-menu"
                    type="button"
                    aria-label="Buka menu"
                    aria-expanded="false"
                >☰</button>

                <div>
                    <span class="muted small">
                        Panel penjual
                    </span>

                    <h1><?= e($pageTitle) ?></h1>
                </div>

                <a
                    class="button button-outline"
                    href="<?= e(url('')) ?>"
                >
                    Lihat marketplace
                </a>

            </header>

            <section class="dashboard-content">