import { products, saveProducts } from "./data/products.js";
import { formatRupiah } from "./modules/format.js";
import { showToast } from "./modules/toast.js";

const $ = (selector) => document.querySelector(selector);

const toast = $("#toast");
const sidebar = $("#sidebar");
const productForm = $("#product-form");
const productList = $("#manage-products");

const orders = [
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

let storeIsOpen = true;

function notify(message) {
  showToast(toast, message);
}

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

/* Pesanan */

function renderOrders() {
  const tableBody = $("#orders-table");
  const fragment = document.createDocumentFragment();

  for (const order of orders) {
    const row = document.createElement("tr");

    const totalCell = document.createElement("td");
    const total = document.createElement("strong");

    total.textContent = formatRupiah(order.total);
    totalCell.append(total);

    const statusCell = document.createElement("td");
    const status = document.createElement("span");

    status.className = `status-pill status-${order.key}`;
    status.textContent = order.status;
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

  const pendingCount = orders.filter(
    (order) => order.key === "pending",
  ).length;

  $("#pending-count").textContent = String(pendingCount);
  $("#stat-pending").textContent = String(pendingCount);
  $("#stat-orders").textContent = String(orders.length);
}

$("#orders-table").addEventListener("click", (event) => {
  const button = event.target.closest("[data-order]");

  if (!button) return;

  const order = orders.find(
    (item) => item.id === button.dataset.order,
  );

  if (!order || order.key === "done") return;

  if (order.key === "pending") {
    order.key = "process";
    order.status = "Diproses";
  } else {
    order.key = "done";
    order.status = "Selesai";
  }

  renderOrders();
  notify(`Status pesanan ${order.id} diperbarui (mode demo).`);
});

/* Produk */

function activeProducts() {
  return products.filter((product) => !product.archived);
}

function renderManagedProducts() {
  const fragment = document.createDocumentFragment();
  const visibleProducts = activeProducts();

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

  const availableCount = visibleProducts.filter(
    (product) => product.available,
  ).length;

  $("#stat-products").textContent = String(availableCount);
  $("#product-count").textContent =
    `${visibleProducts.length} produk`;
}

function resetProductForm() {
  productForm.reset();
  productForm.elements.available.checked = true;
  productForm.elements.emoji.value = "📦";
  productForm.dataset.editingId = "";

  $("#product-form-title").textContent = "Tambah produk baru";
  productForm.querySelector('[type="submit"]').textContent =
    "Simpan produk";

  $("#cancel-product").hidden = true;
}

function editProduct(productId) {
  const product = products.find(
    (item) => item.id === productId && !item.archived,
  );

  if (!product) {
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

  $("#cancel-product").hidden = false;
  productForm.scrollIntoView({ behavior: "smooth", block: "start" });
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

  // Arsipkan, jangan langsung menghapus data secara permanen.
  product.archived = true;

  if (!persistProducts()) {
    product.archived = false;
    return;
  }

  if (productForm.dataset.editingId === String(productId)) {
    resetProductForm();
  }

  renderManagedProducts();
  notify(`Produk "${product.name}" berhasil dihapus dari katalog.`);
}

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
    products.reduce((max, product) => Math.max(max, product.id), 0) + 1;

  const newProduct = {
    id: nextId,
    name,
    category,
    unit,
    price,
    emoji,
    tag,
    store: "Warung Sederhana",
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

$("#cancel-product").addEventListener("click", resetProductForm);

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

/* Status warung */

function updateStoreStatus(isOpen) {
  storeIsOpen = isOpen;

  $("#store-status-label").textContent = isOpen ? "Buka" : "Tutup";
  $("#store-status-label").style.color = isOpen
    ? "var(--green)"
    : "var(--danger)";

  $("#store-toggle-text").textContent = isOpen
    ? "Warung sedang buka"
    : "Warung sedang tutup";
}

$("#store-open-toggle").addEventListener("change", (event) => {
  updateStoreStatus(event.target.checked);

  notify(
    `Warung ditandai ${storeIsOpen ? "buka" : "tutup"} (mode demo).`,
  );
});

/* Navigasi dashboard */

$("#mobile-menu").addEventListener("click", () => {
  sidebar.classList.toggle("open");
});

document.querySelectorAll(".side-link").forEach((link) => {
  link.addEventListener("click", () => {
    document.querySelectorAll(".side-link").forEach((item) => {
      item.classList.toggle("active", item === link);
    });

    sidebar.classList.remove("open");
  });
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    sidebar.classList.remove("open");
  }
});

/* Inisialisasi */

updateStoreStatus($("#store-open-toggle").checked);
renderOrders();
renderManagedProducts();