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

<div class="welcome-row">
    <div>
        <h2>Pengaturan warung</h2>

        <p class="muted">
            Kelola informasi profil dan status operasional.
        </p>
    </div>

    <span class="demo-badge">
        <span class="dot"></span>
        Mode demo
    </span>
</div>

<section class="dashboard-card store-control">

    <div>
        <span class="eyebrow eyebrow-plain">
            OPERASIONAL
        </span>

        <h3>Status warung</h3>

        <p class="muted">
            Atur apakah warung menerima pesanan baru.
        </p>
    </div>

    <label
        class="switch-label"
        for="store-open-toggle"
    >
        <span id="store-toggle-text">
            <?= (bool) $store['is_open']
                ? 'Warung sedang buka'
                : 'Warung sedang tutup' ?>
        </span>

        <input
            type="checkbox"
            id="store-open-toggle"
            <?= (bool) $store['is_open'] ? 'checked' : '' ?>
        >

        <span
            class="switch"
            aria-hidden="true"
        ></span>
    </label>

    <p
        class="muted"
        id="store-status-feedback"
        role="status"
    ></p>

</section>

<section class="dashboard-card store-control">

    <div>
        <span class="eyebrow eyebrow-plain">
            PENGIRIMAN
        </span>

        <h3>Gratis ongkir</h3>

        <p class="muted">
            Aktifkan jika program gratis ongkir sudah
            disepakati antara warung dan aplikasi Pesan Ajah.
        </p>
    </div>

    <label
        class="switch-label"
        for="free-delivery-toggle"
    >
        <span id="free-delivery-toggle-text">
            <?= (bool) $store['free_delivery']
                ? 'Gratis ongkir aktif'
                : 'Gratis ongkir tidak aktif' ?>
        </span>

        <input
            type="checkbox"
            id="free-delivery-toggle"
            <?= (bool) $store['free_delivery'] ? 'checked' : '' ?>
        >

        <span
            class="switch"
            aria-hidden="true"
        ></span>
    </label>

    <p
        class="muted"
        id="free-delivery-feedback"
        role="status"
        aria-live="polite"
    ></p>

</section>

<section class="dashboard-card">

    <div class="card-heading">
        <div>
            <h3>Profil warung</h3>

            <p class="muted">
                Informasi ini digunakan untuk menampilkan
                identitas warung.
            </p>
        </div>
    </div>

    <form
        class="store-settings-form"
        id="store-settings-form"
    >

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
            
            <div class="store-icon-field">
                <span class="store-icon-label">
                    Ikon toko
                </span>
            
                <div class="store-icon-preview">
                    <img
                        id="store-icon-preview"
                        src="<?= !empty($store['icon_image'])
                            ? e(url($store['icon_image']))
                            : '' ?>"
                        alt="Pratinjau ikon toko"
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
                        Ikon toko
                    </div>
                </div>
            
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
                    Format JPG, PNG, atau WebP. Maksimal 2 MB.
                </small>
            
                <button
                    class="button button-primary"
                    id="save-store-icon"
                    type="button"
                    disabled
                >
                    Simpan ikon toko
                </button>
            
                <p
                    id="store-icon-feedback"
                    class="muted"
                    role="status"
                    aria-live="polite"
                ></p>
            </div>

            <label class="form-field">
                <span>Nama pemilik</span>

                <input
                    name="owner"
                    maxlength="80"
                    autocomplete="name"
                    value="<?= e($store['owner'] ?? '') ?>"
                    required
                >
            </label>

            <label class="form-field">
                <span>Nomor WhatsApp / telepon</span>

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

            <label class="form-field store-description-field">
                <span>Deskripsi warung</span>

                <textarea
                    name="description"
                    maxlength="500"
                    rows="4"
                    placeholder="Ceritakan tentang warung Anda"
                ><?= e($store['description'] ?? '') ?></textarea>
            </label>

        </div>

        <div class="product-form-actions">

            <button
                class="button button-primary"
                type="submit"
            >
                Simpan pengaturan
            </button>

            <button
                class="button button-outline"
                id="reset-store-settings"
                type="button"
            >
                Batalkan perubahan
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

<div class="dashboard-note">
    <strong>Catatan:</strong>

    <p>
        Data profil awal diambil dari database.
        Proses penyimpanan perubahan masih perlu
        dihubungkan ke backend PHP.
    </p>
</div>

<?php require __DIR__ . '/_footer.php'; ?>