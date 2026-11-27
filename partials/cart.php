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

            <!-- KONFIRMASI PESANAN -->
            <section class="cart-confirmation" id="cart-confirmation" hidden>
                <div class="cart-section-heading">
                    <h3>Ringkasan</h3>
                </div>
            
                <div class="cart-confirmation__table-wrap">
                    <table class="cart-confirmation__table">
                        <thead>
                            <tr>
                                <th>Nama Item</th>
                                <th>Jumlah</th>
                                <th>Harga</th>
                                <th>Jumlah Harga</th>
                            </tr>
                        </thead>
                        <tbody id="confirmation-items"></tbody>
                    </table>
                </div>
            
                <div class="cart-confirmation__breakdown">
                    <div class="cart-confirmation__row">
                        <span>Subtotal Produk</span>
                        <strong id="confirmation-subtotal">Rp0</strong>
                    </div>
                
                    <div class="cart-confirmation__row">
                        <span>Ongkir</span>
                        <strong id="confirmation-delivery-fee">Rp0</strong>
                    </div>
                
                    <div class="cart-confirmation__total">
                        <span id="confirmation-total-label">Total Pesanan</span>
                        <strong id="confirmation-total">Rp0</strong>
                    </div>
                
                    <p
                        class="cart-confirmation__note"
                        id="confirmation-delivery-note"
                        role="status"
                        hidden
                    >
                        Pilih lokasi GPS untuk menghitung estimasi ongkir.
                        Total akhir akan dihitung kembali oleh sistem saat checkout.
                    </p>
                </div>
            
                <p class="cart-confirmation__question">
                    Apakah kamu sudah yakin dengan pesananmu?
                </p>
            
                <div class="cart-confirmation__actions">
                    <button
                        class="button button-secondary"
                        id="edit-cart-button"
                        type="button"
                    >
                        Ubah Pesanan
                    </button>
            
                    <button
                        class="button button-primary"
                        id="confirm-order-button"
                        type="button"
                    >
                        Yakin dan Lanjutkan
                    </button>
                </div>
            </section>
            
            <!-- PESANAN BERHASIL -->
            <section class="cart-order-success" id="cart-order-success" hidden>
                <div class="cart-order-success__icon">
                    <svg viewBox="0 0 64 64" fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <circle cx="32" cy="32" r="29" fill="#E8F5EC"/>
                        <path
                            d="m18 32 9 9 19-20"
                            stroke="#176B45"
                            stroke-width="5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
            
                <h3>Pesanan Berhasil Dibuat</h3>
                
                <p>
                    Pesanan sudah tercatat di sistem. Silakan pastikan
                    detail pesanan sudah dikirim melalui WhatsApp Seller.
                </p>
            
                <button
                    class="button button-primary button-full"
                    id="success-back-button"
                    type="button"
                >
                    Kembali
                </button>
            </section>

            <!-- Konten checkout -->
            <div class="cart-footer" id="cart-footer" hidden>
            
                <!-- DATA PEMBELI -->
                <section class="checkout-form" aria-labelledby="buyer-heading">
            
                    <div class="cart-section-heading">
                        <h3 id="buyer-heading">Data Pembeli</h3>
                    </div>
            
                    <div class="form-field">
                        <label for="buyer-name">Nama</label>
                        <input
                            type="text"
                            id="buyer-name"
                            name="buyer_name"
                            placeholder="Nama lengkap"
                            autocomplete="name"
                            maxlength="100"
                            required
                        >
                    </div>
            
                    <div class="form-field" id="buyer-phone-field">
                        <label for="buyer-phone">No WhatsApp</label>
                        <input
                            type="tel"
                            id="buyer-phone"
                            name="buyer_phone"
                            placeholder="08xxxxxxxxxx"
                            autocomplete="tel"
                            inputmode="tel"
                            maxlength="20"
                        >
                    </div>
            
                    <div class="form-field" id="delivery-address-section">
                        <label for="delivery-address">Alamat</label>
            
                        <div class="cart-address-input">
                            <input
                                type="text"
                                id="delivery-address"
                                name="delivery_address"
                                placeholder="Detail tempat / rumah"
                                maxlength="255"
                            >
                        
                            <button
                                type="button"
                                id="use-location-button"
                                aria-label="Gunakan lokasi GPS"
                                title="Gunakan lokasi GPS"
                            >
                                <img
                                    src="/assets/images/icons/location.svg"
                                    alt=""
                                    width="21"
                                    height="21"
                                >
                            </button>
                        </div>
                        
                        <p
                            id="delivery-gps-error"
                            class="delivery-gps-error"
                            role="alert"
                            hidden
                        >
                            Atur GPS terlebih dahulu.
                        </p>
                        
                        <input type="hidden" id="delivery-maps-url">
                        <input type="hidden" id="delivery-latitude">
                        <input type="hidden" id="delivery-longitude">
                        
                    </div>
            
                </section>
            
                <!-- ITEM PESANAN -->
                <section class="cart-products-section">
            
                    <div class="cart-section-heading">
                        <h3>Item Pesanan</h3>
                        <span class="cart-item-count">
                            <span id="cart-count-items">0</span> item
                        </span>
                    </div>
            
                    <div class="cart-items" id="cart-items"></div>
            
                </section>
            
                <!-- CATATAN -->
                <section class="buyer-note-section">
            
                    <div class="cart-section-heading">
                        <h3>Catatan</h3>
                    </div>
            
                    <div class="form-field">
                        <textarea
                            id="buyer-note"
                            name="buyer_note"
                            placeholder="Tambahkan catatan pesanan (opsional)"
                            rows="3"
                            maxlength="500"
                        ></textarea>
                    </div>
            
                </section>
            
                <!-- METODE PEMESANAN -->
                <section class="fulfillment-section">
            
                    <div class="cart-section-heading">
                        <h3>Metode Pemesanan</h3>
                    </div>
            
                    <p
                        id="delivery-unavailable-note"
                        class="muted"
                        role="status"
                        hidden
                    >
                        Layanan Delivery sedang offline. Silakan pilih Take Away.
                    </p>
            
                    <fieldset class="fulfillment-options">
                        <legend class="visually-hidden">
                            Pilih metode pemesanan
                        </legend>
            
                        <label class="fulfillment-option">
                            <input
                                type="radio"
                                name="fulfillment-method"
                                value="pickup"
                            >
                            <span class="fulfillment-copy">
                                <strong>Take Away</strong>
                                <small>Ambil sendiri di warung</small>
                            </span>
                        </label>
            
                        <label class="fulfillment-option">
                            <input
                                type="radio"
                                name="fulfillment-method"
                                value="delivery"
                                checked
                            >
                            <span class="fulfillment-copy">
                                <strong>Delivery</strong>
                                <small>Pesanan diantar ke alamat</small>
                            </span>
                            <span
                                class="fulfillment-price"
                                id="delivery-option-price"
                            >
                                Rp1.000
                            </span>
                        </label>
                    </fieldset>
            
                </section>
            
                <!-- RINGKASAN PEMBAYARAN -->
                <section class="order-summary">
            
                    <h3>Ringkasan Pembayaran</h3>
            
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong id="cart-subtotal">Rp0</strong>
                    </div>
            
                    <div class="summary-row">
                        <span>Ongkir</span>
                        <strong id="delivery-fee">Rp0</strong>
                    </div>
            
                    <div class="total-row">
                        <span>TOTAL</span>
                        <strong id="cart-total">Rp0</strong>
                    </div>
            
                    <p
                        class="fulfillment-note muted"
                        id="fulfillment-note"
                    ></p>
            
                </section>
            
                <!-- TOMBOL CHECKOUT -->
                <div class="checkout-actions">
                    <button
                        class="button button-primary button-full"
                        id="checkout-button"
                        type="button"
                    >
                        Pesan Sekarang
                    </button>
                </div>
            
            </div>
        </div>
    </aside>
</div>
