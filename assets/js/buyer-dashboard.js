/* =========================================================
   PESAN AJAH — BUYER DASHBOARD
   File: assets/js/buyer-dashboard.js

   Fitur:
   - Memuat pesanan melalui token pelacakan lokal.
   - Menampilkan status pesanan warung dan pengantaran.
   - Menampilkan rincian produk, varian, dan pembayaran.
   - Menampilkan alamat dan tautan Google Maps yang tervalidasi.
   - Menghapus pesanan selesai/dibatalkan dari riwayat lokal.
   - Refresh otomatis setiap 15 detik.
   - Loading, empty state, dan error handling.
========================================================= */

(() => {
    "use strict";

    const STORAGE_KEY = "pesan_ajah_order_tokens";
    const API_URL = "/api/orders.php";
    const REFRESH_INTERVAL = 15000;

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

    /* =====================================================
       UTILITIES
    ===================================================== */

    function normalizeStatus(value) {
        return String(value || "")
            .trim()
            .toLowerCase();
    }

    function formatRupiah(value) {
        const amount = Number(value) || 0;

        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0
        }).format(amount);
    }

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

    function createElement(tag, className = "", text = null) {
        const element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        if (text !== null && text !== undefined) {
            element.textContent = String(text);
        }

        return element;
    }

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

            const allowed =
                allowedHosts.includes(hostname) ||
                hostname.endsWith(".google.com");

            return allowed ? url.href : null;
        } catch {
            return null;
        }
    }

    /* =====================================================
       LOCAL ORDER STORAGE
    ===================================================== */

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

    /* =====================================================
       MESSAGES AND EMPTY STATE
    ===================================================== */

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

    function updateEmptyState() {
        const hasOrders = currentOrders.length > 0;

        if (elements.orders) {
            elements.orders.hidden = !hasOrders;
        }

        if (elements.empty) {
            elements.empty.hidden = hasOrders;
        }
    }

    function showLoadingState() {
        if (!elements.orders) {
            return;
        }

        elements.orders.hidden = false;
        elements.orders.replaceChildren();

        elements.orders.appendChild(
            createElement(
                "div",
                "buyer-loading",
                "Memuat riwayat pesanan..."
            )
        );

        if (elements.empty) {
            elements.empty.hidden = true;
        }
    }

    /* =====================================================
       PAYMENT COMPONENTS
    ===================================================== */

    function createPaymentRow(label, value, isTotal = false) {
        const row = createElement(
            "div",
            isTotal
                ? "buyer-payment-row buyer-payment-total"
                : "buyer-payment-row"
        );

        row.append(
            createElement(
                "span",
                "buyer-payment-label",
                label
            ),
            createElement(
                "span",
                "buyer-payment-value",
                value
            )
        );

        return row;
    }

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
                    formatRupiah(deliveryFee)
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

    /* =====================================================
       ORDER ITEMS
    ===================================================== */

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

                const details = createElement(
                    "div",
                    "buyer-item-details"
                );

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

                details.appendChild(
                    createElement(
                        "div",
                        "buyer-item-name",
                        `${name} × ${quantity}`
                    )
                );

                const variant = String(
                    item.product_variant || ""
                ).trim();

                if (variant) {
                    details.appendChild(
                        createElement(
                            "small",
                            "buyer-item-variant",
                            variant
                        )
                    );
                }

                row.append(
                    details,
                    createElement(
                        "div",
                        "buyer-item-total",
                        formatRupiah(subtotal)
                    )
                );

                list.appendChild(row);
            });
        }

        section.appendChild(list);

        return section;
    }

    /* =====================================================
       DELIVERY ADDRESS
    ===================================================== */

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

    /* =====================================================
       ORDER AND DELIVERY STATUS
    ===================================================== */

    function createOrderStatusSection(order) {
        const section = createElement(
            "section",
            "buyer-order-status-panel"
        );

        const orderStatus = normalizeStatus(order.status) ||
            "pending";

        const statusRow = createElement(
            "div",
            "buyer-status-row"
        );

        statusRow.append(
            createElement(
                "span",
                "buyer-status-caption",
                "Status warung"
            ),
            createElement(
                "span",
                [
                    "buyer-status",
                    STATUS_CLASSES[orderStatus] ||
                        "buyer-status-pending"
                ].join(" "),
                STATUS_LABELS[orderStatus] ||
                    "Status belum tersedia"
            )
        );

        section.appendChild(statusRow);

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

        const deliveryStatus = normalizeStatus(
            order.delivery_status
        );

        const deliveryLabel = deliveryStatus
            ? (
                DELIVERY_STATUS_LABELS[deliveryStatus] ||
                "Status pengantaran belum tersedia"
            )
            : "Menunggu driver mengambil pesanan";

        const deliveryRow = createElement(
            "div",
            "buyer-delivery-status-row"
        );

        const badgeClasses = ["buyer-delivery-badge"];

        if (deliveryStatus === "completed") {
            badgeClasses.push("is-complete");
        }

        if (deliveryStatus === "cancelled") {
            badgeClasses.push("is-cancelled");
        }

        deliveryRow.append(
            createElement(
                "span",
                "buyer-status-caption",
                "Status pengantaran"
            ),
            createElement(
                "span",
                badgeClasses.join(" "),
                deliveryLabel
            )
        );

        section.appendChild(deliveryRow);

        if (
            !deliveryStatus ||
            deliveryStatus === "cancelled" ||
            !DELIVERY_STEPS.includes(deliveryStatus)
        ) {
            return section;
        }

        const list = createElement(
            "ol",
            "buyer-delivery-steps"
        );

        const currentIndex = DELIVERY_STEPS.indexOf(
            deliveryStatus
        );

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

    /* =====================================================
       DELETE FROM LOCAL HISTORY
    ===================================================== */

    function removeBuyerOrder(order) {
        const status = normalizeStatus(order.status);

        if (!DELETABLE_STATUSES.has(status)) {
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

        const confirmation = status === "completed"
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

    /* =====================================================
       ORDER FOOTER
    ===================================================== */

    function createOrderFooter(order) {
        const footer = createElement(
            "footer",
            "buyer-order-footer"
        );

        const methodLabel =
            order.delivery_method === "pickup"
                ? "Ambil sendiri"
                : "Diantar";

        footer.append(
            createElement(
                "span",
                "buyer-order-method",
                `Metode: ${methodLabel}`
            ),
            createElement(
                "span",
                "buyer-order-updated",
                `Diperbarui: ${formatDate(
                    order.updated_at || order.created_at
                )}`
            )
        );

        const status = normalizeStatus(order.status);

        if (DELETABLE_STATUSES.has(status)) {
            const actions = createElement(
                "div",
                "buyer-order-actions"
            );

            const button = createElement(
                "button",
                "buyer-order-delete",
                STATUS_DELETE_LABELS[status]
            );

            button.type = "button";

            button.addEventListener("click", () => {
                removeBuyerOrder(order);
            });

            actions.appendChild(button);
            footer.appendChild(actions);
        }

        return footer;
    }

    /* =====================================================
       ORDER CARD
    ===================================================== */

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

        heading.append(
            createElement(
                "p",
                "buyer-order-store",
                order.store_name ||
                order.store ||
                "Warung"
            ),
            createElement(
                "p",
                "buyer-order-number",
                `No. Pesanan: ${order.order_number || "-"}`
            ),
            createElement(
                "p",
                "buyer-order-date",
                formatDate(order.created_at)
            )
        );

        header.appendChild(heading);
        card.appendChild(header);

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

    /* =====================================================
       RENDER ORDERS
    ===================================================== */

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

    /* =====================================================
       LOAD ORDERS
    ===================================================== */

    async function loadOrders() {
        if (isLoading || !elements.orders) {
            return;
        }

        isLoading = true;

        const savedOrders = getSavedOrders();

        if (savedOrders.length === 0) {
            currentOrders = [];
            renderOrders();
            hideMessage();
            isLoading = false;
            return;
        }

        /*
         * Jangan menghapus tampilan pesanan terakhir saat
         * refresh berlangsung. Loading awal saja yang memakai
         * placeholder.
         */
        if (currentOrders.length === 0) {
            showLoadingState();
        }

        hideMessage();

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

            if (!response.ok || result.success === false) {
                throw new Error(
                    result.message ||
                    "Gagal memuat riwayat pesanan."
                );
            }

            const orders = Array.isArray(result.orders)
                ? result.orders
                : [];

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

            /*
             * Jika pernah berhasil memuat data, pertahankan data
             * terakhir. Gangguan jaringan tidak membuat daftar
             * pesanan langsung terlihat kosong.
             */
            if (currentOrders.length > 0) {
                renderOrders();
            } else {
                elements.orders.replaceChildren();
                updateEmptyState();
            }

            showMessage(
                error.message ||
                "Tidak dapat memuat pesanan. Periksa koneksi lalu coba lagi.",
                "error"
            );
        } finally {
            isLoading = false;
        }
    }

    /* =====================================================
       AUTO REFRESH
    ===================================================== */

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
        }, REFRESH_INTERVAL);
    }

    document.addEventListener("visibilitychange", () => {
        if (
            document.visibilityState === "visible" &&
            !isLoading
        ) {
            loadOrders();
        }
    });

    /* =====================================================
       INITIALIZATION
    ===================================================== */

    function init() {
        if (elements.currentYear) {
            elements.currentYear.textContent = String(
                new Date().getFullYear()
            );
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