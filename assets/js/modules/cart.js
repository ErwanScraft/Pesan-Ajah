import { formatRupiah } from "./format.js";
import { setImageSource } from "./image.js";

export function createCart(products) {
  const items = new Map();

  function findProduct(productId) {
    return products.find(
      (product) =>
        Number(product.id) === Number(productId),
    );
  }

  function add(productId) {
    const product = findProduct(productId);

    if (!product || !product.available) {
      return null;
    }

    const current =
      items.get(Number(productId)) ?? 0;

    if (current >= 99) {
      return null;
    }

    items.set(Number(productId), current + 1);

    return product;
  }

  function change(productId, delta) {
    const id = Number(productId);

    const current = items.get(id) ?? 0;

    const next = current + Number(delta);

    if (!Number.isFinite(next) || next <= 0) {
      items.delete(id);

      return 0;
    }

    const quantity = Math.min(
      99,
      Math.floor(next),
    );

    items.set(id, quantity);

    return quantity;
  }

  function quantityFor(productId) {
    return items.get(Number(productId)) ?? 0;
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
        amount += Number(product.price) * count;
      }
    }

    return amount;
  }

  function entries() {
    return [...items.entries()].map(
      ([productId, quantity]) => ({
        product_id: Number(productId),
        quantity,
      }),
    );
  }

  function clear() {
    items.clear();
  }

  function createCartImage(product) {
    const container = document.createElement("div");
  
    container.className = "cart-item-image";
  
    const placeholder = document.createElement("span");
  
    placeholder.className = "cart-item-image-placeholder";
    placeholder.textContent = "Foto tidak tersedia";
  
    const image = document.createElement("img");
  
    image.alt = product.name || "Foto produk";
    image.loading = "lazy";
    image.decoding = "async";
  
    image.addEventListener(
      "error",
      () => {
        image.hidden = true;
        placeholder.hidden = false;
      },
      { once: true },
    );
  
    if (product.image) {
      placeholder.hidden = true;
  
      const validSource = setImageSource(
        image,
        product.image,
        () => {
          image.hidden = true;
          placeholder.hidden = false;
        },
      );
  
      if (validSource) {
        container.append(image);
      } else {
        placeholder.hidden = false;
      }
    } else {
      image.hidden = true;
      placeholder.hidden = false;
    }
  
    container.append(placeholder);
  
    return container;
  }

  function render(view) {
    const {
      count,
      list,
      empty,
      footer,
      totalLabel,
    } = view;

    const cartEntries = [...items.entries()].filter(
      ([productId]) => findProduct(productId),
    );

    count.textContent = String(quantity());

    empty.hidden = cartEntries.length > 0;
    footer.hidden = cartEntries.length === 0;

    list.replaceChildren();

    for (const [productId, amount] of cartEntries) {
      const product = findProduct(productId);

      if (!product) {
        continue;
      }

      const item = document.createElement("article");

      item.className = "cart-item";

      const image = createCartImage(product);

      const details = document.createElement("div");

      details.className = "cart-item-details";

      const name = document.createElement("p");

      name.className = "cart-item-name";
      name.textContent = product.name;

      const price = document.createElement("span");

      price.className = "cart-item-price";

      price.textContent = formatRupiah(
        Number(product.price) * amount,
      );

      details.append(name, price);

      const controls = document.createElement("div");

      controls.className = "quantity-control";

      const removeButton = document.createElement("button");

      removeButton.type = "button";
      removeButton.dataset.remove = String(productId);

      removeButton.setAttribute(
        "aria-label",
        `Kurangi ${product.name}`,
      );

      removeButton.textContent = "−";

      const quantityLabel = document.createElement("span");

      quantityLabel.textContent = String(amount);

      quantityLabel.setAttribute(
        "aria-label",
        "Jumlah",
      );

      const addButton = document.createElement("button");

      addButton.type = "button";
      addButton.dataset.add = String(productId);

      addButton.setAttribute(
        "aria-label",
        `Tambah ${product.name}`,
      );

      addButton.textContent = "+";

      addButton.disabled =
        !product.available || amount >= 99;

      controls.append(
        removeButton,
        quantityLabel,
        addButton,
      );

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
    entries,
    clear,
    render,
  };
}