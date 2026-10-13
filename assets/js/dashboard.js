
import { products } from "./data/products.js";
import { formatRupiah } from "./modules/format.js";
import { showToast } from "./modules/toast.js";

const $ = (selector) => document.querySelector(selector);
const toast = $("#toast");
const sidebar = $("#sidebar");

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

function createCell(value, className = "") {
  const cell = document.createElement("td");
  cell.textContent = String(value);

  if (className) {
    cell.className = className;
  }

  return cell;
}

function renderOrders() {
  const tableBody = $("#orders-table");
  const fragment = document.createDocumentFragment();

  for (const order of orders) {
    const row = document.createElement("tr");

    const id = createCell(order.id, "order-id");
    const buyer = createCell(order.buyer);
    const summary = createCell(order.summary);

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
    row.append(id, buyer, summary, totalCell, statusCell, actionCell);
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

function renderManagedProducts() {
  const list = $("#manage-products");
  const fragment = document.createDocumentFragment();

  for (const product of products) {
    const row = document.createElement("div");
    row.className = "manage-row";

    const productInfo = document.createElement("div");
    productInfo.className = "manage-product";

    const emoji = document.createElement("span");
    emoji.className = "manage-emoji";
    emoji.setAttribute("aria-hidden", "true");
    emoji.textContent = product.emoji;

    const details = document.createElement("span");
    const name = document.createElement("strong");
    const metadata = document.createElement("small");

    name.textContent = product.name;
    metadata.textContent =
      `${product.unit} · ${formatRupiah(product.price)}`;

    details.append(name, metadata);
    productInfo.append(emoji, details);

    const label = document.createElement("label");
    label.className = product.available
      ? "availability-control availability-yes"
      : "availability-control availability-no";

    const input = document.createElement("input");
    input.type = "checkbox";
    input.dataset.product = String(product.id);
    input.checked = product.available;
    input.setAttribute(
      "aria-label",
      `Ketersediaan ${product.name}`,
    );

    const statusText = document.createElement("span");
    statusText.textContent = product.available ? "Tersedia" : "Habis";

    label.append(input, statusText);
    row.append(productInfo, label);
    fragment.append(row);
  }

  list.replaceChildren(fragment);

  const availableCount = products.filter(
    (product) => product.available,
  ).length;

  $("#stat-products").textContent = String(availableCount);
}

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
  showToast(toast, `Status pesanan ${order.id} diperbarui (mode demo).`);
});

$("#manage-products").addEventListener("change", (event) => {
  const input = event.target.closest("[data-product]");

  if (!input) return;

  const product = products.find(
    (item) => item.id === Number(input.dataset.product),
  );

  if (!product) return;

  product.available = input.checked;
  renderManagedProducts();

  showToast(
    toast,
    `${product.name}: ${product.available ? "tersedia" : "habis"} (mode demo).`,
  );
});

$("#store-open-toggle").addEventListener("change", (event) => {
  updateStoreStatus(event.target.checked);

  showToast(
    toast,
    `Warung ditandai ${storeIsOpen ? "buka" : "tutup"} (mode demo).`,
  );
});

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

updateStoreStatus($("#store-open-toggle").checked);
renderOrders();
renderManagedProducts();
