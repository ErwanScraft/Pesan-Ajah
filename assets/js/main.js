import { products } from "./data/products.js";
import { createCart } from "./modules/cart.js";
import { createCatalog } from "./modules/catalog.js";
import { showToast } from "./modules/toast.js";
import { formatRupiah } from "./modules/format.js";

const $ = (selector) => document.querySelector(selector);

const DELIVERY_FEE = 1000;

const toast = $("#toast");
const cartOverlay = $("#cart-overlay");
const cartButton = $("#cart-trigger");
const closeCartButton = $("#close-cart");
const cartItems = $("#cart-items");
const checkoutButton = $("#checkout-button");

const fulfillmentInputs = document.querySelectorAll(
  'input[name="fulfillment-method"]',
);

/*
 * Array products berasal dari modul data lama.
 * Array ini diisi dengan data hasil API MySQL.
 */
const cart = createCart(products);

const cartView = {
  count: $("#cart-count"),
  list: cartItems,
  empty: $("#cart-empty"),
  footer: $("#cart-footer"),
  totalLabel: $("#cart-subtotal"),
};

let catalog;
let csrfToken = "";
let catalogLoaded = false;

function selectedFulfillment() {
  return (
    document.querySelector(
      'input[name="fulfillment-method"]:checked',
    )?.value ?? "delivery"
  );
}

/*
 * Cari produk pertama dalam keranjang untuk mengetahui
 * warung yang sedang dipilih.
 */
function selectedStore() {
  const entry = cart.entries()[0];

  if (!entry) {
    return null;
  }

  return products.find(
    (product) => Number(product.id) === entry.product_id,
  ) ?? null;
}

function getDeliveryFee() {
  if (cart.quantity() === 0) {
    return 0;
  }

  if (selectedFulfillment() !== "delivery") {
    return 0;
  }

  const product = selectedStore();

  if (!product) {
    return DELIVERY_FEE;
  }

  return product.store_free_delivery
    ? 0
    : DELIVERY_FEE;
}

function renderCart() {
  cart.render(cartView);
  
  const cartCountLabel = $("#cart-count-label");

  if (cartCountLabel) {
    cartCountLabel.textContent = String(cart.quantity());
  }

  const deliveryFee = getDeliveryFee();
  const subtotal = cart.total();

  const deliveryOptionPrice = $("#delivery-option-price");

  if (deliveryOptionPrice) {
    if (selectedStore()?.store_free_delivery) {
      deliveryOptionPrice.innerHTML = `
        <span class="delivery-price-original">
          ${formatRupiah(DELIVERY_FEE)}
        </span>
      `;
    } else {
      deliveryOptionPrice.textContent = formatRupiah(
        DELIVERY_FEE,
      );
    }
  }

  const deliveryFeeElement = $("#delivery-fee");

  if (deliveryFeeElement) {
    deliveryFeeElement.textContent = formatRupiah(
      deliveryFee,
    );
  }

  const totalElement = $("#cart-total");

  if (totalElement) {
    totalElement.textContent = formatRupiah(
      subtotal + deliveryFee,
    );
  }

  const fulfillmentNote = $("#fulfillment-note");

  if (fulfillmentNote) {
    if (selectedFulfillment() === "pickup") {
      fulfillmentNote.textContent =
        "Ambil sendiri di warung tanpa biaya pengantaran.";
    } else if (selectedStore()?.store_free_delivery) {
      fulfillmentNote.textContent =
        "Biaya pengantaran ditanggung sesuai pengaturan warung.";
    } else {
      fulfillmentNote.textContent =
        "Ongkir ditetapkan aplikasi sebesar Rp1.000 per pengantaran.";
    }
  }

  if (checkoutButton) {
    checkoutButton.disabled =
      !catalogLoaded || cart.quantity() === 0;
  }
}

