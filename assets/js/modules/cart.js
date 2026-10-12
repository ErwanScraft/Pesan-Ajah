import { formatRupiah } from "./format.js";
export function createCart(products) {
  const items = new Map();
  function add(productId) {
    const product = products.find((item) => item.id === productId);
    if (!product || !product.available) return null;
    items.set(productId, (items.get(productId) || 0) + 1);
    return product;
  }
  function change(productId, delta) {
    const next = (items.get(productId) || 0) + delta;
    if (next <= 0) items.delete(productId); else items.set(productId, next);
  }
  function quantityFor(productId) { return items.get(productId) || 0; }
  function quantity() { return [...items.values()].reduce((sum, value) => sum + value, 0); }
  function total() { return [...items.entries()].reduce((sum, [id, count]) => sum + (products.find((item) => item.id === id)?.price || 0) * count, 0); }
  function render({ count, list, empty, footer, totalLabel }) {
    const entries = [...items.entries()]; count.textContent = quantity(); empty.hidden = entries.length > 0; footer.hidden = entries.length === 0;
    list.innerHTML = entries.map(([id, amount]) => {
      const product = products.find((item) => item.id === id); if (!product) return "";
      return `<div class="cart-item"><div class="cart-item-emoji" aria-hidden="true">${product.emoji}</div><div><p class="cart-item-name">${product.name}</p><span class="cart-item-price">${formatRupiah(product.price * amount)}</span></div><div class="quantity-control"><button type="button" data-remove="${id}" aria-label="Kurangi ${product.name}">−</button><span>${amount}</span><button type="button" data-add="${id}" aria-label="Tambah ${product.name}">+</button></div></div>`;
    }).join(""); totalLabel.textContent = formatRupiah(total());
  }
  return { add, change, quantity, quantityFor, total, render };
}
