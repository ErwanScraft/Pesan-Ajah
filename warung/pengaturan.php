<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_PATH . '/warung_auth.php';

/*
|--------------------------------------------------------------------------
| AUTENTIKASI WARUNG
|--------------------------------------------------------------------------
*/

$store = require_store_owner();

/*
|--------------------------------------------------------------------------
| INFORMASI HALAMAN
|--------------------------------------------------------------------------
*/

$pageKey = 'settings';
$pageTitle = 'Pengaturan';
$activePage = 'pengaturan';

require __DIR__ . '/_header.php';

?>

<!-- =========================================================
     HEADER HALAMAN
========================================================= -->

<div class="welcome-row settings-page-heading">
    <div>
        <span class="eyebrow eyebrow-plain">
            PREFERENSI WARUNG
        </span>

        <h2>Pengaturan warung</h2>

        <p class="muted">
            Kelola identitas, informasi profil, dan operasional warung.
        </p>
    </div>
</div>


<!-- =========================================================
     STATUS OPERASIONAL WARUNG
========================================================= -->

<section
    class="dashboard-card store-control"
    aria-labelledby="store-status-heading"
>
    <div>
        <span class="eyebrow eyebrow-plain">
            OPERASIONAL
        </span>

        <h3 id="store-status-heading">
            Status warung
        </h3>

        <p class="muted">
            Atur apakah warung menerima pesanan baru.
        </p>
    </div>

    <label
        class="switch-label"
        for="store-open-toggle"
    >
        <span id="store-toggle-text">
            <?= (bool) ($store['is_open'] ?? false)
                ? 'Warung sedang buka'
                : 'Warung sedang tutup' ?>
        </span>

        <input
            type="checkbox"
            id="store-open-toggle"
            <?= (bool) ($store['is_open'] ?? false)
                ? 'checked'
                : '' ?>
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
        aria-live="polite"
    ></p>
</section>


<!-- =========================================================
     PENGATURAN GRATIS ONGKIR
========================================================= -->

<section
    class="dashboard-card store-control"
    aria-labelledby="free-delivery-heading"
>
    <div>
        <span class="eyebrow eyebrow-plain">
            PENGIRIMAN
        </span>

        <h3 id="free-delivery-heading">
            Gratis ongkir
        </h3>

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
            <?= (bool) ($store['free_delivery'] ?? false)
                ? 'Gratis ongkir aktif'
                : 'Gratis ongkir tidak aktif' ?>
        </span>

        <input
            type="checkbox"
            id="free-delivery-toggle"
            <?= (bool) ($store['free_delivery'] ?? false)
                ? 'checked'
                : '' ?>
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


<!-- =========================================================
     PROFIL WARUNG
========================================================= -->

<section
    class="dashboard-card"
    aria-labelledby="store-profile-heading"
>
    <div class="card-heading">
        <div>
            <h3 id="store-profile-heading">
                Profil warung
            </h3>

            <p class="muted">
                Kelola informasi dan identitas yang ditampilkan
                kepada pembeli.
            </p>
        </div>
    </div>

    <form
        class="store-settings-form"
        id="store-settings-form"
    >

        <!-- =================================================
             IKON TOKO

             Terpisah dari product-form-grid agar komponen
             gambar tidak memengaruhi tata letak formulir.
        ================================================== -->

        <section
            class="store-icon-section"
            aria-labelledby="store-icon-heading"
        >
            <div class="store-icon-heading">
                <h4 id="store-icon-heading">
                    Ikon toko
                </h4>

                <p class="muted">
                    Gunakan gambar yang mudah dikenali pembeli.
                    Ikon akan ditampilkan di navigasi warung.
                </p>
            </div>

            <div class="store-icon-layout">

                <!-- Pratinjau ikon toko -->

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
                        <span aria-hidden="true">+</span>

                        <small>
                            Belum ada ikon
                        </small>
                    </div>

                </div>


                <!-- Kontrol unggah ikon -->

                <div class="store-icon-controls">

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
                        Format JPG, PNG, atau WebP.
                        Maksimal 2 MB.
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
                        class="store-icon-feedback muted"
                        role="status"
                        aria-live="polite"
                    ></p>

                </div>

            </div>
        </section>


        <!-- =================================================
             FORMULIR INFORMASI WARUNG

             Semua field profil tetap berada dalam grid.
        ================================================== -->

        <div class="product-form-grid">

            <!-- Nama warung -->

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


            <!-- Nama pemilik -->

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


            <!-- Nomor WhatsApp -->

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


            <!-- Alamat warung -->

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


            <!-- Deskripsi warung -->

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


        <!-- =================================================
             AKSI FORMULIR
        ================================================== -->

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


        <!-- Pesan status penyimpanan profil -->

        <p
            class="store-settings-help muted"
            id="store-settings-feedback"
            role="status"
            aria-live="polite"
        ></p>

    </form>
</section>


<?php require __DIR__ . '/_footer.php'; ?>