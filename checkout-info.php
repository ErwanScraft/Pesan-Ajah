<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$orderNumber = trim(
    is_string($_GET['order'] ?? null)
        ? $_GET['order']
        : ''
);

if (
    $orderNumber === ''
    || !preg_match('/^PA\d{6}[A-F0-9]{8}$/i', $orderNumber)
) {
    http_response_code(400);
    $invalidOrder = true;
} else {
    $invalidOrder = false;
}

?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="theme-color"
        content="#176b45"
    >

    <meta
        name="robots"
        content="noindex, nofollow"
    >

    <title>
        Informasi Checkout — Pesan Ajah
    </title>

    <link
        rel="icon"
        type="image/webp"
        href="/assets/images/logo.webp"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        :root {
            color-scheme: light;

            --primary: #176b45;
            --primary-dark: #105638;
            --primary-light: #eaf5ee;

            --text: #202923;
            --muted: #68736c;

            --border: #e4eae6;
            --surface: #ffffff;
            --background: #f5f8f6;

            --warning-bg: #fff7e6;
            --warning-text: #8a5a00;

            --radius: 20px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;

            display: flex;
            flex-direction: column;

            background: var(--background);

            color: var(--text);

            font-family:
                "DM Sans",
                sans-serif;

            -webkit-font-smoothing: antialiased;
        }

        button,
        a {
            font: inherit;
        }

        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
        }

        .header-inner {
            width: min(100% - 32px, 1080px);
            min-height: 72px;
            margin: 0 auto;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;

            color: var(--text);
            text-decoration: none;
        }

        .brand img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .brand-name {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand-name span {
            color: var(--primary);
        }

        .header-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }

        .main {
            flex: 1;

            width: min(100% - 32px, 560px);
            margin: 40px auto;

            display: flex;
            align-items: center;
        }

        .checkout-card {
            width: 100%;
            padding: 32px;

            background: var(--surface);

            border: 1px solid var(--border);
            border-radius: var(--radius);

            box-shadow:
                0 12px 36px rgb(24 55 38 / 5%);
        }

        .status-icon {
            width: 68px;
            height: 68px;
            margin-bottom: 24px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: var(--primary-light);
            color: var(--primary);

            font-size: 32px;
            font-weight: 700;
        }

        .eyebrow {
            display: block;
            margin-bottom: 10px;

            color: var(--primary);

            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 14px;

            font-size: clamp(25px, 5vw, 34px);
            line-height: 1.25;
            letter-spacing: -1px;
        }

        .description {
            margin: 0;

            color: var(--muted);

            font-size: 14px;
            line-height: 1.8;
        }

        .order-box {
            margin-top: 26px;
            padding: 18px;

            background: #f8faf8;

            border: 1px solid var(--border);
            border-radius: 14px;
        }

        .order-label {
            display: block;
            margin-bottom: 8px;

            color: var(--muted);

            font-size: 12px;
            font-weight: 600;
        }

        .order-number {
            overflow-wrap: anywhere;

            color: var(--primary);

            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .steps {
            display: grid;
            gap: 22px;

            margin-top: 30px;
        }

        .step {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .step-number {
            flex: 0 0 34px;

            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 11px;

            background: var(--primary-light);
            color: var(--primary);

            font-size: 14px;
            font-weight: 800;
        }

        .step-content {
            flex: 1;
            min-width: 0;
        }

        .step-content h2 {
            margin: 2px 0 6px;

            font-size: 14px;
            font-weight: 800;
        }

        .step-content p {
            margin: 0;

            color: var(--muted);

            font-size: 13px;
            line-height: 1.7;
        }

        .notice {
            margin-top: 28px;
            padding: 16px;

            background: var(--warning-bg);
            color: var(--warning-text);

            border-radius: 12px;

            font-size: 12px;
            line-height: 1.8;
        }

        .notice strong {
            display: block;
            margin-bottom: 4px;

            font-size: 13px;
        }

        .actions {
            display: grid;
            gap: 12px;

            margin-top: 28px;
        }

        .button {
            width: 100%;
            min-height: 50px;
            padding: 14px 18px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;

            border: 1px solid transparent;
            border-radius: 12px;

            font-size: 14px;
            font-weight: 800;

            text-align: center;
            text-decoration: none;

            cursor: pointer;

            transition:
                background 160ms ease,
                transform 160ms ease;
        }

        .button:active {
            transform: scale(0.98);
        }

        .button-primary {
            background: var(--primary);
            color: #fff;
        }

        .button-primary:hover {
            background: var(--primary-dark);
        }

        .button-secondary {
            background: #fff;

            border-color: var(--border);

            color: var(--text);
        }

        .button-secondary:hover {
            background: #f8faf8;
        }

        .button:focus-visible,
        .brand:focus-visible {
            outline: 3px solid #8ac5a5;
            outline-offset: 3px;
        }

        .footer {
            padding: 22px 16px;

            border-top: 1px solid var(--border);

            color: var(--muted);

            font-size: 12px;
            text-align: center;
        }

        .footer strong {
            color: var(--primary);
        }

        [hidden] {
            display: none !important;
        }

        @media (max-width: 480px) {
            .header-inner {
                min-height: 64px;
            }

            .header-label {
                font-size: 11px;
            }

            .main {
                width: min(100% - 24px, 560px);
                margin: 20px auto;
            }

            .checkout-card {
                padding: 24px 20px;
                border-radius: 16px;
            }

            .status-icon {
                width: 58px;
                height: 58px;
                margin-bottom: 20px;

                font-size: 28px;
            }

            .order-box {
                padding: 15px;
            }

            .order-number {
                font-size: 18px;
            }

            .steps {
                gap: 20px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>

    <header class="header">

        <div class="header-inner">

            <a
                class="brand"
                href="/"
                aria-label="Pesan Ajah, kembali ke beranda"
            >

                <img
                    src="/assets/images/logo.webp"
                    alt=""
                    width="38"
                    height="38"
                >

                <span class="brand-name">
                    Pesan <span>Ajah</span>
                </span>

            </a>

            <span class="header-label">
                Informasi Pesanan
            </span>

        </div>

    </header>

    <main class="main">

        <section
            class="checkout-card"
            aria-labelledby="page-title"
        >

            <?php if ($invalidOrder): ?>

                <div class="status-icon" aria-hidden="true">
                    !
                </div>

                <span class="eyebrow">
                    Checkout
                </span>

                <h1 id="page-title">
                    Nomor pesanan tidak valid
                </h1>

                <p class="description">
                    Nomor pesanan tidak ditemukan pada alamat halaman
                    ini. Silakan kembali ke beranda Pesan Ajah dan
                    periksa pesananmu.
                </p>

                <div class="actions">

                    <a
                        class="button button-primary"
                        href="/"
                    >
                        Kembali ke Beranda
                    </a>

                </div>

            <?php else: ?>

                <div class="status-icon" aria-hidden="true">
                    ✓
                </div>

                <span class="eyebrow">
                    Checkout Berhasil
                </span>

                <h1 id="page-title">
                    Pesananmu sedang menunggu konfirmasi seller.
                </h1>

                <p class="description">
                    Pesanan berhasil dicatat di Pesan Ajah.
                    Selanjutnya, kamu akan diarahkan ke WhatsApp seller
                    untuk mengirimkan rincian pesanan secara langsung.
                </p>

                <div class="order-box">

                    <span class="order-label">
                        Nomor Pesanan
                    </span>

                    <div
                        class="order-number"
                        id="order-number"
                    ><?= htmlspecialchars(
                        $orderNumber,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></div>

                </div>

                <div class="steps">

                    <div class="step">

                        <div
                            class="step-number"
                            aria-hidden="true"
                        >
                            1
                        </div>

                        <div class="step-content">

                            <h2>
                                Kirim pesanan ke WhatsApp seller
                            </h2>

                            <p>
                                Tekan tombol di bawah untuk membuka
                                WhatsApp dengan pesan pesanan yang
                                sudah disiapkan. Pastikan kamu benar-benar
                                mengirim pesan tersebut.
                            </p>

                        </div>

                    </div>

                    <div class="step">

                        <div
                            class="step-number"
                            aria-hidden="true"
                        >
                            2
                        </div>

                        <div class="step-content">

                            <h2>
                                Tunggu tanggapan seller
                            </h2>

                            <p>
                                Seller akan memeriksa rincian pesanan
                                dan memberikan tanggapan melalui
                                WhatsApp untuk mengonfirmasi apakah
                                pesanan dapat diproses.
                            </p>

                        </div>

                    </div>

                    <div class="step">

                        <div
                            class="step-number"
                            aria-hidden="true"
                        >
                            3
                        </div>

                        <div class="step-content">

                            <h2>
                                Pesanan diproses setelah dikonfirmasi
                            </h2>

                            <p>
                                Tunggu konfirmasi seller sebelum
                                menganggap pesananmu diterima dan
                                mulai diproses.
                            </p>

                        </div>

                    </div>

                </div>

                <div class="notice">

                    <strong>
                        Penting untuk diperhatikan
                    </strong>

                    Pesanan yang tersimpan di Pesan Ajah belum berarti
                    seller sudah menyetujuinya. Pastikan pesan WhatsApp
                    berhasil dikirim dan tunggu jawaban seller.
                    Jangan melakukan pembayaran sebelum ada kejelasan
                    dari seller.

                </div>

                <div class="actions">

                    <button
                        class="button button-primary"
                        id="continue-whatsapp"
                        type="button"
                    >
                        Lanjutkan ke WhatsApp Seller
                        <span aria-hidden="true">→</span>
                    </button>

                    <a
                        class="button button-secondary"
                        href="/"
                    >
                        Kembali ke Beranda
                    </a>

                    <p
                        id="checkout-error"
                        role="alert"
                        hidden
                        style="
                            margin: 0;
                            color: #b42318;
                            font-size: 13px;
                            line-height: 1.7;
                        "
                    ></p>

                </div>

            <?php endif; ?>

        </section>

    </main>

    <footer class="footer">
        &copy; <?= date('Y') ?>
        <strong>Pesan Ajah</strong>.
        Marketplace warung lokal.
    </footer>

    <?php if (!$invalidOrder): ?>

        <script>
            (() => {
                "use strict";

                const button = document.querySelector(
                    "#continue-whatsapp"
                );

                const errorElement = document.querySelector(
                    "#checkout-error"
                );

                const orderNumber = <?= json_encode(
                    $orderNumber,
                    JSON_HEX_TAG
                    | JSON_HEX_AMP
                    | JSON_HEX_APOS
                    | JSON_HEX_QUOT
                ) ?>;

                const storageKey =
                    "pesan_ajah_checkout_redirect";

                button.addEventListener("click", () => {
                    errorElement.hidden = true;
                    errorElement.textContent = "";

                    let checkoutData;

                    try {
                        checkoutData = JSON.parse(
                            sessionStorage.getItem(storageKey) || "null"
                        );
                    } catch {
                        checkoutData = null;
                    }

                    if (
                        !checkoutData
                        || checkoutData.order_number !== orderNumber
                        || typeof checkoutData.whatsapp_url !== "string"
                    ) {
                        errorElement.textContent =
                            "Informasi tujuan WhatsApp tidak tersedia. " +
                            "Silakan kembali ke halaman sebelumnya dan " +
                            "ulangi proses checkout jika diperlukan.";

                        errorElement.hidden = false;

                        return;
                    }

                    let whatsappUrl;

                    try {
                        whatsappUrl = new URL(
                            checkoutData.whatsapp_url
                        );
                    } catch {
                        errorElement.textContent =
                            "Tautan WhatsApp tidak valid.";

                        errorElement.hidden = false;

                        return;
                    }

                    if (
                        whatsappUrl.protocol !== "https:"
                        || ![
                            "wa.me",
                            "api.whatsapp.com",
                            "web.whatsapp.com"
                        ].includes(whatsappUrl.hostname)
                    ) {
                        errorElement.textContent =
                            "Tujuan WhatsApp tidak valid.";

                        errorElement.hidden = false;

                        return;
                    }

                    button.disabled = true;
                    button.textContent = "Membuka WhatsApp...";

                    /*
                     * Gunakan URL dari hasil checkout yang tersimpan
                     * di sessionStorage. Tidak membuat ulang pesanan.
                     */
                    window.location.assign(whatsappUrl.href);
                });
            })();
        </script>

    <?php endif; ?>

</body>

</html>