import { products } from "./data/products.js";
import { formatRupiah } from "./modules/format.js";
import { showToast } from "./modules/toast.js";
const $ = (selector) => document.querySelector(selector), toast = $("#toast");
const orders = [
  { id: "PA-0012", buyer: "Siti Aminah", summary: "Beras Premium × 1", total: 16000, status: "Menunggu", key: "pending" },
  { id: "PA-0011", buyer: "Budi Santoso", summary: "Telur Ayam × 1", total: 29000, status: "Diproses", key: "process" },
  { id: "PA-0010", buyer: "Dewi Lestari", summary: "Mi Instan × 3", total: 10500, status: "Selesai", key: "done" },
];
function renderOrders() {
  $("#orders-table").innerHTML = orders.map((order) => `<tr><td class="order-id">${order.id}</td><td>${order.buyer}</td><td>${order.summary}</td><td><strong>${formatRupiah(order.total)}</strong></td><td><span class="status-pill status-${order.key}">${order.status}</span></td><td><button class="table-action" type="button" data-order="${order.id}" ${order.key === "done" ? "disabled" : ""}>${order.key === "pending" ? "Konfirmasi" : order.key === "process" ? "Selesaikan" : "Selesai"}</button></td></tr>`).join("");
  $("#pending-count").textContent = orders.filter((order) => order.key === "pending").length; $("#stat-pending").textContent = orders.filter((order) => order.key === "pending").length;
}
function renderManagedProducts() {
  $("#manage-products").innerHTML = products.map((product) => `<div class="manage-row"><div class="manage-product"><span class="manage-emoji" aria-hidden="true">${product.emoji}</span><span><strong>${product.name}</strong><small>${product.unit} · ${formatRupiah(product.price)}</small></span></div><label class="availability-control ${product.available ? "availability-yes" : "availability-no"}"><input type="checkbox" data-product="${product.id}" ${product.available ? "checked" : ""}>${product.available ? "Tersedia" : "Habis"}</label></div>`).join("");
  $("#stat-products").textContent = products.filter((product) => product.available).length;
}
$("#orders-table").addEventListener("click", (event) => { const button = event.target.closest("[data-order]"); if (!button) return; const order = orders.find((item) => item.id === button.dataset.order); if (!order || order.key === "done") return; if (order.key === "pending") { order.key = "process"; order.status = "Diproses"; } else { order.key = "done"; order.status = "Selesai"; } renderOrders(); showToast(toast, `Status pesanan ${order.id} diperbarui (mode demo).`); });
$("#manage-products").addEventListener("change", (event) => { const input = event.target.closest("[data-product]"); if (!input) return; const product = products.find((item) => item.id === Number(input.dataset.product)); if (!product) return; product.available = input.checked; renderManagedProducts(); showToast(toast, `Status ${product.name}: ${product.available ? "tersedia" : "habis"} (mode demo).`); });
$("#store-open-toggle").addEventListener("change", (event) => { const isOpen = event.target.checked; $("#store-status-label").textContent = isOpen ? "Buka" : "Tutup"; $("#store-status-label").style.color = isOpen ? "var(--green)" : "#a45b44"; $("#store-toggle-text").textContent = isOpen ? "Warung sedang buka" : "Warung sedang tutup"; showToast(toast, `Warung ditandai ${isOpen ? "buka" : "tutup"} (mode demo).`); });
$("#mobile-menu").addEventListener("click", () => $("#sidebar").classList.toggle("open"));
document.querySelectorAll(".side-link").forEach((link) => link.addEventListener("click", () => { document.querySelectorAll(".side-link").forEach((item) => item.classList.remove("active")); link.classList.add("active"); $("#sidebar").classList.remove("open"); }));
renderOrders(); renderManagedProducts();
