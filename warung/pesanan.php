<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_PATH . '/warung_auth.php';

$store = require_store_owner();

$pageKey = 'orders';
$pageTitle = 'Pesanan';
$activePage = 'pesanan';

require __DIR__ . '/_header.php';

?>

<section class="orders-page">

    <div class="orders-page-heading">
        <h2>Pesanan</h2>

        <button
            class="icon-button orders-refresh"
            id="orders-refresh"
            type="button"
            aria-label="Muat ulang pesanan"
            title="Muat ulang"
        >
            ↻
        </button>
    </div>

    <section
        class="orders-summary-section"
        aria-labelledby="orders-summary-title"
    >
        <h3 id="orders-summary-title">
            Ringkasan status pesanan
        </h3>

        <div
            class="orders-summary-grid"
            id="orders-summary"
        >
            <article class="order-summary-card">
                <span>Menunggu</span>
                <strong>—</strong>
            </article>

            <article class="order-summary-card">
                <span>Diproses</span>
                <strong>—</strong>
            </article>

            <article class="order-summary-card">
                <span>Siap diambil</span>
                <strong>—</strong>
            </article>

            <article class="order-summary-card">
                <span>Diantar</span>
                <strong>—</strong>
            </article>
        </div>
    </section>

    <section
        class="orders-list-section"
        aria-labelledby="orders-list-title"
    >
        <div class="orders-list-heading">
            <h3 id="orders-list-title">Daftar pesanan</h3>

            <span class="orders-count">
                <span id="orders-count">0</span> pesanan
            </span>
        </div>

        <div
            class="orders-filters"
            role="group"
            aria-label="Filter status pesanan"
        >
            <button
                type="button"
                class="order-filter active"
                data-order-filter="all"
                aria-pressed="true"
            >
                Semua
            </button>

            <button
                type="button"
                class="order-filter"
                data-order-filter="waiting"
                aria-pressed="false"
            >
                Menunggu
            </button>

            <button
                type="button"
                class="order-filter"
                data-order-filter="processing"
                aria-pressed="false"
            >
                Diproses
            </button>

            <button
                type="button"
                class="order-filter"
                data-order-filter="pickup"
                aria-pressed="false"
            >
                Siap diambil
            </button>

            <button
                type="button"
                class="order-filter"
                data-order-filter="delivery"
                aria-pressed="false"
            >
                Diantar
            </button>

            <button
                type="button"
                class="order-filter"
                data-order-filter="completed"
                aria-pressed="false"
            >
                Riwayat
            </button>
        </div>

        <div
            class="orders-list"
            id="orders-list"
            aria-live="polite"
        >
            <div class="orders-empty">
                Memuat pesanan...
            </div>
        </div>
    </section>

</section>

<?php require __DIR__ . '/_footer.php'; ?>
