const $ = (selector) => document.querySelector(selector);

const storageKey = "pesan_ajah_order_tokens";

const messageElement = $("#buyer-message");
const ordersElement = $("#buyer-orders");
const emptyElement = $("#buyer-empty");

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

  const date = new Date(value.replace(" ", "T"));

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

  return labels[status] || status;
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

function makeElement(tag, className, text) {
  const element = document.createElement(tag);

  if (className) {
    element.className = className;
  }

  if (text !== undefined) {
    element.textContent = String(text);
  }

  return element;
}

function makeLink(href, text, className = "") {
  const link = document.createElement("a");

  link.href = href;
  link.textContent = text;
  link.className = className;

  link.target = "_blank";
  link.rel = "noopener noreferrer";

  return link;
}

function validMapsUrl(value) {
  if (!value) {
    return false;
  }

  try {
    const url = new URL(value);
    const host = url.hostname.toLowerCase();

    const allowedHosts = [
      "google.com",
      "www.google.com",
      "maps.google.com",
      "maps.app.goo.gl",
      "goo.gl",
    ];

    return (
      url.protocol === "https:" &&
      (
        allowedHosts.includes(host) ||
        host.endsWith(".google.com")
      )
    );
  } catch {
    return false;
  }
}

function createOrderCard(order) {
  const card = makeElement("article", "buyer-order-card");

  const header = makeElement("div", "buyer-order-header");

  const heading = makeElement("div", "buyer-order-heading");

  heading.append(
    makeElement("span", "buyer-order-label", "Nomor pesanan"),
    makeElement("h2", "buyer-order-number", order.order_number),
    makeElement("p", "buyer-order-store", order.store_name),
  );

  const status = makeElement(
    "span",
    `buyer-status buyer-status-${statusClass(order.status)}`,
    statusLabel(order.status),
  );

  header.append(heading, status);

  const details = makeElement("div", "buyer-order-details");

  const addDetail = (label, value) => {
    const row = makeElement("div", "buyer-detail-row");

    row.append(
      makeElement("span", "buyer-detail-label", label),
      makeElement("strong", "buyer-detail-value", value),
    );

    details.append(row);
  };

  addDetail(
    "Tanggal",
    formatDate(order.created_at),
  );

  addDetail(
    "Metode",
    order.delivery_method === "delivery"
      ? "Delivery"
      : "Ambil sendiri",
  );

  addDetail(
    "Subtotal",
    formatRupiah(order.subtotal),
  );

  addDetail(
    "Ongkir",
    formatRupiah(order.delivery_fee),
  );

  addDetail(
    "Total",
    formatRupiah(order.total),
  );

  const itemSection = makeElement("div", "buyer-order-items");

  itemSection.append(
    makeElement("h3", "", "Rincian produk"),
  );

  const itemList = makeElement("ul", "buyer-item-list");

  for (const item of order.items || []) {
    const row = makeElement("li", "buyer-item");

    row.append(
      makeElement(
        "span",
        "buyer-item-name",
        `${item.product_name} × ${item.quantity}`,
      ),
      makeElement(
        "strong",
        "buyer-item-total",
        formatRupiah(item.subtotal),
      ),
    );

    itemList.append(row);
  }

  itemSection.append(itemList);

  card.append(header, details, itemSection);

  if (order.delivery_method === "delivery") {
    const addressSection = makeElement(
      "div",
      "buyer-address-section",
    );

    addressSection.append(
      makeElement("h3", "", "Alamat pengantaran"),
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
        makeLink(
          mapsUrl,
          "Buka lokasi di Google Maps",
          "button button-outline buyer-map-button",
        ),
      );
    }

    card.append(addressSection);
  }

  const footer = makeElement("div", "buyer-order-footer");

  footer.append(
    makeElement(
      "span",
      "buyer-order-updated",
      `Pembaruan terakhir: ${formatDate(order.updated_at)}`,
    ),
  );

  card.append(footer);

  return card;
}

async function loadOrders() {
  let savedOrders = [];

  try {
    const parsed = JSON.parse(
      localStorage.getItem(storageKey) || "[]",
    );

    if (Array.isArray(parsed)) {
      savedOrders = parsed;
    }
  } catch {
    savedOrders = [];
  }

  const tokens = savedOrders
    .map((item) => item?.token)
    .filter(
      (token) =>
        typeof token === "string" &&
        /^[a-f0-9]{64}$/i.test(token),
    )
    .slice(0, 50);

  if (tokens.length === 0) {
    messageElement.hidden = true;
    ordersElement.replaceChildren();
    emptyElement.hidden = false;
    return;
  }

  messageElement.hidden = false;
  messageElement.textContent = "Memuat pesanan...";

  try {
    const response = await fetch("/user/api/orders.php", {
      method: "POST",
      credentials: "same-origin",
      cache: "no-store",

      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },

      body: JSON.stringify({ tokens }),
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(
        result.message || "Pesanan gagal dimuat.",
      );
    }

    const orders = Array.isArray(result.orders)
      ? result.orders
      : [];

    ordersElement.replaceChildren();

    if (orders.length === 0) {
      messageElement.hidden = true;
      emptyElement.hidden = false;
      return;
    }

    emptyElement.hidden = true;
    messageElement.hidden = true;

    for (const order of orders) {
      ordersElement.append(createOrderCard(order));
    }
  } catch (error) {
    messageElement.hidden = false;

    messageElement.textContent =
      error instanceof Error
        ? error.message
        : "Terjadi kesalahan saat memuat pesanan.";
  }
}

const currentYear = $("#buyer-current-year");

if (currentYear) {
  currentYear.textContent = String(
    new Date().getFullYear(),
  );
}

loadOrders();