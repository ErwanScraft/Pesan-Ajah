/* =========================================================
   PESAN AJAH — BUYER DASHBOARD
   File: assets/js/buyer-dashboard.js

   Fitur:
   - Memuat pesanan berdasarkan token pelacakan.
   - Menampilkan status warung dan pengantaran terpadu.
   - Menampilkan produk dan ringkasan pembayaran.
   - Menampilkan alamat pengantaran dan lokasi Maps.
   - Menghapus pesanan selesai atau dibatalkan dari riwayat
     lokal pembeli tanpa menghapus data transaksi di database.
   - Tampilan loading, empty state, dan error.
========================================================= */

(() => {
    "use strict";

    const STORAGE_KEY = "pesan_ajah_order_tokens";
    const API_URL = "/api/orders.php";

    const DELETABLE_STATUSES = new Set([
        "completed",
        "cancelled"
    ]);

    const STATUS_LABELS = {
        pending: "Menunggu konfirmasi",
        confirmed: "Dikonfirmasi",
        processing: "Sedang diproses",
        completed: "Selesai",
        cancelled: "Dibatalkan"
    };

    const STATUS_CLASSES = {
        pending: "buyer-status-pending",
        confirmed: "buyer-status-confirmed",
        processing: "buyer-status-processing",
        completed: "buyer-status-completed",
        cancelled: "buyer-status-cancelled"
    };

    const DELIVERY_STATUS_LABELS = {
        accepted: "Pesanan diambil driver",
        to_store: "Driver menuju warung",
        picked_up: "Pesanan diambil dari warung",
        to_buyer: "Driver menuju pembeli",
        completed: "Pengantaran selesai",
        cancelled: "Pengantaran dibatalkan"
    };

    const DELIVERY_STEPS = [
        "accepted",
        "to_store",
        "picked_up",
        "to_buyer",
        "completed"
    ];

    const STATUS_DELETE_LABELS = {
        completed: "Hapus dari riwayat",
        cancelled: "Hapus dari riwayat"
    };

    const elements = {
        message: document.getElementById("buyer-message"),
        orders: document.getElementById("buyer-orders"),
        empty: document.getElementById("buyer-empty"),
        currentYear: document.getElementById("buyer-current-year")
    };

    let currentOrders = [];
    let isLoading = false;
    let refreshTimer = null;

    /*
     * Memperbarui pesanan setiap 15 detik ketika halaman
     * terlihat dan tidak ada proses pemuatan lain.
     */
    function startOrderRefresh() {
        if (refreshTimer !== null) {
            window.clearInterval(refreshTimer);
        }

        refreshTimer = window.setInterval(() => {
            if (
                document.visibilityState === "visible" &&
                !isLoading
            ) {
                loadOrders();
            }
        }, 15000);
    }

    /*
     * Format mata uang rupiah.
     */
    function formatRupiah(value) {
        const amount = Number(value) || 0;

        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0
        }).format(amount);
    }

    /*
     * Format tanggal dan waktu.
     */
    function formatDate(value) {
        if (!value) {
            return "Tanggal tidak tersedia";
        }

        const normalized = String(value).includes("T")
            ? String(value)
            : String(value).replace(" ", "T");

        const date = new Date(normalized);

        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return new Intl.DateTimeFormat("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        }).format(date);
    }

    /*
     * Membuat elemen HTML dengan aman.
     * Data API dimasukkan melalui textContent, bukan innerHTML.
     */
    function createElement(tag, className, text) {
        const element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        if (text !== undefined && text !== null) {
            element.textContent = String(text);
        }

        return element;
    }

    /*
     * Membaca token pelacakan dari localStorage.
     */
    function getSavedOrders() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);

            if (!raw) {
                return [];
            }

            const parsed = JSON.parse(raw);

            if (!Array.isArray(parsed)) {
                return [];
            }

            return parsed.filter((item) => {
                return (
                    item &&
                    typeof item === "object" &&
                    typeof item.token === "string" &&
                    item.token.trim() !== ""
                );
            });
        } catch (error) {
            console.error(
                "[Buyer Dashboard] Gagal membaca riwayat:",
                error
            );

            return [];
        }
    }

    /*
     * Menyimpan token pelacakan kembali ke localStorage.
     */
    function saveOrders(items) {
        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(items)
            );

            return true;
        } catch (error) {
            console.error(
                "[Buyer Dashboard] Gagal menyimpan riwayat:",
                error
            );

            return false;
        }
    }

    /*
     * Menampilkan notifikasi.
     */
    function showMessage(message, type = "") {
        if (!elements.message) {
            return;
        }

        elements.message.textContent = message;

        elements.message.classList.remove(
            "is-success",
            "is-error"
        );

        if (type === "success") {
            elements.message.classList.add("is-success");
        }

        if (type === "error") {
            elements.message.classList.add("is-error");
        }

        elements.message.hidden = false;
    }

    /*
     * Menyembunyikan notifikasi.
     */
    function hideMessage() {
        if (!elements.message) {
            return;
        }

        elements.message.textContent = "";

        elements.message.classList.remove(
            "is-success",
            "is-error"
        );

        elements.message.hidden = true;
    }

    /*
     * Menampilkan atau menyembunyikan empty state.
     */
    function updateEmptyState() {
        const hasOrders = currentOrders.length > 0;

        if (elements.orders) {
            elements.orders.hidden = !hasOrders;
        }

        if (elements.empty) {
            elements.empty.hidden = hasOrders;
        }
    }

    /*
     * Memastikan tautan Maps menggunakan HTTPS
     * dan berasal dari domain Google Maps yang diizinkan.
     */
    function getSafeMapsUrl(value) {
        if (!value || typeof value !== "string") {
            return null;
        }

        try {
            const url = new URL(value);

            if (url.protocol !== "https:") {
                return null;
            }

            const hostname = url.hostname.toLowerCase();

            const allowedHosts = [
                "google.com",
                "www.google.com",
                "maps.google.com",
                "maps.app.goo.gl",
                "goo.gl"
            ];

            const isAllowed =
                allowedHosts.includes(hostname) ||
                hostname.endsWith(".google.com");

            return isAllowed ? url.href : null;
        } catch {
            return null;
        }
    }

    /*
     * Membuat satu baris pembayaran.
     */
    function createPaymentRow(label, value, isTotal = false) {
        const row = createElement(
            "div",
            isTotal
                ? "buyer-payment-row buyer-payment-total"
                : "buyer-payment-row"
        );

        const labelElement = createElement(
            "span",
            "buyer-payment-label",
            label
        );

        const valueElement = createElement(
            "span",
            "buyer-payment-value",
            value
        );

        row.append(labelElement, valueElement);

        return row;
    }

    /*
     * Membuat bagian daftar produk.
     */
    function createItemsSection(order) {
        const section = createElement(
            "section",
            "buyer-order-items"
        );

        section.appendChild(
            createElement(
                "h3",
                "buyer-section-title",
                "Produk pesanan"
            )
        );

        const list = createElement("ul", "buyer-item-list");

        const items = Array.isArray(order.items)
            ? order.items
            : [];

        if (items.length === 0) {
            list.appendChild(
                createElement(
                    "li",
                    "buyer-item-empty",
                    "Rincian produk tidak tersedia."
                )
            );
        } else {
            items.forEach((item) => {
                const row = createElement("li", "buyer-item");

                const name = String(
                    item.product_name ||
                    item.name ||
                    "Produk"
                );

                const quantity = Math.max(
                    0,
                    Number(item.quantity) || 0
                );

                const subtotal = Number(
                    item.subtotal ??
                    (
                        Number(item.price || 0) *
                        quantity
                    )
                ) || 0;

                const nameElement = createElement(
                    "div",
                    "buyer-item-name",
                    `${name} × ${quantity}`
                );

                const variant = item.product_variant
                    ? String(item.product_variant)
                    : "";

                const variantElement = createElement(
                    "small",
                    "buyer-item-variant",
                    variant
                );

                variantElement.hidden = !variant;

                const totalElement = createElement(
                    "div",
                    "buyer-item-total",
                    formatRupiah(subtotal)
                );

                row.appendChild(nameElement);

                if (variant) {
                    row.appendChild(variantElement);
                }

                row.appendChild(totalElement);
                list.appendChild(row);
            });
        }

        section.appendChild(list);

        return section;
    }

    /*
     * Membuat ringkasan pembayaran.
     */
    function createPaymentSection(order) {
        const section = createElement(
            "section",
            "buyer-order-payment"
        );

        const subtotal = Number(order.subtotal) || 0;
        const deliveryFee = Number(order.delivery_fee) || 0;
        const total = Number(order.total) || 0;

        section.appendChild(
            createPaymentRow(
                "Subtotal",
                formatRupiah(subtotal)
            )
        );

        if (order.delivery_method === "delivery") {
            section.appendChild(
                createPaymentRow(
                    "Ongkos kirim",
                    deliveryFee > 0
                        ? formatRupiah(deliveryFee)
                        : "Rp0"
                )
            );
        }

        section.appendChild(
            createPaymentRow(
                "Total pembayaran",
                formatRupiah(total),
                true
            )
        );

        return section;
    }

    /*
     * Membuat bagian alamat pengantaran.
     * Bagian ini tidak ditampilkan untuk ambil sendiri.
     */
    function createAddressSection(order) {
        if (order.delivery_method !== "delivery") {
            return null;
        }

        const address = String(
            order.delivery_address || ""
        ).trim();

        const mapsUrl = getSafeMapsUrl(
            order.delivery_maps_url
        );

        if (!address && !mapsUrl) {
            return null;
        }

        const section = createElement(
            "section",
            "buyer-order-address"
        );

        section.appendChild(
            createElement(
                "span",
                "buyer-address-label",
                "Alamat pengantaran"
            )
        );

        if (address) {
            section.appendChild(
                createElement(
                    "p",
                    "buyer-address",
                    address
                )
            );
        }

        if (mapsUrl) {
            const link = createElement(
                "a",
                "buyer-order-map",
                "Lihat lokasi di Google Maps"
            );

            link.href = mapsUrl;
            link.target = "_blank";
            link.rel = "noopener noreferrer";

            section.appendChild(link);
        }

        return section;
    }

    /*
     * Menghapus token pesanan dari browser pembeli.
     *
     * Tidak menghapus data pada tabel orders atau order_items.
     * Pesanan tetap tersedia di Dashboard Warung.
     */
    function removeBuyerOrder(order) {
        if (!DELETABLE_STATUSES.has(order.status)) {
            return;
        }

        const orderNumber = String(
            order.order_number || ""
        );

        if (!orderNumber) {
            showMessage(
                "Nomor pesanan tidak ditemukan. Riwayat tidak dapat dihapus.",
                "error"
            );

            return;
        }

        const confirmation =
            order.status === "completed"
                ? `Hapus pesanan ${orderNumber} dari riwayat Buyer?`
                : `Hapus pesanan ${orderNumber} yang dibatalkan dari riwayat Buyer?`;

        if (!window.confirm(confirmation)) {
            return;
        }

        const savedOrders = getSavedOrders();

        const updatedOrders = savedOrders.filter((item) => {
            return (
                String(item.order_number || "") !== orderNumber
            );
        });

        if (!saveOrders(updatedOrders)) {
            showMessage(
                "Riwayat gagal diperbarui. Coba lagi.",
                "error"
            );

            return;
        }

        currentOrders = currentOrders.filter((item) => {
            return (
                String(item.order_number || "") !== orderNumber
            );
        });

        renderOrders();

        showMessage(
            "Pesanan berhasil dihapus dari riwayat Buyer.",
            "success"
        );
    }

    /*
     * Membuat footer kartu pesanan.
     */
    function createOrderFooter(order) {
        const footer = createElement(
            "footer",
            "buyer-order-footer"
        );

        const methodLabel =
            order.delivery_method === "pickup"
                ? "Ambil sendiri"
                : "Diantar";

        footer.appendChild(
            createElement(
                "span",
                "buyer-order-method",
                `Metode: ${methodLabel}`
            )
        );

        const updatedAt =
            order.updated_at ||
            order.created_at;

        footer.appendChild(
            createElement(
                "span",
                "buyer-order-updated",
                `Diperbarui: ${formatDate(updatedAt)}`
            )
        );

        /*
         * Tombol hapus hanya muncul pada status:
         * completed atau cancelled.
         */
        if (DELETABLE_STATUSES.has(order.status)) {
            const actions = createElement(
                "div",
                "buyer-order-actions"
            );

            const deleteButton = createElement(
                "button",
                "buyer-order-delete",
                STATUS_DELETE_LABELS[order.status]
            );

            deleteButton.type = "button";

            deleteButton.addEventListener("click", () => {
                removeBuyerOrder(order);
            });

            actions.appendChild(deleteButton);
            footer.appendChild(actions);
        }

        return footer;
    }

    /*
     * Panel status terpadu:
     * - Status pesanan dari warung.
     * - Status pengantaran dari driver.
     * - Progres pengantaran jika tersedia.
     */
    function createOrderStatusSection(order) {
        const section = createElement(
            "section",
            "buyer-order-status-panel"
        );

        const orderStatus = String(
            order.status || "pending"
        ).toLowerCase();

        const statusRow = createElement(
            "div",
            "buyer-status-row"
        );

        statusRow.appendChild(
            createElement(
                "span",
                "buyer-status-caption",
                "Status warung"
            )
        );

        statusRow.appendChild(
            createElement(
                "span",
                [
                    "buyer-status",
                    STATUS_CLASSES[orderStatus] ||
                        "buyer-status-pending"
                ].join(" "),
                STATUS_LABELS[orderStatus] ||
                    "Menunggu konfirmasi"
            )
        );

        section.appendChild(statusRow);

        /*
         * Untuk pesanan ambil sendiri, tidak ada progres driver.
         */
        if (order.delivery_method !== "delivery") {
            section.appendChild(
                createElement(
                    "p",
                    "buyer-delivery-current",
                    "Metode: Ambil sendiri"
                )
            );

            return section;
        }

        const deliveryStatus = String(
            order.delivery_status || ""
        ).toLowerCase();

        const deliveryLabel = deliveryStatus
            ? DELIVERY_STATUS_LABELS[deliveryStatus] ||
                "Status tidak dikenal"
            : "Menunggu driver mengambil pesanan";

        const deliveryRow = createElement(
            "div",
            "buyer-delivery-status-row"
        );

        deliveryRow.appendChild(
            createElement(
                "span",
                "buyer-status-caption",
                "Status pengantaran"
            )
        );

        deliveryRow.appendChild(
            createElement(
                "span",
                [
                    "buyer-delivery-badge",
                    deliveryStatus === "completed"
                        ? "is-complete"
                        : "",
                    deliveryStatus === "cancelled"
                        ? "is-cancelled"
                        : ""
                ].filter(Boolean).join(" "),
                deliveryLabel
            )
        );

        section.appendChild(deliveryRow);

        /*
         * Belum ada progres atau pengantaran dibatalkan:
         * status tetap terlihat tanpa daftar langkah.
         */
        if (
            !deliveryStatus ||
            deliveryStatus === "cancelled"
        ) {
            return section;
        }

        const list = createElement(
            "ol",
            "buyer-delivery-steps"
        );

        const currentIndex =
            DELIVERY_STEPS.indexOf(deliveryStatus);

        DELIVERY_STEPS.forEach((step, index) => {
            const item = createElement(
                "li",
                index <= currentIndex
                    ? "buyer-delivery-step is-complete"
                    : "buyer-delivery-step"
            );

            item.appendChild(
                createElement(
                    "span",
                    "buyer-delivery-step-label",
                    DELIVERY_STATUS_LABELS[step]
                )
            );

            list.appendChild(item);
        });

        section.appendChild(list);

        return section;
    }

    /*
     * Membuat satu kartu pesanan.
     */
    function createOrderCard(order) {
        const card = createElement(
            "article",
            "buyer-order-card"
        );

        const header = createElement(
            "header",
            "buyer-order-header"
        );

        const heading = createElement(
            "div",
            "buyer-order-heading"
        );

        heading.appendChild(
            createElement(
                "p",
                "buyer-order-store",
                order.store_name ||
                order.store ||
                "Warung"
            )
        );

        heading.appendChild(
            createElement(
                "p",
                "buyer-order-number",
                `No. Pesanan: ${order.order_number || "-"}`
            )
        );

        heading.appendChild(
            createElement(
                "p",
                "buyer-order-date",
                formatDate(order.created_at)
            )
        );

        header.appendChild(heading);
        card.appendChild(header);

        /*
         * Urutan komponen kartu:
         * 1. Identitas pesanan.
         * 2. Status warung dan pengantaran.
         * 3. Produk.
         * 4. Pembayaran.
         * 5. Alamat jika menggunakan delivery.
         * 6. Informasi tambahan dan aksi.
         */
        card.appendChild(createOrderStatusSection(order));
        card.appendChild(createItemsSection(order));
        card.appendChild(createPaymentSection(order));

        const addressSection = createAddressSection(order);

        if (addressSection) {
            card.appendChild(addressSection);
        }

        card.appendChild(createOrderFooter(order));

        return card;
    }

    /*
     * Merender seluruh kartu pesanan.
     */
    function renderOrders() {
        if (!elements.orders) {
            return;
        }

        elements.orders.replaceChildren();

        currentOrders.forEach((order) => {
            elements.orders.appendChild(
                createOrderCard(order)
            );
        });

        updateEmptyState();
    }

    /*
     * Memuat data pesanan dari API.
     */
    async function loadOrders() {
        if (isLoading) {
            return;
        }

        isLoading = true;

        hideMessage();

        if (!elements.orders) {
            isLoading = false;
            return;
        }

        const savedOrders = getSavedOrders();

        if (savedOrders.length === 0) {
            currentOrders = [];
            renderOrders();
            isLoading = false;
            return;
        }

        elements.orders.hidden = false;
        elements.orders.replaceChildren();

        elements.orders.appendChild(
            createElement(
                "div",
                "buyer-message",
                "Memuat riwayat pesanan..."
            )
        );

        try {
            const tokens = savedOrders.map((item) => item.token);

            const response = await fetch(API_URL, {
                method: "POST",
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ tokens })
            });

            let result;

            try {
                result = await response.json();
            } catch {
                throw new Error(
                    "Server mengembalikan respons yang tidak valid."
                );
            }

            if (!response.ok) {
                throw new Error(
                    result.message ||
                    "Gagal memuat riwayat pesanan."
                );
            }

            if (result.success === false) {
                throw new Error(
                    result.message ||
                    "Gagal memuat riwayat pesanan."
                );
            }

            const orders = Array.isArray(result.orders)
                ? result.orders
                : [];

            /*
             * Menggabungkan nomor pesanan dari API dengan
             * metadata lokal jika API tidak mengirimkannya.
             */
            currentOrders = orders.map((order) => {
                const localOrder = savedOrders.find((item) => {
                    return (
                        String(item.order_number || "") ===
                        String(order.order_number || "")
                    );
                });

                return {
                    ...order,
                    order_number:
                        order.order_number ||
                        localOrder?.order_number ||
                        "",
                    created_at:
                        order.created_at ||
                        localOrder?.created_at ||
                        ""
                };
            });

            /*
             * Urutkan dari pesanan terbaru.
             */
            currentOrders.sort((a, b) => {
                const dateA = new Date(
                    String(a.created_at || "").replace(" ", "T")
                ).getTime() || 0;

                const dateB = new Date(
                    String(b.created_at || "").replace(" ", "T")
                ).getTime() || 0;

                return dateB - dateA;
            });

            renderOrders();
        } catch (error) {
            console.error(
                "[Buyer Dashboard] Gagal memuat pesanan:",
                error
            );

            currentOrders = [];
            renderOrders();

            showMessage(
                error.message ||
                "Tidak dapat memuat pesanan. Periksa koneksi lalu coba lagi.",
                "error"
            );
        } finally {
            isLoading = false;
        }
    }

    /*
     * Inisialisasi Dashboard Buyer.
     */
    function init() {
        if (elements.currentYear) {
            elements.currentYear.textContent =
                String(new Date().getFullYear());
        }

        if (!elements.orders) {
            return;
        }

        loadOrders();
        startOrderRefresh();
    }

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            init,
            { once: true }
        );
    } else {
        init();
    }
})();
