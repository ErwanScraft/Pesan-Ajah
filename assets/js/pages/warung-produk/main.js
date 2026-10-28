/**
 * Logika khusus halaman pengelolaan produk warung.
 *
 * API request, notifikasi, format harga, dan helper tombol tetap
 * menggunakan implementasi bersama dari dashboard.js.
 */
export async function initWarungProducts({
  $,
  API,
  apiRequest,
  postJSON,
  notify,
  formatRupiah,
  createIconButton,
  resolveImageUrl,
  csrfToken,
}) {
  let products = [];
  let editingProductId = null;

  const productImageInput = $("#product-image-input");
  const productImagePreview = $("#product-image-preview-img");
  const productImagePlaceholder = $("#product-image-placeholder");
  const removeProductImageButton = $("#remove-product-image");
  const removeProductImageOption = $("#remove-product-image-option");
  const removeProductImageCheckbox = $("#remove-product-image-checkbox");

  let currentProductImage = null;
  let selectedProductImage = null;

  function renderProductImagePreview(imageUrl = null) {
    if (!productImagePreview || !productImagePlaceholder) {
      return;
    }

    const source = resolveImageUrl(imageUrl);

    if (!source) {
      productImagePreview.removeAttribute("src");
      productImagePreview.hidden = true;
      productImagePlaceholder.hidden = false;
      return;
    }

    productImagePreview.hidden = false;
    productImagePlaceholder.hidden = true;

    productImagePreview.onerror = () => {
      productImagePreview.removeAttribute("src");
      productImagePreview.hidden = true;
      productImagePlaceholder.hidden = false;
    };

    productImagePreview.src = source;
  }

  function resetProductImageState() {
    currentProductImage = null;
    selectedProductImage = null;

    if (productImageInput) {
      productImageInput.value = "";
    }

    if (removeProductImageCheckbox) {
      removeProductImageCheckbox.checked = false;
    }

    if (removeProductImageButton) {
      removeProductImageButton.hidden = true;
    }

    if (removeProductImageOption) {
      removeProductImageOption.hidden = true;
    }

    renderProductImagePreview();
  }

  productImageInput?.addEventListener("change", () => {
    const file = productImageInput.files?.[0];

    if (!file) {
      selectedProductImage = null;
      renderProductImagePreview(currentProductImage);
      return;
    }

    const allowedTypes = [
      "image/jpeg",
      "image/png",
      "image/webp",
    ];

    if (!allowedTypes.includes(file.type)) {
      productImageInput.value = "";
      selectedProductImage = null;
      renderProductImagePreview(currentProductImage);
      notify("Format foto harus JPG, PNG, atau WebP.");
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      productImageInput.value = "";
      selectedProductImage = null;
      renderProductImagePreview(currentProductImage);
      notify("Ukuran foto maksimal 5 MB.");
      return;
    }

    selectedProductImage = file;

    const reader = new FileReader();

    reader.onload = () => {
      if (typeof reader.result === "string") {
        renderProductImagePreview(reader.result);
      }
    };

    reader.onerror = () => {
      notify("Foto tidak dapat dibaca.");
    };

    reader.readAsDataURL(file);

    if (removeProductImageCheckbox) {
      removeProductImageCheckbox.checked = false;
    }
  });

  removeProductImageButton?.addEventListener("click", () => {
    selectedProductImage = null;

    if (productImageInput) {
      productImageInput.value = "";
    }

    if (currentProductImage && removeProductImageCheckbox) {
      removeProductImageCheckbox.checked = true;
    }

    renderProductImagePreview();
  });

  removeProductImageCheckbox?.addEventListener("change", () => {
    if (removeProductImageCheckbox.checked) {
      selectedProductImage = null;

      if (productImageInput) {
        productImageInput.value = "";
      }

      renderProductImagePreview();
    } else {
      renderProductImagePreview(
        selectedProductImage ? null : currentProductImage,
      );
    }
  });

  const productForm = $("#product-form");
  const productList = $("#manage-products");
  const productModal = $("#product-editor-modal");
  const addProductButton = $("#add-product-button");

  let productModalPreviousFocus = null;

  function openProductModal() {
    if (!productModal || !productForm) {
      return;
    }

    productModalPreviousFocus = document.activeElement;

    productModal.hidden = false;
    document.body.classList.add("products-modal-open");

    window.requestAnimationFrame(() => {
      productForm.querySelector('input[name="name"]')?.focus();
    });
  }

  function closeProductModal() {
    if (!productModal || productModal.hidden) {
      return;
    }

    productModal.hidden = true;
    document.body.classList.remove("products-modal-open");

    if (productModalPreviousFocus instanceof HTMLElement) {
      productModalPreviousFocus.focus();
    }
  }

  addProductButton?.addEventListener("click", () => {
    resetProductForm();
    openProductModal();
  });

  productModal
    ?.querySelectorAll("[data-close-product-modal]")
    .forEach((button) => {
      button.addEventListener("click", closeProductModal);
    });

  productModal?.addEventListener("click", (event) => {
    if (event.target === productModal) {
      closeProductModal();
    }
  });

  async function loadProducts() {
    const data = await apiRequest(API.products);

    products = data.products.map((product) => ({
      ...product,
      id: Number(product.id),
      price: Number(product.price),
      available: Boolean(Number(product.is_available)),
    }));

    renderProducts();
  }

  function renderProducts() {
    if (!productList) return;

    productList.replaceChildren();

    const count = $("#product-count");

    if (count) {
      count.textContent = `${products.length} produk`;
    }

    if (products.length === 0) {
      const empty = document.createElement("p");

      empty.className = "product-empty";
      empty.textContent = "Belum ada produk.";

      productList.append(empty);
      return;
    }

    const categoryMap = new Map();

    for (const product of products) {
      const category =
        String(product.category || "").trim() || "Lainnya";

      if (!categoryMap.has(category)) {
        categoryMap.set(category, []);
      }

      categoryMap.get(category).push(product);
    }

    function readCategoryOrder() {
      try {
        const parsed = JSON.parse(
          document.body.dataset.categoryOrder || "[]",
        );

        return Array.isArray(parsed)
          ? parsed.filter(
              (category) => typeof category === "string",
            )
          : [];
      } catch {
        return [];
      }
    }

    const categoryRank = new Map(
      readCategoryOrder().map((category, index) => [
        category,
        index,
      ]),
    );

    function compareCategories(a, b) {
      const rankA = categoryRank.get(a);
      const rankB = categoryRank.get(b);

      if (rankA !== undefined && rankB !== undefined) {
        return rankA - rankB;
      }

      if (rankA !== undefined) return -1;
      if (rankB !== undefined) return 1;

      return a.localeCompare(b, "id-ID");
    }

    const sortedCategories = [...categoryMap.keys()].sort(
      compareCategories,
    );

    function createProductThumbnail(product) {
      const thumbnail = document.createElement("div");
      thumbnail.className = "manage-product-thumbnail";
    
      const placeholder = document.createElement("span");
      placeholder.className = "manage-product-image-placeholder";
      placeholder.textContent = "Tanpa foto";
    
      const image = document.createElement("img");
    
      image.alt = product.name
        ? `Foto ${product.name}`
        : "Foto produk";
    
      image.loading = "lazy";
      image.decoding = "async";
      image.hidden = true;
    
      thumbnail.append(image, placeholder);
    
      function showPlaceholder() {
        image.hidden = true;
        image.removeAttribute("src");
        placeholder.hidden = false;
      }
    
      // Ambil URL melalui helper yang sudah digunakan aplikasi.
      const imageUrl = resolveImageUrl(product.image);
    
      if (!imageUrl) {
        showPlaceholder();
        return thumbnail;
      }
    
      // Tampilkan gambar setelah berhasil dimuat.
      image.addEventListener(
        "load",
        () => {
          image.hidden = false;
          placeholder.hidden = true;
        },
        { once: true },
      );
    
      // Jika URL gambar gagal dimuat, tampilkan placeholder.
      image.addEventListener(
        "error",
        showPlaceholder,
        { once: true },
      );
    
      // Pasang URL setelah event listener terdaftar.
      image.src = imageUrl;
    
      // Menangani gambar yang sudah selesai dimuat dari cache.
      if (image.complete && image.naturalWidth > 0) {
        image.hidden = false;
        placeholder.hidden = true;
      }
    
      return thumbnail;
    }

    function createProductInfo(product) {
      const info = document.createElement("div");
      info.className = "manage-product";

      const details = document.createElement("span");
      const name = document.createElement("strong");

      name.textContent = product.name || "Produk tanpa nama";

      const variant = document.createElement("small");
      variant.className = "manage-product-variant";
      variant.textContent = product.variant || "";
      variant.hidden = !product.variant;

      const metadata = document.createElement("small");
      metadata.textContent =
        `${product.unit || "-"} · ${formatRupiah(product.price)}`;

      details.append(name, variant, metadata);

      info.append(createProductThumbnail(product), details);

      return info;
    }

    function renderAvailabilityButton(button, product) {
      const available = product.available;

      button.classList.toggle("is-available", available);
      button.classList.toggle("is-unavailable", !available);

      button.setAttribute(
        "aria-label",
        available
          ? `Tandai ${product.name} sebagai habis`
          : `Tandai ${product.name} sebagai tersedia`,
      );

      button.title = available
        ? "Tersedia — klik untuk menandai habis"
        : "Habis — klik untuk mengaktifkan";

      button.innerHTML = available
        ? `
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <circle
              cx="12" cy="12" r="9"
              fill="none" stroke="currentColor" stroke-width="1.8"
            />
            <path
              d="m8 12 2.5 2.5L16.5 8"
              fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round"
            />
          </svg>
        `
        : `
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <circle
              cx="12" cy="12" r="9"
              fill="none" stroke="currentColor" stroke-width="1.8"
            />
            <path
              d="m9 9 6 6m0-6-6 6"
              fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round"
            />
          </svg>
        `;
    }

    async function updateAvailability(product, button) {
      if (button.disabled) return;

      const previousValue = product.available;
      const nextValue = !previousValue;

      button.disabled = true;
      button.classList.add("is-saving");
      button.title = "Menyimpan perubahan...";
      button.setAttribute(
        "aria-label",
        `Menyimpan status produk ${product.name}`,
      );

      try {
        await postJSON(API.products, {
          id: product.id,
          name: product.name,
          variant: product.variant || "",
          category: product.category || "Lainnya",
          description: product.description || "",
          price: product.price,
          unit: product.unit,
          tag: product.tag || "",
          available: nextValue,
        });

        product.available = nextValue;
        renderAvailabilityButton(button, product);

        notify(
          nextValue
            ? "Produk berhasil diaktifkan."
            : "Produk ditandai habis.",
        );

        await loadProducts();
      } catch (error) {
        product.available = previousValue;
        renderAvailabilityButton(button, product);

        notify(
          error instanceof Error
            ? error.message
            : "Status produk gagal diperbarui.",
        );
      } finally {
        button.disabled = false;
        button.classList.remove("is-saving");
      }
    }

    function createProductRow(product) {
      const row = document.createElement("article");
      row.className = "manage-row";
      row.dataset.productId = String(product.id);

      const info = createProductInfo(product);
      const actions = document.createElement("div");
      actions.className = "manage-actions";

      const availabilityButton = document.createElement("button");
      availabilityButton.type = "button";
      availabilityButton.className = "availability-icon";

      renderAvailabilityButton(availabilityButton, product);

      availabilityButton.addEventListener("click", () => {
        updateAvailability(product, availabilityButton);
      });

      const editButton = createIconButton(
        `Edit ${product.name}`,
        "edit",
        () => editProduct(product.id),
      );

      const deleteButton = createIconButton(
        `Hapus ${product.name}`,
        "delete",
        () => deleteProduct(product.id),
        "danger-action",
      );

      actions.append(
        availabilityButton,
        editButton,
        deleteButton,
      );

      row.append(info, actions);

      return row;
    }

    for (const category of sortedCategories) {
      const categoryProducts = categoryMap.get(category);

      if (!categoryProducts?.length) continue;

      const section = document.createElement("section");
      section.className = "product-category-group";
      section.setAttribute("aria-label", `Kategori ${category}`);

      const header = document.createElement("header");
      header.className = "product-category-heading";

      const title = document.createElement("h4");
      title.textContent = category;

      const categoryCount = document.createElement("span");
      categoryCount.className = "product-category-count";
      categoryCount.textContent =
        `${categoryProducts.length} produk`;

      header.append(title, categoryCount);

      const card = document.createElement("div");
      card.className = "product-category-card";

      for (const product of categoryProducts) {
        card.append(createProductRow(product));
      }

      section.append(header, card);
      productList.append(section);
    }
  }

  function resetProductForm() {
    if (!productForm) return;

    productForm.reset();

    if (productForm.elements.unit_quantity) {
      productForm.elements.unit_quantity.value = "1";
    }

    editingProductId = null;

    resetProductImageState();

    const title = $("#product-form-title");
    const submit = productForm.querySelector('[type="submit"]');
    const cancel = $("#cancel-product");

    if (title) {
      title.textContent = "Tambah produk baru";
    }

    if (submit) {
      submit.textContent = "Simpan produk";
    }

    if (cancel) {
      cancel.hidden = true;
    }

    closeProductModal();
  }

  function parseProductUnit(unitValue) {
    const value = String(unitValue || "").trim();

    const match = value.match(/^(\d+(?:[.,]\d+)?)\s+(.+)$/);

    if (match) {
      return {
        quantity: match[1].replace(",", "."),
        name: match[2].trim(),
      };
    }

    return {
      quantity: "1",
      name: value || "pcs",
    };
  }

  function setProductUnitFields(unitValue) {
    const quantityInput = productForm?.elements.unit_quantity;
    const unitSelect = productForm?.elements.unit_name;

    if (!quantityInput || !unitSelect) return;

    const parsed = parseProductUnit(unitValue);
    const unitName = parsed.name;

    quantityInput.value = parsed.quantity;

    const existingOption = Array.from(unitSelect.options).find(
      (option) =>
        option.value.toLowerCase() === unitName.toLowerCase(),
    );

    if (!existingOption) {
      const legacyOption = document.createElement("option");
      legacyOption.value = unitName;
      legacyOption.textContent = unitName;
      unitSelect.add(legacyOption);
    }

    unitSelect.value = existingOption
      ? existingOption.value
      : unitName;
  }

  function editProduct(id) {
    const product = products.find((item) => item.id === id);

    if (!product || !productForm) return;

    editingProductId = product.id;

    productForm.elements.name.value = product.name;

    if (productForm.elements.variant) {
      productForm.elements.variant.value = product.variant || "";
    }

    productForm.elements.category.value = product.category;
    productForm.elements.price.value = String(product.price);

    setProductUnitFields(product.unit);

    if (productForm.elements.tag) {
      productForm.elements.tag.value = product.tag || "";
    }

    currentProductImage = product.image || null;
    selectedProductImage = null;

    if (productImageInput) {
      productImageInput.value = "";
    }

    if (removeProductImageCheckbox) {
      removeProductImageCheckbox.checked = false;
    }

    renderProductImagePreview(currentProductImage);

    if (removeProductImageButton) {
      removeProductImageButton.hidden = !currentProductImage;
    }

    if (removeProductImageOption) {
      removeProductImageOption.hidden = !currentProductImage;
    }

    productForm.elements.available.checked = product.available;

    const description = productForm.elements.description;

    if (description) {
      description.value = product.description || "";
    }

    $("#product-form-title").textContent = "Edit produk";

    productForm.querySelector('[type="submit"]').textContent =
      "Simpan perubahan";

    $("#cancel-product").hidden = false;

    openProductModal();
  }

  async function deleteProduct(id) {
    const product = products.find((item) => item.id === id);

    if (!product) return;

    if (
      !window.confirm(
        `Hapus produk "${product.name}" dari katalog?`,
      )
    ) {
      return;
    }

    try {
      await postJSON(API.products, { id }, "DELETE");

      await loadProducts();
      resetProductForm();

      notify("Produk berhasil dihapus.");
    } catch (error) {
      notify(error.message);
    }
  }

  if (productForm) {
    productForm.addEventListener("submit", async (event) => {
      event.preventDefault();

      const wasEditing = editingProductId !== null;
      const formData = new FormData(productForm);

      const payload = {
        name: String(formData.get("name") || "").trim(),
        variant: String(formData.get("variant") || "").trim(),
        category: String(formData.get("category") || "").trim(),
        price: Number(formData.get("price")),
        unitQuantity: Number(formData.get("unit_quantity")),
        unitName: String(formData.get("unit_name") || "").trim(),
        tag: String(formData.get("tag") || "").trim(),
        description: String(
          formData.get("description") || "",
        ).trim(),
        available: formData.has("available"),
      };

      payload.unit = `${payload.unitQuantity} ${payload.unitName}`;

      if (
        !payload.name ||
        !payload.category ||
        !payload.unitName ||
        !Number.isFinite(payload.unitQuantity) ||
        payload.unitQuantity <= 0 ||
        payload.unitQuantity > 999999 ||
        !Number.isSafeInteger(payload.price) ||
        payload.price < 1
      ) {
        notify("Periksa kembali informasi produk.");
        return;
      }

      const requestData = new FormData();

      requestData.set("name", payload.name);
      requestData.set("variant", payload.variant);
      requestData.set("category", payload.category);
      requestData.set("price", String(payload.price));
      requestData.set("unit", payload.unit);
      requestData.set(
        "unit_quantity",
        String(payload.unitQuantity),
      );
      requestData.set("unit_name", payload.unitName);
      requestData.set("tag", payload.tag);
      requestData.set("description", payload.description);
      requestData.set("available", String(payload.available));

      if (editingProductId !== null) {
        requestData.set("id", String(editingProductId));
      }

      if (selectedProductImage) {
        requestData.set("image", selectedProductImage);
      }

      if (
        removeProductImageCheckbox?.checked &&
        !selectedProductImage
      ) {
        requestData.set("remove_image", "1");
      }

      const submitButton = productForm.querySelector(
        '[type="submit"]',
      );

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = "Menyimpan...";
      }

      try {
        let response;

        try {
          response = await fetch(API.products, {
            method: "POST",
            credentials: "same-origin",
            cache: "no-store",
            headers: {
              Accept: "application/json",
              "X-CSRF-Token": csrfToken,
            },
            body: requestData,
          });
        } catch (error) {
          console.error("Gagal menghubungi API produk:", error);

          throw new Error(
            "Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.",
          );
        }

        // Baca respons sebagai teks terlebih dahulu agar error PHP
        // yang menghasilkan HTML tidak disamarkan menjadi JSON error.
        const responseText = await response.text();
        let data = null;

        try {
          data = responseText
            ? JSON.parse(responseText)
            : null;
        } catch {
          data = null;
        }

        if (response.status === 401) {
          window.location.href = "/warung/login.php";
          return;
        }

        if (!data || typeof data !== "object") {
          console.error(
            "API produk mengembalikan respons non-JSON:",
            response.status,
            responseText.slice(0, 1000),
          );

          throw new Error(
            response.status >= 500
              ? `Server gagal menyimpan produk (HTTP ${response.status}). Periksa error log PHP di hosting.`
              : `Respons server tidak valid (HTTP ${response.status}). Muat ulang halaman dan coba lagi.`,
          );
        }

        if (!response.ok || data.success === false) {
          throw new Error(
            data.message ||
              `Produk gagal disimpan (HTTP ${response.status}).`,
          );
        }

        await loadProducts();
        resetProductForm();

        notify(
          wasEditing
            ? "Produk berhasil diperbarui."
            : "Produk berhasil ditambahkan.",
        );
      } catch (error) {
        console.error("Proses penyimpanan produk gagal:", error);

        notify(
          error instanceof Error
            ? error.message
            : "Produk gagal disimpan.",
        );
      } finally {
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.textContent =
            editingProductId !== null
              ? "Simpan perubahan"
              : "Simpan produk";
        }
      }
    });
  }

  $("#cancel-product")?.addEventListener(
    "click",
    resetProductForm,
  );

  await loadProducts();
}
