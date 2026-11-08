export function formatReceiptCurrency(value) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number(value) || 0);
}

export function formatReceiptDate(value) {
  if (!value) {
    return "-";
  }

  const date = new Date(String(value).replace(" ", "T"));

  if (Number.isNaN(date.getTime())) {
    return String(value);
  }

  return new Intl.DateTimeFormat("id-ID", {
    day: "2-digit",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}

export function normalizeReceiptItem(item) {
  const name = item.name || item.product_name || "Produk";
  const variant = item.variant || item.product_variant || "";
  const quantity = Number(item.quantity) || 0;
  const price = Number(item.price) || 0;

  return {
    name,
    variant,
    quantity,
    price,
    subtotal: Number(item.subtotal) || price * quantity,
  };
}

export function normalizeReceiptData(data) {
  return {
    type: data.type || "order",

    store: {
      name: data.store?.name || "Warung",
      logo: data.store?.logo || "",
      address: data.store?.address || "",
      phone: data.store?.phone || "",
    },

    order: data.order || {},

    items: (Array.isArray(data.items) ? data.items : [])
      .map(normalizeReceiptItem),

    total: Number(data.total) || 0,

    payment: {
      method: data.payment?.method || "",
      received: Number(data.payment?.received) || 0,
      change: Number(data.payment?.change) || 0,
    },

    createdAt: data.createdAt || data.created_at || "",
  };
}
