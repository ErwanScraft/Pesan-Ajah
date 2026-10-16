<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PESAN AJAH — SHARED HEADER
|--------------------------------------------------------------------------
|
| Digunakan oleh:
| - index.php
| - warung.php
|
| Variabel:
| $isStorePage = true  : Halaman publik warung
| $isStorePage = false : Halaman utama marketplace
|
*/

$isStorePage = $isStorePage ?? false;

?>

<header class="site-header<?= $isStorePage ? ' store-header' : '' ?>">
    <div class="container header-inner">

        <!-- Brand -->
        <a
            class="brand"
            href="/"
            aria-label="Pesan Ajah — Beranda"
        >
            <img
                class="brand-logo"
                src="/assets/images/logo.webp"
                alt=""
                width="44"
                height="44"
            >

            <span class="brand-text">
                <strong>Pesan Ajah</strong>
                <small>Belanja dekat, jadi mudah.</small>
            </span>
        </a>

        <?php if (!$isStorePage): ?>

            <!-- Wilayah layanan hanya pada halaman utama -->
            <div class="location">
                <span class="pin" aria-hidden="true">⌖</span>

                <span>
                    <small>Wilayah layanan</small>
                    <strong>Banyukuning</strong>
                </span>
            </div>

        <?php endif; ?>

        <!-- Navigasi dan keranjang -->
        <div class="<?= $isStorePage ? 'store-header-actions' : 'header-actions' ?>">

            <a
                class="text-link dashboard-link"
                href="/user/"
            >
                Dashboard Buyer
            </a>

            <button
                class="cart-trigger<?= $isStorePage ? ' store-cart-trigger' : '' ?>"
                id="cart-trigger"
                type="button"
                aria-label="Buka keranjang belanja"
                title="Keranjang belanja"
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

                <span
                    class="count"
                    id="cart-count"
                >0</span>
            </button>

        </div>

    </div>
</header>