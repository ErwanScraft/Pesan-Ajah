<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_PATH . '/warung_auth.php';

$store = require_store_owner();

$pageKey = 'stock';
$pageTitle = 'Stock';
$activePage = 'stock';

require __DIR__ . '/_header.php';

?>

<div class="stock-page">

    <div class="stock-page-heading">
        <div>
            <h2>Manajemen Stock</h2>
            <p>Atur jumlah persediaan setiap produk warung.</p>
        </div>
    </div>

    <section class="dashboard-card stock-card">

        <div class="stock-list-heading">
            <h3>Daftar Stock Produk</h3>
        </div>

        <div
            class="stock-list"
            id="manage-stock"
            aria-live="polite"
        >
            <div class="stock-loading">
                Memuat produk...
            </div>
        </div>

    </section>

</div>

<?php require __DIR__ . '/_footer.php'; ?>