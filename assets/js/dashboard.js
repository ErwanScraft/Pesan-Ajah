import { resolveImageUrl, setImageSource } from "./modules/image.js";
import {
  initErrorLogger,
  captureError,
} from "./core/error-logger.js";

import {
  showToast,
  confirmAction,
} from "./modules/toast.js";

initErrorLogger();

const $ = (selector) => document.querySelector(selector);

const page = document.body.dataset.page;
const csrfToken = document.body.dataset.csrfToken;

const API = {
  dashboard: "/warung/api/dashboard.php",
  products: "/warung/api/products.php",
  orders: "/warung/api/orders.php",
  settings: "/warung/api/settings.php",
  push: "/warung/api/push-subscription.php",
};

async function apiRequest(url, options = {}) {
  const response = await fetch(url, {
    credentials: "same-origin",
    cache: "no-store",
    ...options,
    headers: {
      Accept: "application/json",
      ...(options.body
        ? { "Content-Type": "application/json" }
        : {}),
      ...(options.method && options.method !== "GET"
        ? { "X-CSRF-Token": csrfToken }
        : {}),
      ...options.headers,
    },
  });

  let data;

  try {
    data = await response.json();
  } catch {
    throw new Error("Respons server tidak valid.");
  }

  if (!response.ok || data.success === false) {
    if (response.status === 401) {
      window.location.href = "/warung/login.php";
    }

    throw new Error(
      data.message || "Permintaan gagal diproses.",
    );
  }

  return data;
}

async function postJSON(url, body, method = "POST") {
  return apiRequest(url, {
    method,
    body: JSON.stringify(body),
  });
}

function urlBase64ToUint8Array(base64String) {
  const padding = "=".repeat((4 - (base64String.length % 4)) % 4);

  const base64 = (base64String + padding)
    .replace(/-/g, "+")
    .replace(/_/g, "/");

  const rawData = window.atob(base64);

  return Uint8Array.from(
    [...rawData].map((char) => char.charCodeAt(0)),
  );
}

async function initializePushNotifications() {
  const button = $("#push-enable-button");

  if (!button) {
    return;
  }

  const supported =
    "serviceWorker" in navigator &&
    "PushManager" in window &&
    "Notification" in window;

  if (!supported) {
    button.disabled = true;
    button.textContent = "Notifikasi tidak didukung";
    return;
  }

  if (Notification.permission === "denied") {
    button.disabled = true;
    button.textContent = "Izin notifikasi diblokir";
    return;
  }

  // Pasang event klik langsung, tanpa menunggu Service Worker.
  button.addEventListener("click", async () => {
    const originalText = button.textContent;

    button.disabled = true;
    button.textContent = "Mengaktifkan...";

    try {
      const permission =
        Notification.permission === "granted"
          ? "granted"
          : await Notification.requestPermission();

      if (permission !== "granted") {
        throw new Error(
          "Izin notifikasi belum diberikan.",
        );
      }

      const registration =
        await navigator.serviceWorker.register(
          "/service-worker.js",
          { scope: "/" },
        );

      await navigator.serviceWorker.ready;

      const keyResponse = await apiRequest(API.push);

      if (!keyResponse.public_key) {
        throw new Error(
          "Kunci notifikasi tidak tersedia.",
        );
      }

      let subscription =
        await registration.pushManager.getSubscription();

      if (!subscription) {
        subscription =
          await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(
              keyResponse.public_key,
            ),
          });
      }

      const subscriptionData = subscription.toJSON();

      await postJSON(API.push, {
        action: "subscribe",
        endpoint: subscriptionData.endpoint,
        keys: subscriptionData.keys,
        contentEncoding: "aes128gcm",
      });

      button.textContent = "Notifikasi aktif";

      notify(
        "Notifikasi pesanan berhasil diaktifkan.",
        "success",
      );
    } catch (error) {
      console.error(
        "[Pesan Ajah] Aktivasi push gagal:",
        error,
      );

      notify(
        error instanceof Error
          ? error.message
          : "Notifikasi gagal diaktifkan.",
        "error",
      );

      button.textContent = originalText;
      button.disabled = false;
    }
  });

  // Sinkronkan subscription lama jika izin sudah diberikan.
  if (Notification.permission === "granted") {
    try {
      const registration =
        await navigator.serviceWorker.register(
          "/service-worker.js",
          { scope: "/" },
        );

      const subscription =
        await registration.pushManager.getSubscription();

      if (subscription) {
        const subscriptionData = subscription.toJSON();

        await postJSON(API.push, {
          action: "subscribe",
          endpoint: subscriptionData.endpoint,
          keys: subscriptionData.keys,
          contentEncoding: "aes128gcm",
        });

        button.textContent = "Notifikasi aktif";
      }
    } catch (error) {
      console.error(
        "[Pesan Ajah] Sinkronisasi subscription gagal:",
        error,
      );
    }
  }
}

