<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_PATH . '/warung_auth.php';

$store = require_store_owner();

$pageKey = 'settings';
$pageTitle = 'Pengaturan';
$activePage = 'pengaturan';

require __DIR__ . '/_header.php';

$isOpen = (bool) ($store['is_open'] ?? false);
$freeDelivery = (bool) ($store['free_delivery'] ?? false);
$iconImage = $store['icon_image'] ?? '';

?>

<div class="settings-page">

    <header class="settings-page-heading">
        <div>
            <h2>Pengaturan</h2>
            <p>Kelola profil dan status warung.</p>
        </div>

        <span class="settings-page-badge">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 10h18M5 10v10h14V10M3 10l2-6h14l2 6M9 20v-6h6v6"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"/>
            </svg>
            Warung
        </span>
    </header>

    <div class="settings-status-grid">

        <section
            class="settings-status-card"
            aria-labelledby="store-status-heading"
        >
            <div class="settings-status-copy">
                <h3 id="store-status-heading">Status Warung</h3>
                <p id="store-toggle-text">
                    <?= $isOpen ? 'Buka' : 'Tutup' ?>
                </p>
            </div>

            <label
                class="settings-status-control"
                for="store-open-toggle"
            >
                <span class="settings-status-indicator <?= $isOpen ? 'is-active' : '' ?>"
                    aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="m7 12 3.2 3.2L17 8.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="9"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"/>
                    </svg>
                </span>

                <input
                    type="checkbox"
                    id="store-open-toggle"
                    aria-label="Terima pesanan"
                    <?= $isOpen ? 'checked' : '' ?>
                >
            </label>

            <p
                class="settings-feedback"
                id="store-status-feedback"
                role="status"
                aria-live="polite"
            ></p>
        </section>

        <section
            class="settings-status-card"
            aria-labelledby="free-delivery-heading"
        >
            <div class="settings-status-copy">
                <h3 id="free-delivery-heading">Gratis Ongkir</h3>
                <p id="free-delivery-toggle-text">
                    <?= $freeDelivery ? 'Aktif' : 'Nonaktif' ?>
                </p>
            </div>

            <label
                class="settings-status-control"
                for="free-delivery-toggle"
            >
                <span class="settings-status-indicator"
                    aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 7h11v11H3zM14 11h4l3 3v4h-7"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"/>
                        <circle cx="7.5" cy="18" r="1.8"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"/>
                        <circle cx="17.5" cy="18" r="1.8"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"/>
                    </svg>
                </span>

                <input
                    type="checkbox"
                    id="free-delivery-toggle"
                    aria-label="Aktifkan gratis ongkir"
                    <?= $freeDelivery ? 'checked' : '' ?>
                >
            </label>

            <p
                class="settings-feedback"
                id="free-delivery-feedback"
                role="status"
                aria-live="polite"
            ></p>
        </section>

    </div>

    <section
        class="settings-profile-card"
        aria-label="Profil warung"
    >
        <form
            class="store-settings-form"
            id="store-settings-form"
        >

            <section
                class="store-icon-section"
                aria-labelledby="store-icon-heading"
            >
                <div class="store-icon-preview">

                    <img
                        id="store-icon-preview"
                        src="<?= !empty($iconImage)
                            ? e(url($iconImage))
                            : '' ?>"
                        alt="Pratinjau ikon warung"
                        <?= empty($iconImage) ? 'hidden' : '' ?>
                    >

                    <div
                        id="store-icon-placeholder"
                        <?= !empty($iconImage) ? 'hidden' : '' ?>
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 10h18M5 10v10h14V10M3 10l2-6h14l2 6M9 20v-6h6v6"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"/>
                        </svg>
                    </div>

                </div>

                <div class="store-icon-controls">
                    <h3 id="store-icon-heading">Ikon Warung</h3>

                    <p class="store-icon-hint">
                        JPG, PNG, WebP · 2 MB
                    </p>

                    <div class="store-icon-actions">
                        <label
                            class="button button-outline"
                            for="store-icon-input"
                        >
                            Pilih Gambar
                        </label>

                        <input
                            type="file"
                            id="store-icon-input"
                            accept="image/jpeg,image/png,image/webp"
                            hidden
                        >

                        <button
                            class="button button-primary"
                            id="save-store-icon"
                            type="button"
                            disabled
                        >
                            Simpan Ikon
                        </button>
                    </div>

                    <p
                        id="store-icon-feedback"
                        class="store-icon-feedback muted"
                        role="status"
                        aria-live="polite"
                    ></p>
                </div>
            </section>

            <div class="settings-fields-grid">

                <label class="form-field">
                    <span>Nama Warung</span>
                    <input
                        name="name"
                        maxlength="80"
                        autocomplete="organization"
                        placeholder="Nama warung"
                        value="<?= e($store['name'] ?? '') ?>"
                        required
                    >
                </label>

                <label class="form-field">
                    <span>Nama Pemilik</span>
                    <input
                        name="owner_name"
                        maxlength="80"
                        autocomplete="name"
                        placeholder="Nama pemilik"
                        value="<?= e($store['owner_name'] ?? '') ?>"
                        required
                    >
                </label>

                <label class="form-field">
                    <span>WhatsApp</span>
                    <input
                        name="phone"
                        type="tel"
                        maxlength="25"
                        autocomplete="tel"
                        placeholder="08xxxxxxxxxx"
                        value="<?= e($store['phone'] ?? '') ?>"
                    >
                </label>

                <label class="form-field">
                    <span>Alamat</span>
                    <input
                        name="address"
                        maxlength="180"
                        autocomplete="street-address"
                        placeholder="Alamat warung"
                        value="<?= e($store['address'] ?? '') ?>"
                        required
                    >
                </label>

            </div>

            <section class="settings-location-card"
                aria-labelledby="store-location-heading">

                <div class="settings-location-heading">
                    <div>
                        <h3 id="store-location-heading">Lokasi GPS</h3>
                        <p>Latitude &amp; Longitude</p>
                    </div>

                    <button
                        class="button button-outline"
                        id="get-store-location"
                        type="button"
                    >
                        Ambil Lokasi
                    </button>
                </div>

                <div class="settings-fields-grid settings-coordinates-grid">

                    <label class="form-field">
                        <span>Latitude</span>
                        <input
                            name="latitude"
                            type="number"
                            min="-90"
                            max="90"
                            step="any"
                            inputmode="decimal"
                            placeholder="-7.xxxxxx"
                        >
                    </label>

                    <label class="form-field">
                        <span>Longitude</span>
                        <input
                            name="longitude"
                            type="number"
                            min="-180"
                            max="180"
                            step="any"
                            inputmode="decimal"
                            placeholder="110.xxxxxx"
                        >
                    </label>

                </div>

                <p
                    id="store-location-feedback"
                    class="settings-inline-feedback muted"
                    role="status"
                    aria-live="polite"
                ></p>

            </section>

            <label class="form-field settings-description-field">
                <span>Deskripsi <small>(opsional)</small></span>
                <textarea
                    name="description"
                    maxlength="500"
                    rows="4"
                    placeholder="Deskripsi singkat warung"
                ><?= e($store['description'] ?? '') ?></textarea>
            </label>

            <div class="settings-form-footer">

                <button
                    class="button button-primary settings-save-button"
                    type="submit"
                >
                    Simpan Perubahan
                </button>

                <button
                    class="button button-outline settings-reset-button"
                    id="reset-store-settings"
                    type="button"
                >
                    Batalkan
                </button>

            </div>

            <p
                class="store-settings-help muted"
                id="store-settings-feedback"
                role="status"
                aria-live="polite"
            ></p>

        </form>
    </section>

</div>

<?php require __DIR__ . '/_footer.php'; ?>
