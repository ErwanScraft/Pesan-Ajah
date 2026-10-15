/* =========================================================
   PESAN AJAH — BUYER DASHBOARD
   File: assets/js/buyer-dashboard.js

   Fitur:
   - Menampilkan pesanan dari database
   - Menampilkan status terbaru
   - Ringkasan produk dan pembayaran
   - Hapus pesanan selesai dari riwayat browser
   - Mobile friendly
========================================================= */

const $ = (selector) => document.querySelector(selector);

const storageKey = "pesan_ajah_order_tokens";

const messageElement = $("#buyer-message");
const ordersElement = $("#buyer-orders");
const emptyElement = $("#buyer-empty");

let currentOrders = [];

/* =========================================================
   FORMAT
========================================================= */

function formatRupiah(value) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number(value) || 0);
}

function formatDate(value) {
  if (!value) {
    return "-";
  }

  const date = new Date(
    String(value).replace(" ", "T"),
  );

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date);
}

function statusLabel(status) {
  const labels = {
    pending: "Menunggu konfirmasi",
    confirmed: "Dikonfirmasi",
    processing: "Sedang diproses",
    completed: "Selesai",
    cancelled: "Dibatalkan",
  };

  return labels[status] || "Status tidak diketahui";
}

function statusClass(status) {
  const allowed = [
    "pending",
    "confirmed",
    "processing",
    "completed",
    "cancelled",
  ];

  return allowed.includes(status) ? status : "pending";
}

/* =========================================================
   ELEMENT
========================================================= */

function makeElement(tag, className = "", text = "") {
  const element = document.createElement(tag);

  if (className) {
    element.className = className;
  }

  if (text !== "") {
    element.textContent = String(text);
  }

  return element;
}

function validMapsUrl(value) {
  if (!value) {
    return false;
  }

  try {
    const url = new URL(value);
    const host = url.hostname.toLowerCase();

    return (
      url.protocol === "https:" &&
      (
        host === "google.com" ||
        host === "www.google.com" ||
        host === "maps.google.com" ||
        host === "maps.app.goo.gl" ||
        host === "goo.gl" ||
        host.endsWith(".google.com")
      )
    );
  } catch {
    return false;
  }
}

function makeMapsLink(url) {
  const link = makeElement(
    "a",
    "buyer-order-map",
    "Lihat lokasi",
  );

  link.href = url;
  link.target = "_blank";
  link.rel = "noopener noreferrer";

  return link;
}

/* =========================================================
   STATUS PESANAN
========================================================= */

function createStatus(statusValue) {
  return makeElement(
    "span",
    `buyer-status buyer-status-${statusClass(statusValue)}`,
    statusLabel(statusValue),
  );
}

/* =========================================================
   HAPUS RIWAYAT BUYER
========================================================= */

/*
 * Menghapus token dari browser pembeli.
 *
 * Data pesanan di database dan Dashboard Warung
 * tidak ikut dihapus.
 */

function removeBuyerOrder(order) {
  if (order.status !== "completed") {
    return;
  }

  const confirmed = window.confirm(
    `Hapus pesanan ${order.order_number} dari riwayat Dashboard Buyer?\n\n` +
    "Data pesanan di warung tetap tersimpan.",
  );

  if (!confirmed) {
    return;
  }

  try {
    const savedOrders = getSavedOrders();

    const updatedOrders = savedOrders.filter(
      (item) => item?.order_number !== order.order_number,
    );

    localStorage.setItem(
      storageKey,
      JSON.stringify(updatedOrders),
    );

    currentOrders = currentOrders.filter(
      (item) => item.order_number !== order.order_number,
    );

    renderOrders();

    showBuyerMessage(
      "Pesanan dihapus dari riwayat Dashboard Buyer.",
      "success",
    );
  } catch (error) {
    console.error(error);

    showBuyerMessage(
      "Riwayat gagal dihapus. Periksa penyimpanan browser.",
      "error",
    );
  }
}

