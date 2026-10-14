<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once APP_PATH . '/warung_auth.php';

$store = require_store_owner();

$pageKey = 'orders';
$pageTitle = 'Pesanan';
$activePage = 'pesanan';

require __DIR__ . '/_header.php';

?>

<div class="welcome-row">
    <div>
        <h2>Daftar pesanan</h2>

        <p class="muted">
            Periksa pesanan dan perbarui status sesuai proses warung.
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
            <h3>Semua pesanan</h3>

            <p class="muted">
                Pesanan ditampilkan dalam tabel berikut.
            </p>
        </div>

        <span class="table-caption">
            Daftar pesanan
        </span>
    </div>

    <div class="table-wrap">

        <table class="data-table">

            <thead>
                <tr>
                    <th scope="col">ID pesanan</th>
                    <th scope="col">Pembeli</th>
                    <th scope="col">Ringkasan</th>
                    <th scope="col">Total</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>

            <tbody id="orders-table"></tbody>

        </table>

    </div>

</section>

<div class="dashboard-note">
    <strong>Catatan:</strong>

    <p>
        Halaman ini sudah dilindungi autentikasi PHP.
        Daftar pesanan masih menggunakan implementasi demo.
        Integrasi database diperlukan agar pesanan tersimpan
        di server dan hanya ditampilkan kepada warung pemiliknya.
    </p>
</div>

<?php require __DIR__ . '/_footer.php'; ?>