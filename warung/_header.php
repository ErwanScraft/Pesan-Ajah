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
        href="<?= e(url('assets/css/style.css')) ?>?v=<?= asset_version('assets/css/style.css') ?>"
    >
    
    <script>
        window.OneSignalDeferred = window.OneSignalDeferred || [];
    </script>
    
    <script
        src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js"
        defer
    ></script>

    <script
        type="module"
        src="<?= e(url('assets/js/dashboard.js')) ?>?v=<?= asset_version('assets/js/dashboard.js') ?>"
    ></script>
</head>

<body
    class="dashboard-page"
    data-page="<?= e($pageKey) ?>"
    data-store-id="<?= e($store['store_id'] ?? '') ?>"
    data-csrf-token="<?= e(csrf_token()) ?>"
    data-onesignal-app-id="<?= e(env('ONESIGNAL_APP_ID', '')) ?>"
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
                aria-label="Menu lainnya"
            >
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

        <!-- Bottom Navigation -->
        <nav
            class="dashboard-bottom-nav"
            aria-label="Navigasi utama"
        >

            <!-- Dashboard -->
            <a
                class="dashboard-bottom-nav__item <?= $activePage === 'dashboard' ? 'is-active' : '' ?>"
                href="<?= e(url('warung/dashboard.php')) ?>"
                <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?>
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="3" width="8" height="8" rx="2"/>
                    <rect x="13" y="3" width="8" height="5" rx="2"/>
                    <rect x="13" y="10" width="8" height="11" rx="2"/>
                    <rect x="3" y="13" width="8" height="8" rx="2"/>
                </svg>

                <span>Dashboard</span>
            </a>

            <!-- Pesanan -->
            <a
                class="dashboard-bottom-nav__item <?= $activePage === 'pesanan' ? 'is-active' : '' ?>"
                href="<?= e(url('warung/pesanan.php')) ?>"
                <?= $activePage === 'pesanan' ? 'aria-current="page"' : '' ?>
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 3h10l3 3v15H4V3h3Z"/>
                    <path d="M8 10h8M8 14h8M8 18h5"/>
                </svg>

                <span>Pesanan</span>
            </a>

            <!-- Kasir -->
            <a
                class="dashboard-bottom-nav__item <?= $activePage === 'kasir' ? 'is-active' : '' ?>"
                href="<?= e(url('warung/kasir.php')) ?>"
                <?= $activePage === 'kasir' ? 'aria-current="page"' : '' ?>
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="5" width="18" height="16" rx="2"/>
                    <path d="M7 2v6M17 2v6M3 10h18"/>
                    <path d="M8 14h2M14 14h2M8 18h2"/>
                </svg>

                <span>Kasir</span>
            </a>

            <!-- Stock -->
            <a
                class="dashboard-bottom-nav__item <?= $activePage === 'stock' ? 'is-active' : '' ?>"
                href="<?= e(url('warung/stock.php')) ?>"
                <?= $activePage === 'stock' ? 'aria-current="page"' : '' ?>
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m12 3 9 5-9 5-9-5 9-5Z"/>
                    <path d="m3 12 9 5 9-5"/>
                    <path d="m3 16 9 5 9-5"/>
                    <path d="M12 13v8"/>
                </svg>

                <span>Stock</span>
            </a>

            <!-- More -->
            <button
                class="dashboard-bottom-nav__item dashboard-bottom-nav__more <?= in_array($activePage, ['produk', 'pengaturan'], true) ? 'is-active' : '' ?>"
                id="dashboard-more-button"
                type="button"
                aria-label="Buka menu lainnya"
                aria-controls="sidebar"
                aria-expanded="false"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="5" cy="5" r="1.5"/>
                    <circle cx="12" cy="5" r="1.5"/>
                    <circle cx="19" cy="5" r="1.5"/>
                    <circle cx="5" cy="12" r="1.5"/>
                    <circle cx="12" cy="12" r="1.5"/>
                    <circle cx="19" cy="12" r="1.5"/>
                    <circle cx="5" cy="19" r="1.5"/>
                    <circle cx="12" cy="19" r="1.5"/>
                    <circle cx="19" cy="19" r="1.5"/>
                </svg>

                <span>More</span>
            </button>

        </nav>

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
                
                <button
                    class="button button-outline"
                    id="push-enable-button"
                    type="button"
                >
                    Aktifkan notifikasi
                </button>

                <a
                    class="button button-outline"
                    href="<?= e(url('')) ?>"
                >
                    Lihat marketplace
                </a>

            </header>

            <section class="dashboard-content">