function addToCart(productId) {
  const product = products.find(
    (item) => Number(item.id) === Number(productId),
  );

  if (!product || !product.available) {
    showToast(toast, "Produk tidak tersedia.");
    return;
  }

  const currentStore = selectedStore();

  /*
   * Satu pesanan hanya boleh berasal dari satu warung.
   */
  if (
    currentStore
    && Number(currentStore.store_id) !== Number(product.store_id)
  ) {
    showToast(
      toast,
      "Keranjang hanya bisa berisi produk dari satu warung. Selesaikan atau kosongkan keranjang terlebih dahulu.",
    );

    return;
  }

  const addedProduct = cart.add(productId);

  if (!addedProduct) {
    showToast(
      toast,
      "Jumlah produk sudah mencapai batas atau produk tidak tersedia.",
    );

    return;
  }

  catalog?.render();
  renderCart();

  showToast(
    toast,
    `${product.name} ditambahkan ke keranjang.`,
  );
}

function openCart() {
  if (!cartOverlay) {
    return;
  }

  cartOverlay.hidden = false;

  // Aktifkan overlay dan animasi drawer.
  requestAnimationFrame(() => {
    cartOverlay.classList.add("active");
  });

  document.body.classList.add("no-scroll");

  closeCartButton?.focus();
}

function closeCart() {
  if (!cartOverlay) {
    return;
  }

  cartOverlay.classList.remove("active");

  document.body.classList.remove("no-scroll");

  cartOverlay.hidden = true;

  cartButton?.focus();
}

/*
|--------------------------------------------------------------------------
| Muat CSRF dan katalog dari MySQL
|--------------------------------------------------------------------------
*/

async function loadCsrfToken() {
  const response = await fetch("/api/csrf.php", {
    method: "GET",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      Accept: "application/json",
    },
  });

  const data = await response.json();

  if (!response.ok || !data.success || !data.csrf_token) {
    throw new Error(
      data.message || "Token keamanan tidak dapat dimuat.",
    );
  }

  csrfToken = data.csrf_token;
}

async function loadCatalog() {
  const storeId = document.body.dataset.storeId;

  const catalogUrl = new URL(
    "/api/catalog.php",
    window.location.origin,
  );

  if (storeId) {
    catalogUrl.searchParams.set("store_id", storeId);
  }

  const response = await fetch(catalogUrl, {
    method: "GET",
    credentials: "same-origin",
    cache: "no-store",
    headers: {
      Accept: "application/json",
    },
  });

  const data = await response.json();

  if (!response.ok || !data.success) {
    throw new Error(
      data.message || "Katalog gagal dimuat.",
    );
  }

  const normalizedProducts = (data.products || []).map(
    (product) => ({
      ...product,

      id: Number(product.id),
      store_id: Number(product.store_id),
      price: Number(product.price),

      store: String(product.store_name || "Warung"),

      available: Boolean(product.available),

      store_is_open: Boolean(product.store_is_open),

      store_free_delivery: Boolean(
        product.store_free_delivery,
      ),

      emoji: product.emoji || "📦",
      tag: product.tag || "",
    }),
  );

  /*
   * Mutasi array yang diimpor, bukan mengganti referensinya.
   * Cart dan catalog sama-sama memakai array ini.
   */
  products.splice(
    0,
    products.length,
    ...normalizedProducts,
  );

  catalogLoaded = true;

  catalog.render();
  renderCart();

  if (normalizedProducts.length === 0) {
    showToast(
      toast,
      "Belum ada produk tersedia dari warung aktif.",
    );
  }
}

/*
|--------------------------------------------------------------------------
| Checkout
|--------------------------------------------------------------------------
*/

function setLocationStatus(message, type = "") {
  const element = $("#location-status");

  if (!element) {
    return;
  }

  element.textContent = message;
  element.classList.remove("is-success", "is-error");

  if (type === "success") {
    element.classList.add("is-success");
  } else if (type === "error") {
    element.classList.add("is-error");
  }
}

