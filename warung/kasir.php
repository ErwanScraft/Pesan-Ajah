<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_PATH . '/warung_auth.php';

$store = require_store_owner();

$pageKey = 'kasir';
$pageTitle = 'Kasir';
$activePage = 'kasir';

require __DIR__ . '/_header.php';

?>

<div class="cashier-page">

    <div class="cashier-heading">
        <div>
            <h2>Kasir Warung</h2>
            <p>Catat penjualan dan proses pembayaran.</p>
        </div>

        <span class="cashier-status">
            <span></span>
            Siap melayani
        </span>
    </div>

    <div class="cashier-layout">

        <section class="cashier-catalog">

            <div class="cashier-section-heading">
                <div>
                    <h3>Daftar Produk</h3>
                    <p>Pilih produk dari katalog warung.</p>
                </div>
            </div>

            <label class="cashier-search">
                <span aria-hidden="true">⌕</span>

                <input
                    type="search"
                    id="cashier-search"
                    placeholder="Cari nama produk..."
                    autocomplete="off"
                >
            </label>

            <div
                class="cashier-categories"
                id="cashier-categories"
            >
                <button
                    type="button"
                    class="active"
                    data-category="all"
                >
                    Semua
                </button>
            </div>

            <div
                class="cashier-products"
                id="cashier-products"
                aria-live="polite"
            >
                <div class="cashier-loading">
                    Memuat produk...
                </div>
            </div>

            <section class="cashier-manual">

                <div class="cashier-section-heading">
                    <div>
                        <h3>Item Manual</h3>
                        <p>Masukkan barang yang belum ada di katalog.</p>
                    </div>

                    <span class="cashier-manual-icon">+</span>
                </div>

                <form id="cashier-manual-form">

                    <label class="cashier-field">
                        <span>Nama barang</span>

                        <input
                            type="text"
                            name="name"
                            maxlength="150"
                            placeholder="Contoh: Es Teh Jumbo"
                            required
                        >
                    </label>

                    <div class="cashier-manual-fields">

                        <label class="cashier-field">
                            <span>Harga satuan</span>

                            <input
                                type="number"
                                name="price"
                                min="1"
                                max="2147483647"
                                step="1"
                                inputmode="numeric"
                                placeholder="5000"
                                required
                            >
                        </label>

                        <label class="cashier-field">
                            <span>Jumlah</span>

                            <input
                                type="number"
                                name="quantity"
                                min="1"
                                max="2147483647"
                                step="1"
                                inputmode="numeric"
                                value="1"
                                required
                            >
                        </label>

                    </div>

                    <button
                        type="submit"
                        class="button button-outline cashier-manual-submit"
                    >
                        + Tambahkan item manual
                    </button>

                </form>

            </section>

        </section>

        <aside class="cashier-panel">

            <div class="cashier-panel-heading">
                <div>
                    <h3>Keranjang</h3>
                    <p id="cashier-item-count">0 item</p>
                </div>

                <button
                    type="button"
                    id="cashier-clear"
                    class="cashier-clear"
                >
                    Kosongkan
                </button>
            </div>

            <div
                class="cashier-cart"
                id="cashier-cart"
            >
                <div class="cashier-cart-empty">
                    <span>▤</span>
                    <strong>Keranjang masih kosong</strong>
                    <p>Pilih produk atau masukkan item manual.</p>
                </div>
            </div>

            <div class="cashier-summary">

                <div class="cashier-summary-row">
                    <span>Total item</span>
                    <strong id="cashier-total-items">0</strong>
                </div>

                <div class="cashier-summary-row cashier-grand-total">
                    <span>Total pembayaran</span>
                    <strong id="cashier-total">Rp0</strong>
                </div>

            </div>

            <div class="cashier-payment">

                <h3>Metode Pembayaran</h3>

                <div class="cashier-payment-options">

                    <label class="cashier-payment-option active">
                        <input
                            type="radio"
                            name="payment_method"
                            value="cash"
                            checked
                        >

                        <span>
                            <strong>Tunai</strong>
                            <small>Dengan kembalian</small>
                        </span>
                    </label>

                    <label class="cashier-payment-option">
                        <input
                            type="radio"
                            name="payment_method"
                            value="qris"
                        >

                        <span>
                            <strong>QRIS</strong>
                            <small>Pembayaran digital</small>
                        </span>
                    </label>

                    <label class="cashier-payment-option">
                        <input
                            type="radio"
                            name="payment_method"
                            value="transfer"
                        >

                        <span>
                            <strong>Transfer</strong>
                            <small>Transfer bank</small>
                        </span>
                    </label>

                </div>

                <div
                    class="cashier-cash-field"
                    id="cashier-cash-field"
                >
                    <label class="cashier-field">
                        <span>Uang diterima</span>

                        <input
                            type="number"
                            id="cashier-cash-received"
                            min="0"
                            step="1"
                            inputmode="numeric"
                            placeholder="Masukkan nominal"
                        >
                    </label>

                    <div class="cashier-change">
                        <span>Kembalian</span>
                        <strong id="cashier-change">Rp0</strong>
                    </div>
                </div>

                <button
                    type="button"
                    class="cashier-checkout-button"
                    id="cashier-checkout"
                    disabled
                >
                    Proses Pembayaran
                    <span>→</span>
                </button>

                <p class="cashier-payment-note">
                    Pastikan jumlah pembayaran sudah benar sebelum diproses.
                </p>

            </div>

        </aside>

    </div>

    <section
        class="cashier-receipt receipt-preview"
        id="cashier-receipt"
        hidden
    >
        <div class="receipt-preview-heading">
            <div>
                <h3>Pembayaran Berhasil</h3>
                <p>Transaksi selesai diproses.</p>
            </div>
    
            <span class="cashier-receipt-check">✓</span>
        </div>
    
        <div id="cashier-receipt-content"></div>
    
        <div class="receipt-preview-actions">
            <button
                type="button"
                class="button button-outline"
                id="cashier-print"
            >
                Cetak Struk
            </button>
    
            <button
                type="button"
                class="button button-primary"
                id="cashier-new"
            >
                Transaksi Baru
            </button>
        </div>
    </section>

</div>

<?php require __DIR__ . '/_footer.php'; ?>