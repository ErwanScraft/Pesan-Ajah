<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Download Aplikasi Pesan Ajah';
$pageDescription = 'Download aplikasi Pesan Ajah untuk kemudahan mengakses dan berbelanja melalui perangkat Android.';

$appVersion = '1.0.5';
$totalDownloads = 0;

try {
    $stmt = database()->query(
        'SELECT total_downloads
         FROM app_download_stats
         WHERE id = 1
         LIMIT 1'
    );

    $totalDownloads = (int) ($stmt->fetchColumn() ?: 0);
} catch (Throwable $exception) {
    error_log(
        '[Pesan Ajah] Download statistics: '
        . $exception->getMessage()
    );
}

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="theme-color" content="#176b45">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="robots" content="index, follow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <title><?= e($pageTitle) ?></title>

    <link
        rel="icon"
        type="image/webp"
        href="/assets/images/logo.webp"
    >

    <link
        rel="stylesheet"
        href="/assets/css/style.css?v=<?= asset_version('assets/css/style.css') ?>"
    >

    <link
        rel="stylesheet"
        href="/assets/css/download.css?v=<?= asset_version('assets/css/download.css') ?>"
    >

    <script
        type="module"
        src="/assets/js/pages/download/main.js?v=<?= asset_version('assets/js/pages/download/main.js') ?>"
    ></script>
</head>

<body class="download-page">

<main class="download-container">

    <!-- HEADER -->
    <header class="download-header">
        <a href="/" class="download-brand">
            <img
                src="/assets/images/logo.webp"
                alt="Logo Pesan Ajah"
                width="48"
                height="48"
            >

            <span>PESAN AJAH</span>
        </a>
    </header>

    <!-- DESKRIPSI -->
    <section class="download-intro">

        <h1>Download Aplikasi Pesan Ajah</h1>

        <p>
            Download aplikasi Pesan Ajah untuk kemudahan dalam
            mengakses aplikasi, membuat pengalaman berbelanja mu
            jadi makin simpel.
        </p>

    </section>

    <!-- INFORMASI APLIKASI -->
    <section class="download-card">

        <h2>Informasi</h2>

        <div class="download-info-list">

            <div class="download-info-item">
                <span>Nama</span>
                <strong>Pesan Ajah</strong>
            </div>

            <div class="download-info-item">
                <span>Versi</span>
                <strong><?= e($appVersion) ?></strong>
            </div>

            <div class="download-info-item">
                <span>Platform</span>
                <strong>Android</strong>
            </div>

            <div class="download-info-item">
                <span>Total Download</span>

                <strong
                    id="download-total"
                    data-total="<?= $totalDownloads ?>"
                >
                    <?= number_format($totalDownloads, 0, ',', '.') ?>
                </strong>
            </div>

        </div>

        <a
            href="/api/app-download.php"
            class="download-button"
            id="download-button"
        >
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v3h14v-3"/>
            </svg>

            <span>Download Sekarang</span>
        </a>

        <p class="download-note">
            File APK resmi aplikasi Pesan Ajah untuk Android.
        </p>

    </section>

    <!-- FORM ULASAN -->
    <section class="download-card">

        <h2>Berikan Ulasan Untuk Aplikasi</h2>

        <form id="review-form" class="review-form">

            <div class="review-field">

                <label for="review-name">Nama</label>

                <input
                    type="text"
                    id="review-name"
                    name="name"
                    maxlength="100"
                    placeholder="Masukkan nama kamu"
                    autocomplete="name"
                    required
                >

            </div>

            <div class="review-field">

                <label>Bintang</label>

                <div
                    class="review-rating"
                    id="review-rating"
                    role="group"
                    aria-label="Pilih rating bintang"
                >

                    <?php for ($i = 1; $i <= 5; $i++): ?>

                        <button
                            type="button"
                            class="review-star"
                            data-rating="<?= $i ?>"
                            aria-label="<?= $i ?> bintang"
                            aria-pressed="false"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.9L12 17.8l-6.2 3.3L7 14.2 2 9.3l6.9-1L12 2Z"/>
                            </svg>
                        </button>

                    <?php endfor; ?>

                </div>

                <input
                    type="hidden"
                    name="rating"
                    id="review-rating-value"
                    value=""
                >

            </div>

            <div class="review-field">

                <label for="review-message">Ulasan</label>

                <textarea
                    id="review-message"
                    name="review"
                    rows="4"
                    maxlength="1000"
                    placeholder="Bagaimana pengalaman kamu menggunakan Pesan Ajah?"
                    required
                ></textarea>

                <small>Maksimal 1000 karakter.</small>

            </div>

            <!-- Honeypot anti-spam -->
            <div class="review-honeypot" aria-hidden="true">
                <label for="review-website">Website</label>
                <input
                    type="text"
                    id="review-website"
                    name="website"
                    tabindex="-1"
                    autocomplete="off"
                >
            </div>

            <p
                class="review-message"
                id="review-status"
                role="status"
                aria-live="polite"
            ></p>

            <button
                type="submit"
                class="review-submit"
                id="review-submit"
            >
                Post Ulasan
            </button>

        </form>

    </section>

    <!-- DAFTAR REVIEW -->
    <section class="download-card">

        <div class="review-heading">
            <h2>Review Aplikasi</h2>

            <div class="review-summary" id="review-summary">
                <strong id="review-average">0.0</strong>

                <div>
                    <div class="review-summary-stars" aria-label="Rating rata-rata">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.9L12 17.8l-6.2 3.3L7 14.2 2 9.3l6.9-1L12 2Z"/>
                            </svg>
                        <?php endfor; ?>
                    </div>

                    <small id="review-count">0 ulasan</small>
                </div>
            </div>
        </div>

        <div
            class="review-list"
            id="review-list"
            aria-live="polite"
        >
            <p class="review-empty">
                Memuat ulasan...
            </p>
        </div>

    </section>

    <!-- FOOTER -->
    <footer class="download-footer">
        <img
            src="/assets/images/logo.webp"
            alt=""
            width="38"
            height="38"
        >

        <h2>Belanja Lebih Simpel dengan Pesan Ajah.</h2>

        <p>
            &copy; <?= date('Y') ?> Pesan Ajah.
            Hak cipta dilindungi.
        </p>
    </footer>

</main>

</body>
</html>
