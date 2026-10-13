import { products, saveProducts } from "./data/products.js";
import { formatRupiah } from "./modules/format.js";
import { showToast } from "./modules/toast.js";
import {
  getStoreSettings,
  saveStoreSettings,
} from "./data/store.js";

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) =>
  [...root.querySelectorAll(selector)];

const page = document.body.dataset.page;
const toast = $("#toast");
const sidebar = $("#sidebar");

const productForm = $("#product-form");
const productList = $("#manage-products");
const storeSettingsForm = $("#store-settings-form");

const ORDERS_STORAGE_KEY = "pesan-ajah-demo-orders";

const defaultOrders = [
  {
    id: "PA-0012",
    buyer: "Siti Aminah",
    summary: "Beras Premium × 1",
    total: 16000,
    status: "Menunggu",
    key: "pending",
  },
  {
    id: "PA-0011",
    buyer: "Budi Santoso",
    summary: "Telur Ayam × 1",
    total: 29000,
    status: "Diproses",
    key: "process",
  },
  {
    id: "PA-0010",
    buyer: "Dewi Lestari",
    summary: "Mi Instan × 3",
    total: 10500,
    status: "Selesai",
    key: "done",
  },
];

function notify(message) {
  showToast(toast, message);
}

/* Penyimpanan pesanan demo */

function loadOrders() {
  try {
    const saved = localStorage.getItem(ORDERS_STORAGE_KEY);

    if (!saved) {
      return defaultOrders.map((order) => ({ ...order }));
    }

    const parsed = JSON.parse(saved);

    if (!Array.isArray(parsed)) {
      return defaultOrders.map((order) => ({ ...order }));
    }

    return parsed;
  } catch {
    return defaultOrders.map((order) => ({ ...order }));
  }
}

const orders = loadOrders();

function persistOrders() {
  try {
    localStorage.setItem(ORDERS_STORAGE_KEY, JSON.stringify(orders));
    return true;
  } catch {
    notify("Pesanan demo gagal disimpan di browser.");
    return false;
  }
}

/* Penyimpanan produk */

function persistProducts() {
  if (!saveProducts()) {
    notify("Data gagal disimpan. Periksa penyimpanan browser.");
    return false;
  }

  return true;
}

function createCell(value, className = "") {
  const cell = document.createElement("td");
  cell.textContent = String(value);

  if (className) {
    cell.className = className;
  }

  return cell;
}

function createButton(text, className, callback) {
  const button = document.createElement("button");

  button.type = "button";
  button.className = className;
  button.textContent = text;
  button.addEventListener("click", callback);

  return button;
}

/* Rincian dan pesanan */

function renderOrders() {
  const tableBody = $("#orders-table");
  const pendingCount = orders.filter(
    (order) => order.key === "pending",
  ).length;

  const pendingLabel = $("#pending-count");
  const pendingStat = $("#stat-pending");
  const ordersStat = $("#stat-orders");

  if (pendingLabel) {
    pendingLabel.textContent = String(pendingCount);
  }

  if (pendingStat) {
    pendingStat.textContent = String(pendingCount);
  }

  if (ordersStat) {
    ordersStat.textContent = String(orders.length);
  }

  if (!tableBody) return;

  const fragment = document.createDocumentFragment();

  for (const order of orders) {
    const row = document.createElement("tr");

    const total = document.createElement("strong");
    total.textContent = formatRupiah(order.total);

    const totalCell = document.createElement("td");
    totalCell.append(total);

    const status = document.createElement("span");
    status.className = `status-pill status-${order.key}`;
    status.textContent = order.status;

    const statusCell = document.createElement("td");
    statusCell.append(status);

    const actionCell = document.createElement("td");
    const actionButton = document.createElement("button");

    actionButton.className = "table-action";
    actionButton.type = "button";
    actionButton.dataset.order = order.id;

    if (order.key === "pending") {
      actionButton.textContent = "Konfirmasi";
    } else if (order.key === "process") {
      actionButton.textContent = "Selesaikan";
    } else {
      actionButton.textContent = "Selesai";
      actionButton.disabled = true;
    }

    actionCell.append(actionButton);

    row.append(
      createCell(order.id, "order-id"),
      createCell(order.buyer),
      createCell(order.summary),
      totalCell,
      statusCell,
      actionCell,
    );

    fragment.append(row);
  }

  tableBody.replaceChildren(fragment);
}

