import { resolveImageUrl, setImageSource } from "./modules/image.js";

const $ = (selector) => document.querySelector(selector);

const page = document.body.dataset.page;
const csrfToken = document.body.dataset.csrfToken;

const API = {
  dashboard: "/warung/api/dashboard.php",
  products: "/warung/api/products.php",
  orders: "/warung/api/orders.php",
  settings: "/warung/api/settings.php",
};

async function apiRequest(url, options = {}) {
  const response = await fetch(url, {
    credentials: "same-origin",
    cache: "no-store",
    ...options,
    headers: {
      Accept: "application/json",
      ...(options.body
        ? { "Content-Type": "application/json" }
        : {}),
      ...(options.method && options.method !== "GET"
        ? { "X-CSRF-Token": csrfToken }
        : {}),
      ...options.headers,
    },
  });

  let data;

  try {
    data = await response.json();
  } catch {
    throw new Error("Respons server tidak valid.");
  }

  if (!response.ok || data.success === false) {
    if (response.status === 401) {
      window.location.href = "/warung/login.php";
    }

    throw new Error(
      data.message || "Permintaan gagal diproses.",
    );
  }

  return data;
}

async function postJSON(url, body, method = "POST") {
  return apiRequest(url, {
    method,
    body: JSON.stringify(body),
  });
}

function notify(message) {
  const toast = $("#toast");

  if (!toast) {
    window.alert(message);
    return;
  }

  toast.textContent = message;
  toast.classList.add("show");

  window.clearTimeout(notify.timeout);

  notify.timeout = window.setTimeout(() => {
    toast.classList.remove("show");
  }, 3000);
}

function formatRupiah(value) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
  }).format(Number(value) || 0);
}

function createCell(value, className = "") {
  const cell = document.createElement("td");

  cell.textContent = String(value ?? "");

  if (className) {
    cell.className = className;
  }

  return cell;
}

function createButton(text, callback, className = "table-action") {
  const button = document.createElement("button");

  button.type = "button";
  button.className = className;
  button.textContent = text;
  button.addEventListener("click", callback);

  return button;
}

/* =========================================================
   DASHBOARD STATISTIK
========================================================= */

async function loadDashboard() {
  const data = await apiRequest(API.dashboard);
  const stats = data.stats;

  const orders = $("#stat-orders");
  const pending = $("#stat-pending");
  const products = $("#stat-products");
  const status = $("#store-status-label");

  if (orders) {
    orders.textContent = String(stats.total_orders);
  }

  if (pending) {
    pending.textContent = String(stats.pending_orders);
  }

  if (products) {
    products.textContent = String(stats.available_products);
  }

  if (status) {
    status.textContent = stats.is_open ? "Buka" : "Tutup";
  }

  const revenue = $("#stat-revenue");

  if (revenue) {
    revenue.textContent = formatRupiah(
      stats.completed_revenue,
    );
  }

  const recent = $("#recent-orders");

  if (recent) {
    recent.replaceChildren();

    for (const order of data.recent_orders) {
      const row = document.createElement("tr");

      row.append(
        createCell(order.order_number, "order-id"),
        createCell(order.buyer_name),
        createCell(formatRupiah(order.total)),
        createCell(order.status),
      );

      recent.append(row);
    }
  }
}

/* =========================================================
   PRODUK
========================================================= */

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

  if (currentProductImage) {
    if (removeProductImageCheckbox) {
      removeProductImageCheckbox.checked = true;
    }

    renderProductImagePreview();
  } else {
    renderProductImagePreview();
  }
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
      selectedProductImage
        ? null
        : currentProductImage,
    );
  }
});

