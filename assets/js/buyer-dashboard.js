/* =========================================================
   PESAN AJAH — BUYER DASHBOARD
   File: assets/js/buyer-dashboard.js
========================================================= */

(() => {
    "use strict";

    const STORAGE_KEY = "pesan_ajah_order_tokens";
    const API_URL = "/api/orders.php";
    const EDIT_API_URL = "/api/edit-order.php";
    
    const EDITABLE_STATUSES = [
        "pending",
        "confirmed",
        "processing"
    ];

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
        completedCount: document.getElementById("buyer-completed-count"),
        categoryTabs: document.querySelectorAll(".buyer-order-tab"),
        categoryCounts: {
            waiting: document.getElementById("buyer-count-waiting"),
            processing: document.getElementById("buyer-count-processing"),
            handoff: document.getElementById("buyer-count-handoff"),
            completed: document.getElementById("buyer-count-completed")
        },
        emptyTitle: document.getElementById("buyer-empty-title"),
        emptyDescription: document.getElementById("buyer-empty-description")
    };

    let currentOrders = [];
    let activeCategory = "waiting";
    
    const ORDER_CATEGORIES = {
        waiting: {
            title: "Belum ada pesanan menunggu",
            description: "Pesanan yang menunggu konfirmasi warung akan muncul di sini."
        },
        processing: {
            title: "Belum ada pesanan diproses",
            description: "Pesanan yang sudah dikonfirmasi dan sedang disiapkan akan muncul di sini."
        },
        handoff: {
            title: "Belum ada pesanan untuk diterima",
            description: "Pesanan yang siap diambil atau sedang diantarkan akan muncul di sini."
        },
        completed: {
            title: "Belum ada riwayat pesanan",
            description: "Pesanan yang selesai atau dibatalkan akan muncul di sini."
        }
    };
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
    
    function getOrderCategory(order) {
        const status = normalize(order.status);
        const method = normalize(order.delivery_method);
    
        if (status === "completed" || status === "cancelled") {
            return "completed";
        }
    
        if (status === "pending") {
            return "waiting";
        }
    
        if (status === "confirmed" || status === "processing") {
            return "processing";
        }
    
        if (
            method === "pickup" &&
            status === "ready_for_pickup"
        ) {
            return "handoff";
        }
    
        if (
            method === "delivery" &&
            (
                status === "ready_for_driver" ||
                status === "in_delivery"
            )
        ) {
            return "handoff";
        }
    
        return "processing";
    }
    
    function getOrdersByCategory(category) {
        return currentOrders.filter((order) => {
            return getOrderCategory(order) === category;
        });
    }

    /* -----------------------------------------------------
       Summary counters
    ----------------------------------------------------- */

    function renderSummary() {
        const waiting = getOrdersByCategory("waiting").length;
        const processing = getOrdersByCategory("processing").length;
        const handoff = getOrdersByCategory("handoff").length;
        const completed = getOrdersByCategory("completed").length;
    
        if (elements.activeCount) {
            elements.activeCount.textContent = String(
                waiting + processing + handoff
            );
        }
    
        if (elements.completedCount) {
            elements.completedCount.textContent = String(
                currentOrders.filter(isCompleted).length
            );
        }
    
        Object.entries(elements.categoryCounts).forEach(([category, element]) => {
            if (!element) return;
    
            element.textContent = String(
                getOrdersByCategory(category).length
            );
        });
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
    
        const isDelivery = normalize(order.delivery_method) === "delivery";
        const deliveryStatus = normalize(order.delivery_status);
        const driverStep = DELIVERY_STEPS.find(
            (step) => step.key === deliveryStatus
        );
    
        // Tampilkan progres driver hanya saat status driver tersedia.
        if (isDelivery && driverStep) {
            const driverProgress = createElement(
                "div",
                "buyer-driver-progress-summary"
            );
    
            const icon = createStatusIcon("delivery");
            icon.classList.add("buyer-driver-progress-icon");
    
            const info = createElement("div", "buyer-driver-progress-info");
    
            info.append(
                createElement(
                    "span",
                    "buyer-driver-progress-caption",
                    "PROGRES DRIVER"
                ),
                createElement(
                    "strong",
                    "buyer-driver-progress-status",
                    driverStep.label
                )
            );
    
            driverProgress.append(icon, info);
            section.append(driverProgress);
        }
    
        section.append(content);
    
        return section;
    }

    /* -----------------------------------------------------
       Status tracking — Horizontal icon stepper
    ----------------------------------------------------- */
    
    function createStatusIcon(kind) {
        const wrapper = createElement("span", "buyer-progress-icon");
        wrapper.setAttribute("aria-hidden", "true");
    
        const paths = {
            confirm: `
                <path d="m5 12 4 4L19 6"
                    stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z"
                    stroke="currentColor" stroke-width="1.7"/>
            `,
            process: `
                <path d="M6 3h12l2 5H4l2-5Z"
                    stroke="currentColor" stroke-width="1.7"
                    stroke-linejoin="round"/>
                <path d="M5 8v13h14V8M9 12h6M9 16h6"
                    stroke="currentColor" stroke-width="1.7"
                    stroke-linecap="round" stroke-linejoin="round"/>
            `,
            delivery: `
                <circle cx="5.5" cy="17.5" r="2.5"
                    stroke="currentColor" stroke-width="1.7"/>
                <circle cx="18.5" cy="17.5" r="2.5"
                    stroke="currentColor" stroke-width="1.7"/>
                <path d="M8 17.5h6l-3-8H7m4 0h4l3.5 8M14 6h3"
                    stroke="currentColor" stroke-width="1.7"
                    stroke-linecap="round" stroke-linejoin="round"/>
            `,
            pickup: `
                <path d="M3 9h18l-2-5H5L3 9Z"
                    stroke="currentColor" stroke-width="1.7"
                    stroke-linejoin="round"/>
                <path d="M5 9v11h14V9M9 20v-6h6v6"
                    stroke="currentColor" stroke-width="1.7"
                    stroke-linejoin="round"/>
            `,
            completed: `
                <path d="m5 12 4 4L19 6"
                    stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"/>
            `
        };
    
        wrapper.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none">
                ${paths[kind] || paths.confirm}
            </svg>
        `;
    
        return wrapper;
    }
    
    function createProgress(order) {
        const panel = createElement("section", "buyer-tracking-panel");
    
        const orderStatus = normalize(order.status);
        const deliveryStatus = normalize(order.delivery_status);
        const isPickup = normalize(order.delivery_method) === "pickup";
        const isCancelled = orderStatus === "cancelled";
    
        if (isCancelled) {
            panel.classList.add("is-cancelled");
    
            panel.appendChild(
                createElement(
                    "p",
                    "buyer-progress-message is-cancelled",
                    "Pesanan telah dibatalkan."
                )
            );
    
            return panel;
        }
    
        const steps = isPickup
            ? [
                { key: "confirm", label: "Konfirmasi" },
                { key: "process", label: "Diproses" },
                { key: "pickup", label: "Siap diambil" },
                { key: "completed", label: "Selesai" }
            ]
            : [
                { key: "confirm", label: "Konfirmasi" },
                { key: "process", label: "Diproses" },
                { key: "delivery", label: "Diantar" },
                { key: "completed", label: "Selesai" }
            ];
    
        let currentIndex = 0;
    
        if (orderStatus === "completed" || deliveryStatus === "completed") {
            currentIndex = 3;
        } else if (isPickup) {
            if (orderStatus === "ready_for_pickup") {
                currentIndex = 2;
            } else if (orderStatus === "processing") {
                currentIndex = 1;
            }
        } else {
            if (
                orderStatus === "ready_for_driver" ||
                orderStatus === "in_delivery" ||
                ["accepted", "to_store", "picked_up", "to_buyer"].includes(deliveryStatus)
            ) {
                currentIndex = 2;
            } else if (orderStatus === "processing") {
                currentIndex = 1;
            }
        }
    
        const list = createElement("ol", "buyer-progress-steps");
        list.setAttribute("aria-label", "Progres pesanan");
    
        steps.forEach((step, index) => {
            const item = createElement("li", "buyer-progress-step");
    
            if (index < currentIndex || orderStatus === "completed" || deliveryStatus === "completed") {
                item.classList.add("is-complete");
            }
    
            if (index === currentIndex && orderStatus !== "completed" && deliveryStatus !== "completed") {
                item.classList.add("is-current");
                item.setAttribute("aria-current", "step");
            }
    
            item.appendChild(createStatusIcon(step.key));
    
            item.appendChild(
                createElement("span", "buyer-progress-label", step.label)
            );
    
            list.appendChild(item);
        });
    
        panel.appendChild(list);
    
        const note = isPickup
            ? orderStatus === "ready_for_pickup"
                ? "Pesanan siap diambil di warung."
                : orderStatus === "completed"
                    ? "Pesanan berhasil diselesaikan."
                    : "Status pengambilan diperbarui otomatis."
            : orderStatus === "ready_for_driver"
                ? "Pesanan siap diserahkan kepada driver."
                : orderStatus === "in_delivery"
                    ? "Pesanan sedang dalam perjalanan."
                    : orderStatus === "completed" || deliveryStatus === "completed"
                        ? "Pengantaran telah selesai."
                        : "Status pengantaran diperbarui otomatis.";
    
        panel.appendChild(
            createElement("p", "buyer-progress-message", note)
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
    
    function createEditModal(order, token) {
        const overlay = createElement("div", "buyer-edit-overlay");
        const modal = createElement("form", "buyer-edit-modal");
    
        modal.setAttribute("aria-label", "Edit pesanan");
    
        const header = createElement("div", "buyer-edit-header");
        const heading = createElement("div", "buyer-edit-heading");
    
        heading.append(
            createElement("h2", "", "Edit Pesanan"),
            createElement("p", "", `Pesanan #${order.order_number}`)
        );
    
        const status = ORDER_STATUS[normalize(order.status)];
    
        const statusBadge = createElement(
            "span",
            "buyer-edit-status",
            status?.label || order.status
        );
    
        const closeButton = createElement("button", "buyer-edit-close", "×");
        closeButton.type = "button";
        closeButton.setAttribute("aria-label", "Tutup");
    
        header.append(heading, statusBadge, closeButton);
    
        const content = createElement("div", "buyer-edit-content");
    
        const productSection = createElement(
            "section",
            "buyer-edit-section"
        );
    
        const productList = createElement("div", "buyer-edit-products");

        productSection.append(
            createElement("h3", "", "Daftar Item"),
            productList
        );
    
        const addButton = createElement(
            "button",
            "buyer-edit-add",
            "＋  Tambah Produk"
        );
    
        addButton.type = "button";
    
        const picker = createElement("div", "buyer-edit-picker");
        picker.hidden = true;
    
        const search = createElement("input", "buyer-edit-search");
        search.type = "search";
        search.placeholder = "Cari produk...";
        search.setAttribute("aria-label", "Cari produk");
    
        const pickerList = createElement("div", "buyer-edit-picker-list");
    
        picker.append(search, pickerList);
    
        const noteSection = createElement(
            "section",
            "buyer-edit-section"
        );
    
        noteSection.appendChild(
            createElement("h3", "", "Catatan Pesanan")
        );
    
        const noteInput = createElement("textarea", "buyer-edit-note");
        noteInput.name = "notes";
        noteInput.maxLength = 500;
        noteInput.placeholder = "Tambahkan catatan pesanan...";
        noteInput.value = order.notes || "";
    
        noteSection.appendChild(noteInput);
    
        content.append(
            productSection,
            addButton,
            picker,
            noteSection
        );
    
        const footer = createElement("div", "buyer-edit-footer");
        const totalRow = createElement("div", "buyer-edit-total");
    
        totalRow.append(
            createElement("span", "", "Total produk"),
            createElement("strong", "buyer-edit-total-value", "Rp0")
        );
    
        const actions = createElement("div", "buyer-edit-actions");
    
        const cancelButton = createElement(
            "button",
            "buyer-edit-cancel",
            "Batal"
        );
    
        cancelButton.type = "button";
    
        const saveButton = createElement(
            "button",
            "buyer-edit-save",
            "Simpan Perubahan"
        );
    
        saveButton.type = "submit";
    
        actions.append(cancelButton, saveButton);
        footer.append(totalRow, actions);
    
        modal.append(header, content, footer);
        overlay.appendChild(modal);
    
        /*
        |--------------------------------------------------------------------------
        | DATA ITEM
        |--------------------------------------------------------------------------
        */
    
        const items = (Array.isArray(order.items) ? order.items : []).map(
            (item) => ({
                productId: item.product_id ? Number(item.product_id) : null,
                name: item.product_name || "Produk",
                variant: item.product_variant || "",
                price: Number(item.price) || 0,
                quantity: Number(item.quantity) || 1,
                image: item.image || "",
                locked: !item.product_id
            })
        );
    
        let catalogProducts = [];
        let catalogLoaded = false;
        
          function resolveEditImageUrl(value) {
              if (typeof value !== "string") {
                  return "";
              }
          
              const path = value.trim();
          
              if (!path) {
                  return "";
              }
          
              if (/^(https?:\/\/|data:image\/|blob:)/i.test(path)) {
                  return path;
              }
          
              if (/^[a-z][a-z0-9+.-]*:/i.test(path)) {
                  return "";
              }
          
              const normalized = path
                  .replace(/\\/g, "/")
                .replace(/^(\.\/)+/, "")
                .replace(/^\/+/, "");
        
            if (
                !normalized ||
                normalized.split("/").includes("..")
            ) {
                return "";
            }
        
            return `/${normalized}`;
        }
    
        function getSubtotal() {
            return items.reduce(
                (total, item) => total + item.price * item.quantity,
                0
            );
        }
    
        function renderTotal() {
            totalRow.querySelector("strong").textContent =
                formatRupiah(getSubtotal());
        }
    
        function renderProducts() {
            productList.replaceChildren();
    
            items.forEach((item, index) => {
                const row = createElement("div", "buyer-edit-product");
    
                const image = createElement("img", "buyer-edit-product-image");
                
                image.alt = item.name;
                image.loading = "lazy";
                image.hidden = true;
                
                const imageUrl = resolveEditImageUrl(item.image);
                
                if (imageUrl) {
                    image.addEventListener("error", () => {
                        image.hidden = true;
                        image.removeAttribute("src");
                    }, { once: true });
                
                    image.src = imageUrl;
                    image.hidden = false;
                }
    
                const info = createElement("div", "buyer-edit-product-info");
    
                info.appendChild(
                    createElement("strong", "", item.name)
                );
    
                if (item.variant) {
                    info.appendChild(
                        createElement("small", "", item.variant)
                    );
                }
    
                info.appendChild(
                    createElement(
                        "span",
                        "",
                        formatRupiah(item.price)
                    )
                );
    
                const controls = createElement(
                    "div",
                    "buyer-edit-product-controls"
                );
    
                if (item.locked) {
                    controls.appendChild(
                        createElement("small", "", "Produk tidak tersedia")
                    );
                } else {
                    const decrease = createElement(
                        "button",
                        "buyer-edit-quantity-button",
                        "−"
                    );
    
                    decrease.type = "button";
                    decrease.setAttribute("aria-label", "Kurangi jumlah");
    
                    decrease.addEventListener("click", () => {
                        if (item.quantity > 1) {
                            item.quantity--;
                            renderProducts();
                        }
                    });
    
                    const quantity = createElement(
                        "strong",
                        "buyer-edit-quantity",
                        item.quantity
                    );
    
                    const increase = createElement(
                        "button",
                        "buyer-edit-quantity-button is-add",
                        "+"
                    );
    
                    increase.type = "button";
                    increase.setAttribute("aria-label", "Tambah jumlah");
    
                    increase.addEventListener("click", () => {
                        if (item.quantity < 99) {
                            item.quantity++;
                            renderProducts();
                        }
                    });
    
                    const remove = createElement(
                        "button",
                        "buyer-edit-remove",
                        "⌫"
                    );
    
                    remove.type = "button";
                    remove.setAttribute("aria-label", "Hapus produk");
    
                    remove.addEventListener("click", () => {
                        items.splice(index, 1);
                        renderProducts();
                    });
    
                    controls.append(
                        decrease,
                        quantity,
                        increase,
                        remove
                    );
                }
    
                row.append(image, info, controls);
                productList.appendChild(row);
            });
    
            renderTotal();
        }
    
        /*
        |--------------------------------------------------------------------------
        | KATALOG PRODUK
        |--------------------------------------------------------------------------
        */
    
        function renderPicker() {
            const query = search.value
                .trim()
                .toLocaleLowerCase("id-ID");
    
            pickerList.replaceChildren();
    
            const filtered = catalogProducts.filter((product) => {
                return (
                    product.available &&
                    (
                        product.name || ""
                    ).toLocaleLowerCase("id-ID").includes(query)
                );
            });
    
            if (!filtered.length) {
                pickerList.appendChild(
                    createElement(
                        "p",
                        "buyer-edit-picker-empty",
                        "Produk tidak ditemukan atau tidak tersedia."
                    )
                );
    
                return;
            }
    
            filtered.forEach((product) => {
                const button = createElement(
                    "button",
                    "buyer-edit-picker-product"
                );
    
                button.type = "button";
    
                const name = createElement(
                    "span",
                    "",
                    product.name
                );
    
                const detail = createElement(
                    "small",
                    "",
                    `${formatRupiah(product.price)}${product.variant ? ` · ${product.variant}` : ""}`
                );
    
                const addIcon = createElement("strong", "", "+");
    
                button.append(name, detail, addIcon);
    
                button.addEventListener("click", () => {
                    const existing = items.find(
                        (item) => item.productId === Number(product.id)
                    );
    
                    if (existing) {
                        if (existing.quantity >= 99) {
                            showMessage(
                                "Jumlah maksimal produk adalah 99.",
                                "error"
                            );
                            return;
                        }
    
                        existing.quantity++;
                    } else {
                        items.push({
                            productId: Number(product.id),
                            name: product.name,
                            variant: product.variant || "",
                            price: Number(product.price),
                            quantity: 1,
                            image: product.image || "",
                            locked: false
                        });
                    }
    
                    renderProducts();
                    picker.hidden = false;
                    search.value = "";
                    renderPicker();
                });
    
                pickerList.appendChild(button);
            });
        }
    
        async function loadPickerProducts() {
            if (catalogLoaded) {
                renderPicker();
                return;
            }
    
            pickerList.replaceChildren(
                createElement("p", "", "Memuat produk...")
            );
    
            try {
                const response = await fetch(
                    `/api/catalog.php?store_id=${encodeURIComponent(order.store_id)}`,
                    {
                        method: "GET",
                        credentials: "same-origin",
                        cache: "no-store",
                        headers: {
                            Accept: "application/json"
                        }
                    }
                );
    
                const result = await response.json();
    
                if (!response.ok || result.success !== true) {
                    throw new Error(
                        result.message || "Katalog gagal dimuat."
                    );
                }
    
                catalogProducts = (result.products || []).map((product) => ({
                    ...product,
                    id: Number(product.id),
                    price: Number(product.price),
                    available: Boolean(product.available)
                }));
    
                catalogLoaded = true;
                renderPicker();
            } catch (error) {
                pickerList.replaceChildren(
                    createElement(
                        "p",
                        "buyer-edit-picker-empty",
                        error.message || "Katalog gagal dimuat."
                    )
                );
            }
        }
    
        addButton.addEventListener("click", async () => {
            picker.hidden = !picker.hidden;
    
            if (!picker.hidden) {
                await loadPickerProducts();
                search.focus();
            }
        });
    
        search.addEventListener("input", renderPicker);
    
        /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */
    
        function closeModal() {
            overlay.remove();
            document.body.classList.remove("buyer-edit-open");
        }
    
        closeButton.addEventListener("click", closeModal);
        cancelButton.addEventListener("click", closeModal);
    
        overlay.addEventListener("click", (event) => {
            if (event.target === overlay) {
                closeModal();
            }
        });
    
        document.body.appendChild(overlay);
        document.body.classList.add("buyer-edit-open");
    
        renderProducts();
    
        /*
        |--------------------------------------------------------------------------
        | SIMPAN PERUBAHAN
        |--------------------------------------------------------------------------
        */
    
        modal.addEventListener("submit", async (event) => {
            event.preventDefault();
    
            const updatedItems = items
                .filter((item) => !item.locked)
                .map((item) => ({
                    product_id: item.productId,
                    quantity: item.quantity
                }));
    
            if (!updatedItems.length && !items.some((item) => item.locked)) {
                showMessage(
                    "Pesanan harus memiliki minimal satu produk.",
                    "error"
                );
                return;
            }
    
            saveButton.disabled = true;
            saveButton.textContent = "Menyimpan...";
    
            try {
                const response = await fetch(EDIT_API_URL, {
                    method: "POST",
                    credentials: "same-origin",
                    cache: "no-store",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        order_number: order.order_number,
                        buyer_token: token,
                        notes: noteInput.value.trim(),
                        items: updatedItems
                    })
                });
    
                const result = await response.json();
    
                if (!response.ok || result.success !== true) {
                    throw new Error(
                        result.message || "Pesanan gagal diperbarui."
                    );
                }
    
                closeModal();
    
                showMessage(
                    result.message || "Pesanan berhasil diperbarui.",
                    "success"
                );
    
                await loadOrders();
            } catch (error) {
                showMessage(
                    error instanceof Error
                        ? error.message
                        : "Pesanan gagal diperbarui.",
                    "error"
                );
    
                saveButton.disabled = false;
                saveButton.textContent = "Simpan Perubahan";
            }
        });
    }

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
            EDITABLE_STATUSES.includes(orderStatus) &&
            localOrder?.token
        ) {
            const editButton = createElement(
                "button",
                "buyer-order-edit",
                "Edit Pesanan"
            );
        
            editButton.type = "button";
        
            editButton.addEventListener("click", () => {
                createEditModal(order, localOrder.token);
            });
        
            orderActions.appendChild(editButton);
        }
        
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
    
    function setActiveCategory(category) {
        if (!ORDER_CATEGORIES[category]) return;
    
        activeCategory = category;
    
        elements.categoryTabs.forEach((tab) => {
            const isActive = tab.dataset.category === category;
    
            tab.classList.toggle("is-active", isActive);
            tab.setAttribute("aria-selected", String(isActive));
        });
    
        renderOrders();
    }

    function renderOrders() {
        if (!elements.orders) return;
    
        const filteredOrders = getOrdersByCategory(activeCategory);
    
        elements.orders.replaceChildren();
    
        filteredOrders.forEach((order, index) => {
            elements.orders.appendChild(
                createOrderCard(order, index)
            );
        });
    
        const hasOrders = filteredOrders.length > 0;
    
        elements.orders.hidden = !hasOrders;
    
        if (elements.empty) {
            elements.empty.hidden = hasOrders;
        }
    
        if (!hasOrders) {
            const emptyContent = ORDER_CATEGORIES[activeCategory];
    
            if (elements.emptyTitle) {
                elements.emptyTitle.textContent = emptyContent.title;
            }
    
            if (elements.emptyDescription) {
                elements.emptyDescription.textContent =
                    emptyContent.description;
            }
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
        
        elements.categoryTabs.forEach((tab) => {
            tab.addEventListener("click", () => {
                setActiveCategory(tab.dataset.category);
            });
        });

        loadOrders();
        startOrderRefresh();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init, { once: true });
    } else {
        init();
    }
})();