function updateLocationPreview() {
  const urlInput = $("#delivery-maps-url");
  const preview = $("#preview-location-link");

  if (!urlInput || !preview) {
    return;
  }

  const value = urlInput.value.trim();

  if (!value) {
    preview.hidden = true;
    preview.removeAttribute("href");
    return;
  }

  try {
    const url = new URL(value);

    const allowedHosts = [
      "google.com",
      "www.google.com",
      "maps.google.com",
      "maps.app.goo.gl",
      "goo.gl",
    ];

    const validHost =
      allowedHosts.includes(url.hostname.toLowerCase()) ||
      url.hostname.toLowerCase().endsWith(".google.com");

    if (url.protocol !== "https:" || !validHost) {
      preview.hidden = true;
      preview.removeAttribute("href");
      return;
    }

    preview.href = url.href;
    preview.hidden = false;
  } catch {
    preview.hidden = true;
    preview.removeAttribute("href");
  }
}

function toggleDeliveryFields() {
  const section = $("#delivery-address-section");
  const isDelivery = selectedFulfillment() === "delivery";

  if (section) {
    section.hidden = !isDelivery;
  }

  const address = $("#delivery-address");

  if (address) {
    address.required = isDelivery;
  }

  if (!isDelivery) {
    const urlInput = $("#delivery-maps-url");
    const latitude = $("#delivery-latitude");
    const longitude = $("#delivery-longitude");

    if (urlInput) urlInput.value = "";
    if (latitude) latitude.value = "";
    if (longitude) longitude.value = "";

    updateLocationPreview();
    setLocationStatus("Lokasi tidak diperlukan untuk ambil sendiri.");
  } else {
    setLocationStatus("Belum ada lokasi yang dipilih.");
  }
}

function validMapsUrl(value) {
  if (!value) {
    return true;
  }

  try {
    const url = new URL(value);

    const host = url.hostname.toLowerCase();

    const allowedHosts = [
      "google.com",
      "www.google.com",
      "maps.google.com",
      "maps.app.goo.gl",
      "goo.gl",
    ];

    return (
      url.protocol === "https:" &&
      (
        allowedHosts.includes(host) ||
        host.endsWith(".google.com")
      )
    );
  } catch {
    return false;
  }
}

async function useCurrentLocation() {
  if (!navigator.geolocation) {
    setLocationStatus(
      "Browser tidak mendukung akses lokasi. Tempel tautan Google Maps secara manual.",
      "error",
    );

    return;
  }

  const button = $("#use-location-button");

  if (button) {
    button.disabled = true;
    button.textContent = "Mengambil lokasi...";
  }

  setLocationStatus("Meminta izin akses lokasi...");

  try {
    const position = await new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(
        resolve,
        reject,
        {
          enableHighAccuracy: true,
          timeout: 15000,
          maximumAge: 0,
        },
      );
    });

    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;

    $("#delivery-latitude").value = latitude.toFixed(7);
    $("#delivery-longitude").value = longitude.toFixed(7);

    $("#delivery-maps-url").value =
      `https://maps.google.com/?q=${latitude},${longitude}`;

    updateLocationPreview();

    setLocationStatus(
      `Lokasi berhasil dipilih: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}.`,
      "success",
    );
  } catch (error) {
    let message = "Lokasi tidak berhasil diperoleh.";

    if (error?.code === 1) {
      message =
        "Izin lokasi ditolak. Aktifkan izin lokasi atau tempel tautan Google Maps.";
    } else if (error?.code === 2) {
      message =
        "Lokasi tidak tersedia. Periksa GPS atau gunakan tautan Google Maps.";
    } else if (error?.code === 3) {
      message =
        "Pengambilan lokasi melewati batas waktu. Silakan coba lagi.";
    }

    setLocationStatus(message, "error");
  } finally {
    if (button) {
      button.disabled = false;
      button.textContent = "Gunakan Lokasi Saya";
    }
  }
}

