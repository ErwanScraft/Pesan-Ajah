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

            <!-- Menu Buyer -->
            <details class="buyer-menu">
                <summary
                    class="buyer-menu-trigger"
                    aria-label="Menu Buyer"
                    title="Menu Buyer"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </summary>

                <nav
                    class="buyer-menu-panel"
                    aria-label="Navigasi Buyer"
                >
                    <a
                        class="buyer-menu-item"
                        href="/user/"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                            <rect x="14" y="14" width="7" height="7" rx="1"/>
                        </svg>

                        <span class="buyer-menu-item-content">
                            <span class="buyer-menu-item-title">
                                Dashboard Buyer
                            </span>

                            <span class="buyer-menu-item-description">
                                Riwayat dan status pesanan
                            </span>
                        </span>
                    </a>

                    <div class="buyer-menu-divider"></div>

                    <div
                        class="buyer-menu-item is-unavailable"
                        aria-disabled="true"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M5 21v-2a7 7 0 0 1 14 0v2"/>
                        </svg>

                        <span class="buyer-menu-item-content">
                            <span class="buyer-menu-item-title">
                                Pengaturan Akun
                            </span>

                            <span class="buyer-menu-item-description">
                                Informasi pribadi Buyer
                            </span>
                        </span>

                        <span class="buyer-menu-badge">
                            Segera
                        </span>
                    </div>

                    <div class="buyer-menu-divider"></div>

                    <div
                        class="buyer-menu-item is-unavailable"
                        aria-disabled="true"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                            <path d="M10 21h4"/>
                        </svg>

                        <span class="buyer-menu-item-content">
                            <span class="buyer-menu-item-title">
                                Notifikasi
                            </span>

                            <span class="buyer-menu-item-description">
                                Pemberitahuan pesanan dan akun
                            </span>
                        </span>

                        <span class="buyer-menu-badge">
                            Segera
                        </span>
                    </div>
                </nav>
            </details>

            <!-- Keranjang: ID harus dipertahankan -->
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
