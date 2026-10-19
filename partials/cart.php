<?php
// Komponen keranjang bersama Pesan Ajah.
// Digunakan oleh index.php dan warung.php.
?>

<div class="cart-overlay" id="cart-overlay" hidden>
    <aside
        class="cart-drawer"
        id="cart-drawer"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cart-title"
    >
        <!-- Header -->
        <header class="cart-header">
            <div class="cart-header-content">
                <h2 id="cart-title">Keranjang</h2>

                <!-- Dipertahankan untuk kompatibilitas JavaScript -->
                <p class="visually-hidden">
                    <span id="cart-count-label">0</span> item dipilih
                </p>
            </div>

            <button
                class="cart-close"
                id="close-cart"
                type="button"
                aria-label="Tutup keranjang"
            >
                <svg
                    width="22"
                    height="22"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <path
                        d="M18 6 6 18M6 6l12 12"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                </svg>
            </button>
        </header>

        <div class="cart-content">

            <!-- Keranjang kosong -->
            <div class="cart-empty" id="cart-empty" hidden>
                <div class="cart-empty-icon" aria-hidden="true">
                    <svg
                        width="38"
                        height="38"
                        viewBox="0 0 24 24"
                        fill="none"
                    >
                        <path
                            d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H6"
                            stroke="currentColor"
                            stroke-width="1.6"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <circle cx="10" cy="20" r="1.2" fill="currentColor"/>
                        <circle cx="18" cy="20" r="1.2" fill="currentColor"/>
                    </svg>
                </div>

                <h3>Keranjang masih kosong</h3>

                <button
                    class="button button-primary"
                    id="empty-cart-close"
                    type="button"
                >
                    Mulai belanja
                </button>
            </div>

            <!-- Konten checkout -->
            <div class="cart-footer" id="cart-footer" hidden>

                <!-- Produk -->
                <section
                    class="cart-products-section"
                    aria-labelledby="cart-products-heading"
                >
                    <div class="cart-section-heading">
                        <h3 id="cart-products-heading">
                            Produk pilihan
                        </h3>

                        <!-- Dipertahankan untuk kompatibilitas JavaScript -->
                        <span class="cart-item-count" aria-hidden="true">
                            <span id="cart-count-items">0</span> item
                        </span>
                    </div>

                    <div class="cart-items" id="cart-items"></div>
                </section>

                <!-- Data pemesan -->
                <section
                    class="checkout-form"
                    id="checkout-form-section"
                    aria-labelledby="buyer-heading"
                >
                    <div class="cart-section-heading">
                        <h3 id="buyer-heading">Data pemesan</h3>
                    </div>

                    <div class="form-field">
                        <label for="buyer-name">
                            Nama lengkap
                        </label>

                        <input
                            type="text"
                            id="buyer-name"
                            name="buyer_name"
                            placeholder="Nama penerima"
                            autocomplete="name"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="buyer-phone">
                            Nomor WhatsApp
                        </label>

                        <input
                            type="tel"
                            id="buyer-phone"
                            name="buyer_phone"
                            placeholder="08xxxxxxxxxx"
                            autocomplete="tel"
                            inputmode="tel"
                            maxlength="20"
                            required
                        >
                    </div>

                    <!-- Detail alamat opsional -->
                    <div
                        class="delivery-address-section"
                        id="delivery-address-section"
                    >
                        <div class="form-field">
                            <label for="delivery-address">
                                Detail alamat
                                <span class="muted">(opsional)</span>
                            </label>

                            <textarea
                                id="delivery-address"
                                name="delivery_address"
                                placeholder="Dusun, RT/RW, patokan rumah, warna pagar"
                                rows="3"
                                maxlength="255"
                                aria-describedby="delivery-address-hint"
                            ></textarea>

                            <small
                                id="delivery-address-hint"
                                class="muted"
                            >
                                Lokasi GPS tetap digunakan untuk menghitung ongkir.
                            </small>
                        </div>

                        <div class="location-picker">
                            <button
                                class="button button-outline location-button"
                                id="use-location-button"
                                type="button"
                            >
                                Gunakan lokasi saya
                            </button>

                            <p
                                class="location-status"
                                id="location-status"
                                role="status"
                                aria-live="polite"
                            ></p>

                            <input
                                type="hidden"
                                id="delivery-maps-url"
                                name="delivery_maps_url"
                            >

                            <input
                                type="hidden"
                                id="delivery-latitude"
                                name="delivery_latitude"
                            >

                            <input
                                type="hidden"
                                id="delivery-longitude"
                                name="delivery_longitude"
                            >

                            <a
                                class="preview-location-link"
                                id="preview-location-link"
                                href="#"
                                target="_blank"
                                rel="noopener noreferrer"
                                hidden
                            >
                                Lihat lokasi di Google Maps
                            </a>
                        </div>
                    </div>
                </section>

                <!-- Catatan pesanan -->
                <section
                    class="buyer-note-section"
                    aria-labelledby="buyer-note-heading"
                >
                    <div class="cart-section-heading">
                        <h3 id="buyer-note-heading">
                            Catatan pesanan
                            <span class="muted">(opsional)</span>
                        </h3>
                    </div>

                    <div class="form-field">
                        <textarea
                            id="buyer-note"
                            name="buyer_note"
                            aria-label="Catatan untuk penjual (opsional)"
                            placeholder="Contoh: jangan terlalu pedas"
                            rows="2"
                            maxlength="500"
                        ></textarea>
                    </div>
                </section>

                <!-- Metode pesanan -->
                <section
                    class="fulfillment-section"
                    aria-labelledby="fulfillment-heading"
                >
                    <div class="cart-section-heading">
                        <h3 id="fulfillment-heading">
                            Metode pesanan
                        </h3>
                    </div>

                    <fieldset class="fulfillment-options">
                        <legend class="visually-hidden">
                            Pilih metode pesanan
                        </legend>

                        <!-- Delivery -->
                        <label class="fulfillment-option">
                            <input
                                type="radio"
                                name="fulfillment-method"
                                value="delivery"
                                checked
                            >

                            <span
                                class="fulfillment-option-icon"
                                aria-hidden="true"
                            >
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M3 7.5H14V17H3V7.5Z"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M14 11H18L21 14.5V17H14V11Z"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linejoin="round"
                                    />
                                    <circle
                                        cx="7"
                                        cy="17.5"
                                        r="2"
                                        fill="white"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                    />
                                    <circle
                                        cx="18"
                                        cy="17.5"
                                        r="2"
                                        fill="white"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                    />
                                </svg>
                            </span>

                            <span class="fulfillment-copy">
                                <strong>Diantar</strong>
                                <small>Ke alamat tujuan</small>
                            </span>

                            <span
                                class="fulfillment-price"
                                id="delivery-option-price"
                            >
                                Rp1.000
                            </span>
                        </label>

                        <!-- Ambil sendiri -->
                        <label class="fulfillment-option">
                            <input
                                type="radio"
                                name="fulfillment-method"
                                value="pickup"
                            >

                            <span
                                class="fulfillment-option-icon"
                                aria-hidden="true"
                            >
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M5 8H19L20 21H4L5 8Z"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M9 9V6.5C9 4.84 10.34 3.5 12 3.5C13.66 3.5 15 4.84 15 6.5V9"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </span>

                            <span class="fulfillment-copy">
                                <strong>Ambil sendiri</strong>
                                <small>Ambil di warung</small>
                            </span>

                            <span class="fulfillment-price">
                                Rp0
                            </span>
                        </label>
                    </fieldset>
                </section>

                <!-- Ringkasan pembayaran -->
                <section
                    class="order-summary"
                    aria-labelledby="payment-heading"
                >
                    <h3 id="payment-heading">
                        Ringkasan pembayaran
                    </h3>

                    <div class="summary-row">
                        <span>Subtotal produk</span>
                        <strong id="cart-subtotal">Rp0</strong>
                    </div>

                    <div class="summary-row">
                        <span>Ongkos kirim</span>
                        <strong id="delivery-fee">Rp0</strong>
                    </div>

                    <div class="total-row">
                        <span>Total pembayaran</span>
                        <strong id="cart-total">Rp0</strong>
                    </div>

                    <p
                        class="fulfillment-note muted"
                        id="fulfillment-note"
                    ></p>
                </section>

                <!-- Checkout -->
                <div class="checkout-actions">
                    <button
                        class="button button-primary button-full"
                        id="checkout-button"
                        type="button"
                    >
                        Pesan sekarang
                    </button>
                </div>
            </div>
        </div>
    </aside>
</div>
