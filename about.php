
<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Tentang Kami — Pesan Ajah';
$pageDescription = 'Pesan Ajah menghubungkan pelanggan dengan warung lokal melalui pengalaman belanja digital yang praktis dan membantu UMKM mengelola produk dengan lebih mudah.';

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
                Belanja lebih mudah,
                <span>warung lokal makin dekat.</span>
            </h1>

            <p>
                Pesan Ajah menghubungkan pelanggan dengan warung sekitar
                melalui pengalaman belanja digital yang praktis, jelas,
                dan mudah digunakan.
            </p>

            <a href="/" class="about-button">
                Jelajahi Warung <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>

    <!-- CERITA KAMI -->
    <section class="about-section container">
        <div class="about-intro">
            <span class="about-label">CERITA KAMI</span>

            <h2>Berawal dari kebutuhan sehari-hari.</h2>

            <p>
                Mengetahui harga dan ketersediaan produk seharusnya tidak
                perlu melalui pertanyaan yang sama berulang kali. Namun,
                hal ini masih menjadi bagian dari keseharian pelanggan
                dan pemilik warung.
            </p>

            <p>
                Pesan Ajah hadir untuk membuat informasi produk lebih
                mudah diakses dan proses pemesanan lebih terorganisir.
                Dengan pengalaman belanja digital yang sederhana,
                pelanggan dapat memilih kebutuhan dengan lebih praktis,
                sementara pemilik warung dapat mengelola produk melalui
                satu dashboard.
            </p>
        </div>
    </section>

    <!-- MANFAAT -->
    <section class="about-benefits">
        <div class="container">
            <div class="about-section-heading">
                <span class="about-label">MANFAAT</span>
                <h2>Dirancang untuk kebutuhan keduanya.</h2>
            </div>

            <div class="about-benefits__grid">
                <article class="about-benefit">
                    <div class="about-benefit__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path
                                d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H6"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <circle cx="10" cy="20" r="1.3" fill="currentColor"/>
                            <circle cx="18" cy="20" r="1.3" fill="currentColor"/>
                        </svg>
                    </div>

                    <h3>Untuk Pelanggan</h3>

                    <p>
                        Temukan produk, periksa harga dan ketersediaannya,
                        lalu lakukan pemesanan dengan proses yang lebih praktis.
                    </p>
                </article>

                <article class="about-benefit">
                    <div class="about-benefit__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path
                                d="M4 10V20h16V10M3 4h18l-1.5 6H4.5L3 4Z"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M9 20v-6h6v6"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>

                    <h3>Untuk Pemilik Warung</h3>

                    <p>
                        Kelola produk, harga, dan ketersediaan dengan mudah
                        melalui Dashboard Warung agar informasi jualan tetap
                        terorganisir dan mudah diperbarui.
                    </p>

                    <a href="/warung/login.php" class="about-text-link">
                        Masuk Dashboard Warung →
                    </a>
                </article>
            </div>
        </div>
    </section>

    <!-- CAKUPAN LAYANAN -->
    <section class="about-section container">
        <div class="about-coverage">
            <span class="about-label">CAKUPAN LAYANAN</span>

            <h2>Bertumbuh bersama warung lokal.</h2>

            <p>
                Pesan Ajah saat ini berfokus melayani mitra warung
                di wilayah Banyu Kuning. Kami berharap dapat terus
                berkembang, menjangkau lebih banyak wilayah, dan
                membuka peluang digital bagi lebih banyak UMKM lokal.
            </p>
        </div>
    </section>

    <!-- DUKUNGAN PENGEMBANGAN -->
    <section class="about-support">
        <div class="container about-support__inner">
            <div>
                <span class="about-label">DUKUNG PENGEMBANGAN</span>

                <h2>Dukungan Anda berarti bagi kami.</h2>

                <p>
                    Pesan Ajah terus dikembangkan agar semakin bermanfaat
                    bagi pelanggan dan warung lokal. Menggunakan layanan,
                    mengajak warung bergabung, atau membagikan Pesan Ajah
                    kepada orang sekitar merupakan dukungan nyata bagi
                    keberlanjutan dan pengembangan platform ini.
                </p>
            </div>

            <a href="/" class="about-button about-button--light">
                Gunakan Pesan Ajah <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>

    <!-- PENGEMBANG -->
    <section class="about-developer container">
        <div class="about-developer__mark" aria-hidden="true">PA</div>

        <div>
            <span class="about-label">PENGEMBANG</span>

            <h2>Erwan Sudandi</h2>

            <p>
                Mengembangkan Pesan Ajah sebagai upaya menghadirkan
                solusi digital yang praktis dan relevan bagi warung
                serta UMKM lokal.
            </p>
        </div>
    </section>
</main>

<?php require __DIR__ . '/_footer.php'; ?>

</body>
</html>
