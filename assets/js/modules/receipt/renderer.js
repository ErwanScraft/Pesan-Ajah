import {
  formatReceiptCurrency,
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

function formatReceiptDateLine(value) {
  if (!value) return "-";

  const date = new Date(String(value).replace(" ", "T"));

  if (Number.isNaN(date.getTime())) return "-";

  const parts = new Intl.DateTimeFormat("en-CA", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).formatToParts(date);

  const values = Object.fromEntries(
    parts.map((part) => [part.type, part.value]),
  );

  return `${values.year} - ${values.month} - ${values.day}`;
}

function formatReceiptTimeLine(value) {
  if (!value) return "-";

  const date = new Date(String(value).replace(" ", "T"));

  if (Number.isNaN(date.getTime())) return "-";

  return new Intl.DateTimeFormat("en-GB", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hourCycle: "h23",
  })
    .format(date)
    .replace(/:/g, ".");
}

function renderCashierInformation(container, data) {
  const section = createElement("div", "receipt-cashier-information");
  const date = new Date(String(data.createdAt).replace(" ", "T"));

  const dateText = Number.isNaN(date.getTime())
    ? "-"
    : new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      }).format(date).replace(/\//g, "-");

  const timeText = Number.isNaN(date.getTime())
    ? "-"
    : new Intl.DateTimeFormat("en-GB", {
        hour: "2-digit",
        minute: "2-digit",
        hourCycle: "h23",
      }).format(date);

  const dateRow = createElement(
    "div",
    "receipt-cashier-meta is-date",
  );

  dateRow.append(
    createElement("span", "", dateText),
    createElement("strong", "", timeText),
  );

  section.append(dateRow);

  if (data.cashierName) {
    const cashierRow = createElement("div", "receipt-cashier-meta");

    cashierRow.append(
      createElement("span", "", "Kasir"),
      createElement("strong", "", data.cashierName),
    );

    section.append(cashierRow);
  }

  const paymentLabels = {
    cash: "Tunai",
    qris: "QRIS",
    transfer: "Transfer",
  };

  const methodRow = createElement("div", "receipt-cashier-meta");

  methodRow.append(
    createElement("span", "", "Metode"),
    createElement(
      "strong",
      "",
      paymentLabels[data.payment.method] || data.payment.method || "-",
    ),
  );

  section.append(methodRow);
  container.append(section);
}

function renderOrderInformation(container, data) {
  const order = data.order;
  const section = createElement("div", "receipt-information");
  const deliveryDate = order.delivery_accepted_at || data.createdAt;

  const dateRow = createElement("div", "receipt-delivery-meta");

  dateRow.append(
    createElement(
      "strong",
      "",
      formatReceiptDateLine(deliveryDate),
    ),
    createElement(
      "strong",
      "",
      String(order.delivery_method || "").toLowerCase() === "pickup"
        ? "Ambil Sendiri"
        : "Delivery",
    ),
  );

  const driverRow = createElement("div", "receipt-delivery-meta");

  driverRow.append(
    createElement(
      "span",
      "",
      formatReceiptTimeLine(deliveryDate),
    ),
    createElement(
      "span",
      "",
      order.driver_name || "-",
    ),
  );

  section.append(
    dateRow,
    driverRow,
    createDivider(),
  );

  if (order.buyer_name) {
    addSummary(section, "Customer", order.buyer_name);
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

  container.append(section);
}

function renderItems(container, items) {
  const list = createElement("div", "receipt-items");

  items.forEach((item, index) => {
    const row = createElement("div", "receipt-item");

    const information = createElement(
      "div",
      "receipt-item-information",
    );

    information.append(
      createElement(
        "strong",
        "receipt-item-name",
        `${index + 1}. ${item.name}`,
      ),
      createElement(
        "small",
        "",
        `${item.quantity} x ${formatReceiptCurrency(item.price)}`,
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

  if (data.type === "order") {
    const order = data.order;

    addSummary(
      summary,
      "Sub Total",
      formatReceiptCurrency(order.subtotal),
    );

    addSummary(
      summary,
      "Ongkir",
      formatReceiptCurrency(order.delivery_fee),
    );

    addSummary(
      summary,
      "Total",
      formatReceiptCurrency(data.total),
      "is-total",
    );
  } else {
    const subtotal = data.items.reduce(
      (sum, item) => sum + item.subtotal,
      0,
    );

    addSummary(
      summary,
      "Sub Total",
      formatReceiptCurrency(subtotal),
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

    summary.append(createDivider());

    addSummary(
      summary,
      "Total",
      formatReceiptCurrency(data.total),
      "is-total",
    );
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
      "Belanja Jauh lebih Simpel",
    ),

    createElement(
      "small",
      "",
      "www.pesanajah.web.id",
    ),

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
  
  if (data.type === "order") {
    const orderNumber = data.order.number || data.order.order_number;
  
    if (orderNumber) {
      container.append(
        createElement(
          "strong",
          "receipt-order-number",
          orderNumber,
        ),
      );
    }
  
    container.append(createDivider());
  
    renderOrderInformation(container, data);
  
    container.append(createDivider());
  } else {
    container.append(createDivider());

    renderCashierInformation(container, data);

    container.append(createDivider());

    container.append(
      createElement("strong", "receipt-section-title", "Item"),
    );
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

