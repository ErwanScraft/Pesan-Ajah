<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Tentang Kami — Pesan Ajah';
$pageDescription = 'Pesan Ajah membantu pelanggan berbelanja dan warung lokal mengelola produk dengan lebih praktis.';

?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#176b45">
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="index, follow">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="icon" type="image/webp" href="/assets/images/logo.webp">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/about.css">
</head>

<body class="about-page">

<?php
$isStorePage = false;
require __DIR__ . '/_header.php';
?>

<main>
    <!-- HERO -->
    <section class="about-hero">
        <div class="container about-hero__inner">
            <span class="about-eyebrow">TENTANG PESAN AJAH</span>

            <h1>
                Belanja lebih praktis,
                <span>dukung warung sekitar.</span>
            </h1>

            <p>
                Cara sederhana untuk menghubungkan pelanggan dengan
                warung lokal melalui pengalaman belanja digital.
            </p>

            <a href="/" class="about-button">
                Jelajahi Warung <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>

    <!-- CERITA -->
    <section class="about-section container">
        <div class="about-intro">
            <span class="about-label">CERITA KAMI</span>

            <h2>Berawal dari kebutuhan nyata.</h2>

            <p>
                Pelanggan sering bertanya tentang harga dan ketersediaan
                barang. Pemilik warung pun harus menjawab pertanyaan
                serupa berulang kali.
            </p>

            <p>
                Dari pengamatan tersebut, Pesan Ajah hadir untuk
                membuat informasi produk lebih mudah diakses dan
                proses jual beli lebih praktis, seperti pengalaman
                checkout di marketplace yang sudah familier.
            </p>
        </div>
    </section>

    <!-- MANFAAT -->
    <section class="about-benefits">
        <div class="container">
            <div class="about-section-heading">
                <span class="about-label">MANFAAT</span>
                <h2>Satu aplikasi, dua manfaat.</h2>
            </div>

            <div class="about-benefits__grid">
                <article class="about-benefit">
                    <div class="about-benefit__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H6"
                                  stroke="currentColor" stroke-width="1.8"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="10" cy="20" r="1.3" fill="currentColor"/>
                            <circle cx="18" cy="20" r="1.3" fill="currentColor"/>
                        </svg>
                    </div>

                    <h3>Untuk Pelanggan</h3>

                    <p>
                        Lihat produk, harga, dan status ketersediaan,
                        lalu pilih barang dan checkout dengan lebih mudah.
                    </p>
                </article>

                <article class="about-benefit">
                    <div class="about-benefit__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M4 10V20h16V10M3 4h18l-1.5 6H4.5L3 4Z"
                                  stroke="currentColor" stroke-width="1.8"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 20v-6h6v6"
                                  stroke="currentColor" stroke-width="1.8"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <h3>Untuk Pemilik Warung</h3>

                    <p>
                        Kelola produk, harga, dan ketersediaan melalui
                        Dashboard Warung dengan lebih simpel dan terorganisir.
                    </p>

                    <a href="/warung/login.php" class="about-text-link">
                        Masuk Dashboard Warung →
                    </a>
                </article>
            </div>
        </div>
    </section>

    <!-- CAKUPAN -->
    <section class="about-section container">
        <div class="about-coverage">
            <span class="about-label">CAKUPAN LAYANAN</span>

            <h2>Dimulai dari Banyu Kuning.</h2>

            <p>
                Saat ini Pesan Ajah berfokus melayani mitra warung
                di wilayah Banyu Kuning. Ke depannya, kami berharap
                dapat menjangkau lebih banyak wilayah dan membantu
                lebih banyak UMKM lokal.
            </p>
        </div>
    </section>

    <!-- DUKUNGAN -->
    <section class="about-support">
        <div class="container about-support__inner">
            <div>
                <span class="about-label">DUKUNG PENGEMBANGAN</span>

                <h2>Bantu Pesan Ajah terus berkembang.</h2>

                <p>
                    Pengembangan aplikasi membutuhkan biaya operasional
                    dan dukungan finansial. Menggunakan layanan,
                    mengajak warung bergabung, dan mengenalkan Pesan
                    Ajah kepada orang sekitar adalah bentuk dukungan
                    yang berarti.
                </p>
            </div>

            <a href="/" class="about-button about-button--light">
                Gunakan Pesan Ajah <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>

    <!-- DEVELOPER -->
    <section class="about-developer container">
        <div class="about-developer__mark" aria-hidden="true">PA</div>

        <div>
            <span class="about-label">PENGEMBANG</span>
            <h2>Erwan Sudandi</h2>
            <p>
                Membangun pengalaman belanja digital yang lebih praktis
                untuk warung dan UMKM lokal.
            </p>
        </div>
    </section>
</main>

<?php require __DIR__ . '/_footer.php'; ?>

</body>
</html>