function notify(message, type = "info") {
  showToast($("#toast"), message, 3500, type);
}

function formatRupiah(value) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number(value) || 0);
}

function createCell(value, className = "") {
  const cell = document.createElement("td");

  cell.textContent = String(value ?? "");

  if (className) {
    cell.className = className;
  }

  return cell;
}

function createButton(text, callback, className = "table-action") {
  const button = document.createElement("button");

  button.type = "button";
  button.className = className;
  button.textContent = text;
  button.addEventListener("click", callback);

  return button;
}

function createIconButton(
  label,
  icon,
  callback,
  className = "table-action",
) {
  const button = document.createElement("button");

  button.type = "button";
  button.className = `table-action icon-action ${className}`;
  button.setAttribute("aria-label", label);
  button.title = label;

  const icons = {
    edit: `
      <svg viewBox="0 0 24 24" aria-hidden="true"
        focusable="false">
        <path d="M12 20h9"
          fill="none" stroke="currentColor"
          stroke-width="1.8" stroke-linecap="round"/>
        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L9 17l-4 1 1-4Z"
          fill="none" stroke="currentColor"
          stroke-width="1.8" stroke-linecap="round"
          stroke-linejoin="round"/>
      </svg>
    `,
    delete: `
      <svg viewBox="0 0 24 24" aria-hidden="true"
        focusable="false">
        <path d="M3 6h18M8 6V4h8v2m-11 0 1 14h12l1-14"
          fill="none" stroke="currentColor"
          stroke-width="1.8" stroke-linecap="round"
          stroke-linejoin="round"/>
        <path d="M10 10v6m4-6v6"
          fill="none" stroke="currentColor"
          stroke-width="1.8" stroke-linecap="round"/>
      </svg>
    `,
  };

  button.innerHTML = icons[icon] || "";
  button.addEventListener("click", callback);

  return button;
}

/* =========================================================
   DASHBOARD STATISTIK
========================================================= */

async function loadDashboard() {
  const data = await apiRequest(API.dashboard);
  const stats = data.stats;

  const orders = $("#stat-orders");
  const pending = $("#stat-pending");
  const products = $("#stat-products");
  const status = $("#store-status-label");

  if (orders) {
    orders.textContent = String(stats.total_orders);
  }

  if (pending) {
    pending.textContent = String(stats.pending_orders);
  }

  if (products) {
    products.textContent = String(stats.available_products);
  }

  if (status) {
    status.textContent = stats.is_open ? "Buka" : "Tutup";
  }

  const revenue = $("#stat-revenue");

  if (revenue) {
    revenue.textContent = formatRupiah(
      stats.completed_revenue,
    );
  }

  const recent = $("#recent-orders");

  if (recent) {
    recent.replaceChildren();

    for (const order of data.recent_orders) {
      const row = document.createElement("tr");

      row.append(
        createCell(order.order_number, "order-id"),
        createCell(order.buyer_name),
        createCell(formatRupiah(order.total)),
        createCell(order.status),
      );

      recent.append(row);
    }
  }
}

/* =========================================================
   PESANAN
========================================================= */

let orders = [];
let orderFilter = "all";

async function loadOrders() {
  const refreshButton = $("#orders-refresh");

  if (refreshButton) {
    refreshButton.disabled = true;
  }

  try {
    const data = await apiRequest(API.orders);

    orders = Array.isArray(data.orders)
      ? data.orders
      : [];

    renderOrders();
  } catch (error) {
    notify(
      error instanceof Error
        ? error.message
        : "Pesanan gagal dimuat.",
    );
  } finally {
    if (refreshButton) {
      refreshButton.disabled = false;
    }
  }
}


function nextOrderStatus(order) {
  const status = String(order.status || "").toLowerCase();
  const method = String(order.delivery_method || "").toLowerCase();

  if (status === "pending") {
    return {
      value: "confirmed",
      label: "Konfirmasi",
    };
  }

  if (status === "confirmed") {
    return {
      value: "processing",
      label: "Mulai proses",
    };
  }

  if (status === "processing") {
    if (method === "pickup") {
      return {
        value: "ready_for_pickup",
        label: "Siap diambil",
      };
    }

    return {
      value: "ready_for_driver",
      label: "Siapkan untuk driver",
    };
  }

  return null;
}

