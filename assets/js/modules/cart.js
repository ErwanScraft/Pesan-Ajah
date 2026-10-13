
import { formatRupiah } from "./format.js";

export function createCart(products) {
  const items = new Map();

  function findProduct(productId) {
    return products.find((product) => product.id === productId);
  }

  function add(productId) {
    const product = findProduct(productId);

    if (!product || !product.available) {
      return null;
    }

    items.set(productId, (items.get(productId) ?? 0) + 1);
    return product;
  }

  function change(productId, delta) {
    const currentQuantity = items.get(productId) ?? 0;
    const nextQuantity = currentQuantity + Number(delta);

    if (!Number.isFinite(nextQuantity) || nextQuantity <= 0) {
      items.delete(productId);
      return 0;
    }

    items.set(productId, Math.floor(nextQuantity));
    return items.get(productId);
  }

  function quantityFor(productId) {
    return items.get(productId) ?? 0;
  }

  function quantity() {
    let count = 0;

    for (const amount of items.values()) {
      count += amount;
    }

    return count;
  }

  function total() {
    let amount = 0;

    for (const [productId, count] of items) {
      const product = findProduct(productId);

      if (product) {
        amount += product.price * count;
      }
    }

    return amount;
  }

  function render(view) {
    const { count, list, empty, footer, totalLabel } = view;
    const entries = [...items.entries()].filter(([productId]) =>
      findProduct(productId),
    );

    count.textContent = String(quantity());
    empty.hidden = entries.length > 0;
    footer.hidden = entries.length === 0;

    list.replaceChildren();

    for (const [productId, amount] of entries) {
      const product = findProduct(productId);

      if (!product) continue;

      const item = document.createElement("article");
      item.className = "cart-item";

      const image = document.createElement("div");
      image.className = "cart-item-emoji";
      image.setAttribute("aria-hidden", "true");
      image.textContent = product.emoji;

      const details = document.createElement("div");

      const name = document.createElement("p");
      name.className = "cart-item-name";
      name.textContent = product.name;

      const price = document.createElement("span");
      price.className = "cart-item-price";
      price.textContent = formatRupiah(product.price * amount);

      details.append(name, price);

      const controls = document.createElement("div");
      controls.className = "quantity-control";

      const removeButton = document.createElement("button");
      removeButton.type = "button";
      removeButton.dataset.remove = String(productId);
      removeButton.setAttribute("aria-label", `Kurangi ${product.name}`);
      removeButton.textContent = "−";

      const quantityLabel = document.createElement("span");
      quantityLabel.textContent = String(amount);
      quantityLabel.setAttribute("aria-label", "Jumlah");

      const addButton = document.createElement("button");
      addButton.type = "button";
      addButton.dataset.add = String(productId);
      addButton.setAttribute("aria-label", `Tambah ${product.name}`);
      addButton.textContent = "+";
      addButton.disabled = !product.available;

      controls.append(removeButton, quantityLabel, addButton);
      item.append(image, details, controls);
      list.append(item);
    }

    totalLabel.textContent = formatRupiah(total());
  }

  return {
    add,
    change,
    quantity,
    quantityFor,
    total,
    render,
  };
}
