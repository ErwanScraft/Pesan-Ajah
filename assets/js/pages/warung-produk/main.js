/**
 * Logika khusus halaman pengelolaan produk warung.
 *
 * API request, notifikasi, format harga, dan helper tombol tetap
 * menggunakan implementasi bersama dari dashboard.js.
 */
import { captureError } from "../../core/error-logger.js";
import { createBottomSheet } from "../../modules/bottom-sheet.js";
 
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
  const uploadProductImageButton = $(
    "#upload-product-image"
  );
  
  const cameraProductImageButton = $(
    "#camera-product-image"
  );
  
  uploadProductImageButton?.addEventListener("click", () => {
    if (!productImageInput) return;
  
    productImageInput.removeAttribute("capture");
    productImageInput.click();
  });
  
  cameraProductImageButton?.addEventListener("click", () => {
    if (!productImageInput) return;
  
    productImageInput.setAttribute(
      "capture",
      "environment"
    );
  
    productImageInput.click();
  });
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
    productImageInput.removeAttribute("capture");
    
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
  const addProductButton = $("#add-product-button");
  const productSearchInput = $("#product-search");
  const productStatusFilter = $("#product-status-filter");
  const bottomSheet = createBottomSheet();
  const productFormHost = $("#product-form-host");

  function openProductSheet() {
    if (!productForm) return;
  
    bottomSheet.open({
      title: editingProductId !== null
        ? "Edit Item"
        : "Tambah Item",
  
      content: productForm,
      size: "large",
  
      onClose: () => {
        productFormHost?.append(productForm);
      },
    });
  }
  
  addProductButton?.addEventListener("click", () => {
    resetProductForm();
    openProductSheet();
  });
  
  function openStockSheet(product) {
    const content = document.createElement("form");
    content.className = "product-stock-sheet";
  
    let isUnlimited = product.stock_quantity === null;
    let isAvailable = product.available;
  
    const quantityInput = document.createElement("input");
  
    quantityInput.type = "number";
    quantityInput.className = "product-stock-input";
    quantityInput.min = "0";
    quantityInput.max = "2147483647";
    quantityInput.step = "1";
    quantityInput.inputMode = "numeric";
    quantityInput.value = isUnlimited
      ? ""
      : String(product.stock_quantity ?? 0);
  
    quantityInput.setAttribute(
      "aria-label",
      "Jumlah stok produk",
    );
  
    const stepper = document.createElement("div");
    stepper.className = "product-stock-stepper";
  
    const decreaseButton = document.createElement("button");
    decreaseButton.type = "button";
    decreaseButton.className = "product-stock-step";
    decreaseButton.textContent = "−";
    decreaseButton.setAttribute("aria-label", "Kurangi stok");
  
    const increaseButton = document.createElement("button");
    increaseButton.type = "button";
    increaseButton.className = "product-stock-step";
    increaseButton.textContent = "+";
    increaseButton.setAttribute("aria-label", "Tambah stok");
  
    stepper.append(
      decreaseButton,
      quantityInput,
      increaseButton,
    );
  
    const options = document.createElement("div");
    options.className = "product-stock-options";
  
    const unlimitedButton = document.createElement("button");
    unlimitedButton.type = "button";
    unlimitedButton.className = "product-stock-option";
    unlimitedButton.textContent = "∞ Unlimited";
  
    const availabilityButton = document.createElement("button");
    availabilityButton.type = "button";
    availabilityButton.className = "product-stock-option";
  
    const submitButton = document.createElement("button");
    submitButton.type = "submit";
    submitButton.className = "button button-primary product-stock-submit";
    submitButton.textContent = "Terapkan";
  
    options.append(
      unlimitedButton,
      availabilityButton,
    );
  
    content.append(
      stepper,
      options,
      submitButton,
    );
  
    function renderControls() {
      quantityInput.disabled = isUnlimited;
      decreaseButton.disabled = isUnlimited;
      increaseButton.disabled = isUnlimited;
  
      unlimitedButton.classList.toggle(
        "active",
        isUnlimited,
      );
  
      unlimitedButton.setAttribute(
        "aria-pressed",
        String(isUnlimited),
      );
  
      availabilityButton.classList.toggle(
        "active",
        isAvailable,
      );
  
      availabilityButton.setAttribute(
        "aria-pressed",
        String(isAvailable),
      );
  
      availabilityButton.textContent = isAvailable
        ? "✓ Tersedia"
        : "Habis";
    }
  
    unlimitedButton.addEventListener("click", () => {
      isUnlimited = !isUnlimited;
  
      if (!isUnlimited && !quantityInput.value) {
        quantityInput.value = "1";
      }
  
      renderControls();
    });
  
    availabilityButton.addEventListener("click", () => {
      isAvailable = !isAvailable;
      renderControls();
    });
  
    decreaseButton.addEventListener("click", () => {
      const quantity = Number(quantityInput.value || 0);
  
      quantityInput.value = String(
        Math.max(0, quantity - 1),
      );
    });
  
    increaseButton.addEventListener("click", () => {
      const quantity = Number(quantityInput.value || 0);
  
      if (quantity >= 2147483647) {
        return;
      }
  
      quantityInput.value = String(quantity + 1);
    });
  
    content.addEventListener("submit", async (event) => {
      event.preventDefault();
  
      let quantity = null;
  
      if (!isUnlimited) {
        const value = quantityInput.value.trim();
  
        if (!/^\d+$/.test(value)) {
          notify("Masukkan jumlah stok yang valid.", "error");
          quantityInput.focus();
          return;
        }
  
        quantity = Number(value);
  
        if (
          !Number.isSafeInteger(quantity)
          || quantity < 0
          || quantity > 2147483647
        ) {
          notify("Jumlah stok berada di luar batas.", "error");
          quantityInput.focus();
          return;
        }
      }
  
      submitButton.disabled = true;
      submitButton.textContent = "Menyimpan...";
  
      try {
        const result = await postJSON(API.stock, {
          product_id: product.id,
          action: "configure",
          quantity,
          unlimited: isUnlimited,
          is_available: isAvailable,
        });
  
        product.stock_quantity = result.stock_quantity;
        product.available = result.is_available;
  
        bottomSheet.close();
  
        await loadProducts();
  
        notify(result.message, "success");
      } catch (error) {
        notify(
          error instanceof Error
            ? error.message
            : "Stok produk gagal diperbarui.",
          "error",
        );
  
        submitButton.disabled = false;
        submitButton.textContent = "Terapkan";
      }
    });
  
    renderControls();
  
    bottomSheet.open({
      title: "Atur Stok",
      content,
      size: "auto",
    });
  }

  function renderProducts() {
    if (!productList) return;

    productList.replaceChildren();

    const count = $("#product-count");

    const searchTerm = String(productSearchInput?.value || "")
      .trim()
      .toLocaleLowerCase("id-ID");

    const statusFilter = productStatusFilter?.value || "all";

    const visibleProducts = products.filter((product) => {
      const searchableText = [
        product.name,
        product.variant,
        product.category,
      ]
        .filter(Boolean)
        .join(" ")
        .toLocaleLowerCase("id-ID");

      const matchesSearch =
        !searchTerm || searchableText.includes(searchTerm);

      const matchesStatus =
        statusFilter === "all" ||
        (statusFilter === "available" && product.available) ||
        (statusFilter === "unavailable" && !product.available);

      return matchesSearch && matchesStatus;
    });

    if (count) {
      count.textContent =
        searchTerm || statusFilter !== "all"
          ? `${visibleProducts.length} dari ${products.length} produk`
          : `${products.length} produk`;
    }

    if (products.length === 0) {
      const empty = document.createElement("p");
      empty.className = "product-empty";
      empty.textContent = "Belum ada produk.";

      productList.append(empty);
      return;
    }

    if (visibleProducts.length === 0) {
      const empty = document.createElement("p");
      empty.className = "product-empty";
      empty.textContent =
        "Tidak ada produk yang sesuai dengan pencarian.";

      productList.append(empty);
      return;
    }

    const categoryMap = new Map();

    for (const product of visibleProducts) {
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
    
      // Jangan gunakan lazy loading untuk gambar yang awalnya hidden.
      image.loading = "eager";
      image.decoding = "async";
      image.hidden = true;
    
      thumbnail.append(image, placeholder);
    
      function showImage() {
        if (image.naturalWidth > 0) {
          image.hidden = false;
          placeholder.hidden = true;
        }
      }
    
      function showPlaceholder() {
        image.hidden = true;
        placeholder.hidden = false;
      }
    
      const imageUrl = resolveImageUrl(product.image);
    
      if (!imageUrl) {
        showPlaceholder();
        return thumbnail;
      }
    
      // Pasang listener sebelum menetapkan src.
      image.addEventListener("load", showImage);
    
      image.addEventListener("error", showPlaceholder, {
        once: true,
      });
    
      image.src = imageUrl;
    
      // Tangani gambar yang sudah tersedia dari browser cache.
      if (image.complete) {
        if (image.naturalWidth > 0) {
          showImage();
        } else {
          showPlaceholder();
        }
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

    function renderStockButton(button, product) {
      button.classList.toggle(
        "is-available",
        product.available,
      );
    
      button.classList.toggle(
        "is-unavailable",
        !product.available,
      );
    
      button.setAttribute(
        "aria-label",
        `Atur stok ${product.name}`,
      );
    
      button.title = "Atur stok";
    
      button.innerHTML = `
        <svg
          viewBox="0 0 24 24"
          aria-hidden="true"
          focusable="false"
        >
          <path
            d="m3 7 9-4 9 4-9 4-9-4Z"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linejoin="round"
          />
          <path
            d="M3 7v10l9 4 9-4V7M12 11v10"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linejoin="round"
          />
        </svg>
      `;
    }

    function createProductRow(product) {
      const row = document.createElement("article");
      row.className = "manage-row";
      row.dataset.productId = String(product.id);

      const info = createProductInfo(product);
      const actions = document.createElement("div");
      actions.className = "manage-actions";

      const stockButton = document.createElement("button");
      
      stockButton.type = "button";
      stockButton.className = "stock-icon-button";
      
      renderStockButton(stockButton, product);
      
      stockButton.addEventListener("click", () => {
        openStockSheet(product);
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
        stockButton,
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

  productSearchInput?.addEventListener("input", renderProducts);
  productStatusFilter?.addEventListener("change", renderProducts);
  
  async function loadProducts() {
    const data = await apiRequest(API.products);

    products = data.products.map((product) => ({
      ...product,
      id: Number(product.id),
      price: Number(product.price),
    
      stock_quantity:
        product.stock_quantity == null
          ? null
          : Math.max(0, Number(product.stock_quantity) || 0),
    
      available: Boolean(Number(product.is_available)),
    }));

    renderProducts();
  }

  function resetProductForm() {
    if (!productForm) return;

    productForm.reset();

    editingProductId = null;

    resetProductImageState();

    if (
      bottomSheet.isOpen() &&
      !productFormHost?.contains(productForm)
    ) {
      bottomSheet.close();
    }
  }

  function setProductUnitField(unitValue) {
      if (!productForm?.elements.unit) return;
      productForm.elements.unit.value = String(unitValue || "");
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

    setProductUnitField(product.unit);

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

    openProductSheet();
  }

  async function deleteProduct(id) {
    const product = products.find(
      (item) => Number(item.id) === Number(id),
    );
  
    if (!product) {
      notify("Produk tidak ditemukan.");
      return;
    }
  
    if (
      !window.confirm(
        `Hapus produk "${product.name}" dari katalog?`,
      )
    ) {
      return;
    }
  
    try {
      await postJSON(API.products, {
        action: "delete",
        id: Number(product.id),
      });
  
      await loadProducts();
      resetProductForm();
  
      notify("Produk berhasil dihapus.");
    } catch (error) {
      notify(
        error instanceof Error
          ? error.message
          : "Produk gagal dihapus.",
      );
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
          unit: String(formData.get("unit") || "").trim(),
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
          !payload.unit ||
          payload.unit.length > 30 ||
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
          captureError(error, {
            type: "api",
            source: API.products,
          });
        
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
          
          captureError(
            new Error(
              `API produk mengembalikan respons non-JSON (HTTP ${response.status}).`,
            ),
            {
              type: "api",
              source: API.products,
              http_status: response.status,
            },
          );

          throw new Error(
            response.status >= 500
              ? `Server gagal menyimpan produk (HTTP ${response.status}). Periksa error log PHP di hosting.`
              : `Respons server tidak valid (HTTP ${response.status}). Muat ulang halaman dan coba lagi.`,
          );
        }

        if (!response.ok || data.success === false) {
        
          captureError(
            new Error(
              data.message ||
                `Produk gagal disimpan (HTTP ${response.status}).`,
            ),
            {
              type: "api",
              source: API.products,
              http_status: response.status,
            },
          );
        
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
          submitButton.textContent = "Simpan";
        }
      }
    });
  }

  await loadProducts();
}