async function submitCheckout() {
  if (cart.quantity() === 0) {
    showToast(toast, "Keranjang masih kosong.");
    return;
  }

  const product = selectedStore();

  if (!product) {
    showToast(toast, "Warung pada keranjang tidak ditemukan.");
    return;
  }

  if (!product.store_is_open) {
    showToast(toast, "Warung sedang tutup.");
    return;
  }

  const buyerName = $("#buyer-name")?.value.trim() ?? "";
  const buyerPhone = $("#buyer-phone")?.value.trim() ?? "";

  const deliveryMethod = selectedFulfillment();

  const deliveryAddress =
    $("#delivery-address")?.value.trim() ?? "";

  const deliveryMapsUrl =
    $("#delivery-maps-url")?.value.trim() ?? "";

  const deliveryLatitude =
    $("#delivery-latitude")?.value.trim() ?? "";

  const deliveryLongitude =
    $("#delivery-longitude")?.value.trim() ?? "";

  if (!buyerName || buyerName.length > 100) {
    showToast(toast, "Nama pembeli wajib diisi.");
    $("#buyer-name")?.focus();
    return;
  }

  if (!buyerPhone || buyerPhone.length > 25) {
    showToast(toast, "Nomor WhatsApp wajib diisi.");
    $("#buyer-phone")?.focus();
    return;
  }

  if (
    deliveryMethod === "delivery" &&
    (!deliveryAddress || deliveryAddress.length > 255)
  ) {
    showToast(
      toast,
      "Alamat pengantaran wajib diisi dan maksimal 255 karakter.",
    );

    $("#delivery-address")?.focus();
    return;
  }

  if (
    deliveryMethod === "delivery" &&
    !validMapsUrl(deliveryMapsUrl)
  ) {
    showToast(
      toast,
      "Tautan lokasi harus berupa tautan Google Maps yang valid.",
    );

    $("#delivery-maps-url")?.focus();
    return;
  }

  if (
    (deliveryLatitude && !deliveryLongitude) ||
    (!deliveryLatitude && deliveryLongitude)
  ) {
    showToast(toast, "Koordinat lokasi tidak lengkap.");
    return;
  }

  /*
   * Buka tab kosong langsung dari aksi klik.
   * WhatsApp baru dibuka setelah database mengonfirmasi
   * bahwa pesanan berhasil disimpan.
   */
  const whatsappWindow = window.open("about:blank", "_blank");

  checkoutButton.disabled = true;

  const originalText = checkoutButton.textContent;

  checkoutButton.textContent = "Menyimpan pesanan...";

  try {
    if (!csrfToken) {
      await loadCsrfToken();
    }

    const payload = {
      _token: csrfToken,

      store_id: product.store_id,

      buyer_name: buyerName,
      buyer_phone: buyerPhone,

      delivery_method: deliveryMethod,

      delivery_address:
        deliveryMethod === "delivery"
          ? deliveryAddress
          : "",

      delivery_maps_url:
        deliveryMethod === "delivery"
          ? deliveryMapsUrl
          : "",

      delivery_latitude:
        deliveryMethod === "delivery"
          ? deliveryLatitude
          : "",

      delivery_longitude:
        deliveryMethod === "delivery"
          ? deliveryLongitude
          : "",

      items: cart.entries(),
    };

    const response = await fetch("/api/checkout.php", {
      method: "POST",
      credentials: "same-origin",
      cache: "no-store",

      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },

      body: JSON.stringify(payload),
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(
        result.message || "Checkout gagal diproses.",
      );
    }

    /*
     * Simpan token agar pembeli dapat membuka
     * Dashboard Buyer setelah meninggalkan halaman.
     */
    if (
      typeof result.buyer_token !== "string" ||
      !/^[a-f0-9]{64}$/i.test(result.buyer_token)
    ) {
      throw new Error(
        "Pesanan tersimpan, tetapi token pelacakan tidak diterima. Simpan nomor pesanan: " +
          result.order_number,
      );
    }

    const storageKey = "pesan_ajah_order_tokens";

    let savedOrders = [];

    try {
      const stored = JSON.parse(
        localStorage.getItem(storageKey) || "[]",
      );

      if (Array.isArray(stored)) {
        savedOrders = stored;
      }
    } catch {
      savedOrders = [];
    }

    savedOrders = savedOrders.filter(
      (order) =>
        order &&
        order.order_number !== result.order_number,
    );

    savedOrders.unshift({
      token: result.buyer_token,
      order_number: result.order_number,
      created_at: new Date().toISOString(),
    });

    /*
     * Simpan paling banyak 50 token pesanan pada browser.
     */
    savedOrders = savedOrders.slice(0, 50);

    try {
      localStorage.setItem(
        storageKey,
        JSON.stringify(savedOrders),
      );
    } catch {
      showToast(
        toast,
        "Pesanan tersimpan, tetapi browser tidak dapat menyimpan riwayat. Buka Dashboard Buyer setelah checkout ini.",
      );
    }

    cart.clear();

    renderCart();
    catalog.render();
    closeCart();

    showToast(
      toast,
      `Pesanan ${result.order_number} tersimpan dengan status menunggu konfirmasi.`,
    );

    if (result.whatsapp_url) {
      if (whatsappWindow && !whatsappWindow.closed) {
        whatsappWindow.location.href = result.whatsapp_url;
      } else {
        window.location.href = result.whatsapp_url;
      }
    } else {
      whatsappWindow?.close();
    }
  } catch (error) {
    whatsappWindow?.close();

    showToast(
      toast,
      error instanceof Error
        ? error.message
        : "Terjadi kesalahan saat checkout.",
    );
  } finally {
    checkoutButton.disabled =
      !catalogLoaded || cart.quantity() === 0;

    checkoutButton.textContent = originalText;
  }
}

