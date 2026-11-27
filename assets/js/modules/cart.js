import { formatRupiah } from "./format.js";
import { setImageSource } from "./image.js";

export function createCart(products) {
  const items = new Map();
  const storageKey = "pesan_ajah_cart_v1";

  function saveCart() {
    try {
      localStorage.setItem(
        storageKey,
        JSON.stringify(
          [...items.entries()].map(([productId, quantity]) => ({
            product_id: productId,
            quantity,
          })),
        ),
      );
    } catch (error) {
      console.warn(
        "[Pesan Ajah] Keranjang gagal disimpan:",
        error,
      );
    }
  }

  function restoreCart() {
    try {
      const stored = JSON.parse(
        localStorage.getItem(storageKey) || "[]",
      );

      if (!Array.isArray(stored)) return;

      stored.forEach((entry) => {
        const productId = Number(entry.product_id);
        const quantity = Number(entry.quantity);

        if (
          !Number.isSafeInteger(productId) ||
          productId <= 0 ||
          !Number.isInteger(quantity) ||
          quantity < 1 ||
          quantity > 99
        ) {
          return;
        }

        items.set(productId, quantity);
      });
    } catch (error) {
      console.warn(
        "[Pesan Ajah] Data keranjang tidak dapat dipulihkan:",
        error,
      );
    }
  }

  restoreCart();

  function findProduct(productId) {
    return products.find(
      (product) => Number(product.id) === Number(productId),
    );
  }

  function add(productId) {
    const product = findProduct(productId);

    if (!product || !product.available) {
      return null;
    }

    const id = Number(productId);
    const current = items.get(id) ?? 0;

    if (current >= 99) {
      return null;
    }

    items.set(id, current + 1);
    saveCart();

    return product;
  }

  function change(productId, delta) {
    const id = Number(productId);
    const current = items.get(id) ?? 0;
    const next = current + Number(delta);

    if (!Number.isFinite(next) || next <= 0) {
      items.delete(id);
      saveCart();
      return 0;
    }

    const quantity = Math.min(99, Math.floor(next));

    items.set(id, quantity);
    saveCart();

    return quantity;
  }

  function quantityFor(productId) {
    return items.get(Number(productId)) ?? 0;
  }

  function quantity(storeId = null) {
    let count = 0;

    for (const [productId, amount] of items) {
      const product = findProduct(productId);

      if (
        storeId !== null &&
        Number(product?.store_id) !== Number(storeId)
      ) {
        continue;
      }

      count += amount;
    }

    return count;
  }

  function total(storeId = null) {
    let amount = 0;

    for (const [productId, count] of items) {
      const product = findProduct(productId);

      if (
        !product ||
        (
          storeId !== null &&
          Number(product.store_id) !== Number(storeId)
        )
      ) {
        continue;
      }

      amount += Number(product.price) * count;
    }

    return amount;
  }

  function entries(storeId = null) {
    return [...items.entries()]
      .filter(([productId]) => {
        if (storeId === null) return true;

        const product = findProduct(productId);

        return Number(product?.store_id) === Number(storeId);
      })
      .map(([productId, quantity]) => ({
        product_id: Number(productId),
        quantity,
      }));
  }

  function clearStore(storeId) {
    for (const productId of items.keys()) {
      const product = findProduct(productId);

      if (Number(product?.store_id) === Number(storeId)) {
        items.delete(productId);
      }
    }

    saveCart();
  }

  function clear() {
    items.clear();
    localStorage.removeItem(storageKey);
  }

  function createCartImage(product) {
    const container = document.createElement("div");
    container.className = "cart-item-image";

    const image = document.createElement("img");
    image.alt = product.name || "Foto produk";
    image.loading = "lazy";
    image.decoding = "async";

    setImageSource(image, product.image);

    container.append(image);

    return container;
  }

  function createStoreImage(product) {
    const container = document.createElement("div");
    container.className = "cart-store-image";

    const image = document.createElement("img");
    image.alt = product.store_name || "Foto warung";
    image.loading = "lazy";
    image.decoding = "async";

    setImageSource(image, product.store_icon_image);

    container.append(image);

    return container;
  }

  function render(view) {
    const {
      count,
      list,
      empty,
      footer,
      totalLabel,
      storeId = null,
      showCheckout = true,
    } = view;
  
    const cartEntries = [...items.entries()].filter(
      ([productId]) => {
        const product = findProduct(productId);
  
        if (!product) return false;
  
        return (
          storeId === null ||
          Number(product.store_id) === Number(storeId)
        );
      },
    );
  
    count.textContent = String(quantity());
  
    empty.hidden = cartEntries.length > 0;
    footer.hidden = cartEntries.length === 0;
  
    list.replaceChildren();
  
    const storeGroups = new Map();
  
    for (const [productId, amount] of cartEntries) {
      const product = findProduct(productId);
  
      if (!product) continue;
  
      const currentStoreId = Number(product.store_id);
  
      if (!storeGroups.has(currentStoreId)) {
        storeGroups.set(currentStoreId, {
          product,
          items: [],
          subtotal: 0,
        });
      }
  
      const group = storeGroups.get(currentStoreId);
  
      group.items.push({
        product,
        productId,
        quantity: amount,
      });
  
      group.subtotal += Number(product.price) * amount;
    }
  
    for (const [currentStoreId, group] of storeGroups) {
      const storeCard = document.createElement("section");
      storeCard.className = "cart-store-group";
  
      const storeHeader = document.createElement("div");
      storeHeader.className = "cart-store-header";
  
      storeHeader.append(createStoreImage(group.product));
  
      const storeName = document.createElement("strong");
      storeName.className = "cart-store-name";
      storeName.textContent =
        group.product.store_name || "Warung";
  
      storeHeader.append(storeName);
      storeCard.append(storeHeader);
  
      const productList = document.createElement("div");
      productList.className = "cart-store-products";
  
      for (const entry of group.items) {
        const {
          product,
          productId,
          quantity,
        } = entry;
  
        const item = document.createElement("article");
        item.className = "cart-item";
  
        const image = createCartImage(product);
  
        const details = document.createElement("div");
        details.className = "cart-item-details";
  
        const name = document.createElement("p");
        name.className = "cart-item-name";
        name.textContent = product.name;
  
        details.append(name);
  
        if (product.variant) {
          const variant = document.createElement("small");
          variant.className = "cart-item-variant";
          variant.textContent = product.variant;
  
          details.append(variant);
        }
  
        const price = document.createElement("span");
        price.className = "cart-item-price";
        price.textContent = formatRupiah(
          Number(product.price) * quantity,
        );
  
        details.append(price);
  
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
        quantityLabel.textContent = String(quantity);
        quantityLabel.setAttribute("aria-label", "Jumlah");
  
        const addButton = document.createElement("button");
        addButton.type = "button";
        addButton.dataset.add = String(productId);
        addButton.setAttribute(
          "aria-label",
          `Tambah ${product.name}`,
        );
        addButton.textContent = "+";
        addButton.disabled =
          !product.available || quantity >= 99;
  
        controls.append(
          removeButton,
          quantityLabel,
          addButton,
        );
  
        item.append(image, details, controls);
        productList.append(item);
      }
  
      const summary = document.createElement("div");
      summary.className = "cart-store-summary";
  
      const subtotalLabel = document.createElement("span");
      subtotalLabel.textContent = "Subtotal";
  
      const subtotalValue = document.createElement("strong");
      subtotalValue.textContent = formatRupiah(group.subtotal);
  
      summary.append(subtotalLabel, subtotalValue);
  
      storeCard.append(productList, summary);
  
      if (showCheckout) {
        const checkout = document.createElement("button");
  
        checkout.type = "button";
        checkout.className =
          "button button-primary button-full cart-store-checkout";
  
        checkout.dataset.checkoutStore = String(currentStoreId);
        checkout.textContent = "Checkout";
        checkout.disabled = !group.product.store_is_open;
  
        if (!group.product.store_is_open) {
          checkout.textContent = "Warung Tutup";
        }
  
        storeCard.append(checkout);
      }
  
      list.append(storeCard);
    }
  
    totalLabel.textContent = formatRupiah(total(storeId));
  }

  return {
    add,
    change,
    quantity,
    quantityFor,
    total,
    entries,
    clear,
    clearStore,
    render,
  };
}