<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$orderNumber = trim(
    is_string($_GET['order'] ?? null)
        ? $_GET['order']
        : ''
);

$invalidOrder = (
    $orderNumber === ''
    || !preg_match('/^PA\d{6}[A-F0-9]{8}$/i', $orderNumber)
);

if ($invalidOrder) {
    http_response_code(400);
}

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#176b45">
    <meta name="robots" content="noindex, nofollow">

    <title>Detail Pesanan — Pesan Ajah</title>

    <link rel="icon" type="image/webp" href="/assets/images/logo.webp">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/checkout-info.css">
</head>

<body>
    <?php
    $isStorePage = false;
    require __DIR__ . '/_header.php';
    ?>

    <main class="checkout-info">
        <div class="checkout-info__container">

            <?php if ($invalidOrder): ?>

                <section class="checkout-info__card">
                    <h1>Nomor pesanan tidak valid</h1>
                    <p class="checkout-info__muted">
                        Periksa kembali nomor pesanan atau kembali ke beranda.
                    </p>
                    <a class="checkout-info__button" href="/">
                        Kembali ke Beranda
                    </a>
                </section>

            <?php else: ?>

                <section class="checkout-info__card" aria-labelledby="checkout-title">
                    <div class="checkout-info__heading">
                        <span class="checkout-info__status" aria-hidden="true">✓</span>

                        <div>
                            <h1 id="checkout-title">Pesanan berhasil dicatat</h1>
                            <p class="checkout-info__muted">
                                Kirim detail pesanan ke Seller melalui WhatsApp.
                            </p>
                        </div>
                    </div>

                    <div class="checkout-info__order-number">
                        <span>Nomor pesanan</span>
                        <strong id="order-number"><?= htmlspecialchars(
                            $orderNumber,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></strong>
                    </div>
                    
                    <!-- Tombol aksi di atas detail pesanan -->
                    <div class="checkout-info__actions">
                        <button
                            class="checkout-info__button"
                            id="continue-whatsapp"
                            type="button"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 20l1.2-5.4A8.4 8.4 0 1 1 21 11.5Z"/>
                                <path d="M8.5 8.5c.5 2.5 2.5 4.5 5 5l1.2-1.2 2 .8"/>
                            </svg>
                            <span>Lanjut kirim pesanan ke Seller</span>
                        </button>
                    
                        <button
                            class="checkout-info__back"
                            id="back-to-marketplace"
                            type="button"
                        >
                            Kembali
                        </button>
                    
                        <p
                            class="checkout-info__error"
                            id="checkout-error"
                            role="alert"
                            hidden
                        ></p>
                    </div>

                    <section class="checkout-info__details" aria-labelledby="detail-title">
                        <h2 id="detail-title">Detail pesanan</h2>

                        <div id="checkout-items" class="checkout-info__items">
                            <p class="checkout-info__muted">
                                Memuat rincian pesanan...
                            </p>
                        </div>

                        <div class="checkout-info__totals">
                            <div class="checkout-info__total-row">
                                <span>Subtotal</span>
                                <span id="checkout-subtotal">—</span>
                            </div>

                            <div
                                class="checkout-info__total-row"
                                id="delivery-row"
                                hidden
                            >
                                <span>Ongkos kirim</span>
                                <span id="checkout-delivery">—</span>
                            </div>

                            <div
                                class="checkout-info__total-row"
                                id="discount-row"
                                hidden
                            >
                                <span>Diskon ongkir</span>
                                <span id="checkout-discount">—</span>
                            </div>

                            <div class="checkout-info__total-row checkout-info__grand-total">
                                <strong>Total</strong>
                                <strong id="checkout-total">—</strong>
                            </div>
                        </div>
                    </section>

                    <aside class="checkout-info__tip">
                        <strong>Selanjutnya</strong>
                        <p>
                            Pastikan pesan terkirim di WhatsApp, lalu tunggu
                            konfirmasi Seller. Pesanan yang tercatat belum
                            berarti sudah disetujui.
                        </p>
                    </aside>
                </section>

            <?php endif; ?>

        </div>
    </main>

    <?php require __DIR__ . '/_footer.php'; ?>

    <?php if (!$invalidOrder): ?>
        <script>
            (() => {
                "use strict";

                const orderNumber = <?= json_encode(
                    $orderNumber,
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                ) ?>;

                const storageKey = "pesan_ajah_checkout_redirect";
                const button = document.querySelector("#continue-whatsapp");
                const errorElement = document.querySelector("#checkout-error");
                const itemsElement = document.querySelector("#checkout-items");

                const formatRupiah = (value) =>
                    new Intl.NumberFormat("id-ID", {
                        style: "currency",
                        currency: "IDR",
                        maximumFractionDigits: 0,
                    }).format(Number(value) || 0);

                function showError(message) {
                    errorElement.textContent = message;
                    errorElement.hidden = false;
                }

                function loadCheckoutSummary() {
                    let data;

                    try {
                        data = JSON.parse(
                            sessionStorage.getItem(storageKey) || "null"
                        );
                    } catch {
                        data = null;
                    }

                    if (
                        !data ||
                        data.order_number !== orderNumber ||
                        typeof data.whatsapp_url !== "string"
                    ) {
                        itemsElement.replaceChildren();

                        const message = document.createElement("p");
                        message.className = "checkout-info__muted";
                        message.textContent =
                            "Rincian tidak tersedia di sesi browser ini.";

                        itemsElement.append(message);
                        return;
                    }

                    const summary = data.summary;

                    if (!summary || !Array.isArray(summary.items)) {
                        itemsElement.replaceChildren();

                        const message = document.createElement("p");
                        message.className = "checkout-info__muted";
                        message.textContent =
                            "Rincian produk tidak tersedia.";

                        itemsElement.append(message);
                        return;
                    }

                    itemsElement.replaceChildren();

                    if (summary.items.length === 0) {
                        const message = document.createElement("p");
                        message.className = "checkout-info__muted";
                        message.textContent = "Tidak ada rincian produk.";
                        itemsElement.append(message);
                    }

                    summary.items.forEach((item) => {
                        const row = document.createElement("div");
                        row.className = "checkout-info__item";

                        const details = document.createElement("div");
                        details.className = "checkout-info__item-details";

                        const name = document.createElement("strong");
                        name.textContent = String(item.name || "Produk");

                        details.append(name);

                        if (item.variant) {
                            const variant = document.createElement("span");
                            variant.textContent = String(item.variant);
                            details.append(variant);
                        }

                        const quantity = Number(item.quantity) || 0;
                        const price = Number(item.price) || 0;

                        const amount = document.createElement("span");
                        amount.className = "checkout-info__item-price";
                        amount.textContent =
                            `${quantity} × ${formatRupiah(price)}`;

                        const lineTotal = document.createElement("strong");
                        lineTotal.textContent =
                            formatRupiah(quantity * price);

                        row.append(details, amount, lineTotal);
                        itemsElement.append(row);
                    });

                    document.querySelector("#checkout-subtotal").textContent =
                        formatRupiah(summary.subtotal);

                    const isDelivery = summary.delivery_method === "delivery";
                    const deliveryRow = document.querySelector("#delivery-row");
                    const discountRow = document.querySelector("#discount-row");

                    deliveryRow.hidden = !isDelivery;
                    discountRow.hidden =
                        !isDelivery || Number(summary.delivery_discount) <= 0;

                    if (isDelivery) {
                        document.querySelector("#checkout-delivery").textContent =
                            formatRupiah(summary.delivery_fee);

                        document.querySelector("#checkout-discount").textContent =
                            `−${formatRupiah(summary.delivery_discount)}`;
                    }

                    // Total diambil dari respons API, bukan dihitung ulang di browser.
                    document.querySelector("#checkout-total").textContent =
                        formatRupiah(summary.total);
                }

                button.addEventListener("click", async () => {
                    errorElement.hidden = true;
                    errorElement.textContent = "";
                
                    let data;
                
                    try {
                        data = JSON.parse(
                            sessionStorage.getItem(storageKey) || "null"
                        );
                    } catch {
                        data = null;
                    }
                
                    if (
                        !data ||
                        data.order_number !== orderNumber ||
                        typeof data.buyer_token !== "string" ||
                        typeof data.whatsapp_url !== "string"
                    ) {
                        showError(
                            "Informasi pesanan tidak tersedia. Silakan kembali ke beranda."
                        );
                        return;
                    }
                
                    let whatsappUrl;
                
                    try {
                        whatsappUrl = new URL(data.whatsapp_url);
                    } catch {
                        showError("Tautan WhatsApp tidak valid.");
                        return;
                    }
                
                    if (
                        whatsappUrl.protocol !== "https:" ||
                        ![
                            "wa.me",
                            "api.whatsapp.com",
                            "web.whatsapp.com",
                        ].includes(whatsappUrl.hostname)
                    ) {
                        showError("Tujuan WhatsApp tidak valid.");
                        return;
                    }
                
                    button.disabled = true;
                    button.querySelector("span").textContent =
                        "Mengaktifkan pesanan...";
                
                    try {
                        const response = await fetch("/api/activate-order.php", {
                            method: "POST",
                            credentials: "same-origin",
                            cache: "no-store",
                            headers: {
                                "Content-Type": "application/json",
                                "Accept": "application/json",
                            },
                            body: JSON.stringify({
                                order_number: data.order_number,
                                buyer_token: data.buyer_token,
                            }),
                        });
                
                        const result = await response.json();
                
                        if (!response.ok || !result.success) {
                            throw new Error(
                                result.message || "Pesanan belum dapat diaktifkan."
                            );
                        }
                
                        if (result.status !== "pending") {
                            throw new Error("Status pesanan belum berhasil diaktifkan.");
                        }
                
                        button.querySelector("span").textContent =
                            "Membuka WhatsApp...";
                
                        window.location.assign(whatsappUrl.href);
                    } catch (error) {
                        showError(
                            error instanceof Error
                                ? error.message
                                : "Terjadi kesalahan. Silakan coba lagi."
                        );
                
                        button.disabled = false;
                        button.querySelector("span").textContent =
                            "Lanjut kirim pesanan ke Seller";
                    }
                });
                
                document
                    .querySelector("#back-to-marketplace")
                    .addEventListener("click", () => {
                        // Menghapus data pengarah lokal agar draft ini
                        // tidak bisa diaktifkan melalui tombol halaman ini.
                        sessionStorage.removeItem(storageKey);
                
                        window.location.assign("/");
                    });

                loadCheckoutSummary();
            })();
        </script>
    <?php endif; ?>
</body>
</html>