const ordersTable = $("#orders-table");

if (ordersTable) {
  ordersTable.addEventListener("click", (event) => {
    const button = event.target.closest("[data-order]");

    if (!button) return;

    const order = orders.find(
      (item) => item.id === button.dataset.order,
    );

    if (!order || order.key === "done") return;

    const previous = {
      key: order.key,
      status: order.status,
    };

    if (order.key === "pending") {
      order.key = "process";
      order.status = "Diproses";
    } else {
      order.key = "done";
      order.status = "Selesai";
    }

    if (!persistOrders()) {
      Object.assign(order, previous);
    }

    renderOrders();
    notify(`Status pesanan ${order.id} diperbarui.`);
  });
}

/* Produk */

function activeProducts() {
  return products.filter((product) => !product.archived);
}

function renderManagedProducts() {
  const visibleProducts = activeProducts();
  const productsStat = $("#stat-products");
  const productCount = $("#product-count");

  if (productsStat) {
    productsStat.textContent = String(
      visibleProducts.filter((product) => product.available).length,
    );
  }

  if (productCount) {
    productCount.textContent = `${visibleProducts.length} produk`;
  }

  if (!productList) return;

  const fragment = document.createDocumentFragment();

  for (const product of visibleProducts) {
    const row = document.createElement("div");
    row.className = "manage-row";

    const info = document.createElement("div");
    info.className = "manage-product";

    const emoji = document.createElement("span");
    emoji.className = "manage-emoji";
    emoji.setAttribute("aria-hidden", "true");
    emoji.textContent = product.emoji || "📦";

    const details = document.createElement("span");
    const name = document.createElement("strong");
    const metadata = document.createElement("small");

    name.textContent = product.name;
    metadata.textContent =
      `${product.unit} · ${formatRupiah(product.price)}`;

    details.append(name, metadata);
    info.append(emoji, details);

    const actions = document.createElement("div");
    actions.className = "manage-actions";

    const availabilityLabel = document.createElement("label");
    availabilityLabel.className = product.available
      ? "availability-control availability-yes"
      : "availability-control availability-no";

    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.checked = Boolean(product.available);
    checkbox.dataset.product = String(product.id);
    checkbox.setAttribute(
      "aria-label",
      `Ketersediaan ${product.name}`,
    );

    const availabilityText = document.createElement("span");
    availabilityText.textContent = product.available
      ? "Tersedia"
      : "Habis";

    availabilityLabel.append(checkbox, availabilityText);

    const editButton = createButton(
      "Edit",
      "table-action",
      () => editProduct(product.id),
    );

    const deleteButton = createButton(
      "Hapus",
      "table-action danger-action",
      () => deleteProduct(product.id),
    );

    deleteButton.setAttribute(
      "aria-label",
      `Hapus produk ${product.name}`,
    );

    actions.append(availabilityLabel, editButton, deleteButton);
    row.append(info, actions);
    fragment.append(row);
  }

  if (visibleProducts.length === 0) {
    const empty = document.createElement("p");
    empty.className = "product-empty";
    empty.textContent =
      "Belum ada produk. Gunakan formulir di atas untuk menambahkan produk.";

    fragment.append(empty);
  }

  productList.replaceChildren(fragment);
}

function resetProductForm() {
  if (!productForm) return;

  productForm.reset();
  productForm.elements.available.checked = true;
  productForm.elements.emoji.value = "📦";
  productForm.dataset.editingId = "";

  $("#product-form-title").textContent = "Tambah produk baru";

  productForm.querySelector('[type="submit"]').textContent =
    "Simpan produk";

  const cancelButton = $("#cancel-product");

  if (cancelButton) {
    cancelButton.hidden = true;
  }
}

