<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_PATH . '/warung_auth.php';

$store = require_store_owner();

$pageKey = 'products';
$pageTitle = 'Produk';
$activePage = 'produk';

$productCategories = require APP_ROOT . '/config/categories.php';

require __DIR__ . '/_header.php';

?>

<div class="products-page">

    <!-- Header halaman -->
    <div class="products-page-heading">
        <h2>Produk</h2>

        <span
            class="table-caption"
            id="product-count"
        >0 produk</span>
    </div>

    <!-- Form produk -->
    <section
        class="dashboard-card products-card"
        aria-labelledby="product-form-title"
    >

        <form
            class="product-form"
            id="product-form"
        >

            <div class="product-form-heading">
                <h3 id="product-form-title">
                    Tambah produk
                </h3>
            </div>

            <div class="product-form-grid">

                <!-- Nama -->
                <label class="form-field">
                    <span>Nama produk</span>

                    <input
                        type="text"
                        name="name"
                        maxlength="100"
                        placeholder="Nama produk"
                        autocomplete="off"
                        required
                    >
                </label>
                
                <!-- Varian produk -->
                <label class="form-field">
                    <span>
                        Varian
                        <small>(opsional)</small>
                    </span>
                
                    <input
                        type="text"
                        name="variant"
                        maxlength="100"
                        placeholder="Contoh: Goreng, Rasa Balado, Kuah"
                        autocomplete="off"
                    >
                </label>

                <!-- Kategori -->
                <label class="form-field">
                    <span>Kategori</span>

                    <select name="category" required>
                        <?php foreach ($productCategories as $category): ?>
                            <option value="<?= e($category) ?>">
                                <?= e($category) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </label>

                <!-- Harga -->
                <label class="form-field">
                    <span>Harga (Rp)</span>

                    <input
                        type="number"
                        name="price"
                        min="1"
                        step="1"
                        inputmode="numeric"
                        placeholder="15000"
                        required
                    >
                </label>

                <!-- Satuan -->
                <label class="form-field">
                    <span>Satuan</span>

                    <input
                        type="text"
                        name="unit"
                        maxlength="40"
                        placeholder="Contoh: 1 kg"
                        required
                    >
                </label>

                <!-- Foto produk -->
                <div class="form-field product-image-field">

                    <span id="product-image-label">
                        Foto produk
                    </span>

                    <div class="product-image-editor">

                        <div
                            class="product-image-preview"
                            id="product-image-preview"
                            role="group"
                            aria-labelledby="product-image-label"
                        >

                            <span id="product-image-placeholder">
                                Belum ada foto
                            </span>

                            <img
                                id="product-image-preview-img"
                                alt="Pratinjau foto produk"
                                loading="eager"
                                decoding="async"
                                hidden
                            >

                        </div>

                        <div class="product-image-actions">

                            <label
                                class="button button-outline product-image-picker"
                                for="product-image-input"
                            >
                                Pilih foto
                            </label>

                            <input
                                type="file"
                                name="image"
                                id="product-image-input"
                                accept="image/jpeg,image/png,image/webp"
                                aria-label="Pilih foto produk"
                                hidden
                            >

                            <button
                                class="button button-outline"
                                type="button"
                                id="remove-product-image"
                                hidden
                            >
                                Hapus foto
                            </button>

                        </div>

                        <p class="form-help">
                            JPG, PNG, WebP · Maks. 5 MB
                        </p>

                        <!-- Opsi hapus foto yang sudah tersimpan -->
                        <label
                            class="form-checkbox product-remove-image-option"
                            id="remove-product-image-option"
                            hidden
                        >

                            <input
                                type="checkbox"
                                name="remove_image"
                                id="remove-product-image-checkbox"
                            >

                            <span>Hapus foto tersimpan</span>

                        </label>

                    </div>

                </div>

                <!-- Label -->
                <label class="form-field">
                    <span>
                        Label produk
                        <small>(opsional)</small>
                    </span>

                    <input
                        type="text"
                        name="tag"
                        maxlength="40"
                        placeholder="Contoh: Terlaris"
                    >
                </label>

                <!-- Deskripsi -->
                <label class="form-field product-description-field">
                    <span>
                        Deskripsi
                        <small>(opsional)</small>
                    </span>

                    <textarea
                        name="description"
                        maxlength="5000"
                        rows="3"
                        placeholder="Deskripsi singkat produk"
                    ></textarea>
                </label>

            </div>

            <!-- Ketersediaan -->
            <label class="form-checkbox product-availability">

                <input
                    type="checkbox"
                    name="available"
                    checked
                >

                <span>Produk tersedia</span>

            </label>

            <!-- Tombol aksi -->
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

        <!-- Daftar produk -->
        <div class="products-list-heading">
            <h3>Daftar produk</h3>
        </div>

        <div
            class="product-manage-list"
            id="manage-products"
            aria-live="polite"
        ></div>

    </section>

</div>

<?php require __DIR__ . '/_footer.php'; ?>