/*
|--------------------------------------------------------------------------
| Event listener
|--------------------------------------------------------------------------
*/

$("#search-form")?.addEventListener(
  "submit",
  (event) => {
    event.preventDefault();

    catalog?.setQuery($("#search-input").value);

    $("#products")?.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  },
);

$("#reset-search")?.addEventListener(
  "click",
  () => catalog?.reset(),
);

cartButton?.addEventListener("click", openCart);

closeCartButton?.addEventListener(
  "click",
  closeCart,
);

$("#empty-cart-close")?.addEventListener(
  "click",
  closeCart,
);

$("#use-location-button")?.addEventListener(
  "click",
  useCurrentLocation,
);

$("#delivery-maps-url")?.addEventListener(
  "input",
  updateLocationPreview,
);

fulfillmentInputs.forEach((input) => {
  input.addEventListener("change", () => {
    toggleDeliveryFields();
    renderCart();
  });
});

toggleDeliveryFields();

cartItems?.addEventListener("click", (event) => {
  const addButton = event.target.closest("[data-add]");

  const removeButton = event.target.closest("[data-remove]");

  if (addButton) {
    addToCart(Number(addButton.dataset.add));
    return;
  }

  if (removeButton) {
    cart.change(
      Number(removeButton.dataset.remove),
      -1,
    );

    catalog?.render();
    renderCart();
  }
});

cartOverlay?.addEventListener("click", (event) => {
  if (event.target === cartOverlay) {
    closeCart();
  }
});

document.addEventListener("keydown", (event) => {
  if (
    event.key === "Escape"
    && cartOverlay
    && !cartOverlay.hidden
  ) {
    closeCart();
  }
});

checkoutButton?.addEventListener(
  "click",
  submitCheckout,
);

const currentYear = $("#current-year");

if (currentYear) {
  currentYear.textContent = String(
    new Date().getFullYear(),
  );
}

/*
|--------------------------------------------------------------------------
| Inisialisasi
|--------------------------------------------------------------------------
*/

catalog = createCatalog({
  products,

  grid: $("#product-grid"),
  total: $("#product-total"),
  empty: $("#empty-state"),
  categoryList: $("#category-list"),
  searchInput: $("#search-input"),

  cart,

  onAdd: addToCart,
});

catalog.render();
renderCart();

async function initializeMarketplace() {
  try {
    await Promise.all([
      loadCsrfToken(),
      loadCatalog(),
    ]);
  } catch (error) {
    console.error(
      "[Pesan Ajah] Gagal menginisialisasi marketplace:",
      error,
    );

    showToast(
      toast,
      error instanceof Error
        ? error.message
        : "Marketplace gagal dimuat.",
    );
  }
}

initializeMarketplace();