<?php
declare(strict_types=1);

$pageTitle = 'Tentang Kami — Pesan Ajah';
$pageDescription = 'Kenali Pesan Ajah, aplikasi belanja warung lokal yang membantu pelanggan dan UMKM berjualan dengan lebih praktis.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta
        name="description"
        content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>"
    >
    <meta name="theme-color" content="#ffffff">

    <link rel="icon" href="assets/images/logo.webp" type="image/webp">
    <link rel="stylesheet" href="assets/css/about.css">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="index.php" aria-label="Pesan Ajah - Beranda">
            <img
                src="assets/images/logo.webp"
                alt="Logo Pesan Ajah"
                width="42"
                height="42"
            >
            <span>Pesan Ajah</span>
        </a>

        <nav class="main-nav" aria-label="Navigasi utama">
            <a href="index.php">Beranda</a>
            <a href="about.php" class="active" aria-current="page">Tentang Kami</a>
        </nav>

        <a class="header-cta" href="index.php">Mulai Belanja</a>
    </div>
</header>

<main>
    <!-- HERO -->
    <section class="hero">
        <div class="hero-inner">
            <span class="eyebrow">
                CERITA DI BALIK PESAN AJAH
            </span>

            <h1>
                Belanja lebih praktis.
                <span>Dukung warung sekitar.</span>
            </h1>

            <p class="hero-description">
                Kami ingin membuat pengalaman belanja di warung lokal
                menjadi lebih mudah, terorganisir, dan sesuai dengan
                kebiasaan belanja digital saat ini.
            </p>

            <div class="hero-actions">
                <a href="index.php" class="button button-primary">
                    Jelajahi Warung
                    <span aria-hidden="true">→</span>
                </a>

                <a href="#cerita-kami" class="button button-secondary">
                    Kenali Pesan Ajah
                </a>
            </div>

            <div class="hero-note">
                <span class="status-dot" aria-hidden="true"></span>
                Berawal dari warung lokal Banyu Kuning
            </div>
        </div>
    </section>

    <!-- LATAR BELAKANG -->
    <section class="section story-section" id="cerita-kami">
        <div class="section-label">01 — CERITA KAMI</div>

        <div class="story-grid">
            <div>
                <h2>
                    Ide sederhana untuk
                    <span>kebutuhan sehari-hari.</span>
                </h2>
            </div>

            <div class="story-copy">
                <p>
                    Pesan Ajah berawal dari pengamatan langsung di
                    lapangan. Warung lokal memiliki peran penting dalam
                    memenuhi kebutuhan sehari-hari, tetapi proses
                    penjualannya masih bisa dibuat lebih fleksibel
                    dengan bantuan teknologi.
                </p>

                <p>
                    Saat ini, banyak orang sudah terbiasa melihat
                    produk, membandingkan harga, memasukkan barang
                    ke keranjang, dan melakukan checkout melalui
                    marketplace besar.
                </p>

                <p>
                    Dari situ, kami ingin menghadirkan pengalaman
                    belanja yang serupa dalam bentuk yang lebih
                    sederhana dan dikhususkan untuk warung serta
                    UMKM kecil di sekitar kita.
                </p>
            </div>
        </div>
    </section>

    <!-- STUDI KASUS -->
    <section class="section problem-section">
        <div class="section-label">02 — MASALAH SEHARI-HARI</div>

        <div class="section-heading">
            <h2>
                Hal sederhana yang sering
                <span>menyita waktu.</span>
            </h2>

            <p>
                Kami melihat ada kebutuhan yang bisa dibuat lebih
                praktis, baik dari sisi pelanggan maupun pemilik warung.
            </p>
        </div>

        <div class="problem-grid">
            <article class="info-card">
                <div class="card-number">01</div>
                <h3>“Barangnya masih ada?”</h3>
                <p>
                    Pelanggan sering harus menghubungi atau datang
                    langsung ke warung hanya untuk memastikan
                    apakah barang yang dicari masih tersedia.
                </p>
            </article>

            <article class="info-card">
                <div class="card-number">02</div>
                <h3>“Harganya berapa?”</h3>
                <p>
                    Pertanyaan harga yang sama bisa muncul berkali-kali.
                    Pelanggan perlu bertanya, sementara pemilik warung
                    harus meluangkan waktu untuk menjawabnya.
                </p>
            </article>

            <article class="info-card">
                <div class="card-number">03</div>
                <h3>Produk perlu dikelola</h3>
                <p>
                    Informasi produk, harga, dan ketersediaan perlu
                    diperbarui agar pelanggan mendapatkan informasi
                    yang lebih jelas dan pemilik warung lebih mudah
                    mengelola jualannya.
                </p>
            </article>
        </div>

        <div class="problem-conclusion">
            <div class="conclusion-mark" aria-hidden="true">✓</div>
            <p>
                <strong>Tujuan kami sederhana:</strong>
                mengurangi kerepotan yang tidak perlu agar proses
                jual beli menjadi lebih praktis dan terorganisir.
            </p>
        </div>
    </section>

    <!-- SOLUSI -->
    <section class="section solution-section">
        <div class="section-label">03 — SOLUSI KAMI</div>

        <div class="section-heading">
            <h2>
                Satu aplikasi,
                <span>dua sisi yang terbantu.</span>
            </h2>

            <p>
                Pesan Ajah dirancang untuk membantu pelanggan
                berbelanja dan membantu pemilik warung mengelola
                produk dengan lebih mudah.
            </p>
        </div>

        <div class="solution-grid">
            <article class="solution-card customer-card">
                <div class="solution-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H6"
                              stroke="currentColor" stroke-width="1.8"
                              stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="10" cy="20" r="1.3" fill="currentColor"/>
                        <circle cx="18" cy="20" r="1.3" fill="currentColor"/>
                    </svg>
                </div>

                <span class="card-kicker">UNTUK PELANGGAN</span>
                <h3>Belanja tanpa banyak tanya.</h3>
                <p>
                    Cari produk dari warung lokal, periksa informasi
                    harga dan status ketersediaan, pilih jumlah barang,
                    lalu lanjutkan pemesanan melalui keranjang.
                </p>

                <ul class="feature-list">
                    <li>Informasi produk lebih mudah dilihat</li>
                    <li>Harga tercantum pada katalog</li>
                    <li>Keranjang dan checkout yang praktis</li>
                    <li>Pilihan pengantaran atau ambil sendiri</li>
                </ul>

                <a href="index.php" class="text-link">
                    Mulai belanja <span aria-hidden="true">→</span>
                </a>
            </article>

            <article class="solution-card seller-card">
                <div class="solution-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 10V20h16V10M3 4h18l-1.5 6H4.5L3 4Z"
                              stroke="currentColor" stroke-width="1.8"
                              stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 20v-6h6v6"
                              stroke="currentColor" stroke-width="1.8"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <span class="card-kicker">UNTUK PEMILIK WARUNG</span>
                <h3>Kelola jualan lebih simpel.</h3>
                <p>
                    Pemilik warung dapat menggunakan Dashboard Warung
                    untuk mengelola informasi produk sehingga
                    pembaruan katalog lebih mudah dilakukan.
                </p>

                <ul class="feature-list">
                    <li>Tambah dan kelola produk</li>
                    <li>Perbarui harga dan informasi barang</li>
                    <li>Atur status ketersediaan produk</li>
                    <li>Kelola informasi warung dari dashboard</li>
                </ul>

                <a href="login.php" class="text-link">
                    Masuk Dashboard Warung
                    <span aria-hidden="true">→</span>
                </a>
            </article>
        </div>

        <p class="section-footnote">
            Informasi yang ditampilkan mengikuti data yang diperbarui
            oleh masing-masing warung. Ketersediaan produk dapat berubah.
        </p>
    </section>

    <!-- CAKUPAN -->
    <section class="section coverage-section">
        <div class="coverage-content">
            <div class="section-label">04 — LANGKAH KAMI</div>

            <h2>
                Dimulai dari Banyu Kuning,
                <span>bertumbuh bersama.</span>
            </h2>

            <p>
                Saat ini, Pesan Ajah berfokus melayani mitra warung
                di wilayah Banyu Kuning. Kami ingin membangun layanan
                yang bermanfaat bagi lingkungan sekitar terlebih
                dahulu, lalu memperluas jangkauannya secara bertahap.
            </p>

            <p>
                Ke depannya, kami berharap semakin banyak warung
                dan UMKM lokal dapat bergabung dan merasakan manfaat
                dari sistem penjualan digital yang lebih praktis.
            </p>

            <div class="coverage-tag">
                <span class="status-dot" aria-hidden="true"></span>
                Fokus saat ini: Banyu Kuning
            </div>
        </div>

        <div class="coverage-visual" aria-hidden="true">
            <div class="orbit orbit-one"></div>
            <div class="orbit orbit-two"></div>
            <div class="coverage-center">
                <img src="assets/images/logo.webp" alt="" width="72" height="72">
                <strong>Pesan Ajah</strong>
                <span>Warung lokal, lebih dekat</span>
            </div>
            <span class="orbit-label label-one">Warung</span>
            <span class="orbit-label label-two">Pelanggan</span>
            <span class="orbit-label label-three">UMKM</span>
        </div>
    </section>

    <!-- DUKUNGAN -->
    <section class="section support-section">
        <div class="support-card">
            <div class="section-label">05 — DUKUNG PENGEMBANGAN</div>

            <h2>
                Agar bisa terus berkembang,
                <span>kami membutuhkan dukungan.</span>
            </h2>

            <p>
                Mengembangkan dan merawat aplikasi membutuhkan waktu,
                biaya operasional, serta dukungan finansial. Dukungan
                dari pengguna, mitra warung, dan masyarakat akan
                membantu Pesan Ajah terus diperbaiki dan dikembangkan.
            </p>

            <p>
                Setiap dukungan berarti bagi keberlanjutan layanan ini
                dan membuka peluang agar lebih banyak UMKM lokal
                dapat terbantu di kemudian hari.
            </p>

            <a href="index.php" class="button button-primary">
                Dukung dengan Menggunakan Pesan Ajah
                <span aria-hidden="true">→</span>
            </a>

            <span class="support-note">
                Untuk saat ini, dukungan bisa dimulai dengan
                menggunakan layanan dan mengenalkannya kepada orang sekitar.
            </span>
        </div>
    </section>

    <!-- DEVELOPER -->
    <section class="section developer-section">
        <div class="developer-mark" aria-hidden="true">PA</div>

        <div class="developer-copy">
            <span class="section-label">06 — PENGEMBANG</span>
            <h2>Dirintis oleh Erwan Sudandi.</h2>
            <p>
                Pesan Ajah dikembangkan dengan tujuan sederhana:
                membantu warung lokal beradaptasi dengan kebiasaan
                belanja digital tanpa membuat prosesnya menjadi rumit.
            </p>
        </div>
    </section>

    <!-- PENUTUP -->
    <section class="closing-section">
        <div class="closing-inner">
            <span class="eyebrow">PESAN AJAH</span>
            <h2>Kebutuhan harian, lebih dekat.</h2>
            <p>
                Mari dukung warung lokal dan buat belanja sehari-hari
                jadi lebih praktis.
            </p>

            <a href="index.php" class="button button-light">
                Jelajahi Warung
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <a class="brand footer-brand" href="index.php">
            <img
                src="assets/images/logo.webp"
                alt=""
                width="36"
                height="36"
            >
            <span>Pesan Ajah</span>
        </a>

        <p>
            Belanja lebih praktis, dukung warung sekitar.
        </p>

        <nav class="footer-nav" aria-label="Navigasi footer">
            <a href="index.php">Beranda</a>
            <a href="about.php" aria-current="page">Tentang Kami</a>
            <a href="login.php">Dashboard Warung</a>
        </nav>

        <div class="footer-bottom">
            <span>
                &copy; <?= date('Y') ?> Pesan Ajah
            </span>
            <span>Dikembangkan oleh Erwan Sudandi</span>
        </div>
    </div>
</footer>

</body>
</html>
