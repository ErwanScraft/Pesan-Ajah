const $ = (selector) => document.querySelector(selector);

const page = document.body.dataset.page;
const csrfToken = document.body.dataset.csrfToken;

const API = {
  dashboard: "/warung/api/dashboard.php",
  products: "/warung/api/products.php",
  orders: "/warung/api/orders.php",
  settings: "/warung/api/settings.php",
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

function notify(message) {
  const toast = $("#toast");

  if (!toast) {
    window.alert(message);
    return;
  }

  toast.textContent = message;
  toast.classList.add("show");

  window.clearTimeout(notify.timeout);

  notify.timeout = window.setTimeout(() => {
    toast.classList.remove("show");
  }, 3000);
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
   PRODUK
========================================================= */

let products = [];
let editingProductId = null;

const productForm = $("#product-form");
const productList = $("#manage-products");

async function loadProducts() {
  const data = await apiRequest(API.products);

  products = data.products.map((product) => ({
    ...product,
    id: Number(product.id),
    price: Number(product.price),
    available: Boolean(Number(product.is_available)),
  }));

  renderProducts();
}

function renderProducts() {
  if (!productList) return;

  productList.replaceChildren();

  const count = $("#product-count");

  if (count) {
    count.textContent = `${products.length} produk`;
  }

  if (products.length === 0) {
    const empty = document.createElement("p");

    empty.className = "product-empty";
    empty.textContent = "Belum ada produk.";

    productList.append(empty);
    return;
  }

  for (const product of products) {
    const row = document.createElement("div");
    row.className = "manage-row";

    const info = document.createElement("div");
    info.className = "manage-product";

    const details = document.createElement("span");
    const name = document.createElement("strong");
    const metadata = document.createElement("small");

    name.textContent = product.name;

    metadata.textContent =
      `${product.unit} · ${formatRupiah(product.price)}`;

    details.append(name, metadata);
    info.append(details);

    const actions = document.createElement("div");
    actions.className = "manage-actions";

    const availabilityLabel = document.createElement("label");
    availabilityLabel.className = "availability-control";

    const checkbox = document.createElement("input");

    checkbox.type = "checkbox";
    checkbox.checked = product.available;

    const availabilityText = document.createElement("span");

    availabilityText.textContent = product.available
      ? "Tersedia"
      : "Habis";

    checkbox.addEventListener("change", async () => {
      try {
        await postJSON(
          API.products,
          {
            id: product.id,
            name: product.name,
            category: product.category,
            description: product.description || "",
            price: product.price,
            unit: product.unit,
            available: checkbox.checked,
          },
          "PUT",
        );

        await loadProducts();

        notify("Ketersediaan produk diperbarui.");
      } catch (error) {
        checkbox.checked = product.available;
        notify(error.message);
      }
    });

    availabilityLabel.append(
      checkbox,
      availabilityText,
    );

    const editButton = createButton("Edit", () => {
      editProduct(product.id);
    });

    const deleteButton = createButton(
      "Hapus",
      () => deleteProduct(product.id),
      "table-action danger-action",
    );

    actions.append(
      availabilityLabel,
      editButton,
      deleteButton,
    );

    row.append(info, actions);

    productList.append(row);
  }
}

function resetProductForm() {
  if (!productForm) return;

  productForm.reset();
  editingProductId = null;

  const title = $("#product-form-title");
  const submit = productForm.querySelector('[type="submit"]');
  const cancel = $("#cancel-product");

  if (title) {
    title.textContent = "Tambah produk baru";
  }

  if (submit) {
    submit.textContent = "Simpan produk";
  }

  if (cancel) {
    cancel.hidden = true;
  }
}

function editProduct(id) {
  const product = products.find(
    (item) => item.id === id,
  );

  if (!product || !productForm) return;

  editingProductId = product.id;

  productForm.elements.name.value = product.name;
  productForm.elements.category.value = product.category;
  productForm.elements.price.value = String(product.price);
  productForm.elements.unit.value = product.unit;

  productForm.elements.available.checked =
    product.available;

  const description = productForm.elements.description;

  if (description) {
    description.value = product.description || "";
  }

  $("#product-form-title").textContent = "Edit produk";

  productForm.querySelector('[type="submit"]').textContent =
    "Simpan perubahan";

  $("#cancel-product").hidden = false;

  productForm.scrollIntoView({
    behavior: "smooth",
    block: "start",
  });
}

async function deleteProduct(id) {
  const product = products.find(
    (item) => item.id === id,
  );

  if (!product) return;

  if (
    !window.confirm(
      `Hapus produk "${product.name}" dari katalog?`,
    )
  ) {
    return;
  }

  try {
    await postJSON(
      API.products,
      { id },
      "DELETE",
    );

    await loadProducts();
    resetProductForm();

    notify("Produk berhasil dihapus.");
  } catch (error) {
    notify(error.message);
  }
}

if (productForm) {
  productForm.addEventListener("submit", async (event) => {
    event.preventDefault();

    const formData = new FormData(productForm);

    const payload = {
      name: String(formData.get("name") || "").trim(),
      category: String(formData.get("category") || "").trim(),
      price: Number(formData.get("price")),
      unit: String(formData.get("unit") || "").trim(),
      description: String(
        formData.get("description") || "",
      ).trim(),
      available: formData.has("available"),
    };

    if (
      !payload.name
      || !payload.category
      || !payload.unit
      || !Number.isSafeInteger(payload.price)
      || payload.price < 1
    ) {
      notify("Periksa kembali informasi produk.");
      return;
    }

    try {
      if (editingProductId !== null) {
        await postJSON(
          API.products,
          {
            id: editingProductId,
            ...payload,
          },
          "PUT",
        );
      } else {
        await postJSON(API.products, payload);
      }

      await loadProducts();
      resetProductForm();

      notify("Produk berhasil disimpan.");
    } catch (error) {
      notify(error.message);
    }
  });
}

$("#cancel-product")?.addEventListener(
  "click",
  resetProductForm,
);

/* =========================================================
   PESANAN
========================================================= */

let orders = [];

async function loadOrders() {
  const data = await apiRequest(API.orders);

  orders = data.orders;

  renderOrders();
}

function nextOrderStatus(status) {
  const next = {
    pending: {
      value: "confirmed",
      label: "Konfirmasi",
    },

    confirmed: {
      value: "processing",
      label: "Proses",
    },

    processing: {
      value: "completed",
      label: "Selesaikan",
    },
  };

  return next[status] || null;
}

function renderOrders() {
  const table = $("#orders-table");

  if (!table) return;

  table.replaceChildren();

  if (orders.length === 0) {
    const row = document.createElement("tr");
    const cell = createCell("Belum ada pesanan.");

    cell.colSpan = 6;
    cell.className = "empty-table-cell";

    row.append(cell);
    table.append(row);

    return;
  }

  for (const order of orders) {
    const row = document.createElement("tr");

    const itemSummary = (order.items || [])
      .map(
        (item) =>
          `${item.product_name} × ${item.quantity}`,
      )
      .join(", ");

    const status = document.createElement("span");

    status.className = `status-pill status-${order.status}`;
    status.textContent = order.status;

    const statusCell = document.createElement("td");

    statusCell.append(status);

    const actions = document.createElement("td");

    const next = nextOrderStatus(order.status);

    if (next) {
      actions.append(
        createButton(next.label, async () => {
          await changeOrderStatus(order, next.value);
        }),
      );
    }

    if (
      order.status === "pending"
      || order.status === "confirmed"
    ) {
      actions.append(
        createButton(
          "Batalkan",
          async () => {
            await changeOrderStatus(order, "cancelled");
          },
          "table-action danger-action",
        ),
      );
    }

    row.append(
      createCell(order.order_number, "order-id"),
      createCell(
        `${order.buyer_name}\n${order.buyer_phone}`,
      ),
      createCell(itemSummary || "Tidak ada rincian"),
      createCell(formatRupiah(order.total)),
      statusCell,
      actions,
    );

    table.append(row);
  }
}

async function changeOrderStatus(order, status) {
  const labels = {
    confirmed: "mengonfirmasi",
    processing: "memproses",
    completed: "menyelesaikan",
    cancelled: "membatalkan",
  };

  if (
    !window.confirm(
      `Anda yakin ingin ${labels[status]} pesanan ${order.order_number}?`,
    )
  ) {
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
    notify(error.message);
  }
}

/* =========================================================
   PENGATURAN
========================================================= */

const settingsForm = $("#store-settings-form");
const openToggle = $("#store-open-toggle");
const freeDeliveryToggle = $("#free-delivery-toggle");

async function loadSettings() {
  const data = await apiRequest(API.settings);

  const settings = data.settings;

  if (settingsForm) {
    for (const key of [
      "name",
      "owner",
      "phone",
      "address",
      "description",
    ]) {
      if (settingsForm.elements[key]) {
        settingsForm.elements[key].value =
          settings[key] || "";
      }
    }
  }

  if (openToggle) {
    openToggle.checked = settings.is_open;
  }

  if (freeDeliveryToggle) {
    freeDeliveryToggle.checked =
      settings.free_delivery;
  }

  updateSettingLabels(settings);
}

function updateSettingLabels(settings) {
  const openText = $("#store-toggle-text");
  const freeText = $("#free-delivery-toggle-text");

  if (openText) {
    openText.textContent = settings.is_open
      ? "Warung sedang buka"
      : "Warung sedang tutup";
  }

  if (freeText) {
    freeText.textContent = settings.free_delivery
      ? "Gratis ongkir aktif"
      : "Gratis ongkir tidak aktif";
  }
}

async function updateSettingStatus(key, value) {
  try {
    await postJSON(API.settings, {
      [key]: value,
    });

    await Promise.all([
      loadSettings(),
      loadDashboard(),
    ]);

    notify("Pengaturan berhasil disimpan.");
  } catch (error) {
    await loadSettings();
    notify(error.message);
  }
}

if (settingsForm) {
  settingsForm.addEventListener("submit", async (event) => {
    event.preventDefault();

    const formData = new FormData(settingsForm);

    const payload = {
      name: String(formData.get("name") || "").trim(),
      owner: String(formData.get("owner") || "").trim(),
      phone: String(formData.get("phone") || "").trim(),
      address: String(formData.get("address") || "").trim(),
      description: String(
        formData.get("description") || "",
      ).trim(),
    };

    if (
      !payload.name
      || !payload.owner
      || !payload.address
    ) {
      notify("Nama warung, pemilik, dan alamat wajib diisi.");
      return;
    }

    try {
      const result = await postJSON(
        API.settings,
        payload,
      );

      $("#store-settings-feedback").textContent =
        result.message;

      await loadSettings();

      notify(result.message);
    } catch (error) {
      notify(error.message);
    }
  });
}

$("#reset-store-settings")?.addEventListener(
  "click",
  loadSettings,
);

openToggle?.addEventListener("change", () => {
  updateSettingStatus(
    "is_open",
    openToggle.checked,
  );
});

freeDeliveryToggle?.addEventListener("change", () => {
  updateSettingStatus(
    "free_delivery",
    freeDeliveryToggle.checked,
  );
});

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
  if (event.key === "Escape") {
    sidebar?.classList.remove("open");
    mobileMenu?.setAttribute("aria-expanded", "false");
  }
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
      await loadProducts();
    }

    if (page === "orders") {
      await loadOrders();
    }

    if (page === "settings") {
      await loadSettings();
    }
  } catch (error) {
    notify(error.message);
  }
}

initializeDashboard();