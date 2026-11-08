import {
  formatReceiptCurrency,
  formatReceiptDate,
  normalizeReceiptData,
} from "./formatter.js";

function createElement(tag, className = "", text = "") {
  const element = document.createElement(tag);

  if (className) {
    element.className = className;
  }

  if (text !== "" && text !== null && text !== undefined) {
    element.textContent = String(text);
  }

  return element;
}

function createDivider() {
  return createElement("div", "receipt-divider");
}

function addSummary(container, label, value, className = "") {
  const row = createElement(
    "div",
    `receipt-summary-row ${className}`.trim(),
  );

  row.append(
    createElement("span", "", label),
    createElement("strong", "", value),
  );

  container.append(row);
}

function renderStore(container, data, resolveImageUrl) {
  const brand = createElement("div", "receipt-brand");

  if (data.store.logo) {
    const logo = document.createElement("img");

    logo.src = resolveImageUrl
      ? resolveImageUrl(data.store.logo)
      : data.store.logo;

    logo.alt = `Logo ${data.store.name}`;
    logo.loading = "eager";

    logo.onerror = () => logo.remove();

    brand.append(logo);
  }

  brand.append(
    createElement("strong", "receipt-store-name", data.store.name),
  );

  if (data.store.address) {
    brand.append(
      createElement("p", "receipt-store-contact", data.store.address),
    );
  }

  if (data.store.phone) {
    brand.append(
      createElement(
        "p",
        "receipt-store-contact",
        `WhatsApp: ${data.store.phone}`,
      ),
    );
  }

  container.append(brand);
}

function renderOrderInformation(container, data) {
  const order = data.order;

  const section = createElement("div", "receipt-information");

  const heading = createElement(
    "strong",
    "receipt-section-title",
    data.type === "cashier"
      ? "INFORMASI TRANSAKSI"
      : "INFORMASI PESANAN",
  );

  section.append(heading);

  if (order.number || order.order_number) {
    addSummary(
      section,
      "Nomor Pesanan",
      order.number || order.order_number,
    );
  }

  if (order.buyer_name) {
    addSummary(section, "Nama Pembeli", order.buyer_name);
  }

  if (order.buyer_phone) {
    addSummary(section, "WhatsApp", order.buyer_phone);
  }

  if (order.delivery_method) {
    addSummary(
      section,
      "Metode Pengiriman",
      String(order.delivery_method).toLowerCase() === "pickup"
        ? "Ambil Sendiri"
        : "Delivery",
    );
  }

  if (order.delivery_address) {
    addSummary(section, "Alamat", order.delivery_address);
  }

  if (order.buyer_note || order.note) {
    addSummary(
      section,
      "Catatan",
      order.buyer_note || order.note,
    );
  }

  if (order.payment_method) {
    addSummary(
      section,
      "Metode Pembayaran",
      order.payment_method,
    );
  }

  container.append(section);
}

function renderItems(container, items) {
  const list = createElement("div", "receipt-items");

  items.forEach((item) => {
    const row = createElement("div", "receipt-item");

    const information = createElement(
      "div",
      "receipt-item-information",
    );

    const name = item.variant
      ? `${item.name} (${item.variant})`
      : item.name;

    information.append(
      createElement("strong", "", name),
      createElement(
        "small",
        "",
        `${item.quantity} × ${formatReceiptCurrency(item.price)}`,
      ),
    );

    row.append(
      information,
      createElement(
        "strong",
        "receipt-item-subtotal",
        formatReceiptCurrency(item.subtotal),
      ),
    );

    list.append(row);
  });

  container.append(list);
}

function renderPayment(container, data) {
  const summary = createElement("div", "receipt-summary");

  addSummary(
    summary,
    "Jumlah Harga Pesanan",
    formatReceiptCurrency(data.total),
    "is-total",
  );

  if (data.type === "cashier") {
    const labels = {
      cash: "Tunai",
      qris: "QRIS",
      transfer: "Transfer",
    };

    addSummary(
      summary,
      "Metode Pembayaran",
      labels[data.payment.method] || data.payment.method || "-",
    );

    if (data.payment.method === "cash") {
      addSummary(
        summary,
        "Uang Buyer",
        formatReceiptCurrency(data.payment.received),
      );

      addSummary(
        summary,
        "Kembalian",
        formatReceiptCurrency(data.payment.change),
        "is-change",
      );
    }
  }

  container.append(summary);
}

function renderFooter(container) {
  const footer = createElement("div", "receipt-footer");

  footer.append(
    createElement("strong", "", "PESAN AJAH"),
    createElement(
      "small",
      "",
      "Terima kasih telah berbelanja",
    ),
  );

  container.append(footer);
}

export function renderReceipt(
  container,
  input,
  options = {},
) {
  if (!container) {
    throw new Error("Container struk tidak ditemukan.");
  }

  const data = normalizeReceiptData(input);

  if (!data.items.length) {
    throw new Error("Struk tidak memiliki item pesanan.");
  }

  container.replaceChildren();

  container.className = "receipt-document";
  container.dataset.receiptType = data.type;

  renderStore(container, data, options.resolveImageUrl);

  container.append(createDivider());

  container.append(
    createElement(
      "strong",
      "receipt-title",
      "NOTA PESANAN",
    ),
    createElement(
      "small",
      "receipt-date",
      formatReceiptDate(data.createdAt),
    ),
  );

  container.append(createDivider());

  if (data.type === "order") {
    renderOrderInformation(container, data);
    container.append(createDivider());
  }

  renderItems(container, data.items);

  container.append(createDivider());

  renderPayment(container, data);

  container.append(createDivider());

  renderFooter(container);

  return data;
}

function renderReceiptItems(container, items, formatCurrency) {
  const fragment = document.createDocumentFragment();

  items.forEach((item) => {
    const row = createElement("div", "receipt-item");

    const information = createElement(
      "div",
      "receipt-item-information",
    );

    information.append(
      createElement("strong", "", item.name),
    );

    if (item.variant) {
      information.append(
        createElement("small", "", item.variant),
      );
    }

    information.append(
      createElement(
        "small",
        "",
        `${item.quantity} × ${formatCurrency(item.price)}`,
      ),
    );

    const subtotal = createElement(
      "strong",
      "receipt-item-subtotal",
      formatCurrency(item.subtotal),
    );

    row.append(information, subtotal);
    fragment.append(row);
  });

  container.replaceChildren(fragment);
}