/* =========================================================
   KARTU PESANAN
========================================================= */

function createOrderCard(order) {
  const card = makeElement(
    "article",
    "buyer-order-card",
  );

  /*
   * Header: warung, nomor pesanan, status.
   */

  const header = makeElement(
    "div",
    "buyer-order-header",
  );

  const identity = makeElement(
    "div",
    "buyer-order-heading",
  );

  identity.append(
    makeElement(
      "p",
      "buyer-order-store",
      order.store_name || "Warung",
    ),
    makeElement(
      "h2",
      "buyer-order-number",
      order.order_number,
    ),
    makeElement(
      "p",
      "buyer-order-date",
      formatDate(order.created_at),
    ),
  );

  header.append(
    identity,
    createStatus(order.status),
  );

  /*
   * Daftar produk.
   */

  const productSection = makeElement(
    "div",
    "buyer-order-items",
  );

  const productList = makeElement(
    "ul",
    "buyer-item-list",
  );

  const items = Array.isArray(order.items)
    ? order.items
    : [];

  for (const item of items) {
    const row = makeElement(
      "li",
      "buyer-item",
    );

    const name = makeElement(
      "span",
      "buyer-item-name",
      `${item.product_name} × ${item.quantity}`,
    );

    const subtotal = makeElement(
      "strong",
      "buyer-item-total",
      formatRupiah(item.subtotal),
    );

    row.append(name, subtotal);
    productList.append(row);
  }

  if (items.length === 0) {
    productList.append(
      makeElement(
        "li",
        "buyer-item-empty",
        "Rincian produk tidak tersedia.",
      ),
    );
  }

  productSection.append(
    makeElement(
      "h3",
      "buyer-section-title",
      "Produk pesanan",
    ),
    productList,
  );

  /*
   * Ringkasan pembayaran.
   */

  const payment = makeElement(
    "div",
    "buyer-order-payment",
  );

  const addPaymentRow = (
    label,
    value,
    className = "",
  ) => {
    const row = makeElement(
      "div",
      `buyer-payment-row ${className}`.trim(),
    );

    row.append(
      makeElement(
        "span",
        "buyer-payment-label",
        label,
      ),
      makeElement(
        "strong",
        "buyer-payment-value",
        value,
      ),
    );

    payment.append(row);
  };

  addPaymentRow(
    "Subtotal",
    formatRupiah(order.subtotal),
  );

  addPaymentRow(
    "Ongkir",
    formatRupiah(order.delivery_fee),
  );

  addPaymentRow(
    "Total pembayaran",
    formatRupiah(order.total),
    "buyer-payment-total",
  );

  /*
   * Informasi pengantaran.
   */

  let addressSection = null;

  if (order.delivery_method === "delivery") {
    addressSection = makeElement(
      "div",
      "buyer-order-address",
    );

    addressSection.append(
      makeElement(
        "span",
        "buyer-address-label",
        "Alamat pengantaran",
      ),
      makeElement(
        "p",
        "buyer-address",
        order.delivery_address || "Alamat tidak tersedia.",
      ),
    );

    let mapsUrl = order.delivery_maps_url || "";

    if (
      !mapsUrl &&
      order.delivery_latitude !== null &&
      order.delivery_longitude !== null
    ) {
      mapsUrl =
        "https://maps.google.com/?q=" +
        encodeURIComponent(
          `${order.delivery_latitude},${order.delivery_longitude}`,
        );
    }

    if (validMapsUrl(mapsUrl)) {
      addressSection.append(
        makeMapsLink(mapsUrl),
      );
    }
  }

  /*
   * Footer.
   */

  const footer = makeElement(
    "div",
    "buyer-order-footer",
  );

  const methodLabel =
    order.delivery_method === "delivery"
      ? "Diantar"
      : "Ambil sendiri";

  footer.append(
    makeElement(
      "span",
      "buyer-order-method",
      methodLabel,
    ),
    makeElement(
      "span",
      "buyer-order-updated",
      `Diperbarui ${formatDate(order.updated_at)}`,
    ),
  );

  /*
   * Tombol hapus hanya tersedia jika selesai.
   */

  if (order.status === "completed") {
    const actions = makeElement(
      "div",
      "buyer-order-actions",
    );

    const deleteButton = makeElement(
      "button",
      "buyer-order-delete",
      "Hapus dari riwayat",
    );

    deleteButton.type = "button";

    deleteButton.addEventListener("click", () => {
      removeBuyerOrder(order);
    });

    actions.append(deleteButton);
    footer.append(actions);
  }

  card.append(
    header,
    productSection,
    payment,
  );

  if (addressSection) {
    card.append(addressSection);
  }

  card.append(footer);

  return card;
}

