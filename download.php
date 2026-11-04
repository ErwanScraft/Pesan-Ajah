<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Download Aplikasi — Pesan Ajah';
$pageDescription = 'Akses Pesan Ajah dengan lebih praktis melalui aplikasi Android. Aplikasi sedang dipersiapkan dan akan tersedia untuk diunduh segera.';

$apkPath = __DIR__ . '/assets/downloads/pesan-ajah.apk';
$apkAvailable = is_file($apkPath) && is_readable($apkPath);

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#176b45">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="robots" content="index, follow">

    <title><?= e($pageTitle) ?></title>

    <link rel="icon" type="image/webp" href="/assets/images/logo.webp">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="/assets/css/style.css?v=<?= asset_version('assets/css/style.css') ?>"
    >

    <link
        rel="stylesheet"
        href="/assets/css/download.css?v=<?= asset_version('assets/css/download.css') ?>"
    >
</head>

<body class="download-page">

    <!-- HEADER -->
    <header class="download-header">
        <div class="download-container download-header__inner">

            <a
                class="download-brand"
                href="/"
                aria-label="Pesan Ajah — Beranda"
            >
                <img
                    src="/assets/images/logo.webp"
                    alt=""
                    width="42"
                    height="42"
                >

                <span>Pesan Ajah</span>
            </a>

            <a class="download-home-link" href="/">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m14 18-6-6 6-6M8 12h13" />
                </svg>

                <span>Kembali ke Beranda</span>
            </a>

        </div>
    </header>

    <main>

        <!-- HERO -->
        <section class="download-hero">
            <div class="download-container download-hero__layout">

                <div class="download-hero__content">

                    <span class="download-eyebrow">
                        <span aria-hidden="true"></span>
                        PESAN AJAH UNTUK ANDROID
                    </span>

                    <h1>
                        Belanja warung lokal,
                        <span>lebih praktis dari genggaman.</span>
                    </h1>

                    <p class="download-hero__description">
                        Nikmati pengalaman menggunakan Pesan Ajah melalui
                        aplikasi Android. Temukan produk warung sekitar dan
                        pesan kebutuhan harian dengan lebih mudah.
                    </p>

                    <div class="download-action">

                        <?php if ($apkAvailable): ?>

                            <a
                                class="download-button"
                                href="/assets/downloads/pesan-ajah.apk"
                                download="pesan-ajah.apk"
                            >
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v3h14v-3" />
                                </svg>

                                Download Aplikasi
                            </a>

                            <p class="download-action__note">
                                File APK resmi Pesan Ajah.
                            </p>

                        <?php else: ?>

                            <span
                                class="download-button is-unavailable"
                                aria-disabled="true"
                            >
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v3h14v-3" />
                                </svg>

                                APK Segera Hadir
                            </span>

                            <p class="download-action__note">
                                Aplikasi sedang dipersiapkan. Silakan gunakan
                                Pesan Ajah melalui website untuk sementara.
                            </p>

                        <?php endif; ?>

                    </div>

                    <div class="download-platform">

                        <span class="download-platform__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                                <path d="M10 18h4" />
                            </svg>
                        </span>

                        <span>
                            <strong>Aplikasi Android</strong>
                            <small>Untuk perangkat Android</small>
                        </span>

                    </div>

                </div>

                <!-- VISUAL APLIKASI -->
                <div
                    class="download-hero__visual"
                    aria-label="Identitas aplikasi Pesan Ajah"
                >

                    <div class="download-app-card">

                        <div class="download-app-card__glow"></div>

                        <img
                            class="download-app-card__logo"
                            src="/assets/images/logo.webp"
                            alt="Logo Pesan Ajah"
                            width="112"
                            height="112"
                        >

                        <strong>Pesan Ajah</strong>

                        <span>Belanja dekat, jadi mudah.</span>

                        <div
                            class="download-app-card__dots"
                            aria-hidden="true"
                        >
                            <i></i>
                            <i></i>
                            <i></i>
                        </div>

                    </div>

                    <span class="download-floating-label">
                        <span aria-hidden="true">✓</span>
                        Warung lokal, lebih dekat
                    </span>

                </div>

            </div>
        </section>

        <!-- BENEFITS -->
        <section class="download-benefits">
            <div class="download-container">

                <div class="download-section-heading">

                    <span class="download-section-label">
                        KENAPA PESAN AJAH?
                    </span>

                    <h2>Kebutuhan harian jadi lebih sederhana.</h2>

                    <p>
                        Dirancang agar pelanggan lebih mudah berbelanja
                        dari warung sekitar.
                    </p>

                </div>

                <div class="download-benefit-grid">

                    <article class="download-benefit">

                        <span class="download-benefit__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m16 16 4 4M8 11h6" />
                            </svg>
                        </span>

                        <h3>Temukan Produk</h3>

                        <p>
                            Lihat pilihan produk dan warung lokal
                            dalam satu tempat.
                        </p>

                    </article>

                    <article class="download-benefit">

                        <span class="download-benefit__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H6" />
                                <circle cx="10" cy="20" r="1" />
                                <circle cx="18" cy="20" r="1" />
                            </svg>
                        </span>

                        <h3>Pesan Lebih Mudah</h3>

                        <p>
                            Pilih kebutuhan dan lanjutkan pemesanan
                            secara praktis.
                        </p>

                    </article>

                    <article class="download-benefit">

                        <span class="download-benefit__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z" />
                                <path d="m9 12 2 2 4-4" />
                            </svg>
                        </span>

                        <h3>Dukung Warung Lokal</h3>

                        <p>
                            Belanja dari warung sekitar dan dukung
                            usaha lokal.
                        </p>

                    </article>

                </div>

            </div>
        </section>

        <!-- INSTALLATION GUIDE -->
        <section class="download-install">
            <div class="download-container download-install__inner">

                <div>

                    <span class="download-section-label">
                        CARA MEMASANG
                    </span>

                    <h2>
                        Mulai gunakan aplikasi dalam beberapa langkah.
                    </h2>

                    <p class="download-install__description">
                        Setelah file APK tersedia, ikuti panduan singkat
                        berikut untuk memasangnya di perangkat Android.
                    </p>

                </div>

                <ol class="download-steps">

                    <li>
                        <span>01</span>

                        <div>
                            <strong>Unduh file APK</strong>

                            <p>
                                Tekan tombol download pada halaman ini.
                            </p>
                        </div>
                    </li>

                    <li>
                        <span>02</span>

                        <div>
                            <strong>Izinkan pemasangan</strong>

                            <p>
                                Jika diminta, izinkan pemasangan aplikasi
                                dari sumber ini.
                            </p>
                        </div>
                    </li>

                    <li>
                        <span>03</span>

                        <div>
                            <strong>Buka Pesan Ajah</strong>

                            <p>
                                Selesaikan pemasangan, lalu buka aplikasinya.
                            </p>
                        </div>
                    </li>

                </ol>

            </div>
        </section>

        <!-- WEBSITE CTA -->
        <section class="download-bottom-cta">
            <div class="download-container download-bottom-cta__inner">

                <div>
                    <h2>Tidak ingin menunggu?</h2>

                    <p>
                        Pesan Ajah tetap bisa digunakan langsung melalui website.
                    </p>
                </div>

                <a href="/" class="download-web-button">
                    Buka Pesan Ajah

                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 12h14m-6-6 6 6-6 6" />
                    </svg>
                </a>

            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="download-footer">
        <div class="download-container download-footer__inner">

            <a href="/" class="download-footer__brand">

                <img
                    src="/assets/images/logo.webp"
                    alt=""
                    width="30"
                    height="30"
                >

                <strong>Pesan Ajah</strong>

            </a>

            <p>
                &copy; <?= date('Y') ?> Pesan Ajah.
                Hak cipta dilindungi.
            </p>

        </div>
    </footer>

</body>
</html>
