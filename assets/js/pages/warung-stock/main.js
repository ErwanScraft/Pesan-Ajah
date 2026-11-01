export async function initWarungStock({
  $,
  API,
  apiRequest,
  postJSON,
  notify,
  resolveImageUrl,
}) {
  const container = $("#manage-stock");

  if (!container) {
    return;
  }

  let products = [];

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

  function createStockRow(product) {
    const row = createElement("article", "stock-item");

    row.dataset.productId = String(product.id);

    const imageWrapper = createElement(
      "div",
      "stock-item-image",
    );

    const image = document.createElement("img");

    image.alt = product.name || "Produk";
    image.loading = "lazy";
    image.decoding = "async";

    if (product.image) {
      image.src = resolveImageUrl(product.image);
    } else {
      imageWrapper.classList.add("is-empty");
      imageWrapper.textContent = "IMG";
    }

    if (product.image) {
      imageWrapper.append(image);
    }

    const information = createElement(
      "div",
      "stock-item-info",
    );

    const name = createElement(
      "strong",
      "stock-item-name",
      product.name,
    );

    const unit = createElement(
      "span",
      "stock-item-unit",
      product.unit || "pcs",
    );

    information.append(name, unit);

    const controls = createElement(
      "div",
      "stock-item-controls",
    );

    const input = document.createElement("input");

    input.type = "number";
    input.className = "stock-quantity-input";
    input.inputMode = "numeric";
    input.min = "0";
    input.max = "2147483647";
    input.step = "1";

    input.value = product.stock_quantity === null
      ? ""
      : String(product.stock_quantity);

    input.placeholder = product.stock_quantity === null
      ? "∞"
      : "0";

    input.setAttribute(
      "aria-label",
      `Jumlah stock ${product.name}`,
    );

    const decreaseButton = createElement(
      "button",
      "stock-adjust-button stock-decrease",
      "−",
    );

    decreaseButton.type = "button";
    decreaseButton.setAttribute(
      "aria-label",
      `Kurangi stock ${product.name}`,
    );

    const increaseButton = createElement(
      "button",
      "stock-adjust-button stock-increase",
      "+",
    );

    increaseButton.type = "button";
    increaseButton.setAttribute(
      "aria-label",
      `Tambah stock ${product.name}`,
    );

    if (product.stock_quantity === null) {
      decreaseButton.disabled = true;
    }

    async function saveStock(action, quantity) {
      const buttons = [
        decreaseButton,
        increaseButton,
      ];

      buttons.forEach((button) => {
        button.disabled = true;
      });

      input.disabled = true;

      try {
        const result = await postJSON(API.stock, {
          product_id: product.id,
          action,
          quantity,
        });

        product.stock_quantity = result.stock_quantity;

        input.value = String(result.stock_quantity);
        input.placeholder = "0";

        decreaseButton.disabled =
          result.stock_quantity <= 0;

        notify(result.message, "success");

      } catch (error) {
        notify(
          error instanceof Error
            ? error.message
            : "Stock gagal diperbarui.",
          "error",
        );

        input.value = product.stock_quantity === null
          ? ""
          : String(product.stock_quantity);

        decreaseButton.disabled =
          product.stock_quantity === null
          || product.stock_quantity <= 0;

      } finally {
        buttons.forEach((button) => {
          button.disabled = false;
        });

        if (product.stock_quantity === null) {
          decreaseButton.disabled = true;
        } else {
          decreaseButton.disabled =
            product.stock_quantity <= 0;
        }

        input.disabled = false;
      }
    }

    decreaseButton.addEventListener("click", async () => {
      if (
        product.stock_quantity === null
        || product.stock_quantity <= 0
      ) {
        return;
      }

      await saveStock("decrease", 1);
    });

    increaseButton.addEventListener("click", async () => {
      await saveStock("increase", 1);
    });

    input.addEventListener("change", async () => {
      const value = input.value.trim();

      if (!/^\d+$/.test(value)) {
        notify("Masukkan jumlah stock yang valid.", "error");

        input.value = product.stock_quantity === null
          ? ""
          : String(product.stock_quantity);

        return;
      }

      const quantity = Number(value);

      if (
        !Number.isSafeInteger(quantity)
        || quantity < 0
        || quantity > 2147483647
      ) {
        notify("Jumlah stock berada di luar batas.", "error");

        input.value = product.stock_quantity === null
          ? ""
          : String(product.stock_quantity);

        return;
      }

      if (
        product.stock_quantity !== null
        && quantity === product.stock_quantity
      ) {
        return;
      }

      await saveStock("set", quantity);
    });

    controls.append(
      input,
      decreaseButton,
      increaseButton,
    );

    row.append(
      imageWrapper,
      information,
      controls,
    );

    return row;
  }

  function renderProducts() {
    container.replaceChildren();

    if (products.length === 0) {
      container.append(
        createElement(
          "div",
          "stock-empty",
          "Belum ada produk.",
        ),
      );

      return;
    }

    const fragment = document.createDocumentFragment();

    products.forEach((product) => {
      fragment.append(createStockRow(product));
    });

    container.append(fragment);
  }

  async function loadProducts() {
    container.innerHTML = "";

    container.append(
      createElement(
        "div",
        "stock-loading",
        "Memuat produk...",
      ),
    );

    try {
      const data = await apiRequest(API.stock);

      products = Array.isArray(data.products)
        ? data.products
        : [];

      renderProducts();

    } catch (error) {
      container.replaceChildren();

      container.append(
        createElement(
          "div",
          "stock-empty",
          error instanceof Error
            ? error.message
            : "Produk gagal dimuat.",
        ),
      );

      notify(
        error instanceof Error
          ? error.message
          : "Produk gagal dimuat.",
        "error",
      );
    }
  }

  await loadProducts();
}