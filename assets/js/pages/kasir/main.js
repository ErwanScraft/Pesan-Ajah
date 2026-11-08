import { renderReceipt } from "../../modules/receipt/renderer.js";
import { printReceipt } from "../../modules/receipt/printer.js";

export async function initWarungKasir({
  $,
  API,
  apiRequest,
  postJSON,
  notify,
  resolveImageUrl,
  formatRupiah,
}) {
  const productsContainer = $("#cashier-products");
  const receiptPreviewContent = $(
    "#cashier-receipt-content"
  );
  const categoriesContainer = $("#cashier-categories");
  const cartContainer = $("#cashier-cart");
  const manualForm = $("#cashier-manual-form");

  if (!productsContainer || !cartContainer) {
    return;
  }

  let products = [];
  let cart = [];
  let selectedCategory = "all";
  let receipt = null;

  const receiptRoot = document.querySelector(
    "#shared-receipt-root"
  );
  
  const receiptPreview = $("#cashier-receipt");
  const checkoutButton = $("#cashier-checkout");
  const cashInput = $("#cashier-cash-received");
  const changeElement = $("#cashier-change");
  const totalElement = $("#cashier-total");

  function createElement(tag, className = "", text = "") {
    const element = document.createElement(tag);

    if (className) {
      element.className = className;
    }

    if (text !== "") {
      element.textContent = String(text);
    }

    return element;
  }

  function createButton(text, className, callback) {
    const button = createElement("button", className, text);

    button.type = "button";
    button.addEventListener("click", callback);

    return button;
  }

  function getPaymentMethod() {
    return document.querySelector(
      'input[name="payment_method"]:checked'
    )?.value || "cash";
  }

  function getTotal() {
    return cart.reduce(
      (sum, item) => sum + item.price * item.quantity,
      0,
    );
  }

  function getTotalItems() {
    return cart.reduce(
      (sum, item) => sum + item.quantity,
      0,
    );
  }

  function getProductCategory(product) {
    return String(product.category || "").trim() || "Lainnya";
  }

  function isProductAvailable(product) {
    return product.is_available
      && (
        product.stock_quantity === null
        || product.stock_quantity > 0
      );
  }

  function addCatalogProduct(product) {
    if (!isProductAvailable(product)) {
      notify("Produk sedang habis atau tidak tersedia.", "error");
      return;
    }

    const existing = cart.find(
      (item) =>
        item.type === "catalog"
        && item.product_id === product.id,
    );

    if (existing) {
      if (
        product.stock_quantity !== null
        && existing.quantity >= product.stock_quantity
      ) {
        notify("Jumlah melebihi stock yang tersedia.", "error");
        return;
      }

      existing.quantity += 1;
    } else {
      cart.push({
        key: `catalog-${product.id}`,
        type: "catalog",
        product_id: product.id,
        name: product.name,
        variant: product.variant || "",
        price: product.price,
        quantity: 1,
        unit: product.unit || "pcs",
      });
    }

    renderCart();
  }

  function addManualItem(name, price, quantity) {
    cart.push({
      key: `manual-${Date.now()}-${Math.random()}`,
      type: "manual",
      product_id: null,
      name,
      variant: "",
      price,
      quantity,
      unit: "pcs",
    });

    renderCart();
  }

  function changeQuantity(key, difference) {
    const item = cart.find((entry) => entry.key === key);

    if (!item) {
      return;
    }

    const nextQuantity = item.quantity + difference;

    if (nextQuantity < 1) {
      cart = cart.filter((entry) => entry.key !== key);
      renderCart();
      return;
    }

    if (item.type === "catalog") {
      const product = products.find(
        (entry) => entry.id === item.product_id,
      );

      if (
        product
        && product.stock_quantity !== null
        && nextQuantity > product.stock_quantity
      ) {
        notify("Jumlah melebihi stock yang tersedia.", "error");
        return;
      }
    }

    item.quantity = nextQuantity;
    renderCart();
  }

  function renderProducts() {
    productsContainer.replaceChildren();

    const keyword = $("#cashier-search").value
      .trim()
      .toLocaleLowerCase("id-ID");

    const filtered = products.filter((product) => {
      const matchesCategory =
        selectedCategory === "all"
        || getProductCategory(product) === selectedCategory;

      const matchesSearch = [
        product.name,
        product.variant,
        product.category,
      ].some((value) =>
        String(value || "")
          .toLocaleLowerCase("id-ID")
          .includes(keyword)
      );

      return matchesCategory && matchesSearch;
    });

    if (!filtered.length) {
      productsContainer.append(
        createElement(
          "div",
          "cashier-empty",
          "Produk tidak ditemukan.",
        ),
      );

      return;
    }

    const fragment = document.createDocumentFragment();

    filtered.forEach((product) => {
      const available = isProductAvailable(product);

      const card = createElement(
        "article",
        `cashier-product-card ${available ? "" : "is-unavailable"}`,
      );

      const imageWrapper = createElement(
        "div",
        "cashier-product-image",
      );

      if (product.image) {
        const image = document.createElement("img");

        image.src = resolveImageUrl(product.image);
        image.alt = product.name;
        image.loading = "lazy";

        imageWrapper.append(image);
      } else {
        imageWrapper.textContent = "IMG";
      }

      if (!available) {
        imageWrapper.append(
          createElement("span", "cashier-sold-out", "HABIS"),
        );
      }

      const information = createElement(
        "div",
        "cashier-product-info",
      );

      information.append(
        createElement("strong", "", product.name),
        createElement(
          "small",
          "",
          product.variant || product.unit || "Produk",
        ),
        createElement(
          "span",
          "cashier-product-price",
          formatRupiah(product.price),
        ),
      );

      const stockText = product.stock_quantity === null
        ? "Stock tidak dibatasi"
        : `Stock: ${product.stock_quantity}`;

      information.append(
        createElement("small", "cashier-stock-info", stockText),
      );

      const addButton = createButton(
        "+",
        "cashier-add-button",
        () => addCatalogProduct(product),
      );

      addButton.disabled = !available;
      addButton.setAttribute(
        "aria-label",
        `Tambah ${product.name}`,
      );

      card.append(imageWrapper, information, addButton);
      fragment.append(card);
    });

    productsContainer.append(fragment);
  }

  function renderCategories() {
    const categories = [
      ...new Set(products.map(getProductCategory)),
    ].sort((a, b) => a.localeCompare(b, "id-ID"));

    categoriesContainer.replaceChildren();

    const allButton = createButton(
      "Semua",
      selectedCategory === "all" ? "active" : "",
      () => {
        selectedCategory = "all";
        renderCategories();
        renderProducts();
      },
    );

    allButton.dataset.category = "all";
    categoriesContainer.append(allButton);

    categories.forEach((category) => {
      const button = createButton(
        category,
        selectedCategory === category ? "active" : "",
        () => {
          selectedCategory = category;
          renderCategories();
          renderProducts();
        },
      );

      button.dataset.category = category;
      categoriesContainer.append(button);
    });
  }

  function renderCart() {
    cartContainer.replaceChildren();

    if (!cart.length) {
      const empty = createElement(
        "div",
        "cashier-cart-empty",
      );

      empty.append(
        createElement("span", "", "▤"),
        createElement("strong", "", "Keranjang masih kosong"),
        createElement(
          "p",
          "",
          "Pilih produk atau masukkan item manual.",
        ),
      );

      cartContainer.append(empty);
    }

    cart.forEach((item) => {
      const row = createElement("article", "cashier-cart-item");

      const info = createElement("div", "cashier-cart-info");

      info.append(
        createElement("strong", "", item.name),
        createElement(
          "small",
          "",
          `${formatRupiah(item.price)} × ${item.quantity}`,
        ),
        createElement(
          "b",
          "",
          formatRupiah(item.price * item.quantity),
        ),
      );

      const controls = createElement(
        "div",
        "cashier-quantity-controls",
      );

      controls.append(
        createButton("−", "", () => changeQuantity(item.key, -1)),
        createElement("span", "", item.quantity),
        createButton("+", "", () => changeQuantity(item.key, 1)),
      );

      row.append(info, controls);
      cartContainer.append(row);
    });

    const total = getTotal();
    const totalItems = getTotalItems();
    const paymentMethod = getPaymentMethod();

    $("#cashier-item-count").textContent =
      `${totalItems} item`;

    $("#cashier-total-items").textContent =
      String(totalItems);

    totalElement.textContent = formatRupiah(total);

    const isCash = paymentMethod === "cash";

    $("#cashier-cash-field").hidden = !isCash;

    const cashReceived = Number(cashInput.value || 0);
    const change = isCash
      ? Math.max(0, cashReceived - total)
      : 0;

    changeElement.textContent = formatRupiah(change);

    checkoutButton.disabled =
      cart.length === 0
      || total < 1
      || (isCash && cashReceived < total);
  }

  function renderPaymentOptions() {
    document.querySelectorAll(
      ".cashier-payment-option",
    ).forEach((option) => {
      const input = option.querySelector("input");

      option.classList.toggle(
        "active",
        Boolean(input?.checked),
      );
    });

    renderCart();
  }

  manualForm.addEventListener("submit", (event) => {
    event.preventDefault();

    const formData = new FormData(manualForm);

    const name = String(formData.get("name") || "").trim();
    const price = Number(formData.get("price"));
    const quantity = Number(formData.get("quantity"));

    if (!name || name.length > 150) {
      notify("Nama item tidak valid.", "error");
      return;
    }

    if (
      !Number.isSafeInteger(price)
      || price < 1
      || price > 2147483647
    ) {
      notify("Harga item tidak valid.", "error");
      return;
    }

    if (
      !Number.isSafeInteger(quantity)
      || quantity < 1
      || quantity > 2147483647
    ) {
      notify("Jumlah item tidak valid.", "error");
      return;
    }

    addManualItem(name, price, quantity);

    manualForm.reset();
    manualForm.elements.quantity.value = "1";

    notify("Item manual ditambahkan.", "success");
  });

  $("#cashier-search").addEventListener(
    "input",
    renderProducts,
  );

  cashInput.addEventListener("input", renderCart);

  document.querySelectorAll(
    'input[name="payment_method"]',
  ).forEach((input) => {
    input.addEventListener("change", renderPaymentOptions);
  });

  $("#cashier-clear").addEventListener("click", () => {
    if (!cart.length) {
      return;
    }

    cart = [];
    renderCart();
  });

  async function loadProducts() {
    try {
      const data = await apiRequest(API.cashier);

      products = Array.isArray(data.products)
        ? data.products
        : [];

      renderCategories();
      renderProducts();
      renderCart();

    } catch (error) {
      productsContainer.replaceChildren(
        createElement(
          "div",
          "cashier-empty",
          error.message || "Produk gagal dimuat.",
        ),
      );

      notify(
        error.message || "Produk gagal dimuat.",
        "error",
      );
    }
  }

  checkoutButton.addEventListener("click", async () => {
    if (!cart.length || checkoutButton.disabled) {
      return;
    }

    const paymentMethod = getPaymentMethod();

    const payload = {
      payment_method: paymentMethod,
      cash_received: paymentMethod === "cash"
        ? Number(cashInput.value)
        : 0,

      items: cart.map((item) => ({
        type: item.type,
        product_id: item.product_id,
        name: item.type === "manual" ? item.name : undefined,
        price: item.type === "manual" ? item.price : undefined,
        quantity: item.quantity,
      })),
    };

    checkoutButton.disabled = true;
    checkoutButton.textContent = "Memproses...";

    try {
      const result = await postJSON(API.cashier, payload);
      
      const receiptData = {
        type: "cashier",
      
        store: {
          name: receipt.store_name,
          logo: receipt.store_logo,
          address: receipt.store_address,
          phone: receipt.store_phone,
        },
      
        items: receipt.items,
        total: receipt.total,
      
        payment: {
          method: receipt.payment_method,
          received: receipt.cash_received,
          change: receipt.change,
        },
      
        createdAt: receipt.created_at,
      };
      
      renderReceipt(receiptRoot, receiptData, {
        resolveImageUrl,
      });
      
      renderReceipt(receiptPreviewContent, receiptData, {
        resolveImageUrl,
      });

      receipt = result.receipt;
      
      renderReceipt(receiptRoot, {
        type: "cashier",
      
        store: {
          name: receipt.store_name,
          logo: receipt.store_logo,
          address: receipt.store_address,
          phone: receipt.store_phone,
        },
      
        items: receipt.items,
      
        total: receipt.total,
      
        payment: {
          method: receipt.payment_method,
          received: receipt.cash_received,
          change: receipt.change,
        },
      
        createdAt: receipt.created_at,
      }, {
        resolveImageUrl,
      });
      
      receiptPreview.hidden = false;
      
      receiptPreview.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });

      cart = [];
      cashInput.value = "";

      await loadProducts();

      notify("Pembayaran berhasil.", "success");

    } catch (error) {
      notify(
        error instanceof Error
          ? error.message
          : "Pembayaran gagal diproses.",
        "error",
      );

    } finally {
      checkoutButton.innerHTML =
        'Proses Pembayaran <span>→</span>';

      renderCart();
    }
  });

  $("#cashier-print").addEventListener("click", () => {
    if (!receipt) {
      return;
    }
  
    printReceipt({
      paperSize: "80mm",
      margin: 3,
    });
  });

  $("#cashier-new").addEventListener("click", () => {
    receipt = null;

    receiptRoot.replaceChildren();
    receiptPreview.hidden = true;
    receiptPreviewContent.replaceChildren();

    $("#cashier-receipt").hidden = true;

    cashInput.value = "";
    manualForm.reset();
    manualForm.elements.quantity.value = "1";

    renderCart();
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  await loadProducts();
}