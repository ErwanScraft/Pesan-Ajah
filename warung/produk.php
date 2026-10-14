<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_PATH . '/warung_auth.php';

$store = require_store_owner();

$pageKey = 'products';
$pageTitle = 'Produk';
$activePage = 'produk';

require __DIR__ . '/_header.php';

?>

<div class="welcome-row">
    <div>
        <h2>Kelola produk</h2>

        <p class="muted">
            Tambah, edit, arsipkan, dan atur ketersediaan produk.
        </p>
    </div>

    <span class="demo-badge">
        <span class="dot"></span>
        Mode demo
    </span>
</div>

<section class="dashboard-card">

    <div class="card-heading">
        <div>
            <h3>Daftar produk</h3>

            <p class="muted">
                Produk yang tersedia akan ditampilkan
                di katalog marketplace.
            </p>
        </div>

        <span
            class="table-caption"
            id="product-count"
        >0 produk</span>
    </div>

    <form
        class="product-form"
        id="product-form"
    >

        <div class="product-form-heading">
            <h4 id="product-form-title">
                Tambah produk baru
            </h4>

            <p class="muted">
                Isi informasi produk dengan benar.
            </p>
        </div>

        <div class="product-form-grid">

            <label class="form-field">
                <span>Nama produk</span>

                <input
                    type="text"
                    name="name"
                    maxlength="100"
                    placeholder="Contoh: Beras Premium"
                    required
                >
            </label>

            <label class="form-field">
                <span>Kategori</span>

                <select name="category" required>
                    <option value="Sembako">Sembako</option>
                    <option value="Makanan">Makanan</option>
                    <option value="Minuman">Minuman</option>
                    <option value="Kebutuhan Rumah">
                        Kebutuhan Rumah
                    </option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </label>

            <label class="form-field">
                <span>Harga (Rp)</span>

                <input
                    type="number"
                    name="price"
                    min="1"
                    step="1"
                    placeholder="15000"
                    required
                >
            </label>

            <label class="form-field">
                <span>Satuan</span>

                <input
                    type="text"
                    name="unit"
                    maxlength="40"
                    placeholder="1 kg, 1 bungkus"
                    required
                >
            </label>

            <label class="form-field">
                <span>Ikon produk (emoji)</span>

                <input
                    type="text"
                    name="emoji"
                    maxlength="8"
                    value="📦"
                    required
                >
            </label>

            <label class="form-field">
                <span>Label produk (opsional)</span>

                <input
                    type="text"
                    name="tag"
                    maxlength="40"
                    placeholder="Produk pilihan"
                >
            </label>
            
            <label class="form-field">
                <span>Deskripsi produk</span>
            
                <textarea
                    name="description"
                    maxlength="5000"
                    rows="3"
                    placeholder="Deskripsi produk"
                ></textarea>
            </label>

        </div>

        <label class="form-checkbox">
            <input
                type="checkbox"
                name="available"
                checked
            >

            <span>
                Produk tersedia dan dapat dipesan
            </span>
        </label>

        <div class="product-form-actions">

            <button
                class="button button-primary"
                type="submit"
            >
                Simpan produk
            </button>

            <button
                class="button button-outline"
                id="cancel-product"
                type="button"
                hidden
            >
                Batal
            </button>

        </div>

    </form>

    <div
        class="product-manage-list"
        id="manage-products"
        aria-live="polite"
    ></div>

</section>

<div class="dashboard-note">
    <strong>Catatan:</strong>

    <p>
        Halaman ini sudah dilindungi autentikasi PHP.
        Pengelolaan produk masih menggunakan implementasi
        JavaScript yang ada. Penyimpanan permanen dan
        pemisahan produk per warung membutuhkan integrasi
        database MySQL.
    </p>
</div>

<?php require __DIR__ . '/_footer.php'; ?>