const productForm = $("#product-form");
const productList = $("#manage-products");

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

  /* -------------------------------------------------------
     PRODUK KOSONG
  ------------------------------------------------------- */

  if (products.length === 0) {
    const empty = document.createElement("p");

    empty.className = "product-empty";
    empty.textContent = "Belum ada produk.";

    productList.append(empty);
    return;
  }

  /* -------------------------------------------------------
     URUTAN KATEGORI
  ------------------------------------------------------- */

  const categoryOrder = [
    "Sembako",
    "Makanan",
    "Minuman",
    "Kebutuhan Rumah",
    "Lainnya",
  ];

  const categoryMap = new Map();

  for (const product of products) {
    const category =
      String(product.category || "").trim() || "Lainnya";

    if (!categoryMap.has(category)) {
      categoryMap.set(category, []);
    }

    categoryMap.get(category).push(product);
  }

  const sortedCategories = [
    ...categoryOrder.filter((category) =>
      categoryMap.has(category),
    ),

    ...[...categoryMap.keys()]
      .filter(
        (category) => !categoryOrder.includes(category),
      )
      .sort((a, b) => a.localeCompare(b, "id")),
  ];

  /* -------------------------------------------------------
     HELPER: THUMBNAIL PRODUK
  ------------------------------------------------------- */

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

    const imageUrl = resolveImageUrl(product.image);

    if (!imageUrl) {
      showPlaceholder();
      return thumbnail;
    }

    image.addEventListener(
      "load",
      () => {
        image.hidden = false;
        placeholder.hidden = true;
      },
      { once: true },
    );

    image.addEventListener(
      "error",
      showPlaceholder,
      { once: true },
    );

    image.src = imageUrl;

    return thumbnail;
  }

  /* -------------------------------------------------------
     HELPER: INFORMASI PRODUK
  ------------------------------------------------------- */

  function createProductInfo(product) {
    const info = document.createElement("div");

    info.className = "manage-product";

    const details = document.createElement("span");

    const name = document.createElement("strong");

    name.textContent = product.name || "Produk tanpa nama";

    const metadata = document.createElement("small");

    metadata.textContent =
      `${product.unit || "-"} · ${formatRupiah(product.price)}`;

    const status = document.createElement("small");

    status.className = product.available
      ? "product-stock-status is-available"
      : "product-stock-status is-unavailable";

    status.textContent = product.available
      ? "Stok tersedia"
      : "Stok habis";

    details.append(name, metadata, status);

    info.append(
      createProductThumbnail(product),
      details,
    );

    return info;
  }

  /* -------------------------------------------------------
     HELPER: TOGGLE KETERSEDIAAN
  ------------------------------------------------------- */

  async function updateAvailability(
    product,
    checkbox,
    statusText,
    availabilityText,
  ) {
    const previousValue = product.available;
    const nextValue = checkbox.checked;

    if (previousValue === nextValue) {
      return;
    }

    checkbox.disabled = true;

    availabilityText.textContent = "Menyimpan...";

    try {
      /*
       * Gunakan POST JSON.
       *
       * Endpoint products.php menganggap POST dengan ID
       * sebagai operasi pembaruan produk.
       *
       * Ini juga menghindari masalah server/cPanel yang
       * membatasi HTTP PUT.
       */
      await postJSON(API.products, {
        id: product.id,
        name: product.name,
        category: product.category || "Lainnya",
        description: product.description || "",
        price: product.price,
        unit: product.unit,
        available: nextValue,
      });

      /*
       * Jangan menganggap status tersimpan sebelum server
       * mengembalikan respons berhasil.
       */
      product.available = nextValue;

      availabilityText.textContent = nextValue
        ? "Tersedia"
        : "Habis";

      statusText.textContent = nextValue
        ? "Stok tersedia"
        : "Stok habis";

      statusText.classList.toggle(
        "is-available",
        nextValue,
      );

      statusText.classList.toggle(
        "is-unavailable",
        !nextValue,
      );

      notify(
        nextValue
          ? "Produk berhasil diaktifkan."
          : "Produk ditandai habis.",
      );

      /*
       * Muat ulang dari database untuk memastikan status
       * yang tampil sesuai dengan data server.
       */
      await loadProducts();
    } catch (error) {
      /*
       * Kembalikan checkbox ke status sebelumnya apabila
       * permintaan gagal.
       */
      checkbox.checked = previousValue;

      availabilityText.textContent = previousValue
        ? "Tersedia"
        : "Habis";

      notify(
        error instanceof Error
          ? error.message
          : "Status produk gagal diperbarui.",
      );
    } finally {
      checkbox.disabled = false;
    }
  }

  /* -------------------------------------------------------
     HELPER: CARD PRODUK
  ------------------------------------------------------- */

  function createProductRow(product) {
    const row = document.createElement("article");

    row.className = "manage-row";

    row.dataset.productId = String(product.id);

    const info = createProductInfo(product);

    const actions = document.createElement("div");

    actions.className = "manage-actions";

    /*
     * Toggle ketersediaan.
     */
    const availabilityLabel = document.createElement("label");

    availabilityLabel.className = "availability-control";

    const checkbox = document.createElement("input");

    checkbox.type = "checkbox";
    checkbox.checked = product.available;

    checkbox.setAttribute(
      "aria-label",
      `Ubah ketersediaan ${product.name}`,
    );

    const availabilityText = document.createElement("span");

    availabilityText.textContent = product.available
      ? "Tersedia"
      : "Habis";

    const statusText = info.querySelector(
      ".product-stock-status",
    );

    checkbox.addEventListener("change", () => {
      updateAvailability(
        product,
        checkbox,
        statusText,
        availabilityText,
      );
    });

    availabilityLabel.append(
      checkbox,
      availabilityText,
    );

    /*
     * Tombol edit.
     */
    const editButton = createButton(
      "Edit",
      () => editProduct(product.id),
    );

    /*
     * Tombol hapus.
     */
    const deleteButton = createButton(
      "Hapus",
      () => deleteProduct(product.id),
      "table-action danger-action",
    );

    actions.append(
      availabilityLabel,
      editButton,
      deleteButton,
    );

    row.append(info, actions);

    return row;
  }

  /* -------------------------------------------------------
     RENDER KATEGORI
  ------------------------------------------------------- */

  for (const category of sortedCategories) {
    const categoryProducts = categoryMap.get(category);

    if (!categoryProducts?.length) {
      continue;
    }

    /*
     * Satu kategori = satu section.
     */
    const section = document.createElement("section");

    section.className = "product-category-group";

    section.setAttribute(
      "aria-label",
      `Kategori ${category}`,
    );

    /*
     * Header kategori.
     */
    const header = document.createElement("header");

    header.className = "product-category-heading";

    const title = document.createElement("h4");

    title.textContent = category;

    const categoryCount = document.createElement("span");

    categoryCount.className = "product-category-count";

    categoryCount.textContent =
      `${categoryProducts.length} produk`;

    header.append(title, categoryCount);

    /*
     * Card daftar produk dalam kategori.
     */
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
}

