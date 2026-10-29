<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_PATH . '/warung_auth.php';

$store = require_store_owner();

$pageKey = 'settings';
$pageTitle = 'Pengaturan';
$activePage = 'pengaturan';

require __DIR__ . '/_header.php';

?>

<div class="settings-page">

    <header class="settings-page-heading">
        <h2>Pengaturan warung</h2>
    </header>

    <!-- Status operasional -->
    <div class="settings-status-grid">

        <section
            class="dashboard-card settings-status-card"
            aria-labelledby="store-status-heading"
        >
            <div class="settings-status-copy">
                <h3 id="store-status-heading">Status warung</h3>

                <p id="store-toggle-text">
                    <?= (bool) ($store['is_open'] ?? false)
                        ? 'Warung sedang buka'
                        : 'Warung sedang tutup' ?>
                </p>
            </div>

            <label
                class="switch-label settings-switch"
                for="store-open-toggle"
            >
                <span class="settings-switch-label">
                    Terima pesanan
                </span>

                <input
                    type="checkbox"
                    id="store-open-toggle"
                    <?= (bool) ($store['is_open'] ?? false)
                        ? 'checked'
                        : '' ?>
                >

                <span class="switch" aria-hidden="true"></span>
            </label>

            <p
                class="settings-feedback"
                id="store-status-feedback"
                role="status"
                aria-live="polite"
            ></p>
        </section>

        <section
            class="dashboard-card settings-status-card"
            aria-labelledby="free-delivery-heading"
        >
            <div class="settings-status-copy">
                <h3 id="free-delivery-heading">Gratis ongkir</h3>

                <p id="free-delivery-toggle-text">
                    <?= (bool) ($store['free_delivery'] ?? false)
                        ? 'Aktif'
                        : 'Tidak aktif' ?>
                </p>
            </div>

            <label
                class="switch-label settings-switch"
                for="free-delivery-toggle"
            >
                <span class="settings-switch-label">
                    Aktifkan promo
                </span>

                <input
                    type="checkbox"
                    id="free-delivery-toggle"
                    <?= (bool) ($store['free_delivery'] ?? false)
                        ? 'checked'
                        : '' ?>
                >

                <span class="switch" aria-hidden="true"></span>
            </label>

            <p
                class="settings-feedback"
                id="free-delivery-feedback"
                role="status"
                aria-live="polite"
            ></p>
        </section>

    </div>

    <!-- Profil warung -->
    <section
        class="dashboard-card settings-profile-card"
        aria-labelledby="store-profile-heading"
    >
        <div class="settings-section-heading">
            <h3 id="store-profile-heading">Profil warung</h3>
        </div>

        <form
            class="store-settings-form"
            id="store-settings-form"
        >

            <!-- Ikon warung -->
            <section
                class="store-icon-section"
                aria-labelledby="store-icon-heading"
            >
                <div class="store-icon-layout">

                    <div class="store-icon-preview">

                        <img
                            id="store-icon-preview"
                            src="<?= !empty($store['icon_image'])
                                ? e(url($store['icon_image']))
                                : '' ?>"
                            alt="Pratinjau ikon warung"
                            <?= empty($store['icon_image'])
                                ? 'hidden'
                                : '' ?>
                        >

                        <div
                            id="store-icon-placeholder"
                            <?= !empty($store['icon_image'])
                                ? 'hidden'
                                : '' ?>
                        >
                            <span aria-hidden="true">+</span>
                            <small>Ikon warung</small>
                        </div>

                    </div>

                    <div class="store-icon-controls">

                        <h4 id="store-icon-heading">
                            Ikon warung
                        </h4>

                        <label
                            class="button button-outline"
                            for="store-icon-input"
                        >
                            Pilih gambar
                        </label>

                        <input
                            type="file"
                            id="store-icon-input"
                            accept="image/jpeg,image/png,image/webp"
                            hidden
                        >

                        <small class="muted">
                            JPG, PNG, WebP · Maks. 2 MB
                        </small>

                        <button
                            class="button button-primary"
                            id="save-store-icon"
                            type="button"
                            disabled
                        >
                            Simpan ikon
                        </button>

                        <p
                            id="store-icon-feedback"
                            class="store-icon-feedback muted"
                            role="status"
                            aria-live="polite"
                        ></p>

                    </div>

                </div>
            </section>

            <!-- Informasi warung -->
            <div class="product-form-grid">

                <label class="form-field">
                    <span>Nama warung</span>

                    <input
                        name="name"
                        maxlength="80"
                        autocomplete="organization"
                        value="<?= e($store['name'] ?? '') ?>"
                        required
                    >
                </label>

                <label class="form-field">
                    <span>Nama pemilik</span>

                    <input
                        name="owner_name"
                        maxlength="80"
                        autocomplete="name"
                        value="<?= e($store['owner_name'] ?? '') ?>"
                        required
                    >
                </label>

                <label class="form-field">
                    <span>Nomor WhatsApp</span>

                    <input
                        name="phone"
                        type="tel"
                        maxlength="25"
                        autocomplete="tel"
                        value="<?= e($store['phone'] ?? '') ?>"
                    >
                </label>

                <label class="form-field">
                    <span>Alamat warung</span>

                    <input
                        name="address"
                        maxlength="180"
                        autocomplete="street-address"
                        value="<?= e($store['address'] ?? '') ?>"
                        required
                    >
                </label>

                <label class="form-field store-location-field">
                    <span>Latitude</span>

                    <input
                        name="latitude"
                        type="number"
                        min="-90"
                        max="90"
                        step="any"
                        inputmode="decimal"
                        placeholder="-7.0000000"
                    >
                </label>

                <label class="form-field store-location-field">
                    <span>Longitude</span>

                    <input
                        name="longitude"
                        type="number"
                        min="-180"
                        max="180"
                        step="any"
                        inputmode="decimal"
                        placeholder="110.0000000"
                    >
                </label>

                <div class="store-location-actions">

                    <button
                        class="button button-outline"
                        id="get-store-location"
                        type="button"
                    >
                        Ambil lokasi GPS
                    </button>

                    <p
                        id="store-location-feedback"
                        class="muted"
                        role="status"
                        aria-live="polite"
                    ></p>

                </div>

                <label class="form-field store-description-field">
                    <span>Deskripsi <small>(opsional)</small></span>

                    <textarea
                        name="description"
                        maxlength="500"
                        rows="3"
                        placeholder="Deskripsi singkat warung"
                    ><?= e($store['description'] ?? '') ?></textarea>
                </label>

            </div>

            <!-- Aksi formulir -->
            <div class="product-form-actions">

                <button
                    class="button button-primary"
                    type="submit"
                >
                    Simpan perubahan
                </button>

                <button
                    class="button button-outline"
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