/* =========================================================
   LOCAL STORAGE
========================================================= */

function getSavedOrders() {
  try {
    const parsed = JSON.parse(
      localStorage.getItem(storageKey) || "[]",
    );

    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

/* =========================================================
   FEEDBACK
========================================================= */

function showBuyerMessage(message, type = "") {
  if (!messageElement) {
    return;
  }

  messageElement.hidden = false;
  messageElement.textContent = message;

  messageElement.classList.remove(
    "is-success",
    "is-error",
  );

  if (type === "success") {
    messageElement.classList.add("is-success");
  }

  if (type === "error") {
    messageElement.classList.add("is-error");
  }
}

/* =========================================================
   RENDER
========================================================= */

function renderOrders() {
  if (!ordersElement || !emptyElement) {
    return;
  }

  ordersElement.replaceChildren();

  if (currentOrders.length === 0) {
    emptyElement.hidden = false;
    return;
  }

  emptyElement.hidden = true;

  for (const order of currentOrders) {
    ordersElement.append(
      createOrderCard(order),
    );
  }
}

/* =========================================================
   LOAD PESANAN DARI DATABASE
========================================================= */

async function loadOrders() {
  if (!messageElement || !ordersElement || !emptyElement) {
    return;
  }

  const savedOrders = getSavedOrders();

  const tokens = savedOrders
    .map((item) => item?.token)
    .filter(
      (token) =>
        typeof token === "string" &&
        /^[a-f0-9]{64}$/i.test(token),
    )
    .slice(0, 50);

  if (tokens.length === 0) {
    currentOrders = [];

    ordersElement.replaceChildren();
    messageElement.hidden = true;
    emptyElement.hidden = false;

    return;
  }

  showBuyerMessage("Memuat pesanan...");

  try {
    const response = await fetch(
      "/api/orders.php",
      {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",

        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },

        body: JSON.stringify({ tokens }),
      },
    );

    const contentType =
      response.headers.get("content-type") || "";

    const responseText = await response.text();

    if (!contentType.toLowerCase().includes("application/json")) {
      throw new Error(
        response.status === 404
          ? "API pesanan tidak ditemukan."
          : "Server tidak mengembalikan data pesanan yang valid.",
      );
    }

    let result;

    try {
      result = JSON.parse(responseText);
    } catch {
      throw new Error(
        "Data pesanan dari server tidak dapat dibaca.",
      );
    }

    if (!response.ok || !result.success) {
      throw new Error(
        result.message || "Pesanan gagal dimuat.",
      );
    }

    currentOrders = Array.isArray(result.orders)
      ? result.orders
      : [];

    messageElement.hidden = true;

    renderOrders();
  } catch (error) {
    console.error(
      "[Pesan Ajah] Dashboard Buyer:",
      error,
    );

    showBuyerMessage(
      error instanceof Error
        ? error.message
        : "Terjadi kesalahan saat memuat pesanan.",
      "error",
    );
  }
}

/* =========================================================
   TAHUN FOOTER
========================================================= */

const currentYear = $("#buyer-current-year");

if (currentYear) {
  currentYear.textContent = String(
    new Date().getFullYear(),
  );
}

/* =========================================================
   INIT
========================================================= */

loadOrders();