function editProduct(id) {
  const product = products.find(
    (item) => item.id === id,
  );

  if (!product || !productForm) return;

  editingProductId = product.id;

  productForm.elements.name.value = product.name;
  productForm.elements.category.value = product.category;
  productForm.elements.price.value = String(product.price);
  productForm.elements.unit.value = product.unit;
  
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

  productForm.elements.available.checked =
    product.available;

  const description = productForm.elements.description;

  if (description) {
    description.value = product.description || "";
  }

  $("#product-form-title").textContent = "Edit produk";

  productForm.querySelector('[type="submit"]').textContent =
    "Simpan perubahan";

  $("#cancel-product").hidden = false;

  productForm.scrollIntoView({
    behavior: "smooth",
    block: "start",
  });
}

async function deleteProduct(id) {
  const product = products.find(
    (item) => item.id === id,
  );

  if (!product) return;

  if (
    !window.confirm(
      `Hapus produk "${product.name}" dari katalog?`,
    )
  ) {
    return;
  }

  try {
    await postJSON(
      API.products,
      { id },
      "DELETE",
    );

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

    const formData = new FormData(productForm);

    const payload = {
      name: String(formData.get("name") || "").trim(),
      category: String(formData.get("category") || "").trim(),
      price: Number(formData.get("price")),
      unit: String(formData.get("unit") || "").trim(),
      description: String(
        formData.get("description") || "",
      ).trim(),
      available: formData.has("available"),
    };

    if (
      !payload.name ||
      !payload.category ||
      !payload.unit ||
      !Number.isSafeInteger(payload.price) ||
      payload.price < 1
    ) {
      notify("Periksa kembali informasi produk.");

      return;
    }

    /*
     * FormData dipakai agar file foto dapat dikirim.
     */
    const requestData = new FormData();

    requestData.set("name", payload.name);
    requestData.set("category", payload.category);
    requestData.set("price", String(payload.price));
    requestData.set("unit", payload.unit);
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
      const response = await fetch(API.products, {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",
        headers: {
          Accept: "application/json",
          "X-CSRF-Token": csrfToken,
        },
        body: requestData,
      });

      let data;

      try {
        data = await response.json();
      } catch {
        throw new Error("Respons server tidak valid.");
      }

      if (!response.ok || data.success === false) {
        if (response.status === 401) {
          window.location.href = "/warung/login.php";

          return;
        }

        throw new Error(
          data.message || "Produk gagal disimpan.",
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

/* =========================================================
   PESANAN
========================================================= */

let orders = [];

async function loadOrders() {
  const data = await apiRequest(API.orders);

  orders = data.orders;

  renderOrders();
}

function nextOrderStatus(status) {
  const next = {
    pending: {
      value: "confirmed",
      label: "Konfirmasi",
    },

    confirmed: {
      value: "processing",
      label: "Proses",
    },

    processing: {
      value: "completed",
      label: "Selesaikan",
    },
  };

  return next[status] || null;
}

function renderOrders() {
  const table = $("#orders-table");

  if (!table) return;

  table.replaceChildren();

  if (orders.length === 0) {
    const row = document.createElement("tr");
    const cell = createCell("Belum ada pesanan.");

    cell.colSpan = 6;
    cell.className = "empty-table-cell";

    row.append(cell);
    table.append(row);

    return;
  }

  for (const order of orders) {
    const row = document.createElement("tr");

    const itemSummary = (order.items || [])
      .map(
        (item) =>
          `${item.product_name} × ${item.quantity}`,
      )
      .join(", ");

    const status = document.createElement("span");

    status.className = `status-pill status-${order.status}`;
    status.textContent = order.status;

    const statusCell = document.createElement("td");

    statusCell.append(status);

    const actions = document.createElement("td");

    const next = nextOrderStatus(order.status);

    if (next) {
      actions.append(
        createButton(next.label, async () => {
          await changeOrderStatus(order, next.value);
        }),
      );
    }

    if (
      order.status === "pending"
      || order.status === "confirmed"
    ) {
      actions.append(
        createButton(
          "Batalkan",
          async () => {
            await changeOrderStatus(order, "cancelled");
          },
          "table-action danger-action",
        ),
      );
    }

    row.append(
      createCell(order.order_number, "order-id"),
      createCell(
        `${order.buyer_name}\n${order.buyer_phone}`,
      ),
      createCell(itemSummary || "Tidak ada rincian"),
      createCell(formatRupiah(order.total)),
      statusCell,
      actions,
    );

    table.append(row);
  }
}

async function changeOrderStatus(order, status) {
  const labels = {
    confirmed: "mengonfirmasi",
    processing: "memproses",
    completed: "menyelesaikan",
    cancelled: "membatalkan",
  };

  if (
    !window.confirm(
      `Anda yakin ingin ${labels[status]} pesanan ${order.order_number}?`,
    )
  ) {
    return;
  }

  try {
    await postJSON(API.orders, {
      id: order.id,
      status,
    });

    await Promise.all([
      loadOrders(),
      loadDashboard(),
    ]);

    notify("Status pesanan berhasil diperbarui.");
  } catch (error) {
    notify(error.message);
  }
}

/* =========================================================
   PENGATURAN
========================================================= */

const settingsForm = $("#store-settings-form");
const openToggle = $("#store-open-toggle");
const freeDeliveryToggle = $("#free-delivery-toggle");

async function loadSettings() {
  const data = await apiRequest(API.settings);

  const settings = data.settings;

  if (settingsForm) {
    for (const key of [
      "name",
      "owner",
      "phone",
      "address",
      "description",
    ]) {
      if (settingsForm.elements[key]) {
        settingsForm.elements[key].value =
          settings[key] || "";
      }
    }
  }

  if (openToggle) {
    openToggle.checked = settings.is_open;
  }

  if (freeDeliveryToggle) {
    freeDeliveryToggle.checked =
      settings.free_delivery;
  }

  updateSettingLabels(settings);
}

function updateSettingLabels(settings) {
  const openText = $("#store-toggle-text");
  const freeText = $("#free-delivery-toggle-text");

  if (openText) {
    openText.textContent = settings.is_open
      ? "Warung sedang buka"
      : "Warung sedang tutup";
  }

  if (freeText) {
    freeText.textContent = settings.free_delivery
      ? "Gratis ongkir aktif"
      : "Gratis ongkir tidak aktif";
  }
}

async function updateSettingStatus(key, value) {
  try {
    await postJSON(API.settings, {
      [key]: value,
    });

    await Promise.all([
      loadSettings(),
      loadDashboard(),
    ]);

    notify("Pengaturan berhasil disimpan.");
  } catch (error) {
    await loadSettings();
    notify(error.message);
  }
}

if (settingsForm) {
  settingsForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    
    const wasEditing = editingProductId !== null;
    const formData = new FormData(settingsForm);

    const payload = {
      name: String(formData.get("name") || "").trim(),
      owner: String(formData.get("owner") || "").trim(),
      phone: String(formData.get("phone") || "").trim(),
      address: String(formData.get("address") || "").trim(),
      description: String(
        formData.get("description") || "",
      ).trim(),
    };

    if (
      !payload.name
      || !payload.owner
      || !payload.address
    ) {
      notify("Nama warung, pemilik, dan alamat wajib diisi.");
      return;
    }

    try {
      const result = await postJSON(
        API.settings,
        payload,
      );

      $("#store-settings-feedback").textContent =
        result.message;

      await loadSettings();

      notify(result.message);
    } catch (error) {
      notify(error.message);
    }
  });
}

$("#reset-store-settings")?.addEventListener(
  "click",
  loadSettings,
);

openToggle?.addEventListener("change", () => {
  updateSettingStatus(
    "is_open",
    openToggle.checked,
  );
});

freeDeliveryToggle?.addEventListener("change", () => {
  updateSettingStatus(
    "free_delivery",
    freeDeliveryToggle.checked,
  );
});

/* =========================================================
   IKON TOKO
========================================================= */

const storeIconInput = $("#store-icon-input");
const storeIconPreview = $("#store-icon-preview");
const storeIconPlaceholder = $("#store-icon-placeholder");
const saveStoreIconButton = $("#save-store-icon");
const storeIconFeedback = $("#store-icon-feedback");

let selectedStoreIcon = null;

storeIconInput?.addEventListener("change", () => {
  const file = storeIconInput.files?.[0];

  selectedStoreIcon = null;

  if (saveStoreIconButton) {
    saveStoreIconButton.disabled = true;
  }

  if (!file) {
    return;
  }

  const allowedTypes = [
    "image/jpeg",
    "image/png",
    "image/webp",
  ];

  if (!allowedTypes.includes(file.type)) {
    storeIconFeedback.textContent =
      "Gunakan gambar JPG, PNG, atau WebP.";

    storeIconInput.value = "";

    return;
  }

  if (file.size > 2 * 1024 * 1024) {
    storeIconFeedback.textContent =
      "Ukuran gambar maksimal 2 MB.";

    storeIconInput.value = "";

    return;
  }

  const imageUrl = URL.createObjectURL(file);
  const image = new Image();

  image.onload = () => {
    URL.revokeObjectURL(imageUrl);

    if (image.width > 4096 || image.height > 4096) {
      storeIconFeedback.textContent =
        "Dimensi gambar maksimal 4096 × 4096 piksel.";

      storeIconInput.value = "";

      return;
    }

    selectedStoreIcon = file;

    storeIconPreview.src = URL.createObjectURL(file);
    storeIconPreview.hidden = false;
    storeIconPlaceholder.hidden = true;

    saveStoreIconButton.disabled = false;

    storeIconFeedback.textContent =
      "Gambar siap disimpan.";
  };

  image.onerror = () => {
    URL.revokeObjectURL(imageUrl);

    storeIconFeedback.textContent =
      "Gambar tidak dapat dibaca.";

    storeIconInput.value = "";
  };

  image.src = imageUrl;
});

saveStoreIconButton?.addEventListener("click", async () => {
  if (!selectedStoreIcon) {
    return;
  }

  const formData = new FormData();

  formData.set("image", selectedStoreIcon);
  formData.set("_token", csrfToken);

  saveStoreIconButton.disabled = true;
  saveStoreIconButton.textContent = "Menyimpan...";

  try {
    const response = await fetch(
      "/warung/api/store-icon.php",
      {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",

        headers: {
          Accept: "application/json",
          "X-CSRF-Token": csrfToken,
        },

        body: formData,
      },
    );

    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(
        result.message || "Ikon toko gagal disimpan.",
      );
    }

    storeIconFeedback.textContent = result.message;

    selectedStoreIcon = null;
    storeIconInput.value = "";

    storeIconPreview.src =
      `/${result.icon_image}?v=${Date.now()}`;

    saveStoreIconButton.disabled = true;

    notify(result.message);
  } catch (error) {
    storeIconFeedback.textContent =
      error instanceof Error
        ? error.message
        : "Ikon toko gagal disimpan.";
  } finally {
    saveStoreIconButton.textContent = "Simpan ikon toko";

    if (selectedStoreIcon) {
      saveStoreIconButton.disabled = false;
    }
  }
});

/* =========================================================
   NAVIGASI
========================================================= */

const sidebar = $("#sidebar");
const mobileMenu = $("#mobile-menu");

mobileMenu?.addEventListener("click", () => {
  const open = sidebar?.classList.toggle("open");

  mobileMenu.setAttribute(
    "aria-expanded",
    String(Boolean(open)),
  );
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    sidebar?.classList.remove("open");
    mobileMenu?.setAttribute("aria-expanded", "false");
  }
});

/* =========================================================
   INISIALISASI
========================================================= */

async function initializeDashboard() {
  try {
    if (page === "overview") {
      await loadDashboard();
    }

    if (page === "products") {
      await loadProducts();
    }

    if (page === "orders") {
      await loadOrders();
    }

    if (page === "settings") {
      await loadSettings();
    }
  } catch (error) {
    notify(error.message);
  }
}

initializeDashboard();