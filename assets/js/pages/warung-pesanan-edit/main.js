export async function initWarungOrderEditor({
  API,
  apiRequest,
  postJSON,
  notify,
  formatRupiah,
  resolveImageUrl,
  reloadOrders,
}) {
  let activeOverlay = null;
  let activeEscapeHandler = null;
  let catalogProducts = null;

  const EDITABLE_STATUSES = [
    "pending",
    "confirmed",
    "processing",
  ];

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

  function createButton(text, className, type = "button") {
    const button = createElement("button", className, text);
    button.type = type;
    return button;
  }

  function closeEditor() {
    if (!activeOverlay) {
      return;
    }
  
    if (activeEscapeHandler) {
      document.removeEventListener(
        "keydown",
        activeEscapeHandler
      );
  
      activeEscapeHandler = null;
    }
  
    activeOverlay.remove();
    activeOverlay = null;
  
    document.body.classList.remove("warung-order-edit-open");
  }

  function getProductImage(image) {
    if (!image) {
      return "";
    }

    return resolveImageUrl(image) || "";
  }

  async function loadCatalog() {
    if (catalogProducts !== null) {
      return catalogProducts;
    }

    const result = await apiRequest(API.products);

    catalogProducts = (result.products || []).map((product) => ({
      id: Number(product.id),
      name: product.name || "Produk",
      variant: product.variant || "",
      price: Number(product.price) || 0,
      image: product.image || "",
      available: Boolean(product.is_available),
    }));

    return catalogProducts;
  }

  function openEditor(order) {
    if (activeOverlay) {
      closeEditor();
    }

    const status = String(order.status || "").toLowerCase();

    if (!EDITABLE_STATUSES.includes(status)) {
      notify("Pesanan tidak dapat diedit pada status ini.");
      return;
    }

    const items = (Array.isArray(order.items) ? order.items : []).map(
      (item) => ({
        productId: item.product_id
          ? Number(item.product_id)
          : null,
        name: item.product_name || "Produk",
        variant: item.product_variant || "",
        price: Number(item.price) || 0,
        quantity: Number(item.quantity) || 1,
        image: item.image || "",
        locked: !item.product_id,
      })
    );

    const overlay = createElement("div", "warung-order-edit-overlay");
    const modal = createElement("form", "warung-order-edit-modal");

    activeOverlay = overlay;

    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-modal", "true");
    modal.setAttribute("aria-label", "Edit pesanan");

    // Header
    const header = createElement("header", "warung-order-edit-header");

    const heading = createElement("div", "warung-order-edit-heading");

    heading.append(
      createElement("h2", "", "Edit Pesanan"),
      createElement(
        "p",
        "",
        `Pesanan #${order.order_number || order.id}`
      )
    );

    const closeButton = createButton(
      "×",
      "warung-order-edit-close"
    );

    closeButton.setAttribute("aria-label", "Tutup editor");

    header.append(heading, closeButton);

    // Content
    const content = createElement("div", "warung-order-edit-content");

    const productSection = createElement(
      "section",
      "warung-order-edit-section"
    );

    const sectionHeading = createElement(
      "div",
      "warung-order-edit-section-heading"
    );

    sectionHeading.append(
      createElement("h3", "", "Daftar Item"),
      createElement(
        "span",
        "warung-order-edit-count",
        `${items.length} item`
      )
    );

    const productList = createElement(
      "div",
      "warung-order-edit-products"
    );

    productSection.append(sectionHeading, productList);

    const addButton = createButton(
      "+ Tambah Produk",
      "warung-order-edit-add"
    );

    const picker = createElement(
      "div",
      "warung-order-edit-picker"
    );

    picker.hidden = true;

    const search = createElement(
      "input",
      "warung-order-edit-search"
    );

    search.type = "search";
    search.placeholder = "Cari nama produk...";
    search.autocomplete = "off";
    search.setAttribute("aria-label", "Cari produk");

    const pickerList = createElement(
      "div",
      "warung-order-edit-picker-list"
    );

    picker.append(search, pickerList);

    // Catatan
    const noteSection = createElement(
      "section",
      "warung-order-edit-section"
    );

    noteSection.append(
      createElement("h3", "", "Catatan Pesanan")
    );

    const noteInput = createElement(
      "textarea",
      "warung-order-edit-note"
    );

    noteInput.name = "notes";
    noteInput.maxLength = 500;
    noteInput.placeholder = "Tambahkan catatan untuk pesanan...";
    noteInput.value = order.buyer_note || "";

    noteSection.append(noteInput);

    content.append(
      productSection,
      addButton,
      picker,
      noteSection
    );

    // Footer
    const footer = createElement("footer", "warung-order-edit-footer");

    const totalRow = createElement(
      "div",
      "warung-order-edit-total"
    );

    const totalLabel = createElement(
      "span",
      "",
      "Subtotal produk"
    );

    const totalValue = createElement(
      "strong",
      "warung-order-edit-total-value",
      formatRupiah(0)
    );

    totalRow.append(totalLabel, totalValue);

    const footerActions = createElement(
      "div",
      "warung-order-edit-actions"
    );

    const cancelButton = createButton(
      "Batal",
      "warung-order-edit-cancel"
    );

    const saveButton = createButton(
      "Simpan Perubahan",
      "warung-order-edit-save",
      "submit"
    );

    footerActions.append(cancelButton, saveButton);
    footer.append(totalRow, footerActions);

    modal.append(header, content, footer);
    overlay.append(modal);

    function getSubtotal() {
      return items.reduce(
        (total, item) => total + item.price * item.quantity,
        0
      );
    }

    function renderTotal() {
      totalValue.textContent = formatRupiah(getSubtotal());

      sectionHeading.querySelector(
        ".warung-order-edit-count"
      ).textContent = `${items.length} item`;
    }

    function renderProducts() {
      productList.replaceChildren();

      if (!items.length) {
        productList.append(
          createElement(
            "p",
            "warung-order-edit-empty",
            "Belum ada item dalam pesanan."
          )
        );

        renderTotal();
        return;
      }

      items.forEach((item, index) => {
        const row = createElement(
          "article",
          "warung-order-edit-product"
        );

        const image = createElement(
          "img",
          "warung-order-edit-product-image"
        );

        image.alt = item.name;
        image.loading = "lazy";

        const imageUrl = getProductImage(item.image);

        if (imageUrl) {
          image.src = imageUrl;

          image.addEventListener("error", () => {
            image.hidden = true;
            image.removeAttribute("src");
          }, { once: true });
        } else {
          image.hidden = true;
        }

        const info = createElement(
          "div",
          "warung-order-edit-product-info"
        );

        info.append(
          createElement("strong", "", item.name)
        );

        if (item.variant) {
          info.append(
            createElement("small", "", item.variant)
          );
        }

        info.append(
          createElement(
            "span",
            "",
            formatRupiah(item.price)
          )
        );

        const controls = createElement(
          "div",
          "warung-order-edit-controls"
        );

        if (item.locked) {
          controls.append(
            createElement(
              "small",
              "warung-order-edit-locked",
              "Produk katalog tidak tersedia"
            )
          );
        } else {
          const decrease = createButton(
            "−",
            "warung-order-edit-quantity"
          );

          decrease.setAttribute(
            "aria-label",
            "Kurangi jumlah"
          );

          decrease.disabled = item.quantity <= 1;

          decrease.addEventListener("click", () => {
            if (item.quantity > 1) {
              item.quantity -= 1;
              renderProducts();
            }
          });

          const quantity = createElement(
            "strong",
            "warung-order-edit-quantity-value",
            item.quantity
          );

          const increase = createButton(
            "+",
            "warung-order-edit-quantity is-add"
          );

          increase.setAttribute(
            "aria-label",
            "Tambah jumlah"
          );

          increase.disabled = item.quantity >= 99;

          increase.addEventListener("click", () => {
            if (item.quantity < 99) {
              item.quantity += 1;
              renderProducts();
            }
          });

          const remove = createButton(
            "",
            "warung-order-edit-remove"
          );

          remove.setAttribute(
            "aria-label",
            "Hapus item"
          );

          remove.title = "Hapus item";

          remove.innerHTML = `
            <svg
              viewBox="0 0 24 24"
              width="19"
              height="19"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M3 6h18"/>
              <path d="M8 6V4h8v2"/>
              <path d="M19 6l-1 14H6L5 6"/>
              <path d="M10 11v5"/>
              <path d="M14 11v5"/>
            </svg>
          `;

          remove.addEventListener("click", () => {
            items.splice(index, 1);
            renderProducts();
          });

          controls.append(
            decrease,
            quantity,
            increase,
            remove
          );
        }

        row.append(image, info, controls);
        productList.append(row);
      });

      renderTotal();
    }

    function renderPicker() {
      const query = search.value
        .trim()
        .toLocaleLowerCase("id-ID");

      pickerList.replaceChildren();

      const filtered = (catalogProducts || []).filter((product) => {
        const name = product.name.toLocaleLowerCase("id-ID");
        const variant = product.variant.toLocaleLowerCase("id-ID");

        return product.available
          && (name.includes(query) || variant.includes(query));
      });

      if (!filtered.length) {
        pickerList.append(
          createElement(
            "p",
            "warung-order-edit-empty",
            "Produk tidak ditemukan atau tidak tersedia."
          )
        );

        return;
      }

      filtered.forEach((product) => {
        const button = createButton(
          "",
          "warung-order-edit-picker-product"
        );

        const name = createElement(
          "strong",
          "",
          product.name
        );

        const detail = createElement(
          "small",
          "",
          `${product.variant ? `${product.variant} · ` : ""}${formatRupiah(product.price)}`
        );

        const addIcon = createElement(
          "span",
          "warung-order-edit-picker-add",
          "+"
        );

        button.append(name, detail, addIcon);

        button.addEventListener("click", () => {
          const existing = items.find(
            (item) => item.productId === product.id
          );

          if (existing) {
            if (existing.quantity >= 99) {
              notify("Jumlah maksimal produk adalah 99.");
              return;
            }

            existing.quantity += 1;
          } else {
            items.push({
              productId: product.id,
              name: product.name,
              variant: product.variant,
              price: product.price,
              quantity: 1,
              image: product.image,
              locked: false,
            });
          }

          renderProducts();
        });

        pickerList.append(button);
      });
    }

    async function showPicker() {
      picker.hidden = !picker.hidden;

      if (picker.hidden) {
        return;
      }

      pickerList.replaceChildren(
        createElement(
          "p",
          "warung-order-edit-empty",
          "Memuat produk..."
        )
      );

      try {
        await loadCatalog();
        renderPicker();
        search.focus();
      } catch (error) {
        pickerList.replaceChildren(
          createElement(
            "p",
            "warung-order-edit-error",
            error.message || "Katalog gagal dimuat."
          )
        );
      }
    }

    addButton.addEventListener("click", showPicker);
    search.addEventListener("input", renderPicker);

    activeEscapeHandler = (event) => {
      if (event.key === "Escape" && activeOverlay === overlay) {
        closeEditor();
      }
    };
    
    function handleBackdrop(event) {
      if (event.target === overlay) {
        closeEditor();
      }
    }
    
    closeButton.addEventListener("click", closeEditor);
    cancelButton.addEventListener("click", closeEditor);
    overlay.addEventListener("click", handleBackdrop);
    
    document.addEventListener(
      "keydown",
      activeEscapeHandler
    );

    document.body.append(overlay);
    document.body.classList.add("warung-order-edit-open");

    renderProducts();

    // Simpan perubahan
    modal.addEventListener("submit", async (event) => {
      event.preventDefault();

      const updatedItems = items
        .filter((item) => !item.locked)
        .map((item) => ({
          product_id: item.productId,
          quantity: item.quantity,
        }));

      if (!updatedItems.length && !items.some((item) => item.locked)) {
        notify("Pesanan harus memiliki minimal satu produk.");
        return;
      }

      saveButton.disabled = true;
      saveButton.textContent = "Menyimpan...";

      try {
        await postJSON(API.editOrder, {
          id: Number(order.id),
          notes: noteInput.value.trim(),
          items: updatedItems,
        });

        closeEditor();

        notify("Pesanan berhasil diperbarui.");

        await reloadOrders();
      } catch (error) {
        notify(
          error instanceof Error
            ? error.message
            : "Pesanan gagal diperbarui."
        );

        saveButton.disabled = false;
        saveButton.textContent = "Simpan Perubahan";
      }
    });
  }

  document.addEventListener("warung:edit-order", (event) => {
    const order = event.detail?.order;

    if (!order) {
      notify("Data pesanan tidak ditemukan.");
      return;
    }

    openEditor(order);
  });
}