function editProduct(productId) {
  const product = products.find(
    (item) => item.id === productId && !item.archived,
  );

  if (!product || !productForm) {
    notify("Produk tidak ditemukan.");
    return;
  }

  productForm.elements.name.value = product.name;
  productForm.elements.category.value = product.category;
  productForm.elements.price.value = String(product.price);
  productForm.elements.unit.value = product.unit;
  productForm.elements.emoji.value = product.emoji || "📦";
  productForm.elements.tag.value = product.tag || "";
  productForm.elements.available.checked = Boolean(product.available);

  productForm.dataset.editingId = String(product.id);

  $("#product-form-title").textContent = "Edit produk";

  productForm.querySelector('[type="submit"]').textContent =
    "Simpan perubahan";

  const cancelButton = $("#cancel-product");

  if (cancelButton) {
    cancelButton.hidden = false;
  }

  productForm.scrollIntoView({
    behavior: "smooth",
    block: "start",
  });

  productForm.elements.name.focus();
}

function deleteProduct(productId) {
  const product = products.find(
    (item) => item.id === productId && !item.archived,
  );

  if (!product) {
    notify("Produk tidak ditemukan.");
    return;
  }

  const confirmed = window.confirm(
    `Hapus "${product.name}" dari katalog? Produk tidak akan ditampilkan lagi di marketplace.`,
  );

  if (!confirmed) return;

  product.archived = true;

  if (!persistProducts()) {
    product.archived = false;
    return;
  }

  if (productForm?.dataset.editingId === String(productId)) {
    resetProductForm();
  }

  renderManagedProducts();
  notify(`Produk "${product.name}" berhasil dihapus dari katalog.`);
}

if (productForm) {
  productForm.addEventListener("submit", (event) => {
    event.preventDefault();

    const formData = new FormData(productForm);

    const name = String(formData.get("name") || "").trim();
    const category = String(formData.get("category") || "").trim();
    const unit = String(formData.get("unit") || "").trim();
    const emoji = String(formData.get("emoji") || "📦").trim();
    const tag = String(formData.get("tag") || "").trim();
    const price = Number(formData.get("price"));
    const available = formData.has("available");

    if (!name || !category || !unit) {
      notify("Lengkapi semua informasi produk.");
      return;
    }

    if (!Number.isSafeInteger(price) || price < 1) {
      notify("Harga harus berupa bilangan bulat lebih dari nol.");
      return;
    }

    const editingId = productForm.dataset.editingId;

    if (editingId) {
      const product = products.find(
        (item) => item.id === Number(editingId) && !item.archived,
      );

      if (!product) {
        notify("Produk yang akan diedit tidak ditemukan.");
        resetProductForm();
        return;
      }

      const previous = { ...product };

      Object.assign(product, {
        name,
        category,
        unit,
        price,
        emoji,
        tag,
        available,
      });

      if (!persistProducts()) {
        Object.assign(product, previous);
        return;
      }

      renderManagedProducts();
      resetProductForm();
      notify(`Produk "${name}" berhasil diperbarui.`);
      return;
    }

    const nextId =
      products.reduce((max, item) => Math.max(max, item.id), 0) + 1;

    const newProduct = {
      id: nextId,
      name,
      category,
      unit,
      price,
      emoji,
      tag,
      store: getStoreSettings().name,
      available,
    };

    products.push(newProduct);

    if (!persistProducts()) {
      products.pop();
      return;
    }

    renderManagedProducts();
    resetProductForm();
    notify(`Produk "${name}" berhasil ditambahkan.`);
  });
}

const cancelProductButton = $("#cancel-product");

if (cancelProductButton) {
  cancelProductButton.addEventListener("click", resetProductForm);
}

if (productList) {
  productList.addEventListener("change", (event) => {
    const input = event.target.closest("[data-product]");

    if (!input) return;

    const product = products.find(
      (item) => item.id === Number(input.dataset.product),
    );

    if (!product) return;

    const previousValue = product.available;
    product.available = input.checked;

    if (!persistProducts()) {
      product.available = previousValue;
      renderManagedProducts();
      return;
    }

    renderManagedProducts();

    notify(
      `${product.name}: ${product.available ? "tersedia" : "habis"}.`,
    );
  });
}

/* Pengaturan profil warung */

function fillStoreSettingsForm() {
  const settings = getStoreSettings();
  const storeName = $("#sidebar-store-name");

  if (storeName) {
    storeName.textContent = settings.name || "Warung";
  }

  if (!storeSettingsForm) return;

  storeSettingsForm.elements.name.value = settings.name || "";
  storeSettingsForm.elements.owner.value = settings.owner || "";
  storeSettingsForm.elements.phone.value = settings.phone || "";
  storeSettingsForm.elements.address.value = settings.address || "";
  storeSettingsForm.elements.description.value =
    settings.description || "";
}

