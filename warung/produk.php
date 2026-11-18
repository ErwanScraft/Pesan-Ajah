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
        <div class="products-heading-copy">
            <h2>Produk</h2>
            <p>Kelola katalog dan ketersediaan produk warung.</p>
        </div>
    
        <div class="products-heading-actions">
    
            <button
                class="button button-primary"
                type="button"
                id="add-product-button"
                aria-haspopup="dialog"
                aria-controls="product-editor-modal"
            >
                <span aria-hidden="true">+</span>
                Tambah produk
            </button>
        </div>
    </div>

    <!-- Form produk -->
    <section
        class="dashboard-card products-card"
        aria-labelledby="products-list-title"
    >
        <div class="products-list-heading">
            <div>
                <h3 id="products-list-title">Daftar produk</h3>
                <p>Atur katalog dan ketersediaan produk.</p>
            </div>
        
            <span class="table-caption" id="product-count">0 produk</span>
        </div>
        
        <div class="products-toolbar">
            <label class="products-search">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16 16 4 4"></path>
                </svg>
        
                <input
                    type="search"
                    id="product-search"
                    placeholder="Cari nama atau varian produk..."
                    autocomplete="off"
                >
            </label>
        
            <select
                id="product-status-filter"
                aria-label="Filter status produk"
            >
                <option value="all">Semua status</option>
                <option value="available">Tersedia</option>
                <option value="unavailable">Habis</option>
            </select>
        </div>
    
        <div
            class="product-manage-list"
            id="manage-products"
            aria-live="polite"
        ></div>
    </section>

    <!-- Form tambah/edit produk -->
    <div id="product-form-host" hidden>
        <form class="product-form product-form--sheet" id="product-form">
    
            <!-- Nama -->
            <label class="form-field">
                <span>Nama</span>
                <input
                    type="text"
                    name="name"
                    maxlength="100"
                    placeholder="Nama produk"
                    autocomplete="off"
                    required
                >
            </label>
    
            <!-- Varian -->
            <label class="form-field">
                <span>Varian <small>(opsional)</small></span>
                <input
                    type="text"
                    name="variant"
                    maxlength="100"
                    placeholder="Contoh: Goreng, Balado"
                    autocomplete="off"
                >
            </label>
    
            <!-- Kategori: tetap wajib untuk API -->
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
    
            <!-- Satuan -->
            <div class="form-field product-unit-field">
                <span>Satuan</span>
    
                <div class="product-unit-inputs">
                    <input
                        type="number"
                        name="unit_quantity"
                        min="0.001"
                        max="999999"
                        step="0.001"
                        inputmode="decimal"
                        placeholder="Jumlah"
                        aria-label="Jumlah satuan"
                        required
                    >
    
                    <select
                        name="unit_name"
                        aria-label="Jenis satuan"
                        required
                    >
                        <option value="pcs">Pcs</option>
                        <option value="porsi">Porsi</option>
                        <option value="buah">Buah</option>
                        <option value="gelas">Gelas</option>
                        <option value="bungkus">Bungkus</option>
                        <option value="botol">Botol</option>
                        <option value="cup">Cup</option>
                        <option value="gram">Gram</option>
                        <option value="kg">Kg</option>
                        <option value="ml">Ml</option>
                        <option value="liter">Liter</option>
                        <option value="ikat">Ikat</option>
                        <option value="pack">Pack</option>
                        <option value="lembar">Lembar</option>
                        <option value="pasang">Pasang</option>
                    </select>
                </div>
            </div>
    
            <!-- Foto -->
            <div class="form-field product-image-field">
                <span>Gambar</span>
    
                <div class="product-image-editor">
    
                    <div
                        class="product-image-preview"
                        id="product-image-preview"
                    >
                        <span id="product-image-placeholder">
                            <svg viewBox="0 0 24 24" width="30" height="30"
                                fill="none" stroke="currentColor"
                                stroke-width="1.6">
                                <rect x="3" y="3" width="18" height="18" rx="3"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <path d="m21 15-5-5L5 21"/>
                            </svg>
                            Belum ada foto
                        </span>
    
                        <img
                            id="product-image-preview-img"
                            alt="Pratinjau gambar produk"
                            hidden
                        >
                    </div>
    
                    <div class="product-image-actions">
    
                        <button
                            type="button"
                            class="button button-outline"
                            id="upload-product-image"
                        >
                            Upload File
                        </button>
    
                        <button
                            type="button"
                            class="button button-outline"
                            id="camera-product-image"
                        >
                            Kamera
                        </button>
    
                        <button
                            type="button"
                            class="button button-outline"
                            id="remove-product-image"
                            hidden
                        >
                            Hapus
                        </button>
    
                    </div>
    
                    <input
                        type="file"
                        name="image"
                        id="product-image-input"
                        accept="image/jpeg,image/png,image/webp"
                        hidden
                    >
    
                    <p class="form-help">
                        JPG, PNG, WebP · Maks. 5 MB
                    </p>
    
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
    
            <!-- Deskripsi -->
            <label class="form-field product-description-field">
                <span>Deskripsi <small>(opsional)</small></span>
                <textarea
                    name="description"
                    maxlength="5000"
                    rows="4"
                    placeholder="Tuliskan deskripsi produk..."
                ></textarea>
            </label>
    
            <!-- Label produk -->
            <label class="form-field">
                <span>Label produk <small>(opsional)</small></span>
                <input
                    type="text"
                    name="tag"
                    maxlength="40"
                    placeholder="Contoh: Terlaris"
                >
            </label>
    
            <!-- Ketersediaan -->
            <label class="form-checkbox product-availability">
                <input
                    type="checkbox"
                    name="available"
                    checked
                >
                <span>Produk tersedia</span>
            </label>
    
            <!-- Simpan -->
            <div class="product-form-actions">
                <button
                    class="button button-primary"
                    type="submit"
                >
                    Simpan
                </button>
            </div>
    
        </form>
    </div>

</div>

<?php require __DIR__ . '/_footer.php'; ?>