function orderStatusLabel(status) {
  const labels = {
    pending: "Menunggu",
    confirmed: "Dikonfirmasi",
    processing: "Diproses",
    ready_for_pickup: "Siap diambil",
    ready_for_driver: "Menunggu driver",
    in_delivery: "Diantar",
    completed: "Selesai",
    cancelled: "Dibatalkan",
  };

  return labels[String(status || "").toLowerCase()]
    || "Status tidak diketahui";
}

function orderMethodLabel(method) {
  return String(method || "").toLowerCase() === "pickup"
    ? "Ambil Sendiri"
    : "Delivery";
}

function formatOrderDate(value) {
  if (!value) {
    return "Waktu tidak tersedia";
  }

  const date = new Date(String(value).replace(" ", "T"));

  if (Number.isNaN(date.getTime())) {
    return String(value);
  }

  return new Intl.DateTimeFormat("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}

function createOrderSummaryCard(label, value, iconName, colorClass) {
  const card = document.createElement("article");
  card.className = "order-summary-card";

  const heading = document.createElement("div");
  heading.className = "order-summary-heading";

  const title = document.createElement("span");
  title.textContent = label;

  const icon = document.createElement("span");
  icon.className = `order-summary-icon ${colorClass}`;
  icon.setAttribute("aria-hidden", "true");
  icon.textContent = iconName;

  const number = document.createElement("strong");
  number.textContent = String(value);

  heading.append(title, icon);
  card.append(heading, number);

  return card;
}

function renderOrderSummary() {
  const target = $("#orders-summary");

  if (!target) {
    return;
  }

  target.replaceChildren();

  const getStatus = (order) =>
    String(order.status || "").toLowerCase();

  const getMethod = (order) =>
    String(order.delivery_method || "").toLowerCase();

  const waiting = orders.filter((order) =>
    getStatus(order) === "pending"
  ).length;

  const processing = orders.filter((order) =>
    ["confirmed", "processing"].includes(getStatus(order))
  ).length;

  const pickup = orders.filter((order) =>
    getMethod(order) === "pickup"
    && getStatus(order) === "ready_for_pickup"
  ).length;

  const delivery = orders.filter((order) =>
    getMethod(order) !== "pickup"
    && getStatus(order) === "in_delivery"
  ).length;

  target.append(
    createOrderSummaryCard(
      "Menunggu",
      waiting,
      "◷",
      "amber",
    ),
    createOrderSummaryCard(
      "Diproses",
      processing,
      "↻",
      "blue",
    ),
    createOrderSummaryCard(
      "Siap diambil",
      pickup,
      "✓",
      "green",
    ),
    createOrderSummaryCard(
      "Diantar",
      delivery,
      "↗",
      "purple",
    ),
  );
}

function filterOrders() {
  return orders.filter((order) => {
    const status = String(order.status || "").toLowerCase();
    const method = String(order.delivery_method || "").toLowerCase();

    switch (orderFilter) {
      case "waiting":
        return status === "pending";

      case "processing":
        return ["confirmed", "processing"].includes(status);

      case "pickup":
        return method === "pickup"
          && status === "ready_for_pickup";

      case "delivery":
        return method !== "pickup"
          && status === "in_delivery";

      case "completed":
        return ["completed", "cancelled"].includes(status);

      case "all":
      default:
        return true;
    }
  });
}

function renderOrders() {
  const list = $("#orders-list");

  if (!list) {
    return;
  }

  renderOrderSummary();
  list.replaceChildren();

  const visibleOrders = filterOrders();
  const count = $("#orders-count");

  if (count) {
    count.textContent = String(visibleOrders.length);
  }

  if (visibleOrders.length === 0) {
    const empty = document.createElement("div");
    empty.className = "orders-empty";

    const title = document.createElement("strong");
    title.textContent = orders.length === 0
      ? "Belum ada pesanan"
      : "Pesanan tidak ditemukan";

    const detail = document.createElement("p");
    detail.textContent = orders.length === 0
      ? "Pesanan baru akan muncul di sini."
      : "Coba pilih filter status yang lain.";

    empty.append(title, detail);
    list.append(empty);

    return;
  }

  for (const order of visibleOrders) {
    const card = document.createElement("article");
    card.className = "order-card";
    card.dataset.orderId = String(order.id);

    const header = document.createElement("div");
    header.className = "order-card-header";

    const orderTitle = document.createElement("h3");
    orderTitle.textContent =
      `Pesanan #${order.order_number || order.id}`;

    const status = document.createElement("span");
    const statusCode = String(order.status || "").toLowerCase();

    status.className = `status-pill status-${statusCode}`;
    status.textContent = orderStatusLabel(statusCode);

    header.append(orderTitle, status);

    const buyer = document.createElement("p");
    buyer.className = "order-card-buyer";
    buyer.textContent =
      `${order.buyer_name || "Pembeli"} · ${formatOrderDate(order.created_at)}`;

    const summary = document.createElement("div");
    summary.className = "order-card-summary";

    const method = document.createElement("span");
    method.className = "order-method";
    method.textContent = orderMethodLabel(order.delivery_method);

    const total = document.createElement("strong");
    total.className = "order-card-total";
    total.textContent = formatRupiah(order.total);

    summary.append(method, total);

    /* Detail pesanan */

    const details = document.createElement("div");
    details.className = "order-card-details";
    details.hidden = true;

    const itemsTitle = document.createElement("strong");
    itemsTitle.textContent = "Rincian produk";

    const itemList = document.createElement("ul");
    itemList.className = "order-item-list";

    for (const item of (order.items || [])) {
      const row = document.createElement("li");

      const itemName = document.createElement("span");
      const variant = item.product_variant
        ? ` (${item.product_variant})`
        : "";

      itemName.textContent =
        `${item.product_name || "Produk"}${variant} × ${item.quantity}`;

      const itemPrice = document.createElement("span");

      const subtotal = item.subtotal
        ?? (Number(item.price || 0) * Number(item.quantity || 0));

      itemPrice.textContent = formatRupiah(subtotal);

      row.append(itemName, itemPrice);
      itemList.append(row);
    }

    details.append(itemsTitle, itemList);

    const meta = document.createElement("div");
    meta.className = "order-detail-meta";

    function addMeta(label, value) {
      if (
        value === null
        || value === undefined
        || String(value).trim() === ""
      ) {
        return;
      }

      const line = document.createElement("p");
      const strong = document.createElement("strong");

      strong.textContent = `${label}: `;

      line.append(
        strong,
        document.createTextNode(String(value)),
      );

      meta.append(line);
    }

    addMeta("Nomor pembeli", order.buyer_phone);

    if (String(order.delivery_method || "").toLowerCase() !== "pickup") {
      addMeta("Alamat", order.delivery_address);

      if (order.delivery_fee !== null && order.delivery_fee !== undefined) {
        addMeta("Ongkir", formatRupiah(order.delivery_fee));
      }
    }

    addMeta("Catatan", order.buyer_note);
    details.append(meta);

    /* Tombol aksi */

    const footer = document.createElement("div");
    footer.className = "order-card-actions";

    const detailButton = createButton(
      "Lihat detail pesanan",
      () => {
        details.hidden = !details.hidden;

        detailButton.textContent = details.hidden
          ? "Lihat detail pesanan"
          : "Tutup detail";

        detailButton.setAttribute(
          "aria-expanded",
          String(!details.hidden),
        );
      },
      "button button-outline order-detail-button",
    );

    detailButton.setAttribute("aria-expanded", "false");
    footer.append(detailButton);

    const next = nextOrderStatus(order);
    
    if (next) {
      const actionButton = createButton(
        next.label,
        async () => {
          await changeOrderStatus(order, next.value);
        },
        "button button-primary order-next-button",
      );
    
      footer.append(actionButton);
    }

    const currentStatus = String(order.status || "").toLowerCase();
    
    if (["pending", "confirmed"].includes(currentStatus)) {
      const cancelButton = createButton(
        "Batalkan",
        async () => {
          await cancelOrder(order);
        },
        "button button-danger order-cancel-button",
      );
    
      footer.append(cancelButton);
    }
    
    const orderStatus = String(order.status || "").toLowerCase();
    
    if (["completed", "cancelled"].includes(orderStatus)) {
      const deleteButton = createButton(
        "Hapus riwayat",
        async () => {
          await deleteOrderHistory(order);
        },
        "button button-danger order-delete-button",
      );
    
      footer.append(deleteButton);
    }

    card.append(
      header,
      buyer,
      summary,
      details,
      footer,
    );

    list.append(card);
  }
}

async function changeOrderStatus(order, status) {
  const labels = {
    confirmed: "mengonfirmasi",
    processing: "memproses",
    ready_for_pickup: "menandai pesanan siap diambil",
    ready_for_driver: "menyiapkan pesanan untuk driver",
  };

  const action = labels[status];

  if (!action) {
    notify("Aksi status tidak valid.");
    return;
  }

  const confirmed = await confirmAction({
    title: "Konfirmasi pesanan",
    message: `Yakin ingin ${action} pesanan ${order.order_number || order.id}?`,
    confirmText: "Ya, lanjutkan",
    cancelText: "Batal",
  });

  if (!confirmed) {
    return;
  }

  try {
    await postJSON(API.orders, {
      id: order.id,
      status,
    });

    await Promise.all([
      loadOrders(),
      loadDashboard(),
    ]);

    notify("Status pesanan berhasil diperbarui.");
  } catch (error) {
    notify(
      error instanceof Error
        ? error.message
        : "Status pesanan gagal diperbarui.",
    );
  }
}

async function cancelOrder(order) {
  const status = String(order.status || "").toLowerCase();

  if (!["pending", "confirmed"].includes(status)) {
    notify("Pesanan tidak dapat dibatalkan pada tahap ini.");
    return;
  }

  const orderLabel = order.order_number || order.id;
  
  const confirmed = await confirmAction({
    title: "Batalkan pesanan?",
    message: `Pesanan #${orderLabel} akan dibatalkan dan masuk ke riwayat.`,
    confirmText: "Ya, batalkan",
    cancelText: "Kembali",
    danger: true,
  });

  if (!confirmed) {
    return;
  }

  try {
    await postJSON(API.orders, {
      action: "cancel",
      id: order.id,
    });

    await Promise.all([
      loadOrders(),
      loadDashboard(),
    ]);

    notify("Pesanan berhasil dibatalkan.");
  } catch (error) {
    notify(
      error instanceof Error
        ? error.message
        : "Pesanan gagal dibatalkan.",
    );
  }
}


async function deleteOrderHistory(order) {
  const status = String(order.status || "").toLowerCase();

  if (!["completed", "cancelled"].includes(status)) {
    notify(
      "Hanya pesanan selesai atau dibatalkan yang dapat dihapus.",
    );
    return;
  }

  const orderLabel = order.order_number || order.id;

  const confirmed = await confirmAction({
    title: "Hapus riwayat pesanan?",
    message: `Riwayat pesanan #${orderLabel} akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`,
    confirmText: "Hapus riwayat",
    cancelText: "Batal",
    danger: true,
  });

  if (!confirmed) {
    return;
  }

  try {
    await postJSON(API.orders, {
      action: "delete",
      id: order.id,
    });

    await Promise.all([
      loadOrders(),
      loadDashboard(),
    ]);

    notify("Riwayat pesanan berhasil dihapus.");
  } catch (error) {
    notify(
      error instanceof Error
        ? error.message
        : "Riwayat pesanan gagal dihapus.",
    );
  }
}

/* Filter pesanan */

document.querySelectorAll("[data-order-filter]").forEach((button) => {
  button.addEventListener("click", () => {
    orderFilter = button.dataset.orderFilter || "all";

    document.querySelectorAll("[data-order-filter]").forEach((item) => {
      const active = item === button;

      item.classList.toggle("active", active);
      item.setAttribute("aria-pressed", String(active));
    });

    renderOrders();
  });
});

/* Muat ulang */

$("#orders-refresh")?.addEventListener(
  "click",
  loadOrders,
);

/* =========================================================
   NAVIGASI
========================================================= */

const sidebar = $("#sidebar");
const mobileMenu = $("#mobile-menu");

mobileMenu?.addEventListener("click", () => {
  const open = sidebar?.classList.toggle("open");

  mobileMenu.setAttribute(
    "aria-expanded",
    String(Boolean(open)),
  );
});

document.addEventListener("keydown", (event) => {
  if (event.key !== "Escape") {
    return;
  }

  if (productModal && !productModal.hidden) {
    closeProductModal();
    return;
  }

  sidebar?.classList.remove("open");
  mobileMenu?.setAttribute("aria-expanded", "false");
});

/* =========================================================
   INISIALISASI
========================================================= */

async function initializeDashboard() {
  try {
    if (page === "overview") {
      await loadDashboard();
    }

    if (page === "products") {
      const { initWarungProducts } = await import(
        "./pages/warung-produk/main.js"
      );
    
      await initWarungProducts({
        $,
        API,
        apiRequest,
        postJSON,
        notify,
        formatRupiah,
        createIconButton,
        resolveImageUrl,
        csrfToken,
      });
    }

    if (page === "orders") {
      await loadOrders();
    }

    if (page === "settings") {
      const { initWarungPengaturan } = await import(
        "./pages/warung-pengaturan/main.js"
      );
    
      await initWarungPengaturan({
        $,
        API,
        apiRequest,
        postJSON,
        notify,
        csrfToken,
        loadDashboard,
      });
    }

  } catch (error) {
    notify(error.message);
  }
}

initializePushNotifications();

initializeDashboard();