function handleStoreSettingsSubmit(event) {
  event.preventDefault();

  const formData = new FormData(storeSettingsForm);
  const currentSettings = getStoreSettings();

  const settings = {
    ...currentSettings,
    name: String(formData.get("name") || "").trim(),
    owner: String(formData.get("owner") || "").trim(),
    phone: String(formData.get("phone") || "").trim(),
    address: String(formData.get("address") || "").trim(),
    description: String(formData.get("description") || "").trim(),
  };

  if (!settings.name || !settings.owner || !settings.address) {
    notify("Nama warung, pemilik, dan alamat wajib diisi.");
    return;
  }

  const feedback = $("#store-settings-feedback");

  if (!saveStoreSettings(settings)) {
    if (feedback) {
      feedback.textContent =
        "Pengaturan gagal disimpan. Periksa penyimpanan browser.";
    }

    notify("Pengaturan warung gagal disimpan.");
    return;
  }

  fillStoreSettingsForm();
  renderManagedProducts();

  if (feedback) {
    feedback.textContent = "Pengaturan berhasil disimpan.";
  }

  notify("Pengaturan warung berhasil disimpan.");
}

function cancelStoreSettingsChanges() {
  fillStoreSettingsForm();

  const feedback = $("#store-settings-feedback");

  if (feedback) {
    feedback.textContent =
      "Perubahan yang belum disimpan telah dibatalkan.";
  }
}

if (storeSettingsForm) {
  storeSettingsForm.addEventListener(
    "submit",
    handleStoreSettingsSubmit,
  );
}

const resetStoreSettingsButton = $("#reset-store-settings");

if (resetStoreSettingsButton) {
  resetStoreSettingsButton.addEventListener(
    "click",
    cancelStoreSettingsChanges,
  );
}

/* Status buka/tutup warung */

function updateStoreStatus(isOpen, persist = true) {
  const statusLabel = $("#store-status-label");
  const toggleText = $("#store-toggle-text");
  const toggle = $("#store-open-toggle");
  const feedback = $("#store-status-feedback");

  if (statusLabel) {
    statusLabel.textContent = isOpen ? "Buka" : "Tutup";
    statusLabel.style.color = isOpen
      ? "var(--green)"
      : "var(--danger)";
  }

  if (toggleText) {
    toggleText.textContent = isOpen
      ? "Warung sedang buka"
      : "Warung sedang tutup";
  }

  if (toggle && toggle.checked !== isOpen) {
    toggle.checked = isOpen;
  }

  if (!persist) return;

  const settings = getStoreSettings();

  if (!saveStoreSettings({ ...settings, isOpen })) {
    if (feedback) {
      feedback.textContent = "Status warung gagal disimpan.";
    }

    notify("Status warung gagal disimpan.");
    return;
  }

  if (feedback) {
    feedback.textContent = isOpen
      ? "Warung ditandai buka."
      : "Warung ditandai tutup.";
  }

  notify(`Warung ditandai ${isOpen ? "buka" : "tutup"}.`);
}

const storeOpenToggle = $("#store-open-toggle");

if (storeOpenToggle) {
  storeOpenToggle.addEventListener("change", (event) => {
    updateStoreStatus(event.target.checked);
  });
}

/* Navigasi mobile */

const mobileMenu = $("#mobile-menu");

if (mobileMenu && sidebar) {
  mobileMenu.addEventListener("click", () => {
    const isOpen = sidebar.classList.toggle("open");

    mobileMenu.setAttribute("aria-expanded", String(isOpen));
  });
}

$$("#sidebar .side-link").forEach((link) => {
  link.addEventListener("click", () => {
    sidebar?.classList.remove("open");
    mobileMenu?.setAttribute("aria-expanded", "false");
  });
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    sidebar?.classList.remove("open");
    mobileMenu?.setAttribute("aria-expanded", "false");
  }
});

/* Inisialisasi sesuai halaman */

function initializeDashboard() {
  const settings = getStoreSettings();

  fillStoreSettingsForm();
  renderOrders();
  renderManagedProducts();

  if ($("#store-status-label") || storeOpenToggle) {
    updateStoreStatus(Boolean(settings.isOpen), false);
  }
}

initializeDashboard();