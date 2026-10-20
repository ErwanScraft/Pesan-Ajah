
/* =========================================================
   PESAN AJAH — BUYER DASHBOARD
   File: assets/js/buyer-dashboard.js
========================================================= */

(() => {
    "use strict";

    const STORAGE_KEY = "pesan_ajah_order_tokens";
    const API_URL = "/api/orders.php";
    const REFRESH_INTERVAL = 15000;

    const ORDER_STATUS = {
        pending: {
            label: "Menunggu Konfirmasi",
            className: "pending"
        },
        confirmed: {
            label: "Dikonfirmasi",
            className: "confirmed"
        },
        processing: {
            label: "Sedang Diproses",
            className: "processing"
        },
        ready_for_pickup: {
            label: "Tersedia",
            className: "ready-for-pickup"
        },
        ready_for_driver: {
            label: "Menunggu Driver",
            className: "ready-for-driver"
        },
        in_delivery: {
            label: "Diantar",
            className: "in-delivery"
        },
        completed: {
            label: "Selesai",
            className: "completed"
        },
        cancelled: {
            label: "Dibatalkan",
            className: "cancelled"
        }
    };

    const DELIVERY_STATUS = {
        accepted: "Driver menerima pesanan",
        to_store: "Driver menuju warung",
        picked_up: "Pesanan diambil dari warung",
        to_buyer: "Driver menuju pembeli",
        completed: "Pengantaran selesai",
        cancelled: "Pengantaran dibatalkan"
    };

    const DELIVERY_STEPS = [
        { key: "accepted", label: "Driver menerima pesanan" },
        { key: "to_store", label: "Driver menuju warung" },
        { key: "picked_up", label: "Pesanan diambil dari warung" },
        { key: "to_buyer", label: "Driver menuju pembeli" },
        { key: "completed", label: "Pesanan sampai" }
    ];

    const elements = {
        message: document.getElementById("buyer-message"),
        orders: document.getElementById("buyer-orders"),
        empty: document.getElementById("buyer-empty"),
        year: document.getElementById("buyer-current-year"),
        activeCount: document.getElementById("buyer-active-count"),
        completedCount: document.getElementById("buyer-completed-count")
    };

    let currentOrders = [];
    let isLoading = false;
    let refreshTimer = null;
    let hasLoadedSuccessfully = false;

    /* -----------------------------------------------------
       Helpers
    ----------------------------------------------------- */

    function normalize(value) {
        return String(value ?? "").trim().toLowerCase();
    }

    function createElement(tag, className = "", text = null) {
        const element = document.createElement(tag);

        if (className) element.className = className;
        if (text !== null) element.textContent = String(text);

        return element;
    }

    function formatRupiah(value) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0
        }).format(Number(value) || 0);
    }

    function formatDate(value) {
        if (!value) return "Tanggal tidak tersedia";

        const raw = String(value);
        const date = new Date(
            raw.includes("T") ? raw : raw.replace(" ", "T")
        );

        if (Number.isNaN(date.getTime())) return raw;

        return new Intl.DateTimeFormat("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        }).format(date);
    }

    function safeMapsUrl(value) {
        if (typeof value !== "string" || !value.trim()) return null;

        try {
            const url = new URL(value);
            const host = url.hostname.toLowerCase();

            if (url.protocol !== "https:") return null;

            if (
                host === "google.com" ||
                host.endsWith(".google.com") ||
                host === "maps.app.goo.gl" ||
                host === "goo.gl"
            ) {
                return url.href;
            }
        } catch {
            return null;
        }

        return null;
    }

    function savedOrders() {
        try {
            const parsed = JSON.parse(
                localStorage.getItem(STORAGE_KEY) || "[]"
            );

            if (!Array.isArray(parsed)) return [];

            return parsed.filter((item) =>
                item &&
                typeof item.token === "string" &&
                /^[a-f0-9]{64}$/i.test(item.token)
            );
        } catch (error) {
            console.error("[Buyer Dashboard] Storage error:", error);
            return [];
        }
    }

    function saveOrders(items) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
            return true;
        } catch (error) {
            console.error("[Buyer Dashboard] Gagal menyimpan:", error);
            return false;
        }
    }

    function showMessage(message, type = "") {
        if (!elements.message) return;

        elements.message.textContent = message;
        elements.message.classList.toggle("is-error", type === "error");
        elements.message.classList.toggle("is-success", type === "success");
        elements.message.hidden = false;
    }

    function hideMessage() {
        if (!elements.message) return;

        elements.message.textContent = "";
        elements.message.classList.remove("is-error", "is-success");
        elements.message.hidden = true;
    }

    function statusInfo(status) {
        const key = normalize(status);
        return ORDER_STATUS[key] || {
            label: key ? key.replaceAll("_", " ") : "Status belum tersedia",
            className: "unknown"
        };
    }

    function isCompleted(order) {
        return normalize(order.status) === "completed";
    }

    function isCancelled(order) {
        return normalize(order.status) === "cancelled";
    }

    function isActive(order) {
        return !isCompleted(order) && !isCancelled(order);
    }

    /* -----------------------------------------------------
       Summary counters
    ----------------------------------------------------- */

    function renderSummary() {
        if (elements.activeCount) {
            elements.activeCount.textContent = String(
                currentOrders.filter(isActive).length
            );
        }

        if (elements.completedCount) {
            elements.completedCount.textContent = String(
                currentOrders.filter(isCompleted).length
            );
        }
    }

    /* -----------------------------------------------------
       Order summary
    ----------------------------------------------------- */

    function itemCount(order) {
        const items = Array.isArray(order.items) ? order.items : [];

        return items.reduce((total, item) => {
            return total + Math.max(0, Number(item.quantity) || 0);
        }, 0);
    }

    function deliveryMethodLabel(order) {
        return normalize(order.delivery_method) === "pickup"
            ? "Ambil sendiri"
            : "Delivery";
    }

    function createOrderHeader(order) {
        const header = createElement("header", "buyer-order-header");
        const identity = createElement("div", "buyer-order-identity");

        identity.append(
            createElement(
                "h2",
                "buyer-order-store",
                order.store_name || "Warung"
            ),
            createElement(
                "p",
                "buyer-order-number",
                `Pesanan #${order.order_number || "-"}`
            ),
            createElement(
                "p",
                "buyer-order-date",
                formatDate(order.created_at)
            )
        );

        const status = statusInfo(order.status);
        const badge = createElement(
            "span",
            `buyer-status-badge is-${status.className}`,
            status.label
        );

        header.append(identity, badge);
        return header;
    }

    function createOrderSummary(order) {
        const section = createElement("section", "buyer-order-summary");

        const icon = createElement("div", "buyer-summary-product-icon");
        icon.setAttribute("aria-hidden", "true");
        icon.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none">
                <path d="m12 3 9 5-9 5-9-5 9-5Z"
                    stroke="currentColor" stroke-width="1.7"
                    stroke-linejoin="round"/>
                <path d="M3 8v9l9 5 9-5V8M12 13v9"
                    stroke="currentColor" stroke-width="1.7"
                    stroke-linejoin="round"/>
            </svg>
        `;

        const content = createElement("div", "buyer-order-summary-content");
        const count = itemCount(order);

        content.append(
            createElement("h3", "buyer-order-summary-title", "Ringkasan pesanan"),
            createElement(
                "p",
                "buyer-order-summary-meta",
                `${count} produk · ${deliveryMethodLabel(order)}`
            ),
            createElement(
                "p",
                "buyer-order-total",
                formatRupiah(order.total)
            )
        );

        section.append(icon, content);
        return section;
    }

    /* -----------------------------------------------------
       Status tracking
    ----------------------------------------------------- */

    function createStatusIcon(kind) {
        const wrapper = createElement("span", "buyer-tracking-icon");
        wrapper.setAttribute("aria-hidden", "true");

        const paths = {
            store: `
                <path d="M3 10h18l-2-6H5l-2 6Z"
                    stroke="currentColor" stroke-width="1.7"
                    stroke-linejoin="round"/>
                <path d="M5 10v10h14V10M9 20v-6h6v6"
                    stroke="currentColor" stroke-width="1.7"
                    stroke-linejoin="round"/>
            `,
            delivery: `
                <circle cx="5.5" cy="17.5" r="2.5"
                    stroke="currentColor" stroke-width="1.7"/>
                <circle cx="18.5" cy="17.5" r="2.5"
                    stroke="currentColor" stroke-width="1.7"/>
                <path d="M8 17.5h6l-3-8H7m4 0h4l3.5 8M14 6h3"
                    stroke="currentColor" stroke-width="1.7"
                    stroke-linecap="round" stroke-linejoin="round"/>
            `
        };

        wrapper.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none">
                ${paths[kind] || paths.store}
            </svg>
        `;

        return wrapper;
    }

    function createTrackingRow(title, description, icon, badge = null) {
        const row = createElement("div", "buyer-tracking-row");
        const symbol = createStatusIcon(icon);
        const copy = createElement("div", "buyer-tracking-copy");

        copy.append(
            createElement("h4", "buyer-tracking-title", title),
            createElement("p", "buyer-tracking-description", description)
        );

        row.append(symbol, copy);

        if (badge) {
            row.appendChild(
                createElement("span", "buyer-tracking-badge", badge)
            );
        }

        return row;
    }

    function createProgress(order) {
        const deliveryStatus = normalize(order.delivery_status);
        const panel = createElement("section", "buyer-tracking-panel");

        const orderStatus = normalize(order.status);
        const orderDescription = {
            pending: "Menunggu konfirmasi warung",
            confirmed: "Pesanan telah dikonfirmasi",
            processing: "Pesanan sedang disiapkan",
            ready_for_pickup: "Pesanan tersedia dan siap diambil di warung",
            ready_for_driver: "Pesanan siap diserahkan kepada driver",
            in_delivery: "Pesanan sedang diantarkan kepada pembeli",
            completed: "Pesanan telah selesai",
            cancelled: "Pesanan dibatalkan"
        }[orderStatus] || "Status pesanan diperbarui";

        const storeRow = createTrackingRow(
            "Pesanan warung",
            orderDescription,
            "store",
            orderStatus === "completed"
                ? "Selesai"
                : orderStatus === "cancelled"
                    ? "Dibatalkan"
                    : null
        );

        panel.appendChild(storeRow);

        if (normalize(order.delivery_method) !== "delivery") {
            panel.appendChild(
                createTrackingRow(
                    "Pengambilan",
                    "Pesanan diambil langsung di warung",
                    "delivery",
                    "Ambil sendiri"
                )
            );

            return panel;
        }

        const deliveryLabel = orderStatus === "ready_for_driver"
            ? "Menunggu driver mengambil pesanan"
            : orderStatus === "in_delivery"
                ? "Pesanan sedang diantarkan"
                : orderStatus === "completed"
                    ? "Pengantaran selesai"
                    : DELIVERY_STATUS[deliveryStatus] || "Menunggu driver";

        panel.appendChild(
            createTrackingRow(
                "Pengantaran",
                deliveryLabel,
                "delivery",
                deliveryStatus === "completed"
                    ? "Selesai"
                    : deliveryStatus === "cancelled"
                        ? "Dibatalkan"
                        : deliveryStatus === "accepted" ||
                          deliveryStatus === "to_store" ||
                          deliveryStatus === "picked_up" ||
                          deliveryStatus === "to_buyer"
                            ? "Berjalan"
                            : "Menunggu"
            )
        );

        const steps = createElement("ol", "buyer-progress-steps");
        const currentIndex = DELIVERY_STEPS.findIndex(
            (step) => step.key === deliveryStatus
        );

        DELIVERY_STEPS.forEach((step, index) => {
            const item = createElement(
                "li",
                "buyer-progress-step"
            );

            if (currentIndex >= 0 && index <= currentIndex) {
                item.classList.add("is-complete");
            }

            if (index === currentIndex) {
                item.classList.add("is-current");
            }

            item.appendChild(
                createElement("span", "buyer-progress-label", step.label)
            );

            steps.appendChild(item);
        });

        if (
            currentIndex >= 0 &&
            deliveryStatus !== "cancelled"
        ) {
            panel.appendChild(steps);

            const progress = createElement("div", "buyer-progress-track");
            const bar = createElement("span", "buyer-progress-bar");

            const percent = currentIndex === 0
                ? 15
                : (currentIndex / (DELIVERY_STEPS.length - 1)) * 100;

            bar.style.width = `${Math.min(100, percent)}%`;
            progress.appendChild(bar);
            panel.appendChild(progress);
        }

        panel.appendChild(
            createElement(
                "p",
                "buyer-tracking-note",
                "Status diperbarui otomatis"
            )
        );

        return panel;
    }

    /* -----------------------------------------------------
       Details: products, payment, address
    ----------------------------------------------------- */

    function createDetails(order) {
        const details = createElement("div", "buyer-order-details");
        details.hidden = true;

        const itemsSection = createElement("section", "buyer-detail-section");
        itemsSection.appendChild(
            createElement("h3", "buyer-detail-heading", "Rincian produk")
        );

        const list = createElement("ul", "buyer-item-list");
        const items = Array.isArray(order.items) ? order.items : [];

        if (items.length === 0) {
            list.appendChild(
                createElement("li", "buyer-item-empty", "Rincian produk tidak tersedia.")
            );
        }

        items.forEach((item) => {
            const row = createElement("li", "buyer-item");
            const info = createElement("div", "buyer-item-info");
            const name = item.product_name || "Produk";
            const quantity = Math.max(0, Number(item.quantity) || 0);
            const price = Number(item.subtotal) ||
                ((Number(item.price) || 0) * quantity);

            info.appendChild(
                createElement("p", "buyer-item-name", `${name} × ${quantity}`)
            );

            if (item.product_variant) {
                info.appendChild(
                    createElement(
                        "p",
                        "buyer-item-variant",
                        item.product_variant
                    )
                );
            }

            row.append(
                info,
                createElement("strong", "buyer-item-price", formatRupiah(price))
            );

            list.appendChild(row);
        });

        itemsSection.appendChild(list);
        details.appendChild(itemsSection);

        const payment = createElement("section", "buyer-detail-section");
        payment.appendChild(
            createElement("h3", "buyer-detail-heading", "Pembayaran")
        );

        payment.append(
            paymentRow("Subtotal", formatRupiah(order.subtotal)),
            ...(normalize(order.delivery_method) === "delivery"
                ? [paymentRow("Ongkos kirim", formatRupiah(order.delivery_fee))]
                : []),
            paymentRow("Total pembayaran", formatRupiah(order.total), true)
        );

        details.appendChild(payment);

        if (normalize(order.delivery_method) === "delivery") {
            const address = String(order.delivery_address || "").trim();
            const maps = safeMapsUrl(order.delivery_maps_url);

            if (address || maps) {
                const addressSection = createElement(
                    "section",
                    "buyer-detail-section"
                );

                addressSection.appendChild(
                    createElement("h3", "buyer-detail-heading", "Alamat pengantaran")
                );

                if (address) {
                    addressSection.appendChild(
                        createElement("p", "buyer-address-text", address)
                    );
                }

                if (maps) {
                    const link = createElement(
                        "a",
                        "buyer-map-link",
                        "Lihat lokasi di Google Maps"
                    );

                    link.href = maps;
                    link.target = "_blank";
                    link.rel = "noopener noreferrer";
                    addressSection.appendChild(link);
                }

                details.appendChild(addressSection);
            }
        }

        return details;
    }

    function paymentRow(label, value, total = false) {
        const row = createElement(
            "div",
            total ? "buyer-payment-row is-total" : "buyer-payment-row"
        );

        row.append(
            createElement("span", "buyer-payment-label", label),
            createElement("strong", "buyer-payment-value", value)
        );

        return row;
    }

    /* -----------------------------------------------------
       Order card
    ----------------------------------------------------- */

    function createOrderCard(order, index) {
        const card = createElement("article", "buyer-order-card");
        const detailsId = `buyer-order-details-${index}`;

        card.append(
            createOrderHeader(order),
            createOrderSummary(order),
            createProgress(order)
        );

        const footer = createElement("footer", "buyer-order-footer");
        const count = itemCount(order);

        footer.appendChild(
            createElement(
                "span",
                "buyer-order-item-count",
                `${count} produk dalam pesanan`
            )
        );

        const toggle = createElement("button", "buyer-details-toggle");
        toggle.type = "button";
        toggle.setAttribute("aria-expanded", "false");
        toggle.setAttribute("aria-controls", detailsId);

        toggle.append(
            createElement("span", "buyer-details-toggle-label", "Lihat rincian"),
            createElement("span", "buyer-details-chevron", "›")
        );

        const details = createDetails(order);
        details.id = detailsId;

        toggle.addEventListener("click", () => {
            const opening = details.hidden;
            details.hidden = !opening;
            toggle.setAttribute("aria-expanded", String(opening));

            const label = toggle.querySelector(".buyer-details-toggle-label");
            if (label) {
                label.textContent = opening ? "Tutup rincian" : "Lihat rincian";
            }

            toggle.classList.toggle("is-open", opening);
        });

        footer.appendChild(toggle);
        card.append(footer, details);

        const orderActions = createElement("div", "buyer-order-actions");
        const orderStatus = normalize(order.status);
        const method = normalize(order.delivery_method);
        
        /*
         * Cari token lokal berdasarkan nomor pesanan.
         * Token mentah tidak dikirim kembali dari server.
         */
        const localOrder = savedOrders().find((item) => {
            return String(item.order_number || "") ===
                String(order.order_number || "");
        });
        
        if (
            method === "pickup" &&
            orderStatus === "ready_for_pickup" &&
            localOrder?.token
        ) {
            const confirmButton = createElement(
                "button",
                "buyer-details-toggle",
                "Konfirmasi sudah diambil"
            );
        
            confirmButton.type = "button";
        
            confirmButton.addEventListener("click", async () => {
                if (
                    !window.confirm(
                        "Konfirmasi bahwa pesanan ini sudah diambil dari warung?"
                    )
                ) {
                    return;
                }
        
                confirmButton.disabled = true;
                confirmButton.textContent = "Memproses...";
        
                try {
                    const response = await fetch(API_URL, {
                        method: "POST",
                        credentials: "same-origin",
                        cache: "no-store",
                        headers: {
                            Accept: "application/json",
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            action: "confirm_pickup",
                            token: localOrder.token
                        })
                    });
        
                    const result = await response.json();
        
                    if (!response.ok || result.success !== true) {
                        throw new Error(
                            result.message ||
                            "Konfirmasi pengambilan gagal."
                        );
                    }
        
                    showMessage(
                        "Pengambilan pesanan berhasil dikonfirmasi.",
                        "success"
                    );
        
                    await loadOrders();
                } catch (error) {
                    showMessage(
                        error instanceof Error
                            ? error.message
                            : "Konfirmasi gagal. Silakan coba lagi.",
                        "error"
                    );
        
                    confirmButton.disabled = false;
                    confirmButton.textContent = "Konfirmasi sudah diambil";
                }
            });
        
            orderActions.appendChild(confirmButton);
        }
        
        if (isCompleted(order) || isCancelled(order)) {
            const deleteButton = createElement(
                "button",
                "buyer-order-delete",
                "Hapus dari riwayat"
            );
        
            deleteButton.type = "button";
            deleteButton.addEventListener("click", () => {
                removeOrder(order);
            });
        
            orderActions.appendChild(deleteButton);
        }
        
        if (orderActions.childElementCount > 0) {
            card.appendChild(orderActions);
        }

        return card;
    }

    function removeOrder(order) {
        const status = normalize(order.status);

        if (status !== "completed" && status !== "cancelled") return;

        const number = String(order.order_number || "");
        const question = `Hapus pesanan ${number || "ini"} dari riwayat di browser ini?`;

        if (!window.confirm(question)) return;

        const updated = savedOrders().filter((item) => {
            return String(item.order_number || "") !== number;
        });

        if (!saveOrders(updated)) {
            showMessage("Riwayat gagal diperbarui.", "error");
            return;
        }

        currentOrders = currentOrders.filter((item) => {
            return String(item.order_number || "") !== number;
        });

        renderOrders();
        showMessage("Pesanan dihapus dari riwayat browser.", "success");
    }

    /* -----------------------------------------------------
       Render and API
    ----------------------------------------------------- */

    function renderOrders() {
        if (!elements.orders) return;

        elements.orders.replaceChildren();

        currentOrders.forEach((order, index) => {
            elements.orders.appendChild(createOrderCard(order, index));
        });

        const hasOrders = currentOrders.length > 0;
        elements.orders.hidden = !hasOrders;

        if (elements.empty) {
            elements.empty.hidden = hasOrders;
        }

        renderSummary();
    }

    async function loadOrders() {
        if (isLoading || !elements.orders) return;

        isLoading = true;
        const localOrders = savedOrders();

        if (localOrders.length === 0) {
            currentOrders = [];
            renderOrders();
            hideMessage();
            isLoading = false;
            return;
        }

        if (!hasLoadedSuccessfully && currentOrders.length === 0) {
            elements.orders.hidden = false;
            elements.orders.replaceChildren(
                createElement("div", "buyer-loading", "Memuat pesanan...")
            );

            if (elements.empty) elements.empty.hidden = true;
        }

        try {
            const response = await fetch(API_URL, {
                method: "POST",
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    tokens: localOrders.map((item) => item.token)
                })
            });

            const result = await response.json();

            if (!response.ok || result.success === false) {
                throw new Error(
                    result.message || "Pesanan gagal dimuat."
                );
            }

            const orders = Array.isArray(result.orders)
                ? result.orders
                : [];

            currentOrders = orders.map((order) => {
                const local = localOrders.find((item) => {
                    return item.order_number &&
                        String(item.order_number) === String(order.order_number);
                });

                return {
                    ...order,
                    order_number: order.order_number || local?.order_number || "",
                    created_at: order.created_at || local?.created_at || ""
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

            hasLoadedSuccessfully = true;
            hideMessage();
            renderOrders();
        } catch (error) {
            console.error("[Buyer Dashboard] Gagal memuat pesanan:", error);

            if (!hasLoadedSuccessfully) {
                currentOrders = [];
                renderOrders();
            }

            showMessage(
                error.message || "Koneksi bermasalah. Coba muat ulang.",
                "error"
            );
        } finally {
            isLoading = false;
        }
    }

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
        if (document.visibilityState === "visible" && !isLoading) {
            loadOrders();
        }
    });

    function init() {
        if (elements.year) {
            elements.year.textContent = String(new Date().getFullYear());
        }

        if (!elements.orders) return;

        loadOrders();
        startOrderRefresh();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init, { once: true });
    } else {
        init();
    }
})();
