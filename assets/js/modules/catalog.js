import { formatRupiah } from "./format.js";
export function createCatalog({ products, grid, total, empty, categoryList, searchInput, cart, onAdd }) {
  let category = "Semua", query = "";
  function filteredProducts() {
    const normalizedQuery = query.trim().toLocaleLowerCase("id-ID");
    return products.filter((product) => (category === "Semua" || product.category === category) && [product.name, product.category, product.store].join(" ").toLocaleLowerCase("id-ID").includes(normalizedQuery));
  }
  function render() {
    const result = filteredProducts(); total.textContent = `${result.length} produk`; empty.hidden = result.length !== 0; grid.hidden = result.length === 0;
    grid.innerHTML = result.map((product) => `<article class="product-card"><div class="product-image"><span class="product-emoji" aria-hidden="true">${product.emoji}</span>${product.tag ? `<span class="product-tag">${product.tag}</span>` : ""}</div><div class="product-info"><div class="product-store">${product.store}</div><h3 class="product-name">${product.name}</h3><span class="product-unit">${product.unit}</span><div class="product-bottom"><span class="product-price">${formatRupiah(product.price)}</span><button class="add-button" type="button" data-add="${product.id}" aria-label="Tambah ${product.name}" ${product.available ? "" : "disabled"} title="${product.available ? "Tambah ke keranjang" : "Produk habis"}">${product.available ? (cart.quantityFor(product.id) || "+") : "–"}</button></div></div></article>`).join("");
  }
  categoryList.addEventListener("click", (event) => {
    const button = event.target.closest("[data-category]"); if (!button) return;
    category = button.dataset.category;
    categoryList.querySelectorAll("[data-category]").forEach((item) => { const active = item === button; item.classList.toggle("active", active); item.setAttribute("aria-pressed", String(active)); }); render();
  });
  searchInput.addEventListener("input", () => { query = searchInput.value; render(); });
  grid.addEventListener("click", (event) => { const button = event.target.closest("[data-add]"); if (button && !button.disabled) onAdd(Number(button.dataset.add)); });
  return { render, reset() { category = "Semua"; query = ""; searchInput.value = ""; categoryList.querySelectorAll("[data-category]").forEach((item) => { const active = item.dataset.category === category; item.classList.toggle("active", active); item.setAttribute("aria-pressed", String(active)); }); render(); }, setQuery(value) { query = value; searchInput.value = value; render(); } };
}
